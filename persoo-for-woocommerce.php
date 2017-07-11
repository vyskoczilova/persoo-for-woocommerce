<?php
/*
Plugin Name: Persoo for WooCommerce
Description: Official plugin. Enable personalized product recommendations, cross-sell and alternatives, super-fast personalized search and autocomplete to increase converions and revenue.
Version:     1.0.1
Author:      Persoo
Author URI:  http://persoo.co
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'PERSOO_DIR', plugin_dir_path( __FILE__ ) );
define( 'PERSOO_URL', plugin_dir_url( __FILE__ ) );
define( 'PERSOO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'PERSOO_VERSION', '0.0.1' );

global $persoo_token_allowed, $persoo_token_lenght, $persoo_ignored_taxonomies, $persoo_product_feed, $persoo_category_feed;
$persoo_token_allowed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOP1234567890";
$persoo_token_lenght = 30;
$persoo_woo_ignored = array( 'product_type', 'product_cat', 'product_tag', 'product_shipping_class', 'product_visibility' );
$persoo_user_ignored = array_map('trim', explode(',', get_option( 'wc_persoo_settings_ignored_taxonomies', '' )));
$persoo_ignored_taxonomies = ( !empty($persoo_user_ignored) ? array_merge( $persoo_woo_ignored, $persoo_user_ignored ) : $persoo_woo_ignored );

// Localize plugin
add_action( 'init', 'persoo_localize_plugin' );
function persoo_localize_plugin() {
    load_plugin_textdomain( 'persoo-for-woocommerce', false, PERSOO_DIR . 'languages/' );
}

// Is WooCommerce active?
add_action( 'plugins_loaded', 'persoo_plugin_init' );
function persoo_plugin_init() {
	
	// If WooCommerce is NOT active
	if ( current_user_can( 'activate_plugins' ) && !class_exists( 'woocommerce' ) ) {
		
		add_action( 'admin_init', 'persoo_deactivate' );
		add_action( 'admin_notices', 'persoo_admin_notice' );	

    // IF WooCommer IS ACTIVE
    } else {

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', 'persoo_admin_scripts' );
			add_filter( 'woocommerce_get_settings_pages', 'persoo_woocommerce_get_settings_pages' );		
			add_filter( 'plugin_action_links_' . PERSOO_PLUGIN_BASENAME, 'persoo_plugin_action_links' );
		} else {
			add_action( 'wp_enqueue_scripts', 'persoo_scripts' );
			if ( is_persoo() ) {
				require_once( PERSOO_DIR . 'includes/class-woo-update.php' );
				require_once( PERSOO_DIR . 'includes/class-persoo-js-snippet.php' );
				add_filter( 'body_class','persoo_add_body_class' ); 			
			}
		}						

	}
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'persoo_on_deaktivation' );
function persoo_on_deaktivation() {
	global $persoo_product_feed, $persoo_category_feed;
	if ( file_exists( WP_CONTENT_DIR . '/'.$persoo_product_feed ) ) {
      unlink( WP_CONTENT_DIR . '/'.$persoo_product_feed );
    }
	if ( file_exists( WP_CONTENT_DIR . '/'.$persoo_category_feed ) ) {
      unlink( WP_CONTENT_DIR . '/'.$persoo_category_feed );
    }
	delete_option( 'wc_persoo_settings_active' );
	delete_option( 'persoo_product_xml.lock' );
	delete_option( 'persoo_category_xml.lock' );
	delete_option( 'persoo_product_xml_progress');
	delete_option( 'persoo_category_feed_generated' );
	delete_option( 'persoo_product_feed_generated' );
}

// Deactivate the Child Plugin
function persoo_deactivate() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}

// On plugin activation
register_activation_hook( __FILE__, 'persoo_plugin_active' );
function persoo_plugin_active() {
	
	// Generate Security Token
	$token = get_option( 'wc_persoo_settings_security_token', false );
	if ( ! $token ) {
		update_option( 'wc_persoo_settings_security_token', persoo_get_random_token() );
	}

	// Generate Feed
	if ( is_persoo() ) {
		persoo_product_xml_feed_update();
		persoo_category_xml_feed_update();	
	}

}

add_action( 'init', 'persoo_xml_feed' );
function persoo_xml_feed() {  
  
  if ( is_persoo() ) {    
	require_once( PERSOO_DIR . 'includes/class-persoo-xml-helpers.php' );
	require_once( PERSOO_DIR . 'includes/class-persoo-product-feed.php' );
	require_once( PERSOO_DIR . 'includes/class-persoo-category-feed.php' );
	
	// Dynamic feed
    add_feed( 'persoo'.get_option( 'wc_persoo_settings_security_token', '' ), 'persoo_view_feed' );
	add_feed( 'persoo_cat'.get_option( 'wc_persoo_settings_security_token', '' ), 'persoo_view_category_feed' );
	
	// Static feed	
	if ( ! wp_next_scheduled( 'persoo_product_xml' ) ) {
		wp_schedule_event( current_time( 'timestamp', 1 ) + MINUTE_IN_SECONDS, 'daily', 'persoo_product_xml' );		
	}             
	if ( ! wp_next_scheduled( 'persoo_category_xml' ) ) {
		wp_schedule_event( current_time( 'timestamp', 1 ) + MINUTE_IN_SECONDS, 'daily', 'persoo_category_xml' );		
	}             

  } else {
	// Remove Static feed from scheduling  
  	if ( wp_next_scheduled( 'persoo_product_xml' ) ) {
        $timestamp = wp_next_scheduled( 'persoo_product_xml' );
        wp_unschedule_event( $timestamp, 'persoo_product_xml' ); 
    }
	if ( wp_next_scheduled( 'persoo_category_xml' ) ) {
        $timestamp = wp_next_scheduled( 'persoo_category_xml' );
        wp_unschedule_event( $timestamp, 'persoo_category_xml' ); 
    }
  }

}

// Add settings link
function persoo_plugin_action_links( $links ) {
	$action_links = array(
		'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=persoo' ) . '" aria-label="' . esc_attr__( 'View Persoo settings', 'persoo-for-woocommerce' ) . '">' . esc_html__( 'Settings', 'persoo-for-woocommerce' ) . '</a>',
	);

	return array_merge( $action_links, $links );
}

// Update static feed
add_action( 'persoo_product_xml', 'persoo_product_xml_feed_update' );
add_action( 'persoo_product_xml_batch', 'persoo_product_xml_feed_update' );
function persoo_product_xml_feed_update() {
	require_once( PERSOO_DIR . 'includes/class-persoo-xml-helpers.php' );
	require_once( PERSOO_DIR . 'includes/class-persoo-product-feed.php' );
	persoo_product_xml_update();
}
add_action( 'persoo_category_xml', 'persoo_category_xml_feed_update' );
add_action( 'persoo_category_xml_batch', 'persoo_category_xml_feed_update' );
function persoo_category_xml_feed_update() {
	require_once( PERSOO_DIR . 'includes/class-persoo-category-feed.php' );
	persoo_category_xml_update();
}

// Set the correct HTTP header for Content-type.
add_filter( 'feed_content_type', 'persoo_rss_content_type', 10, 2 );
function persoo_rss_content_type( $content_type, $type ) {
	if ( substr($type, 0, strlen('persoo')) === 'persoo' ) {
		return feed_content_type( 'rss2' );
	}
	return $content_type;
}

// Throw an Alert to tell the Admin why it didn't activate
function persoo_admin_notice() {
	$persoo_plugin = __( 'Persoo', 'persoo-for-woocommerce' );
	$woocommerce_plugin = __( 'WooCommerce', 'persoo-for-woocommerce' );
			
			echo '<div class="error"><p>'
				. sprintf( __( '%1$s requires %2$s. Please activate %2$s before activation of %1$s. This plugin has been deactivated.', 'persoo-for-woocommerce' ), '<strong>' . esc_html( $persoo_plugin ) . '</strong>', '<strong>' . esc_html( $woocommerce_plugin ) . '</strong>' )
				. '</p></div>';
		
	if ( isset( $_GET['activate'] ) )
		unset( $_GET['activate'] );
}

// Insert settings tab
function persoo_woocommerce_get_settings_pages( $settings ) {
	$settings[] = include( 'includes/admin/wc-settings-tab.php' );  
	return $settings;
}

// Persoo is active
function is_persoo() {
	if ( get_option( 'wc_persoo_settings_security_token', false ) && get_option( 'wc_persoo_settings_active', 'no' ) == 'yes' ) {
		return true;
	} else {
		return false;
	}
}

// Load JS persoo
function persoo_scripts( $hook ) {       
	wp_enqueue_script( 'persoo-datalayer', PERSOO_URL . 'assets/js/persoo-datalayer.js', array('jquery'));    
}

// Load JS for admin
function persoo_admin_scripts( $hook ) {
    global $persoo_token_allowed, $persoo_token_lenght;
    if ( 'woocommerce_page_wc-settings' === $hook ) {
        wp_register_script( 'persoo-admin', PERSOO_URL . 'assets/js/admin-script.js', array('jquery'));
        wp_enqueue_script( 'persoo-admin');
            $params = array(
                'i18n_regenerate' => __('Do you really want to proceed? You will need to change the Feed URL in the Persoo Application. Sometimes you will need to reasign WordPress\' Permalinks', 'persoo-for-woocommerce'),
                'token_allowed' => $persoo_token_allowed,			
				'token_lenght' => $persoo_token_lenght,
            );
        wp_localize_script( 'persoo-admin', 'persoo-for-woocommerce', $params );
    }
}

// Generate Random Token
function persoo_get_random_token() {    
    
	global $persoo_token_allowed, $persoo_token_lenght;
	$random_string = "";
    $num_valid_chars = strlen($persoo_token_allowed);

    for ($i = 0; $i < $persoo_token_lenght; $i++) {
        $random_pick = mt_rand(1, $num_valid_chars);
        $random_char = $persoo_token_allowed[$random_pick-1];
        $random_string .= $random_char;
    }
    return $random_string;
}

// Other taxonomies
function persoo_other_taxonomies() {
            
	global $persoo_ignored_taxonomies;

	$taxonomies = get_object_taxonomies( 'product', 'objects' );
	$other_taxonomies = array();
	foreach ( $taxonomies as $t) {
		
		// if not in ignorred taxomy
		if ( !in_array( $t->name, $persoo_ignored_taxonomies ) ) {            
			
			array_push( $other_taxonomies, $t->name );

		}
	}

	return $other_taxonomies;

}

// Map other taxonomies based on user settings
function persoo_map_taxonomies() {
	$other_taxonomies = persoo_other_taxonomies();
	$taxonomies_map = array();	
	if ( !empty ( $other_taxonomies ) ) {
		foreach ( $other_taxonomies as $ot ) {

			$map_ot = get_option( 'wc_persoo_settings_ot_'.$ot, false );
			$taxonomies_map[$ot] = ( $map_ot ? $map_ot : $ot );

		}
	}
	return $taxonomies_map;
}

// Construct category hierarchy, accepts type of term fields
function persoo_get_category_hierarchy( $term_id, $type = 'term_id', $visited = array() ) {
	$chain = '';
	$separator = ':';
	$parent = get_term( $term_id, 'category' );

	if ( is_wp_error( $parent ) )
		return $parent;

	if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) ) {
		$visited[] = $parent->parent;
		$chain .= persoo_get_category_hierarchy( $parent->parent, $type, $visited ).$separator;
	}

	$chain .= $parent->$type;
	return $chain;
}

// Persoo query arguments
if ( ! function_exists( 'persoo_xml_args' ) ) {
	function persoo_xml_args( $limit = false, $offset = false) {
		$args = array(
			'post_type' => 'product',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
				'key' => '_visibility',
				'value' => 'hidden',
				'compare' => '!=',
				)
			),	
			'fields' => 'ids',
		);
		if ( $limit ) {
			$args['posts_per_page'] = $limit;
		} else {
			$args['nopaging'] = true;
		}
		if ( $offset ) {
			$args['offset'] = $offset;
		}
		return $args;
	}
}

// Add custom pagetype body class
function persoo_add_body_class( $classes ) {

	$persoo_class = 'persoo_';
	// Homepage
	if ( is_front_page() ) {
		$persoo_class .= 'homepage';                          
	}
	// product detail page
	elseif ( is_product() ) {
		$persoo_class .=  'detail';   
	}
	// product list (category) page
	elseif ( is_product_category() || is_product_tag() ) {
		$persoo_class .= 'category';                         
	}
	// on the page of basket and through the checkout process
	elseif ( is_cart() || is_checkout() ) {
		$persoo_class .= 'basket';
	}
	// 404 page not found
	elseif ( is_404() ) {
		$persoo_class .= 'error';
	}
	// search
	elseif ( is_search() ) {
		$persoo_class .= 'search';
	}
	// on other pages
	else {
		$persoo_class .= 'other';
	}

	$classes[] = $persoo_class;
	return $classes;
}