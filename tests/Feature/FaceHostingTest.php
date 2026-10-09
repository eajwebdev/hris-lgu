<?php

namespace Tests\Feature;

use Illuminate\Support\Env;
use Tests\TestCase;

class FaceHostingTest extends TestCase
{
    private function profile(?string $runtime, bool $registration, bool $punch): array
    {
        $repository = Env::getRepository();
        $values = ['FACE_RUNTIME' => $runtime, 'FACE_SCORING_ENABLED' => $registration ? 'true' : 'false', 'FACE_PUNCH_SCORING_ENABLED' => $punch ? 'true' : 'false'];
        $previous = [];
        foreach ($values as $key => $value) {
            $previous[$key] = $repository->get($key);
            $value === null ? $repository->clear($key) : $repository->set($key, $value);
        }
        try {
            return require config_path('face.php');
        } finally {
            foreach ($previous as $key => $value) {
                $value === null ? $repository->clear($key) : $repository->set($key, $value);
            }
        }
    }

    public function test_browser_profile_disables_both_python_paths_despite_legacy_flags(): void
    {
        $face = $this->profile('browser', true, true);
        $this->assertSame('browser', $face['runtime']);
        $this->assertFalse($face['scoring']['enabled']);
        $this->assertFalse($face['scoring']['punch']['enabled']);
        $this->assertTrue($face['require_qr']);
        $this->assertTrue($face['liveness_flash_frames']['require_images']);
    }

    public function test_server_profile_enables_independent_scoring_for_both_paths(): void
    {
        $face = $this->profile('server', false, false);
        $this->assertTrue($face['scoring']['enabled']);
        $this->assertTrue($face['scoring']['punch']['enabled']);
        $this->assertTrue($face['scoring']['required']);
        $this->assertTrue($face['scoring']['punch']['required']);
    }

    public function test_unset_profile_preserves_existing_separate_scoring_flags(): void
    {
        $face = $this->profile(null, true, false);
        $this->assertTrue($face['scoring']['enabled']);
        $this->assertFalse($face['scoring']['punch']['enabled']);
    }

    public function test_misspelled_profile_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->profile('browzer', true, true);
    }

    public function test_bundled_assets_and_php_are_ready_for_browser_hosting(): void
    {
        config(['face.runtime' => 'browser', 'face.scoring.enabled' => false, 'face.scoring.punch.enabled' => false]);
        $this->artisan('face:check')->expectsOutputToContain('Face hosting checks passed (browser mode).')->assertSuccessful();
    }

    public function test_missing_uploads_are_reported(): void
    {
        $this->app->usePublicPath(sys_get_temp_dir() . '/hris-missing-face-assets');
        $this->artisan('face:check')->expectsOutputToContain('public/models/arcface/det_500m.onnx')->assertFailed();
    }

    public function test_non_https_hosting_domain_is_refused(): void
    {
        config(['app.url' => 'http://hris.example.test']);
        $this->artisan('face:check')->expectsOutputToContain('Set APP_URL to the HTTPS domain')->assertFailed();
    }
}
