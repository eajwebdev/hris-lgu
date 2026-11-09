<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every page of the web app, opened by each kind of user: none of them may
 * answer with a server error. A 403 or a redirect is a fine answer for a page
 * that person may not open; a 500 never is.
 */
class RouteSmokeTest extends TestCase
{
    use DatabaseTransactions;

    /** Routes that are not pages: downloads of external files, logouts, long-polls. */
    private const SKIP = [
        'logout', 'google.login', 'google.callback', 'auth.google', 'auth.google.callback',
    ];

    public static function users(): array
    {
        return [
            'Administrator' => ['admin'],
            'HR Administrator' => ['hr'],
            'employee' => ['employee'],
            'Mayor' => ['mayor'],
        ];
    }

    #[DataProvider('users')]
    public function test_no_page_fails_with_a_server_error(string $who): void
    {
        $employees = Employee::where('emp_status', 1)->orderBy('id')->take(3)->get();
        [$employee, $supervisor, $mayor] = $employees->all();

        $employee->supervisor = $supervisor->id;
        $employee->save();
        $mayor->emp_status = 5;
        $mayor->save();
        Setting::query()->update(['hr' => $supervisor->id, 'mayor' => $mayor->id]);

        $actor = match ($who) {
            'admin' => [User::where('role', 'Administrator')->firstOrFail(), 'web'],
            'hr' => [User::where('role', 'HR Administrator')->firstOrFail(), 'web'],
            'employee' => [$employee, 'employee'],
            'mayor' => [$mayor, 'employee'],
        };

        $failures = [];

        foreach (Route::getRoutes() as $route) {
            if (!in_array('GET', $route->methods(), true)) {
                continue;
            }
            $uri = $route->uri();
            if (str_starts_with($uri, 'api/') || str_starts_with($uri, '_') || str_starts_with($uri, 'sanctum')
                || str_starts_with($uri, 'storage') || str_starts_with($uri, 'up')
                || in_array($route->getName(), self::SKIP, true)) {
                continue;
            }
            if (!in_array('login_auth', $route->gatherMiddleware(), true)) {
                continue;
            }

            // Fill required parameters with the employee's id: most pages
            // take an employee id; the rest answer 404, which is fine here.
            $path = preg_replace_callback('/\{(\w+)(\?)?\}/', function ($m) use ($employee) {
                return $m[2] ?? false ? '' : (string) $employee->id;
            }, $uri);
            $path = '/' . trim(preg_replace('#/+#', '/', $path), '/');

            $this->app['auth']->forgetGuards();
            $this->actingAs($actor[0], $actor[1]);

            try {
                $status = $this->get($path)->getStatusCode();
            } catch (\Throwable $e) {
                $failures[] = "$path threw " . get_class($e) . ': ' . $e->getMessage();
                continue;
            }

            if ($status >= 500) {
                $failures[] = "$path answered $status";
            }
        }

        $this->assertSame([], $failures, "Pages failing for $who:\n" . implode("\n", $failures));
    }
}
