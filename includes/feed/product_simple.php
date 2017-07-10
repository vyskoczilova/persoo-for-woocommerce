<?php

$xmlWriter->startElement( 'shopitem' );
$xmlWriter->writeElement( 'itemID', $product_id ); 

foreach ( $prirazene_kategorie as $k) {    
    $xmlWriter->writeElement( 'categoryID', $k->term_id );
}
foreach ( $prirazene_kategorie as $k) {    
    $xmlWriter->writeElement( 'hierarchyID', persoo_get_category_hierarchy( $k->term_id ) );
}
foreach ( $prirazene_kategorie as $k) {    
    $xmlWriter->writeElement( 'category', $k->name );
}
foreach ( $prirazene_kategorie as $k) {    
    $xmlWriter->writeElement( 'hierarchy', persoo_get_category_hierarchy( $k->term_id, 'name' ) );
}

if ( ! empty ( $nazev_produkt ) ) {
    $xmlWriter->writeElement( 'title', $nazev_produkt );
}
if ( ! empty ( $popis_produkt ) ) {
    $xmlWriter->writeElement( 'description', $popis_produkt );
}
if ( ! empty ( $woo->sku($produkt) ) ) {
    $xmlWriter->writeElement( 'sku', $woo->sku($produkt) );
}
$xmlWriter->writeElement( 'price', $woo->price_including_tax( $produkt ) );
if ( $produkt -> is_on_sale() ) {
    $xmlWriter->writeElement( 'priceOriginal', $woo->regular_price( $produkt ) );
}
$xmlWriter->writeElement( 'link', get_permalink( $product_id ) );
$xmlWriter->writeElement( 'imageLink', str_replace( array( '%3A', '%2F' ), array ( ':', '/' ), urlencode( wp_get_attachment_url( get_post_thumbnail_id( $product_id ) ) ) ) );                        

$xmlWriter->writeElement( 'availability', $woo->availability( $produkt ) );
//$xmlWriter->writeElement( 'availability', var_dump( $produkt ) );
$stock = $woo->stock( $produkt );
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