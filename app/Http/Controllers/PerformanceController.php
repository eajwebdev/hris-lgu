<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PerformanceController extends Controller
{
    public function systemPerformance(Request $request)
    {
        $logPath = storage_path('logs/performance.log');
        $logs = File::exists($logPath) ? File::get($logPath) : '';

        $entries = [];

        foreach (explode("\n", $logs) as $line) {
            if (preg_match('/^\[(.*?)\] Request: (.+?) - (\d+)ms/', $line, $matches)) {
                $entries[] = [
                    'timestamp' => $matches[1],
                    'type' => 'request',
                    'url' => $matches[2],
                    'time' => (int)$matches[3],
                ];
            } elseif (preg_match('/^\[(.*?)\] Slow Query \((\d+)ms\):/', $line, $matches)) {
                $entries[] = [
                    'timestamp' => $matches[1],
                    'type' => 'slow_query',
                    'time' => (int)$matches[2],
                ];
            }
        }

        // Sort entries by timestamp descending
        usort($entries, fn($a, $b) => strtotime($b['timestamp']) <=> strtotime($a['timestamp']));

        // Limit to last 20 entries
        $entries = array_slice($entries, 0, 20);

        return view('home.system-performance', compact('entries'));
    }
}
