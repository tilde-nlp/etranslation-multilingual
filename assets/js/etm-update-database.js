jQuery( function() {
    function trigger_update_by_ajax( data ) {
        jQuery.ajax({
            url: etm_updb_localized['admin_ajax_url'],
            type: 'post',
            dataType: 'json',
            data: data,
            success: function (response) {
                jQuery('#etm-update-database-progress').append(response['progress_message'])
                if ( response['etm_update_completed'] == 'no' ) {
                    trigger_update_by_ajax(response);
                }
            },
            error: function (errorThrown) {
                jQuery('#etm-update-database-progress').append(errorThrown['responseText'])
                console.log('eTranslation Multilingual AJAX Request Error while triggering database update');
            }
        });
    };
    trigger_update_by_ajax( {
        action: 'etm_update_database',
        etm_updb_nonce: etm_updb_localized['nonce'],
        initiate_update: true,
    } );
});