<?php 

namespace AdzWP\Core;

use AdzWP\NotFoundException;
use AdzWP\ForbiddenException;

/**
 * Enhanced View class with template caching and security
 */
class View extends Core {

    protected static $templateCache = [];
    protected static $templatePaths = [];
    
    /**
     * Render a template with data
     * 
     * @param string $template Template name
     * @param array $data Template variables
     * @param bool $cache Enable template caching
     * @param string|bool $layout Layout template to wrap content in, or false to disable
     * @return string Rendered content
     * @throws NotFoundException
     */
    public static function render($template, $data = [], $cache = true, $layout = 'layouts/main')
    {
        $templateFile = static::findTemplate($template);
        
        if (!$templateFile) {
            throw new NotFoundException("Template '{$template}' not found");
        }
        
        $content = static::renderTemplate($templateFile, $data, $cache);
        
        // If layout is specified and template is not already a layout template
        if ($layout && $layout !== false && !str_starts_with($template, 'layouts/')) {
            $layoutFile = static::findTemplate($layout);
            
            if ($layoutFile) {
                // Render content within the layout
                $layoutData = array_merge($data, ['content' => $content]);
                return static::renderTemplate($layoutFile, $layoutData, $cache);
            }
        }
        
        return $content;
    }
    
    /**
     * Find template file in registered paths
     */
    protected static function findTemplate($template)
    {
        $templateFile = $template . '.php';
        
        // Check cache first
        if (isset(static::$templateCache[$template])) {
            return static::$templateCache[$template];
        }
        
        $paths = static::getTemplatePaths();
        
        foreach ($paths as $path) {
            $fullPath = rtrim($path, '/') . '/' . $templateFile;
            
            if (file_exists($fullPath) && is_readable($fullPath)) {
                static::$templateCache[$template] = $fullPath;
                return $fullPath;
            }
        }
        
        return null;
    }
    
    /**
     * Get template search paths
     */
    protected static function getTemplatePaths()
    {
        if (empty(static::$templatePaths)) {
            static::$templatePaths = [
                // Plugin views directory
                (defined('ADZ_PLUGIN_PATH') ? ADZ_PLUGIN_PATH : ADZ::$path) . 'views/',
                // Theme template overrides
                get_template_directory() . '/adz-templates/',
                get_stylesheet_directory() . '/adz-templates/',
            ];
            
            // Allow filtering of template paths
            if (function_exists('apply_filters')) {
                static::$templatePaths = apply_filters('adz_template_paths', static::$templatePaths);
            }
        }
        
        return static::$templatePaths;
    }
    
    /**
     * Render template file with security checks
     */
    protected static function renderTemplate($templateFile, $data = [], $cache = true)
    {
        // Security check - ensure template is in allowed paths
        $realPath = realpath($templateFile);
        $allowed = false;
        
        foreach (static::getTemplatePaths() as $allowedPath) {
            if (is_dir($allowedPath) && strpos($realPath, realpath($allowedPath)) === 0) {
                $allowed = true;
                break;
            }
        }
        
        if (!$allowed) {
            throw new ForbiddenException('Template path not allowed');
        }
        
        ob_start();
        
        if (!empty($data) && is_array($data)) {
            // Sanitize variables for security
            $data = static::sanitizeTemplateData($data);
            extract($data, EXTR_SKIP);
        }
        
        try {
            include $templateFile;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new Exception('Template rendering failed: ' . $e->getMessage());
        }
        
        $content = ob_get_contents();
        ob_end_clean();
        
        return $content;
    }
    
    /**
     * Sanitize template data for security
     */
    protected static function sanitizeTemplateData($data)
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            // Only allow alphanumeric keys
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                continue;
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }
    
    /**
     * Add template path
     */
    public static function addTemplatePath($path, $priority = 10)
    {
        if (is_dir($path)) {
            static::$templatePaths[] = rtrim($path, '/') . '/';
        }
    }
    
    /**
     * Clear template cache
     */
    public static function clearCache()
    {
        static::$templateCache = [];
    }
    
    /**
     * Check if template exists
     */
    public static function exists($template)
    {
        return static::findTemplate($template) !== null;
    }
    
    /**
     * Render partial template
     */
    public static function partial($template, $data = [])
    {
        return static::render('partials/' . $template, $data);
    }
    
    /**
     * Include template directly (for use within templates)
     */
    public static function include($template, $data = [])
    {
        echo static::render($template, $data);
    }

}