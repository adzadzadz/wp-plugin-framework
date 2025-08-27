<?php
/**
 * Layout Template: main
 * 
 * This layout wraps view content in a structured section.
 * Layouts are lightweight wrappers that don't include full HTML structure.
 * 
 * Available variables:
 * - $content: The rendered view content
 * - $title: Page/section title (if set)
 * - All variables passed from the controller
 * 
 * Usage: View::render('viewname', $data, true, 'layouts/main')
 * Disable: View::render('viewname', $data, true, false)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get plugin configuration for context
$config = \AdzWP\Core\Config::getInstance();
$plugin_name = $config->get('plugin.name', 'ADZ Plugin');
$plugin_slug = $config->get('plugin.slug', 'adz-plugin');
?>

<section id="<?php echo esc_attr($plugin_slug . '-main'); ?>" class="adz-template adz-template--main">
    <?php if (isset($title)): ?>
        <header class="template-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="template-title h4 mb-0"><?php echo esc_html($title); ?></h2>
                
                <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <?php foreach ($breadcrumbs as $crumb): ?>
                                <?php if (isset($crumb['url'])): ?>
                                    <li class="breadcrumb-item">
                                        <a href="<?php echo esc_url($crumb['url']); ?>">
                                            <?php echo esc_html($crumb['label']); ?>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        <?php echo esc_html($crumb['label']); ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php endif; ?>
            </div>
            
            <?php if (isset($subtitle)): ?>
                <p class="template-subtitle text-muted mt-2 mb-0"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>
        </header>
    <?php endif; ?>
    
    <?php if (isset($alerts) && is_array($alerts)): ?>
        <div class="template-alerts mb-3">
            <?php foreach ($alerts as $alert): ?>
                <div class="alert alert-<?php echo esc_attr($alert['type'] ?? 'info'); ?> <?php echo isset($alert['dismissible']) && $alert['dismissible'] ? 'alert-dismissible fade show' : ''; ?>">
                    <?php if (isset($alert['icon'])): ?>
                        <i class="<?php echo esc_attr($alert['icon']); ?> me-2"></i>
                    <?php endif; ?>
                    
                    <?php echo wp_kses_post($alert['message']); ?>
                    
                    <?php if (isset($alert['dismissible']) && $alert['dismissible']): ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <main class="template-content">
        <?php if (isset($content)): ?>
            <?php echo $content; ?>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No content provided to template.
            </div>
        <?php endif; ?>
    </main>
    
    <?php if (isset($sidebar_content)): ?>
        <aside class="template-sidebar mt-4">
            <div class="card">
                <div class="card-body">
                    <?php echo wp_kses_post($sidebar_content); ?>
                </div>
            </div>
        </aside>
    <?php endif; ?>
    
    <?php if (isset($actions) && is_array($actions)): ?>
        <footer class="template-actions mt-4 pt-3 border-top">
            <div class="d-flex gap-2 flex-wrap">
                <?php foreach ($actions as $action): ?>
                    <a href="<?php echo esc_url($action['url']); ?>" 
                       class="btn <?php echo esc_attr($action['class'] ?? 'btn-secondary'); ?>"
                       <?php if (isset($action['target'])): ?>target="<?php echo esc_attr($action['target']); ?>"<?php endif; ?>
                       <?php if (isset($action['data']) && is_array($action['data'])): ?>
                           <?php foreach ($action['data'] as $key => $value): ?>
                               data-<?php echo esc_attr($key); ?>="<?php echo esc_attr($value); ?>"
                           <?php endforeach; ?>
                       <?php endif; ?>>
                        <?php if (isset($action['icon'])): ?>
                            <i class="<?php echo esc_attr($action['icon']); ?> me-1"></i>
                        <?php endif; ?>
                        <?php echo esc_html($action['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </footer>
    <?php endif; ?>
</section>

<?php
/**
 * Action hook for adding custom content after the layout
 * 
 * @param string $layout_name Layout name
 * @param array $layout_data All data passed to layout
 */
do_action('adz_after_layout_main', 'main', get_defined_vars());
?>