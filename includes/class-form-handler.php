<?php
/**
 * Classe per la gestione del form frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRR_Form_Handler {

    /**
     * Riferimento al database
     */
    private $database;

    /**
     * Riferimento all'email sender
     */
    private $email_sender;

    /**
     * Costruttore
     */
    public function __construct($database, $email_sender) {
        $this->database = $database;
        $this->email_sender = $email_sender;

        // Registra shortcode
        add_shortcode('form_richiesta_cliente', array($this, 'render_form'));

        // Registra AJAX handlers
        add_action('wp_ajax_crr_submit_form', array($this, 'handle_form_submit'));
        add_action('wp_ajax_nopriv_crr_submit_form', array($this, 'handle_form_submit'));

        // Carica gli assets frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Carica gli script e stili frontend
     */
    public function enqueue_scripts() {
        // Registra ma non carica subito (sarà caricato solo se c'è lo shortcode)
        wp_register_style(
            'crr-form-style',
            CRR_PLUGIN_URL . 'public/css/form-style.css',
            array(),
            CRR_VERSION
        );

        wp_register_script(
            'crr-form-script',
            CRR_PLUGIN_URL . 'public/js/form-script.js',
            array('jquery'),
            CRR_VERSION,
            true
        );

        wp_localize_script('crr-form-script', 'crr_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crr_form_nonce'),
            'messages' => array(
                'sending' => __('Invio in corso...', 'cliente-richieste-regionali'),
                'success' => __('La tua richiesta è stata inviata con successo!', 'cliente-richieste-regionali'),
                'error' => __('Si è verificato un errore. Riprova più tardi.', 'cliente-richieste-regionali'),
                'validation_error' => __('Per favore compila tutti i campi obbligatori.', 'cliente-richieste-regionali')
            )
        ));
    }

    /**
     * Renderizza il form
     */
    public function render_form($atts) {
        // Carica gli stili e script
        wp_enqueue_style('crr-form-style');
        wp_enqueue_script('crr-form-script');

        // Ottieni le regioni
        $regioni = $this->database->get_regioni();

        // Ottieni i campi configurati
        $fields = CRR_Admin::get_form_fields();

        // Inizia l'output buffering
        ob_start();

        // Include il template del form
        include CRR_PLUGIN_DIR . 'public/views/form-richiesta.php';

        return ob_get_clean();
    }

    /**
     * Gestisce l'invio del form via AJAX
     */
    public function handle_form_submit() {
        // Verifica nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crr_form_nonce')) {
            wp_send_json_error(array(
                'message' => __('Errore di sicurezza. Ricarica la pagina e riprova.', 'cliente-richieste-regionali')
            ));
        }

        // Debug: log dei dati ricevuti
        $this->log_debug('POST data ricevuti: ' . print_r($_POST, true));

        // Ottieni i campi configurati
        $fields = CRR_Admin::get_form_fields();
        $this->log_debug('Campi configurati: ' . print_r($fields, true));
        $errors = array();
        $data = array();

        // Valida e raccogli i dati per ogni campo
        foreach ($fields as $field) {
            $field_id = $field['id'];
            $field_type = $field['type'];
            $field_required = !empty($field['required']);
            $field_label = $field['label'];

            // Salta campi non input
            if (in_array($field_type, array('heading', 'paragraph'))) {
                continue;
            }

            // Ottieni il valore
            $value = isset($_POST[$field_id]) ? $_POST[$field_id] : '';

            // Gestione checkbox group (array)
            if ($field_type === 'checkbox_group' && is_array($value)) {
                $value = implode(', ', array_map('sanitize_text_field', $value));
            } elseif ($field_type === 'checkbox') {
                $value = !empty($value) ? __('Sì', 'cliente-richieste-regionali') : __('No', 'cliente-richieste-regionali');
            } elseif ($field_type === 'textarea') {
                $value = sanitize_textarea_field($value);
            } elseif ($field_type === 'email') {
                $value = sanitize_email($value);
            } else {
                $value = sanitize_text_field($value);
            }

            // Validazione obbligatorietà
            if ($field_required && empty($value) && $value !== '0') {
                // Eccezione per checkbox: non obbligatorio vuol dire che può essere non spuntato
                if ($field_type !== 'checkbox') {
                    $errors[$field_id] = sprintf(
                        __('Il campo %s è obbligatorio.', 'cliente-richieste-regionali'),
                        $field_label
                    );
                }
            }

            // Validazione specifica per tipo
            if (!empty($_POST[$field_id])) {
                switch ($field_type) {
                    case 'email':
                        if (!is_email($_POST[$field_id])) {
                            $errors[$field_id] = __('Inserisci un indirizzo email valido.', 'cliente-richieste-regionali');
                        }
                        break;
                    case 'url':
                        if (!filter_var($_POST[$field_id], FILTER_VALIDATE_URL)) {
                            $errors[$field_id] = __('Inserisci un URL valido.', 'cliente-richieste-regionali');
                        }
                        break;
                    case 'regione':
                        if (!in_array($_POST[$field_id], $this->database->get_regioni())) {
                            $errors[$field_id] = __('Seleziona una regione valida.', 'cliente-richieste-regionali');
                        }
                        break;
                }
            }

            $data[$field_id] = $value;
            $this->log_debug("Campo {$field_id}: {$value}");
        }

        $this->log_debug('Dati finali raccolti: ' . print_r($data, true));

        // Se ci sono errori, ritorna
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => __('Per favore correggi gli errori nel form.', 'cliente-richieste-regionali'),
                'errors' => $errors
            ));
        }

        // Verifica che ci sia almeno la regione (necessaria per il routing email)
        if (empty($data['regione'])) {
            wp_send_json_error(array(
                'message' => __('La regione è obbligatoria.', 'cliente-richieste-regionali')
            ));
        }

        // Inserisci nel database
        $richiesta_id = $this->database->insert_richiesta_dynamic($data, $fields);

        if (!$richiesta_id) {
            wp_send_json_error(array(
                'message' => __('Errore durante il salvataggio. Riprova più tardi.', 'cliente-richieste-regionali')
            ));
        }

        // Invia email
        $email_sent = $this->email_sender->send_notification($richiesta_id);

        // Aggiorna stato email
        if ($email_sent) {
            $this->database->update_email_status($richiesta_id, 1);
        }

        wp_send_json_success(array(
            'message' => __('La tua richiesta è stata inviata con successo! Ti contatteremo al più presto.', 'cliente-richieste-regionali'),
            'richiesta_id' => $richiesta_id
        ));
    }

    /**
     * Scrive nel log di debug
     */
    private function log_debug($message) {
        $log_file = CRR_PLUGIN_DIR . 'form-debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }

    /**
     * Ottiene l'etichetta di un campo
     */
    private function get_field_label($field_id) {
        $fields = CRR_Admin::get_form_fields();

        foreach ($fields as $field) {
            if ($field['id'] === $field_id) {
                return $field['label'];
            }
        }

        return $field_id;
    }
}
