<?php
/**
 * Video-Funktionen für Mein Kochbuch Rezepte
 */

// Sicherheitsprüfung: Direkter Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

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
        foreach ($videos as $index => $video_url) {
            $video_id = $this->extract_youtube_id($video_url);
            
            if (!$video_id) {
                continue;
            }
            
            $video_entry = array(
                'thumbnail_loc' => 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg',
                'title' => $post->post_title . ' - ' . __('Video', 'mein-kochbuch-rezepte') . ' ' . ($index + 1),
                'description' => wp_trim_words($post->post_content, 30, '...'),
                'content_loc' => '',
                'player_loc' => 'https://www.youtube.com/embed/' . $video_id,
                'duration' => 0, // Nicht bekannt ohne YouTube API
                'publication_date' => get_the_date('c', $post_id),
                'family_friendly' => 'yes',
                'requires_subscription' => 'no',
                'platform' => 'web mobile',
                'live' => 'no',
            );
            
            // Zum Sitemap-Eintrag hinzufügen
            $entry['video'][] = $video_entry;
        }
        
        return $entry;
    }
    
    /**
     * Extrahiert die YouTube-Video-ID aus einer URL
     *
     * @param string $url Die YouTube-URL
     * @return string|false Die Video-ID oder false, wenn keine gefunden wurde
     */
    private function extract_youtube_id($url) {
        $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\s*[^\/\n\s]+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        
        return false;
    }
}

/**
 * Diese Funktion wurde entfernt, da sie in backend-metaboxes.php bereits definiert ist
 * Bitte nutzen Sie stattdessen die dort definierte Funktion 'mkr_get_youtube_thumbnail'
 */

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
 * Extrahiert Metadaten aus einem YouTube-Video
 *
 * @param string $video_id Die YouTube-Video-ID
 * @return array Die Metadaten des Videos
 */
function mkr_get_youtube_metadata($video_id) {
    // YouTube API-Schlüssel aus den Einstellungen holen
    $api_key = get_option('mkr_youtube_api_key');
    
    // Wenn kein API-Schlüssel vorhanden ist, leere Metadaten zurückgeben
    if (empty($api_key)) {
        return array(
            'title' => '',
            'description' => '',
            'duration' => 0,
            'thumbnail' => "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg",
        );
    }
    
    // YouTube API-Aufruf
    $api_url = "https://www.googleapis.com/youtube/v3/videos?id={$video_id}&key={$api_key}&part=snippet,contentDetails";
    $response = wp_remote_get($api_url);
    
    // Fehlerbehandlung
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return array(
            'title' => '',
            'description' => '',
            'duration' => 0,
            'thumbnail' => "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg",
        );
    }
    
    // Antwort auswerten
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (empty($data['items'][0])) {
        return array(
            'title' => '',
            'description' => '',
            'duration' => 0,
            'thumbnail' => "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg",
        );
    }
    
    $video_data = $data['items'][0];
    
    // Dauer im ISO 8601-Format in Sekunden umwandeln
    $duration_iso = isset($video_data['contentDetails']['duration']) ? $video_data['contentDetails']['duration'] : 'PT0S';
    $duration_seconds = mkr_convert_iso8601_to_seconds($duration_iso);
    
    // Thumbnail aus verschiedenen Auflösungen wählen
    $thumbnail_url = "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg"; // Standard-Fallback
    
    if (isset($video_data['snippet']['thumbnails'])) {
        $thumbnails = $video_data['snippet']['thumbnails'];
        
        if (isset($thumbnails['maxres'])) {
            $thumbnail_url = $thumbnails['maxres']['url'];
        } elseif (isset($thumbnails['high'])) {
            $thumbnail_url = $thumbnails['high']['url'];
        } elseif (isset($thumbnails['medium'])) {
            $thumbnail_url = $thumbnails['medium']['url'];
        } elseif (isset($thumbnails['default'])) {
            $thumbnail_url = $thumbnails['default']['url'];
        }
    }
    
    return array(
        'title' => isset($video_data['snippet']['title']) ? $video_data['snippet']['title'] : '',
        'description' => isset($video_data['snippet']['description']) ? $video_data['snippet']['description'] : '',
        'duration' => $duration_seconds,
        'thumbnail' => $thumbnail_url,
    );
}

/**
 * Wandelt eine ISO 8601-Dauer in Sekunden um
 *
 * @param string $iso8601_duration Die Dauer im ISO 8601-Format (z.B. PT5M30S)
 * @return int Die Dauer in Sekunden
 */
function mkr_convert_iso8601_to_seconds($iso8601_duration) {
    $pattern = '/^P(?:([0-9]+)D)?(?:T(?:([0-9]+)H)?(?:([0-9]+)M)?(?:([0-9]+)S)?)?$/';
    preg_match($pattern, $iso8601_duration, $matches);
    
    $days = isset($matches[1]) ? (int) $matches[1] : 0;
    $hours = isset($matches[2]) ? (int) $matches[2] : 0;
    $minutes = isset($matches[3]) ? (int) $matches[3] : 0;
    $seconds = isset($matches[4]) ? (int) $matches[4] : 0;
    
    return $days * 86400 + $hours * 3600 + $minutes * 60 + $seconds;
}

/**
 * Fügt ein Einstellungsfeld für den YouTube API-Schlüssel hinzu
 */
function mkr_register_youtube_api_settings() {
    register_setting('general', 'mkr_youtube_api_key', array(
        'type' => 'string',
        'description' => __('YouTube API-Schlüssel für erweiterte Videofunktionen', 'mein-kochbuch-rezepte'),
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    add_settings_field(
        'mkr_youtube_api_key',
        __('YouTube API-Schlüssel', 'mein-kochbuch-rezepte'),
        'mkr_youtube_api_field_callback',
        'general',
        'default',
        array('label_for' => 'mkr_youtube_api_key')
    );
}
add_action('admin_init', 'mkr_register_youtube_api_settings');

/**
 * Callback für das YouTube API-Schlüssel-Einstellungsfeld
 */
function mkr_youtube_api_field_callback($args) {
    $api_key = get_option('mkr_youtube_api_key');
    
    ?>
    <input type="text" id="<?php echo esc_attr($args['label_for']); ?>" name="<?php echo esc_attr($args['label_for']); ?>" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
    <p class="description">
        <?php _e('Geben Sie hier Ihren YouTube API-Schlüssel ein, um erweiterte Videoinformationen abzurufen.', 'mein-kochbuch-rezepte'); ?>
        <a href="https://developers.google.com/youtube/v3/getting-started" target="_blank"><?php _e('Hier erhalten Sie einen API-Schlüssel', 'mein-kochbuch-rezepte'); ?></a>
    </p>
    <?php
}

/**
 * Shortcode für die Anzeige eines YouTube-Videos
 *
 * @param array $atts Die Shortcode-Attribute
 * @return string Das HTML für die Videoeinbettung
 */
function mkr_youtube_video_shortcode($atts) {
    $atts = shortcode_atts(array(
        'url' => '',
        'width' => 560,
        'height' => 315,
        'autoplay' => 0,
        'controls' => 1,
        'rel' => 0,
    ), $atts, 'mkr_youtube');
    
    // URL validieren
    $video_id = mkr_extract_youtube_id($atts['url']);
    
    if (!$video_id) {
        return '<div class="mkr-error">' . __('Ungültige YouTube-URL', 'mein-kochbuch-rezepte') . '</div>';
    }
    
    // Parameter für das eingebettete Video
    $params = array(
        'autoplay' => (int) $atts['autoplay'],
        'controls' => (int) $atts['controls'],
        'rel' => (int) $atts['rel'],
    );
    
    // Parameter in einen Query-String umwandeln
    $query_string = http_build_query($params);
    
    // HTML für das eingebettete Video erstellen
    $html = sprintf(
        '<div class="mkr-video-container" style="position: relative; padding-bottom: 56.25%%; height: 0; overflow: hidden; max-width: 100%%;">
            <iframe style="position: absolute; top: 0; left: 0; width: 100%%; height: 100%%;" width="%d" height="%d" src="https://www.youtube.com/embed/%s?%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>',
        (int) $atts['width'],
        (int) $atts['height'],
        esc_attr($video_id),
        esc_attr($query_string)
    );
    
    return $html;
}
add_shortcode('mkr_youtube', 'mkr_youtube_video_shortcode');

/**
 * Registriert ein Block für das Video-Shortcode im Gutenberg-Editor
 */
function mkr_register_youtube_block() {
    // Nur laden, wenn der Block-Editor verfügbar ist
    if (!function_exists('register_block_type')) {
        return;
    }
    
    wp_register_script(
        'mkr-youtube-block',
        MKR_PLUGIN_URL . 'assets/js/youtube-block.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
        MKR_VERSION
    );
    
    register_block_type('mkr/youtube-video', array(
        'editor_script' => 'mkr-youtube-block',
        'render_callback' => 'mkr_youtube_video_shortcode',
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
            'rel' => array(
                'type' => 'boolean',
                'default' => false,
            ),
        ),
    ));
}
add_action('init', 'mkr_register_youtube_block');