<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lookup;
use App\Models\Unit;
use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeRole;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardStatsSeeder extends Seeder
{
    public function run()
    {
        // 1. Fetch or create roles
        $agentRole = Lookup::firstOrCreate(
            ['name' => 'Agent'],
            ['type' => 'role', 'value' => 2, 'order_no' => 2]
        );

        $adminRole = Lookup::firstOrCreate(
            ['name' => 'Admin'],
            ['type' => 'role', 'value' => 1, 'order_no' => 1]
        );

        // 2. Fetch or create Units
        $units = [
            'Banking' => ['code' => 'BNK', 'short' => 'Banking'],
            'Credit' => ['code' => 'CRD', 'short' => 'Credit'],
            'Emerald' => ['code' => 'EMR', 'short' => 'Emerald'],
            'Merchant' => ['code' => 'MCH', 'short' => 'Merchant']
        ];

        $unitModels = [];
        foreach ($units as $name => $meta) {
            $unitModels[$name] = Unit::firstOrCreate(
                ['name' => $name],
                ['parent_id' => 0, 'type' => 1, 'code' => $meta['code'], 'short_name' => $meta['short'], 'is_active' => 1]
            );
        }

        // 3. Clear existing logs first to ensure idempotency and clean seed
        DB::table('user_login_logs')->truncate();
        DB::table('menu_access_logs')->truncate();

        // 4. Create multiple Agent Employees & Users for each unit
        // We'll create a nice spread of active and inactive agents.
        $nppStart = 80000;
        
        // Banking agents: 35 active, 8 inactive
        // Credit agents: 22 active, 4 inactive
        // Emerald agents: 15 active, 2 inactive
        // Merchant agents: 18 active, 5 inactive
        $agentCounts = [
            'Banking' => ['active' => 35, 'inactive' => 8],
            'Credit' => ['active' => 22, 'inactive' => 4],
            'Emerald' => ['active' => 15, 'inactive' => 2],
            'Merchant' => ['active' => 18, 'inactive' => 5],
        ];

        $createdUsers = [];

        foreach ($agentCounts as $unitName => $counts) {
            $unit = $unitModels[$unitName];
            
            // Create Active Agents
            for ($i = 0; $i < $counts['active']; $i++) {
                $npp = (string)($nppStart++);
                
                // Avoid duplicate employee record
                $emp = Employee::where('npp', $npp)->first();
                if (!$emp) {
                    $emp = Employee::create([
                        'npp' => $npp,
                        'name' => "Agent " . $unitName . " " . ($i + 1),
                        'is_active' => 1,
                        'email' => "agent_" . strtolower($unitName) . "_" . ($i + 1) . "@bni.co.id",
                        'last_login' => now()->subMinutes(rand(10, 10000)),
                    ]);
                    
                    $usr = User::create([
                        'username' => "agent_" . strtolower($unitName) . "_" . ($i + 1),
                        'password' => 'password',
                        'employee_id' => $emp->id,
                        'attempt_count' => 0,
                    ]);

                    EmployeeRole::create([
                        'employee_id' => $emp->id,
                        'role_id' => $agentRole->id,
                        'unit_id' => $unit->id,
                        'status_id' => 1,
                    ]);

                    $createdUsers[] = [
                        'user_id' => $usr->id,
                        'unit_id' => $unit->id,
                        'unit_name' => $unitName
                    ];
                }
            }

            // Create Inactive Agents
            for ($i = 0; $i < $counts['inactive']; $i++) {
                $npp = (string)($nppStart++);
                
                $emp = Employee::where('npp', $npp)->first();
                if (!$emp) {
                    $emp = Employee::create([
                        'npp' => $npp,
                        'name' => "Inactive Agent " . $unitName . " " . ($i + 1),
                        'is_active' => 0,
                        'email' => "agent_inactive_" . strtolower($unitName) . "_" . ($i + 1) . "@bni.co.id",
                        'last_login' => now()->subDays(rand(10, 50)),
                    ]);
                    
                    $usr = User::create([
                        'username' => "agent_inactive_" . strtolower($unitName) . "_" . ($i + 1),
                        'password' => 'password',
                        'employee_id' => $emp->id,
                        'attempt_count' => 0,
                    ]);

                    EmployeeRole::create([
                        'employee_id' => $emp->id,
                        'role_id' => $agentRole->id,
                        'unit_id' => $unit->id,
                        'status_id' => 1,
                    ]);
                }
            }
        }

        // 5. Seed login logs & menu click logs over the last 30 days
        // We want to construct a realistic timeline.
        // We'll iterate day by day.
        $now = Carbon::now();
        
        $loginLogs = [];
        $clickLogs = [];

        // Define menu popularity weights to make charts look awesome!
        // Some menus are highly accessed, others rarely.
        $menuPopularity = [
            'Menu 1' => 90,   // High
            'Menu 2' => 45,
            'Menu 3' => 80,   // High
            'Menu 4' => 30,
            'Menu 5' => 75,   // High
            'Menu 6' => 20,
            'Menu 7' => 15,
            'Menu 8' => 40,
            'Menu 9' => 60,
            'Menu 10' => 50,
            'Menu 11' => 25,
            'Menu 12' => 10,  // Low
            'Menu 13' => 35,
            'Menu 14' => 18,
            'Menu 15' => 8    // Low
        ];

        // Retrieve menu titles from portal settings to simulate real clicked titles!
        $portalSettings = DB::table('portal_settings')->get();
        $menuTitlesMap = [];
        foreach ($portalSettings as $ps) {
            if ($ps->section) {
                $menuTitlesMap[$ps->section][] = $ps->title;
            }
        }

        foreach ($createdUsers as $userData) {
            $userId = $userData['user_id'];
            $unitId = $userData['unit_id'];
            $unitName = $userData['unit_name'];

            // Each user logs in on a subset of the past 30 days
            for ($day = 30; $day >= 0; $day--) {
                $date = (clone $now)->subDays($day);
                
                // Agents login 70% of weekdays, 20% of weekends
                $isWeekend = $date->isWeekend();
                $loginChance = $isWeekend ? 20 : 70;
                
                // Adjust activity slightly by unit (Banking and Credit are more active)
                if ($unitName === 'Banking') $loginChance += 10;
                if ($unitName === 'Credit') $loginChance += 5;

                if (rand(1, 100) <= $loginChance) {
                    $loginTime = (clone $date)->setHour(rand(7, 17))->setMinute(rand(0, 59))->setSecond(rand(0, 59));
                    
                    $loginLogs[] = [
                        'user_id' => $userId,
                        'unit_id' => $unitId,
                        'unit_name' => $unitName,
                        'login_at' => $loginTime,
                        'created_at' => $loginTime,
                        'updated_at' => $loginTime,
                    ];

                    // If logged in, they will click some menus!
                    // Random number of clicks on this login session (1 to 8 clicks)
                    $clickCount = rand(1, 8);
                    for ($c = 0; $c < $clickCount; $c++) {
                        // Weighted menu selection
                        $menuName = $this->getRandomWeighted($menuPopularity);
                        
                        // Find a realistic title or use a default
                        $title = "Shortcut to " . $menuName;
                        if (isset($menuTitlesMap[$menuName]) && count($menuTitlesMap[$menuName]) > 0) {
                            $title = $menuTitlesMap[$menuName][array_rand($menuTitlesMap[$menuName])];
                        }

                        $clickTime = (clone $loginTime)->addMinutes(rand(2, 120));

                        $clickLogs[] = [
                            'user_id' => $userId,
                            'unit_id' => $unitId,
                            'unit_name' => $unitName,
                            'menu_name' => $menuName,
                            'menu_title' => $title,
                            'accessed_at' => $clickTime,
                            'created_at' => $clickTime,
                            'updated_at' => $clickTime,
                        ];
                    }
                }
            }
        }

        // Bulk insert logs for performance
        // PostgreSQL might have max placeholder limits, so we chunk them
        foreach (array_chunk($loginLogs, 500) as $chunk) {
            DB::table('user_login_logs')->insert($chunk);
        }

        foreach (array_chunk($clickLogs, 500) as $chunk) {
            DB::table('menu_access_logs')->insert($chunk);
        }

        $this->command->info('Dashboard statistic logs successfully seeded!');
    }

    private function getRandomWeighted(array $weights)
    {
        $totalWeight = array_sum($weights);
        $rand = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($weights as $key => $weight) {
            $currentWeight += $weight;
            if ($rand <= $currentWeight) {
                return $key;
            }
        }
        
        return array_key_first($weights);
    }
}
