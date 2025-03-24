<?php
/**
 * Funktionen für die Frontend-Anzeige von Rezepten
 */

// Sicherheitsprüfung: Direkter Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zeigt ein Rezept im Frontend an
 *
 * @param int $post_id Die ID des Rezept-Beitrags
 * @return string HTML-Ausgabe des Rezepts
 */
function mkr_display_recipe($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'recipe') {
        return '';
    }

    ob_start();

    // Metadaten abrufen
    $ingredients = get_post_meta($post_id, '_mkr_ingredients', true);
    $ingredients = maybe_unserialize($ingredients);
    $utensils = get_post_meta($post_id, '_mkr_utensils', true);
    $utensils = maybe_unserialize($utensils);
    $instructions = get_post_meta($post_id, '_mkr_instructions', true);
    $videos = get_post_meta($post_id, '_mkr_videos', true);
    $total_calories = get_post_meta($post_id, '_mkr_total_calories', true);
    $total_proteins = get_post_meta($post_id, '_mkr_total_proteins', true);
    $total_fats = get_post_meta($post_id, '_mkr_total_fats', true);
    $total_carbs = get_post_meta($post_id, '_mkr_total_carbs', true);
    $servings = get_post_meta($post_id, '_mkr_servings', true) ?: 4;
    $prep_time = get_post_meta($post_id, '_mkr_prep_time', true);
    $cook_time = get_post_meta($post_id, '_mkr_cook_time', true);
    $total_time = get_post_meta($post_id, '_mkr_total_time', true);

    // Inhaltstext mit verlinkten Begriffen
    $content = mkr_link_glossary_terms($post->post_content);

    ?>
    <div id="recipe-container" class="mkr-recipe-container">
        <!-- Rezept-Titel -->
        <h1 class="mkr-recipe-title"><?php echo esc_html($post->post_title); ?></h1>
        
        <!-- Einstellungsbutton -->
        <button id="settings-toggle" class="mkr-settings-toggle" aria-label="<?php esc_attr_e('Einstellungen öffnen', 'mein-kochbuch-rezepte'); ?>">
            <span class="mkr-settings-icon"><?php _e('⚙️', 'mein-kochbuch-rezepte'); ?></span>
            <span class="mkr-settings-text"><?php _e('Einstellungen', 'mein-kochbuch-rezepte'); ?></span>
        </button>
        
        <!-- Hauptbild des Rezepts -->
        <?php if (has_post_thumbnail($post_id)): ?>
            <div class="mkr-recipe-featured-image">
                <?php echo get_the_post_thumbnail($post_id, 'large', ['class' => 'mkr-recipe-image', 'alt' => esc_attr($post->post_title)]); ?>
            </div>
        <?php endif; ?>
        
        <!-- Rezeptinhalt -->
        <div class="mkr-recipe-content">
            <?php echo wpautop($content); ?>
        </div>

        <!-- Rezeptdetails -->
        <?php if ($servings || $prep_time || $cook_time || $total_time || $total_calories): ?>
            <div class="mkr-recipe-details">
                <div class="mkr-recipe-meta">
                    <?php if ($servings): ?>
                        <div class="mkr-recipe-meta-item">
                            <span class="mkr-meta-icon">👥</span>
                            <span class="mkr-meta-label"><?php _e('Portionen:', 'mein-kochbuch-rezepte'); ?></span>
                            <span class="mkr-meta-value"><?php echo esc_html($servings); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($prep_time): ?>
                        <div class="mkr-recipe-meta-item">
                            <span class="mkr-meta-icon">⏲️</span>
                            <span class="mkr-meta-label"><?php _e('Vorbereitungszeit:', 'mein-kochbuch-rezepte'); ?></span>
                            <span class="mkr-meta-value"><?php echo esc_html($prep_time); ?> <?php _e('Min.', 'mein-kochbuch-rezepte'); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($cook_time): ?>
                        <div class="mkr-recipe-meta-item">
                            <span class="mkr-meta-icon">🍳</span>
                            <span class="mkr-meta-label"><?php _e('Kochzeit:', 'mein-kochbuch-rezepte'); ?></span>
                            <span class="mkr-meta-value"><?php echo esc_html($cook_time); ?> <?php _e('Min.', 'mein-kochbuch-rezepte'); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($total_time): ?>
                        <div class="mkr-recipe-meta-item">
                            <span class="mkr-meta-icon">⏱️</span>
                            <span class="mkr-meta-label"><?php _e('Gesamtzeit:', 'mein-kochbuch-rezepte'); ?></span>
                            <span class="mkr-meta-value"><?php echo esc_html($total_time); ?> <?php _e('Min.', 'mein-kochbuch-rezepte'); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($total_calories && $servings): ?>
                        <div class="mkr-recipe-meta-item">
                            <span class="mkr-meta-icon">🔥</span>
                            <span class="mkr-meta-label"><?php _e('Kalorien pro Portion:', 'mein-kochbuch-rezepte'); ?></span>
                            <span class="mkr-meta-value"><?php echo esc_html(round($total_calories / $servings)); ?> <?php _e('kcal', 'mein-kochbuch-rezepte'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Teilenbuttons für soziale Medien -->
        <div class="mkr-share-buttons">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" rel="noopener noreferrer" class="mkr-share-button mkr-share-facebook" aria-label="<?php esc_attr_e('Auf Facebook teilen', 'mein-kochbuch-rezepte'); ?>">
                <span class="mkr-share-icon">📱</span>
                <span class="mkr-share-text"><?php _e('Auf Facebook teilen', 'mein-kochbuch-rezepte'); ?></span>
            </a>
            
            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" target="_blank" rel="noopener noreferrer" class="mkr-share-button mkr-share-twitter" aria-label="<?php esc_attr_e('Auf X teilen', 'mein-kochbuch-rezepte'); ?>">
                <span class="mkr-share-icon">🐦</span>
                <span class="mkr-share-text"><?php _e('Auf X teilen', 'mein-kochbuch-rezepte'); ?></span>
            </a>
            
            <a href="whatsapp://send?text=<?php echo urlencode(get_the_title() . ' - ' . get_permalink()); ?>" class="mkr-share-button mkr-share-whatsapp" aria-label="<?php esc_attr_e('Per WhatsApp teilen', 'mein-kochbuch-rezepte'); ?>">
                <span class="mkr-share-icon">💬</span>
                <span class="mkr-share-text"><?php _e('Per WhatsApp teilen', 'mein-kochbuch-rezepte'); ?></span>
            </a>
            
            <a href="mailto:?subject=<?php echo rawurlencode(get_the_title()); ?>&body=<?php echo rawurlencode(get_the_title() . "\n" . get_permalink()); ?>" class="mkr-share-button mkr-share-email" aria-label="<?php esc_attr_e('Per E-Mail teilen', 'mein-kochbuch-rezepte'); ?>">
                <span class="mkr-share-icon">✉️</span>
                <span class="mkr-share-text"><?php _e('Per E-Mail teilen', 'mein-kochbuch-rezepte'); ?></span>
            </a>
            
            <button class="mkr-share-button mkr-share-print" onclick="window.print();" aria-label="<?php esc_attr_e('Rezept drucken', 'mein-kochbuch-rezepte'); ?>">
                <span class="mkr-share-icon">🖨️</span>
                <span class="mkr-share-text"><?php _e('Drucken', 'mein-kochbuch-rezepte'); ?></span>
            </button>
        </div>

        <!-- Zutaten -->
        <?php if ($ingredients && is_array($ingredients)): ?>
            <div id="ingredients" class="mkr-recipe-section mkr-ingredients-section">
                <h2 class="mkr-section-title"><?php _e('Zutaten', 'mein-kochbuch-rezepte'); ?></h2>
                
                <!-- Portionsrechner -->
                <div class="mkr-portionsrechner" aria-labelledby="portionsrechner-label">
                    <span id="portionsrechner-label"><?php _e('Portionen anpassen:', 'mein-kochbuch-rezepte'); ?></span>
                    <button type="button" class="mkr-portions-button mkr-decrease-portions" aria-label="<?php esc_attr_e('Portionen verringern', 'mein-kochbuch-rezepte'); ?>">−</button>
                    <input type="number" id="portions" class="mkr-portions-input" value="<?php echo esc_attr($servings); ?>" data-original-portions="<?php echo esc_attr($servings); ?>" min="1" aria-label="<?php esc_attr_e('Anzahl der Portionen', 'mein-kochbuch-rezepte'); ?>" />
                    <button type="button" class="mkr-portions-button mkr-increase-portions" aria-label="<?php esc_attr_e('Portionen erhöhen', 'mein-kochbuch-rezepte'); ?>">+</button>
                </div>
                
                <!-- Einheiten-Umschalter -->
                <div class="mkr-unit-selector">
                    <label for="unit-system"><?php _e('Einheiten:', 'mein-kochbuch-rezepte'); ?></label>
                    <select id="unit-system" class="mkr-unit-select">
                        <option value="metric"><?php _e('Metrisch (g, ml)', 'mein-kochbuch-rezepte'); ?></option>
                        <option value="cups"><?php _e('Cups (US)', 'mein-kochbuch-rezepte'); ?></option>
                    </select>
                </div>
                
                <!-- Zutatenliste -->
                <ul class="mkr-ingredients-list">
                    <?php 
                    foreach ($ingredients as $ingredient): 
                        $amount = $ingredient['amount'];
                        $unit = $ingredient['unit'];
                        $name = $ingredient['name'];
                        
                        // Zutat in der Datenbank suchen
                        $args = [
                            'post_type' => 'ingredient',
                            'post_status' => 'publish',
                            'title' => $name,
                            'posts_per_page' => 1
                        ];
                        
                        $ingredient_query = new WP_Query($args);
                        $ingredient_post = $ingredient_query->have_posts() ? $ingredient_query->posts[0] : null;
                        
                        if ($ingredient_post) {
                            $link = get_permalink($ingredient_post->ID);
                            $weight_per_cup = get_post_meta($ingredient_post->ID, '_mkr_weight_per_cup', true);
                            $calories_per_100g = get_post_meta($ingredient_post->ID, '_mkr_calories_per_100g', true);
                            $proteins_per_100g = get_post_meta($ingredient_post->ID, '_mkr_proteins_per_100g', true);
                            $fats_per_100g = get_post_meta($ingredient_post->ID, '_mkr_fats_per_100g', true);
                            $carbs_per_100g = get_post_meta($ingredient_post->ID, '_mkr_carbs_per_100g', true);
                            $display_name = '<a href="' . esc_url($link) . '" class="mkr-ingredient-link">' . esc_html($name) . '</a>';
                            $is_liquid = in_array(strtolower($unit), ['ml', 'l']) ? 'true' : 'false';
                        } else {
                            $weight_per_cup = '';
                            $display_name = esc_html($name);
                            $is_liquid = in_array(strtolower($unit), ['ml', 'l']) ? 'true' : 'false';
                        }
                    ?>
                        <li class="mkr-ingredient-item" data-original-amount="<?php echo esc_attr($amount); ?>" data-unit="<?php echo esc_attr($unit); ?>" data-weight-per-cup="<?php echo esc_attr($weight_per_cup); ?>" data-is-liquid="<?php echo $is_liquid; ?>">
                            <div class="mkr-ingredient-checkbox-wrapper">
                                <input type="checkbox" id="ingredient-<?php echo sanitize_title($name); ?>" class="mkr-ingredient-checkbox" aria-label="<?php printf(esc_attr__('Zutat %s abhaken', 'mein-kochbuch-rezepte'), esc_attr($name)); ?>" />
                                <label for="ingredient-<?php echo sanitize_title($name); ?>" class="screen-reader-text"><?php printf(esc_attr__('Zutat %s abhaken', 'mein-kochbuch-rezepte'), esc_attr($name)); ?></label>
                            </div>
                            <div class="mkr-ingredient-text">
                                <span class="mkr-amount" data-original-amount="<?php echo esc_attr($amount); ?>"><?php echo esc_html($amount); ?></span>
                                <span class="mkr-unit"><?php echo esc_html($unit); ?></span>
                                <span class="mkr-cups-amount" style="display: none;"></span>
                                <span class="mkr-ingredient-name"><?php echo $display_name; ?></span>
                            </div>
                            <button type="button" class="mkr-add-to-shopping-list" aria-label="<?php printf(esc_attr__('%s zur Einkaufsliste hinzufügen', 'mein-kochbuch-rezepte'), esc_attr($name)); ?>">
                                <span class="mkr-shopping-icon">🛒</span>
                            </button>
                        </li>
                    <?php 
                        wp_reset_postdata();
                    endforeach; 
                    ?>
                </ul>
                
                <!-- Zur Einkaufsliste hinzufügen -->
                <button type="button" class="mkr-add-all-to-shopping-list button button-primary">
                    <?php _e('Alle Zutaten zur Einkaufsliste hinzufügen', 'mein-kochbuch-rezepte'); ?>
                </button>
                
                <!-- Nährwertinformationen -->
                <?php if ($total_calories || $total_proteins || $total_fats || $total_carbs): ?>
                    <div class="mkr-recipe-nutrition">
                        <h3><?php _e('Nährwerte (pro Portion)', 'mein-kochbuch-rezepte'); ?></h3>
                        <table class="mkr-nutrition-table">
                            <tbody>
                                <?php if ($total_calories): ?>
                                    <tr>
                                        <th scope="row"><?php _e('Kalorien:', 'mein-kochbuch-rezepte'); ?></th>
                                        <td><span class="mkr-nutrition-value"><?php echo round($total_calories / $servings, 1); ?></span> kcal</td>
                                    </tr>
                                <?php endif; ?>
                                
                                <?php if ($total_proteins): ?>
                                    <tr>
                                        <th scope="row"><?php _e('Proteine:', 'mein-kochbuch-rezepte'); ?></th>
                                        <td><span class="mkr-nutrition-value"><?php echo round($total_proteins / $servings, 1); ?></span> g</td>
                                    </tr>
                                <?php endif; ?>
                                
                                <?php if ($total_fats): ?>
                                    <tr>
                                        <th scope="row"><?php _e('Fette:', 'mein-kochbuch-rezepte'); ?></th>
                                        <td><span class="mkr-nutrition-value"><?php echo round($total_fats / $servings, 1); ?></span> g</td>
                                    </tr>
                                <?php endif; ?>
                                
                                <?php if ($total_carbs): ?>
                                    <tr>
                                        <th scope="row"><?php _e('Kohlenhydrate:', 'mein-kochbuch-rezepte'); ?></th>
                                        <td><span class="mkr-nutrition-value"><?php echo round($total_carbs / $servings, 1); ?></span> g</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Utensilien/Küchengeräte -->
        <?php if ($utensils && is_array($utensils) && !empty($utensils)): ?>
            <div class="mkr-recipe-section mkr-utensils-section">
                <h2 class="mkr-section-title"><?php _e('Küchengeräte', 'mein-kochbuch-rezepte'); ?></h2>
                <ul class="mkr-utensils-list">
                    <?php foreach ($utensils as $utensil): 
                        // Utensil in der Datenbank suchen
                        $args = [
                            'post_type' => 'utensil',
                            'post_status' => 'publish',
                            'title' => $utensil,
                            'posts_per_page' => 1
                        ];
                        
                        $utensil_query = new WP_Query($args);
                        $utensil_post = $utensil_query->have_posts() ? $utensil_query->posts[0] : null;
                        
                        if ($utensil_post) {
                            $link = get_permalink($utensil_post->ID);
                            $display_name = '<a href="' . esc_url($link) . '" class="mkr-utensil-link">' . esc_html($utensil) . '</a>';
                        } else {
                            $display_name = esc_html($utensil);
                        }
                    ?>
                        <li class="mkr-utensil-item">
                            <div class="mkr-utensil-checkbox-wrapper">
                                <input type="checkbox" id="utensil-<?php echo sanitize_title($utensil); ?>" class="mkr-utensil-checkbox" aria-label="<?php printf(esc_attr__('Küchengerät %s abhaken', 'mein-kochbuch-rezepte'), esc_attr($utensil)); ?>" />
                                <label for="utensil-<?php echo sanitize_title($utensil); ?>" class="screen-reader-text"><?php printf(esc_attr__('Küchengerät %s abhaken', 'mein-kochbuch-rezepte'), esc_attr($utensil)); ?></label>
                            </div>
                            <div class="mkr-utensil-text">
                                <?php echo $display_name; ?>
                            </div>
                        </li>
                    <?php 
                        wp_reset_postdata();
                    endforeach; 
                    ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Zubereitungsanleitung -->
        <?php if ($instructions): ?>
            <div id="instructions" class="mkr-recipe-section mkr-instructions-section">
                <h2 class="mkr-section-title"><?php _e('Zubereitung', 'mein-kochbuch-rezepte'); ?></h2>
                
                <?php
                // Anleitung in Schritte unterteilen
                $steps = [];
                
                if (strpos($instructions, '<ol>') !== false || strpos($instructions, '<ul>') !== false) {
                    // Falls HTML-Listen verwendet werden
                    $dom = new DOMDocument();
                    @$dom->loadHTML('<?xml encoding="UTF-8">' . $instructions);
                    $lists = $dom->getElementsByTagName('li');
                    
                    foreach ($lists as $list_item) {
                        $steps[] = $dom->saveHTML($list_item);
                    }
                } else {
                    // Ansonsten nach Absätzen aufteilen
                    $steps = explode("\n", trim($instructions));
                }
                
                // Leere Schritte entfernen
                $steps = array_filter($steps, function($step) {
                    return trim(strip_tags($step)) !== '';
                });
                
                if (!empty($steps)):
                ?>
                    <ol class="mkr-instructions-list">
                        <?php foreach ($steps as $index => $step): ?>
                            <li class="mkr-instruction-step" data-step="<?php echo $index; ?>">
                                <div class="mkr-instruction-content">
                                    <?php echo wpautop($step); ?>
                                    
                                    <?php
                                    // Videos zu diesem Schritt anzeigen, falls vorhanden
                                    if (isset($videos[$index]) && !empty($videos[$index])):
                                        $video_url = $videos[$index];
                                        $video_id = '';
                                        
                                        // YouTube-Video-ID extrahieren
                                        if (preg_match('/(?:youtube\.com\/(?:[^\/\n\s]+\/\s*[^\/\n\s]+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video_url, $matches)) {
                                            $video_id = $matches[1];
                                        }
                                        
                                        if ($video_id):
                                    ?>
                                            <div class="mkr-step-video">
                                                <iframe width="560" height="315" src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                            </div>
                                    <?php 
                                        endif;
                                    endif; 
                                    ?>
                                </div>
                                <button type="button" class="mkr-step-complete" aria-label="<?php printf(esc_attr__('Schritt %d als erledigt markieren', 'mein-kochbuch-rezepte'), $index + 1); ?>">
                                    <?php _e('Fertig', 'mein-kochbuch-rezepte'); ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Kommentare/Bewertungen -->
        <?php if (comments_open() || get_comments_number()): ?>
            <div class="mkr-recipe-section mkr-comments-section">
                <h2 class="mkr-section-title"><?php _e('Kommentare & Bewertungen', 'mein-kochbuch-rezepte'); ?></h2>
                <div class="mkr-comments-container">
                    <?php comments_template(); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Ähnliche Rezepte -->
        <?php
        // Zutaten für die Ähnlichkeitssuche
        $ingredient_names = array_column($ingredients, 'name');
        
        // Taxonomien für die Ähnlichkeitssuche
        $taxonomies = ['difficulty', 'diet', 'cuisine', 'season'];
        $tax_terms = [];
        
        foreach ($taxonomies as $tax) {
            $terms = get_the_terms($post_id, $tax);
            if ($terms && !is_wp_error($terms)) {
                $tax_terms[$tax] = wp_list_pluck($terms, 'term_id');
            }
        }
        
        $args = [
            'post_type' => 'recipe',
            'posts_per_page' => 3,
            'post__not_in' => [$post_id],
            'meta_query' => ['relation' => 'OR'],
            'tax_query' => ['relation' => 'OR'],
        ];
        
        // Nach ähnlichen Zutaten suchen
        if (!empty($ingredient_names)) {
            $ingredients_meta_query = ['relation' => 'OR'];
            
            foreach ($ingredient_names as $ingredient_name) {
                $ingredients_meta_query[] = [
                    'key' => '_mkr_ingredients',
                    'value' => sanitize_text_field($ingredient_name),
                    'compare' => 'LIKE',
                ];
            }
            
            $args['meta_query'][] = $ingredients_meta_query;
        }
        
        // Nach ähnlichen Taxonomien suchen
        foreach ($tax_terms as $tax => $terms) {
            if (!empty($terms)) {
                $args['tax_query'][] = [
                    'taxonomy' => $tax,
                    'field' => 'term_id',
                    'terms' => $terms,
                ];
            }
        }
        
        $related_recipes = new WP_Query($args);
        
        if ($related_recipes->have_posts()):
        ?>
            <div class="mkr-recipe-section mkr-related-recipes-section">
                <h2 class="mkr-section-title"><?php _e('Ähnliche Rezepte', 'mein-kochbuch-rezepte'); ?></h2>
                <div class="mkr-related-recipes">
                    <?php while ($related_recipes->have_posts()): $related_recipes->the_post(); ?>
                        <div class="mkr-related-recipe">
                            <a href="<?php the_permalink(); ?>" class="mkr-related-recipe-link">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="mkr-related-recipe-image">
                                        <?php the_post_thumbnail('thumbnail', ['class' => 'mkr-related-thumbnail', 'alt' => esc_attr(get_the_title())]); ?>
                                    </div>
                                <?php endif; ?>
                                <h3 class="mkr-related-recipe-title"><?php the_title(); ?></h3>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php 
            wp_reset_postdata();
        endif; 
        ?>
        
        <!-- Einstellungsmenü -->
        <div id="settings-menu" class="mkr-settings-menu" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Einstellungen', 'mein-kochbuch-rezepte'); ?>" style="display: none;">
            <div class="mkr-settings-content">
                <h2 class="mkr-settings-heading"><?php _e('Einstellungen', 'mein-kochbuch-rezepte'); ?></h2>
                
                <fieldset class="mkr-setting-group">
                    <legend class="mkr-setting-title"><?php _e('Farbschema', 'mein-kochbuch-rezepte'); ?></legend>
                    <div class="mkr-button-group color-scheme">
                        <label class="mkr-button-label" for="color-scheme-light">
                            <input type="radio" id="color-scheme-light" name="color-scheme" value="light" />
                            <span><?php _e('Hell', 'mein-kochbuch-rezepte'); ?></span>
                        </label>
                        <label class="mkr-button-label" for="color-scheme-dark">
                            <input type="radio" id="color-scheme-dark" name="color-scheme" value="dark" />
                            <span><?php _e('Dunkel', 'mein-kochbuch-rezepte'); ?></span>
                        </label>
                        <label class="mkr-button-label" for="color-scheme-high-contrast">
                            <input type="radio" id="color-scheme-high-contrast" name="color-scheme" value="high-contrast" />
                            <span><?php _e('Hoher Kontrast', 'mein-kochbuch-rezepte'); ?></span>
                        </label>
                    </div>
                </fieldset>
                
                <fieldset class="mkr-setting-group">
                    <legend class="mkr-setting-title"><?php _e('Schriftgröße', 'mein-kochbuch-rezepte'); ?></legend>
                    <div class="mkr-button-group font-size">
                        <label class="mkr-button-label" for="font-size-small">
                            <input type="radio" id="font-size-small" name="font-size" value="small" />
                            <span><?php _e('Klein', 'mein-kochbuch-rezepte'); ?></span>
                        </label>
                        <label class="mkr-button-label" for="font-size-medium">
                            <input type="radio" id="font-size-medium" name="font-size" value="medium" />
                            <span><?php _e('Mittel', 'mein-kochbuch-rezepte'); ?></span>
                        </label>
                        <label class="mkr-button-label" for="font-size-large">
                            <input type="radio" id="font-size-large" name="font-size" value="large" />
                            <span><?php _e('Groß', 'mein-kochbuch-rezepte'); ?></span>
                        </label>
                        <label class="mkr-button-label" for="font-size-x-large">
                            <input type="radio" id="font-size-x-large" name="font-size" value="x-large" />
                            <span><?php _e('Sehr groß', 'mein-kochbuch-rezepte'); ?></span>
                        </label>
                    </div>
                </fieldset>
                
                <fieldset class="mkr-setting-group">
                    <legend class="mkr-setting-title"><?php _e('Layout', 'mein-kochbuch-rezepte'); ?></legend>
                    <div class="mkr-button-group layout">
                        <label class="mkr-button-label" for="layout-default">
                            <input type="radio" id="layout-default" name="layout" value="default" />
                            <span><?php _e('Standard', 'mein-kochbuch-rezepte'); ?></span>
                        </label>
                        <label class="mkr-button-label" for="layout-ingredients-first">
                            <input type="radio" id="layout-ingredients-first" name="layout" value="ingredients-first" />
                            <span><?php _e('Zutaten zuerst', 'mein-kochbuch-rezepte'); ?></span>
                        </label>
                    </div>
                </fieldset>
                
                <div class="mkr-settings-buttons">
                    <button type="button" id="save-settings" class="mkr-button mkr-button-primary"><?php _e('Speichern', 'mein-kochbuch-rezepte'); ?></button>
                    <button type="button" id="close-settings" class="mkr-button"><?php _e('Schließen', 'mein-kochbuch-rezepte'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Verknüpft Fachbegriffe im Text mit den entsprechenden Lexikoneinträgen
 *
 * @param string $content Der zu verarbeitende Inhalt
 * @return string Der verarbeitete Inhalt mit verlinkten Fachbegriffen
 */
function mkr_link_glossary_terms($content) {
    // Alle Fachbegriffe aus dem Lexikon abrufen
    $glossary_terms = get_posts([
        'post_type' => 'glossary',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'DESC', // Längere Begriffe zuerst ersetzen
    ]);
    
    if (empty($glossary_terms)) {
        return $content;
    }
    
    // HTML-Tags und deren Attribute schützen, um zu verhindern, dass in ihnen Ersetzungen vorgenommen werden
    $content = preg_replace_callback(
        '/<([^>]*)>/',
        function($matches) {
            return '<!-- TAG_START -->' . base64_encode($matches[0]) . '<!-- TAG_END -->';
        },
        $content
    );
    
    // Alle Fachbegriffe durchlaufen und im Text verlinken
    foreach ($glossary_terms as $term) {
        $term_name = $term->post_title;
        $term_link = get_permalink($term->ID);
        
        // Wortzwischengrenzen verwenden, um nur ganze Wörter zu ersetzen
        $pattern = '/\b(' . preg_quote($term_name, '/') . ')\b/i';
        
        // Nur das erste Vorkommen ersetzen
        $replacement = '<a href="' . esc_url($term_link) . '" class="mkr-glossary-link" title="' . esc_attr($term_name) . '" data-tooltip="' . esc_attr(wp_trim_words($term->post_excerpt, 20)) . '">$1</a>';
        $content = preg_replace($pattern, $replacement, $content, 1);
    }
    
    // Geschützte HTML-Tags wiederherstellen
    $content = preg_replace_callback(
        '/<!-- TAG_START -->([^<]*)<!-- TAG_END -->/',
        function($matches) {
            return base64_decode($matches[1]);
        },
        $content
    );
    
    return $content;
}

/**
 * Registriert notwendige Styles und Scripts für das Frontend
 */
function mkr_register_frontend_assets() {
    // Scripts für den Portionsrechner und die Einstellungen
    wp_enqueue_script('mkr-portionsrechner', MKR_PLUGIN_URL . 'assets/js/portionsrechner.js', ['jquery'], MKR_VERSION, true);
    wp_enqueue_script('mkr-settings-menu', MKR_PLUGIN_URL . 'assets/js/settings-menu.js', ['jquery'], MKR_VERSION, true);
    
    // Script für die Einkaufsliste
    wp_enqueue_script('mkr-shopping-list', MKR_PLUGIN_URL . 'assets/js/shopping-list.js', ['jquery'], MKR_VERSION, true);
    
    // Lokalisierte Strings für JavaScript
    wp_localize_script('mkr-shopping-list', 'mkrShoppingList', [
        'addedToList' => __('Zur Einkaufsliste hinzugefügt', 'mein-kochbuch-rezepte'),
        'viewList' => __('Einkaufsliste anzeigen', 'mein-kochbuch-rezepte'),
        'shoppingListPage' => get_permalink(get_page_by_path('einkaufsliste')),
    ]);
}
add_action('wp_enqueue_scripts', 'mkr_register_frontend_assets');