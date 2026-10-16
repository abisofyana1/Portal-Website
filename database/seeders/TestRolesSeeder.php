<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lookup;
use App\Models\Unit;
use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeRole;

class TestRolesSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Roles (Lookups)
        $adminRole = Lookup::firstOrCreate(
            ['name' => 'Admin'],
            ['type' => 'role', 'value' => 1, 'order_no' => 1]
        );

        $agentRole = Lookup::firstOrCreate(
            ['name' => 'Agent'],
            ['type' => 'role', 'value' => 2, 'order_no' => 2]
        );

        // 2. Create Units
        $unitBank = Unit::firstOrCreate(
            ['name' => 'Banking'],
            ['parent_id' => 0, 'type' => 1, 'code' => 'BNK', 'short_name' => 'Bank', 'is_active' => 1]
        );

        $unitCredit = Unit::firstOrCreate(
            ['name' => 'Credit'],
            ['parent_id' => 0, 'type' => 1, 'code' => 'CRD', 'short_name' => 'Credit', 'is_active' => 1]
        );

        // 3. Create Admin User
        $empAdmin = Employee::create([
            'npp' => '90001',
            'name' => 'Test Admin User',
            'is_active' => 1,
            'email' => 'admin_test@bni.co.id',
            'last_login' => now(),
        ]);

        User::create([
            'username' => 'user_admin',
            'password' => 'password', // Plain text as per current system
            'employee_id' => $empAdmin->id,
            'attempt_count' => 0,
        ]);

        EmployeeRole::create([
            'employee_id' => $empAdmin->id,
            'role_id' => $adminRole->id,
            'unit_id' => $unitBank->id,
            'status_id' => 1,
        ]);

        // 4. Create Agent User
        $empAgent = Employee::create([
            'npp' => '90002',
            'name' => 'Test Agent User',
            'is_active' => 1,
            'email' => 'agent_test@bni.co.id',
            'last_login' => now(),
        ]);

        User::create([
            'username' => 'user_agent',
            'password' => 'password',
            'employee_id' => $empAgent->id,
            'attempt_count' => 0,
        ]);

        EmployeeRole::create([
            'employee_id' => $empAgent->id,
            'role_id' => $agentRole->id,
            'unit_id' => $unitCredit->id,
            'status_id' => 1,
        ]);

        $this->command->info('Test users created: user_admin (Admin) and user_agent (Agent) with password "password".');
    }
}
