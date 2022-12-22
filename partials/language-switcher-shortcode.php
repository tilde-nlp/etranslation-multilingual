<?php
$current_language_preference = $this->add_shortcode_preferences($shortcode_settings, $current_language['code'], $current_language['name']);

?>
<div class="etm_language_switcher_shortcode">
<div class="etm-language-switcher etm-language-switcher-container" data-no-translation <?php echo ( isset( $_GET['etm-edit-translation'] ) && $_GET['etm-edit-translation'] == 'preview' ) ? 'data-etm-unpreviewable="etm-unpreviewable"' : '' ?>>
    <div class="etm-ls-shortcode-current-language">
        <a href="#" class="etm-ls-shortcode-disabled-language etm-ls-disabled-language" title="<?php echo esc_attr( $current_language['name'] ); ?>" onclick="event.preventDefault()">
			<?php echo $current_language_preference; /* phpcs:ignore */ /* escaped inside the function that generates the output */ ?>
		</a>
    </div>
    <div class="etm-ls-shortcode-language">
        <?php if ( apply_filters('etm_ls_shortcode_show_disabled_language', true, $current_language, $current_language_preference, $this->settings ) ){ ?>
        <a href="#" class="etm-ls-shortcode-disabled-language etm-ls-disabled-language"  title="<?php echo esc_attr( $current_language['name'] ); ?>" onclick="event.preventDefault()">
			<?php echo $current_language_preference; /* phpcs:ignore */ /* escaped inside the function that generates the output */ ?>
		</a>
        <?php } ?>
    <?php foreach ( $other_languages as $code => $name ){

        $language_preference = $this->add_shortcode_preferences($shortcode_settings, $code, $name);
        ?>
        <a href="<?php echo esc_url( $this->url_converter->get_url_for_language($code, false) ); ?>" title="<?php echo esc_attr( $name ); ?>">
            <?php echo $language_preference; /* phpcs:ignore */ /* escaped inside the function that generates the output */ ?>
        </a>

    <?php } ?>
    </div>
    <script type="application/javascript">
        // need to have the same with set from JS on both divs. Otherwise it can push stuff around in HTML
        var etm_ls_shortcodes = document.querySelectorAll('.etm_language_switcher_shortcode .etm-language-switcher');
        if ( etm_ls_shortcodes.length > 0) {
            // get the last language switcher added
            var etm_el = etm_ls_shortcodes[etm_ls_shortcodes.length - 1];

            var etm_shortcode_language_item = etm_el.querySelector( '.etm-ls-shortcode-language' )
            // set width
            var etm_ls_shortcode_width                                               = etm_shortcode_language_item.offsetWidth + 16;
            etm_shortcode_language_item.style.width                                  = etm_ls_shortcode_width + 'px';
            etm_el.querySelector( '.etm-ls-shortcode-current-language' ).style.width = etm_ls_shortcode_width + 'px';

            // We're putting this on display: none after we have its width.
            etm_shortcode_language_item.style.display = 'none';
        }
    </script>
</div>
</div>