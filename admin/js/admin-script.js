/**
 * Script Admin - Cliente Richieste Regionali
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Reinvio email
        $(document).on('click', '.crr-resend-email', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var richiestaId = $btn.data('id');

            if (!richiestaId) {
                return;
            }

            // Conferma
            if (!confirm(crr_admin.messages.confirm_resend)) {
                return;
            }

            // Disabilita pulsante
            var originalText = $btn.text();
            $btn.prop('disabled', true).text(crr_admin.messages.saving);

            // Invia richiesta
            $.ajax({
                url: crr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'crr_resend_email',
                    nonce: crr_admin.nonce,
                    richiesta_id: richiestaId
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data.message, 'success');

                        // Aggiorna stato nella tabella
                        var $row = $btn.closest('tr');
                        var $status = $row.find('.crr-status');
                        if ($status.length) {
                            $status
                                .removeClass('crr-status-pending')
                                .addClass('crr-status-success')
                                .text('Inviata');
                        }
                    } else {
                        showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotice(crr_admin.messages.error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });

        // Toggle attivo per contatti regionali
        $('.crr-contatti-table').on('change', 'input[type="checkbox"]', function() {
            var $checkbox = $(this);
            var $row = $checkbox.closest('tr');

            if ($checkbox.is(':checked')) {
                $row.removeClass('inactive');
            } else {
                $row.addClass('inactive');
            }
        });

        // Inizializza stato righe
        $('.crr-contatti-table input[type="checkbox"]').each(function() {
            var $checkbox = $(this);
            var $row = $checkbox.closest('tr');

            if (!$checkbox.is(':checked')) {
                $row.addClass('inactive');
            }
        });

        // Mostra notice
        function showNotice(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');

            $('.wrap > h1').after($notice);

            // Inizializza pulsante dismiss
            if (typeof wp !== 'undefined' && wp.updates && wp.updates.addAdminNotice) {
                wp.updates.addAdminNotice({
                    id: 'crr-notice',
                    className: 'notice-' + type,
                    message: message
                });
            } else {
                // Fallback: rimuovi dopo 5 secondi
                setTimeout(function() {
                    $notice.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }

            // Scroll in cima
            $('html, body').animate({ scrollTop: 0 }, 300);
        }

        // Conferma prima di uscire se ci sono modifiche non salvate
        var formModified = false;

        $('form input, form select, form textarea').on('change input', function() {
            formModified = true;
        });

        $('form').on('submit', function() {
            formModified = false;
        });

        $(window).on('beforeunload', function() {
            if (formModified) {
                return 'Ci sono modifiche non salvate. Sei sicuro di voler uscire?';
            }
        });

    });

})(jQuery);
