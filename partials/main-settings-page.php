
<div id="trp-main-settings" class="wrap">
    <?php echo "<img style='width: 200px;' src='" . TRP_PLUGIN_URL . "assets/images/Logo_eTranslation_v6b.svg' />" ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'etm_settings' ); ?>
        <h1> <?php esc_html_e( 'eTranslation Multilingual Settings', 'etranslation-multilingual' );?></h1>
        <?php do_action ( 'trp_settings_navigation_tabs' ); ?>

        <div id="trp-main-settings__wrap">
            <table id="trp-options" class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Default Language', 'etranslation-multilingual' ); ?> </th>
                    <td>
                        <select id="trp-default-language" name="etm_settings[default-language]" class="trp-select2">
                            <?php
                            foreach( $languages as $language_code => $language_name ){ ?>
                                <option title="<?php echo esc_attr( $language_code ); ?>" value="<?php echo esc_attr( $language_code ); ?>" <?php echo ( $this->settings['default-language'] == $language_code ? 'selected' : '' ); ?> >
                                    <?php echo esc_html( $language_name ); ?>
                                </option>
                            <?php }?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Select the original language of your content.', 'etranslation-multilingual' ); ?>
                        </p>

                        <p class="warning" style="display: none;" >
                            <?php esc_html_e( 'WARNING. Changing the default language will invalidate existing translations.', 'etranslation-multilingual' ); ?><br/>
                            <?php esc_html_e( 'Even changing from en_GB to en_US, because they are treated as two different languages.', 'etranslation-multilingual' ); ?><br/>
                            <?php esc_html_e( 'In most cases changing the default flag is all it is needed: ', 'etranslation-multilingual' ); ?>
                        </p>

                    </td>
                </tr>

                <?php do_action( 'trp_language_selector', $languages ); ?>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Native language name', 'etranslation-multilingual' ); ?> </th>
                    <td>
                        <select id="trp-native-language-name" name="etm_settings[native_or_english_name]" class="trp-select">
                            <option value="english_name" <?php selected( $this->settings['native_or_english_name'], 'english_name' ); ?>><?php esc_html_e( 'No', 'etranslation-multilingual') ?></option>
                            <option value="native_name" <?php selected( $this->settings['native_or_english_name'], 'native_name' ); ?>><?php esc_html_e( 'Yes', 'etranslation-multilingual') ?></option>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Select Yes if you want to display languages in their native names. Otherwise, languages will be displayed in English.', 'etranslation-multilingual' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Use a subdirectory for the default language', 'etranslation-multilingual' ); ?> </th>
                    <td>
                        <select id="trp-subdirectory-for-default-language" name="etm_settings[add-subdirectory-to-default-language]" class="trp-select">
                            <option value="no" <?php selected( $this->settings['add-subdirectory-to-default-language'], 'no' ); ?>><?php esc_html_e( 'No', 'etranslation-multilingual') ?></option>
                            <option value="yes" <?php selected( $this->settings['add-subdirectory-to-default-language'], 'yes' ); ?>><?php esc_html_e( 'Yes', 'etranslation-multilingual') ?></option>
                        </select>
                        <p class="description">
                            <?php echo wp_kses ( __( 'Select Yes if you want to add the subdirectory in the URL for the default language.</br>By selecting Yes, the default language seen by website visitors will become the first one in the "All Languages" list.', 'etranslation-multilingual' ), array( 'br' => array() )  ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Force language in custom links', 'etranslation-multilingual' ); ?> </th>
                    <td>
                        <select id="trp-force-language-in-custom-links" name="etm_settings[force-language-to-custom-links]" class="trp-select">
                            <option value="no" <?php selected( $this->settings['force-language-to-custom-links'], 'no' ); ?>><?php esc_html_e( 'No', 'etranslation-multilingual') ?></option>
                            <option value="yes" <?php selected( $this->settings['force-language-to-custom-links'], 'yes' ); ?>><?php esc_html_e( 'Yes', 'etranslation-multilingual') ?></option>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Select Yes if you want to force custom links without language encoding to keep the currently selected language.', 'etranslation-multilingual' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'Language Switcher', 'etranslation-multilingual' ); ?> </th>
                    <td>
                        <div class="trp-ls-type">
                            <input type="checkbox" disabled checked id="trp-ls-shortcode" ><b><?php esc_html_e( 'Shortcode ', 'etranslation-multilingual' ); ?>[language-switcher] </b>
                            <div>
                                <?php $this->output_language_switcher_select( 'shortcode-options', $this->settings['shortcode-options'] ); ?>
                            </div>
                            <p class="description">
                                <?php esc_html_e( 'Use shortcode on any page or widget.', 'etranslation-multilingual' ); ?>
                            </p>
                        </div>
                        <div class="trp-ls-type">
                            <label><input type="checkbox" id="trp-ls-menu" disabled checked ><b><?php esc_html_e( 'Menu item', 'etranslation-multilingual' ); ?></b></label>
                            <div>
                                <?php $this->output_language_switcher_select( 'menu-options', $this->settings['menu-options'] ); ?>
                            </div>
                            <p class="description">
                                <?php
                                $link_start = '<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) .'">';
                                $link_end = '</a>';
                                printf( wp_kses( __( 'Go to  %1$s Appearance -> Menus%2$s to add languages to the Language Switcher in any menu.', 'etranslation-multilingual' ), [ 'a' => [ 'href' => [] ] ] ), $link_start, $link_end ); //phpcs:ignore ?>
                            </p>
                        </div>
                        <div class="trp-ls-type">
                            <label><input type="checkbox" id="trp-ls-floater" name="etm_settings[trp-ls-floater]"  value="yes"  <?php if ( isset($this->settings['trp-ls-floater']) && ( $this->settings['trp-ls-floater'] == 'yes' ) ){ echo 'checked'; }  ?>><b><?php esc_html_e( 'Floating language selection', 'etranslation-multilingual' ); ?></b></label>
                            <div>
                                <?php $this->output_language_switcher_select( 'floater-options', $this->settings['floater-options'] ); ?>
                                <?php $this->output_language_switcher_floater_color( $this->settings['floater-color'] ); ?>
                                <?php $this->output_language_switcher_floater_possition( $this->settings['floater-position'] ); ?>
                            </div>
                            <p class="description">
                                <?php esc_html_e( 'Add a floating dropdown that follows the user on every page.', 'etranslation-multilingual' ); ?>
                            </p>
                        </div>
                    </td>
                </tr>

                <?php do_action ( 'trp_extra_settings', $this->settings ); ?>
            </table>
        </div>

        <p class="submit"><input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'etranslation-multilingual' ); ?>" /></p>
    </form>
</div>
