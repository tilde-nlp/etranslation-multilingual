/*
 * Script used in Settings Page
 */

jQuery( function() {

    /**
     * Change the language selector and slugs
     */
    function ETM_Settings_Language_Selector() {
        var _this = this;
        var duplicate_url_error_message;
        var iso_codes;
        var domains;

        /**
         * Initialize select to become select2
         */
        this.initialize_select2 = function () {
            jQuery('.etm-select2').each(function () {
                var select_element = jQuery(this);
                select_element.select2(/*arguments*/);
            });
        };

        this.get_default_url_slug = function( new_language ){
            var return_slug = iso_codes[new_language];
            var url_slugs = _this.get_existing_url_slugs();
            url_slugs.push( return_slug );
            if ( has_duplicates ( url_slugs ) ){
                return_slug = new_language;
            }
            return return_slug.toLowerCase();
        };

        this.add_language = function(){
            var selected_language = jQuery( '#etm-select-language' );
            var new_language = selected_language.val();
            if ( new_language == "" ){
                return;
            }

            selected_language.val( '' ).trigger( 'change' );

            var new_option = jQuery( '.etm-language' ).first().clone();
            new_option = jQuery( new_option );

            new_option.find( '.etm-hidden-default-language' ).remove();
            new_option.find( '.select2-container' ).remove();
            var select = new_option.find( 'select.etm-translation-language' );
            select.removeAttr( 'disabled' );
            select.find( 'option' ).each(function(index, el){
                el.text = el.text.replace('Default: ', '');
            })

            select.val( new_language );
            select.select2();

            var checkbox = new_option.find( 'input.etm-translation-published' );
            checkbox.removeAttr( 'disabled' );
            checkbox.val( new_language );

            var url_slug = new_option.find( 'input.etm-language-slug' );
            url_slug.val( _this.get_default_url_slug( new_language ) );
            url_slug.attr('name', 'etm_settings[url-slugs][' + new_language + ']' );

            var language_code = new_option.find( 'input.etm-language-code' );
            language_code.val( new_language);

            var remove = new_option.find( '.etm-remove-language' ).toggle();

            new_option = jQuery( '#etm-sortable-languages' ).append( new_option );
            new_option.find( '.etm-remove-language' ).last().click( _this.remove_language );
            
            update_domains();
        };

        this.remove_language = function( element ){
            var message = jQuery( element.target ).attr( 'data-confirm-message' );
            var confirmed = confirm( message );
            if ( confirmed ) {
                jQuery ( element.target ).parent().parent().remove();
            }
        };

        this.update_default_language = function(){
            var selected_language = jQuery( '#etm-default-language').val();
            jQuery( '.etm-hidden-default-language' ).val( selected_language );
            jQuery( '.etm-translation-published[disabled]' ).val( selected_language );
            jQuery( '.etm-translation-language[disabled]').val( selected_language ).trigger( 'change' );
            update_domains();
        };

        function get_lang_from_code(code) {
            return code.split("_")[0];
        }

        function update_domains() {
            if (domains) { 
                var languages = [];
                var selected_language = jQuery( '#etm-default-language').val();
                var source = get_lang_from_code(selected_language);
                jQuery('input.etm-translation-published').each(function() {
                    languages.push(get_lang_from_code(jQuery(this).val()));
                });            
                var domainFields = jQuery('select.etm-translation-language-domain');    
                for (var i = 0; i < domainFields.length; i++) {
                    var target = languages[i];
                    var supportedDomains = get_supported_domains(source, target, domains);
                    var previousDomain = jQuery(domainFields[i]).val();
                    jQuery(domainFields[i]).empty();
                    var domainKeys = Object.keys(supportedDomains);
                    domainKeys.forEach(key => {
                        jQuery(domainFields[i]).append('<option value="' + key + '">' + supportedDomains[key] + '</option>');
                    });
                    var defaultDomain = 'GEN';
                    if (domainKeys.includes(previousDomain)) {
                        jQuery(domainFields[i]).val(previousDomain);
                    } else if (domainKeys.includes(defaultDomain)) {
                        jQuery(domainFields[i]).val(defaultDomain);
                    } else if (domainKeys.length == 0) {
                        jQuery(domainFields[i]).append('<option value="-" selected>-</option>');
                    }
                }
            }  
        }

        function get_supported_domains(source, target, domains) {
            var result = {}
            var searchValue = source + "-" + target;
            Object.keys(domains).forEach(key => {
                langPairs = domains[key].languagePairs.map(p => p.substring(0, 5).toLowerCase());
                if (langPairs.includes(searchValue)) {
                    result[key] = domains[key].name;
                }
            });
            return result;
        }

        function has_duplicates(array) {
            var valuesSoFar = Object.create(null);
            for (var i = 0; i < array.length; ++i) {
                var value = array[i];
                if (value in valuesSoFar) {
                    return true;
                }
                valuesSoFar[value] = true;
            }
            return false;
        }

        this.get_existing_url_slugs = function(){
            var url_slugs = [];
            jQuery( '.etm-language-slug' ).each( function (){
                url_slugs.push( jQuery( this ).val().toLowerCase() );
            } );
            return url_slugs;
        };

        this.check_unique_url_slugs = function (event){
            var url_slugs = _this.get_existing_url_slugs();
            if ( has_duplicates(url_slugs)){
                alert( duplicate_url_error_message );
                event.preventDefault();
            }
        };

        this.update_url_slug_and_status = function ( event ) {
            var select = jQuery( event.target );
            var new_language = select.val();
            var row = jQuery( select ).parents( '.etm-language' ) ;
            row.find( '.etm-language-slug' ).attr( 'name', 'etm_settings[url-slugs][' + new_language + ']').val( '' ).val( _this.get_default_url_slug( new_language ) );
            row.find( '.etm-language-code' ).val( '' ).val( new_language );
            row.find( '.etm-translation-published' ).val( new_language );
        };

        this.initialize = function () {
            this.initialize_select2();

            if ( !jQuery( '.etm-language-selector-limited' ).length ){
                return;
            }

            duplicate_url_error_message = etm_url_slugs_info['error_message_duplicate_slugs'];
            iso_codes = etm_url_slugs_info['iso_codes'];
            domains = etm_url_slugs_info['domains'];
            update_domains();

            jQuery( '#etm-sortable-languages' ).sortable({ handle: '.etm-sortable-handle' });
            jQuery( '#etm-add-language' ).click( _this.add_language );
            jQuery( '.etm-remove-language' ).click( _this.remove_language );
            jQuery( '#etm-default-language' ).on( 'change', _this.update_default_language );
            jQuery( "form[action='options.php']").on ( 'submit', _this.check_unique_url_slugs );
            jQuery( '#etm-languages-table' ).on( 'change', '.etm-translation-language', _this.update_url_slug_and_status );
        };

        this.initialize();
    }

    /*
     * Manage adding and removing items from an option of tpe list from Advanced Settings page
     */
    function ETM_Advanced_Settings_List( table ){

        var _this = this

        this.addEventHandlers = function( table ){
            var add_list_entry = table.querySelector( '.etm-add-list-entry' );

            // add event listener on ADD button
            add_list_entry.querySelector('.etm-adst-button-add-new-item').addEventListener("click", _this.add_item );

            var removeButtons = table.querySelectorAll( '.etm-adst-remove-element' );
            for( var i = 0 ; i < removeButtons.length ; i++ ) {
                removeButtons[i].addEventListener("click", _this.remove_item)
            }
        }

        this.remove_item = function( event ){
            if ( confirm( event.target.getAttribute( 'data-confirm-message' ) ) ){
                jQuery( event.target ).closest( '.etm-list-entry' ).remove()
            }
        }

        this.add_item = function () {
            var add_list_entry = table.querySelector( '.etm-add-list-entry' );
            var clone = add_list_entry.cloneNode(true)

            // Remove the etm-add-list-entry class from the second element after it was cloned
            add_list_entry.classList.remove('etm-add-list-entry');

            // Show Add button, hide Remove button
            add_list_entry.querySelector( '.etm-adst-button-add-new-item' ).style.display = 'none'
            add_list_entry.querySelector( '.etm-adst-remove-element' ).style.display = 'block'

            // Design change to add the cloned element at the bottom of list
            // Done becasue the select box element cannot be cloned with its selected state
            var itemInserted =  add_list_entry.parentNode.insertBefore(clone, add_list_entry.nextSibling);

            // Set name attributes
            var dataNames = add_list_entry.querySelectorAll( '[data-name]' )
            for( var i = 0 ; i < dataNames.length ; i++ ) {
                dataNames[i].setAttribute( 'name', dataNames[i].getAttribute('data-name') );
            }

            var removeButtons = table.querySelectorAll( '.etm-adst-remove-element' );
            for( var i = 0 ; i < removeButtons.length ; i++ ) {
                removeButtons[i].addEventListener("click", _this.remove_item)
            }

            // Reset values of textareas with new items
            var dataValues = clone.querySelectorAll( '[data-name]' )
            for( var i = 0 ; i < dataValues.length ; i++ ) {
                dataValues[i].value = ''
            }

            //Restore checkbox(es) values after cloning and clearing; alternative than excluding from reset
            var restoreCheckboxes = clone.querySelectorAll ( 'input[type=checkbox]' )
            for( var i = 0 ; i < restoreCheckboxes.length ; i++ ) {
                restoreCheckboxes[i].value = 'yes'
            }

            // Add click listener on new row's Add button
            var addButton = itemInserted.querySelector('.etm-adst-button-add-new-item');
            addButton.addEventListener("click", _this.add_item );
        }

        _this.addEventHandlers( table )
    }
    var etmSettingsLanguages = new ETM_Settings_Language_Selector();

    jQuery('#etm-default-language').on("select2:selecting", function(e) {
        jQuery("#etm-options .warning").show('fast');
    });

    var etranslationCredentials = ETM_Field_Toggler();
    etranslationCredentials.init('.etm-translation-engine', '.et-credentials', 'etranslation');

    jQuery(document).trigger( 'etmInitFieldToggler' );

    // Used for the main machine translation toggle to show/hide all options below it
    function ETM_show_hide_machine_translation_options(){
        if( jQuery( '#etm-machine-translation-enabled' ).val() != 'yes' )
            jQuery( '.etm-machine-translation-options tbody tr:not(:first-child)').hide()
        else
            jQuery( '.etm-machine-translation-options tbody tr:not(:first-child)').show()

        if( jQuery( '#etm-machine-translation-enabled' ).val() == 'yes' )
            jQuery('.etm-translation-engine:checked').trigger('change')
    }

    ETM_show_hide_machine_translation_options()
    jQuery('#etm-machine-translation-enabled').on( 'change', function(){
        ETM_show_hide_machine_translation_options()
    })

    jQuery( '#etm-test-api-key' ).show()

    // Options of type List adding, from Advanced Settings page
    var etmListOptions = document.querySelectorAll( '.etm-adst-list-option' );
    for ( var i = 0 ; i < etmListOptions.length ; i++ ){
        new ETM_Advanced_Settings_List( etmListOptions[i] );
    }

});

function ETM_Field_Toggler (){
    var _$setting_toggled, _$trigger_field, _trigger_field_value_for_show, _trigger_field_value

    function show_hide_based_on_value( value ) {
        if ( value === _trigger_field_value_for_show )
            _$setting_toggled.show()
        else
            _$setting_toggled.hide()
    }

    function add_event_on_change() {

        _$trigger_field.on('change', function () {
            show_hide_based_on_value( this.value )
        })

    }

    function init( trigger_select_id, setting_id, value_for_show ){
        _trigger_field_value_for_show = value_for_show
        _$trigger_field               = jQuery( trigger_select_id )
        _$setting_toggled             = jQuery( setting_id ).parents('tr')

        if( _$trigger_field.hasClass( 'etm-radio') )
            _trigger_field_value = jQuery( trigger_select_id + ':checked' ).val()
        else
            _trigger_field_value = _$trigger_field.val()

        show_hide_based_on_value( _trigger_field_value )
        add_event_on_change()
    }

    return {
        init: init
    }
}