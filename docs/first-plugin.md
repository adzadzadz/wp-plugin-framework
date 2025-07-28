# Your First Plugin with ADZ Framework

This tutorial walks you through creating your first plugin using the ADZ Plugin Framework. We'll build a simple "User Notes" plugin that allows users to save private notes in their WordPress dashboard.

## What We'll Build

A plugin that:
- Adds a "My Notes" menu in the admin dashboard
- Allows users to create, edit, and delete personal notes
- Includes proper security, validation, and error handling
- Demonstrates all major framework features

## Prerequisites

- WordPress installation (local or staging)
- PHP 7.4 or higher
- Composer installed
- Basic PHP and WordPress knowledge

## Step 1: Create the Plugin

### Install the Framework

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/your-repo/wp-plugin-framework.git user-notes
cd user-notes
composer install
./adz.sh init
```

### Update Plugin Information

Edit the main plugin file (`user-notes.php`):

```php
<?php
/**
 * Plugin Name: User Notes
 * Plugin URI: https://example.com/user-notes
 * Description: Allows users to save private notes in their dashboard
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: user-notes
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('USER_NOTES_VERSION', '1.0.0');
define('USER_NOTES_FILE', __FILE__);
define('USER_NOTES_PATH', plugin_dir_path(__FILE__));
define('USER_NOTES_URL', plugin_dir_url(__FILE__));

// Load the framework
require_once USER_NOTES_PATH . 'vendor/autoload.php';

// Initialize plugin
new UserNotes\Plugin();
```

## Step 2: Configure the Plugin

### Generate Configuration Files

```bash
./adz.sh make:config
```

### Update Configuration

Edit `config/app.php`:

```php
<?php

return [
    'plugin' => [
        'name' => 'User Notes',
        'version' => '1.0.0',
        'text_domain' => 'user-notes',
        'slug' => 'user-notes',
        'namespace' => 'UserNotes'
    ],
    
    'admin' => [
        'menu_title' => 'My Notes',
        'menu_slug' => 'user-notes',
        'capability' => 'read',  // All logged-in users
        'icon' => 'dashicons-edit-large',
        'position' => 25
    ],
    
    'features' => [
        'rich_editor' => true,
        'categories' => false,
        'sharing' => false
    ]
];
```

## Step 3: Create the Database

### Generate Migration

```bash
./adz.sh make:migration create_user_notes_table
```

### Edit Migration File

Edit `database/migrations/YYYY_MM_DD_HHMMSS_create_user_notes_table.php`:

```php
<?php

use AdzHive\Database;

$db = Database::getInstance();

// Create user_notes table
$db->createTable('user_notes', [
    'columns' => [
        'id' => [
            'type' => 'bigint',
            'length' => 20,
            'null' => false,
            'auto_increment' => true
        ],
        'user_id' => [
            'type' => 'bigint',
            'length' => 20,
            'null' => false
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
        'color' => [
            'type' => 'varchar',
            'length' => 7,
            'null' => false,
            'default' => '#fff3cd'
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
        'user_id_idx' => [
            'type' => 'KEY',
            'columns' => 'user_id'
        ],
        'created_at_idx' => [
            'type' => 'KEY',
            'columns' => 'created_at'
        ]
    ]
]);
```

### Run Migration

```bash
./adz.sh db:migrate
```

## Step 4: Create the Model

### Generate Model

```bash
./adz.sh make:model NoteModel
```

### Edit Model

Edit `src/models/NoteModel.php`:

```php
<?php

namespace UserNotes\Models;

use AdzHive\Database;

class NoteModel
{
    protected $db;
    protected $table = 'user_notes';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public function findByUser($userId, $limit = 50)
    {
        return $this->db->table($this->table)
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    public function find($id, $userId = null)
    {
        $query = $this->db->table($this->table)
            ->where('id', $id);
            
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->first();
    }
    
    public function create($data)
    {
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');
        
        return $this->db->table($this->table)->insert($data);
    }
    
    public function update($id, $data, $userId = null)
    {
        $data['updated_at'] = current_time('mysql');
        
        $query = $this->db->table($this->table)
            ->where('id', $id);
            
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->update($data);
    }
    
    public function delete($id, $userId = null)
    {
        $query = $this->db->table($this->table)
            ->where('id', $id);
            
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->delete();
    }
    
    public function countByUser($userId)
    {
        return $this->db->table($this->table)
            ->where('user_id', $userId)
            ->count();
    }
}
```

## Step 5: Create the Controller

### Generate Controller

```bash
./adz.sh make:controller NotesController
```

### Edit Controller

Edit `src/controllers/NotesController.php`:

```php
<?php

namespace UserNotes\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Config;
use AdzHive\Validator;
use AdzHive\ValidationException;
use UserNotes\Models\NoteModel;

class NotesController extends Controller 
{
    protected $security;
    protected $config;
    protected $noteModel;
    
    public $actions = [
        'admin_menu' => 'addAdminMenu',
        'admin_enqueue_scripts' => 'enqueueAdminAssets',
        'admin_post_save_note' => 'saveNote',
        'admin_post_delete_note' => 'deleteNote',
        'wp_ajax_get_note' => 'getNote'
    ];
    
    protected function bootstrap()
    {
        $this->security = Security::getInstance();
        $this->config = Config::getInstance();
        $this->noteModel = new NoteModel();
    }
    
    public function addAdminMenu()
    {
        add_menu_page(
            $this->config->get('plugin.name'),
            $this->config->get('admin.menu_title'),
            $this->config->get('admin.capability'),
            $this->config->get('admin.menu_slug'),
            [$this, 'renderNotesPage'],
            $this->config->get('admin.icon'),
            $this->config->get('admin.position')
        );
    }
    
    public function renderNotesPage()
    {
        try {
            $this->security->checkCapability($this->config->get('admin.capability'));
            
            $userId = get_current_user_id();
            $notes = $this->noteModel->findByUser($userId);
            $totalNotes = $this->noteModel->countByUser($userId);
            
            $data = [
                'notes' => $notes,
                'total_notes' => $totalNotes,
                'nonce' => $this->security->createNonce('notes_action'),
                'ajax_nonce' => wp_create_nonce('notes_ajax')
            ];
            
            include USER_NOTES_PATH . 'src/views/admin/notes-page.php';
            
        } catch (Exception $e) {
            wp_die('Error loading notes: ' . $e->getMessage());
        }
    }
    
    public function saveNote()
    {
        try {
            $this->security->checkCapability($this->config->get('admin.capability'));
            $this->security->verifyRequest('_notes_nonce', 'notes_action');
            
            // Rate limiting: 20 saves per hour
            $this->security->checkRateLimit('save_note', 20, 3600);
            
            $noteId = intval($_POST['note_id'] ?? 0);
            $isEdit = !empty($noteId);
            
            // Validate input
            $validator = Validator::make($_POST, [
                'title' => 'required|string|min:1|max:255',
                'content' => 'string|max:10000',
                'color' => 'regex:/^#[0-9A-Fa-f]{6}$/'
            ], [
                'title.required' => 'Please enter a title for your note.',
                'title.max' => 'Title must be less than 255 characters.',
                'content.max' => 'Content must be less than 10,000 characters.',
                'color.regex' => 'Please select a valid color.'
            ]);
            
            if ($validator->fails()) {
                throw new ValidationException('Please check your input', $validator->errors());
            }
            
            // Sanitize data
            $data = $this->security->sanitizeArray($_POST, [
                'title' => 'text',
                'content' => 'html',
                'color' => 'text'
            ]);
            
            $userId = get_current_user_id();
            
            if ($isEdit) {
                // Verify user owns the note
                $existingNote = $this->noteModel->find($noteId, $userId);
                if (!$existingNote) {
                    throw new Exception('Note not found or access denied.');
                }
                
                $this->noteModel->update($noteId, $data, $userId);
                $message = 'Note updated successfully!';
                
                adz_log_info('Note updated', [
                    'note_id' => $noteId,
                    'user_id' => $userId,
                    'title' => $data['title']
                ]);
                
            } else {
                $data['user_id'] = $userId;
                $noteId = $this->noteModel->create($data);
                $message = 'Note created successfully!';
                
                adz_log_info('Note created', [
                    'note_id' => $noteId,
                    'user_id' => $userId,
                    'title' => $data['title']
                ]);
            }
            
            wp_redirect(add_query_arg([
                'message' => urlencode($message)
            ], admin_url('admin.php?page=' . $this->config->get('admin.menu_slug'))));
            exit;
            
        } catch (ValidationException $e) {
            $errors = [];
            foreach ($e->getErrors() as $field => $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            
            wp_redirect(add_query_arg([
                'error' => urlencode(implode(' ', $errors))
            ], wp_get_referer()));
            exit;
            
        } catch (Exception $e) {
            adz_log_error('Note save failed', [
                'error' => $e->getMessage(),
                'user_id' => get_current_user_id()
            ]);
            
            wp_redirect(add_query_arg([
                'error' => urlencode('An error occurred. Please try again.')
            ], wp_get_referer()));
            exit;
        }
    }
    
    public function deleteNote()
    {
        try {
            $this->security->checkCapability($this->config->get('admin.capability'));
            $this->security->verifyRequest('_delete_nonce', 'delete_note');
            
            $noteId = intval($_POST['note_id'] ?? 0);
            $userId = get_current_user_id();
            
            if (!$noteId) {
                throw new Exception('Invalid note ID.');
            }
            
            // Verify user owns the note
            $note = $this->noteModel->find($noteId, $userId);
            if (!$note) {
                throw new Exception('Note not found or access denied.');
            }
            
            $this->noteModel->delete($noteId, $userId);
            
            adz_log_info('Note deleted', [
                'note_id' => $noteId,
                'user_id' => $userId,
                'title' => $note->title
            ]);
            
            wp_redirect(add_query_arg([
                'message' => urlencode('Note deleted successfully!')
            ], admin_url('admin.php?page=' . $this->config->get('admin.menu_slug'))));
            exit;
            
        } catch (Exception $e) {
            wp_redirect(add_query_arg([
                'error' => urlencode($e->getMessage())
            ], wp_get_referer()));
            exit;
        }
    }
    
    public function getNote()
    {
        try {
            $this->security->verifyAjaxRequest('notes_ajax');
            $this->security->checkCapability($this->config->get('admin.capability'));
            
            $noteId = intval($_GET['note_id'] ?? 0);
            $userId = get_current_user_id();
            
            $note = $this->noteModel->find($noteId, $userId);
            
            if (!$note) {
                wp_send_json_error('Note not found');
            }
            
            wp_send_json_success([
                'id' => $note->id,
                'title' => $note->title,
                'content' => $note->content,
                'color' => $note->color
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function enqueueAdminAssets($hook)
    {
        if (strpos($hook, $this->config->get('admin.menu_slug')) !== false) {
            wp_enqueue_style(
                'user-notes-admin',
                USER_NOTES_URL . 'src/assets/css/admin.css',
                [],
                USER_NOTES_VERSION
            );
            
            wp_enqueue_script(
                'user-notes-admin',
                USER_NOTES_URL . 'src/assets/js/admin.js',
                ['jquery'],
                USER_NOTES_VERSION,
                true
            );
            
            wp_localize_script('user-notes-admin', 'userNotes', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('notes_ajax'),
                'delete_confirm' => 'Are you sure you want to delete this note?'
            ]);
        }
    }
}
```

## Step 6: Create the Views

### Main Admin Page

Create `src/views/admin/notes-page.php`:

```php
<div class="wrap">
    <h1><?php echo esc_html($this->config->get('plugin.name')); ?></h1>
    
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['message'])); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html(urldecode($_GET['error'])); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="user-notes-container">
        <div class="notes-header">
            <div class="notes-stats">
                <span class="notes-count"><?php echo esc_html($total_notes); ?> notes</span>
            </div>
            <button type="button" class="button button-primary" id="add-note-btn">
                Add New Note
            </button>
        </div>
        
        <div class="notes-grid">
            <?php if (empty($notes)): ?>
                <div class="no-notes">
                    <p>You haven't created any notes yet. Click "Add New Note" to get started!</p>
                </div>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="note-card" style="background-color: <?php echo esc_attr($note->color); ?>">
                        <div class="note-header">
                            <h3 class="note-title"><?php echo esc_html($note->title); ?></h3>
                            <div class="note-actions">
                                <button type="button" class="button-link edit-note" data-id="<?php echo esc_attr($note->id); ?>">
                                    Edit
                                </button>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline;">
                                    <?php wp_nonce_field('delete_note', '_delete_nonce'); ?>
                                    <input type="hidden" name="action" value="delete_note">
                                    <input type="hidden" name="note_id" value="<?php echo esc_attr($note->id); ?>">
                                    <button type="submit" class="button-link delete-note" onclick="return confirm('Are you sure?')">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="note-content">
                            <?php echo wp_kses_post($note->content); ?>
                        </div>
                        <div class="note-meta">
                            Updated: <?php echo esc_html(date('M j, Y g:i A', strtotime($note->updated_at))); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Note Modal -->
    <div id="note-modal" class="note-modal" style="display: none;">
        <div class="note-modal-content">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="note-form">
                <?php wp_nonce_field('notes_action', '_notes_nonce'); ?>
                <input type="hidden" name="action" value="save_note">
                <input type="hidden" name="note_id" id="note_id" value="">
                
                <div class="note-form-header">
                    <h2 id="modal-title">Add New Note</h2>
                    <button type="button" class="close-modal">&times;</button>
                </div>
                
                <div class="note-form-body">
                    <div class="form-group">
                        <label for="note_title">Title *</label>
                        <input type="text" id="note_title" name="title" required maxlength="255">
                    </div>
                    
                    <div class="form-group">
                        <label for="note_content">Content</label>
                        <textarea id="note_content" name="content" rows="8"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="note_color">Color</label>
                        <div class="color-picker">
                            <input type="color" id="note_color" name="color" value="#fff3cd">
                            <div class="color-presets">
                                <button type="button" class="color-preset" data-color="#fff3cd" style="background: #fff3cd"></button>
                                <button type="button" class="color-preset" data-color="#d4edda" style="background: #d4edda"></button>
                                <button type="button" class="color-preset" data-color="#cce5ff" style="background: #cce5ff"></button>
                                <button type="button" class="color-preset" data-color="#f8d7da" style="background: #f8d7da"></button>
                                <button type="button" class="color-preset" data-color="#e2e3e5" style="background: #e2e3e5"></button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="note-form-footer">
                    <button type="button" class="button cancel-btn">Cancel</button>
                    <button type="submit" class="button button-primary">Save Note</button>
                </div>
            </form>
        </div>
    </div>
</div>
```

## Step 7: Add Styles and JavaScript

### Create CSS

Create `src/assets/css/admin.css`:

```css
.user-notes-container {
    max-width: 1200px;
    margin: 20px 0;
}

.notes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ddd;
}

.notes-stats {
    color: #666;
}

.notes-count {
    font-weight: 600;
}

.notes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.note-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background: #fff3cd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.2s;
}

.note-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.note-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.note-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
    flex: 1;
}

.note-actions {
    display: flex;
    gap: 10px;
    margin-left: 15px;
}

.note-actions .button-link {
    color: #666;
    text-decoration: none;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.note-actions .button-link:hover {
    color: #333;
    background-color: rgba(0,0,0,0.1);
}

.note-content {
    color: #555;
    line-height: 1.6;
    margin-bottom: 15px;
    max-height: 200px;
    overflow-y: auto;
}

.note-meta {
    font-size: 12px;
    color: #888;
    border-top: 1px solid rgba(0,0,0,0.1);
    padding-top: 10px;
}

.no-notes {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

/* Modal Styles */
.note-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.note-modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90%;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.note-form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 20px 0;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}

.note-form-header h2 {
    margin: 0;
    padding-bottom: 15px;
}

.close-modal {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-modal:hover {
    color: #333;
}

.note-form-body {
    padding: 0 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #0073aa;
    outline: none;
    box-shadow: 0 0 0 1px #0073aa;
}

.color-picker {
    display: flex;
    align-items: center;
    gap: 15px;
}

.color-presets {
    display: flex;
    gap: 8px;
}

.color-preset {
    width: 30px;
    height: 30px;
    border: 2px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    transition: border-color 0.2s;
}

.color-preset:hover,
.color-preset.active {
    border-color: #0073aa;
}

.note-form-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 20px;
    border-top: 1px solid #ddd;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .notes-grid {
        grid-template-columns: 1fr;
    }
    
    .notes-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
}
```

### Create JavaScript

Create `src/assets/js/admin.js`:

```javascript
jQuery(document).ready(function($) {
    const modal = $('#note-modal');
    const form = $('#note-form');
    const modalTitle = $('#modal-title');
    
    // Open modal for new note
    $('#add-note-btn').on('click', function() {
        resetForm();
        modalTitle.text('Add New Note');
        modal.show();
    });
    
    // Edit note
    $('.edit-note').on('click', function() {
        const noteId = $(this).data('id');
        loadNoteForEdit(noteId);
    });
    
    // Close modal
    $('.close-modal, .cancel-btn').on('click', function() {
        modal.hide();
    });
    
    // Close modal on backdrop click
    modal.on('click', function(e) {
        if (e.target === this) {
            modal.hide();
        }
    });
    
    // Color preset selection
    $('.color-preset').on('click', function() {
        const color = $(this).data('color');
        $('#note_color').val(color);
        $('.color-preset').removeClass('active');
        $(this).addClass('active');
    });
    
    // Form submission
    form.on('submit', function(e) {
        const title = $('#note_title').val().trim();
        if (!title) {
            e.preventDefault();
            alert('Please enter a title for your note.');
            $('#note_title').focus();
        }
    });
    
    function resetForm() {
        form[0].reset();
        $('#note_id').val('');
        $('#note_color').val('#fff3cd');
        $('.color-preset').removeClass('active');
        $('.color-preset[data-color="#fff3cd"]').addClass('active');
    }
    
    function loadNoteForEdit(noteId) {
        $.ajax({
            url: userNotes.ajax_url,
            type: 'GET',
            data: {
                action: 'get_note',
                note_id: noteId,
                nonce: userNotes.nonce
            },
            success: function(response) {
                if (response.success) {
                    const note = response.data;
                    $('#note_id').val(note.id);
                    $('#note_title').val(note.title);
                    $('#note_content').val(note.content);
                    $('#note_color').val(note.color);
                    
                    $('.color-preset').removeClass('active');
                    $(`.color-preset[data-color="${note.color}"]`).addClass('active');
                    
                    modalTitle.text('Edit Note');
                    modal.show();
                } else {
                    alert('Error loading note: ' + response.data);
                }
            },
            error: function() {
                alert('Error loading note. Please try again.');
            }
        });
    }
});
```

## Step 8: Create the Main Plugin Class

Create `src/Plugin.php`:

```php
<?php

namespace UserNotes;

use AdzHive\Config;
use UserNotes\Controllers\NotesController;

class Plugin
{
    protected $config;
    protected $controllers = [];
    
    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->loadControllers();
        $this->init();
    }
    
    protected function loadControllers()
    {
        $this->controllers = [
            'notes' => new NotesController(),
        ];
    }
    
    protected function init()
    {
        // Initialize controllers
        foreach ($this->controllers as $controller) {
            if (method_exists($controller, 'init')) {
                $controller->init();
            }
        }
        
        // Plugin lifecycle hooks
        register_activation_hook(USER_NOTES_FILE, [$this, 'activate']);
        register_deactivation_hook(USER_NOTES_FILE, [$this, 'deactivate']);
        
        // Load text domain
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
    }
    
    public function activate()
    {
        // Run migrations
        $this->runMigrations();
        
        // Set default options
        add_option('user_notes_version', USER_NOTES_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate()
    {
        // Clear caches
        wp_cache_flush();
    }
    
    public function loadTextDomain()
    {
        load_plugin_textdomain(
            $this->config->get('plugin.text_domain'),
            false,
            dirname(plugin_basename(USER_NOTES_FILE)) . '/languages'
        );
    }
    
    protected function runMigrations()
    {
        $migrationsPath = USER_NOTES_PATH . 'database/migrations/';
        
        if (is_dir($migrationsPath)) {
            $files = glob($migrationsPath . '*.php');
            sort($files);
            
            foreach ($files as $file) {
                include_once $file;
            }
        }
    }
}
```

## Step 9: Test Your Plugin

### Activate the Plugin

1. Go to WordPress Admin → Plugins
2. Find "User Notes" and click "Activate"
3. You should see "My Notes" in the admin menu

### Test Features

1. **Create a note**: Click "Add New Note", fill in the form, save
2. **Edit a note**: Click "Edit" on an existing note, modify, save  
3. **Delete a note**: Click "Delete" and confirm
4. **Test security**: Try accessing with different users
5. **Test validation**: Try submitting empty forms

### Check Logs

```bash
# View recent logs
tail -f wp-content/uploads/adz-logs/adz-plugin.log

# Run health check
cd /path/to/your-plugin
./adz.sh health:check
```

## What You've Learned

Congratulations! You've built a complete WordPress plugin using the ADZ Framework. You've implemented:

✅ **Database operations** with migrations and models  
✅ **Security features** with CSRF protection and validation  
✅ **Modern architecture** with controllers and views  
✅ **Error handling** with logging and exceptions  
✅ **AJAX functionality** with proper nonce verification  
✅ **Configuration management** with organized settings  
✅ **WordPress integration** with hooks and standards  

## Next Steps

1. **Add features**: Categories for notes, sharing, search
2. **Improve UX**: Rich text editor, drag-and-drop, themes
3. **Add tests**: Unit tests for models and controllers
4. **Optimize**: Caching, database indexing, performance
5. **Documentation**: User guide, developer docs

## Common Issues

**Plugin not activating?**
- Check PHP version (7.4+)
- Run `composer install`
- Check error logs

**Database errors?**
- Ensure proper permissions
- Run `./adz.sh db:migrate`
- Check table prefix configuration

**Assets not loading?**
- Verify file paths in controller
- Check file permissions
- Clear any caching

You now have a solid foundation for building sophisticated WordPress plugins with the ADZ Framework!