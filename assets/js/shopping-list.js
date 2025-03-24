/**
 * Einkaufslisten-Funktionalität für Mein Kochbuch
 * 
 * Ermöglicht das Hinzufügen von Zutaten zur Einkaufsliste und das Verwalten der Liste.
 */

jQuery(document).ready(function($) {
    // Konstanten für localStorage-Keys
    const SHOPPING_LIST_KEY = 'mkrShoppingList';
    const RECIPE_ITEMS_KEY = 'mkrRecipeItems';

    /**
     * Initialisiert die Einkaufslisten-Funktionalität
     */
    function initShoppingList() {
        // Einzelne Zutat zur Einkaufsliste hinzufügen
        $('.mkr-add-to-shopping-list').on('click', function(e) {
            e.preventDefault();
            
            const ingredientItem = $(this).closest('.mkr-ingredient-item');
            const amount = ingredientItem.find('.mkr-amount').text();
            const unit = ingredientItem.find('.mkr-unit').text();
            const name = ingredientItem.find('.mkr-ingredient-name').text();
            const recipeTitle = $('.mkr-recipe-title').text();
            
            // Zutat formatieren
            const ingredientText = formatIngredient(amount, unit, name);
            
            // Zur Einkaufsliste hinzufügen
            addToShoppingList(ingredientText, recipeTitle);
            
            // Feedback für den Benutzer
            showNotification(ingredientText);
        });
        
        // Alle Zutaten zur Einkaufsliste hinzufügen
        $('.mkr-add-all-to-shopping-list').on('click', function(e) {
            e.preventDefault();
            
            const recipeTitle = $('.mkr-recipe-title').text();
            const ingredients = [];
            
            // Alle Zutaten sammeln
            $('.mkr-ingredient-item').each(function() {
                const amount = $(this).find('.mkr-amount').text();
                const unit = $(this).find('.mkr-unit').text();
                const name = $(this).find('.mkr-ingredient-name').text();
                
                // Zutat formatieren
                const ingredientText = formatIngredient(amount, unit, name);
                ingredients.push(ingredientText);
            });
            
            // Alle zur Einkaufsliste hinzufügen
            addMultipleToShoppingList(ingredients, recipeTitle);
            
            // Feedback für den Benutzer
            showNotification(ingredients.length + ' ' + (ingredients.length === 1 ? 'Zutat' : 'Zutaten'));
        });
        
        // Auf der Einkaufslisten-Seite: Liste rendern
        if ($('#mkr-shopping-list-container').length) {
            renderShoppingList();
            
            // Ereignishandler für die Einkaufsliste
            initShoppingListEvents();
        }
    }
    
    /**
     * Formatiert eine Zutat als Text
     */
    function formatIngredient(amount, unit, name) {
        // Einheit nur hinzufügen, wenn sie nicht leer ist
        const unitText = unit ? ' ' + unit + ' ' : ' ';
        return amount + unitText + name.trim();
    }
    
    /**
     * Fügt ein einzelnes Element zur Einkaufsliste hinzu
     */
    function addToShoppingList(item, recipeTitle) {
        let shoppingList = getShoppingList();
        let recipeItems = getRecipeItems();
        
        // Zutat zur Liste hinzufügen, wenn sie noch nicht vorhanden ist
        if (!shoppingList.includes(item)) {
            shoppingList.push(item);
            
            // Rezeptreferenz speichern
            if (!recipeItems[item]) {
                recipeItems[item] = [];
            }
            if (!recipeItems[item].includes(recipeTitle)) {
                recipeItems[item].push(recipeTitle);
            }
            
            // Listen speichern
            saveShoppingList(shoppingList);
            saveRecipeItems(recipeItems);
        }
    }
    
    /**
     * Fügt mehrere Elemente zur Einkaufsliste hinzu
     */
    function addMultipleToShoppingList(items, recipeTitle) {
        let shoppingList = getShoppingList();
        let recipeItems = getRecipeItems();
        
        items.forEach(function(item) {
            // Nur hinzufügen, wenn die Zutat noch nicht in der Liste ist
            if (!shoppingList.includes(item)) {
                shoppingList.push(item);
                
                // Rezeptreferenz speichern
                if (!recipeItems[item]) {
                    recipeItems[item] = [];
                }
                if (!recipeItems[item].includes(recipeTitle)) {
                    recipeItems[item].push(recipeTitle);
                }
            }
        });
        
        // Listen speichern
        saveShoppingList(shoppingList);
        saveRecipeItems(recipeItems);
    }
    
    /**
     * Zeigt eine Benachrichtigung an, dass etwas zur Einkaufsliste hinzugefügt wurde
     */
    function showNotification(itemText) {
        const notification = $('<div class="mkr-notification">')
            .html(mkrShoppingList.addedToList + ': <strong>' + itemText + '</strong>');
        
        // Link zur Einkaufsliste hinzufügen
        if (mkrShoppingList.shoppingListPage) {
            const viewLink = $('<a>')
                .attr('href', mkrShoppingList.shoppingListPage)
                .text(mkrShoppingList.viewList)
                .addClass('mkr-view-shopping-list');
            
            notification.append('<br>').append(viewLink);
        }
        
        // Benachrichtigung anzeigen
        $('body').append(notification);
        
        // Benachrichtigung nach einigen Sekunden ausblenden
        setTimeout(function() {
            notification.addClass('mkr-notification-fadeout');
            setTimeout(function() {
                notification.remove();
            }, 500);
        }, 3000);
    }
    
    /**
     * Initialisiert die Ereignishandler für die Einkaufsliste auf der Einkaufslisten-Seite
     */
    function initShoppingListEvents() {
        // Zutat als erledigt markieren
        $('#mkr-shopping-list-container').on('click', '.mkr-shopping-item-checkbox', function() {
            const item = $(this).closest('.mkr-shopping-item');
            item.toggleClass('completed');
        });
        
        // Zutat aus der Liste entfernen
        $('#mkr-shopping-list-container').on('click', '.mkr-remove-shopping-item', function() {
            const item = $(this).closest('.mkr-shopping-item');
            const itemText = item.data('item-text');
            
            // Aus der Liste entfernen
            removeFromShoppingList(itemText);
            
            // Element animiert entfernen
            item.addClass('mkr-item-removed');
            setTimeout(function() {
                item.remove();
                
                // Falls die Liste leer ist, Message anzeigen
                if ($('.mkr-shopping-item').length === 0) {
                    $('#mkr-shopping-list-container').html('<p class="mkr-empty-list">' + 
                        'Deine Einkaufsliste ist leer. Füge Zutaten aus Rezepten hinzu.' + '</p>');
                    
                    // Aktionsbuttons für leere Liste ausblenden
                    $('.mkr-shopping-list-actions').hide();
                }
            }, 300);
        });
        
        // Liste sortieren
        $('#mkr-sort-shopping-list').on('click', function() {
            sortShoppingList();
        });
        
        // Liste löschen
        $('#mkr-clear-shopping-list').on('click', function() {
            if (confirm('Möchtest du wirklich die gesamte Einkaufsliste löschen?')) {
                clearShoppingList();
            }
        });
        
        // PDF exportieren
        $('#mkr-export-pdf').on('click', function() {
            exportShoppingListPDF();
        });
    }
    
    /**
     * Rendert die Einkaufsliste auf der Einkaufslisten-Seite
     */
    function renderShoppingList() {
        const container = $('#mkr-shopping-list-container');
        const shoppingList = getShoppingList();
        const recipeItems = getRecipeItems();
        
        // Wenn die Liste leer ist
        if (shoppingList.length === 0) {
            container.html('<p class="mkr-empty-list">' + 
                'Deine Einkaufsliste ist leer. Füge Zutaten aus Rezepten hinzu.' + '</p>');
            
            // Aktionsbuttons ausblenden
            $('.mkr-shopping-list-actions').hide();
            return;
        }
        
        // Aktionsbuttons einblenden
        $('.mkr-shopping-list-actions').show();
        
        // Liste erstellen
        const listElement = $('<ul class="mkr-shopping-list">');
        
        shoppingList.forEach(function(item) {
            const listItem = $('<li class="mkr-shopping-item">').data('item-text', item);
            
            // Checkbox
            const checkboxWrapper = $('<div class="mkr-shopping-item-checkbox-wrapper">');
            const checkbox = $('<input type="checkbox" class="mkr-shopping-item-checkbox">');
            checkboxWrapper.append(checkbox);
            
            // Text
            const itemTextElement = $('<span class="mkr-shopping-item-text">').text(item);
            
            // Rezeptreferenzen
            const recipeReferences = $('<div class="mkr-item-recipes">');
            if (recipeItems[item] && recipeItems[item].length > 0) {
                recipeItems[item].forEach(function(recipe) {
                    const recipeTag = $('<span class="mkr-recipe-tag">').text(recipe);
                    recipeReferences.append(recipeTag);
                });
            }
            
            // Löschbutton
            const removeButton = $('<button type="button" class="mkr-remove-shopping-item" aria-label="Entfernen">&times;</button>');
            
            // Alles zusammenfügen
            listItem.append(checkboxWrapper, itemTextElement, recipeReferences, removeButton);
            listElement.append(listItem);
        });
        
        // Liste in den Container einfügen
        container.empty().append(listElement);
    }
    
    /**
     * Sortiert die Einkaufsliste nach Kategorien
     */
    function sortShoppingList() {
        // Kategorien für die Sortierung
        const categories = {
            'Obst & Gemüse': ['apfel', 'banane', 'birne', 'orange', 'zitrone', 'karotte', 'kartoffel', 'zwiebel', 'knoblauch', 'salat', 'tomate', 'gurke'],
            'Milchprodukte': ['milch', 'käse', 'butter', 'joghurt', 'sahne', 'quark'],
            'Fleisch & Fisch': ['rind', 'schwein', 'huhn', 'pute', 'hack', 'schinken', 'wurst', 'fisch', 'lachs', 'thunfisch'],
            'Backwaren': ['brot', 'brötchen', 'toast', 'mehl', 'zucker', 'backpulver', 'hefe'],
            'Gewürze': ['salz', 'pfeffer', 'gewürz', 'kräuter', 'oregano', 'basilikum', 'thymian', 'rosmarin', 'zimt', 'vanille'],
            'Sonstiges': []
        };
        
        let shoppingList = getShoppingList();
        let sortedList = [];
        let categorizedItems = {};
        
        // Initialisiere Kategorien
        Object.keys(categories).forEach(function(category) {
            categorizedItems[category] = [];
        });
        
        // Kategorisiere Elemente
        shoppingList.forEach(function(item) {
            let assigned = false;
            
            // Durchsuche jede Kategorie
            Object.keys(categories).forEach(function(category) {
                if (assigned) return;
                
                const keywords = categories[category];
                
                // Prüfe, ob das Element zu dieser Kategorie gehört
                for (let i = 0; i < keywords.length; i++) {
                    if (item.toLowerCase().includes(keywords[i])) {
                        categorizedItems[category].push(item);
                        assigned = true;
                        break;
                    }
                }
            });
            
            // Wenn keine passende Kategorie gefunden wurde, zu "Sonstiges" hinzufügen
            if (!assigned) {
                categorizedItems['Sonstiges'].push(item);
            }
        });
        
        // Sortierte Liste erstellen
        Object.keys(categories).forEach(function(category) {
            if (categorizedItems[category].length > 0) {
                // Alphabetisch innerhalb der Kategorie sortieren
                categorizedItems[category].sort();
                
                // Zur sortierten Liste hinzufügen
                sortedList = sortedList.concat(categorizedItems[category]);
            }
        });
        
        // Liste speichern und neu rendern
        saveShoppingList(sortedList);
        renderShoppingList();
    }
    
    /**
     * Entfernt ein Element aus der Einkaufsliste
     */
    function removeFromShoppingList(item) {
        let shoppingList = getShoppingList();
        let recipeItems = getRecipeItems();
        
        // Element aus der Liste entfernen
        const index = shoppingList.indexOf(item);
        if (index !== -1) {
            shoppingList.splice(index, 1);
        }
        
        // Rezeptreferenz entfernen
        if (recipeItems[item]) {
            delete recipeItems[item];
        }
        
        // Listen speichern
        saveShoppingList(shoppingList);
        saveRecipeItems(recipeItems);
    }
    
    /**
     * Löscht die gesamte Einkaufsliste
     */
    function clearShoppingList() {
        // Listen löschen
        saveShoppingList([]);
        saveRecipeItems({});
        
        // UI aktualisieren
        $('#mkr-shopping-list-container').html('<p class="mkr-empty-list">' + 
            'Deine Einkaufsliste ist leer. Füge Zutaten aus Rezepten hinzu.' + '</p>');
        
        // Aktionsbuttons ausblenden
        $('.mkr-shopping-list-actions').hide();
    }
    
    /**
     * Exportiert die Einkaufsliste als PDF
     */
    function exportShoppingListPDF() {
        const shoppingList = getShoppingList();
        
        // AJAX-Anfrage zum Server
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mkr_export_shopping_list_pdf',
                nonce: mkrShoppingList.nonce,
                items: JSON.stringify(shoppingList)
            },
            success: function(response) {
                // Der Server wird eine Datei zum Download anbieten
                if (!response.success) {
                    alert('Fehler beim Exportieren der Einkaufsliste.');
                }
            },
            error: function() {
                alert('Fehler beim Exportieren der Einkaufsliste.');
            }
        });
    }
    
    /**
     * Hilfsfunktion: Lädt die Einkaufsliste aus dem localStorage
     */
    function getShoppingList() {
        const listJSON = localStorage.getItem(SHOPPING_LIST_KEY);
        return listJSON ? JSON.parse(listJSON) : [];
    }
    
    /**
     * Hilfsfunktion: Speichert die Einkaufsliste im localStorage
     */
    function saveShoppingList(list) {
        localStorage.setItem(SHOPPING_LIST_KEY, JSON.stringify(list));
    }
    
    /**
     * Hilfsfunktion: Lädt die Rezeptreferenzen aus dem localStorage
     */
    function getRecipeItems() {
        const itemsJSON = localStorage.getItem(RECIPE_ITEMS_KEY);
        return itemsJSON ? JSON.parse(itemsJSON) : {};
    }
    
    /**
     * Hilfsfunktion: Speichert die Rezeptreferenzen im localStorage
     */
    function saveRecipeItems(items) {
        localStorage.setItem(RECIPE_ITEMS_KEY, JSON.stringify(items));
    }
    
    // Einkaufslisten-Funktionalität initialisieren
    initShoppingList();
});