<?php
/**
 * Example View Template
 * 
 * Available variables:
 * - All variables passed from the controller
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="adz-view">
    <h1><?php echo esc_html($title ?? 'Example View'); ?></h1>
    
    <div class="content">
        <?php if (isset($content)): ?>
            <?php echo wp_kses_post($content); ?>
        <?php else: ?>
            <p>This is an example view template. You can customize this content.</p>
        <?php endif; ?>
    </div>
</div>