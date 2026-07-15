<?php

namespace Tests\Feature;

use App\Models\AttendancePunchLog;
use App\Models\AttendanceStation;
use App\Models\Employee;
use App\Models\User;
use App\Services\FaceEmbeddingService;
use App\Services\GeoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The geo layer: stations, the punch tag, and the HR monitor.
 *
 * The face fixtures mirror AttendancePortalTest: a person is a random 128-vector
 * and a head turn adds a shared pose direction, which is the property the
 * server's liveness check verifies.
 */
class AttendanceGeoTest extends TestCase
{
    use DatabaseTransactions;

    private const POSE_STRENGTH = 0.15;

    /** Mabinay municipal hall, roughly. */
    private const HALL_LAT = 9.7292000;
    private const HALL_LNG = 122.9080000;

    private Employee $alice;
    private FaceEmbeddingService $faces;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->faces = app(FaceEmbeddingService::class);
        $this->alice = Employee::orderBy('id')->firstOrFail();
        $this->alice->forceFill(['stat_1' => 1])->save();
    }

    // ---------------------------------------------------------------- fixtures

    private function randomVector(int $seed): array
    {
        mt_srand($seed);

        $vector = [];

        for ($i = 0; $i < 128; $i++) {
            $vector[] = (mt_rand() / mt_getrandmax()) - 0.5;
        }

        return $vector;
    }

    private function frame(int $person, ?string $pose, int $jitter, float $amplitude = 0.03): array
    {
        $vector = $this->randomVector($person);

        if ($pose !== null) {
            $direction = $this->randomVector($pose === 'left' ? 770001 : 770002);

            for ($i = 0; $i < 128; $i++) {
                $vector[$i] += self::POSE_STRENGTH * $direction[$i];
            }
        }

        $noise = $this->randomVector($jitter);

        for ($i = 0; $i < 128; $i++) {
            $vector[$i] += $noise[$i] * $amplitude * 2;
        }

        return $vector;
    }

    private function enrol(Employee $employee, int $person): void
    {
        $captures = [
            ['type' => 'front',    'embedding' => $this->frame($person, null,    $person + 1, 0.02)],
            ['type' => 'left',     'embedding' => $this->frame($person, 'left',  $person + 2, 0.02)],
            ['type' => 'right',    'embedding' => $this->frame($person, 'right', $person + 3, 0.02)],
            ['type' => 'movement', 'embedding' => $this->frame($person, null,    $person + 4, 0.03)],
        ];

        $master = $this->faces->masterEmbedding(array_column($captures, 'embedding'));

        $employee->face_embeddings = $this->faces->payload($captures, $master, 1, 'Test HR');
        $employee->save();

        $this->faces->storeVector($employee->id, $master);
    }

    /** A live punch, optionally carrying a GPS fix. */
    private function livePunch(int $person, ?array $geo)
    {
        $challenge = $this->postJson(route('attendanceChallenge'))->assertOk()->json('challenge');

        // Frontal-only: a run of straight-ahead frames, no head turns.
        $frames = [];
        $t      = 0;

        for ($i = 0; $i < 5; $i++) {
            $frames[] = ['stage' => 'neutral', 'pose' => null, 't' => $t, 'descriptor' => $this->frame($person, null, $person + 100 + $i, 0.05)];
            $t += 400;
        }

        return $this->postJson(route('attendancePunch'), [
            'mode'   => 'face',
            'action' => 'in',
            'nonce'  => $challenge['nonce'],
            'frames' => $frames,
            'geo'    => $geo,
        ]);
    }

    private function admin(): User
    {
        return User::where('role', 'Administrator')->firstOrFail();
    }

    private function station(array $overrides = []): AttendanceStation
    {
        return AttendanceStation::create(array_merge([
            'name'     => 'Municipal Hall',
            'lat'      => self::HALL_LAT,
            'lng'      => self::HALL_LNG,
            'radius_m' => 200,
            'active'   => true,
        ], $overrides));
    }

    // ---------------------------------------------------------------- geometry

    public function test_haversine_distance_is_sane(): void
    {
        $geo = app(GeoService::class);

        // ~0.001° of latitude is ~111 m, everywhere on Earth.
        $d = $geo->distanceMeters(self::HALL_LAT, self::HALL_LNG, self::HALL_LAT + 0.001, self::HALL_LNG);

        $this->assertEqualsWithDelta(111.0, $d, 2.0);
        $this->assertSame(0.0, $geo->distanceMeters(self::HALL_LAT, self::HALL_LNG, self::HALL_LAT, self::HALL_LNG));
    }

    // ---------------------------------------------------------------- tagging

    public function test_a_punch_inside_a_station_is_tagged_in_range(): void
    {
        $this->enrol($this->alice, 500);
        $this->station();

        $this->livePunch(500, ['lat' => self::HALL_LAT + 0.0003, 'lng' => self::HALL_LNG, 'accuracy' => 12])
            ->assertOk()
            ->assertJsonPath('location.out_of_range', false)
            ->assertJsonPath('location.station_name', 'Municipal Hall');

        $log = AttendancePunchLog::where('emp_ID', $this->alice->emp_ID)->latest('id')->firstOrFail();

        $this->assertFalse($log->out_of_range);
        $this->assertSame('Municipal Hall', $log->station_name);
        $this->assertLessThan(100, $log->distance_m);
        $this->assertSame(12, $log->accuracy_m);
    }

    public function test_a_punch_far_from_every_station_is_flagged_but_still_recorded(): void
    {
        $this->enrol($this->alice, 510);
        $this->station();

        // ~5.5 km north of the hall.
        $response = $this->livePunch(510, ['lat' => self::HALL_LAT + 0.05, 'lng' => self::HALL_LNG, 'accuracy' => 8])
            ->assertOk()
            ->assertJsonPath('recorded', true)
            ->assertJsonPath('location.out_of_range', true);

        $this->assertGreaterThan(5000, $response->json('location.distance_m'));

        $log = AttendancePunchLog::where('emp_ID', $this->alice->emp_ID)->latest('id')->firstOrFail();

        $this->assertTrue($log->out_of_range);
        $this->assertGreaterThan(5000, $log->distance_m);
    }

    public function test_the_nearest_station_wins_when_several_exist(): void
    {
        $this->enrol($this->alice, 520);
        $this->station();
        $this->station(['name' => 'North Annex', 'lat' => self::HALL_LAT + 0.02]);

        // Right next to the annex, far from the hall.
        $this->livePunch(520, ['lat' => self::HALL_LAT + 0.0201, 'lng' => self::HALL_LNG])
            ->assertOk()
            ->assertJsonPath('location.station_name', 'North Annex')
            ->assertJsonPath('location.out_of_range', false);
    }

    public function test_an_inactive_station_does_not_count(): void
    {
        $this->enrol($this->alice, 530);
        $this->station(['active' => false]);

        // Standing exactly on the disabled station: nothing to compare against.
        $this->livePunch(530, ['lat' => self::HALL_LAT, 'lng' => self::HALL_LNG])
            ->assertOk()
            ->assertJsonPath('location.station_name', null)
            ->assertJsonPath('location.out_of_range', null);
    }

    public function test_a_punch_without_location_is_recorded_and_marked_unlocated(): void
    {
        $this->enrol($this->alice, 540);
        $this->station();

        $this->livePunch(540, null)
            ->assertOk()
            ->assertJsonPath('recorded', true)
            ->assertJsonPath('location.has_location', false)
            ->assertJsonPath('location.out_of_range', null);

        $log = AttendancePunchLog::where('emp_ID', $this->alice->emp_ID)->latest('id')->firstOrFail();

        $this->assertNull($log->lat);
        $this->assertNull($log->out_of_range);
    }

    public function test_no_stations_configured_means_no_flag_either_way(): void
    {
        $this->enrol($this->alice, 550);

        $this->livePunch(550, ['lat' => self::HALL_LAT, 'lng' => self::HALL_LNG])
            ->assertOk()
            ->assertJsonPath('location.out_of_range', null);
    }

    public function test_nonsense_coordinates_are_rejected(): void
    {
        $this->enrol($this->alice, 560);

        $this->livePunch(560, ['lat' => 200, 'lng' => 0])->assertStatus(422);
    }

    // ---------------------------------------------------------------- stations CRUD

    public function test_admin_can_create_update_and_delete_a_station(): void
    {
        $this->actingAs($this->admin(), 'web');

        $this->post(route('stationStore'), [
            'name' => 'Main Gate', 'lat' => self::HALL_LAT, 'lng' => self::HALL_LNG, 'radius_m' => 100, 'active' => 1,
        ])->assertRedirect();

        $station = AttendanceStation::where('name', 'Main Gate')->firstOrFail();

        $this->post(route('stationUpdate', $station->id), [
            'name' => 'Main Gate', 'lat' => self::HALL_LAT, 'lng' => self::HALL_LNG, 'radius_m' => 300, 'active' => 0,
        ])->assertRedirect();

        $station->refresh();
        $this->assertSame(300, $station->radius_m);
        $this->assertFalse($station->active);

        $this->post(route('stationDelete', $station->id))->assertRedirect();
        $this->assertDatabaseMissing('attendance_stations', ['id' => $station->id]);
    }

    public function test_an_employee_cannot_manage_stations_or_open_the_monitor(): void
    {
        $this->actingAs($this->alice, 'employee');

        $this->post(route('stationStore'), [
            'name' => 'Rogue', 'lat' => 0, 'lng' => 0, 'radius_m' => 100,
        ])->assertStatus(403);

        $this->get(route('attendanceMonitor'))->assertStatus(403);
    }

    public function test_the_settings_page_shows_the_stations_group(): void
    {
        $this->station();

        $this->actingAs($this->admin(), 'web');

        $this->get(route('settings'))
            ->assertOk()
            ->assertSee('Attendance Stations')
            ->assertSee('Municipal Hall')
            ->assertSee(route('stationStore'));
    }

    // ---------------------------------------------------------------- monitor

    public function test_the_monitor_lists_todays_punches_with_their_flags(): void
    {
        $this->enrol($this->alice, 570);
        $this->station();

        $this->livePunch(570, ['lat' => self::HALL_LAT + 0.05, 'lng' => self::HALL_LNG]);

        $this->actingAs($this->admin(), 'web');

        $this->get(route('attendanceMonitor'))
            ->assertOk()
            ->assertSee('FACE ATTENDANCE MONITOR')
            ->assertSee(trim("{$this->alice->fname} {$this->alice->lname}"))
            ->assertSee('from Municipal Hall')
            ->assertSee('1 out of range');
    }
}
