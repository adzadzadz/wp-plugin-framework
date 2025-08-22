<?php

namespace AdzWP\Core;

abstract class Core
{

    protected $container = [];

    public $pluginPath;

    function __construct(array $args = [])
    {
        foreach ($args as $prop => $v) {
            if (property_exists($this, $prop)) $this->$prop = $v;
        }
        $this->init();
    }

    public function init()
    {
        $this->registerHooks();
        $this->bootstrap();
    }

    protected function registerHooks()
    {
        if (property_exists($this, 'actions') && is_array($this->actions)) {
            foreach ($this->actions as $hook => $callback) {
                $this->registerAction($hook, $callback);
            }
        }

        if (property_exists($this, 'filters') && is_array($this->filters)) {
            foreach ($this->filters as $hook => $callback) {
                $this->registerFilter($hook, $callback);
            }
        }
    }

    protected function registerAction($hook, $callback)
    {
        if (is_string($callback)) {
            $callback = [$this, $callback];
        }

        $priority = 10;
        $acceptedArgs = 1;

        if (is_array($callback) && isset($callback['callback'])) {
            $priority = $callback['priority'] ?? 10;
            $acceptedArgs = $callback['accepted_args'] ?? 1;
            $callback = is_string($callback['callback']) ? [$this, $callback['callback']] : $callback['callback'];
        }

        if (function_exists('add_action')) {
            \add_action($hook, $callback, $priority, $acceptedArgs);
        }
    }

    protected function registerFilter($hook, $callback)
    {
        if (is_string($callback)) {
            $callback = [$this, $callback];
        }

        $priority = 10;
        $acceptedArgs = 1;

        if (is_array($callback) && isset($callback['callback'])) {
            $priority = $callback['priority'] ?? 10;
            $acceptedArgs = $callback['accepted_args'] ?? 1;
            $callback = is_string($callback['callback']) ? [$this, $callback['callback']] : $callback['callback'];
        }

        if (function_exists('add_filter')) {
            \add_filter($hook, $callback, $priority, $acceptedArgs);
        }
    }

    protected function bootstrap()
    {
        // Override in child classes
    }

    public function bind($key, $value)
    {
        $this->container[$key] = $value;
    }

    public function get($key, $default = null)
    {
        return $this->container[$key] ?? $default;
    }
}
