#!/bin/bash

echo "Welcome to AdzWP! Where we put all the bullshit aside and start coding."
echo "Or as I like to call it, 'Operation CWAL' (Can't Wait Any Longer)."
echo ""

# Initialize permissions
if [[ "$1" == "init" ]]; then
  chmod u+x adz.sh
  chmod -R u+x tools
  echo "Permissions set successfully!"
  exit 0
fi

# Legacy asset management
if [[ "$1" == "asset" ]]; then
  ./tools/manage-assets.sh 
  exit 0
fi

# Check if WordPress is available (for CLI commands)
if [[ -f "wp-config.php" ]] || [[ -f "../wp-config.php" ]] || [[ -f "../../wp-config.php" ]]; then
  # Use WordPress CLI system
  php -r "
    // Find WordPress bootstrap
    \$wp_config_paths = ['wp-config.php', '../wp-config.php', '../../wp-config.php'];
    \$wp_config = null;
    
    foreach (\$wp_config_paths as \$path) {
      if (file_exists(\$path)) {
        \$wp_config = \$path;
        break;
      }
    }
    
    if (!\$wp_config) {
      echo 'WordPress not found. Please run from WordPress root or plugin directory.\n';
      exit(1);
    }
    
    // Minimal WordPress bootstrap
    define('ABSPATH', dirname(realpath(\$wp_config)) . '/');
    
    if (!defined('WP_CONTENT_DIR')) {
      define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
    }
    
    // Load WordPress configuration
    require_once \$wp_config;
    
    // Load WordPress
    require_once ABSPATH . 'wp-settings.php';
    
    // Load our framework
    if (file_exists('adz/dev-tools/hive/Console.php')) {
      require_once 'adz/dev-tools/hive/Console.php';
      require_once 'adz/dev-tools/hive/Config.php';
      require_once 'adz/dev-tools/hive/Log.php';
      require_once 'adz/dev-tools/hive/Exception.php';
      require_once 'adz/dev-tools/hive/Database.php';
      require_once 'adz/dev-tools/hive/helpers/functions.php';
      
      \$console = new AdzHive\\Console();
      \$console->run(\$argv);
    } else {
      echo 'ADZ Framework not found. Please ensure the framework is properly installed.\n';
      exit(1);
    }
  " -- "$@"
else
  echo "WordPress installation not found."
  echo "Please run this command from your WordPress root directory or plugin directory."
  echo ""
  echo "Available commands without WordPress:"
  echo "  ./adz.sh init     - Initialize file permissions"
  echo "  ./adz.sh asset    - Manage assets (legacy)"
  echo ""
  echo "With WordPress available:"
  echo "  ./adz.sh make:controller ControllerName"
  echo "  ./adz.sh make:model ModelName"
  echo "  ./adz.sh make:migration migration_name"
  echo "  ./adz.sh make:config"
  echo "  ./adz.sh db:migrate"
  echo "  ./adz.sh db:seed"
  echo "  ./adz.sh cache:clear"
  echo "  ./adz.sh log:clear"
  echo "  ./adz.sh health:check"
fi