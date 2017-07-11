<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'persooXmlHelpers' ) ) {
    class persooXmlHelpers {

        public function get_categories( $product_id ) {
            $categories = array();
            $available_categories = get_the_terms( $product_id, 'product_cat' );
            if ( $available_categories && ! is_wp_error( $available_categories ) ) {
                $categories = $available_categories;
            }
            return $categories;
        }

        public function get_product_description( $post_excerpt, $post_content, $variation ) {
            $description = "";
            $product_description = "";
            $variation_description = "";
            if ( ! empty ( $post_excerpt ) ) {
                $product_description = $post_excerpt;
            } else {
                $product_description = $post_content;
            }
            if ( $variation ) {
                if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '2.4', '>=' ) ) {
                $variation_description = $variation->get_variation_description();
                } else {
                $variation_description = get_post_meta( $variation->variation_id, '_variation_description', true );
                }
                if ( empty ( $variation_description ) ) {
                $variation_description = $product_description;
                }
                $description = $variation_description;
            } else {
                $description = $product_description;
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