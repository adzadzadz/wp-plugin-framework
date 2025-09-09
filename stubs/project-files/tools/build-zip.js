#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const archiver = require('archiver');
const chalk = require('chalk');

class PluginZipBuilder {
    constructor(options = {}) {
        this.projectDir = process.cwd();
        this.buildDir = path.join(this.projectDir, 'build');
        this.distDir = path.join(this.projectDir, 'dist');
        this.pluginName = options.name || this.getPluginName();
        this.version = options.version || this.getVersion();
        
        // Ensure directories exist
        if (!fs.existsSync(this.distDir)) {
            fs.mkdirSync(this.distDir, { recursive: true });
        }
    }

    log(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const colors = {
            info: chalk.blue,
            success: chalk.green,
            error: chalk.red,
            warning: chalk.yellow
        };
        
        console.log(`${chalk.gray(timestamp)} ${colors[type]('●')} ${message}`);
    }

    getPluginName() {
        try {
            const packageJson = JSON.parse(fs.readFileSync(path.join(this.projectDir, 'package.json'), 'utf8'));
            return packageJson.name || 'plugin';
        } catch (error) {
            // Try to get from main plugin file
            const pluginFiles = fs.readdirSync(this.projectDir).filter(file => file.endsWith('.php'));
            for (const file of pluginFiles) {
                const content = fs.readFileSync(path.join(this.projectDir, file), 'utf8');
                const match = content.match(/Plugin Name:\s*(.+)/i);
                if (match) {
                    return match[1].trim().toLowerCase().replace(/\s+/g, '-');
                }
            }
            return 'plugin';
        }
    }

    getVersion() {
        try {
            const packageJson = JSON.parse(fs.readFileSync(path.join(this.projectDir, 'package.json'), 'utf8'));
            return packageJson.version || '1.0.0';
        } catch (error) {
            return '1.0.0';
        }
    }

    shouldIncludeFile(filePath, relativePath) {
        // Exclude development files and directories
        const excludePatterns = [
            /^\.git/,
            /^\.vscode/,
            /^\.idea/,
            /^node_modules/,
            /^vendor\/.*\/tests/,
            /^vendor\/.*\/test/,
            /^vendor\/.*\/\.git/,
            /^tests/,
            /^build/,
            /^dist/,
            /^tools/,
            /^docs/,
            /\.md$/,
            /\.log$/,
            /\.tmp$/,
            /\.DS_Store$/,
            /Thumbs\.db$/,
            /^package\.json$/,
            /^package-lock\.json$/,
            /^yarn\.lock$/,
            /^composer\.lock$/,
            /^\.env/,
            /^\.gitignore$/,
            /^\.gitattributes$/,
            /^phpunit\.xml$/,
            /^webpack\.config\.js$/,
            /^gulpfile\.js$/,
            /^adz$/,
            /^adz\.bat$/
        ];

        return !excludePatterns.some(pattern => pattern.test(relativePath));
    }

    async copyProjectFiles() {
        this.log('Copying project files to build directory...', 'info');
        
        const copyRecursive = (sourceDir, targetDir, basePath = '') => {
            const items = fs.readdirSync(sourceDir);
            
            for (const item of items) {
                const sourcePath = path.join(sourceDir, item);
                const targetPath = path.join(targetDir, item);
                const relativePath = path.join(basePath, item).replace(/\\/g, '/');
                const stat = fs.statSync(sourcePath);
                
                if (!this.shouldIncludeFile(sourcePath, relativePath)) {
                    continue;
                }
                
                if (stat.isDirectory()) {
                    if (!fs.existsSync(targetPath)) {
                        fs.mkdirSync(targetPath, { recursive: true });
                    }
                    copyRecursive(sourcePath, targetPath, relativePath);
                } else {
                    fs.copyFileSync(sourcePath, targetPath);
                }
            }
        };

        // Clear build directory first
        if (fs.existsSync(this.buildDir)) {
            fs.rmSync(this.buildDir, { recursive: true, force: true });
        }
        fs.mkdirSync(this.buildDir, { recursive: true });

        // Copy all project files
        copyRecursive(this.projectDir, this.buildDir);
        
        this.log('Project files copied successfully', 'success');
    }

    async replaceAssets() {
        this.log('Replacing assets with minified versions...', 'info');
        
        const buildAssetsDir = path.join(this.buildDir, 'assets');
        const minifiedAssetsDir = path.join(this.buildDir, 'build', 'assets');
        
        // If we have minified assets, replace the original assets
        if (fs.existsSync(minifiedAssetsDir)) {
            if (fs.existsSync(buildAssetsDir)) {
                fs.rmSync(buildAssetsDir, { recursive: true, force: true });
            }
            
            // Move minified assets to assets directory
            fs.renameSync(minifiedAssetsDir, buildAssetsDir);
            
            // Remove the now-empty build directory
            const innerBuildDir = path.join(this.buildDir, 'build');
            if (fs.existsSync(innerBuildDir)) {
                fs.rmSync(innerBuildDir, { recursive: true, force: true });
            }
            
            this.log('Assets replaced with minified versions', 'success');
        } else {
            this.log('No minified assets found, using original assets', 'warning');
        }
    }

    async updateVersions() {
        this.log('Updating version numbers in files...', 'info');
        
        // Update main plugin file
        const pluginFiles = fs.readdirSync(this.buildDir).filter(file => file.endsWith('.php'));
        
        for (const file of pluginFiles) {
            const filePath = path.join(this.buildDir, file);
            let content = fs.readFileSync(filePath, 'utf8');
            
            // Check if this is a main plugin file
            if (content.includes('Plugin Name:')) {
                content = content.replace(/Version:\s*[\d.]+/i, `Version: ${this.version}`);
                fs.writeFileSync(filePath, content);
                this.log(`Updated version in ${file}`, 'success');
                break;
            }
        }
        
        // Update any other version references if needed
        const constantsFile = path.join(this.buildDir, 'adz', 'wp', 'core', 'Config.php');
        if (fs.existsSync(constantsFile)) {
            let content = fs.readFileSync(constantsFile, 'utf8');
            content = content.replace(/'version'\s*=>\s*'[\d.]+'/, `'version' => '${this.version}'`);
            fs.writeFileSync(constantsFile, content);
        }
    }

    async createZip() {
        const zipFileName = `${this.pluginName}-${this.version}.zip`;
        const zipPath = path.join(this.distDir, zipFileName);
        
        this.log(`Creating plugin zip: ${zipFileName}...`, 'info');
        
        return new Promise((resolve, reject) => {
            const output = fs.createWriteStream(zipPath);
            const archive = archiver('zip', {
                zlib: { level: 9 } // Maximum compression
            });

            output.on('close', () => {
                const sizeInMB = (archive.pointer() / 1024 / 1024).toFixed(2);
                this.log(`Plugin zip created successfully: ${zipFileName} (${sizeInMB}MB)`, 'success');
                resolve(zipPath);
            });

            archive.on('error', (err) => {
                this.log(`Error creating zip: ${err.message}`, 'error');
                reject(err);
            });

            archive.pipe(output);

            // Add all files from build directory to zip
            archive.directory(this.buildDir, this.pluginName);
            
            archive.finalize();
        });
    }

    async build() {
        try {
            this.log(`Building plugin: ${this.pluginName} v${this.version}`, 'info');
            
            await this.copyProjectFiles();
            await this.replaceAssets();
            await this.updateVersions();
            const zipPath = await this.createZip();
            
            this.log('Plugin build completed successfully!', 'success');
            this.log(`Output: ${path.relative(this.projectDir, zipPath)}`, 'info');
            
            return zipPath;
        } catch (error) {
            this.log(`Build failed: ${error.message}`, 'error');
            throw error;
        }
    }
}

// CLI handling
const args = process.argv.slice(2);
const options = {};

// Parse arguments
for (let i = 0; i < args.length; i++) {
    const arg = args[i];
    if (arg === '--name' && i + 1 < args.length) {
        options.name = args[i + 1];
        i++;
    } else if (arg === '--version' && i + 1 < args.length) {
        options.version = args[i + 1];
        i++;
    } else if (arg.match(/^\d+\.\d+(\.\d+)?/)) {
        // If argument looks like a version number (e.g., "3.0.2"), use it as version
        options.version = arg;
    }
}

const builder = new PluginZipBuilder(options);
builder.build().catch(console.error);