# Changelog

All notable changes to the ADZ WordPress Plugin Framework will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Service Layer**: Complete service architecture with dependency injection and auto-registration
- Auto-hook registration: methods named `actionWpInit()` and `filterTheTitle()` automatically become WordPress hooks
- Priority parameter support: `actionWpInit($priority = 20)` for cleaner hook priority management
- Service examples and comprehensive documentation in stubs for new users

---

## [v2.3.1] - 2025-08-24

### Removed
- Unnecessary setup.php file from root directory

## [v2.3.0] - 2025-08-22

### Added
- Auto-command setup: `adz` command created in project root after installation

## [v2.2.0] - 2025-08-22

### Added  
- Automatic composer update after project creation

## [v2.1.0] - 2025-08-22

### Added
- Auto-scaffolding plugin setup system with pre-configured structure

## [v2.0.0] - 2025-08-22

### Added
- Initial ADZ WordPress Plugin Framework release with MVC architecture
- Core Controller, Model, View classes with PSR-4 autoloading
- Database layer with Query Builder and ORM-style models
- Comprehensive testing framework with 61+ unit tests
- CLI tools and professional plugin scaffolding system

---

## Release Links

- [Unreleased]: https://github.com/adzadzadz/wp-plugin-framework/compare/v2.3.1...develop
- [v2.3.1]: https://github.com/adzadzadz/wp-plugin-framework/releases/tag/v2.3.1
- [v2.3.0]: https://github.com/adzadzadz/wp-plugin-framework/releases/tag/v2.3.0
- [v2.2.0]: https://github.com/adzadzadz/wp-plugin-framework/releases/tag/v2.2.0
- [v2.1.0]: https://github.com/adzadzadz/wp-plugin-framework/releases/tag/v2.1.0
- [v2.0.0]: https://github.com/adzadzadz/wp-plugin-framework/releases/tag/v2.0.0