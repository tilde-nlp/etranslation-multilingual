<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <?php
        do_action( 'etm_head' );
    ?>

    <title>eTranslation Multilingual</title>
</head>
<body class="etm-editor-body">

    <div id="etm-editor-container">
        <etm-editor
            ref='etm_editor'
        >
        </etm-editor>
    </div>

    <?php do_action( 'etm_translation_manager_footer' ); ?>
</body>
</html>

<?php
