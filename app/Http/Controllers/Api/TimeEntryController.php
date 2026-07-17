<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use App\Models\Dtr;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TimeEntryController extends Controller
{
    // ==== CONFIG ====
    private int   $embeddingLimit = 7;
    private float $dedupeThr2     = 0.28 * 0.28;
    private float $acceptThr2     = 0.65 * 0.65;    
    // ==== HELPERS ====
    private function shortDecrypt($encrypted) { $key = config('api.crypto.key'); $cipher = config('api.crypto.cipher'); $encrypted = strtr($encrypted, '-_', '+/'); return openssl_decrypt(base64_decode($encrypted), $cipher, $key, 0); }
    private function l2Normalize(array $v): array {
        $sum = 0.0;
        foreach ($v as $x) { $sum += $x * $x; }
        $norm = sqrt(max($sum, 1e-12));
        foreach ($v as $i => $x) { $v[$i] = $x / $norm; }
        return $v;
    }
    private function l2Distance2(array $a, array $b): float {
        $s = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $d = $a[$i] - $b[$i];
            $s += $d * $d;
        }
        return $s;
    }
    private function dedupeEmbeddings(array $embs): array {
        $out = [];
        foreach ($embs as $e) {
            if (!is_array($e) || count($e) !== 128) continue;
            $keep = true;
            foreach ($out as $x) {
                if ($this->l2Distance2($e, $x) < $this->dedupeThr2) { $keep = false; break; }
            }
            if ($keep) $out[] = $e;
        }
        return $out;
    }
    private function centroid(array $embs): ?array {
        $acc = array_fill(0, 128, 0.0);
        $k = 0;
        foreach ($embs as $e) {
            if (!is_array($e) || count($e) !== 128) continue;
            $k++;
            for ($i = 0; $i < 128; $i++) { $acc[$i] += $e[$i]; }
        }
        if ($k === 0) return null;
        for ($i = 0; $i < 128; $i++) { $acc[$i] /= $k; }
        return $this->l2Normalize($acc);
    }
    private function readEmbObj(?string $json): array {
        if (!$json) return ['vecs' => [], 'centroid' => null];
        $raw  = json_decode($json, true);
        $vecs = $raw['vecs'] ?? [];
        $cent = $raw['centroid'] ?? null;
        // Light shape checks (defensive)
        $vecs = array_values(array_filter($vecs, fn($v) => is_array($v) && count($v) === 128));
        if (!(is_array($cent) && count($cent) === 128)) $cent = null;
        return ['vecs' => $vecs, 'centroid' => $cent];
    }
    private function isValidVec($v): bool {
        if (!is_array($v) || count($v) !== 128) return false;
        foreach ($v as $x) {
            if (!is_numeric($x)) return false;
            if (!is_finite((float)$x)) return false; // guard NaN/INF
        }
        return true;
    }    
    // ==== APIs ====
    public function checkRestrictionLevel(Request $request) {
        $ttl = 30; // seconds
        $row = Cache::remember('settings:te_rstrct', $ttl, function () {
            return DB::table('settings')->select('te_rstrct_lvl')->first();
        });
        $level = (int) ($row?->te_rstrct_lvl ?? 2);
        // Raw window in 24h
        $startStr = '11:00';
        $endStr   = '13:30';
        $tz    = 'Asia/Manila';
        $now   = Carbon::now($tz);
        $start = Carbon::createFromFormat('H:i', $startStr, $tz)->setDate($now->year, $now->month, $now->day);
        $end   = Carbon::createFromFormat('H:i', $endStr,   $tz)->setDate($now->year, $now->month, $now->day);
        // Handle overnight (e.g., 23:00–02:00)
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }
        // ---- helper to format window in 12h and collapse AM/PM when same ----
        $fmtWindow = function (Carbon $s, Carbon $e) {
            $sTime = $s->format('g:i');
            $eTime = $e->format('g:i');
            $sMer  = $s->format('A');
            $eMer  = $e->format('A');
            // Collapse only when they are on the same calendar day AND same meridiem
            if ($s->isSameDay($e) && $sMer === $eMer) {
                return "{$sTime}–{$eTime} {$sMer}";
            }
            return "{$sTime} {$sMer}–{$eTime} {$eMer}";
        };
        $allowed = true;
        $message = null;
        if ($level === 2) {
            $allowed = false;
            $message = 'Not available.';
        } elseif ($level === 1) {
            $allowed = $now->between($start, $end); // inclusive bounds
            if (!$allowed) {
                $message = 'Only allowed from ' . $fmtWindow($start, $end) . '.';
            }
        }
        return response()->json([
            'level'       => $level,
            'allowed'     => $allowed,
            // Keep individual parts in 12h, plus a preformatted label that collapses AM/PM
            'window'      => [
                'start' => $start->format('g:i A'),
                'end'   => $end->format('g:i A'),
                'label' => $fmtWindow($start, $end),
            ],
            'server_time' => $now->toIso8601String(),
            'message'     => $allowed ? null : ($message ?? 'Action not available.'),
        ]);
    }
    public function fetchLicense(Request $request) {
        // Optional: very light rate limit (per IP)
        $key = 'facesdk:license:' . $request->ip();
        if (!Cache::add($key, 1, now()->addSeconds(10))) {
            return response()->json([
                'ok' => false,
                'message' => 'Too many requests'
            ], 429);
        }
        $license = DB::table('settings')->value('te_key');
        if (!is_string($license) || trim($license) === '') {
            return response()->json([
                'ok' => false,
                'message' => 'License not configured'
            ], 500);
        }
        return response()->json([
            'ok'      => true,
            'license' => $license,
        ], 200);
    }
    public function fetchLogzonesWithCampuses(Request $request) {
        $ttl = 60;
        $payload = Cache::remember('logzones:payload', $ttl, function () {
            $zones = DB::table('logzones')->where('active_stat', 1)->orderBy('id')->get()->map(function ($z) {
                $points = json_decode($z->points, true) ?: [];
                return [
                    'id'      => (int) $z->id,
                    'label'   => (string) $z->label,
                    'points'  => $points,
                    'camp_id' => (int) $z->camp_id,
                ];
            })->values()->all();
            $campuses = DB::table('campuses')
                ->join('logzones', 'campuses.id', '=', 'logzones.camp_id')
                ->where('logzones.active_stat', 1)
                ->select('campuses.id', 'campuses.campus_abbr as abbr')
                ->distinct()->orderBy('campuses.id')->get()
                ->map(fn($c) => ['id' => (int)$c->id, 'abbr' => (string)$c->abbr])
                ->values()->all();
            return ['zones' => $zones, 'campuses' => $campuses];
        });
        $raw  = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $tag  = sha1($raw);           // store/tag unquoted
        $etag = '"'.$tag.'"';         // send quoted (HTTP spec)
        // Normalize client ETags: strip quotes and W/ prefix
        $clientEtags = array_map(function ($e) {
            $e = trim($e);
            $e = ltrim($e, 'W/');                 // weak validators ok for compare here
            return trim($e, '"');                 // strip quotes
        }, $request->getEtags() ?? []);
        if (in_array($tag, $clientEtags, true)) {
            return response()
                ->noContent(204)
                ->setEtag($etag)
                ->header('Cache-Control', 'no-store, no-store, max-age=0');
        }
        return response()
            ->json($payload, 200, [], JSON_UNESCAPED_UNICODE)
            ->setEtag($etag)
            ->header('Cache-Control', 'no-store, max-age=0');
    }
    public function validateQr(Request $request) {
        $qrRaw = $request->input('qr');
        $mode  = $request->input('mode', 'emp');
        // 200-only responses to keep frontend compatible
        if (!in_array($mode, ['emp', 'admin'], true)) {
            return response()->json(['valid' => false, 'message' => 'Invalid mode.'], 200);
        }
        if (!is_string($qrRaw) || trim($qrRaw) === '') {
            return response()->json(['valid' => false, 'message' => 'Invalid QR code (missing).'], 200);
        }
        try {
            $empId = trim((string) $this->shortDecrypt($qrRaw));
        } catch (\Throwable $e) {
            return response()->json(['valid' => false, 'message' => 'Invalid QR code (decryption failed).'], 200);
        }
        if ($empId === '') {
            return response()->json(['valid' => false, 'message' => 'Invalid QR code.'], 200);
        }
        // JSON-aware face check in SQL; avoids roundtripping the blob
        $row = DB::table('employees')
            ->select([
                'emp_ID',
                'fname',
                'lname',
                DB::raw("
                    (
                        JSON_VALID(face_embeddings)
                        AND (
                            COALESCE(JSON_LENGTH(JSON_EXTRACT(face_embeddings, '$.vecs')), 0) > 0
                            OR JSON_EXTRACT(face_embeddings, '$.centroid') IS NOT NULL
                        )
                    ) AS has_face
                "),
            ])
            ->where('emp_ID', $empId)
            ->where('stat_1', 1)
            ->first();
        if (!$row) {
            return response()->json(['valid' => false, 'message' => 'Employee not found or inactive.'], 200);
        }
        if (!(bool) $row->has_face) {
            return response()->json(['valid' => false, 'message' => 'No face registered for this employee.'], 200);
        }
        $isAdmin = false;
        if ($mode === 'admin') {
            $csv = (string) DB::table('settings')->value('hr_kiosk');
            $ids = array_filter(array_map('trim', preg_split('/\s*,\s*/', $csv, -1, PREG_SPLIT_NO_EMPTY)));
            if (!in_array($row->emp_ID, $ids, true)) {
                return response()->json(['valid' => false, 'message' => 'Not authorized.'], 200);
            }
            $isAdmin = true;
        }
        $name = trim(preg_replace('/\s+/', ' ', "{$row->fname} {$row->lname}"));
        return response()->json([
            'valid'    => true,
            'emp_id'   => $row->emp_ID,
            'name'     => $name,
            'is_admin' => $mode === 'admin' ? $isAdmin : false,
        ], 200);
    }
    public function faceClaim(Request $request) {
        // 1) Validate embedding input
        $embedding = $request->input('embedding');
        $qrRaw     = $request->input('qr');
        if (!$this->isValidVec($embedding)) {
            return response()->json(['error' => 'invalid_embedding', 'message' => 'Invalid embedding'], 400);
        }
        if (!is_string($qrRaw) || trim($qrRaw) === '') {
            return response()->json(['match'=>false,'message'=>'Invalid QR (missing).'], 200);
        }
        // 2) Decrypt QR
        try {
            $qrDecrypted = (string) $this->shortDecrypt($qrRaw);
        } catch (\Throwable $e) {
            return response()->json(['match'=>false,'message'=>'Invalid QR (decryption failed).'], 200);
        }
        $empId = trim($qrDecrypted ?? '');
        if ($empId === '') {
            return response()->json(['match'=>false,'message'=>'Invalid QR.'], 200);
        }
        // 3) Fetch only ACTIVE employees (stat_1 = 1), removed unused mname
        $row = DB::table('employees')
            ->select('emp_ID', 'fname', 'lname', 'face_embeddings')
            ->where('emp_ID', $empId)
            ->where('stat_1', 1)
            ->first();
        if (!$row) {
            return response()->json(['match'=>false,'message'=>'Employee not found or inactive.'], 200);
        }
        // 4) Parse stored embeddings
        $p = $this->readEmbObj($row->face_embeddings);
        $vecs = array_values(array_filter($p['vecs'] ?? [], fn($v) => $this->isValidVec($v)));
        $cent = (is_array($p['centroid']) && count($p['centroid']) === 128) ? $p['centroid'] : null;
        if (empty($vecs) && !$cent) {
            return response()->json(['match'=>false,'message'=>'No face registered for this employee.'], 200);
        }
        // 5) Normalize probe and compute best distance
        $probe = $this->l2Normalize($embedding);
        $bestD2 = INF;
        if ($cent) {
            $c = $this->l2Normalize($cent);
            $bestD2 = min($bestD2, $this->l2Distance2($probe, $c));
        }
        foreach ($vecs as $v) {
            $vn = $this->l2Normalize($v);
            $d2 = $this->l2Distance2($probe, $vn);
            if ($d2 < $bestD2) $bestD2 = $d2;
        }
        if (!is_finite($bestD2)) {
            return response()->json(['match'=>false,'message'=>'Face did not match.'], 200);
        }
        // 6) Threshold decision
        $passAbs = ($bestD2 < $this->acceptThr2);
        if ($passAbs) {
            $name = trim(preg_replace('/\s+/', ' ', "{$row->fname} {$row->lname}"));
            return response()->json([
                'match'   => true,
                'emp_id'  => $row->emp_ID,
                'name'    => $name,
                'message' => 'Face matched.'
            ], 200);
        }
        return response()->json(['match'=>false,'message'=>'Face did not match.'], 200);
    }
    public function logAttendance(Request $request) {
        try {
            $validated = $request->validate([
                'emp_id'  => 'required|string',
                'zone_id' => ['required','string','regex:/^-?\d+$/'],
                'action'  => 'required|integer|in:1,2,3',
            ]);
            // Preserve DB casing for emp_ID
            $empRow = DB::table('employees')
                ->select('emp_ID')
                ->where('emp_ID', $validated['emp_id'])
                ->first();
            if (!$empRow) {
                return response()->json(['error' => 'Unknown emp_ID'], 404);
            }
            $empId    = $empRow->emp_ID;
            $zoneId   = $validated['zone_id'];
            $action   = (int) $validated['action'];
            $deviceId = (string) $zoneId;
            $now  = now();                       // current server time
            $date = $now->toDateString();        // YYYY-MM-DD
            $time = $now->format('H:i:s');       // HH:MM:SS (seconds precision)
            $timeField   = match ($action) { 1 => 'time_in', 2 => 'time_out', 3 => 'time_over' };
            $deviceField = match ($action) { 1 => 'device_id_in', 2 => 'device_id_out', 3 => 'device_id_over' };
            $allowed     = true;
            $waitSeconds = 0;
            $didUpdate   = false;
            DB::transaction(function () use (
                $empId, $date, $time, $timeField, $deviceField, $deviceId, $now,
                &$allowed, &$waitSeconds, &$didUpdate, $action
            ) {
                // Lock all rows for this (emp_ID, date); newest first so ->first() is MAX(id)
                $rows = Dtr::where('emp_ID', $empId)
                    ->where('date', $date)
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->get();
                $record = $rows->first();
                if (!$record) {
                    // No row yet: create the canonical row (it becomes MAX(id) by definition)
                    Dtr::create([
                        'emp_ID'     => $empId,
                        'date'       => $date,
                        $timeField   => $time,
                        $deviceField => $deviceId,
                    ]);
                    $didUpdate = true;
                    return;
                }
                // Throttle rule (unchanged semantics):
                // TIME IN blocked if the first OUT >= 11:00:00 was < 60s ago.
                if ($action === 1 && !empty($record->time_out)) {
                    $outs = array_values(array_filter(array_map('trim', explode(',', $record->time_out))));
                    if (!empty($outs)) {
                        $threshold = '11:00:00';
                        $validOuts = array_values(array_filter($outs, fn ($t) => strtotime($t) >= strtotime($threshold)));
                        if (!empty($validOuts)) {
                            $firstQualOut = $validOuts[0];
                            // Compare as "today HH:MM:SS" vs server "now"
                            $lastOut = Carbon::createFromFormat('Y-m-d H:i:s', $date.' '.$firstQualOut, $now->timezone);
                            $elapsed = $lastOut->diffInSeconds($now);
                            if ($elapsed < 60) {
                                $allowed     = false;
                                $waitSeconds = 60 - $elapsed;
                            }
                        }
                    }
                }
                // Append only to the canonical (MAX id) row and avoid exact duplicate second
                if ($allowed) {
                    $existingTimes   = $record->$timeField   ? array_values(array_filter(array_map('trim', explode(',', $record->$timeField))))   : [];
                    $existingDevices = $record->$deviceField ? array_values(array_filter(array_map('trim', explode(',', $record->$deviceField)))) : [];
                    if (!in_array($time, $existingTimes, true)) {
                        $existingTimes[]   = $time;
                        $existingDevices[] = $deviceId;
                        $record->update([
                            $timeField   => implode(',', $existingTimes),
                            $deviceField => implode(',', $existingDevices),
                        ]);
                        $didUpdate = true;
                    }
                }
            });
            // 200 = updated/created, 202 = no change, 429 = throttled
            $status = $allowed ? ($didUpdate ? 200 : 202) : 429;
            return response()->json([
                'success'      => $didUpdate,
                'updated'      => $didUpdate,
                'allowed'      => $allowed,
                'wait_seconds' => $waitSeconds,
                'time'         => $now->format('h:i:s A'),
                'type'         => match ($action) { 1 => 'TIME IN', 2 => 'TIME OUT', 3 => 'OVERTIME' },
                'emp_id'       => $empId,
                'zone_id'      => $zoneId,
            ], $status);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function fetchLatestLogs(Request $request) {
        $MAX_DATES = 31;
        $empId = $request->input('empId');
        if (!$empId) {
            return response()->json(['error' => 'Missing empId'], 400);
        }
        // Burst control: 1 request per 3s per employee
        $key = 'latestlogs:' . $empId;
        $cooldown = 3;
        if (!Cache::add($key, 1, now()->addSeconds($cooldown))) {
            return response()
                ->json(['error' => 'Too many requests'], 429)
                ->header('Retry-After', (string)$cooldown);
        }
        // Default labels
        $DEFAULT_CAMPUS = 'TBD';
        $DEFAULT_LABEL  = 'TBD';
        // Fetch recent date window
        $dates = DB::table('dtrs')
            ->where('emp_ID', $empId)
            ->where(function ($q) {
                $q->whereNotNull('time_in')->where('time_in', '!=', '')
                ->orWhereNotNull('time_out')->where('time_out', '!=', '')
                ->orWhereNotNull('time_over')->where('time_over', '!=', '');
            })
            ->orderBy('date', 'desc')
            ->limit($MAX_DATES)
            ->pluck('date')
            ->toArray();
        if (empty($dates)) {
            return response()->json([
                'window_days'    => $MAX_DATES,
                'dates_included' => [],
                'logs'           => [],
            ], 200);
        }
        // Fetch all relevant rows for those dates
        $rows = DB::table('dtrs')
            ->join('employees', 'dtrs.emp_ID', '=', 'employees.emp_ID')
            ->where('dtrs.emp_ID', $empId)
            ->whereIn('dtrs.date', $dates)
            ->select('dtrs.*', 'employees.fname', 'employees.lname', 'employees.suffix')
            ->orderBy('dtrs.date', 'desc')
            ->get();
        // Collect unique positive/negative IDs
        $deviceIds = [];
        $zoneIds   = [];
        foreach ($rows as $r) {
            $extractIds = function ($str) {
                return array_filter(array_map('trim', explode(',', (string)$str)));
            };
            foreach ($extractIds($r->device_id_in) as $id)  $id > 0 ? $deviceIds[$id] = true : $zoneIds[$id] = true;
            foreach ($extractIds($r->device_id_out) as $id) $id > 0 ? $deviceIds[$id] = true : $zoneIds[$id] = true;
            foreach ($extractIds($r->device_id_over) as $id)$id > 0 ? $deviceIds[$id] = true : $zoneIds[$id] = true;
        }
        // Pull only needed lookups (sign rule preserved)
        $deviceById         = empty($deviceIds) ? [] : DB::table('f_devices')->whereIn('id', array_keys($deviceIds))->pluck('label', 'id')->toArray();
        $deviceCampusById   = empty($deviceIds) ? [] : DB::table('f_devices')->whereIn('id', array_keys($deviceIds))->pluck('camp_id', 'id')->toArray();
        $zoneById           = empty($zoneIds)   ? [] : DB::table('logzones')->whereIn('id', array_keys($zoneIds))->pluck('label', 'id')->toArray();
        $zoneCampusById     = empty($zoneIds)   ? [] : DB::table('logzones')->whereIn('id', array_keys($zoneIds))->pluck('camp_id', 'id')->toArray();
        $campusIds = array_unique(array_merge(array_values($deviceCampusById), array_values($zoneCampusById)));
        $campusById = empty($campusIds) ? [] : DB::table('campuses')->whereIn('id', $campusIds)->pluck('campus_name', 'id')->toArray();
        $campusName = function (?int $id) use ($deviceCampusById, $zoneCampusById, $campusById, $DEFAULT_CAMPUS) {
            if ($id === null) return $DEFAULT_CAMPUS;
            if ($id > 0) {
                $campId = $deviceCampusById[$id] ?? null;
            } else {
                $campId = $zoneCampusById[$id] ?? null;
            }
            return $campId ? ($campusById[$campId] ?? $DEFAULT_CAMPUS) : $DEFAULT_CAMPUS;
        };
        $labelName = function (?int $id) use ($deviceById, $zoneById, $DEFAULT_LABEL) {
            if ($id === null) return $DEFAULT_LABEL;
            return $id > 0 ? ($deviceById[$id] ?? $DEFAULT_LABEL)
                        : ($zoneById[$id] ?? $DEFAULT_LABEL);
        };
        // Expand all time fields
        $out = [];
        foreach ($rows as $r) {
            $append = function ($times, $ids, $type) use (&$out, $r, $campusName, $labelName) {
                $timesArr = array_filter(explode(',', (string)$times));
                $idsArr   = explode(',', (string)$ids);
                foreach ($timesArr as $i => $t) {
                    $raw = trim($idsArr[$i] ?? '');
                    $id  = $raw === '' ? null : (int)$raw;
                    $out[] = [
                        'type'         => $type,
                        'date'         => $r->date,
                        'time'         => $t,
                        'fname'        => $r->fname,
                        'lname'        => $r->lname,
                        'suffix'       => $r->suffix,
                        'zone_id'      => $id,
                        'campus_name'  => $campusName($id),
                        'zone_label'   => $labelName($id),
                        'ts'           => "{$r->date}T{$t}",
                    ];
                }
            };
            $append($r->time_in,  $r->device_id_in,  'time_in');
            $append($r->time_out, $r->device_id_out, 'time_out');
            $append($r->time_over,$r->device_id_over,'time_over');
        }
        // Sort newest first (no Carbon needed)
        usort($out, fn($a, $b) => strcmp($b['ts'], $a['ts']));
        foreach ($out as &$r) unset($r['ts']);
        return response()->json([
            'window_days'    => $MAX_DATES,
            'dates_included' => $dates,
            'logs'           => $out,
        ], 200);
    }
    public function downloadDtr(Request $request) {
        $encrypted = $request->input('e');
        if (empty($encrypted)) {
            return response('Missing encrypted parameter (e)', 400)
                ->header('Content-Type', 'text/plain');
        }
        // Use your existing shortDecrypt (unchanged)
        $empId = $this->shortDecrypt($encrypted);
        $empId = trim($empId ?? '');
        if (empty($empId)) {
            return response('Invalid or corrupted employee data', 400)
                ->header('Content-Type', 'text/plain');
        }
        // Employee lookup
        $employee = Employee::where('emp_ID', $empId)
            ->where('stat_1', 1)
            ->first();
        if (!$employee) {
            return response('Employee not found or inactive', 404)
                ->header('Content-Type', 'text/plain');
        }
        // Fetch DTR records
        // $query = Dtr::where('emp_ID', $empId);
        // $dateInput = $request->input('date'); // optional YYYY-MM
        // if ($dateInput && preg_match('/^\d{4}-\d{2}$/', $dateInput)) {
        //     $year  = substr($dateInput, 0, 4);
        //     $month = substr($dateInput, 5, 2);
        //     $query->whereYear('date', $year)
        //         ->whereMonth('date', $month);
        // } else {
        //     // Default: last 6 months
        //     $query->where('date', '>=', now()->subMonths(6)->startOfMonth())
        //         ->orderBy('date', 'desc');
        // }
        // $dtr = $query->get();
        // Return the printable view
        // return view('print.dtr-print', [
        //     'employee'     => $employee,
        //     'dtr'          => $dtr,
        //     'generated_at' => now()->format('M d, Y h:i A'),
        //     'period'       => $dateInput ?? 'Last 6 months',
        // ]);
        $fullName = trim(
            ($employee->fname ?? '') . ' ' .
            ($employee->mname ?? '') . ' ' .
            ($employee->lname ?? '')
        );
        return response($fullName, 200)->header('Content-Type', 'text/plain');
    }
    public function adminFaceClaim(Request $request) {
        // 1) Validate embedding input
        $embedding = $request->input('embedding');
        $qrRaw     = $request->input('qr');
        if (!$this->isValidVec($embedding)) {
            return response()->json(['error' => 'invalid_embedding', 'message' => 'Invalid embedding'], 400);
        }
        if (!is_string($qrRaw) || trim($qrRaw) === '') {
            return response()->json(['match'=>false,'is_admin'=>false,'message'=>'Invalid QR (missing).'], 200);
        }
        // 2) Decrypt QR
        try {
            $qrDecrypted = (string) $this->shortDecrypt($qrRaw);
        } catch (\Throwable $e) {
            return response()->json(['match'=>false,'is_admin'=>false,'message'=>'Invalid QR (decryption failed).'], 200);
        }
        $empId = trim($qrDecrypted ?? '');
        if ($empId === '') {
            return response()->json(['match'=>false,'is_admin'=>false,'message'=>'Invalid QR.'], 200);
        }
        // 3) Fetch only ACTIVE employees (stat_1 = 1), removed unused mname
        $row = DB::table('employees')
            ->select('emp_ID', 'fname', 'lname', 'face_embeddings')
            ->where('emp_ID', $empId)
            ->where('stat_1', 1)
            ->first();
        if (!$row) {
            return response()->json(['match'=>false,'is_admin'=>false,'message'=>'Employee not found or inactive.'], 200);
        }
        // 4) Parse stored embeddings
        $p = $this->readEmbObj($row->face_embeddings);
        $vecs = array_values(array_filter($p['vecs'] ?? [], fn($v) => $this->isValidVec($v)));
        $cent = (is_array($p['centroid']) && count($p['centroid']) === 128) ? $p['centroid'] : null;
        if (empty($vecs) && !$cent) {
            return response()->json(['match'=>false,'is_admin'=>false,'message'=>'No face registered for this employee.'], 200);
        }
        // 5) Normalize probe and compute best distance
        $probe = $this->l2Normalize($embedding);
        $bestD2 = INF;
        if ($cent) {
            $c = $this->l2Normalize($cent);
            $bestD2 = min($bestD2, $this->l2Distance2($probe, $c));
        }
        foreach ($vecs as $v) {
            $vn = $this->l2Normalize($v);
            $d2 = $this->l2Distance2($probe, $vn);
            if ($d2 < $bestD2) $bestD2 = $d2;
        }
        if (!is_finite($bestD2)) {
            return response()->json(['match'=>false,'is_admin'=>false,'message'=>'Face did not match.'], 200);
        }
        // 6) Threshold decision
        $passAbs = ($bestD2 < $this->acceptThr2);
        if (!$passAbs) {
            return response()->json(['match'=>false,'is_admin'=>false,'message'=>'Face did not match.'], 200);
        }
        // 7) Check admin status
        $csv = (string) DB::table('settings')->value('hr_kiosk');
        $arr = array_filter(array_map('trim', preg_split('/\s*,\s*/', $csv, -1, PREG_SPLIT_NO_EMPTY)));
        $isAdmin = in_array($row->emp_ID, $arr, true);

        $name = trim(preg_replace('/\s+/', ' ', "{$row->fname} {$row->lname}"));

        return response()->json([
            'match'    => true,
            'is_admin' => $isAdmin,
            'emp_id'   => $row->emp_ID,
            'name'     => $name,
            'message'  => 'Face matched' . ($isAdmin ? ' and authorized.' : '.'),
        ], 200);
    }
    public function adminPassVerify(Request $request) {
        // Only require password, with a sane length cap
        $request->validate([
            'password' => 'required|string|max:30',
        ]);
        try {
            // Read bcrypt hash directly
            $hash = DB::connection('mysql')
                ->table('settings')
                ->value('hrk_pw');
            if (!is_string($hash) || $hash === '') {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Password not set.',
                ], 500);
            }
            $input = (string) $request->input('password');
            if (Hash::check($input, $hash)) {
                // Optional: keep hashes fresh if cost changes
                if (Hash::needsRehash($hash)) {
                    DB::connection('mysql')
                        ->table('settings')
                        ->limit(1)
                        ->update(['hrk_pw' => Hash::make($input)]);
                }
                return response()->json(['ok' => true], 200);
            }
            // Wrong password: still HTTP 200, ok=false
            return response()->json([
                'ok'      => false,
                'message' => 'Incorrect password.',
            ], 200);
        } catch (\Throwable $e) {
            // Generic server error
            return response()->json([
                'ok'      => false,
                'message' => 'Server error.',
            ], 500);
        }
    }
    public function fetchEmployees(Request $request) {
        $employees = DB::table('employees')
            ->select('emp_ID', 'fname', 'mname', 'lname')
            ->where('stat_1', 1)
            ->orderBy('lname')
            ->get()
            ->map(function ($emp) {
                $parts = array_filter([$emp->fname, $emp->mname, $emp->lname]);
                return [
                    'emp_ID' => $emp->emp_ID,
                    'name'   => implode(' ', $parts),
                ];
            });
        return response()->json($employees);
    }
    public function faceRegister(Request $request) {
        $empId      = $request->input('emp_ID');
        $enrollerId = $request->input('enroller_ID');
        $embedding  = $request->input('embedding');
        $embeddings = $request->input('embeddings');
        if (!$empId || !$enrollerId) {
            return response()->json(['error' => 'Missing emp_ID or enroller_ID'], 400);
        }
        // Validate both employees exist
        if (!DB::table('employees')->where('emp_ID', $empId)->exists()) {
            return response()->json(['error' => 'Employee not found'], 404);
        }
        if (!DB::table('employees')->where('emp_ID', $enrollerId)->exists()) {
            return response()->json(['error' => 'Invalid enroller_ID'], 400);
        }
        // Normalize incoming embeddings
        $incoming = [];
        if (is_array($embeddings)) {
            foreach ($embeddings as $e) if ($this->isValidVec($e)) $incoming[] = $this->l2Normalize($e);
        } elseif ($this->isValidVec($embedding)) {
            $incoming[] = $this->l2Normalize($embedding);
        } else {
            return response()->json(['error' => 'Invalid embedding(s)'], 400);
        }
        if (empty($incoming)) return response()->json(['error' => 'No valid embeddings'], 400);
        // NEW: server-side de-dup (order-preserving)  ← Change #1
        $incoming = $this->dedupeEmbeddings($incoming);
        try {
            $result = DB::transaction(function () use ($empId, $enrollerId, $incoming) {
                $now     = now();
                $today   = $now->toDateString();
                $nowTime = $now->format('H:i:s');
                // ---- 1) Save embeddings (LOCK row) ----
                $row = DB::table('employees')
                    ->where('emp_ID', $empId)
                    ->lockForUpdate()
                    ->first();
                // Keep only the latest N after de-dup
                $merged = array_slice($incoming, -$this->embeddingLimit);
                // Recompute centroid from kept vectors
                $cent   = $this->centroid($merged);
                DB::table('employees')
                    ->where('emp_ID', $empId)
                    ->update(['face_embeddings' => json_encode(['vecs' => $merged, 'centroid' => $cent])]);
                // ---- 2) Append/Upsert registration history (concurrency-safe) ----
                DB::statement(
                    "INSERT INTO emp_timeentry_reghist (enroller_ID, `date`, enrolled_IDs, enrolled_times)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    enrolled_IDs = IF(enrolled_IDs IS NULL OR enrolled_IDs='',
                                        VALUES(enrolled_IDs),
                                        CONCAT(enrolled_IDs, ',', VALUES(enrolled_IDs))),
                    enrolled_times = IF(enrolled_times IS NULL OR enrolled_times='',
                                        VALUES(enrolled_times),
                                        CONCAT(enrolled_times, ',', VALUES(enrolled_times)))",
                    [$enrollerId, $today, $empId, $nowTime]
                );
                return ['total' => count($merged)];
            });
            Cache::forget('face_centroids');
            return response()->json([
                'success'          => true,
                'emp_ID'           => $empId,
                'total_embeddings' => $result['total'],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // ==== DEPRECATED ====    
    private float $ratioThr = 0.78;
    private int   $kMin     = 16;
    private int   $kMax     = 48;    
    private function findClosestEmployee(array $probe): array {
        $probe = $this->l2Normalize($probe);
        // 1) scan all centroids, keep top-K
        $centroids = $this->getCachedCentroids();
        if ($centroids->isEmpty()) return [null, INF, false, INF];
        $cand = [];
        foreach ($centroids as $emp) {
            $d2 = $this->l2Distance2($probe, $emp->centroid);
            $cand[] = [$emp, $d2];
        }
        usort($cand, fn($a,$b) => $a[1] <=> $b[1]);
        [$_, $bestCentD2] = $cand[0];
        $r2   = $this->ratioThr * $this->ratioThr; // e.g., 0.78^2 = 0.6084
        $band = $bestCentD2 / max($r2, 1e-9);
        // Keep only candidates that can affect the ratio test
        $cand = array_values(array_filter($cand, fn($c) => $c[1] <= $band));
        // Ensure enough rivals but cap cost
        $kMin = property_exists($this, 'kMin') ? $this->kMin : 16;
        $kMax = property_exists($this, 'kMax') ? $this->kMax : 48;
        if (count($cand) < $kMin) { $cand = array_slice($cand, 0, $kMin); }
        if (count($cand) > $kMax) { $cand = array_slice($cand, 0, $kMax); }
        // One round-trip for all shortlisted employees.
        $ids    = array_map(fn($c) => $c[0]->emp_ID, $cand);
        $rawMap = DB::table('employees')
            ->whereIn('emp_ID', $ids)
            ->pluck('face_embeddings', 'emp_ID');
        // 2) refine across ALL shortlisted employees’ vectors; pick global best + runner-up
        $bestEmp = null; $bestD2 = INF; $secondD2 = INF; $bestFromVec = false;
        foreach ($cand as [$emp, $centD2]) {
            $json = $rawMap->get($emp->emp_ID);
            $p    = $this->readEmbObj($json);
            $vecs = array_values(array_filter($p['vecs'] ?? [], fn($v) => $this->isValidVec($v)));
            // consider centroid but prefer real vectors when better
            $localMin = $centD2; 
            $localFromVec = false;
            foreach ($vecs as $v) {
                $v  = $this->l2Normalize($v);
                $d2 = $this->l2Distance2($probe, $v);
                if ($d2 < $localMin) { 
                    $localMin = $d2; 
                    $localFromVec = true; 
                }
            }
            if ($localMin < $bestD2) {
                $secondD2   = $bestD2;
                $bestD2     = $localMin;
                $bestEmp    = $emp;
                $bestFromVec= $localFromVec;
            } elseif ($localMin < $secondD2) {
                $secondD2 = $localMin;
            }
        }
        // Return: [$bestEmp, $bestD2, $bestFromVec(bool), $secondD2]
        return [$bestEmp, $bestD2, $bestFromVec, $secondD2];
    }
    private function getCachedCentroids() {
        $ttl = 900; // seconds
        return Cache::remember('face_centroids', $ttl, function () {
            return DB::table('employees')
                ->select('emp_ID', 'fname', 'mname', 'lname', 'face_embeddings')
                ->where('stat_1', 1)
                ->whereNotNull('face_embeddings')
                ->get()
                ->map(function ($e) {
                    $p = $this->readEmbObj($e->face_embeddings);
                    $cent = (is_array($p['centroid']) && count($p['centroid']) === 128)
                        ? $this->l2Normalize($p['centroid'])
                        : null;
                    if (!$cent) return null;
                    return (object)[
                        'emp_ID'   => $e->emp_ID,
                        'fname'    => $e->fname,
                        'mname'    => $e->mname,
                        'lname'    => $e->lname,
                        'centroid' => $cent,
                    ];
                })
                ->filter()
                ->values();
        });
    }
    public function fetchLogzones(Request $request) {
        $ttl = 60; // seconds
        // Build payload from cache (DB touched at most once per $ttl)
        $zones = Cache::remember('logzones_legacy:payload', $ttl, function () {
            return DB::table('logzones')->where('active_stat', 1)->get()->map(function ($zone) {
                $points = json_decode($zone->points, true);
                if (!is_array($points)) $points = [];
                return [
                    'id'     => (int) $zone->id,
                    'label'  => (string) $zone->label,
                    'points' => $points,
                ];
            })->values()->all(); // store as plain array
        });
        // Derive an ETag directly from the cached payload
        $etag = sha1(json_encode($zones, JSON_UNESCAPED_UNICODE));
        // If client already has this version, short-circuit
        if (in_array($etag, $request->getEtags() ?? [], true)) {
            return response()
                ->noContent(304)
                ->setEtag($etag)
                ->header('Cache-Control', 'public, max-age=60');
        }
        return response()
            ->json($zones, 200, [], JSON_UNESCAPED_UNICODE)
            ->setEtag($etag)
            ->header('Cache-Control', 'public, max-age=60');
    }
    public function faceVerify(Request $request) {
        $embedding = $request->input('embedding');
        if (!$this->isValidVec($embedding)) { return response()->json(['error' => 'Invalid embedding'], 400); }
        [$bestEmp, $bestD2, $_, $secondD2] = $this->findClosestEmployee($embedding);
        if (!$bestEmp || !is_finite($bestD2)) { return response()->json(['match' => false, 'distance' => 2.0]); }
        $passAbs   = ($bestD2 < $this->acceptThr2);
        $passRatio = is_finite($secondD2) && $secondD2 > 0 ? ($bestD2 < ($this->ratioThr * $this->ratioThr) * $secondD2) : true;
        if ($passAbs && $passRatio) {
            return response()->json([
                'match'    => true,
                'emp_id'   => $bestEmp->emp_ID,
                'name'     => trim("{$bestEmp->fname} {$bestEmp->mname} {$bestEmp->lname}"),
                'distance' => sqrt($bestD2),
            ]);
        }
        return response()->json(['match' => false, 'distance' => sqrt($bestD2)]);
    }
    public function adminFaceVerify(Request $request) {
        $embedding = $request->input('embedding');
        if (!$this->isValidVec($embedding)) { return response()->json(['error' => 'Invalid embedding'], 400); }
        [$bestEmp, $bestD2, $_, $secondD2] = $this->findClosestEmployee($embedding);
        $nonMatch = fn(float $d2) => response()->json([
            'match'    => false,
            'is_admin' => false,
            'distance' => is_finite($d2) ? sqrt($d2) : 2.0,
        ]);
        if (!$bestEmp || !is_finite($bestD2)) { return $nonMatch(INF); }
        $passAbs = ($bestD2 < $this->acceptThr2);
        $passRatio = is_finite($secondD2) && $secondD2 > 0 ? ($bestD2 < ($this->ratioThr * $this->ratioThr) * $secondD2) : true;
        if (!($passAbs && $passRatio)) { return $nonMatch($bestD2); }
        $hrKioskCsv = (string) DB::table('settings')->value('hr_kiosk');
        $ids = array_filter(array_map('trim', preg_split('/\s*,\s*/', $hrKioskCsv, -1, PREG_SPLIT_NO_EMPTY)));
        $isAdmin = in_array($bestEmp->emp_ID, $ids, true);
        return response()->json([
            'match'    => true,
            'is_admin' => $isAdmin,
            'emp_id'   => $bestEmp->emp_ID,
            'name'     => trim("{$bestEmp->fname} {$bestEmp->mname} {$bestEmp->lname}"),
            'distance' => sqrt($bestD2),
        ]);
    }
}