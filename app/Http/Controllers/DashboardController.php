<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lookup;
use App\Models\Unit;
use App\Models\Employee;
use App\Models\EmployeeRole;
use App\Models\News;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get statistics for the dashboard based on the selected unit.
     */
    public function getStats(Request $request)
    {
        $unitName = $request->input('unit_name');
        
        // Find unit ID if a specific unit is provided and is not global/Super Admin
        $unit = null;
        $isGlobal = true;
        
        if ($unitName && !in_array(strtolower($unitName), ['super admin', 'all', 'global', 'admin'])) {
            $unit = Unit::where(DB::raw('LOWER(name)'), strtolower($unitName))->first();
            if ($unit) {
                $isGlobal = false;
            }
        }

        // --- Calculate Agent Counts (All roles combined: Admin, Agent, Super Admin) ---
        $agentQuery = EmployeeRole::query();
        if (!$isGlobal) {
            $agentQuery->where('unit_id', $unit->id);
        }

        $totalAgents = (clone $agentQuery)->count();
        
        $activeAgents = (clone $agentQuery)->whereHas('employee', function ($q) {
            $q->where('is_active', 1);
        })->count();

        $inactiveAgents = (clone $agentQuery)->whereHas('employee', function ($q) {
            $q->where('is_active', 0);
        })->count();


        // --- Calculate Daily Logins (Past 30 Days) ---
        $loginStats = [];
        $avgDailyLogins = 0;
        
        $startDate = Carbon::now()->subDays(29)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $loginLogsQuery = DB::table('user_login_logs')
            ->whereBetween('login_at', [$startDate, $endDate]);

        if (!$isGlobal) {
            $loginLogsQuery->where('unit_name', $unit->name);
        }

        // Group by Date in PHP to ensure compatibility and fill missing days with 0
        $rawLogins = $loginLogsQuery
            ->select(DB::raw('DATE(login_at) as date_only'), DB::raw('COUNT(DISTINCT user_id) as count'))
            ->groupBy('date_only')
            ->orderBy('date_only', 'asc')
            ->get()
            ->pluck('count', 'date_only')
            ->toArray();

        $loginChartData = [];
        $totalLoginCount = 0;

        for ($i = 29; $i >= 0; $i--) {
            $dateStr = Carbon::now()->subDays($i)->format('Y-m-d');
            $labelStr = Carbon::now()->subDays($i)->format('d M');
            $count = $rawLogins[$dateStr] ?? 0;
            
            $loginChartData[] = [
                'date' => $dateStr,
                'label' => $labelStr,
                'count' => $count
            ];
            $totalLoginCount += $count;
        }

        $avgDailyLogins = round($totalLoginCount / 30, 1);


        // --- Calculate News (Active vs Expired) ---
        $now = Carbon::now();
        $newsQuery = News::query();

        // If unit-specific, filter news by target_unit
        if (!$isGlobal) {
            $newsQuery->where(function ($q) use ($unit) {
                $q->where('target_unit', 'like', '%"' . $unit->name . '"%')
                    ->orWhere('target_unit', $unit->name)
                    ->orWhere('target_unit', 'like', '%"All Layanan"%')
                    ->orWhere('target_unit', 'All Layanan')
                    ->orWhereNull('target_unit');
            });
        }

        // Active News
        $activeNews = (clone $newsQuery)->where(function ($q) use ($now) {
            $q->whereNull('end_time')->orWhere('end_time', '>=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('start_time')->orWhere('start_time', '<=', $now);
        })->count();

        // Expired News
        $expiredNews = (clone $newsQuery)->where('end_time', '<', $now)->count();


        // --- Calculate Menu Access Statistics (Menu 1 to 15) ---
        $menuClickQuery = DB::table('menu_access_logs');
        if (!$isGlobal) {
            $menuClickQuery->where('unit_name', $unit->name);
        }

        $rawClicks = $menuClickQuery
            ->select('menu_name', DB::raw('count(*) as count'))
            ->whereIn('menu_name', array_map(fn($i) => "Menu $i", range(1, 15)))
            ->groupBy('menu_name')
            ->get()
            ->pluck('count', 'menu_name')
            ->toArray();

        $menuClickData = [];
        for ($m = 1; $m <= 15; $m++) {
            $menuName = "Menu $m";
            $menuClickData[] = [
                'menu' => $menuName,
                'clicks' => $rawClicks[$menuName] ?? 0
            ];
        }

        // --- Calculate Unit-by-Unit Breakdowns ---
        $unitDetails = [];
        $allUnits = [];
        if ($isGlobal) {
            $allUnits = Unit::orderBy('name', 'asc')->get();
        } else if ($unit) {
            $allUnits = collect([$unit]);
        }

        foreach ($allUnits as $u) {
            // Agent stats for this unit (All roles combined: Admin, Agent, Super Admin)
            $uAgentQuery = EmployeeRole::where('unit_id', $u->id);
            $uTotalAgents = (clone $uAgentQuery)->count();
            $uActiveAgents = (clone $uAgentQuery)->whereHas('employee', function ($q) {
                $q->where('is_active', 1);
            })->count();
            $uInactiveAgents = (clone $uAgentQuery)->whereHas('employee', function ($q) {
                $q->where('is_active', 0);
            })->count();

            // Calculate active and inactive counts for each specific role in this unit
            $uActiveAdmins = (clone $uAgentQuery)->where('role_id', 1)->whereHas('employee', function ($q) {
                $q->where('is_active', 1);
            })->count();
            $uInactiveAdmins = (clone $uAgentQuery)->where('role_id', 1)->whereHas('employee', function ($q) {
                $q->where('is_active', 0);
            })->count();

            $uActiveAgentsForRole = (clone $uAgentQuery)->where('role_id', 2)->whereHas('employee', function ($q) {
                $q->where('is_active', 1);
            })->count();
            $uInactiveAgentsForRole = (clone $uAgentQuery)->where('role_id', 2)->whereHas('employee', function ($q) {
                $q->where('is_active', 0);
            })->count();

            $uActiveSuperAdmins = (clone $uAgentQuery)->where('role_id', 3)->whereHas('employee', function ($q) {
                $q->where('is_active', 1);
            })->count();
            $uInactiveSuperAdmins = (clone $uAgentQuery)->where('role_id', 3)->whereHas('employee', function ($q) {
                $q->where('is_active', 0);
            })->count();

            // News stats for this unit
            $uNewsQuery = News::query();
            $uNewsQuery->where(function ($q) use ($u) {
                $q->where('target_unit', 'like', '%"' . $u->name . '"%')
                    ->orWhere('target_unit', $u->name)
                    ->orWhere('target_unit', 'like', '%"All Layanan"%')
                    ->orWhere('target_unit', 'All Layanan')
                    ->orWhereNull('target_unit');
            });
            
            $uActiveNews = (clone $uNewsQuery)->where(function ($q) use ($now) {
                $q->whereNull('end_time')->orWhere('end_time', '>=', $now);
            })->where(function ($q) use ($now) {
                $q->whereNull('start_time')->orWhere('start_time', '<=', $now);
            })->count();
            
            $uExpiredNews = (clone $uNewsQuery)->where('end_time', '<', $now)->count();

            $unitDetails[] = [
                'unit_name' => $u->name,
                'agent_stats' => [
                    'total' => $uTotalAgents,
                    'active' => $uActiveAgents,
                    'inactive' => $uInactiveAgents,
                    'admin_active' => $uActiveAdmins,
                    'admin_inactive' => $uInactiveAdmins,
                    'agent_active' => $uActiveAgentsForRole,
                    'agent_inactive' => $uInactiveAgentsForRole,
                    'super_admin_active' => $uActiveSuperAdmins,
                    'super_admin_inactive' => $uInactiveSuperAdmins,
                ],
                'news_stats' => [
                    'active' => $uActiveNews,
                    'expired' => $uExpiredNews,
                    'total' => $uActiveNews + $uExpiredNews
                ]
            ];
        }

        // --- Calculate Keaktifan Login Stats (Sering, Jarang, Tidak Pernah) ---
        // 1. Sering Login Query
        $frequentQuery = \App\Models\User::with(['employee.roles.unit'])
            ->select('users.*')
            ->selectSub(function($q) {
                $q->from('user_login_logs')
                  ->whereColumn('user_login_logs.user_id', 'users.id')
                  ->selectRaw('count(*)');
            }, 'login_count')
            ->whereExists(function($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                  ->from('user_login_logs')
                  ->whereColumn('user_login_logs.user_id', 'users.id');
            });

        if (!$isGlobal) {
            $frequentQuery->whereHas('employee.roles', function($q) use ($unit) {
                $q->where('unit_id', $unit->id);
            });
        }

        $frequentLogins = $frequentQuery
            ->orderBy('login_count', 'desc')
            ->limit(1000)
            ->get();

        $frequentLoginsData = $frequentLogins->map(function($u) {
            $unitName = $u->employee && $u->employee->roles && $u->employee->roles->first() && $u->employee->roles->first()->unit 
                ? $u->employee->roles->first()->unit->name 
                : '-';
            return [
                'npp' => $u->employee->npp ?? '-',
                'name' => $u->employee->name ?? '-',
                'unit' => $unitName,
                'count' => $u->login_count ?? 0,
            ];
        });

        // 2. Jarang Login Query
        $rareQuery = \App\Models\User::with(['employee.roles.unit'])
            ->select('users.*')
            ->selectSub(function($q) {
                $q->from('user_login_logs')
                  ->whereColumn('user_login_logs.user_id', 'users.id')
                  ->selectRaw('count(*)');
            }, 'login_count')
            ->whereExists(function($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                  ->from('user_login_logs')
                  ->whereColumn('user_login_logs.user_id', 'users.id');
            });

        if (!$isGlobal) {
            $rareQuery->whereHas('employee.roles', function($q) use ($unit) {
                $q->where('unit_id', $unit->id);
            });
        }

        $rareLogins = $rareQuery
            ->orderBy('login_count', 'asc')
            ->limit(1000)
            ->get();

        $rareLoginsData = $rareLogins->map(function($u) {
            $unitName = $u->employee && $u->employee->roles && $u->employee->roles->first() && $u->employee->roles->first()->unit 
                ? $u->employee->roles->first()->unit->name 
                : '-';
            return [
                'npp' => $u->employee->npp ?? '-',
                'name' => $u->employee->name ?? '-',
                'unit' => $unitName,
                'count' => $u->login_count ?? 0,
            ];
        });

        // 3. Tidak Pernah Login Query
        $neverQuery = \App\Models\User::with(['employee.roles.unit'])
            ->select('users.*')
            ->whereNotExists(function($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                  ->from('user_login_logs')
                  ->whereColumn('user_login_logs.user_id', 'users.id');
            });

        if (!$isGlobal) {
            $neverQuery->whereHas('employee.roles', function($q) use ($unit) {
                $q->where('unit_id', $unit->id);
            });
        }

        $neverLogins = $neverQuery
            ->orderBy('users.id', 'desc')
            ->limit(1000)
            ->get();

        $neverLoginsData = $neverLogins->map(function($u) {
            $unitName = $u->employee && $u->employee->roles && $u->employee->roles->first() && $u->employee->roles->first()->unit 
                ? $u->employee->roles->first()->unit->name 
                : '-';
            return [
                'npp' => $u->employee->npp ?? '-',
                'name' => $u->employee->name ?? '-',
                'unit' => $unitName,
                'count' => 0,
            ];
        });

        // Return everything packed together
        return response()->json([
            'unit' => $unitName ?: 'Global',
            'agent_stats' => [
                'total' => $totalAgents,
                'active' => $activeAgents,
                'inactive' => $inactiveAgents,
            ],
            'login_stats' => [
                'average' => $avgDailyLogins,
                'total_30_days' => $totalLoginCount,
                'chart' => $loginChartData,
            ],
            'news_stats' => [
                'active' => $activeNews,
                'expired' => $expiredNews,
                'total' => $activeNews + $expiredNews
            ],
            'menu_stats' => $menuClickData,
            'unit_details' => $unitDetails,
            'activity_stats' => [
                'frequent' => $frequentLoginsData,
                'rare' => $rareLoginsData,
                'never' => $neverLoginsData,
            ]
        ]);
    }

    /**
     * Track a menu click from Menu 1 to Menu 15.
     */
    public function trackClick(Request $request)
    {
        $request->validate([
            'menu_name' => 'required|string',
            'menu_title' => 'nullable|string',
            'unit_name' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Resolve unit ID if possible
        $unit = Unit::where('name', $request->unit_name)->first();

        $log = DB::table('menu_access_logs')->insert([
            'user_id' => $user->id,
            'unit_id' => $unit ? $unit->id : null,
            'unit_name' => $request->unit_name,
            'menu_name' => $request->menu_name,
            'menu_title' => $request->menu_title ?: $request->menu_name,
            'accessed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Click tracked successfully']);
    }

    /**
     * Track a login access event.
     */
    public function trackLogin(Request $request)
    {
        $request->validate([
            'unit_name' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $unit = Unit::where('name', $request->unit_name)->first();

        DB::table('user_login_logs')->insert([
            'user_id' => $user->id,
            'unit_id' => $unit ? $unit->id : null,
            'unit_name' => $request->unit_name,
            'login_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Login tracked successfully']);
    }
}
