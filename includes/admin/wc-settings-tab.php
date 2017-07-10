<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_Settings_Persoo extends WC_Settings_Page {

    /**
     * Constructor
     */
    public function __construct() {

        $this->id    = 'persoo';

        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
        add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

    }

    /**
     * Add plugin options tab
     *
     * @since     1.0.0
     *
     * @return array
     */
    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs[$this->id] = __( 'Persoo', 'persoo-for-woocommerce' );
        return $settings_tabs;
    }

    /**
     * Get sections
     *
     * @since     1.0.0
     *
     * @return array
     */
    public function get_sections() {

        $sections = array(
            ''                  => __( 'General', 'persoo-for-woocommerce' ),
            'advanced'          => __( 'Avanced', 'persoo-for-woocommerce' ),

        );

        return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
    }


    /**
     * Get sections
     *
     * @since     1.0.0
     *
     * @return array
     */
    public function get_settings( $current_section = '' ) {        

        switch( $current_section ){

            case '' :                
                
                if ( is_persoo() ) {

                    // feed url
                    $persoo_product_feed = 'persoo_'.get_option( 'wc_persoo_settings_security_token', '' ).'.xml';
	                $persoo_category_feed = 'persoo_cat_'.get_option( 'wc_persoo_settings_security_token', '' ).'.xml';
                    $feed = 'persoo'.get_option( 'wc_persoo_settings_security_token', '' );
                    $feed2 = 'persoo_cat'.get_option( 'wc_persoo_settings_security_token', '' );
                    $url = get_bloginfo( 'url' ).'/?feed='.$feed;
                    $url2 = get_bloginfo( 'url' ).'/?feed='.$feed2;

                    // description
                    $persoo_integration_description = __( 'Product Feed:', 'persoo-for-woocommerce' ) . ' <a href="'.$url.'" target="_blank">'. __( 'dynamic', 'persoo-for-woocommerce' ) .'</a> | <a href="'.WP_CONTENT_URL.'/'.$persoo_product_feed.'" target="_blank">' . __( 'static XML', 'persoo-for-woocommerce' ) . '</a> (' . __('last generated:', 'persoo-for-woocommerce') . ' '.get_option( 'persoo_product_feed_generated', __('never', 'persoo-for-woocommerce')).', '. __('updates daily', 'persoo-for-woocommerce') . ')';
                    $persoo_integration_description .= '<br />'. __( 'Category Feed:', 'persoo-for-woocommerce' ) . ' <a href="'.$url2.'" target="_blank">'. __( 'dynamic', 'persoo-for-woocommerce' ) .'</a> | <a href="'.WP_CONTENT_URL.'/'.$persoo_category_feed.'" target="_blank">' . __( 'static XML', 'persoo-for-woocommerce' ) . '</a> (' . __('last generated:', 'persoo-for-woocommerce') . ' '.get_option( 'persoo_category_feed_generated', __('never', 'persoo-for-woocommerce')).', '. __('updates daily', 'persoo-for-woocommerce') . ')';

                } else {
                    $persoo_integration_description = __('To obtain your Persoo Product Feed URL you need to activate the Persoo integration first.', 'persoo-for-woocommerce');
                }

                $settings = array(
                    'section_title' => array(
                        'name'     => __( 'Persoo integration', 'persoo-for-woocommerce' ),
                        'type'     => 'title',
                        'desc'     => $persoo_integration_description,
                        'id'       => 'wc_persoo_settings_section_title'
                    ),
                    'api_key' => array(
                        'name' => __( 'API', 'persoo-for-woocommerce' ),
                        'type' => 'text',
                        'desc' => __( 'Paste your Persoo API key.', 'persoo-for-woocommerce' ),
                        'id'   => 'wc_persoo_settings_api_key',
                        'css'  => 'width:300px',
                        'custom_attributes' => array(
                            'maxlength'  => 64,
                        ),
                        'desc_tip' =>  true,
                    ),
                    'active' => array(
                        'name' => __( 'Activate integration', 'persoo-for-woocommerce' ),
                        'type' => 'checkbox',
                        'desc' => __( 'Check to activate the Persoo integration.', 'persoo-for-woocommerce' ),
                        'id'   => 'wc_persoo_settings_active'
                    ),
                    'section_end' => array(
                        'type' => 'sectionend',
                        'id' => 'wc_persoo_settings_section_end'
                    ),
                );                
                $other_taxonomies = persoo_other_taxonomies();
                if ( !empty ( $other_taxonomies ) ) {
                    $settings['section_title_2'] = array(
                        'name'     => __( 'Additional Persoo Fields', 'persoo-for-woocommerce' ),
                        'type'     => 'title',
                        'desc'     => __( 'Here you can map existing categories to Persoo fields. If you don\'t want to change its name, let the field empty.', 'persoo-for-woocommerce' ) ,
                        'id'       => 'wc_persoo_settings_section_title_2'
                    );
                    foreach ( $other_taxonomies as $ot ) {
                        $settings['persoo_ot_'.$ot] = array(
                            'name' => $ot,
                            'type' => 'text',                           
                            'id'   => 'wc_persoo_settings_ot_'.$ot,
                            'css'  => 'width:300px',
                        );
                    }
                    $settings['section_end_2'] = array(
                        'type' => 'sectionend',
                        'id' => 'wc_persoo_settings_section_end_2'
                    );
                }

            break;
            case 'advanced':
                $settings = array(
                    'section_advanced' => array(
                        'name'     => __( 'Advanced', 'persoo-for-woocommerce' ),
                        'type'     => 'title',
                        'desc'     => 'Settings only for experts.',
                        'id'       => 'wc_persoo_settings_section_advanced'
                    ),
                    'data_layer_name' => array(
                        'name' => __( 'Data Layer Name', 'persoo-for-woocommerce' ),
                        'type' => 'text',
                        'id'   => 'wc_persoo_settings_data_layer_name',
                        'css'  => 'width:300px',
                        'desc_tip' =>  false,
                        'default' => 'dataLayer',
                        'custom_attributes' => array(
                            'pattern'  => '[a-zA-Z0-9]+',
                        ),
                    ),
                    'ignored_taxonomies' => array(
                        'name' => __( 'Ignored taxonomies', 'persoo-for-woocommerce' ),
                        'type' => 'text',
                        'desc' => __( 'List of fields to be excluded from the product feed, comma separated values', 'persoo-for-woocommerce' ),
                        'id'   => 'wc_persoo_settings_ignored_taxonomies',
                        'css'  => 'width:300px',
                        'desc_tip' =>  false,                        
                    ),
                    'security_token' => array(
                        'name' => __( 'Feed secutity token', 'persoo-for-woocommerce' ),
                        'type' => 'text',
                        'desc' => '<a href="#" id="persoo_token">'.__( 'Regenerate', 'persoo-for-woocommerce' ).'</a>',
                        'id'   => 'wc_persoo_settings_security_token',
                        'css'  => 'width:300px',
                        'desc_tip' =>  false,                        
                        'custom_attributes' => array(
                            'disabled' => 'disabled',
                        ),
                    ),
                    'section_end2' => array(
                        'type' => 'sectionend',
                        'id' => 'wc_persoo_settings_section_end-2'
                    ),
                );
            break;            

        }

        return apply_filters( 'wc_settings_tab_persoo_settings', $settings, $current_section );

    }

    /**
     * Output the settings
     *
     * @since     1.0.0
     *
     */
    public function output() {
        global $current_section;
        $settings = $this->get_settings( $current_section );
        WC_Admin_Settings::output_fields( $settings );
    }


    /**
     * Save settings
     *
     * @since     1.0.0
     *
     */
    public function save() {
        global $current_section;
        $settings = $this->get_settings( $current_section );
        WC_Admin_Settings::save_fields( $settings );
        
        // Generate Feed if turned on
        if ( is_persoo() ) {

             wp_schedule_single_event( current_time( 'timestamp', 1 ) + ( 0.1 * MINUTE_IN_SECONDS ), 'persoo_product_xml' );
             wp_schedule_single_event( current_time( 'timestamp', 1 ) + ( 0.1 * MINUTE_IN_SECONDS ), 'persoo_category_xml' );
            
        }

    }

}

new WC_Settings_Persoo();