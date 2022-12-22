<?php

class ETM_Preferred_User_Language{

    protected $settings;
    /** @var ETM_Languages */
    protected $etm_languages;
    /** @var ETM_eTranslation_Multilingual */
    protected $etm;

    public function __construct(){}

    public function get_published_languages(){
        if ( ! $this->etm_languages ){
            $etm = ETM_eTranslation_Multilingual::get_etm_instance();
            $this->etm_languages = $etm->get_component( 'languages' );
        }
        if ( ! $this->settings ){
            $etm = ETM_eTranslation_Multilingual::get_etm_instance();
            $etm_settings = $etm->get_component( 'settings' );
            $this->settings = $etm_settings->get_settings();
        }

        $languages_to_display = $this->settings['publish-languages'];

        $published_languages = $this->etm_languages->get_language_names( $languages_to_display );

        return $published_languages;
    }

    public function always_use_this_language($user){

        global $ETM_LANGUAGE;

        $published_languages = $this->get_published_languages();

        $user_ID = 0;
        $user_ID = $user->ID;
        $language = $ETM_LANGUAGE;

        if ($user_ID > 0) {
            $language = get_user_meta( $user_ID, 'etm_language', true );
        }

        if(empty($language) || ! array_key_exists($language, $published_languages) ){
            $language = $this->settings['default-language'];
        }

        $last_visited_language = $published_languages[$language];

        $always_use_this_language = get_user_meta( $user_ID, 'etm_always_use_this_language', true );
?>
<h3><?php esc_html_e( 'eTranslation Multilingual Preferred User Language', 'etranslation-multilingual' ); ?></h3>

<table class="form-table">
    <tr>
        <th><label for="preferred_language"><?php esc_html_e( 'Preferred language to navigate the site', 'etranslation-multilingual' ); ?></label></th>
        <td>
            <select style="width: 350px" name="etm_selected_language">
                <option value="<?php echo esc_attr( $language ); ?>"><?php echo esc_html($last_visited_language); ?></option>
                <?php foreach ($published_languages as $language_code => $language_name){
                    if ($language_code != $language){ ?>
                    <option title="<?php echo esc_attr( $language_code ); ?>" value="<?php echo esc_attr( $language_code ); ?>">
                        <?php echo esc_html( $language_name ); ?>
                    </option>
                <?php } } ?>
            </select>
            <p class="description">
                <?php echo wp_kses_post( __( "The language is automatically set based by the last visited language by the user." , 'etranslation-multilingual' ) ); ?>
            </p>
        </td>
    </tr>
</table>


<table class="form-table">
    <tr>
        <th></th>
        <td>
            <label><input type="checkbox"
                   id="always_use_this_language_checkbox"
                   name="etm_always_use_this_language_checkbox"
                   value="yes"
            <?php if (!empty($always_use_this_language) && $always_use_this_language == 'yes'){ ?> checked <?php } ?>>
            <strong><?php esc_html_e( 'Always use this language', 'etranslation-multilingual' ); ?></strong>
                </input></label>
            <p class="description">
                <?php echo wp_kses_post( __( "By checking this setting the preferred language will remain the one selected above, without the possibility of being changed in the frontend.<br>This language will be used in different operations such as sending email to the user." , 'etranslation-multilingual' ) ); ?>
            </p>

        </td>
    </tr>
</table>

        <?php
    }

    public function update_profile_fields($user_id) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        $published_languages = $this->get_published_languages();

        if(isset($_POST['etm_selected_language']) && in_array($_POST['etm_selected_language'], $this->settings['publish-languages']) && etm_is_valid_language_code($_POST['etm_selected_language'] ) ) { /* phpcs:ignore */

            update_user_meta( $user_id, 'etm_language', $_POST['etm_selected_language'] ); /* phpcs:ignore */  /* the variable was checked in the if statement  */
        }else{
            update_user_meta( $user_id, 'etm_language', $this->settings['default-language'] );
        }

        if(isset($_POST['etm_always_use_this_language_checkbox']) && $_POST['etm_always_use_this_language_checkbox'] == 'yes') {
            update_user_meta( $user_id, 'etm_always_use_this_language', "yes" );
        }else{
            update_user_meta( $user_id, 'etm_always_use_this_language', "no" );
        }

    }

}