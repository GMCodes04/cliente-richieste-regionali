<?php
/**
 * Vista: Form Builder
 */

if (!defined('ABSPATH')) {
    exit;
}

// Mostra messaggi
settings_errors('crr_messages');

// Ottieni i campi salvati
$fields = CRR_Admin::get_form_fields();

// Tipi di campo disponibili
$field_types = array(
    'text' => __('Testo', 'cliente-richieste-regionali'),
    'email' => __('Email', 'cliente-richieste-regionali'),
    'tel' => __('Telefono', 'cliente-richieste-regionali'),
    'number' => __('Numero', 'cliente-richieste-regionali'),
    'textarea' => __('Area di testo', 'cliente-richieste-regionali'),
    'select' => __('Menu a tendina', 'cliente-richieste-regionali'),
    'checkbox' => __('Checkbox singola', 'cliente-richieste-regionali'),
    'checkbox_group' => __('Gruppo Checkbox', 'cliente-richieste-regionali'),
    'radio' => __('Radio buttons', 'cliente-richieste-regionali'),
    'date' => __('Data', 'cliente-richieste-regionali'),
    'time' => __('Ora', 'cliente-richieste-regionali'),
    'url' => __('URL', 'cliente-richieste-regionali'),
    'regione' => __('Regione (speciale)', 'cliente-richieste-regionali'),
    'heading' => __('Intestazione/Titolo', 'cliente-richieste-regionali'),
    'paragraph' => __('Paragrafo/Testo', 'cliente-richieste-regionali'),
    'hidden' => __('Campo nascosto', 'cliente-richieste-regionali'),
);

$width_options = array(
    'full' => __('Intera larghezza', 'cliente-richieste-regionali'),
    'half' => __('Mezza larghezza', 'cliente-richieste-regionali'),
    'third' => __('Un terzo', 'cliente-richieste-regionali'),
);
?>

<div class="wrap crr-form-builder-wrap">
    <h1><?php _e('Costruttore Form', 'cliente-richieste-regionali'); ?></h1>

    <p><?php _e('Personalizza i campi del form. Trascina per riordinare, aggiungi nuovi campi o modifica quelli esistenti.', 'cliente-richieste-regionali'); ?></p>

    <div class="crr-builder-info">
        <strong><?php _e('Shortcode:', 'cliente-richieste-regionali'); ?></strong>
        <code>[form_richiesta_cliente]</code>
        <span class="description"><?php _e('Inserisci questo shortcode in qualsiasi pagina per mostrare il form.', 'cliente-richieste-regionali'); ?></span>
    </div>

    <form method="post" action="" id="crr-form-builder">
        <?php wp_nonce_field('crr_save_form_fields', 'crr_form_builder_nonce'); ?>

        <div class="crr-builder-container">
            <!-- Lista campi -->
            <div class="crr-fields-list" id="crr-fields-list">
                <?php foreach ($fields as $index => $field) : ?>
                    <div class="crr-field-item" data-index="<?php echo $index; ?>">
                        <div class="crr-field-header">
                            <span class="crr-field-drag dashicons dashicons-move"></span>
                            <span class="crr-field-title"><?php echo esc_html($field['label']); ?></span>
                            <span class="crr-field-type-badge"><?php echo esc_html($field_types[$field['type']] ?? $field['type']); ?></span>
                            <span class="crr-field-toggle dashicons dashicons-arrow-down"></span>
                            <button type="button" class="crr-field-remove" title="<?php _e('Rimuovi', 'cliente-richieste-regionali'); ?>">&times;</button>
                        </div>
                        <div class="crr-field-content" style="display: none;">
                            <input type="hidden" name="crr_fields[<?php echo $index; ?>][id]" value="<?php echo esc_attr($field['id']); ?>" class="crr-field-id">

                            <div class="crr-field-row">
                                <div class="crr-field-col">
                                    <label><?php _e('Etichetta', 'cliente-richieste-regionali'); ?></label>
                                    <input type="text" name="crr_fields[<?php echo $index; ?>][label]" value="<?php echo esc_attr($field['label']); ?>" class="crr-field-label regular-text" required>
                                </div>
                                <div class="crr-field-col">
                                    <label><?php _e('Tipo campo', 'cliente-richieste-regionali'); ?></label>
                                    <select name="crr_fields[<?php echo $index; ?>][type]" class="crr-field-type">
                                        <?php foreach ($field_types as $type_key => $type_label) : ?>
                                            <option value="<?php echo esc_attr($type_key); ?>" <?php selected($field['type'], $type_key); ?>>
                                                <?php echo esc_html($type_label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="crr-field-row">
                                <div class="crr-field-col">
                                    <label><?php _e('Placeholder', 'cliente-richieste-regionali'); ?></label>
                                    <input type="text" name="crr_fields[<?php echo $index; ?>][placeholder]" value="<?php echo esc_attr($field['placeholder'] ?? ''); ?>" class="regular-text">
                                </div>
                                <div class="crr-field-col">
                                    <label><?php _e('Larghezza', 'cliente-richieste-regionali'); ?></label>
                                    <select name="crr_fields[<?php echo $index; ?>][width]">
                                        <?php foreach ($width_options as $w_key => $w_label) : ?>
                                            <option value="<?php echo esc_attr($w_key); ?>" <?php selected($field['width'] ?? 'full', $w_key); ?>>
                                                <?php echo esc_html($w_label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="crr-field-row crr-options-row" style="<?php echo in_array($field['type'], array('select', 'checkbox_group', 'radio')) ? '' : 'display:none;'; ?>">
                                <div class="crr-field-col-full">
                                    <label><?php _e('Opzioni (una per riga)', 'cliente-richieste-regionali'); ?></label>
                                    <textarea name="crr_fields[<?php echo $index; ?>][options]" rows="4" class="large-text crr-field-options" placeholder="Opzione 1&#10;Opzione 2&#10;Opzione 3"><?php echo esc_textarea($field['options'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="crr-field-row crr-field-checkboxes">
                                <label>
                                    <input type="checkbox" name="crr_fields[<?php echo $index; ?>][required]" value="1" <?php checked($field['required'] ?? 0, 1); ?>>
                                    <?php _e('Campo obbligatorio', 'cliente-richieste-regionali'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" name="crr_fields[<?php echo $index; ?>][in_email]" value="1" <?php checked($field['in_email'] ?? 0, 1); ?>>
                                    <?php _e('Includi nell\'email', 'cliente-richieste-regionali'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pulsante aggiungi campo -->
            <div class="crr-add-field-container">
                <button type="button" class="button button-secondary" id="crr-add-field">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Aggiungi Campo', 'cliente-richieste-regionali'); ?>
                </button>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="crr_save_form_fields" class="button button-primary button-large" value="<?php _e('Salva Form', 'cliente-richieste-regionali'); ?>">
            <button type="button" class="button" id="crr-reset-fields"><?php _e('Ripristina Default', 'cliente-richieste-regionali'); ?></button>
        </p>
    </form>
</div>

<!-- Template per nuovo campo -->
<script type="text/template" id="crr-field-template">
    <div class="crr-field-item" data-index="{{INDEX}}">
        <div class="crr-field-header">
            <span class="crr-field-drag dashicons dashicons-move"></span>
            <span class="crr-field-title"><?php _e('Nuovo Campo', 'cliente-richieste-regionali'); ?></span>
            <span class="crr-field-type-badge"><?php _e('Testo', 'cliente-richieste-regionali'); ?></span>
            <span class="crr-field-toggle dashicons dashicons-arrow-down"></span>
            <button type="button" class="crr-field-remove" title="<?php _e('Rimuovi', 'cliente-richieste-regionali'); ?>">&times;</button>
        </div>
        <div class="crr-field-content">
            <input type="hidden" name="crr_fields[{{INDEX}}][id]" value="campo_{{INDEX}}" class="crr-field-id">

            <div class="crr-field-row">
                <div class="crr-field-col">
                    <label><?php _e('Etichetta', 'cliente-richieste-regionali'); ?></label>
                    <input type="text" name="crr_fields[{{INDEX}}][label]" value="" class="crr-field-label regular-text" required>
                </div>
                <div class="crr-field-col">
                    <label><?php _e('Tipo campo', 'cliente-richieste-regionali'); ?></label>
                    <select name="crr_fields[{{INDEX}}][type]" class="crr-field-type">
                        <?php foreach ($field_types as $type_key => $type_label) : ?>
                            <option value="<?php echo esc_attr($type_key); ?>"><?php echo esc_html($type_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="crr-field-row">
                <div class="crr-field-col">
                    <label><?php _e('Placeholder', 'cliente-richieste-regionali'); ?></label>
                    <input type="text" name="crr_fields[{{INDEX}}][placeholder]" value="" class="regular-text">
                </div>
                <div class="crr-field-col">
                    <label><?php _e('Larghezza', 'cliente-richieste-regionali'); ?></label>
                    <select name="crr_fields[{{INDEX}}][width]">
                        <?php foreach ($width_options as $w_key => $w_label) : ?>
                            <option value="<?php echo esc_attr($w_key); ?>"><?php echo esc_html($w_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="crr-field-row crr-options-row" style="display:none;">
                <div class="crr-field-col-full">
                    <label><?php _e('Opzioni (una per riga)', 'cliente-richieste-regionali'); ?></label>
                    <textarea name="crr_fields[{{INDEX}}][options]" rows="4" class="large-text crr-field-options" placeholder="Opzione 1&#10;Opzione 2&#10;Opzione 3"></textarea>
                </div>
            </div>

            <div class="crr-field-row crr-field-checkboxes">
                <label>
                    <input type="checkbox" name="crr_fields[{{INDEX}}][required]" value="1">
                    <?php _e('Campo obbligatorio', 'cliente-richieste-regionali'); ?>
                </label>
                <label>
                    <input type="checkbox" name="crr_fields[{{INDEX}}][in_email]" value="1">
                    <?php _e('Includi nell\'email', 'cliente-richieste-regionali'); ?>
                </label>
            </div>
        </div>
    </div>
</script>

<script>
jQuery(document).ready(function($) {
    var fieldIndex = <?php echo count($fields); ?>;

    // Toggle campo
    $(document).on('click', '.crr-field-header', function(e) {
        if ($(e.target).hasClass('crr-field-remove')) return;
        var $item = $(this).closest('.crr-field-item');
        var $content = $item.find('.crr-field-content');
        var $toggle = $item.find('.crr-field-toggle');

        $content.slideToggle(200);
        $toggle.toggleClass('dashicons-arrow-down dashicons-arrow-up');
    });

    // Rimuovi campo
    $(document).on('click', '.crr-field-remove', function(e) {
        e.stopPropagation();
        if (confirm('<?php _e('Sei sicuro di voler rimuovere questo campo?', 'cliente-richieste-regionali'); ?>')) {
            $(this).closest('.crr-field-item').fadeOut(200, function() {
                $(this).remove();
                reindexFields();
            });
        }
    });

    // Aggiungi campo
    $('#crr-add-field').on('click', function() {
        var template = $('#crr-field-template').html();
        template = template.replace(/\{\{INDEX\}\}/g, fieldIndex);
        $('#crr-fields-list').append(template);

        var $newField = $('#crr-fields-list .crr-field-item:last');
        $newField.find('.crr-field-content').show();
        $newField.find('.crr-field-toggle').removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
        $newField.find('.crr-field-label').focus();

        fieldIndex++;
    });

    // Aggiorna titolo quando cambia l'etichetta
    $(document).on('input', '.crr-field-label', function() {
        var label = $(this).val() || '<?php _e('Nuovo Campo', 'cliente-richieste-regionali'); ?>';
        $(this).closest('.crr-field-item').find('.crr-field-title').text(label);

        // Aggiorna anche l'ID se è un nuovo campo
        var $idField = $(this).closest('.crr-field-item').find('.crr-field-id');
        if ($idField.val().indexOf('campo_') === 0) {
            var newId = label.toLowerCase()
                .replace(/[àáâãäå]/g, 'a')
                .replace(/[èéêë]/g, 'e')
                .replace(/[ìíîï]/g, 'i')
                .replace(/[òóôõö]/g, 'o')
                .replace(/[ùúûü]/g, 'u')
                .replace(/[^a-z0-9]/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_|_$/g, '');
            $idField.val(newId || 'campo');
        }
    });

    // Mostra/nascondi opzioni in base al tipo
    $(document).on('change', '.crr-field-type', function() {
        var type = $(this).val();
        var $optionsRow = $(this).closest('.crr-field-item').find('.crr-options-row');
        var $badge = $(this).closest('.crr-field-item').find('.crr-field-type-badge');

        if (type === 'select' || type === 'checkbox_group' || type === 'radio') {
            $optionsRow.slideDown(200);
        } else {
            $optionsRow.slideUp(200);
        }

        // Aggiorna badge
        $badge.text($(this).find('option:selected').text());
    });

    // Drag and drop per riordinare
    if (typeof $.fn.sortable !== 'undefined') {
        $('#crr-fields-list').sortable({
            handle: '.crr-field-drag',
            placeholder: 'crr-field-placeholder',
            update: function() {
                reindexFields();
            }
        });
    }

    // Reindicizza i campi dopo riordino o rimozione
    function reindexFields() {
        $('#crr-fields-list .crr-field-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/crr_fields\[\d+\]/, 'crr_fields[' + index + ']');
                    $(this).attr('name', name);
                }
            });
        });
    }

    // Reset ai default
    $('#crr-reset-fields').on('click', function() {
        if (confirm('<?php _e('Sei sicuro di voler ripristinare i campi di default? Tutte le personalizzazioni andranno perse.', 'cliente-richieste-regionali'); ?>')) {
            // Invia form con flag di reset
            $('<input>').attr({
                type: 'hidden',
                name: 'crr_reset_fields',
                value: '1'
            }).appendTo('#crr-form-builder');

            // Svuota i campi salvati
            $('#crr-fields-list').empty();
            $('#crr-form-builder').submit();
        }
    });
});
</script>
