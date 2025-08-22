<?php

namespace App\Models;

use AdzWP\Db\Model;

class ExampleModel extends Model
{
    protected $table = 'examples';
    
    protected $fillable = [
        'name',
        'description',
        'status'
    ];
    
    protected $guarded = [
        'id'
    ];
    
    /**
     * Find an example by ID
     */
    public function find($id)
    {
        return $this->queryBuilder()
            ->select(['id', 'name', 'description', 'status'])
            ->from($this->table)
            ->where('id', $id)
            ->first();
    }
    
    /**
     * Get active examples
     */
    public function getActive($limit = 10)
    {
        return $this->queryBuilder()
            ->select(['id', 'name', 'description'])
            ->from($this->table)
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Create a new example
     */
    public function create($data)
    {
        $data = $this->validateData($data);
        
        return $this->queryBuilder()
            ->table($this->table)
            ->insert($data);
    }
    
    /**
     * Validate data before saving
     */
    protected function validateData($data)
    {
        $clean = [];
        
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                $clean[$field] = sanitize_text_field($data[$field]);
            }
        }
        
        return $clean;
    }
}