jQuery( document ).ready(function(){
/*    jQuery('#wppb_manage_fields #field').select2({
        placeholder: 'Select an option'
    })*/

    // var overlay = jQuery('<div id="etm_select2_overlay"> </div>')
    // overlay.appendTo('#etm-controls');

    var $eventSelectLanguage = jQuery("#etm-language-select");
    $eventSelectLanguage.on("select2:open", function (e) {
        jQuery('#etm_select2_overlay').fadeIn('100')
    });
    $eventSelectLanguage.on("select2:close", function (e) {
        jQuery('#etm_select2_overlay').hide();
    });

    var $eventSelectString = jQuery("#etm-string-categories");
    $eventSelectString.on("select2:open", function (e) {
        jQuery('#etm_select2_overlay').fadeIn('100')
    });
    $eventSelectString.on("select2:close", function (e) {
        jQuery('#etm_select2_overlay').hide();
    });



})
