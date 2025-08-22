<?php

namespace AdzWP;

/**
 * Enhanced Database Model with ORM-like features
 * 
 * Provides an active record pattern for database interactions
 */
abstract class Model
{
    protected $connection;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = [];
    protected $attributes = [];
    protected $original = [];
    protected $exists = false;
    protected $timestamps = true;
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Model constructor
     */
    public function __construct(array $attributes = [])
    {
        $this->connection = Connection::getInstance();
        
        if (!$this->table) {
            $this->table = $this->getDefaultTableName();
        }
        
        $this->fill($attributes);
    }

    /**
     * Get the default table name from class name
     */
    protected function getDefaultTableName()
    {
        $className = basename(str_replace('\\', '/', get_class($this)));
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        
        // Remove 'Model' suffix if present
        $tableName = preg_replace('/model$/', '', $tableName);
        
        // Pluralize (simple pluralization)
        if (substr($tableName, -1) === 'y') {
            $tableName = substr($tableName, 0, -1) . 'ies';
        } elseif (substr($tableName, -1) === 's') {
            $tableName .= 'es';
        } else {
            $tableName .= 's';
        }
        
        return $tableName;
    }

    /**
     * Create a new query builder instance
     */
    public function newQuery()
    {
        return (new QueryBuilder($this->connection))->table($this->table);
    }

    /**
     * Static method to start a query
     */
    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * Find a model by its primary key
     */
    public static function find($id)
    {
        $instance = new static;
        $result = $instance->newQuery()
                          ->where($instance->primaryKey, $id)
                          ->first();
        
        if ($result) {
            return $instance->newFromBuilder($result);
        }
        
        return null;
    }

    /**
     * Find a model by its primary key or throw an exception
     */
    public static function findOrFail($id)
    {
        $model = static::find($id);
        
        if (!$model) {
            throw new \Exception("Model not found with {$model->primaryKey}: {$id}");
        }
        
        return $model;
    }

    /**
     * Get all models
     */
    public static function all()
    {
        $instance = new static;
        $results = $instance->newQuery()->get();
        
        return $instance->hydrate($results);
    }

    /**
     * Create a new model and save it
     */
    public static function create(array $attributes = [])
    {
        $model = new static($attributes);
        $model->save();
        
        return $model;
    }

    /**
     * Update or create a model
     */
    public static function updateOrCreate(array $attributes, array $values = [])
    {
        $instance = new static;
        
        // Build where clause from attributes
        $query = $instance->newQuery();
        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }
        
        $model = $query->first();
        
        if ($model) {
            $modelInstance = $instance->newFromBuilder($model);
            $modelInstance->fill($values);
            $modelInstance->save();
            return $modelInstance;
        }
        
        return static::create(array_merge($attributes, $values));
    }

    /**
     * Save the model
     */
    public function save()
    {
        if ($this->exists) {
            return $this->performUpdate();
        }
        
        return $this->performInsert();
    }

    /**
     * Perform an insert operation
     */
    protected function performInsert()
    {
        $attributes = $this->getAttributesForInsert();
        
        if ($this->timestamps) {
            $now = date($this->dateFormat);
            $attributes['created_at'] = $now;
            $attributes['updated_at'] = $now;
        }
        
        $result = $this->connection->insert($this->table, $attributes);
        
        if ($result) {
            $this->setAttribute($this->primaryKey, $this->connection->getInsertId());
            $this->exists = true;
            $this->syncOriginal();
            return true;
        }
        
        return false;
    }

    /**
     * Perform an update operation
     */
    protected function performUpdate()
    {
        $dirty = $this->getDirty();
        
        if (empty($dirty)) {
            return true; // No changes to save
        }
        
        if ($this->timestamps) {
            $dirty['updated_at'] = date($this->dateFormat);
        }
        
        $result = $this->connection->update(
            $this->table,
            $dirty,
            [$this->primaryKey => $this->getAttribute($this->primaryKey)]
        );
        
        if ($result !== false) {
            $this->syncOriginal();
            return true;
        }
        
        return false;
    }

    /**
     * Delete the model
     */
    public function delete()
    {
        if (!$this->exists) {
            return false;
        }
        
        $result = $this->connection->delete(
            $this->table,
            [$this->primaryKey => $this->getAttribute($this->primaryKey)]
        );
        
        if ($result) {
            $this->exists = false;
            return true;
        }
        
        return false;
    }

    /**
     * Get attributes for insert
     */
    protected function getAttributesForInsert()
    {
        $attributes = $this->attributes;
        
        // Remove primary key if it's null (for auto-increment)
        if (is_null($this->getAttribute($this->primaryKey))) {
            unset($attributes[$this->primaryKey]);
        }
        
        return $this->filterFillableAttributes($attributes);
    }

    /**
     * Get dirty attributes (changed since last sync)
     */
    public function getDirty()
    {
        $dirty = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        
        return $this->filterFillableAttributes($dirty);
    }

    /**
     * Filter attributes based on fillable/guarded rules
     */
    protected function filterFillableAttributes(array $attributes)
    {
        if (!empty($this->fillable)) {
            return array_intersect_key($attributes, array_flip($this->fillable));
        }
        
        if (!empty($this->guarded)) {
            return array_diff_key($attributes, array_flip($this->guarded));
        }
        
        return $attributes;
    }

    /**
     * Fill the model with attributes
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        
        return $this;
    }

    /**
     * Check if an attribute is fillable
     */
    public function isFillable($key)
    {
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }
        
        if (!empty($this->guarded)) {
            return !in_array($key, $this->guarded);
        }
        
        return true;
    }

    /**
     * Set an attribute
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get an attribute
     */
    public function getAttribute($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Check if an attribute exists
     */
    public function hasAttribute($key)
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Get all attributes
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Sync the original attributes with current
     */
    public function syncOriginal()
    {
        $this->original = $this->attributes;
        return $this;
    }

    /**
     * Create a new model instance from query builder result
     */
    public function newFromBuilder($attributes)
    {
        $model = new static;
        $model->exists = true;
        
        // Convert stdClass to array if needed
        if (is_object($attributes)) {
            $attributes = (array) $attributes;
        }
        
        $model->attributes = $attributes;
        $model->syncOriginal();
        
        return $model;
    }

    /**
     * Create a collection of models from an array of attributes
     */
    public function hydrate(array $items)
    {
        $models = [];
        
        foreach ($items as $item) {
            $models[] = $this->newFromBuilder($item);
        }
        
        return $models;
    }

    /**
     * Get the model's primary key value
     */
    public function getKey()
    {
        return $this->getAttribute($this->primaryKey);
    }

    /**
     * Get the primary key for the model
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Magic getter for attributes
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter for attributes
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset for attributes
     */
    public function __isset($key)
    {
        return $this->hasAttribute($key);
    }

    /**
     * Magic unset for attributes
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Convert the model to an array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Convert the model to JSON
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Handle dynamic static method calls
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->newQuery()->$method(...$parameters);
    }

    /**
     * Handle dynamic method calls
     */
    public function __call($method, $parameters)
    {
        return $this->newQuery()->$method(...$parameters);
    }

    /**
     * Convert the model to string (JSON)
     */
    public function __toString()
    {
        return $this->toJson();
    }
}