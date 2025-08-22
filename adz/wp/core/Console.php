<?php

namespace AdzWP;

/**
 * CLI Console Helper for development tools
 */
class Console
{
    protected static $commands = [];
    protected $config;
    
    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->registerDefaultCommands();
    }
    
    protected function registerDefaultCommands()
    {
        $this->registerCommand('make:controller', [$this, 'makeController']);
        $this->registerCommand('make:model', [$this, 'makeModel']);
        $this->registerCommand('make:migration', [$this, 'makeMigration']);
        $this->registerCommand('make:config', [$this, 'makeConfig']);
        $this->registerCommand('db:migrate', [$this, 'runMigrations']);
        $this->registerCommand('db:seed', [$this, 'runSeeds']);
        $this->registerCommand('cache:clear', [$this, 'clearCache']);
        $this->registerCommand('log:clear', [$this, 'clearLogs']);
        $this->registerCommand('health:check', [$this, 'healthCheck']);
    }
    
    public static function registerCommand($name, $callback)
    {
        self::$commands[$name] = $callback;
    }
    
    public function run($args)
    {
        if (empty($args) || !isset($args[1])) {
            $this->showHelp();
            return;
        }
        
        $command = $args[1];
        $params = array_slice($args, 2);
        
        if (!isset(self::$commands[$command])) {
            $this->error("Command '$command' not found.");
            $this->showHelp();
            return;
        }
        
        try {
            call_user_func(self::$commands[$command], $params);
        } catch (\Exception $e) {
            $this->error("Error executing command: " . $e->getMessage());
        }
    }
    
    protected function showHelp()
    {
        $this->info("ADZ Plugin Framework CLI");
        $this->info("Usage: php adz <command> [options]");
        $this->info("");
        $this->info("Available commands:");
        
        foreach (array_keys(self::$commands) as $command) {
            $this->info("  $command");
        }
    }
    
    public function makeController($params)
    {
        if (empty($params[0])) {
            $this->error("Controller name is required.");
            $this->info("Usage: php adz make:controller ControllerName");
            return;
        }
        
        $name = $params[0];
        $className = str_replace('Controller', '', $name) . 'Controller';
        $fileName = $className . '.php';
        $filePath = getcwd() . '/src/controllers/' . $fileName;
        
        if (file_exists($filePath)) {
            $this->error("Controller already exists: $filePath");
            return;
        }
        
        $template = $this->getControllerTemplate($className);
        
        if (!is_dir(dirname($filePath))) {
            wp_mkdir_p(dirname($filePath));
        }
        
        file_put_contents($filePath, $template);
        
        $this->success("Controller created: $filePath");
    }
    
    public function makeModel($params)
    {
        if (empty($params[0])) {
            $this->error("Model name is required.");
            $this->info("Usage: php adz make:model ModelName");
            return;
        }
        
        $name = $params[0];
        $className = str_replace('Model', '', $name) . 'Model';
        $fileName = $className . '.php';
        $filePath = getcwd() . '/src/models/' . $fileName;
        
        if (file_exists($filePath)) {
            $this->error("Model already exists: $filePath");
            return;
        }
        
        $template = $this->getModelTemplate($className);
        
        if (!is_dir(dirname($filePath))) {
            wp_mkdir_p(dirname($filePath));
        }
        
        file_put_contents($filePath, $template);
        
        $this->success("Model created: $filePath");
    }
    
    public function makeMigration($params)
    {
        if (empty($params[0])) {
            $this->error("Migration name is required.");
            $this->info("Usage: php adz make:migration create_users_table");
            return;
        }
        
        $name = $params[0];
        $timestamp = date('Y_m_d_His');
        $fileName = $timestamp . '_' . $name . '.php';
        $filePath = getcwd() . '/database/migrations/' . $fileName;
        
        $template = $this->getMigrationTemplate($name);
        
        if (!is_dir(dirname($filePath))) {
            wp_mkdir_p(dirname($filePath));
        }
        
        file_put_contents($filePath, $template);
        
        $this->success("Migration created: $filePath");
    }
    
    public function makeConfig($params)
    {
        $configPath = $this->config->getConfigPath();
        
        if (!is_dir($configPath)) {
            wp_mkdir_p($configPath);
        }
        
        $configs = [
            'app.php' => $this->getAppConfigTemplate(),
            'database.php' => $this->getDatabaseConfigTemplate(),
            'logging.php' => $this->getLoggingConfigTemplate(),
            'security.php' => $this->getSecurityConfigTemplate(),
            'cache.php' => $this->getCacheConfigTemplate()
        ];
        
        foreach ($configs as $file => $content) {
            $filePath = $configPath . $file;
            
            if (!file_exists($filePath)) {
                file_put_contents($filePath, $content);
                $this->success("Config created: $filePath");
            } else {
                $this->warning("Config already exists: $filePath");
            }
        }
    }
    
    public function runMigrations($params)
    {
        $migrationsPath = getcwd() . '/database/migrations/';
        
        if (!is_dir($migrationsPath)) {
            $this->error("Migrations directory not found: $migrationsPath");
            return;
        }
        
        $files = glob($migrationsPath . '*.php');
        sort($files);
        
        $this->info("Running migrations...");
        
        foreach ($files as $file) {
            $this->info("Migrating: " . basename($file));
            
            try {
                include $file;
                $this->success("Migrated: " . basename($file));
            } catch (\Exception $e) {
                $this->error("Failed to migrate " . basename($file) . ": " . $e->getMessage());
            }
        }
        
        $this->success("Migrations completed!");
    }
    
    public function runSeeds($params)
    {
        $seedsPath = getcwd() . '/database/seeds/';
        
        if (!is_dir($seedsPath)) {
            $this->error("Seeds directory not found: $seedsPath");
            return;
        }
        
        $files = glob($seedsPath . '*.php');
        
        $this->info("Running seeds...");
        
        foreach ($files as $file) {
            $this->info("Seeding: " . basename($file));
            
            try {
                include $file;
                $this->success("Seeded: " . basename($file));
            } catch (\Exception $e) {
                $this->error("Failed to seed " . basename($file) . ": " . $e->getMessage());
            }
        }
        
        $this->success("Seeding completed!");
    }
    
    public function clearCache($params)
    {
        // Clear WordPress transients
        global $wpdb;
        
        $this->info("Clearing cache...");
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'");
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        $this->success("Cache cleared!");
    }
    
    public function clearLogs($params)
    {
        $logger = Log::getInstance();
        $logPath = $logger->getLogPath();
        
        if (file_exists($logPath)) {
            unlink($logPath);
            $this->success("Logs cleared!");
        } else {
            $this->info("No log file found.");
        }
    }
    
    public function healthCheck($params)
    {
        $this->info("Running health checks...");
        
        // Check database connection
        try {
            $db = Database::getInstance();
            $db->getValue("SELECT 1");
            $this->success("Database: OK");
        } catch (\Exception $e) {
            $this->error("Database: FAILED - " . $e->getMessage());
        }
        
        // Check file permissions
        $paths = [
            getcwd() . '/src/',
            $this->config->getConfigPath(),
            WP_CONTENT_DIR . '/adz-logs/'
        ];
        
        foreach ($paths as $path) {
            if (is_writable($path)) {
                $this->success("Writable: $path");
            } else {
                $this->error("Not writable: $path");
            }
        }
        
        // Check required extensions
        $extensions = ['curl', 'json', 'mbstring'];
        
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $this->success("Extension: $ext");
            } else {
                $this->error("Missing extension: $ext");
            }
        }
        
        $this->info("Health check completed!");
    }
    
    protected function getControllerTemplate($className)
    {
        $namespace = $this->config->get('plugin.namespace', 'YourPlugin');
        
        return "<?php

namespace {$namespace}\\Controllers;

use AdzFramework\WordPress\\Controller;

class {$className} extends Controller 
{
    public \$actions = [
        // 'init' => 'initialize',
    ];
    
    public \$filters = [
        // 'the_content' => 'modifyContent',
    ];
    
    protected function bootstrap()
    {
        // Initialization code here
    }
    
    // Add your methods here
}";
    }
    
    protected function getModelTemplate($className)
    {
        $namespace = $this->config->get('plugin.namespace', 'YourPlugin');
        
        return "<?php

namespace {$namespace}\\Models;

use AdzFramework\Core\\Database;

class {$className}
{
    protected \$db;
    protected \$table = 'your_table';
    
    public function __construct()
    {
        \$this->db = Database::getInstance();
    }
    
    public function find(\$id)
    {
        return \$this->db->table(\$this->table)
            ->where('id', \$id)
            ->first();
    }
    
    public function all()
    {
        return \$this->db->table(\$this->table)->get();
    }
    
    public function create(\$data)
    {
        return \$this->db->table(\$this->table)->insert(\$data);
    }
    
    public function update(\$id, \$data)
    {
        return \$this->db->table(\$this->table)
            ->where('id', \$id)
            ->update(\$data);
    }
    
    public function delete(\$id)
    {
        return \$this->db->table(\$this->table)
            ->where('id', \$id)
            ->delete();
    }
}";
    }
    
    protected function getMigrationTemplate($name)
    {
        return "<?php

use AdzFramework\Core\\Database;

// Migration: {$name}

\$db = Database::getInstance();

// Create table
\$db->createTable('your_table', [
    'columns' => [
        'id' => [
            'type' => 'bigint',
            'length' => 20,
            'null' => false,
            'auto_increment' => true
        ],
        'name' => [
            'type' => 'varchar',
            'length' => 255,
            'null' => false
        ],
        'email' => [
            'type' => 'varchar',
            'length' => 255,
            'null' => false
        ],
        'created_at' => [
            'type' => 'datetime',
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP'
        ],
        'updated_at' => [
            'type' => 'datetime',
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]
    ],
    'indexes' => [
        'PRIMARY' => [
            'type' => 'PRIMARY',
            'columns' => 'id'
        ],
        'email_unique' => [
            'type' => 'UNIQUE',
            'columns' => 'email'
        ]
    ]
]);";
    }
    
    protected function getAppConfigTemplate()
    {
        return "<?php

return [
    'plugin' => [
        'name' => 'My Plugin',
        'version' => '1.0.0',
        'text_domain' => 'my-plugin',
        'slug' => 'my-plugin',
        'namespace' => 'MyPlugin'
    ],
    'admin' => [
        'menu_title' => 'My Plugin',
        'menu_slug' => 'my-plugin-admin',
        'capability' => 'manage_options',
        'icon' => 'dashicons-admin-tools',
        'position' => 25
    ]
];";
    }
    
    protected function getDatabaseConfigTemplate()
    {
        return "<?php

return [
    'database' => [
        'prefix' => 'mp_',
        'charset' => 'utf8mb4',
        'collate' => 'utf8mb4_unicode_ci'
    ]
];";
    }
    
    protected function getLoggingConfigTemplate()
    {
        return "<?php

return [
    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'max_file_size' => 10485760, // 10MB
        'max_files' => 5
    ]
];";
    }
    
    protected function getSecurityConfigTemplate()
    {
        return "<?php

return [
    'security' => [
        'enable_nonce' => true,
        'enable_csrf' => true,
        'enable_rate_limiting' => true,
        'rate_limit_attempts' => 60,
        'rate_limit_window' => 3600
    ]
];";
    }
    
    protected function getCacheConfigTemplate()
    {
        return "<?php

return [
    'cache' => [
        'enabled' => true,
        'default_ttl' => 3600,
        'driver' => 'transient'
    ]
];";
    }
    
    protected function info($message)
    {
        echo "\033[0;32m[INFO]\033[0m $message\n";
    }
    
    protected function success($message)
    {
        echo "\033[0;32m[SUCCESS]\033[0m $message\n";
    }
    
    protected function warning($message)
    {
        echo "\033[0;33m[WARNING]\033[0m $message\n";
    }
    
    protected function error($message)
    {
        echo "\033[0;31m[ERROR]\033[0m $message\n";
    }
}