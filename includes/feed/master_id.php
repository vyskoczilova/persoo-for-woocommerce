<?php

$master_id = $woo->id($produkt);
$prirazene_kategorie = $xmlHelper->get_kategorie( $master_id, 'product_cat' );
$nazev_produkt = get_the_title( $master_id );
$popis_produkt = $xmlHelper->popis_produktu( $woo->excerpt($produkt),$woo->content($produkt), false );    