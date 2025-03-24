<?php
get_header();
if (have_posts()) : while (have_posts()) : the_post();
    echo '<h1>' . get_the_title() . '</h1>';
    echo '<p>' . get_the_content() . '</p>';
endwhile; endif;
get_footer();