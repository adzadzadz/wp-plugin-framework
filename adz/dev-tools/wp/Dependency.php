<?php 

namespace AdzWP;

use ADZ;
use AdzWP\Plugin;

class Dependency extends Core {

  public static function addAdminNotice( $depName )
  {
    $name = ADZ::$conf->name;
    add_action( 'admin_notices', function() use( $name, $depName )
    {
      echo \AdzWP\View::render("dependency/admin_notices", [
        "dependency" => $depName,
        "name" => $name
      ]);
    });
  }

  public static function monitor_status()
  {
    foreach ( ADZ::$conf->dependencies as $id => $dep ) {
      $is_active = Self::is_active( $id, $dep->slug );
      if ( $is_active < Self::STATUS_ACTIVE ) {
        Self::addAdminNotice( $dep->textDomain );
      } else {

      }
    }
  }

  public static function is_active( $id, $slug ) 
  {
    if ( ! function_exists( 'is_plugin_active' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    if ( !is_plugin_active( $slug ) ) {
      return ADZ::$conf->dependencies[$id]->active = Self::STATUS_INACTIVE;
    } else {
      return ADZ::$conf->dependencies[$id]->active = Self::STATUS_ACTIVE;
    }
  }

  public static function install_required() 
  {
    foreach ( ADZ::$conf->dependencies as $id => $info ) {
      
      if ( !Self::is_ready( $info->mainClassName ) ) {
        if ( Self::is_installed( $id ) >= Self::STATUS_INSTALLED ) {
          ADZ::$conf->dependencies[$id]->installed = Self::STATUS_INSTALLED;
        } else {
          if ( Self::install_plugin( $info->zip ) ) {
            ADZ::$conf->dependencies[$id]->installed = Self::STATUS_INSTALLED;
          } else {
            ADZ::$conf->dependencies[$id]->installed = Self::STATUS_INSTALL_FAILED;
          }
        }
        activate_plugin( $info->slug );
      } 
      ADZ::$conf->dependencies[$id]->acvite = Self::STATUS_ACTIVE;
      
    }
  }

  public static function is_ready( String $className )
  {
    if ( !class_exists( $className ) ) {
      return false;
    }
    return true;
  }
    
  public static function is_installed( $id ) 
  {
    if ( !function_exists( 'get_plugins' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $all_plugins = get_plugins();

    if ( !empty( $all_plugins[ ADZ::$conf->dependencies[$id]->slug ] ) ) {
      ADZ::$conf->dependencies[$id]->installed = Self::STATUS_INSTALLED;
    } else {
      ADZ::$conf->dependencies[$id]->installed = Self::STATUS_UNINSTALLED;
    }

    return ADZ::$conf->dependencies[$id]->installed;
  }

  public static function install_plugin( $zip_url ) 
  {
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    wp_cache_flush();
    
    $upgrader = new \Plugin_Upgrader();
    $installed = $upgrader->install( $zip_url );
  
    return $installed;
  }
  
  public static function upgrade_plugin( $slug ) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    wp_cache_flush();
    
    $upgrader = new \Plugin_Upgrader();
    $upgraded = $upgrader->upgrade( $slug );
  
    return $upgraded;
  }


}