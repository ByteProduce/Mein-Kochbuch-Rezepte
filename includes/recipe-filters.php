<?php
/**
 * Filter-Funktionen für Rezepte
 */

// Sicherheitsprüfung: Direkter Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filtert die Rezeptabfrage, um Benutzerfilter zu berücksichtigen
 */
add_action('pre_get_posts', 'mkr_filter_recipes');
function mkr_filter_recipes($query) {
    // Nur auf der Rezeptarchivseite und nicht im Admin-Bereich
    if (is_post_type_archive('recipe') && !is_admin() && $query->is_main_query()) {
        $tax_query = array();
        $meta_query = array();
        
        // Schwierigkeitsgrad-Filter
        if (isset($_GET['difficulty']) && !empty($_GET['difficulty'])) {
            $tax_query[] = array(
                'taxonomy' => 'difficulty',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['difficulty']),
            );
        }
        
        // Diätvorgaben-Filter
        if (isset($_GET['diet']) && !empty($_GET['diet'])) {
            $tax_query[] = array(
                'taxonomy' => 'diet',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['diet']),
            );
        } elseif (is_user_logged_in() && get_option('mkr_apply_user_diet', 'yes') === 'yes') {
            // Benutzerdiätvorgaben berücksichtigen, wenn keine explizit ausgewählt
            $user_diet = get_user_meta(get_current_user_id(), 'mkr_diet', true);
            if ($user_diet) {
                $tax_query[] = array(
                    'taxonomy' => 'diet',
                    'field' => 'slug',
                    'terms' => $user_diet,
                );
            }
        }
        
        // Küchenart-Filter
        if (isset($_GET['cuisine']) && !empty($_GET['cuisine'])) {
            $tax_query[] = array(
                'taxonomy' => 'cuisine',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['cuisine']),
            );
        }
        
        // Saison-Filter
        if (isset($_GET['season']) && !empty($_GET['season'])) {
            $tax_query[] = array(
                'taxonomy' => 'season',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['season']),
            );
        } elseif (get_option('mkr_show_seasonal_recipes', 'yes') === 'yes') {
            // Aktuelle Saison automatisch filtern
            $current_month = date('n');
            $current_season = '';
            
            if ($current_month >= 3 && $current_month <= 5) {
                $current_season = 'frühling';
            } elseif ($current_month >= 6 && $current_month <= 8) {
                $current_season = 'sommer';
            } elseif ($current_month >= 9 && $current_month <= 11) {
                $current_season = 'herbst';
            } else {
                $current_season = 'winter';
            }
            
            $tax_query[] = array(
                'taxonomy' => 'season',
                'field' => 'slug',
                'terms' => array($current_season, 'ganzjährig'),
                'operator' => 'IN',
            );
        }
        
        // Zubereitungszeit-Filter
        if (isset($_GET['prep_time']) && !empty($_GET['prep_time'])) {
            $meta_query[] = array(
                'key' => '_mkr_total_time',
                'value' => intval($_GET['prep_time']),
                'compare' => '<=',
                'type' => 'NUMERIC',
            );
        }
        
        // Portionen-Filter
        if (isset($_GET['servings']) && !empty($_GET['servings'])) {
            $meta_query[] = array(
                'key' => '_mkr_servings',
                'value' => intval($_GET['servings']),
                'compare' => '=',
                'type' => 'NUMERIC',
            );
        }
        
        // Zutaten-Filter
        if (isset($_GET['ingredient']) && !empty($_GET['ingredient'])) {
            $ingredient = sanitize_text_field($_GET['ingredient']);
            $meta_query[] = array(
                'key' => '_mkr_ingredients',
                'value' => $ingredient,
                'compare' => 'LIKE',
            );
        }
        
        // Nach Kaloriengehalt filtern
        if (isset($_GET['max_calories']) && !empty($_GET['max_calories'])) {
            $max_calories = intval($_GET['max_calories']);
            
            if ($max_calories > 0) {
                $meta_query[] = array(
                    'key' => '_mkr_total_calories',
                    'value' => $max_calories,
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                );
            }
        }
        
        // Abfrage für Rezepte mit Video
        if (isset($_GET['has_video']) && $_GET['has_video'] === '1') {
            $meta_query[] = array(
                'key' => '_mkr_videos',
                'compare' => 'EXISTS',
            );
        }
        
        // Sortierung
        if (isset($_GET['orderby']) && !empty($_GET['orderby'])) {
            switch ($_GET['orderby']) {
                case 'date':
                    $query->set('orderby', 'date');
                    $query->set('order', 'DESC');
                    break;
                case 'title':
                    $query->set('orderby', 'title');
                    $query->set('order', 'ASC');
                    break;
                case 'rating':
                    $query->set('orderby', 'meta_value_num');
                    $query->set('meta_key', '_mkr_average_rating');
                    $query->set('order', 'DESC');
                    break;
                case 'popularity':
                    $query->set('orderby', 'meta_value_num');
                    $query->set('meta_key', '_mkr_view_count');
                    $query->set('order', 'DESC');
                    break;
                case 'time_asc':
                    $query->set('orderby', 'meta_value_num');
                    $query->set('meta_key', '_mkr_total_time');
                    $query->set('order', 'ASC');
                    break;
                case 'time_desc':
                    $query->set('orderby', 'meta_value_num');
                    $query->set('meta_key', '_mkr_total_time');
                    $query->set('order', 'DESC');
                    break;
                case 'random':
                    $query->set('orderby', 'rand');
                    break;
            }
        }
        
        // Suchanfrage verarbeiten
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $query->set('s', sanitize_text_field($_GET['s']));
        }
        
        // Tax-Query zur Hauptabfrage hinzufügen (wenn vorhanden)
        if (!empty($tax_query)) {
            // Wenn mehr als eine Taxonomie gefiltert wird, die Beziehung festlegen
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            
            $query->set('tax_query', $tax_query);
        }
        
        // Meta-Query zur Hauptabfrage hinzufügen (wenn vorhanden)
        if (!empty($meta_query)) {
            // Wenn mehr als ein Meta-Feld gefiltert wird, die Beziehung festlegen
            if (count($meta_query) > 1) {
                $meta_query['relation'] = 'AND';
            }
            
            $query->set('meta_query', $meta_query);
        }
        
        // Anzahl der Rezepte pro Seite
        $recipes_per_page = get_option('mkr_recipes_per_page', 12);
        $query->set('posts_per_page', intval($recipes_per_page));
    }
}

/**
 * Fügt die Rezeptsuchabfrage zu den WordPress-Suchabfragen hinzu
 */
add_filter('pre_get_posts', 'mkr_include_recipes_in_search');
function mkr_include_recipes_in_search($query) {
    if ($query->is_main_query() && $query->is_search() && !is_admin()) {
        $current_types = $query->get('post_type');
        
        if ('post' === $current_types) {
            // Nur Posts werden standardmäßig durchsucht, füge Rezepte hinzu
            $query->set('post_type', array('post', 'recipe'));
        } elseif (empty($current_types)) {
            // Keine Post-Typen festgelegt, setze Posts und Rezepte
            $query->set('post_type', array('post', 'page', 'recipe'));
        } elseif (is_array($current_types) && !in_array('recipe', $current_types)) {
            // Array von Post-Typen, füge Rezepte hinzu, wenn nicht bereits vorhanden
            $current_types[] = 'recipe';
            $query->set('post_type', $current_types);
        }
    }
    
    return $query;
}

/**
 * Registriert Widget für die Rezeptfilter in der Seitenleiste
 */
class MKR_Recipe_Filter_Widget extends WP_Widget {
    /**
     * Konstruktor
     */
    public function __construct() {
        parent::__construct(
            'mkr_recipe_filter_widget',
            __('Rezeptfilter', 'mein-kochbuch-rezepte'),
            array('description' => __('Filter für Rezepte anzeigen', 'mein-kochbuch-rezepte'))
        );
    }
    
    /**
     * Frontend-Darstellung des Widgets
     */
    public function widget($args, $instance) {
        // Nur auf der Rezeptarchivseite anzeigen
        if (!is_post_type_archive('recipe') && !is_tax(array('difficulty', 'diet', 'cuisine', 'season'))) {
            return;
        }
        
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        // Aktuelle URL ohne Query-Parameter ermitteln
        $current_url = remove_query_arg(array('difficulty', 'diet', 'cuisine', 'season', 'prep_time', 'servings', 'max_calories', 'has_video', 'orderby', 'paged'));
        
        ?>
        <div class="mkr-recipe-filters-widget">
            <form method="get" action="<?php echo esc_url($current_url); ?>">
                <?php
                // Schwierigkeitsgrad-Filter
                if (!empty($instance['show_difficulty'])) {
                    $this->render_taxonomy_filter('difficulty', __('Schwierigkeitsgrad', 'mein-kochbuch-rezepte'));
                }
                
                // Diätvorgaben-Filter
                if (!empty($instance['show_diet'])) {
                    $this->render_taxonomy_filter('diet', __('Diätvorgaben', 'mein-kochbuch-rezepte'));
                }
                
                // Küchenart-Filter
                if (!empty($instance['show_cuisine'])) {
                    $this->render_taxonomy_filter('cuisine', __('Küchenart', 'mein-kochbuch-rezepte'));
                }
                
                // Saison-Filter
                if (!empty($instance['show_season'])) {
                    $this->render_taxonomy_filter('season', __('Saison', 'mein-kochbuch-rezepte'));
                }
                
                // Zubereitungszeit-Filter
                if (!empty($instance['show_prep_time'])) {
                    $this->render_prep_time_filter();
                }
                
                // Portionen-Filter
                if (!empty($instance['show_servings'])) {
                    $this->render_servings_filter();
                }
                
                // Kaloriengehalt-Filter
                if (!empty($instance['show_calories'])) {
                    $this->render_calories_filter();
                }
                
                // Video-Filter
                if (!empty($instance['show_video'])) {
                    $this->render_video_filter();
                }
                
                // Sortierung
                if (!empty($instance['show_sorting'])) {
                    $this->render_sorting_options();
                }
                ?>
                
                <button type="submit" class="mkr-button mkr-button-primary"><?php _e('Filtern', 'mein-kochbuch-rezepte'); ?></button>
                <a href="<?php echo esc_url($current_url); ?>" class="mkr-button"><?php _e('Zurücksetzen', 'mein-kochbuch-rezepte'); ?></a>
            </form>
        </div>
        <?php
        
        echo $args['after_widget'];
    }
    
    /**
     * Render-Methode für Taxonomie-Filter
     */
    private function render_taxonomy_filter($taxonomy, $label) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return;
        }
        
        $current_value = isset($_GET[$taxonomy]) ? sanitize_text_field($_GET[$taxonomy]) : '';
        
        ?>
        <div class="mkr-filter-group">
            <label for="<?php echo esc_attr($taxonomy); ?>"><?php echo esc_html($label); ?></label>
            <select name="<?php echo esc_attr($taxonomy); ?>" id="<?php echo esc_attr($taxonomy); ?>">
                <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                <?php foreach ($terms as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($current_value, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }
    
    /**
     * Render-Methode für Zubereitungszeit-Filter
     */
    private function render_prep_time_filter() {
        $current_value = isset($_GET['prep_time']) ? intval($_GET['prep_time']) : '';
        
        ?>
        <div class="mkr-filter-group">
            <label for="prep_time"><?php _e('Maximale Zubereitungszeit (Minuten)', 'mein-kochbuch-rezepte'); ?></label>
            <select name="prep_time" id="prep_time">
                <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                <option value="15" <?php selected($current_value, 15); ?>><?php _e('Bis 15 Minuten', 'mein-kochbuch-rezepte'); ?></option>
                <option value="30" <?php selected($current_value, 30); ?>><?php _e('Bis 30 Minuten', 'mein-kochbuch-rezepte'); ?></option>
                <option value="45" <?php selected($current_value, 45); ?>><?php _e('Bis 45 Minuten', 'mein-kochbuch-rezepte'); ?></option>
                <option value="60" <?php selected($current_value, 60); ?>><?php _e('Bis 1 Stunde', 'mein-kochbuch-rezepte'); ?></option>
                <option value="90" <?php selected($current_value, 90); ?>><?php _e('Bis 1,5 Stunden', 'mein-kochbuch-rezepte'); ?></option>
                <option value="120" <?php selected($current_value, 120); ?>><?php _e('Bis 2 Stunden', 'mein-kochbuch-rezepte'); ?></option>
            </select>
        </div>
        <?php
    }
    
    /**
     * Render-Methode für Portionen-Filter
     */
    private function render_servings_filter() {
        $current_value = isset($_GET['servings']) ? intval($_GET['servings']) : '';
        
        ?>
        <div class="mkr-filter-group">
            <label for="servings"><?php _e('Portionen', 'mein-kochbuch-rezepte'); ?></label>
            <select name="servings" id="servings">
                <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                <option value="1" <?php selected($current_value, 1); ?>><?php _e('1 Person', 'mein-kochbuch-rezepte'); ?></option>
                <option value="2" <?php selected($current_value, 2); ?>><?php _e('2 Personen', 'mein-kochbuch-rezepte'); ?></option>
                <option value="4" <?php selected($current_value, 4); ?>><?php _e('4 Personen', 'mein-kochbuch-rezepte'); ?></option>
                <option value="6" <?php selected($current_value, 6); ?>><?php _e('6+ Personen', 'mein-kochbuch-rezepte'); ?></option>
            </select>
        </div>
        <?php
    }
    
    /**
     * Render-Methode für Kaloriengehalt-Filter
     */
    private function render_calories_filter() {
        $current_value = isset($_GET['max_calories']) ? intval($_GET['max_calories']) : '';
        
        ?>
        <div class="mkr-filter-group">
            <label for="max_calories"><?php _e('Maximale Kalorien pro Portion', 'mein-kochbuch-rezepte'); ?></label>
            <select name="max_calories" id="max_calories">
                <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                <option value="300" <?php selected($current_value, 300); ?>><?php _e('Bis 300 kcal', 'mein-kochbuch-rezepte'); ?></option>
                <option value="500" <?php selected($current_value, 500); ?>><?php _e('Bis 500 kcal', 'mein-kochbuch-rezepte'); ?></option>
                <option value="700" <?php selected($current_value, 700); ?>><?php _e('Bis 700 kcal', 'mein-kochbuch-rezepte'); ?></option>
                <option value="1000" <?php selected($current_value, 1000); ?>><?php _e('Bis 1000 kcal', 'mein-kochbuch-rezepte'); ?></option>
            </select>
        </div>
        <?php
    }
    
    /**
     * Render-Methode für Video-Filter
     */
    private function render_video_filter() {
        $current_value = isset($_GET['has_video']) ? $_GET['has_video'] : '';
        
        ?>
        <div class="mkr-filter-group">
            <label>
                <input type="checkbox" name="has_video" value="1" <?php checked($current_value, '1'); ?>>
                <?php _e('Nur Rezepte mit Video', 'mein-kochbuch-rezepte'); ?>
            </label>
        </div>
        <?php
    }
    
    /**
     * Render-Methode für Sortieroptionen
     */
    private function render_sorting_options() {
        $current_value = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : '';
        
        ?>
        <div class="mkr-filter-group">
            <label for="orderby"><?php _e('Sortieren nach', 'mein-kochbuch-rezepte'); ?></label>
            <select name="orderby" id="orderby">
                <option value="date" <?php selected($current_value, 'date'); ?>><?php _e('Datum (neueste zuerst)', 'mein-kochbuch-rezepte'); ?></option>
                <option value="title" <?php selected($current_value, 'title'); ?>><?php _e('Titel (A-Z)', 'mein-kochbuch-rezepte'); ?></option>
                <option value="rating" <?php selected($current_value, 'rating'); ?>><?php _e('Bewertung (höchste zuerst)', 'mein-kochbuch-rezepte'); ?></option>
                <option value="popularity" <?php selected($current_value, 'popularity'); ?>><?php _e('Beliebtheit', 'mein-kochbuch-rezepte'); ?></option>
                <option value="time_asc" <?php selected($current_value, 'time_asc'); ?>><?php _e('Zubereitungszeit (kürzeste zuerst)', 'mein-kochbuch-rezepte'); ?></option>
                <option value="time_desc" <?php selected($current_value, 'time_desc'); ?>><?php _e('Zubereitungszeit (längste zuerst)', 'mein-kochbuch-rezepte'); ?></option>
                <option value="random" <?php selected($current_value, 'random'); ?>><?php _e('Zufällig', 'mein-kochbuch-rezepte'); ?></option>
            </select>
        </div>
        <?php
    }
    
    /**
     * Backend-Formular für Widget-Optionen
     */
    public function form($instance) {
        $defaults = array(
            'title' => __('Rezepte filtern', 'mein-kochbuch-rezepte'),
            'show_difficulty' => 1,
            'show_diet' => 1,
            'show_cuisine' => 1,
            'show_season' => 1,
            'show_prep_time' => 1,
            'show_servings' => 1,
            'show_calories' => 1,
            'show_video' => 1,
            'show_sorting' => 1,
        );
        
        $instance = wp_parse_args((array) $instance, $defaults);
        
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Titel:', 'mein-kochbuch-rezepte'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>">
        </p>
        
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_difficulty')); ?>" name="<?php echo esc_attr($this->get_field_name('show_difficulty')); ?>" value="1" <?php checked($instance['show_difficulty'], 1); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_difficulty')); ?>"><?php _e('Schwierigkeitsgrad anzeigen', 'mein-kochbuch-rezepte'); ?></label>
        </p>
        
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_diet')); ?>" name="<?php echo esc_attr($this->get_field_name('show_diet')); ?>" value="1" <?php checked($instance['show_diet'], 1); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_diet')); ?>"><?php _e('Diätvorgaben anzeigen', 'mein-kochbuch-rezepte'); ?></label>
        </p>
        
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_cuisine')); ?>" name="<?php echo esc_attr($this->get_field_name('show_cuisine')); ?>" value="1" <?php checked($instance['show_cuisine'], 1); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_cuisine')); ?>"><?php _e('Küchenart anzeigen', 'mein-kochbuch-rezepte'); ?></label>
        </p>
        
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_season')); ?>" name="<?php echo esc_attr($this->get_field_name('show_season')); ?>" value="1" <?php checked($instance['show_season'], 1); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_season')); ?>"><?php _e('Saison anzeigen', 'mein-kochbuch-rezepte'); ?></label>
        </p>
        
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_prep_time')); ?>" name="<?php echo esc_attr($this->get_field_name('show_prep_time')); ?>" value="1" <?php checked($instance['show_prep_time'], 1); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_prep_time')); ?>"><?php _e('Zubereitungszeit anzeigen', 'mein-kochbuch-rezepte'); ?></label>
        </p>
        
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_servings')); ?>" name="<?php echo esc_attr($this->get_field_name('show_servings')); ?>" value="1" <?php checked($instance['show_servings'], 1); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_servings')); ?>"><?php _e('Portionen anzeigen', 'mein-kochbuch-rezepte'); ?></label>
        </p>
        
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_calories')); ?>" name="<?php echo esc_attr($this->get_field_name('show_calories')); ?>" value="1" <?php checked($instance['show_calories'], 1); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_calories')); ?>"><?php _e('Kalorien anzeigen', 'mein-kochbuch-rezepte'); ?></label>
        </p>
        
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_video')); ?>" name="<?php echo esc_attr($this->get_field_name('show_video')); ?>" value="1" <?php checked($instance['show_video'], 1); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_video')); ?>"><?php _e('Video-Filter anzeigen', 'mein-kochbuch-rezepte'); ?></label>
        </p>
        
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_sorting')); ?>" name="<?php echo esc_attr($this->get_field_name('show_sorting')); ?>" value="1" <?php checked($instance['show_sorting'], 1); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_sorting')); ?>"><?php _e('Sortierung anzeigen', 'mein-kochbuch-rezepte'); ?></label>
        </p>
        <?php
    }
    
    /**
     * Verarbeitung beim Speichern der Widget-Optionen
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['show_difficulty'] = (!empty($new_instance['show_difficulty'])) ? 1 : 0;
        $instance['show_diet'] = (!empty($new_instance['show_diet'])) ? 1 : 0;
        $instance['show_cuisine'] = (!empty($new_instance['show_cuisine'])) ? 1 : 0;
        $instance['show_season'] = (!empty($new_instance['show_season'])) ? 1 : 0;
        $instance['show_prep_time'] = (!empty($new_instance['show_prep_time'])) ? 1 : 0;
        $instance['show_servings'] = (!empty($new_instance['show_servings'])) ? 1 : 0;
        $instance['show_calories'] = (!empty($new_instance['show_calories'])) ? 1 : 0;
        $instance['show_video'] = (!empty($new_instance['show_video'])) ? 1 : 0;
        $instance['show_sorting'] = (!empty($new_instance['show_sorting'])) ? 1 : 0;
        
        return $instance;
    }
}

/**
 * Registrieren des Widgets
 */
function mkr_register_recipe_filter_widget() {
    register_widget('MKR_Recipe_Filter_Widget');
}
add_action('widgets_init', 'mkr_register_recipe_filter_widget');

/**
 * Fügt Einstellungen für die Rezeptfilter hinzu
 */
function mkr_register_recipe_filter_settings() {
    register_setting('reading', 'mkr_recipes_per_page', array(
        'type' => 'integer',
        'description' => __('Anzahl der Rezepte pro Seite', 'mein-kochbuch-rezepte'),
        'sanitize_callback' => 'absint',
        'default' => 12,
    ));
    
    register_setting('reading', 'mkr_apply_user_diet', array(
        'type' => 'string',
        'description' => __('Benutzerdiätvorgaben automatisch anwenden', 'mein-kochbuch-rezepte'),
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'yes',
    ));
    
    register_setting('reading', 'mkr_show_seasonal_recipes', array(
        'type' => 'string',
        'description' => __('Saisonale Rezepte automatisch anzeigen', 'mein-kochbuch-rezepte'),
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'yes',
    ));
    
    add_settings_field(
        'mkr_recipes_per_page',
        __('Rezepte pro Seite', 'mein-kochbuch-rezepte'),
        'mkr_recipes_per_page_callback',
        'reading',
        'default'
    );
    
    add_settings_field(
        'mkr_apply_user_diet',
        __('Benutzerdiätvorgaben', 'mein-kochbuch-rezepte'),
        'mkr_apply_user_diet_callback',
        'reading',
        'default'
    );
    
    add_settings_field(
        'mkr_show_seasonal_recipes',
        __('Saisonale Rezepte', 'mein-kochbuch-rezepte'),
        'mkr_show_seasonal_recipes_callback',
        'reading',
        'default'
    );
}
add_action('admin_init', 'mkr_register_recipe_filter_settings');

/**
 * Callback für die Rezepte-pro-Seite-Einstellung
 */
function mkr_recipes_per_page_callback() {
    $value = get_option('mkr_recipes_per_page', 12);
    ?>
    <input type="number" min="1" id="mkr_recipes_per_page" name="mkr_recipes_per_page" value="<?php echo esc_attr($value); ?>">
    <p class="description"><?php _e('Anzahl der Rezepte, die auf der Archivseite pro Seite angezeigt werden.', 'mein-kochbuch-rezepte'); ?></p>
    <?php
}

/**
 * Callback für die Benutzerdiätvorgaben-Einstellung
 */
function mkr_apply_user_diet_callback() {
    $value = get_option('mkr_apply_user_diet', 'yes');
    ?>
    <select id="mkr_apply_user_diet" name="mkr_apply_user_diet">
        <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Ja', 'mein-kochbuch-rezepte'); ?></option>
        <option value="no" <?php selected($value, 'no'); ?>><?php _e('Nein', 'mein-kochbuch-rezepte'); ?></option>
    </select>
    <p class="description"><?php _e('Automatisch Rezepte basierend auf den Diätvorgaben des angemeldeten Benutzers filtern.', 'mein-kochbuch-rezepte'); ?></p>
    <?php
}

/**
 * Callback für die saisonale-Rezepte-Einstellung
 */
function mkr_show_seasonal_recipes_callback() {
    $value = get_option('mkr_show_seasonal_recipes', 'yes');
    ?>
    <select id="mkr_show_seasonal_recipes" name="mkr_show_seasonal_recipes">
        <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Ja', 'mein-kochbuch-rezepte'); ?></option>
        <option value="no" <?php selected($value, 'no'); ?>><?php _e('Nein', 'mein-kochbuch-rezepte'); ?></option>
    </select>
    <p class="description"><?php _e('Automatisch Rezepte basierend auf der aktuellen Saison anzeigen.', 'mein-kochbuch-rezepte'); ?></p>
    <?php
}