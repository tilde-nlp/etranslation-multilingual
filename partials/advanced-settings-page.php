
<div id="etm-advanced-settings" class="wrap">
    <?php echo "<img style='width: 200px;' src='" . ETM_PLUGIN_URL . "assets/images/Logo_eTranslation_v6b.svg' />" ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'etm_advanced_settings' ); ?>
        <h1> <?php esc_html_e( 'eTranslation Multilingual Advanced Settings', 'etranslation-multilingual' );?></h1>
        <?php do_action ( 'etm_settings_navigation_tabs' ); ?>

        <?php do_action('etm_before_output_advanced_settings_options' ); ?>

        <table id="etm-options" class="form-table">
            <?php do_action('etm_output_advanced_settings_options' ); ?>
        </table>

        <?php do_action('etm_after_output_advanced_settings_options' ); ?>

	    <?php submit_button( __( 'Save Changes', 'etranslation-multilingual' ) ); ?>
    </form>
</div>
