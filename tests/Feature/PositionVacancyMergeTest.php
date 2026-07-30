<?php
namespace Tests\Feature;

use App\Models\JobHiring;
use App\Models\PositionDescription;
use App\Models\User;
use Tests\TestCase;

/**
 * The Position Description and the vacancy are now one screen: saving the
 * position also publishes it, copying the descriptive fields onto the posting.
 */
class PositionVacancyMergeTest extends TestCase
{
    private const ITEM = 'MERGE-TEST-1';

    private function admin(): User
    {
        return User::where('role', 'Administrator')->firstOrFail();
    }

    /** Each test builds its own position, so none depends on another's leftovers. */
    private function makePosition(array $publication = []): PositionDescription
    {
        $this->actingAs($this->admin(), 'web')->post(route('positionDescriptionStore'), array_merge([
            'position_title' => 'Merge Test Officer',
            'item_number'    => self::ITEM,
            'bureau_office'  => 'HRMO',
            'qs_education'   => "Bachelor's degree",
            'qs_eligibility' => 'CS Professional',
            'qs_training'    => '4 hours',
            'qs_experience'  => '1 year',
        ], $publication))->assertRedirect();

        return PositionDescription::where('item_number', self::ITEM)->firstOrFail();
    }

    private function publication(array $overrides = []): array
    {
        return array_merge([
            'type'           => 'Permanent',
            'salary'         => 27000,
            'posted_at'      => '2026-07-01',
            'expiration_at'  => '2026-07-20',
            'vacancy_status' => 'Open',
        ], $overrides);
    }

    public function test_saving_a_position_with_publication_dates_creates_the_posting(): void
    {
        $pd = $this->makePosition($this->publication());
        $posting = $pd->postings()->firstOrFail();

        // Descriptive fields are snapshotted onto the posting, not retyped.
        $this->assertSame('Merge Test Officer', $posting->title);
        $this->assertSame(self::ITEM, $posting->plantilla_item_no);
        $this->assertSame('HRMO', $posting->assignment);
        $this->assertSame("Bachelor's degree", $posting->education);
        $this->assertSame('CS Professional', $posting->eligibility);
        $this->assertSame('Open', $posting->status);
        $this->assertSame(1, $pd->postings()->count());
    }

    public function test_a_position_can_be_saved_without_publishing_it(): void
    {
        $pd = $this->makePosition();

        $this->assertSame(0, $pd->postings()->count(), 'no dates means no vacancy');
    }

    public function test_saving_again_edits_the_same_round(): void
    {
        $pd = $this->makePosition($this->publication());

        $this->actingAs($this->admin(), 'web')->post(route('positionDescriptionUpdate', $pd->id), array_merge([
            'position_title' => 'Merge Test Officer',
            'item_number'    => self::ITEM,
        ], $this->publication(['expiration_at' => '2026-07-25', 'vacancy_status' => 'Closed'])))
            ->assertRedirect();

        $this->assertSame(1, $pd->postings()->count(), 'should edit, not add a round');
        $this->assertSame('Closed', $pd->postings()->first()->status);
    }

    public function test_new_round_publishes_a_second_posting(): void
    {
        $pd = $this->makePosition($this->publication());

        $this->actingAs($this->admin(), 'web')->post(route('positionDescriptionUpdate', $pd->id), array_merge([
            'position_title' => 'Merge Test Officer',
            'item_number'    => self::ITEM,
        ], $this->publication([
            'posted_at' => '2028-01-05', 'expiration_at' => '2028-01-25', 'new_round' => 1,
        ])))->assertRedirect();

        $this->assertSame(2, $pd->postings()->count(), 'a new round should be a separate posting');
        $this->assertStringStartsWith('2028-01-05', (string) $pd->latestPosting->posted_at);
    }

    public function test_closing_date_before_posting_date_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'web')->post(route('positionDescriptionStore'), [
            'position_title' => 'Bad Dates Officer',
            'type'           => 'Permanent',
            'posted_at'      => '2026-07-20',
            'expiration_at'  => '2026-07-01',
            'vacancy_status' => 'Open',
        ])->assertSessionHasErrors('expiration_at');
    }

    public function test_the_old_job_openings_url_redirects_to_positions(): void
    {
        $this->actingAs($this->admin(), 'web')
            ->get(route('jlist'))
            ->assertRedirect(route('positionDescriptionList'));
    }

    protected function tearDown(): void
    {
        foreach (PositionDescription::whereIn('item_number', [self::ITEM])
            ->orWhere('position_title', 'Bad Dates Officer')->get() as $pd) {
            JobHiring::where('position_description_id', $pd->id)->delete();
            $pd->delete();
        }
        parent::tearDown();
    }
}
