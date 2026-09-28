<?php

/**
 * AJAX фильтр проектов по тегу
 */
add_action('wp_ajax_filter_projects', 'filter_projects_ajax');
add_action('wp_ajax_nopriv_filter_projects', 'filter_projects_ajax');

function filter_projects_ajax()
{
    check_ajax_referer('filter_projects', 'nonce');

    $tag_id = isset($_POST['tag_id'])
        ? absint($_POST['tag_id'])
        : 0;

    $args = [
        'post_type'      => 'projects',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ];

    if ($tag_id) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'project_tag',
                'field'    => 'term_id',
                'terms'    => $tag_id,
            ],
        ];
    }

    $projects = new WP_Query($args);

    ob_start();

    if ($projects->have_posts()) {

        $animation_delay = 0.1;

        while ($projects->have_posts()) {
            $projects->the_post();

            get_template_part(
                'sections/projects/project-item',
                null,
                [
                    'animation_delay' => $animation_delay,
                ]
            );

            $animation_delay += 0.1;

            if ($animation_delay > 1) {
                $animation_delay = 0.1;
            }
        }
    } else {
?>
        <div class="projects-empty">
            <p>Проектов с таким тегом пока нет.</p>
        </div>
<?php
    }

    wp_reset_postdata();

    wp_send_json_success([
        'html' => ob_get_clean(),
    ]);
}
