<?php
/**
 * Metaboxen für Zutaten
 */

// Sicherheitsprüfung: Direkter Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fügt die Metaboxen zum Zutaten-Editor hinzu
 */
add_action('add_meta_boxes', 'mkr_add_ingredient_meta_boxes');
function mkr_add_ingredient_meta_boxes() {
    add_meta_box(
        'mkr_ingredient_nutrition',
        __('Nährwertinformationen', 'mein-kochbuch-rezepte'),
        'mkr_ingredient_nutrition_callback',
        'ingredient',
        'normal',
        'high'
    );
    
    add_meta_box(
        'mkr_ingredient_metrics',
        __('Maßangaben', 'mein-kochbuch-rezepte'),
        'mkr_ingredient_metrics_callback',
        'ingredient',
        'normal',
        'high'
    );
    
    add_meta_box(
        'mkr_ingredient_alternatives',
        __('Alternativen', 'mein-kochbuch-rezepte'),
        'mkr_ingredient_alternatives_callback',
        'ingredient',
        'normal',
        'default'
    );
    
    add_meta_box(
        'mkr_ingredient_seasonal',
        __('Saisonalität', 'mein-kochbuch-rezepte'),
        'mkr_ingredient_seasonal_callback',
        'ingredient',
        'side',
        'default'
    );
}

/**
 * Callback für die Nährwertinformationen-Metabox
 */
function mkr_ingredient_nutrition_callback($post) {
    wp_nonce_field('mkr_ingredient_save_data', 'mkr_ingredient_meta_nonce');
    
    $calories = get_post_meta($post->ID, '_mkr_calories_per_100g', true);
    $proteins = get_post_meta($post->ID, '_mkr_proteins_per_100g', true);
    $fats = get_post_meta($post->ID, '_mkr_fats_per_100g', true);
    $carbs = get_post_meta($post->ID, '_mkr_carbs_per_100g', true);
    $sugar = get_post_meta($post->ID, '_mkr_sugar_per_100g', true);
    $fiber = get_post_meta($post->ID, '_mkr_fiber_per_100g', true);
    $salt = get_post_meta($post->ID, '_mkr_salt_per_100g', true);
    
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="mkr_calories"><?php _e('Kalorien pro 100g:', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="number" id="mkr_calories" name="mkr_calories" value="<?php echo esc_attr($calories); ?>" step="0.1" min="0" aria-describedby="mkr_calories_desc" />
                <p id="mkr_calories_desc" class="description"><?php _e('Angabe in Kilokalorien (kcal)', 'mein-kochbuch-rezepte'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mkr_proteins"><?php _e('Proteine pro 100g (g):', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="number" id="mkr_proteins" name="mkr_proteins" value="<?php echo esc_attr($proteins); ?>" step="0.1" min="0" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mkr_fats"><?php _e('Fette pro 100g (g):', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="number" id="mkr_fats" name="mkr_fats" value="<?php echo esc_attr($fats); ?>" step="0.1" min="0" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mkr_carbs"><?php _e('Kohlenhydrate pro 100g (g):', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="number" id="mkr_carbs" name="mkr_carbs" value="<?php echo esc_attr($carbs); ?>" step="0.1" min="0" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mkr_sugar"><?php _e('davon Zucker pro 100g (g):', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="number" id="mkr_sugar" name="mkr_sugar" value="<?php echo esc_attr($sugar); ?>" step="0.1" min="0" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mkr_fiber"><?php _e('Ballaststoffe pro 100g (g):', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="number" id="mkr_fiber" name="mkr_fiber" value="<?php echo esc_attr($fiber); ?>" step="0.1" min="0" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mkr_salt"><?php _e('Salz pro 100g (g):', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="number" id="mkr_salt" name="mkr_salt" value="<?php echo esc_attr($salt); ?>" step="0.1" min="0" />
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Callback für die Maßangaben-Metabox
 */
function mkr_ingredient_metrics_callback($post) {
    $weight_per_cup = get_post_meta($post->ID, '_mkr_weight_per_cup', true);
    $weight_per_unit = get_post_meta($post->ID, '_mkr_weight_per_unit', true);
    $density = get_post_meta($post->ID, '_mkr_density', true);
    $volume_to_weight = get_post_meta($post->ID, '_mkr_volume_to_weight', true);
    $metric_imperial_conversion = get_post_meta($post->ID, '_mkr_metric_imperial_conversion', true);
    
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="mkr_weight_per_cup"><?php _e('Gewicht pro Cup (g):', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="number" id="mkr_weight_per_cup" name="mkr_weight_per_cup" value="<?php echo esc_attr($weight_per_cup); ?>" step="0.1" min="0" aria-describedby="mkr_weight_per_cup_desc" />
                <p id="mkr_weight_per_cup_desc" class="description"><?php _e('Gewicht in Gramm für 1 US-Cup dieser Zutat', 'mein-kochbuch-rezepte'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mkr_weight_per_unit"><?php _e('Gewicht pro Stück (g):', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="number" id="mkr_weight_per_unit" name="mkr_weight_per_unit" value="<?php echo esc_attr($weight_per_unit); ?>" step="0.1" min="0" aria-describedby="mkr_weight_per_unit_desc" />
                <p id="mkr_weight_per_unit_desc" class="description"><?php _e('Durchschnittliches Gewicht eines Stücks dieser Zutat in Gramm', 'mein-kochbuch-rezepte'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mkr_density"><?php _e('Dichte (g/ml):', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="number" id="mkr_density" name="mkr_density" value="<?php echo esc_attr($density); ?>" step="0.01" min="0" aria-describedby="mkr_density_desc" />
                <p id="mkr_density_desc" class="description"><?php _e('Dichte der Zutat in g/ml (für Flüssigkeiten)', 'mein-kochbuch-rezepte'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mkr_volume_to_weight"><?php _e('Volumen zu Gewicht:', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="text" id="mkr_volume_to_weight" name="mkr_volume_to_weight" value="<?php echo esc_attr($volume_to_weight); ?>" aria-describedby="mkr_volume_to_weight_desc" />
                <p id="mkr_volume_to_weight_desc" class="description"><?php _e('z.B. "1 EL = 15g" oder "1 TL = 5g"', 'mein-kochbuch-rezepte'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="mkr_metric_imperial_conversion"><?php _e('Metrisch-Imperial Umrechnung:', 'mein-kochbuch-rezepte'); ?></label>
            </th>
            <td>
                <input type="text" id="mkr_metric_imperial_conversion" name="mkr_metric_imperial_conversion" value="<?php echo esc_attr($metric_imperial_conversion); ?>" aria-describedby="mkr_metric_imperial_conversion_desc" />
                <p id="mkr_metric_imperial_conversion_desc" class="description"><?php _e('z.B. "100g = 3.5oz" oder "1 cup = 125g"', 'mein-kochbuch-rezepte'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Callback für die Alternativen-Metabox
 */
function mkr_ingredient_alternatives_callback($post) {
    $alternatives = get_post_meta($post->ID, '_mkr_alternatives', true);
    $alternatives = is_array($alternatives) ? $alternatives : [];
    
    ?>
    <div class="mkr-alternatives-container">
        <div class="mkr-alternatives-list">
            <?php if (!empty($alternatives)): ?>
                <?php foreach ($alternatives as $index => $alternative): ?>
                    <div class="mkr-alternative-group">
                        <input type="text" name="mkr_alternative_name[]" value="<?php echo esc_attr($alternative['name']); ?>" placeholder="<?php esc_attr_e('Name der Alternative', 'mein-kochbuch-rezepte'); ?>" class="mkr-alternative-name" data-autocomplete-type="ingredient" />
                        <input type="text" name="mkr_alternative_ratio[]" value="<?php echo esc_attr($alternative['ratio']); ?>" placeholder="<?php esc_attr_e('Verhältnis (z.B. 1:1)', 'mein-kochbuch-rezepte'); ?>" class="mkr-alternative-ratio" />
                        <button type="button" class="button mkr-remove-alternative"><?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" id="mkr-add-alternative" class="button button-primary"><?php _e('Alternative hinzufügen', 'mein-kochbuch-rezepte'); ?></button>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#mkr-add-alternative').click(function() {
            const group = $('<div class="mkr-alternative-group">');
            group.html(`
                <input type="text" name="mkr_alternative_name[]" placeholder="<?php esc_attr_e('Name der Alternative', 'mein-kochbuch-rezepte'); ?>" class="mkr-alternative-name" data-autocomplete-type="ingredient" />
                <input type="text" name="mkr_alternative_ratio[]" placeholder="<?php esc_attr_e('Verhältnis (z.B. 1:1)', 'mein-kochbuch-rezepte'); ?>" class="mkr-alternative-ratio" />
                <button type="button" class="button mkr-remove-alternative"><?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?></button>
            `);
            
            group.appendTo('.mkr-alternatives-list');
            
            if (typeof window.setupInlineAutocomplete === 'function') {
                window.setupInlineAutocomplete(group.find('.mkr-alternative-name'), 'ingredient');
            }
        });
        
        $(document).on('click', '.mkr-remove-alternative', function() {
            $(this).closest('.mkr-alternative-group').remove();
        });
    });
    </script>
    <?php
}

/**
 * Callback für die Saisonalität-Metabox
 */
function mkr_ingredient_seasonal_callback($post) {
    $seasonal_months = get_post_meta($post->ID, '_mkr_seasonal_months', true);
    $seasonal_months = is_array($seasonal_months) ? $seasonal_months : [];
    
    $months = [
        '1' => __('Januar', 'mein-kochbuch-rezepte'),
        '2' => __('Februar', 'mein-kochbuch-rezepte'),
        '3' => __('März', 'mein-kochbuch-rezepte'),
        '4' => __('April', 'mein-kochbuch-rezepte'),
        '5' => __('Mai', 'mein-kochbuch-rezepte'),
        '6' => __('Juni', 'mein-kochbuch-rezepte'),
        '7' => __('Juli', 'mein-kochbuch-rezepte'),
        '8' => __('August', 'mein-kochbuch-rezepte'),
        '9' => __('September', 'mein-kochbuch-rezepte'),
        '10' => __('Oktober', 'mein-kochbuch-rezepte'),
        '11' => __('November', 'mein-kochbuch-rezepte'),
        '12' => __('Dezember', 'mein-kochbuch-rezepte')
    ];
    
    ?>
    <p><?php _e('Wähle die Monate, in denen diese Zutat Saison hat:', 'mein-kochbuch-rezepte'); ?></p>
    
    <div class="mkr-seasonal-months">
        <?php foreach ($months as $month_num => $month_name): ?>
            <label for="mkr_month_<?php echo esc_attr($month_num); ?>" class="selectit">
                <input type="checkbox" id="mkr_month_<?php echo esc_attr($month_num); ?>" name="mkr_seasonal_months[]" value="<?php echo esc_attr($month_num); ?>" <?php checked(in_array($month_num, $seasonal_months)); ?> />
                <?php echo esc_html($month_name); ?>
            </label><br>
        <?php endforeach; ?>
    </div>
    
    <p>
        <label for="mkr_all_year">
            <input type="checkbox" id="mkr_all_year" name="mkr_all_year" />
            <?php _e('Ganzjährig verfügbar', 'mein-kochbuch-rezepte'); ?>
        </label>
    </p>
    
    <script>
    jQuery(document).ready(function($) {
        $('#mkr_all_year').on('change', function() {
            if ($(this).is(':checked')) {
                $('.mkr-seasonal-months input[type="checkbox"]').prop('checked', true);
            } else {
                $('.mkr-seasonal-months input[type="checkbox"]').prop('checked', false);
            }
        });
    });
    </script>
    <?php
}

/**
 * Speichert die Zutaten-Metadaten beim Speichern einer Zutat
 */
add_action('save_post_ingredient', 'mkr_save_ingredient_meta');
function mkr_save_ingredient_meta($post_id) {
    // Nonce-Überprüfung
    if (!isset($_POST['mkr_ingredient_meta_nonce']) || !wp_verify_nonce($_POST['mkr_ingredient_meta_nonce'], 'mkr_ingredient_save_data')) {
        return;
    }
    
    // Autosave-Überprüfung
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Berechtigungsprüfung
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Nährwertinformationen speichern
    if (isset($_POST['mkr_calories'])) {
        update_post_meta($post_id, '_mkr_calories_per_100g', sanitize_text_field($_POST['mkr_calories']));
    }
    
    if (isset($_POST['mkr_proteins'])) {
        update_post_meta($post_id, '_mkr_proteins_per_100g', sanitize_text_field($_POST['mkr_proteins']));
    }
    
    if (isset($_POST['mkr_fats'])) {
        update_post_meta($post_id, '_mkr_fats_per_100g', sanitize_text_field($_POST['mkr_fats']));
    }
    
    if (isset($_POST['mkr_carbs'])) {
        update_post_meta($post_id, '_mkr_carbs_per_100g', sanitize_text_field($_POST['mkr_carbs']));
    }
    
    if (isset($_POST['mkr_sugar'])) {
        update_post_meta($post_id, '_mkr_sugar_per_100g', sanitize_text_field($_POST['mkr_sugar']));
    }
    
    if (isset($_POST['mkr_fiber'])) {
        update_post_meta($post_id, '_mkr_fiber_per_100g', sanitize_text_field($_POST['mkr_fiber']));
    }
    
    if (isset($_POST['mkr_salt'])) {
        update_post_meta($post_id, '_mkr_salt_per_100g', sanitize_text_field($_POST['mkr_salt']));
    }
    
    // Maßangaben speichern
    if (isset($_POST['mkr_weight_per_cup'])) {
        update_post_meta($post_id, '_mkr_weight_per_cup', sanitize_text_field($_POST['mkr_weight_per_cup']));
    }
    
    if (isset($_POST['mkr_weight_per_unit'])) {
        update_post_meta($post_id, '_mkr_weight_per_unit', sanitize_text_field($_POST['mkr_weight_per_unit']));
    }
    
    if (isset($_POST['mkr_density'])) {
        update_post_meta($post_id, '_mkr_density', sanitize_text_field($_POST['mkr_density']));
    }
    
    if (isset($_POST['mkr_volume_to_weight'])) {
        update_post_meta($post_id, '_mkr_volume_to_weight', sanitize_text_field($_POST['mkr_volume_to_weight']));
    }
    
    if (isset($_POST['mkr_metric_imperial_conversion'])) {
        update_post_meta($post_id, '_mkr_metric_imperial_conversion', sanitize_text_field($_POST['mkr_metric_imperial_conversion']));
    }
    
    // Alternativen speichern
    $alternatives = [];
    if (isset($_POST['mkr_alternative_name']) && is_array($_POST['mkr_alternative_name'])) {
        foreach ($_POST['mkr_alternative_name'] as $index => $name) {
            if (!empty($name)) {
                $alternatives[] = [
                    'name' => sanitize_text_field($name),
                    'ratio' => isset($_POST['mkr_alternative_ratio'][$index]) ? sanitize_text_field($_POST['mkr_alternative_ratio'][$index]) : '1:1'
                ];
            }
        }
    }
    update_post_meta($post_id, '_mkr_alternatives', $alternatives);
    
    // Saisonalität speichern
    $seasonal_months = isset($_POST['mkr_seasonal_months']) ? array_map('sanitize_text_field', $_POST['mkr_seasonal_months']) : [];
    update_post_meta($post_id, '_mkr_seasonal_months', $seasonal_months);
}