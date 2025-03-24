<?php
/**
 * Funktionen für das Benutzerprofil
 */

// Sicherheitsprüfung: Direkter Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Benutzerprofil-Felder zum Profilformular hinzufügen
 *
 * @param WP_User $user Benutzer-Objekt
 */
add_action('show_user_profile', 'mkr_add_user_fields');
add_action('edit_user_profile', 'mkr_add_user_fields');
function mkr_add_user_fields($user) {
    $diet = get_user_meta($user->ID, 'mkr_diet', true);
    $diets = get_terms(['taxonomy' => 'diet', 'hide_empty' => false]);
    
    $favorite_recipes = get_user_meta($user->ID, 'mkr_favorite_recipes', true);
    if (!is_array($favorite_recipes)) {
        $favorite_recipes = [];
    }
    
    $cooking_level = get_user_meta($user->ID, 'mkr_cooking_level', true);
    $excluded_ingredients = get_user_meta($user->ID, 'mkr_excluded_ingredients', true);
    $measurement_system = get_user_meta($user->ID, 'mkr_measurement_system', true) ?: 'metric';
    $color_scheme = get_user_meta($user->ID, 'mkr_color_scheme', true) ?: 'light';
    $font_size = get_user_meta($user->ID, 'mkr_font_size', true) ?: 'medium';
    
    ?>
    <h2><?php _e('Kochbuch-Einstellungen', 'mein-kochbuch-rezepte'); ?></h2>
    
    <table class="form-table">
        <tr>
            <th><label for="mkr_diet"><?php _e('Diätvorgaben', 'mein-kochbuch-rezepte'); ?></label></th>
            <td>
                <select name="mkr_diet" id="mkr_diet">
                    <option value=""><?php _e('Keine', 'mein-kochbuch-rezepte'); ?></option>
                    <?php foreach ($diets as $diet_term): ?>
                        <option value="<?php echo esc_attr($diet_term->slug); ?>" <?php selected($diet, $diet_term->slug); ?>>
                            <?php echo esc_html($diet_term->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Wähle eine Diät, die du bevorzugst. Dies wird für Rezeptempfehlungen verwendet.', 'mein-kochbuch-rezepte'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th><label for="mkr_cooking_level"><?php _e('Kochlevel', 'mein-kochbuch-rezepte'); ?></label></th>
            <td>
                <select name="mkr_cooking_level" id="mkr_cooking_level">
                    <option value="beginner" <?php selected($cooking_level, 'beginner'); ?>><?php _e('Anfänger', 'mein-kochbuch-rezepte'); ?></option>
                    <option value="intermediate" <?php selected($cooking_level, 'intermediate'); ?>><?php _e('Fortgeschritten', 'mein-kochbuch-rezepte'); ?></option>
                    <option value="expert" <?php selected($cooking_level, 'expert'); ?>><?php _e('Experte', 'mein-kochbuch-rezepte'); ?></option>
                </select>
                <p class="description"><?php _e('Dein Kochlevel beeinflusst die empfohlenen Rezepte.', 'mein-kochbuch-rezepte'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th><label for="mkr_excluded_ingredients"><?php _e('Zutaten ausschließen', 'mein-kochbuch-rezepte'); ?></label></th>
            <td>
                <textarea name="mkr_excluded_ingredients" id="mkr_excluded_ingredients" rows="3" class="large-text"><?php echo esc_textarea($excluded_ingredients); ?></textarea>
                <p class="description"><?php _e('Zutaten, die du vermeiden möchtest (durch Kommas getrennt).', 'mein-kochbuch-rezepte'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th><label><?php _e('Maßsystem', 'mein-kochbuch-rezepte'); ?></label></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><?php _e('Maßsystem', 'mein-kochbuch-rezepte'); ?></legend>
                    <label>
                        <input type="radio" name="mkr_measurement_system" value="metric" <?php checked($measurement_system, 'metric'); ?>>
                        <?php _e('Metrisch (g, ml)', 'mein-kochbuch-rezepte'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="mkr_measurement_system" value="imperial" <?php checked($measurement_system, 'imperial'); ?>>
                        <?php _e('Imperial (oz, cups)', 'mein-kochbuch-rezepte'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
    </table>
    
    <h3><?php _e('Darstellung', 'mein-kochbuch-rezepte'); ?></h3>
    
    <table class="form-table">
        <tr>
            <th><label><?php _e('Farbschema', 'mein-kochbuch-rezepte'); ?></label></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><?php _e('Farbschema', 'mein-kochbuch-rezepte'); ?></legend>
                    <label>
                        <input type="radio" name="mkr_color_scheme" value="light" <?php checked($color_scheme, 'light'); ?>>
                        <?php _e('Hell', 'mein-kochbuch-rezepte'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="mkr_color_scheme" value="dark" <?php checked($color_scheme, 'dark'); ?>>
                        <?php _e('Dunkel', 'mein-kochbuch-rezepte'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="mkr_color_scheme" value="high-contrast" <?php checked($color_scheme, 'high-contrast'); ?>>
                        <?php _e('Hoher Kontrast', 'mein-kochbuch-rezepte'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        
        <tr>
            <th><label><?php _e('Schriftgröße', 'mein-kochbuch-rezepte'); ?></label></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><?php _e('Schriftgröße', 'mein-kochbuch-rezepte'); ?></legend>
                    <label>
                        <input type="radio" name="mkr_font_size" value="small" <?php checked($font_size, 'small'); ?>>
                        <?php _e('Klein', 'mein-kochbuch-rezepte'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="mkr_font_size" value="medium" <?php checked($font_size, 'medium'); ?>>
                        <?php _e('Mittel', 'mein-kochbuch-rezepte'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="mkr_font_size" value="large" <?php checked($font_size, 'large'); ?>>
                        <?php _e('Groß', 'mein-kochbuch-rezepte'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="mkr_font_size" value="x-large" <?php checked($font_size, 'x-large'); ?>>
                        <?php _e('Sehr groß', 'mein-kochbuch-rezepte'); ?>
                    </label>
                </fieldset>
                <p class="description"><?php _e('Diese Einstellungen werden nur in "Mein Kochbuch" wirksam.', 'mein-kochbuch-rezepte'); ?></p>
            </td>
        </tr>
    </table>
    
    <h3><?php _e('Favorisierte Rezepte', 'mein-kochbuch-rezepte'); ?></h3>
    
    <?php if (!empty($favorite_recipes)): ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('Rezept', 'mein-kochbuch-rezepte'); ?></th>
                    <th scope="col"><?php _e('Hinzugefügt am', 'mein-kochbuch-rezepte'); ?></th>
                    <th scope="col"><?php _e('Aktionen', 'mein-kochbuch-rezepte'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($favorite_recipes as $recipe_id => $date_added): 
                    $recipe = get_post($recipe_id);
                    if (!$recipe) continue;
                ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_permalink($recipe_id); ?>" target="_blank">
                                <?php echo esc_html($recipe->post_title); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo date_i18n(get_option('date_format'), strtotime($date_added)); ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small mkr-remove-favorite" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
                                <?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <input type="hidden" name="mkr_removed_favorites" id="mkr_removed_favorites" value="">
        
        <script>
        jQuery(document).ready(function($) {
            const removedFavorites = [];
            
            $('.mkr-remove-favorite').on('click', function() {
                const recipeId = $(this).data('recipe-id');
                const row = $(this).closest('tr');
                
                row.fadeOut(300, function() {
                    $(this).remove();
                });
                
                removedFavorites.push(recipeId);
                $('#mkr_removed_favorites').val(removedFavorites.join(','));
            });
        });
        </script>
    <?php else: ?>
        <p><?php _e('Noch keine Rezepte favorisiert.', 'mein-kochbuch-rezepte'); ?></p>
    <?php endif; ?>
    <?php
}

/**
 * Speichern der Benutzerprofil-Felder
 *
 * @param int $user_id Benutzer-ID
 */
add_action('personal_options_update', 'mkr_save_user_fields');
add_action('edit_user_profile_update', 'mkr_save_user_fields');
function mkr_save_user_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }
    
    // Diätvorgaben speichern
    if (isset($_POST['mkr_diet'])) {
        update_user_meta($user_id, 'mkr_diet', sanitize_text_field($_POST['mkr_diet']));
    }
    
    // Kochlevel speichern
    if (isset($_POST['mkr_cooking_level'])) {
        update_user_meta($user_id, 'mkr_cooking_level', sanitize_text_field($_POST['mkr_cooking_level']));
    }
    
    // Ausgeschlossene Zutaten speichern
    if (isset($_POST['mkr_excluded_ingredients'])) {
        update_user_meta($user_id, 'mkr_excluded_ingredients', sanitize_textarea_field($_POST['mkr_excluded_ingredients']));
    }
    
    // Maßsystem speichern
    if (isset($_POST['mkr_measurement_system'])) {
        update_user_meta($user_id, 'mkr_measurement_system', sanitize_text_field($_POST['mkr_measurement_system']));
    }
    
    // Farbschema speichern
    if (isset($_POST['mkr_color_scheme'])) {
        update_user_meta($user_id, 'mkr_color_scheme', sanitize_text_field($_POST['mkr_color_scheme']));
    }
    
    // Schriftgröße speichern
    if (isset($_POST['mkr_font_size'])) {
        update_user_meta($user_id, 'mkr_font_size', sanitize_text_field($_POST['mkr_font_size']));
    }
    
    // Entfernte Favoriten verarbeiten
    if (isset($_POST['mkr_removed_favorites']) && !empty($_POST['mkr_removed_favorites'])) {
        $removed_ids = explode(',', sanitize_text_field($_POST['mkr_removed_favorites']));
        $favorite_recipes = get_user_meta($user_id, 'mkr_favorite_recipes', true);
        
        if (is_array($favorite_recipes)) {
            foreach ($removed_ids as $recipe_id) {
                unset($favorite_recipes[$recipe_id]);
            }
            
            update_user_meta($user_id, 'mkr_favorite_recipes', $favorite_recipes);
        }
    }
}

/**
 * AJAX-Handler zum Hinzufügen eines Rezepts zu den Favoriten
 */
function mkr_ajax_add_favorite_recipe() {
    // Sicherheitsprüfung
    if (!check_ajax_referer('mkr_favorite_action', 'nonce', false)) {
        wp_send_json_error(array('message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    // Anmeldestatus prüfen
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Sie müssen angemeldet sein, um Rezepte zu favorisieren.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    // Parameter validieren
    $recipe_id = isset($_POST['recipe_id']) ? intval($_POST['recipe_id']) : 0;
    
    if ($recipe_id <= 0 || get_post_type($recipe_id) !== 'recipe') {
        wp_send_json_error(array('message' => __('Ungültiges Rezept.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    $user_id = get_current_user_id();
    $favorite_recipes = get_user_meta($user_id, 'mkr_favorite_recipes', true);
    
    if (!is_array($favorite_recipes)) {
        $favorite_recipes = array();
    }
    
    // Prüfen, ob das Rezept bereits favorisiert ist
    if (isset($favorite_recipes[$recipe_id])) {
        wp_send_json_error(array('message' => __('Dieses Rezept ist bereits in Ihren Favoriten.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    // Rezept zu Favoriten hinzufügen
    $favorite_recipes[$recipe_id] = current_time('mysql');
    update_user_meta($user_id, 'mkr_favorite_recipes', $favorite_recipes);
    
    wp_send_json_success(array(
        'message' => __('Rezept wurde zu Ihren Favoriten hinzugefügt.', 'mein-kochbuch-rezepte'),
        'recipe_id' => $recipe_id
    ));
    
    wp_die();
}
add_action('wp_ajax_mkr_add_favorite_recipe', 'mkr_ajax_add_favorite_recipe');

/**
 * AJAX-Handler zum Entfernen eines Rezepts aus den Favoriten
 */
function mkr_ajax_remove_favorite_recipe() {
    // Sicherheitsprüfung
    if (!check_ajax_referer('mkr_favorite_action', 'nonce', false)) {
        wp_send_json_error(array('message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    // Anmeldestatus prüfen
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Sie müssen angemeldet sein, um Rezepte zu verwalten.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    // Parameter validieren
    $recipe_id = isset($_POST['recipe_id']) ? intval($_POST['recipe_id']) : 0;
    
    if ($recipe_id <= 0) {
        wp_send_json_error(array('message' => __('Ungültiges Rezept.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    $user_id = get_current_user_id();
    $favorite_recipes = get_user_meta($user_id, 'mkr_favorite_recipes', true);
    
    if (!is_array($favorite_recipes) || !isset($favorite_recipes[$recipe_id])) {
        wp_send_json_error(array('message' => __('Dieses Rezept ist nicht in Ihren Favoriten.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    // Rezept aus Favoriten entfernen
    unset($favorite_recipes[$recipe_id]);
    update_user_meta($user_id, 'mkr_favorite_recipes', $favorite_recipes);
    
    wp_send_json_success(array(
        'message' => __('Rezept wurde aus Ihren Favoriten entfernt.', 'mein-kochbuch-rezepte'),
        'recipe_id' => $recipe_id
    ));
    
    wp_die();
}
add_action('wp_ajax_mkr_remove_favorite_recipe', 'mkr_ajax_remove_favorite_recipe');

/**
 * Benutzereigene Einstellungen für das Frontend anwenden
 */
function mkr_apply_user_settings() {
    // Nur für angemeldete Benutzer und auf Frontend-Seiten
    if (!is_user_logged_in() || is_admin()) {
        return;
    }
    
    $user_id = get_current_user_id();
    $color_scheme = get_user_meta($user_id, 'mkr_color_scheme', true);
    $font_size = get_user_meta($user_id, 'mkr_font_size', true);
    
    // CSS-Klassen zum Body hinzufügen
    add_filter('body_class', function($classes) use ($color_scheme, $font_size) {
        if ($color_scheme) {
            $classes[] = $color_scheme;
        }
        
        if ($font_size) {
            $classes[] = $font_size;
        }
        
        return $classes;
    });
    
    // JavaScript-Variablen für die Einstellungen
    add_action('wp_footer', function() use ($user_id) {
        $measurement_system = get_user_meta($user_id, 'mkr_measurement_system', true) ?: 'metric';
        
        // Für die Portionsrechner-Einstellungen
        ?>
        <script>
        // Benutzerspezifische Einstellungen
        window.mkrUserSettings = {
            measurementSystem: '<?php echo esc_js($measurement_system); ?>'
        };
        </script>
        <?php
    }, 100);
}
add_action('wp', 'mkr_apply_user_settings');

/**
 * Shortcode zur Anzeige von favorisierten Rezepten
 */
function mkr_favorite_recipes_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>' . __('Sie müssen angemeldet sein, um Ihre favorisierten Rezepte zu sehen.', 'mein-kochbuch-rezepte') . '</p>';
    }
    
    $atts = shortcode_atts(array(
        'limit' => 10,
        'columns' => 3,
    ), $atts, 'mkr_favorite_recipes');
    
    $limit = intval($atts['limit']);
    $columns = intval($atts['columns']);
    
    $user_id = get_current_user_id();
    $favorite_recipes = get_user_meta($user_id, 'mkr_favorite_recipes', true);
    
    if (!is_array($favorite_recipes) || empty($favorite_recipes)) {
        return '<p>' . __('Sie haben noch keine Rezepte favorisiert.', 'mein-kochbuch-rezepte') . '</p>';
    }
    
    // Nach Hinzufügedatum sortieren (neueste zuerst)
    arsort($favorite_recipes);
    
    // Auf Limit beschränken
    if ($limit > 0) {
        $favorite_recipes = array_slice($favorite_recipes, 0, $limit, true);
    }
    
    $recipe_ids = array_keys($favorite_recipes);
    
    $args = array(
        'post_type' => 'recipe',
        'post__in' => $recipe_ids,
        'posts_per_page' => -1,
        'orderby' => 'post__in', // Reihenfolge aus $recipe_ids beibehalten
    );
    
    $recipes = new WP_Query($args);
    
    if (!$recipes->have_posts()) {
        return '<p>' . __('Keine Rezepte gefunden.', 'mein-kochbuch-rezepte') . '</p>';
    }
    
    ob_start();
    ?>
    <div class="mkr-favorite-recipes">
        <div class="mkr-recipe-grid" style="grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);">
            <?php while ($recipes->have_posts()): $recipes->the_post(); ?>
                <div class="mkr-recipe-card">
                    <a href="<?php the_permalink(); ?>" class="mkr-recipe-card-link">
                        <?php if (has_post_thumbnail()): ?>
                            <div class="mkr-recipe-card-image">
                                <?php the_post_thumbnail('medium', array('class' => 'mkr-recipe-thumbnail')); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mkr-recipe-card-content">
                            <h3 class="mkr-recipe-title"><?php the_title(); ?></h3>
                            
                            <?php
                            // Meta-Informationen
                            $prep_time = get_post_meta(get_the_ID(), '_mkr_prep_time', true);
                            $cook_time = get_post_meta(get_the_ID(), '_mkr_cook_time', true);
                            
                            if ($prep_time || $cook_time): ?>
                                <div class="mkr-recipe-meta">
                                    <?php if ($prep_time): ?>
                                        <span class="mkr-prep-time">
                                            <span class="mkr-icon">⏲️</span> <?php echo esc_html($prep_time); ?> <?php _e('Min.', 'mein-kochbuch-rezepte'); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($cook_time): ?>
                                        <span class="mkr-cook-time">
                                            <span class="mkr-icon">🍳</span> <?php echo esc_html($cook_time); ?> <?php _e('Min.', 'mein-kochbuch-rezepte'); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    
                    <button type="button" class="mkr-remove-from-favorites" data-recipe-id="<?php the_ID(); ?>" aria-label="<?php esc_attr_e('Aus Favoriten entfernen', 'mein-kochbuch-rezepte'); ?>">
                        <span class="mkr-icon">❌</span>
                    </button>
                </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('.mkr-remove-from-favorites').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const recipeId = button.data('recipe-id');
            const recipeCard = button.closest('.mkr-recipe-card');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'mkr_remove_favorite_recipe',
                    nonce: '<?php echo wp_create_nonce('mkr_favorite_action'); ?>',
                    recipe_id: recipeId
                },
                beforeSend: function() {
                    button.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        recipeCard.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Prüfen, ob noch Rezepte übrig sind
                            if ($('.mkr-recipe-card').length === 0) {
                                $('.mkr-favorite-recipes').html('<p><?php _e('Sie haben noch keine Rezepte favorisiert.', 'mein-kochbuch-rezepte'); ?></p>');
                            }
                        });
                    } else {
                        alert(response.data.message);
                        button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('<?php _e('Fehler beim Entfernen des Rezepts aus den Favoriten.', 'mein-kochbuch-rezepte'); ?>');
                    button.prop('disabled', false);
                }
            });
        });
    });
    </script>
    
    <style>
    .mkr-favorite-recipes {
        margin-bottom: 30px;
    }
    
    .mkr-recipe-grid {
        display: grid;
        grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);
        gap: 20px;
    }
    
    .mkr-recipe-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .mkr-recipe-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .mkr-recipe-card-link {
        display: block;
        text-decoration: none;
        color: inherit;
    }
    
    .mkr-recipe-card-image {
        height: 180px;
        overflow: hidden;
    }
    
    .mkr-recipe-thumbnail {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease-in-out;
    }
    
    .mkr-recipe-card:hover .mkr-recipe-thumbnail {
        transform: scale(1.05);
    }
    
    .mkr-recipe-card-content {
        padding: 15px;
    }
    
    .mkr-recipe-title {
        margin: 0 0 10px 0;
        font-size: 1.1rem;
    }
    
    .mkr-recipe-meta {
        display: flex;
        gap: 15px;
        font-size: 0.9rem;
        color: #666;
    }
    
    .mkr-remove-from-favorites {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(255, 255, 255, 0.8);
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s ease-in-out;
    }
    
    .mkr-recipe-card:hover .mkr-remove-from-favorites {
        opacity: 1;
    }
    
    @media (max-width: 768px) {
        .mkr-recipe-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .mkr-remove-from-favorites {
            opacity: 1;
        }
    }
    
    @media (max-width: 480px) {
        .mkr-recipe-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('mkr_favorite_recipes', 'mkr_favorite_recipes_shortcode');

/**
 * Favoriten-Button zu Rezepten hinzufügen
 */
function mkr_add_favorite_button() {
    if (!is_singular('recipe')) {
        return;
    }
    
    $recipe_id = get_the_ID();
    $is_favorite = false;
    
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $favorite_recipes = get_user_meta($user_id, 'mkr_favorite_recipes', true);
        
        if (is_array($favorite_recipes) && isset($favorite_recipes[$recipe_id])) {
            $is_favorite = true;
        }
    }
    
    ?>
    <div class="mkr-favorite-button-container">
        <?php if (is_user_logged_in()): ?>
            <button type="button" id="mkr-toggle-favorite" class="mkr-favorite-button <?php echo $is_favorite ? 'mkr-is-favorite' : ''; ?>" data-recipe-id="<?php echo esc_attr($recipe_id); ?>">
                <span class="mkr-favorite-icon"><?php echo $is_favorite ? '❤️' : '🤍'; ?></span>
                <span class="mkr-favorite-text">
                    <?php echo $is_favorite ? __('Favorisiert', 'mein-kochbuch-rezepte') : __('Favorisieren', 'mein-kochbuch-rezepte'); ?>
                </span>
            </button>
        <?php else: ?>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="mkr-favorite-button">
                <span class="mkr-favorite-icon">🤍</span>
                <span class="mkr-favorite-text"><?php _e('Anmelden zum Favorisieren', 'mein-kochbuch-rezepte'); ?></span>
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (is_user_logged_in()): ?>
    <script>
    jQuery(document).ready(function($) {
        $('#mkr-toggle-favorite').on('click', function() {
            const button = $(this);
            const recipeId = button.data('recipe-id');
            const isFavorite = button.hasClass('mkr-is-favorite');
            
            // AJAX-Anfrage zum Hinzufügen/Entfernen
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: isFavorite ? 'mkr_remove_favorite_recipe' : 'mkr_add_favorite_recipe',
                    nonce: '<?php echo wp_create_nonce('mkr_favorite_action'); ?>',
                    recipe_id: recipeId
                },
                beforeSend: function() {
                    button.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        // UI aktualisieren
                        if (isFavorite) {
                            button.removeClass('mkr-is-favorite');
                            button.find('.mkr-favorite-icon').text('🤍');
                            button.find('.mkr-favorite-text').text('<?php esc_js(_e('Favorisieren', 'mein-kochbuch-rezepte')); ?>');
                        } else {
                            button.addClass('mkr-is-favorite');
                            button.find('.mkr-favorite-icon').text('❤️');
                            button.find('.mkr-favorite-text').text('<?php esc_js(_e('Favorisiert', 'mein-kochbuch-rezepte')); ?>');
                        }
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e('Fehler beim Verarbeiten der Anfrage.', 'mein-kochbuch-rezepte'); ?>');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php endif; ?>
    
    <style>
    .mkr-favorite-button-container {
        margin: 20px 0;
        text-align: center;
    }
    
    .mkr-favorite-button {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        background-color: #f8f8f8;
        border: 1px solid #ddd;
        border-radius: 30px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s ease-in-out;
        text-decoration: none;
        color: inherit;
    }
    
    .mkr-favorite-button:hover {
        background-color: #f0f0f0;
        color: inherit;
    }
    
    .mkr-favorite-button.mkr-is-favorite {
        background-color: #ffebee;
        border-color: #ffcdd2;
    }
    
    .mkr-favorite-icon {
        font-size: 18px;
        margin-right: 8px;
    }
    
    @media (max-width: 768px) {
        .mkr-favorite-button {
            width: 100%;
            justify-content: center;
        }
    }
    </style>
    <?php
}
add_action('mkr_after_recipe_title', 'mkr_add_favorite_button');