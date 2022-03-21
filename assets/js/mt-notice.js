function hideMtNotice() {
    jQuery(".mt-notice-container").hide();
    jQuery(".mt-notice-space").hide();
}
jQuery( document ).ready(function() {
    var mtNoticeContainer = jQuery(".mt-notice-container");
    if(mtNoticeContainer.length){
        jQuery(".mt-notice-space").css("height", mtNoticeContainer.height());
    }
});