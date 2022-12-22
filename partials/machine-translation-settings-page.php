<div id="etm-main-settings" class="wrap">
    <?php echo "<img style='width: 200px;' src='" . ETM_PLUGIN_URL . "assets/images/Logo_eTranslation_v6b.svg' />" ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'etm_machine_translation_settings' ); ?>
        <h1> <?php esc_html_e( 'eTranslation Multilingual Automatic Translation', 'etranslation-multilingual' );?></h1>
        <?php do_action ( 'etm_settings_navigation_tabs' ); ?>

        <table id="etm-options" class="form-table etm-machine-translation-options">
            <tr>
                <th scope="row"><?php esc_html_e( 'Enable Automatic Translation', 'etranslation-multilingual' ); ?> </th>
                <td>
                    <select id="etm-machine-translation-enabled" name="etm_machine_translation_settings[machine-translation]" class="etm-select">
                        <option value="no" <?php selected( $this->settings['etm_machine_translation_settings']['machine-translation'], 'no' ); ?>><?php esc_html_e( 'No', 'etranslation-multilingual') ?></option>
                        <option value="yes" <?php selected( $this->settings['etm_machine_translation_settings']['machine-translation'], 'yes' ); ?>><?php esc_html_e( 'Yes', 'etranslation-multilingual') ?></option>
                    </select>

                    <p class="description">
                        <?php esc_html_e( 'Enable or disable the automatic translation of the site. To minimize translation costs, each untranslated string is automatically translated only once, then stored in the database.', 'etranslation-multilingual' ) ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Translation Engine', 'etranslation-multilingual' ); ?> </th>
                <td>
                    <?php $translation_engines = apply_filters( 'etm_machine_translation_engines', array() ); ?>

                    <?php foreach( $translation_engines as $engine ) : ?>
                        <label for="etm-translation-engine-<?= esc_attr( $engine['value'] ) ?>" style="margin-right:10px;">
                             <input type="radio" class="etm-translation-engine etm-radio" id="etm-translation-engine-<?= esc_attr( $engine['value'] ) ?>" name="etm_machine_translation_settings[translation-engine]" value="<?= esc_attr( $engine['value'] ) ?>" <?php checked( $this->settings['etm_machine_translation_settings']['translation-engine'], $engine['value'] ); ?>>
                             <?php echo esc_html( $engine['label'] ) ?>
                        </label>
                    <?php endforeach; ?>

                    <p class="description">
                        <?php esc_html_e( 'Choose which engine you want to use in order to automatically translate your website.', 'etranslation-multilingual' ) ?>
                    </p>
                </td>
            </tr>

            <?php do_action ( 'etm_machine_translation_extra_settings_middle', $this->settings['etm_machine_translation_settings'] ); ?>

            <?php if( !empty( $machine_translator->credentials_set() ) ) : ?>
                <tr id="etm-test-api-key">
                    <th scope="row"></th>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=etm_test_machine_api' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Test API credentials', 'etranslation-multilingual' ); ?></a>
                        <p class="description">
                            <?php esc_html_e( 'Click here to check if the selected translation engine is configured correctly.', 'etranslation-multilingual' ) ?>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>

            <tr style="border-bottom: 1px solid #ccc;"></tr>

            <tr>
                <th scope=row><?php esc_html_e( 'Block Crawlers', 'etranslation-multilingual' ); ?></th>
                <td>
                    <label>
                        <input type=checkbox name="etm_machine_translation_settings[block-crawlers]" value="yes" <?php isset( $this->settings['etm_machine_translation_settings']['block-crawlers'] ) ? checked( $this->settings['etm_machine_translation_settings']['block-crawlers'], 'yes' ) : checked( '', 'yes' ); ?>>
                        <?php esc_html_e( 'Yes' , 'etranslation-multilingual' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Block crawlers from triggering automatic translations on your website.', 'etranslation-multilingual' ); ?>
                    </p>
                </td>
            </tr>

            <tr>
               <th scope=row><?php esc_html_e( 'Log machine translation queries.', 'etranslation-multilingual' ); ?></th>
               <td>
                   <label>
                       <input type=checkbox name="etm_machine_translation_settings[machine_translation_log]" value="yes" <?php isset( $this->settings['etm_machine_translation_settings']['machine_translation_log'] ) ? checked( $this->settings['etm_machine_translation_settings']['machine_translation_log'], 'yes' ) : checked( '', 'yes' ); ?>>
                       <?php esc_html_e( 'Yes' , 'etranslation-multilingual' ); ?>
                   </label>
                   <p class="description">
                       <?php echo wp_kses( __( 'Only enable for testing purposes. Can impact performance.<br>All records are stored in the wp_etm_machine_translation_log database table. Use a plugin like <a href="https://wordpress.org/plugins/wp-data-access/" target="_blank">WP Data Access</a> to browse the logs or directly from your database manager (PHPMyAdmin, etc.)', 'etranslation-multilingual' ), array( 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) ) ); ?>
                   </p>
               </td>
           </tr>

           <tr>
                <th scope="row"><?php esc_html_e( 'Show machine translation notice', 'etranslation-multilingual' ); ?></th>
                <td>
                    <select id="show-mt-notice" name="etm_machine_translation_settings[show-mt-notice]" class="etm-select">
                        <option value="yes" <?php selected( $this->settings['etm_machine_translation_settings']['show-mt-notice'], 'yes' ); ?>><?php esc_html_e( 'Yes', 'etranslation-multilingual') ?></option>
                        <option value="no" <?php selected( $this->settings['etm_machine_translation_settings']['show-mt-notice'], 'no' ); ?>><?php esc_html_e( 'No', 'etranslation-multilingual') ?></option>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'Select No if you do not want to show machine translation notice bar at the top of every translated page.', 'etranslation-multilingual' ); ?>
                    </p>
                </td>
            </tr>

            <?php do_action ( 'etm_machine_translation_extra_settings_bottom', $this->settings['etm_machine_translation_settings'] ); ?>
        </table>

        <p class="submit"><input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'etranslation-multilingual' ); ?>" /></p>
    </form>
</div>
