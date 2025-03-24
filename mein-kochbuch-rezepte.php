<?php
/*
Plugin Name: Mein Kochbuch Rezepte
Description: Ein Plugin für Rezepte mit YouTube-Videos, Zutaten-Lexikon, Utensilien-Lexikon, Fachbegriff-Lexikon, JSON-Import, Kalorienberechnung, SEO-Sitemap, Responsive Design, Barrierefreiheit, Verknüpfungen, Portionsrechner und Video-Sitemap.
Version: 3.7.5
Author: Sylvia Falbesoner
Text Domain: mein-kochbuch-rezepte
Domain Path: /languages
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Sicherheitsprüfung: Direkter Zugriff auf die Datei verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Composer Autoloader einbinden
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Hauptklasse des Plugins "Mein Kochbuch Rezepte"
 * Implementiert als Singleton, um nur eine Instanz zu gewährleisten
 */
class MeinKochbuchRezepte {
    const VERSION = '3.7.5';
    const TEXT_DOMAIN = 'mein-kochbuch-rezepte';
    private static $instance = null;

    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function define_constants() {
        define('MKR_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('MKR_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('MKR_VERSION', self::VERSION);
        define('MKR_TEXT_DOMAIN', self::TEXT_DOMAIN);
    }

    private function includes() {
        require_once MKR_PLUGIN_DIR . 'includes/import-functions.php';
        require_once MKR_PLUGIN_DIR . 'includes/frontend-display.php';
        require_once MKR_PLUGIN_DIR . 'includes/schema-markup.php';
        require_once MKR_PLUGIN_DIR . 'includes/backend-metaboxes.php';
        require_once MKR_PLUGIN_DIR . 'includes/backend-metaboxes-ingredients.php';
        require_once MKR_PLUGIN_DIR . 'includes/shopping-list.php';
        require_once MKR_PLUGIN_DIR . 'includes/meal-planner.php';
        require_once MKR_PLUGIN_DIR . 'includes/video-functions.php';
        require_once MKR_PLUGIN_DIR . 'includes/recipe-filters.php';
        require_once MKR_PLUGIN_DIR . 'includes/user-profile.php';
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activation_hook']);
        register_deactivation_hook(__FILE__, [$this, 'deactivation_hook']);
        
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_head', [$this, 'set_charset']);
        add_action('admin_menu', [$this, 'add_import_export_menu']);
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('init', [$this, 'optimize_queries']);
        add_action('init', [$this, 'enhance_security']);
        add_filter('single_template', [$this, 'load_custom_template']);
        add_filter('archive_template', [$this, 'load_archive_template']);
        add_filter('wp_sitemaps_post_types', [$this, 'add_custom_post_types_to_sitemap']);
        add_filter('wp_sitemaps_providers', [$this, 'add_video_sitemap_provider']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_shortcode('mkr_shopping_list', 'mkr_shopping_list_shortcode');
        add_shortcode('mkr_meal_planner', 'mkr_meal_planner_shortcode');
        add_shortcode('mkr_cooking_school', [$this, 'cooking_school_shortcode']);
        add_action('wp_ajax_mkr_get_inspiration', [$this, 'get_inspiration']);
        add_action('wp_ajax_nopriv_mkr_get_inspiration', [$this, 'get_inspiration']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }

    /**
     * Optimize database queries for better performance
     */
    public function optimize_queries() {
        // Use object caching for frequently accessed data
        add_filter('posts_where', [$this, 'optimize_recipe_queries']);
        
        // Implement transient caching for expensive operations
        if (!wp_using_ext_object_cache()) {
            add_action('save_post_recipe', [$this, 'flush_recipe_cache']);
            add_action('save_post_ingredient', [$this, 'flush_ingredient_cache']);
        }
    }

    /**
     * Optimize recipe queries with indexing hints
     */
    public function optimize_recipe_queries($where) {
        global $wpdb;
        if (is_post_type_archive('recipe') || is_tax(['cuisine', 'difficulty', 'diet', 'season'])) {
            // Add indexing hints for better performance on recipe archives
            $where = str_replace("post_type = 'recipe'", "post_type = 'recipe' USE INDEX (type_status_date)", $where);
        }
        return $where;
    }

    /**
     * Flush cache when recipes are updated
     */
    public function flush_recipe_cache($post_id) {
        delete_transient('mkr_popular_recipes');
        delete_transient('mkr_seasonal_recipes_' . date('n'));
        delete_transient('mkr_recent_recipes');
    }

    /**
     * Flush cache when ingredients are updated
     */
    public function flush_ingredient_cache($post_id) {
        delete_transient('mkr_ingredient_list');
    }

    /**
     * Enhance security with proper capability checks and nonce validation
     */
    public function enhance_security() {
        // Add nonce verification to AJAX handlers
        add_action('wp_ajax_nopriv_mkr_export_shopping_list_pdf', [$this, 'verify_ajax_nonce'], 1);
        add_action('wp_ajax_nopriv_mkr_order_shopping_list', [$this, 'verify_ajax_nonce'], 1);
    }

    /**
     * Verify AJAX nonces for better security
     */
    public function verify_ajax_nonce() {
        $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';
        $nonce_key = '';
        
        switch ($action) {
            case 'mkr_get_inspiration':
                $nonce_key = 'mkr_inspiration';
                break;
            case 'mkr_export_shopping_list_pdf':
                $nonce_key = 'mkr_export_shopping_list';
                break;
            case 'mkr_order_shopping_list':
                $nonce_key = 'mkr_shopping_list';
                break;
        }
        
        if (!empty($nonce_key) && (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], $nonce_key))) {
            wp_send_json_error(['message' => __('Security check failed.', 'mein-kochbuch-rezepte')]);
            die();
        }
    }

    /**
     * Enhanced sanitization for recipe data
     */
    public function sanitize_recipe_data($data) {
        if (!is_array($data)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_recipe_data($value);
            } else {
                // Sanitize based on key/context
                switch ($key) {
                    case 'title':
                    case 'name':
                        $sanitized[$key] = sanitize_text_field($value);
                        break;
                    case 'content':
                    case 'instructions':
                        $sanitized[$key] = wp_kses_post($value);
                        break;
                    case 'url':
                        $sanitized[$key] = esc_url_raw($value);
                        break;
                    default:
                        $sanitized[$key] = sanitize_text_field($value);
                }
            }
        }
        
        return $sanitized;
    }

    public function activation_hook() {
        // Erstelle notwendige Verzeichnisse, wenn sie nicht existieren
        $upload_dir = wp_upload_dir();
        $mkr_dir = $upload_dir['basedir'] . '/mein-kochbuch-rezepte';
        
        if (!file_exists($mkr_dir)) {
            wp_mkdir_p($mkr_dir);
        }
        
        // Initialisiere Taxonomien und Beitragstypen für korrekte Permalinks
        $this->register_post_types();
        $this->register_taxonomies();
        flush_rewrite_rules();
        
        // Erstelle Standard-Seiten
        $this->create_default_pages();
    }

    /**
     * Create default pages for the plugin
     */
    private function create_default_pages() {
        // Create Shopping List page if it doesn't exist
        $shopping_page = get_page_by_path('einkaufsliste');
        if (!$shopping_page) {
            $page_id = wp_insert_post([
                'post_title'     => __('Einkaufsliste', 'mein-kochbuch-rezepte'),
                'post_name'      => 'einkaufsliste',
                'post_content'   => '[mkr_shopping_list]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed'
            ]);
            update_option('mkr_shopping_list_page_id', $page_id);
        }
        
        // Create Meal Planner page if it doesn't exist
        $planner_page = get_page_by_path('mahlzeitenplaner');
        if (!$planner_page) {
            $page_id = wp_insert_post([
                'post_title'     => __('Mahlzeitenplaner', 'mein-kochbuch-rezepte'),
                'post_name'      => 'mahlzeitenplaner',
                'post_content'   => '[mkr_meal_planner]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed'
            ]);
            update_option('mkr_meal_planner_page_id', $page_id);
        }
        
        // Create Cooking School page if it doesn't exist
        $school_page = get_page_by_path('kochschule');
        if (!$school_page) {
            $page_id = wp_insert_post([
                'post_title'     => __('Interaktive Kochschule', 'mein-kochbuch-rezepte'),
                'post_name'      => 'kochschule',
                'post_content'   => '[mkr_cooking_school]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed'
            ]);
            update_option('mkr_cooking_school_page_id', $page_id);
        }
    }

    public function deactivation_hook() {
        flush_rewrite_rules();
    }
    
    public function enqueue_assets() {
        // Allgemeine Styles und Scripts
        wp_enqueue_style('mkr-style', MKR_PLUGIN_URL . 'assets/css/style.css', [], MKR_VERSION);
        
        // jQuery UI für Autocomplete
        wp_enqueue_style('jquery-ui', '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
        wp_enqueue_script('jquery-ui-autocomplete');
        
        // Plugin-spezifische Scripts
        wp_enqueue_script('mkr-portionsrechner', MKR_PLUGIN_URL . 'assets/js/portionsrechner.js', ['jquery'], MKR_VERSION, true);
        wp_enqueue_script('mkr-autocomplete', MKR_PLUGIN_URL . 'assets/js/autocomplete.js', ['jquery', 'jquery-ui-autocomplete'], MKR_VERSION, true);
        wp_enqueue_script('mkr-dark-mode', MKR_PLUGIN_URL . 'assets/js/dark-mode.js', ['jquery'], MKR_VERSION, true);
        wp_enqueue_script('mkr-settings-menu', MKR_PLUGIN_URL . 'assets/js/settings-menu.js', ['jquery'], MKR_VERSION, true);
        wp_enqueue_script('mkr-shopping-list', MKR_PLUGIN_URL . 'assets/js/shopping-list.js', ['jquery'], MKR_VERSION, true);

        // AJAX URLs und Nonces für JavaScript
        wp_localize_script('mkr-autocomplete', 'mkrAutocomplete', [
            'restUrl' => rest_url('mkr/v1/'),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
        
        wp_localize_script('mkr-portionsrechner', 'mkrPortions', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mkr_portions')
        ]);
        
        wp_localize_script('mkr-shopping-list', 'mkrShoppingList', [
            'addedToList' => __('Zur Einkaufsliste hinzugefügt', 'mein-kochbuch-rezepte'),
            'viewList' => __('Einkaufsliste anzeigen', 'mein-kochbuch-rezepte'),
            'shoppingListPage' => get_permalink(get_option('mkr_shopping_list_page_id')),
            'nonce' => wp_create_nonce('mkr_shopping_list')
        ]);
    }

    public function enqueue_admin_assets($hook) {
        global $post;
        
        // Nur auf Post-Edit-Seiten für unsere benutzerdefinierten Post-Types
        if (('post.php' === $hook || 'post-new.php' === $hook) && isset($post) && 
            in_array($post->post_type, ['recipe', 'ingredient', 'utensil', 'glossary'])) {
            
            wp_enqueue_style('mkr-admin-style', MKR_PLUGIN_URL . 'assets/css/admin.css', [], MKR_VERSION);
            wp_enqueue_script('mkr-admin', MKR_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'jquery-ui-autocomplete'], MKR_VERSION, true);
            
            wp_localize_script('mkr-admin', 'mkrAdmin', [
                'restUrl' => rest_url('mkr/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'pluginUrl' => MKR_PLUGIN_URL
            ]);
        }
    }

    public function set_charset() {
        echo '<meta charset="UTF-8">';
    }

    public function add_import_export_menu() {
        add_menu_page(
            __('Rezept-Import', MKR_TEXT_DOMAIN), 
            __('Rezept-Tools', MKR_TEXT_DOMAIN), 
            'manage_options', 
            'mkr-import', 
            'mkr_import_page', 
            'dashicons-carrot', 
            20
        );
        
        add_submenu_page(
            'mkr-import', 
            __('Rezept-Import', MKR_TEXT_DOMAIN), 
            __('Import', MKR_TEXT_DOMAIN), 
            'manage_options', 
            'mkr-import', 
            'mkr_import_page'
        );
        
        add_submenu_page(
            'mkr-import', 
            __('Rezept-Export', MKR_TEXT_DOMAIN), 
            __('Export', MKR_TEXT_DOMAIN), 
            'manage_options', 
            'mkr-export', 
            'mkr_export_page'
        );
        
        // Add a settings page
        add_submenu_page(
            'mkr-import',
            __('Einstellungen', MKR_TEXT_DOMAIN),
            __('Einstellungen', MKR_TEXT_DOMAIN),
            'manage_options',
            'mkr-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Render the plugin settings page
     */
    public function render_settings_page() {
        // Include the settings page template
        include MKR_PLUGIN_DIR . 'templates/settings-page.php';
    }

    public function register_post_types() {
        register_post_type('recipe', [
            'labels' => [
                'name' => __('Rezepte', MKR_TEXT_DOMAIN), 
                'singular_name' => __('Rezept', MKR_TEXT_DOMAIN),
                'add_new' => __('Neues Rezept', MKR_TEXT_DOMAIN),
                'add_new_item' => __('Neues Rezept hinzufügen', MKR_TEXT_DOMAIN),
                'edit_item' => __('Rezept bearbeiten', MKR_TEXT_DOMAIN),
                'new_item' => __('Neues Rezept', MKR_TEXT_DOMAIN),
                'view_item' => __('Rezept ansehen', MKR_TEXT_DOMAIN),
                'search_items' => __('Rezepte suchen', MKR_TEXT_DOMAIN),
                'not_found' => __('Keine Rezepte gefunden', MKR_TEXT_DOMAIN),
                'not_found_in_trash' => __('Keine Rezepte im Papierkorb gefunden', MKR_TEXT_DOMAIN),
                'all_items' => __('Alle Rezepte', MKR_TEXT_DOMAIN)
            ],
            'public' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'comments'],
            'menu_icon' => 'dashicons-carrot',
            'has_archive' => true,
            'rewrite' => ['slug' => 'rezepte'],
            'show_in_rest' => true,
            'menu_position' => 5,
            'capability_type' => 'post',
            'hierarchical' => false
        ]);
        
        register_post_type('ingredient', [
            'labels' => [
                'name' => __('Zutaten-Lexikon', MKR_TEXT_DOMAIN), 
                'singular_name' => __('Zutat', MKR_TEXT_DOMAIN),
                'add_new' => __('Neue Zutat', MKR_TEXT_DOMAIN),
                'add_new_item' => __('Neue Zutat hinzufügen', MKR_TEXT_DOMAIN),
                'edit_item' => __('Zutat bearbeiten', MKR_TEXT_DOMAIN),
                'new_item' => __('Neue Zutat', MKR_TEXT_DOMAIN),
                'view_item' => __('Zutat ansehen', MKR_TEXT_DOMAIN),
                'search_items' => __('Zutaten suchen', MKR_TEXT_DOMAIN),
                'not_found' => __('Keine Zutaten gefunden', MKR_TEXT_DOMAIN),
                'not_found_in_trash' => __('Keine Zutaten im Papierkorb gefunden', MKR_TEXT_DOMAIN),
                'all_items' => __('Alle Zutaten', MKR_TEXT_DOMAIN)
            ],
            'public' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon' => 'dashicons-food',
            'has_archive' => true,
            'rewrite' => ['slug' => 'zutaten'],
            'show_in_rest' => true,
            'menu_position' => 6
        ]);
        
        register_post_type('utensil', [
            'labels' => [
                'name' => __('Utensilien-Lexikon', MKR_TEXT_DOMAIN), 
                'singular_name' => __('Utensil', MKR_TEXT_DOMAIN),
                'add_new' => __('Neues Utensil', MKR_TEXT_DOMAIN),
                'add_new_item' => __('Neues Utensil hinzufügen', MKR_TEXT_DOMAIN),
                'edit_item' => __('Utensil bearbeiten', MKR_TEXT_DOMAIN),
                'new_item' => __('Neues Utensil', MKR_TEXT_DOMAIN),
                'view_item' => __('Utensil ansehen', MKR_TEXT_DOMAIN),
                'search_items' => __('Utensilien suchen', MKR_TEXT_DOMAIN),
                'not_found' => __('Keine Utensilien gefunden', MKR_TEXT_DOMAIN),
                'not_found_in_trash' => __('Keine Utensilien im Papierkorb gefunden', MKR_TEXT_DOMAIN),
                'all_items' => __('Alle Utensilien', MKR_TEXT_DOMAIN)
            ],
            'public' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon' => 'dashicons-hammer',
            'has_archive' => true,
            'rewrite' => ['slug' => 'utensilien'],
            'show_in_rest' => true,
            'menu_position' => 7
        ]);
        
        register_post_type('glossary', [
            'labels' => [
                'name' => __('Fachbegriff-Lexikon', MKR_TEXT_DOMAIN), 
                'singular_name' => __('Fachbegriff', MKR_TEXT_DOMAIN),
                'add_new' => __('Neuer Fachbegriff', MKR_TEXT_DOMAIN),
                'add_new_item' => __('Neuen Fachbegriff hinzufügen', MKR_TEXT_DOMAIN),
                'edit_item' => __('Fachbegriff bearbeiten', MKR_TEXT_DOMAIN),
                'new_item' => __('Neuer Fachbegriff', MKR_TEXT_DOMAIN),
                'view_item' => __('Fachbegriff ansehen', MKR_TEXT_DOMAIN),
                'search_items' => __('Fachbegriffe suchen', MKR_TEXT_DOMAIN),
                'not_found' => __('Keine Fachbegriffe gefunden', MKR_TEXT_DOMAIN),
                'not_found_in_trash' => __('Keine Fachbegriffe im Papierkorb gefunden', MKR_TEXT_DOMAIN),
                'all_items' => __('Alle Fachbegriffe', MKR_TEXT_DOMAIN)
            ],
            'public' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon' => 'dashicons-book',
            'has_archive' => true,
            'rewrite' => ['slug' => 'fachbegriffe'],
            'show_in_rest' => true,
            'menu_position' => 8
        ]);
    }

    public function register_taxonomies() {
        register_taxonomy('difficulty', 'recipe', [
            'labels' => [
                'name' => __('Schwierigkeitsgrad', MKR_TEXT_DOMAIN),
                'singular_name' => __('Schwierigkeitsgrad', MKR_TEXT_DOMAIN),
                'search_items' => __('Schwierigkeitsgrade suchen', MKR_TEXT_DOMAIN),
                'all_items' => __('Alle Schwierigkeitsgrade', MKR_TEXT_DOMAIN),
                'edit_item' => __('Schwierigkeitsgrad bearbeiten', MKR_TEXT_DOMAIN),
                'update_item' => __('Schwierigkeitsgrad aktualisieren', MKR_TEXT_DOMAIN),
                'add_new_item' => __('Neuen Schwierigkeitsgrad hinzufügen', MKR_TEXT_DOMAIN),
                'new_item_name' => __('Name des neuen Schwierigkeitsgrads', MKR_TEXT_DOMAIN),
                'menu_name' => __('Schwierigkeitsgrad', MKR_TEXT_DOMAIN)
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'schwierigkeitsgrad'],
            'show_admin_column' => true
        ]);
        
        register_taxonomy('diet', 'recipe', [
            'labels' => [
                'name' => __('Diätvorgaben', MKR_TEXT_DOMAIN),
                'singular_name' => __('Diätvorgabe', MKR_TEXT_DOMAIN),
                'search_items' => __('Diätvorgaben suchen', MKR_TEXT_DOMAIN),
                'all_items' => __('Alle Diätvorgaben', MKR_TEXT_DOMAIN),
                'edit_item' => __('Diätvorgabe bearbeiten', MKR_TEXT_DOMAIN),
                'update_item' => __('Diätvorgabe aktualisieren', MKR_TEXT_DOMAIN),
                'add_new_item' => __('Neue Diätvorgabe hinzufügen', MKR_TEXT_DOMAIN),
                'new_item_name' => __('Name der neuen Diätvorgabe', MKR_TEXT_DOMAIN),
                'menu_name' => __('Diätvorgaben', MKR_TEXT_DOMAIN)
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'diaet'],
            'show_admin_column' => true
        ]);
        
        register_taxonomy('cuisine', 'recipe', [
            'labels' => [
                'name' => __('Küchenart', MKR_TEXT_DOMAIN),
                'singular_name' => __('Küchenart', MKR_TEXT_DOMAIN),
                'search_items' => __('Küchenarten suchen', MKR_TEXT_DOMAIN),
                'all_items' => __('Alle Küchenarten', MKR_TEXT_DOMAIN),
                'edit_item' => __('Küchenart bearbeiten', MKR_TEXT_DOMAIN),
                'update_item' => __('Küchenart aktualisieren', MKR_TEXT_DOMAIN),
                'add_new_item' => __('Neue Küchenart hinzufügen', MKR_TEXT_DOMAIN),
                'new_item_name' => __('Name der neuen Küchenart', MKR_TEXT_DOMAIN),
                'menu_name' => __('Küchenarten', MKR_TEXT_DOMAIN)
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'kuechenart'],
            'show_admin_column' => true
        ]);
        
        register_taxonomy('season', 'recipe', [
            'labels' => [
                'name' => __('Saison', MKR_TEXT_DOMAIN),
                'singular_name' => __('Saison', MKR_TEXT_DOMAIN),
                'search_items' => __('Saisonen suchen', MKR_TEXT_DOMAIN),
                'all_items' => __('Alle Saisonen', MKR_TEXT_DOMAIN),
                'edit_item' => __('Saison bearbeiten', MKR_TEXT_DOMAIN),
                'update_item' => __('Saison aktualisieren', MKR_TEXT_DOMAIN),
                'add_new_item' => __('Neue Saison hinzufügen', MKR_TEXT_DOMAIN),
                'new_item_name' => __('Name der neuen Saison', MKR_TEXT_DOMAIN),
                'menu_name' => __('Saisonen', MKR_TEXT_DOMAIN)
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'saison'],
            'show_admin_column' => true
        ]);
        
        // Neue Taxonomie für Mahlzeiten-Typ
        register_taxonomy('meal_type', 'recipe', [
            'labels' => [
                'name' => __('Mahlzeitentyp', MKR_TEXT_DOMAIN),
                'singular_name' => __('Mahlzeitentyp', MKR_TEXT_DOMAIN),
                'search_items' => __('Mahlzeitentypen suchen', MKR_TEXT_DOMAIN),
                'all_items' => __('Alle Mahlzeitentypen', MKR_TEXT_DOMAIN),
                'edit_item' => __('Mahlzeitentyp bearbeiten', MKR_TEXT_DOMAIN),
                'update_item' => __('Mahlzeitentyp aktualisieren', MKR_TEXT_DOMAIN),
                'add_new_item' => __('Neuen Mahlzeitentyp hinzufügen', MKR_TEXT_DOMAIN),
                'new_item_name' => __('Name des neuen Mahlzeitentyps', MKR_TEXT_DOMAIN),
                'menu_name' => __('Mahlzeitentypen', MKR_TEXT_DOMAIN)
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'mahlzeitentyp'],
            'show_admin_column' => true
        ]);
        
        // Standardwerte für Taxonomien erstellen, falls sie nicht existieren
        $this->create_default_taxonomy_terms();
    }
    
    private function create_default_taxonomy_terms() {
        $default_terms = [
            'difficulty' => ['Einfach', 'Mittel', 'Schwierig'],
            'diet' => ['Vegan', 'Vegetarisch', 'Glutenfrei', 'Laktosefrei', 'Low Carb'],
            'cuisine' => ['Deutsch', 'Italienisch', 'Französisch', 'Asiatisch', 'Amerikanisch'],
            'season' => ['Frühling', 'Sommer', 'Herbst', 'Winter', 'Ganzjährig'],
            'meal_type' => ['Frühstück', 'Mittagessen', 'Abendessen', 'Snack', 'Dessert', 'Beilage']
        ];
        
        foreach ($default_terms as $taxonomy => $terms) {
            foreach ($terms as $term) {
                if (!term_exists($term, $taxonomy)) {
                    wp_insert_term($term, $taxonomy);
                }
            }
        }
    }

    public function load_custom_template($template) {
        if (is_singular('recipe')) {
            $plugin_template = MKR_PLUGIN_DIR . 'templates/recipe-template.php';
            if (file_exists($plugin_template)) return $plugin_template;
        } elseif (is_singular('ingredient')) {
            $plugin_template = MKR_PLUGIN_DIR . 'templates/ingredient-template.php';
            if (file_exists($plugin_template)) return $plugin_template;
        } elseif (is_singular('utensil')) {
            $plugin_template = MKR_PLUGIN_DIR . 'templates/utensil-template.php';
            if (file_exists($plugin_template)) return $plugin_template;
        } elseif (is_singular('glossary')) {
            $plugin_template = MKR_PLUGIN_DIR . 'templates/term-template.php';
            if (file_exists($plugin_template)) return $plugin_template;
        }
        return $template;
    }

    public function load_archive_template($template) {
        if (is_post_type_archive('recipe')) {
            $plugin_template = MKR_PLUGIN_DIR . 'templates/archive-recipe.php';
            if (file_exists($plugin_template)) return $plugin_template;
        } elseif (is_post_type_archive('ingredient')) {
            $plugin_template = MKR_PLUGIN_DIR . 'templates/archive-ingredient.php';
            if (file_exists($plugin_template)) return $plugin_template;
        } elseif (is_post_type_archive('utensil')) {
            $plugin_template = MKR_PLUGIN_DIR . 'templates/archive-utensil.php';
            if (file_exists($plugin_template)) return $plugin_template;
        } elseif (is_post_type_archive('glossary')) {
            $plugin_template = MKR_PLUGIN_DIR . 'templates/archive-glossary.php';
            if (file_exists($plugin_template)) return $plugin_template;
        }
        return $template;
    }

    public function add_custom_post_types_to_sitemap($post_types) {
        $post_types['recipe'] = get_post_type_object('recipe');
        $post_types['ingredient'] = get_post_type_object('ingredient');
        $post_types['utensil'] = get_post_type_object('utensil');
        $post_types['glossary'] = get_post_type_object('glossary');
        return $post_types;
    }

    public function add_video_sitemap_provider($providers) {
        // Die Video-Sitemap-Provider-Klasse wird nun direkt in video-functions.php definiert
        $providers['video'] = new MKR_Video_Sitemap_Provider();
        return $providers;
    }

    public function register_rest_routes() {
        register_rest_route('mkr/v1', '/ingredients', [
            'methods' => 'GET',
            'callback' => [$this, 'get_ingredients'],
            'permission_callback' => '__return_true',
        ]);
        
        register_rest_route('mkr/v1', '/utensils', [
            'methods' => 'GET',
            'callback' => [$this, 'get_utensils'],
            'permission_callback' => '__return_true',
        ]);
        
        register_rest_route('mkr/v1', '/recipes/search', [
            'methods' => 'GET',
            'callback' => [$this, 'search_recipes'],
            'permission_callback' => '__return_true',
        ]);
        
        // Neue API-Route für saisonale Rezepte
        register_rest_route('mkr/v1', '/recipes/seasonal', [
            'methods' => 'GET',
            'callback' => [$this, 'get_seasonal_recipes'],
            'permission_callback' => '__return_true',
        ]);
        
        // Neue API-Route für Rezeptstatistiken
        register_rest_route('mkr/v1', '/recipes/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_recipe_stats'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ]);
    }

    public function get_ingredients($request) {
        $search = sanitize_text_field($request->get_param('search'));
        $per_page = intval($request->get_param('per_page')) ?: 10;
        
        // Verwende Transient-Caching für häufige Anfragen
        $cache_key = 'mkr_ingredients_' . md5($search . $per_page);
        $cached_results = get_transient($cache_key);
        
        if ($cached_results !== false) {
            return $cached_results;
        }
        
        $args = [
            'post_type' => 'ingredient',
            'posts_per_page' => $per_page,
            's' => $search,
        ];
        $query = new WP_Query($args);
        $results = [];
        
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'excerpt' => get_the_excerpt(),
            ];
        }
        
        wp_reset_postdata();
        
        // Cache für 1 Stunde speichern
        set_transient($cache_key, $results, HOUR_IN_SECONDS);
        
        return $results;
    }

    public function get_utensils($request) {
        $search = sanitize_text_field($request->get_param('search'));
        $per_page = intval($request->get_param('per_page')) ?: 10;
        
        // Verwende Transient-Caching für häufige Anfragen
        $cache_key = 'mkr_utensils_' . md5($search . $per_page);
        $cached_results = get_transient($cache_key);
        
        if ($cached_results !== false) {
            return $cached_results;
        }
        
        $args = [
            'post_type' => 'utensil',
            'posts_per_page' => $per_page,
            's' => $search,
        ];
        $query = new WP_Query($args);
        $results = [];
        
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'excerpt' => get_the_excerpt(),
            ];
        }
        
        wp_reset_postdata();
        
        // Cache für 1 Stunde speichern
        set_transient($cache_key, $results, HOUR_IN_SECONDS);
        
        return $results;
    }

    public function search_recipes($request) {
        $search = sanitize_text_field($request->get_param('search'));
        $per_page = intval($request->get_param('per_page')) ?: 10;
        $page = intval($request->get_param('page')) ?: 1;
        $cuisine = sanitize_text_field($request->get_param('cuisine'));
        $diet = sanitize_text_field($request->get_param('diet'));
        $difficulty = sanitize_text_field($request->get_param('difficulty'));
        $meal_type = sanitize_text_field($request->get_param('meal_type'));
        $max_time = intval($request->get_param('max_time'));
        
        $args = [
            'post_type' => 'recipe',
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => $search,
            'tax_query' => [],
        ];
        
        // Taxonomie-Filter hinzufügen
        if (!empty($cuisine)) {
            $args['tax_query'][] = [
                'taxonomy' => 'cuisine',
                'field' => 'slug',
                'terms' => $cuisine,
            ];
        }
        
        if (!empty($diet)) {
            $args['tax_query'][] = [
                'taxonomy' => 'diet',
                'field' => 'slug',
                'terms' => $diet,
            ];
        }
        
        if (!empty($difficulty)) {
            $args['tax_query'][] = [
                'taxonomy' => 'difficulty',
                'field' => 'slug',
                'terms' => $difficulty,
            ];
        }
        
        if (!empty($meal_type)) {
            $args['tax_query'][] = [
                'taxonomy' => 'meal_type',
                'field' => 'slug',
                'terms' => $meal_type,
            ];
        }
        
        // Zeit-Filter hinzufügen
        if ($max_time > 0) {
            $args['meta_query'] = [
                [
                    'key' => '_mkr_total_time',
                    'value' => $max_time,
                    'compare' => '<=',
                    'type' => 'NUMERIC',
                ],
            ];
        }
        
        $query = new WP_Query($args);
        $results = [];
        $total_pages = ceil($query->found_posts / $per_page);
        
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Metadaten abrufen
            $prep_time = get_post_meta($post_id, '_mkr_prep_time', true);
            $cook_time = get_post_meta($post_id, '_mkr_cook_time', true);
            $total_time = get_post_meta($post_id, '_mkr_total_time', true);
            $servings = get_post_meta($post_id, '_mkr_servings', true);
            $total_calories = get_post_meta($post_id, '_mkr_total_calories', true);
            
            $results[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                'excerpt' => get_the_excerpt(),
                'prep_time' => $prep_time,
                'cook_time' => $cook_time,
                'total_time' => $total_time,
                'servings' => $servings,
                'calories_per_serving' => $servings ? round($total_calories / $servings) : 0,
                'cuisines' => $this->get_taxonomy_terms($post_id, 'cuisine'),
                'diets' => $this->get_taxonomy_terms($post_id, 'diet'),
                'difficulty' => $this->get_taxonomy_terms($post_id, 'difficulty'),
                'meal_types' => $this->get_taxonomy_terms($post_id, 'meal_type'),
            ];
        }
        
        wp_reset_postdata();
        
        return [
            'success' => true,
            'data' => $results,
            'pagination' => [
                'total_items' => $query->found_posts,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page,
            ],
        ];
    }
    
    /**
     * Neue API-Methode für saisonale Rezepte
     */
    public function get_seasonal_recipes($request) {
        $month = intval($request->get_param('month')) ?: date('n');
        $limit = intval($request->get_param('limit')) ?: 5;
        
        // Transient-Cache für saisonale Rezepte
        $cache_key = 'mkr_seasonal_recipes_' . $month . '_' . $limit;
        $cached_results = get_transient($cache_key);
        
        if ($cached_results !== false) {
            return $cached_results;
        }
        
        // Season bestimmen
        $season = '';
        if ($month >= 3 && $month <= 5) {
            $season = 'frühling';
        } elseif ($month >= 6 && $month <= 8) {
            $season = 'sommer';
        } elseif ($month >= 9 && $month <= 11) {
            $season = 'herbst';
        } else {
            $season = 'winter';
        }
        
        // Rezepte der aktuellen Saison abrufen
        $args = [
            'post_type' => 'recipe',
            'posts_per_page' => $limit,
            'orderby' => 'rand',
            'tax_query' => [
                [
                    'taxonomy' => 'season',
                    'field' => 'slug',
                    'terms' => [$season, 'ganzjährig'],
                    'operator' => 'IN',
                ],
            ],
        ];
        
        $query = new WP_Query($args);
        $results = [];
        
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            $results[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                'excerpt' => get_the_excerpt(),
                'season' => $season,
            ];
        }
        
        wp_reset_postdata();
        
        // Cache für 12 Stunden speichern
        set_transient($cache_key, $results, 12 * HOUR_IN_SECONDS);
        
        return $results;
    }
    
    /**
     * Neue API-Methode für Rezeptstatistiken
     */
    public function get_recipe_stats($request) {
        // Nur für Administratoren und Editoren verfügbar
        if (!current_user_can('edit_posts')) {
            return new WP_Error('rest_forbidden', __('Sie haben nicht genügend Berechtigungen.', 'mein-kochbuch-rezepte'), ['status' => 401]);
        }
        
        // Statistiken sammeln
        $stats = [];
        
        // Gesamtzahl der Rezepte
        $stats['total_recipes'] = wp_count_posts('recipe')->publish;
        
        // Gesamtzahl der Zutaten und Utensilien
        $stats['total_ingredients'] = wp_count_posts('ingredient')->publish;
        $stats['total_utensils'] = wp_count_posts('utensil')->publish;
        
        // Durchschnittliche Zubereitungszeit
        $prep_times = $this->get_average_meta_value('recipe', '_mkr_total_time');
        $stats['avg_prep_time'] = $prep_times['avg'];
        
        // Durchschnittliche Kalorien pro Portion
        $avg_calories = $this->get_average_calories_per_serving();
        $stats['avg_calories_per_serving'] = $avg_calories;
        
        // Anzahl der Rezepte pro Taxonomie
        $taxonomies = ['difficulty', 'diet', 'cuisine', 'season', 'meal_type'];
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => true,
            ]);
            
            if (!is_wp_error($terms)) {
                $stats[$taxonomy] = [];
                foreach ($terms as $term) {
                    $count = $term->count;
                    $stats[$taxonomy][$term->slug] = [
                        'name' => $term->name,
                        'count' => $count,
                        'percentage' => $stats['total_recipes'] > 0 ? round(($count / $stats['total_recipes']) * 100, 1) : 0,
                    ];
                }
            }
        }
        
        // Beliebteste Rezepte (Basierend auf Kommentaren und Views)
        $popular_recipes = $this->get_popular_recipes(5);
        $stats['popular_recipes'] = $popular_recipes;
        
        return $stats;
    }
    
    /**
     * Hilfsmethode: Durchschnittswert eines Meta-Feldes berechnen
     */
    private function get_average_meta_value($post_type, $meta_key) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT AVG(meta_value) as avg, MIN(meta_value) as min, MAX(meta_value) as max
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status = 'publish'
            AND pm.meta_key = %s
            AND pm.meta_value != ''",
            $post_type,
            $meta_key
        );
        
        $result = $wpdb->get_row($query, ARRAY_A);
        
        return [
            'avg' => $result['avg'] ? round($result['avg'], 1) : 0,
            'min' => $result['min'] ? intval($result['min']) : 0,
            'max' => $result['max'] ? intval($result['max']) : 0,
        ];
    }
    
    /**
     * Hilfsmethode: Durchschnittliche Kalorien pro Portion berechnen
     */
    private function get_average_calories_per_serving() {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT AVG(c.meta_value / NULLIF(s.meta_value, 0)) as avg_calories
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} c ON p.ID = c.post_id AND c.meta_key = '_mkr_total_calories'
            JOIN {$wpdb->postmeta} s ON p.ID = s.post_id AND s.meta_key = '_mkr_servings'
            WHERE p.post_type = 'recipe'
            AND p.post_status = 'publish'
            AND c.meta_value != ''
            AND s.meta_value != ''"
        );
        
        $result = $wpdb->get_var($query);
        
        return $result ? round($result, 1) : 0;
    }
    
    /**
     * Hilfsmethode: Beliebte Rezepte abrufen
     */
    private function get_popular_recipes($limit = 5) {
        // Transient-Cache für beliebte Rezepte
        $cache_key = 'mkr_popular_recipes';
        $cached_results = get_transient($cache_key);
        
        if ($cached_results !== false) {
            return $cached_results;
        }
        
        // Rezepte nach Kommentaren und Views sortieren
        $args = [
            'post_type' => 'recipe',
            'posts_per_page' => $limit,
            'orderby' => 'comment_count',
            'order' => 'DESC',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_mkr_view_count',
                    'compare' => 'EXISTS',
                ],
                [
                    'key' => '_mkr_view_count',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];
        
        $query = new WP_Query($args);
        $results = [];
        
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            $results[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'comment_count' => get_comments_number($post_id),
                'view_count' => get_post_meta($post_id, '_mkr_view_count', true) ?: 0,
            ];
        }
        
        wp_reset_postdata();
        
        // Cache für 1 Tag speichern
        set_transient($cache_key, $results, DAY_IN_SECONDS);
        
        return $results;
    }
    
    /**
     * Hilfsmethode: Taxonomiebegriffe eines Beitrags abrufen
     */
    private function get_taxonomy_terms($post_id, $taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy);
        $term_data = [];
        
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $term_data[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }
        
        return $term_data;
    }

    public function cooking_school_shortcode($atts = []) {
        ob_start();
        include MKR_PLUGIN_DIR . 'templates/cooking-school.php';
        return ob_get_clean();
    }

    public function get_inspiration() {
        check_ajax_referer('mkr_inspiration', 'nonce');
        
        $hour = (int) date('H');
        $current_month = (int) date('m');
        
        // Ermittle die aktuelle Saison
        $season = '';
        if ($current_month >= 3 && $current_month <= 5) {
            $season = 'frühling'; // Frühling
        } elseif ($current_month >= 6 && $current_month <= 8) {
            $season = 'sommer'; // Sommer
        } elseif ($current_month >= 9 && $current_month <= 11) {
            $season = 'herbst'; // Herbst
        } else {
            $season = 'winter'; // Winter
        }
        
        // Ermittle Mahlzeitentyp basierend auf Tageszeit
        $meal_type = '';
        if ($hour >= 6 && $hour < 11) {
            $meal_type = 'frühstück'; // Frühstück
        } elseif ($hour >= 11 && $hour < 15) {
            $meal_type = 'mittagessen'; // Mittagessen
        } elseif ($hour >= 15 && $hour < 18) {
            $meal_type = 'snack'; // Snack
        } else {
            $meal_type = 'abendessen'; // Abendessen
        }
        
        // Argumente für die WP_Query
        $args = [
            'post_type' => 'recipe',
            'posts_per_page' => 1,
            'orderby' => 'rand',
            'tax_query' => [
                'relation' => 'AND',
                [
                    'taxonomy' => 'season',
                    'field' => 'slug',
                    'terms' => [$season, 'ganzjährig'],
                    'operator' => 'IN',
                ],
                [
                    'taxonomy' => 'meal_type',
                    'field' => 'slug',
                    'terms' => $meal_type,
                ]
            ],
        ];
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Zubereitungszeit und Schwierigkeitsgrad abrufen
            $total_time = get_post_meta($post_id, '_mkr_total_time', true);
            $difficulty_terms = get_the_terms($post_id, 'difficulty');
            $difficulty = !empty($difficulty_terms) ? $difficulty_terms[0]->name : '';
            
            wp_send_json_success([
                'id' => $post_id,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
                'excerpt' => get_the_excerpt(),
                'total_time' => $total_time,
                'difficulty' => $difficulty,
                'season' => $season,
                'meal_type' => $meal_type
            ]);
        } else {
            // Fallback: Zufälliges Rezept ohne Saisonberücksichtigung
            $fallback_query = new WP_Query([
                'post_type' => 'recipe',
                'posts_per_page' => 1,
                'orderby' => 'rand',
            ]);
            
            if ($fallback_query->have_posts()) {
                $fallback_query->the_post();
                wp_send_json_success([
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                    'excerpt' => get_the_excerpt()
                ]);
            } else {
                wp_send_json_error(['message' => __('Keine Rezepte gefunden.', MKR_TEXT_DOMAIN)]);
            }
        }
        
        wp_die();
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            MKR_TEXT_DOMAIN, 
            false, 
            basename(dirname(__FILE__)) . '/languages'
        );
    }
}

// Plugin-Instanz erstellen
MeinKochbuchRezepte::instance();