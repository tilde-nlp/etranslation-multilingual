jQuery('.etm_language_switcher_shortcode .etm-ls-shortcode-current-language').click(function () {
    jQuery( '.etm_language_switcher_shortcode .etm-ls-shortcode-current-language' ).addClass('etm-ls-clicked');
    jQuery( '.etm_language_switcher_shortcode .etm-ls-shortcode-language' ).addClass('etm-ls-clicked');
});

jQuery('.etm_language_switcher_shortcode .etm-ls-shortcode-language').click(function () {
    jQuery( '.etm_language_switcher_shortcode .etm-ls-shortcode-current-language' ).removeClass('etm-ls-clicked');
    jQuery( '.etm_language_switcher_shortcode .etm-ls-shortcode-language' ).removeClass('etm-ls-clicked');
});

jQuery(document).keyup(function(e) {
    if (e.key === "Escape") {
        jQuery( '.etm_language_switcher_shortcode .etm-ls-shortcode-current-language' ).removeClass('etm-ls-clicked');
        jQuery( '.etm_language_switcher_shortcode .etm-ls-shortcode-language' ).removeClass('etm-ls-clicked');
    }
});

jQuery(document).on("click", function(event){
    if(!jQuery(event.target).closest(".etm_language_switcher_shortcode .etm-ls-shortcode-current-language").length){
        jQuery( '.etm_language_switcher_shortcode .etm-ls-shortcode-current-language' ).removeClass('etm-ls-clicked');
        jQuery( '.etm_language_switcher_shortcode .etm-ls-shortcode-language' ).removeClass('etm-ls-clicked');
    }
});