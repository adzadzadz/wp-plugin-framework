<?php 

namespace AdzWP\Core;

use AdzWP\ValidationException;
use AdzWP\Security;

/**
 * Enhanced Model class with validation and data management
 */
abstract class Model extends \AdzWP\Core\Core 
{
    /**
     * Model data
     */
    protected array $data = [];
    
    /**
     * Model attributes that are mass assignable
     */
    protected array $fillable = [];
    
    /**
     * Model attributes that are guarded from mass assignment
     */
    protected array $guarded = ['id'];
    
    /**
     * Validation rules
     */
    protected array $rules = [];
    
    /**
     * Validation messages
     */
    protected array $messages = [];
    
    /**
     * Indicates if the model should be timestamped
     */
    protected bool $timestamps = true;
    
    public function __construct(array $attributes = [])
    {
        parent::__construct();
        
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
    
    /**
     * Fill the model with an array of attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        
        return $this;
    }
    
    /**
     * Determine if the given attribute may be mass assigned
     */
    public function isFillable(string $key): bool
    {
        if (in_array($key, $this->fillable)) {
            return true;
        }
        
        if (!empty($this->fillable) && !in_array($key, $this->guarded)) {
            return false;
        }
        
        return !in_array($key, $this->guarded);
    }
    
    /**
     * Set a given attribute on the model
     */
    public function setAttribute(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Get an attribute from the model
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Determine if an attribute exists on the model
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
    
    /**
     * Get all of the current attributes on the model
     */
    public function getAttributes(): array
    {
        return $this->data;
    }
    
    /**
     * Set the array of model attributes. No checking is done.
     */
    public function setRawAttributes(array $attributes): self
    {
        $this->data = $attributes;
        return $this;
    }
    
    /**
     * Validate the model data
     * 
     * @throws ValidationException
     */
    public function validate(): bool
    {
        if (empty($this->rules)) {
            return true;
        }
        
        $validator = new Validator($this->data, $this->rules, $this->messages);
        
        if (!$validator->passes()) {
            throw new ValidationException('Validation failed', $validator->errors());
        }
        
        return true;
    }
    
    /**
     * Save the model
     */
    public function save(): bool
    {
        $this->validate();
        
        if ($this->timestamps) {
            $this->updateTimestamps();
        }
        
        // Override this method in child classes to implement actual saving
        return $this->performSave();
    }
    
    /**
     * Update the model's timestamps
     */
    protected function updateTimestamps(): void
    {
        $time = current_time('mysql');
        
        if (!$this->hasAttribute('created_at')) {
            $this->setAttribute('created_at', $time);
        }
        
        $this->setAttribute('updated_at', $time);
    }
    
    /**
     * Perform the actual save operation
     * Override this method in child classes
     */
    protected function performSave(): bool
    {
        return true;
    }
    
    /**
     * Convert the model to array
     */
    public function toArray(): array
    {
        return $this->data;
    }
    
    /**
     * Convert the model to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     * Sanitize model data
     */
    public function sanitize(array $rules = []): self
    {
        $security = Security::getInstance();
        
        foreach ($this->data as $key => $value) {
            $rule = $rules[$key] ?? 'text';
            $this->data[$key] = $security->sanitize($value, $rule);
        }
        
        return $this;
    }
    
    /**
     * Magic getter
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Magic setter
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Magic isset
     */
    public function __isset(string $key): bool
    {
        return $this->hasAttribute($key);
    }
    
    /**
     * Magic unset
     */
    public function __unset(string $key): void
    {
        unset($this->data[$key]);
    }
    
    /**
     * Convert the model to string (JSON)
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}