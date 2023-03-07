<div id="etm-addons-page" class="wrap">

    <h1> <?php esc_html_e( 'eTranslation Multilingual Settings', 'etranslation-multilingual' );?></h1>

    <div class="grid feat-header">
        <div class="grid-cell">
            <h2><?php esc_html_e('Optimize eTranslation Multilingual database tables', 'etranslation-multilingual' );?> </h2>
	        <?php if ( empty( $_GET['etm_rm_duplicates'] ) ){ ?>
                <div>
			        <?php echo wp_kses_post( __( '<strong>IMPORTANT NOTE: Before performing this action it is strongly recommended to first backup the database.</strong><br><br>', 'etranslation-multilingual' ) )?>
                </div>
                <form onsubmit="return confirm('<?php echo esc_js( __( 'IMPORTANT: It is strongly recommended to first backup the database!! Are you sure you want to continue?', 'etranslation-multilingual' ) ); ?>');">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_attr_e('Operations to perform', 'etranslation-multilingual');?></th>
                            <td>
                                <input type="hidden" name="etm_rm_nonce" value="<?php echo esc_attr( wp_create_nonce('tpremoveduplicaterows') )?>">
                                <input type="hidden" name="page" value="etm_remove_duplicate_rows">
                                <input type="hidden" name="etm_rm_batch" value="1">
                                <input type="hidden" name="etm_rm_duplicates" value="<?php echo esc_attr( $this->settings['translation-languages'][0] ); ?>">

                                <input type="checkbox" name="etm_rm_cdata_original_and_dictionary" id="etm_rm_cdata_original_and_dictionary" checked><label for="etm_rm_cdata_original_and_dictionary"><?php esc_attr_e( 'Remove CDATA for original and dictionary strings', 'etranslation-multilingual' ); ?></label></input><br>
                                <p class="description">
                                    <?php echo wp_kses ( __( 'Removes CDATA from etm_original_strings and etm_dictionary_* tables.<br>This type of content should not be detected by eTranslation Multilingual. It might have been introduced in the database in older versions of the plugin.', 'etranslation-multilingual' ), array( 'br' => array() )  ); ?>
                                </p>
                                <br>
                                <input type="checkbox" name="etm_rm_untranslated_links" id="etm_rm_untranslated_links" checked><label for="etm_rm_untranslated_links"><?php esc_attr_e( 'Remove untranslated links from dictionary tables', 'etranslation-multilingual' ); ?></label></input><br>
                                <p class="description">
                                    <?php echo wp_kses ( __( 'Removes untranslated links and images from all etm_dictionary_* tables. These tables contain translations for user-inputted strings such as post content, post title, menus etc.', 'etranslation-multilingual' ), array( 'br' => array() )  ); ?>
                                </p>
                                <br>
                                <input type="checkbox" name="etm_rm_duplicates_gettext" id="etm_rm_duplicates_gettext" checked><label for="etm_rm_duplicates_gettext"><?php esc_attr_e( 'Remove duplicate rows for gettext strings', 'etranslation-multilingual' ); ?></label></input><br>
                                <p class="description">
                                    <?php echo wp_kses ( __( 'Cleans up all etm_gettext_* tables of duplicate rows. These tables contain translations for themes and plugin strings.', 'etranslation-multilingual' ), array( 'br' => array() )  ); ?>
                                </p>
                                <br>
                                <input type="checkbox" name="etm_rm_duplicates_dictionary" id="etm_rm_duplicates_dictionary" checked><label for="etm_rm_duplicates_dictionary"><?php esc_attr_e( 'Remove duplicate rows for dictionary strings', 'etranslation-multilingual' ); ?></label></input><br>
                                <p class="description">
                                    <?php echo wp_kses ( __( 'Cleans up all etm_dictionary_* tables of duplicate rows. These tables contain translations for user-inputted strings such as post content, post title, menus etc.', 'etranslation-multilingual' ), array( 'br' => array() )  ); ?>
                                </p>
                                <br>
                                <input type="checkbox" name="etm_rm_duplicates_original_strings" id="etm_rm_duplicates_original_strings" checked><label for="etm_rm_duplicates_original_strings"><?php esc_attr_e( 'Remove duplicate rows for original dictionary strings', 'etranslation-multilingual' ); ?></label></input><br>
                                <p class="description">
                                    <?php echo wp_kses ( __( 'Cleans up all etm_original_strings table of duplicate rows. This table contains strings in the default language, without any translation.<br>The etm_original_meta table, which contains meta information that refers to the post parent’s id, is also regenerated.<br>Such duplicates can appear in exceptional situations of unexpected behavior.', 'etranslation-multilingual' ), array( 'br' => array() )  ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <br>
                    <input type="submit" class="button-primary" name="etm_rm_duplicates_of_the_selected_option" value="<?php esc_attr_e( 'Optimize Database', 'etranslation-multilingual' ); ?>">
                </form>
            <?php } ?>

        </div>
    </div>

</div>