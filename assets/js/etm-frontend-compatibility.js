document.addEventListener("DOMContentLoaded", function(event) {
    function etmClearWooCartFragments(){

        // clear WooCommerce cart fragments when switching language
        var etm_language_switcher_urls = document.querySelectorAll(".etm-language-switcher-container a:not(.etm-ls-disabled-language)");

        for (i = 0; i < etm_language_switcher_urls.length; i++) {
            etm_language_switcher_urls[i].addEventListener("click", function(){
                if ( typeof wc_cart_fragments_params !== 'undefined' && typeof wc_cart_fragments_params.fragment_name !== 'undefined' ) {
                    window.sessionStorage.removeItem(wc_cart_fragments_params.fragment_name);
                }
            });
        }
    }

    etmClearWooCartFragments();
});
