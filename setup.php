<?php
/**
 * Manual setup script for ADZ Framework
 * 
 * Run this if the automatic setup didn't work:
 * php setup.php
 */

echo "🚀 Running ADZ Framework setup...\n";

// Check if setup script exists
$setupScript = __DIR__ . '/bin/setup-plugin';
if (!file_exists($setupScript)) {
    echo "❌ Setup script not found!\n";
    exit(1);
}

// Execute the setup script
echo "📦 Executing plugin setup...\n";
passthru("php " . escapeshellarg($setupScript));
echo "✅ Setup complete!\n";