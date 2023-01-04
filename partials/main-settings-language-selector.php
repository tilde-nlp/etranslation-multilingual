<tr>
	<th scope="row"> <?php esc_html_e( 'All Languages', 'etranslation-multilingual' ); ?> </th>
	<td>
		<table id="etm-languages-table">
			<thead>
				<tr>
					<th colspan="2"><?php esc_html_e( 'Language', 'etranslation-multilingual' ); ?></th>
					<th><?php esc_html_e( 'Domain', 'etranslation-multilingual' ); ?></th>
					<th><?php esc_html_e( 'Code', 'etranslation-multilingual' ); ?></th>
					<th><?php esc_html_e( 'Slug', 'etranslation-multilingual' ); ?></th>
				</tr>
			</thead>
			<tbody id="etm-sortable-languages" class="etm-language-selector-limited">

			<?php
			$domain_array       = array();
			$etm                = ETM_eTranslation_Multilingual::get_etm_instance();
			$machine_translator = $etm->get_component( 'machine_translator' );
			if ( $machine_translator instanceof ETM_eTranslation_Machine_Translator && $machine_translator->is_available() && $machine_translator->credentials_set() ) {
				$domain_array = $machine_translator->get_all_domains();
			}
			?>

			<?php
			foreach ( $this->settings['translation-languages'] as $key => $selected_language_code ) {
				$default_language = ( $selected_language_code == $this->settings['default-language'] );
				?>
				<tr class="etm-language">
					<td><span class="etm-sortable-handle"></span></td>
					<td>
						<select name="etm_settings[translation-languages][]" class="etm-select2 etm-translation-language" <?php echo ( esc_attr( $default_language ) ) ? 'disabled' : ''; ?>>
							<?php foreach ( $languages as $language_code => $language_name ) { ?>
								<option title="<?php echo esc_attr( $language_code ); ?>" value="<?php echo esc_attr( $language_code ); ?>" <?php echo ( $language_code == $selected_language_code ) ? 'selected' : ''; ?>>
									<?php echo ( esc_html( $default_language ) ) ? 'Default: ' : ''; ?>
									<?php echo esc_html( $language_name ); ?>
								</option>
							<?php } ?>
						</select>
						<input type="hidden" class="etm-translation-published" name="etm_settings[publish-languages][]" value="<?php echo esc_attr( $selected_language_code ); ?>" />
						<?php if ( $default_language ) { ?>
							<input type="hidden" class="etm-hidden-default-language" name="etm_settings[translation-languages][]" value="<?php echo esc_attr( $selected_language_code ); ?>" />
						<?php } ?>
					</td>
					<td>
						<select name="etm_settings[translation-languages-domain][]" class="etm-translation-language-domain" <?php disabled( empty( $domain_array ), true ); ?>>
							<?php
							foreach ( $domain_array as $key => $value ) {
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php echo ( isset( $this->settings['translation-languages-domain-parameter'][ $selected_language_code ] ) && $key == $this->settings['translation-languages-domain-parameter'][ $selected_language_code ] ) ? 'selected' : ''; ?>><?php echo esc_html( $value->name ); ?></option>
								<?php
							}
							?>
						</select>
					</td>
					<td>
						<input class="etm-language-code etm-code-slug" type="text" disabled value="<?php echo esc_attr( $selected_language_code ); ?>">
					</td>
					<td>
						<input class="etm-language-slug etm-code-slug" name="etm_settings[url-slugs][<?php echo esc_attr( $selected_language_code ); ?>]" type="text" style="text-transform: lowercase;" value="<?php echo esc_attr( $this->url_converter->get_url_slug( $selected_language_code, false ) ); ?>">
					</td>
					<td>
						<a class="etm-remove-language" style=" <?php echo ( esc_attr( $default_language ) ) ? 'display:none' : ''; ?>" data-confirm-message="<?php esc_attr_e( 'Are you sure you want to remove this language?', 'etranslation-multilingual' ); ?>"><?php esc_html_e( 'Remove', 'etranslation-multilingual' ); ?></a>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<div id="etm-new-language">
			<select id="etm-select-language" class="etm-select2 etm-translation-language" >
				<?php
				$etm           = ETM_eTranslation_Multilingual::get_etm_instance();
				$etm_languages = $etm->get_component( 'languages' );
				$wp_languages  = $etm_languages->get_wp_languages();
				?>
				<option value=""><?php esc_html_e( 'Choose...', 'etranslation-multilingual' ); ?></option>
				<?php foreach ( $languages as $language_code => $language_name ) { ?>

					<?php if ( isset( $wp_languages[ $language_code ]['is_custom_language'] ) && $wp_languages[ $language_code ]['is_custom_language'] === true ) { ?>
				<optgroup label="<?php echo esc_html__( 'Custom Languages', 'etranslation-multilingual' ); ?>">
						<?php break; ?>
					<?php } ?>
					<?php } ?>
					<?php foreach ( $languages as $language_code => $language_name ) { ?>

						<?php if ( isset( $wp_languages[ $language_code ]['is_custom_language'] ) && $wp_languages[ $language_code ]['is_custom_language'] === true ) { ?>
							<option title="<?php echo esc_attr( $language_code ); ?>" value="<?php echo esc_attr( $language_code ); ?>">
								<?php echo esc_html( $language_name ); ?>
							</option>

						<?php } ?>

					<?php } ?>
				</optgroup>

				<?php foreach ( $languages as $language_code => $language_name ) { ?>
					<?php if ( ! isset( $wp_languages[ $language_code ]['is_custom_language'] ) || ( isset( $wp_languages[ $language_code ]['is_custom_language'] ) && $wp_languages[ $language_code ]['is_custom_language'] !== true ) ) { ?>

					<option title="<?php echo esc_attr( $language_code ); ?>" value="<?php echo esc_attr( $language_code ); ?>">
						<?php echo esc_html( $language_name ); ?>
					</option>
					<?php } ?>
				<?php } ?>
			</select>
			<button type="button" id="etm-add-language" class="button-secondary"><?php esc_html_e( 'Add', 'etranslation-multilingual' ); ?></button>
		</div>
		<p class="description">
			<?php echo esc_html( __( 'Select the languages you wish to make your website available in.' ) ); ?>
		</p>
	</td>
</tr>
