<?php
/**
 * Plugin Name: Cliente Richieste Regionali
 * Plugin URI:
 * Description: Gestisce le richieste dei clienti e invia email automatiche ai contatti regionali con solo i dati selezionati.
 * Version: 1.1.0
 * Author: Marco
 * Author URI:
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cliente-richieste-regionali
 * Domain Path: /languages
 */

// Impedisce l'accesso diretto
if (!defined('ABSPATH')) {
    exit;
}

// Definizione costanti
define('CRR_VERSION', '1.1.0');
define('CRR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale del plugin
 */
class Cliente_Richieste_Regionali {

    /**
     * Istanza singleton
     */
    private static $instance = null;

    /**
     * Riferimenti alle classi
     */
    public $database;
    public $form_handler;
    public $email_sender;
    public $admin;

    /**
     * Ottiene l'istanza singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Costruttore
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Carica le dipendenze
     */
    private function load_dependencies() {
        require_once CRR_PLUGIN_DIR . 'includes/class-database.php';
        require_once CRR_PLUGIN_DIR . 'includes/class-form-handler.php';
        require_once CRR_PLUGIN_DIR . 'includes/class-email-sender.php';
        require_once CRR_PLUGIN_DIR . 'includes/class-admin.php';
    }

    /**
     * Inizializza gli hook
     */
    private function init_hooks() {
        // Hook di attivazione/disattivazione
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Inizializza le classi dopo che WordPress è caricato
        add_action('plugins_loaded', array($this, 'init_classes'));

        // Carica le traduzioni
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Inizializza le classi del plugin
     */
    public function init_classes() {
        $this->database = new CRR_Database();
        $this->email_sender = new CRR_Email_Sender($this->database);
        $this->form_handler = new CRR_Form_Handler($this->database, $this->email_sender);

        if (is_admin()) {
            $this->admin = new CRR_Admin($this->database, $this->email_sender);
        }
    }

    /**
     * Attivazione plugin
     */
    public function activate() {
        require_once CRR_PLUGIN_DIR . 'includes/class-database.php';
        $database = new CRR_Database();
        $database->create_tables();
        $database->set_default_options();

        // Pulisce la cache dei rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Disattivazione plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Carica le traduzioni
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'cliente-richieste-regionali',
            false,
            dirname(CRR_PLUGIN_BASENAME) . '/languages/'
        );
    }
}

/**
 * Funzione per ottenere l'istanza del plugin
 */
function CRR() {
    return Cliente_Richieste_Regionali::get_instance();
}

// Avvia il plugin
CRR();
