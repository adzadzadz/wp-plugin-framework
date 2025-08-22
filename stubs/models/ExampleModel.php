<?php

namespace App\Models;

use Adz\WordPress\Model;

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
    
    // Add model relationships and methods here
}