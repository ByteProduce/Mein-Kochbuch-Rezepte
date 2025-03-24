<?php
/**
 * Metaboxen für Rezepte
 */

// Sicherheitsprüfung: Direkter Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fügt die Metaboxen zum Rezept-Editor hinzu
 */
add_action('add_meta_boxes', 'mkr_add_recipe_meta_boxes');
function mkr_add_recipe_meta_boxes() {
    add_meta_box(
        'mkr_recipe_ingredients',
        __('Zutaten', 'mein-kochbuch-rezepte'),
        'mkr_recipe_ingredients_callback',
        'recipe',
        'normal',
        'high'
    );
    
    add_meta_box(
        'mkr_recipe_utensils',
        __('Kochutensilien', 'mein-kochbuch-rezepte'),
        'mkr_recipe_utensils_callback',
        'recipe',
        'normal',
        'high'
    );
    
    add_meta_box(
        'mkr_recipe_instructions',
        __('Zubereitung', 'mein-kochbuch-rezepte'),
        'mkr_recipe_instructions_callback',
        'recipe',
        'normal',
        'high'
    );
    
    add_meta_box(
        'mkr_recipe_videos',
        __('Schritt-für-Schritt Videos', 'mein-kochbuch-rezepte'),
        'mkr_recipe_videos_callback',
        'recipe',
        'normal',
        'high'
    );
    
    add_meta_box(
        'mkr_recipe_details',
        __('Rezeptdetails', 'mein-kochbuch-rezepte'),
        'mkr_recipe_details_callback',
        'recipe',
        'normal',
        'high'
    );
}

/**
 * Callback für die Zutaten Metabox
 */
function mkr_recipe_ingredients_callback($post) {
    wp_nonce_field('mkr_recipe_save_data', 'mkr_recipe_meta_nonce');
    
    $ingredients = get_post_meta($post->ID, '_mkr_ingredients', true);
    $ingredients = is_array($ingredients) ? $ingredients : [];
    
    ?>
    <div class="mkr-ingredients-container">
        <div class="mkr-ingredients-list">
            <?php if (!empty($ingredients)): ?>
                <?php foreach ($ingredients as $index => $ingredient): ?>
                    <div class="mkr-ingredient-group">
                        <input type="number" name="mkr_ingredient_amount[]" value="<?php echo esc_attr($ingredient['amount']); ?>" placeholder="<?php esc_attr_e('Menge', 'mein-kochbuch-rezepte'); ?>" min="0" step="0.1" aria-label="<?php esc_attr_e('Zutatenmenge', 'mein-kochbuch-rezepte'); ?>" />
                        
                        <select name="mkr_ingredient_unit[]" aria-label="<?php esc_attr_e('Maßeinheit', 'mein-kochbuch-rezepte'); ?>">
                            <?php foreach (['g', 'kg', 'ml', 'l', 'TL', 'EL', 'Stk', 'Prise', 'Scheibe', 'Blatt'] as $unit): ?>
                                <option value="<?php echo esc_attr($unit); ?>" <?php selected($unit, $ingredient['unit']); ?>><?php echo esc_html($unit); ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div class="mkr-ingredient-name-container">
                            <input type="text" name="mkr_ingredient_name[]" value="<?php echo esc_attr($ingredient['name']); ?>" placeholder="<?php esc_attr_e('Zutatenname', 'mein-kochbuch-rezepte'); ?>" class="mkr-ingredient-name" data-autocomplete-type="ingredient" aria-label="<?php esc_attr_e('Zutatenname', 'mein-kochbuch-rezepte'); ?>" />
                        </div>
                        
                        <button type="button" class="button mkr-remove-ingredient" aria-label="<?php esc_attr_e('Zutat entfernen', 'mein-kochbuch-rezepte'); ?>"><?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" id="mkr-add-ingredient" class="button button-primary" aria-label="<?php esc_attr_e('Zutat hinzufügen', 'mein-kochbuch-rezepte'); ?>"><?php _e('Zutat hinzufügen', 'mein-kochbuch-rezepte'); ?></button>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#mkr-add-ingredient').click(function() {
            const group = $('<div class="mkr-ingredient-group">');
            group.html(`
                <input type="number" name="mkr_ingredient_amount[]" placeholder="<?php esc_attr_e('Menge', 'mein-kochbuch-rezepte'); ?>" min="0" step="0.1" aria-label="<?php esc_attr_e('Zutatenmenge', 'mein-kochbuch-rezepte'); ?>" />
                
                <select name="mkr_ingredient_unit[]" aria-label="<?php esc_attr_e('Maßeinheit', 'mein-kochbuch-rezepte'); ?>">
                    <?php foreach (['g', 'kg', 'ml', 'l', 'TL', 'EL', 'Stk', 'Prise', 'Scheibe', 'Blatt'] as $unit): ?>
                        <option value="<?php echo esc_attr($unit); ?>"><?php echo esc_html($unit); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <div class="mkr-ingredient-name-container">
                    <input type="text" name="mkr_ingredient_name[]" placeholder="<?php esc_attr_e('Zutatenname', 'mein-kochbuch-rezepte'); ?>" class="mkr-ingredient-name" data-autocomplete-type="ingredient" aria-label="<?php esc_attr_e('Zutatenname', 'mein-kochbuch-rezepte'); ?>" />
                </div>
                
                <button type="button" class="button mkr-remove-ingredient" aria-label="<?php esc_attr_e('Zutat entfernen', 'mein-kochbuch-rezepte'); ?>"><?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?></button>
            `);
            
            group.appendTo('.mkr-ingredients-list');
            
            if (typeof window.setupInlineAutocomplete === 'function') {
                window.setupInlineAutocomplete(group.find('.mkr-ingredient-name'), 'ingredient');
            }
        });
        
        $(document).on('click', '.mkr-remove-ingredient', function() {
            $(this).closest('.mkr-ingredient-group').remove();
        });
    });
    </script>
    <?php
}

/**
 * Callback für die Utensilien Metabox
 */
function mkr_recipe_utensils_callback($post) {
    wp_nonce_field('mkr_recipe_save_data', 'mkr_recipe_meta_nonce');
    
    $utensils = get_post_meta($post->ID, '_mkr_utensils', true);
    $utensils = is_array($utensils) ? $utensils : [];
    
    ?>
    <div class="mkr-utensils-container">
        <div class="mkr-utensils-list">
            <?php if (!empty($utensils)): ?>
                <?php foreach ($utensils as $index => $utensil): ?>
                    <div class="mkr-utensil-group">
                        <input type="text" name="mkr_utensil_name[]" value="<?php echo esc_attr($utensil); ?>" placeholder="<?php esc_attr_e('Utensil', 'mein-kochbuch-rezepte'); ?>" class="mkr-utensil-name" data-autocomplete-type="utensil" aria-label="<?php esc_attr_e('Utensil', 'mein-kochbuch-rezepte'); ?>" />
                        <button type="button" class="button mkr-remove-utensil" aria-label="<?php esc_attr_e('Utensil entfernen', 'mein-kochbuch-rezepte'); ?>"><?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" id="mkr-add-utensil" class="button button-primary" aria-label="<?php esc_attr_e('Utensil hinzufügen', 'mein-kochbuch-rezepte'); ?>"><?php _e('Utensil hinzufügen', 'mein-kochbuch-rezepte'); ?></button>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#mkr-add-utensil').click(function() {
            const group = $('<div class="mkr-utensil-group">');
            group.html(`
                <input type="text" name="mkr_utensil_name[]" placeholder="<?php esc_attr_e('Utensil', 'mein-kochbuch-rezepte'); ?>" class="mkr-utensil-name" data-autocomplete-type="utensil" aria-label="<?php esc_attr_e('Utensil', 'mein-kochbuch-rezepte'); ?>" />
                <button type="button" class="button mkr-remove-utensil" aria-label="<?php esc_attr_e('Utensil entfernen', 'mein-kochbuch-rezepte'); ?>"><?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?></button>
            `);
            
            group.appendTo('.mkr-utensils-list');
            
            if (typeof window.setupInlineAutocomplete === 'function') {
                window.setupInlineAutocomplete(group.find('.mkr-utensil-name'), 'utensil');
            }
        });
        
        $(document).on('click', '.mkr-remove-utensil', function() {
            $(this).closest('.mkr-utensil-group').remove();
        });
    });
    </script>
    <?php
}

/**
 * Callback für die Zubereitungsanleitung Metabox
 */
function mkr_recipe_instructions_callback($post) {
    $content = wp_kses_post(get_post_meta($post->ID, '_mkr_instructions', true));
    $editor_id = 'mkr_instructions_editor';
    $settings = [
        'textarea_name' => 'mkr_instructions',
        'media_buttons' => true,
        'teeny' => false,
        'textarea_rows' => 15,
        'editor_css' => '',
        'editor_class' => 'mkr-instructions-editor',
        'tinymce' => [
            'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo',
            'toolbar2' => '',
        ],
    ];
    
    ?>
    <p class="description"><?php _e('Geben Sie hier die Zubereitungsschritte ein. Jeder Absatz oder Listenpunkt wird als separater Schritt behandelt.', 'mein-kochbuch-rezepte'); ?></p>
    <?php
    
    wp_editor($content, $editor_id, $settings);
}

/**
 * Callback für die Videos Metabox
 */
function mkr_recipe_videos_callback($post) {
    wp_nonce_field('mkr_recipe_save_data', 'mkr_recipe_meta_nonce');
    
    $videos = get_post_meta($post->ID, '_mkr_videos', true);
    $videos = is_array($videos) ? $videos : [];
    
    ?>
    <div class="mkr-videos-container">
        <div class="mkr-videos-list">
            <?php if (empty($videos)): ?>
                <div class="mkr-video-group">
                    <input type="url" name="mkr_video_url[]" value="" placeholder="<?php esc_attr_e('YouTube URL (z.B. https://www.youtube.com/watch?v=xyz)', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-input" aria-label="<?php esc_attr_e('Video-URL', 'mein-kochbuch-rezepte'); ?>" />
                    <img class="mkr-video-thumbnail" src="" alt="<?php esc_attr_e('Video-Vorschaubild', 'mein-kochbuch-rezepte'); ?>" style="max-width: 120px; display: none;" />
                    <button type="button" class="button mkr-remove-video" aria-label="<?php esc_attr_e('Video entfernen', 'mein-kochbuch-rezepte'); ?>"><?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?></button>
                </div>
            <?php else: ?>
                <?php foreach ($videos as $index => $video): ?>
                    <?php $thumbnail = mkr_get_youtube_thumbnail($video); ?>
                    <div class="mkr-video-group">
                        <input type="url" name="mkr_video_url[]" value="<?php echo esc_attr($video); ?>" placeholder="<?php esc_attr_e('YouTube URL (z.B. https://www.youtube.com/watch?v=xyz)', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-input" aria-label="<?php esc_attr_e('Video-URL', 'mein-kochbuch-rezepte'); ?>" />
                        <img class="mkr-video-thumbnail" src="<?php echo esc_url($thumbnail); ?>" alt="<?php esc_attr_e('Video-Vorschaubild', 'mein-kochbuch-rezepte'); ?>" style="max-width: 120px; <?php echo $thumbnail ? '' : 'display: none;'; ?>" />
                        <button type="button" class="button mkr-remove-video" aria-label="<?php esc_attr_e('Video entfernen', 'mein-kochbuch-rezepte'); ?>"><?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" id="mkr-add-video" class="button button-primary" aria-label="<?php esc_attr_e('Video hinzufügen', 'mein-kochbuch-rezepte'); ?>"><?php _e('Video hinzufügen', 'mein-kochbuch-rezepte'); ?></button>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        function updateThumbnail(input) {
            const url = input.val();
            const thumbnail = $('.mkr-video-thumbnail', input.parent());
            
            if (url.match(/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/)) {
                const match = url.match(/(?:v=|\.be\/)([a-zA-Z0-9_-]{11})/);
                const videoId = match ? match[1] : null;
                
                if (videoId) {
                    thumbnail.attr('src', 'https://img.youtube.com/vi/' + videoId + '/default.jpg').show();
                } else {
                    thumbnail.hide();
                }
            } else {
                thumbnail.hide();
            }
        }

        $('#mkr-add-video').click(function() {
            const group = $('<div class="mkr-video-group">');
            group.html(`
                <input type="url" name="mkr_video_url[]" placeholder="<?php esc_attr_e('YouTube URL (z.B. https://www.youtube.com/watch?v=xyz)', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-input" aria-label="<?php esc_attr_e('Video-URL', 'mein-kochbuch-rezepte'); ?>" />
                <img class="mkr-video-thumbnail" src="" alt="<?php esc_attr_e('Video-Vorschaubild', 'mein-kochbuch-rezepte'); ?>" style="max-width: 120px; display: none;" />
                <button type="button" class="button mkr-remove-video" aria-label="<?php esc_attr_e('Video entfernen', 'mein-kochbuch-rezepte'); ?>"><?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?></button>
            `);
            
            group.appendTo('.mkr-videos-list');
            group.find('.mkr-video-input').on('input', function() { 
                updateThumbnail($(this)); 
            });
        });

        $(document).on('click', '.mkr-remove-video', function() {
            $(this).closest('.mkr-video-group').remove();
        });

        $('.mkr-video-input').on('input', function() { 
            updateThumbnail($(this)); 
        });
    });
    </script>
    <?php
}

/**
 * Callback für die Rezeptdetails Metabox
 */
function mkr_recipe_details_callback($post) {
    $servings = get_post_meta($post->ID, '_mkr_servings', true);
    $prep_time = get_post_meta($post->ID, '_mkr_prep_time', true);
    $cook_time = get_post_meta($post->ID, '_mkr_cook_time', true);
    $total_time = get_post_meta($post->ID, '_mkr_total_time', true);
    
    ?>
    <p>
        <label for="mkr_servings"><strong><?php _e('Portionen:', 'mein-kochbuch-rezepte'); ?></strong></label><br>
        <input type="number" id="mkr_servings" name="mkr_servings" value="<?php echo esc_attr($servings); ?>" min="1" aria-describedby="mkr_servings_desc" />
        <p id="mkr_servings_desc" class="description"><?php _e('Anzahl der Portionen, die dieses Rezept ergibt.', 'mein-kochbuch-rezepte'); ?></p>
    </p>
    
    <p>
        <label for="mkr_prep_time"><strong><?php _e('Vorbereitungszeit (Minuten):', 'mein-kochbuch-rezepte'); ?></strong></label><br>
        <input type="number" id="mkr_prep_time" name="mkr_prep_time" value="<?php echo esc_attr($prep_time); ?>" min="0" aria-describedby="mkr_prep_time_desc" />
        <p id="mkr_prep_time_desc" class="description"><?php _e('Zeit in Minuten, die für die Vorbereitung benötigt wird.', 'mein-kochbuch-rezepte'); ?></p>
    </p>
    
    <p>
        <label for="mkr_cook_time"><strong><?php _e('Kochzeit (Minuten):', 'mein-kochbuch-rezepte'); ?></strong></label><br>
        <input type="number" id="mkr_cook_time" name="mkr_cook_time" value="<?php echo esc_attr($cook_time); ?>" min="0" aria-describedby="mkr_cook_time_desc" />
        <p id="mkr_cook_time_desc" class="description"><?php _e('Zeit in Minuten, die für das Kochen/Backen benötigt wird.', 'mein-kochbuch-rezepte'); ?></p>
    </p>
    
    <p>
        <label for="mkr_total_time"><strong><?php _e('Gesamtzeit (Minuten):', 'mein-kochbuch-rezepte'); ?></strong></label><br>
        <input type="number" id="mkr_total_time" name="mkr_total_time" value="<?php echo esc_attr($total_time); ?>" min="0" aria-describedby="mkr_total_time_desc" />
        <p id="mkr_total_time_desc" class="description"><?php _e('Gesamtzeit in Minuten (einschließlich Ruhezeit, falls vorhanden).', 'mein-kochbuch-rezepte'); ?></p>
    </p>
    
    <script>
    jQuery(document).ready(function($) {
        // Auto-Berechnung der Gesamtzeit
        $('#mkr_prep_time, #mkr_cook_time').on('change', function() {
            const prepTime = parseInt($('#mkr_prep_time').val()) || 0;
            const cookTime = parseInt($('#mkr_cook_time').val()) || 0;
            
            if (prepTime > 0 || cookTime > 0) {
                $('#mkr_total_time').val(prepTime + cookTime);
            }
        });
    });
    </script>
    <?php
}

/**
 * Speichert die Rezept-Metadaten beim Speichern eines Rezepts
 */
add_action('save_post_recipe', 'mkr_save_recipe_meta');
function mkr_save_recipe_meta($post_id) {
    // Nonce-Überprüfung
    if (!isset($_POST['mkr_recipe_meta_nonce']) || !wp_verify_nonce($_POST['mkr_recipe_meta_nonce'], 'mkr_recipe_save_data')) {
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
    
    // Zutaten speichern
    $ingredients = [];
    if (isset($_POST['mkr_ingredient_amount']) && is_array($_POST['mkr_ingredient_amount'])) {
        foreach ($_POST['mkr_ingredient_amount'] as $index => $amount) {
            if (!empty($_POST['mkr_ingredient_name'][$index])) {
                $ingredients[] = [
                    'amount' => sanitize_text_field($amount),
                    'unit' => sanitize_text_field($_POST['mkr_ingredient_unit'][$index]),
                    'name' => sanitize_text_field($_POST['mkr_ingredient_name'][$index]),
                ];
            }
        }
    }
    update_post_meta($post_id, '_mkr_ingredients', $ingredients);
    
    // Utensilien speichern
    $utensils = [];
    if (isset($_POST['mkr_utensil_name']) && is_array($_POST['mkr_utensil_name'])) {
        foreach ($_POST['mkr_utensil_name'] as $utensil) {
            if (!empty($utensil)) {
                $utensils[] = sanitize_text_field($utensil);
            }
        }
    }
    update_post_meta($post_id, '_mkr_utensils', $utensils);
    
    // Anleitung speichern
    if (isset($_POST['mkr_instructions'])) {
        update_post_meta($post_id, '_mkr_instructions', wp_kses_post($_POST['mkr_instructions']));
    }
    
    // Videos speichern
    if (isset($_POST['mkr_video_url'])) {
        $videos = array_filter(array_map('esc_url_raw', $_POST['mkr_video_url']));
        update_post_meta($post_id, '_mkr_videos', $videos);
    }
    
    // Rezeptdetails speichern
    if (isset($_POST['mkr_servings'])) {
        update_post_meta($post_id, '_mkr_servings', intval($_POST['mkr_servings']));
    }
    
    if (isset($_POST['mkr_prep_time'])) {
        update_post_meta($post_id, '_mkr_prep_time', intval($_POST['mkr_prep_time']));
    }
    
    if (isset($_POST['mkr_cook_time'])) {
        update_post_meta($post_id, '_mkr_cook_time', intval($_POST['mkr_cook_time']));
    }
    
    if (isset($_POST['mkr_total_time'])) {
        update_post_meta($post_id, '_mkr_total_time', intval($_POST['mkr_total_time']));
    }
    
    // Kalorien für das Rezept berechnen und speichern
    mkr_calculate_recipe_calories($post_id);
}

/**
 * Berechnet die Kalorien für ein Rezept basierend auf den Zutaten
 *
 * @param int $post_id Rezept-ID
 */
function mkr_calculate_recipe_calories($post_id) {
    $ingredients = get_post_meta($post_id, '_mkr_ingredients', true);
    if (!is_array($ingredients) || empty($ingredients)) {
        return;
    }
    
    $total_calories = 0;
    $total_proteins = 0;
    $total_fats = 0;
    $total_carbs = 0;
    
    foreach ($ingredients as $ingredient) {
        $amount = floatval($ingredient['amount']);
        $unit = $ingredient['unit'];
        $name = $ingredient['name'];
        
        // Suche nach der Zutat in der Datenbank
        $args = [
            'post_type' => 'ingredient',
            'post_status' => 'publish',
            'title' => $name,
            'posts_per_page' => 1
        ];
        
        $ingredient_query = new WP_Query($args);
        
        if ($ingredient_query->have_posts()) {
            $ingredient_post = $ingredient_query->posts[0];
            $calories_per_100g = floatval(get_post_meta($ingredient_post->ID, '_mkr_calories_per_100g', true));
            $proteins_per_100g = floatval(get_post_meta($ingredient_post->ID, '_mkr_proteins_per_100g', true));
            $fats_per_100g = floatval(get_post_meta($ingredient_post->ID, '_mkr_fats_per_100g', true));
            $carbs_per_100g = floatval(get_post_meta($ingredient_post->ID, '_mkr_carbs_per_100g', true));
            
            // Konvertiere Menge in Gramm
            $grams = 0;
            
            if ($unit === 'kg') {
                $grams = $amount * 1000;
            } elseif ($unit === 'g') {
                $grams = $amount;
            } elseif ($unit === 'ml' || $unit === 'l') {
                // Für Flüssigkeiten: ml/l zu g konvertieren (näherungsweise 1:1 für Wasser)
                $grams = ($unit === 'l') ? $amount * 1000 : $amount;
            } elseif ($unit === 'TL') {
                // Ein Teelöffel entspricht etwa 5g
                $grams = $amount * 5;
            } elseif ($unit === 'EL') {
                // Ein Esslöffel entspricht etwa 15g
                $grams = $amount * 15;
            } elseif ($unit === 'Stk' || $unit === 'Scheibe' || $unit === 'Blatt' || $unit === 'Prise') {
                // Hier wäre eine Lookup-Tabelle für standardisierte Gewichte nützlich
                // Vereinfachte Annahme:
                $weight_per_unit = floatval(get_post_meta($ingredient_post->ID, '_mkr_weight_per_unit', true));
                $grams = $weight_per_unit > 0 ? $amount * $weight_per_unit : 0;
            }
            
            // Kalorien und Nährwerte berechnen
            if ($grams > 0) {
                $total_calories += ($calories_per_100g * $grams) / 100;
                $total_proteins += ($proteins_per_100g * $grams) / 100;
                $total_fats += ($fats_per_100g * $grams) / 100;
                $total_carbs += ($carbs_per_100g * $grams) / 100;
            }
        }
        
        wp_reset_postdata();
    }
    
    // Auf ganze Zahlen runden und speichern
    update_post_meta($post_id, '_mkr_total_calories', round($total_calories));
    update_post_meta($post_id, '_mkr_total_proteins', round($total_proteins, 1));
    update_post_meta($post_id, '_mkr_total_fats', round($total_fats, 1));
    update_post_meta($post_id, '_mkr_total_carbs', round($total_carbs, 1));
}

/**
 * Extrahiert die YouTube-Vorschaubildurl aus einer YouTube-URL
 *
 * @param string $url YouTube-URL
 * @return string URL des Vorschaubilds oder leerer String
 */
function mkr_get_youtube_thumbnail($url) {
    if (preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/', $url)) {
        $video_id = preg_match('/(?:v=|\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches) ? $matches[1] : '';
        return $video_id ? "https://img.youtube.com/vi/{$video_id}/default.jpg" : '';
    }
    
    return '';
}