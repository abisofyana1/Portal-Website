<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\EmployeeRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::with('parent');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('parent')) {
            $query->whereHas('parent', function ($q) use ($request) {
                $q->where(DB::raw('LOWER(name)'), 'like', '%' . strtolower($request->parent) . '%');
            });
        }
        if ($request->filled('code')) {
            $query->where(DB::raw('LOWER(code)'), 'like', '%' . strtolower($request->code) . '%');
        }
        if ($request->filled('name')) {
            $query->where(DB::raw('LOWER(name)'), 'like', '%' . strtolower($request->name) . '%');
        }
        if ($request->filled('shortName')) {
            $query->where(DB::raw('LOWER(short_name)'), 'like', '%' . strtolower($request->shortName) . '%');
        }
        if ($request->filled('address')) {
            $query->where(DB::raw('LOWER(address)'), 'like', '%' . strtolower($request->address) . '%');
        }
        if ($request->filled('status')) {
            $val = strtolower($request->status);
            $bit = ($val === 'yes') ? 1 : (($val === 'no') ? 0 : null);
            if (!is_null($bit)) {
                $query->where('is_active', $bit);
            }
        }

        if ($request->boolean('all') || $request->input('per_page') === 'all' || $request->input('all') == 1) {
            $allUnits = $query->orderBy('id', 'asc')->get();
            $data = $allUnits->map(function ($unit) {
                return [
                    'id' => $unit->id,
                    'type' => $unit->type,
                    'parent_id' => $unit->parent_id,
                    'parent_name' => $unit->parent ? $unit->parent->name : '',
                    'code' => $unit->code,
                    'name' => $unit->name,
                    'short_name' => $unit->short_name,
                    'address' => $unit->address,
                    'is_active' => $unit->is_active ? 'Yes' : 'No',
                ];
            });

            return response()->json([
                'data' => $data,
                'total' => $allUnits->count(),
            ]);
        }

        $paginate = $request->input('per_page', 10);
        $units = $query->orderBy('id', 'desc')->paginate($paginate);

        $data = $units->map(function ($unit) {
            return [
                'id' => $unit->id,
                'type' => $unit->type,
                'parent_id' => $unit->parent_id,
                'parent_name' => $unit->parent ? $unit->parent->name : '',
                'code' => $unit->code,
                'name' => $unit->name,
                'short_name' => $unit->short_name,
                'address' => $unit->address,
                'is_active' => $unit->is_active ? 'Yes' : 'No',
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $units->currentPage(),
            'last_page' => $units->lastPage(),
            'total' => $units->total(),
            'per_page' => $units->perPage(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'name' => 'required',
            'shortName' => 'required',
            'isActive' => 'required',
        ]);

        $unit = Unit::create([
            'type' => $request->type,
            'parent_id' => $request->parentId ?: null,
            'code' => $request->code,
            'name' => $request->name,
            'short_name' => $request->shortName,
            'address' => $request->address,
            'is_active' => $request->isActive === 'Yes' ? 1 : 0,
        ]);

        \App\Services\ActivityLogger::log('CREATE', 'UNIT', "Membuat unit kerja baru: '{$unit->name}' ({$unit->short_name})", ['code' => $unit->code, 'type' => $unit->type]);

        return response()->json(['message' => 'Unit created successfully', 'data' => $unit]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'type' => 'required',
            'name' => 'required',
            'shortName' => 'required',
            'isActive' => 'required',
        ]);

        $unit = Unit::findOrFail($id);
        $oldName = $unit->name;
        $unit->update([
            'type' => $request->type,
            'parent_id' => $request->parentId ?: null,
            'code' => $request->code,
            'name' => $request->name,
            'short_name' => $request->shortName,
            'address' => $request->address,
            'is_active' => $request->isActive === 'Yes' ? 1 : 0,
        ]);

        \App\Services\ActivityLogger::log('UPDATE', 'UNIT', "Memperbarui data unit kerja '{$oldName}' menjadi '{$unit->name}' ({$unit->short_name})", ['code' => $unit->code]);

        return response()->json(['message' => 'Unit updated successfully', 'data' => $unit]);
    }

    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['message' => 'No units selected'], 400);
        }

        DB::beginTransaction();
        try {
            $units = Unit::whereIn('id', $ids)->get();
            $unitNames = $units->pluck('name')->implode(', ');

            // 1. Set parent_id to null for child units referencing these units to prevent orphan errors
            Unit::whereIn('parent_id', $ids)->update(['parent_id' => null]);

            // 2. Delete related employee roles referencing these units to clear foreign key relations
            EmployeeRole::whereIn('unit_id', $ids)->delete();

            // 3. Delete the units
            Unit::whereIn('id', $ids)->delete();

            DB::commit();
            \App\Services\ActivityLogger::log('DELETE', 'UNIT', "Menghapus secara massal unit kerja: {$unitNames}", ['ids' => $ids]);
            return response()->json(['message' => 'Units deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete units: ' . $e->getMessage()], 500);
        }
    }
}
