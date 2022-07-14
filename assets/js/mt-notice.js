function hideMtNotice() {
    jQuery(".mt-notice-container").hide();
    jQuery(".mt-notice-space").hide();
}
jQuery( document ).ready(function() {
    var original_url = mt_notice_params.original_url;
    var img_url = mt_notice_params.img_url;

    jQuery('body').prepend(
        `<div class="mt-notice-container">\
            <div class="translation-notice">\
                This page has been machine-translated. <a href="${original_url}" class="mt-notice-link">Show original</a>\
            </div>\
            <div id="mt-notice-hide" onclick="hideMtNotice()"><img src='${img_url}' /></div>\
        </div>\
        <div class="mt-notice-space"></div>`
    );

    var mtNoticeContainer = jQuery(".mt-notice-container");
    if(mtNoticeContainer.length){
        jQuery(".mt-notice-space").css("height", mtNoticeContainer.height());
    }
});