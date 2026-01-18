<?php
/**
 * Classe per il pannello di amministrazione
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRR_Admin {

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

        // Registra il menu admin
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Carica gli assets admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Gestione azioni admin
        add_action('admin_init', array($this, 'handle_admin_actions'));

        // AJAX per operazioni admin
        add_action('wp_ajax_crr_resend_email', array($this, 'ajax_resend_email'));
        add_action('wp_ajax_crr_save_contatti', array($this, 'ajax_save_contatti'));
    }

    /**
     * Aggiunge il menu admin
     */
    public function add_admin_menu() {
        // Menu principale
        add_menu_page(
            __('Richieste Clienti', 'cliente-richieste-regionali'),
            __('Richieste Clienti', 'cliente-richieste-regionali'),
            'manage_options',
            'crr-richieste',
            array($this, 'render_richieste_page'),
            'dashicons-email-alt',
            30
        );

        // Sottomenu: Lista Richieste
        add_submenu_page(
            'crr-richieste',
            __('Tutte le Richieste', 'cliente-richieste-regionali'),
            __('Tutte le Richieste', 'cliente-richieste-regionali'),
            'manage_options',
            'crr-richieste',
            array($this, 'render_richieste_page')
        );

        // Sottomenu: Contatti Regionali
        add_submenu_page(
            'crr-richieste',
            __('Contatti Regionali', 'cliente-richieste-regionali'),
            __('Contatti Regionali', 'cliente-richieste-regionali'),
            'manage_options',
            'crr-contatti',
            array($this, 'render_contatti_page')
        );

        // Sottomenu: Impostazioni Email
        add_submenu_page(
            'crr-richieste',
            __('Impostazioni Email', 'cliente-richieste-regionali'),
            __('Impostazioni Email', 'cliente-richieste-regionali'),
            'manage_options',
            'crr-impostazioni',
            array($this, 'render_impostazioni_page')
        );

        // Sottomenu: Costruttore Form
        add_submenu_page(
            'crr-richieste',
            __('Costruttore Form', 'cliente-richieste-regionali'),
            __('Costruttore Form', 'cliente-richieste-regionali'),
            'manage_options',
            'crr-form-builder',
            array($this, 'render_form_builder_page')
        );

        // Sottomenu: Debug Log
        add_submenu_page(
            'crr-richieste',
            __('Debug Email', 'cliente-richieste-regionali'),
            __('Debug Email', 'cliente-richieste-regionali'),
            'manage_options',
            'crr-debug',
            array($this, 'render_debug_page')
        );
    }

    /**
     * Carica gli script e stili admin
     */
    public function enqueue_admin_scripts($hook) {
        // Carica solo nelle pagine del plugin
        if (strpos($hook, 'crr-') === false) {
            return;
        }

        wp_enqueue_style(
            'crr-admin-style',
            CRR_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            CRR_VERSION
        );

        wp_enqueue_script(
            'crr-admin-script',
            CRR_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery'),
            CRR_VERSION,
            true
        );

        wp_localize_script('crr-admin-script', 'crr_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crr_admin_nonce'),
            'messages' => array(
                'confirm_resend' => __('Sei sicuro di voler reinviare l\'email?', 'cliente-richieste-regionali'),
                'email_sent' => __('Email inviata con successo!', 'cliente-richieste-regionali'),
                'email_error' => __('Errore durante l\'invio dell\'email.', 'cliente-richieste-regionali'),
                'saving' => __('Salvataggio...', 'cliente-richieste-regionali'),
                'saved' => __('Salvato!', 'cliente-richieste-regionali'),
                'error' => __('Errore durante il salvataggio.', 'cliente-richieste-regionali')
            )
        ));
    }

    /**
     * Gestisce le azioni admin (POST)
     */
    public function handle_admin_actions() {
        // Salvataggio campi form
        if (isset($_POST['crr_save_form_fields']) && isset($_POST['crr_form_builder_nonce'])) {
            if (wp_verify_nonce($_POST['crr_form_builder_nonce'], 'crr_save_form_fields')) {
                $this->save_form_fields();
            }
        }

        // Salvataggio impostazioni email
        if (isset($_POST['crr_save_impostazioni']) && isset($_POST['crr_impostazioni_nonce'])) {
            if (wp_verify_nonce($_POST['crr_impostazioni_nonce'], 'crr_save_impostazioni')) {
                $this->save_impostazioni();
            }
        }

        // Salvataggio contatti regionali
        if (isset($_POST['crr_save_contatti']) && isset($_POST['crr_contatti_nonce'])) {
            if (wp_verify_nonce($_POST['crr_contatti_nonce'], 'crr_save_contatti')) {
                $this->save_contatti();
            }
        }
    }

    /**
     * Salva le impostazioni email
     */
    private function save_impostazioni() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Ottieni i campi dal Form Builder
        $form_fields = self::get_form_fields();
        $campi_email = array();

        // Salva lo stato di ogni campo dinamicamente
        foreach ($form_fields as $field) {
            $field_id = $field['id'];
            $campi_email[$field_id] = isset($_POST['crr_campo_' . $field_id]) ? 1 : 0;
        }
        update_option('crr_campi_email', $campi_email);

        // Aggiorna anche il Form Builder con le impostazioni in_email
        $updated_fields = array();
        foreach ($form_fields as $field) {
            $field['in_email'] = isset($campi_email[$field['id']]) ? $campi_email[$field['id']] : 0;
            $updated_fields[] = $field;
        }
        update_option('crr_form_fields', $updated_fields);

        // Altre impostazioni
        if (isset($_POST['crr_email_oggetto'])) {
            update_option('crr_email_oggetto', sanitize_text_field($_POST['crr_email_oggetto']));
        }

        if (isset($_POST['crr_email_template'])) {
            update_option('crr_email_template', sanitize_textarea_field($_POST['crr_email_template']));
        }

        if (isset($_POST['crr_email_fallback'])) {
            update_option('crr_email_fallback', sanitize_email($_POST['crr_email_fallback']));
        }

        if (isset($_POST['crr_email_mittente'])) {
            update_option('crr_email_mittente', sanitize_email($_POST['crr_email_mittente']));
        }

        if (isset($_POST['crr_nome_mittente'])) {
            update_option('crr_nome_mittente', sanitize_text_field($_POST['crr_nome_mittente']));
        }

        // Copia completa
        update_option('crr_copia_completa_attiva', isset($_POST['crr_copia_completa_attiva']) ? 1 : 0);

        if (isset($_POST['crr_email_copia_completa'])) {
            update_option('crr_email_copia_completa', sanitize_email($_POST['crr_email_copia_completa']));
        }

        add_settings_error('crr_messages', 'crr_message', __('Impostazioni salvate.', 'cliente-richieste-regionali'), 'updated');
    }

    /**
     * Salva i contatti regionali
     */
    private function save_contatti() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $regioni = $this->database->get_regioni();

        foreach ($regioni as $regione) {
            $key = sanitize_title($regione);

            $data = array(
                'emails' => isset($_POST['crr_emails_' . $key]) ? $_POST['crr_emails_' . $key] : '',
                'nome_contatto' => isset($_POST['crr_nome_' . $key]) ? sanitize_text_field($_POST['crr_nome_' . $key]) : '',
                'attivo' => isset($_POST['crr_attivo_' . $key]) ? 1 : 0
            );

            $this->database->update_contatto($regione, $data);
        }

        add_settings_error('crr_messages', 'crr_message', __('Contatti regionali salvati.', 'cliente-richieste-regionali'), 'updated');
    }

    /**
     * Renderizza la pagina richieste
     */
    public function render_richieste_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Gestione visualizzazione dettaglio
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
            $this->render_dettaglio_richiesta(intval($_GET['id']));
            return;
        }

        include CRR_PLUGIN_DIR . 'admin/views/richieste-list.php';
    }

    /**
     * Renderizza il dettaglio di una richiesta
     */
    private function render_dettaglio_richiesta($id) {
        $richiesta = $this->database->get_richiesta($id);

        if (!$richiesta) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . __('Richiesta non trovata.', 'cliente-richieste-regionali') . '</p></div></div>';
            return;
        }

        include CRR_PLUGIN_DIR . 'admin/views/richiesta-dettaglio.php';
    }

    /**
     * Renderizza la pagina contatti
     */
    public function render_contatti_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        include CRR_PLUGIN_DIR . 'admin/views/contatti-regionali.php';
    }

    /**
     * Renderizza la pagina impostazioni
     */
    public function render_impostazioni_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        include CRR_PLUGIN_DIR . 'admin/views/impostazioni-email.php';
    }

    /**
     * Renderizza la pagina debug
     */
    public function render_debug_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Gestione pulizia log
        if (isset($_POST['crr_clear_log']) && wp_verify_nonce($_POST['crr_debug_nonce'], 'crr_clear_log')) {
            $log_file = CRR_PLUGIN_DIR . 'email-debug.log';
            if (file_exists($log_file)) {
                unlink($log_file);
            }
            echo '<div class="notice notice-success"><p>' . __('Log cancellato.', 'cliente-richieste-regionali') . '</p></div>';
        }

        // Gestione test email
        if (isset($_POST['crr_test_email']) && wp_verify_nonce($_POST['crr_debug_nonce'], 'crr_test_email')) {
            $test_to = sanitize_email($_POST['crr_test_email_to']);
            if ($test_to) {
                $sent = wp_mail(
                    $test_to,
                    'Test Email - Cliente Richieste Regionali',
                    "Questa è un'email di test dal plugin Cliente Richieste Regionali.\n\nSe ricevi questa email, il sistema funziona correttamente.",
                    array('Content-Type: text/plain; charset=UTF-8')
                );
                if ($sent) {
                    echo '<div class="notice notice-success"><p>' . sprintf(__('Email di test inviata a %s', 'cliente-richieste-regionali'), $test_to) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Errore nell\'invio dell\'email di test. Controlla il log.', 'cliente-richieste-regionali') . '</p></div>';
                }
            }
        }

        include CRR_PLUGIN_DIR . 'admin/views/debug-email.php';
    }

    /**
     * Renderizza la pagina form builder
     */
    public function render_form_builder_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        include CRR_PLUGIN_DIR . 'admin/views/form-builder.php';
    }

    /**
     * Salva i campi del form
     */
    private function save_form_fields() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $fields = array();

        if (isset($_POST['crr_fields']) && is_array($_POST['crr_fields'])) {
            foreach ($_POST['crr_fields'] as $index => $field) {
                $fields[] = array(
                    'id' => sanitize_key($field['id']),
                    'label' => sanitize_text_field($field['label']),
                    'type' => sanitize_key($field['type']),
                    'required' => isset($field['required']) ? 1 : 0,
                    'placeholder' => sanitize_text_field($field['placeholder'] ?? ''),
                    'options' => isset($field['options']) ? sanitize_textarea_field($field['options']) : '',
                    'in_email' => isset($field['in_email']) ? 1 : 0,
                    'width' => sanitize_key($field['width'] ?? 'full'),
                    'order' => intval($index)
                );
            }
        }

        update_option('crr_form_fields', $fields);
        add_settings_error('crr_messages', 'crr_message', __('Campi del form salvati.', 'cliente-richieste-regionali'), 'updated');
    }

    /**
     * Ottiene i campi del form (con default)
     */
    public static function get_form_fields() {
        $fields = get_option('crr_form_fields', null);

        // Se non ci sono campi salvati, usa i default
        if ($fields === null || empty($fields)) {
            $fields = self::get_default_fields();
        }

        // Ordina per order
        usort($fields, function($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        return $fields;
    }

    /**
     * Campi di default
     */
    public static function get_default_fields() {
        return array(
            array(
                'id' => 'nome',
                'label' => 'Nome',
                'type' => 'text',
                'required' => 1,
                'placeholder' => '',
                'options' => '',
                'in_email' => 0,
                'width' => 'half',
                'order' => 0
            ),
            array(
                'id' => 'cognome',
                'label' => 'Cognome',
                'type' => 'text',
                'required' => 1,
                'placeholder' => '',
                'options' => '',
                'in_email' => 0,
                'width' => 'half',
                'order' => 1
            ),
            array(
                'id' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'required' => 1,
                'placeholder' => '',
                'options' => '',
                'in_email' => 0,
                'width' => 'half',
                'order' => 2
            ),
            array(
                'id' => 'telefono',
                'label' => 'Telefono',
                'type' => 'tel',
                'required' => 0,
                'placeholder' => '',
                'options' => '',
                'in_email' => 0,
                'width' => 'half',
                'order' => 3
            ),
            array(
                'id' => 'regione',
                'label' => 'Regione',
                'type' => 'regione',
                'required' => 1,
                'placeholder' => 'Seleziona regione...',
                'options' => '',
                'in_email' => 1,
                'width' => 'full',
                'order' => 4
            ),
            array(
                'id' => 'richiesta',
                'label' => 'La tua richiesta',
                'type' => 'textarea',
                'required' => 1,
                'placeholder' => 'Descrivi la tua richiesta...',
                'options' => '',
                'in_email' => 1,
                'width' => 'full',
                'order' => 5
            )
        );
    }

    /**
     * AJAX: Reinvia email
     */
    public function ajax_resend_email() {
        check_ajax_referer('crr_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permessi insufficienti.', 'cliente-richieste-regionali')));
        }

        $richiesta_id = isset($_POST['richiesta_id']) ? intval($_POST['richiesta_id']) : 0;

        if (!$richiesta_id) {
            wp_send_json_error(array('message' => __('ID richiesta non valido.', 'cliente-richieste-regionali')));
        }

        $sent = $this->email_sender->resend_notification($richiesta_id);

        if ($sent) {
            wp_send_json_success(array('message' => __('Email inviata con successo!', 'cliente-richieste-regionali')));
        } else {
            wp_send_json_error(array('message' => __('Errore durante l\'invio dell\'email.', 'cliente-richieste-regionali')));
        }
    }

    /**
     * Ottiene i dati per la lista richieste
     */
    public function get_richieste_for_list() {
        $args = array(
            'regione' => isset($_GET['filter_regione']) ? sanitize_text_field($_GET['filter_regione']) : '',
            'email_inviata' => isset($_GET['filter_email']) && $_GET['filter_email'] !== '' ? intval($_GET['filter_email']) : '',
            'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'data_creazione',
            'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC',
            'limit' => 20,
            'offset' => isset($_GET['paged']) ? (intval($_GET['paged']) - 1) * 20 : 0
        );

        return array(
            'items' => $this->database->get_richieste($args),
            'total' => $this->database->count_richieste($args)
        );
    }
}
