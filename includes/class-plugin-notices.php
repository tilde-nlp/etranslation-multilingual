<?php
/**
 * Class that adds a misc notice
 *
 *
 * @return void
 */
class ETM_Add_General_Notices{
    public $notificationId = '';
    public $notificationMessage = '';
    public $notificationClass = '';
    public $startDate = '';
    public $endDate = '';
    public $force_show = false;//this attribute ignores the dismiss notification

    function __construct( $notificationId, $notificationMessage, $notificationClass = 'updated' , $startDate = '', $endDate = '', $force_show = false ){
        $this->notificationId = $notificationId;
        $this->notificationMessage = $notificationMessage;
        $this->notificationClass = $notificationClass;
        $this->force_show = $force_show;

        if( !empty( $startDate ) && time() < strtotime( $startDate ) )
            return;

        if( !empty( $endDate ) && time() > strtotime( $endDate ) )
            return;

        add_action( 'admin_notices', array( $this, 'add_admin_notice' ) );
        add_action( 'admin_init', array( $this, 'dismiss_notification' ) );
    }


    // Display a notice that can be dismissed in case the serial number is inactive
    function add_admin_notice() {
        global $current_user ;
        global $pagenow;

        $user_id = $current_user->ID;
        do_action( $this->notificationId.'_before_notification_displayed', $current_user, $pagenow );

        if ( current_user_can( 'manage_options' ) ){
            // Check that the user hasn't already clicked to ignore the message
            if ( ! get_user_meta($user_id, $this->notificationId.'_dismiss_notification' ) || $this->force_show  ) {//ignore the dismissal if we have force_show
                add_filter('safe_style_css', array( $this, 'allow_z_index_in_wp_kses'));
                echo wp_kses( apply_filters($this->notificationId.'_notification_message','<div class="'. $this->notificationClass .'" style="position:relative;'  . ((strpos($this->notificationClass, 'etm-narrow')!==false ) ? 'max-width: 825px;' : '') . '" >'.$this->notificationMessage.'</div>', $this->notificationMessage), [ 'div' => [ 'class' => [],'style' => [] ], 'p' => ['style' => [], 'class' => []], 'a' => ['href' => [], 'type'=> [], 'class'=> [], 'style'=>[], 'title'=>[],'target'=>[]], 'span' => ['class'=> []], 'strong' => [] ] );
                remove_filter('safe_style_css', array( $this, 'allow_z_index_in_wp_kses'));
            }
            do_action( $this->notificationId.'_notification_displayed', $current_user, $pagenow );
        }
        do_action( $this->notificationId.'_after_notification_displayed', $current_user, $pagenow );
    }

    function allow_z_index_in_wp_kses( $styles ) {
        $styles[] = 'z-index';
        $styles[] = 'position';
        return $styles;
    }

    function dismiss_notification() {
        global $current_user;

        $user_id = $current_user->ID;

        do_action( $this->notificationId.'_before_notification_dismissed', $current_user );

        // If user clicks to ignore the notice, add that to their user meta
        if ( isset( $_GET[$this->notificationId.'_dismiss_notification']) && '0' == $_GET[$this->notificationId.'_dismiss_notification'] )
            add_user_meta( $user_id, $this->notificationId.'_dismiss_notification', 'true', true );

        do_action( $this->notificationId.'_after_notification_dismissed', $current_user );
    }
}

Class ETM_Plugin_Notifications {

    public $notifications = array();
    private static $_instance = null;
    private $prefix = 'etm';
    private $menu_slug = 'options-general.php';
    public $pluginPages = array( 'etranslation-multilingual', 'etm_addons_page', 'etm_advanced_page', 'etm_machine_translation', 'etm_test_machine_api' );

    protected function __construct() {
        add_action( 'admin_init', array( $this, 'dismiss_admin_notifications' ), 200 );
        add_action( 'admin_init', array( $this, 'add_admin_menu_notification_counts' ), 1000 );
        add_action( 'admin_init', array( $this, 'remove_other_plugin_notices' ), 1001 );
    }


    function dismiss_admin_notifications() {
        if( ! empty( $_GET[$this->prefix.'_dismiss_admin_notification'] ) ) {
            $notifications = self::get_instance();
            $notifications->dismiss_notification( sanitize_text_field( $_GET[$this->prefix.'_dismiss_admin_notification'] ) );
        }

    }

    function add_admin_menu_notification_counts() {

        global $menu, $submenu;

        $notifications = ETM_Plugin_Notifications::get_instance();

        if( ! empty( $menu ) ) {
            foreach( $menu as $menu_position => $menu_data ) {
                if( ! empty( $menu_data[2] ) && $menu_data[2] == $this->menu_slug ) {
                    $menu_count = $notifications->get_count_in_menu();
                    if( ! empty( $menu_count ) )
                        $menu[$menu_position][0] .= '<span class="update-plugins '.$this->prefix.'-update-plugins"><span class="plugin-count">' . $menu_count . '</span></span>';
                }
            }
        }

        if( ! empty( $submenu[$this->menu_slug] ) ) {
            foreach( $submenu[$this->menu_slug] as $menu_position => $menu_data ) {
                $menu_count = $notifications->get_count_in_submenu( $menu_data[2] );
                if( ! empty( $menu_count ) )
                    $submenu[$this->menu_slug][$menu_position][0] .= '<span class="update-plugins '.$this->prefix.'-update-plugins"><span class="plugin-count">' . $menu_count . '</span></span>';
            }
        }
    }

    /* handle other plugin notifications on our plugin pages */
    function remove_other_plugin_notices(){
        /* remove all other plugin notifications except our own from the rest of the PB pages */
        if( $this->is_plugin_page() ) {
            global $wp_filter;
            if (!empty($wp_filter['admin_notices'])) {
                if (!empty($wp_filter['admin_notices']->callbacks)) {
                    foreach ($wp_filter['admin_notices']->callbacks as $priority => $callbacks_level) {
                        if (!empty($callbacks_level)) {
                            foreach ($callbacks_level as $key => $callback) {
                                if( is_array( $callback['function'] ) ){
                                    if( is_object($callback['function'][0])) {//object here
                                        if (strpos(get_class($callback['function'][0]), 'PMS_') !== 0 && strpos(get_class($callback['function'][0]), 'WPPB_') !== 0 && strpos(get_class($callback['function'][0]), 'ETM_') !== 0 && strpos(get_class($callback['function'][0]), 'WCK_') !== 0) {
                                            unset($wp_filter['admin_notices']->callbacks[$priority][$key]);//unset everything that doesn't come from our plugins
                                        }
                                    }
                                } else if( is_string( $callback['function'] ) ){//it should be a function name
                                    if (strpos($callback['function'], 'pms_') !== 0 && strpos($callback['function'], 'wppb_') !== 0 && strpos($callback['function'], 'etm_') !== 0 && strpos($callback['function'], 'wck_') !== 0) {
                                        unset($wp_filter['admin_notices']->callbacks[$priority][$key]);//unset everything that doesn't come from our plugins
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

    }

    /**
     *
     *
     */
    public static function get_instance() {
        if( is_null( self::$_instance ) )
            self::$_instance = new ETM_Plugin_Notifications();

        return self::$_instance;
    }


    /**
     *
     *
     */
    public function add_notification( $notification_id = '', $notification_message = '', $notification_class = 'update-nag', $count_in_menu = true, $count_in_submenu = array(), $show_in_all_backend = false, $non_dismissable = false ) {

        if( empty( $notification_id ) )
            return;

        if( empty( $notification_message ) )
            return;

        global $current_user;

        /**
         * added a $show_in_all_backend argument that allows some notifications to be displayed on all the pages not just the plugin pages
         * we needed it for license notifications
         */
        $force_show = false;
        if( get_user_meta( $current_user->ID, $notification_id . '_dismiss_notification' ) ) {
            if( !$non_dismissable && !($this->is_plugin_page() && $show_in_all_backend) ){
                return;
            }
            else{
                $force_show = true; //if $show_in_all_backend is true then we ignore the dismiss on plugin pages, but on the rest of the pages it can be dismissed
            }
        }

        $this->notifications[$notification_id] = array(
            'id' 	  		   => $notification_id,
            'message' 		   => $notification_message,
            'class'   		   => $notification_class,
            'count_in_menu'    => $count_in_menu,
            'count_in_submenu' => $count_in_submenu
        );


        if( $this->is_plugin_page() || ($show_in_all_backend && isset( $GLOBALS['PHP_SELF']) && $GLOBALS['PHP_SELF'] === '/wp-admin/index.php' ) ) {
            new ETM_Add_General_Notices( $notification_id, $notification_message, $notification_class, '', '', $force_show );
        }

    }


    /**
     *
     *
     */
    public function get_notifications() {
        return $this->notifications;
    }


    /**
     *
     *
     */
    public function get_notification( $notification_id = '' ) {

        if( empty( $notification_id ) )
            return null;

        $notifications = $this->get_notifications();

        if( ! empty( $notifications[$notification_id] ) )
            return $notifications[$notification_id];
        else
            return null;

    }


    /**
     *
     *
     */
    public function dismiss_notification( $notification_id = '' ) {
        global $current_user;
        add_user_meta( $current_user->ID, $notification_id . '_dismiss_notification', 'true', true );
        do_action('etm_dismiss_notification', $notification_id, $current_user);
    }


    /**
     *
     *
     */
    public function get_count_in_menu() {
        $count = 0;

        foreach( $this->notifications as $notification ) {
            if( ! empty( $notification['count_in_menu'] ) )
                $count++;
        }

        return $count;
    }


    /**
     *
     *
     */
    public function get_count_in_submenu( $submenu = '' ) {

        if( empty( $submenu ) )
            return 0;

        $count = 0;

        foreach( $this->notifications as $notification ) {
            if( empty( $notification['count_in_submenu'] ) )
                continue;

            if( ! is_array( $notification['count_in_submenu'] ) )
                continue;

            if( ! in_array( $submenu, $notification['count_in_submenu'] ) )
                continue;

            $count++;
        }

        return $count;

    }


    /**
     * Test if we are an a page that belong to our plugin
     *
     */
    public function is_plugin_page() {
        if( !empty( $this->pluginPages ) ){
            foreach ( $this->pluginPages as $pluginPage ){
                if( ! empty( $_GET['page'] ) && false !== strpos( sanitize_text_field( $_GET['page'] ), $pluginPage ) )
                    return true;

                if( ! empty( $_GET['post_type'] ) && false !== strpos( sanitize_text_field( $_GET['post_type'] ), $pluginPage ) )
                    return true;

                if( ! empty( $_GET['post'] ) && false !== strpos( get_post_type( (int)$_GET['post'] ), $pluginPage ) )
                    return true;
            }
        }

        return false;
    }

}


class ETM_Trigger_Plugin_Notifications{

    private $settings;
    private $settings_obj;
    private $machine_translator_logger;

    function __construct($settings) {
        $this->settings = $settings;

        add_action( 'admin_init', array( $this, 'add_plugin_notifications' ) );
    }

    function add_plugin_notifications() {

        $notifications = ETM_Plugin_Notifications::get_instance();

        /*
         *
         *  Machine translation enabled and  quota is met.
         *
         */
        $etm = ETM_eTranslation_Multilingual::get_etm_instance();
        if ( ! $this->settings_obj )
            $this->settings_obj = $etm->get_component( 'settings' );

        if ( ! $this->machine_translator_logger )
            $this->machine_translator_logger = $etm->get_component( 'machine_translator_logger' );

        if( isset($this->settings['etm_machine_translation_settings']['machine-translation']) &&
            'yes' === $this->settings['etm_machine_translation_settings']['machine-translation'] && $this->machine_translator_logger->quota_exceeded() ) {
            /* this must be unique */
            $notification_id = 'etm_machine_translation_quota_exceeded_'. date('Ymd');

            $message = '<img style="float: left; margin: 10px 12px 10px 0; max-width: 80px;" src="' . ETM_PLUGIN_URL . 'assets/images/get_param_addon.jpg" />';
            $message .= '<p style="margin-top: 16px;padding-right:30px;">';
                $message .= sprintf( __( 'The daily quota for machine translation characters exceeded. Please check the <strong>eTranslation Multilingual -> <a href="%s">Automatic Translation</a></strong> page for more information.', 'etranslation-multilingual' ), admin_url( 'admin.php?page=etm_machine_translation' ) );
            $message .= '</p>';
            //make sure to use the etm_dismiss_admin_notification arg
            $message .= '<a href="' . add_query_arg(array('etm_dismiss_admin_notification' => $notification_id)) . '" type="button" class="notice-dismiss"><span class="screen-reader-text">' . __('Dismiss this notice.', 'etranslation-multilingual') . '</span></a>';

            $notifications->add_notification($notification_id, $message, 'etm-notice etm-narrow notice notice-info', true, array('etranslation-multilingual'));
        }


        /*
         * One or more languages are unsupported by automatic translation.
         */
        $etm = ETM_eTranslation_Multilingual::get_etm_instance();
        $machine_translator = $etm->get_component( 'machine_translator' );

        if ($machine_translator != null ) {
            if ( apply_filters( 'etm_mt_available_supported_languages_show_notice', true, $this->settings['translation-languages'], $this->settings ) &&
                isset($this->settings['etm_machine_translation_settings']['machine-translation']) &&
                'yes' === $this->settings['etm_machine_translation_settings']['machine-translation'] &&
                !$machine_translator->check_languages_availability( $this->settings['translation-languages'] )
            ) {
                /* this must be unique */
                $notification_id = 'etm_mt_unsupported_languages';

                $message = '<p style="margin-top: 16px;padding-right:30px;">';
                $message .= sprintf( __( 'One or more languages are unsupported by the automatic translation provider. Please check the <strong>eTranslation Multilingual -> <a href="%s">Automatic Translation</a></strong> page for more information.', 'etranslation-multilingual' ), admin_url( 'admin.php?page=etm_machine_translation#etm_unsupported_languages' ) );
                $message .= '</p>';
                //make sure to use the etm_dismiss_admin_notification arg
                $message .= '<a href="' . add_query_arg( array( 'etm_dismiss_admin_notification' => $notification_id ) ) . '" type="button" class="notice-dismiss"><span class="screen-reader-text">' . __( 'Dismiss this notice.', 'etranslation-multilingual' ) . '</span></a>';

                $notifications->add_notification( $notification_id, $message, 'etm-notice etm-narrow notice notice-info', true, array( 'etranslation-multilingual' ) );
            }
        }

    }

}
