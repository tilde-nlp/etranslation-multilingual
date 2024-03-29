<?php

class ETM_Advanced_Tab {

	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	/*
	 * Add new tab to ETM settings
	 *
	 * Hooked to etm_settings_tabs
	 */
	public function add_advanced_tab_to_settings( $tab_array ) {
		$tab_array[] = array(
			'name' => __( 'Advanced', 'etranslation-multilingual' ),
			'url'  => admin_url( 'admin.php?page=etm_advanced_page' ),
			'page' => 'etm_advanced_page',
		);
		return $tab_array;
	}

	/*
	 * Add submenu for advanced page tab
	 *
	 * Hooked to admin_menu
	 */
	public function add_submenu_page_advanced() {
		add_submenu_page(
			'ETMHidden',
			'eTranslation Multilingual Advanced Settings',
			'ETMHidden',
			apply_filters( 'etm_settings_capability', 'manage_options' ),
			'etm_advanced_page',
			array(
				$this,
				'advanced_page_content',
			)
		);
	}

	/**
	 * Register setting
	 *
	 * Hooked to admin_init
	 */
	public function register_setting() {
		register_setting( 'etm_advanced_settings', 'etm_advanced_settings', array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Output admin notices after saving settings.
	 */
	public function admin_notices() {
		settings_errors( 'etm_advanced_settings' );
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings( $submitted_settings ) {
		$registered_settings = $this->get_registered_advanced_settings();
		$prev_settings       = get_option( 'etm_advanced_settings', array() );

		$settings = array();
		foreach ( $registered_settings as $registered_setting ) {

			// checkboxes are not set so we're setting them up as false
			if ( ! isset( $submitted_settings[ $registered_setting['name'] ] ) ) {
				$submitted_settings[ $registered_setting['name'] ] = false;
			}

			if ( isset( $submitted_settings[ $registered_setting['name'] ] ) ) {
				switch ( $registered_setting['type'] ) {
					case 'checkbox': {
						$settings[ $registered_setting['name'] ] = ( $submitted_settings[ $registered_setting['name'] ] === 'yes' ) ? 'yes' : 'no';
						break;
					}
					case 'select':
					case 'input': {
						$settings[ $registered_setting['name'] ] = sanitize_text_field( $submitted_settings[ $registered_setting['name'] ] );
						break;
					}
					case 'radio' : {
						$settings[ $registered_setting['name'] ] = sanitize_text_field( $submitted_settings[ $registered_setting['name'] ] );
						break;
					}
					case 'custom': {
						foreach ( $registered_setting['rows'] as $row_label => $row_type ) {
							if ( isset( $submitted_settings[ $registered_setting['name'] ][ $row_label ] ) ) {

								if ( $row_type != 'textarea' ) {
									$value = sanitize_text_field( $submitted_settings[ $registered_setting['name'] ][ $row_label ] );
								} else {
									$value = sanitize_textarea_field( $submitted_settings[ $registered_setting['name'] ][ $row_label ] );
								}

								$settings[ $registered_setting['name'] ][ $row_label ] = $value;

							}
						}
						break;
					}
					case 'input_array': {
						foreach ( $registered_setting['rows'] as $row_label => $row_name ) {
							if ( isset( $submitted_settings[ $registered_setting['name'] ][ $row_label ] ) ) {
									$settings[ $registered_setting['name'] ][ $row_label ] = sanitize_text_field( $submitted_settings[ $registered_setting['name'] ][ $row_label ] );
							}
						}
						break;
					}
					case 'number': {
						$settings[ $registered_setting['name'] ] = sanitize_text_field( intval( $submitted_settings[ $registered_setting['name'] ] ) );
						break;
					}
					case 'list':
					case 'mixed':
						/*
						We use the same parsing and saving mechanism for list and mixed advanced types.
						*/
						{
						$settings[ $registered_setting['name'] ] = array();
						$one_column                              = '';
						foreach ( $registered_setting['columns'] as $column => $column_name ) {
							$one_column = ( empty( $one_column ) && ! ( is_array( $column_name ) && $column_name ['type'] === 'checkbox' ) ) ? $column : $one_column;
							$settings[ $registered_setting['name'] ][ $column ] = array();
							if ( isset( $submitted_settings[ $registered_setting['name'] ][ $column ] ) ) {
								foreach ( $submitted_settings[ $registered_setting['name'] ][ $column ] as $key => $value ) {
									$settings[ $registered_setting['name'] ][ $column ][] = sanitize_text_field( $value );
								}
							}
						}

						 /*
						  If the setting is a type "checkbox" we remove one empty value from the sub-array if it comes after a 'yes' value
							In this case we properly save an empty value for an unchecked checkbox
							and also control the display checked/unchecked on the frontend
						 */
						foreach ( $registered_setting['columns'] as $column => $column_name ) {
							if ( is_array( $column_name ) && $column_name ['type'] === 'checkbox' ) {
								foreach ( $settings[ $registered_setting['name'] ] [ $column ] as $submitted_key => $submitted_value ) {
									if ( $submitted_value === 'yes' ) {
										unset( $settings[ $registered_setting['name'] ] [ $column ] [ $submitted_key + 1 ] );
									}
									// Check for illegal values at checkbox side
									if ( ! $submitted_value === 'yes' || ! $submitted_value === '' ) {
										$settings[ $registered_setting['name'] ] [ $column ] [ $submitted_key ] = '';
									}
								}
							}
						}

						// remove empty rows except checkboxes
						foreach ( $settings[ $registered_setting['name'] ][ $one_column ] as $key => $value ) {
							$is_empty = true;
							foreach ( $registered_setting['columns'] as $column => $column_name ) {
								if ( $settings[ $registered_setting['name'] ][ $column ][ $key ] != '' || ( is_array( $column_name ) && $column_name ['type'] === 'checkbox' ) ) {
									$is_empty = false;
									break;
								}
							}
							if ( $is_empty ) {
								foreach ( $registered_setting['columns'] as $column => $column_name ) {

									unset( $settings[ $registered_setting['name'] ][ $column ][ $key ] );
								}
							}
						}

						foreach ( $settings[ $registered_setting['name'] ] as $column => $value ) {
							$settings[ $registered_setting['name'] ][ $column ] = array_values( $settings[ $registered_setting['name'] ][ $column ] );
						}
						break;
					}
				}
			} //endif

			// not all settings are updated by the user. Some are modified by the program and used as storage.
			// This is somewhat bad from a data model kind of way, but it's easy to pass the $settings variable around between classes.
			if ( isset( $registered_setting['data_model'] )
				&& $registered_setting['data_model'] == 'not_updatable_by_user'
				&& isset( $prev_settings[ $registered_setting['name'] ] )
			) {
				$settings[ $registered_setting['name'] ] = $prev_settings[ $registered_setting['name'] ];
			}
		} //end foreach of parsing all the registered settings array

		if ( apply_filters( 'etm_saving_advanced_settings_is_successful', true, $settings, $submitted_settings ) ) {
			add_settings_error( 'etm_advanced_settings', 'settings_updated', esc_html__( 'Settings saved.', 'etranslation-multilingual' ), 'updated' );
		}

		return apply_filters( 'etm_extra_sanitize_advanced_settings', $settings, $submitted_settings, $prev_settings );
	}

	/*
	 * Advanced page content
	 */

	public function get_registered_advanced_settings() {
		return apply_filters( 'etm_register_advanced_settings', array() );
	}

	/*
	 * Require the custom codes from the specified folder
	 */

	public function advanced_page_content() {
		require_once ETM_PLUGIN_DIR . 'partials/advanced-settings-page.php';
	}

	/*
	 * Get array of registered options from custom code to display in Advanced Settings page
	 */

	public function include_custom_codes() {
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/disable-dynamic-translation.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/force-slash-at-end-of-links.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/enable-numerals-translation.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/custom-date-format.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/custom-language.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/exclude-dynamic-selectors.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/exclude-gettext-strings.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/exclude-selectors.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/exclude-selectors-automatic-translation.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/fix-broken-html.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/show-dynamic-content-before-translation.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/enable-hreflang-xdefault.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/strip-gettext-post-content.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/strip-gettext-post-meta.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/exclude-words-from-auto-translate.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/disable-post-container-tags.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/separators.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/remove-duplicates-from-db.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/do-not-translate-certain-paths.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/opposite-flag-shortcode.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/regular-tab-string-translation.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/open-language-switcher-shortcode-on-click.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/hreflang-remove-locale.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/etranslation-wait-timeout.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/html-lang-remove-locale.php';
		include_once ETM_PLUGIN_DIR . 'includes/advanced-settings/disable-gettext-strings.php';
	}

	/*
	 * Hooked to etm_before_output_advanced_settings_options
	 */

	function etm_advanced_settings_content_table() {
		$advanced_settings_array = $this->get_registered_advanced_settings();

		$html                    = '<p id="etm_advanced_tab_content_table">';
		$advanced_settings_array = apply_filters( 'etm_advanced_tab_add_element', $advanced_settings_array );
		foreach ( $advanced_settings_array as $setting ) {
			if ( $setting['type'] !== 'separator' ) {
				continue;
			}
			$html .= '<a class="etm_advanced_tab_content_table_item" href="#' . esc_attr( $setting['name'] ) . '">' . esc_html( $setting['label'] ) . '</a> | ';
		}
		$html  = rtrim( $html, ' | ' );
		$html .= '</p>';
		escape_and_echo_html( $html );
	}


	/*
	 * Hooked to etm_settings_navigation_tabs
	 */
	public function output_advanced_options() {
		$advanced_settings_array = $this->get_registered_advanced_settings();
		foreach ( $advanced_settings_array as $setting ) {
			switch ( $setting['type'] ) {
				case 'checkbox':
					escape_and_echo_html( $this->checkbox_setting( $setting ) );
					break;
				case 'radio':
					escape_and_echo_html( $this->radio_setting( $setting ) );
					break;
				case 'input':
					escape_and_echo_html( $this->input_setting( $setting ) );
					break;
				case 'number':
					escape_and_echo_html( $this->input_setting( $setting, 'number' ) );
					break;
				case 'input_array':
					escape_and_echo_html( $this->input_array_setting( $setting ) );
					break;
				case 'select':
					escape_and_echo_html( $this->select_setting( $setting ) );
					break;
				case 'separator':
					escape_and_echo_html( $this->separator_setting( $setting ) );
					break;
				case 'list':
					escape_and_echo_html( $this->add_to_list_setting( $setting ) );
					break;
				case 'text':
					escape_and_echo_html( $this->text_setting( $setting ) );
					break;
				case 'mixed':
					escape_and_echo_html( $this->mixed_setting( $setting ) );
					break;
				case 'custom':
					escape_and_echo_html( $this->custom_setting( $setting ) );
					break;
			}
		}
	}

	/**
	 * Return HTML of a checkbox type setting
	 *
	 * @param $setting
	 *
	 * @return 'string'
	 */
	public function checkbox_setting( $setting ) {
		$adv_option = $this->settings['etm_advanced_settings'];
		$checked    = ( isset( $adv_option[ $setting['name'] ] ) && $adv_option[ $setting['name'] ] === 'yes' ) ? 'checked' : '';
		$html       = "
             <tr>
                <th scope='row'>" . esc_html( $setting['label'] ) . "</th>
                <td>
	                <label>
	                    <input type='checkbox' id='" . esc_attr( $setting['name'] ) . "' name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . "]' value='yes' " . $checked . '>
	                    ' . __( 'Yes', 'etranslation-multilingual' ) . "
			        </label>
                    <p class='description'>
                        " . wp_kses_post( $setting['description'] ) . '
                    </p>
                </td>
            </tr>';
		return apply_filters( 'etm_advanced_setting_checkbox', $html );
	}

	/**
	 * Return HTML of a radio button type setting
	 *
	 * @param $setting
	 *
	 * @return 'string'
	 */
	public function radio_setting( $setting ) {
		$adv_option = $this->settings['etm_advanced_settings'];
		$html       = "
             <tr>
                <th scope='row'>" . esc_html( $setting['label'] ) . "</th>
                <td class='etm-adst-radio'>";

		foreach ( $setting['options'] as $key => $option ) {

			if ( isset( $adv_option[ $setting['name'] ] ) && ! empty( $adv_option[ $setting['name'] ] ) ) {
				if ( $adv_option[ $setting['name'] ] === $option ) {
					$checked = 'checked="checked"';
				} else {
					$checked = '';
				}
			} else {
				if ( $setting['default'] === $option ) {
					$checked = 'checked="checked"';
				} else {
					$checked = '';
				}
			}
			$setting_name = $setting['name'];
			$label        = $setting['labels'][ $key ];
			$html        .= "<label>
	                    <input type='radio' id='" . esc_attr( $setting_name ) . "' name='etm_advanced_settings[" . esc_attr( $setting_name ) . "]' value='" . esc_attr( $option ) . "' $checked >
	                    " . esc_html( $label ) . '
			          </label>';
		}

		$html .= "  <p class='description'>
                        " . wp_kses_post( $setting['description'] ) . '
                    </p>
                </td>
            </tr>';
		return apply_filters( 'etm_advanced_setting_radio', $html );
	}

	/**
	 * Return HTML of a input type setting
	 *
	 * @param array  $setting
	 * @param string $type
	 *
	 * @return 'string'
	 */
	public function input_setting( $setting, $type = 'text' ) {
		$adv_option = $this->settings['etm_advanced_settings'];
		$default    = ( isset( $setting['default'] ) ) ? $setting['default'] : '';
		$value      = ( isset( $adv_option[ $setting['name'] ] ) ) ? $adv_option[ $setting['name'] ] : $default;
		$html       = "
             <tr>
                <th scope='row'>" . esc_html( $setting['label'] ) . "</th>
                <td>
	                <label>
	                    <input type='" . esc_attr( $type ) . "' id='" . esc_attr( $setting['name'] ) . "' name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . "]' value='" . esc_attr( $value ) . "'>
			        </label>
                    <p class='description'>
                        " . wp_kses_post( $setting['description'] ) . '
                    </p>
                </td>
            </tr>';
		return apply_filters( 'etm_advanced_setting_input', $html );
	}

	/**
	 * Return HTML of an array type setting
	 *
	 * @param $setting
	 * @param string  $type
	 *
	 * @return 'string'
	 */
	public function input_array_setting( $setting, $type = 'text' ) {
		$adv_option = $this->settings['etm_advanced_settings'];
		$default    = ( isset( $setting['default'] ) ) ? $setting['default'] : '';

		$html = "
             <tr>
                <th scope='row'>" . esc_html( $setting['label'] ) . "</th>
                <td>
                <table class='form-table advanced-setting-input-array'>";
		foreach ( $setting['rows'] as $row_label => $row_name ) {
			$value = ( isset( $adv_option[ $setting['name'] ][ $row_label ] ) ) ? $adv_option[ $setting['name'] ][ $row_label ] : $default;

			$html .= "
			    <tr>
			        <td><label for='" . esc_attr( $setting['name'] ) . '-' . esc_attr( $row_label ) . "'> " . esc_attr( $row_name ) . " </label></td><td><input type='" . esc_attr( $type ) . "' id='" . esc_attr( $setting['name'] ) . '-' . esc_attr( $row_label ) . "' name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $row_label ) . "]' value='" . esc_attr( $value ) . "'>
			        </td>
			    </tr>";
		}
		$html .= "</table>
<p class='description'>" . wp_kses_post( $setting['description'] ) . '</p>
                </td>
            </tr>';
		return apply_filters( 'etm_advanced_setting_input_array', $html );
	}

	/**
	 * Return HTML of a input type setting
	 *
	 * @param array  $setting
	 * @param string $type
	 *
	 * @return 'string'
	 */
	public function select_setting( $setting ) {
		$option  = get_option( 'etm_advanced_settings', true );
		$default = ( isset( $setting['default'] ) ) ? $setting['default'] : '';
		$value   = ( isset( $option[ $setting['name'] ] ) ) ? $option[ $setting['name'] ] : $default;

		$options = '';
		foreach ( $setting['options'] as $lang => $label ) {
			( $value == $lang ) ? $selected = 'selected' : $selected = '';
			$options                       .= "<option value='" . esc_attr( $lang ) . "' $selected>" . esc_html( $label ) . '</option>';
		}

		$html = "
             <tr>
                <th scope='row'>" . esc_html( $setting['label'] ) . "</th>
                <td>
	                <label>
	                    <select class='advanced-setting-select' id='" . esc_attr( $setting['name'] ) . "' name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . "]'>
	                        " . $options . "
	                    </select>
			        </label>
                    <p class='description'>
                        " . wp_kses_post( $setting['description'] ) . '
                    </p>
                </td>
            </tr>';
		return apply_filters( 'etm_advanced_setting_select', $html );
	}

	/**
	 * Return HTML of a separator type setting
	 *
	 * @param $setting
	 *
	 * @return 'string'
	 */
	public function separator_setting( $setting ) {
		$html = '';
		if ( ! isset( $setting['no-border'] ) || $setting['no-border'] !== true ) {
			 $html .= "
             <tr class='advanced-setting-separator' id='" . esc_attr( $setting['name'] ) . "'>
                <th scope='row'></th>
                <td></td>
            </tr>";
		}
		$html .= '<tr><td><h2>' . esc_html( $setting['label'] ) . '<h2></td></tr>';
		return apply_filters( 'etm_advanced_setting_separator', $html );
	}

	/**
	 * Return HTML of a checkbox type setting
	 *
	 * @param $setting
	 *
	 * @return 'string'
	 */
	public function add_to_list_setting( $setting ) {
		$adv_option = $this->settings['etm_advanced_settings'];
		$html       = "
             <tr>
                <th scope='row'>" . esc_html( $setting['label'] ) . "</th>
                <td>
	                <table class='etm-adst-list-option'>
						<thead>
							";
		foreach ( $setting['columns'] as $key => $value ) {
			$html .= '<th><strong>' . esc_html( $value ) . '</strong></th>';
		}
		// "Remove" button
		$html .= '<th></th>';

		// list existing entries
		$html .= '		</thead>';

		$first_column = '';
		foreach ( $setting['columns'] as $column => $column_name ) {
			$first_column = $column;
			break;
		}
		if ( isset( $adv_option[ $setting['name'] ] ) && is_array( $adv_option[ $setting['name'] ] ) ) {
			foreach ( $adv_option[ $setting['name'] ][ $first_column ] as $index => $value ) {
				$html .= "<tr class='etm-list-entry'>";
				foreach ( $setting['columns'] as $column => $column_name ) {
					$html .= "<td><textarea name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $column ) . "][]'>" . htmlspecialchars( $adv_option[ $setting['name'] ][ $column ][ $index ], ENT_QUOTES ) . '</textarea></td>';
				}
				$html .= "<td><span class='etm-adst-remove-element' data-confirm-message='" . esc_html__( 'Are you sure you want to remove this item?', 'etranslation-multilingual' ) . "'>" . esc_html__( 'Remove', 'etranslation-multilingual' ) . '</span></td>';
				$html .= '</tr>';
			}
		}

		// add new entry to list
		$html .= "<tr class='etm-add-list-entry etm-list-entry'>";
		foreach ( $setting['columns'] as $column => $column_name ) {
			$html .= "<td><textarea id='new_entry_" . esc_attr( $setting['name'] ) . '_' . esc_attr( $column ) . "' data-name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $column ) . "][]' data-setting-name='" . esc_attr( $setting['name'] ) . "' data-column-name='" . esc_attr( $column ) . "'></textarea></td>";

		}
		$html .= "<td><input type='button' class='button-secondary etm-adst-button-add-new-item' value='" . esc_html__( 'Add', 'etranslation-multilingual' ) . "'><span class='etm-adst-remove-element d-none' data-confirm-message='" . esc_html__( 'Are you sure you want to remove this item?', 'etranslation-multilingual' ) . "'>" . esc_html__( 'Remove', 'etranslation-multilingual' ) . '</span></td>';
		$html .= '</tr></table>';

		$html .= "<p class='description'>
                        " . wp_kses_post( $setting['description'] ) . '
                    </p>
                </td>
            </tr>';
		return apply_filters( 'etm_advanced_setting_list', $html );
	}

	/**
	 * Return HTML of a text type setting
	 *
	 * @param $setting
	 *
	 * @return 'string'
	 */
	public function text_setting( $setting ) {
		$html = "
             <tr>
                <th scope='row'>" . esc_html( $setting['label'] ) . "</th>
                <td>
	                <p class='description'>
                        " . wp_kses_post( $setting['description'] ) . '
                    </p>
                </td>
            </tr>';
		return apply_filters( 'etm_advanced_setting_text', $html );
	}

	public function mixed_setting( $setting ) {
		$adv_option = $this->settings['etm_advanced_settings'];
		$html       = "
             <tr>
                <th scope='row'>" . esc_html( $setting['label'] ) . "</th>
                <td>
	                <table class='etm-adst-list-option'>
						<thead>
							";
		foreach ( $setting['columns'] as $option_name => $option_details ) {
			if ( isset( $setting['columns'][ $option_name ]['required'] ) && $setting['columns'][ $option_name ]['required'] === true ) {
				$html .= '<th class="etm_lang_code"><strong>' . esc_html( $option_details['label'] ) . '<span title="Required"> *</span> </strong></th>';
			} else {
				$html .= '<th><strong>' . esc_html( $option_details['label'] ) . '</strong></th>';
			}
		}

		// "Remove" button
		$html .= '<th></th>';

		// list existing entries
		$html .= '		</thead>';

		$first_column = '';
		foreach ( $setting['columns'] as $column => $column_name ) {
			$first_column = $column;
			break;
		}

		if ( isset( $adv_option[ $setting['name'] ] ) && is_array( $adv_option[ $setting['name'] ] ) ) {
			foreach ( $adv_option[ $setting['name'] ][ $first_column ] as $index => $value ) {

				$html .= "<tr class='etm-list-entry'>";

				foreach ( $setting['columns'] as $option_name => $option_details ) {
					switch ( $option_details['type'] ) {
						case 'text':
							$html .= "<td class=' " . $option_name . " '><input class='etm_narrow_input' type='text' name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]' value='" . htmlspecialchars( $adv_option[ $setting['name'] ][ $option_name ][ $index ], ENT_QUOTES ) . "'></td>";

							break;
						case 'textarea':
							$html .= "<td><textarea class='etm_narrow_input' name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]'>" . htmlspecialchars( $adv_option[ $setting['name'] ][ $option_name ][ $index ], ENT_QUOTES ) . '</textarea></td>';
							break;
						case 'select':
							$html .= "<td><select class='etm-select-advanced' name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]'>";
							$html .= "<option value=''>" . esc_html__( 'Select...', 'etranslation-multilingual' ) . '</option>';
							foreach ( $option_details['values'] as $select_key => $select_value ) {
								$selected = ( $adv_option[ $setting['name'] ][ $option_name ][ $index ] === $select_value ) ? "selected='selected'" : '';
								$html    .= "<option value='" . esc_attr( $select_value ) . "'$selected>" . esc_html( $select_value ) . '</option>';
							}
							$html .= '</select></td>';
							break;
						case 'checkbox':
							$datavalue = isset( $adv_option[ $setting['name'] ][ $option_name ][ $index ] ) ? htmlspecialchars( $adv_option[ $setting['name'] ][ $option_name ][ $index ], ENT_QUOTES ) : '';
							$checked   = ( $datavalue === 'yes' ) ? "checked='checked'" : '';
							$html     .= "<td><input type='checkbox' class='etm-adv-chk' name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]' id='new_entry_" . esc_attr( $setting['name'] ) . '_' . esc_attr( $option_name ) . "' data-name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]' data-setting-name='" . esc_attr( $setting['name'] ) . "' data-column-name='" . esc_attr( $option_name ) . "' value='yes' " . $checked . '>';
							$html     .= "<input type='hidden' name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]' id='new_entry_" . esc_attr( $setting['name'] ) . '_' . esc_attr( $option_name ) . "' data-name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]' data-setting-name='" . esc_attr( $setting['name'] ) . "' data-column-name='" . esc_attr( $option_name ) . "' value=''>";
							$html     .= '</td>';
							break;
					}
				}
				$html .= "<td><span class='etm-adst-remove-element' data-confirm-message='" . esc_html__( 'Are you sure you want to remove this item?', 'etranslation-multilingual' ) . "'>" . esc_html__( 'Remove', 'etranslation-multilingual' ) . '</span></td>';
				$html .= '</tr>';
			}
		}
		// Add new entry to list; renders the last row which is initially empty.
		$html .= "<tr class='etm-add-list-entry etm-list-entry'>";

		foreach ( $setting['columns'] as $option_name => $option_details ) {

			switch ( $option_details['type'] ) {
				case 'text':
					$html .= "<td class=' " . $option_name . " '><input type='text' class='etm_narrow_input' id='new_entry_" . esc_attr( $setting['name'] ) . '_' . esc_attr( $option_name ) . "' data-name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]' data-setting-name='" . esc_attr( $setting['name'] ) . "' data-column-name='" . esc_attr( $option_name ) . "' placeholder='" . esc_attr( $setting['columns'][ $option_name ]['placeholder'] ) . "' '></input></td>";
					break;
				case 'textarea':
					$html .= "<td class='etm_narrow_input'><textarea id='new_entry_" . esc_attr( $setting['name'] ) . '_' . esc_attr( $option_name ) . "' data-name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]' data-setting-name='" . esc_attr( $setting['name'] ) . "' data-column-name='" . esc_attr( $option_name ) . "'></textarea></td>";
					break;
				case 'select':
					$html .= "<td><select id='new_entry_" . esc_attr( $setting['name'] ) . '_' . esc_attr( $option_name ) . "' data-name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]' data-setting-name='" . esc_attr( $setting['name'] ) . "' data-column-name='" . esc_attr( $option_name ) . "'>";
					$html .= "<option value=''>" . __( 'Select...', 'etranslation-multilingual' ) . '</option>';
					foreach ( $option_details['values'] as $select_key => $select_value ) {
						$html .= "<option value='" . esc_attr( $select_value ) . "'>" . esc_html( $select_value ) . '</option>';
					}
					$html .= '</select></td>';
					break;
				case 'checkbox':
					$html .= "<td><input type='checkbox' class='etm-adv-chk' id='new_entry_" . esc_attr( $setting['name'] ) . '_' . esc_attr( $option_name ) . "' data-name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]' data-column-name='" . esc_attr( $option_name ) . "' value='yes'>";
					$html .= "<input type='hidden' id='new_entry_" . esc_attr( $setting['name'] ) . '_' . esc_attr( $option_name ) . "' data-name='etm_advanced_settings[" . esc_attr( $setting['name'] ) . '][' . esc_attr( $option_name ) . "][]' data-column-name='" . esc_attr( $option_name ) . "' value=''>";
					$html .= '</td>';
					break;
			}
		}
		$html .= "<td><input type='button' id='button_add_" . esc_attr( $setting['name'] ) . "' class='button-secondary etm-adst-button-add-new-item' value='" . esc_html__( 'Add', 'etranslation-multilingual' ) . "'><span class='etm-adst-remove-element d-none' data-confirm-message='" . esc_html__( 'Are you sure you want to remove this item?', 'etranslation-multilingual' ) . "'>" . esc_html__( 'Remove', 'etranslation-multilingual' ) . '</span></td>';
		$html .= '</tr></table>';
		$html .= "<p class='description'>
                        " . wp_kses_post( $setting['description'] ) . '
                    </p>
                </td>
            </tr>';

		return apply_filters( 'etm_advanced_setting_list', $html );

	}


	/**
	 * Can be used to output content outside the very static methods from above
	 * Hook to the provided filter
	 */
	public function custom_setting( $setting ) {

		if ( empty( $setting['name'] ) ) {
			return;
		}

		return apply_filters( 'etm_advanced_setting_custom_' . $setting['name'], $setting );

	}


}
