<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\Events\StatementPrepared;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerIsolatedUatGuard();
    }

    /**
     * P0 Database Guard: Prevent any query on isolated_uat from touching protected or ambiguous databases.
     * Guards both default connection and explicitly requested connections (--database=isolated_uat).
     */
    protected function registerIsolatedUatGuard(): void
    {
        if (!class_exists(DB::class)) {
            return;
        }

        $guardAction = function ($connection, string $phase = 'query') {
            if (!$connection || $connection->getName() !== 'isolated_uat') {
                return;
            }

            $rawDbName = (string) $connection->getDatabaseName();
            $dbName = strtolower(basename(str_replace('\\', '/', $rawDbName)));
            $forbidden = ['hbos', 'production', 'laravel', 'mysql', 'sys', 'information_schema', 'performance_schema', 'test', ''];

            if (in_array($dbName, $forbidden, true) || !str_starts_with($dbName, 'hbos_uat_run_')) {
                throw new \RuntimeException(
                    "[FATAL SECURITY ABORT] Isolated UAT connection rejected ($phase). Forbidden or unverified database: '{$rawDbName}'"
                );
            }
        };

        // 1. Guard default connection (invoked if DB_CONNECTION=isolated_uat)
        DB::beforeExecuting(function ($query, $bindings, $connection) use ($guardAction) {
            $guardAction($connection, 'default beforeExecuting');
        });

        // 2. Extend 'isolated_uat' connection resolver so beforeExecuting hook is attached directly
        // to any Connection instance created for 'isolated_uat' (e.g. via --database=isolated_uat)
        if ($this->app->bound('db')) {
            $this->app['db']->extend('isolated_uat', function ($config, $name) use ($guardAction) {
                $factory = $this->app['db.factory'];
                $connection = $factory->make($config, $name);

                $connection->beforeExecuting(function ($query, $bindings, $conn) use ($guardAction) {
                    $guardAction($conn, 'isolated_uat beforeExecuting');
                });

                return $connection;
            });
        }

        // 3. Global Event: ConnectionEstablished
        // Intercepts the moment ANY named connection establishes a PDO session
        if (class_exists(Event::class)) {
            Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) use ($guardAction) {
                if ($event->connection && $event->connection->getName() === 'isolated_uat') {
                    $guardAction($event->connection, 'ConnectionEstablished');
                    $event->connection->beforeExecuting(function ($query, $bindings, $conn) use ($guardAction) {
                        $guardAction($conn, 'event beforeExecuting');
                    });
                }
            });

            // 4. Global Event: StatementPrepared
            // Intercepts before any PDO statement executes on any connection
            Event::listen(StatementPrepared::class, function (StatementPrepared $event) use ($guardAction) {
                if ($event->connection && $event->connection->getName() === 'isolated_uat') {
                    $guardAction($event->connection, 'StatementPrepared');
                }
            });
        }
    }
}
