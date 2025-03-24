<?php
get_header();
if (have_posts()) : while (have_posts()) : the_post();
    echo '<h1>' . get_the_title() . '</h1>';
    echo '<p>' . get_the_content() . '</p>';

    $calories = get_post_meta(get_the_ID(), '_mkr_calories_per_100g', true);
    if ($calories) {
        echo '<p><strong>Kalorien pro 100 g:</strong> ' . esc_html($calories) . ' kcal</p>';
    }

    $weight_per_cup = get_post_meta(get_the_ID(), '_mkr_weight_per_cup', true);
    if ($weight_per_cup) {
        echo '<p><strong>Gewicht pro Cup:</strong> ' . esc_html($weight_per_cup) . ' g</p>';
    }

    $related_recipes = get_posts(array(
        'post_type' => 'recipe',
        'meta_query' => array(
            array(
                'key' => '_mkr_ingredients',
                'value' => get_the_title(),
                'compare' => 'LIKE'
            )
        )
    ));
    if ($related_recipes) {
        echo '<h2>Verwandte Rezepte</h2>';
        echo '<ul>';
        foreach ($related_recipes as $recipe) {
            echo '<li><a href="' . get_permalink($recipe->ID) . '">' . esc_html($recipe->post_title) . '</a></li>';
        }
        echo '</ul>';
    }
endwhile; endif;
get_footer();