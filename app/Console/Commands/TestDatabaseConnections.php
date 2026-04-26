<?php

namespace App\Console\Commands;

use App\Services\DatabaseConnectionService;
use Illuminate\Console\Command;

class TestDatabaseConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:db-connections {--database= : Test specific database only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all MariaDB database connections';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Database Connections...');
        $this->newLine();

        $specificDatabase = $this->option('database');

        if ($specificDatabase) {
            $this->testSingleDatabase($specificDatabase);
        } else {
            $this->testAllDatabases();
        }
    }

    private function testSingleDatabase(string $database)
    {
        $this->info("📋 Testing database: {$database}");

        try {
            $connection = DatabaseConnectionService::getConnection($database);
            $this->line("   Connection: {$connection}");

            $testResult = DatabaseConnectionService::testConnection($database);

            if ($testResult) {
                $this->info('   ✅ Connection successful');

                // Get database name
                $dbInfo = DatabaseConnectionService::queryOn($database, 'SELECT DATABASE() as database_name')[0];
                $this->line("   Database: {$dbInfo->database_name}");

                // Count tables
                $tables = DatabaseConnectionService::getTables($database);
                $this->line('   Tables: ' . count($tables));
            } else {
                $this->error('   ❌ Connection failed');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
    }

    private function testAllDatabases()
    {
        $this->info('📊 Testing all available databases...');
        $this->newLine();

        $dbInfo = DatabaseConnectionService::getDatabaseInfo();

        $this->table(
            ['Database', 'Connection', 'Database Name', 'Env Var', 'Status'],
            array_map(function ($name, $info) {
                return [
                    $name,
                    $info['connection'],
                    $info['database'],
                    $info['env_var'],
                    $info['status'] === 'connected' ? '✅ Connected' : '❌ ' . $info['status']
                ];
            }, array_keys($dbInfo), $dbInfo)
        );

        $this->newLine();
        $this->info('📋 Detailed Information:');
        $this->newLine();

        foreach ($dbInfo as $name => $info) {
            if ($info['status'] === 'connected') {
                $this->info("🗄️  {$name}:");
                $this->line("   Connection: {$info['connection']}");
                $this->line("   Database: {$info['database']}");
                $this->line("   Environment: {$info['env_var']}");

                // Get table count
                try {
                    $tables = DatabaseConnectionService::getTables($name);
                    $this->line('   Tables: ' . count($tables));

                    if (count($tables) > 0) {
                        $this->line('   First 5 tables: ' . implode(', ', array_slice(array_map('current', $tables), 0, 5)));
                    }
                } catch (\Exception $e) {
                    $this->warn('   Could not list tables: ' . $e->getMessage());
                }

                $this->newLine();
            }
        }

        $this->info('💡 Usage Examples:');
        $this->newLine();
        $this->line('   // Query on specific database:');
        $this->line('   $users = DatabaseConnectionService::queryOn("proforma", "SELECT * FROM users LIMIT 5");');
        $this->newLine();
        $this->line('   // Check if table exists:');
        $this->line('   $exists = DatabaseConnectionService::tableExists("finance", "invoices");');
        $this->newLine();
        $this->line('   // Count records:');
        $this->line('   $count = DatabaseConnectionService::countRecords("doc", "documents");');
        $this->newLine();
        $this->line('   // Use model with specific connection:');
        $this->line('   $post = DatabaseConnectionService::model(Post::class, "compilance")->find(1);');
    }
}
