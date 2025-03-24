<?php
/**
 * Funktionen für die Einkaufsliste
 */

// Sicherheitsprüfung: Direkter Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registriert den Shortcode für die Einkaufsliste
 */
function mkr_shopping_list_shortcode() {
    ob_start();
    include MKR_PLUGIN_DIR . 'templates/shopping-list-template.php';
    return ob_get_clean();
}
add_shortcode('mkr_shopping_list', 'mkr_shopping_list_shortcode');

/**
 * Erstellt eine Seite für die Einkaufsliste beim Plugin-Aktivieren, falls noch nicht vorhanden
 */
function mkr_create_shopping_list_page() {
    // Prüfen, ob die Seite bereits existiert (nach Slug)
    $shopping_page = get_page_by_path('einkaufsliste');
    
    // Wenn die Seite noch nicht existiert, erstellen
    if (!$shopping_page) {
        $page_id = wp_insert_post(
            array(
                'post_title'     => __('Einkaufsliste', 'mein-kochbuch-rezepte'),
                'post_name'      => 'einkaufsliste',
                'post_content'   => '[mkr_shopping_list]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed'
            )
        );
        
        update_option('mkr_shopping_list_page_id', $page_id);
    } else {
        update_option('mkr_shopping_list_page_id', $shopping_page->ID);
    }
}
register_activation_hook(MKR_PLUGIN_DIR . 'mein-kochbuch-rezepte.php', 'mkr_create_shopping_list_page');

/**
 * AJAX-Handler für den Export der Einkaufsliste als PDF
 */
function mkr_export_shopping_list_pdf() {
    // Sicherheitsüberprüfung
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mkr_export_shopping_list')) {
        wp_send_json_error(array('message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    // Überprüfen, ob Elemente vorhanden sind
    if (!isset($_POST['items']) || empty($_POST['items'])) {
        wp_send_json_error(array('message' => __('Keine Elemente zum Exportieren gefunden.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    $items = json_decode(stripslashes($_POST['items']), true);
    
    if (!is_array($items)) {
        wp_send_json_error(array('message' => __('Ungültiges Format der Elemente.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    // TCPDF-Library laden
    require_once MKR_PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php';
    
    // Neues PDF-Dokument erstellen
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // PDF-Metadaten setzen
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(get_bloginfo('name'));
    $pdf->SetTitle(__('Einkaufsliste', 'mein-kochbuch-rezepte'));
    $pdf->SetSubject(__('Einkaufsliste', 'mein-kochbuch-rezepte'));
    $pdf->SetKeywords(__('Einkaufsliste, Rezepte, Zutaten', 'mein-kochbuch-rezepte'));
    
    // Standardschriftarten setzen
    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Standardmonospace-Schriftart setzen
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Ränder setzen
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Automatische Seitenumbrüche
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
    
    // Bildmaßstab
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Schriften festlegen
    if (is_file(dirname(__FILE__).'/custom/tcpdf_config.php')) {
        require_once(dirname(__FILE__).'/custom/tcpdf_config.php');
    }
    
    // Erste Seite hinzufügen
    $pdf->AddPage();
    
    // Unicode-Unterstützung aktivieren
    $pdf->SetFont('dejavusans', '', 12);
    
    // Titel
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, __('Einkaufsliste', 'mein-kochbuch-rezepte'), 0, 1, 'C');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 10, __('Erstellt am ', 'mein-kochbuch-rezepte') . date_i18n(get_option('date_format')), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Standardschriftart für den Inhalt
    $pdf->SetFont('dejavusans', '', 12);
    
    // Elemente sortieren und nach Kategorien gruppieren, falls möglich
    $categorized_items = mkr_categorize_shopping_items($items);
    
    // Kategorisierte Elemente ausgeben
    foreach ($categorized_items as $category => $category_items) {
        if (!empty($category_items)) {
            // Kategorie ausgeben
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 10, $category, 0, 1);
            $pdf->SetFont('dejavusans', '', 12);
            
            // Elemente der Kategorie ausgeben
            foreach ($category_items as $item) {
                $pdf->Cell(10, 10, '□', 0, 0);
                $pdf->Cell(0, 10, $item, 0, 1);
            }
            
            $pdf->Ln(5);
        }
    }
    
    // Ausgabe des PDF-Dokuments
    $pdf->Output('einkaufsliste_' . date('Y-m-d') . '.pdf', 'D');
    
    wp_die();
}
add_action('wp_ajax_mkr_export_shopping_list_pdf', 'mkr_export_shopping_list_pdf');
add_action('wp_ajax_nopriv_mkr_export_shopping_list_pdf', 'mkr_export_shopping_list_pdf');

/**
 * AJAX-Handler für die Sortierung der Einkaufsliste
 */
function mkr_order_shopping_list() {
    // Sicherheitsüberprüfung
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mkr_shopping_list')) {
        wp_send_json_error(array('message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    // Überprüfen, ob Elemente vorhanden sind
    if (!isset($_POST['items']) || empty($_POST['items'])) {
        wp_send_json_error(array('message' => __('Keine Elemente zum Sortieren gefunden.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    $items = json_decode(stripslashes($_POST['items']), true);
    
    if (!is_array($items)) {
        wp_send_json_error(array('message' => __('Ungültiges Format der Elemente.', 'mein-kochbuch-rezepte')));
        wp_die();
    }
    
    // Elemente sortieren
    $sorted_items = mkr_categorize_shopping_items($items, true);
    
    // Erfolgreich zurückgeben
    wp_send_json_success(array('items' => $sorted_items));
    
    wp_die();
}
add_action('wp_ajax_mkr_order_shopping_list', 'mkr_order_shopping_list');
add_action('wp_ajax_nopriv_mkr_order_shopping_list', 'mkr_order_shopping_list');

/**
 * Kategorisiert Einkaufslisten-Elemente nach verschiedenen Kategorien
 *
 * @param array $items Die zu kategorisierenden Elemente
 * @param bool $flatten Falls true, wird eine flache Liste zurückgegeben
 * @return array Die kategorisierten Elemente
 */
function mkr_categorize_shopping_items($items, $flatten = false) {
    // Kategorien für die Sortierung
    $categories = array(
        __('Obst & Gemüse', 'mein-kochbuch-rezepte') => array(
            'apfel', 'birne', 'orange', 'banane', 'zitrone', 'limette', 'beere', 'erdbeere', 'himbeere', 'brombeere',
            'blaubeere', 'heidelbeere', 'johannisbeere', 'stachelbeere', 'traube', 'kiwi', 'pflaume', 'pfirsich', 'nektarine',
            'melone', 'wassermelone', 'ananas', 'mango', 'papaya', 'avocado', 'tomate', 'gurke', 'karotte', 'möhre', 'rübe',
            'sellerie', 'spargel', 'kartoffel', 'süßkartoffel', 'zwiebel', 'knoblauch', 'lauch', 'porree', 'salat', 'kohl',
            'rotkohl', 'weißkohl', 'grünkohl', 'wirsing', 'rosenkohl', 'brokkoli', 'blumenkohl', 'fenchel', 'spinat', 'mangold',
            'rhabarber', 'pilz', 'champignon', 'kürbis', 'zucchini', 'aubergine', 'paprika', 'chili', 'erbse', 'bohne', 'mais',
            'radieschen', 'rettich', 'artischocke', 'okra', 'rucola', 'feldsalat'
        ),
        __('Milchprodukte', 'mein-kochbuch-rezepte') => array(
            'milch', 'sahne', 'schlagsahne', 'joghurt', 'quark', 'käse', 'gouda', 'emmentaler', 'parmesan', 'mozzarella',
            'feta', 'frischkäse', 'camembert', 'brie', 'butter', 'margarine', 'buttermilch', 'kefir', 'schmand', 'saure sahne',
            'crème fraîche', 'kondensmilch', 'schmelzkäse'
        ),
        __('Fleisch & Fisch', 'mein-kochbuch-rezepte') => array(
            'fleisch', 'rind', 'schwein', 'kalb', 'lamm', 'huhn', 'hähnchen', 'pute', 'truthahn', 'ente', 'gans', 'wild',
            'hirsch', 'reh', 'hase', 'kaninchen', 'fisch', 'lachs', 'forelle', 'thunfisch', 'kabeljau', 'hering', 'makrele',
            'garnele', 'scampi', 'muschel', 'tintenfisch', 'hackfleisch', 'gehacktes', 'schinken', 'speck', 'wurst', 'salami',
            'wurstwaren'
        ),
        __('Backwaren', 'mein-kochbuch-rezepte') => array(
            'brot', 'brötchen', 'semmel', 'toast', 'croissant', 'hörnchen', 'baguette', 'ciabatta', 'zwieback', 'kuchen',
            'torte', 'gebäck', 'keks', 'muffin', 'plätzchen'
        ),
        __('Gewürze & Kräuter', 'mein-kochbuch-rezepte') => array(
            'gewürz', 'kräuter', 'salz', 'pfeffer', 'paprika', 'zimt', 'muskat', 'kardamom', 'nelke', 'anis', 'kümmel',
            'curry', 'kurkuma', 'safran', 'lorbeer', 'thymian', 'rosmarin', 'oregano', 'basilikum', 'petersilie', 'dill',
            'schnittlauch', 'minze', 'majoran', 'koriander', 'vanille', 'ingwer', 'chili', 'cayenne', 'kerbel', 'salbei',
            'estragon', 'kresse', 'safran'
        ),
        __('Grundnahrungsmittel', 'mein-kochbuch-rezepte') => array(
            'mehl', 'zucker', 'salz', 'öl', 'essig', 'reis', 'nudel', 'spaghetti', 'makkaroni', 'penne', 'tagliatelle',
            'fusilli', 'farfalle', 'gnocchi', 'hefe', 'backpulver', 'natron', 'vanillezucker', 'puderzucker', 'brauner zucker',
            'honig', 'sirup', 'ahornsirup', 'agavendicksaft', 'erdnussbutter', 'marmelade', 'konfitüre', 'gelee', 'müsli',
            'haferflocken', 'cornflakes', 'grieß', 'polenta', 'quinoa', 'couscous', 'bulgur', 'linse', 'bohne', 'kichererbse',
            'erbse', 'mais', 'nuss', 'erdnuss', 'mandel', 'haselnuss', 'walnuss', 'cashew', 'pistazie', 'samen', 'kürbiskern',
            'sonnenblumenkern', 'leinsamen', 'chiasamen', 'haferkleie', 'weizenkleie'
        ),
        __('Konserven & Fertigprodukte', 'mein-kochbuch-rezepte') => array(
            'dose', 'konserve', 'tomate', 'passata', 'ketchup', 'mayonnaise', 'senf', 'soße', 'suppe', 'brühe', 'fond',
            'thunfisch', 'mais', 'erbse', 'bohne', 'kichererbse', 'linse', 'olive', 'kapern', 'gurke', 'ananas'
        ),
        __('Getränke', 'mein-kochbuch-rezepte') => array(
            'wasser', 'mineralwasser', 'saft', 'orangensaft', 'apfelsaft', 'traubensaft', 'limonade', 'cola', 'sprite',
            'fanta', 'eistee', 'kaffee', 'tee', 'bier', 'wein', 'sekt', 'champagner', 'whisky', 'rum', 'wodka', 'gin'
        ),
        __('Sonstiges', 'mein-kochbuch-rezepte') => array()
    );
    
    // Initialisiere kategorisierte Elemente
    $categorized_items = array();
    foreach ($categories as $category => $keywords) {
        $categorized_items[$category] = array();
    }
    
    // Einheitliche Kleinschreibung für die Elemente
    $items = array_map('strtolower', $items);
    
    // Sortiere Elemente in Kategorien
    foreach ($items as $item) {
        $found_category = false;
        
        foreach ($categories as $category => $keywords) {
            // Prüfe, ob das Element in die aktuelle Kategorie passt
            foreach ($keywords as $keyword) {
                if (strpos(strtolower($item), $keyword) !== false) {
                    $categorized_items[$category][] = $item;
                    $found_category = true;
                    break;
                }
            }
            
            if ($found_category) {
                break;
            }
        }
        
        // Wenn keine passende Kategorie gefunden wurde, zur Kategorie "Sonstiges" hinzufügen
        if (!$found_category) {
            $categorized_items[__('Sonstiges', 'mein-kochbuch-rezepte')][] = $item;
        }
    }
    
    // Alphabetisch innerhalb jeder Kategorie sortieren
    foreach ($categorized_items as $category => $items) {
        if (!empty($items)) {
            sort($categorized_items[$category]);
        }
    }
    
    // Falls eine flache Liste gewünscht ist, alle Kategorien zusammenführen
    if ($flatten) {
        $flat_items = array();
        foreach ($categorized_items as $category => $items) {
            foreach ($items as $item) {
                $flat_items[] = $item;
            }
        }
        return $flat_items;
    }
    
    return $categorized_items;
}