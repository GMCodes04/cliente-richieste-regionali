<?php
/**
 * Vista: Lista Richieste
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ottieni i dati
$data = CRR()->admin->get_richieste_for_list();
$richieste = $data['items'];
$total = $data['total'];
$regioni = CRR()->database->get_regioni();

// Ottieni i campi dal Form Builder per determinare quali mostrare nella lista
$form_fields = CRR_Admin::get_form_fields();

// Trova i campi chiave per la lista (primo campo testo come "nome", email, regione)
$nome_field = null;
$email_field = null;
$regione_field = null;
$textarea_field = null;

foreach ($form_fields as $field) {
    if ($field['type'] === 'text' && $nome_field === null) {
        $nome_field = $field;
    } elseif ($field['type'] === 'email' && $email_field === null) {
        $email_field = $field;
    } elseif ($field['type'] === 'regione' && $regione_field === null) {
        $regione_field = $field;
    } elseif ($field['type'] === 'textarea' && $textarea_field === null) {
        $textarea_field = $field;
    }
}

// Paginazione
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$total_pages = ceil($total / $per_page);

// Filtri attuali
$filter_regione = isset($_GET['filter_regione']) ? sanitize_text_field($_GET['filter_regione']) : '';
$filter_email = isset($_GET['filter_email']) && $_GET['filter_email'] !== '' ? intval($_GET['filter_email']) : '';

/**
 * Funzione helper per ottenere il valore di un campo da una richiesta
 */
function crr_get_field_value($richiesta, $field_id) {
    // Prima controlla i campi standard
    if (isset($richiesta[$field_id]) && $richiesta[$field_id] !== '') {
        return $richiesta[$field_id];
    }

    // Poi controlla i dati extra
    if (!empty($richiesta['dati_extra'])) {
        $dati_extra = json_decode($richiesta['dati_extra'], true);
        if (is_array($dati_extra) && isset($dati_extra[$field_id])) {
            $value = isset($dati_extra[$field_id]['value']) ? $dati_extra[$field_id]['value'] : '';
            if (is_array($value)) {
                return implode(', ', $value);
            }
            return $value;
        }
    }

    return '';
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Richieste Clienti', 'cliente-richieste-regionali'); ?></h1>

    <hr class="wp-header-end">

    <!-- Filtri -->
    <div class="crr-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="crr-richieste">

            <select name="filter_regione">
                <option value=""><?php _e('Tutte le regioni', 'cliente-richieste-regionali'); ?></option>
                <?php foreach ($regioni as $regione) : ?>
                    <option value="<?php echo esc_attr($regione); ?>" <?php selected($filter_regione, $regione); ?>>
                        <?php echo esc_html($regione); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="filter_email">
                <option value=""><?php _e('Tutte', 'cliente-richieste-regionali'); ?></option>
                <option value="1" <?php selected($filter_email, 1); ?>><?php _e('Email inviata', 'cliente-richieste-regionali'); ?></option>
                <option value="0" <?php selected($filter_email, 0); ?>><?php _e('Email non inviata', 'cliente-richieste-regionali'); ?></option>
            </select>

            <input type="submit" class="button" value="<?php _e('Filtra', 'cliente-richieste-regionali'); ?>">

            <?php if ($filter_regione || $filter_email !== '') : ?>
                <a href="<?php echo admin_url('admin.php?page=crr-richieste'); ?>" class="button"><?php _e('Reset', 'cliente-richieste-regionali'); ?></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Statistiche -->
    <div class="crr-stats">
        <span><?php printf(_n('%s richiesta trovata', '%s richieste trovate', $total, 'cliente-richieste-regionali'), number_format_i18n($total)); ?></span>
    </div>

    <!-- Tabella -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="column-id" style="width: 50px;"><?php _e('ID', 'cliente-richieste-regionali'); ?></th>
                <th scope="col" class="column-nome">
                    <?php echo $nome_field ? esc_html($nome_field['label']) : __('Nome', 'cliente-richieste-regionali'); ?>
                </th>
                <?php if ($email_field) : ?>
                <th scope="col" class="column-email"><?php echo esc_html($email_field['label']); ?></th>
                <?php endif; ?>
                <?php if ($regione_field) : ?>
                <th scope="col" class="column-regione"><?php echo esc_html($regione_field['label']); ?></th>
                <?php endif; ?>
                <?php if ($textarea_field) : ?>
                <th scope="col" class="column-richiesta"><?php echo esc_html($textarea_field['label']); ?></th>
                <?php endif; ?>
                <th scope="col" class="column-data"><?php _e('Data', 'cliente-richieste-regionali'); ?></th>
                <th scope="col" class="column-email-status" style="width: 100px;"><?php _e('Email', 'cliente-richieste-regionali'); ?></th>
                <th scope="col" class="column-actions" style="width: 150px;"><?php _e('Azioni', 'cliente-richieste-regionali'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($richieste)) : ?>
                <tr>
                    <td colspan="8"><?php _e('Nessuna richiesta trovata.', 'cliente-richieste-regionali'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($richieste as $richiesta) : ?>
                    <tr>
                        <td><?php echo esc_html($richiesta['id']); ?></td>
                        <td>
                            <strong>
                            <?php
                            // Mostra i primi campi di testo come identificativo
                            $nome_display = '';
                            if ($nome_field) {
                                $nome_display = crr_get_field_value($richiesta, $nome_field['id']);
                            }
                            // Se c'è un secondo campo testo (cognome), aggiungilo
                            $found_nome = false;
                            foreach ($form_fields as $field) {
                                if ($field['type'] === 'text') {
                                    if (!$found_nome) {
                                        $found_nome = true;
                                        continue;
                                    }
                                    // Secondo campo testo
                                    $cognome = crr_get_field_value($richiesta, $field['id']);
                                    if ($cognome) {
                                        $nome_display .= ' ' . $cognome;
                                    }
                                    break;
                                }
                            }
                            echo esc_html($nome_display ?: '-');
                            ?>
                            </strong>
                        </td>
                        <?php if ($email_field) : ?>
                        <td><?php echo esc_html(crr_get_field_value($richiesta, $email_field['id']) ?: '-'); ?></td>
                        <?php endif; ?>
                        <?php if ($regione_field) : ?>
                        <td><?php echo esc_html(crr_get_field_value($richiesta, $regione_field['id']) ?: $richiesta['regione']); ?></td>
                        <?php endif; ?>
                        <?php if ($textarea_field) : ?>
                        <td class="column-richiesta">
                            <?php
                            $richiesta_text = crr_get_field_value($richiesta, $textarea_field['id']);
                            echo esc_html($richiesta_text ? wp_trim_words($richiesta_text, 10, '...') : '-');
                            ?>
                        </td>
                        <?php endif; ?>
                        <td>
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($richiesta['data_creazione']))); ?>
                        </td>
                        <td>
                            <?php if ($richiesta['email_inviata']) : ?>
                                <span class="crr-status crr-status-success"><?php _e('Inviata', 'cliente-richieste-regionali'); ?></span>
                            <?php else : ?>
                                <span class="crr-status crr-status-pending"><?php _e('Non inviata', 'cliente-richieste-regionali'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=crr-richieste&action=view&id=' . $richiesta['id']); ?>" class="button button-small">
                                <?php _e('Dettagli', 'cliente-richieste-regionali'); ?>
                            </a>
                            <button type="button" class="button button-small crr-resend-email" data-id="<?php echo esc_attr($richiesta['id']); ?>">
                                <?php _e('Reinvia', 'cliente-richieste-regionali'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginazione -->
    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s elemento', '%s elementi', $total, 'cliente-richieste-regionali'), number_format_i18n($total)); ?>
                </span>
                <span class="pagination-links">
                    <?php
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    echo $page_links;
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>
