<?php

namespace AdzWP\Scripts;

use Composer\Script\Event;

/**
 * Composer Scripts for ADZ WordPress Plugin Framework
 * 
 * Contains static methods that are called during composer install/update
 */
class ComposerScripts
{
    /**
     * Copy the ADZ CLI command to the project root
     * 
     * This runs after composer install/update to ensure the adz command
     * is available in the project root directory.
     * 
     * @param Event $event
     */
    public static function copyAdzCommand(?Event $event = null)
    {
        $vendorDir = 'vendor/adzadzadz/wp-plugin-framework';
        $sourcePath = $vendorDir . '/bin/adz';
        
        // Check if we're in a project that has the framework installed
        if (!file_exists($sourcePath)) {
            if ($event) {
                $event->getIO()->write('<comment>ADZ Framework not found, skipping CLI copy</comment>');
            }
            return;
        }
        
        // Determine target file name based on OS
        $targetFile = (PHP_OS_FAMILY === 'Windows') ? 'adz.bat' : 'adz';
        
        // Remove existing file or symlink
        if (file_exists($targetFile)) {
            if (is_link($targetFile)) {
                unlink($targetFile);
            } elseif (is_file($targetFile)) {
                unlink($targetFile);
            }
        }
        
        // Copy the CLI script
        if (copy($sourcePath, $targetFile)) {
            // Make it executable on Unix systems
            if (PHP_OS_FAMILY !== 'Windows') {
                chmod($targetFile, 0755);
            }
            
            if ($event) {
                $event->getIO()->write('<info>ADZ command copied to project root</info>');
            } else {
                echo "ADZ command copied to project root\n";
            }
        } else {
            if ($event) {
                $event->getIO()->write('<error>Failed to copy ADZ command</error>');
            } else {
                echo "Failed to copy ADZ command\n";
            }
        }
    }
}