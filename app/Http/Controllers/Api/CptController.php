<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CptController
{
    public function sync(Request $request)
    {
        if ($request->bearerToken() !== config('services.cpt_sync.token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $updatedSince = $request->query('updated_since');
        $page = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);

        $officeColumns = $this->columnsFor('offices');
        $employeeColumns = $this->columnsFor('employees');

        $officeSelect = [
            'id',
            'office_name',
            'office_abbr',
            $this->selectColumnOrNull($officeColumns, 'office_head_id'),
            $this->selectColumnOrNull($officeColumns, 'oic_id'),
            $this->selectColumnOrNull($officeColumns, 'created_at'),
            $this->selectColumnOrNull($officeColumns, 'updated_at'),
        ];

        $employeeSelect = collect([
            'id',
            'fname',
            'mname',
            'lname',
            'emp_ID',
            'emp_status',
            'emp_dept',
            'supervisor',
            'org_email',
            'password',
            'stat_1',
            'esign',
            'created_at',
            'updated_at',
        ])->filter(fn ($column) => in_array($column, $employeeColumns, true))->values()->all();

        $offices = $page === 1
            ? DB::table('offices')
                ->select($officeSelect)
                ->when($updatedSince && in_array('updated_at', $officeColumns, true), function ($query) use ($updatedSince) {
                    $query->where('updated_at', '>=', $updatedSince);
                })
                ->orderBy('id')
                ->get()
            : collect();

        $employeeQuery = DB::table('employees')
            ->select($employeeSelect)
            ->when($updatedSince && in_array('updated_at', $employeeColumns, true), function ($query) use ($updatedSince) {
                $query->where('updated_at', '>=', $updatedSince);
            })
            ->orderBy('id');

        $employees = (clone $employeeQuery)
            ->forPage($page, $perPage)
            ->get();

        $hasMoreEmployees = (clone $employeeQuery)
            ->offset($page * $perPage)
            ->limit(1)
            ->exists();

        return response()->json([
            'synced_at' => now()->toIso8601String(),
            'offices' => $offices,
            'employees' => $employees,
            'next_page_url' => $hasMoreEmployees
                ? $request->fullUrlWithQuery(['page' => $page + 1, 'per_page' => $perPage])
                : null,
        ]);
    }

    private function columnsFor(string $table): array
    {
        return collect(DB::select("SHOW COLUMNS FROM `{$table}`"))
            ->pluck('Field')
            ->all();
    }

    private function selectColumnOrNull(array $columns, string $column)
    {
        return in_array($column, $columns, true) ? $column : DB::raw('NULL as '.$column);
    }
}