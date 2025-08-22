<?php 

namespace AdzWP;

class PolymerHelper extends Core {

  public function usePolymer()
  {
    add_action( 'wp_enqueue_scripts', 'sp_theme_enqueue_styles' );
    function sp_theme_enqueue_styles() {
      wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
      wp_enqueue_style( 'polymer', get_stylesheet_directory_uri() . '/bower_components/webcomponentsjs/webcomponents-lite.min.js' );
    }

    add_action( 'wp_head', [$this, 'include_polymer_elements'] );
  }

  public function euqueueStyle()
  {

  }

  public function usePolymerElements()
  {
    

  }

}