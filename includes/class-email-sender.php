<?php
/**
 * Classe per l'invio delle email automatiche
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRR_Email_Sender {

    /**
     * Riferimento al database
     */
    private $database;

    /**
     * Etichette dei campi
     */
    private $field_labels;

    /**
     * Costruttore
     */
    public function __construct($database) {
        $this->database = $database;

        $this->field_labels = array(
            'nome' => __('Nome', 'cliente-richieste-regionali'),
            'cognome' => __('Cognome', 'cliente-richieste-regionali'),
            'email' => __('Email', 'cliente-richieste-regionali'),
            'telefono' => __('Telefono', 'cliente-richieste-regionali'),
            'indirizzo' => __('Indirizzo', 'cliente-richieste-regionali'),
            'citta' => __('Città', 'cliente-richieste-regionali'),
            'cap' => __('CAP', 'cliente-richieste-regionali'),
            'regione' => __('Regione', 'cliente-richieste-regionali'),
            'richiesta' => __('Richiesta', 'cliente-richieste-regionali')
        );
    }

    /**
     * Invia la notifica email per una richiesta
     * Invia email separate a ogni destinatario (nessuno vede gli altri)
     */
    public function send_notification($richiesta_id) {
        // Ottieni i dati della richiesta
        $richiesta = $this->database->get_richiesta($richiesta_id);

        if (!$richiesta) {
            $this->log('Richiesta non trovata: ID ' . $richiesta_id);
            return false;
        }

        // Ottieni la lista delle email (array)
        $emails = $this->database->get_emails_for_regione($richiesta['regione']);

        if (empty($emails)) {
            $this->log('Email destinatario vuota per regione: ' . $richiesta['regione']);
            return false;
        }

        // Costruisci l'oggetto
        $oggetto = $this->build_subject($richiesta);

        // Costruisci il corpo dell'email
        $body = $this->build_body($richiesta);

        // Imposta gli headers
        $headers = $this->build_headers();

        // Log prima dell'invio
        $this->log('=== TENTATIVO INVIO EMAIL ===');
        $this->log('Richiesta ID: ' . $richiesta_id);
        $this->log('Destinatari: ' . implode(', ', $emails));
        $this->log('Oggetto: ' . $oggetto);

        // Cattura errori wp_mail
        add_action('wp_mail_failed', array($this, 'log_mail_error'));

        // Invia email SEPARATE a ogni destinatario
        $all_sent = true;
        foreach ($emails as $email) {
            $email = trim($email);
            if (empty($email)) {
                continue;
            }

            $sent = wp_mail($email, $oggetto, $body, $headers);

            if ($sent) {
                $this->log('Email inviata con SUCCESSO a: ' . $email);
            } else {
                $this->log('Email FALLITA per: ' . $email);
                $all_sent = false;
            }
        }

        // Rimuovi l'action
        remove_action('wp_mail_failed', array($this, 'log_mail_error'));

        // Invia copia completa se attiva
        $this->send_copia_completa($richiesta);

        return $all_sent;
    }

    /**
     * Invia una copia completa della richiesta con tutti i campi
     */
    private function send_copia_completa($richiesta) {
        // Verifica se la copia completa è attiva
        $copia_attiva = get_option('crr_copia_completa_attiva', 0);
        $email_copia = get_option('crr_email_copia_completa', '');

        if (!$copia_attiva || empty($email_copia)) {
            return false;
        }

        $this->log('Invio copia completa a: ' . $email_copia);

        // Costruisci l'oggetto
        $oggetto = '[COPIA COMPLETA] ' . $this->build_subject($richiesta);

        // Costruisci il corpo con TUTTI i campi
        $body = $this->build_body_completo($richiesta);

        // Imposta gli headers
        $headers = $this->build_headers();

        // Cattura errori wp_mail
        add_action('wp_mail_failed', array($this, 'log_mail_error'));

        $sent = wp_mail($email_copia, $oggetto, $body, $headers);

        remove_action('wp_mail_failed', array($this, 'log_mail_error'));

        if ($sent) {
            $this->log('Copia completa inviata con SUCCESSO a: ' . $email_copia);
        } else {
            $this->log('Copia completa FALLITA per: ' . $email_copia);
        }

        return $sent;
    }

    /**
     * Costruisce il corpo dell'email con TUTTI i campi
     */
    private function build_body_completo($richiesta) {
        // Ottieni i campi configurati nel Form Builder
        $fields = CRR_Admin::get_form_fields();

        // Costruisci il contenuto con TUTTI i campi
        $contenuto_parts = array();

        foreach ($fields as $field) {
            // Salta campi non-input
            if (in_array($field['type'], array('heading', 'paragraph', 'hidden'))) {
                continue;
            }

            $field_id = $field['id'];
            $field_label = $field['label'];

            // Cerca il valore nei campi standard della richiesta
            $valore = '';
            if (isset($richiesta[$field_id]) && !empty($richiesta[$field_id])) {
                $valore = $richiesta[$field_id];
            } else {
                // Cerca nei dati extra (campi dinamici)
                if (!empty($richiesta['dati_extra'])) {
                    $dati_extra = json_decode($richiesta['dati_extra'], true);
                    if (is_array($dati_extra) && isset($dati_extra[$field_id])) {
                        $valore = $dati_extra[$field_id]['value'];
                    }
                }
            }

            // Formatta array (checkbox groups, etc)
            if (is_array($valore)) {
                $valore = implode(', ', $valore);
            }

            // Aggiungi al contenuto (anche se vuoto, per mostrare tutti i campi)
            if ($field['type'] === 'textarea') {
                $contenuto_parts[] = "{$field_label}:\n" . ($valore ?: '-');
            } else {
                $contenuto_parts[] = "{$field_label}: " . ($valore ?: '-');
            }
        }

        $contenuto_email = implode("\n", $contenuto_parts);

        // Template per copia completa
        $body = __("COPIA COMPLETA - Nuova richiesta ricevuta\n\n", 'cliente-richieste-regionali');
        $body .= "ID Richiesta: " . $richiesta['id'] . "\n";
        $body .= "Data: " . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($richiesta['data_creazione'])) . "\n\n";
        $body .= "=== TUTTI I DATI ===\n\n";
        $body .= $contenuto_email;
        $body .= "\n\n---\n";
        $body .= __('Email generata automaticamente dal sistema.', 'cliente-richieste-regionali');

        return $body;
    }

    /**
     * Log errori wp_mail
     */
    public function log_mail_error($wp_error) {
        $this->log('WP_MAIL ERROR: ' . $wp_error->get_error_message());
        $this->log('Error data: ' . print_r($wp_error->get_error_data(), true));
    }

    /**
     * Scrive nel log
     */
    private function log($message) {
        $log_file = CRR_PLUGIN_DIR . 'email-debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }

    /**
     * Costruisce l'oggetto dell'email
     */
    private function build_subject($richiesta) {
        $oggetto = get_option('crr_email_oggetto', __('Nuova richiesta dalla regione {regione}', 'cliente-richieste-regionali'));

        // Sostituisci i placeholder
        $oggetto = str_replace('{regione}', $richiesta['regione'], $oggetto);
        $oggetto = str_replace('{id}', $richiesta['id'], $oggetto);

        return $oggetto;
    }

    /**
     * Costruisce il corpo dell'email
     */
    private function build_body($richiesta) {
        // Ottieni il template
        $template = get_option('crr_email_template', __("È stata ricevuta una nuova richiesta.\n\n{contenuto_email}\n\n---\nEmail generata automaticamente dal sistema.", 'cliente-richieste-regionali'));

        // Ottieni i campi configurati nel Form Builder
        $fields = CRR_Admin::get_form_fields();

        // Ottieni le impostazioni dei campi email (sovrascrivono in_email del Form Builder)
        $campi_email = get_option('crr_campi_email', array());

        // Costruisci il contenuto con solo i campi selezionati
        $contenuto_parts = array();

        foreach ($fields as $field) {
            // Salta campi non-input
            if (in_array($field['type'], array('heading', 'paragraph', 'hidden'))) {
                continue;
            }

            $field_id = $field['id'];
            $field_label = $field['label'];

            // Verifica se il campo deve essere incluso nell'email
            // Prima controlla le impostazioni email, poi il Form Builder
            $in_email = false;
            if (isset($campi_email[$field_id])) {
                $in_email = !empty($campi_email[$field_id]);
            } else {
                $in_email = !empty($field['in_email']);
            }

            if (!$in_email) {
                continue;
            }

            // Cerca il valore nei campi standard della richiesta
            $valore = '';
            if (isset($richiesta[$field_id]) && !empty($richiesta[$field_id])) {
                $valore = $richiesta[$field_id];
            } else {
                // Cerca nei dati extra (campi dinamici)
                if (!empty($richiesta['dati_extra'])) {
                    $dati_extra = json_decode($richiesta['dati_extra'], true);
                    if (is_array($dati_extra) && isset($dati_extra[$field_id])) {
                        $valore = $dati_extra[$field_id]['value'];
                    }
                }
            }

            // Se c'è un valore, aggiungilo al contenuto
            if (!empty($valore)) {
                // Formatta array (checkbox groups, etc)
                if (is_array($valore)) {
                    $valore = implode(', ', $valore);
                }

                // Formatta textarea in modo diverso (multilinea)
                if ($field['type'] === 'textarea') {
                    $contenuto_parts[] = "{$field_label}:\n{$valore}";
                } else {
                    $contenuto_parts[] = "{$field_label}: {$valore}";
                }
            }
        }

        $contenuto_email = implode("\n", $contenuto_parts);

        // Sostituisci i placeholder nel template
        $body = str_replace('{contenuto_email}', $contenuto_email, $template);
        $body = str_replace('{regione}', isset($richiesta['regione']) ? $richiesta['regione'] : '', $body);
        $body = str_replace('{id}', $richiesta['id'], $body);
        $body = str_replace('{data}', date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($richiesta['data_creazione'])), $body);

        return $body;
    }

    /**
     * Costruisce gli headers dell'email
     */
    private function build_headers() {
        $headers = array();

        $nome_mittente = get_option('crr_nome_mittente', get_bloginfo('name'));
        $email_mittente = get_option('crr_email_mittente', get_option('admin_email'));

        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = "From: {$nome_mittente} <{$email_mittente}>";

        return $headers;
    }

    /**
     * Reinvia l'email per una richiesta esistente
     */
    public function resend_notification($richiesta_id) {
        $sent = $this->send_notification($richiesta_id);

        if ($sent) {
            $this->database->update_email_status($richiesta_id, 1);
        }

        return $sent;
    }

    /**
     * Ottiene l'anteprima dell'email per una richiesta
     */
    public function get_email_preview($richiesta_id) {
        $richiesta = $this->database->get_richiesta($richiesta_id);

        if (!$richiesta) {
            return false;
        }

        return array(
            'to' => $this->database->get_email_for_regione($richiesta['regione']),
            'subject' => $this->build_subject($richiesta),
            'body' => $this->build_body($richiesta)
        );
    }
}
