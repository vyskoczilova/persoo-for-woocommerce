<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'persooXmlHelpers' ) ) {
    class persooXmlHelpers {

        public function get_kategorie( $product_id ) {
            $kategorie = array();
            $dostupne_kategorie = get_the_terms( $product_id, 'product_cat' );
            if ( $dostupne_kategorie && ! is_wp_error( $dostupne_kategorie ) ) {
                $kategorie = $dostupne_kategorie;
            }
            return $kategorie;
        }

        public function popis_produktu( $post_excerpt, $post_content, $varianta ) {
            $description = "";
            $produkt_description = "";
            $varianta_description = "";
            if ( ! empty ( $post_excerpt ) ) {
                $produkt_description = $post_excerpt;
            } else {
                $produkt_description = $post_content;
            }
            if ( $varianta ) {
                if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '2.4', '>=' ) ) {
                $varianta_description = $varianta->get_variation_description();
                } else {
                $varianta_description = get_post_meta( $varianta->variation_id, '_variation_description', true );
                }
                if ( empty ( $varianta_description ) ) {
                $varianta_description = $produkt_description;
                }
                $description = $varianta_description;
            } else {
                $description = $produkt_description;
            }
            $description = strip_shortcodes( $description );
            $description = str_replace( chr(26), '', $description );
            return wp_strip_all_tags( $description );
        }

        public function get_all_taxonomies ( $taxonomy, $post_id ) {
            $taxonomy_all = array();
            if ( taxonomy_exists( $taxonomy ) ) {
                $terms = get_the_terms($post_id, $taxonomy );
                if (! empty($terms)) {
                    foreach ($terms as $t) {
                        array_push($taxonomy_all, $t->slug);
                    }
                }                
            } 

            if ( !empty($taxonomy_all) ) {
                $taxonomy_all = implode('|', $taxonomy_all);
                return $taxonomy_all;
            } else {
                return false;
            }
            
        }

    }
}