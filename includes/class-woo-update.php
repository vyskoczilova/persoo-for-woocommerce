<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// class fixing the WooCommerce 2.6 & 3.0.0+ issues
if ( ! class_exists( 'wooUpdate' ) ) {
    class wooUpdate {

        private $old_woo;

        function __construct() {
            $this->old_woo = $this->checkVersion();
        }

        private function checkVersion() {
            if ( version_compare( WC_VERSION, '2.7', '<' )) { 
                return true;
            } else {
                return false;
            }
        }

        public function price( $item ) {
            if ( $this->old_woo ) { 
                return $item['data']->price;
            } else { 
                return $item['data']->get_price();
            } 
        }

        public function price_including_tax( $item ) {
            if ( $this->old_woo ) { 
                return $item->get_price_including_tax();
            } else { 
                return wc_get_price_including_tax( $item, array( 1, $item->get_price() ));
            } 
        }

        public function regular_price( $item ) {
            if ( $this->old_woo ) { 
                return $item->get_regular_price();
            } else { 
                return wc_get_price_including_tax( $item, array( 1, $item->get_regular_price() ));
            } 
        }

        public function stock( $item ) {
            $manage_stock = get_option( 'woocommerce_manage_stock', false );
            if ( $this->old_woo ) { 
                if ( $item->managing_stock() ) {                    
                    return $item->get_total_stock();
                } else {     
                    return $item->stock_status;
                }
            } else { 
                if ( $item->get_stock_quantity() ) {
                    return $item->get_stock_quantity();
                } else {
                    return $item->get_stock_status();
                }
            } 
        }

        public function availability( $item ) {
            $availability = $this->stock( $item );
            if ( is_numeric($availability) ) {
                switch ( $availability ) {
                    case 0:
                        $availability = 'outofstock';
                    default:
                        $availability = 'instock';
                        break;
                }
            }
            return $availability;
        }

        public function excerpt( $item ) {
            if ( $this->old_woo ) { 
                return $item->post->post_excerpt;
            } else { 
                return $item->get_short_description();
            } 
        }

        public function content( $item ) {
            if ( $this->old_woo ) { 
                return $item->post->post_content;
            } else { 
                return $item->get_description();
            } 
        }

        public function id( $item ) {
            if ( $this->old_woo ) { 
                return $item->id;
            } else { 
                return $item->get_id();
            } 
        }

        public function id_variation ( $variation ) {
            if ( $this->old_woo ) { 
                return $variation->variation_id;
            } else { 
                return $variation->get_id();
            } 
        }

        public function sku( $item ) {
            if ( $this->old_woo ) { 
                return $item->sku;
            } else { 
                return $item->get_sku();
            }
        }

        public function billing_email( $order ) {
            if ( $this->old_woo ) {
                return $order->billing_email;
            } else {
                return $order->get_billing_email();
            }
        }

        public function shipping_total( $order ) {
            if ( $this->old_woo ) {
                return $order->get_total_shipping();
            } else {
                return $order->get_shipping_total();
            }
        }

        public function order_item_quantity( $item ) {
            if ( $this->old_woo ) {
                return $item['qty'];
            } else {
                return $item['quantity'];
            }
        }

        public function order_item_price( $item ) {
            if ( $this->old_woo ) {
                return $item['line_subtotal']/$item['qty'];
            } else {
                return $item['subtotal']/$item['quantity'];
            }
        }
    }
}