<?php 

/** 
 * @author Adrian T. Saycon <adzbite@gmail.com>
 * @version 1.0.0
 * @package ADZWP Framework
 * @copyright 2021 www.adriansaycon.com
 */

\ADZ::$assets_path = \ADZ::$path . '/src/assets/';
\ADZ::$js_path = \ADZ::$path . '/src/assets/js/';
\ADZ::$css_path = \ADZ::$path . '/src/assets/css/';

register_activation_hook( \ADZ::$conf->slug, function() 
{
  \ADZ::$plugin::install();
  \flush_rewrite_rules();
});

register_deactivation_hook( \ADZ::$conf->slug , function() 
{
  \ADZ::$plugin::uninstall();
  \flush_rewrite_rules();
});