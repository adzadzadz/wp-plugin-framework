<?php

namespace AdzWP\Core;

/**
 * AssetManager - Handle CSS and JavaScript assets with framework integration
 * 
 * Provides centralized asset management with Bootstrap 5 integration,
 * context-aware loading, and WordPress best practices.
 */
class AssetManager
{
    /**
     * Registered assets
     * 
     * @var array
     */
    protected static $assets = [
        'styles' => [],
        'scripts' => []
    ];
    
    /**
     * Default assets configuration
     * 
     * @var array
     */
    protected static $defaults = [
        'bootstrap' => [
            'css' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
            'js' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
            'contexts' => ['admin', 'plugin'] // Only load in admin and plugin contexts
        ]
    ];
    
    /**
     * Initialize asset manager
     */
    public static function init()
    {
        // Hook into WordPress
        add_action('wp_enqueue_scripts', [static::class, 'enqueueFrontendAssets']);
        add_action('admin_enqueue_scripts', [static::class, 'enqueueAdminAssets']);
        
        // Register default assets
        static::registerDefaults();
    }
    
    /**
     * Register default assets (Bootstrap 5, etc.)
     */
    protected static function registerDefaults()
    {
        // Bootstrap 5 CSS
        static::registerStyle('bootstrap-css', [
            'url' => static::$defaults['bootstrap']['css'],
            'contexts' => static::$defaults['bootstrap']['contexts'],
            'version' => '5.3.2',
            'priority' => 5
        ]);
        
        // Bootstrap 5 JS
        static::registerScript('bootstrap-js', [
            'url' => static::$defaults['bootstrap']['js'],
            'contexts' => static::$defaults['bootstrap']['contexts'],
            'version' => '5.3.2',
            'priority' => 5,
            'in_footer' => true
        ]);
    }
    
    /**
     * Register a CSS stylesheet
     * 
     * @param string $handle Asset handle
     * @param array $config Asset configuration
     */
    public static function registerStyle($handle, $config = [])
    {
        $defaults = [
            'url' => '',
            'dependencies' => [],
            'version' => '1.0.0',
            'media' => 'all',
            'contexts' => ['all'], // 'admin', 'frontend', 'plugin', 'all'
            'priority' => 10,
            'conditional' => null // Function to check if asset should load
        ];
        
        static::$assets['styles'][$handle] = array_merge($defaults, $config);
    }
    
    /**
     * Register a JavaScript file
     * 
     * @param string $handle Asset handle
     * @param array $config Asset configuration
     */
    public static function registerScript($handle, $config = [])
    {
        $defaults = [
            'url' => '',
            'dependencies' => [],
            'version' => '1.0.0',
            'in_footer' => true,
            'contexts' => ['all'],
            'priority' => 10,
            'conditional' => null,
            'localize' => [] // Localization data
        ];
        
        static::$assets['scripts'][$handle] = array_merge($defaults, $config);
    }
    
    /**
     * Enqueue frontend assets
     */
    public static function enqueueFrontendAssets()
    {
        static::enqueueAssets('frontend');
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook_suffix Current admin page hook suffix
     */
    public static function enqueueAdminAssets($hook_suffix = '')
    {
        static::enqueueAssets('admin', $hook_suffix);
    }
    
    /**
     * Enqueue assets for specific context
     * 
     * @param string $context Current context (admin, frontend, plugin)
     * @param string $hook_suffix Admin page hook (for admin context)
     */
    protected static function enqueueAssets($context, $hook_suffix = '')
    {
        // Determine if we're in plugin context
        $is_plugin_context = static::isPluginContext($hook_suffix);
        
        // Enqueue stylesheets
        foreach (static::$assets['styles'] as $handle => $config) {
            if (static::shouldLoadAsset($config, $context, $is_plugin_context)) {
                wp_enqueue_style(
                    "adz-{$handle}",
                    $config['url'],
                    $config['dependencies'],
                    $config['version'],
                    $config['media']
                );
            }
        }
        
        // Enqueue scripts
        foreach (static::$assets['scripts'] as $handle => $config) {
            if (static::shouldLoadAsset($config, $context, $is_plugin_context)) {
                wp_enqueue_script(
                    "adz-{$handle}",
                    $config['url'],
                    $config['dependencies'],
                    $config['version'],
                    $config['in_footer']
                );
                
                // Add localization if provided
                if (!empty($config['localize'])) {
                    wp_localize_script(
                        "adz-{$handle}",
                        $config['localize']['object_name'] ?? 'adzData',
                        $config['localize']['data'] ?? []
                    );
                }
            }
        }
    }
    
    /**
     * Check if asset should load in current context
     * 
     * @param array $config Asset configuration
     * @param string $context Current context
     * @param bool $is_plugin_context Is plugin context
     * @return bool
     */
    protected static function shouldLoadAsset($config, $context, $is_plugin_context)
    {
        // Check conditional function
        if ($config['conditional'] && is_callable($config['conditional'])) {
            if (!call_user_func($config['conditional'])) {
                return false;
            }
        }
        
        // Check contexts
        $contexts = $config['contexts'];
        
        if (in_array('all', $contexts)) {
            return true;
        }
        
        if (in_array($context, $contexts)) {
            // For admin context, check if Bootstrap should only load in plugin pages
            if ($context === 'admin' && in_array('plugin', $contexts)) {
                return $is_plugin_context;
            }
            return true;
        }
        
        if (in_array('plugin', $contexts) && $is_plugin_context) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if current page is within plugin context
     * 
     * @param string $hook_suffix Admin page hook
     * @return bool
     */
    protected static function isPluginContext($hook_suffix = '')
    {
        // Check if we're on a plugin admin page
        global $pagenow;
        
        // Plugin-specific admin pages usually contain the plugin slug
        if (!empty($hook_suffix) && (
            strpos($hook_suffix, 'adz') !== false ||
            strpos($hook_suffix, static::getPluginSlug()) !== false
        )) {
            return true;
        }
        
        // Check current screen
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && (
                strpos($screen->id, 'adz') !== false ||
                strpos($screen->id, static::getPluginSlug()) !== false
            )) {
                return true;
            }
        }
        
        // Check query parameters for plugin context
        if (isset($_GET['page']) && (
            strpos($_GET['page'], 'adz') !== false ||
            strpos($_GET['page'], static::getPluginSlug()) !== false
        )) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get plugin slug from configuration
     * 
     * @return string
     */
    protected static function getPluginSlug()
    {
        $config = Config::getInstance();
        return $config->get('plugin.slug', 'adz-plugin');
    }
    
    /**
     * Add inline CSS
     * 
     * @param string $handle Style handle to add CSS to
     * @param string $css CSS code
     */
    public static function addInlineStyle($handle, $css)
    {
        wp_add_inline_style("adz-{$handle}", $css);
    }
    
    /**
     * Add inline JavaScript
     * 
     * @param string $handle Script handle to add JS to
     * @param string $js JavaScript code
     * @param string $position Position ('before' or 'after')
     */
    public static function addInlineScript($handle, $js, $position = 'after')
    {
        wp_add_inline_script("adz-{$handle}", $js, $position);
    }
    
    /**
     * Deregister an asset
     * 
     * @param string $handle Asset handle
     * @param string $type Asset type ('style' or 'script')
     */
    public static function deregister($handle, $type = 'both')
    {
        if ($type === 'style' || $type === 'both') {
            unset(static::$assets['styles'][$handle]);
            wp_dequeue_style("adz-{$handle}");
            wp_deregister_style("adz-{$handle}");
        }
        
        if ($type === 'script' || $type === 'both') {
            unset(static::$assets['scripts'][$handle]);
            wp_dequeue_script("adz-{$handle}");
            wp_deregister_script("adz-{$handle}");
        }
    }
    
    /**
     * Enable/disable Bootstrap 5
     * 
     * @param bool $enable Enable or disable Bootstrap
     * @param array $contexts Contexts where Bootstrap should load
     */
    public static function setBootstrap($enable = true, $contexts = ['admin', 'plugin'])
    {
        if ($enable) {
            static::$assets['styles']['bootstrap-css']['contexts'] = $contexts;
            static::$assets['scripts']['bootstrap-js']['contexts'] = $contexts;
        } else {
            static::deregister('bootstrap-css', 'style');
            static::deregister('bootstrap-js', 'script');
        }
    }
    
    /**
     * Get all registered assets
     * 
     * @return array
     */
    public static function getAssets()
    {
        return static::$assets;
    }
    
    /**
     * Clear all registered assets
     */
    public static function clear()
    {
        static::$assets = ['styles' => [], 'scripts' => []];
    }
}