<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IsolatedUatConnectionGuardTest extends TestCase
{
    /**
     * Test that an explicitly requested isolated_uat connection pointing to 'hbos' is rejected with FATAL SECURITY ABORT.
     */
    public function test_isolated_uat_connection_blocks_forbidden_hbos_database(): void
    {
        // Reconfigure isolated_uat using sqlite driver for local test isolation without hitting MariaDB
        config([
            'database.connections.isolated_uat' => [
                'driver' => 'sqlite',
                'database' => 'hbos',
                'prefix' => '',
            ],
        ]);
        DB::purge('isolated_uat');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('[FATAL SECURITY ABORT]');

        // Execute query on explicitly named connection
        DB::connection('isolated_uat')->select('SELECT 1');
    }

    /**
     * Test that an explicitly requested isolated_uat connection with an empty database is rejected.
     */
    public function test_isolated_uat_connection_blocks_empty_database(): void
    {
        config([
            'database.connections.isolated_uat' => [
                'driver' => 'sqlite',
                'database' => '',
                'prefix' => '',
            ],
        ]);
        DB::purge('isolated_uat');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('[FATAL SECURITY ABORT]');

        DB::connection('isolated_uat')->select('SELECT 1');
    }

    /**
     * Test that an arbitrary non-prefixed database name is rejected.
     */
    public function test_isolated_uat_connection_blocks_non_prefixed_database(): void
    {
        config([
            'database.connections.isolated_uat' => [
                'driver' => 'sqlite',
                'database' => 'some_arbitrary_customer_db',
                'prefix' => '',
            ],
        ]);
        DB::purge('isolated_uat');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('[FATAL SECURITY ABORT]');

        DB::connection('isolated_uat')->select('SELECT 1');
    }

    /**
     * Test that an explicitly requested isolated_uat connection with a valid run name is permitted.
     */
    public function test_isolated_uat_connection_permits_valid_run_specific_database(): void
    {
        $tempDb = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hbos_uat_run_valid_' . uniqid() . '.sqlite';
        touch($tempDb);

        try {
            config([
                'database.connections.isolated_uat' => [
                    'driver' => 'sqlite',
                    'database' => $tempDb,
                    'prefix' => '',
                ],
            ]);
            DB::purge('isolated_uat');

            $result = DB::connection('isolated_uat')->select('SELECT 1 as val');
            $this->assertNotEmpty($result);
            $this->assertEquals(1, $result[0]->val);
        } finally {
            DB::purge('isolated_uat');
            if (file_exists($tempDb)) {
                @unlink($tempDb);
            }
        }
    }

    /**
     * Test that the default testing connection is not blocked or disrupted.
     */
    public function test_default_test_connection_remains_unaffected(): void
    {
        $result = DB::select('SELECT 1 as val');
        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result[0]->val);
    }

    /**
     * Test that the uat:verify-connection artisan command succeeds with a valid isolated UAT database.
     */
    public function test_verify_uat_command_succeeds_with_valid_run_database(): void
    {
        $tempDb = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hbos_uat_run_cmd_' . uniqid() . '.sqlite';
        touch($tempDb);

        try {
            config([
                'database.connections.isolated_uat' => [
                    'driver' => 'sqlite',
                    'database' => $tempDb,
                    'prefix' => '',
                ],
            ]);
            DB::purge('isolated_uat');

            $this->artisan('uat:verify-connection', [
                '--database' => 'isolated_uat',
                '--expected-db' => basename($tempDb),
            ])->assertExitCode(0);
        } finally {
            DB::purge('isolated_uat');
            if (file_exists($tempDb)) {
                @unlink($tempDb);
            }
        }
    }

    /**
     * Test that the uat:verify-connection artisan command fails closed if targeting a forbidden database.
     */
    public function test_verify_uat_command_fails_on_forbidden_database(): void
    {
        config([
            'database.connections.isolated_uat' => [
                'driver' => 'sqlite',
                'database' => 'hbos',
                'prefix' => '',
            ],
        ]);
        DB::purge('isolated_uat');

        $this->artisan('uat:verify-connection', [
            '--database' => 'isolated_uat',
            '--expected-db' => 'hbos',
        ])->assertExitCode(1);
    }

    /**
     * Test that the uat:verify-connection artisan command fails closed on database name mismatch.
     */
    public function test_verify_uat_command_fails_on_database_mismatch(): void
    {
        $tempDb = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hbos_uat_run_mismatch_' . uniqid() . '.sqlite';
        touch($tempDb);

        try {
            config([
                'database.connections.isolated_uat' => [
                    'driver' => 'sqlite',
                    'database' => $tempDb,
                    'prefix' => '',
                ],
            ]);
            DB::purge('isolated_uat');

            $this->artisan('uat:verify-connection', [
                '--database' => 'isolated_uat',
                '--expected-db' => 'hbos_uat_run_different_name',
            ])->assertExitCode(1);
        } finally {
            DB::purge('isolated_uat');
            if (file_exists($tempDb)) {
                @unlink($tempDb);
            }
        }
    }

    /**
     * Test that the uat:verify-connection artisan command rejects unapproved connection names.
     */
    public function test_verify_uat_command_fails_on_wrong_connection(): void
    {
        $this->artisan('uat:verify-connection', [
            '--database' => 'sqlite',
        ])->assertExitCode(1);
    }
}
