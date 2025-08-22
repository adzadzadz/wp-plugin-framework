# Build System & Asset Management

The ADZ WordPress Plugin Framework includes a comprehensive build system with asset minification and plugin distribution capabilities.

## Prerequisites

- **Node.js** 14.0.0 or higher
- **npm** (comes with Node.js)

The build system will automatically install required dependencies when first run.

## Quick Start

### Complete Build Process
```bash
# Build everything and create installable plugin zip
./adz.sh build

# Build with custom name and version
./adz.sh build --name my-plugin --version 1.2.0
```

### Individual Build Commands
```bash
# Build and minify assets only
./adz.sh build:assets

# Watch assets for changes (development)
./adz.sh build:watch

# Create installable zip only
./adz.sh build:zip
```

## Build Process Overview

The build system performs these steps:

1. **Asset Processing**: Minifies CSS and JavaScript files
2. **File Copying**: Copies relevant project files, excluding development files
3. **Version Updates**: Updates version numbers in plugin files
4. **Zip Creation**: Creates an installable WordPress plugin zip file

## Asset Management

### Directory Structure
```
src/
└── assets/
    ├── css/
    │   ├── admin.css
    │   └── frontend.css
    └── js/
        ├── admin.js
        └── frontend.js
```

### Asset Processing Features

- **CSS Minification**: Using CleanCSS with level 2 optimization
- **JS Minification**: Using Terser with compression and mangling
- **Source Preservation**: Already minified files (`.min.css`, `.min.js`) are copied as-is
- **File Watching**: Development mode with automatic rebuilding on file changes
- **Size Reporting**: Shows compression ratios for optimized files

### Supported File Types

**Minified:**
- `.css` → Minified with CleanCSS
- `.js` → Minified with Terser

**Copied as-is:**
- `.min.css`, `.min.js` (already minified)
- Images: `.png`, `.jpg`, `.jpeg`, `.gif`, `.svg`, `.webp`
- Fonts: `.woff`, `.woff2`, `.ttf`, `.eot`
- Other assets: copied without modification

## Build Commands Reference

### `./adz.sh build`
Complete build process that creates a production-ready installable plugin.

**Options:**
- `--name <name>`: Custom plugin name for zip file
- `--version <version>`: Custom version number
- `--verbose`: Show detailed output

**Example:**
```bash
./adz.sh build --name my-awesome-plugin --version 2.1.0 --verbose
```

### `./adz.sh build:assets`
Build and minify assets only, useful during development.

**Options:**
- `--verbose`: Show detailed compression stats

**Example:**
```bash
./adz.sh build:assets --verbose
```

### `./adz.sh build:watch`
Watch assets for changes and automatically rebuild when files are modified.

**Usage:**
```bash
./adz.sh build:watch
```

Press `Ctrl+C` to stop watching.

### `./adz.sh build:zip`
Create installable plugin zip without rebuilding assets.

**Options:**
- `--name <name>`: Custom plugin name
- `--version <version>`: Custom version number

**Example:**
```bash
./adz.sh build:zip --name production-plugin --version 1.0.0
```

## Configuration

### Package.json Scripts
The build system uses Node.js scripts defined in `package.json`:

```json
{
  "scripts": {
    "build": "node tools/build.js",
    "build:assets": "node tools/build-assets.js",
    "build:zip": "node tools/build-zip.js",
    "watch": "node tools/build-assets.js --watch",
    "clean": "rm -rf build dist *.zip"
  }
}
```

### Build Tools
- **Terser**: JavaScript minification and compression
- **CleanCSS**: CSS minification and optimization  
- **Archiver**: Zip file creation with maximum compression
- **Chokidar**: File watching for development mode
- **Chalk**: Colored console output

## Output Structure

### Build Directory (`build/`)
Temporary directory containing processed files:
```
build/
├── assets/          # Minified CSS/JS
├── src/            # PHP source files
├── adz/            # Framework files
└── vendor/         # Composer dependencies (production only)
```

### Distribution Directory (`dist/`)
Contains the final installable plugin zip:
```
dist/
└── my-plugin-1.0.0.zip
```

## Excluded Files

The build process automatically excludes development files:

**Directories:**
- `node_modules/`
- `tests/`
- `build/`
- `dist/`
- `tools/`
- `docs/`
- `.git/`
- `.vscode/`
- `.idea/`
- `vendor/*/tests/`

**Files:**
- `*.md` (README, docs)
- `*.log`
- `.env*`
- `.gitignore`
- `package.json`
- `package-lock.json`
- `composer.lock`
- `phpunit.xml`
- Build configuration files

## Development Workflow

### 1. Development Mode
```bash
# Start asset watcher
./adz.sh build:watch

# Edit your CSS/JS files
# Assets are automatically rebuilt on save
```

### 2. Testing Build
```bash
# Build assets to test minification
./adz.sh build:assets --verbose

# Check build output
ls -la build/assets/
```

### 3. Production Build
```bash
# Create production-ready plugin
./adz.sh build --name my-plugin --version 1.0.0

# Install the zip file
ls -la dist/
```

## Troubleshooting

### Node.js Not Found
```bash
# Install Node.js first
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### Build Dependencies Missing
```bash
# Install manually if auto-install fails
npm install
```

### Permission Errors
```bash
# Make sure build tools are executable
chmod +x tools/*.js
```

### Clean Build
```bash
# Remove all build artifacts
npm run clean

# Or manually
rm -rf build dist *.zip node_modules
```

## Advanced Usage

### Custom Minification Options

Edit `tools/build-assets.js` to customize minification:

```javascript
// JavaScript minification options
const result = await minify(code, {
    compress: {
        drop_console: false,      // Keep console.log in development
        drop_debugger: true,      // Remove debugger statements
        pure_funcs: ['console.log'] // Remove specific functions
    },
    mangle: true,                 // Shorten variable names
    format: {
        comments: /^!/            // Keep license comments
    }
});

// CSS minification options
const cleanCSS = new CleanCSS({
    level: 2,                     // Optimization level (0-2)
    returnPromise: true,
    sourceMap: false              // Disable source maps
});
```

### Integration with Other Tools

The build system can be integrated with:

- **GitHub Actions**: Automated builds on push
- **WordPress Plugin Directory**: SVN deployment scripts  
- **CI/CD Pipelines**: Docker containers with Node.js
- **Local Development**: VS Code tasks and scripts

### Build Hooks

Add custom build steps by modifying the build scripts:

```javascript
// In tools/build.js - add custom steps
async build() {
    await this.runScript('build-assets.js');
    await this.customProcessing();      // Your custom step
    await this.runScript('build-zip.js');
}
```

The build system provides a robust foundation for WordPress plugin development with modern asset management and distribution capabilities.