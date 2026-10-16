<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeRole;
use App\Models\Lookup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['employee.roles.role', 'employee.roles.unit']);

        // --- Unit-based Data Isolation ---
        $currentUser = Auth::user();
        $isSuperAdmin = false;
        $userUnitIds = [];

        if ($currentUser && $currentUser->employee) {
            $userRoles = $currentUser->employee->roles()->with(['role', 'unit'])->get();
            $username = strtolower($currentUser->username ?? '');
            $employeeName = strtolower($currentUser->employee->name ?? '');

            // Detect Super Admin via roles, unit, or naming convention
            foreach ($userRoles as $r) {
                $roleName = strtolower($r->role?->name ?? '');
                $unitName = strtolower($r->unit?->name ?? '');
                
                if (str_contains($roleName, 'super admin') || str_contains($unitName, 'super admin')) {
                    $isSuperAdmin = true;
                    break;
                }
                if ($r->unit_id) {
                    $userUnitIds[] = $r->unit_id;
                }
            }

            if (!$isSuperAdmin && (
                $username === 'admin' || 
                $username === 'admin-all' || 
                $username === 'adminall'
            )) {
                $isSuperAdmin = true;
            }
        }

        if (!$isSuperAdmin) {
            if (!empty($userUnitIds)) {
                $query->whereHas('employee.roles', function ($q) use ($userUnitIds) {
                    $q->whereIn('unit_id', $userUnitIds);
                });
            } else {
                // If not super admin and has no units, show nothing
                $query->whereRaw('1 = 0');
            }
        }
        // ---------------------------------

        if ($request->filled('npp')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where(DB::raw('LOWER(npp)'), 'like', '%' . strtolower($request->npp) . '%');
            });
        }

        if ($request->filled('name')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where(DB::raw('LOWER(name)'), 'like', '%' . strtolower($request->name) . '%');
            });
        }

        if ($request->filled('role')) {
            $roleSearch = strtolower($request->role);
            $query->where(function ($gQ) use ($roleSearch) {
                // 1. Regular database role matching
                $gQ->whereHas('employee.roles', function ($q) use ($roleSearch) {
                    if (str_contains($roleSearch, ' ')) {
                        $parts = explode(' ', $roleSearch, 2);
                        $rPart = $parts[0];
                        $uPart = $parts[1];
                        $q->whereHas('role', function ($sub) use ($rPart) {
                            $sub->where(DB::raw('LOWER(name)'), 'like', '%' . $rPart . '%');
                        })->whereHas('unit', function ($sub) use ($uPart) {
                            $sub->where(DB::raw('LOWER(name)'), 'like', '%' . $uPart . '%');
                        });
                    } else {
                        $q->where(function ($sub) use ($roleSearch) {
                            $sub->whereHas('role', function ($rQ) use ($roleSearch) {
                                $rQ->where(DB::raw('LOWER(name)'), 'like', '%' . $roleSearch . '%');
                            })->orWhereHas('unit', function ($uQ) use ($roleSearch) {
                                $uQ->where(DB::raw('LOWER(name)'), 'like', '%' . $roleSearch . '%');
                            });
                        });
                    }
                });

                // 2. Super Admin fallback by username if filter matches "super admin" or "superadmin" or "admin super admin"
                if (str_contains($roleSearch, 'super admin') || str_contains($roleSearch, 'superadmin') || str_contains($roleSearch, 'super')) {
                    $gQ->orWhere(function ($sub) {
                        $sub->where(DB::raw('LOWER(username)'), 'adminall')
                            ->orWhere(DB::raw('LOWER(username)'), 'admin-all')
                            ->orWhere(DB::raw('LOWER(username)'), 'like', '%superadmin%');
                    });
                }
            });
        }

        if ($request->filled('email')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where(DB::raw('LOWER(email)'), 'like', '%' . strtolower($request->email) . '%');
            });
        }

        if ($request->filled('active')) {
            $val = strtolower($request->active);
            $bit = ($val === 'yes') ? 1 : (($val === 'no') ? 0 : null);

            if (!is_null($bit)) {
                $query->whereHas('employee', function ($q) use ($bit) {
                    $q->where('is_active', $bit);
                });
            }
        }

        if ($request->filled('lastLogin')) {
            $query->where('last_login', 'like', '%' . $request->lastLogin . '%');
        }

        if ($request->filled('lastLogout')) {
            $query->where('last_logout', 'like', '%' . $request->lastLogout . '%');
        }

        if ($request->filled('wrongPass')) {
            $query->where('attempt_count', 'like', '%' . $request->wrongPass . '%');
        }

        // Add more filters as needed for email, etc.

        $paginate = $request->input('per_page', 20);
        $users = $query->orderBy('id', 'desc')->paginate($paginate);

        $data = $users->map(function ($user) {
            $isSuperAdmin = false;
            $username = strtolower($user->username);
            if ($username === 'adminall' || $username === 'admin-all' || str_contains($username, 'superadmin')) {
                $isSuperAdmin = true;
            }

            $roles = $user->employee && $user->employee->roles && $user->employee->roles->count() > 0 ? $user->employee->roles->map(function ($r) {
                $roleName = $r->role ? $r->role->name : null;
                $unitName = $r->unit ? $r->unit->short_name : null;

                if ($roleName && $unitName) {
                    return "- $roleName $unitName";
                } elseif ($roleName) {
                    return "- $roleName";
                }
                return null;
            })->filter()->implode("\n") : ($isSuperAdmin ? "- Admin Super Admin" : "-");

            $rawRoles = $user->employee && $user->employee->roles ? $user->employee->roles->map(function ($r) {
                if (!$r->role)
                    return null;

                return [
                    'roleName' => $r->role->name,
                    'unitName' => $r->unit ? $r->unit->name : '',
                    'status' => 'Active', // Mock status or derive if available in DB
                ];
            })->filter()->values() : [];

            return [
                'id' => $user->id,
                'npp' => $user->employee->npp ?? '-',
                'name' => $user->employee->name ?? '-',
                'role' => $roles, // Formatted string for table display
                'raw_roles' => $rawRoles, // Structured data for edit modal
                'email' => $user->employee->email ?? '',
                'active' => ($user->employee && $user->employee->is_active) ? 'Yes' : 'No',
                'lastLogin' => $user->last_login ? $user->last_login->toIso8601String() : '',
                'lastLogout' => $user->last_logout ? $user->last_logout->toIso8601String() : '',
                'wrongPass' => $user->attempt_count,
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'total' => $users->total(),
            'per_page' => $users->perPage(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'npp' => 'required', // Validation moved to logic to handle orphan employees
            'name' => 'required',
            'username' => 'required',
            'password' => 'required',
            'email' => 'required',
        ]);

        // Check if NPP is taken (exists as user or username already exists)
        $isNppTaken = false;
        $employeeByNpp = Employee::where('npp', $request->npp)->first();
        if (($employeeByNpp && $employeeByNpp->users()->exists()) || User::where('username', $request->username)->exists()) {
            $isNppTaken = true;
        }

        // Check if Email is taken by any employee/user who already has a user
        $isEmailTaken = false;
        $employeeByEmail = Employee::where('email', $request->email)->first();
        if ($employeeByEmail && $employeeByEmail->users()->exists()) {
            $isEmailTaken = true;
        }

        // Combined checks
        if ($isNppTaken && $isEmailTaken) {
            return response()->json(['message' => 'The npp and email have already been taken.'], 422);
        }

        if ($isNppTaken) {
            return response()->json(['message' => 'The npp has already been taken (User exists).'], 422);
        }

        if ($isEmailTaken) {
            return response()->json(['message' => 'The email has already been taken.'], 422);
        }

        DB::beginTransaction();
        try {
            // Check if Employee exists
            $employee = Employee::where('npp', $request->npp)->first();

            if ($employee) {
                // Reuse and update employee
                $employee->update([
                    'name' => $request->name,
                    'email' => $request->email,
                    'is_active' => 1,
                ]);
            } else {
                // Create new employee
                $employee = Employee::create([
                    'npp' => $request->npp,
                    'name' => $request->name,
                    'email' => $request->email,
                    'is_active' => 1,
                ]);
            }

            $user = User::create([
                'username' => $request->username,
                'password' => $request->password,
                'employee_id' => $employee->id,
                'attempt_count' => 0,
            ]);

            // Save Roles
            if ($request->has('roles') && is_array($request->roles)) {
                foreach ($request->roles as $roleData) {
                    $roleName = $roleData['roleName'] ?? null;
                    $unitName = $roleData['unitName'] ?? null;

                    if ($roleName) {
                        $roleLookup = \App\Models\Lookup::where('name', $roleName)->first();
                        $unit = \App\Models\Unit::where('name', $unitName)->first();

                        // Default status to 1 if not found
                        $statusId = 1;

                        if ($roleLookup) {
                            \App\Models\EmployeeRole::create([
                                'employee_id' => $employee->id,
                                'role_id' => $roleLookup->id,
                                'unit_id' => $unit ? $unit->id : null,
                                'status_id' => $statusId,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            \App\Services\ActivityLogger::log('CREATE', 'USER', "Membuat pengguna baru dengan NPP '{$request->npp}' dan Nama '{$request->name}'", ['username' => $request->username]);
            return response()->json(['message' => 'User created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create user: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
        ]);

        $user = User::findOrFail($id);
        $employee = $user->employee;

        // Verify that Email does not already exist for another employee
        $existingEmailEmployee = Employee::where('email', $request->email)->first();
        if ($existingEmailEmployee && (!$employee || $existingEmailEmployee->id !== $employee->id)) {
            return response()->json(['message' => 'The email has already been taken.'], 422);
        }

        // === Tangkap nilai LAMA sebelum update untuk perbandingan ===
        $oldName     = $employee->name ?? '-';
        $oldEmail    = $employee->email ?? '-';
        $oldIsActive = ($employee && $employee->is_active) ? 'Yes' : 'No';
        $npp         = $employee->npp ?? '-';

        DB::beginTransaction();
        try {
            if ($employee) {
                $employee->update([
                    'name'      => $request->name,
                    'email'     => $request->email,
                    'is_active' => $request->isActive === 'Yes' ? 1 : 0,
                ]);

                // Sync roles if provided
                if ($request->has('roles') && is_array($request->roles)) {
                    // Remove old roles
                    $employee->roles()->delete();

                    foreach ($request->roles as $roleData) {
                        $roleName = $roleData['roleName'] ?? null;
                        $unitName = $roleData['unitName'] ?? null;

                        if ($roleName) {
                            $roleLookup = \App\Models\Lookup::where('name', $roleName)->first();
                            $unit = \App\Models\Unit::where('name', $unitName)->first();
                            $statusId = 1;

                            if ($roleLookup) {
                                \App\Models\EmployeeRole::create([
                                    'employee_id' => $employee->id,
                                    'role_id'     => $roleLookup->id,
                                    'unit_id'     => $unit ? $unit->id : null,
                                    'status_id'   => $statusId,
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            // === Bandingkan nilai lama vs baru dan buat deskripsi detail ===
            $newName     = $request->name;
            $newEmail    = $request->email;
            $newIsActive = $request->isActive === 'Yes' ? 'Yes' : 'No';

            $changes = [];

            if (trim($oldName) !== trim($newName)) {
                $changes[] = "Employee Name: \"{$oldName}\" menjadi \"{$newName}\"";
            }
            if (trim($oldEmail) !== trim($newEmail)) {
                $changes[] = "Email: \"{$oldEmail}\" menjadi \"{$newEmail}\"";
            }
            if ($oldIsActive !== $newIsActive) {
                $changes[] = "Status Aktif: \"{$oldIsActive}\" menjadi \"{$newIsActive}\"";
            }

            $baseDesc = "Memperbarui data pengguna '{$user->username}' (NPP: {$npp})";

            if (!empty($changes)) {
                $baseDesc .= ', Perubahan ' . implode('; Perubahan ', $changes);
            } else {
                // Tidak ada perubahan pada nama/email/status — kemungkinan hanya perubahan peran/unit
                $baseDesc .= ' (Tidak ada perubahan pada Nama, Email, atau Status Aktif — kemungkinan perubahan Peran/Unit)';
            }

            \App\Services\ActivityLogger::log('UPDATE', 'USER', $baseDesc);

            return response()->json(['message' => 'User updated successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update user: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['message' => 'No users selected'], 400);
        }

        $users = User::with('employee')->whereIn('id', $ids)->get();
        $usernames = $users->pluck('username')->implode(', ');

        foreach ($users as $user) {
            // 1. Delete User Sessions (if any)
            $user->sessions()->delete();

            $employee = $user->employee;

            // 2. Delete User (must be done before deleting employee because users.employee_id references employee)
            $user->delete();

            // 3. Delete Employee and related roles (if employee exists and no other users reference it - though 1:1 usually)
            if ($employee) {
                // Check if other users reference this employee (if applicable), but assuming 1-1 or cascading cleanup:
                if ($employee->users()->count() === 0) {
                    // Delete Employee Roles first
                    $employee->roles()->delete();
                    // Delete Employee
                    $employee->delete();
                }
            }
        }

        \App\Services\ActivityLogger::log('DELETE', 'USER', "Menghapus secara massal pengguna: {$usernames}", ['ids' => $ids]);

        return response()->json(['message' => 'Users deleted successfully']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'password' => 'required',
        ]);

        $user = User::find($request->user_id);
        $user->password = $request->password;
        $user->save();

        \App\Services\ActivityLogger::log('RESET_PASSWORD', 'USER', "Mereset kata sandi pengguna '{$user->username}'");

        return response()->json(['message' => 'Password user berhasil direset']);
    }
    public function resetSession(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        if ($user) {
            $user->sessions()->delete();
            \App\Services\ActivityLogger::log('UPDATE', 'USER', "Memulihkan sesi aktif (reset session) untuk pengguna '{$user->username}'");
            return response()->json(['message' => 'Session user berhasil direset']);
        }

        return response()->json(['message' => 'User tidak ditemukan'], 404);
    }
}
