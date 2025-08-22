#!/bin/bash

# ADZ WordPress Plugin Framework - Code Generation Tool
# Built for developers who love MVC architecture and rapid development

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Framework paths
FRAMEWORK_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_DIR="$FRAMEWORK_DIR/src"
MIGRATIONS_DIR="$FRAMEWORK_DIR/migrations"
TEMPLATES_DIR="$FRAMEWORK_DIR/templates"

# Show header
show_header() {
    echo -e "${BLUE}🚀 ADZ WordPress Plugin Framework${NC}"
    echo -e "${YELLOW}   The intuitive MVC framework for WordPress plugin development${NC}"
    echo ""
}

# Show usage information
show_usage() {
    show_header
    echo "Usage: ./adz.sh [command] [options]"
    echo ""
    echo -e "${GREEN}📦 Code Generation:${NC}"
    echo "  make:controller <name>      Create a new controller"
    echo "  make:model <name>           Create a new model"
    echo "  make:view <name>            Create a new view template"
    echo "  make:migration <name>       Create a new database migration"
    echo ""
    echo -e "${GREEN}🗄️  Database Management:${NC}"
    echo "  migrate                     Run pending migrations"
    echo "  migrate:rollback [steps]    Rollback migrations (default: 1 step)"
    echo "  migrate:reset               Reset all migrations"
    echo "  migrate:status              Show migration status"
    echo ""
    echo -e "${GREEN}🔧 Framework Commands:${NC}"
    echo "  init                        Initialize framework permissions"
    echo "  test                        Run framework tests"
    echo "  test:unit                   Run unit tests only"
    echo "  test:coverage               Run tests with coverage"
    echo ""
    echo -e "${GREEN}Examples:${NC}"
    echo "  ./adz.sh make:controller PostController"
    echo "  ./adz.sh make:model User --migration"
    echo "  ./adz.sh make:view posts/index"
    echo "  ./adz.sh make:migration create_posts_table"
    echo "  ./adz.sh migrate"
}

# Utility functions
log_success() {
    echo -e "${GREEN}✓${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1" >&2
}

log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Ensure directory exists
ensure_dir() {
    if [[ ! -d "$1" ]]; then
        mkdir -p "$1"
        log_info "Created directory: $1"
    fi
}

# Convert CamelCase to snake_case
camel_to_snake() {
    echo "$1" | sed 's/\([A-Z]\)/_\1/g' | sed 's/^_//' | tr '[:upper:]' '[:lower:]'
}

# Convert snake_case to CamelCase
snake_to_camel() {
    local input="$1"
    # Split by underscore and capitalize each part
    local result=""
    IFS='_' read -ra PARTS <<< "$input"
    for part in "${PARTS[@]}"; do
        if [[ -n "$part" ]]; then
            result="${result}$(echo "${part:0:1}" | tr '[:lower:]' '[:upper:]')${part:1}"
        fi
    done
    echo "$result"
}

# Pluralize a word (simple pluralization)
pluralize() {
    local word="$1"
    if [[ "$word" =~ y$ ]]; then
        echo "${word%y}ies"
    elif [[ "$word" =~ [sxz]$ ]] || [[ "$word" =~ [cs]h$ ]]; then
        echo "${word}es"
    else
        echo "${word}s"
    fi
}

# Generate Controller
make_controller() {
    local name="$1"
    local namespace_path="$2"
    
    if [[ -z "$name" ]]; then
        log_error "Controller name is required"
        echo "Usage: ./adz.sh make:controller <ControllerName>"
        exit 1
    fi
    
    # Handle namespaced controllers (e.g., Admin/SettingsController)
    if [[ "$name" == *"/"* ]]; then
        namespace_path=$(dirname "$name")
        name=$(basename "$name")
    fi
    
    # Ensure Controller suffix
    if [[ ! "$name" =~ Controller$ ]]; then
        name="${name}Controller"
    fi
    
    # Create directory structure
    local controller_dir="$SRC_DIR/controllers"
    if [[ -n "$namespace_path" ]]; then
        controller_dir="$controller_dir/$namespace_path"
    fi
    
    ensure_dir "$controller_dir"
    
    local controller_file="$controller_dir/$name.php"
    
    if [[ -f "$controller_file" ]]; then
        log_warning "Controller already exists: $controller_file"
        read -p "Overwrite? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 0
        fi
    fi
    
    # Generate namespace
    local namespace="adz\\controllers"
    if [[ -n "$namespace_path" ]]; then
        namespace="$namespace\\$(echo "$namespace_path" | tr '/' '\\')"
    fi
    
    # Generate controller content
    cat > "$controller_file" << EOF
<?php

namespace $namespace;

use AdzWP\\WordPressController as Controller;

class $name extends Controller
{
    public \$actions = [
        'init' => 'initialize'
    ];

    public \$filters = [];

    protected function bootstrap()
    {
        // Additional initialization if needed
    }

    public function initialize()
    {
        // WordPress initialization logic
        if (\$this->isAdmin()) {
            // Admin-specific initialization
        }
        
        if (\$this->isFrontend()) {
            // Frontend-specific initialization
        }
    }
}
EOF
    
    log_success "Controller created: $controller_file"
}

# Generate Model
make_model() {
    local name="$1"
    local create_migration="$2"
    
    if [[ -z "$name" ]]; then
        log_error "Model name is required"
        echo "Usage: ./adz.sh make:model <ModelName> [--migration]"
        exit 1
    fi
    
    # Ensure Model suffix
    if [[ ! "$name" =~ Model$ ]]; then
        name="${name}Model"
    fi
    
    ensure_dir "$SRC_DIR/models"
    
    local model_file="$SRC_DIR/models/$name.php"
    
    if [[ -f "$model_file" ]]; then
        log_warning "Model already exists: $model_file"
        read -p "Overwrite? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 0
        fi
    fi
    
    # Generate table name from model name
    local base_name="${name%Model}"
    local table_name=$(pluralize $(camel_to_snake "$base_name"))
    
    # Generate model content
    cat > "$model_file" << EOF
<?php

namespace adz\\models;

use AdzWP\\Model;

class $name extends Model
{
    protected \$table = '$table_name';
    
    protected \$fillable = [
        // Add fillable attributes here
    ];
    
    protected \$guarded = [
        'id'
    ];
    
    // Add model relationships and methods here
}
EOF
    
    log_success "Model created: $model_file"
    
    # Create migration if requested
    if [[ "$create_migration" == "--migration" ]] || [[ "$3" == "--migration" ]]; then
        make_migration "create_${table_name}_table"
    fi
}

# Generate View
make_view() {
    local name="$1"
    
    if [[ -z "$name" ]]; then
        log_error "View name is required"
        echo "Usage: ./adz.sh make:view <path/viewname>"
        exit 1
    fi
    
    local view_dir="$TEMPLATES_DIR/$(dirname "$name")"
    local view_name="$(basename "$name")"
    
    ensure_dir "$view_dir"
    
    local view_file="$view_dir/$view_name.php"
    
    if [[ -f "$view_file" ]]; then
        log_warning "View already exists: $view_file"
        read -p "Overwrite? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 0
        fi
    fi
    
    # Generate view content
    cat > "$view_file" << 'EOF'
<?php
/**
 * View Template
 * 
 * Available variables:
 * - All variables passed from the controller
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="adz-view">
    <h1><?php echo esc_html($title ?? 'Default Title'); ?></h1>
    
    <div class="content">
        <?php if (isset($content)): ?>
            <?php echo wp_kses_post($content); ?>
        <?php else: ?>
            <p>Default content goes here.</p>
        <?php endif; ?>
    </div>
</div>
EOF
    
    log_success "View created: $view_file"
}

# Generate Migration
make_migration() {
    local name="$1"
    
    if [[ -z "$name" ]]; then
        log_error "Migration name is required"
        echo "Usage: ./adz.sh make:migration <migration_name>"
        exit 1
    fi
    
    ensure_dir "$MIGRATIONS_DIR"
    
    local timestamp=$(date +"%Y_%m_%d_%H%M%S")
    local migration_file="$MIGRATIONS_DIR/${timestamp}_${name}.php"
    local class_name=$(snake_to_camel "$name")
    
    # Generate migration content
    cat > "$migration_file" << EOF
<?php

use AdzWP\\Migration;

class $class_name extends Migration
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
EOF
    
    log_success "Migration created: $migration_file"
    log_info "Run './adz.sh migrate' to execute pending migrations"
}

# Run migrations
run_migrations() {
    log_info "Running migrations..."
    
    # Check if we can load the framework
    if [[ ! -f "$FRAMEWORK_DIR/vendor/autoload.php" ]]; then
        log_error "Composer autoloader not found. Run 'composer install' first."
        exit 1
    fi
    
    # Create a simple migration runner
    php -r "
    require_once '$FRAMEWORK_DIR/vendor/autoload.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Connection.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Migration.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Schema.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Migrator.php';
    require_once '$FRAMEWORK_DIR/adz/wp/helpers/functions.php';
    
    try {
        \$migrator = new AdzWP\\Migrator('$MIGRATIONS_DIR/');
        \$executed = \$migrator->migrate();
        
        if (empty(\$executed)) {
            echo \"No pending migrations found.\n\";
        } else {
            echo \"Executed migrations:\n\";
            foreach (\$executed as \$migration) {
                echo \"  - \$migration\n\";
            }
        }
    } catch (Exception \$e) {
        echo \"Migration failed: \" . \$e->getMessage() . \"\n\";
        exit(1);
    }
    "
    
    log_success "Migrations completed"
}

# Rollback migrations
rollback_migrations() {
    local steps="${1:-1}"
    log_info "Rolling back $steps migration(s)..."
    
    # Check if we can load the framework
    if [[ ! -f "$FRAMEWORK_DIR/vendor/autoload.php" ]]; then
        log_error "Composer autoloader not found. Run 'composer install' first."
        exit 1
    fi
    
    # Create a simple rollback runner
    php -r "
    require_once '$FRAMEWORK_DIR/vendor/autoload.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Connection.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Migration.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Schema.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Migrator.php';
    require_once '$FRAMEWORK_DIR/adz/wp/helpers/functions.php';
    
    try {
        \$migrator = new AdzWP\\Migrator('$MIGRATIONS_DIR/');
        \$rolledBack = \$migrator->rollback($steps);
        
        if (empty(\$rolledBack)) {
            echo \"No migrations to rollback.\\n\";
        } else {
            echo \"Rolled back migrations:\\n\";
            foreach (\$rolledBack as \$migration) {
                echo \"  - \$migration\\n\";
            }
        }
    } catch (Exception \$e) {
        echo \"Rollback failed: \" . \$e->getMessage() . \"\\n\";
        exit(1);
    }
    "
    
    log_success "Migrations rolled back"
}

# Reset all migrations
reset_migrations() {
    log_info "Resetting all migrations..."
    
    # Check if we can load the framework
    if [[ ! -f "$FRAMEWORK_DIR/vendor/autoload.php" ]]; then
        log_error "Composer autoloader not found. Run 'composer install' first."
        exit 1
    fi
    
    # Create a simple reset runner
    php -r "
    require_once '$FRAMEWORK_DIR/vendor/autoload.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Connection.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Migration.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Schema.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Migrator.php';
    require_once '$FRAMEWORK_DIR/adz/wp/helpers/functions.php';
    
    try {
        \$migrator = new AdzWP\\Migrator('$MIGRATIONS_DIR/');
        \$reset = \$migrator->reset();
        
        if (empty(\$reset)) {
            echo \"No migrations to reset.\\n\";
        } else {
            echo \"Reset migrations:\\n\";
            foreach (\$reset as \$migration) {
                echo \"  - \$migration\\n\";
            }
        }
    } catch (Exception \$e) {
        echo \"Reset failed: \" . \$e->getMessage() . \"\\n\";
        exit(1);
    }
    "
    
    log_success "All migrations reset"
}

# Migration status
migration_status() {
    log_info "Checking migration status..."
    
    # Check if we can load the framework
    if [[ ! -f "$FRAMEWORK_DIR/vendor/autoload.php" ]]; then
        log_error "Composer autoloader not found. Run 'composer install' first."
        exit 1
    fi
    
    # Create a simple status checker
    php -r "
    require_once '$FRAMEWORK_DIR/vendor/autoload.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Connection.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Migration.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Schema.php';
    require_once '$FRAMEWORK_DIR/adz/wp/database/Migrator.php';
    require_once '$FRAMEWORK_DIR/adz/wp/helpers/functions.php';
    
    try {
        \$migrator = new AdzWP\\Migrator('$MIGRATIONS_DIR/');
        \$status = \$migrator->status();
        
        if (empty(\$status)) {
            echo \"No migration files found.\\n\";
        } else {
            printf(\"%-50s %s\\n\", 'Migration', 'Status');
            printf(\"%s\\n\", str_repeat('-', 60));
            foreach (\$status as \$migration) {
                printf(\"%-50s %s\\n\", \$migration['migration'], \$migration['status']);
            }
        }
    } catch (Exception \$e) {
        echo \"Status check failed: \" . \$e->getMessage() . \"\\n\";
        exit(1);
    }
    "
}

# Initialize framework
init_framework() {
    log_info "Initializing ADZ Framework..."
    
    # Set permissions
    chmod +x "$FRAMEWORK_DIR/adz.sh"
    
    # Create necessary directories
    ensure_dir "$SRC_DIR/controllers"
    ensure_dir "$SRC_DIR/models"
    ensure_dir "$SRC_DIR/views"
    ensure_dir "$TEMPLATES_DIR"
    ensure_dir "$MIGRATIONS_DIR"
    ensure_dir "$FRAMEWORK_DIR/assets/css"
    ensure_dir "$FRAMEWORK_DIR/assets/js"
    ensure_dir "$FRAMEWORK_DIR/assets/images"
    
    # Install composer dependencies if composer.json exists
    if [[ -f "$FRAMEWORK_DIR/composer.json" ]] && command -v composer &> /dev/null; then
        log_info "Installing Composer dependencies..."
        cd "$FRAMEWORK_DIR" && composer install --no-dev
    fi
    
    log_success "Framework initialized successfully!"
    log_info "You can now start generating controllers, models, and views."
}

# Run tests
run_tests() {
    local test_type="$1"
    
    if [[ ! -f "$FRAMEWORK_DIR/vendor/bin/phpunit" ]]; then
        log_error "PHPUnit not found. Run 'composer install' first."
        exit 1
    fi
    
    cd "$FRAMEWORK_DIR"
    
    case "$test_type" in
        "unit")
            ./vendor/bin/phpunit --testsuite=Unit
            ;;
        "coverage")
            ./vendor/bin/phpunit --coverage-html coverage
            log_success "Coverage report generated in coverage/ directory"
            ;;
        *)
            ./vendor/bin/phpunit
            ;;
    esac
}

# Main command dispatcher
main() {
    local command="$1"
    
    case "$command" in
        "make:controller")
            make_controller "$2" "$3"
            ;;
        "make:model")
            make_model "$2" "$3"
            ;;
        "make:view")
            make_view "$2"
            ;;
        "make:migration")
            make_migration "$2"
            ;;
        "migrate")
            run_migrations
            ;;
        "migrate:rollback")
            rollback_migrations "$2"
            ;;
        "migrate:reset")
            reset_migrations
            ;;
        "migrate:status")
            migration_status
            ;;
        "init")
            init_framework
            ;;
        "test")
            run_tests
            ;;
        "test:unit")
            run_tests "unit"
            ;;
        "test:coverage")
            run_tests "coverage"
            ;;
        "help"|"--help"|"-h"|"")
            show_usage
            ;;
        *)
            log_error "Unknown command: $command"
            echo ""
            show_usage
            exit 1
            ;;
    esac
}

# Run the main function with all arguments
main "$@"