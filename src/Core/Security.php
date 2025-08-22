<?php

namespace AdzWP\Core;

class Security
{
    protected static $instance = null;
    protected $nonceAction = 'adz_nonce_action';
    protected $nonceName = 'adz_nonce';
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    public function createNonce($action = null)
    {
        $action = $action ?: $this->nonceAction;
        return wp_create_nonce($action);
    }
    
    public function verifyNonce($nonce, $action = null)
    {
        $action = $action ?: $this->nonceAction;
        return wp_verify_nonce($nonce, $action);
    }
    
    public function getNonceField($action = null, $name = null, $referer = true, $echo = true)
    {
        $action = $action ?: $this->nonceAction;
        $name = $name ?: $this->nonceName;
        
        return wp_nonce_field($action, $name, $referer, $echo);
    }
    
    public function verifyRequest($nonceName = null, $action = null)
    {
        $nonceName = $nonceName ?: $this->nonceName;
        $action = $action ?: $this->nonceAction;
        
        if (!isset($_REQUEST[$nonceName])) {
            throw new UnauthorizedException('Security check failed: Nonce not found');
        }
        
        if (!$this->verifyNonce($_REQUEST[$nonceName], $action)) {
            throw new UnauthorizedException('Security check failed: Invalid nonce');
        }
        
        return true;
    }
    
    public function verifyAjaxRequest($nonceName = null, $action = null)
    {
        if (!wp_doing_ajax()) {
            throw new ForbiddenException('Not an AJAX request');
        }
        
        return $this->verifyRequest($nonceName, $action);
    }
    
    public function checkCapability($capability = 'manage_options', $userId = null)
    {
        $userId = $userId ?: get_current_user_id();
        
        if (!user_can($userId, $capability)) {
            throw new ForbiddenException('Insufficient permissions');
        }
        
        return true;
    }
    
    public function sanitize($input, $type = 'text')
    {
        switch ($type) {
            case 'text':
                return sanitize_text_field($input);
                
            case 'textarea':
                return sanitize_textarea_field($input);
                
            case 'email':
                return sanitize_email($input);
                
            case 'url':
                return esc_url_raw($input);
                
            case 'int':
                return intval($input);
                
            case 'float':
                return floatval($input);
                
            case 'bool':
                return filter_var($input, FILTER_VALIDATE_BOOLEAN);
                
            case 'key':
                return sanitize_key($input);
                
            case 'filename':
                return sanitize_file_name($input);
                
            case 'html':
                return wp_kses_post($input);
                
            case 'array':
                if (!is_array($input)) {
                    return [];
                }
                return array_map([$this, 'sanitize'], $input);
                
            default:
                return sanitize_text_field($input);
        }
    }
    
    public function sanitizeArray(array $data, array $rules)
    {
        $sanitized = [];
        
        foreach ($rules as $field => $type) {
            if (isset($data[$field])) {
                $sanitized[$field] = $this->sanitize($data[$field], $type);
            }
        }
        
        return $sanitized;
    }
    
    public function escape($input, $context = 'html')
    {
        switch ($context) {
            case 'html':
                return esc_html($input);
                
            case 'attr':
                return esc_attr($input);
                
            case 'url':
                return esc_url($input);
                
            case 'js':
                return esc_js($input);
                
            case 'textarea':
                return esc_textarea($input);
                
            default:
                return esc_html($input);
        }
    }
    
    public function generateToken($length = 32)
    {
        return wp_generate_password($length, false);
    }
    
    public function hashPassword($password)
    {
        return wp_hash_password($password);
    }
    
    public function checkPassword($password, $hash, $userId = '')
    {
        return wp_check_password($password, $hash, $userId);
    }
    
    public function isSSL()
    {
        return is_ssl();
    }
    
    public function forceSSL()
    {
        if (!$this->isSSL() && !is_admin()) {
            wp_redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301);
            exit();
        }
    }
    
    public function getRateLimitKey($action = '', $identifier = null)
    {
        if (!$identifier) {
            $identifier = $this->getClientIdentifier();
        }
        
        return 'adz_rate_limit_' . md5($action . '_' . $identifier);
    }
    
    public function checkRateLimit($action = '', $limit = 10, $window = 3600, $identifier = null)
    {
        $key = $this->getRateLimitKey($action, $identifier);
        $attempts = get_transient($key);
        
        if ($attempts === false) {
            set_transient($key, 1, $window);
            return true;
        }
        
        if ($attempts >= $limit) {
            throw new ForbiddenException('Rate limit exceeded. Please try again later.');
        }
        
        set_transient($key, $attempts + 1, $window);
        return true;
    }
    
    protected function getClientIdentifier()
    {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        
        return 'ip_' . $this->getClientIP();
    }
    
    public function getClientIP()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}