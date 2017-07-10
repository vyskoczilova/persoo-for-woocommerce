jQuery( function() {

    persoo_track_ajax_add_to_cart();
	jQuery( "body" )
		.on( "added_to_cart", function() {
            persoo('send','addToBasket',{itemID: persoo_clicked_product.id , pageType: persoo_clicked_product.pageType });
		});

	if ( window.location.search.indexOf( "added-to-cart" ) > -1 ) {
		    persoo('send','addToBasket',{itemID: persoo_clicked_product.id , pageType: persoo_clicked_product.pageType });
	}

});

var persoo_clicked_product = {};
function persoo_track_ajax_add_to_cart()
{
	jQuery('body').on('click','.add_to_cart_button', function()
	{	
        
		var productContainer = jQuery(this).parents('.product').eq(0), product = {};
            productClass = productContainer.attr("class").match(/post-[0-9]+\b/);
            productBody = jQuery('body').attr("class").match(/persoo_[a-z]+\b/);
            
            product.id = productClass[0].substr(5);            
            product.pageType = productBody[0].substr(7);
			
			persoo_clicked_product = product;
	});
}