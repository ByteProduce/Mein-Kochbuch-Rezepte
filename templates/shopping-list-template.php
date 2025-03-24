<?php
/**
 * Template für die Einkaufsliste
 *
 * @package Mein Kochbuch Rezepte
 */

// Sicherheitsüberprüfung - direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mkr-shopping-list-container">
    <h1 class="mkr-page-title"><?php _e('Meine Einkaufsliste', 'mein-kochbuch-rezepte'); ?></h1>
    
    <div id="mkr-shopping-list-app">
        <!-- Dieses Element wird durch JavaScript mit der Einkaufsliste gefüllt -->
        <div id="mkr-shopping-list-container"></div>

        <!-- Aktionen für die Einkaufsliste -->
        <div class="mkr-shopping-list-actions">
            <button type="button" id="mkr-sort-shopping-list" class="mkr-button">
                <span class="mkr-icon">🔄</span>
                <?php _e('Nach Kategorien sortieren', 'mein-kochbuch-rezepte'); ?>
            </button>
            
            <button type="button" id="mkr-export-pdf" class="mkr-button">
                <span class="mkr-icon">📄</span>
                <?php _e('Als PDF exportieren', 'mein-kochbuch-rezepte'); ?>
            </button>
            
            <button type="button" id="mkr-clear-shopping-list" class="mkr-button mkr-button-danger">
                <span class="mkr-icon">🗑️</span>
                <?php _e('Liste leeren', 'mein-kochbuch-rezepte'); ?>
            </button>
        </div>

        <!-- Neue Elemente manuell hinzufügen -->
        <div class="mkr-shopping-list-add">
            <h2><?php _e('Eigenen Eintrag hinzufügen', 'mein-kochbuch-rezepte'); ?></h2>
            <div class="mkr-shopping-list-add-form">
                <input type="text" id="mkr-new-item" placeholder="<?php esc_attr_e('z.B. 500g Mehl', 'mein-kochbuch-rezepte'); ?>" aria-label="<?php esc_attr_e('Neues Element', 'mein-kochbuch-rezepte'); ?>">
                <button type="button" id="mkr-add-item" class="mkr-button mkr-button-primary">
                    <span class="mkr-icon">➕</span>
                    <?php _e('Hinzufügen', 'mein-kochbuch-rezepte'); ?>
                </button>
            </div>
        </div>

        <!-- Rezeptvorschläge -->
        <div class="mkr-recipe-suggestions">
            <h2><?php _e('Rezeptvorschläge', 'mein-kochbuch-rezepte'); ?></h2>
            <p><?php _e('Basierend auf deiner Einkaufsliste könnten dich diese Rezepte interessieren:', 'mein-kochbuch-rezepte'); ?></p>
            
            <div id="mkr-recipe-suggestions-container" class="mkr-recipe-grid"></div>
            
            <button type="button" id="mkr-load-more-suggestions" class="mkr-button">
                <?php _e('Weitere Vorschläge laden', 'mein-kochbuch-rezepte'); ?>
            </button>
        </div>
    </div>

    <!-- Rückmeldung für leere Einkaufsliste / Fehler -->
    <div id="mkr-empty-list-message" class="mkr-message mkr-empty-message" style="display: none;">
        <p><?php _e('Deine Einkaufsliste ist noch leer.', 'mein-kochbuch-rezepte'); ?></p>
        <p><?php _e('Füge Zutaten aus Rezepten hinzu oder erstelle manuelle Einträge.', 'mein-kochbuch-rezepte'); ?></p>
        
        <div class="mkr-go-to-recipes">
            <a href="<?php echo esc_url(get_post_type_archive_link('recipe')); ?>" class="mkr-button mkr-button-primary">
                <?php _e('Rezepte durchstöbern', 'mein-kochbuch-rezepte'); ?>
            </a>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Lokale Speicher-Keys
    const SHOPPING_LIST_KEY = 'mkrShoppingList';
    const RECIPE_ITEMS_KEY = 'mkrRecipeItems';
    
    // Einkaufsliste laden und anzeigen
    function loadShoppingList() {
        const listJson = localStorage.getItem(SHOPPING_LIST_KEY);
        const shoppingList = listJson ? JSON.parse(listJson) : [];
        const recipeItemsJson = localStorage.getItem(RECIPE_ITEMS_KEY);
        const recipeItems = recipeItemsJson ? JSON.parse(recipeItemsJson) : {};
        
        const container = $('#mkr-shopping-list-container');
        
        // Leerer Zustand
        if (shoppingList.length === 0) {
            $('#mkr-empty-list-message').show();
            $('.mkr-shopping-list-actions').hide();
            container.empty();
            return;
        }
        
        // Einkaufsliste anzeigen
        $('#mkr-empty-list-message').hide();
        $('.mkr-shopping-list-actions').show();
        
        container.empty();
        const listElement = $('<ul class="mkr-shopping-list"></ul>');
        
        // Elemente hinzufügen
        shoppingList.forEach(function(item) {
            const listItem = $('<li class="mkr-shopping-item"></li>')
                .data('item-text', item);
                
            // Checkbox
            const checkboxWrapper = $('<div class="mkr-shopping-item-checkbox-wrapper"></div>');
            const checkbox = $('<input type="checkbox" class="mkr-shopping-item-checkbox" aria-label="' + 
                               <?php echo json_encode(__('Artikel abhaken', 'mein-kochbuch-rezepte')); ?> + '">');
            checkboxWrapper.append(checkbox);
            
            // Artikeltext
            const itemText = $('<span class="mkr-shopping-item-text"></span>').text(item);
            
            // Rezeptverweise
            let recipeRefs = '';
            if (recipeItems[item] && recipeItems[item].length > 0) {
                const recipeTags = $('<div class="mkr-item-recipes"></div>');
                recipeItems[item].forEach(function(recipe) {
                    const tag = $('<span class="mkr-recipe-tag"></span>').text(recipe);
                    recipeTags.append(tag);
                });
                recipeRefs = recipeTags;
            }
            
            // Entfernen-Button
            const removeButton = $('<button type="button" class="mkr-remove-shopping-item" aria-label="' + 
                                  <?php echo json_encode(__('Artikel entfernen', 'mein-kochbuch-rezepte')); ?> + 
                                  '">×</button>');
            
            // Elemente zusammenfügen
            listItem.append(checkboxWrapper, itemText, recipeRefs, removeButton);
            listElement.append(listItem);
        });
        
        container.append(listElement);
        
        // Rezeptvorschläge aktualisieren
        updateRecipeSuggestions(shoppingList);
    }
    
    // Neues Element zur Einkaufsliste hinzufügen
    function addItemToShoppingList(item) {
        if (!item.trim()) return; // Leere Einträge vermeiden
        
        const listJson = localStorage.getItem(SHOPPING_LIST_KEY);
        const shoppingList = listJson ? JSON.parse(listJson) : [];
        
        // Prüfen, ob das Element bereits in der Liste ist (case-insensitive)
        const normalizedItem = item.trim();
        const itemExists = shoppingList.some(existingItem => 
            existingItem.toLowerCase() === normalizedItem.toLowerCase());
        
        if (!itemExists) {
            shoppingList.push(normalizedItem);
            localStorage.setItem(SHOPPING_LIST_KEY, JSON.stringify(shoppingList));
            
            // Liste neu laden
            loadShoppingList();
            
            // Eingabefeld leeren
            $('#mkr-new-item').val('');
        }
    }
    
    // Element aus der Einkaufsliste entfernen
    function removeItemFromShoppingList(item) {
        const listJson = localStorage.getItem(SHOPPING_LIST_KEY);
        const shoppingList = listJson ? JSON.parse(listJson) : [];
        const recipeItemsJson = localStorage.getItem(RECIPE_ITEMS_KEY);
        const recipeItems = recipeItemsJson ? JSON.parse(recipeItemsJson) : {};
        
        // Element entfernen
        const updatedList = shoppingList.filter(existingItem => existingItem !== item);
        localStorage.setItem(SHOPPING_LIST_KEY, JSON.stringify(updatedList));
        
        // Rezeptreferenz entfernen
        if (recipeItems[item]) {
            delete recipeItems[item];
            localStorage.setItem(RECIPE_ITEMS_KEY, JSON.stringify(recipeItems));
        }
        
        // Liste neu laden
        loadShoppingList();
    }
    
    // Einkaufsliste nach Kategorien sortieren
    function sortShoppingList() {
        const listJson = localStorage.getItem(SHOPPING_LIST_KEY);
        const shoppingList = listJson ? JSON.parse(listJson) : [];
        
        if (shoppingList.length === 0) return;
        
        // AJAX-Anfrage für serverseitige Sortierung
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'mkr_order_shopping_list',
                nonce: '<?php echo wp_create_nonce('mkr_shopping_list'); ?>',
                items: JSON.stringify(shoppingList)
            },
            beforeSend: function() {
                // Ladezustand anzeigen
                $('#mkr-sort-shopping-list').prop('disabled', true);
            },
            success: function(response) {
                if (response.success && response.data && response.data.items) {
                    // Sortierte Liste speichern
                    localStorage.setItem(SHOPPING_LIST_KEY, JSON.stringify(response.data.items));
                    
                    // Liste neu laden
                    loadShoppingList();
                }
            },
            complete: function() {
                // Ladezustand entfernen
                $('#mkr-sort-shopping-list').prop('disabled', false);
            }
        });
    }
    
    // Einkaufsliste als PDF exportieren
    function exportShoppingListPDF() {
        const listJson = localStorage.getItem(SHOPPING_LIST_KEY);
        const shoppingList = listJson ? JSON.parse(listJson) : [];
        
        if (shoppingList.length === 0) return;
        
        // Formular erstellen und absenden
        const form = $('<form>', {
            'method': 'post',
            'action': '<?php echo admin_url('admin-ajax.php'); ?>',
            'target': '_blank'
        });
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'action',
            'value': 'mkr_export_shopping_list_pdf'
        }));
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'nonce',
            'value': '<?php echo wp_create_nonce('mkr_export_shopping_list'); ?>'
        }));
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'items',
            'value': JSON.stringify(shoppingList)
        }));
        
        form.appendTo('body').submit().remove();
    }
    
    // Einkaufsliste leeren
    function clearShoppingList() {
        if (confirm(<?php echo json_encode(__('Möchtest du wirklich die gesamte Einkaufsliste löschen?', 'mein-kochbuch-rezepte')); ?>)) {
            localStorage.setItem(SHOPPING_LIST_KEY, JSON.stringify([]));
            localStorage.setItem(RECIPE_ITEMS_KEY, JSON.stringify({}));
            
            // Liste neu laden
            loadShoppingList();
        }
    }
    
    // Rezeptvorschläge basierend auf der Einkaufsliste anzeigen
    function updateRecipeSuggestions(shoppingList) {
        if (!shoppingList || shoppingList.length === 0) {
            $('#mkr-recipe-suggestions-container').empty();
            $('.mkr-recipe-suggestions').hide();
            return;
        }
        
        // Rezeptvorschläge anzeigen
        $('.mkr-recipe-suggestions').show();
        
        // AJAX-Anfrage für Rezeptvorschläge - hier könnte man eine eigene API-Route erstellen
        // Das ist ein einfaches Beispiel, das zufällige Rezepte lädt
        // In der Realität würde man hier Rezepte basierend auf den Zutaten in der Einkaufsliste laden
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'mkr_get_inspiration',
                nonce: '<?php echo wp_create_nonce('mkr_inspiration'); ?>'
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Hier würden wir mehrere Rezepte anzeigen
                    // Da die aktuelle API nur ein zufälliges Rezept zurückgibt, leiten wir einfach weiter
                    $('#mkr-recipe-suggestions-container').html(
                        '<p><?php _e('Ein passendes Rezept für dich:', 'mein-kochbuch-rezepte'); ?></p>' +
                        '<p><a href="' + response.data.permalink + '" class="mkr-button">' + 
                        '<?php _e('Zum Rezept', 'mein-kochbuch-rezepte'); ?></a></p>'
                    );
                }
            }
        });
    }
    
    // Event-Handler
    
    // Einkaufsliste laden
    loadShoppingList();
    
    // Neues Element hinzufügen (Button-Klick)
    $('#mkr-add-item').on('click', function() {
        const newItem = $('#mkr-new-item').val().trim();
        addItemToShoppingList(newItem);
    });
    
    // Neues Element hinzufügen (Enter-Taste)
    $('#mkr-new-item').on('keypress', function(e) {
        if (e.which === 13) {
            const newItem = $(this).val().trim();
            addItemToShoppingList(newItem);
            e.preventDefault();
        }
    });
    
    // Element als erledigt markieren
    $(document).on('change', '.mkr-shopping-item-checkbox', function() {
        const item = $(this).closest('.mkr-shopping-item');
        item.toggleClass('completed');
    });
    
    // Element aus der Liste entfernen
    $(document).on('click', '.mkr-remove-shopping-item', function() {
        const item = $(this).closest('.mkr-shopping-item');
        const itemText = item.data('item-text');
        
        // Animation beim Entfernen
        item.addClass('mkr-item-removed');
        
        // Nach der Animation das Element tatsächlich entfernen
        setTimeout(function() {
            removeItemFromShoppingList(itemText);
        }, 300);
    });
    
    // Liste sortieren
    $('#mkr-sort-shopping-list').on('click', sortShoppingList);
    
    // Liste als PDF exportieren
    $('#mkr-export-pdf').on('click', exportShoppingListPDF);
    
    // Liste leeren
    $('#mkr-clear-shopping-list').on('click', clearShoppingList);
    
    // Weitere Vorschläge laden
    $('#mkr-load-more-suggestions').on('click', function() {
        // Hier würde man weitere Rezeptvorschläge laden
        // Da die aktuelle API nur ein zufälliges Rezept zurückgibt, laden wir einfach ein neues
        const listJson = localStorage.getItem(SHOPPING_LIST_KEY);
        const shoppingList = listJson ? JSON.parse(listJson) : [];
        updateRecipeSuggestions(shoppingList);
    });
});
</script>

<style>
.mkr-shopping-list-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.mkr-page-title {
    margin-bottom: 30px;
    text-align: center;
}

.mkr-shopping-list {
    list-style-type: none;
    padding: 0;
    margin: 0 0 30px 0;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.mkr-shopping-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid var(--border-color);
    transition: background-color 0.2s, opacity 0.3s, transform 0.3s;
}

.mkr-shopping-item:last-child {
    border-bottom: none;
}

.mkr-shopping-item:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.mkr-shopping-item.completed {
    text-decoration: line-through;
    color: var(--text-muted);
}

.mkr-shopping-item.mkr-item-removed {
    opacity: 0;
    transform: translateX(100%);
}

.mkr-shopping-item-checkbox-wrapper {
    margin-right: 15px;
}

.mkr-shopping-item-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.mkr-shopping-item-text {
    flex: 1;
}

.mkr-item-recipes {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-left: 10px;
    margin-right: 10px;
}

.mkr-recipe-tag {
    font-size: 0.75rem;
    padding: 2px 8px;
    background-color: var(--primary-light);
    border-radius: 12px;
    white-space: nowrap;
}

.mkr-remove-shopping-item {
    background: none;
    border: none;
    color: var(--danger-color);
    font-size: 1.5rem;
    line-height: 1;
    padding: 0;
    margin-left: 10px;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s;
}

.mkr-remove-shopping-item:hover {
    opacity: 1;
}

.mkr-shopping-list-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 40px;
}

.mkr-shopping-list-add {
    margin-bottom: 40px;
    padding: 20px;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: var(--border-radius);
}

.mkr-shopping-list-add h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.mkr-shopping-list-add-form {
    display: flex;
    gap: 10px;
}

.mkr-shopping-list-add-form input {
    flex: 1;
}

.mkr-recipe-suggestions {
    margin-bottom: 40px;
    display: none;
}

.mkr-recipe-suggestions h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.mkr-recipe-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.mkr-message {
    text-align: center;
    padding: 40px 20px;
    margin: 20px 0;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: var(--border-radius);
}

.mkr-message p {
    margin-bottom: 15px;
}

.mkr-go-to-recipes {
    margin-top: 20px;
}

.mkr-icon {
    margin-right: 5px;
}

/* Dark mode fixes */
body.dark .mkr-shopping-list-add,
body.dark .mkr-message,
body.dark .mkr-shopping-item:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

body.dark .mkr-recipe-tag {
    background-color: rgba(0, 115, 170, 0.2);
}

/* Responsive design */
@media (max-width: 768px) {
    .mkr-shopping-list-add-form {
        flex-direction: column;
    }
    
    .mkr-shopping-list-actions {
        flex-direction: column;
    }
    
    .mkr-item-recipes {
        width: 100%;
        margin: 5px 0;
    }
    
    .mkr-shopping-item {
        flex-wrap: wrap;
    }
}
</style>