<?php
/**
 * Funktionen für den Mahlzeitenplaner
 */

// Sicherheitsprüfung: Direkter Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registriert den Shortcode für den Mahlzeitenplaner
 */
function mkr_meal_planner_shortcode() {
    ob_start();
    include MKR_PLUGIN_DIR . 'templates/meal-planner-template.php';
    return ob_get_clean();
}
add_shortcode('mkr_meal_planner', 'mkr_meal_planner_shortcode');

/**
 * Erstellt eine Seite für den Mahlzeitenplaner beim Plugin-Aktivieren, falls noch nicht vorhanden
 */
function mkr_create_meal_planner_page() {
    // Prüfen, ob die Seite bereits existiert (nach Slug)
    $planner_page = get_page_by_path('mahlzeitenplaner');
    
    // Wenn die Seite noch nicht existiert, erstellen
    if (!$planner_page) {
        $page_id = wp_insert_post(
            array(
                'post_title'     => __('Mahlzeitenplaner', 'mein-kochbuch-rezepte'),
                'post_name'      => 'mahlzeitenplaner',
                'post_content'   => '[mkr_meal_planner]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed'
            )
        );
        
        update_option('mkr_meal_planner_page_id', $page_id);
    } else {
        update_option('mkr_meal_planner_page_id', $planner_page->ID);
    }
}
register_activation_hook(MKR_PLUGIN_DIR . 'mein-kochbuch-rezepte.php', 'mkr_create_meal_planner_page');

/**
 * Registriert die REST-API-Route für den Mahlzeitenplaner
 */
function mkr_register_meal_planner_routes() {
    register_rest_route('mkr/v1', '/meal-planner', array(
        'methods'  => 'GET',
        'callback' => 'mkr_get_meal_plan',
        'permission_callback' => '__return_true',
    ));
    
    register_rest_route('mkr/v1', '/meal-planner', array(
        'methods'  => 'POST',
        'callback' => 'mkr_save_meal_plan',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
    ));
    
    register_rest_route('mkr/v1', '/meal-planner/recipes', array(
        'methods'  => 'GET',
        'callback' => 'mkr_get_recipes_for_planner',
        'permission_callback' => '__return_true',
    ));
    
    register_rest_route('mkr/v1', '/meal-planner/suggestions', array(
        'methods'  => 'GET',
        'callback' => 'mkr_get_recipe_suggestions',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'mkr_register_meal_planner_routes');

/**
 * Callback für die GET-Anfrage an die Mahlzeitenplaner-API
 *
 * @param WP_REST_Request $request Die Anfrage-Daten
 * @return WP_REST_Response Die Antwort-Daten
 */
function mkr_get_meal_plan($request) {
    // Benutzer-ID und Datumsbereich abrufen
    $user_id = is_user_logged_in() ? get_current_user_id() : 0;
    $start_date = $request->get_param('start_date') ?: date('Y-m-d');
    $num_days = intval($request->get_param('num_days')) ?: 7;
    
    // Wenn der Benutzer nicht angemeldet ist, einen leeren Plan zurückgeben
    if ($user_id === 0) {
        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'meal_plan' => array(),
                'is_logged_in' => false
            )
        ));
    }
    
    // Datum parsen und Zeitraum berechnen
    $start_datetime = new DateTime($start_date);
    $end_datetime = clone $start_datetime;
    $end_datetime->modify('+' . ($num_days - 1) . ' days');
    
    // Mahlzeitenplan aus der Datenbank holen
    $meal_plan = get_user_meta($user_id, 'mkr_meal_plan', true);
    
    if (!is_array($meal_plan)) {
        $meal_plan = array();
    }
    
    // Nur die Mahlzeiten im angeforderten Zeitraum zurückgeben
    $filtered_plan = array();
    $current_date = clone $start_datetime;
    
    for ($i = 0; $i < $num_days; $i++) {
        $date_key = $current_date->format('Y-m-d');
        
        if (isset($meal_plan[$date_key])) {
            $filtered_plan[$date_key] = $meal_plan[$date_key];
        } else {
            $filtered_plan[$date_key] = array(
                'breakfast' => array(),
                'lunch' => array(),
                'dinner' => array(),
                'snack' => array()
            );
        }
        
        $current_date->modify('+1 day');
    }
    
    return rest_ensure_response(array(
        'success' => true,
        'data' => array(
            'meal_plan' => $filtered_plan,
            'is_logged_in' => true,
            'start_date' => $start_datetime->format('Y-m-d'),
            'end_date' => $end_datetime->format('Y-m-d')
        )
    ));
}

/**
 * Callback für die POST-Anfrage an die Mahlzeitenplaner-API
 *
 * @param WP_REST_Request $request Die Anfrage-Daten
 * @return WP_REST_Response Die Antwort-Daten
 */
function mkr_save_meal_plan($request) {
    $user_id = get_current_user_id();
    
    if ($user_id === 0) {
        return new WP_Error('not_logged_in', __('Benutzer ist nicht angemeldet.', 'mein-kochbuch-rezepte'), array('status' => 401));
    }
    
    $meal_plan_data = $request->get_param('meal_plan');
    
    if (!is_array($meal_plan_data)) {
        return new WP_Error('invalid_data', __('Ungültige Daten für den Mahlzeitenplan.', 'mein-kochbuch-rezepte'), array('status' => 400));
    }
    
    // Bestehenden Mahlzeitenplan abrufen und aktualisieren
    $existing_meal_plan = get_user_meta($user_id, 'mkr_meal_plan', true);
    
    if (!is_array($existing_meal_plan)) {
        $existing_meal_plan = array();
    }
    
    // Neue Daten mit dem bestehenden Plan zusammenführen
    $updated_meal_plan = array_merge($existing_meal_plan, $meal_plan_data);
    
    // Plan speichern
    update_user_meta($user_id, 'mkr_meal_plan', $updated_meal_plan);
    
    // Erfolg zurückmelden
    return rest_ensure_response(array(
        'success' => true,
        'message' => __('Mahlzeitenplan erfolgreich gespeichert.', 'mein-kochbuch-rezepte')
    ));
}

/**
 * Callback für die Rezeptliste für den Mahlzeitenplaner
 *
 * @param WP_REST_Request $request Die Anfrage-Daten
 * @return WP_REST_Response Die Antwort-Daten
 */
function mkr_get_recipes_for_planner($request) {
    $search = $request->get_param('search') ?: '';
    $per_page = intval($request->get_param('per_page')) ?: 10;
    $page = intval($request->get_param('page')) ?: 1;
    
    // Query-Parameter für die Rezeptsuche
    $args = array(
        'post_type' => 'recipe',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    // Suchbegriff hinzufügen, falls vorhanden
    if (!empty($search)) {
        $args['s'] = $search;
    }
    
    // Taxonomie-Filter, falls vorhanden
    $taxonomies = array('difficulty', 'diet', 'cuisine', 'season');
    $tax_query = array();
    
    foreach ($taxonomies as $taxonomy) {
        $term_slug = $request->get_param($taxonomy);
        
        if (!empty($term_slug)) {
            $tax_query[] = array(
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $term_slug
            );
        }
    }
    
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    // Rezepte abfragen
    $query = new WP_Query($args);
    $recipes = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            $recipe_id = get_the_ID();
            $prep_time = get_post_meta($recipe_id, '_mkr_prep_time', true);
            $cook_time = get_post_meta($recipe_id, '_mkr_cook_time', true);
            $total_time = get_post_meta($recipe_id, '_mkr_total_time', true) ?: ($prep_time + $cook_time);
            
            $recipes[] = array(
                'id' => $recipe_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url($recipe_id, 'thumbnail'),
                'prep_time' => $prep_time,
                'cook_time' => $cook_time,
                'total_time' => $total_time,
                'difficulty' => mkr_get_taxonomy_terms($recipe_id, 'difficulty'),
                'cuisine' => mkr_get_taxonomy_terms($recipe_id, 'cuisine')
            );
        }
        
        wp_reset_postdata();
    }
    
    // Pagination-Informationen
    $total_recipes = $query->found_posts;
    $total_pages = ceil($total_recipes / $per_page);
    
    return rest_ensure_response(array(
        'success' => true,
        'data' => array(
            'recipes' => $recipes,
            'pagination' => array(
                'total' => $total_recipes,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $total_pages
            )
        )
    ));
}

/**
 * Callback für Rezeptvorschläge für den Mahlzeitenplaner
 *
 * @param WP_REST_Request $request Die Anfrage-Daten
 * @return WP_REST_Response Die Antwort-Daten
 */
function mkr_get_recipe_suggestions($request) {
    $meal_type = $request->get_param('meal_type') ?: 'all';
    $count = intval($request->get_param('count')) ?: 5;
    
    // Saisonale Rezepte bevorzugen
    $current_month = date('n');
    $season_terms = array();
    
    if ($current_month >= 3 && $current_month <= 5) {
        $season_terms[] = 'frühling'; // Frühling
    } elseif ($current_month >= 6 && $current_month <= 8) {
        $season_terms[] = 'sommer'; // Sommer
    } elseif ($current_month >= 9 && $current_month <= 11) {
        $season_terms[] = 'herbst'; // Herbst
    } else {
        $season_terms[] = 'winter'; // Winter
    }
    
    // Zur Sicherheit auch "ganzjährig" hinzufügen
    $season_terms[] = 'ganzjährig';
    
    // Query-Parameter für die Rezeptsuche
    $args = array(
        'post_type' => 'recipe',
        'posts_per_page' => $count,
        'post_status' => 'publish',
        'orderby' => 'rand', // Zufällige Auswahl
        'tax_query' => array(
            array(
                'taxonomy' => 'season',
                'field' => 'slug',
                'terms' => $season_terms,
                'operator' => 'IN'
            )
        )
    );
    
    // Nach Mahlzeittyp filtern, falls angegeben
    if ($meal_type !== 'all') {
        // Hier könnte eine Taxonomie oder ein benutzerdefiniertes Feld verwendet werden
        // Beispiel für ein benutzerdefiniertes Feld
        $args['meta_query'] = array(
            array(
                'key' => '_mkr_meal_type',
                'value' => $meal_type,
                'compare' => '=',
            )
        );
    }
    
    // Benutzereinstellungen berücksichtigen, falls angemeldet
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $user_diet = get_user_meta($user_id, 'mkr_diet', true);
        
        if (!empty($user_diet)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'diet',
                'field' => 'slug',
                'terms' => $user_diet,
            );
        }
    }
    
    // Rezepte abfragen
    $query = new WP_Query($args);
    $recipes = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            $recipe_id = get_the_ID();
            
            $recipes[] = array(
                'id' => $recipe_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url($recipe_id, 'thumbnail'),
                'prep_time' => get_post_meta($recipe_id, '_mkr_prep_time', true),
                'cook_time' => get_post_meta($recipe_id, '_mkr_cook_time', true),
                'total_time' => get_post_meta($recipe_id, '_mkr_total_time', true),
                'difficulty' => mkr_get_taxonomy_terms($recipe_id, 'difficulty'),
                'cuisine' => mkr_get_taxonomy_terms($recipe_id, 'cuisine')
            );
        }
        
        wp_reset_postdata();
    }
    
    return rest_ensure_response(array(
        'success' => true,
        'data' => $recipes
    ));
}

/**
 * Hilfsfunktion: Holt die Taxonomie-Begriffe eines Beitrags
 *
 * @param int $post_id Die Beitrags-ID
 * @param string $taxonomy Die Taxonomie
 * @return array Array mit Begriffs-Informationen
 */
function mkr_get_taxonomy_terms($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    $result = array();
    
    if ($terms && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            $result[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug
            );
        }
    }
    
    return $result;
}