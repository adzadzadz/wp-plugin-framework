<?php

namespace AdzWP\Core;

class Exception extends \Exception
{
    protected $context = [];
    protected $httpCode = 500;
    protected $shouldLog = true;
    
    public function __construct($message = "", $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        
        if ($this->shouldLog) {
            $this->logException();
        }
    }
    
    protected function logException()
    {
        $logger = Log::getInstance();
        
        $context = array_merge($this->context, [
            'exception' => get_class($this),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ]);
        
        $logger->error($this->getMessage(), $context);
    }
    
    public function getContext()
    {
        return $this->context;
    }
    
    public function getHttpCode()
    {
        return $this->httpCode;
    }
    
    public function toArray()
    {
        $data = [
            'error' => true,
            'message' => $this->getMessage(),
            'code' => $this->getCode()
        ];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $data['debug'] = [
                'exception' => get_class($this),
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'context' => $this->context
            ];
        }
        
        return $data;
    }
    
    public function toJson()
    {
        return json_encode($this->toArray());
    }
    
    public function render()
    {
        if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            wp_send_json_error($this->toArray(), $this->httpCode);
        } else {
            wp_die(
                $this->getMessage(),
                get_bloginfo('name') . ' - Error',
                ['response' => $this->httpCode]
            );
        }
    }
}

class ValidationException extends Exception
{
    protected $httpCode = 400;
    protected $errors = [];
    
    public function __construct($message = "Validation failed", array $errors = [], $code = 0, ?\Throwable $previous = null)
    {
        $this->errors = $errors;
        
        if (!empty($errors) && empty($message)) {
            $message = $this->formatErrors();
        }
        
        parent::__construct($message, $code, $previous, ['errors' => $errors]);
    }
    
    protected function formatErrors()
    {
        $messages = [];
        
        foreach ($this->errors as $field => $error) {
            if (is_array($error)) {
                $messages[] = $field . ': ' . implode(', ', $error);
            } else {
                $messages[] = $field . ': ' . $error;
            }
        }
        
        return implode('; ', $messages);
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    public function toArray()
    {
        $data = parent::toArray();
        $data['errors'] = $this->errors;
        
        return $data;
    }
}

class NotFoundException extends Exception
{
    protected $httpCode = 404;
    
    public function __construct($message = "Resource not found", $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}

class UnauthorizedException extends Exception
{
    protected $httpCode = 401;
    
    public function __construct($message = "Unauthorized access", $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}

class ForbiddenException extends Exception
{
    protected $httpCode = 403;
    
    public function __construct($message = "Access forbidden", $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}

class DatabaseException extends Exception
{
    protected $query;
    
    public function __construct($message = "Database error", $query = '', $code = 0, ?\Throwable $previous = null)
    {
        $this->query = $query;
        
        $context = ['query' => $query];
        
        if ($previous instanceof \Exception) {
            $context['mysql_error'] = $previous->getMessage();
        }
        
        parent::__construct($message, $code, $previous, $context);
    }
    
    public function getQuery()
    {
        return $this->query;
    }
}