<?php
/**
 * Video-Funktionen für Mein Kochbuch Rezepte (Ohne YouTube API-Abhängigkeit)
 */

// Sicherheitsprüfung: Direkter Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSS und JS für Video-Funktionen laden
 */
function mkr_enqueue_video_assets() {
    // Admin-Bereich
    add_action('admin_enqueue_scripts', function($hook) {
        global $post;
        
        // Nur auf Post-Edit-Seiten für Rezepte laden
        if (('post.php' === $hook || 'post-new.php' === $hook) && isset($post) && 'recipe' === $post->post_type) {
            wp_enqueue_style('mkr-video-admin', MKR_PLUGIN_URL . 'assets/css/video-admin.css', array(), MKR_VERSION);
        }
    });
    
    // Frontend
    add_action('wp_enqueue_scripts', function() {
        wp_enqueue_style('mkr-video-frontend', MKR_PLUGIN_URL . 'assets/css/video-frontend.css', array(), MKR_VERSION);
    });
}
add_action('init', 'mkr_enqueue_video_assets');

/**
 * Provider-Klasse für die Video-Sitemap
 */
class MKR_Video_Sitemap_Provider extends WP_Sitemaps_Provider {
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->name = 'video';
        $this->object_type = 'video';
    }

    /**
     * Generiert die Liste der URLs für die Sitemap
     *
     * @param int $page_num Die Seitennummer
     * @param string $post_type Der Beitragstyp (wird ignoriert)
     * @return array Die Liste der URLs für diese Seite der Sitemap
     */
    public function get_url_list($page_num, $post_type = '') {
        // Rezepte mit Videos abfragen
        $args = array(
            'post_type' => 'recipe',
            'posts_per_page' => 50,
            'paged' => $page_num,
            'meta_query' => array(
                array(
                    'key' => '_mkr_videos',
                    'value' => '',
                    'compare' => '!=',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        $url_list = array();
        
        while ($query->have_posts()) {
            $query->the_post();
            
            $post_id = get_the_ID();
            $videos = get_post_meta($post_id, '_mkr_videos', true);
            
            // Videos validieren und in gültigem Format für die Sitemap ausgeben
            if (is_array($videos) && !empty($videos)) {
                $url_list[] = $this->prepare_video_sitemap_entry($post_id, $videos);
            }
        }
        
        wp_reset_postdata();
        
        return $url_list;
    }

    /**
     * Ermittelt die Anzahl der Seiten für die Sitemap
     *
     * @param string $post_type Der Beitragstyp (wird ignoriert)
     * @return int Die Anzahl der Seiten
     */
    public function get_max_num_pages($post_type = '') {
        $args = array(
            'post_type' => 'recipe',
            'posts_per_page' => 50,
            'meta_query' => array(
                array(
                    'key' => '_mkr_videos',
                    'value' => '',
                    'compare' => '!=',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        
        return ceil($query->found_posts / 50);
    }
    
    /**
     * Bereitet einen Sitemap-Eintrag für ein Video vor
     *
     * @param int $post_id Die Beitrags-ID
     * @param array $videos Die Video-URLs
     * @return array Der formatierte Sitemap-Eintrag
     */
    private function prepare_video_sitemap_entry($post_id, $videos) {
        $post = get_post($post_id);
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
        $permalink = get_permalink($post_id);
        
        // Grundlegende Informationen
        $entry = array(
            'loc' => $permalink,
            'lastmod' => get_the_modified_date('c', $post_id),
            'video' => array(),
        );
        
        // Für jedes Video einen Eintrag erstellen
        foreach ($videos as $index => $video_data) {
            // Benutzerdefinierte Video-Details verwenden oder fallback auf YouTube-Integration
            $video_url = '';
            $video_title = '';
            $video_thumbnail = '';
            $video_description = '';
            $video_duration = 0;
            
            // Überprüfen, ob es sich um ein Array mit benutzerdefinierten Details handelt
            if (is_array($video_data)) {
                $video_url = isset($video_data['url']) ? $video_data['url'] : '';
                $video_title = isset($video_data['title']) ? $video_data['title'] : '';
                $video_thumbnail = isset($video_data['thumbnail']) ? $video_data['thumbnail'] : '';
                $video_description = isset($video_data['description']) ? $video_data['description'] : '';
                $video_duration = isset($video_data['duration']) ? $video_data['duration'] : 0;
            } else {
                // Fallback für ältere Daten: URL direkt und YouTube-Informationen extrahieren
                $video_url = $video_data;
                $video_id = mkr_extract_youtube_id($video_url);
                
                if ($video_id) {
                    $video_title = $post->post_title . ' - ' . __('Video', 'mein-kochbuch-rezepte') . ' ' . ($index + 1);
                    $video_thumbnail = "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
                    $video_description = wp_trim_words($post->post_content, 30, '...');
                    // Video-Dauer kann ohne API nicht zuverlässig ermittelt werden
                }
            }
            
            // Nur gültige Videos zur Sitemap hinzufügen
            if (!empty($video_url)) {
                $video_entry = array(
                    'thumbnail_loc' => $video_thumbnail ?: $thumbnail_url,
                    'title' => $video_title ?: ($post->post_title . ' - ' . __('Video', 'mein-kochbuch-rezepte') . ' ' . ($index + 1)),
                    'description' => $video_description ?: wp_trim_words($post->post_content, 30, '...'),
                    'content_loc' => '',
                    'player_loc' => $video_url,
                    'duration' => $video_duration,
                    'publication_date' => get_the_date('c', $post_id),
                    'family_friendly' => 'yes',
                    'requires_subscription' => 'no',
                    'platform' => 'web mobile',
                    'live' => 'no',
                );
                
                // Zum Sitemap-Eintrag hinzufügen
                $entry['video'][] = $video_entry;
            }
        }
        
        return $entry;
    }
}

/**
 * Extrahiert die YouTube-Video-ID aus einer URL
 *
 * @param string $url Die YouTube-URL
 * @return string|false Die Video-ID oder false, wenn keine gefunden wurde
 */
function mkr_extract_youtube_id($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\s*[^\/\n\s]+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
    
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    
    return false;
}

/**
 * Holt ein Vorschaubild für ein YouTube-Video basierend auf der ID
 * 
 * @param string $video_id Die YouTube-Video-ID
 * @return string Die URL des Vorschaubilds
 */
function mkr_get_youtube_thumbnail($video_id) {
    if (empty($video_id)) {
        return '';
    }
    
    return "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
}

/**
 * Shortcode für die Anzeige eines Videos (YouTube oder benutzerdefiniert)
 *
 * @param array $atts Die Shortcode-Attribute
 * @return string Das HTML für die Videoeinbettung
 */
function mkr_video_shortcode($atts) {
    $atts = shortcode_atts(array(
        'url' => '',
        'width' => 560,
        'height' => 315,
        'autoplay' => 0,
        'controls' => 1,
        'title' => '',
        'type' => 'youtube', // youtube, vimeo, custom
        'thumbnail' => '',
    ), $atts, 'mkr_video');
    
    // Wenn keine URL vorhanden ist, Fehlermeldung anzeigen
    if (empty($atts['url'])) {
        return '<div class="mkr-error">' . __('Keine Video-URL angegeben', 'mein-kochbuch-rezepte') . '</div>';
    }
    
    $html = '';
    
    // Je nach Videotyp den passenden HTML-Code erstellen
    switch ($atts['type']) {
        case 'youtube':
            // YouTube-Video-ID extrahieren
            $video_id = mkr_extract_youtube_id($atts['url']);
            
            if (!$video_id) {
                return '<div class="mkr-error">' . __('Ungültige YouTube-URL', 'mein-kochbuch-rezepte') . '</div>';
            }
            
            // Parameter für das eingebettete Video
            $params = array(
                'autoplay' => (int) $atts['autoplay'],
                'controls' => (int) $atts['controls'],
                'rel' => 0,
            );
            
            // Parameter in einen Query-String umwandeln
            $query_string = http_build_query($params);
            
            // HTML für das eingebettete Video erstellen
            $html = sprintf(
                '<div class="mkr-video-container">
                    <iframe width="%d" height="%d" src="https://www.youtube.com/embed/%s?%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen title="%s"></iframe>
                </div>',
                (int) $atts['width'],
                (int) $atts['height'],
                esc_attr($video_id),
                esc_attr($query_string),
                esc_attr(!empty($atts['title']) ? $atts['title'] : __('Video', 'mein-kochbuch-rezepte'))
            );
            break;
            
        case 'vimeo':
            // Vimeo-Video-ID extrahieren
            $vimeo_id = '';
            if (preg_match('/vimeo\.com\/([0-9]+)/', $atts['url'], $matches)) {
                $vimeo_id = $matches[1];
            }
            
            if (!$vimeo_id) {
                return '<div class="mkr-error">' . __('Ungültige Vimeo-URL', 'mein-kochbuch-rezepte') . '</div>';
            }
            
            // HTML für das eingebettete Vimeo-Video erstellen
            $html = sprintf(
                '<div class="mkr-video-container">
                    <iframe width="%d" height="%d" src="https://player.vimeo.com/video/%s?autoplay=%d" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen title="%s"></iframe>
                </div>',
                (int) $atts['width'],
                (int) $atts['height'],
                esc_attr($vimeo_id),
                (int) $atts['autoplay'],
                esc_attr(!empty($atts['title']) ? $atts['title'] : __('Video', 'mein-kochbuch-rezepte'))
            );
            break;
            
        case 'custom':
        default:
            // Für benutzerdefinierte Videos eine einfache HTML5-Videoplayer erstellen
            $poster_attr = !empty($atts['thumbnail']) ? ' poster="' . esc_url($atts['thumbnail']) . '"' : '';
            $controls_attr = !empty($atts['controls']) ? ' controls' : '';
            $autoplay_attr = !empty($atts['autoplay']) ? ' autoplay muted' : '';
            
            $html = sprintf(
                '<div class="mkr-video-container">
                    <video width="%d" height="%d"%s%s%s>
                        <source src="%s" type="video/mp4">
                        %s
                    </video>
                </div>',
                (int) $atts['width'],
                (int) $atts['height'],
                $poster_attr,
                $controls_attr,
                $autoplay_attr,
                esc_url($atts['url']),
                __('Ihr Browser unterstützt keine HTML5-Videos.', 'mein-kochbuch-rezepte')
            );
            break;
    }
    
    return $html;
}
add_shortcode('mkr_video', 'mkr_video_shortcode');

// Legacy-Support für den alten YouTube-Shortcode
add_shortcode('mkr_youtube', 'mkr_video_shortcode');

/**
 * Registriert einen Block für das Video-Shortcode im Gutenberg-Editor
 */
function mkr_register_video_block() {
    // Nur laden, wenn der Block-Editor verfügbar ist
    if (!function_exists('register_block_type')) {
        return;
    }
    
    wp_register_script(
        'mkr-video-block',
        MKR_PLUGIN_URL . 'assets/js/video-block.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
        MKR_VERSION
    );
    
    register_block_type('mkr/video', array(
        'editor_script' => 'mkr-video-block',
        'render_callback' => 'mkr_video_shortcode',
        'attributes' => array(
            'url' => array(
                'type' => 'string',
                'default' => '',
            ),
            'width' => array(
                'type' => 'number',
                'default' => 560,
            ),
            'height' => array(
                'type' => 'number',
                'default' => 315,
            ),
            'autoplay' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'controls' => array(
                'type' => 'boolean',
                'default' => true,
            ),
            'title' => array(
                'type' => 'string',
                'default' => '',
            ),
            'type' => array(
                'type' => 'string',
                'default' => 'youtube',
            ),
            'thumbnail' => array(
                'type' => 'string',
                'default' => '',
            ),
        ),
    ));
}
add_action('init', 'mkr_register_video_block');

/**
 * Anpassen der Metabox für Videos, um manuelle Eingabe zu unterstützen
 * 
 * Diese Funktion sollte in backend-metaboxes.php hinzugefügt/ersetzt werden
 */
function mkr_recipe_videos_metabox_callback($post) {
    wp_nonce_field('mkr_recipe_save_data', 'mkr_recipe_meta_nonce');
    
    // Hidden field für das ausgewählte Thumbnail als Beitragsbild
    echo '<input type="hidden" id="mkr_thumbnail_for_featured_image" name="mkr_thumbnail_for_featured_image" value="" />';
    
    // Bestehende Videos abrufen
    $videos = get_post_meta($post->ID, '_mkr_videos', true);
    $videos = is_array($videos) ? $videos : array();
    
    ?>
    <p class="description">
        <?php _e('Fügen Sie Videos zu Ihrem Rezept hinzu. Sie können YouTube-URLs oder eigene Videos eingeben.', 'mein-kochbuch-rezepte'); ?>
    </p>
    
    <div class="mkr-videos-container">
        <div class="mkr-videos-list">
            <?php if (empty($videos)): ?>
                <div class="mkr-video-group">
                    <div class="mkr-video-type-toggle">
                        <select name="mkr_video_type[]" class="mkr-video-type-select">
                            <option value="youtube"><?php _e('YouTube', 'mein-kochbuch-rezepte'); ?></option>
                            <option value="vimeo"><?php _e('Vimeo', 'mein-kochbuch-rezepte'); ?></option>
                            <option value="custom"><?php _e('Eigenes Video', 'mein-kochbuch-rezepte'); ?></option>
                        </select>
                    </div>
                    
                    <div class="mkr-video-fields">
                        <input type="url" name="mkr_video_url[]" value="" placeholder="<?php esc_attr_e('Video-URL eingeben', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-url" />
                        <input type="text" name="mkr_video_title[]" value="" placeholder="<?php esc_attr_e('Titel (optional)', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-title" />
                        <input type="url" name="mkr_video_thumbnail[]" value="" placeholder="<?php esc_attr_e('Vorschaubild-URL (optional)', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-thumbnail-url" />
                    </div>
                    
                    <div class="mkr-video-preview">
                        <img class="mkr-video-thumbnail-preview" src="" alt="<?php esc_attr_e('Video-Vorschaubild', 'mein-kochbuch-rezepte'); ?>" style="max-width: 120px; display: none;" />
                    </div>
                    
                    <button type="button" class="button mkr-remove-video">
                        <?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?>
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($videos as $index => $video): ?>
                    <?php
                    // Prüfen, ob es sich um ein älteres Format (nur URL) oder neueres Format (Array) handelt
                    $is_legacy = !is_array($video);
                    $video_url = $is_legacy ? $video : (isset($video['url']) ? $video['url'] : '');
                    $video_title = !$is_legacy && isset($video['title']) ? $video['title'] : '';
                    $video_type = !$is_legacy && isset($video['type']) ? $video['type'] : 'youtube';
                    $video_thumbnail = !$is_legacy && isset($video['thumbnail']) ? $video['thumbnail'] : '';
                    
                    // Für YouTube-Videos automatisch ein Vorschaubild generieren
                    if ($video_type === 'youtube' && empty($video_thumbnail)) {
                        $video_id = mkr_extract_youtube_id($video_url);
                        if ($video_id) {
                            $video_thumbnail = mkr_get_youtube_thumbnail($video_id);
                        }
                    }
                    ?>
                    <div class="mkr-video-group">
                        <div class="mkr-video-type-toggle">
                            <select name="mkr_video_type[]" class="mkr-video-type-select">
                                <option value="youtube" <?php selected($video_type, 'youtube'); ?>><?php _e('YouTube', 'mein-kochbuch-rezepte'); ?></option>
                                <option value="vimeo" <?php selected($video_type, 'vimeo'); ?>><?php _e('Vimeo', 'mein-kochbuch-rezepte'); ?></option>
                                <option value="custom" <?php selected($video_type, 'custom'); ?>><?php _e('Eigenes Video', 'mein-kochbuch-rezepte'); ?></option>
                            </select>
                        </div>
                        
                        <div class="mkr-video-fields">
                            <input type="url" name="mkr_video_url[]" value="<?php echo esc_attr($video_url); ?>" placeholder="<?php esc_attr_e('Video-URL eingeben', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-url" />
                            <input type="text" name="mkr_video_title[]" value="<?php echo esc_attr($video_title); ?>" placeholder="<?php esc_attr_e('Titel (optional)', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-title" />
                            <input type="url" name="mkr_video_thumbnail[]" value="<?php echo esc_attr($video_thumbnail); ?>" placeholder="<?php esc_attr_e('Vorschaubild-URL (optional)', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-thumbnail-url" />
                        </div>
                        
                        <div class="mkr-video-preview">
                            <?php if (!empty($video_thumbnail)): ?>
                                <img class="mkr-video-thumbnail-preview" src="<?php echo esc_url($video_thumbnail); ?>" alt="<?php esc_attr_e('Video-Vorschaubild', 'mein-kochbuch-rezepte'); ?>" style="max-width: 120px;" />
                        <button type="button" class="button mkr-use-as-featured-image" style="margin-top: 5px; width: 100%;"><?php _e('Als Beitragsbild verwenden', 'mein-kochbuch-rezepte'); ?></button>
                            <?php else: ?>
                                <img class="mkr-video-thumbnail-preview" src="" alt="<?php esc_attr_e('Video-Vorschaubild', 'mein-kochbuch-rezepte'); ?>" style="max-width: 120px; display: none;" />
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" class="button mkr-remove-video">
                            <?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" id="mkr-add-video" class="button button-primary">
            <?php _e('Video hinzufügen', 'mein-kochbuch-rezepte'); ?>
        </button>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Felder basierend auf dem ausgewählten Videotyp anzeigen/ausblenden
        function toggleVideoFields() {
            $('.mkr-video-group').each(function() {
                var group = $(this);
                var videoType = group.find('.mkr-video-type-select').val();
                
                // Alle Felder zurücksetzen
                group.find('.mkr-video-fields input').show();
                
                // Felder je nach Typ anpassen
                if (videoType === 'youtube' || videoType === 'vimeo') {
                    // Für YouTube/Vimeo brauchen wir kein Thumbnail, das wird automatisch geladen
                    if (videoType === 'youtube') {
                        group.find('.mkr-video-thumbnail-url').hide();
                    }
                } else if (videoType === 'custom') {
                    // Für benutzerdefinierte Videos alle Felder anzeigen
                }
            });
        }
        
        // Vorschaubild und andere Felder aktualisieren, wenn sich die URL ändert
        function updateVideoPreview(input) {
            var group = $(input).closest('.mkr-video-group');
            var videoType = group.find('.mkr-video-type-select').val();
            var url = $(input).val();
            var thumbnailPreview = group.find('.mkr-video-thumbnail-preview');
            var thumbnailUrlInput = group.find('.mkr-video-thumbnail-url');
            
            if (videoType === 'youtube' && url) {
                // YouTube-Vorschaubild laden
                var match = url.match(/(?:v=|\.be\/)([a-zA-Z0-9_-]{11})/);
                var videoId = match ? match[1] : null;
                
                if (videoId) {
                    var thumbnailUrl = 'https://img.youtube.com/vi/' + videoId + '/maxresdefault.jpg';
                    thumbnailPreview.attr('src', thumbnailUrl).show();
                // Auch den "Als Beitragsbild verwenden"-Button anzeigen
                group.find('.mkr-use-as-featured-image').show();
                    
                    // Thumbnail-URL automatisch setzen, wenn leer
                    if (!thumbnailUrlInput.val()) {
                        thumbnailUrlInput.val(thumbnailUrl);
                    }
                } else {
                    thumbnailPreview.hide();
                }
            } else if (videoType === 'vimeo' && url) {
                // Für Vimeo müssten wir das Vorschaubild über die Vimeo API laden,
                // aber aus Einfachheit überspringen wir das hier
                thumbnailPreview.hide();
            } else if (videoType === 'custom') {
                // Für benutzerdefinierte Videos das manuelle Vorschaubild anzeigen, wenn vorhanden
                var thumbnailUrl = thumbnailUrlInput.val();
                if (thumbnailUrl) {
                    thumbnailPreview.attr('src', thumbnailUrl).show();
                } else {
                    thumbnailPreview.hide();
                }
            } else {
                thumbnailPreview.hide();
            }
        }
        
        // Event-Handler für die Videotyp-Änderung
        $(document).on('change', '.mkr-video-type-select', function() {
            toggleVideoFields();
            updateVideoPreview($(this).closest('.mkr-video-group').find('.mkr-video-url'));
        });
        
        // Event-Handler für die URL-Änderung
        $(document).on('input', '.mkr-video-url', function() {
            updateVideoPreview(this);
        });
        
        // Event-Handler für die Thumbnail-URL-Änderung
        $(document).on('input', '.mkr-video-thumbnail-url', function() {
            var group = $(this).closest('.mkr-video-group');
            var thumbnailPreview = group.find('.mkr-video-thumbnail-preview');
            var thumbnailUrl = $(this).val();
            
            if (thumbnailUrl) {
                thumbnailPreview.attr('src', thumbnailUrl).show();
            } else {
                // Wenn keine manuelle URL eingegeben wurde, versuchen wir das YouTube-Vorschaubild
                updateVideoPreview(group.find('.mkr-video-url'));
            }
        });
        
        // Video hinzufügen
        $('#mkr-add-video').click(function() {
            var group = $(`
                <div class="mkr-video-group">
                    <div class="mkr-video-type-toggle">
                        <select name="mkr_video_type[]" class="mkr-video-type-select">
                            <option value="youtube"><?php _e('YouTube', 'mein-kochbuch-rezepte'); ?></option>
                            <option value="vimeo"><?php _e('Vimeo', 'mein-kochbuch-rezepte'); ?></option>
                            <option value="custom"><?php _e('Eigenes Video', 'mein-kochbuch-rezepte'); ?></option>
                        </select>
                    </div>
                    
                    <div class="mkr-video-fields">
                        <input type="url" name="mkr_video_url[]" value="" placeholder="<?php esc_attr_e('Video-URL eingeben', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-url" />
                        <input type="text" name="mkr_video_title[]" value="" placeholder="<?php esc_attr_e('Titel (optional)', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-title" />
                        <input type="url" name="mkr_video_thumbnail[]" value="" placeholder="<?php esc_attr_e('Vorschaubild-URL (optional)', 'mein-kochbuch-rezepte'); ?>" class="mkr-video-thumbnail-url" />
                    </div>
                    
                    <div class="mkr-video-preview">
                        <img class="mkr-video-thumbnail-preview" src="" alt="<?php esc_attr_e('Video-Vorschaubild', 'mein-kochbuch-rezepte'); ?>" style="max-width: 120px; display: none;" />
                    </div>
                    
                    <button type="button" class="button mkr-remove-video">
                        <?php _e('Entfernen', 'mein-kochbuch-rezepte'); ?>
                    </button>
                </div>
            `);
            
            group.appendTo('.mkr-videos-list');
            toggleVideoFields();
        });
        
        // Video entfernen
        $(document).on('click', '.mkr-remove-video', function() {
            $(this).closest('.mkr-video-group').remove();
        });
        
        // Thumbnail als Beitragsbild verwenden
        $(document).on('click', '.mkr-use-as-featured-image', function() {
            var thumbnailUrl = $(this).closest('.mkr-video-group').find('.mkr-video-thumbnail-preview').attr('src');
            if (thumbnailUrl) {
                // URL in ein Hidden Field speichern
                $('#mkr_thumbnail_for_featured_image').val(thumbnailUrl);
                
                // Visuelle Rückmeldung geben
                $('.mkr-use-as-featured-image').removeClass('button-primary').text('<?php _e("Als Beitragsbild verwenden", "mein-kochbuch-rezepte"); ?>');
                $(this).addClass('button-primary').text('<?php _e("Wird als Beitragsbild verwendet", "mein-kochbuch-rezepte"); ?>');
                
                // Benachrichtigung anzeigen
                if ($('.mkr-thumbnail-notice').length === 0) {
                    $('<div class="notice notice-info mkr-thumbnail-notice"><p><?php _e("Das Thumbnail wird als Beitragsbild gesetzt, sobald Sie das Rezept speichern.", "mein-kochbuch-rezepte"); ?></p></div>').insertBefore('.mkr-videos-container');
                }
            }
        });
        
        // Initialisierung
        toggleVideoFields();
    });
    </script>
    

    <?php
}

/**
 * Speichert die Video-Metadaten
 * Diese Funktion sollte in save_post_recipe in backend-metaboxes.php ersetzt werden
 */
function mkr_save_recipe_videos($post_id) {
    // Überprüfen, ob die Arrays vorhanden sind
    if (!isset($_POST['mkr_video_url']) || !is_array($_POST['mkr_video_url'])) {
        return;
    }
    
    $videos = array();
    $video_urls = $_POST['mkr_video_url'];
    $video_types = isset($_POST['mkr_video_type']) ? $_POST['mkr_video_type'] : array();
    $video_titles = isset($_POST['mkr_video_title']) ? $_POST['mkr_video_title'] : array();
    $video_thumbnails = isset($_POST['mkr_video_thumbnail']) ? $_POST['mkr_video_thumbnail'] : array();
    
    foreach ($video_urls as $index => $url) {
        if (empty($url)) {
            continue;
        }
        
        $video_data = array(
            'url' => esc_url_raw($url),
            'type' => isset($video_types[$index]) ? sanitize_text_field($video_types[$index]) : 'youtube',
            'title' => isset($video_titles[$index]) ? sanitize_text_field($video_titles[$index]) : '',
            'thumbnail' => isset($video_thumbnails[$index]) ? esc_url_raw($video_thumbnails[$index]) : '',
        );
        
        $videos[] = $video_data;
    }
    
    update_post_meta($post_id, '_mkr_videos', $videos);
    
    // Prüfen, ob ein Thumbnail als Beitragsbild gesetzt werden soll
    if (isset($_POST['mkr_thumbnail_for_featured_image']) && !empty($_POST['mkr_thumbnail_for_featured_image'])) {
        $thumbnail_url = esc_url_raw($_POST['mkr_thumbnail_for_featured_image']);
        
        // Prüfen, ob bereits ein Beitragsbild existiert
        if (!has_post_thumbnail($post_id)) {
            // Bild von URL importieren und als Beitragsbild setzen
            mkr_set_featured_image_from_url($post_id, $thumbnail_url);
        }
    }
}

/**
 * Importiert ein Bild von einer URL und setzt es als Beitragsbild
 *
 * @param int $post_id Beitrags-ID
 * @param string $image_url Bild-URL
 * @return bool Erfolg oder Misserfolg
 */
function mkr_set_featured_image_from_url($post_id, $image_url) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    // Temporäre Datei erstellen
    $tmp = download_url($image_url);
    
    // Fehler beim Download
    if (is_wp_error($tmp)) {
        return false;
    }
    
    // Dateinamen extrahieren
    $filename = basename($image_url);
    
    // Dateityp ermitteln
    $file_array = array(
        'name'     => $filename,
        'tmp_name' => $tmp
    );
    
    // Fehlerprüfung deaktivieren
    $old_error_reporting = error_reporting(0);
    
    // Datei in die Mediathek hochladen
    $attachment_id = media_handle_sideload($file_array, $post_id);
    
    // Fehlerberichterstattung wiederherstellen
    error_reporting($old_error_reporting);
    
    // Temporäre Datei löschen
    @unlink($tmp);
    
    // Fehler beim Hochladen
    if (is_wp_error($attachment_id)) {
        return false;
    }
    
    // Bild als Beitragsbild setzen
    set_post_thumbnail($post_id, $attachment_id);
    
    return true;
}