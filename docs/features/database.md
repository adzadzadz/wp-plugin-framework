# Database Operations

The ADZ Plugin Framework provides a powerful database abstraction layer with a fluent query builder, making database operations intuitive and secure while maintaining full compatibility with WordPress database conventions.

## Overview

Database features include:
- **Fluent Query Builder** - Method chaining for complex queries
- **WordPress Integration** - Uses WordPress database connection and conventions
- **Security** - Automatic SQL injection prevention
- **Transactions** - Full transaction support with rollback
- **Schema Management** - Table creation and migration tools
- **Performance** - Optimized queries and caching support

## Getting Started

### Basic Usage

```php
use AdzHive\Database;

$db = Database::getInstance();

// Simple query
$users = $db->table('users')->get();

// Query with conditions
$activeUsers = $db->table('users')
    ->where('status', 'active')
    ->where('created_at', '>', '2023-01-01')
    ->orderBy('name')
    ->get();
```

### Table Names

The framework automatically handles WordPress table prefixes:

```php
// These are equivalent:
$db->table('users')                    // Uses: wp_adz_users
$db->table('adz_users')               // Uses: wp_adz_users  
$db->table($db->getTableName('users')) // Uses: wp_adz_users
```

## Query Builder

### Basic Queries

#### Select All Records

```php
// Get all records
$posts = $db->table('posts')->get();

// Get specific columns
$posts = $db->table('posts')
    ->select(['id', 'title', 'status'])
    ->get();

// Get single record
$post = $db->table('posts')
    ->where('id', 1)
    ->first();
```

#### Where Clauses

```php
// Basic where clause
$db->table('users')->where('status', 'active');

// Where with operator
$db->table('posts')->where('views', '>', 1000);

// Multiple where clauses (AND)
$db->table('users')
    ->where('status', 'active')
    ->where('role', 'subscriber')
    ->where('created_at', '>', '2023-01-01');

// OR where clauses
$db->table('posts')
    ->where('status', 'published')
    ->orWhere('status', 'featured');
```

#### Advanced Where Conditions

```php
// WHERE IN
$db->table('posts')
    ->whereIn('status', ['published', 'featured', 'sticky']);

// WHERE NOT IN
$db->table('users')
    ->whereNotIn('role', ['spam', 'banned']);

// WHERE BETWEEN
$db->table('posts')
    ->whereBetween('created_at', ['2023-01-01', '2023-12-31']);

// WHERE NULL / NOT NULL
$db->table('users')
    ->whereNull('deleted_at')
    ->whereNotNull('email_verified_at');
```

### Joins

```php
// Inner join
$results = $db->table('posts')
    ->join('users', 'posts.author_id', '=', 'users.id')
    ->select(['posts.*', 'users.name as author_name'])
    ->get();

// Left join
$results = $db->table('posts')
    ->leftJoin('post_meta', 'posts.id', '=', 'post_meta.post_id')
    ->get();

// Multiple joins
$results = $db->table('posts')
    ->join('users', 'posts.author_id', '=', 'users.id')
    ->leftJoin('categories', 'posts.category_id', '=', 'categories.id')
    ->select(['posts.*', 'users.name', 'categories.title as category'])
    ->get();
```

### Ordering and Limiting

```php
// Order by
$posts = $db->table('posts')
    ->orderBy('created_at', 'DESC')
    ->orderBy('title', 'ASC')
    ->get();

// Limit and offset
$posts = $db->table('posts')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->offset(20)
    ->get();

// Pagination helper
$page = 2;
$perPage = 10;
$posts = $db->table('posts')
    ->limit($perPage)
    ->offset(($page - 1) * $perPage)
    ->get();
```

### Grouping and Aggregation

```php
// Group by
$stats = $db->table('posts')
    ->select(['status', 'COUNT(*) as count'])
    ->groupBy('status')
    ->get();

// Having clause
$popularAuthors = $db->table('posts')
    ->select(['author_id', 'COUNT(*) as post_count'])
    ->groupBy('author_id')
    ->having('post_count', '>', 10)
    ->get();

// Count records
$totalPosts = $db->table('posts')->count();
$publishedPosts = $db->table('posts')
    ->where('status', 'published')
    ->count();

// Check existence
$hasPublishedPosts = $db->table('posts')
    ->where('status', 'published')
    ->exists();
```

## CRUD Operations

### Insert Operations

```php
// Insert single record
$postId = $db->table('posts')->insert([
    'title' => 'My New Post',
    'content' => 'Post content here...',
    'status' => 'draft',
    'author_id' => get_current_user_id(),
    'created_at' => current_time('mysql')
]);

// Insert multiple records
$db->table('tags')->insert([
    ['name' => 'PHP', 'slug' => 'php'],
    ['name' => 'WordPress', 'slug' => 'wordpress'],
    ['name' => 'JavaScript', 'slug' => 'javascript']
]);

// Get inserted ID
echo "New post ID: " . $postId;
```

### Update Operations

```php
// Update with where clause
$affectedRows = $db->table('posts')
    ->where('id', 1)
    ->update([
        'title' => 'Updated Title',
        'updated_at' => current_time('mysql')
    ]);

// Update multiple records
$db->table('posts')
    ->where('status', 'draft')
    ->where('created_at', '<', date('Y-m-d', strtotime('-30 days')))
    ->update(['status' => 'archived']);

// Conditional updates
if ($db->table('posts')->where('id', $postId)->exists()) {
    $db->table('posts')
        ->where('id', $postId)
        ->update(['views' => $views + 1]);
}
```

### Delete Operations

```php
// Delete with conditions
$deletedRows = $db->table('posts')
    ->where('status', 'spam')
    ->delete();

// Delete single record
$db->table('posts')
    ->where('id', $postId)
    ->delete();

// Delete with multiple conditions
$db->table('posts')
    ->where('status', 'draft')
    ->where('created_at', '<', date('Y-m-d', strtotime('-1 year')))
    ->delete();
```

## Raw Queries

### Executing Raw SQL

```php
// Raw query with results
$results = $db->get(
    "SELECT p.*, u.name as author_name 
     FROM {$db->getTableName('posts')} p 
     JOIN {$db->getTableName('users')} u ON p.author_id = u.id 
     WHERE p.status = %s 
     ORDER BY p.created_at DESC",
    ['published']
);

// Raw query without results
$db->query(
    "UPDATE {$db->getTableName('posts')} 
     SET views = views + 1 
     WHERE id = %d",
    [$postId]
);

// Get single value
$totalViews = $db->getValue(
    "SELECT SUM(views) FROM {$db->getTableName('posts')} WHERE status = %s",
    ['published']
);

// Get single row
$post = $db->getRow(
    "SELECT * FROM {$db->getTableName('posts')} WHERE slug = %s",
    [$slug]
);
```

### Prepared Statements

```php
// Parameters are automatically escaped
$posts = $db->get(
    "SELECT * FROM {$db->getTableName('posts')} 
     WHERE author_id = %d 
     AND status = %s 
     AND created_at BETWEEN %s AND %s",
    [$authorId, $status, $startDate, $endDate]
);
```

## Transactions

### Basic Transactions

```php
try {
    $db->beginTransaction();
    
    // Insert post
    $postId = $db->table('posts')->insert([
        'title' => 'New Post',
        'content' => 'Content here...'
    ]);
    
    // Insert tags
    $db->table('post_tags')->insert([
        'post_id' => $postId,
        'tag_id' => 1
    ]);
    
    // Update statistics
    $db->table('statistics')
        ->where('key', 'total_posts')
        ->update(['value' => $db->getValue("SELECT COUNT(*) FROM {$db->getTableName('posts')}")]);
    
    $db->commit();
    
    adz_log_info('Post created successfully', ['post_id' => $postId]);
    
} catch (Exception $e) {
    $db->rollback();
    adz_log_error('Failed to create post', ['error' => $e->getMessage()]);
    throw $e;
}
```

### Transaction Callbacks

```php
// Use transaction callback for automatic rollback
$result = $db->transaction(function($db) use ($postData, $tagIds) {
    
    // Insert post
    $postId = $db->table('posts')->insert($postData);
    
    // Insert tags
    foreach ($tagIds as $tagId) {
        $db->table('post_tags')->insert([
            'post_id' => $postId,
            'tag_id' => $tagId
        ]);
    }
    
    // Update counters
    $db->query("UPDATE {$db->getTableName('counters')} SET posts = posts + 1");
    
    return $postId;
});

echo "Created post ID: " . $result;
```

## Schema Management

### Creating Tables

```php
// Define table schema
$schema = [
    'columns' => [
        'id' => [
            'type' => 'bigint',
            'length' => 20,
            'null' => false,
            'auto_increment' => true
        ],
        'title' => [
            'type' => 'varchar',
            'length' => 255,
            'null' => false
        ],
        'content' => [
            'type' => 'longtext',
            'null' => true
        ],
        'status' => [
            'type' => 'varchar',
            'length' => 20,
            'null' => false,
            'default' => 'draft'
        ],
        'author_id' => [
            'type' => 'bigint',
            'length' => 20,
            'null' => false
        ],
        'created_at' => [
            'type' => 'datetime',
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP'
        ],
        'updated_at' => [
            'type' => 'datetime',
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]
    ],
    'indexes' => [
        'PRIMARY' => [
            'type' => 'PRIMARY',
            'columns' => 'id'
        ],
        'status_idx' => [
            'type' => 'KEY',
            'columns' => 'status'
        ],
        'author_idx' => [
            'type' => 'KEY',
            'columns' => 'author_id'
        ],
        'created_idx' => [
            'type' => 'KEY',
            'columns' => 'created_at'
        ]
    ]
];

// Create the table
$db->createTable('posts', $schema);
```

### Table Operations

```php
// Check if table exists
if (!$db->tableExists('posts')) {
    $db->createTable('posts', $schema);
}

// Drop table
$db->dropTable('old_table');

// Get table name with prefix
$tableName = $db->getTableName('posts'); // Returns: wp_adz_posts
```

## Model Integration

### Creating a Model

```php
<?php

namespace MyPlugin\Models;

use AdzHive\Database;

class PostModel
{
    protected $db;
    protected $table = 'posts';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function find($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->first();
    }
    
    public function findBySlug($slug)
    {
        return $this->db->table($this->table)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();
    }
    
    public function all($status = null)
    {
        $query = $this->db->table($this->table);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->orderBy('created_at', 'DESC')->get();
    }
    
    public function create($data)
    {
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        return $this->db->table($this->table)->insert($data);
    }
    
    public function update($id, $data)
    {
        $data['updated_at'] = current_time('mysql');
        
        return $this->db->table($this->table)
            ->where('id', $id)
            ->update($data);
    }
    
    public function delete($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->delete();
    }
    
    public function getByAuthor($authorId, $limit = 10)
    {
        return $this->db->table($this->table)
            ->where('author_id', $authorId)
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    public function getPopular($limit = 10)
    {
        return $this->db->table($this->table)
            ->where('status', 'published')
            ->orderBy('views', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    public function search($query, $limit = 20)
    {
        return $this->db->table($this->table)
            ->where('title', 'LIKE', "%{$query}%")
            ->orWhere('content', 'LIKE', "%{$query}%")
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
}
```

### Using the Model

```php
use MyPlugin\Models\PostModel;

$postModel = new PostModel();

// Find post by ID
$post = $postModel->find(1);

// Get all published posts
$publishedPosts = $postModel->all('published');

// Create new post
$newPostId = $postModel->create([
    'title' => 'My New Post',
    'content' => 'Post content...',
    'status' => 'published',
    'author_id' => get_current_user_id()
]);

// Update post
$postModel->update($newPostId, [
    'title' => 'Updated Title'
]);

// Search posts
$searchResults = $postModel->search('wordpress tutorial');
```

## Database Migrations

### Creating Migrations

```bash
# Generate migration file
./adz.sh make:migration create_posts_table
```

This creates a migration file in `database/migrations/`:

```php
<?php
// 2023_12_01_120000_create_posts_table.php

use AdzHive\Database;

$db = Database::getInstance();

// Create posts table
$db->createTable('posts', [
    'columns' => [
        'id' => [
            'type' => 'bigint',
            'length' => 20,
            'null' => false,
            'auto_increment' => true
        ],
        'title' => [
            'type' => 'varchar',
            'length' => 255,
            'null' => false
        ],
        'slug' => [
            'type' => 'varchar',
            'length' => 255,
            'null' => false
        ],
        'content' => [
            'type' => 'longtext',
            'null' => true
        ],
        'excerpt' => [
            'type' => 'text',
            'null' => true
        ],
        'status' => [
            'type' => 'varchar',
            'length' => 20,
            'null' => false,
            'default' => 'draft'
        ],
        'author_id' => [
            'type' => 'bigint',
            'length' => 20,
            'null' => false
        ],
        'views' => [
            'type' => 'int',
            'length' => 11,
            'null' => false,
            'default' => '0'
        ],
        'created_at' => [
            'type' => 'datetime',
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP'
        ],
        'updated_at' => [
            'type' => 'datetime',
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]
    ],
    'indexes' => [
        'PRIMARY' => [
            'type' => 'PRIMARY',
            'columns' => 'id'
        ],
        'slug_unique' => [
            'type' => 'UNIQUE',
            'columns' => 'slug'
        ],
        'status_idx' => [
            'type' => 'KEY',
            'columns' => 'status'
        ],
        'author_idx' => [
            'type' => 'KEY',
            'columns' => 'author_id'
        ],
        'created_idx' => [
            'type' => 'KEY',
            'columns' => 'created_at'
        ]
    ]
]);

// Insert sample data
$db->table('posts')->insert([
    [
        'title' => 'Welcome to ADZ Framework',
        'slug' => 'welcome-to-adz-framework',
        'content' => 'This is your first post using the ADZ Plugin Framework.',
        'status' => 'published',
        'author_id' => 1
    ],
    [
        'title' => 'Getting Started Guide',
        'slug' => 'getting-started-guide',
        'content' => 'Learn how to use the ADZ Plugin Framework effectively.',
        'status' => 'draft',
        'author_id' => 1
    ]
]);
```

### Running Migrations

```bash
# Run all pending migrations
./adz.sh db:migrate

# Check migration status
./adz.sh db:status
```

## Performance Optimization

### Query Optimization

```php
// Use indexes effectively
$posts = $db->table('posts')
    ->where('status', 'published')  // Uses status_idx
    ->where('author_id', $authorId)  // Uses author_idx
    ->orderBy('created_at', 'DESC')  // Uses created_idx
    ->get();

// Limit results
$posts = $db->table('posts')
    ->where('status', 'published')
    ->limit(10)  // Only get what you need
    ->get();

// Select specific columns
$posts = $db->table('posts')
    ->select(['id', 'title', 'created_at'])  // Don't select large content field
    ->where('status', 'published')
    ->get();
```

### Caching Results

```php
// Cache expensive queries
function getPopularPosts($limit = 10) {
    $cacheKey = 'popular_posts_' . $limit;
    $cached = get_transient($cacheKey);
    
    if ($cached !== false) {
        return $cached;
    }
    
    $db = Database::getInstance();
    $posts = $db->table('posts')
        ->where('status', 'published')
        ->orderBy('views', 'DESC')
        ->limit($limit)
        ->get();
    
    // Cache for 1 hour
    set_transient($cacheKey, $posts, HOUR_IN_SECONDS);
    
    return $posts;
}
```

## Error Handling

### Database Exceptions

```php
use AdzHive\DatabaseException;

try {
    $postId = $db->table('posts')->insert([
        'title' => 'New Post',
        'author_id' => $authorId
    ]);
    
} catch (DatabaseException $e) {
    adz_log_error('Database insert failed', [
        'error' => $e->getMessage(),
        'query' => $e->getQuery(),
        'data' => ['title' => 'New Post', 'author_id' => $authorId]
    ]);
    
    throw new Exception('Failed to create post');
}
```

### Query Debugging

```php
// Enable query logging in development
if (defined('WP_DEBUG') && WP_DEBUG) {
    // Log all queries
    add_action('db_query', function($query) {
        adz_log_debug('Database query', ['query' => $query]);
    });
}

// Check last query
$db->query("UPDATE posts SET views = views + 1 WHERE id = %d", [$postId]);
adz_log_debug('Last query executed', ['query' => $db->lastQuery()]);
```

## Best Practices

### 1. Use Prepared Statements

```php
// Good - Parameters are escaped
$posts = $db->get(
    "SELECT * FROM {$db->getTableName('posts')} WHERE author_id = %d",
    [$authorId]
);

// Bad - Vulnerable to SQL injection
$posts = $db->get(
    "SELECT * FROM {$db->getTableName('posts')} WHERE author_id = $authorId"
);
```

### 2. Use Transactions for Related Operations

```php
// Good - All operations succeed or fail together
$db->transaction(function($db) use ($postData, $tags) {
    $postId = $db->table('posts')->insert($postData);
    
    foreach ($tags as $tag) {
        $db->table('post_tags')->insert([
            'post_id' => $postId,
            'tag_id' => $tag['id']
        ]);
    }
    
    return $postId;
});
```

### 3. Optimize Queries

```php
// Good - Specific columns and conditions
$posts = $db->table('posts')
    ->select(['id', 'title', 'created_at'])
    ->where('status', 'published')
    ->where('created_at', '>', $since)
    ->limit(10)
    ->get();

// Bad - Select everything unnecessarily
$posts = $db->table('posts')->get();
```

### 4. Handle Errors Gracefully

```php
try {
    $result = $db->table('posts')->where('id', $id)->first();
    
    if (!$result) {
        throw new NotFoundException('Post not found');
    }
    
    return $result;
    
} catch (DatabaseException $e) {
    adz_log_error('Database error', ['error' => $e->getMessage()]);
    return null;
}
```

The database system provides a powerful, secure, and intuitive way to work with data while maintaining full WordPress compatibility and following best practices for performance and security.