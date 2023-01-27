<?php
/*
Plugin Name: YOAST SEO: Primary term for Post & Product permalink and breadcrumb
Plugin URI: https://murdeni.com
Description: YOAST Primary as post & product permalink and NAVXT breadcrumb 
Version: 1.0
Author: Feri murdeni
Author URI: https://whello.id
Text Domain: murdeni-yoast-primary-permalink
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

require'updater/plugin-update-checker.php';

if ( ! class_exists( 'MurdeniYoastPrimaryTermPermalink' ) ) :

  class MurdeniYoastPrimaryTermPermalink {
  	public $version = '1.0';
    public $parent  = 'wordpress-seo/wp-seo.php';

  	public function __construct() {        
          define('MurdeniYoastPrimaryTermPermalink_VERSION', $this->version );  
          $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker('https://github.com/fmurdeni/murdeni-yoast-primary-permalink', __FILE__, 'murdeni-yoast-primary-permalink');
          $myUpdateChecker->setBranch('master');
          
          // HOOKS
          add_filter('post_type_link', array($this, 'primary_permalink_structure'), 1, 2);  // make sure the priority is not large than 10
          add_action( 'admin_init', array($this, 'child_plugin_has_parent_plugin') );

          add_action('bcn_after_fill', array($this, 'set_taxonomy_breadcrumb_trail'));
    }


    public function child_plugin_has_parent_plugin() {
        if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( $this->parent ) ) {
            add_action( 'admin_notices', array($this, 'whello_install_breadcrumb') );            

            deactivate_plugins( plugin_basename( __FILE__ ) ); 

            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }
        }
    }

     
    public function whello_install_breadcrumb() {
      ?>
      <div class="error">
          <h3><?php _e( 'Plugin required.', 'pku' ); ?></h3>
          <p> <?php _e( 'Sorry, but this plugin requires another plugins to be installed and active.', 'pku' ); ?> </p>  
          <a href="<?php echo admin_url( 'plugin-install.php?tab=plugin-information&plugin=wp-seo' ); ?>" target="_blank"><?php _e('Install YOAST SEO', 'murdeni-yoast-primary-permalink'); ?></a> 
          
          
      </div>
      <?php
    }


    /**
     * Change Permastructure of post & product according to 
     * YOAST Primary term
     * @taxonomy product_cat & category
     * Param:   $post_link     string of permalink  
     *          $post          Post Object
     * @since Ver. 1.0
     * */
    public function primary_permalink_structure($post_link, $post) {
        $term = $this->get_primary_term($post);
        
        if ( 'product' == $post->post_type && false !== strpos($post_link, '%product_cat%') ) {      
            if ($term) {
                $post_link = str_replace('%product_cat%', $term->slug, $post_link);
            }  
            
        } else if ('post' == $post->post_type && false !== strpos($post_link, '%category%')) {
            if ($term) {
                $post_link = str_replace('%category%', $term->slug, $post_link);
            }

        }

        return $post_link;
    }

    /**
     * Set NAVXT Breadcrumb according to single URL
     * https://github.com/mtekk/Breadcrumb-NavXT/blob/master/class.bcn_breadcrumb.php
     * */	
    public function set_taxonomy_breadcrumb_trail($trail){

        // Check whether plugin navxt is active
        if ( !is_plugin_active( 'breadcrumb-navxt/breadcrumb-navxt.php' ) ) {
            return;
        }

        // Extract first
        $current = $trail->breadcrumbs[0];
        $base = $trail->breadcrumbs[count($trail->breadcrumbs)-2];
        $home = end($trail->breadcrumbs);
        $trail->breadcrumbs = [];

        if ( is_singular() && !is_page() ) {
            global $post;          
            $permalink = get_permalink( $post->ID );

            $permas = array_filter(explode(get_bloginfo('url'), $permalink)); 
            $permas = array_filter(explode('/', $permas[1])); 
          
            array_pop($permas);
            array_shift($permas);
          
            foreach ($permas as $key => $perma) {
                if ( is_singular('product') ) {   
                   $term = get_term_by('slug', $perma, 'product_cat');
                } else {
                   $term = get_term_by('slug', $perma, 'category');
                }

                if ($term) {
                    if (class_exists('bcn_breadcrumb')) {
                        // new bcn_breadcrumb($title = '', $template = '', array $type = array(), $url = '', $id = null, $linked = false)
                        $trail->add(new bcn_breadcrumb($term->name, null, array(), 'https://github.com', null, true));
                    }
                        
                }
            
            }

            array_push($trail->breadcrumbs, $base, $home );        
            array_unshift($trail->breadcrumbs, $current);
         
        } else {

          array_push($trail->breadcrumbs, $base, $home );        
          if (get_queried_object()->parent != 0) array_unshift($trail->breadcrumbs, $current);

        }

    }

    /**
     * Return term object
     * */
    private function get_primary_term($post, $taxonomy = null){        

        if ($post->post_type === 'post') {
            $taxonomy = $taxonomy ?: 'category';
        } else if ($post->post_type === 'product') { 
            $taxonomy = $taxonomy ?: 'product_cat';
        }


        $primary_category = get_post_meta( $post->ID, '_yoast_wpseo_primary_'.$taxonomy, true );
        if (!empty($primary_category)){
            return get_term($primary_category);            
        } 
    }



  }

endif;

new MurdeniYoastPrimaryTermPermalink;