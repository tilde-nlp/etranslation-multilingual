<?php

add_filter( 'etm_register_advanced_settings', 'etm_register_exclude_gettext_strings', 100 );
function etm_register_exclude_gettext_strings( $settings_array ){
	$settings_array[] = array(
		'name'          => 'exclude_gettext_strings',
		'type'          => 'list',
		'columns'       => array(
								'string' => __('Gettext String', 'etranslation-multilingual' ),
								'domain' => __('Domain', 'etranslation-multilingual')
							),
		'label'         => esc_html__( 'Exclude Gettext Strings', 'etranslation-multilingual' ),
		'description'   => wp_kses( __( 'Exclude these strings from being translated as Gettext strings by eTranslation Multilingual. Leave the domain empty to take into account any Gettext string.<br/>Can still be translated through po/mo files.', 'etranslation-multilingual' ), array( 'br' => array() ) ),
	);
	return $settings_array;
}

/**
 * Exclude gettext from being translated
 */
add_action( 'init', 'etm_load_exclude_strings' );
function etm_load_exclude_strings(){
	$option = get_option( 'etm_advanced_settings', true );

	if( isset( $option['exclude_gettext_strings'] ) && count( $option['exclude_gettext_strings']['string'] ) > 0 )
		add_filter('etm_skip_gettext_processing', 'etm_exclude_strings', 1000, 4 );

}

function etm_exclude_strings ( $return, $translation, $text, $domain ){
	$option = get_option( 'etm_advanced_settings', true );

	if ( isset( $option['exclude_gettext_strings'] ) ) {

		foreach( $option['exclude_gettext_strings']['string'] as $key => $string ){

            if((empty(trim($string))) && (trim($domain ) === trim( $option['exclude_gettext_strings']['domain'][$key]))){

                return true;
            }

			if( trim( $text ) === trim( $string ) ){

				if( empty( $option['exclude_gettext_strings']['domain'][$key] ) )
					return true;
				else if( trim( $domain ) === trim( $option['exclude_gettext_strings']['domain'][$key] ) )
					return true;

			}

		}
	}

	return $return;
}
