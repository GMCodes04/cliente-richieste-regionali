/**
 * Script Form Richiesta Cliente (Dinamico)
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var $form = $('#crr-richiesta-form');

        if (!$form.length) {
            return;
        }

        var $submitBtn = $form.find('.crr-submit-btn');
        var $btnText = $submitBtn.find('.crr-btn-text');
        var $btnLoading = $submitBtn.find('.crr-btn-loading');
        var $message = $form.find('.crr-form-message');

        // Invio form
        $form.on('submit', function(e) {
            e.preventDefault();

            // Reset errori
            clearErrors();

            // Validazione
            if (!validateForm()) {
                return;
            }

            // Mostra loading
            setLoading(true);

            // Prepara i dati manualmente per includere tutti i campi
            var formData = {};

            // Aggiungi action e nonce
            formData['action'] = 'crr_submit_form';
            formData['nonce'] = crr_ajax.nonce;

            // Raccogli tutti i campi input, select, textarea
            $form.find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var type = $field.attr('type');

                if (!name || name === 'crr_nonce' || name === '_wp_http_referer') {
                    return; // Salta i campi nonce di WordPress
                }

                // Checkbox
                if (type === 'checkbox') {
                    if (name.indexOf('[]') !== -1) {
                        // Checkbox group
                        var cleanName = name.replace('[]', '');
                        if (!formData[cleanName]) {
                            formData[cleanName] = [];
                        }
                        if ($field.is(':checked')) {
                            formData[cleanName].push($field.val());
                        }
                    } else {
                        // Checkbox singola
                        formData[name] = $field.is(':checked') ? '1' : '';
                    }
                }
                // Radio
                else if (type === 'radio') {
                    if ($field.is(':checked')) {
                        formData[name] = $field.val();
                    }
                }
                // Altri campi
                else {
                    formData[name] = $field.val();
                }
            });

            // Debug
            console.log('Form data:', formData);

            // Invia richiesta AJAX
            $.ajax({
                url: crr_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('Response:', response);
                    setLoading(false);

                    if (response.success) {
                        showMessage(response.data.message, 'success');
                        $form[0].reset();
                    } else {
                        if (response.data && response.data.errors) {
                            showFieldErrors(response.data.errors);
                        }
                        showMessage(response.data ? response.data.message : 'Errore sconosciuto', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    setLoading(false);
                    console.log('AJAX Error:', status, error);
                    console.log('Response Text:', xhr.responseText);
                    showMessage(crr_ajax.messages.error, 'error');
                }
            });
        });

        // Validazione lato client dinamica
        function validateForm() {
            var isValid = true;
            var checkedRadios = {};
            console.log('Inizio validazione form...');

            // Valida tutti i campi required
            $form.find('[required]').each(function() {
                var $field = $(this);
                var fieldName = $field.attr('name');
                var fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();
                var value = '';

                // Gestione diversi tipi di input
                if (fieldType === 'checkbox') {
                    if (!$field.is(':checked')) {
                        showError(fieldName, 'Questo campo è obbligatorio.');
                        isValid = false;
                    }
                    return;
                } else if (fieldType === 'radio') {
                    // Per radio, controlla se almeno uno è selezionato nel gruppo
                    var radioName = $field.attr('name');
                    if (!checkedRadios[radioName]) {
                        if (!$form.find('input[name="' + radioName + '"]:checked').length) {
                            showError(radioName, 'Seleziona un\'opzione.');
                            isValid = false;
                        }
                        checkedRadios[radioName] = true;
                    }
                    return;
                } else {
                    value = $field.val();
                    // Gestisci null e undefined
                    if (value === null || value === undefined) {
                        value = '';
                    }
                    if ($.isArray(value)) {
                        value = value.join('');
                    }
                    value = String(value).trim();
                }

                if (!value) {
                    var label = $field.closest('.crr-form-field').find('label').first().text().replace('*', '').trim();
                    showError(fieldName, 'Il campo ' + label + ' è obbligatorio.');
                    isValid = false;
                }

                // Validazioni specifiche per tipo
                if (value && fieldType === 'email') {
                    if (!isValidEmail(value)) {
                        showError(fieldName, 'Inserisci un indirizzo email valido.');
                        isValid = false;
                    }
                }

                if (value && fieldType === 'url') {
                    if (!isValidUrl(value)) {
                        showError(fieldName, 'Inserisci un URL valido.');
                        isValid = false;
                    }
                }
            });

            console.log('Validazione completata. Form valido:', isValid);
            return isValid;
        }

        // Mostra errore su un campo
        function showError(fieldName, message) {
            // Cerca il campo per name (rimuovi [] per checkbox group)
            var cleanName = fieldName.replace('[]', '');
            var $field = $form.find('[name="' + fieldName + '"], [name="' + cleanName + '[]"]').first();

            if (!$field.length) {
                $field = $form.find('#crr_' + cleanName);
            }

            var $container = $field.closest('.crr-form-field');

            $field.addClass('crr-error');
            $container.addClass('has-error');
            $container.find('.crr-error-message').text(message);
        }

        // Mostra errori da server
        function showFieldErrors(errors) {
            $.each(errors, function(field, message) {
                showError(field, message);
            });
        }

        // Pulisci errori
        function clearErrors() {
            $form.find('.crr-error').removeClass('crr-error');
            $form.find('.has-error').removeClass('has-error');
            $form.find('.crr-error-message').text('');
            hideMessage();
        }

        // Mostra messaggio
        function showMessage(text, type) {
            $message
                .removeClass('success error')
                .addClass(type)
                .text(text)
                .fadeIn();

            // Scroll al messaggio
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 300);
        }

        // Nascondi messaggio
        function hideMessage() {
            $message.fadeOut();
        }

        // Imposta stato loading
        function setLoading(loading) {
            if (loading) {
                $submitBtn.prop('disabled', true);
                $btnText.hide();
                $btnLoading.show();
            } else {
                $submitBtn.prop('disabled', false);
                $btnText.show();
                $btnLoading.hide();
            }
        }

        // Validazione email
        function isValidEmail(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        // Validazione URL
        function isValidUrl(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        }

        // Rimuovi errore quando l'utente modifica un campo
        $form.on('input change', 'input, select, textarea', function() {
            var $field = $(this);
            var $container = $field.closest('.crr-form-field');

            $field.removeClass('crr-error');
            $container.removeClass('has-error');
            $container.find('.crr-error-message').text('');
        });
    });

})(jQuery);
