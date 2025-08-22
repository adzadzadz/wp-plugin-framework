<?php

namespace AdzWP\Core;

class Validator
{
    protected $data = [];
    protected $rules = [];
    protected $errors = [];
    protected $customMessages = [];
    protected $stopOnFirstFailure = false;
    
    protected $validators = [
        'required', 'email', 'url', 'numeric', 'integer', 'boolean',
        'string', 'array', 'min', 'max', 'between', 'in', 'not_in',
        'regex', 'date', 'before', 'after', 'alpha', 'alpha_num',
        'alpha_dash', 'confirmed', 'different', 'same', 'unique',
        'exists', 'file', 'mimes', 'max_file_size'
    ];
    
    public function __construct(array $data = [], array $rules = [], array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $messages;
    }
    
    public static function make(array $data, array $rules, array $messages = [])
    {
        return new static($data, $rules, $messages);
    }
    
    public function validate()
    {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $this->validateField($field, $rules);
            
            if ($this->stopOnFirstFailure && $this->hasErrors()) {
                break;
            }
        }
        
        return !$this->hasErrors();
    }
    
    protected function validateField($field, $rules)
    {
        $rules = is_string($rules) ? explode('|', $rules) : $rules;
        $value = $this->getValue($field);
        
        foreach ($rules as $rule) {
            $this->applyRule($field, $value, $rule);
            
            if ($this->stopOnFirstFailure && isset($this->errors[$field])) {
                break;
            }
        }
    }
    
    protected function applyRule($field, $value, $rule)
    {
        if (is_string($rule)) {
            list($ruleName, $parameters) = $this->parseRule($rule);
        } else {
            $ruleName = $rule;
            $parameters = [];
        }
        
        $method = 'validate' . str_replace('_', '', ucwords($ruleName, '_'));
        
        if (method_exists($this, $method)) {
            $result = $this->$method($field, $value, $parameters);
            
            if (!$result) {
                $this->addError($field, $ruleName, $parameters);
            }
        }
    }
    
    protected function parseRule($rule)
    {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $parameters = isset($parts[1]) ? explode(',', $parts[1]) : [];
        
        return [$ruleName, $parameters];
    }
    
    protected function getValue($field)
    {
        if (strpos($field, '.') !== false) {
            $keys = explode('.', $field);
            $value = $this->data;
            
            foreach ($keys as $key) {
                if (is_array($value) && isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return null;
                }
            }
            
            return $value;
        }
        
        return $this->data[$field] ?? null;
    }
    
    protected function validateRequired($field, $value, $parameters)
    {
        return !is_null($value) && $value !== '' && $value !== [];
    }
    
    protected function validateEmail($field, $value, $parameters)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    protected function validateUrl($field, $value, $parameters)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    protected function validateNumeric($field, $value, $parameters)
    {
        return is_numeric($value);
    }
    
    protected function validateInteger($field, $value, $parameters)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    protected function validateBoolean($field, $value, $parameters)
    {
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false', 'yes', 'no'], true);
    }
    
    protected function validateString($field, $value, $parameters)
    {
        return is_string($value);
    }
    
    protected function validateArray($field, $value, $parameters)
    {
        return is_array($value);
    }
    
    protected function validateMin($field, $value, $parameters)
    {
        $min = $parameters[0] ?? 0;
        
        if (is_numeric($value)) {
            return $value >= $min;
        } elseif (is_string($value)) {
            return strlen($value) >= $min;
        } elseif (is_array($value)) {
            return count($value) >= $min;
        }
        
        return false;
    }
    
    protected function validateMax($field, $value, $parameters)
    {
        $max = $parameters[0] ?? PHP_INT_MAX;
        
        if (is_numeric($value)) {
            return $value <= $max;
        } elseif (is_string($value)) {
            return strlen($value) <= $max;
        } elseif (is_array($value)) {
            return count($value) <= $max;
        }
        
        return false;
    }
    
    protected function validateBetween($field, $value, $parameters)
    {
        $min = $parameters[0] ?? 0;
        $max = $parameters[1] ?? PHP_INT_MAX;
        
        return $this->validateMin($field, $value, [$min]) && 
               $this->validateMax($field, $value, [$max]);
    }
    
    protected function validateIn($field, $value, $parameters)
    {
        return in_array($value, $parameters);
    }
    
    protected function validateNotIn($field, $value, $parameters)
    {
        return !in_array($value, $parameters);
    }
    
    protected function validateRegex($field, $value, $parameters)
    {
        $pattern = $parameters[0] ?? '';
        return preg_match($pattern, $value) === 1;
    }
    
    protected function validateDate($field, $value, $parameters)
    {
        $format = $parameters[0] ?? 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);
        
        return $date && $date->format($format) === $value;
    }
    
    protected function validateAlpha($field, $value, $parameters)
    {
        return ctype_alpha($value);
    }
    
    protected function validateAlphaNum($field, $value, $parameters)
    {
        return ctype_alnum($value);
    }
    
    protected function validateAlphaDash($field, $value, $parameters)
    {
        return preg_match('/^[a-zA-Z0-9_-]+$/', $value) === 1;
    }
    
    protected function validateConfirmed($field, $value, $parameters)
    {
        $confirmField = $field . '_confirmation';
        return isset($this->data[$confirmField]) && $value === $this->data[$confirmField];
    }
    
    protected function validateDifferent($field, $value, $parameters)
    {
        $otherField = $parameters[0] ?? '';
        return !isset($this->data[$otherField]) || $value !== $this->data[$otherField];
    }
    
    protected function validateSame($field, $value, $parameters)
    {
        $otherField = $parameters[0] ?? '';
        return isset($this->data[$otherField]) && $value === $this->data[$otherField];
    }
    
    protected function validateUnique($field, $value, $parameters)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . ($parameters[0] ?? '');
        $column = $parameters[1] ?? $field;
        $except = $parameters[2] ?? null;
        
        if (empty($table)) {
            return false;
        }
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE $column = %s",
            $value
        );
        
        if ($except) {
            $query .= $wpdb->prepare(" AND id != %d", $except);
        }
        
        return $wpdb->get_var($query) == 0;
    }
    
    protected function validateExists($field, $value, $parameters)
    {
        global $wpdb;
        
        $table = $wpdb->prefix . ($parameters[0] ?? '');
        $column = $parameters[1] ?? 'id';
        
        if (empty($table)) {
            return false;
        }
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE $column = %s",
            $value
        );
        
        return $wpdb->get_var($query) > 0;
    }
    
    protected function addError($field, $rule, $parameters = [])
    {
        $message = $this->getErrorMessage($field, $rule, $parameters);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    protected function getErrorMessage($field, $rule, $parameters)
    {
        $key = $field . '.' . $rule;
        
        if (isset($this->customMessages[$key])) {
            $message = $this->customMessages[$key];
        } elseif (isset($this->customMessages[$rule])) {
            $message = $this->customMessages[$rule];
        } else {
            $message = $this->getDefaultMessage($rule);
        }
        
        return $this->replaceParameters($message, $field, $parameters);
    }
    
    protected function getDefaultMessage($rule)
    {
        $messages = [
            'required' => 'The :field field is required.',
            'email' => 'The :field must be a valid email address.',
            'url' => 'The :field must be a valid URL.',
            'numeric' => 'The :field must be numeric.',
            'integer' => 'The :field must be an integer.',
            'boolean' => 'The :field must be true or false.',
            'string' => 'The :field must be a string.',
            'array' => 'The :field must be an array.',
            'min' => 'The :field must be at least :min.',
            'max' => 'The :field must not exceed :max.',
            'between' => 'The :field must be between :min and :max.',
            'in' => 'The :field must be one of: :values.',
            'not_in' => 'The :field must not be one of: :values.',
            'regex' => 'The :field format is invalid.',
            'date' => 'The :field must be a valid date.',
            'alpha' => 'The :field must contain only letters.',
            'alpha_num' => 'The :field must contain only letters and numbers.',
            'alpha_dash' => 'The :field must contain only letters, numbers, dashes, and underscores.',
            'confirmed' => 'The :field confirmation does not match.',
            'different' => 'The :field and :other must be different.',
            'same' => 'The :field and :other must match.',
            'unique' => 'The :field has already been taken.',
            'exists' => 'The selected :field is invalid.'
        ];
        
        return $messages[$rule] ?? 'The :field is invalid.';
    }
    
    protected function replaceParameters($message, $field, $parameters)
    {
        $field = str_replace('_', ' ', $field);
        $message = str_replace(':field', $field, $message);
        
        if (count($parameters) > 0) {
            $message = str_replace(':min', $parameters[0] ?? '', $message);
            $message = str_replace(':max', $parameters[1] ?? $parameters[0] ?? '', $message);
            $message = str_replace(':values', implode(', ', $parameters), $message);
            $message = str_replace(':other', $parameters[0] ?? '', $message);
        }
        
        return $message;
    }
    
    public function errors()
    {
        return $this->errors;
    }
    
    public function hasErrors()
    {
        return !empty($this->errors);
    }
    
    public function firstError($field = null)
    {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        
        foreach ($this->errors as $errors) {
            if (!empty($errors)) {
                return $errors[0];
            }
        }
        
        return null;
    }
    
    public function validated()
    {
        $validated = [];
        
        foreach ($this->rules as $field => $rules) {
            if (!isset($this->errors[$field])) {
                $validated[$field] = $this->getValue($field);
            }
        }
        
        return $validated;
    }
    
    public function fails()
    {
        return !$this->validate();
    }
    
    public function passes()
    {
        return $this->validate();
    }
}