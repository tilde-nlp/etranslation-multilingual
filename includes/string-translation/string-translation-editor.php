<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <?php
    do_action( 'trp_string_translation_editor_head' );
    ?>
    <title>eTranslation Multilingual - <?php esc_html_e('String Translation Editor', 'etranslation-multilingual'); ?> </title>
</head>
<body class="trp-editor-body">

    <div id="trp-editor-container">
        <trp-string-translation
            ref="trp_string_translation_editor"
        >
        </trp-string-translation>
    </div>

    <?php do_action( 'trp_string_translation_editor_footer' ); ?>
</body>
</html>

<?php
