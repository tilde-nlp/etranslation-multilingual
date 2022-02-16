<?php

class TRP_String_Translation_Helper {
    /* @var TRP_Query */
    protected $trp_query;
    /* @var TRP_String_Translation */
    protected $string_translation;
    protected $settings;


    /** Functions used by regular, gettext and slugs from SEO Pack */
    public function check_ajax( $type, $get_or_save ) {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            check_ajax_referer( 'string_translation_' . $get_or_save . '_strings_' . $type, 'security' );

            $action = ( $get_or_save === 'save' ) ? 'trp_save_translations_' : 'trp_string_translation_get_strings_';
            if ( isset( $_POST['action'] ) && $_POST['action'] === $action . $type ) {
                return true;
            }
        }
        wp_die();
    }

    public function get_sanitized_query_args( $string_type ) {
        $trp = TRP_Translate_Press::get_trp_instance();
        if ( !$this->string_translation ) {
            $this->string_translation = $trp->get_component( 'string_translation' );
        }
        if ( !$this->trp_query ) {
            $this->trp_query = $trp->get_component( 'query' );
        }
        if ( !$this->settings ) {
            $trp_settings   = $trp->get_component( 'settings' );
            $this->settings = $trp_settings->get_settings();
        }
        $query_args   = array();
        $posted_query = ( empty( $_POST['query'] ) ) ? array() : json_decode( stripslashes( $_POST['query'] ), true ); /* phpcs:ignore */ /* sanitized below */

        // translation status
        $translation_status_filters = $this->string_translation->get_translation_status_filters();
        $query_args['status']       = array();
        foreach ( $translation_status_filters['translation_status'] as $translation_status_key => $value ) {
            if ( !empty( $posted_query[ $translation_status_key ] ) && ( $posted_query[ $translation_status_key ] === true || $posted_query[ $translation_status_key ] === 'true' ) ) {
                $constant_func_name     = 'get_constant_' . $translation_status_key;
                $query_args['status'][] = $this->trp_query->$constant_func_name();
            }
        }
        if ( count( $query_args['status'] ) === 3 ) {
            // if all 3 states are true then consider the query as if the no special translation status requirement was requested
            $query_args['status'] = array();
        }

        // search string
        $query_args['s'] = ( empty( $posted_query['s'] ) ) ? '' : trim( esc_sql( sanitize_text_field( $posted_query['s'] ) ) );

        // page
        $query_args['page'] = ( empty( $posted_query['page'] ) ? 1 : ( ( intval( $posted_query['page'] ) < 1 ) ? 1 : intval( $posted_query['page'] ) ) );

        // language
        $query_args['language'] = ( !empty( $posted_query['language'] ) && in_array( $posted_query['language'], $this->settings['translation-languages'] ) ) ?
            $posted_query['language'] : '';

        // order
        $query_args['order']   = ( empty( $posted_query['order'] ) || !in_array( $posted_query['order'], array( 'asc', 'desc' ) ) ) ? '' : sanitize_text_field( $posted_query['order'] );
        $query_args['orderby'] = ( empty( $posted_query['orderby'] ) ) ? '' : sanitize_text_field( $posted_query['orderby'] );


        // specific filters for each string type
        $string_types                = $this->string_translation->get_string_types();
        $specific_string_type_config = $string_types[ $string_type ];
        foreach ( $specific_string_type_config['filters'] as $specific_filter_key => $specific_filter_values ) {
            $query_args[ $specific_filter_key ] =
                ( !empty( $posted_query[ $specific_filter_key ] ) && isset( $specific_filter_values[ $posted_query[ $specific_filter_key ] ] ) ) ?
                    $posted_query[ $specific_filter_key ] : '';
        }

        return apply_filters( 'trp_sanitized_query_args', $query_args, $string_type, $string_types );
    }


    /** Functions used for regular and gettext */
    public function add_where_clauses_to_query( $query, $where_clauses ) {
        if ( count( $where_clauses ) > 0 ) {
            $query .= 'WHERE ';
            foreach ( $where_clauses as $where_clause ) {
                $query .= $where_clause . ' AND ';
            }
            $query = rtrim( $query, ' AND ' ) . ' ';
        }
        return $query;
    }

    public function get_language_table_column_based_query_for_filters( $filters, $translation_languages, $sanitized_args ) {
        $where_clauses = array();
        foreach ( $filters as $column_name => $filter_name ) {
            if ( !empty( $sanitized_args[ $filter_name ] ) ) {
                $column_query = '( ';
                foreach ( $translation_languages as $language ) {
                    $column_query .= $this->get_column_query( $column_name, $sanitized_args[ $filter_name ], esc_sql( sanitize_text_field( $language ) ) ) . ' OR ';
                }
                $column_query    = rtrim( $column_query, ' OR ' ) . ' ) ';
                $where_clauses[] = $column_query;
            }
        }
        return $where_clauses;
    }

    public function get_column_query( $column_name, $column_values, $language ) {
        $query = '';

        if ( is_array( $column_values ) ) {
            foreach ( $column_values as $value ) {
                $query .= $language . '.' . $column_name . ' = ' . $value . ' OR ';
            }
        } else {
            $query .= $language . '.' . $column_name . ' = ' . $column_values . ' OR ';
        }
        $query = rtrim( $query, ' OR ' );

        return $query;
    }

    public function get_join_language_table_sql( $table_name, $language ) {
        return 'LEFT JOIN ' . $table_name . ' AS ' . $language . ' ON ' . $language . '.original_id = original_strings.id ';
    }

}
