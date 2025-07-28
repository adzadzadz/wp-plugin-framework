<?php

namespace YourPlugin\Controllers;

use AdzWP\Controller;
use AdzHive\Security;
use AdzHive\Validator;
use AdzHive\ValidationException;
use AdzHive\Config;

/**
 * Example CRUD Controller demonstrating best practices
 * 
 * This controller shows how to:
 * - Handle form submissions securely
 * - Validate user input
 * - Use the configuration system
 * - Implement proper error handling
 * - Work with WordPress hooks automatically
 */
class PostController extends Controller 
{
    protected $security;
    protected $config;
    
    public $actions = [
        'admin_menu' => 'addAdminMenu',
        'admin_post_create_post' => 'handleCreatePost',
        'admin_post_update_post' => 'handleUpdatePost',
        'admin_post_delete_post' => 'handleDeletePost',
        'wp_ajax_get_posts' => 'handleAjaxGetPosts'
    ];
    
    public $filters = [
        'the_content' => [
            'callback' => 'addCustomContent',
            'priority' => 10,
            'accepted_args' => 1
        ]
    ];
    
    protected function bootstrap()
    {
        $this->security = Security::getInstance();
        $this->config = Config::getInstance();
    }
    
    public function addAdminMenu()
    {
        add_menu_page(
            $this->config->get('plugin.name', 'My Plugin'),
            'My Posts',
            $this->config->get('admin.capability', 'manage_options'),
            'my-posts',
            [$this, 'renderPostsPage'],
            $this->config->get('admin.icon', 'dashicons-admin-post'),
            $this->config->get('admin.position', 25)
        );
        
        add_submenu_page(
            'my-posts',
            'Add New Post',
            'Add New',
            $this->config->get('admin.capability', 'manage_options'),
            'my-posts-add',
            [$this, 'renderAddPostPage']
        );
    }
    
    public function renderPostsPage()
    {
        try {
            $this->security->checkCapability();
            
            global $wpdb;
            $table = $wpdb->prefix . $this->config->get('database.prefix', 'adz_') . 'posts';
            
            $posts = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
            
            include plugin_dir_path(__FILE__) . '../views/admin/posts-list.php';
            
        } catch (\Exception $e) {
            adz_handle_exception($e);
        }
    }
    
    public function renderAddPostPage()
    {
        try {
            $this->security->checkCapability();
            
            $nonce = $this->security->getNonceField('create_post_action', '_create_post_nonce');
            
            include plugin_dir_path(__FILE__) . '../views/admin/posts-add.php';
            
        } catch (\Exception $e) {
            adz_handle_exception($e);
        }
    }
    
    public function handleCreatePost()
    {
        try {
            // Security checks
            $this->security->checkCapability();
            $this->security->verifyRequest('_create_post_nonce', 'create_post_action');
            
            // Rate limiting
            $this->security->checkRateLimit('create_post', 10, 3600);
            
            // Validate input
            $validator = Validator::make($_POST, [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'status' => 'required|in:draft,published',
                'category_id' => 'numeric|exists:categories,id'
            ]);
            
            if ($validator->fails()) {
                throw new ValidationException('Invalid post data', $validator->errors());
            }
            
            // Sanitize data
            $data = $this->security->sanitizeArray($_POST, [
                'title' => 'text',
                'content' => 'html',
                'status' => 'text',
                'category_id' => 'int'
            ]);
            
            // Save to database
            global $wpdb;
            $table = $wpdb->prefix . $this->config->get('database.prefix', 'adz_') . 'posts';
            
            $result = $wpdb->insert(
                $table,
                array_merge($data, [
                    'author_id' => get_current_user_id(),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ]),
                ['%s', '%s', '%s', '%d', '%d', '%s', '%s']
            );
            
            if ($result === false) {
                throw new DatabaseException('Failed to create post', $wpdb->last_query);
            }
            
            // Log the action
            adz_log_info('Post created successfully', [
                'post_id' => $wpdb->insert_id,
                'user_id' => get_current_user_id(),
                'title' => $data['title']
            ]);
            
            // Redirect with success message
            wp_redirect(admin_url('admin.php?page=my-posts&message=created'));
            exit;
            
        } catch (\Exception $e) {
            adz_handle_exception($e);
        }
    }
    
    public function handleUpdatePost()
    {
        try {
            $this->security->checkCapability();
            $this->security->verifyRequest('_update_post_nonce', 'update_post_action');
            
            $postId = intval($_POST['post_id'] ?? 0);
            
            if (!$postId) {
                throw new ValidationException('Invalid post ID');
            }
            
            // Validate input
            $validator = Validator::make($_POST, [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'status' => 'required|in:draft,published'
            ]);
            
            if ($validator->fails()) {
                throw new ValidationException('Invalid post data', $validator->errors());
            }
            
            // Sanitize data
            $data = $this->security->sanitizeArray($_POST, [
                'title' => 'text',
                'content' => 'html',
                'status' => 'text'
            ]);
            
            // Update database
            global $wpdb;
            $table = $wpdb->prefix . $this->config->get('database.prefix', 'adz_') . 'posts';
            
            $result = $wpdb->update(
                $table,
                array_merge($data, ['updated_at' => current_time('mysql')]),
                ['id' => $postId],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );
            
            if ($result === false) {
                throw new DatabaseException('Failed to update post', $wpdb->last_query);
            }
            
            adz_log_info('Post updated successfully', [
                'post_id' => $postId,
                'user_id' => get_current_user_id()
            ]);
            
            wp_redirect(admin_url('admin.php?page=my-posts&message=updated'));
            exit;
            
        } catch (\Exception $e) {
            adz_handle_exception($e);
        }
    }
    
    public function handleDeletePost()
    {
        try {
            $this->security->checkCapability();
            $this->security->verifyRequest('_delete_post_nonce', 'delete_post_action');
            
            $postId = intval($_POST['post_id'] ?? 0);
            
            if (!$postId) {
                throw new ValidationException('Invalid post ID');
            }
            
            global $wpdb;
            $table = $wpdb->prefix . $this->config->get('database.prefix', 'adz_') . 'posts';
            
            $result = $wpdb->delete($table, ['id' => $postId], ['%d']);
            
            if ($result === false) {
                throw new DatabaseException('Failed to delete post', $wpdb->last_query);
            }
            
            adz_log_info('Post deleted successfully', [
                'post_id' => $postId,
                'user_id' => get_current_user_id()
            ]);
            
            wp_redirect(admin_url('admin.php?page=my-posts&message=deleted'));
            exit;
            
        } catch (\Exception $e) {
            adz_handle_exception($e);
        }
    }
    
    public function handleAjaxGetPosts()
    {
        try {
            $this->security->verifyAjaxRequest();
            $this->security->checkCapability();
            
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = min(50, max(1, intval($_GET['per_page'] ?? 10)));
            $offset = ($page - 1) * $perPage;
            
            global $wpdb;
            $table = $wpdb->prefix . $this->config->get('database.prefix', 'adz_') . 'posts';
            
            $posts = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $perPage,
                $offset
            ));
            
            $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            
            wp_send_json_success([
                'posts' => $posts,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => intval($total),
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error($e->toArray());
        }
    }
    
    public function addCustomContent($content)
    {
        if (is_single() && in_the_loop() && is_main_query()) {
            $customContent = '<div class="my-plugin-content">';
            $customContent .= '<p>This content was added by My Plugin!</p>';
            $customContent .= '</div>';
            
            return $content . $customContent;
        }
        
        return $content;
    }
}