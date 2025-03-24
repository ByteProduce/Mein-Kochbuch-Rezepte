/**
 * Autocomplete-Funktionalität für Mein Kochbuch
 * 
 * Bietet Autovervollständigung für Zutaten und Utensilien im Backend
 * und Frontend des Plugins.
 */

jQuery(document).ready(function($) {
    /**
     * Initialisiert die Autovervollständigung für ein bestimmtes Eingabefeld
     * 
     * @param {jQuery} input - Das jQuery-Objekt des Eingabefelds
     * @param {string} type - Der Typ der Autovervollständigung ('ingredient' oder 'utensil')
     */
    function setupInlineAutocomplete(input, type) {
        // Sicherstellen, dass jQuery UI Autocomplete verfügbar ist
        if (typeof $.ui === 'undefined' || typeof $.ui.autocomplete === 'undefined') {
            console.warn('jQuery UI Autocomplete ist nicht verfügbar');
            return;
        }
        
        // Autovervollständigung initialisieren
        input.autocomplete({
            source: function(request, response) {
                // AJAX-Anfrage an die REST-API
                $.ajax({
                    url: mkrAutocomplete.restUrl + type + 's',
                    method: 'GET',
                    data: { 
                        search: request.term,
                        _wpnonce: mkrAutocomplete.nonce
                    },
                    beforeSend: function(xhr) {
                        // Authorization-Header für die REST-API setzen
                        xhr.setRequestHeader('X-WP-Nonce', mkrAutocomplete.nonce);
                    },
                    success: function(data) {
                        // Daten für die Autocomplete-Dropdown formatieren
                        if (Array.isArray(data)) {
                            response(data.map(item => ({
                                label: item.title,
                                value: item.title,
                                id: item.id,
                                url: item.permalink
                            })));
                        } else {
                            response([]);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Fehler bei der Autovervollständigungsanfrage:', error);
                        response([]);
                    }
                });
            },
            minLength: 2,  // Minimale Anzahl an Zeichen vor Beginn der Suche
            delay: 300,    // Verzögerung in ms vor der Suche
            autoFocus: false,  // Erstes Element nicht automatisch fokussieren
            select: function(event, ui) {
                // Wert des Eingabefelds setzen
                input.val(ui.item.value);
                
                // Benutzerdefiniertes Ereignis auslösen für mögliche weitere Verarbeitung
                input.trigger('autocomplete:selected', [ui.item]);
                
                return false;
            },
            open: function() {
                // Dropdown-Position und Stil anpassen
                $('.ui-autocomplete').css('z-index', 9999);
            }
        }).autocomplete('instance')._renderItem = function(ul, item) {
            // Anpassung des Dropdown-Items mit zusätzlichen Informationen
            return $('<li>')
                .append('<div>' + item.label + '</div>')
                .appendTo(ul);
        };
        
        // Barrierefreiheits-Verbesserungen
        input.attr('aria-autocomplete', 'list')
             .attr('aria-expanded', 'false')
             .attr('autocomplete', 'off');
        
        // Bei Fokus das Dropdown automatisch öffnen, wenn genug Zeichen eingegeben wurden
        input.on('focus', function() {
            if ($(this).val().length >= 2) {
                $(this).autocomplete('search');
            }
        });
        
        // Unterstützung für Touch-Geräte verbessern
        input.on('touchstart', function() {
            if ($(this).autocomplete('widget').is(':visible')) {
                $(this).autocomplete('close');
            } else if ($(this).val().length >= 2) {
                $(this).autocomplete('search');
            }
        });
    }
    
    // Die Funktion im globalen Bereich verfügbar machen für die Verwendung in anderen Skripten
    window.setupInlineAutocomplete = setupInlineAutocomplete;
    
    // Autovervollständigung für vorhandene Felder initialisieren
    function initExistingAutocompleteFields() {
        // Initialisiere alle Zutaten-Eingabefelder
        $('.mkr-ingredient-name').each(function() {
            setupInlineAutocomplete($(this), 'ingredient');
        });
        
        // Initialisiere alle Utensilien-Eingabefelder
        $('.mkr-utensil-name').each(function() {
            setupInlineAutocomplete($(this), 'utensil');
        });
        
        // Initialisiere alle Eingabefelder mit data-autocomplete-type
        $('[data-autocomplete-type]').each(function() {
            const type = $(this).data('autocomplete-type');
            if (type === 'ingredient' || type === 'utensil') {
                setupInlineAutocomplete($(this), type);
            }
        });
    }
    
    // Ereignishandler für dynamisch hinzugefügte Felder
    function setupDynamicFieldMonitoring() {
        // MutationObserver verwenden, um neue Felder zu erkennen
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length) {
                        $(mutation.addedNodes).find('[data-autocomplete-type]').each(function() {
                            const type = $(this).data('autocomplete-type');
                            if (type && !$(this).data('autocomplete-initialized')) {
                                setupInlineAutocomplete($(this), type);
                                $(this).data('autocomplete-initialized', true);
                            }
                        });
                    }
                });
            });
            
            // Beobachte den Dokument-Body auf Änderungen
            observer.observe(document.body, { 
                childList: true, 
                subtree: true 
            });
        }
        
        // Fallback für ältere Browser: Ereignishandler für bekannte Aktionen
        $(document).on('click', '#mkr-add-ingredient, #mkr-add-utensil, #mkr-add-alternative', function() {
            // Zeitverzögert initialisieren, um sicherzustellen, dass die DOM-Aktualisierungen abgeschlossen sind
            setTimeout(function() {
                initExistingAutocompleteFields();
            }, 100);
        });
    }
    
    // Autovervollständigung initialisieren, wenn die nötigen Voraussetzungen erfüllt sind
    if (typeof mkrAutocomplete !== 'undefined' && mkrAutocomplete.restUrl) {
        // Bestehende Felder initialisieren
        initExistingAutocompleteFields();
        
        // Überwachung für dynamisch hinzugefügte Felder einrichten
        setupDynamicFieldMonitoring();
    }
    
    /**
     * Frontend-Suchfunktion für Rezepte
     */
    function setupRecipeSearch() {
        const searchInput = $('#mkr-search');
        const searchResults = $('#mkr-search-results');
        
        if (!searchInput.length) {
            return;
        }
        
        // Timeout für die Verzögerung der Suche
        let searchTimeout = null;
        
        // Eingabe-Handler
        searchInput.on('input', function() {
            const query = $(this).val();
            
            // Leeres Suchfeld: Ergebnisse ausblenden
            if (query.length < 2) {
                searchResults.removeClass('active').empty();
                return;
            }
            
            // Verzögerung, um zu vermeiden, dass bei jedem Tastendruck eine Anfrage gesendet wird
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                // AJAX-Anfrage an die REST-API
                $.ajax({
                    url: mkrAutocomplete.restUrl + 'recipes/search',
                    method: 'GET',
                    data: { 
                        search: query,
                        _wpnonce: mkrAutocomplete.nonce
                    },
                    beforeSend: function(xhr) {
                        // Authorization-Header für die REST-API setzen
                        xhr.setRequestHeader('X-WP-Nonce', mkrAutocomplete.nonce);
                        
                        // Loading-Indikator anzeigen
                        searchResults.html('<div class="mkr-search-loading">Suche...</div>').addClass('active');
                    },
                    success: function(data) {
                        // Ergebnisse anzeigen
                        searchResults.empty();
                        
                        if (data.length > 0) {
                            // Ergebnisse anzeigen
                            $.each(data, function(index, item) {
                                const resultItem = $('<div class="mkr-search-item">')
                                    .html('<div class="mkr-search-item-title">' + item.title + '</div>')
                                    .on('click', function() {
                                        window.location.href = item.permalink;
                                    });
                                
                                searchResults.append(resultItem);
                            });
                        } else {
                            // Keine Ergebnisse gefunden
                            searchResults.html('<div class="mkr-search-no-results">Keine Ergebnisse gefunden</div>');
                        }
                        
                        searchResults.addClass('active');
                    },
                    error: function(xhr, status, error) {
                        console.error('Fehler bei der Rezeptsuche:', error);
                        searchResults.html('<div class="mkr-search-error">Ein Fehler ist aufgetreten</div>').addClass('active');
                    }
                });
            }, 500);
        });
        
        // Klick außerhalb des Suchfelds schließt die Ergebnisse
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.mkr-search-container').length) {
                searchResults.removeClass('active');
            }
        });
        
        // Escape-Taste schließt die Ergebnisse
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && searchResults.hasClass('active')) {
                searchResults.removeClass('active');
                searchInput.focus();
            }
        });
    }
    
    // Rezeptsuche initialisieren
    setupRecipeSearch();
});