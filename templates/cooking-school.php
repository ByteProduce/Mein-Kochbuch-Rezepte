<?php
get_header();
?>
<div class="wrap">
    <h1><?php _e('Interaktive Kochschule', 'mein-kochbuch-rezepte'); ?></h1>
    <div id="mkr-live-stream">
        <p><?php _e('Hier könnten Live-Kochkurse stattfinden. Integration mit einem Streaming-Dienst erforderlich.', 'mein-kochbuch-rezepte'); ?></p>
    </div>
    <?php
    $recipes = get_posts(['post_type' => 'recipe', 'posts_per_page' => 5]);
    foreach ($recipes as $recipe) {
        $videos = get_post_meta($recipe->ID, '_mkr_videos', true);
        if ($videos && is_array($videos)) {
            echo '<h2>' . esc_html($recipe->post_title) . '</h2>';
            foreach ($videos as $video) {
                if (preg_match('/youtube\.com|youtu\.be/', $video)) {
                    $video_id = preg_match('/(?:v=|\.be\/)([a-zA-Z0-9_-]{11})/', $video, $matches) ? $matches[1] : '';
                    echo '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></iframe>';
                }
            }
        }
    }
    ?>
</div>
<?php
get_footer();