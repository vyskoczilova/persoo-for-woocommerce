<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'persooProductFeed' ) ) {
    class persooProductFeed {

        private $args;

        /**
        * Constructor
        */
        function __construct() {

            $this->args = persoo_xml_args();
            $this->constructFeed();

        }

        private function constructFeed() {

            $products = get_posts( $this->args );
            $xmlHelper = new persooXmlHelpers();
            $woo = new wooUpdate();
            $taxonomies = persoo_other_taxonomies();
            $map_taxonomies = persoo_map_taxonomies();
            
            $xmlWriter = new XMLWriter();
            $xmlWriter->openMemory();
            $xmlWriter->setIndent( true );
            $xmlWriter->startDocument( '1.0', 'utf-8' );
            $xmlWriter->startElement( 'rss' );
                $xmlWriter->writeAttribute( 'version', '2.0' );
                $xmlWriter->writeAttribute( 'xmlns:g', 'http://base.google.com/ns/1.0' );
                $xmlWriter->startElement( 'channel' );
                $xmlWriter->writeElement( 'title', get_bloginfo() );
                $xmlWriter->writeElement( 'link', get_bloginfo( 'url' ) );
                $xmlWriter->writeElement( 'description', get_bloginfo( 'description' ) );

                    foreach ( $products as $product_id ) {

                        require( PERSOO_DIR . 'includes/feed/product_id.php' );                 

                        if ( $product->is_type( 'variable' ) ) {

                            require( PERSOO_DIR . 'includes/feed/master_id.php' ); 

                            foreach( $product->get_available_variations() as $available_variation ) {
                            
                                $variation = new WC_Product_Variation( $available_variation['variation_id'] );
                                if ( $variation->variation_is_visible() ) {

                                    require( PERSOO_DIR . 'includes/feed/product_variation.php' ); 
                                    
                                }
                            }
                            
                        } elseif ( $product->is_type( 'simple' ) ) {

                            require( PERSOO_DIR . 'includes/feed/product_simple.php' ); 

                        }
                }
                $xmlWriter->endElement();
            $xmlWriter->endElement();

            $xmlWriter->endDocument();
            header( 'Content-type: text/xml' );
            echo $xmlWriter->outputMemory();

        }

    }


}
function persoo_view_feed() {
    new persooProductFeed();
}

function persoo_product_xml_update() {
  global $wpdb;
  $persoo_product_feed = 'persoo_'.get_option( 'wc_persoo_settings_security_token', '' ).'.xml';
  $lock_name = 'persoo_product_xml.lock';
  $lock_result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES (%s, %s, 'no') /* LOCK */", $lock_name, time() ) );
  if ( ! $lock_result ) {
    $lock_result = get_option( $lock_name );
    if ( ! $lock_result || ( $lock_result > ( time() - HOUR_IN_SECONDS ) ) ) {
      wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'persoo_product_xml_batch' );
      return;
    }
  }
  update_option( $lock_name, time() );

  $limit = 1000; // Number of products processed in one call
  $offset = 0;
  $progress = get_option( 'persoo_product_xml_progress' );
  if ( ! empty ( $progress ) ) {
    $offset = $progress;
  }

  $xmlWriter = new XMLWriter();
  $xmlWriter->openMemory();
  $xmlWriter->setIndent( true );

  $args = persoo_xml_args( $limit, $offset );

  $products = get_posts( $args );
  $xmlHelper = new persooXmlHelpers();
  $woo = new wooUpdate();
  $taxonomies = persoo_other_taxonomies();
  $map_taxonomies = persoo_map_taxonomies();

  $xmlWriter->startDocument( '1.0', 'utf-8' );
  $xmlWriter->startElement( 'SHOP' );

  if ( ! $products ) {
    if ( wp_next_scheduled( 'persoo_product_xml' ) ) {
      $timestamp = wp_next_scheduled( 'persoo_product_xml' );
      wp_unschedule_event( $timestamp, 'persoo_product_xml' );
    }
    $xmlWriter->endElement();
    $xmlWriter->endDocument();

    $output = $xmlWriter->outputMemory();
    $output = substr( $output, strpos( $output, "\n" ) + 1 );
    $output = str_replace( '<SHOP/>', '</SHOP>', $output );
    file_put_contents( WP_CONTENT_DIR . '/'.$persoo_product_feed.'.tmp', $output, FILE_APPEND );

    if ( file_exists( WP_CONTENT_DIR . '/'.$persoo_product_feed ) ) {
      unlink( WP_CONTENT_DIR . '/'.$persoo_product_feed );
    }
    if ( file_exists( WP_CONTENT_DIR .  '/'.$persoo_product_feed.'.tmp' ) ) {
      rename( WP_CONTENT_DIR .  '/'.$persoo_product_feed.'.tmp' , WP_CONTENT_DIR . '/'.$persoo_product_feed );
    }

    delete_option( 'persoo_product_xml_progress' );
    delete_option( $lock_name );

    $mytheme_timezone = get_option('timezone_string');
    date_default_timezone_set($mytheme_timezone);
    $dformat = get_option('time_format') .' '. get_option('date_format');
    update_option( 'persoo_product_feed_generated', date( $dformat, time()) );

    return;
  }
  wp_schedule_single_event( current_time( 'timestamp', 1 ) + ( 3 * MINUTE_IN_SECONDS ), 'persoo_product_xml_batch' );

  $number_of_products = 0;
  $current_number_of_products = 0;

  foreach ( $products as $product_id ) {
    
    if ( $current_number_of_products > $limit ) {
      break;
    }

    require( PERSOO_DIR . 'includes/feed/product_id.php' ); 

    if ( $product->is_type( 'variable' ) ) {

      require( PERSOO_DIR . 'includes/feed/master_id.php' ); 

      foreach( $product->get_available_variations() as $available_variation ) {
        $variation = new WC_Product_Variation( $available_variation['variation_id'] );
        if ( $variation->is_in_stock() && $variation->variation_is_visible() ) {

          require( PERSOO_DIR . 'includes/feed/product_variation.php' ); 
          
        }
        $current_number_of_products++;
      }
    } elseif ( $product->is_type( 'simple' ) ) {
      if ( $product->is_in_stock() ) {
        
        require( PERSOO_DIR . 'includes/feed/product_simple.php' );

      }
      $current_number_of_products++;
    }
    $number_of_products++;
  }
  
  $output = $xmlWriter->outputMemory();
  if ( ! empty ( $progress ) ) {
    $output = substr( $output, strpos( $output, "\n" ) + 1 );
    $output = substr( $output, strpos( $output, "\n" ) + 1 );
  }
  else {
    header( 'Content-type: text/xml' );
  }

  file_put_contents( WP_CONTENT_DIR . '/'.$persoo_product_feed.'.tmp', $output, FILE_APPEND );
  $xmlWriter->flush( true );
  
  $offset = $offset + $number_of_products;
  
  update_option( 'persoo_product_xml_progress', utf8_encode(html_entity_decode($offset )));
  delete_option( $lock_name );
}