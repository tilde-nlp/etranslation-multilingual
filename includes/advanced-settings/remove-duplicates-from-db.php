<?php
add_filter( 'etm_register_advanced_settings', 'etm_register_remove_duplicate_entries_from_db', 530 );
function etm_register_remove_duplicate_entries_from_db( $settings_array ){
    $settings_array[] = array(
        'name'          => 'remove_duplicate_entries_from_db',
        'type'          => 'text',
        'label'         => esc_html__( 'Optimize eTranslation Multilingual database tables', 'etranslation-multilingual' ),
        'description'   => wp_kses_post( sprintf( __( 'Click <a href="%s">here</a> to remove duplicate rows from the database.', 'etranslation-multilingual' ), admin_url('admin.php?page=etm_remove_duplicate_rows') ) ),
    );
    return $settings_array;
}
