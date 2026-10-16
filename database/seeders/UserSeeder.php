<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Unit (e.g., PortalBank)
        $unit = \App\Models\Unit::create([
            'parent_id' => null,
            'type' => 1,
            'code' => '001',
            'short_name' => 'HQ',
            'name' => 'Headquarters',
            'address' => 'Sample Address',
            'is_active' => 1,
        ]);

        // Create Employee
        $employee = \App\Models\Employee::create([
            'npp' => '12345',
            'name' => 'Administrator',
            'is_active' => 1,
            'email' => 'admin@portalbcca.com',
            'last_login' => now(),
        ]);

        // Create User (Plaintext password as per legacy logic)
        $user = \App\Models\User::create([
            'username' => 'admin',
            'password' => 'password', // Plaintext
            'employee_id' => $employee->id,
            'last_login' => now(),
            'attempt_count' => 0,
        ]);

        // Create Employee Role (Admin Role)
        \App\Models\EmployeeRole::create([
            'employee_id' => $employee->id,
            'unit_id' => $unit->id,
            'role_id' => 1, // Admin logic triggers on role_id 1
            'status_id' => 1,
        ]);

        // Additional User for specific portal testing if needed
        // ...
    }
}
