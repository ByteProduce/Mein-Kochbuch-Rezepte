<?php
/**
 * Template für die Plugin-Einstellungsseite
 *
 * @package Mein Kochbuch Rezepte
 */

// Sicherheitsüberprüfung - direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Einstellungen speichern
if (isset($_POST['mkr_save_settings']) && check_admin_referer('mkr_settings_nonce')) {
    // Allgemeine Einstellungen
    update_option('mkr_recipes_per_page', absint($_POST['mkr_recipes_per_page']));
    update_option('mkr_show_seasonal_recipes', sanitize_text_field($_POST['mkr_show_seasonal_recipes']));
    update_option('mkr_apply_user_diet', sanitize_text_field($_POST['mkr_apply_user_diet']));
    update_option('mkr_units_system', sanitize_text_field($_POST['mkr_units_system']));
    
    // SEO Einstellungen
    update_option('mkr_enable_recipe_schema', isset($_POST['mkr_enable_recipe_schema']) ? '1' : '0');
    update_option('mkr_enable_video_sitemap', isset($_POST['mkr_enable_video_sitemap']) ? '1' : '0');
    
    // PDF Export Einstellungen
    update_option('mkr_pdf_logo', esc_url_raw($_POST['mkr_pdf_logo']));
    update_option('mkr_pdf_author', sanitize_text_field($_POST['mkr_pdf_author']));
    update_option('mkr_pdf_footer_text', wp_kses_post($_POST['mkr_pdf_footer_text']));
    
    // API-Schlüssel
    update_option('mkr_youtube_api_key', sanitize_text_field($_POST['mkr_youtube_api_key']));
    
    // Cache-Einstellungen
    update_option('mkr_enable_caching', isset($_POST['mkr_enable_caching']) ? '1' : '0');
    update_option('mkr_cache_duration', absint($_POST['mkr_cache_duration']));
    
    // Erfolgsbenachrichtigung
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Einstellungen gespeichert.', 'mein-kochbuch-rezepte') . '</p></div>';
}

// Einstellungen abrufen
$recipes_per_page = get_option('mkr_recipes_per_page', 12);
$show_seasonal_recipes = get_option('mkr_show_seasonal_recipes', 'yes');
$apply_user_diet = get_option('mkr_apply_user_diet', 'yes');
$units_system = get_option('mkr_units_system', 'metric');
$enable_recipe_schema = get_option('mkr_enable_recipe_schema', '1');
$enable_video_sitemap = get_option('mkr_enable_video_sitemap', '1');
$pdf_logo = get_option('mkr_pdf_logo', '');
$pdf_author = get_option('mkr_pdf_author', get_bloginfo('name'));
$pdf_footer_text = get_option('mkr_pdf_footer_text', '© ' . date('Y') . ' ' . get_bloginfo('name'));
$youtube_api_key = get_option('mkr_youtube_api_key', '');
$enable_caching = get_option('mkr_enable_caching', '1');
$cache_duration = get_option('mkr_cache_duration', 24);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('mkr_settings_nonce'); ?>
        
        <div class="mkr-settings-tabs">
            <div class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('Allgemein', 'mein-kochbuch-rezepte'); ?></a>
                <a href="#appearance" class="nav-tab"><?php _e('Darstellung', 'mein-kochbuch-rezepte'); ?></a>
                <a href="#seo" class="nav-tab"><?php _e('SEO', 'mein-kochbuch-rezepte'); ?></a>
                <a href="#export" class="nav-tab"><?php _e('PDF-Export', 'mein-kochbuch-rezepte'); ?></a>
                <a href="#api" class="nav-tab"><?php _e('API-Schlüssel', 'mein-kochbuch-rezepte'); ?></a>
                <a href="#performance" class="nav-tab"><?php _e('Leistung', 'mein-kochbuch-rezepte'); ?></a>
            </div>
            
            <!-- Allgemeine Einstellungen -->
            <div id="general" class="tab-content active">
                <h2><?php _e('Allgemeine Einstellungen', 'mein-kochbuch-rezepte'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mkr_recipes_per_page"><?php _e('Rezepte pro Seite', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="mkr_recipes_per_page" id="mkr_recipes_per_page" value="<?php echo esc_attr($recipes_per_page); ?>" min="1" max="100" class="small-text">
                            <p class="description"><?php _e('Anzahl der Rezepte, die auf der Archivseite angezeigt werden.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mkr_show_seasonal_recipes"><?php _e('Saisonale Rezepte', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <select name="mkr_show_seasonal_recipes" id="mkr_show_seasonal_recipes">
                                <option value="yes" <?php selected($show_seasonal_recipes, 'yes'); ?>><?php _e('Ja', 'mein-kochbuch-rezepte'); ?></option>
                                <option value="no" <?php selected($show_seasonal_recipes, 'no'); ?>><?php _e('Nein', 'mein-kochbuch-rezepte'); ?></option>
                            </select>
                            <p class="description"><?php _e('Automatisch Rezepte basierend auf der aktuellen Saison anzeigen.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mkr_apply_user_diet"><?php _e('Benutzerdiätvorgaben', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <select name="mkr_apply_user_diet" id="mkr_apply_user_diet">
                                <option value="yes" <?php selected($apply_user_diet, 'yes'); ?>><?php _e('Ja', 'mein-kochbuch-rezepte'); ?></option>
                                <option value="no" <?php selected($apply_user_diet, 'no'); ?>><?php _e('Nein', 'mein-kochbuch-rezepte'); ?></option>
                            </select>
                            <p class="description"><?php _e('Automatisch Rezepte basierend auf den Diätvorgaben des Benutzers filtern.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mkr_units_system"><?php _e('Standardmaßsystem', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <select name="mkr_units_system" id="mkr_units_system">
                                <option value="metric" <?php selected($units_system, 'metric'); ?>><?php _e('Metrisch (g, ml)', 'mein-kochbuch-rezepte'); ?></option>
                                <option value="imperial" <?php selected($units_system, 'imperial'); ?>><?php _e('Imperial (oz, cups)', 'mein-kochbuch-rezepte'); ?></option>
                            </select>
                            <p class="description"><?php _e('Standardmäßiges Maßsystem für neue Benutzer.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Darstellungseinstellungen -->
            <div id="appearance" class="tab-content">
                <h2><?php _e('Darstellungseinstellungen', 'mein-kochbuch-rezepte'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Farbschema', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <div class="mkr-color-picker-section">
                                <div class="mkr-color-picker-field">
                                    <label for="mkr_primary_color"><?php _e('Primärfarbe', 'mein-kochbuch-rezepte'); ?></label>
                                    <input type="text" name="mkr_primary_color" id="mkr_primary_color" value="<?php echo esc_attr(get_option('mkr_primary_color', '#0073aa')); ?>" class="mkr-color-picker">
                                </div>
                                
                                <div class="mkr-color-picker-field">
                                    <label for="mkr_secondary_color"><?php _e('Sekundärfarbe', 'mein-kochbuch-rezepte'); ?></label>
                                    <input type="text" name="mkr_secondary_color" id="mkr_secondary_color" value="<?php echo esc_attr(get_option('mkr_secondary_color', '#6c757d')); ?>" class="mkr-color-picker">
                                </div>
                                
                                <div class="mkr-color-picker-field">
                                    <label for="mkr_text_color"><?php _e('Textfarbe', 'mein-kochbuch-rezepte'); ?></label>
                                    <input type="text" name="mkr_text_color" id="mkr_text_color" value="<?php echo esc_attr(get_option('mkr_text_color', '#333333')); ?>" class="mkr-color-picker">
                                </div>
                            </div>
                            <p class="description"><?php _e('Farbschema des Plugins anpassen.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mkr_font_family"><?php _e('Schriftart', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <select name="mkr_font_family" id="mkr_font_family">
                                <option value="system" <?php selected(get_option('mkr_font_family', 'system'), 'system'); ?>><?php _e('System-Schriftart', 'mein-kochbuch-rezepte'); ?></option>
                                <option value="serif" <?php selected(get_option('mkr_font_family'), 'serif'); ?>><?php _e('Serifenschrift', 'mein-kochbuch-rezepte'); ?></option>
                                <option value="sans-serif" <?php selected(get_option('mkr_font_family'), 'sans-serif'); ?>><?php _e('Sans-Serif-Schrift', 'mein-kochbuch-rezepte'); ?></option>
                                <option value="monospace" <?php selected(get_option('mkr_font_family'), 'monospace'); ?>><?php _e('Monospace-Schrift', 'mein-kochbuch-rezepte'); ?></option>
                            </select>
                            <p class="description"><?php _e('Schriftart für Rezepte und andere Inhalte.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mkr_image_style"><?php _e('Bildstil', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <select name="mkr_image_style" id="mkr_image_style">
                                <option value="rounded" <?php selected(get_option('mkr_image_style', 'rounded'), 'rounded'); ?>><?php _e('Abgerundet', 'mein-kochbuch-rezepte'); ?></option>
                                <option value="square" <?php selected(get_option('mkr_image_style'), 'square'); ?>><?php _e('Quadratisch', 'mein-kochbuch-rezepte'); ?></option>
                                <option value="circle" <?php selected(get_option('mkr_image_style'), 'circle'); ?>><?php _e('Kreisförmig', 'mein-kochbuch-rezepte'); ?></option>
                            </select>
                            <p class="description"><?php _e('Stil für Bilder in Rezepten und Karten.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- SEO-Einstellungen -->
            <div id="seo" class="tab-content">
                <h2><?php _e('SEO-Einstellungen', 'mein-kochbuch-rezepte'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mkr_enable_recipe_schema"><?php _e('Recipe Schema.org', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="mkr_enable_recipe_schema" id="mkr_enable_recipe_schema" value="1" <?php checked($enable_recipe_schema, '1'); ?>>
                            <label for="mkr_enable_recipe_schema"><?php _e('Schema.org Markup für Rezepte aktivieren', 'mein-kochbuch-rezepte'); ?></label>
                            <p class="description"><?php _e('Fügt strukturierte Daten zu Rezepten hinzu, um Rich Snippets in Suchmaschinen zu ermöglichen.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mkr_enable_video_sitemap"><?php _e('Video Sitemap', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="mkr_enable_video_sitemap" id="mkr_enable_video_sitemap" value="1" <?php checked($enable_video_sitemap, '1'); ?>>
                            <label for="mkr_enable_video_sitemap"><?php _e('Video-Sitemap aktivieren', 'mein-kochbuch-rezepte'); ?></label>
                            <p class="description"><?php _e('Erstellt eine spezielle Sitemap für Videos in Rezepten für eine bessere Indexierung.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mkr_meta_description_template"><?php _e('Meta-Beschreibungsvorlage', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <textarea name="mkr_meta_description_template" id="mkr_meta_description_template" rows="3" class="large-text"><?php echo esc_textarea(get_option('mkr_meta_description_template', __('Ein leckeres Rezept für {title} mit {ingredients}. Zubereitungszeit: {prep_time} Minuten.', 'mein-kochbuch-rezepte'))); ?></textarea>
                            <p class="description">
                                <?php _e('Vorlage für Meta-Beschreibungen. Verfügbare Platzhalter: {title}, {ingredients}, {prep_time}, {cook_time}, {total_time}, {servings}, {calories}.', 'mein-kochbuch-rezepte'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- PDF-Export-Einstellungen -->
            <div id="export" class="tab-content">
                <h2><?php _e('PDF-Export-Einstellungen', 'mein-kochbuch-rezepte'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mkr_pdf_logo"><?php _e('PDF-Logo-URL', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="mkr_pdf_logo" id="mkr_pdf_logo" value="<?php echo esc_attr($pdf_logo); ?>" class="regular-text">
                            <button type="button" class="button mkr-media-upload" data-target="mkr_pdf_logo">
                                <?php _e('Medien auswählen', 'mein-kochbuch-rezepte'); ?>
                            </button>
                            <p class="description"><?php _e('Logo, das im PDF-Export angezeigt wird.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mkr_pdf_author"><?php _e('PDF-Autor', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="mkr_pdf_author" id="mkr_pdf_author" value="<?php echo esc_attr($pdf_author); ?>" class="regular-text">
                            <p class="description"><?php _e('Name des Autors für exportierte PDFs.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mkr_pdf_footer_text"><?php _e('PDF-Fußzeilentext', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <textarea name="mkr_pdf_footer_text" id="mkr_pdf_footer_text" rows="2" class="large-text"><?php echo esc_textarea($pdf_footer_text); ?></textarea>
                            <p class="description"><?php _e('Text für die Fußzeile in exportierten PDFs.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- API-Schlüssel-Einstellungen -->
            <div id="api" class="tab-content">
                <h2><?php _e('API-Schlüssel', 'mein-kochbuch-rezepte'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mkr_youtube_api_key"><?php _e('YouTube API-Schlüssel', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="mkr_youtube_api_key" id="mkr_youtube_api_key" value="<?php echo esc_attr($youtube_api_key); ?>" class="regular-text">
                            <p class="description">
                                <?php _e('API-Schlüssel für erweiterte YouTube-Funktionen.', 'mein-kochbuch-rezepte'); ?> 
                                <a href="https://developers.google.com/youtube/v3/getting-started" target="_blank"><?php _e('Hier erhalten Sie einen API-Schlüssel', 'mein-kochbuch-rezepte'); ?></a>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Leistungseinstellungen -->
            <div id="performance" class="tab-content">
                <h2><?php _e('Leistungseinstellungen', 'mein-kochbuch-rezepte'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mkr_enable_caching"><?php _e('Caching aktivieren', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="mkr_enable_caching" id="mkr_enable_caching" value="1" <?php checked($enable_caching, '1'); ?>>
                            <label for="mkr_enable_caching"><?php _e('Transient-Caching für Rezeptdaten aktivieren', 'mein-kochbuch-rezepte'); ?></label>
                            <p class="description"><?php _e('Verbessert die Leistung, indem häufig verwendete Daten zwischengespeichert werden.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mkr_cache_duration"><?php _e('Cache-Dauer (Stunden)', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="mkr_cache_duration" id="mkr_cache_duration" value="<?php echo esc_attr($cache_duration); ?>" min="1" max="72" class="small-text">
                            <p class="description"><?php _e('Dauer in Stunden, für die Daten im Cache gespeichert werden.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Cache leeren', 'mein-kochbuch-rezepte'); ?></label>
                        </th>
                        <td>
                            <button type="button" id="mkr-clear-cache" class="button button-secondary">
                                <?php _e('Plugin-Cache leeren', 'mein-kochbuch-rezepte'); ?>
                            </button>
                            <span id="mkr-clear-cache-status" style="display: inline-block; margin-left: 10px;"></span>
                            <p class="description"><?php _e('Alle vom Plugin gespeicherten Transients löschen.', 'mein-kochbuch-rezepte'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="mkr_save_settings" class="button button-primary" value="<?php _e('Einstellungen speichern', 'mein-kochbuch-rezepte'); ?>">
        </p>
    </form>
</div>

<style>
.mkr-settings-tabs .nav-tab-wrapper {
    margin-bottom: 20px;
}

.mkr-settings-tabs .tab-content {
    display: none;
}

.mkr-settings-tabs .tab-content.active {
    display: block;
}

.mkr-color-picker-section {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.mkr-color-picker-field {
    margin-bottom: 15px;
}

.mkr-color-picker-field label {
    display: block;
    margin-bottom: 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tabs
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Tabs aktivieren/deaktivieren
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Tab-Inhalt anzeigen/ausblenden
        var target = $(this).attr('href');
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Media Uploader für PDF-Logo
    $('.mkr-media-upload').on('click', function(e) {
        e.preventDefault();
        
        var targetInputId = $(this).data('target');
        var mediaUploader;
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media({
            title: '<?php _e("Logo auswählen", "mein-kochbuch-rezepte"); ?>',
            button: {
                text: '<?php _e("Auswählen", "mein-kochbuch-rezepte"); ?>'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#' + targetInputId).val(attachment.url);
        });
        
        mediaUploader.open();
    });
    
    // Cache leeren
    $('#mkr-clear-cache').on('click', function() {
        var $button = $(this);
        var $status = $('#mkr-clear-cache-status');
        
        $button.prop('disabled', true);
        $status.text('<?php _e("Cache wird geleert...", "mein-kochbuch-rezepte"); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mkr_clear_cache',
                nonce: '<?php echo wp_create_nonce("mkr_clear_cache_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $status.text('<?php _e("Cache erfolgreich geleert!", "mein-kochbuch-rezepte"); ?>');
                } else {
                    $status.text('<?php _e("Fehler beim Leeren des Caches.", "mein-kochbuch-rezepte"); ?>');
                }
            },
            error: function() {
                $status.text('<?php _e("Fehler beim Leeren des Caches.", "mein-kochbuch-rezepte"); ?>');
            },
            complete: function() {
                $button.prop('disabled', false);
                setTimeout(function() {
                    $status.text('');
                }, 3000);
            }
        });
    });
    
    // Color Picker initialisieren
    if ($.fn.wpColorPicker) {
        $('.mkr-color-picker').wpColorPicker();
    }
});
</script>