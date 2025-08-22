#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { minify } = require('terser');
const CleanCSS = require('clean-css');
const chokidar = require('chokidar');
const chalk = require('chalk');

class AssetBuilder {
    constructor(options = {}) {
        this.watch = options.watch || false;
        this.verbose = options.verbose || false;
        this.buildDir = path.join(process.cwd(), 'build');
        this.sourceDir = path.join(process.cwd(), 'src');
        
        // Ensure build directory exists
        if (!fs.existsSync(this.buildDir)) {
            fs.mkdirSync(this.buildDir, { recursive: true });
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

    async minifyJS(inputPath, outputPath) {
        try {
            const code = fs.readFileSync(inputPath, 'utf8');
            const result = await minify(code, {
                compress: {
                    drop_console: false,
                    drop_debugger: true,
                    pure_funcs: ['console.log']
                },
                mangle: true,
                format: {
                    comments: /^!/
                }
            });

            if (result.error) {
                throw result.error;
            }

            fs.writeFileSync(outputPath, result.code);
            this.log(`Minified JS: ${path.basename(inputPath)} → ${path.basename(outputPath)}`, 'success');
            
            const originalSize = fs.statSync(inputPath).size;
            const minifiedSize = fs.statSync(outputPath).size;
            const reduction = ((originalSize - minifiedSize) / originalSize * 100).toFixed(1);
            
            if (this.verbose) {
                this.log(`  Size reduction: ${originalSize}B → ${minifiedSize}B (${reduction}%)`, 'info');
            }
        } catch (error) {
            this.log(`Error minifying ${inputPath}: ${error.message}`, 'error');
            throw error;
        }
    }

    async minifyCSS(inputPath, outputPath) {
        try {
            const css = fs.readFileSync(inputPath, 'utf8');
            const cleanCSS = new CleanCSS({
                level: 2,
                returnPromise: true,
                sourceMap: false
            });

            const result = await cleanCSS.minify(css);
            
            if (result.errors.length > 0) {
                throw new Error(result.errors.join(', '));
            }

            fs.writeFileSync(outputPath, result.styles);
            this.log(`Minified CSS: ${path.basename(inputPath)} → ${path.basename(outputPath)}`, 'success');
            
            const originalSize = Buffer.byteLength(css, 'utf8');
            const minifiedSize = Buffer.byteLength(result.styles, 'utf8');
            const reduction = ((originalSize - minifiedSize) / originalSize * 100).toFixed(1);
            
            if (this.verbose) {
                this.log(`  Size reduction: ${originalSize}B → ${minifiedSize}B (${reduction}%)`, 'info');
            }
        } catch (error) {
            this.log(`Error minifying ${inputPath}: ${error.message}`, 'error');
            throw error;
        }
    }

    copyFile(source, destination) {
        const destDir = path.dirname(destination);
        if (!fs.existsSync(destDir)) {
            fs.mkdirSync(destDir, { recursive: true });
        }
        fs.copyFileSync(source, destination);
    }

    async processAssets() {
        this.log('Starting asset processing...', 'info');
        
        const assetsDir = path.join(this.sourceDir, 'assets');
        const buildAssetsDir = path.join(this.buildDir, 'assets');
        
        if (!fs.existsSync(assetsDir)) {
            this.log('No assets directory found, skipping asset processing', 'warning');
            return;
        }

        // Ensure build assets directory exists
        if (!fs.existsSync(buildAssetsDir)) {
            fs.mkdirSync(buildAssetsDir, { recursive: true });
        }

        await this.processDirectory(assetsDir, buildAssetsDir);
        this.log('Asset processing completed!', 'success');
    }

    async processDirectory(sourceDir, buildDir) {
        const items = fs.readdirSync(sourceDir);
        
        for (const item of items) {
            const sourcePath = path.join(sourceDir, item);
            const buildPath = path.join(buildDir, item);
            const stat = fs.statSync(sourcePath);
            
            if (stat.isDirectory()) {
                if (!fs.existsSync(buildPath)) {
                    fs.mkdirSync(buildPath, { recursive: true });
                }
                await this.processDirectory(sourcePath, buildPath);
            } else {
                await this.processFile(sourcePath, buildPath);
            }
        }
    }

    async processFile(sourcePath, buildPath) {
        const ext = path.extname(sourcePath).toLowerCase();
        
        try {
            switch (ext) {
                case '.js':
                    if (sourcePath.includes('.min.')) {
                        // Already minified, just copy
                        this.copyFile(sourcePath, buildPath);
                        this.log(`Copied (already minified): ${path.basename(sourcePath)}`, 'info');
                    } else {
                        await this.minifyJS(sourcePath, buildPath);
                    }
                    break;
                    
                case '.css':
                    if (sourcePath.includes('.min.')) {
                        // Already minified, just copy
                        this.copyFile(sourcePath, buildPath);
                        this.log(`Copied (already minified): ${path.basename(sourcePath)}`, 'info');
                    } else {
                        await this.minifyCSS(sourcePath, buildPath);
                    }
                    break;
                    
                default:
                    // Copy other files as-is (images, fonts, etc.)
                    this.copyFile(sourcePath, buildPath);
                    if (this.verbose) {
                        this.log(`Copied: ${path.basename(sourcePath)}`, 'info');
                    }
                    break;
            }
        } catch (error) {
            this.log(`Failed to process ${sourcePath}: ${error.message}`, 'error');
        }
    }

    startWatcher() {
        this.log('Starting file watcher...', 'info');
        
        const assetsDir = path.join(this.sourceDir, 'assets');
        
        if (!fs.existsSync(assetsDir)) {
            this.log('Assets directory not found for watching', 'error');
            return;
        }
        
        const watcher = chokidar.watch(assetsDir, {
            ignored: /(^|[\/\\])\../,
            persistent: true
        });

        watcher
            .on('change', async (filePath) => {
                this.log(`File changed: ${path.relative(process.cwd(), filePath)}`, 'info');
                const relativePath = path.relative(assetsDir, filePath);
                const buildPath = path.join(this.buildDir, 'assets', relativePath);
                await this.processFile(filePath, buildPath);
            })
            .on('add', async (filePath) => {
                this.log(`File added: ${path.relative(process.cwd(), filePath)}`, 'info');
                const relativePath = path.relative(assetsDir, filePath);
                const buildPath = path.join(this.buildDir, 'assets', relativePath);
                await this.processFile(filePath, buildPath);
            })
            .on('unlink', (filePath) => {
                this.log(`File removed: ${path.relative(process.cwd(), filePath)}`, 'warning');
                const relativePath = path.relative(assetsDir, filePath);
                const buildPath = path.join(this.buildDir, 'assets', relativePath);
                if (fs.existsSync(buildPath)) {
                    fs.unlinkSync(buildPath);
                }
            });

        this.log('File watcher started. Press Ctrl+C to stop.', 'success');
    }

    async run() {
        try {
            if (this.watch) {
                await this.processAssets();
                this.startWatcher();
            } else {
                await this.processAssets();
            }
        } catch (error) {
            this.log(`Build failed: ${error.message}`, 'error');
            process.exit(1);
        }
    }
}

// CLI handling
const args = process.argv.slice(2);
const options = {
    watch: args.includes('--watch') || args.includes('-w'),
    verbose: args.includes('--verbose') || args.includes('-v')
};

const builder = new AssetBuilder(options);
builder.run().catch(console.error);