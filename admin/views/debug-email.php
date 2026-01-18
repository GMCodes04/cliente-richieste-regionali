<?php
/**
 * Vista: Debug Email
 */

if (!defined('ABSPATH')) {
    exit;
}

$log_file = CRR_PLUGIN_DIR . 'email-debug.log';
$log_content = '';

if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
}
?>

<div class="wrap">
    <h1><?php _e('Debug Email', 'cliente-richieste-regionali'); ?></h1>

    <p><?php _e('Usa questa pagina per diagnosticare problemi con l\'invio delle email.', 'cliente-richieste-regionali'); ?></p>

    <!-- Test Email -->
    <div class="crr-settings-section">
        <h2><?php _e('Invia Email di Test', 'cliente-richieste-regionali'); ?></h2>
        <p><?php _e('Invia un\'email di test per verificare che il sistema di invio funzioni.', 'cliente-richieste-regionali'); ?></p>

        <form method="post" action="">
            <?php wp_nonce_field('crr_test_email', 'crr_debug_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="crr_test_email_to"><?php _e('Email destinatario', 'cliente-richieste-regionali'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="crr_test_email_to" id="crr_test_email_to"
                               value="<?php echo esc_attr(get_option('admin_email')); ?>"
                               class="regular-text" required>
                        <p class="description"><?php _e('Inserisci un indirizzo email valido per il test.', 'cliente-richieste-regionali'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="crr_test_email" class="button button-primary" value="<?php _e('Invia Email di Test', 'cliente-richieste-regionali'); ?>">
            </p>
        </form>
    </div>

    <!-- Info Sistema -->
    <div class="crr-settings-section">
        <h2><?php _e('Informazioni Sistema', 'cliente-richieste-regionali'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _e('PHP Version', 'cliente-richieste-regionali'); ?></th>
                <td><?php echo esc_html(phpversion()); ?></td>
            </tr>
            <tr>
                <th><?php _e('WordPress Version', 'cliente-richieste-regionali'); ?></th>
                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
            </tr>
            <tr>
                <th><?php _e('Admin Email', 'cliente-richieste-regionali'); ?></th>
                <td><?php echo esc_html(get_option('admin_email')); ?></td>
            </tr>
            <tr>
                <th><?php _e('Email Mittente Plugin', 'cliente-richieste-regionali'); ?></th>
                <td><?php echo esc_html(get_option('crr_email_mittente', get_option('admin_email'))); ?></td>
            </tr>
            <tr>
                <th><?php _e('Email Fallback', 'cliente-richieste-regionali'); ?></th>
                <td><?php echo esc_html(get_option('crr_email_fallback', get_option('admin_email'))); ?></td>
            </tr>
            <tr>
                <th><?php _e('Plugin SMTP Attivo', 'cliente-richieste-regionali'); ?></th>
                <td>
                    <?php
                    $smtp_plugins = array(
                        'wp-mail-smtp/wp_mail_smtp.php' => 'WP Mail SMTP',
                        'easy-wp-smtp/easy-wp-smtp.php' => 'Easy WP SMTP',
                        'post-smtp/postman-smtp.php' => 'Post SMTP',
                        'smtp-mailer/main.php' => 'SMTP Mailer',
                        'fluent-smtp/fluent-smtp.php' => 'FluentSMTP'
                    );

                    $found = false;
                    foreach ($smtp_plugins as $plugin_path => $plugin_name) {
                        if (is_plugin_active($plugin_path)) {
                            echo '<span style="color: green;">&#10004; ' . esc_html($plugin_name) . '</span>';
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        echo '<span style="color: red;">&#10008; ' . __('Nessun plugin SMTP rilevato', 'cliente-richieste-regionali') . '</span>';
                        echo '<p class="description" style="color: #d63638;">';
                        echo __('ATTENZIONE: Senza un plugin SMTP, le email potrebbero non essere inviate correttamente. Ti consiglio di installare "WP Mail SMTP" e configurarlo con Gmail, Outlook o un altro servizio.', 'cliente-richieste-regionali');
                        echo '</p>';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Log -->
    <div class="crr-settings-section">
        <h2><?php _e('Log Email', 'cliente-richieste-regionali'); ?></h2>

        <?php if (empty($log_content)) : ?>
            <p><?php _e('Nessun log disponibile. I log verranno generati quando proverai a inviare un\'email.', 'cliente-richieste-regionali'); ?></p>
        <?php else : ?>
            <p>
                <form method="post" action="" style="display: inline;">
                    <?php wp_nonce_field('crr_clear_log', 'crr_debug_nonce'); ?>
                    <input type="submit" name="crr_clear_log" class="button" value="<?php _e('Cancella Log', 'cliente-richieste-regionali'); ?>"
                           onclick="return confirm('<?php _e('Sei sicuro di voler cancellare il log?', 'cliente-richieste-regionali'); ?>');">
                </form>
            </p>
            <textarea readonly class="large-text code" rows="20" style="font-family: monospace; font-size: 12px;"><?php echo esc_textarea($log_content); ?></textarea>
        <?php endif; ?>
    </div>

    <!-- Suggerimenti -->
    <div class="crr-settings-section">
        <h2><?php _e('Suggerimenti per Risolvere Problemi Email', 'cliente-richieste-regionali'); ?></h2>
        <ol>
            <li>
                <strong><?php _e('Installa un plugin SMTP', 'cliente-richieste-regionali'); ?></strong><br>
                <?php _e('La maggior parte dei server non può inviare email direttamente. Installa "WP Mail SMTP" e configuralo con Gmail, Outlook, SendGrid o un altro servizio.', 'cliente-richieste-regionali'); ?>
            </li>
            <li>
                <strong><?php _e('Verifica l\'email mittente', 'cliente-richieste-regionali'); ?></strong><br>
                <?php _e('L\'email mittente deve essere valida e possibilmente dello stesso dominio del sito.', 'cliente-richieste-regionali'); ?>
            </li>
            <li>
                <strong><?php _e('Controlla la cartella spam', 'cliente-richieste-regionali'); ?></strong><br>
                <?php _e('Le email potrebbero finire nello spam del destinatario.', 'cliente-richieste-regionali'); ?>
            </li>
            <li>
                <strong><?php _e('Verifica i contatti regionali', 'cliente-richieste-regionali'); ?></strong><br>
                <?php _e('Assicurati che la regione selezionata abbia un contatto email configurato E attivo.', 'cliente-richieste-regionali'); ?>
            </li>
        </ol>
    </div>
</div>
