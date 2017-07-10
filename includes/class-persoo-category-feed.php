<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'persooCategoryFeed' ) ) {
    class persooCategoryFeed {

        private $args;

        /**
        * Constructor
        */
        function __construct() {

            $this->constructFeed();

        }

        private function constructFeed() {

            $categories = get_terms( 'product_cat', array(
                'hide_empty' => false,
            ) );
            
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

                    foreach ( $categories as $category ) {

                        require( PERSOO_DIR . 'includes/feed/category.php' );                 
                       
                }
                $xmlWriter->endElement();
            $xmlWriter->endElement();

            $xmlWriter->endDocument();
            header( 'Content-type: text/xml' );
            echo $xmlWriter->outputMemory();

        }

    }


}
function persoo_view_category_feed() {
    new persooCategoryFeed();
}


function persoo_category_xml_update() {
  global $wpdb;
  
  $persoo_category_feed = 'persoo_cat_'.get_option( 'wc_persoo_settings_security_token', '' ).'.xml';
  $lock_name = 'persoo_category_xml.lock';
  
  $lock_result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES (%s, %s, 'no') ", $lock_name, time() ) );
  if ( ! $lock_result ) {
    $lock_result = get_option( $lock_name );
    if ( ! $lock_result || ( $lock_result > ( time() - HOUR_IN_SECONDS ) ) ) {
      wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'persoo_category_xml_batch' );
      return;
    }
  }
  update_option( $lock_name, time() );

  $xmlWriter = new XMLWriter();
  $xmlWriter->openMemory();
  $xmlWriter->setIndent( true );

   $categories = get_terms( 'product_cat', array(
      'hide_empty' => false,
   ) );

  $xmlWriter->startDocument( '1.0', 'utf-8' );
  $xmlWriter->startElement( 'CATEGORIES' );  

  foreach ( $categories as $category ) {
    require( PERSOO_DIR . 'includes/feed/category.php' );   
  }
  
  $xmlWriter->endElement();
  $xmlWriter->endDocument();

  $output = $xmlWriter->outputMemory();
  header( 'Content-type: text/xml' );
  
  file_put_contents( WP_CONTENT_DIR . '/'.$persoo_category_feed.'.tmp', $output, FILE_APPEND );
  $xmlWriter->flush( true );

  if ( file_exists( WP_CONTENT_DIR . '/'.$persoo_category_feed ) ) {
    unlink( WP_CONTENT_DIR . '/'.$persoo_category_feed );
  }
  if ( file_exists( WP_CONTENT_DIR .  '/'.$persoo_category_feed.'.tmp' ) ) {
    rename( WP_CONTENT_DIR .  '/'.$persoo_category_feed.'.tmp' , WP_CONTENT_DIR . '/'.$persoo_category_feed );
  }

  delete_option( $lock_name );

  $mytheme_timezone = get_option('timezone_string');
  date_default_timezone_set($mytheme_timezone);
  $dformat = get_option('time_format') .' '. get_option('date_format');
  update_option( 'persoo_category_feed_generated', date( $dformat, time()) );

}