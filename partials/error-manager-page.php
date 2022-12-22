<div id="etm-errors-page" class="wrap">

    <h1> <?php esc_html_e( 'eTranslation Multilingual Errors', 'etranslation-multilingual' );?></h1>
    <?php $page_output = apply_filters( 'etm_error_manager_page_output', '' );
    if ( $page_output === '' ){
        $page_output = esc_html__('There are no logged errors.', 'etranslation-multilingual');
    }
    echo $page_output; /* phpcs:ignore */ /* sanitized in the functions hooked to the filters */

    ?>

</div>
