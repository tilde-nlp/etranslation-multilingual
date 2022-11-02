<h2 class="nav-tab-wrapper">
        <?php
        $trp = TRP_Translate_Press::get_trp_instance();
        $settings = $trp->get_component('settings');
        foreach( $tabs as $tb ) {
            $id_str = "";
            if ($tb['page'] == 'etranslation-multilingual' && !$settings->mt_setup_done()) {
                $id_str = 'id="show-login-alert" ';
            }
            echo '<a href="' . esc_url( $tb['url'] ) . '" '. $id_str . ( $tb['page'] == 'trp_translation_editor' ? 'target="_blank"' : '' ) .' class="nav-tab ' . ( ( $active_tab == $tb['page'] ) ? 'nav-tab-active' : '' ) . ( ( $tb['page'] == 'trp_translation_editor' ) ? 'trp-translation-editor' : '' ) . '">' . esc_html( $tb['name'] ) . '</a>';
        }
        ?>
</h2>
