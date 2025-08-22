<?php

namespace AdzWP\Db;

/**
 * Database Migrator
 * 
 * Handles running and tracking database migrations
 */
class Migrator
{
    protected $connection;
    protected $migrationsTable = 'migrations';
    protected $migrationsPath;

    public function __construct($migrationsPath = null)
    {
        $this->connection = Connection::getInstance();
        $this->migrationsPath = $migrationsPath ?: $this->getDefaultMigrationsPath();
        $this->ensureMigrationsTableExists();
    }

    /**
     * Get the default migrations path
     */
    protected function getDefaultMigrationsPath()
    {
        return defined('ADZ_PLUGIN_PATH') ? ADZ_PLUGIN_PATH . 'migrations/' : './migrations/';
    }

    /**
     * Ensure the migrations table exists
     */
    protected function ensureMigrationsTableExists()
    {
        $table = $this->connection->getTable($this->migrationsTable);
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `migration` varchar(255) NOT NULL,
            `batch` int(11) NOT NULL,
            `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->connection->query($sql);
    }

    /**
     * Run all pending migrations
     */
    public function migrate()
    {
        $pendingMigrations = $this->getPendingMigrations();
        
        if (empty($pendingMigrations)) {
            adz_log_info('No pending migrations found');
            return [];
        }
        
        $batch = $this->getNextBatchNumber();
        $executed = [];
        
        foreach ($pendingMigrations as $migration) {
            try {
                $this->runMigration($migration, $batch);
                $executed[] = $migration;
                adz_log_info("Migrated: {$migration}");
            } catch (\Exception $e) {
                adz_log_error("Migration failed: {$migration} - " . $e->getMessage());
                throw $e;
            }
        }
        
        return $executed;
    }

    /**
     * Rollback the last batch of migrations
     */
    public function rollback($steps = 1)
    {
        $batches = $this->getExecutedBatches();
        
        if (empty($batches)) {
            adz_log_info('No migrations to rollback');
            return [];
        }
        
        $rolledBack = [];
        
        for ($i = 0; $i < $steps && !empty($batches); $i++) {
            $batch = array_pop($batches);
            $migrations = $this->getMigrationsInBatch($batch);
            
            // Rollback in reverse order
            $migrations = array_reverse($migrations);
            
            foreach ($migrations as $migration) {
                try {
                    $this->rollbackMigration($migration);
                    $rolledBack[] = $migration;
                    adz_log_info("Rolled back: {$migration}");
                } catch (\Exception $e) {
                    adz_log_error("Rollback failed: {$migration} - " . $e->getMessage());
                    throw $e;
                }
            }
        }
        
        return $rolledBack;
    }

    /**
     * Reset all migrations
     */
    public function reset()
    {
        $migrations = $this->getExecutedMigrations();
        
        // Rollback in reverse order
        $migrations = array_reverse($migrations);
        $rolledBack = [];
        
        foreach ($migrations as $migration) {
            try {
                $this->rollbackMigration($migration);
                $rolledBack[] = $migration;
                adz_log_info("Reset: {$migration}");
            } catch (\Exception $e) {
                adz_log_error("Reset failed: {$migration} - " . $e->getMessage());
                throw $e;
            }
        }
        
        return $rolledBack;
    }

    /**
     * Get migration status
     */
    public function status()
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        $status = [];
        
        foreach ($allMigrations as $migration) {
            $status[] = [
                'migration' => $migration,
                'status' => in_array($migration, $executedMigrations) ? 'Executed' : 'Pending'
            ];
        }
        
        return $status;
    }

    /**
     * Get all migration files
     */
    protected function getAllMigrationFiles()
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }
        
        $files = glob($this->migrationsPath . '*.php');
        $migrations = [];
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            $migrations[] = $filename;
        }
        
        sort($migrations);
        return $migrations;
    }

    /**
     * Get pending migrations
     */
    protected function getPendingMigrations()
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        return array_diff($allMigrations, $executedMigrations);
    }

    /**
     * Get executed migrations
     */
    protected function getExecutedMigrations()
    {
        $table = $this->connection->getTable($this->migrationsTable);
        $sql = "SELECT migration FROM `{$table}` ORDER BY id";
        
        $results = $this->connection->getResults($sql);
        $migrations = [];
        
        foreach ($results as $result) {
            $migrations[] = $result->migration;
        }
        
        return $migrations;
    }

    /**
     * Get executed batches
     */
    protected function getExecutedBatches()
    {
        $table = $this->connection->getTable($this->migrationsTable);
        $sql = "SELECT DISTINCT batch FROM `{$table}` ORDER BY batch";
        
        $results = $this->connection->getResults($sql);
        $batches = [];
        
        foreach ($results as $result) {
            $batches[] = (int) $result->batch;
        }
        
        return $batches;
    }

    /**
     * Get migrations in a specific batch
     */
    protected function getMigrationsInBatch($batch)
    {
        $table = $this->connection->getTable($this->migrationsTable);
        $sql = $this->connection->prepare(
            "SELECT migration FROM `{$table}` WHERE batch = %d ORDER BY id",
            $batch
        );
        
        $results = $this->connection->getResults($sql);
        $migrations = [];
        
        foreach ($results as $result) {
            $migrations[] = $result->migration;
        }
        
        return $migrations;
    }

    /**
     * Get the next batch number
     */
    protected function getNextBatchNumber()
    {
        $table = $this->connection->getTable($this->migrationsTable);
        $sql = "SELECT MAX(batch) as max_batch FROM `{$table}`";
        
        $result = $this->connection->getVar($sql);
        
        return $result ? (int) $result + 1 : 1;
    }

    /**
     * Run a single migration
     */
    protected function runMigration($migration, $batch)
    {
        $migrationInstance = $this->loadMigration($migration);
        
        // Run the migration
        $migrationInstance->up();
        
        // Record the migration
        $this->recordMigration($migration, $batch);
    }

    /**
     * Rollback a single migration
     */
    protected function rollbackMigration($migration)
    {
        $migrationInstance = $this->loadMigration($migration);
        
        // Rollback the migration
        $migrationInstance->down();
        
        // Remove the migration record
        $this->removeMigrationRecord($migration);
    }

    /**
     * Load a migration instance
     */
    protected function loadMigration($migration)
    {
        $filepath = $this->migrationsPath . $migration . '.php';
        
        if (!file_exists($filepath)) {
            throw new \Exception("Migration file not found: {$filepath}");
        }
        
        require_once $filepath;
        
        // Convert filename to class name
        // e.g., 2024_01_01_000000_create_users_table -> CreateUsersTable
        $className = $this->migrationToClassName($migration);
        
        if (!class_exists($className)) {
            throw new \Exception("Migration class not found: {$className}");
        }
        
        return new $className();
    }

    /**
     * Convert migration filename to class name
     */
    protected function migrationToClassName($migration)
    {
        // Remove timestamp prefix and convert to PascalCase
        $parts = explode('_', $migration);
        
        // Skip first 4 parts (timestamp: YYYY_MM_DD_HHMMSS)
        $nameParts = array_slice($parts, 4);
        
        return implode('', array_map('ucfirst', $nameParts));
    }

    /**
     * Record a migration as executed
     */
    protected function recordMigration($migration, $batch)
    {
        $this->connection->insert($this->migrationsTable, [
            'migration' => $migration,
            'batch' => $batch
        ]);
    }

    /**
     * Remove a migration record
     */
    protected function removeMigrationRecord($migration)
    {
        $this->connection->delete($this->migrationsTable, [
            'migration' => $migration
        ]);
    }

    /**
     * Create a new migration file
     */
    public function createMigration($name)
    {
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $this->migrationsPath . $filename;
        
        // Ensure migrations directory exists
        if (!is_dir($this->migrationsPath)) {
            wp_mkdir_p($this->migrationsPath);
        }
        
        // Generate class name
        $className = $this->migrationToClassName($timestamp . '_' . $name);
        
        // Create migration stub
        $stub = $this->getMigrationStub($className);
        
        file_put_contents($filepath, $stub);
        
        return $filepath;
    }

    /**
     * Get migration stub template
     */
    protected function getMigrationStub($className)
    {
        return "<?php

use AdzWP\\Migration;

class {$className} extends Migration
{
    /**
     * Run the migration
     */
    public function up()
    {
        // Example: Create a new table
        \$this->createTable('example_table', function(\$table) {
            \$table->id();
            \$table->string('name');
            \$table->text('description')->nullable();
            \$table->boolean('is_active')->default(1);
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        \$this->dropTable('example_table');
    }
}
";
    }
}