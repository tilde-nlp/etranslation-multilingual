<?php

/** Compatibility functions with WP core and various themes and plugins*/

/**
 * Remove '?fl_builder' query param from edit translation url (when clicking the admin bar button to enter the translation Editor)
 *
 * Otherwise after publishing out of BB and clicking TP admin bar button, it’s still showing the BB interface
 *
 * @param $url
 *
 * @return bool
 */
function etm_beaver_builder_compatibility( $url ){

    $url = remove_query_arg('fl_builder', $url );

    return esc_url ($url);

}
add_filter( 'etm_edit_translation_url', 'etm_beaver_builder_compatibility' );


/**
 * Mb Strings missing PHP library error notice
 */
function etm_mbstrings_notification(){
    echo '<div class="notice notice-error"><p>' . wp_kses( __( '<strong>eTranslation Multilingual</strong> requires <strong><a href="http://php.net/manual/en/book.mbstring.php">Multibyte String PHP library</a></strong>. Please contact your server administrator to install it on your server.','etranslation-multilingual' ), [ 'a' => [ 'href' => [] ], 'strong' => [] ] ) . '</p></div>';
}

function etm_missing_mbstrings_library( $allow_to_run ){
    if ( ! extension_loaded('mbstring') ) {
        add_action( 'admin_menu', 'etm_mbstrings_notification' );
        return false;
    }
    return $allow_to_run;
}
add_filter( 'etm_allow_tp_to_run', 'etm_missing_mbstrings_library' );

/**
 * Don't have html inside menu title tags. Some themes just put in the title the content of the link without striping HTML
 */
add_filter( 'nav_menu_link_attributes', 'etm_remove_html_from_menu_title', 10, 3);
function etm_remove_html_from_menu_title( $atts, $item, $args ){
    if( isset( $atts['title'] ) )
        $atts['title'] = wp_strip_all_tags($atts['title']);

    return $atts;
}

/**
 * Rework wp_trim_words so we can trim Chinese, Japanese and Thai words since they are based on characters as words.
 *
 * @since 1.3.0
 *
 * @param string $text      Text to trim.
 * @param int    $num_words Number of words. Default 55.
 * @param string $more      Optional. What to append if $text needs to be trimmed. Default '&hellip;'.
 * @return string Trimmed text.
 */
function etm_wp_trim_words( $text, $num_words, $more, $original_text ) {
    if ( null === $more ) {
        $more = __( '&hellip;' );//phpcs:ignore
    }
    // what we receive is the short text in the filter
    $text = $original_text;
    $text = wp_strip_all_tags( $text );

    $etm = ETM_eTranslation_Multilingual::get_etm_instance();
    $etm_settings = $etm->get_component( 'settings' );
    $settings = $etm_settings->get_settings();

    $default_language= $settings["default-language"];

    $char_is_word = false;
    foreach (array('ja', 'tw', 'zh') as $lang){
        if (strpos($default_language, $lang) !== false){
            $char_is_word = true;
        }
    }

    if ( $char_is_word && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {
        $text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
        preg_match_all( '/./u', $text, $words_array );
        $words_array = array_slice( $words_array[0], 0, $num_words + 1 );
        $sep = '';
    } else {
        $words_array = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
        $sep = ' ';
    }

    if ( count( $words_array ) > $num_words ) {
        array_pop( $words_array );
        $text = implode( $sep, $words_array );
        $text = $text . $more;
    } else {
        $text = implode( $sep, $words_array );
    }

    return $text;
}
add_filter('wp_trim_words', 'etm_wp_trim_words', 100, 4);


/**
 * Use home_url in the https://www.peepso.com/ ajax front-end url so strings come back translated.
 *
 * @since 1.3.1
 *
 * @param array $data   Peepso data
 * @return array
 */
add_filter( 'peepso_data', 'etm_use_home_url_in_peepso_ajax' );
function etm_use_home_url_in_peepso_ajax( $data ){
    if ( is_array( $data ) && isset( $data['ajaxurl_legacy'] ) ){
        $data['ajaxurl_legacy'] = home_url( '/peepsoajax/' );
    }
    return $data;
}

/**
 * Compatibility with Peepso urls having extra / due their link builder not considering home urls having trailing slashes
 */
add_filter('peepso_get_page', 'etm_remove_peepso_double_slash', 10, 2);
function etm_remove_peepso_double_slash( $page, $name){

    // avoid accidentally replacing // from http://
    $page = str_replace('http://', 'http:/', $page );
    $page = str_replace('https://', 'https:/', $page );

    $page = str_replace('//', '/', $page );

    // place it back
    $page = str_replace('https:/', 'https://', $page );
    $page = str_replace('http:/', 'http://', $page );

    return $page;
};

/**
 * Filter ginger_iframe_banner and ginger_text_banner to use shortcodes so our conditional lang shortcode works.
 *
 * @since 1.3.1
 *
 * @param string $content
 * @return string
 */

add_filter('ginger_iframe_banner', 'etm_do_shortcode', 999 );
add_filter('ginger_text_banner', 'etm_do_shortcode', 999 );
function etm_do_shortcode($content){
    return do_shortcode(stripcslashes($content));
}


/**
 * Compatibility with WooCommerce PDF Invoices & Packing Slips
 * https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/
 *
 * @since 1.4.3
 *
 */
// fix attachment name in email
add_filter( 'wpo_wcpdf_filename', 'etm_woo_pdf_invoices_and_packing_slips_compatibility' );

// fix #etmgettext inside invoice pdf
add_filter( 'wpo_wcpdf_get_html', 'etm_woo_pdf_invoices_and_packing_slips_compatibility');
function etm_woo_pdf_invoices_and_packing_slips_compatibility($title){
    if ( class_exists( 'ETM_Translation_Manager' ) ) {
        return 	ETM_Translation_Manager::strip_gettext_tags($title);
    }
}

// fix font of pdf breaking because of str_get_html() call inside translate_page()
add_filter( 'etm_stop_translating_page', 'etm_woo_pdf_invoices_and_packing_slips_compatibility_dont_translate_pdf', 10, 2 );
function etm_woo_pdf_invoices_and_packing_slips_compatibility_dont_translate_pdf( $bool, $output ){
    if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'generate_wpo_wcpdf' ) {
        return true;
    }
    return $bool;
}

/**
 * Compatibility with WooCommerce PDF Invoices (woocommerce-ultimate-pdf-invoices)
 * https://www.welaunch.io/en/product/woocommerce-pdf-invoices/
 *
 * @since 1.4.3
 *
 */
add_filter( 'woocommerce_pdf_invoices_content', 'etm_woo_ultimate_pdf_invoices_compatibility');
add_filter( 'woocommerce_pdf_invoices_order_data', 'etm_woo_ultimate_pdf_invoices_data_compatibility');

function etm_woo_ultimate_pdf_invoices_compatibility($title){
    if ( class_exists( 'ETM_Translation_Manager' ) ) {
        return 	ETM_Translation_Manager::strip_gettext_tags($title);
    }
}

function etm_woo_ultimate_pdf_invoices_data_compatibility($data_array){
    if ( class_exists( 'ETM_Translation_Manager' ) ) {
        $data_array = array_map('ETM_Translation_Manager::strip_gettext_tags',$data_array );
    }
    return $data_array;
}

/**
 * Compatibility with WooCommerce PDF Catalog (woocommerce-pdf-catalog)
 * https://www.welaunch.io/en/product/woocommerce-pdf-catalog/
 *
 * @since 2.2.7
 *
 */
add_filter( 'etm_stop_translating_page', 'etm_woocommerce_pdf_catalog_compatibility_dont_translate_pdf', 10, 2 );
function etm_woocommerce_pdf_catalog_compatibility_dont_translate_pdf( $bool, $output ){
	if ( isset( $_REQUEST['pdf-catalog'] ) ) {
		return true;
	}
	return $bool;
}


/**
 * Compatibility with WooCommerce order notes
 *
 * When a new order is placed in secondary languages, in admin area WooCommerce->Orders->Edit Order, the right sidebar contains Order notes which can contain #etmst tags.
 *
 * @since 1.4.3
 */

// old orders
add_filter( 'woocommerce_get_order_note', 'etm_woo_notes_strip_etmst' );
// new orders
add_filter( 'woocommerce_new_order_note_data', 'etm_woo_notes_strip_etmst' );
function etm_woo_notes_strip_etmst( $note_array ){
    foreach ( $note_array as $item => $value ){
        $note_array[$item] = ETM_Translation_Manager::strip_gettext_tags( $value );
    }
    return $note_array;
}

/*
 * Compatibility with WooCommerce back-end display order shipping taxes
 */
add_filter('woocommerce_order_item_display_meta_key','etm_woo_data_strip_etmst');
add_filter('woocommerce_order_item_get_method_title','etm_woo_data_strip_etmst');
function etm_woo_data_strip_etmst( $data ){
    return ETM_Translation_Manager::strip_gettext_tags( $data );
}

/**
 * Compatibility with WooCommerce country list on checkout.
 *
 * Skip detection by translate-dom-changes of the list of countries
 *
 */
add_filter( 'etm_skip_selectors_from_dynamic_translation', 'etm_woo_skip_dynamic_translation' );
function etm_woo_skip_dynamic_translation( $skip_selectors ){
    if( class_exists( 'WooCommerce' ) ) {
        $add_skip_selectors = array( '#billing_country', '#shipping_country', '#billing_state', '#shipping_state', '#select2-billing_country-results',  '#select2-billing_state-results', '#select2-shipping_country-results', '#select2-shipping_state-results' );
        return array_merge( $skip_selectors, $add_skip_selectors );
    }
    return $skip_selectors;
}

/**
 * Prevent translation of names and addresses in WooCommerce emails.
 */
add_action( 'woocommerce_email_customer_details', 'etm_woo_prevent_address_from_translation_in_emails' );
function etm_woo_prevent_address_from_translation_in_emails(){
    add_filter( 'woocommerce_order_get_formatted_shipping_address', 'etm_woo_address_no_translate', 10, 3 );
    add_filter( 'woocommerce_order_get_formatted_billing_address', 'etm_woo_address_no_translate', 10, 3 );
}

function etm_woo_address_no_translate( $address, $raw_address, $order ){
    return empty( $address ) ? $address : '<span data-no-translation>' . $address . '</span>';
}

/**
 * Compatibility with WooCommerce product variation.
 *
 * Add span tag to woocommerce product variation name.
 *
 * Product variation name keep changes, but the prefix is the same. Wrap the prefix to allow translating that part separately.
 */
add_filter( 'woocommerce_product_variation_title', 'etm_woo_wrap_variation', 8, 4);
function etm_woo_wrap_variation($name, $product, $title_base, $title_suffix){
    $separator  = '<span> - </span>';
    return $title_suffix ? $title_base . $separator . $title_suffix : $title_base;
}


/**
 * Compatibility with Query Monitor
 *
 * Remove their HTML and reappend it after translate_page function finishes
 */
add_filter('etm_before_translate_content', 'etm_qm_strip_query_monitor_html', 10, 1 );
function etm_qm_strip_query_monitor_html( $output ) {

    $query_monitor = apply_filters( 'etm_query_monitor_begining_string', '<!-- Begin Query Monitor output -->' );
    $pos = strpos( $output, $query_monitor );

    if ( $pos !== false ){
        global $etm_query_monitor_string;
        $etm_query_monitor_string = substr( $output, $pos );
        $output = substr( $output, 0, $pos );

    }

    return $output;
}

add_filter( 'etm_translated_html', 'etm_qm_reappend_query_monitor_html', 10, 1 );
function etm_qm_reappend_query_monitor_html( $final_html ){
    global $etm_query_monitor_string;

    if ( isset( $etm_query_monitor_string ) && !empty( $etm_query_monitor_string ) ){
        $final_html .= $etm_query_monitor_string;
    }

    return $final_html;
}

// etmgettext tags don't get escaped because they add <small> tags through a regex.
add_filter( 'qm/output/title', 'etm_qm_strip_gettext', 100);
function etm_qm_strip_gettext( $data ){
    if ( is_array( $data ) ) {
        foreach( $data as $key => $value ){
            $data[$key] = etm_qm_strip_gettext($value);
        }
    }else {
        // remove small tags
        $data = preg_replace('(<(\/)?small>)', '', $data);
        // strip gettext (not needed, they are just numbers shown in admin bar anyway)
        $data = ETM_Translation_Manager::strip_gettext_tags( $data );
        // add small tags back the same way they do it in the filter 'qm/output/title'
        $data = preg_replace( '#\s?([^0-9,\.]+)#', '<small>$1</small>', $data );
    }
    return $data;
}

/**
 * Compatibility with SeedProd Coming Soon
 *
 * Manually include the scripts and styles if do_action('enqueue_scripts') is not called
 */
add_filter( 'etm_translated_html', 'etm_force_include_scripts', 10, 4 );
function etm_force_include_scripts( $final_html, $ETM_LANGUAGE, $language_code, $preview_mode ){
    if ( $preview_mode ){
        $etm = ETM_eTranslation_Multilingual::get_etm_instance();
        $translation_render = $etm->get_component( 'translation_render' );
        $etm_data = $translation_render->get_etm_data();

        $scripts_and_styles = apply_filters('etm_editor_missing_scripts_and_styles', array(
            'jquery'                        => "<script type='text/javascript' src='" . includes_url( '/js/jquery/jquery.js' ) . "'></script>",
            'etm-iframe-preview-script.js'  => "<script type='text/javascript' src='" . ETM_PLUGIN_URL . "assets/js/etm-iframe-preview-script.js'></script>",
            'etm-translate-dom-changes.js'  => "<script>etm_data = '" . addslashes(json_encode($etm_data) ) . "'; etm_data = JSON.parse(etm_data);</script><script type='text/javascript' src='" . ETM_PLUGIN_URL . "assets/js/etm-translate-dom-changes.js'></script>",
            'etm-preview-iframe-style-css'  => "<link rel='stylesheet' id='etm-preview-iframe-style-css'  href='" . ETM_PLUGIN_URL . "assets/css/etm-preview-iframe-style.css' type='text/css' media='all' />",
            'dashicons'                     => "<link rel='stylesheet' id='dashicons-css'  href='" . includes_url( '/css/dashicons.min.css' ) . "' type='text/css' media='all' />"
        ));

        $missing_script = '';
        foreach($scripts_and_styles as $key => $value ){
            if ( strpos( $final_html, $key ) === false ){
                $missing_script .= $value;
            }
        }

        if ( $missing_script !== '' ){
            $html = eTranslationMultilingual\str_get_html( $final_html, true, true, ETM_DEFAULT_TARGET_CHARSET, false, ETM_DEFAULT_BR_TEXT, ETM_DEFAULT_SPAN_TEXT );
            if ( $html === false ) {
                return $final_html;
            }

            $body = $html->find( 'body', 0 );
            if ( $body ) {
                $body->innertext = $body->innertext . $missing_script;
            }

            $final_html = $html->save();
        }
    }
    return $final_html;
}

/*
 * Compatibility with plugins sending Gettext strings in requests such as Cartflows
 *
 * Strip gettext wrappings from the requests made from http->post()
 */
// Strip of gettext wrappings all the values of the body request array
add_filter( 'http_request_args', 'etm_strip_etmst_from_requests', 10, 2 );
function etm_strip_etmst_from_requests($args, $url){
    if( is_array( $args['body'] ) ) {
        array_walk_recursive( $args['body'], 'etm_array_walk_recursive_strip_gettext_tags' );
    }else{
        $args['body'] = ETM_Translation_Manager::strip_gettext_tags( $args['body'] );
    }
    return $args;
}
function etm_array_walk_recursive_strip_gettext_tags( &$value ){
    $value = ETM_Translation_Manager::strip_gettext_tags( $value );
}

// Strip of gettext wrappings the customer_name and customer_email keys. Found in WC Stripe and Cartflows
add_filter( 'wc_stripe_payment_metadata', 'etm_strip_request_metadata_keys' );
function etm_strip_request_metadata_keys( $metadata ){
    foreach( $metadata as $key => $value ) {
        $stripped_key = ETM_Translation_Manager::strip_gettext_tags( $key );
        if ( $stripped_key != $key ) {
            $metadata[ $stripped_key ] = $value;
            unset( $metadata[ $key ] );
        }
    }
    return $metadata;
}

/**
 * Compatibility with NextGEN Gallery
 *
 * They start an output buffer at init -1 (before ours at init 0). They print footer scripts after we run translate_page,
 * resulting in outputting scripts that won't be stripped of etmst etm-gettext wrappings.
 * This includes WooCommerce Checkout scripts, resulting in etmst wrappings around form fields like Street Address.
 * Another issue is that translation editor is a blank page.
 *
 * We cannot move their hook to priority 1 because we do not have access to the object that gets hooked is not retrievable so we can't call remove_filter()
 * Also we cannot simply disable ngg using run_ngg_resource_manager hook because we would be disabling features of their plugin.
 *
 * So the only solution that works is to move our hook to -2.
 */
add_filter( 'etm_start_output_buffer_priority', 'etm_nextgen_compatibility' );
function etm_nextgen_compatibility( $priority ){
    if ( class_exists( 'C_Photocrati_Resource_Manager' ) ) {
        return '-2';
    }
    return $priority;
}

/**
 * Compatibility with NextGEN Gallery
 *
 * This plugin is adding wp_footer forcefully in a shutdown hook and appends it to "</body>" which bring up admin bar in translation editor.
 *
 * This filter prevents ngg from hooking the filters to alter the html.
 */
add_filter( 'run_ngg_resource_manager', 'etm_nextgen_disable_nextgen_in_translation_editor');
function etm_nextgen_disable_nextgen_in_translation_editor( $bool ){
    if ( isset( $_REQUEST['etm-edit-translation'] ) && sanitize_text_field( $_REQUEST['etm-edit-translation'] ) === 'true' ) {
        return false;
    }
    return $bool;
}

/**
 * Compatibility with WooCommerce added to cart message
 *
 * Makes sure title of product is translated.
 *
 * The title of product is added through sprintf %s of a Gettext.
 *
 */
add_filter( 'the_title', 'etm_woo_translate_product_title_added_to_cart', 10, 2 );
function etm_woo_translate_product_title_added_to_cart( ...$args ){
    // fix themes that don't implement the_title filter correctly. Works on PHP 5.6 >.
    // Implemented this because users we getting this error frequently.
    if( isset($args[0])){
        $title = $args[0];
    } else {
        $title = '';
    }


    if( class_exists( 'WooCommerce' ) ){
        if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);//set a limit if it is supported to improve performance
        }
        else{
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        $list_of_functions = apply_filters( 'etm_woo_translate_title_before_translate_page', array( 'wc_add_to_cart_message' ) );
        if( !empty( $callstack_functions ) ) {
            foreach ( $callstack_functions as $callstack_function ) {
                if ( in_array( $callstack_function['function'], $list_of_functions ) ) {
                    $etm = ETM_eTranslation_Multilingual::get_etm_instance();
                    $translation_render = $etm->get_component( 'translation_render' );
                    $title = $translation_render->translate_page($title);
                    break;
                }
            }
        }
    }
    return $title;
}

/**
 * Compatibility with WooCommerce "remove from cart" action
 *
 * In some cases (eg for Taiwanese) the product name and double quotes &ldquo &rdquo HTML entities
 * were translated/parsed wrongly.
 * We provide a fix by adding spaces between the quotes and product name
 *
 */

if( class_exists( 'WooCommerce' ) ) {
	add_filter( 'woocommerce_cart_item_removed_title', 'etm_woo_fix_product_remove_from_cart_notice', 10, 2 );

	function etm_woo_fix_product_remove_from_cart_notice($message, $cart_item){
		$product = wc_get_product( $cart_item['product_id'] );
		if ($product){
			$message =  sprintf( _x( '&ldquo; %s &rdquo;', 'Item name in quotes', 'woocommerce' ), $product->get_name() ); //phpcs:ignore
		}
		return $message;
	}
}

/**
 * Compatibility with WooTour plugin
 *
 * They replace spaces (" ") with \u0020, after we apply #etmst and because we don't strip them it breaks html
 */
add_action('init', 'etm_wootour_add_gettext_filter');
function etm_wootour_add_gettext_filter(){
    if ( class_exists( 'WooTour_Booking' ) ){
        add_filter('gettext', 'etm_wootour_exclude_gettext_strings', 1000, 3 );
    }
}
function etm_wootour_exclude_gettext_strings($translation, $text, $domain){
    if ( $domain == 'woo-tour' ){
        if ( in_array( $text, array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ) ) ){
            return ETM_Translation_Manager::strip_gettext_tags( $translation );
        }
    }
    return $translation;
}

/**
 * Compatibility with WooCommerce cart product name translation
 * For products with the character - in the product name.
 *
 * There is a difference between the rendered – and –. Two different characters.
 * Somehow in the cart is the minus one, in the shop listing is the longer separator.
 * Make the cart contain the same type of character which is obtained using get_the_title.
 */
add_filter( 'woocommerce_cart_item_name', 'etm_woo_cart_item_name', 8, 3 );
function etm_woo_cart_item_name( $product_name, $cart_item, $cart_item_key ){
    if ( isset( $cart_item['product_id'] ) ){
        $title = get_the_title( $cart_item['product_id'] );
        if ( !empty( $title )){
            if ( strpos( $product_name, '</a>' ) ) {
                preg_match_all('~<a(.*?)href="([^"]+)"(.*?)>~', $product_name, $matches);
                $product_name = sprintf( '<a href="%s">%s</a>', esc_url( $matches[2][0] ), $title );
            }
        }
    }
    return $product_name;
}

/**
 * Compatibility with WooCommerce PDF Invoices & Packing Slips
 *
 * Translate product name and variation (meta) in pdf invoices.
 */
add_filter( 'wpo_wcpdf_order_item_data', 'etm_woo_wcpdf_translate_product_name', 10, 3 );
function etm_woo_wcpdf_translate_product_name( $data, $order, $type ){
    if ( isset( $data['name'] ) ) {
        $etm = ETM_eTranslation_Multilingual::get_etm_instance();
        $translation_render = $etm->get_component('translation_render');
        remove_filter( 'etm_stop_translating_page', 'etm_woo_pdf_invoices_and_packing_slips_compatibility_dont_translate_pdf', 10 );
        $data['name'] = $translation_render->translate_page($data['name']);
        if ( isset( $data['meta'] ) ) {
            $data['meta'] = $translation_render->translate_page($data['meta']);
        }
        add_filter( 'etm_stop_translating_page', 'etm_woo_pdf_invoices_and_packing_slips_compatibility_dont_translate_pdf', 10, 2 );
    }
    return $data;
}

/**
 * Compatibility with WooCommerce Checkout Add-Ons plugin
 *
 * Exclude name of "paid add-on" item from being run through gettext.
 *
 * No other filters were found. Advanced settings strip meta did not work.
 * It's being added through WC->add_fee and inserted directly in db in custom table.
 */
add_action( 'woocommerce_cart_calculate_fees', 'etm_woo_checkout_add_ons_filter_etmstr', 10, 2);
function etm_woo_checkout_add_ons_filter_etmstr(){
    if ( class_exists('WC_Checkout_Add_Ons_Frontend') ) {
        add_filter('etm_skip_gettext_processing', 'etm_woo_checkout_exclude_strings', 1000, 4);
    }
}

function etm_woo_checkout_exclude_strings( $return, $translation, $text, $domain) {
    if ( $domain === 'woocommerce-checkout-add-ons' ) {
        $add_ons = wc_checkout_add_ons()->get_add_ons();
        foreach ($add_ons as $add_on) {
            if ( $add_on->name === $text)
                return true;
        }
    }
    return $return;
}

/**
 * Compatibility with WooCommerce Fondy Payment gateway
 */
add_action('init', 'etm_woo_fondy_payment_gateway_add_gettext_filter');
function etm_woo_fondy_payment_gateway_add_gettext_filter(){
    if ( class_exists( 'WC_fondy' ) ){
        add_filter('gettext', 'etm_woo_fondy_payment_gateway_exclude_gettext_strings', 1000, 3 );
    }
}

function etm_woo_fondy_payment_gateway_exclude_gettext_strings($translation, $text, $domain){
    if ( $domain == 'fondy-woocommerce-payment-gateway' && $text == 'Order: ' ){
        return ETM_Translation_Manager::strip_gettext_tags( $translation );
    }
    return $translation;
}

/**
 * Compatibility with Woocommerce Product Filters plugin
 * They stop the buffering at priority -150 and that leaves #etmst style tags before we get to remove them
 *
 * The caveat to removing or adding a foreign filter is that it can be done via
 * a) static class call or b) through an object instance
 *
 * In this case we obtained access to global objects set by the WCPF plugin
 * and their public methods.
 */

add_action( 'init', 'etm_woo_product_filters', 10 );

function etm_woo_product_filters(){
	if( isset( $GLOBALS['wcpf_plugin'] ) && class_exists( 'WooCommerce_Product_Filter_Plugin\Filters' ) ){
		$wcpf_plugin = $GLOBALS['wcpf_plugin'];
		$component_register = $wcpf_plugin->get_component_register();
		$filters = $component_register->get('Filters');
		$hook_manager = $filters->get_hook_manager();
		$hook_manager->remove_action( 'shutdown', 'end_of_buffering', -150 );
		$hook_manager->add_action( 'shutdown', 'end_of_buffering', 100 );
	}
}

/**
 * Compatibility with Elementor Popups Links
 *
 * The url is urlencoded so we add the language to it but we shouldn't.
 *
 */
add_filter('etm_skip_url_for_language', 'etm_skip_elementor_popup_action_from_url_converter', 10, 2);
function etm_skip_elementor_popup_action_from_url_converter($value, $url){
	if(strpos($url, '%23elementor-action') !== false){
		return true;
	}
	return $value;
}

/**
 * Strip gettext wrapping from get_the_date function parameter $d
 */
add_filter('get_the_date','etm_strip_gettext_from_get_the_date', 1, 3);
function etm_strip_gettext_from_get_the_date($the_date, $d = NULL, $post = NULL){
	if ( $d === NULL || $post === NULL ){
		return $the_date;
	}

    $d = ETM_Translation_Manager::strip_gettext_tags( $d );
    $post = get_post( $post );

    if ( ! $post ) {
        return false;
    }

    if ( '' == $d ) {
        $the_date = get_post_time( get_option( 'date_format' ), false, $post, true );
    } else {
        $the_date = get_post_time( $d, false, $post, true );
    }

    return $the_date;
}


/**
 * Compatibility with Affiliate Theme
 * It's adding parameters found in the filter forms automatically, braking the query.
 * eTranslation Multilingual adds the etm-form-language for other reasons. So we need to remove it in this case.
 * https://affiliatetheme.io
 *
 */
add_filter('at_set_product_filter_query', 'etm_remove_lang_param_from_query');
function etm_remove_lang_param_from_query($args){

	if ( isset( $args['meta_query'] ) && is_array( $args['meta_query']) ){
		foreach($args['meta_query'] as $key => $value){
			if ($value['key'] == 'etm-form-language'){
				unset( $args['meta_query'][$key] );
			}
		}
		$args['meta_query'] = array_values($args['meta_query']);
	}

	return $args;
}


/**
 * Set user prefered language to the language he was present on new user creation.
 * Only set it if an existing locale isn't set already, in case the registration comes from a form that sets the locale manually.
 *
 */
add_action( 'user_register', 'etm_add_user_prefered_language', 10 );
function etm_add_user_prefered_language($user_id) {
	global $ETM_LANGUAGE;
	if ( ! empty( $ETM_LANGUAGE ) ) {
		$user_locale = get_user_meta( $user_id, 'locale', true );
		if ( empty( $user_locale ) ) {
			update_user_meta( $user_id, 'locale', $ETM_LANGUAGE );
		}
	}
}

/*
 * Dflip Compatibility
 * With Secondary Language First, it deferes jquery and scripts don't load on the Elementor Editor.
 * Not sure exactly what's causing. I assume it's because Elementor loads with Ajax certain elements and that comes back broken somehow.
 */
add_action('wp_enqueue_scripts', 'etm_remove_dflip_defer_script', 9999);
function etm_remove_dflip_defer_script(){
	if(class_exists('DFlip')){
		$dflip_instance = DFlip::get_instance();
		remove_filter( 'script_loader_tag', array( $dflip_instance, 'add_defer_attribute' ), 10, 2 );
	}
}

/**
 * Ignore WooCommerce display_name gettext
 * _x( '%1$s %2$s', 'display name', 'woocommerce' ) || wordpress\wp-content\plugins\woocommerce\includes\class-wc-customer.php
 * _x( '%1$s %2$s', 'Display name based on first name and last name')   || wordpress\wp-includes\user.php
 * This will insert etmstr strings in the database. So just ignore it.
 *
 */
add_filter('etm_skip_gettext_processing', 'etm_exclude_woo_display_name_gettext', 2000, 4 );
function etm_exclude_woo_display_name_gettext ( $return, $translation, $text, $domain ){
	if($text == '%1$s %2$s' && $domain == 'woocommerce'){
		return true;
	}

	if($text == '%1$s %2$s' && $domain == 'default'){
		return true;
	}

	return $return;
}

/** Compatibility with superfly menu plugin.
 *
 *  Moving their script later so that dynamic translation detects their strings.
 */
add_action('wp_head','etm_superfly_change_menu_loading_hook', 5);
function etm_superfly_change_menu_loading_hook(){
    if ( remove_action ('wp_head', 'sf_dynamic') ){
        add_action ('wp_print_footer_scripts', 'sf_dynamic', 20);
    }
}

/**
 * Compatibility with Yoast SEO Canonical URL and Opengraph URL
 * Yoast places the canonical wrongly and it's not processed correctly.
 */
add_filter( 'wpseo_canonical', 'etm_wpseo_canonical_compat', 99999, 2);
function etm_wpseo_canonical_compat( $canonical, $presentation_class = null ){
    global $ETM_LANGUAGE;
    $etm           = ETM_eTranslation_Multilingual::get_etm_instance();
    $url_converter = $etm->get_component( 'url_converter' );
    $canonical     = $url_converter->get_url_for_language( $ETM_LANGUAGE, $canonical, '' );

    return $canonical;
};

add_filter( 'wpseo_opengraph_url', 'etm_opengraph_url', 99999 );
function etm_opengraph_url( $url ) {
	global $ETM_LANGUAGE;
	$etm = ETM_eTranslation_Multilingual::get_etm_instance();
	$url_converter = $etm->get_component( 'url_converter' );
	$url = $url_converter->get_url_for_language($ETM_LANGUAGE, $url, '');

	return $url;
}

/**
 * Compatibility with Oxygen plugin
 *
 * Improves stylesheet loading time by disabling gettext and regular text detection for pages loaded with xlink=css
 */
add_action( 'etm_before_running_hooks', 'etm_oxygen_remove_gettext_hooks', 10, 1 );
function etm_oxygen_remove_gettext_hooks( $etm_loader ) {
    if ( isset( $_REQUEST['xlink'] ) && $_REQUEST['xlink'] === 'css' ) {
        $etm                 = ETM_eTranslation_Multilingual::get_etm_instance();
        $gettext_manager = $etm->get_component( 'gettext_manager' );
        $translation_render = $etm->get_component( 'translation_render' );
        $etm_loader->remove_hook( 'init', 'create_gettext_translated_global', $gettext_manager );
        $etm_loader->remove_hook( 'init', 'initialize_gettext_processing', $gettext_manager );
        $etm_loader->remove_hook( 'shutdown', 'machine_translate_gettext', $gettext_manager );
        $etm_loader->remove_hook( 'init', 'start_output_buffer', $translation_render );
        $etm_loader->remove_hook( 'the_title', 'wrap_with_post_id', $translation_render );
        $etm_loader->remove_hook( 'the_content', 'wrap_with_post_id', $translation_render );
    }
}

/**
 * Compatibility with Oxygen plugin for search
 * Basically they use shortcodes to output content so we wrap the shortcode output for certain shortcodes
 */
if( function_exists('ct_is_show_builder') ) {
    add_filter('do_shortcode_tag', 'tp_oxygen_search_compatibility', 10, 4);
    function tp_oxygen_search_compatibility($output, $tag, $attr, $m){
    	// we're skiping the oxygen $tag as that one represents a dynamic shortcode based on custom fields. At times it contains images, links, numbers. Rarely we see actual content.
    	if( $tag === 'ct_headline' || $tag === 'ct_text_block' ) {
            global $post, $ETM_LANGUAGE;

            if (empty($post->ID))
                return $output;

            //we try to wrap only the actual content of the post
            if (!is_main_query())
                return $output;

            $etm = ETM_eTranslation_Multilingual::get_etm_instance();
            $etm_settings = $etm->get_component( 'settings' );
            $settings = $etm_settings->get_settings();

            if ($ETM_LANGUAGE !== $settings['default-language']) {
                if (is_singular() && !empty($post->ID)) {
                    $output = "<etm-post-container data-etm-post-id='" . $post->ID . "'>" . $output . "</etm-post-container>";//changed " to ' to not break cases when the filter is applied inside an html attribute (title for example)
                }
            }
        }

        return $output;
    }

    /**
     * Disable ETM when the Oxygen Builder is being loaded
     */
    add_filter( 'etm_stop_translating_page', 'etm_oxygen_disable_etm_in_builder');
    function etm_oxygen_disable_etm_in_builder(){

        if( defined( 'SHOW_CT_BUILDER' ) )
            return true;

    }

    /**
     * Hide Floating Language Switcher when the Oxygen is shown
     */
    add_filter( 'etm_floating_ls_html', 'etm_page_builder_compatibility_disable_language_switcher' );
    function etm_page_builder_compatibility_disable_language_switcher( $html ){

        if( isset( $_GET['ct_builder'] ) && $_GET['ct_builder'] == 'true' )
            return '';

        return $html;

    }

}

if( function_exists( 'ct_is_show_builder' ) || defined( 'FL_BUILDER_VERSION' ) ){

    /**
     * Used to redirect Oxygen Builder front-end to the default language.
     * Hooked before ETM_Language_Switcher::redirect_to_correct_language() so we don't redirect twice
     */
    add_action( 'template_redirect', 'etm_page_builder_compatibility_redirect_to_default_language', 10 );
    function etm_page_builder_compatibility_redirect_to_default_language(){

        if( !is_admin() && ( ( isset( $_GET['ct_builder'] ) && $_GET['ct_builder'] == 'true' ) || isset( $_GET['fl_builder'] ) ) ){

            $etm           = ETM_eTranslation_Multilingual::get_etm_instance();
            $url_converter = $etm->get_component('url_converter');
            $settings      = ( new ETM_Settings() )->get_settings();

            $current_url  = $url_converter->cur_page_url();
            $current_lang = $url_converter->get_lang_from_url_string( $current_url );

            if( ( $current_lang == null && $settings['add-subdirectory-to-default-language'] == 'yes' ) || ( $current_lang != null && $current_lang != $settings['default-language'] ) ){
                $link_to_redirect = $url_converter->get_url_for_language( $settings['default-language'], null, '' );

                if( $link_to_redirect != $current_url ){
                    wp_redirect( $link_to_redirect, 301 );
                    exit;
                }

            }

        }

    }

    /**
     * Disable automatic language redirect when the Oxygen or Beaver Builders are showing
     */
    add_filter( 'etm_ald_enqueue_redirecting_script', 'etm_ald_dont_redirect_inside_page_builders');
    function etm_ald_dont_redirect_inside_page_builders( $enqueue_redirecting_script ){
        if( ( isset( $_GET['ct_builder'] ) && $_GET['ct_builder'] == 'true' ) || isset( $_GET['fl_builder'] ) ){
            return false;
        }
        return $enqueue_redirecting_script;
    }
}

/**
 * Compatibility with Brizy editor
 */
add_filter( 'etm_enable_dynamic_translation', 'etm_brizy_disable_dynamic_translation' );
function etm_brizy_disable_dynamic_translation( $enable ){
    if ( isset( $_REQUEST['brizy-edit-iframe'] ) ){
        return false;
    }
    return $enable;
}

/**
 * Compatibility with Brizy PRO menu, the language switcher inside the menu does not work fully yet
 * Compatibility with Brizy assets loading with language slug in url (the 'process' function)
 */
if( defined( 'BRIZY_PRO_VERSION' ) || defined( 'BRIZY_VERSION' ) ){
    add_filter( 'etm_home_url', 'etm_brizy_menu_pro_compatibility', 10, 5 );
    function etm_brizy_menu_pro_compatibility( $new_url, $abs_home, $ETM_LANGUAGE, $path, $url ){
        if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);//set a limit if it is supported to improve performance
        }
        else{
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        $list_of_functions = array( 'restoreSiteUrl', 'process' );
        if( !empty( $callstack_functions ) ) {
            foreach ( $callstack_functions as $callstack_function ) {
                if ( in_array( $callstack_function['function'], $list_of_functions ) ) {
                    return $url;
                }
            }
        }

        return $new_url;
    }
}


/**
 * Compatibility with woocommerce-pdf-vouchers plugin, removed language from download link of the vouchers
 */
if( defined( 'WOO_VOU_PLUGIN_VERSION' ) ){
    add_filter( 'etm_home_url', 'etm_woocommerce_pdf_vouchers_download_file_compatibility', 10, 5 );
    function etm_woocommerce_pdf_vouchers_download_file_compatibility( $new_url, $abs_home, $ETM_LANGUAGE, $path, $url ){
        if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);//set a limit if it is supported to improve performance
        }
        else{
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        $list_of_functions = array( 'get_item_download_url' );
        if( !empty( $callstack_functions ) ) {
            foreach ( $callstack_functions as $callstack_function ) {
                if ( in_array( $callstack_function['function'], $list_of_functions ) ) {
                    return $url;
                }
            }
        }

        return $new_url;
    }
}


/**
 * Compatibility with Advanced WooCommerce Search 1/2
 * Returns post ids where searched key matches translated version of post.
 */

add_filter( 'aws_search_results_products_ids', 'etm_aws_search_results_products_ids', 10, 2 );
function etm_aws_search_results_products_ids(  $posts_ids, $s ){
    global $ETM_LANGUAGE;
    $etm = ETM_eTranslation_Multilingual::get_etm_instance();
    $etm_settings = $etm->get_component( 'settings' );
    $settings = $etm_settings->get_settings();

    if ( $ETM_LANGUAGE !== $settings['default-language'] ) {
        $etm_search = $etm->get_component( 'search' );
        $search_result_ids = $etm_search->get_post_ids_containing_search_term($s, null);

        if (!empty ( $search_result_ids) ) {
            return $search_result_ids;
        }
    }

    return $posts_ids;
}

/**
 * Compatibility with Advanced WooCommerce Search 2/2
 * Solves issue with caching results in a different language
 */
add_filter( 'wpml_current_language', 'etm_aws_current_language' );
function etm_aws_current_language( $lang ) {
    if ( class_exists( 'AWS_Main' ) ) {
        global $ETM_LANGUAGE;
        $lang = $ETM_LANGUAGE;
    }
    return $lang;
}
/**
 * Compatibility with thrive Arhitect plugin which does a "nice" little trick with remove_all_filters( 'template_include' ); so we need to stop that or else it will not load our translation editor
 */
add_filter('tcb_allow_landing_page_edit', 'etm_thrive_arhitect_compatibility');
add_filter('tcb_is_editor_page', 'etm_thrive_arhitect_compatibility');//this is for Thrive theme
function etm_thrive_arhitect_compatibility($bool)    {
    if (isset($_REQUEST['etm-edit-translation']))
        $bool = false;

    return $bool;
}
// do not redirect the URL's that are used inside Thrive Architect Editor
add_filter( 'etm_allow_language_redirect', 'etm_thrive_no_redirect_in_editor', 10, 3 );
function etm_thrive_no_redirect_in_editor( $allow_redirect, $needed_language, $current_page_url ){
	if ( strpos($current_page_url, 'tve=true&tcbf')!== false ){
		return false;
	}
	return $allow_redirect;
};
// skip the URL's that are used inside Thrive Architect Editor as they are stripped of parameters in certain cases and the editor isn't working.
add_filter('etm_skip_url_for_language', 'etm_thrive_skip_language_in_editor', 10, 2);
function etm_thrive_skip_language_in_editor($skip, $url){
	if ( strpos($url, 'tve=true&tcbf') !== false ){
		return true;
	}
	return $skip;
}

/**
 * Compatibility with the RECON gateway for woocommerce. We must not send the "etm-form-language" hidden field in the post request to the gateway
 */
if( class_exists('WC_Gateway_RECON') ) {
    add_filter('etm_form_inputs', 'etm_recon_gateway_compatibility', 10, 4);
    function etm_recon_gateway_compatibility($input, $etm_language, $slug, $row)
    {
        if (isset($row->attr['name']) && $row->attr['name'] === 'checkout') {
            $input = '';
        }
        return $input;
    }
}


/**
 * Compatibility with Classified Listing plugin Search in secondary language
 */
// do not return inline autocomplete because when clicking the results, the input is filled with original title instead of translated
add_filter( 'rtcl_inline_search_autocomplete_args', 'etm_rtcl_autocomplete_search_results', 10, 2 );
function etm_rtcl_autocomplete_search_results( $args, $request ){
    global $ETM_LANGUAGE;
    $etm = ETM_eTranslation_Multilingual::get_etm_instance();
    $etm_settings = $etm->get_component( 'settings' );
    $settings = $etm_settings->get_settings();

    if ( $ETM_LANGUAGE !== $settings['default-language'] ) {
        $args['post__in'] = array('1');
        return $args;
    }

    return $args;
}

// Otherwise etm-post-container is not added
add_action( 'wp_body_open', 'etm_overrule_main_query_condition', 10, 2 );
function etm_overrule_main_query_condition(){
    if ( class_exists('Rtcl') ) {
        add_filter( 'etm_wrap_with_post_id_overrule', '__return_false' );
    }
}

// Otherwise etm-post-container is stripped
add_filter( 'wp_kses_allowed_html', 'etm_prevent_kses_from_stripping_etm_post_container', 10, 2 );
function etm_prevent_kses_from_stripping_etm_post_container( $allowedposttags, $context  ) {
    if ( class_exists('Rtcl') && $context === 'post' ){
        $allowedposttags['etm-post-container'] = array( 'data-etm-post-id' => true );
    }
    return $allowedposttags;
}

// Filter search results to show secondary language results
add_action('rtcl_listing_query', 'etm_rtcl_search_results', 10, 2);
function etm_rtcl_search_results ($q, $t){
    if ( empty( $q->get('s')) ){
        return;
    }
    global $ETM_LANGUAGE;
    $etm = ETM_eTranslation_Multilingual::get_etm_instance();
    $etm_settings = $etm->get_component( 'settings' );
    $settings = $etm_settings->get_settings();

    if ( $ETM_LANGUAGE !== $settings['default-language'] ) {
        $etm_search = $etm->get_component( 'search' );
        $search_result_ids = $etm_search->get_post_ids_containing_search_term($q->get('s'), null);
        $q->set('s', '');
        if ( empty($search_result_ids)){
            $search_result_ids = array(0);
        }
        $q->set('post__in', $search_result_ids );
    }
}

/*
 * Compatibility with AddToAny Share Buttons
 *
 * Skip detection by translate-dom-changes of the url change when hitting the share button
 *
 */
add_filter( 'etm_skip_selectors_from_dynamic_translation', 'etm_add_to_any_skip_dynamic_translation' );
function etm_add_to_any_skip_dynamic_translation( $skip_selectors ){
    if( function_exists( 'A2A_SHARE_SAVE_init' ) ) {
        $add_skip_selectors = array( '.addtoany_list' );
        return array_merge( $skip_selectors, $add_skip_selectors );
    }
    return $skip_selectors;
}

/*
 * Compatibility with Uncode theme menu on mobile
 *
 * Skip detection by translate-dom-changes of the url change when hitting the menu
 *
 */
add_filter( 'etm_skip_selectors_from_dynamic_translation', 'etm_uncode_skip_dynamic_translation' );
function etm_uncode_skip_dynamic_translation( $skip_selectors ){
    if( function_exists( 'uncode_setup' ) ) {
        $add_skip_selectors = array( '.menu-horizontal .menu-smart' );
        return array_merge( $skip_selectors, $add_skip_selectors );
    }
    return $skip_selectors;
}

/*
 * Compatibility with PDF Embedder Premium Secure
 *
 * Skips link processing if ?pdfemb-serveurl is in the url
 *
 */
// first we need to disable adding the language to home_url() because the plugin is using it to construct a url.
add_filter( 'etm_home_url', 'etm_skip_home_url_processing_for_pdfemb_server_url', 10, 5 );
function etm_skip_home_url_processing_for_pdfemb_server_url(  $new_url, $abs_home, $ETM_LANGUAGE, $path, $url ){
	if( class_exists( 'core_pdf_embedder' ) ){
		$callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		$list_of_functions =  array( 'modify_pdfurl' ) ;
		if( !empty( $callstack_functions ) ) {
			foreach ( $callstack_functions as $callstack_function ) {
				if ( in_array( $callstack_function['function'], $list_of_functions ) ) {
					$new_url = $url;
					break;
				}
			}
		}
	}
	return $new_url;
}
// and after that we need to make sure we're not adding the language when we process the url's in the page.
add_filter( 'etm_skip_url_for_language', 'etm_skip_link_processing_for_pdfemb_server_url', 10, 2 );
function etm_skip_link_processing_for_pdfemb_server_url( $skip, $url ){
	if( strpos($url, '?pdfemb-serveurl') !== false ) {
		$skip = true;
	}
	return $skip;
}

/**
 * Add compatibility with blockquote tweet button in elementor
 * if the quote has one parameter it will be automatically translated, if not then you need to use conditional language shortcodes
 */
add_filter( 'wp_parse_str', 'etm_elementor_blockquote_translate_tweet_button' );
function etm_elementor_blockquote_translate_tweet_button( $array ){
    if( array_key_exists( 'text', $array ) ){

        if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);//set a limit if it is supported to improve performance
        }
        else{
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        if( !empty( $callstack_functions ) ) {
            foreach ($callstack_functions as $callstack_function) {
                if ( $callstack_function['function'] === 'render_content' ) {
                    //enable conditional language shortcode
                    $array['text'] = do_shortcode( $array['text'] );

                    //try to eliminate the author from the text before we try to translate it
                    $tweet_link_text = $array['text'];
                    $tweet_link_text = explode( ' — ', $tweet_link_text );
                    if( count( $tweet_link_text ) > 1 ){
                        $quote_author = array_pop( $tweet_link_text );
                    }
                    $tweet_link_text = implode( ' — ', $tweet_link_text );

                    //try and translate the text
                    $etm = ETM_eTranslation_Multilingual::get_etm_instance();
                    $translation_render = $etm->get_component( 'translation_render' );
                    $array['text'] = $translation_render->translate_page($tweet_link_text);

                    //add author if it was eliminated
                    if(!empty($quote_author) )
                        $array['text'] = $array['text'] . ' — ' .  $translation_render->translate_page($quote_author);

                    break;
                }
            }
        }

    }
    return $array;
}

/**
 * Add compatibility with blockquote tweet button in elementor pro that had the link broken, it doubled the language in the url
 */
if( function_exists('elementor_pro_load_plugin') ) {
    add_filter('etm_home_url', 'etm_elementor_blockquote_tweet_button_url', 10, 5);
    function etm_elementor_blockquote_tweet_button_url($new_url, $abs_home, $ETM_LANGUAGE, $path, $url)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);//set a limit if it is supported to improve performance
        } else {
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        $list_of_functions = array('render');
        if (!empty($callstack_functions)) {
            foreach ($callstack_functions as $callstack_function) {
                if (in_array($callstack_function['function'], $list_of_functions) && isset($callstack_function['class']) && $callstack_function['class'] === 'ElementorPro\Modules\Blockquote\Widgets\Blockquote') {
                    return $url;
                }
            }
        }

        return $new_url;
    }
}

/**
 * Add compatibility with Elementor so we allow conditional shortcodes in post excerpt
 * this allows twitter button to have a translated text
 */
if( defined('ELEMENTOR_VERSION') ) {
    add_filter('the_post', 'etm_elementor_translate_tweet_button_excerpt');
    function etm_elementor_translate_tweet_button_excerpt($post){
        if (!empty($post->post_excerpt)) {
            $post->post_excerpt = do_shortcode($post->post_excerpt);
        }

        return $post;
    }
}

/**
 * Add current-menu-item css class to menu items in WP Nav Menu
 *
 * Don't add them to language switcher items.
 * Always adds them to secondary languages.
 * Add them to default language if Use subdirectory is set to Yes
 */
add_filter('wp_nav_menu_objects', 'etm_add_current_menu_item_css_class');
function etm_add_current_menu_item_css_class( $items ){
    global $ETM_LANGUAGE;
    $etm = ETM_eTranslation_Multilingual::get_etm_instance();
    $url_converter = $etm->get_component('url_converter');
    $etm_settings = $etm->get_component( 'settings' );
    $settings = $etm_settings->get_settings();

    add_filter('pre_get_posts', 'etm_the_event_calendar_set_query_to_true', 2, 1);

    foreach( $items as $item ){
        if ( !( $ETM_LANGUAGE === $settings['default-language'] && isset( $settings['add-subdirectory-to-default-language']) && $settings['add-subdirectory-to-default-language'] !== 'yes'  ) &&
            !in_array( 'current-menu-item', $item->classes ) && !in_array( 'menu-item-object-language_switcher', $item->classes ) && ( !empty($item->url) && $item->url !== '#')
        ){
            $url_for_language = $url_converter->get_url_for_language( $ETM_LANGUAGE, $item->url );
            $url_for_language = strpos( $url_for_language, '#' ) ? substr( $url_for_language, 0, strpos( $url_for_language, '#' ) ) : $url_for_language;
            $cur_page_url = set_url_scheme( untrailingslashit( $url_converter->cur_page_url() ) );

            if ( untrailingslashit( $url_for_language ) == untrailingslashit( $cur_page_url ) ){
                $item->classes[] = 'current-menu-item';
            }
        }
        if(!in_array('current-language-menu-item', $item->classes) && in_array('menu-item-object-language_switcher', $item->classes)){
            $current_language = $url_converter->get_lang_from_url_string($item->url);

            if($current_language == null){
                $current_language = $settings['default-language'];
            }

            if($current_language == $ETM_LANGUAGE){
                $item->classes[] = 'current-language-menu-item';
            }
        }
    }

    remove_filter('pre_get_posts', 'etm_the_event_calendar_set_query_to_true', 2);
    return $items;
}

/**
 * Function needed to set tribe_suppress_query_filters to false in query in order to avoid errors with The Event Calendar
 *
 * @param $query
 * @return mixed
 */
function etm_the_event_calendar_set_query_to_true($query){
    $query->set('tribe_suppress_query_filters', false);

    return $query;
}

/**
 * Compatibility with xstore theme ajax search on other languages than english and when automatic translation was on
 * a class from the search form got translated
 */
if( function_exists('initial_ETC') ) {
    add_filter('etm_skip_gettext_processing', 'etm_exclude_xstore_search_class', 999, 4);
    function etm_exclude_xstore_search_class($return, $translation, $text, $domain){

        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);//set a limit if it is supported to improve performance
        } else {
            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        $list_of_functions = array();
        if (!empty($callstack_functions)) {
            foreach ($callstack_functions as $callstack_function) {
                $list_of_functions[] = $callstack_function['function'];
            }
        }

        if (in_array('esc_attr_e', $list_of_functions) && in_array('header_content_callback', $list_of_functions))
            return true;

        return $return;
    }
}

/**
 * Add compatibility with Business Directory Plugin that requires to update permalinks when we are on other languages or else it will throw a 404 error
 */
if( defined( 'WPBDP_PLUGIN_FILE' ) ) {
    add_filter('etm_prevent_permalink_update_on_other_languages', 'etm_prevent_permalink_update_on_other_languages');
    function etm_prevent_permalink_update_on_other_languages($bool){
        return false;
    }
}


/**
 * Exclude some problematic gettext strings from being translated
 */
add_filter('etm_skip_gettext_processing', 'etm_exclude_problematic_gettext_strings', 999, 4 );
function etm_exclude_problematic_gettext_strings ( $return, $translation, $text, $domain ){
    $exclude_strings = array(
        // some examples on how to use: (domain is optional)
        //array( 'string' => 'Some Text', 'domain' => 'some-domain' )
        //array( 'string' => 'Some Other Text' )
        array( 'string' => 'Ștefan Vodă' )//this is translated by Google Translate into german as "Fan Vod" and the quotes create problems
    );

    foreach( $exclude_strings as $string_details ){
        if( $text === $string_details['string'] ){
            if( empty( $string_details['domain'] ) )
                return true;
            else if( $domain === $string_details['domain'] )
                return true;
        }
    }

    return $return;
}


/**
 * Compatibility with WooCommerce API
 *
 * Particularly with Paypal and myPOS checkout
 *
 * When the IPN request comes do not translate anything outputted.
 * MyPOS expects "OK" string which does not have to be translated as regular string.
 * Paypal had etmst in the details sent.
 */
add_action( 'woocommerce_api_request', 'etm_woo_wc_api_handle_api_request', 1 );
function etm_woo_wc_api_handle_api_request( ){
    add_filter( 'etm_skip_gettext_processing', '__return_true' );
    add_filter( 'etm_stop_translating_page', '__return_true' );
}

/**
 * Compatibility with WooCommerce Min/Max Quantities that wrongly add data-quantity attribute two times on the link and our parser breaks this. The update of the parser to 1.9.1 should render this redundant
 */
if( class_exists('WC_Min_Max_Quantities') ) {
    add_filter('woocommerce_loop_add_to_cart_link', 'etm_check_duplicate_quantity_attribute_on_link', 99, 2);
    function etm_check_duplicate_quantity_attribute_on_link($html, $product){
        $occurrences = substr_count($html, " data-quantity=");
        if ($occurrences > 1) {
            $html = preg_replace('/(data-quantity="\d+"(?!.*data-quantity="\d+"))/', '', $html, 1);
        }

        return $html;
    }
}

/**
 * Add here compatibility with search plugins
 */
add_filter('etm_force_search', 'etm_force_search' );
function etm_force_search( $bool ){
    //force search in xstore theme ajax search
    if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'etheme_ajax_search' )
        $bool = true;

    //compatibility with WooCommerce Product Search plugin
    if( class_exists('WooCommerce_Product_Search_Service') ) {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'product_search')
            $bool = true;
    }

    return $bool;
}

/**
 * Compatibility with WooCommerce Product Search plugin
 * The only way I found is to hijack the cache in the get_post_ids_for_request() function from WooCommerce_Product_Search_Service class. It comes with a loss of performance
 */
if( class_exists('WooCommerce_Product_Search_Service') ) {

    add_filter('woocommerce_product_search_request_search_query', 'etm_woocommerce_product_search_compatibility');

    function etm_woocommerce_product_search_compatibility($search_query)
    {
        global $ETM_LANGUAGE;
        $etm = ETM_eTranslation_Multilingual::get_etm_instance();
        $etm_settings = $etm->get_component('settings');
        $settings = $etm_settings->get_settings();

        if ($ETM_LANGUAGE !== $settings['default-language']) {
            $title = isset($_REQUEST[WooCommerce_Product_Search_Service::TITLE]) ? intval($_REQUEST[WooCommerce_Product_Search_Service::TITLE]) > 0 : WooCommerce_Product_Search_Service::DEFAULT_TITLE;
            $excerpt = isset($_REQUEST[WooCommerce_Product_Search_Service::EXCERPT]) ? intval($_REQUEST[WooCommerce_Product_Search_Service::EXCERPT]) > 0 : WooCommerce_Product_Search_Service::DEFAULT_EXCERPT;
            $content = isset($_REQUEST[WooCommerce_Product_Search_Service::CONTENT]) ? intval($_REQUEST[WooCommerce_Product_Search_Service::CONTENT]) > 0 : WooCommerce_Product_Search_Service::DEFAULT_CONTENT;
            $tags = isset($_REQUEST[WooCommerce_Product_Search_Service::TAGS]) ? intval($_REQUEST[WooCommerce_Product_Search_Service::TAGS]) > 0 : WooCommerce_Product_Search_Service::DEFAULT_TAGS;
            $sku = isset($_REQUEST[WooCommerce_Product_Search_Service::SKU]) ? intval($_REQUEST[WooCommerce_Product_Search_Service::SKU]) > 0 : WooCommerce_Product_Search_Service::DEFAULT_SKU;
            $categories = isset($_REQUEST[WooCommerce_Product_Search_Service::CATEGORIES]) ? intval($_REQUEST[WooCommerce_Product_Search_Service::CATEGORIES]) > 0 : WooCommerce_Product_Search_Service::DEFAULT_CATEGORIES;
            $attributes = isset($_REQUEST[WooCommerce_Product_Search_Service::ATTRIBUTES]) ? intval($_REQUEST[WooCommerce_Product_Search_Service::ATTRIBUTES]) > 0 : WooCommerce_Product_Search_Service::DEFAULT_ATTRIBUTES;
            $variations = isset($_REQUEST[WooCommerce_Product_Search_Service::VARIATIONS]) ? intval($_REQUEST[WooCommerce_Product_Search_Service::VARIATIONS]) > 0 : WooCommerce_Product_Search_Service::DEFAULT_VARIATIONS;

            $min_price = isset($_REQUEST[WooCommerce_Product_Search_Service::MIN_PRICE]) ? WooCommerce_Product_Search_Service::to_float($_REQUEST[WooCommerce_Product_Search_Service::MIN_PRICE]) : null;//phpcs:ignore
            $max_price = isset($_REQUEST[WooCommerce_Product_Search_Service::MAX_PRICE]) ? WooCommerce_Product_Search_Service::to_float($_REQUEST[WooCommerce_Product_Search_Service::MAX_PRICE]) : null;//phpcs:ignore
            if ($min_price !== null && $min_price <= 0) {
                $min_price = null;
            }
            if ($max_price !== null && $max_price <= 0) {
                $max_price = null;
            }
            if ($min_price !== null && $max_price !== null && $max_price < $min_price) {
                $max_price = null;
            }

            $on_sale = isset($_REQUEST[WooCommerce_Product_Search_Service::ON_SALE]) ? intval($_REQUEST[WooCommerce_Product_Search_Service::ON_SALE]) > 0 : WooCommerce_Product_Search_Service::DEFAULT_ON_SALE;

            //this is how they get the key in the method get_cache_key()
            $cache_key = md5(implode('-', array(
                'title' => $title,
                'excerpt' => $excerpt,
                'content' => $content,
                'tags' => $tags,
                'sku' => $sku,
                'categories' => $categories,
                'attributes' => $attributes,
                'variations' => $variations,

                'search_query' => $search_query,
                'min_price' => $min_price,
                'max_price' => $max_price,
                'on_sale' => $on_sale
            )));

            $etm_search = $etm->get_component('search');
            $include = $etm_search->get_post_ids_containing_search_term($search_query, null);
            wp_cache_set($cache_key, $include, WooCommerce_Product_Search_Service::POST_CACHE_GROUP, WooCommerce_Product_Search_Service::CACHE_LIFETIME);
        }

        return $search_query;
    }
}



/**
 * Strip tags manually from a problematic string coming from the My Listing theme
 */
 add_action('init', 'etm_mylisting_hook_exclude_string' );
 function etm_mylisting_hook_exclude_string(){
     if( class_exists( 'MyListing\\App' ) ){
         add_filter('gettext_with_context', 'etm_mylisting_exclude_string', 101, 4 );
     }
 }

 function etm_mylisting_exclude_string( $translation, $text, $context, $domain ){

     if( $domain == 'my-listing' && $text == 'my-listings' )
         $translation = ETM_Translation_Manager::strip_gettext_tags( $translation );

     return $translation;

 }


/**
 * Compatibility with Google Site Kit plugin
 *
 * Problem was that Site Kit dashboard kept disconnecting, thinking the url must have changed.
 *
 * To replicate, set TP option "Add language to subdirectory" Yes and use Complianz plugin, wizard step 2,
 * to perform re-scan of cookies. This triggered the disconnect.
 */
add_filter('googlesitekit_canonical_home_url', 'etm_googlesitekit_compatibility_home_url' );
function etm_googlesitekit_compatibility_home_url( $url ) {
    $etm = ETM_eTranslation_Multilingual::get_etm_instance();
    $url_converter = $etm->get_component('url_converter');
    return $url_converter->get_abs_home();
}


/**
 * Compatibility with WPEngine hosting
 *
 * Detect and handle query length limiting feature of WPEngine. Without this check, the query returns no results as if
 * there were no translations found. This results in duplicate row inserting and unnecessary automatic translation
 * usage.
 */
add_filter('etm_get_existing_translations', 'etm_wpengine_query_limit_check', 10, 3 );
function etm_wpengine_query_limit_check($dictionary, $prepared_query, $strings_array){
    if ( function_exists('is_wpe') && ( !defined ('WPE_GOVERNOR') || ( defined ('WPE_GOVERNOR') && WPE_GOVERNOR != false ) ) && strlen($prepared_query) >= 16000 ){
        $etm = ETM_eTranslation_Multilingual::get_etm_instance();
        $etm_query = $etm->get_component( 'query' );
        $etm_query->maybe_record_automatic_translation_error(array( 'details' => esc_html__("Detected long query limitation on WPEngine hosting. Some large pages may appear untranslated. You can remove limitation by adding the following to your site’s wp-config.php: define( 'WPE_GOVERNOR', false ); ", 'etranslation-multilingual')), true );
        return false;
    }else{
        return $dictionary;
    }
}


/**
 * Compatibility with Dokan plugin
 *
 * Dates are run through gettext and the this breaks further functions because of wrappings
 */
if ( class_exists('WeDevs_Dokan')) {
    add_filter( 'etm_skip_gettext_processing', 'etm_exclude_dokan_date_strings', 20, 4 );
}
function etm_exclude_dokan_date_strings($return, $translation, $text, $domain) {
    $skip_text = array('Y/m/d g:i:s A', 'Y/m/d');
    if ($domain == 'dokan' && in_array( $text, $skip_text) ){
        return true;
    }
    return $return;
}

function etm_add_language_to_pms_wppb_restriction_redirect_url( $redirect_url ){

    global $ETM_LANGUAGE;

    $etm = ETM_eTranslation_Multilingual::get_etm_instance();
    $url_converter = $etm->get_component('url_converter');

    return $url_converter->get_url_for_language( $ETM_LANGUAGE, $redirect_url, '' );

}

if( defined( 'PMS_VERSION' ) )
    add_filter( 'pms_restricted_post_redirect_url', 'etm_add_language_to_pms_wppb_restriction_redirect_url' );

if( function_exists( 'wppb_plugin_init' ) )
    add_filter( 'wppb_restricted_post_redirect_url', 'etm_add_language_to_pms_wppb_restriction_redirect_url' );

/**
 * Compatibility with wp-Typography
 * The $filters array is set to empty, so it does not affect the strings anymore in the function etm_remove_filters_wp_typography.
 * Then it is reset with a higher priority by calling the function process() inside the etm_add_filters_wp_typography function.
 */

if(class_exists('WP_Typography')) {
    add_action('plugins_loaded', 'etm_wp_typography');
}

function etm_wp_typography(){
    global $ETM_LANGUAGE;
    $etm = ETM_eTranslation_Multilingual::get_etm_instance();
    $etm_settings = $etm->get_component('settings');
    $settings = $etm_settings->get_settings();

    if ($ETM_LANGUAGE !== $settings['default-language']) {
        add_filter( 'typo_content_filters', 'etm_remove_filters_wp_typography' );
        add_filter( 'etm_translated_html', 'etm_add_filters_wp_typography', 100000, 1 );
        add_filter('run_wptexturize', '__return_null', 11);
    }
}

function etm_remove_filters_wp_typography($filters){
    $filters = [];

    return $filters;
}



function etm_add_filters_wp_typography($final_html){
    $wpt= WP_Typography::get_instance();

    add_filter('run_wptexturize', '__return_false', 11);

    $final_html = $wpt->process($final_html, $is_title = false, $force_feed = false, null );

    return $final_html;

}


/*
 * Compatibility with All In One SEO Pack
 */

if(function_exists('aioseo')){

    if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
        $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 15);//set a limit if it is supported to improve performance
    } else {
        $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
    }

    if (!empty($callstack_functions)) {
        foreach ( $callstack_functions as $callstack_function ) {
            if ( isset($callstack_function["object"]->{"callbacks"}) ) {
                foreach ($callstack_function["object"]->{"callbacks"}[10] as $key=>$value){
                    if(strpos($key, 'actionScheduler')){
                        if(array_key_exists('breadcrumbs_archiveFormat',  $callstack_function["object"]->{"callbacks"}[10][ $key ]["function"][0]->{"options"}->{"localized"}  )) {
                            add_action( 'etm_before_running_hooks', 'etm_AIOSEO_remove_gettext_hooks', 10, 1 );
                        }
                    }
                }
            }
        }
    }
}

function etm_AIOSEO_remove_gettext_hooks($etm_loader){
    $etm                = ETM_eTranslation_Multilingual::get_etm_instance();
    $translation_render = $etm->get_component( 'translation_render' );
    $etm_loader->remove_hook( 'the_title', 'wrap_with_post_id', $translation_render );
}


/**
 * Compatibility with Elementor/Divi/WPBakery when "Use a subdirectory for the default language" is set to Yes
 * Making sure the page edited with Elementor/Divi/WPBakery appears in the default language instead of the first language from the Language list
 */
add_filter( 'etm_needed_language', 'etm_page_builders_compatibility_with_subdirectory_for_default_language', 10, 4 );
function etm_page_builders_compatibility_with_subdirectory_for_default_language( $needed_language, $lang_from_url, $settings, $etm) {
    if ( ( ( isset( $_GET['action'] ) && $_GET['action'] === 'elementor' ) || isset( $_GET['elementor-preview'] ) ) //Elementor
        || ( ( isset( $_GET['et_fb'] ) && $_GET['et_fb'] === '1' ) && ( isset( $_GET['PageSpeed'] ) && $_GET['PageSpeed'] === "off" ) ) //Divi
        || ( ( isset( $_GET['vc_action'] ) && $_GET['vc_action'] === 'vc_inline' ) || ( isset( $_GET['vc_editable'] ) && $_GET['vc_editable'] === 'true' ) ) ) { //WPBakery
        $needed_language = $settings['default-language'];
    }
    return $needed_language;
}


/**
 * Compatibility with Give WP plugin.
 *
 * When automatic translation is active and we are on secondary language, clicking the Donate button will not redirect you to the confirmation page.
 * This happens because "Give WP" expects an admin ajax request to return "success" but TP translates it in another language.
 */
add_filter( 'etm_stop_translating_page', 'etm_give_wp_compatibility', 10, 2 );
function etm_give_wp_compatibility( $bool, $output ){
    if ( isset( $_REQUEST['give_ajax'] ) && $_REQUEST['give_ajax'] == 'true' ) {
        return true;
    }
    return $bool;
}

/*
 * Divi is filtering the locale which is in turn accessed on every gettext call by eTranslation Multilingual. Together these two things slow down the site to 20+ seconds
 * The fix is to remove the Divi hook and replace it with another one that caches the result, so it's fast.
 * Ideally this is a fix Divi should do, however, it negatively impacts TP, so we're doing it for them.
 */
add_filter('locale', 'etm_remove_divi_locale_filter', 999999);
function etm_remove_divi_locale_filter($lang){
    remove_filter( 'locale', 'et_divi_maybe_change_frontend_locale' );
    return $lang;
}
function etm_et_divi_maybe_change_frontend_locale( $locale ) {
    $cache_key = 'et_divi_option';
    $theme_options = wp_cache_get( $cache_key );
    $option_name   = 'divi_disable_translations';
    if (false === $theme_options){
        $theme_options = get_option( 'et_divi' );
        wp_cache_set( $cache_key, $theme_options );
    }
    $disable_translations = isset ( $theme_options[ $option_name ] ) ? $theme_options[ $option_name ] : false;
    if ( 'on' === $disable_translations ) {
        return 'en_GB';
    }
    return $locale;
}
add_filter( 'locale', 'etm_et_divi_maybe_change_frontend_locale' );
