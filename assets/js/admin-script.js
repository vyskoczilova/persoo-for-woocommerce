(function($) {

    $(document).ready(function() {
                                 
            // enable active only if API filled out
            var api = $('#wc_persoo_settings_api_key');
            if ( api.length ) {
                enableActive( api );
                api.change( function(){
                    enableActive( api );
                });
            }

            // regenerate token
            var token = $('#persoo_token');
            if ( token.lenght ) {
                token.on('click', function(e) {
                    e.preventDefault();
                    if( confirm( persoo.i18n_regenerate )) {
                        $('#wc_persoo_settings_security_token').val( randomToken( persoo.token_lenght ));
                    }
                });					
            }

    });

    function enableActive( api ) {
        
        var active = $('#wc_persoo_settings_active');
        if ( api.val() ) {
            active.prop("disabled", false);
        } else {
            active.prop("disabled", true).prop( "checked", false );
        }

    }

    function randomToken( length ) {
        var chars = persoo.token_allowed;
        var token = "";
        for (var x = 0; x < length; x++) {
            var i = Math.floor(Math.random() * chars.length);
            token += chars.charAt(i);
        }
        return token;
    }
	
})( jQuery );