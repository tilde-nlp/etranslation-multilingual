<div id="trp-addons-page" class="wrap">

    <h1> <?php esc_html_e( 'TranslatePress Settings', 'etranslation-multilingual' );?></h1>
    <?php do_action ( 'trp_settings_navigation_tabs' ); ?>

    <?php
    //initialize the object
    $trp_addons_listing = new TRP_Addons_List_Table();
    $trp_addons_listing->images_folder = TRP_PLUGIN_URL.'assets/images/';
    $trp_addons_listing->text_domain = 'etranslation-multilingual';
    $trp_addons_listing->header = array( 'title' => __('TranslatePress Add-ons', 'etranslation-multilingual' ) );
    if( defined( 'TRANSLATE_PRESS' ) )
        $trp_addons_listing->current_version = TRANSLATE_PRESS;
    else
        $trp_addons_listing->current_version = 'TranslatePress - Multilingual';//in free version we do not define the constant as free version needs to be active always
    $trp_addons_listing->tooltip_header = __( 'TranslatePress Add-ons', 'etranslation-multilingual' );
    $trp_addons_listing->tooltip_content = sprintf( __( 'You must first purchase this version to have access to the addon %1$shere%2$s', 'etranslation-multilingual' ), '<a target="_blank" href="'. trp_add_affiliate_id_to_link('https://translatepress.com/pricing/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=TRP').'">', '</a>' );


    //Add Advanced section
    $trp_addons_listing->section_header = array( 'title' => __('Advanced Add-ons', 'etranslation-multilingual' ), 'description' => __('These addons extend your translation plugin and are available in the Developer, Business and Personal plans.', 'etranslation-multilingual')  );
    $trp_addons_listing->section_versions = array( 'TranslatePress - Dev', 'TranslatePress - Personal', 'TranslatePress - Business', 'TranslatePress - Developer' );
    $trp_addons_listing->items = array(
        array(  'slug' => 'tp-add-on-seo-pack/tp-seo-pack.php',
            'type' => 'add-on',
            'name' => __( 'SEO Pack', 'etranslation-multilingual' ),
            'description' => __( 'SEO support for page slug, page title, description and facebook and twitter social graph information. The HTML lang attribute is properly set.', 'etranslation-multilingual' ),
            'icon' => 'seo_icon_translatepress.png',
            'doc_url' => 'https://translatepress.com/docs/addons/seo-pack/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=TRP',
        ),
        array(  'slug' => 'tp-add-on-extra-languages/tp-extra-languages.php',
            'type' => 'add-on',
            'name' => __( 'Multiple Languages', 'etranslation-multilingual' ),
            'description' => __( 'Add as many languages as you need for your project to go global. Publish your language only when all your translations are done.', 'etranslation-multilingual' ),
            'icon' => 'multiple_lang_icon.png',
            'doc_url' => 'https://translatepress.com/docs/addons/multiple-languages/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=TRP',
        ),
    );
    $trp_addons_listing->add_section();

    //Add Pro Section
    $trp_addons_listing->section_header = array( 'title' => __('Pro Add-ons', 'etranslation-multilingual' ), 'description' => __('These addons extend your translation plugin and are available in the Business and Developer plans.', 'etranslation-multilingual')  );
    $trp_addons_listing->section_versions = array( 'TranslatePress - Dev', 'TranslatePress - Business', 'TranslatePress - Developer' );
    $trp_addons_listing->items = array(
        array(  'slug' => 'tp-add-on-deepl/index.php',
            'type' => 'add-on',
            'name' => __( 'DeepL Automatic Translation', 'etranslation-multilingual' ),
            'description' => __( 'Automatically translate your website through the DeepL API.', 'etranslation-multilingual' ),
            'icon' => 'deepl-add-on.png',
            'doc_url' => 'https://translatepress.com/docs/addons/deepl-automatic-translation/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=TRP',
        ),
        array(  'slug' => 'tp-add-on-automatic-language-detection/tp-automatic-language-detection.php',
            'type' => 'add-on',
            'name' => __( 'Automatic User Language Detection', 'etranslation-multilingual' ),
            'description' => __( 'Automatically redirects new visitors to their preferred language based on browser settings or IP address and remembers the last visited language.', 'etranslation-multilingual' ),
            'icon' => 'auto-detect-language-add-on.png',
            'doc_url' => 'https://translatepress.com/docs/addons/automatic-user-language-detection/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=TRP',
        ),
        array(  'slug' => 'tp-add-on-translator-accounts/index.php',
            'type' => 'add-on',
            'name' => __( 'Translator Accounts', 'etranslation-multilingual' ),
            'description' => __( 'Create translator accounts for new users or allow existing users that are not administrators to translate your website.', 'etranslation-multilingual' ),
            'icon' => 'translator-accounts-addon.png',
            'doc_url' => 'https://translatepress.com/docs/addons/translator-accounts/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=TRP',
        ),
        array(  'slug' => 'tp-add-on-browse-as-other-roles/tp-browse-as-other-role.php',
            'type' => 'add-on',
            'name' => __( 'Browse As User Role', 'etranslation-multilingual' ),
            'description' => __( 'Navigate your website just like a particular user role would. Really useful for dynamic content or hidden content that appears for particular users.', 'etranslation-multilingual' ),
            'icon' => 'view-as-addon.png',
            'doc_url' => 'https://translatepress.com/docs/addons/browse-as-role/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=TRP',
        ),
        array(  'slug' => 'tp-add-on-navigation-based-on-language/tp-navigation-based-on-language.php',
            'type' => 'add-on',
            'name' => __( 'Navigation Based on Language', 'etranslation-multilingual' ),
            'description' => __( 'Configure different menu items for different languages.', 'etranslation-multilingual' ),
            'icon' => 'menu_based_on_lang.png',
            'doc_url' => 'https://translatepress.com/docs/addons/navigate-based-language/?utm_source=wpbackend&utm_medium=clientsite&utm_content=add-on-page&utm_campaign=TRP',
        ),
    );
    $trp_addons_listing->add_section();


    //Add Recommended Plugins
    $trp_addons_listing->section_header = array( 'title' => __('Recommended Plugins', 'etranslation-multilingual' ), 'description' => __('A short list of plugins you can use to extend your website.', 'etranslation-multilingual')  );
    $trp_addons_listing->section_versions = array( 'TranslatePress - Dev', 'TranslatePress - Personal', 'TranslatePress - Business', 'TranslatePress - Developer', 'TranslatePress - Multilingual' );
    $trp_addons_listing->items = array(
        array(  'slug' => 'profile-builder/index.php',
            'short-slug' => 'pb',
            'type' => 'plugin',
            'name' => __( 'Profile Builder', 'etranslation-multilingual' ),
            'description' => __( 'Capture more user information on the registration form with the help of Profile Builder\'s custom user profile fields and/or add an Email Confirmation process to verify your customers accounts.', 'etranslation-multilingual' ),
            'icon' => 'pb_logo.jpg',
            'doc_url' => 'https://www.cozmoslabs.com/wordpress-profile-builder/?utm_source=tpbackend&utm_medium=clientsite&utm_content=tp-addons-page&utm_campaign=TPPB',
            'disabled' => $plugin_settings['pb']['disabled'],
            'install_button' =>  $plugin_settings['pb']['install_button']
        ),
        array(  'slug' => 'paid-member-subscriptions/index.php',
            'short-slug' => 'pms',
            'type' => 'plugin',
            'name' => __( 'Paid Member Subscriptions', 'etranslation-multilingual' ),
            'description' => __( 'Accept user payments, create subscription plans and restrict content on your membership site.', 'etranslation-multilingual' ),
            'icon' => 'pms_logo.jpg',
            'doc_url' => 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=tpbackend&utm_medium=clientsite&utm_content=tp-addons-page&utm_campaign=TPPMS',
            'disabled' => $plugin_settings['pms']['disabled'],
            'install_button' =>  $plugin_settings['pms']['install_button']
        )
    );
    $trp_addons_listing->add_section();


    //Display the whole listing
    $trp_addons_listing->display_addons();

    ?>


</div>
