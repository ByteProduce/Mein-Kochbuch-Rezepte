<?php
/**
 * Schema.org Markup für Rezepte
 * 
 * Fügt strukturierte Daten zu Rezepten hinzu, um die SEO zu verbessern
 * und Rich Snippets in Suchmaschinen zu ermöglichen.
 */

// Sicherheitsprüfung: Direkter Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fügt das Schema.org-Markup für Rezepte im Header hinzu
 */
add_action('wp_head', 'mkr_add_recipe_schema');
function mkr_add_recipe_schema() {
    // Nur auf Einzelseiten von Rezepten ausführen
    if (!is_singular('recipe')) {
        return;
    }
    
    $post_id = get_the_ID();
    
    // Rezeptdaten abrufen
    $recipe_data = mkr_get_recipe_schema_data($post_id);
    
    // Schema als JSON-LD ausgeben
    if (!empty($recipe_data)) {
        echo '<script type="application/ld+json">' . wp_json_encode($recipe_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
}

/**
 * Erstellt die strukturierten Daten für ein Rezept
 *
 * @param int $post_id Die ID des Rezept-Beitrags
 * @return array Die strukturierten Daten gemäß Schema.org
 */
function mkr_get_recipe_schema_data($post_id) {
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'recipe') {
        return [];
    }
    
    // Grundlegende Metadaten abrufen
    $ingredients = get_post_meta($post_id, '_mkr_ingredients', true);
    $ingredients = maybe_unserialize($ingredients);
    $instructions = get_post_meta($post_id, '_mkr_instructions', true);
    $videos = get_post_meta($post_id, '_mkr_videos', true);
    $total_calories = get_post_meta($post_id, '_mkr_total_calories', true);
    $total_proteins = get_post_meta($post_id, '_mkr_total_proteins', true);
    $total_fats = get_post_meta($post_id, '_mkr_total_fats', true);
    $total_carbs = get_post_meta($post_id, '_mkr_total_carbs', true);
    $servings = get_post_meta($post_id, '_mkr_servings', true) ?: 1;
    $prep_time = get_post_meta($post_id, '_mkr_prep_time', true);
    $cook_time = get_post_meta($post_id, '_mkr_cook_time', true);
    $total_time = get_post_meta($post_id, '_mkr_total_time', true);
    
    // Rezept-Kategorien/-Taxonomien abrufen
    $recipe_category = [];
    $recipe_cuisine = [];
    
    $categories = get_the_terms($post_id, 'difficulty');
    if ($categories && !is_wp_error($categories)) {
        foreach ($categories as $category) {
            $recipe_category[] = $category->name;
        }
    }
    
    $diets = get_the_terms($post_id, 'diet');
    if ($diets && !is_wp_error($diets)) {
        foreach ($diets as $diet) {
            $recipe_category[] = $diet->name;
        }
    }
    
    $cuisines = get_the_terms($post_id, 'cuisine');
    if ($cuisines && !is_wp_error($cuisines)) {
        foreach ($cuisines as $cuisine) {
            $recipe_cuisine[] = $cuisine->name;
        }
    }
    
    // Zeiten in ISO 8601-Dauer formatieren
    $prep_time_iso = $prep_time ? 'PT' . $prep_time . 'M' : '';
    $cook_time_iso = $cook_time ? 'PT' . $cook_time . 'M' : '';
    $total_time_iso = $total_time ? 'PT' . $total_time . 'M' : '';
    
    // Autor abrufen
    $author = get_the_author_meta('display_name', $post->post_author);
    $author_url = get_author_posts_url($post->post_author);
    
    // Basis-Schema erstellen
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Recipe",
        "mainEntityOfPage" => [
            "@type" => "WebPage",
            "@id" => get_permalink($post_id)
        ],
        "name" => $post->post_title,
        "headline" => $post->post_title,
        "author" => [
            "@type" => "Person",
            "name" => $author,
            "url" => $author_url
        ],
        "publisher" => [
            "@type" => "Organization",
            "name" => get_bloginfo('name'),
            "logo" => [
                "@type" => "ImageObject",
                "url" => get_site_icon_url(512) ?: get_site_icon_url()
            ]
        ],
        "datePublished" => get_the_date('c', $post_id),
        "dateModified" => get_the_modified_date('c', $post_id),
        "description" => get_the_excerpt($post_id) ?: wp_trim_words($post->post_content, 55, '...'),
    ];
    
    // Hauptbild hinzufügen
    if (has_post_thumbnail($post_id)) {
        $image_id = get_post_thumbnail_id($post_id);
        $image_url = wp_get_attachment_image_url($image_id, 'full');
        $image_meta = wp_get_attachment_metadata($image_id);
        
        if ($image_url && $image_meta) {
            $schema['image'] = [
                "@type" => "ImageObject",
                "url" => $image_url,
                "width" => $image_meta['width'],
                "height" => $image_meta['height']
            ];
        } else {
            $schema['image'] = get_the_post_thumbnail_url($post_id, 'full');
        }
    }
    
    // Zutatenliste hinzufügen
    if (is_array($ingredients) && !empty($ingredients)) {
        $schema['recipeIngredient'] = [];
        
        foreach ($ingredients as $ingredient) {
            $formatted_ingredient = '';
            
            if (isset($ingredient['amount']) && $ingredient['amount'] !== '') {
                $formatted_ingredient .= $ingredient['amount'] . ' ';
            }
            
            if (isset($ingredient['unit']) && $ingredient['unit'] !== '') {
                $formatted_ingredient .= $ingredient['unit'] . ' ';
            }
            
            if (isset($ingredient['name']) && $ingredient['name'] !== '') {
                $formatted_ingredient .= $ingredient['name'];
            }
            
            if ($formatted_ingredient !== '') {
                $schema['recipeIngredient'][] = trim($formatted_ingredient);
            }
        }
    }
    
    // Zubereitungsanleitung hinzufügen
    if ($instructions) {
        $schema['recipeInstructions'] = [];
        
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
        
        // Schritte ins Schema übernehmen
        foreach ($steps as $index => $step) {
            $step_text = strip_tags($step);
            
            if (!empty($step_text)) {
                $schema['recipeInstructions'][] = [
                    "@type" => "HowToStep",
                    "text" => $step_text,
                    "position" => $index + 1
                ];
            }
        }
    }
    
    // Video hinzufügen, falls vorhanden
    if (is_array($videos) && !empty($videos)) {
        $video_url = $videos[0]; // Das erste Video verwenden
        $video_id = '';
        
        // YouTube-Video-ID extrahieren
        if (preg_match('/(?:youtube\.com\/(?:[^\/\n\s]+\/\s*[^\/\n\s]+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video_url, $matches)) {
            $video_id = $matches[1];
            
            if ($video_id) {
                $schema['video'] = [
                    "@type" => "VideoObject",
                    "name" => $post->post_title,
                    "description" => get_the_excerpt($post_id) ?: wp_trim_words($post->post_content, 55, '...'),
                    "thumbnailUrl" => "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg",
                    "contentUrl" => "https://www.youtube.com/watch?v={$video_id}",
                    "embedUrl" => "https://www.youtube.com/embed/{$video_id}",
                    "uploadDate" => get_the_date('c', $post_id)
                ];
            }
        }
    }
    
    // Zeiten hinzufügen
    if ($prep_time_iso) {
        $schema['prepTime'] = $prep_time_iso;
    }
    
    if ($cook_time_iso) {
        $schema['cookTime'] = $cook_time_iso;
    }
    
    if ($total_time_iso) {
        $schema['totalTime'] = $total_time_iso;
    }
    
    // Portionen und Nährwertinformationen hinzufügen
    if ($servings) {
        $schema['recipeYield'] = intval($servings);
    }
    
    if ($total_calories || $total_proteins || $total_fats || $total_carbs) {
        $schema['nutrition'] = [
            "@type" => "NutritionInformation"
        ];
        
        if ($total_calories) {
            $calories_per_serving = round($total_calories / $servings, 1);
            $schema['nutrition']['calories'] = $calories_per_serving . ' kcal';
        }
        
        if ($total_proteins) {
            $proteins_per_serving = round($total_proteins / $servings, 1);
            $schema['nutrition']['proteinContent'] = $proteins_per_serving . ' g';
        }
        
        if ($total_fats) {
            $fats_per_serving = round($total_fats / $servings, 1);
            $schema['nutrition']['fatContent'] = $fats_per_serving . ' g';
        }
        
        if ($total_carbs) {
            $carbs_per_serving = round($total_carbs / $servings, 1);
            $schema['nutrition']['carbohydrateContent'] = $carbs_per_serving . ' g';
        }
    }
    
    // Kategorien und Küchen hinzufügen
    if (!empty($recipe_category)) {
        $schema['recipeCategory'] = $recipe_category;
    }
    
    if (!empty($recipe_cuisine)) {
        $schema['recipeCuisine'] = $recipe_cuisine;
    }
    
    // Bewertungen einbinden, falls vorhanden
    if (comments_open($post_id) || get_comments_number($post_id) > 0) {
        $reviews = mkr_get_recipe_reviews($post_id);
        
        if ($reviews) {
            $schema['review'] = $reviews;
            
            // Aggregierte Bewertung berechnen
            $ratings = array_column($reviews, 'reviewRating');
            if (!empty($ratings)) {
                $rating_values = array_column($ratings, 'ratingValue');
                $average_rating = array_sum($rating_values) / count($rating_values);
                
                $schema['aggregateRating'] = [
                    "@type" => "AggregateRating",
                    "ratingValue" => round($average_rating, 1),
                    "ratingCount" => count($ratings),
                    "bestRating" => 5,
                    "worstRating" => 1
                ];
            }
        }
    }
    
    /**
     * Filter für Schema-Daten vor der Ausgabe
     * 
     * @param array $schema Die strukturierten Daten
     * @param int $post_id Die ID des Rezept-Beitrags
     */
    return apply_filters('mkr_recipe_schema_data', $schema, $post_id);
}

/**
 * Ruft die Bewertungen (Kommentare) eines Rezepts für Schema.org ab
 *
 * @param int $post_id Die ID des Rezept-Beitrags
 * @return array|false Die Bewertungen im Schema.org-Format oder false, wenn keine vorhanden
 */
function mkr_get_recipe_reviews($post_id) {
    $comments = get_comments([
        'post_id' => $post_id,
        'status' => 'approve',
    ]);
    
    if (empty($comments)) {
        return false;
    }
    
    $reviews = [];
    
    foreach ($comments as $comment) {
        // Bewertung aus dem Kommentar extrahieren (falls ein Bewertungssystem verwendet wird)
        $rating = get_comment_meta($comment->comment_ID, 'rating', true);
        
        // Wenn keine Bewertung vorhanden ist, überspringen oder Standardwert verwenden
        if (!$rating) {
            continue; // oder: $rating = 5; // Standardwert
        }
        
        $reviews[] = [
            "@type" => "Review",
            "author" => [
                "@type" => "Person",
                "name" => $comment->comment_author
            ],
            "datePublished" => get_comment_date('c', $comment->comment_ID),
            "reviewBody" => $comment->comment_content,
            "reviewRating" => [
                "@type" => "Rating",
                "ratingValue" => intval($rating),
                "bestRating" => 5,
                "worstRating" => 1
            ]
        ];
    }
    
    return !empty($reviews) ? $reviews : false;
}

/**
 * Fügt das Schema.org-Markup für Zutaten im Header hinzu
 */
add_action('wp_head', 'mkr_add_ingredient_schema');
function mkr_add_ingredient_schema() {
    // Nur auf Einzelseiten von Zutaten ausführen
    if (!is_singular('ingredient')) {
        return;
    }
    
    $post_id = get_the_ID();
    $post = get_post($post_id);
    
    if (!$post) {
        return;
    }
    
    // Zutatendaten abrufen
    $calories = get_post_meta($post_id, '_mkr_calories_per_100g', true);
    $proteins = get_post_meta($post_id, '_mkr_proteins_per_100g', true);
    $fats = get_post_meta($post_id, '_mkr_fats_per_100g', true);
    $carbs = get_post_meta($post_id, '_mkr_carbs_per_100g', true);
    
    // Basis-Schema erstellen
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "NutritionInformation",
        "name" => $post->post_title,
        "description" => get_the_excerpt($post_id) ?: wp_trim_words($post->post_content, 55, '...'),
    ];
    
    // Nährwertinformationen hinzufügen
    if ($calories) {
        $schema['calories'] = $calories . ' kcal';
    }
    
    if ($proteins) {
        $schema['proteinContent'] = $proteins . ' g';
    }
    
    if ($fats) {
        $schema['fatContent'] = $fats . ' g';
    }
    
    if ($carbs) {
        $schema['carbohydrateContent'] = $carbs . ' g';
    }
    
    // Hauptbild hinzufügen
    if (has_post_thumbnail($post_id)) {
        $schema['image'] = get_the_post_thumbnail_url($post_id, 'full');
    }
    
    // Schema als JSON-LD ausgeben
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}

/**
 * Registriert die Schema.org-Daten für die Rezeptsuche
 */
add_action('wp_head', 'mkr_add_recipe_search_schema');
function mkr_add_recipe_search_schema() {
    // Nur auf der Rezeptarchivseite oder der Suchseite ausführen
    if (!is_post_type_archive('recipe') && !is_search()) {
        return;
    }
    
    $site_url = trailingslashit(get_home_url());
    
    // Basis-Schema erstellen
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "WebSite",
        "url" => $site_url,
        "potentialAction" => [
            "@type" => "SearchAction",
            "target" => $site_url . "?s={search_term_string}&post_type=recipe",
            "query-input" => "required name=search_term_string"
        ]
    ];
    
    // Schema als JSON-LD ausgeben
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}

/**
 * Fügt das Schema.org-Markup für BreadcrumbList im Header hinzu
 */
add_action('wp_head', 'mkr_add_breadcrumb_schema');
function mkr_add_breadcrumb_schema() {
    // Nur auf Einzelseiten ausführen
    if (!is_singular()) {
        return;
    }
    
    global $post;
    
    // Basis-Schema erstellen
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => []
    ];
    
    // Startseite als erstes Element hinzufügen
    $schema['itemListElement'][] = [
        "@type" => "ListItem",
        "position" => 1,
        "name" => get_bloginfo('name'),
        "item" => get_home_url()
    ];
    
    // Position für weitere Elemente
    $position = 2;
    
    // Beitragstyp-Archive hinzufügen
    $post_type = get_post_type();
    $post_type_obj = get_post_type_object($post_type);
    
    if ($post_type_obj) {
        $schema['itemListElement'][] = [
            "@type" => "ListItem",
            "position" => $position++,
            "name" => $post_type_obj->labels->name,
            "item" => get_post_type_archive_link($post_type)
        ];
    }
    
    // Taxonomien/Kategorien hinzufügen
    $taxonomies = get_object_taxonomies($post_type, 'objects');
    
    foreach ($taxonomies as $taxonomy) {
        if ($taxonomy->hierarchical) {
            $terms = get_the_terms($post->ID, $taxonomy->name);
            
            if ($terms && !is_wp_error($terms)) {
                $primary_term = $terms[0]; // Erstes Element als primär betrachten
                
                $schema['itemListElement'][] = [
                    "@type" => "ListItem",
                    "position" => $position++,
                    "name" => $primary_term->name,
                    "item" => get_term_link($primary_term)
                ];
            }
        }
    }
    
    // Aktueller Beitrag als letztes Element hinzufügen
    $schema['itemListElement'][] = [
        "@type" => "ListItem",
        "position" => $position,
        "name" => get_the_title(),
        "item" => get_permalink()
    ];
    
    // Schema als JSON-LD ausgeben
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}