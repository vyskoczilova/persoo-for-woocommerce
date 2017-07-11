<?php

$xmlWriter->startElement( 'shopitem' );
$xmlWriter->writeElement( 'itemID', $product_id ); 

foreach ( $selected_categories as $k) {    
    $xmlWriter->writeElement( 'categoryID', $k->term_id );
}
foreach ( $selected_categories as $k) {    
    $xmlWriter->writeElement( 'hierarchyID', persoo_get_category_hierarchy( $k->term_id ) );
}
foreach ( $selected_categories as $k) {    
    $xmlWriter->writeElement( 'category', $k->name );
}
foreach ( $selected_categories as $k) {    
    $xmlWriter->writeElement( 'hierarchy', persoo_get_category_hierarchy( $k->term_id, 'name' ) );
}

if ( ! empty ( $product_title ) ) {
    $xmlWriter->writeElement( 'title', $product_title );
}
if ( ! empty ( $product_decsription ) ) {
    $xmlWriter->writeElement( 'description', $product_decsription );
}
if ( ! empty ( $woo->sku($product) ) ) {
    $xmlWriter->writeElement( 'sku', $woo->sku($product) );
}
$xmlWriter->writeElement( 'price', $woo->price_including_tax( $product ) );
if ( $product -> is_on_sale() ) {
    $xmlWriter->writeElement( 'priceOriginal', $woo->regular_price( $product ) );
}
$xmlWriter->writeElement( 'link', get_permalink( $product_id ) );
$xmlWriter->writeElement( 'imageLink', str_replace( array( '%3A', '%2F' ), array ( ':', '/' ), urlencode( wp_get_attachment_url( get_post_thumbnail_id( $product_id ) ) ) ) );                        

$xmlWriter->writeElement( 'availability', $woo->availability( $product ) );

$stock = $woo->stock( $product );
if ( is_numeric( $stock ) && $stock > 0 ) {
    $xmlWriter->writeElement( 'available', $stock );
}
                            
if ( ! empty ( $taxonomies ) ) {                                
    foreach ( $taxonomies as $t ) {
        $tax_value = $xmlHelper->get_all_taxonomies( $t, $product_id );
        if ( $tax_value ) {
            $xmlWriter->writeElement( $map_taxonomies[$t], $tax_value );
        }
    }
}

$xmlWriter->endElement();