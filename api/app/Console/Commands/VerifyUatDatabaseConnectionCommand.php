<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class VerifyUatDatabaseConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uat:verify-connection 
                            {--database=isolated_uat : The target database connection name}
                            {--expected-db= : The exact expected run database name (e.g. hbos_uat_run_...)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies the actual connected database identity on the isolated UAT connection before running migrations or serving requests';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connectionName = (string) $this->option('database');
        $expectedDb = (string) $this->option('expected-db');

        $this->info("=================================================================");
        $this->info("   HBOS UAT DATABASE CONNECTION LIVE PRE-FLIGHT VERIFICATION    ");
        $this->info("=================================================================");

        if ($connectionName !== 'isolated_uat') {
            $this->error("[FATAL SECURITY ABORT] Only the 'isolated_uat' connection can be verified for UAT. Received: '{$connectionName}'");
            return 1;
        }

        try {
            $connection = DB::connection($connectionName);
            $driver = $connection->getDriverName();
            $configuredDb = (string) $connection->getDatabaseName();

            // 1. Live Query on PDO connection to detect the REAL server-side database
            if ($driver === 'sqlite') {
                $rows = $connection->select("PRAGMA database_list");
                $liveDb = !empty($rows) ? basename(str_replace('\\', '/', $rows[0]->file ?? '')) : '';
                if (empty($liveDb)) {
                    $liveDb = basename(str_replace('\\', '/', $configuredDb));
                }
            } else {
                $res = $connection->selectOne("SELECT DATABASE() as current_db");
                $liveDb = $res ? (string) $res->current_db : '';
            }

            $liveDbNormalized = strtolower($liveDb);
            $forbiddenDbs = ['hbos', 'production', 'laravel', 'mysql', 'sys', 'information_schema', 'performance_schema', 'test', ''];

            // 2. Reject Forbidden Databases
            if (in_array($liveDbNormalized, $forbiddenDbs, true)) {
                $this->error("[FATAL SECURITY ABORT] Connected database '{$liveDb}' is in the protected forbidden database list!");
                return 1;
            }

            // 3. Enforce Mandatory Run Prefix
            if (!str_starts_with($liveDbNormalized, 'hbos_uat_run_')) {
                $this->error("[FATAL SECURITY ABORT] Connected database '{$liveDb}' does not match the mandatory 'hbos_uat_run_*' prefix!");
                return 1;
            }

            // 4. Validate Against Expected Database if Provided
            if (!empty($expectedDb)) {
                $expectedNormalized = strtolower(basename(str_replace('\\', '/', $expectedDb)));
                if ($liveDbNormalized !== $expectedNormalized) {
                    $this->error("[FATAL SECURITY ABORT] Connected database '{$liveDb}' does not match expected target '{$expectedDb}'!");
                    return 1;
                }
            }

            // 5. Output Safe, Non-Secret Verification Audit
            $host = (string) ($connection->getConfig('host') ?? '127.0.0.1');
            $port = (string) ($connection->getConfig('port') ?? ($driver === 'sqlite' ? 'N/A' : '3306'));
            $user = (string) ($connection->getConfig('username') ?? 'N/A');

            $this->info("Connection Name     : {$connectionName}");
            $this->info("Driver              : {$driver}");
            $this->info("Host                : {$host}");
            $this->info("Port                : {$port}");
            $this->info("User                : {$user}");
            $this->info("Live Connected DB   : {$liveDb}");
            $this->info("Pre-flight Audit    : PASS (Verified strictly isolated UAT database)");
            $this->info("=================================================================");

            return 0;

        } catch (Throwable $e) {
            $this->error("[FATAL SECURITY ABORT] Database verification failed: " . $e->getMessage());
            return 1;
        }
    }
}
