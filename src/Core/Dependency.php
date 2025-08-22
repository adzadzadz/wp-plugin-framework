<?php 

namespace AdzWP\Core;


class Dependency extends Core {

  public static function addAdminNotice( $depName )
  {
    $name = \Adz::get('app.name', 'ADZ Framework Plugin');
    add_action( 'admin_notices', function() use( $name, $depName )
    {
      echo '<div class="notice notice-warning"><p>';
      echo sprintf( 
        __( '<strong>%s</strong> requires <strong>%s</strong> to be installed and activated.', 'adz-framework' ),
        esc_html( $name ),
        esc_html( $depName )
      );
      echo '</p></div>';
    });
  }

  public static function monitor_status()
  {
    $dependencies = \Adz::get('dependencies', []);
    foreach ( $dependencies as $id => $dep ) {
      $slug = is_array($dep) ? $dep['slug'] : $dep->slug;
      $name = is_array($dep) ? $dep['name'] : $dep->textDomain;
      
      $is_active = Self::is_active( $slug );
      if ( $is_active < Self::STATUS_ACTIVE ) {
        Self::addAdminNotice( $name );
      }
    }
  }

  public static function is_active( $slug ) 
  {
    if ( ! function_exists( 'is_plugin_active' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    if ( !is_plugin_active( $slug ) ) {
      return Self::STATUS_INACTIVE;
    } else {
      return Self::STATUS_ACTIVE;
    }
  }

  public static function install_required() 
  {
    $dependencies = \Adz::get('dependencies', []);
    
    if ( empty( $dependencies ) ) {
      return;
    }
    
    return Self::auto_install_dependencies( $dependencies );
  }

  public static function is_ready( String $className )
  {
    if ( !class_exists( $className ) ) {
      return false;
    }
    return true;
  }
    
  public static function is_installed( $slug ) 
  {
    if ( !function_exists( 'get_plugins' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $all_plugins = get_plugins();

    if ( !empty( $all_plugins[ $slug ] ) ) {
      return Self::STATUS_INSTALLED;
    } else {
      return Self::STATUS_UNINSTALLED;
    }
  }

  public static function install_plugin( $zip_url ) 
  {
    if ( ! current_user_can( 'install_plugins' ) ) {
      return false;
    }
    
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    include_once ABSPATH . 'wp-admin/includes/file.php';
    include_once ABSPATH . 'wp-admin/includes/misc.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
    
    wp_cache_flush();
    
    $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
    $installed = $upgrader->install( $zip_url );
  
    return !is_wp_error( $installed ) && $installed;
  }
  
  public static function upgrade_plugin( $slug ) {
    if ( ! current_user_can( 'update_plugins' ) ) {
      return false;
    }
    
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    wp_cache_flush();
    
    $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
    $upgraded = $upgrader->upgrade( $slug );
  
    return !is_wp_error( $upgraded ) && $upgraded;
  }

  /**
   * Install plugin from WordPress.org repository
   * 
   * @param string $slug Plugin slug (e.g., 'woocommerce')
   * @return bool Success status
   */
  public static function install_from_repo( $slug ) {
    if ( ! current_user_can( 'install_plugins' ) ) {
      return false;
    }

    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    include_once ABSPATH . 'wp-admin/includes/file.php';
    include_once ABSPATH . 'wp-admin/includes/misc.php';

    $api = plugins_api( 'plugin_information', [
      'slug' => $slug,
      'fields' => [
        'sections' => false,
        'screenshots' => false,
        'tags' => false,
        'contributors' => false,
        'requires' => false,
        'tested' => false,
        'homepage' => false,
        'added' => false,
        'last_updated' => false,
        'compatibility' => false,
        'download_link' => true,
      ]
    ]);

    if ( is_wp_error( $api ) ) {
      return false;
    }

    $upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
    $installed = $upgrader->install( $api->download_link );

    return !is_wp_error( $installed ) && $installed;
  }

  /**
   * Automatically install and activate required dependencies
   * 
   * @param array $dependencies Array of dependencies with format:
   *   [
   *     'slug' => 'plugin-folder/plugin-file.php',
   *     'name' => 'Plugin Name',
   *     'source' => 'repo' or 'url',
   *     'url' => 'download_url' (if source is 'url')
   *   ]
   * @return array Results of installation attempts
   */
  public static function auto_install_dependencies( $dependencies = [] ) {
    $results = [];
    
    foreach ( $dependencies as $dep ) {
      $slug = $dep['slug'] ?? '';
      $name = $dep['name'] ?? '';
      $source = $dep['source'] ?? 'repo';
      $url = $dep['url'] ?? '';
      
      if ( empty( $slug ) ) {
        $results[ $name ] = [
          'status' => 'error',
          'message' => 'Invalid dependency configuration'
        ];
        continue;
      }
      
      // Check if already installed and active
      if ( is_plugin_active( $slug ) ) {
        $results[ $name ] = [
          'status' => 'already_active',
          'message' => 'Plugin already installed and active'
        ];
        continue;
      }
      
      $installed = false;
      
      // Install based on source
      if ( $source === 'url' && !empty( $url ) ) {
        $installed = self::install_plugin( $url );
      } else {
        // Extract plugin slug from folder/file format
        $plugin_slug = explode( '/', $slug )[0];
        $installed = self::install_from_repo( $plugin_slug );
      }
      
      if ( $installed ) {
        // Try to activate
        $activated = activate_plugin( $slug );
        if ( is_wp_error( $activated ) ) {
          $results[ $name ] = [
            'status' => 'installed_not_activated',
            'message' => 'Plugin installed but could not be activated: ' . $activated->get_error_message()
          ];
        } else {
          $results[ $name ] = [
            'status' => 'success',
            'message' => 'Plugin installed and activated successfully'
          ];
        }
      } else {
        $results[ $name ] = [
          'status' => 'install_failed',
          'message' => 'Failed to install plugin'
        ];
      }
    }
    
    return $results;
  }

}