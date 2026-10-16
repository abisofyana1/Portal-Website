<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lookup;
use App\Models\Unit;
use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeRole;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds for Live Demo testing.
     * Roles: Super Admin, Admin Unit, Agent Unit
     */
    public function run(): void
    {
        // 1. Pastikan Lookups untuk Role tersedia
        $superAdminRole = Lookup::firstOrCreate(
            ['name' => 'Super Admin', 'type' => 'role'],
            ['value' => 3, 'order_no' => 3]
        );

        $adminRole = Lookup::firstOrCreate(
            ['name' => 'Admin', 'type' => 'role'],
            ['value' => 1, 'order_no' => 1]
        );

        $agentRole = Lookup::firstOrCreate(
            ['name' => 'Agent', 'type' => 'role'],
            ['value' => 2, 'order_no' => 2]
        );

        // 2. Pastikan Unit Tersedia
        $unitSuperAdmin = Unit::firstOrCreate(
            ['name' => 'Super Admin'],
            ['code' => 'Super Admin', 'short_name' => 'Super Admin', 'type' => 1, 'address' => 'Super Admin', 'is_active' => 1]
        );

        $unitBanking = Unit::firstOrCreate(
            ['name' => 'Banking'],
            ['code' => 'Banking', 'short_name' => 'Banking', 'type' => 1, 'address' => 'Banking', 'is_active' => 1]
        );

        // ---------------------------------------------------------
        // 3. User 1: ROLE SUPER ADMIN
        // ---------------------------------------------------------
        $empSuper = Employee::firstOrCreate(
            ['npp' => 'DEMO001'],
            [
                'name' => 'Super Administrator Demo',
                'is_active' => 1,
                'email' => 'superadmin@demo.com',
                'last_login' => now(),
            ]
        );
        $empSuper->update(['is_active' => 1]);

        $userSuper = User::updateOrCreate(
            ['username' => 'superadmin_demo'],
            [
                'password' => 'password123',
                'employee_id' => $empSuper->id,
                'attempt_count' => 0,
            ]
        );

        EmployeeRole::updateOrCreate(
            [
                'employee_id' => $empSuper->id,
                'role_id' => $superAdminRole->id,
            ],
            [
                'unit_id' => $unitSuperAdmin->id,
                'status_id' => 1,
            ]
        );

        // ---------------------------------------------------------
        // 4. User 2: ROLE ADMIN UNIT (Unit Banking)
        // ---------------------------------------------------------
        $empAdminUnit = Employee::firstOrCreate(
            ['npp' => 'DEMO002'],
            [
                'name' => 'Admin Banking Demo',
                'is_active' => 1,
                'email' => 'admin_banking@demo.com',
                'last_login' => now(),
            ]
        );
        $empAdminUnit->update(['is_active' => 1]);

        $userAdminUnit = User::updateOrCreate(
            ['username' => 'admin_banking'],
            [
                'password' => 'password123',
                'employee_id' => $empAdminUnit->id,
                'attempt_count' => 0,
            ]
        );

        EmployeeRole::updateOrCreate(
            [
                'employee_id' => $empAdminUnit->id,
                'role_id' => $adminRole->id,
            ],
            [
                'unit_id' => $unitBanking->id,
                'status_id' => 1,
            ]
        );

        // ---------------------------------------------------------
        // 5. User 3: ROLE AGENT UNIT (Unit Banking)
        // ---------------------------------------------------------
        $empAgentUnit = Employee::firstOrCreate(
            ['npp' => 'DEMO003'],
            [
                'name' => 'Agent Banking Demo',
                'is_active' => 1,
                'email' => 'agent_banking@demo.com',
                'last_login' => now(),
            ]
        );
        $empAgentUnit->update(['is_active' => 1]);

        $userAgentUnit = User::updateOrCreate(
            ['username' => 'agent_banking'],
            [
                'password' => 'password123',
                'employee_id' => $empAgentUnit->id,
                'attempt_count' => 0,
            ]
        );

        EmployeeRole::updateOrCreate(
            [
                'employee_id' => $empAgentUnit->id,
                'role_id' => $agentRole->id,
            ],
            [
                'unit_id' => $unitBanking->id,
                'status_id' => 1,
            ]
        );

        $this->command->info('=== AKUN DEMO BERHASIL DIBUAT ===');
        $this->command->info('1. Super Admin : superadmin_demo / password123');
        $this->command->info('2. Admin Unit  : admin_banking   / password123 (Unit Banking)');
        $this->command->info('3. Agent Unit  : agent_banking   / password123 (Unit Banking)');
    }
}
