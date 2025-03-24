<?php
get_header();
?>
<div class="mkr-recipe-filters">
    <form method="get" action="<?php echo esc_url(get_post_type_archive_link('recipe')); ?>">
        <div class="filter-group">
            <label for="difficulty"><?php _e('Schwierigkeitsgrad', 'mein-kochbuch-rezepte'); ?></label>
            <select name="difficulty" id="difficulty">
                <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                <?php
                $difficulties = get_terms(['taxonomy' => 'difficulty', 'hide_empty' => false]);
                foreach ($difficulties as $difficulty) {
                    echo '<option value="' . esc_attr($difficulty->slug) . '" ' . selected($_GET['difficulty'] ?? '', $difficulty->slug, false) . '>' . esc_html($difficulty->name) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="filter местоположение">
            <label for="diet"><?php _e('Diätvorgaben', 'mein-kochbuch-rezepte'); ?></label>
            <select name="diet" id="diet">
                <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                <?php
                $diets = get_terms(['taxonomy' => 'diet', 'hide_empty' => false]);
                foreach ($diets as $diet) {
                    echo '<option value="' . esc_attr($diet->slug) . '" ' . selected($_GET['diet'] ?? '', $diet->slug, false) . '>' . esc_html($diet->name) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="cuisine"><?php _e('Küchenart', 'mein-kochbuch-rezepte'); ?></label>
            <select name="cuisine" id="cuisine">
                <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                <?php
                $cuisines = get_terms(['taxonomy' => 'cuisine', 'hide_empty' => false]);
                foreach ($cuisines as $cuisine) {
                    echo '<option value="' . esc_attr($cuisine->slug) . '" ' . selected($_GET['cuisine'] ?? '', $cuisine->slug, false) . '>' . esc_html($cuisine->name) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="season"><?php _e('Saison', 'mein-kochbuch-rezepte'); ?></label>
            <select name="season" id="season">
                <option value=""><?php _e('Alle', 'mein-kochbuch-rezepte'); ?></option>
                <?php
                $seasons = get_terms(['taxonomy' => 'season', 'hide_empty' => false]);
                foreach ($seasons as $season) {
                    echo '<option value="' . esc_attr($season->slug) . '" ' . selected($_GET['season'] ?? '', $season->slug, false) . '>' . esc_html($season->name) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="prep_time"><?php _e('Maximale Zubereitungszeit (Minuten)', 'mein-kochbuch-rezepte'); ?></label>
            <input type="number" name="prep_time" id="prep_time" value="<?php echo esc_attr($_GET['prep_time'] ?? ''); ?>" min="0" />
        </div>
        <div class="filter-group">
            <label for="servings"><?php _e('Portionen', 'mein-kochbuch-rezepte'); ?></label>
            <input type="number" name="servings" id="servings" value="<?php echo esc_attr($_GET['servings'] ?? ''); ?>" min="1" />
        </div>
        <button type="submit" class="button button-primary"><?php _e('Filtern', 'mein-kochbuch-rezepte'); ?></button>
    </form>
</div>
<?php
if (have_posts()) :
    while (have_posts()) : the_post();
        echo '<div class="recipe-card">';
        echo '<h2><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
        echo '<div class="recipe-excerpt">' . get_the_excerpt() . '</div>';
        echo '</div>';
    endwhile;
else :
    echo '<p>' . __('Keine Rezepte gefunden.', 'mein-kochbuch-rezepte') . '</p>';
endif;
get_footer();