<?php
 
 namespace App\Http\Controllers\Api;
 
 use App\Http\Controllers\Controller;
 use Illuminate\Http\Request;
 use App\Models\Employee;
 use App\Models\Dtr;
 use Carbon\Carbon;
 use App\Models\DtrTest;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Validator;

 class DtrController extends Controller
 {
     public function getGuard()
    {  
        if (\Auth::guard('web')->check()) {
            return 'web';
        } elseif (\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function eventMonitoring(Request $request)
    {
        
    }
    
    public function syncDtr_(Request $request)
    {
        $data = json_decode($request->getContent(), true);
    
        if (!is_array($data)) {
           return response()->json(['error' => 'Invalid JSON data'], 400);
        }
    
        $insertData = [];
        $dates = [];
    
        foreach ($data as $item) {
           if (!isset($item['emp_ID'], $item['date'])) {
              continue;
           }
    
           $insertData[] = [
              'device_id_in' => $item['device_id_in'] ?? null,
              'device_id_out' => $item['device_id_out'] ?? null,
              'device_id_over' => $item['device_id_over'] ?? null,
              'emp_ID' => $item['emp_ID'],
              'time_in' => $item['time_in'] ?? null,
              'time_out' => $item['time_out'] ?? null,
              'time_over' => $item['time_over'] ?? null,
              'date' => $item['date'],
           ];
    
           $dates[$item['date']] = true;
    
           if (count($insertData) >= 100) {
              DtrTest::insert($insertData);
              $insertData = [];
           }
        }
    
        if (!empty($insertData)) {
           DtrTest::insert($insertData);
        }
    
        if (!empty($dates)) {
           DB::statement("SET SESSION group_concat_max_len = 4294967295");
            
           $dateList = array_keys($dates);
    
           // Fetch merged data for all dates
           $mergedData = DtrTest::select(
             'emp_ID',
             'date',
             DB::raw("MAX(id) as id"),
             DB::raw("GROUP_CONCAT(NULLIF(device_id_in, '') ORDER BY device_id_in SEPARATOR ',') AS device_id_in"),
             DB::raw("GROUP_CONCAT(NULLIF(device_id_out, '') ORDER BY device_id_out SEPARATOR ',') AS device_id_out"),
             DB::raw("GROUP_CONCAT(NULLIF(device_id_over, '') ORDER BY device_id_over SEPARATOR ',') AS device_id_over"),
             DB::raw("GROUP_CONCAT(NULLIF(time_in, '') ORDER BY time_in SEPARATOR ',') AS time_in"),
             DB::raw("GROUP_CONCAT(NULLIF(time_out, '') ORDER BY time_out SEPARATOR ',') AS time_out"),
             DB::raw("GROUP_CONCAT(NULLIF(time_over, '') ORDER BY time_over SEPARATOR ',') AS time_over")
          )
          ->whereIn('date', $dateList)
          ->groupBy('emp_ID', 'date')
          ->get();
    
           $updates = [];
           foreach ($mergedData as $data) {
              $filteredTimeIn = $this->removeDuplicatesWithDeviceIds($data->time_in, $data->device_id_in);
              $filteredTimeOut = $this->removeDuplicatesWithDeviceIds($data->time_out, $data->device_id_out);
              $filteredTimeOver = $this->removeDuplicatesWithDeviceIds($data->time_over, $data->device_id_over);
    
              $updates[] = [
                 'id' => $data->id,
                 'device_id_in' => $filteredTimeIn['device_ids'] ?: null,
                 'device_id_out' => $filteredTimeOut['device_ids'] ?: null,
                 'device_id_over' => $filteredTimeOver['device_ids'] ?: null,
                 'time_in' => $filteredTimeIn['times'] ?: null,
                 'time_out' => $filteredTimeOut['times'] ?: null,
                 'time_over' => $filteredTimeOver['times'] ?: null,
              ];
           }

           // Batch update
           DB::table('dtrs_test')->upsert($updates, ['id'], ['device_id_in', 'device_id_out', 'device_id_over', 'time_in', 'time_out', 'time_over']);
    
           // Delete duplicates (only keep latest per emp_ID, date)
           DB::table('dtrs_test')
              ->whereIn('date', $dateList)
              ->whereNotIn('id', function ($query) use ($dateList) {
                 $query->selectRaw('MAX(id)')
                    ->from('dtrs_test')
                    ->whereIn('date', $dateList)
                    ->groupBy('emp_ID', 'date');
              })
              ->delete();
        }
        
        DB::statement("SET GLOBAL max_connect_errors = 1000000;");
        
        return response()->json(['message' => 'DTR Sync Complete']);
    }

    public function syncDtrManual(Request $request)
    {
        $data = Dtr::where('date', $request->date)->get();

        if ($data->isEmpty()) {
            return response()->json(['error' => 'No data found for the given date'], 404);
        }

        $insertData = [];
        $dates = [];

        foreach ($data as $item) {
            if (!isset($item->emp_ID, $item->date)) {
                continue;
            }

            $insertData[] = [
                'device_id_in' => $item->device_id_in ?? null,
                'device_id_out' => $item->device_id_out ?? null,
                'device_id_over' => $item->device_id_over ?? null,
                'emp_ID' => $item->emp_ID,
                'time_in' => $item->time_in ?? null,
                'time_out' => $item->time_out ?? null,
                'time_over' => $item->time_over ?? null,
                'date' => $item->date,
            ];

            $dates[$item->date] = true;

            if (count($insertData) >= 100) {
                DtrTest::insert($insertData);
                $insertData = [];
            }
        }

        if (!empty($insertData)) {
            DtrTest::insert($insertData);
        }

        if (!empty($dates)) {
            DB::statement("SET SESSION group_concat_max_len = 4294967295");

            $dateList = array_keys($dates);

            // Fetch merged data for all dates
            $mergedData = DtrTest::select(
                'emp_ID',
                'date',
                DB::raw("MAX(id) as id"),
                DB::raw("GROUP_CONCAT(NULLIF(device_id_in, '') ORDER BY device_id_in SEPARATOR ',') AS device_id_in"),
                DB::raw("GROUP_CONCAT(NULLIF(device_id_out, '') ORDER BY device_id_out SEPARATOR ',') AS device_id_out"),
                DB::raw("GROUP_CONCAT(NULLIF(device_id_over, '') ORDER BY device_id_over SEPARATOR ',') AS device_id_over"),
                DB::raw("GROUP_CONCAT(NULLIF(time_in, '') ORDER BY time_in SEPARATOR ',') AS time_in"),
                DB::raw("GROUP_CONCAT(NULLIF(time_out, '') ORDER BY time_out SEPARATOR ',') AS time_out"),
                DB::raw("GROUP_CONCAT(NULLIF(time_over, '') ORDER BY time_over SEPARATOR ',') AS time_over")
            )
            ->whereIn('date', $dateList)
            ->groupBy('emp_ID', 'date')
            ->get();

            $updates = [];
            foreach ($mergedData as $data) {
                $filteredTimeIn = $this->removeDuplicatesWithDeviceIds($data->time_in, $data->device_id_in);
                $filteredTimeOut = $this->removeDuplicatesWithDeviceIds($data->time_out, $data->device_id_out);
                $filteredTimeOver = $this->removeDuplicatesWithDeviceIds($data->time_over, $data->device_id_over);

                $updates[] = [
                    'id' => $data->id,
                    'device_id_in' => $filteredTimeIn['device_ids'] ?: null,
                    'device_id_out' => $filteredTimeOut['device_ids'] ?: null,
                    'device_id_over' => $filteredTimeOver['device_ids'] ?: null,
                    'time_in' => $filteredTimeIn['times'] ?: null,
                    'time_out' => $filteredTimeOut['times'] ?: null,
                    'time_over' => $filteredTimeOver['times'] ?: null,
                ];
            }

            // Batch update
            DB::table('dtrs_test')->upsert($updates, ['id'], ['device_id_in', 'device_id_out', 'device_id_over', 'time_in', 'time_out', 'time_over']);

            // Delete duplicates (only keep latest per emp_ID, date)
            DB::table('dtrs_test')
                ->whereIn('date', $dateList)
                ->whereNotIn('id', function ($query) use ($dateList) {
                    $query->selectRaw('MAX(id)')
                        ->from('dtrs_test')
                        ->whereIn('date', $dateList)
                        ->groupBy('emp_ID', 'date');
                })
                ->delete();
        }

        DB::statement("SET GLOBAL max_connect_errors = 1000000;");
        
        return response()->json(['message' => 'DTR Sync Complete']);
    }

    public function syncDtr(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        
        if (!is_array($data)) {
            return response()->json(['error' => 'Invalid JSON data'], 400);
        }

        $insertData = [];
        $dates = [];

        foreach ($data as $item) {
            if (!isset($item['emp_ID'], $item['date'])) {
                continue;
            }

            $insertData[] = [
                'device_id_in' => $item['device_id_in'] ?? null,
                'device_id_out' => $item['device_id_out'] ?? null,
                'device_id_over' => $item['device_id_over'] ?? null,
                'emp_ID' => $item['emp_ID'],
                'time_in' => $item['time_in'] ?? null,
                'time_out' => $item['time_out'] ?? null,
                'time_over' => $item['time_over'] ?? null,
                'date' => $item['date'],
            ];

            $dates[$item['date']] = true;

            if (count($insertData) >= 100) {
                Dtr::insert($insertData);
                $insertData = [];
            }
        }

        if (!empty($insertData)) {
            Dtr::insert($insertData);
        }

        if (!empty($dates)) {
            DB::statement("SET SESSION group_concat_max_len = 4294967295");

            $dateList = array_keys($dates);

            // Fetch merged data for all dates
            $mergedData = Dtr::select(
                'emp_ID',
                'date',
                DB::raw("MAX(id) as id"),
                DB::raw("GROUP_CONCAT(NULLIF(device_id_in, '') ORDER BY device_id_in SEPARATOR ',') AS device_id_in"),
                DB::raw("GROUP_CONCAT(NULLIF(device_id_out, '') ORDER BY device_id_out SEPARATOR ',') AS device_id_out"),
                DB::raw("GROUP_CONCAT(NULLIF(device_id_over, '') ORDER BY device_id_over SEPARATOR ',') AS device_id_over"),
                DB::raw("GROUP_CONCAT(NULLIF(time_in, '') ORDER BY time_in SEPARATOR ',') AS time_in"),
                DB::raw("GROUP_CONCAT(NULLIF(time_out, '') ORDER BY time_out SEPARATOR ',') AS time_out"),
                DB::raw("GROUP_CONCAT(NULLIF(time_over, '') ORDER BY time_over SEPARATOR ',') AS time_over"),
                DB::raw("GROUP_CONCAT(DISTINCT NULLIF(device_id_in, '') ORDER BY device_id_in SEPARATOR ',') AS device_id_in"),
                DB::raw("GROUP_CONCAT(DISTINCT NULLIF(device_id_out, '') ORDER BY device_id_out SEPARATOR ',') AS device_id_out"),
                DB::raw("GROUP_CONCAT(DISTINCT NULLIF(device_id_over, '') ORDER BY device_id_over SEPARATOR ',') AS device_id_over"),
                DB::raw("GROUP_CONCAT(DISTINCT NULLIF(time_in, '') ORDER BY time_in SEPARATOR ',') AS time_in"),
                DB::raw("GROUP_CONCAT(DISTINCT NULLIF(time_out, '') ORDER BY time_out SEPARATOR ',') AS time_out"),
                DB::raw("GROUP_CONCAT(DISTINCT NULLIF(time_over, '') ORDER BY time_over SEPARATOR ',') AS time_over")
            )
            ->whereIn('date', $dateList)
            ->groupBy('emp_ID', 'date')
            ->get();

            $updates = [];
            foreach ($mergedData as $data) {
                $filteredTimeIn = $this->removeDuplicatesWithDeviceIds($data->time_in, $data->device_id_in);
                $filteredTimeOut = $this->removeDuplicatesWithDeviceIds($data->time_out, $data->device_id_out);
                $filteredTimeOver = $this->removeDuplicatesWithDeviceIds($data->time_over, $data->device_id_over);

                $updates[] = [
                    'id' => $data->id,
                    'device_id_in' => $data->device_id_in ?: null,
                    'device_id_out' => $data->device_id_out ?: null,
                    'device_id_over' => $data->device_id_over ?: null,
                    'time_in' => $data->time_in ?: null,
                    'time_out' => $data->time_out ?: null,
                    'time_over' => $data->time_over ?: null,
                    'device_id_in' => $this->trimCommas($filteredTimeIn['device_ids']),
                    'device_id_out' => $this->trimCommas($filteredTimeOut['device_ids']),
                    'device_id_over' => $this->trimCommas($filteredTimeOver['device_ids']),
                    'time_in' => $this->trimCommas($filteredTimeIn['times']),
                    'time_out' => $this->trimCommas($filteredTimeOut['times']),
                    'time_over' => $this->trimCommas($filteredTimeOver['times']),
                ];
            }

            // Batch update
            DB::table('dtrs')->upsert($updates, ['id'], ['device_id_in', 'device_id_out', 'device_id_over', 'time_in', 'time_out', 'time_over']);

            // Delete duplicates (only keep latest per emp_ID, date)
            DB::table('dtrs')
                ->whereIn('date', $dateList)
                ->whereNotIn('id', function ($query) use ($dateList) {
                    $query->selectRaw('MAX(id)')
                        ->from('dtrs')
                        ->whereIn('date', $dateList)
                        ->groupBy('emp_ID', 'date');
                })
                ->delete();
        }

        DB::statement("SET GLOBAL max_connect_errors = 1000000;");
        
        return response()->json(['message' => 'DTR Sync Complete']);
    }
    
    private function removeDuplicatesWithDeviceIds($times, $deviceIds)
    {
        $timeArray = explode(',', $times);
        $deviceIdArray = explode(',', $deviceIds);
        $uniqueData = [];
    
        foreach ($timeArray as $index => $time) {
            $deviceId = $deviceIdArray[$index] ?? null;
            if (!isset($uniqueData[$time])) {
                $uniqueData[$time] = $deviceId;
            }
        }
    
        return [
            'times' => implode(',', array_keys($uniqueData)),
            'device_ids' => implode(',', array_values($uniqueData))
        ];
    }
    
    private function trimCommas($value)
    {
        return trim($value, ',');
    }   
    

    function checkCoordinates() {
        //Inside
        // $point = ['lat' => 9.853287, 'lng' => 122.889427];
        // $point = ['lat' => 9.852922, 'lng' => 122.889298];
        // $point = ['lat' => 9.853191, 'lng' => 122.889365];

        //outside
        // $point = ['lat' => 9.853024, 'lng' => 122.890152];
        // $point = ['lat' => 9.852726, 'lng' => 122.890197];
        // $point = ['lat' => 9.853378, 'lng' => 122.889465];
        // $point = ['lat' => 9.853287, 'lng' => 122.889427];
    
        // 🔷 Polygon coordinates (can be 4, 6, or more)
        $polygon = [
            ['lat' => 9.853224, 'lng' => 122.889381],
            ['lat' => 9.853086, 'lng' => 122.888930],
            ['lat' => 9.852587, 'lng' => 122.889539],
            ['lat' => 9.853216, 'lng' => 122.890124],
            // Add more points if needed
        ];
    
        // 🧠 Ray-casting algorithm
        $x = $point['lng'];
        $y = $point['lat'];
        $inside = false;
    
        $n = count($polygon);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lng'];
            $yi = $polygon[$i]['lat'];
            $xj = $polygon[$j]['lng'];
            $yj = $polygon[$j]['lat'];
    
            $intersect = (($yi > $y) != ($yj > $y)) &&
                         ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-10) + $xi);
    
            if ($intersect) {
                $inside = !$inside;
            }
        }
    
        return $inside ? "✅ Inside the area" : "❌ Outside the area";
    }

    public function appdtrauthcheck(Request $request)
    {
        $androidid = $request->input('androidid');
        $empid = $request->input('empid');
    
        $alreadyLinked = Employee::where('android_id', $androidid)->first();
        if ($alreadyLinked) {
            // return response()->json(['error' => 'This Android ID is already linked to another account.'], 400);
            return 0;
        }
    
        $employee = Employee::where('emp_ID', $empid)->first();
    
        if (!$employee) {
            return response()->json(['error' => 'Employee not found.'], 404);
        }
        
        if (empty($employee->android_id)) {
            $employee->android_id = $androidid;
            $employee->save();
            // return response()->json(['message' => 'Android ID linked successfully.']);
            return 1;
        } else {
            // return response()->json(['error' => 'Account already linked.'], 400);
            return 2;
        }
    }
    
    
    public function appdtrauthLogin(Request $request)
    {
        $request->validate([
            'android' => 'required|string',
            'cat' => 'required|integer|in:1,2,3',
        ]);
    
        $currentDate = Carbon::now('Asia/Manila')->format('Y-m-d');
        $currentTime = Carbon::now('Asia/Manila')->format('H:i:s');
    
        $androidid = $request->input('android');
        $cat =  $request->input('cat');
        $deviceID = 17;
    
        $employee = Employee::where('android_id', $androidid)->first();
    
        if (!$employee) {
            return response()->json(['error' => 'Employee not found.'], 404);
        }
    
        $empID = $employee->emp_ID;
    
        $record = Dtr::where('emp_ID', $empID)
                     ->where('date', $currentDate)
                     ->first();
    
        if ($record) {
            switch ($cat) {
                case 1:
                    $record->device_id_in = $record->device_id_in
                        ? $record->device_id_in . ',' . $deviceID
                        : $deviceID;
                    $record->time_in = $record->time_in
                        ? $record->time_in . ',' . $currentTime
                        : $currentTime;
                    break;
    
                case 2:
                    $record->device_id_out = $record->device_id_out
                        ? $record->device_id_out . ',' . $deviceID
                        : $deviceID;
                    $record->time_out = $record->time_out
                        ? $record->time_out . ',' . $currentTime
                        : $currentTime;
                    break;
    
                case 3:
                    $record->device_id_over = $record->device_id_over
                        ? $record->device_id_over . ',' . $deviceID
                        : $deviceID;
                    $record->time_over = $record->time_over
                        ? $record->time_over . ',' . $currentTime
                        : $currentTime;
                    break;
            }
    
            $record->save();
            return response()->json(['message' => 'DTR updated successfully.']);
        }

        $test = new Dtr();
        $test->emp_ID = $empID;
        $test->date = $currentDate;
    
        switch ($cat) {
            case 1:
                $test->device_id_in = $deviceID;
                $test->time_in = $currentTime;
                break;
    
            case 2:
                $test->device_id_out = $deviceID;
                $test->time_out = $currentTime;
                break;
    
            case 3:
                $test->device_id_over = $deviceID;
                $test->time_over = $currentTime;
                break;
        }
    
        $test->save();
    
        return response()->json(['message' => 'DTR created successfully.']);
    }
    

    public function appdtrLogs(Request $request)
    {
        $request->validate([
            'androidid' => 'required|string',
        ]);
        $androidid = $request->input('androidid') ?? '0123456789';
        $dtr = Employee::where('android_id', $androidid)->first();
        $empID = $dtr->emp_ID;

        $currentDate = Carbon::now('Asia/Manila')->format('Y-m-d');
        $logs = Dtr::where('emp_ID', $empID)
               ->where('date', $currentDate)
               ->first();

        if (!$logs) {
            return response()->json(['error' => 'No logs found for the given employee and date.'], 404);
        }

        return response()->json([
            'emp_ID' => $logs->emp_ID,
            'date' => $logs->date,
            'time_in' => $logs->time_in,
            'time_out' => $logs->time_out,
            'time_over' => $logs->time_over,
            'device_id_in' => $logs->device_id_in,
            'device_id_out' => $logs->device_id_out,
            'device_id_over' => $logs->device_id_over,
        ]);
    }
 }