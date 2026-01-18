<?php
/**
 * Classe per gli aggiornamenti automatici del plugin da GitHub
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRR_Updater {

    /**
     * Username GitHub
     */
    private $github_username = 'GMCodes04';

    /**
     * Nome repository
     */
    private $github_repo = 'cliente-richieste-regionali';

    /**
     * File principale del plugin
     */
    private $plugin_file;

    /**
     * Slug del plugin
     */
    private $plugin_slug;

    /**
     * Versione corrente
     */
    private $current_version;

    /**
     * Dati GitHub cachati
     */
    private $github_response;

    /**
     * Costruttore
     */
    public function __construct() {
        $this->plugin_file = CRR_PLUGIN_BASENAME;
        $this->plugin_slug = dirname($this->plugin_file);
        $this->current_version = CRR_VERSION;

        // Hook per verificare aggiornamenti
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));

        // Hook per mostrare info del plugin
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);

        // Hook dopo l'aggiornamento
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }

    /**
     * Ottiene le informazioni della release da GitHub
     */
    private function get_github_release() {
        if (!empty($this->github_response)) {
            return $this->github_response;
        }

        // Controlla cache
        $cached = get_transient('crr_github_release');
        if ($cached !== false) {
            $this->github_response = $cached;
            return $cached;
        }

        // Chiamata API GitHub
        $url = "https://api.github.com/repos/{$this->github_username}/{$this->github_repo}/releases/latest";

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            ),
            'timeout' => 10
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (empty($data) || isset($data->message)) {
            return false;
        }

        // Cache per 6 ore
        set_transient('crr_github_release', $data, 6 * HOUR_IN_SECONDS);

        $this->github_response = $data;
        return $data;
    }

    /**
     * Verifica se c'è un aggiornamento disponibile
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $release = $this->get_github_release();

        if (!$release) {
            return $transient;
        }

        // Rimuovi 'v' dalla versione GitHub (es: v1.2.0 -> 1.2.0)
        $github_version = ltrim($release->tag_name, 'v');

        // Confronta versioni
        if (version_compare($github_version, $this->current_version, '>')) {
            // Trova il file zip nella release
            $download_url = '';
            if (!empty($release->assets)) {
                foreach ($release->assets as $asset) {
                    if (strpos($asset->name, '.zip') !== false) {
                        $download_url = $asset->browser_download_url;
                        break;
                    }
                }
            }

            // Se non c'è asset, usa il source code
            if (empty($download_url)) {
                $download_url = $release->zipball_url;
            }

            $transient->response[$this->plugin_file] = (object) array(
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_file,
                'new_version' => $github_version,
                'package' => $download_url,
                'url' => "https://github.com/{$this->github_username}/{$this->github_repo}",
                'icons' => array(),
                'banners' => array(),
                'tested' => get_bloginfo('version'),
                'requires_php' => '7.4'
            );
        }

        return $transient;
    }

    /**
     * Mostra le informazioni del plugin nella finestra popup
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $release = $this->get_github_release();

        if (!$release) {
            return $result;
        }

        $github_version = ltrim($release->tag_name, 'v');

        $plugin_info = array(
            'name' => 'Cliente Richieste Regionali',
            'slug' => $this->plugin_slug,
            'version' => $github_version,
            'author' => '<a href="https://github.com/' . $this->github_username . '">GMCodes04</a>',
            'homepage' => "https://github.com/{$this->github_username}/{$this->github_repo}",
            'requires' => '5.0',
            'tested' => get_bloginfo('version'),
            'requires_php' => '7.4',
            'downloaded' => 0,
            'last_updated' => $release->published_at,
            'sections' => array(
                'description' => 'Plugin WordPress per gestione richieste clienti con email regionali.',
                'changelog' => $this->parse_changelog($release->body),
                'installation' => 'Carica il plugin nella directory /wp-content/plugins/ e attivalo.'
            ),
            'download_link' => $release->zipball_url
        );

        return (object) $plugin_info;
    }

    /**
     * Processa il changelog da markdown
     */
    private function parse_changelog($body) {
        if (empty($body)) {
            return '<p>Nessuna nota di rilascio disponibile.</p>';
        }

        // Converti markdown base in HTML
        $html = nl2br(esc_html($body));
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        $html = preg_replace('/^- (.*)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);

        return $html;
    }

    /**
     * Dopo l'installazione, rinomina la cartella
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        // Verifica che sia il nostro plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_file) {
            return $result;
        }

        // La cartella scaricata da GitHub ha un nome diverso (es: GMCodes04-cliente-richieste-regionali-xxxxx)
        // Dobbiamo rinominarla
        $plugin_folder = WP_PLUGIN_DIR . '/' . $this->plugin_slug;

        if ($wp_filesystem->move($result['destination'], $plugin_folder)) {
            $result['destination'] = $plugin_folder;
        }

        // Riattiva il plugin
        activate_plugin($this->plugin_file);

        // Pulisci la cache
        delete_transient('crr_github_release');

        return $result;
    }
}
