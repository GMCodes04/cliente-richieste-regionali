<?php
/**
 * Vista: Dettaglio Richiesta
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ottieni i campi dal Form Builder
$form_fields = CRR_Admin::get_form_fields();

// Prepara i dati extra (campi dinamici)
$dati_extra = array();
if (!empty($richiesta['dati_extra'])) {
    $dati_extra = json_decode($richiesta['dati_extra'], true);
    if (!is_array($dati_extra)) {
        $dati_extra = array();
    }
}

// Tipi di campo da escludere dalla visualizzazione
$excluded_types = array('heading', 'paragraph', 'hidden');

// Debug: mostra i dati raw
if (isset($_GET['debug'])) {
    echo '<pre style="background:#f0f0f0;padding:10px;margin:10px 0;">';
    echo '<strong>Richiesta raw:</strong>' . "\n";
    print_r($richiesta);
    echo "\n<strong>Dati extra decodificati:</strong>\n";
    print_r($dati_extra);
    echo "\n<strong>Campi Form Builder:</strong>\n";
    foreach ($form_fields as $f) {
        echo $f['id'] . ' (' . $f['type'] . ")\n";
    }
    echo '</pre>';
}
?>

<div class="wrap">
    <h1>
        <?php printf(__('Richiesta #%d', 'cliente-richieste-regionali'), $richiesta['id']); ?>
        <a href="<?php echo admin_url('admin.php?page=crr-richieste'); ?>" class="page-title-action"><?php _e('Torna alla lista', 'cliente-richieste-regionali'); ?></a>
    </h1>

    <div class="crr-dettaglio-wrapper">
        <!-- Info Richiesta - Campi dinamici dal Form Builder -->
        <div class="crr-dettaglio-card">
            <h2><?php _e('Dati Richiesta', 'cliente-richieste-regionali'); ?></h2>
            <table class="form-table">
                <?php foreach ($form_fields as $field) : ?>
                    <?php
                    // Salta i tipi di campo non-dati
                    if (in_array($field['type'], $excluded_types)) {
                        continue;
                    }

                    $field_id = $field['id'];
                    $field_label = $field['label'];

                    // Cerca il valore nei campi standard o nei dati extra
                    $valore = '';
                    if (isset($richiesta[$field_id]) && $richiesta[$field_id] !== '') {
                        $valore = $richiesta[$field_id];
                    } elseif (isset($dati_extra[$field_id])) {
                        $valore = isset($dati_extra[$field_id]['value']) ? $dati_extra[$field_id]['value'] : '';
                    }

                    // Formatta array (checkbox groups, multi-select)
                    if (is_array($valore)) {
                        $valore = implode(', ', $valore);
                    }

                    // Mostra il valore o un placeholder
                    $display_value = !empty($valore) ? esc_html($valore) : '-';

                    // Formattazione speciale per alcuni tipi
                    if ($field['type'] === 'email' && !empty($valore)) {
                        $display_value = '<a href="mailto:' . esc_attr($valore) . '">' . esc_html($valore) . '</a>';
                    } elseif ($field['type'] === 'tel' && !empty($valore)) {
                        $display_value = '<a href="tel:' . esc_attr($valore) . '">' . esc_html($valore) . '</a>';
                    } elseif ($field['type'] === 'url' && !empty($valore)) {
                        $display_value = '<a href="' . esc_url($valore) . '" target="_blank">' . esc_html($valore) . '</a>';
                    } elseif ($field['type'] === 'textarea' && !empty($valore)) {
                        $display_value = '<div class="crr-richiesta-text">' . nl2br(esc_html($valore)) . '</div>';
                    } elseif ($field['type'] === 'checkbox' && $valore === '1') {
                        $display_value = '<span class="dashicons dashicons-yes" style="color: green;"></span> ' . __('Sì', 'cliente-richieste-regionali');
                    } elseif ($field['type'] === 'checkbox' && $valore === '') {
                        $display_value = '<span class="dashicons dashicons-no" style="color: #999;"></span> ' . __('No', 'cliente-richieste-regionali');
                    } elseif ($field['type'] === 'regione' && !empty($valore)) {
                        $display_value = '<strong>' . esc_html($valore) . '</strong>';
                    }
                    ?>
                    <tr>
                        <th><?php echo esc_html($field_label); ?></th>
                        <td><?php echo $display_value; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Info Sistema -->
        <div class="crr-dettaglio-card crr-dettaglio-meta">
            <h2><?php _e('Informazioni', 'cliente-richieste-regionali'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php _e('Data ricezione', 'cliente-richieste-regionali'); ?></th>
                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($richiesta['data_creazione']))); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Stato email', 'cliente-richieste-regionali'); ?></th>
                    <td>
                        <?php if ($richiesta['email_inviata']) : ?>
                            <span class="crr-status crr-status-success"><?php _e('Email inviata', 'cliente-richieste-regionali'); ?></span>
                        <?php else : ?>
                            <span class="crr-status crr-status-pending"><?php _e('Email non inviata', 'cliente-richieste-regionali'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Destinatari email', 'cliente-richieste-regionali'); ?></th>
                    <td>
                        <?php
                        $emails_destinatari = CRR()->database->get_emails_for_regione($richiesta['regione']);
                        echo esc_html(implode(', ', $emails_destinatari));
                        ?>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="button" class="button button-primary crr-resend-email" data-id="<?php echo esc_attr($richiesta['id']); ?>">
                    <?php _e('Reinvia Email', 'cliente-richieste-regionali'); ?>
                </button>
            </p>
        </div>
    </div>
</div>
