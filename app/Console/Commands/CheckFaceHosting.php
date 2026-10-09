<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckFaceHosting extends Command
{
    protected $signature = 'face:check';
    protected $description = 'Check face recognition assets and PHP requirements for hosting';

    public function handle(): int
    {
        $errors = [];
        $runtime = config('face.runtime');
        if (!in_array($runtime, ['browser', 'server'], true)) {
            $errors[] = 'FACE_RUNTIME must be browser or server.';
        }
        if (!extension_loaded('gd')) {
            $errors[] = 'Enable the PHP GD extension for flash-image verification.';
        }
        foreach ([
            'js/face-engine/face-engine.js',
            'js/onnx/ort.wasm.min.js',
            'js/onnx/ort-wasm-simd-threaded.mjs.js',
            'js/onnx/ort-wasm-simd-threaded.wasm',
            'models/arcface/det_500m.onnx',
            'models/arcface/w600k_mbf.onnx',
            'models/arcface/antispoof.onnx',
        ] as $asset) {
            $path = public_path($asset);
            if (!is_readable($path) || filesize($path) === 0) {
                $errors[] = 'Upload the missing or empty public/' . $asset . '.';
            }
        }

        $url = config('app.url');
        $host = parse_url($url, PHP_URL_HOST);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' && !in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            $errors[] = 'Set APP_URL to the HTTPS domain so the browser can access the camera.';
        }
        if ($runtime === 'browser') {
            if (config('face.scoring.enabled') || config('face.scoring.punch.enabled')) {
                $errors[] = 'Browser mode must disable server scoring. Run php artisan config:clear after setting FACE_RUNTIME=browser.';
            }
            if (!config('face.require_qr') || !config('face.liveness_flash_frames.require_images')) {
                $errors[] = 'Keep FACE_REQUIRE_QR=true and FACE_FLASH_IMAGES_REQUIRED=true for browser mode.';
            }
        }
        foreach ($errors as $error) {
            $this->error($error);
        }
        if ($errors) {
            return self::FAILURE;
        }

        $this->info('Face hosting checks passed (' . $runtime . ' mode).');
        if ($runtime === 'browser') {
            $this->line('ONNX Runtime Web runs on the camera device; no Python or background inference service is needed.');
        } else {
            $this->line('Server mode also needs the scoring service. This check does not verify that service is reachable.');
        }
        $this->line('Open registration and the kiosk on the hosted HTTPS domain to verify camera permissions and asset serving.');
        return self::SUCCESS;
    }
}
