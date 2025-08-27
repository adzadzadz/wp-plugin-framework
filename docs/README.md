# ADZ WordPress Plugin Framework Documentation

Welcome to the ADZ WordPress Plugin Framework documentation. This framework provides a modern, structured approach to WordPress plugin development with MVC architecture, dependency management, and comprehensive plugin lifecycle support.

## 📚 Documentation Index

### Getting Started
- [Quick Start Guide](getting-started.md) - Get up and running in minutes
- [Installation](installation.md) - Installation and setup instructions
- [Framework Architecture](architecture.md) - Understanding the framework structure

### Core Features
- [Plugin Lifecycle Management](PLUGIN_LIFECYCLE.md) - Install, activate, deactivate, uninstall hooks
- [Controllers](controllers.md) - MVC controller system with automatic hook registration
- [Models & Database](models-database.md) - Database abstraction and ORM-like functionality
- [Views & Layouts](views-layouts.md) - Template system with Bootstrap 5 integration
- [Asset Management](asset-management.md) - CSS/JS management with context-aware loading
- [Configuration](configuration.md) - Configuration management system

### Advanced Features
- [Dependency Management](dependency-management.md) - Automatic plugin dependency installation
- [CLI Commands](cli-commands.md) - Complete command-line interface
- [Security](security.md) - Security features and best practices
- [Testing](testing.md) - Unit testing and integration testing

### API Reference
- [Core Classes](api/core.md) - Core framework classes
- [Helper Functions](api/helpers.md) - Utility and helper functions
- [Traits](api/traits.md) - Reusable traits and behaviors

### Examples & Tutorials
- [Building Your First Plugin](examples/first-plugin.md) - Step-by-step tutorial
- [Advanced Plugin Examples](examples/advanced.md) - Complex plugin scenarios
- [Migration Guide](migration.md) - Migrating from other frameworks

## 🚀 Quick Example

```php
<?php
// main-plugin-file.php

// Initialize framework
$pluginManager = \AdzWP\Core\PluginManager::getInstance(__FILE__);

// Set up lifecycle hooks
$pluginManager
    ->onActivate(function() {
        // Create tables, set options
    })
    ->onDeactivate(function() {
        // Cleanup tasks
    })
    ->setupOptions(['my_setting' => 'default'])
    ->setupCapabilities(['manage_my_plugin']);

// Initialize controllers
new App\Controllers\ExampleController();
```

## 🔧 Framework Features

- **MVC Architecture** - Clean separation of concerns
- **Automatic Hook Registration** - Declarative hook management
- **Plugin Lifecycle Management** - Complete install/uninstall workflow
- **View System with Layouts** - Structured templates with Bootstrap 5 integration
- **Asset Management** - Context-aware CSS/JS loading with CDN support
- **Dependency Management** - Automatic plugin installation
- **Database Abstraction** - Eloquent-style query builder
- **Security Features** - Built-in sanitization and validation
- **Testing Framework** - PHPUnit integration with WordPress mocking
- **CLI Tools** - Code generation and scaffolding

## 📋 Requirements

- WordPress 5.0+
- PHP 7.4+
- Composer

## 🤝 Contributing

See [CONTRIBUTING.md](../CONTRIBUTING.md) for contribution guidelines.

## 📄 License

This framework is licensed under the [GPL v2 or later](../LICENSE).