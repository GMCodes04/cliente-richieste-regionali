<?php
/**
 * Template: Form Richiesta Cliente (Dinamico)
 *
 * Variabili disponibili:
 * - $fields: array dei campi configurati dal Form Builder
 * - $regioni: array delle regioni italiane
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="crr-form-container">
    <form id="crr-richiesta-form" class="crr-form" method="post">
        <?php wp_nonce_field('crr_form_nonce', 'crr_nonce'); ?>

        <?php foreach ($fields as $field) :
            $field_id = esc_attr($field['id']);
            $field_type = $field['type'];
            $field_label = esc_html($field['label']);
            $field_required = !empty($field['required']);
            $field_placeholder = isset($field['placeholder']) ? esc_attr($field['placeholder']) : '';
            $field_options = isset($field['options']) ? $field['options'] : '';
            $required_html = $field_required ? ' required' : '';
            $required_star = $field_required ? ' <span class="required">*</span>' : '';
        ?>

            <?php if ($field_type === 'heading') : ?>
                <div class="crr-form-row crr-form-heading">
                    <h3><?php echo $field_label; ?></h3>
                </div>

            <?php elseif ($field_type === 'paragraph') : ?>
                <div class="crr-form-row crr-form-paragraph">
                    <p><?php echo $field_label; ?></p>
                </div>

            <?php elseif ($field_type === 'hidden') : ?>
                <input type="hidden" name="<?php echo $field_id; ?>" value="<?php echo $field_placeholder; ?>">

            <?php elseif ($field_type === 'regione') : ?>
                <div class="crr-form-row">
                    <div class="crr-form-field">
                        <label for="crr_<?php echo $field_id; ?>"><?php echo $field_label; ?><?php echo $required_star; ?></label>
                        <select id="crr_<?php echo $field_id; ?>" name="<?php echo $field_id; ?>"<?php echo $required_html; ?>>
                            <option value=""><?php _e('Seleziona regione...', 'cliente-richieste-regionali'); ?></option>
                            <?php foreach ($regioni as $regione) : ?>
                                <option value="<?php echo esc_attr($regione); ?>"><?php echo esc_html($regione); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="crr-error-message"></span>
                    </div>
                </div>

            <?php elseif ($field_type === 'textarea') : ?>
                <div class="crr-form-row">
                    <div class="crr-form-field">
                        <label for="crr_<?php echo $field_id; ?>"><?php echo $field_label; ?><?php echo $required_star; ?></label>
                        <textarea id="crr_<?php echo $field_id; ?>" name="<?php echo $field_id; ?>" rows="5" placeholder="<?php echo $field_placeholder; ?>"<?php echo $required_html; ?>></textarea>
                        <span class="crr-error-message"></span>
                    </div>
                </div>

            <?php elseif ($field_type === 'select') : ?>
                <div class="crr-form-row">
                    <div class="crr-form-field">
                        <label for="crr_<?php echo $field_id; ?>"><?php echo $field_label; ?><?php echo $required_star; ?></label>
                        <select id="crr_<?php echo $field_id; ?>" name="<?php echo $field_id; ?>"<?php echo $required_html; ?>>
                            <option value=""><?php _e('Seleziona...', 'cliente-richieste-regionali'); ?></option>
                            <?php
                            $options_array = array_filter(array_map('trim', explode("\n", $field_options)));
                            foreach ($options_array as $option) :
                            ?>
                                <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="crr-error-message"></span>
                    </div>
                </div>

            <?php elseif ($field_type === 'checkbox') : ?>
                <div class="crr-form-row">
                    <div class="crr-form-field crr-form-field-checkbox">
                        <label class="crr-checkbox-label">
                            <input type="checkbox" id="crr_<?php echo $field_id; ?>" name="<?php echo $field_id; ?>" value="1"<?php echo $required_html; ?>>
                            <span><?php echo $field_label; ?><?php echo $required_star; ?></span>
                        </label>
                        <span class="crr-error-message"></span>
                    </div>
                </div>

            <?php elseif ($field_type === 'checkbox_group') : ?>
                <div class="crr-form-row">
                    <div class="crr-form-field crr-form-field-checkbox-group">
                        <label><?php echo $field_label; ?><?php echo $required_star; ?></label>
                        <div class="crr-checkbox-group">
                            <?php
                            $options_array = array_filter(array_map('trim', explode("\n", $field_options)));
                            foreach ($options_array as $index => $option) :
                                $option_id = $field_id . '_' . $index;
                            ?>
                                <label class="crr-checkbox-option">
                                    <input type="checkbox" name="<?php echo $field_id; ?>[]" value="<?php echo esc_attr($option); ?>">
                                    <span><?php echo esc_html($option); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <span class="crr-error-message"></span>
                    </div>
                </div>

            <?php elseif ($field_type === 'radio') : ?>
                <div class="crr-form-row">
                    <div class="crr-form-field crr-form-field-radio">
                        <label><?php echo $field_label; ?><?php echo $required_star; ?></label>
                        <div class="crr-radio-group">
                            <?php
                            $options_array = array_filter(array_map('trim', explode("\n", $field_options)));
                            foreach ($options_array as $index => $option) :
                                $option_id = $field_id . '_' . $index;
                            ?>
                                <label class="crr-radio-option">
                                    <input type="radio" name="<?php echo $field_id; ?>" value="<?php echo esc_attr($option); ?>"<?php echo ($index === 0 && $field_required) ? ' required' : ''; ?>>
                                    <span><?php echo esc_html($option); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <span class="crr-error-message"></span>
                    </div>
                </div>

            <?php else : ?>
                <!-- Campi standard: text, email, tel, number, date, time, url -->
                <div class="crr-form-row">
                    <div class="crr-form-field">
                        <label for="crr_<?php echo $field_id; ?>"><?php echo $field_label; ?><?php echo $required_star; ?></label>
                        <input type="<?php echo esc_attr($field_type); ?>"
                               id="crr_<?php echo $field_id; ?>"
                               name="<?php echo $field_id; ?>"
                               placeholder="<?php echo $field_placeholder; ?>"
                               <?php echo $required_html; ?>>
                        <span class="crr-error-message"></span>
                    </div>
                </div>

            <?php endif; ?>

        <?php endforeach; ?>

        <div class="crr-form-row crr-form-submit">
            <button type="submit" class="crr-submit-btn">
                <span class="crr-btn-text"><?php _e('Invia Richiesta', 'cliente-richieste-regionali'); ?></span>
                <span class="crr-btn-loading" style="display: none;">
                    <span class="crr-spinner"></span>
                    <?php _e('Invio in corso...', 'cliente-richieste-regionali'); ?>
                </span>
            </button>
        </div>

        <div class="crr-form-message" style="display: none;"></div>
    </form>
</div>
