<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DatabaseConnectionService
{
    /**
     * Available database connections
     */
    const CONNECTIONS = [
        'proforma' => 'proforma',
        'compilance' => 'compilance',
        'doc' => 'doc',
        'finance' => 'finance',
        'unico' => 'unico',
        'call' => 'call',
        'default' => 'mariadb',
    ];

    /**
     * Get connection name for database
     */
    public static function getConnection(string $database): string
    {
        return self::CONNECTIONS[$database] ?? 'mariadb';
    }

    /**
     * Switch to specific database connection
     */
    public static function switchTo(string $database): void
    {
        $connection = self::getConnection($database);
        Config::set('database.default', $connection);
        DB::purge($connection);
    }

    /**
     * Get query builder for specific database
     */
    public static function on(string $database)
    {
        $connection = self::getConnection($database);
        return DB::connection($connection);
    }

    /**
     * Get all available databases
     */
    public static function getAvailableDatabases(): array
    {
        return array_keys(self::CONNECTIONS);
    }

    /**
     * Get database info for all connections
     */
    public static function getDatabaseInfo(): array
    {
        $info = [];

        foreach (self::CONNECTIONS as $name => $connection) {
            try {
                $db = DB::connection($connection);
                $databaseName = $db->getDatabaseName();

                $info[$name] = [
                    'connection' => $connection,
                    'database' => $databaseName,
                    'env_var' => 'DB_' . strtoupper($name),
                    'status' => 'connected'
                ];
            } catch (\Exception $e) {
                $info[$name] = [
                    'connection' => $connection,
                    'database' => 'N/A',
                    'env_var' => 'DB_' . strtoupper($name),
                    'status' => 'error: ' . $e->getMessage()
                ];
            }
        }

        return $info;
    }

    /**
     * Test connection to specific database
     */
    public static function testConnection(string $database): bool
    {
        try {
            $connection = self::getConnection($database);
            $db = DB::connection($connection);
            $db->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Execute query on specific database
     */
    public static function queryOn(string $database, string $query, array $bindings = [])
    {
        return self::on($database)->select($query, $bindings);
    }

    /**
     * Get table list from specific database
     */
    public static function getTables(string $database): array
    {
        return self::on($database)->select('SHOW TABLES');
    }

    /**
     * Check if table exists in specific database
     */
    public static function tableExists(string $database, string $table): bool
    {
        $tables = self::getTables($database);
        $tableNames = array_map('current', $tables);
        return in_array($table, $tableNames);
    }

    /**
     * Get record count from table in specific database
     */
    public static function countRecords(string $database, string $table): int
    {
        return self::on($database)->table($table)->count();
    }

    /**
     * Create a model instance with specific database connection
     */
    public static function model(string $modelClass, string $database)
    {
        $connection = self::getConnection($database);
        $model = new $modelClass;
        $model->setConnection($connection);
        return $model;
    }
}
