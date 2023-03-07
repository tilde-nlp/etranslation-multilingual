<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <?php
    do_action( 'etm_string_translation_editor_head' );
    ?>
    <title>eTranslation Multilingual - <?php esc_html_e('String Translation Editor', 'etranslation-multilingual'); ?> </title>
</head>
<body class="etm-editor-body">

    <div id="etm-editor-container">
        <etm-string-translation
            ref="etm_string_translation_editor"
        >
        </etm-string-translation>
    </div>

    <?php do_action( 'etm_string_translation_editor_footer' ); ?>
</body>
</html>

<?php
