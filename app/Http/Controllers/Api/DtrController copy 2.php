<?php
 
 namespace App\Http\Controllers\Api;
 
 use App\Http\Controllers\Controller; // ✅ Import the base Controller
 use Illuminate\Http\Request;
 use App\Models\Employee;
 use App\Models\Dtr;
 use App\Models\DtrTest;
 use Illuminate\Support\Facades\DB;
 
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
 
     public function syncDtrBatch(Request $request)
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
                     'device_id_in' => $filteredTimeIn['device_ids'] ?: null,
                     'device_id_out' => $filteredTimeOut['device_ids'] ?: null,
                     'device_id_over' => $filteredTimeOver['device_ids'] ?: null,
                     'time_in' => $filteredTimeIn['times'] ?: null,
                     'time_out' => $filteredTimeOut['times'] ?: null,
                     'time_over' => $filteredTimeOver['times'] ?: null,
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
         $uniqueTimes = [];
         $uniqueDeviceIds = [];
 
         foreach ($timeArray as $index => $time) {
             if (!in_array($time, $uniqueTimes)) {
                 $uniqueTimes[] = $time;
                 $uniqueDeviceIds[] = $deviceIdArray[$index];
             }
         }
 
         return [
             'times' => implode(',', $uniqueTimes),
             'device_ids' => implode(',', $uniqueDeviceIds)
         ];
     }
 
 }