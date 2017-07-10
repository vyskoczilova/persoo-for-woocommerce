<?php

$produkt = wc_get_product( $product_id );
$nazev_produkt = get_the_title( $product_id );
$popis_produkt = $xmlHelper->popis_produktu( $woo->excerpt($produkt),$woo->content($produkt), false );             
$prirazene_kategorie = $xmlHelper->get_kategorie( $product_id, 'product_cat' );  