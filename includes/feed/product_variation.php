<?php

$variation_id = $woo->id_variation( $varianta );
                                    
$xmlWriter->startElement( 'shopitem' );
$xmlWriter->writeElement( 'itemID', $variation_id );
$xmlWriter->writeElement( 'itemGroupID', $master_id );

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
if ( ! empty ( $woo->sku($varianta) ) ) {
    $xmlWriter->writeElement( 'sku', $woo->sku($varianta) );
}
$xmlWriter->writeElement( 'price', $woo->price_including_tax( $varianta ) );
if ( $produkt -> is_on_sale() ) {
    $xmlWriter->writeElement( 'priceOriginal', $woo->regular_price( $varianta ) );
}
$xmlWriter->writeElement( 'link', get_permalink( $variation_id ) );
$xmlWriter->writeElement( 'imageLink', str_replace( array( '%3A', '%2F' ), array ( ':', '/' ), urlencode( wp_get_attachment_url( get_post_thumbnail_id( $variation_id ) ) ) ) );

$xmlWriter->writeElement( 'availability', $woo->availability( $varianta ) );
$stock = $woo->stock( $varianta );
if ( is_numeric( $stock ) && $stock > 0 ) {
    $xmlWriter->writeElement( 'available', $stock );
}

$attributes = $varianta->get_variation_attributes();
$used_tax = array();                                 
if ( $attributes ) {                                                         
    foreach ( $attributes as $key=>$value ) {
        $map_a = substr( $key, 10); 
        $xmlWriter->writeElement( $map_taxonomies[$map_a], $value );
        array_push( $used_tax, $map_a );                                            
    }
}
if ( ! empty ( $taxonomies ) ) {         
    foreach ( $taxonomies as $t ) {
        if ( ! in_array( $t, $used_tax )) {
            $tax_value = $xmlHelper->get_all_taxonomies( $t, $master_id );
            if ( $tax_value ) {
                $xmlWriter->writeElement( $map_taxonomies[$t], $tax_value );
            }
        }
    }
} 
$xmlWriter->endElement();