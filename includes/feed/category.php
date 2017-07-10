<?php

$xmlWriter->startElement( 'item' );
$xmlWriter->writeElement( 'categoryID', $category->term_id );          
$xmlWriter->writeElement( 'hierarchyID', persoo_get_category_hierarchy( $category->term_id ) );
$xmlWriter->writeElement( 'hierarchy', persoo_get_category_hierarchy( $category->term_id, 'name' ) );
$xmlWriter->writeElement( 'categoryLink', get_category_link( $category->term_id ) );
$xmlWriter->writeElement( 'categoryName', $category->name );
$xmlWriter->endElement();