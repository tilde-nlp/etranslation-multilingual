jQuery( function() {
    function place_tp_button() {

        // check if gutenberg's editor root element is present.
        var editorEl = document.getElementById( 'editor' );
        if ( !editorEl ){ // do nothing if there's no gutenberg root element on page.
            return;
        }

        var unsubscribe = wp.data.subscribe( function () {
                if ( !document.getElementById( "trp-link-id" ) ){
                    var toolbalEl = editorEl.querySelector( '.edit-post-header-toolbar__left' );
                    if ( toolbalEl instanceof HTMLElement ){
                        toolbalEl.insertAdjacentHTML("afterend", trp_url_tp_editor[0] );
                    }
                }
        } );
     }

        place_tp_button();
});
