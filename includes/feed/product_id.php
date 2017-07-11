<?php

$product = wc_get_product( $product_id );
$product_title = get_the_title( $product_id );
$product_decsription = $xmlHelper->get_product_description( $woo->excerpt($product),$woo->content($product), false );             
$selected_categories = $xmlHelper->get_categories( $product_id, 'product_cat' );  