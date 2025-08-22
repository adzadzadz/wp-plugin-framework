<?php

namespace AdzWP;

class WordPressController extends Controller {

    public $filters = [];
    public $actions = [];

    public function addAction($hook, $callback = null, $priority = 10, $accepted_args = 1)
    {
        $callback = $callback ?? $hook;
        
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }
        
        return add_action($hook, $callback, $priority, $accepted_args);
    }

    public function removeAction($hook, $callback = null, $priority = 10)
    {
        $callback = $callback ?? $hook;
        
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }
        
        return remove_action($hook, $callback, $priority);
    }

    public function addFilter($hook, $callback = null, $priority = 10, $accepted_args = 1)
    {
        $callback = $callback ?? $hook;
        
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }
        
        return add_filter($hook, $callback, $priority, $accepted_args);
    }

    public function removeFilter($hook, $callback = null, $priority = 10)
    {
        $callback = $callback ?? $hook;
        
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }
        
        return remove_filter($hook, $callback, $priority);
    }

    public function doAction($hook, ...$args)
    {
        return do_action($hook, ...$args);
    }

    public function applyFilters($hook, $value, ...$args)
    {
        return apply_filters($hook, $value, ...$args);
    }

    public function hasAction($hook)
    {
        return has_action($hook);
    }

    public function hasFilter($hook)
    {
        return has_filter($hook);
    }

    protected function getCurrentUserId()
    {
        return get_current_user_id();
    }

    protected function currentUserCan($capability)
    {
        return current_user_can($capability);
    }

    protected function isAdmin()
    {
        return is_admin();
    }

    protected function isFrontend()
    {
        return !is_admin();
    }

    protected function wpDie($message, $title = '', $args = [])
    {
        wp_die($message, $title, $args);
    }

    protected function sanitizeText($text)
    {
        return sanitize_text_field($text);
    }

    protected function escapeHtml($text)
    {
        return esc_html($text);
    }

    protected function escapeUrl($url)
    {
        return esc_url($url);
    }

    protected function verifyNonce($nonce, $action = -1)
    {
        return wp_verify_nonce($nonce, $action);
    }

    protected function createNonce($action = -1)
    {
        return wp_create_nonce($action);
    }

}