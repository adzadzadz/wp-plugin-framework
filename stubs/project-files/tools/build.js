#!/usr/bin/env node

const { spawn } = require('child_process');
const path = require('path');
const chalk = require('chalk');

class MainBuilder {
    constructor(options = {}) {
        this.options = options;
        this.verbose = options.verbose || false;
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

    async runScript(scriptPath, args = []) {
        return new Promise((resolve, reject) => {
            const child = spawn('node', [scriptPath, ...args], {
                stdio: this.verbose ? 'inherit' : 'pipe',
                cwd: process.cwd()
            });

            if (!this.verbose) {
                child.stdout.on('data', (data) => {
                    process.stdout.write(data);
                });
                
                child.stderr.on('data', (data) => {
                    process.stderr.write(data);
                });
            }

            child.on('close', (code) => {
                if (code === 0) {
                    resolve();
                } else {
                    reject(new Error(`Script ${scriptPath} exited with code ${code}`));
                }
            });

            child.on('error', (error) => {
                reject(error);
            });
        });
    }

    async build() {
        try {
            this.log('Starting complete plugin build process...', 'info');
            
            // Step 1: Build and minify assets
            this.log('Step 1: Building and minifying assets...', 'info');
            await this.runScript(path.join(__dirname, 'build-assets.js'), 
                this.verbose ? ['--verbose'] : []);
            
            // Step 2: Create installable zip
            this.log('Step 2: Creating installable plugin zip...', 'info');
            const zipArgs = [];
            if (this.options.name) zipArgs.push('--name', this.options.name);
            if (this.options.version) zipArgs.push('--version', this.options.version);
            
            await this.runScript(path.join(__dirname, 'build-zip.js'), zipArgs);
            
            this.log('Build process completed successfully!', 'success');
            this.log('Your installable plugin zip is ready in the dist/ directory', 'info');
            
        } catch (error) {
            this.log(`Build process failed: ${error.message}`, 'error');
            process.exit(1);
        }
    }
}

// CLI handling
const args = process.argv.slice(2);
const options = {
    verbose: args.includes('--verbose') || args.includes('-v')
};

// Parse named arguments
for (let i = 0; i < args.length; i++) {
    const arg = args[i];
    if (arg === '--name' && i + 1 < args.length) {
        options.name = args[i + 1];
        i++;
    } else if (arg === '--version' && i + 1 < args.length) {
        options.version = args[i + 1];
        i++;
    }
}

// Show help if requested
if (args.includes('--help') || args.includes('-h')) {
    console.log(`
${chalk.bold('ADZ Plugin Framework Build Tool')}

Usage: node tools/build.js [options]

Options:
  --name <name>      Plugin name for the zip file
  --version <ver>    Plugin version number  
  --verbose, -v      Show verbose output
  --help, -h         Show this help message

Examples:
  node tools/build.js
  node tools/build.js --name my-plugin --version 1.2.0
  node tools/build.js --verbose
`);
    process.exit(0);
}

const builder = new MainBuilder(options);
builder.build().catch(console.error);