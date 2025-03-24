<?php
/**
 * Template für den Mahlzeitenplaner
 *
 * @package Mein Kochbuch Rezepte
 */

// Sicherheitsüberprüfung - direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Prüfen, ob der Benutzer angemeldet ist
$is_logged_in = is_user_logged_in();
?>

<div class="mkr-meal-planner-container">
    <h1 class="mkr-page-title"><?php _e('Mahlzeitenplaner', 'mein-kochbuch-rezepte'); ?></h1>
    
    <?php if (!$is_logged_in): ?>
        <div class="mkr-login-prompt">
            <p><?php _e('Um den Mahlzeitenplaner zu verwenden und Ihren Plan zu speichern, müssen Sie angemeldet sein.', 'mein-kochbuch-rezepte'); ?></p>
            <p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="mkr-button mkr-button-primary">
                    <?php _e('Anmelden', 'mein-kochbuch-rezepte'); ?>
                </a>
                <?php if (get_option('users_can_register')): ?>
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="mkr-button">
                        <?php _e('Registrieren', 'mein-kochbuch-rezepte'); ?>
                    </a>
                <?php endif; ?>
            </p>
            <p><?php _e('Sie können den Planer auch ohne Anmeldung verwenden, aber Ihre Änderungen werden nicht gespeichert.', 'mein-kochbuch-rezepte'); ?></p>
        </div>
    <?php endif; ?>
    
    <div id="mkr-meal-planner-app">
        <!-- Steuerelemente -->
        <div class="mkr-planner-controls">
            <div class="mkr-date-navigation">
                <button type="button" id="mkr-prev-week" class="mkr-button">
                    <span aria-hidden="true">←</span> <?php _e('Vorherige Woche', 'mein-kochbuch-rezepte'); ?>
                </button>
                
                <div class="mkr-current-week">
                    <span id="mkr-date-range"><?php _e('Woche vom', 'mein-kochbuch-rezepte'); ?> ...</span>
                </div>
                
                <button type="button" id="mkr-next-week" class="mkr-button">
                    <?php _e('Nächste Woche', 'mein-kochbuch-rezepte'); ?> <span aria-hidden="true">→</span>
                </button>
            </div>
            
            <div class="mkr-planner-actions">
                <button type="button" id="mkr-today" class="mkr-button mkr-button-secondary">
                    <?php _e('Heute', 'mein-kochbuch-rezepte'); ?>
                </button>
                
                <?php if ($is_logged_in): ?>
                    <button type="button" id="mkr-save-plan" class="mkr-button mkr-button-primary">
                        <?php _e('Plan speichern', 'mein-kochbuch-rezepte'); ?>
                    </button>
                <?php endif; ?>
                
                <button type="button" id="mkr-generate-shopping-list" class="mkr-button mkr-button-secondary">
                    <?php _e('Einkaufsliste erstellen', 'mein-kochbuch-rezepte'); ?>
                </button>
                
                <button type="button" id="mkr-print-plan" class="mkr-button">
                    <?php _e('Drucken', 'mein-kochbuch-rezepte'); ?>
                </button>
            </div>
        </div>
        
        <!-- Wochenplan -->
        <div id="mkr-weekly-planner" class="mkr-weekly-planner">
            <div class="mkr-loading">
                <?php _e('Lädt...', 'mein-kochbuch-rezepte'); ?>
            </div>
        </div>
        
        <!-- Rezeptsuche und Vorschläge -->
        <div class="mkr-recipe-selection">
            <div class="mkr-recipe-search">
                <h2><?php _e('Rezepte suchen', 'mein-kochbuch-rezepte'); ?></h2>
                
                <div class="mkr-search-form">
                    <input type="text" id="mkr-recipe-search" placeholder="<?php esc_attr_e('Rezeptname, Zutat...', 'mein-kochbuch-rezepte'); ?>" aria-label="<?php esc_attr_e('Rezepte suchen', 'mein-kochbuch-rezepte'); ?>">
                    
                    <div class="mkr-search-filters">
                        <select id="mkr-filter-cuisine" aria-label="<?php esc_attr_e('Küchenart', 'mein-kochbuch-rezepte'); ?>">
                            <option value=""><?php _e('Alle Küchenarten', 'mein-kochbuch-rezepte'); ?></option>
                            <?php
                            $cuisines = get_terms(['taxonomy' => 'cuisine', 'hide_empty' => true]);
                            foreach ($cuisines as $cuisine) {
                                echo '<option value="' . esc_attr($cuisine->slug) . '">' . esc_html($cuisine->name) . '</option>';
                            }
                            ?>
                        </select>
                        
                        <select id="mkr-filter-diet" aria-label="<?php esc_attr_e('Diät', 'mein-kochbuch-rezepte'); ?>">
                            <option value=""><?php _e('Alle Diäten', 'mein-kochbuch-rezepte'); ?></option>
                            <?php
                            $diets = get_terms(['taxonomy' => 'diet', 'hide_empty' => true]);
                            foreach ($diets as $diet) {
                                echo '<option value="' . esc_attr($diet->slug) . '">' . esc_html($diet->name) . '</option>';
                            }
                            ?>
                        </select>
                        
                        <select id="mkr-filter-time" aria-label="<?php esc_attr_e('Zubereitungszeit', 'mein-kochbuch-rezepte'); ?>">
                            <option value=""><?php _e('Jede Zubereitungszeit', 'mein-kochbuch-rezepte'); ?></option>
                            <option value="15"><?php _e('Bis 15 Minuten', 'mein-kochbuch-rezepte'); ?></option>
                            <option value="30"><?php _e('Bis 30 Minuten', 'mein-kochbuch-rezepte'); ?></option>
                            <option value="60"><?php _e('Bis 1 Stunde', 'mein-kochbuch-rezepte'); ?></option>
                            <option value="90"><?php _e('Bis 1,5 Stunden', 'mein-kochbuch-rezepte'); ?></option>
                        </select>
                        
                        <button type="button" id="mkr-search-recipes" class="mkr-button mkr-button-primary">
                            <?php _e('Suchen', 'mein-kochbuch-rezepte'); ?>
                        </button>
                    </div>
                </div>
                
                <div id="mkr-search-results" class="mkr-search-results"></div>
                
                <div class="mkr-pagination"></div>
            </div>
            
            <div class="mkr-recipe-suggestions">
                <h2><?php _e('Rezeptvorschläge', 'mein-kochbuch-rezepte'); ?></h2>
                
                <div class="mkr-suggestion-filters">
                    <button type="button" data-meal-type="breakfast" class="mkr-meal-type-filter mkr-button">
                        <?php _e('Frühstück', 'mein-kochbuch-rezepte'); ?>
                    </button>
                    <button type="button" data-meal-type="lunch" class="mkr-meal-type-filter mkr-button">
                        <?php _e('Mittagessen', 'mein-kochbuch-rezepte'); ?>
                    </button>
                    <button type="button" data-meal-type="dinner" class="mkr-meal-type-filter mkr-button mkr-active">
                        <?php _e('Abendessen', 'mein-kochbuch-rezepte'); ?>
                    </button>
                    <button type="button" data-meal-type="snack" class="mkr-meal-type-filter mkr-button">
                        <?php _e('Snack', 'mein-kochbuch-rezepte'); ?>
                    </button>
                </div>
                
                <div id="mkr-suggestions-content" class="mkr-suggestions-content">
                    <div class="mkr-loading">
                        <?php _e('Lädt Vorschläge...', 'mein-kochbuch-rezepte'); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status und Benachrichtigungen -->
        <div id="mkr-notifications" class="mkr-notifications"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Konfiguration
    const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    const restUrl = '<?php echo esc_url_raw(rest_url('mkr/v1')); ?>';
    const nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
    const shoppingListPage = '<?php echo esc_url(get_permalink(get_option('mkr_shopping_list_page_id'))); ?>';
    
    // Zustandsvariablen
    let currentStartDate = moment().startOf('week');
    let currentWeekDays = [];
    let currentMealPlan = {};
    let draggedRecipe = null;
    let activeMealType = 'dinner';  // Standard: Abendessen
    
    // Initialisierung
    function init() {
        // Datumsbereich setzen
        updateDateRange();
        
        // Wochentage generieren
        generateWeekDays();
        
        // Planer anzeigen
        loadMealPlan();
        
        // Vorschläge laden
        loadRecipeSuggestions();
        
        // Event-Handler einrichten
        setupEventHandlers();
    }
    
    // Datum aktualisieren
    function updateDateRange() {
        const endDate = moment(currentStartDate).add(6, 'days');
        const formattedStart = currentStartDate.format('DD.MM.YYYY');
        const formattedEnd = endDate.format('DD.MM.YYYY');
        
        $('#mkr-date-range').text(`${formattedStart} - ${formattedEnd}`);
    }
    
    // Wochentage generieren
    function generateWeekDays() {
        currentWeekDays = [];
        
        for (let i = 0; i < 7; i++) {
            const day = moment(currentStartDate).add(i, 'days');
            currentWeekDays.push({
                date: day.format('YYYY-MM-DD'),
                dayName: day.locale('de').format('dddd'),
                dayShort: day.locale('de').format('dd'),
                dayNum: day.date()
            });
        }
    }
    
    // Mahlzeitenplan laden
    function loadMealPlan() {
        const startDate = currentStartDate.format('YYYY-MM-DD');
        const numDays = 7;
        
        $('#mkr-weekly-planner').html('<div class="mkr-loading"><?php _e('Lädt...', 'mein-kochbuch-rezepte'); ?></div>');
        
        // Planer via REST API laden
        $.ajax({
            url: `${restUrl}/meal-planner`,
            method: 'GET',
            data: {
                start_date: startDate,
                num_days: numDays
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(response) {
                if (response.success) {
                    currentMealPlan = response.data.meal_plan || {};
                    renderMealPlanner();
                } else {
                    showNotification('error', '<?php _e('Fehler beim Laden des Plans', 'mein-kochbuch-rezepte'); ?>');
                    $('#mkr-weekly-planner').html('<div class="mkr-error"><?php _e('Fehler beim Laden des Plans.', 'mein-kochbuch-rezepte'); ?></div>');
                }
            },
            error: function() {
                showNotification('error', '<?php _e('Fehler beim Laden des Plans', 'mein-kochbuch-rezepte'); ?>');
                $('#mkr-weekly-planner').html('<div class="mkr-error"><?php _e('Fehler beim Laden des Plans.', 'mein-kochbuch-rezepte'); ?></div>');
            }
        });
    }
    
    // Mahlzeitenplaner rendern
    function renderMealPlanner() {
        const plannerHtml = `
            <table class="mkr-meal-planner-table">
                <thead>
                    <tr>
                        <th class="mkr-meal-time-header"><?php _e('Mahlzeit', 'mein-kochbuch-rezepte'); ?></th>
                        ${currentWeekDays.map(day => `
                            <th class="mkr-day-header ${isTodayClass(day.date)}">
                                <div class="mkr-day-name">${day.dayName}</div>
                                <div class="mkr-day-num">${day.dayNum}</div>
                            </th>
                        `).join('')}
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="mkr-meal-time"><?php _e('Frühstück', 'mein-kochbuch-rezepte'); ?></td>
                        ${currentWeekDays.map(day => `
                            <td class="mkr-meal-cell ${isTodayClass(day.date)}" data-date="${day.date}" data-meal-type="breakfast">
                                ${renderMeals(day.date, 'breakfast')}
                                <button type="button" class="mkr-add-meal" aria-label="<?php esc_attr_e('Frühstück hinzufügen', 'mein-kochbuch-rezepte'); ?>">+</button>
                            </td>
                        `).join('')}
                    </tr>
                    <tr>
                        <td class="mkr-meal-time"><?php _e('Mittagessen', 'mein-kochbuch-rezepte'); ?></td>
                        ${currentWeekDays.map(day => `
                            <td class="mkr-meal-cell ${isTodayClass(day.date)}" data-date="${day.date}" data-meal-type="lunch">
                                ${renderMeals(day.date, 'lunch')}
                                <button type="button" class="mkr-add-meal" aria-label="<?php esc_attr_e('Mittagessen hinzufügen', 'mein-kochbuch-rezepte'); ?>">+</button>
                            </td>
                        `).join('')}
                    </tr>
                    <tr>
                        <td class="mkr-meal-time"><?php _e('Abendessen', 'mein-kochbuch-rezepte'); ?></td>
                        ${currentWeekDays.map(day => `
                            <td class="mkr-meal-cell ${isTodayClass(day.date)}" data-date="${day.date}" data-meal-type="dinner">
                                ${renderMeals(day.date, 'dinner')}
                                <button type="button" class="mkr-add-meal" aria-label="<?php esc_attr_e('Abendessen hinzufügen', 'mein-kochbuch-rezepte'); ?>">+</button>
                            </td>
                        `).join('')}
                    </tr>
                    <tr>
                        <td class="mkr-meal-time"><?php _e('Snack', 'mein-kochbuch-rezepte'); ?></td>
                        ${currentWeekDays.map(day => `
                            <td class="mkr-meal-cell ${isTodayClass(day.date)}" data-date="${day.date}" data-meal-type="snack">
                                ${renderMeals(day.date, 'snack')}
                                <button type="button" class="mkr-add-meal" aria-label="<?php esc_attr_e('Snack hinzufügen', 'mein-kochbuch-rezepte'); ?>">+</button>
                            </td>
                        `).join('')}
                    </tr>
                </tbody>
            </table>
        `;
        
        $('#mkr-weekly-planner').html(plannerHtml);
        setupDragAndDrop();
    }
    
    // Mahlzeiten für eine Zelle rendern
    function renderMeals(date, mealType) {
        if (!currentMealPlan[date] || !currentMealPlan[date][mealType] || !currentMealPlan[date][mealType].length) {
            return '';
        }
        
        const meals = currentMealPlan[date][mealType];
        
        return meals.map(meal => `
            <div class="mkr-meal-item" data-recipe-id="${meal.id || ''}">
                <div class="mkr-meal-content">
                    ${meal.title}
                    ${meal.id ? `<a href="${meal.permalink}" target="_blank" class="mkr-meal-link" aria-label="<?php esc_attr_e('Rezept öffnen', 'mein-kochbuch-rezepte'); ?>">↗</a>` : ''}
                </div>
                <button type="button" class="mkr-remove-meal" aria-label="<?php esc_attr_e('Mahlzeit entfernen', 'mein-kochbuch-rezepte'); ?>">×</button>
            </div>
        `).join('');
    }
    
    // CSS-Klasse für den heutigen Tag
    function isTodayClass(date) {
        return moment().format('YYYY-MM-DD') === date ? 'mkr-today' : '';
    }
    
    // Event-Handler einrichten
    function setupEventHandlers() {
        // Kalendar-Navigation
        $('#mkr-prev-week').on('click', function() {
            currentStartDate.subtract(7, 'days');
            updateDateRange();
            generateWeekDays();
            loadMealPlan();
        });
        
        $('#mkr-next-week').on('click', function() {
            currentStartDate.add(7, 'days');
            updateDateRange();
            generateWeekDays();
            loadMealPlan();
        });
        
        $('#mkr-today').on('click', function() {
            currentStartDate = moment().startOf('week');
            updateDateRange();
            generateWeekDays();
            loadMealPlan();
        });
        
        // Plan speichern
        $('#mkr-save-plan').on('click', function() {
            if (!isLoggedIn) {
                showNotification('warning', '<?php _e('Bitte melden Sie sich an, um den Plan zu speichern.', 'mein-kochbuch-rezepte'); ?>');
                return;
            }
            
            saveMealPlan();
        });
        
        // Einkaufsliste generieren
        $('#mkr-generate-shopping-list').on('click', function() {
            generateShoppingList();
        });
        
        // Plan drucken
        $('#mkr-print-plan').on('click', function() {
            window.print();
        });
        
        // Mahlzeit hinzufügen
        $(document).on('click', '.mkr-add-meal', function() {
            const cell = $(this).closest('.mkr-meal-cell');
            const date = cell.data('date');
            const mealType = cell.data('meal-type');
            
            promptAddMeal(date, mealType);
        });
        
        // Mahlzeit entfernen
        $(document).on('click', '.mkr-remove-meal', function() {
            const mealItem = $(this).closest('.mkr-meal-item');
            const cell = mealItem.closest('.mkr-meal-cell');
            const date = cell.data('date');
            const mealType = cell.data('meal-type');
            const index = cell.find('.mkr-meal-item').index(mealItem);
            
            removeMeal(date, mealType, index);
        });
        
        // Rezeptsuche
        $('#mkr-search-recipes').on('click', function() {
            searchRecipes();
        });
        
        $('#mkr-recipe-search').on('keypress', function(e) {
            if (e.which === 13) {
                searchRecipes();
                e.preventDefault();
            }
        });
        
        // Mahlzeittyp-Filter für Vorschläge
        $('.mkr-meal-type-filter').on('click', function() {
            $('.mkr-meal-type-filter').removeClass('mkr-active');
            $(this).addClass('mkr-active');
            
            activeMealType = $(this).data('meal-type');
            loadRecipeSuggestions();
        });
    }
    
    // Drag-and-Drop einrichten
    function setupDragAndDrop() {
        // Rezepte aus Suchergebnissen und Vorschlägen ziehbar machen
        $('.mkr-recipe-card').draggable({
            helper: 'clone',
            revert: 'invalid',
            zIndex: 100,
            start: function(event, ui) {
                const recipeCard = $(this);
                draggedRecipe = {
                    id: recipeCard.data('recipe-id'),
                    title: recipeCard.data('recipe-title'),
                    permalink: recipeCard.data('recipe-permalink')
                };
            }
        });
        
        // Plannerzellen als Dropzone definieren
        $('.mkr-meal-cell').droppable({
            accept: '.mkr-recipe-card',
            hoverClass: 'mkr-drop-hover',
            drop: function(event, ui) {
                const cell = $(this);
                const date = cell.data('date');
                const mealType = cell.data('meal-type');
                
                if (draggedRecipe) {
                    addMeal(date, mealType, draggedRecipe);
                    draggedRecipe = null;
                }
            }
        });
    }
    
    // Mahlzeit hinzufügen (manuell)
    function promptAddMeal(date, mealType) {
        const mealTitle = prompt(<?php echo json_encode(__('Name der Mahlzeit eingeben:', 'mein-kochbuch-rezepte')); ?>);
        
        if (mealTitle) {
            addMeal(date, mealType, { title: mealTitle });
        }
    }
    
    // Mahlzeit zum Plan hinzufügen
    function addMeal(date, mealType, meal) {
        if (!currentMealPlan[date]) {
            currentMealPlan[date] = {
                breakfast: [],
                lunch: [],
                dinner: [],
                snack: []
            };
        }
        
        if (!currentMealPlan[date][mealType]) {
            currentMealPlan[date][mealType] = [];
        }
        
        currentMealPlan[date][mealType].push(meal);
        renderMealPlanner();
        
        if (isLoggedIn) {
            saveMealPlan();
        }
    }
    
    // Mahlzeit aus dem Plan entfernen
    function removeMeal(date, mealType, index) {
        if (currentMealPlan[date] && currentMealPlan[date][mealType] && 
            currentMealPlan[date][mealType].length > index) {
            
            currentMealPlan[date][mealType].splice(index, 1);
            renderMealPlanner();
            
            if (isLoggedIn) {
                saveMealPlan();
            }
        }
    }
    
    // Mahlzeitenplan speichern
    function saveMealPlan() {
        if (!isLoggedIn) return;
        
        const saveButton = $('#mkr-save-plan');
        saveButton.prop('disabled', true).text(<?php echo json_encode(__('Speichern...', 'mein-kochbuch-rezepte')); ?>);
        
        $.ajax({
            url: `${restUrl}/meal-planner`,
            method: 'POST',
            data: {
                meal_plan: currentMealPlan
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', <?php echo json_encode(__('Plan erfolgreich gespeichert!', 'mein-kochbuch-rezepte')); ?>);
                } else {
                    showNotification('error', <?php echo json_encode(__('Fehler beim Speichern des Plans.', 'mein-kochbuch-rezepte')); ?>);
                }
            },
            error: function() {
                showNotification('error', <?php echo json_encode(__('Fehler beim Speichern des Plans.', 'mein-kochbuch-rezepte')); ?>);
            },
            complete: function() {
                saveButton.prop('disabled', false).text(<?php echo json_encode(__('Plan speichern', 'mein-kochbuch-rezepte')); ?>);
            }
        });
    }
    
    // Einkaufsliste generieren
    function generateShoppingList() {
        // Rezepte im aktuellen Plan sammeln
        const recipeIds = new Set();
        
        Object.values(currentMealPlan).forEach(dayPlan => {
            ['breakfast', 'lunch', 'dinner', 'snack'].forEach(mealType => {
                if (dayPlan[mealType] && dayPlan[mealType].length) {
                    dayPlan[mealType].forEach(meal => {
                        if (meal.id) recipeIds.add(meal.id);
                    });
                }
            });
        });
        
        if (recipeIds.size === 0) {
            showNotification('warning', <?php echo json_encode(__('Keine Rezepte im Plan gefunden. Fügen Sie zuerst Rezepte hinzu.', 'mein-kochbuch-rezepte')); ?>);
            return;
        }
        
        // Hier würde im Idealfall eine API-Anfrage erfolgen, um die Zutatenliste zu erstellen
        // Da diese Funktion noch nicht implementiert ist, führen wir einen einfachen Redirect zur Einkaufsliste durch
        
        showNotification('info', <?php echo json_encode(__('Die Einkaufsliste wird geöffnet...', 'mein-kochbuch-rezepte')); ?>);
        setTimeout(() => {
            window.location.href = shoppingListPage;
        }, 1000);
    }
    
    // Rezepte suchen
    function searchRecipes() {
        const searchTerm = $('#mkr-recipe-search').val();
        const cuisine = $('#mkr-filter-cuisine').val();
        const diet = $('#mkr-filter-diet').val();
        const maxTime = $('#mkr-filter-time').val();
        
        $('#mkr-search-results').html('<div class="mkr-loading"><?php _e('Suche...', 'mein-kochbuch-rezepte'); ?></div>');
        
        // Hier würde eine API-Anfrage erfolgen, um Rezepte zu suchen
        // Für dieses Beispiel erstellen wir eine Mock-Anfrage
        setTimeout(() => {
            // Beispiel-Ergebnisse
            const results = [
                { id: 1, title: "<?php _e('Spaghetti Bolognese', 'mein-kochbuch-rezepte'); ?>", permalink: "#", total_time: 45 },
                { id: 2, title: "<?php _e('Griechischer Salat', 'mein-kochbuch-rezepte'); ?>", permalink: "#", total_time: 15 },
                { id: 3, title: "<?php _e('Pfannkuchen', 'mein-kochbuch-rezepte'); ?>", permalink: "#", total_time: 20 }
            ];
            
            renderSearchResults(results);
        }, 500);
    }
    
    // Suchergebnisse rendern
    function renderSearchResults(results) {
        if (!results || results.length === 0) {
            $('#mkr-search-results').html('<div class="mkr-no-results"><?php _e('Keine Ergebnisse gefunden.', 'mein-kochbuch-rezepte'); ?></div>');
            return;
        }
        
        const resultsHtml = `
            <div class="mkr-recipe-grid">
                ${results.map(recipe => `
                    <div class="mkr-recipe-card" 
                         data-recipe-id="${recipe.id}" 
                         data-recipe-title="${recipe.title}" 
                         data-recipe-permalink="${recipe.permalink}">
                        <div class="mkr-recipe-card-content">
                            <h3 class="mkr-recipe-title">${recipe.title}</h3>
                            <div class="mkr-recipe-meta">
                                <span class="mkr-recipe-time">
                                    <span class="mkr-icon">⏱️</span> ${recipe.total_time} <?php _e('Min.', 'mein-kochbuch-rezepte'); ?>
                                </span>
                            </div>
                            <div class="mkr-recipe-actions">
                                <a href="${recipe.permalink}" class="mkr-button mkr-button-small" target="_blank">
                                    <?php _e('Ansehen', 'mein-kochbuch-rezepte'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
        
        $('#mkr-search-results').html(resultsHtml);
        setupDragAndDrop();
    }
    
    // Rezeptvorschläge laden
    function loadRecipeSuggestions() {
        $('#mkr-suggestions-content').html('<div class="mkr-loading"><?php _e('Lädt Vorschläge...', 'mein-kochbuch-rezepte'); ?></div>');
        
        // Hier würde eine API-Anfrage erfolgen, um Rezeptvorschläge zu laden
        // Für dieses Beispiel erstellen wir eine Mock-Anfrage
        setTimeout(() => {
            // Beispiel-Vorschläge
            const suggestions = [
                { id: 4, title: "<?php _e('Gebratener Lachs', 'mein-kochbuch-rezepte'); ?>", permalink: "#", total_time: 30 },
                { id: 5, title: "<?php _e('Hähnchen-Curry', 'mein-kochbuch-rezepte'); ?>", permalink: "#", total_time: 40 },
                { id: 6, title: "<?php _e('Gemüseauflauf', 'mein-kochbuch-rezepte'); ?>", permalink: "#", total_time: 60 }
            ];
            
            renderSuggestions(suggestions);
        }, 500);
    }
    
    // Vorschläge rendern
    function renderSuggestions(suggestions) {
        if (!suggestions || suggestions.length === 0) {
            $('#mkr-suggestions-content').html('<div class="mkr-no-results"><?php _e('Keine Vorschläge verfügbar.', 'mein-kochbuch-rezepte'); ?></div>');
            return;
        }
        
        const suggestionsHtml = `
            <div class="mkr-recipe-grid">
                ${suggestions.map(recipe => `
                    <div class="mkr-recipe-card" 
                         data-recipe-id="${recipe.id}" 
                         data-recipe-title="${recipe.title}" 
                         data-recipe-permalink="${recipe.permalink}">
                        <div class="mkr-recipe-card-content">
                            <h3 class="mkr-recipe-title">${recipe.title}</h3>
                            <div class="mkr-recipe-meta">
                                <span class="mkr-recipe-time">
                                    <span class="mkr-icon">⏱️</span> ${recipe.total_time} <?php _e('Min.', 'mein-kochbuch-rezepte'); ?>
                                </span>
                            </div>
                            <div class="mkr-recipe-actions">
                                <a href="${recipe.permalink}" class="mkr-button mkr-button-small" target="_blank">
                                    <?php _e('Ansehen', 'mein-kochbuch-rezepte'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
        
        $('#mkr-suggestions-content').html(suggestionsHtml);
        setupDragAndDrop();
    }
    
    // Benachrichtigungen anzeigen
    function showNotification(type, message) {
        const notification = $(`
            <div class="mkr-notification mkr-notification-${type}">
                <span class="mkr-notification-message">${message}</span>
                <button type="button" class="mkr-close-notification">&times;</button>
            </div>
        `);
        
        $('#mkr-notifications').append(notification);
        
        // Benachrichtigung nach 5 Sekunden automatisch ausblenden
        setTimeout(() => {
            notification.addClass('mkr-fade-out');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
        
        // Button zum Schließen
        notification.find('.mkr-close-notification').on('click', function() {
            notification.addClass('mkr-fade-out');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }
    
    // Initialisierung starten
    init();
});
</script>

<style>
.mkr-meal-planner-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.mkr-page-title {
    margin-bottom: 30px;
    text-align: center;
}

.mkr-login-prompt {
    margin-bottom: 30px;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: var(--border-radius);
    text-align: center;
}

.mkr-planner-controls {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 15px;
}

.mkr-date-navigation {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mkr-current-week {
    font-size: 1.1rem;
    font-weight: bold;
    padding: 0 10px;
}

.mkr-planner-actions {
    display: flex;
    gap: 10px;
}

.mkr-weekly-planner {
    margin-bottom: 40px;
    overflow-x: auto;
}

.mkr-meal-planner-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.mkr-meal-planner-table th,
.mkr-meal-planner-table td {
    border: 1px solid var(--border-color);
    padding: 10px;
    vertical-align: top;
}

.mkr-meal-time-header {
    width: 120px;
    text-align: right;
    background-color: #f8f9fa;
}

.mkr-day-header {
    text-align: center;
    background-color: #f8f9fa;
    min-width: 120px;
}

.mkr-day-name {
    font-weight: bold;
}

.mkr-day-num {
    font-size: 0.9rem;
    color: var(--text-muted);
}

.mkr-meal-time {
    text-align: right;
    font-weight: bold;
    background-color: #f8f9fa;
}

.mkr-meal-cell {
    min-height: 100px;
    transition: background-color 0.2s;
}

.mkr-today {
    background-color: var(--primary-light);
}

.mkr-drop-hover {
    background-color: rgba(0, 115, 170, 0.1);
}

.mkr-meal-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 10px;
    margin-bottom: 5px;
    background-color: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.mkr-meal-content {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mkr-meal-link {
    margin-left: 5px;
    color: var(--primary-color);
    text-decoration: none;
}

.mkr-remove-meal {
    background: none;
    border: none;
    color: var(--danger-color);
    cursor: pointer;
    font-size: 1.2rem;
    line-height: 1;
    padding: 0;
    margin-left: 5px;
    opacity: 0.6;
    transition: opacity 0.2s;
}

.mkr-remove-meal:hover {
    opacity: 1;
}

.mkr-add-meal {
    display: block;
    width: 100%;
    padding: 5px;
    margin-top: 5px;
    background-color: transparent;
    border: 1px dashed var(--border-color);
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
}

.mkr-add-meal:hover {
    background-color: rgba(0, 0, 0, 0.05);
    color: var(--text-color);
}

.mkr-recipe-selection {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.mkr-recipe-search,
.mkr-recipe-suggestions {
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: var(--border-radius);
}

.mkr-recipe-search h2,
.mkr-recipe-suggestions h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.mkr-search-form {
    margin-bottom: 20px;
}

#mkr-recipe-search {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
}

.mkr-search-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.mkr-search-filters select {
    flex: 1;
    min-width: 120px;
}

.mkr-recipe-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.mkr-recipe-card {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background-color: white;
    overflow: hidden;
    cursor: move;
    transition: transform 0.2s, box-shadow 0.2s;
}

.mkr-recipe-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.mkr-recipe-card-content {
    padding: 15px;
}

.mkr-recipe-title {
    margin: 0 0 10px 0;
    font-size: 1rem;
}

.mkr-recipe-meta {
    margin-bottom: 10px;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.mkr-recipe-actions {
    text-align: right;
}

.mkr-pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 15px;
}

.mkr-suggestion-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    overflow-x: auto;
    padding-bottom: 5px;
}

.mkr-meal-type-filter {
    white-space: nowrap;
}

.mkr-meal-type-filter.mkr-active {
    background-color: var(--primary-color);
    color: white;
}

.mkr-loading {
    padding: 20px;
    text-align: center;
    color: var(--text-muted);
}

.mkr-error,
.mkr-no-results {
    padding: 20px;
    text-align: center;
    color: var(--danger-color);
}

.mkr-notifications {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    max-width: 300px;
}

.mkr-notification {
    padding: 15px;
    margin-bottom: 10px;
    border-radius: var(--border-radius);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
    animation: mkr-fade-in 0.3s ease-out;
}

.mkr-notification-success {
    background-color: #28a745;
    color: white;
}

.mkr-notification-error {
    background-color: #dc3545;
    color: white;
}

.mkr-notification-warning {
    background-color: #ffc107;
    color: #212529;
}

.mkr-notification-info {
    background-color: #17a2b8;
    color: white;
}

.mkr-close-notification {
    background: none;
    border: none;
    color: inherit;
    font-size: 1.2rem;
    line-height: 1;
    padding: 0;
    margin-left: 10px;
    opacity: 0.7;
    cursor: pointer;
}

.mkr-close-notification:hover {
    opacity: 1;
}

.mkr-fade-out {
    animation: mkr-fade-out 0.3s ease-out forwards;
}

@keyframes mkr-fade-in {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes mkr-fade-out {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(-20px); }
}

.mkr-button-small {
    padding: 5px 10px;
    font-size: 0.8rem;
}

/* Responsive design */
@media (max-width: 992px) {
    .mkr-recipe-selection {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .mkr-planner-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .mkr-date-navigation {
        justify-content: space-between;
    }
    
    .mkr-planner-actions {
        justify-content: center;
    }
    
    .mkr-meal-time-header,
    .mkr-meal-time {
        width: auto;
        text-align: left;
    }
}

/* Print styles */
@media print {
    .mkr-login-prompt,
    .mkr-planner-actions,
    .mkr-recipe-selection,
    .mkr-add-meal,
    .mkr-remove-meal,
    .mkr-meal-link,
    .mkr-notifications {
        display: none !important;
    }
    
    .mkr-meal-planner-container {
        padding: 0;
    }
    
    .mkr-meal-planner-table {
        width: 100%;
        page-break-inside: avoid;
    }
    
    .mkr-meal-item {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    .mkr-today {
        background-color: #f8f8f8;
    }
}
</style>