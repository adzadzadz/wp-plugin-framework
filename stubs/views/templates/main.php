<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    
    <div class="site-wrapper">
        <header class="site-header">
            <?php if (isset($header_content)) echo $header_content; ?>
        </header>
        
        <main class="site-main">
            <?php echo $content; ?>
        </main>
        
        <footer class="site-footer">
            <?php if (isset($footer_content)) echo $footer_content; ?>
        </footer>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>