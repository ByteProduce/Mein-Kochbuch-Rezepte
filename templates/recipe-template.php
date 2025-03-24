<?php
/**
 * Template für die Anzeige eines einzelnen Rezepts
 *
 * @package Mein Kochbuch Rezepte
 */

get_header();
?>

<div class="mkr-recipe-page-wrapper">
    <div class="mkr-container">
        <?php
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                
                // Breadcrumb anzeigen
                if (function_exists('yoast_breadcrumb')) {
                    yoast_breadcrumb('<div class="mkr-breadcrumbs">', '</div>');
                } else {
                    // Einfache eigene Breadcrumb
                    ?>
                    <div class="mkr-breadcrumbs">
                        <a href="<?php echo esc_url(home_url('/')); ?>"><?php _e('Startseite', 'mein-kochbuch-rezepte'); ?></a> &raquo;
                        <a href="<?php echo esc_url(get_post_type_archive_link('recipe')); ?>"><?php _e('Rezepte', 'mein-kochbuch-rezepte'); ?></a> &raquo;
                        <span><?php the_title(); ?></span>
                    </div>
                    <?php
                }
                
                // Rezept mit der Frontend-Anzeige-Funktion anzeigen
                echo mkr_display_recipe(get_the_ID());
                
                // Portionsrechner doppelt anzeigen - wurde bereits in mkr_display_recipe integriert
                
                // Empfehlungen unten anzeigen
                $categories = get_the_terms(get_the_ID(), 'cuisine');
                if ($categories && !is_wp_error($categories)) {
                    $category_ids = wp_list_pluck($categories, 'term_id');
                    
                    $args = array(
                        'post_type' => 'recipe',
                        'posts_per_page' => 3,
                        'post__not_in' => array(get_the_ID()),
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'cuisine',
                                'field' => 'term_id',
                                'terms' => $category_ids,
                            ),
                        ),
                        'orderby' => 'rand',
                    );
                    
                    $related_query = new WP_Query($args);
                    
                    if ($related_query->have_posts()) :
                        ?>
                        <div class="mkr-recommendations">
                            <h2><?php _e('Das könnte Ihnen auch gefallen', 'mein-kochbuch-rezepte'); ?></h2>
                            
                            <div class="mkr-recommendations-grid">
                                <?php
                                while ($related_query->have_posts()) :
                                    $related_query->the_post();
                                    ?>
                                    <div class="mkr-recommendation-card">
                                        <a href="<?php the_permalink(); ?>" class="mkr-recommendation-link">
                                            <?php if (has_post_thumbnail()) : ?>
                                                <div class="mkr-recommendation-image">
                                                    <?php the_post_thumbnail('medium', array('class' => 'mkr-recommendation-thumbnail')); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <h3 class="mkr-recommendation-title"><?php the_title(); ?></h3>
                                            
                                            <?php
                                            // Taxonomien anzeigen
                                            $cuisine_terms = get_the_terms(get_the_ID(), 'cuisine');
                                            $difficulty_terms = get_the_terms(get_the_ID(), 'difficulty');
                                            
                                            if ($cuisine_terms || $difficulty_terms) :
                                                echo '<div class="mkr-recommendation-meta">';
                                                
                                                if ($cuisine_terms && !is_wp_error($cuisine_terms)) {
                                                    $cuisine = array_shift($cuisine_terms);
                                                    echo '<span class="mkr-cuisine-tag">' . esc_html($cuisine->name) . '</span>';
                                                }
                                                
                                                if ($difficulty_terms && !is_wp_error($difficulty_terms)) {
                                                    $difficulty = array_shift($difficulty_terms);
                                                    echo '<span class="mkr-difficulty-tag">' . esc_html($difficulty->name) . '</span>';
                                                }
                                                
                                                echo '</div>';
                                            endif;
                                            ?>
                                        </a>
                                    </div>
                                    <?php
                                endwhile;
                                ?>
                            </div>
                        </div>
                        <?php
                        wp_reset_postdata();
                    endif;
                }
                
            endwhile;
        else :
            ?>
            <div class="mkr-no-recipe">
                <h1><?php _e('Rezept nicht gefunden', 'mein-kochbuch-rezepte'); ?></h1>
                <p><?php _e('Leider konnte das gesuchte Rezept nicht gefunden werden.', 'mein-kochbuch-rezepte'); ?></p>
                <a href="<?php echo esc_url(get_post_type_archive_link('recipe')); ?>" class="mkr-button"><?php _e('Zurück zur Rezeptübersicht', 'mein-kochbuch-rezepte'); ?></a>
            </div>
            <?php
        endif;
        ?>
    </div>
</div>

<style>
/* Zusätzliche Template-spezifische Styles */
.mkr-recipe-page-wrapper {
    padding: 2rem 0;
}

.mkr-breadcrumbs {
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.mkr-breadcrumbs a {
    color: var(--primary-color);
    text-decoration: none;
}

.mkr-breadcrumbs a:hover {
    text-decoration: underline;
}

.mkr-no-recipe {
    text-align: center;
    padding: 3rem 0;
}

.mkr-recommendations {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
}

.mkr-recommendations h2 {
    margin-bottom: 1.5rem;
    text-align: center;
}

.mkr-recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.mkr-recommendation-card {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.mkr-recommendation-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow);
}

.mkr-recommendation-link {
    display: block;
    text-decoration: none;
    color: var(--text-color);
}

.mkr-recommendation-image {
    height: 180px;
    overflow: hidden;
    background-color: #f8f9fa;
}

.mkr-recommendation-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.mkr-recommendation-card:hover .mkr-recommendation-thumbnail {
    transform: scale(1.05);
}

.mkr-recommendation-title {
    padding: 0.75rem;
    margin: 0;
    font-size: 1.1rem;
    text-align: center;
}

.mkr-recommendation-meta {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    padding: 0 0.75rem 0.75rem;
}

.mkr-cuisine-tag,
.mkr-difficulty-tag {
    font-size: 0.8rem;
    padding: 0.2rem 0.5rem;
    border-radius: 3px;
    color: var(--text-light);
}

.mkr-cuisine-tag {
    background-color: var(--primary-color);
}

.mkr-difficulty-tag {
    background-color: var(--secondary-color);
}

@media (max-width: 768px) {
    .mkr-recommendations-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
get_footer();