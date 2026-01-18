<?php
/**
 * Classe per la gestione del database
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRR_Database {

    /**
     * Nome tabella richieste
     */
    private $table_richieste;

    /**
     * Nome tabella contatti regionali
     */
    private $table_contatti;

    /**
     * Lista regioni italiane
     */
    private $regioni = array(
        'Abruzzo',
        'Basilicata',
        'Calabria',
        'Campania',
        'Emilia-Romagna',
        'Friuli Venezia Giulia',
        'Lazio',
        'Liguria',
        'Lombardia',
        'Marche',
        'Molise',
        'Piemonte',
        'Puglia',
        'Sardegna',
        'Sicilia',
        'Toscana',
        'Trentino-Alto Adige',
        'Umbria',
        'Valle d\'Aosta',
        'Veneto'
    );

    /**
     * Costruttore
     */
    public function __construct() {
        global $wpdb;
        $this->table_richieste = $wpdb->prefix . 'crr_richieste';
        $this->table_contatti = $wpdb->prefix . 'crr_contatti_regionali';
    }

    /**
     * Ottiene la lista delle regioni
     */
    public function get_regioni() {
        return $this->regioni;
    }

    /**
     * Crea le tabelle del plugin
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabella richieste
        $sql_richieste = "CREATE TABLE {$this->table_richieste} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nome varchar(100) DEFAULT '',
            cognome varchar(100) DEFAULT '',
            email varchar(150) DEFAULT '',
            telefono varchar(20) DEFAULT '',
            indirizzo varchar(255) DEFAULT '',
            citta varchar(100) DEFAULT '',
            cap varchar(10) DEFAULT '',
            regione varchar(50) NOT NULL,
            richiesta text DEFAULT '',
            dati_extra longtext DEFAULT '',
            data_creazione datetime DEFAULT CURRENT_TIMESTAMP,
            email_inviata tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY regione (regione),
            KEY data_creazione (data_creazione)
        ) $charset_collate;";

        // Tabella contatti regionali
        $sql_contatti = "CREATE TABLE {$this->table_contatti} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            regione varchar(50) NOT NULL,
            emails text DEFAULT '',
            nome_contatto varchar(100) DEFAULT '',
            attivo tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY regione (regione)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_richieste);
        dbDelta($sql_contatti);

        // Inserisce le regioni di default
        $this->insert_default_regioni();
    }

    /**
     * Inserisce le regioni di default
     */
    private function insert_default_regioni() {
        global $wpdb;

        foreach ($this->regioni as $regione) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->table_contatti} WHERE regione = %s",
                $regione
            ));

            if (!$exists) {
                $wpdb->insert(
                    $this->table_contatti,
                    array(
                        'regione' => $regione,
                        'emails' => '',
                        'nome_contatto' => '',
                        'attivo' => 0
                    ),
                    array('%s', '%s', '%s', '%d')
                );
            }
        }
    }

    /**
     * Imposta le opzioni di default
     */
    public function set_default_options() {
        // Campi da includere nell'email (default: solo regione e richiesta)
        $campi_email_default = array(
            'nome' => 0,
            'cognome' => 0,
            'email' => 0,
            'telefono' => 0,
            'indirizzo' => 0,
            'citta' => 0,
            'cap' => 0,
            'regione' => 1,
            'richiesta' => 1
        );

        if (!get_option('crr_campi_email')) {
            add_option('crr_campi_email', $campi_email_default);
        }

        if (!get_option('crr_email_oggetto')) {
            add_option('crr_email_oggetto', __('Nuova richiesta dalla regione {regione}', 'cliente-richieste-regionali'));
        }

        if (!get_option('crr_email_template')) {
            $template_default = __("È stata ricevuta una nuova richiesta.\n\n{contenuto_email}\n\n---\nEmail generata automaticamente dal sistema.", 'cliente-richieste-regionali');
            add_option('crr_email_template', $template_default);
        }

        if (!get_option('crr_email_fallback')) {
            add_option('crr_email_fallback', get_option('admin_email'));
        }

        if (!get_option('crr_email_mittente')) {
            add_option('crr_email_mittente', get_option('admin_email'));
        }

        if (!get_option('crr_nome_mittente')) {
            add_option('crr_nome_mittente', get_bloginfo('name'));
        }
    }

    // ========================================
    // METODI CRUD PER RICHIESTE
    // ========================================

    /**
     * Inserisce una nuova richiesta (legacy)
     */
    public function insert_richiesta($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table_richieste,
            array(
                'nome' => sanitize_text_field($data['nome'] ?? ''),
                'cognome' => sanitize_text_field($data['cognome'] ?? ''),
                'email' => sanitize_email($data['email'] ?? ''),
                'telefono' => sanitize_text_field($data['telefono'] ?? ''),
                'indirizzo' => sanitize_text_field($data['indirizzo'] ?? ''),
                'citta' => sanitize_text_field($data['citta'] ?? ''),
                'cap' => sanitize_text_field($data['cap'] ?? ''),
                'regione' => sanitize_text_field($data['regione']),
                'richiesta' => sanitize_textarea_field($data['richiesta'] ?? ''),
                'dati_extra' => '',
                'data_creazione' => current_time('mysql'),
                'email_inviata' => 0
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Inserisce una nuova richiesta con campi dinamici
     */
    public function insert_richiesta_dynamic($data, $fields) {
        global $wpdb;

        // Campi standard della tabella
        $standard_fields = array('nome', 'cognome', 'email', 'telefono', 'indirizzo', 'citta', 'cap', 'regione', 'richiesta');

        // Separa i dati standard dai dati extra
        $db_data = array(
            'nome' => '',
            'cognome' => '',
            'email' => '',
            'telefono' => '',
            'indirizzo' => '',
            'citta' => '',
            'cap' => '',
            'regione' => '',
            'richiesta' => '',
            'data_creazione' => current_time('mysql'),
            'email_inviata' => 0
        );

        $extra_data = array();

        foreach ($data as $key => $value) {
            if (in_array($key, $standard_fields)) {
                $db_data[$key] = $value;
            } else {
                // Trova l'etichetta del campo
                $label = $key;
                foreach ($fields as $field) {
                    if ($field['id'] === $key) {
                        $label = $field['label'];
                        break;
                    }
                }
                $extra_data[$key] = array(
                    'label' => $label,
                    'value' => $value
                );
            }
        }

        // Salva i dati extra come JSON
        $db_data['dati_extra'] = !empty($extra_data) ? json_encode($extra_data, JSON_UNESCAPED_UNICODE) : '';

        // Formato: nome, cognome, email, telefono, indirizzo, citta, cap, regione, richiesta, data_creazione, email_inviata, dati_extra
        $result = $wpdb->insert(
            $this->table_richieste,
            $db_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Ottiene tutti i dati di una richiesta (inclusi extra)
     */
    public function get_richiesta_completa($id) {
        $richiesta = $this->get_richiesta($id);

        if (!$richiesta) {
            return null;
        }

        // Decodifica i dati extra
        $richiesta['dati_extra_decoded'] = array();
        if (!empty($richiesta['dati_extra'])) {
            $decoded = json_decode($richiesta['dati_extra'], true);
            if (is_array($decoded)) {
                $richiesta['dati_extra_decoded'] = $decoded;
            }
        }

        return $richiesta;
    }

    /**
     * Ottiene una richiesta per ID
     */
    public function get_richiesta($id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_richieste} WHERE id = %d",
            $id
        ), ARRAY_A);
    }

    /**
     * Ottiene tutte le richieste con filtri
     */
    public function get_richieste($args = array()) {
        global $wpdb;

        $defaults = array(
            'regione' => '',
            'email_inviata' => '',
            'data_da' => '',
            'data_a' => '',
            'orderby' => 'data_creazione',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['regione'])) {
            $where[] = 'regione = %s';
            $values[] = $args['regione'];
        }

        if ($args['email_inviata'] !== '') {
            $where[] = 'email_inviata = %d';
            $values[] = (int) $args['email_inviata'];
        }

        if (!empty($args['data_da'])) {
            $where[] = 'data_creazione >= %s';
            $values[] = $args['data_da'] . ' 00:00:00';
        }

        if (!empty($args['data_a'])) {
            $where[] = 'data_creazione <= %s';
            $values[] = $args['data_a'] . ' 23:59:59';
        }

        $where_clause = implode(' AND ', $where);

        // Sanitizza orderby e order
        $allowed_orderby = array('id', 'nome', 'cognome', 'email', 'regione', 'data_creazione', 'email_inviata');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'data_creazione';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $limit = absint($args['limit']);
        $offset = absint($args['offset']);

        $sql = "SELECT * FROM {$this->table_richieste} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT {$limit} OFFSET {$offset}";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Conta le richieste con filtri
     */
    public function count_richieste($args = array()) {
        global $wpdb;

        $where = array('1=1');
        $values = array();

        if (!empty($args['regione'])) {
            $where[] = 'regione = %s';
            $values[] = $args['regione'];
        }

        if (isset($args['email_inviata']) && $args['email_inviata'] !== '') {
            $where[] = 'email_inviata = %d';
            $values[] = (int) $args['email_inviata'];
        }

        if (!empty($args['data_da'])) {
            $where[] = 'data_creazione >= %s';
            $values[] = $args['data_da'] . ' 00:00:00';
        }

        if (!empty($args['data_a'])) {
            $where[] = 'data_creazione <= %s';
            $values[] = $args['data_a'] . ' 23:59:59';
        }

        $where_clause = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) FROM {$this->table_richieste} WHERE {$where_clause}";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Aggiorna lo stato email di una richiesta
     */
    public function update_email_status($id, $status) {
        global $wpdb;

        return $wpdb->update(
            $this->table_richieste,
            array('email_inviata' => (int) $status),
            array('id' => (int) $id),
            array('%d'),
            array('%d')
        );
    }

    /**
     * Elimina una richiesta
     */
    public function delete_richiesta($id) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_richieste,
            array('id' => (int) $id),
            array('%d')
        );
    }

    // ========================================
    // METODI CRUD PER CONTATTI REGIONALI
    // ========================================

    /**
     * Ottiene un contatto per regione
     */
    public function get_contatto_by_regione($regione) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_contatti} WHERE regione = %s",
            $regione
        ), ARRAY_A);
    }

    /**
     * Ottiene tutti i contatti regionali
     */
    public function get_contatti() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->table_contatti} ORDER BY regione ASC",
            ARRAY_A
        );
    }

    /**
     * Aggiorna un contatto regionale
     */
    public function update_contatto($regione, $data) {
        global $wpdb;

        // Sanitizza ogni email nella lista
        $emails_raw = isset($data['emails']) ? $data['emails'] : '';
        $emails_sanitized = $this->sanitize_emails_list($emails_raw);

        return $wpdb->update(
            $this->table_contatti,
            array(
                'emails' => $emails_sanitized,
                'nome_contatto' => sanitize_text_field($data['nome_contatto'] ?? ''),
                'attivo' => (int) ($data['attivo'] ?? 0)
            ),
            array('regione' => $regione),
            array('%s', '%s', '%d'),
            array('%s')
        );
    }

    /**
     * Sanitizza una lista di email (separate da virgola o a capo)
     */
    private function sanitize_emails_list($emails_string) {
        // Separa per virgola o a capo
        $emails = preg_split('/[\s,;]+/', $emails_string);
        $valid_emails = array();

        foreach ($emails as $email) {
            $email = trim($email);
            if (!empty($email) && is_email($email)) {
                $valid_emails[] = sanitize_email($email);
            }
        }

        return implode(', ', array_unique($valid_emails));
    }

    /**
     * Ottiene le email per una regione come array
     */
    public function get_emails_for_regione($regione) {
        $contatto = $this->get_contatto_by_regione($regione);

        if ($contatto && $contatto['attivo'] && !empty($contatto['emails'])) {
            // Splitta le email e ritorna come array
            $emails = array_map('trim', explode(',', $contatto['emails']));
            return array_filter($emails);
        }

        // Ritorna email fallback come array
        return array(get_option('crr_email_fallback', get_option('admin_email')));
    }

    /**
     * Ottiene l'email per una regione (o fallback) - compatibilità
     * Ritorna tutte le email separate da virgola per wp_mail
     */
    public function get_email_for_regione($regione) {
        $emails = $this->get_emails_for_regione($regione);
        return implode(', ', $emails);
    }
}
