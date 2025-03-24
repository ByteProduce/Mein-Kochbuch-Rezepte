<?php
/**
 * Funktionen für Import und Export von Rezepten
 */

// Sicherheitsprüfung: Direkter Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zeigt die Import-Seite im Admin-Bereich an
 */
function mkr_import_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Sie haben nicht genügend Berechtigungen, um auf diese Seite zuzugreifen.', 'mein-kochbuch-rezepte'));
    }
    
    // Meldungsvariablen
    $success_message = '';
    $error_message = '';
    
    // JSON-Import
    if (isset($_POST['mkr_import_json']) && isset($_FILES['mkr_json_file']) && $_FILES['mkr_json_file']['error'] == 0) {
        // Nonce prüfen
        if (!isset($_POST['mkr_import_nonce']) || !wp_verify_nonce($_POST['mkr_import_nonce'], 'mkr_import_action')) {
            $error_message = __('Sicherheitsprüfung fehlgeschlagen. Bitte versuchen Sie es erneut.', 'mein-kochbuch-rezepte');
        } else {
            $file = $_FILES['mkr_json_file']['tmp_name'];
            
            // Datei-Typ überprüfen
            $file_type = wp_check_filetype(basename($_FILES['mkr_json_file']['name']), ['json' => 'application/json']);
            if ($file_type['ext'] !== 'json') {
                $error_message = __('Bitte laden Sie eine gültige JSON-Datei hoch.', 'mein-kochbuch-rezepte');
            } else {
                $json = file_get_contents($file);
                $recipes = json_decode($json, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $error_message = __('Ungültige JSON-Datei: ', 'mein-kochbuch-rezepte') . json_last_error_msg();
                } elseif (empty($recipes) || !is_array($recipes)) {
                    $error_message = __('Die JSON-Datei enthält keine gültigen Rezeptdaten.', 'mein-kochbuch-rezepte');
                } else {
                    $imported_count = 0;
                    $updated_count = 0;
                    
                    foreach ($recipes as $recipe) {
                        // Überprüfen, ob alle erforderlichen Felder vorhanden sind
                        if (empty($recipe['title'])) {
                            continue;
                        }
                        
                        // Prüfen, ob das Rezept bereits existiert
                        $existing_recipe_id = 0;
                        $existing_recipes = get_posts([
                            'post_type' => 'recipe',
                            'title' => $recipe['title'],
                            'post_status' => 'any',
                            'posts_per_page' => 1,
                            'fields' => 'ids'
                        ]);
                        
                        if (!empty($existing_recipes)) {
                            $existing_recipe_id = $existing_recipes[0];
                        }
                        
                        $post_args = [
                            'post_title' => sanitize_text_field($recipe['title']),
                            'post_content' => isset($recipe['content']) ? wp_kses_post($recipe['content']) : '',
                            'post_type' => 'recipe',
                            'post_status' => 'publish',
                        ];
                        
                        if ($existing_recipe_id) {
                            $post_args['ID'] = $existing_recipe_id;
                            $post_id = wp_update_post($post_args);
                            $updated_count++;
                        } else {
                            $post_id = wp_insert_post($post_args);
                            $imported_count++;
                        }
                        
                        if ($post_id && !is_wp_error($post_id)) {
                            // Meta-Daten aktualisieren/hinzufügen
                            if (isset($recipe['ingredients']) && is_array($recipe['ingredients'])) {
                                update_post_meta($post_id, '_mkr_ingredients', $recipe['ingredients']);
                            }
                            
                            if (isset($recipe['utensils']) && is_array($recipe['utensils'])) {
                                update_post_meta($post_id, '_mkr_utensils', $recipe['utensils']);
                            }
                            
                            if (isset($recipe['instructions'])) {
                                update_post_meta($post_id, '_mkr_instructions', wp_kses_post($recipe['instructions']));
                            }
                            
                            if (isset($recipe['videos']) && is_array($recipe['videos'])) {
                                update_post_meta($post_id, '_mkr_videos', array_map('esc_url_raw', $recipe['videos']));
                            }
                            
                            if (isset($recipe['servings'])) {
                                update_post_meta($post_id, '_mkr_servings', intval($recipe['servings']));
                            }
                            
                            if (isset($recipe['prep_time'])) {
                                update_post_meta($post_id, '_mkr_prep_time', intval($recipe['prep_time']));
                            }
                            
                            if (isset($recipe['cook_time'])) {
                                update_post_meta($post_id, '_mkr_cook_time', intval($recipe['cook_time']));
                            }
                            
                            if (isset($recipe['total_time'])) {
                                update_post_meta($post_id, '_mkr_total_time', intval($recipe['total_time']));
                            }
                            
                            // Taxonomien setzen
                            if (isset($recipe['taxonomies']) && is_array($recipe['taxonomies'])) {
                                foreach ($recipe['taxonomies'] as $taxonomy => $terms) {
                                    if (taxonomy_exists($taxonomy) && is_array($terms)) {
                                        wp_set_object_terms($post_id, $terms, $taxonomy);
                                    }
                                }
                            }
                            
                            // Beitragsbild setzen, falls URL angegeben
                            if (isset($recipe['featured_image_url']) && !empty($recipe['featured_image_url'])) {
                                mkr_set_featured_image_from_url($post_id, esc_url_raw($recipe['featured_image_url']));
                            }
                        }
                    }
                    
                    $success_message = sprintf(
                        __('%d Rezepte importiert, %d Rezepte aktualisiert.', 'mein-kochbuch-rezepte'),
                        $imported_count,
                        $updated_count
                    );
                }
            }
        }
    }
    
    // CSV-Import
    if (isset($_POST['mkr_import_csv']) && isset($_FILES['mkr_csv_file']) && $_FILES['mkr_csv_file']['error'] == 0) {
        // Nonce prüfen
        if (!isset($_POST['mkr_import_nonce']) || !wp_verify_nonce($_POST['mkr_import_nonce'], 'mkr_import_action')) {
            $error_message = __('Sicherheitsprüfung fehlgeschlagen. Bitte versuchen Sie es erneut.', 'mein-kochbuch-rezepte');
        } else {
            $file = $_FILES['mkr_csv_file']['tmp_name'];
            
            // Datei-Typ überprüfen
            $file_type = wp_check_filetype(basename($_FILES['mkr_csv_file']['name']), ['csv' => 'text/csv']);
            if ($file_type['ext'] !== 'csv') {
                $error_message = __('Bitte laden Sie eine gültige CSV-Datei hoch.', 'mein-kochbuch-rezepte');
            } else {
                // CSV-Datei verarbeiten
                if (($handle = fopen($file, "r")) !== FALSE) {
                    $header = fgetcsv($handle, 0, ',');
                    $imported_count = 0;
                    $updated_count = 0;
                    
                    // Spaltenindizes bestimmen
                    $column_indexes = array_flip($header);
                    
                    // Prüfen, ob mindestens ein Titel vorhanden ist
                    if (!isset($column_indexes['title'])) {
                        $error_message = __('Die CSV-Datei muss eine "title"-Spalte enthalten.', 'mein-kochbuch-rezepte');
                        fclose($handle);
                    } else {
                        while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
                            $title = isset($data[$column_indexes['title']]) ? $data[$column_indexes['title']] : '';
                            
                            if (empty($title)) {
                                continue;
                            }
                            
                            // Prüfen, ob das Rezept bereits existiert
                            $existing_recipe_id = 0;
                            $existing_recipes = get_posts([
                                'post_type' => 'recipe',
                                'title' => $title,
                                'post_status' => 'any',
                                'posts_per_page' => 1,
                                'fields' => 'ids'
                            ]);
                            
                            if (!empty($existing_recipes)) {
                                $existing_recipe_id = $existing_recipes[0];
                            }
                            
                            $post_args = [
                                'post_title' => sanitize_text_field($title),
                                'post_content' => isset($column_indexes['content']) ? wp_kses_post($data[$column_indexes['content']]) : '',
                                'post_type' => 'recipe',
                                'post_status' => 'publish',
                            ];
                            
                            if ($existing_recipe_id) {
                                $post_args['ID'] = $existing_recipe_id;
                                $post_id = wp_update_post($post_args);
                                $updated_count++;
                            } else {
                                $post_id = wp_insert_post($post_args);
                                $imported_count++;
                            }
                            
                            if ($post_id && !is_wp_error($post_id)) {
                                // Meta-Daten aktualisieren/hinzufügen
                                if (isset($column_indexes['servings'])) {
                                    update_post_meta($post_id, '_mkr_servings', intval($data[$column_indexes['servings']]));
                                }
                                
                                if (isset($column_indexes['prep_time'])) {
                                    update_post_meta($post_id, '_mkr_prep_time', intval($data[$column_indexes['prep_time']]));
                                }
                                
                                if (isset($column_indexes['cook_time'])) {
                                    update_post_meta($post_id, '_mkr_cook_time', intval($data[$column_indexes['cook_time']]));
                                }
                                
                                if (isset($column_indexes['total_time'])) {
                                    update_post_meta($post_id, '_mkr_total_time', intval($data[$column_indexes['total_time']]));
                                }
                                
                                if (isset($column_indexes['instructions'])) {
                                    update_post_meta($post_id, '_mkr_instructions', wp_kses_post($data[$column_indexes['instructions']]));
                                }
                                
                                // Zutaten verarbeiten, falls vorhanden
                                if (isset($column_indexes['ingredients'])) {
                                    $ingredients_raw = $data[$column_indexes['ingredients']];
                                    $ingredients_array = array_map('trim', explode(';', $ingredients_raw));
                                    $ingredients = [];
                                    
                                    foreach ($ingredients_array as $ingredient_str) {
                                        $matches = [];
                                        // Format: Menge Einheit Name
                                        if (preg_match('/^(\d+(?:\.\d+)?)\s+(\w+)\s+(.+)$/', $ingredient_str, $matches)) {
                                            $ingredients[] = [
                                                'amount' => $matches[1],
                                                'unit' => $matches[2],
                                                'name' => $matches[3]
                                            ];
                                        }
                                    }
                                    
                                    if (!empty($ingredients)) {
                                        update_post_meta($post_id, '_mkr_ingredients', $ingredients);
                                    }
                                }
                                
                                // Utensilien verarbeiten, falls vorhanden
                                if (isset($column_indexes['utensils'])) {
                                    $utensils_raw = $data[$column_indexes['utensils']];
                                    $utensils = array_map('trim', explode(';', $utensils_raw));
                                    
                                    if (!empty($utensils)) {
                                        update_post_meta($post_id, '_mkr_utensils', $utensils);
                                    }
                                }
                                
                                // Taxonomien verarbeiten
                                $taxonomies = ['difficulty', 'diet', 'cuisine', 'season'];
                                foreach ($taxonomies as $taxonomy) {
                                    if (isset($column_indexes[$taxonomy])) {
                                        $terms = array_map('trim', explode(';', $data[$column_indexes[$taxonomy]]));
                                        if (!empty($terms)) {
                                            wp_set_object_terms($post_id, $terms, $taxonomy);
                                        }
                                    }
                                }
                            }
                        }
                        
                        fclose($handle);
                        $success_message = sprintf(
                            __('%d Rezepte importiert, %d Rezepte aktualisiert.', 'mein-kochbuch-rezepte'),
                            $imported_count,
                            $updated_count
                        );
                    }
                } else {
                    $error_message = __('Die CSV-Datei konnte nicht geöffnet werden.', 'mein-kochbuch-rezepte');
                }
            }
        }
    }
    
    // Import-Formular anzeigen
    ?>
    <div class="wrap">
        <h1><?php _e('Rezept-Import', 'mein-kochbuch-rezepte'); ?></h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($success_message); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2><?php _e('JSON-Import', 'mein-kochbuch-rezepte'); ?></h2>
            <p><?php _e('Importieren Sie Rezepte aus einer JSON-Datei.', 'mein-kochbuch-rezepte'); ?></p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('mkr_import_action', 'mkr_import_nonce'); ?>
                <input type="file" name="mkr_json_file" accept=".json" required />
                <p class="description"><?php _e('Akzeptierte Dateitypen: .json', 'mein-kochbuch-rezepte'); ?></p>
                <p><input type="submit" name="mkr_import_json" class="button button-primary" value="<?php _e('JSON importieren', 'mein-kochbuch-rezepte'); ?>" /></p>
            </form>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2><?php _e('CSV-Import', 'mein-kochbuch-rezepte'); ?></h2>
            <p><?php _e('Importieren Sie Rezepte aus einer CSV-Datei.', 'mein-kochbuch-rezepte'); ?></p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('mkr_import_action', 'mkr_import_nonce'); ?>
                <input type="file" name="mkr_csv_file" accept=".csv" required />
                <p class="description"><?php _e('Akzeptierte Dateitypen: .csv', 'mein-kochbuch-rezepte'); ?></p>
                <p><?php _e('Die CSV-Datei sollte folgende Spalten haben: title (erforderlich), content, servings, prep_time, cook_time, total_time, instructions, ingredients, utensils, difficulty, diet, cuisine, season.', 'mein-kochbuch-rezepte'); ?></p>
                <p><input type="submit" name="mkr_import_csv" class="button button-primary" value="<?php _e('CSV importieren', 'mein-kochbuch-rezepte'); ?>" /></p>
            </form>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2><?php _e('Formatvorlage', 'mein-kochbuch-rezepte'); ?></h2>
            <p><?php _e('Laden Sie eine Beispieldatei herunter, um das richtige Format zu sehen.', 'mein-kochbuch-rezepte'); ?></p>
            <p><a href="<?php echo esc_url(MKR_PLUGIN_URL . 'assets/example/recipe-import-template.json'); ?>" class="button"><?php _e('JSON-Vorlage herunterladen', 'mein-kochbuch-rezepte'); ?></a></p>
            <p><a href="<?php echo esc_url(MKR_PLUGIN_URL . 'assets/example/recipe-import-template.csv'); ?>" class="button"><?php _e('CSV-Vorlage herunterladen', 'mein-kochbuch-rezepte'); ?></a></p>
        </div>
    </div>
    <?php
}

/**
 * Zeigt die Export-Seite im Admin-Bereich an
 */
function mkr_export_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Sie haben nicht genügend Berechtigungen, um auf diese Seite zuzugreifen.', 'mein-kochbuch-rezepte'));
    }
    
    // Meldungsvariablen
    $success_message = '';
    $error_message = '';
    
    // Export-Anfrage verarbeiten
    if (isset($_POST['mkr_export_submit'])) {
        if (!isset($_POST['mkr_export_nonce']) || !wp_verify_nonce($_POST['mkr_export_nonce'], 'mkr_export_action')) {
            $error_message = __('Sicherheitsprüfung fehlgeschlagen. Bitte versuchen Sie es erneut.', 'mein-kochbuch-rezepte');
        } else {
            $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'recipe';
            $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json';
            
            // Beitragstyp validieren
            $valid_post_types = ['recipe', 'ingredient', 'utensil', 'glossary'];
            if (!in_array($post_type, $valid_post_types)) {
                $post_type = 'recipe';
            }
            
            // Format validieren
            $valid_formats = ['json', 'csv'];
            if (!in_array($format, $valid_formats)) {
                $format = 'json';
            }
            
            // Taxonomiefilter
            $taxonomy_filters = [];
            if ($post_type === 'recipe') {
                $taxonomies = ['difficulty', 'diet', 'cuisine', 'season'];
                foreach ($taxonomies as $taxonomy) {
                    if (isset($_POST[$taxonomy]) && !empty($_POST[$taxonomy])) {
                        $taxonomy_filters[$taxonomy] = sanitize_text_field($_POST[$taxonomy]);
                    }
                }
            }
            
            // Daten abrufen
            $args = [
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ];
            
            // Taxonomiefilter hinzufügen
            if (!empty($taxonomy_filters)) {
                $args['tax_query'] = [];
                foreach ($taxonomy_filters as $taxonomy => $term) {
                    $args['tax_query'][] = [
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                        'terms' => $term,
                    ];
                }
                
                if (count($args['tax_query']) > 1) {
                    $args['tax_query']['relation'] = 'AND';
                }
            }
            
            $query = new WP_Query($args);
            $posts = $query->posts;
            
            if (empty($posts)) {
                $error_message = __('Keine Einträge gefunden, die den Kriterien entsprechen.', 'mein-kochbuch-rezepte');
            } else {
                $export_data = [];
                
                foreach ($posts as $post) {
                    $post_data = [
                        'title' => $post->post_title,
                        'content' => $post->post_content,
                    ];
                    
                    // Meta-Daten je nach Beitragstyp hinzufügen
                    if ($post_type === 'recipe') {
                        $post_data['ingredients'] = get_post_meta($post->ID, '_mkr_ingredients', true);
                        $post_data['utensils'] = get_post_meta($post->ID, '_mkr_utensils', true);
                        $post_data['instructions'] = get_post_meta($post->ID, '_mkr_instructions', true);
                        $post_data['videos'] = get_post_meta($post->ID, '_mkr_videos', true);
                        $post_data['servings'] = get_post_meta($post->ID, '_mkr_servings', true);
                        $post_data['prep_time'] = get_post_meta($post->ID, '_mkr_prep_time', true);
                        $post_data['cook_time'] = get_post_meta($post->ID, '_mkr_cook_time', true);
                        $post_data['total_time'] = get_post_meta($post->ID, '_mkr_total_time', true);
                        
                        // Taxonomien hinzufügen
                        $post_data['taxonomies'] = [];
                        $taxonomies = ['difficulty', 'diet', 'cuisine', 'season'];
                        foreach ($taxonomies as $taxonomy) {
                            $terms = get_the_terms($post->ID, $taxonomy);
                            if ($terms && !is_wp_error($terms)) {
                                $post_data['taxonomies'][$taxonomy] = wp_list_pluck($terms, 'slug');
                            }
                        }
                        
                        // Beitragsbild hinzufügen
                        if (has_post_thumbnail($post->ID)) {
                            $post_data['featured_image_url'] = get_the_post_thumbnail_url($post->ID, 'full');
                        }
                    } elseif ($post_type === 'ingredient') {
                        $post_data['calories_per_100g'] = get_post_meta($post->ID, '_mkr_calories_per_100g', true);
                        $post_data['proteins_per_100g'] = get_post_meta($post->ID, '_mkr_proteins_per_100g', true);
                        $post_data['fats_per_100g'] = get_post_meta($post->ID, '_mkr_fats_per_100g', true);
                        $post_data['carbs_per_100g'] = get_post_meta($post->ID, '_mkr_carbs_per_100g', true);
                        $post_data['weight_per_cup'] = get_post_meta($post->ID, '_mkr_weight_per_cup', true);
                    } elseif ($post_type === 'utensil') {
                        // Spezifische Utensil-Metadaten, falls vorhanden
                    } elseif ($post_type === 'glossary') {
                        // Spezifische Glossary-Metadaten, falls vorhanden
                    }
                    
                    $export_data[] = $post_data;
                }
                
                // Export-Datei erstellen
                $filename = $post_type . '-export-' . date('Y-m-d') . '.' . $format;
                
                if ($format === 'json') {
                    $output = json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    
                    // Download-Header setzen
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Content-Length: ' . strlen($output));
                    
                    echo $output;
                    exit;
                } elseif ($format === 'csv') {
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    
                    $output = fopen('php://output', 'w');
                    
                    // CSV-Header basierend auf Beitragstyp erstellen
                    $header = ['title', 'content'];
                    
                    if ($post_type === 'recipe') {
                        $header = array_merge($header, ['servings', 'prep_time', 'cook_time', 'total_time', 'instructions']);
                        
                        // Weitere Recipe-spezifische Header
                        $header[] = 'ingredients_raw'; // Zutaten als Text
                        $header[] = 'utensils_raw'; // Utensilien als Text
                        $header[] = 'difficulty';
                        $header[] = 'diet';
                        $header[] = 'cuisine';
                        $header[] = 'season';
                    } elseif ($post_type === 'ingredient') {
                        $header = array_merge($header, ['calories_per_100g', 'proteins_per_100g', 'fats_per_100g', 'carbs_per_100g', 'weight_per_cup']);
                    }
                    
                    // CSV-Header schreiben
                    fputcsv($output, $header);
                    
                    // CSV-Daten schreiben
                    foreach ($export_data as $post_data) {
                        $row = [$post_data['title'], $post_data['content']];
                        
                        if ($post_type === 'recipe') {
                            $row[] = $post_data['servings'];
                            $row[] = $post_data['prep_time'];
                            $row[] = $post_data['cook_time'];
                            $row[] = $post_data['total_time'];
                            $row[] = $post_data['instructions'];
                            
                            // Zutaten formatieren
                            $ingredients_raw = '';
                            if (is_array($post_data['ingredients'])) {
                                $ingredients_formatted = [];
                                foreach ($post_data['ingredients'] as $ingredient) {
                                    $ingredients_formatted[] = $ingredient['amount'] . ' ' . $ingredient['unit'] . ' ' . $ingredient['name'];
                                }
                                $ingredients_raw = implode('; ', $ingredients_formatted);
                            }
                            $row[] = $ingredients_raw;
                            
                            // Utensilien formatieren
                            $utensils_raw = is_array($post_data['utensils']) ? implode('; ', $post_data['utensils']) : '';
                            $row[] = $utensils_raw;
                            
                            // Taxonomien
                            $taxonomies = ['difficulty', 'diet', 'cuisine', 'season'];
                            foreach ($taxonomies as $taxonomy) {
                                $row[] = isset($post_data['taxonomies'][$taxonomy]) ? implode('; ', $post_data['taxonomies'][$taxonomy]) : '';
                            }
                        } elseif ($post_type === 'ingredient') {
                            $row[] = $post_data['calories_per_100g'];
                            $row[] = $post_data['proteins_per_100g'];
                            $row[] = $post_data['fats_per_100g'];
                            $row[] = $post_data['carbs_per_100g'];
                            $row[] = $post_data['weight_per_cup'];
                        }
                        
                        fputcsv($output, $row);
                    }
                    
                    fclose($output);
                    exit;
                }
            }
        }
    }
    
    // Export-Formular anzeigen
    ?>
    <div class="wrap">
        <h1><?php _e('Rezept-Export', 'mein-kochbuch-rezepte'); ?></h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($success_message); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2><?php _e('Daten exportieren', 'mein-kochbuch-rezepte'); ?></h2>
            <p><?php _e('Wählen Sie den Beitragstyp und das Format für den Export.', 'mein-kochbuch-rezepte'); ?></p>
            
            <form method="post">
                <?php wp_nonce_field('mkr_export_action', 'mkr_export_nonce'); ?>
                
                <p>
                    <label for="post_type"><strong><?php _e('Beitragstyp:', 'mein-kochbuch-rezepte'); ?></strong></label><br>
                    <select name="post_type" id="post_type">
                        <option value="recipe"><?php _e('Rezepte', 'mein-kochbuch-rezepte'); ?></option>
                        <option value="ingredient"><?php _e('Zutaten', 'mein-kochbuch-rezepte'); ?></option>
                        <option value="utensil"><?php _e('Utensilien', 'mein-kochbuch-rezepte'); ?></option>
                        <option value="glossary"><?php _e('Fachbegriffe', 'mein-kochbuch-rezepte'); ?></option>
                    </select>
                </p>
                
                <p>
                    <label for="format"><strong><?php _e('Format:', 'mein-kochbuch-rezepte'); ?></strong></label><br>
                    <select name="format" id="format">
                        <option value="json"><?php _e('JSON', 'mein-kochbuch-rezepte'); ?></option>
                        <option value="csv"><?php _e('CSV', 'mein-kochbuch-rezepte'); ?></option>
                    </select>
                </p>
                
                <div id="recipe-filters" style="display: block;">
                    <h3><?php _e('Rezept-Filter (optional)', 'mein-kochbuch-rezepte'); ?></h3>
                    
                    <p>
                        <label for="difficulty"><strong><?php _e('Schwierigkeitsgrad:', 'mein-kochbuch-rezepte'); ?></strong></label><br>
                        <select name="difficulty" id="difficulty">
                            <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                            <?php
                            $difficulties = get_terms(['taxonomy' => 'difficulty', 'hide_empty' => false]);
                            foreach ($difficulties as $difficulty) {
                                echo '<option value="' . esc_attr($difficulty->slug) . '">' . esc_html($difficulty->name) . '</option>';
                            }
                            ?>
                        </select>
                    </p>
                    
                    <p>
                        <label for="diet"><strong><?php _e('Diätvorgaben:', 'mein-kochbuch-rezepte'); ?></strong></label><br>
                        <select name="diet" id="diet">
                            <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                            <?php
                            $diets = get_terms(['taxonomy' => 'diet', 'hide_empty' => false]);
                            foreach ($diets as $diet) {
                                echo '<option value="' . esc_attr($diet->slug) . '">' . esc_html($diet->name) . '</option>';
                            }
                            ?>
                        </select>
                    </p>
                    
                    <p>
                        <label for="cuisine"><strong><?php _e('Küchenart:', 'mein-kochbuch-rezepte'); ?></strong></label><br>
                        <select name="cuisine" id="cuisine">
                            <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                            <?php
                            $cuisines = get_terms(['taxonomy' => 'cuisine', 'hide_empty' => false]);
                            foreach ($cuisines as $cuisine) {
                                echo '<option value="' . esc_attr($cuisine->slug) . '">' . esc_html($cuisine->name) . '</option>';
                            }
                            ?>
                        </select>
                    </p>
                    
                    <p>
                        <label for="season"><strong><?php _e('Saison:', 'mein-kochbuch-rezepte'); ?></strong></label><br>
                        <select name="season" id="season">
                            <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                            <?php
                            $seasons = get_terms(['taxonomy' => 'season', 'hide_empty' => false]);
                            foreach ($seasons as $season) {
                                echo '<option value="' . esc_attr($season->slug) . '">' . esc_html($season->name) . '</option>';
                            }
                            ?>
                        </select>
                    </p>
                </div>
                
                <p><input type="submit" name="mkr_export_submit" class="button button-primary" value="<?php _e('Exportieren', 'mein-kochbuch-rezepte'); ?>" /></p>
            </form>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#post_type').on('change', function() {
            if ($(this).val() === 'recipe') {
                $('#recipe-filters').show();
            } else {
                $('#recipe-filters').hide();
            }
        });
    });
    </script>
    <?php
}

/**
 * Lädt ein Bild von einer URL und setzt es als Beitragsbild
 *
 * @param int $post_id Beitrags-ID
 * @param string $image_url Bild-URL
 * @return bool Erfolg oder Misserfolg
 */
function mkr_set_featured_image_from_url($post_id, $image_url) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    // Prüfen, ob die URL gültig ist
    if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Prüfen, ob ein Beitragsbild bereits existiert
    if (has_post_thumbnail($post_id)) {
        return true;
    }
    
    // Bild herunterladen und an Medienbibliothek anhängen
    $attachment_id = media_sideload_image($image_url, $post_id, '', 'id');
    
    if (is_wp_error($attachment_id)) {
        return false;
    }
    
    // Als Beitragsbild setzen
    set_post_thumbnail($post_id, $attachment_id);
    
    return true;
}