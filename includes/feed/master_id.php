<?php

$master_id = $woo->id($product);
$selected_categories = $xmlHelper->get_categories( $master_id, 'product_cat' );
$product_title = get_the_title( $master_id );
$product_decsription = $xmlHelper->get_product_description( $woo->excerpt($product),$woo->content($product), false );    