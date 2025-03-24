/**
 * Einstellungsmenü-Funktionalität für Mein Kochbuch
 * 
 * Ermöglicht Anpassungen der Darstellung wie Farbschema, Schriftgröße und Layout.
 * Speichert Einstellungen im localStorage für persistente Benutzerpräferenzen.
 */

jQuery(document).ready(function($) {
    // DOM-Elemente
    const settingsToggle = $('#settings-toggle');
    const settingsMenu = $('#settings-menu');
    const saveSettings = $('#save-settings');
    const closeSettings = $('#close-settings');
    const colorSchemeInputs = $('input[name="color-scheme"]');
    const fontSizeInputs = $('input[name="font-size"]');
    const layoutInputs = $('input[name="layout"]');
    
    // localStorage-Keys
    const COLOR_SCHEME_KEY = 'mkr_color_scheme';
    const FONT_SIZE_KEY = 'mkr_font_size';
    const LAYOUT_KEY = 'mkr_layout';
    
    /**
     * Initialisiert das Einstellungsmenü und lädt die gespeicherten Präferenzen
     */
    function initSettingsMenu() {
        // Öffne/Schließe das Einstellungsmenü
        settingsToggle.on('click', function(e) {
            e.preventDefault();
            settingsMenu.addClass('active').attr('aria-hidden', 'false');
            trapFocus(settingsMenu);
        });
        
        closeSettings.on('click', function(e) {
            e.preventDefault();
            settingsMenu.removeClass('active').attr('aria-hidden', 'true');
            restoreFocus();
        });
        
        // Schließen mit Escape-Taste
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && settingsMenu.hasClass('active')) {
                settingsMenu.removeClass('active').attr('aria-hidden', 'true');
                restoreFocus();
            }
        });
        
        // Klick außerhalb des Menüs schließt es
        $(document).on('click', function(e) {
            if (settingsMenu.hasClass('active') && 
                !$(e.target).closest('.mkr-settings-content').length && 
                !$(e.target).closest('#settings-toggle').length) {
                settingsMenu.removeClass('active').attr('aria-hidden', 'true');
                restoreFocus();
            }
        });
        
        // Speichern-Button
        saveSettings.on('click', function(e) {
            e.preventDefault();
            saveUserSettings();
            settingsMenu.removeClass('active').attr('aria-hidden', 'true');
            restoreFocus();
        });
        
        // Button-Gruppen-Auswahl visualisieren
        $('.mkr-button-group input[type="radio"]').on('change', function() {
            const group = $(this).attr('name');
            $(`.mkr-button-group.${group} .mkr-button-label`).removeClass('selected');
            $(this).closest('.mkr-button-label').addClass('selected');
        });
        
        // Gespeicherte Einstellungen laden
        loadUserSettings();
    }
    
    /**
     * Speichert die Benutzereinstellungen im localStorage
     */
    function saveUserSettings() {
        // Farbschema
        const colorScheme = $('input[name="color-scheme"]:checked').val();
        if (colorScheme) {
            $('body').removeClass('light dark high-contrast').addClass(colorScheme);
            localStorage.setItem(COLOR_SCHEME_KEY, colorScheme);
            
            // Passenden Meta-Theme-Color setzen
            let themeColor = '#ffffff'; // Standard für Light-Modus
            if (colorScheme === 'dark') {
                themeColor = '#222222';
            } else if (colorScheme === 'high-contrast') {
                themeColor = '#000000';
            }
            
            // Meta-Tag aktualisieren oder erstellen
            let metaThemeColor = $('meta[name="theme-color"]');
            if (metaThemeColor.length) {
                metaThemeColor.attr('content', themeColor);
            } else {
                $('head').append(`<meta name="theme-color" content="${themeColor}">`);
            }
        }
        
        // Schriftgröße
        const fontSize = $('input[name="font-size"]:checked').val();
        if (fontSize) {
            $('body').removeClass('small medium large x-large').addClass(fontSize);
            localStorage.setItem(FONT_SIZE_KEY, fontSize);
        }
        
        // Layout
        const layout = $('input[name="layout"]:checked').val();
        if (layout) {
            $('body').removeClass('default ingredients-first').addClass(layout);
            localStorage.setItem(LAYOUT_KEY, layout);
        }
    }
    
    /**
     * Lädt die gespeicherten Benutzereinstellungen aus dem localStorage
     */
    function loadUserSettings() {
        // Farbschema
        const savedColorScheme = localStorage.getItem(COLOR_SCHEME_KEY);
        if (savedColorScheme) {
            $('body').addClass(savedColorScheme);
            $(`input[name="color-scheme"][value="${savedColorScheme}"]`).prop('checked', true)
                .closest('.mkr-button-label').addClass('selected');
            
            // Passenden Meta-Theme-Color setzen
            let themeColor = '#ffffff'; // Standard für Light-Modus
            if (savedColorScheme === 'dark') {
                themeColor = '#222222';
            } else if (savedColorScheme === 'high-contrast') {
                themeColor = '#000000';
            }
            
            // Meta-Tag aktualisieren oder erstellen
            let metaThemeColor = $('meta[name="theme-color"]');
            if (metaThemeColor.length) {
                metaThemeColor.attr('content', themeColor);
            } else {
                $('head').append(`<meta name="theme-color" content="${themeColor}">`);
            }
        } else {
            // Standardmäßig Light-Modus
            $('body').addClass('light');
            $('input[name="color-scheme"][value="light"]').prop('checked', true)
                .closest('.mkr-button-label').addClass('selected');
        }
        
        // Schriftgröße
        const savedFontSize = localStorage.getItem(FONT_SIZE_KEY);
        if (savedFontSize) {
            $('body').addClass(savedFontSize);
            $(`input[name="font-size"][value="${savedFontSize}"]`).prop('checked', true)
                .closest('.mkr-button-label').addClass('selected');
        } else {
            // Standardmäßig mittlere Schriftgröße
            $('body').addClass('medium');
            $('input[name="font-size"][value="medium"]').prop('checked', true)
                .closest('.mkr-button-label').addClass('selected');
        }
        
        // Layout
        const savedLayout = localStorage.getItem(LAYOUT_KEY);
        if (savedLayout) {
            $('body').addClass(savedLayout);
            $(`input[name="layout"][value="${savedLayout}"]`).prop('checked', true)
                .closest('.mkr-button-label').addClass('selected');
        } else {
            // Standardmäßig Standard-Layout
            $('body').addClass('default');
            $('input[name="layout"][value="default"]').prop('checked', true)
                .closest('.mkr-button-label').addClass('selected');
        }
    }
    
    let lastFocusedElement = null;
    
    /**
     * Hilft bei der Tastaturnavigation, indem der Fokus im Modal gehalten wird
     */
    function trapFocus(modal) {
        lastFocusedElement = document.activeElement;
        
        // Fokussierbare Elemente im Modal finden
        const focusableElements = modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        const firstFocusableElement = focusableElements[0];
        const lastFocusableElement = focusableElements[focusableElements.length - 1];
        
        // Fokus auf das erste Element setzen
        setTimeout(function() {
            firstFocusableElement.focus();
        }, 100);
        
        // Tastaturnavigation im Modal halten
        modal.on('keydown', function(e) {
            const isTabPressed = e.key === 'Tab';
            
            if (!isTabPressed) {
                return;
            }
            
            if (e.shiftKey) { // Shift + Tab
                if (document.activeElement === firstFocusableElement) {
                    lastFocusableElement.focus();
                    e.preventDefault();
                }
            } else { // Tab
                if (document.activeElement === lastFocusableElement) {
                    firstFocusableElement.focus();
                    e.preventDefault();
                }
            }
        });
    }
    
    /**
     * Stellt den Fokus auf das Element wieder her, das vor dem Öffnen des Modals fokussiert war
     */
    function restoreFocus() {
        if (lastFocusedElement) {
            setTimeout(function() {
                lastFocusedElement.focus();
            }, 100);
        }
    }
    
    // Einstellungsmenü initialisieren, wenn die nötigen Elemente vorhanden sind
    if (settingsToggle.length && settingsMenu.length) {
        initSettingsMenu();
    }
});