<?php
/**
 * Vista: Impostazioni Email
 */

if (!defined('ABSPATH')) {
    exit;
}

// Mostra messaggi
settings_errors('crr_messages');

// Ottieni i campi dal Form Builder
$form_fields = CRR_Admin::get_form_fields();

// Ottieni le impostazioni correnti dei campi email
$campi_email = get_option('crr_campi_email', array());

$email_oggetto = get_option('crr_email_oggetto', __('Nuova richiesta dalla regione {regione}', 'cliente-richieste-regionali'));
$email_template = get_option('crr_email_template', __("È stata ricevuta una nuova richiesta.\n\n{contenuto_email}\n\n---\nEmail generata automaticamente dal sistema.", 'cliente-richieste-regionali'));
$email_fallback = get_option('crr_email_fallback', get_option('admin_email'));
$email_mittente = get_option('crr_email_mittente', get_option('admin_email'));
$nome_mittente = get_option('crr_nome_mittente', get_bloginfo('name'));
$email_copia_completa = get_option('crr_email_copia_completa', '');
$copia_completa_attiva = get_option('crr_copia_completa_attiva', 0);

// Tipi di campo da escludere dalle impostazioni email (non hanno dati utili)
$excluded_types = array('heading', 'paragraph', 'hidden');
?>

<div class="wrap">
    <h1><?php _e('Impostazioni Email', 'cliente-richieste-regionali'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('crr_save_impostazioni', 'crr_impostazioni_nonce'); ?>

        <!-- Campi da includere -->
        <div class="crr-settings-section">
            <h2><?php _e('Campi da Includere nell\'Email', 'cliente-richieste-regionali'); ?></h2>
            <p class="description"><?php _e('Seleziona quali campi del form devono essere inclusi nell\'email inviata ai contatti regionali. Puoi anche configurare questa opzione direttamente nel Costruttore Form usando l\'opzione "Includi in email".', 'cliente-richieste-regionali'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Campi del form', 'cliente-richieste-regionali'); ?></th>
                    <td>
                        <fieldset>
                            <?php foreach ($form_fields as $field) : ?>
                                <?php
                                // Salta i tipi di campo non-dati
                                if (in_array($field['type'], $excluded_types)) {
                                    continue;
                                }

                                $field_id = $field['id'];
                                $field_label = $field['label'];

                                // Verifica se il campo è selezionato (dal Form Builder o dalle impostazioni email)
                                $is_checked = !empty($field['in_email']);
                                if (isset($campi_email[$field_id])) {
                                    $is_checked = !empty($campi_email[$field_id]);
                                }
                                ?>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="crr_campo_<?php echo esc_attr($field_id); ?>" value="1" <?php checked($is_checked, true); ?>>
                                    <?php echo esc_html($field_label); ?>
                                    <span style="color: #666; font-size: 12px;">(<?php echo esc_html($field['type']); ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                        <p class="description" style="margin-top: 10px;">
                            <?php _e('Nota: Queste impostazioni sovrascrivono l\'opzione "Includi in email" del Costruttore Form quando salvi.', 'cliente-richieste-regionali'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Template Email -->
        <div class="crr-settings-section">
            <h2><?php _e('Template Email', 'cliente-richieste-regionali'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="crr_email_oggetto"><?php _e('Oggetto Email', 'cliente-richieste-regionali'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="crr_email_oggetto" id="crr_email_oggetto"
                               value="<?php echo esc_attr($email_oggetto); ?>"
                               class="large-text">
                        <p class="description">
                            <?php _e('Placeholder disponibili: {regione}, {id}, {data}', 'cliente-richieste-regionali'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="crr_email_template"><?php _e('Corpo Email', 'cliente-richieste-regionali'); ?></label>
                    </th>
                    <td>
                        <textarea name="crr_email_template" id="crr_email_template"
                                  rows="10" class="large-text code"><?php echo esc_textarea($email_template); ?></textarea>
                        <p class="description">
                            <?php _e('Placeholder disponibili: {contenuto_email} (i campi selezionati sopra), {regione}, {id}, {data}', 'cliente-richieste-regionali'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Impostazioni Mittente -->
        <div class="crr-settings-section">
            <h2><?php _e('Impostazioni Mittente', 'cliente-richieste-regionali'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="crr_nome_mittente"><?php _e('Nome Mittente', 'cliente-richieste-regionali'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="crr_nome_mittente" id="crr_nome_mittente"
                               value="<?php echo esc_attr($nome_mittente); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="crr_email_mittente"><?php _e('Email Mittente', 'cliente-richieste-regionali'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="crr_email_mittente" id="crr_email_mittente"
                               value="<?php echo esc_attr($email_mittente); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="crr_email_fallback"><?php _e('Email Fallback', 'cliente-richieste-regionali'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="crr_email_fallback" id="crr_email_fallback"
                               value="<?php echo esc_attr($email_fallback); ?>"
                               class="regular-text">
                        <p class="description">
                            <?php _e('Email di destinazione per le regioni senza contatto configurato.', 'cliente-richieste-regionali'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Copia Completa -->
        <div class="crr-settings-section">
            <h2><?php _e('Copia Completa Richieste', 'cliente-richieste-regionali'); ?></h2>
            <p class="description"><?php _e('Invia una copia completa di ogni richiesta (con TUTTI i campi) a un indirizzo email specifico, indipendentemente dalle impostazioni dei campi sopra.', 'cliente-richieste-regionali'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Attiva copia completa', 'cliente-richieste-regionali'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="crr_copia_completa_attiva" value="1" <?php checked($copia_completa_attiva, 1); ?>>
                            <?php _e('Invia una copia con tutti i dati della richiesta', 'cliente-richieste-regionali'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="crr_email_copia_completa"><?php _e('Email destinatario', 'cliente-richieste-regionali'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="crr_email_copia_completa" id="crr_email_copia_completa"
                               value="<?php echo esc_attr($email_copia_completa); ?>"
                               class="regular-text">
                        <p class="description">
                            <?php _e('Indirizzo email che riceverà la copia completa di tutte le richieste.', 'cliente-richieste-regionali'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="crr_save_impostazioni" class="button button-primary" value="<?php _e('Salva Impostazioni', 'cliente-richieste-regionali'); ?>">
        </p>
    </form>
</div>
