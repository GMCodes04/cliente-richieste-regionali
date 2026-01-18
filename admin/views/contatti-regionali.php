<?php
/**
 * Vista: Contatti Regionali
 */

if (!defined('ABSPATH')) {
    exit;
}

// Mostra messaggi
settings_errors('crr_messages');

// Ottieni i contatti
$contatti = CRR()->database->get_contatti();
?>

<div class="wrap">
    <h1><?php _e('Contatti Regionali', 'cliente-richieste-regionali'); ?></h1>

    <p><?php _e('Configura le email di contatto per ogni regione. Puoi inserire più indirizzi email separati da virgola. Le richieste provenienti da una regione verranno inviate a tutti gli indirizzi configurati.', 'cliente-richieste-regionali'); ?></p>

    <form method="post" action="">
        <?php wp_nonce_field('crr_save_contatti', 'crr_contatti_nonce'); ?>

        <table class="wp-list-table widefat fixed striped crr-contatti-table">
            <thead>
                <tr>
                    <th scope="col" style="width: 40px;"><?php _e('Attivo', 'cliente-richieste-regionali'); ?></th>
                    <th scope="col" style="width: 180px;"><?php _e('Regione', 'cliente-richieste-regionali'); ?></th>
                    <th scope="col"><?php _e('Email Contatti (una per riga o separate da virgola)', 'cliente-richieste-regionali'); ?></th>
                    <th scope="col"><?php _e('Nome Referente (opzionale)', 'cliente-richieste-regionali'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contatti as $contatto) :
                    $key = sanitize_title($contatto['regione']);
                ?>
                    <tr>
                        <td>
                            <input type="checkbox"
                                   name="crr_attivo_<?php echo esc_attr($key); ?>"
                                   value="1"
                                   <?php checked($contatto['attivo'], 1); ?>>
                        </td>
                        <td>
                            <strong><?php echo esc_html($contatto['regione']); ?></strong>
                        </td>
                        <td>
                            <textarea name="crr_emails_<?php echo esc_attr($key); ?>"
                                      class="large-text"
                                      rows="2"
                                      placeholder="email1@esempio.it, email2@esempio.it"><?php echo esc_textarea(isset($contatto['emails']) ? $contatto['emails'] : (isset($contatto['email']) ? $contatto['email'] : '')); ?></textarea>
                        </td>
                        <td>
                            <input type="text"
                                   name="crr_nome_<?php echo esc_attr($key); ?>"
                                   value="<?php echo esc_attr($contatto['nome_contatto']); ?>"
                                   class="regular-text"
                                   placeholder="Nome referente">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="description">
            <?php
            $email_fallback = get_option('crr_email_fallback', get_option('admin_email'));
            printf(
                __('Le regioni non attive o senza email configurata invieranno le richieste a: %s (configurabile nelle Impostazioni Email)', 'cliente-richieste-regionali'),
                '<strong>' . esc_html($email_fallback) . '</strong>'
            );
            ?>
        </p>

        <p class="submit">
            <input type="submit" name="crr_save_contatti" class="button button-primary" value="<?php _e('Salva Contatti', 'cliente-richieste-regionali'); ?>">
        </p>
    </form>
</div>
