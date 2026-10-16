<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SystemErrorLog;
use App\Models\Employee;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_super_admin_can_update_error_log_status(): void
    {
        $employee = Employee::create([
            'npp' => '12345',
            'name' => 'Super Admin Test',
            'is_active' => 1,
            'email' => 'admin@test.com',
        ]);

        $user = User::create([
            'username' => 'superadmin',
            'password' => bcrypt('password'),
            'employee_id' => $employee->id,
        ]);

        $log = SystemErrorLog::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'error_class' => 'Exception',
            'error_message' => 'Something went wrong',
            'explanation' => 'Simulasi error',
            'suggestion' => 'Perbaiki kode',
            'status' => 'Error',
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/error-logs/{$log->id}/status", [
                'status' => 'Solved',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Status log error berhasil diperbarui.',
            ]);

        $this->assertDatabaseHas('system_error_logs', [
            'id' => $log->id,
            'status' => 'Solved',
        ]);
    }

    public function test_non_super_admin_cannot_update_error_log_status(): void
    {
        $employee = Employee::create([
            'npp' => '67890',
            'name' => 'Regular User Test',
            'is_active' => 1,
            'email' => 'user@test.com',
        ]);

        $user = User::create([
            'username' => 'regular_user',
            'password' => bcrypt('password'),
            'employee_id' => $employee->id,
        ]);

        $log = SystemErrorLog::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'error_class' => 'Exception',
            'error_message' => 'Something went wrong',
            'explanation' => 'Simulasi error',
            'suggestion' => 'Perbaiki kode',
            'status' => 'Error',
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/api/error-logs/{$log->id}/status", [
                'status' => 'Solved',
            ]);

        $response->assertStatus(403);
    }

    public function test_security_scan_automatically_resolves_unresolved_malware_logs_if_clean(): void
    {
        $employee = Employee::create([
            'npp' => '12345',
            'name' => 'Super Admin Test',
            'is_active' => 1,
            'email' => 'admin@test.com',
        ]);

        $user = User::create([
            'username' => 'superadmin',
            'password' => bcrypt('password'),
            'employee_id' => $employee->id,
        ]);

        // Create an unresolved malware exception log
        $log = SystemErrorLog::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'error_class' => 'App\Exceptions\Security\MalwareInfectionException',
            'error_message' => 'Malware detected',
            'explanation' => 'Malware detected explanation',
            'suggestion' => 'Clean it',
            'status' => 'Error',
        ]);

        // Run the security scan endpoint
        $response = $this->actingAs($user)
            ->postJson("/api/security/scan");

        $response->assertStatus(200);

        // Check that the log is now Solved
        $this->assertDatabaseHas('system_error_logs', [
            'id' => $log->id,
            'status' => 'Solved',
        ]);
    }
}

