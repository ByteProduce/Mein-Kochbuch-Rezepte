/**
 * Portionsrechner-Funktionalität für Mein Kochbuch
 * 
 * Ermöglicht die Anpassung von Zutatenmengen basierend auf der Änderung der Portionenzahl.
 * Unterstützt auch die Konvertierung zwischen metrischen Einheiten und US-Cups.
 */

jQuery(document).ready(function($) {
    // DOM-Elemente
    const portionsInput = $('#portions');
    const ingredientsList = $('.mkr-ingredients-list li');
    const decreaseButton = $('.mkr-decrease-portions');
    const increaseButton = $('.mkr-increase-portions');
    const unitSelector = $('#unit-system');
    
    // Standardeinheiten und ihre Umrechnungsfaktoren
    const unitConversions = {
        'g': { type: 'weight', factor: 1 },
        'kg': { type: 'weight', factor: 1000 },
        'ml': { type: 'volume', factor: 1 },
        'l': { type: 'volume', factor: 1000 },
        'TL': { type: 'volume', factor: 5 },   // 1 TL ≈ 5ml
        'EL': { type: 'volume', factor: 15 },  // 1 EL ≈ 15ml
        'Prise': { type: 'weight', factor: 0.5 }, // Schätzung
        'Stk': { type: 'unit', factor: 1 },
        'Blatt': { type: 'unit', factor: 1 },
        'Scheibe': { type: 'unit', factor: 1 }
    };

    /**
     * Initialisiert den Portionsrechner
     */
    function initPortionsrechner() {
        // Funktionalität für +/- Buttons
        decreaseButton.on('click', function() {
            const currentPortions = parseInt(portionsInput.val());
            if (currentPortions > 1) {
                portionsInput.val(currentPortions - 1).trigger('change');
            }
        });
        
        increaseButton.on('click', function() {
            const currentPortions = parseInt(portionsInput.val());
            portionsInput.val(currentPortions + 1).trigger('change');
        });

        // Ereignis für direkte Eingabe
        portionsInput.on('change', updateIngredientAmounts);
        
        // Ereignis für Einheitenwechsel
        unitSelector.on('change', updateUnitSystem);
        
        // Initialen Zustand speichern
        ingredientsList.each(function() {
            const amountSpan = $(this).find('.mkr-amount');
            const originalAmount = parseFloat(amountSpan.data('original-amount'));
            amountSpan.data('original-amount', originalAmount);
        });
    }

    /**
     * Aktualisiert die Zutatenmengen basierend auf der geänderten Portionenzahl
     */
    function updateIngredientAmounts() {
        const originalPortions = parseInt(portionsInput.data('original-portions'));
        const newPortions = parseInt(portionsInput.val());
        
        if (isNaN(newPortions) || newPortions < 1) {
            portionsInput.val(originalPortions);
            return;
        }
        
        ingredientsList.each(function() {
            const amountSpan = $(this).find('.mkr-amount');
            const originalAmount = parseFloat($(this).data('original-amount'));
            
            if (!isNaN(originalAmount)) {
                const newAmount = (originalAmount * newPortions) / originalPortions;
                
                // Formatierung der Zahl: Keine Dezimalstellen für ganze Zahlen, sonst 1 Dezimalstelle
                const formattedAmount = Number.isInteger(newAmount) ? newAmount : newAmount.toFixed(1);
                amountSpan.text(formattedAmount);
                
                // Auch die US-Cup-Umrechnung aktualisieren, falls sichtbar
                if (unitSelector.val() === 'cups') {
                    updateToCups($(this));
                }
            }
        });
        
        // Nährwertinformationen pro Portion aktualisieren
        updateNutritionPerPortion(originalPortions, newPortions);
    }
    
    /**
     * Aktualisiert die Nährwertangaben pro Portion
     */
    function updateNutritionPerPortion(originalPortions, newPortions) {
        const nutritionValues = $('.mkr-nutrition-value');
        
        nutritionValues.each(function() {
            const originalValue = parseFloat($(this).data('original-value'));
            
            if (!isNaN(originalValue)) {
                const newValue = (originalValue * originalPortions) / newPortions;
                $(this).text(newValue.toFixed(1));
            } else {
                // Beim ersten Aufruf den Originalwert speichern
                const currentValue = parseFloat($(this).text());
                $(this).data('original-value', currentValue);
                
                const newValue = (currentValue * originalPortions) / newPortions;
                $(this).text(newValue.toFixed(1));
            }
        });
    }

    /**
     * Wechselt zwischen metrischen Einheiten und US-Cups
     */
    function updateUnitSystem() {
        const system = $(this).val();
        
        if (system === 'cups') {
            // Zu US-Cups konvertieren
            ingredientsList.each(function() {
                updateToCups($(this));
            });
        } else {
            // Zurück zu metrischen Einheiten
            ingredientsList.each(function() {
                const amountSpan = $(this).find('.mkr-amount');
                const unitSpan = $(this).find('.mkr-unit');
                const cupsAmount = $(this).find('.mkr-cups-amount');
                
                // Ursprüngliche Anzeige wiederherstellen
                amountSpan.show();
                unitSpan.show();
                cupsAmount.hide();
            });
        }
    }

    /**
     * Konvertiert eine Zutatenmenge zu US-Cups wenn möglich
     */
    function updateToCups(ingredientItem) {
        const amountSpan = ingredientItem.find('.mkr-amount');
        const unitSpan = ingredientItem.find('.mkr-unit');
        const cupsAmount = ingredientItem.find('.mkr-cups-amount');
        const amount = parseFloat(amountSpan.text());
        const unit = unitSpan.text();
        const weightPerCup = ingredientItem.data('weight-per-cup');
        const isLiquid = ingredientItem.data('is-liquid') === true || ingredientItem.data('is-liquid') === 'true';
        
        // Konvertierung nur durchführen, wenn die notwendigen Daten verfügbar sind
        if (!isNaN(amount) && unitConversions[unit]) {
            const unitInfo = unitConversions[unit];
            
            if (unitInfo.type === 'weight' && weightPerCup) {
                // Gewicht zu Cups konvertieren
                const gramsAmount = amount * unitInfo.factor;
                const cupsValue = gramsAmount / parseFloat(weightPerCup);
                formatAndDisplayCups(cupsValue, amountSpan, unitSpan, cupsAmount);
            } 
            else if (unitInfo.type === 'volume' && isLiquid) {
                // Volumen zu Cups konvertieren (1 Cup = 236.588 ml)
                const mlAmount = amount * unitInfo.factor;
                const cupsValue = mlAmount / 236.588;
                formatAndDisplayCups(cupsValue, amountSpan, unitSpan, cupsAmount);
            }
            else {
                // Keine Konvertierung möglich - ursprüngliche Anzeige beibehalten
                amountSpan.show();
                unitSpan.show();
                cupsAmount.hide();
            }
        }
    }

    /**
     * Formatiert die Cup-Menge und zeigt sie an
     */
    function formatAndDisplayCups(cupsValue, amountSpan, unitSpan, cupsAmount) {
        let formattedCups = '';
        
        // Formatierung der Cup-Mengen für bessere Lesbarkeit
        if (cupsValue < 0.125) {
            formattedCups = 'ein Hauch';
        } else if (cupsValue <= 0.20) {
            formattedCups = '⅛ Cup';
        } else if (cupsValue <= 0.29) {
            formattedCups = '¼ Cup';
        } else if (cupsValue <= 0.425) {
            formattedCups = '⅓ Cup';
        } else if (cupsValue <= 0.625) {
            formattedCups = '½ Cup';
        } else if (cupsValue <= 0.79) {
            formattedCups = '⅔ Cup';
        } else if (cupsValue <= 0.875) {
            formattedCups = '¾ Cup';
        } else if (cupsValue < 1) {
            formattedCups = '⅞ Cup';
        } else {
            // Für größere Mengen: x.x Cups
            const wholeCups = Math.floor(cupsValue);
            const fraction = cupsValue - wholeCups;
            
            if (fraction < 0.125) {
                formattedCups = wholeCups + ' Cup' + (wholeCups !== 1 ? 's' : '');
            } else if (fraction < 0.375) {
                formattedCups = wholeCups + '¼ Cups';
            } else if (fraction < 0.625) {
                formattedCups = wholeCups + '½ Cups';
            } else if (fraction < 0.875) {
                formattedCups = wholeCups + '¾ Cups';
            } else {
                formattedCups = (wholeCups + 1) + ' Cup' + ((wholeCups + 1) !== 1 ? 's' : '');
            }
        }
        
        // Anzeige der Cup-Menge
        cupsAmount.text(formattedCups).show();
        amountSpan.hide();
        unitSpan.hide();
    }

    // Portionsrechner initialisieren, wenn die nötigen Elemente vorhanden sind
    if (portionsInput.length && ingredientsList.length) {
        initPortionsrechner();
    }
});