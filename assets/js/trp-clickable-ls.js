jQuery('.trp-ls-shortcode-current-language').click(function () {
    jQuery( '.trp-ls-shortcode-current-language' ).addClass('trp-ls-clicked');
    jQuery( '.trp-ls-shortcode-language' ).addClass('trp-ls-clicked');
});

jQuery('.trp-ls-shortcode-language').click(function () {
    jQuery( '.trp-ls-shortcode-current-language' ).removeClass('trp-ls-clicked');
    jQuery( '.trp-ls-shortcode-language' ).removeClass('trp-ls-clicked');
});

jQuery(document).keyup(function(e) {
    if (e.key === "Escape") {
        jQuery( '.trp-ls-shortcode-current-language' ).removeClass('trp-ls-clicked');
        jQuery( '.trp-ls-shortcode-language' ).removeClass('trp-ls-clicked');
    }
});

jQuery(document).on("click", function(event){
    if(!jQuery(event.target).closest(".trp-ls-shortcode-current-language").length){
        jQuery( '.trp-ls-shortcode-current-language' ).removeClass('trp-ls-clicked');
        jQuery( '.trp-ls-shortcode-language' ).removeClass('trp-ls-clicked');
    }
});