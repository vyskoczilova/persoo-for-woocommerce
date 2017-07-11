<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'persooJsSnippet' ) ) {
    class persooJsSnippet {

        private $apiKey;
        private $dataLayerName;

        /**
        * Constructor
        */
        function __construct() {
            
            $this->dataLayerName = ( get_option( 'wc_persoo_settings_data_layer_name', false ) ? get_option( 'wc_persoo_settings_data_layer_name', false) : 'dataLayer' );
            
            // Get Persoo apikey
            $this->apiKey = esc_html(trim(get_option( 'wc_persoo_settings_api_key', true )));
            
            // Load Persoo script in HTML head tag            
            add_action( 'wp_head', array($this, 'persoo_load_snippet'), 1, 0);

            // Hook a custom action
            do_action( 'persoo_snippet_loaded' );
            

        }

        /**
        * Add JS Snippet into HTML head tag
        *
        * @since     1.0.0
        */
        public function persoo_load_snippet() {
            ?>
<script type='text/javascript'>
<?php echo $this->dataLayer(); ?>

var persooConfig = {
    apikey: '<?php echo $this->apiKey; ?>',
    persooName: 'persoo',
    dataLayerName: '<?php echo $this->dataLayerName; ?>',
    settings_tolerance: 2000,
    personalizations_tolerance: 2500,
};

var persooLoader=function(a,b,c,d,e){var f=d.persooName,g='_persoo_hide_body';return{hideBody:function(){var b=a.createElement('style'),c='body{opacity:0 !important;filter:alpha(opacity=0)'+' !important;background:none !important;}',d=a.getElementsByTagName('head')[0];b.setAttribute('id',g),b.setAttribute('type','text/css'),b.styleSheet?b.styleSheet.cssText=c:b.appendChild(a.createTextNode(c)),d.appendChild(b)},finish:function(){if(!c){c=!0;var b=a.getElementById(g);b&&b.parentNode.removeChild(b)}},loadScript:function(b){var c=a.createElement('script');c.src=b,c.type='text/javascript',c.onerror=function(){persooLoader.finish()},a.getElementsByTagName('head')[0].appendChild(c)},init:function(){b[f]=b[f]||function(){(b[f].q=b[f].q||[]).push([].slice.call(arguments))},b[f].l=1*new Date,b[f].apikey=d.apikey,b[f].dataLayerName=d.dataLayerName;var c=a.cookie.match('(^|; )'+e+'=([^;]*)'),g=location.search.match('[?&]'+e+'=([^&]*)'),h=g?g[1]:c?c[2]:'p';d.settings_tolerance>0&&(setTimeout(this.finish,d.settings_tolerance),this.hideBody());var i=(d.scriptsHostname||'//scripts.persoo.cz/')+d.apikey+'/'+h;this.loadScript(i+'/actions.js'),this.loadScript(i+'/persoo.js')}}}(document,window,!1,persooConfig,'persooEnvironment');persooLoader.init();
</script>
            <?php
        }

        /**
        * Construct a static dataLayer array based on currentlly loaded page and formats it.
        * http://support.persoo.co/technical-guide/data-collection/data-layer/
        *
        * @since     1.0.0
        * @return    string    Formatted dataLayer.
        */
        public function dataLayer() {

            global $post, $wp_query, $product;        
            $dataLayer = array();
            $woo = new wooUpdate();
            
            $dataLayer['currency'] = get_woocommerce_currency();
            $impressedProducts = $this->get_impressed_products();
            if ( $impressedProducts != '[]' ) {
                $dataLayer['impressedProducts'] = $impressedProducts;
            }

            // Homepage
            if ( is_front_page() ) {
                $dataLayer['pageType'] = 'homepage';                                
            }
            // product detail page
            elseif ( is_product() ) {
                $dataLayer['pageType'] = 'detail';
                $dataLayer['itemID'] = $post->ID;
                $dataLayer['categoryID'] = $this->getTaxonomyIds('product_cat');
                $dataLayer['hierarchy'] = persoo_get_category_hierarchy( $this->getTaxonomyIds('product_cat') );                
                $dataLayer = $this->getOtherTaxonomies( $dataLayer );   
                // TODO, co když je tu víc kategorií             
            }
            // product list (category) page
            elseif ( is_product_category() || is_product_tag() ) {
                $object = get_queried_object();
                $dataLayer['pageType'] = 'category';                
                $dataLayer['categoryID'] = $object->term_id;
                $dataLayer['hierarchyID'] = persoo_get_category_hierarchy( $object->term_id );                
                $dataLayer['category'] = $object->name;
                $dataLayer['hierarchy'] = persoo_get_category_hierarchy( $object->term_id, 'name' );                
            }
            // on the page of basket, through the checkout process and order received page
            elseif ( is_cart() || is_checkout() || is_order_received_page() ) {
                $dataLayer['pageType'] = 'basket';
            }
            // 404 page not found
            elseif ( is_404() ) {
                $dataLayer['pageType'] = 'error';
            }
            // search
            elseif ( is_search() ) {
                $dataLayer['pageType'] = 'search';
                $dataLayer['query'] = get_search_query();
                $dataLayer['results'] = $wp_query->found_posts;                
            }
            // on other pages
            else {
                $dataLayer['pageType'] = 'other';
            }

            // If anything in basket
            if ( WC()->cart->get_cart_contents_count() > 0 ) {
                $basketItems = array();
                foreach ( WC()->cart->cart_contents as $item ) {
                    $basketItems[ "'" . $item['product_id'] . "'"] = array ('quantity' => $item['quantity'], 'price' => $woo->price($item));

                }
                $dataLayer['basketItems'] = $basketItems;
                $dataLayer['basketTotal'] = WC()->cart->cart_contents_total;
            }             

            // User identification
            // http://support.persoo.co/technical-guide/data-collection/data-layer/#1455649078160-d8812c17-82a0
            if ( is_user_logged_in() ) {
                $user_id = get_current_user_id();
                $user = get_userdata( $user_id );
                $dataLayer['userId'] = $user_id;
                $dataLayer['login'] = $user->user_login;
                $dataLayer['email'] = $user->user_email;
            } else {
                if ( is_order_received_page() ) {
                    global $wp;
                    $order_id = isset( $wp->query_vars['order-received'] ) ? intval( $wp->query_vars['order-received'] ) : 0;
                    $order = new WC_Order( $order_id );
                    if ( $order && ! $order->has_status( 'failed' ) ) {
                        $dataLayer['email'] = $woo->billing_email( $order );
                    }

                }
            }
            
            // Order received  
            // http://support.persoo.co/technical-guide/data-collection/data-layer/#transaction 
            // https://wptheming.com/2015/06/custom-conversion-tracking-woocommerce/       
            if ( is_order_received_page() ) {
                global $wp;
                $order_id = isset( $wp->query_vars['order-received'] ) ? intval( $wp->query_vars['order-received'] ) : 0;
                $order = new WC_Order( $order_id );

                if ( $order && ! $order->has_status( 'failed' ) ) {
                    $price = $order->get_total();
                    $order_id = $order->get_order_number();
                    $tax = $order->get_total_tax();
                    $shipping = $woo->shipping_total( $order );

                    foreach ( $order->get_items() as $item ) {                    

                       $item_quantity = $woo->order_item_quantity( $item );
                       $item_price = $woo->order_item_price( $item );

                       $basketItems[ "'" . $item['product_id'] . "'"] = array ('quantity' => $item_quantity , 'price' => $item_price);

                    }
                    $dataLayer['basketItems'] = $basketItems;
                    $dataLayer['basketTotal'] = $price;
                    $dataLayer['transaction'] = array( 'id' => $order_id, 'revenue' => $price, 'shipping' => $shipping , 'tax' => $tax );
                    
                }

            }

            return $this->dataLayerEncode( apply_filters( 'persoo_data_layer', $dataLayer ));

        }

        /**
        * Generates correctly fomatted dataLayer from array
        *
        * @since     1.0.0
        * @var       array     dataLayer array to format
        * @return    string    Formatted dataLayer.
        */
        public function dataLayerEncode( $array ) {

             return 'var ' . $this->dataLayerName . ' = ' . json_encode([$array], JSON_PRETTY_PRINT);

        }

        /**
        * Return ids of custom taxonomy if exists
        *
        * @since     1.0.0
        * @var       string   taxonomy name  
        * @return    string   IDs
        */
        public function getTaxonomyIds( $taxonomy ) {
            
            global $post;
            $array = array();

            if ( taxonomy_exists( $taxonomy ) ) {
                $terms = get_the_terms($post->ID, $taxonomy );
                foreach ($terms as $t) {
                    array_push($array, $t->term_id);
                }
            }

            if ( $taxonomy == "product_cat" ) {
                return $array[0];
            } else {
                return $array;
            }

        }

        /**
        * Return slugs of custom taxonomy if exists
        *
        * @since     1.0.0
        * @var       string   taxonomy name  
        * @return    string   slugs
        */
        public function getTaxonomySlugs( $taxonomy ) {
            
            global $post;
            $array = array();

            if ( taxonomy_exists( $taxonomy ) ) {
                $terms = get_the_terms($post->ID, $taxonomy );
                if (! empty($terms)) {
                    foreach ($terms as $t) {
                        array_push($array, $t->slug);
                    }
                }                
            } 

            if ( !empty($array) ) {
                return $array;
            } else {
                return false;
            }

        }

        /**
        * Add other taxonomies available to dataLayer
        *
        * @since     1.0.0
        * @var       array   dataLayer  
        * @return    array   dataLayer with available taxonomies
        */
        public function getOtherTaxonomies( $dataLayer ) {
            
            $taxonomies = persoo_other_taxonomies();
            $map_taxonomies = persoo_map_taxonomies();
            foreach ( $taxonomies as $t) {
                    
                // if the taxonomy is set for the current product
                if ( $this->getTaxonomySlugs($t) ) {
                    $dataLayer[$map_taxonomies[$t]] = $this->getTaxonomySlugs($t);              
                }

            }

            return $dataLayer;

        }

        /**
        * Grabs impressed Products from WP Query
        *
        * @since     1.0.0
        * @var       
        * @return    array  
        */
        public function get_impressed_products() {
            global $wp_query, $wc_query;
            $impressedProducts = array();
            if ( $wc_query == "product_query" ) {
                foreach ( $wp_query->posts as $p ) {
                    array_push ( $impressedProducts, $p->ID );
                }
            }
            return $impressedProducts;
        }

    }
}
new persooJsSnippet();