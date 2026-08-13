<?php get_header() ?>
<section class="page">
    <?php get_template_part('template-parts/page-header'); ?>
    <div class="page-content">
        <div class="container">

            <div class="page-content__header">
                <h1 class="post-title"><?php the_title(); ?></h1>
                <div class="page-content__header__bottom">
                    <div class="single-description">
                        <div class="sd-date"><?php
                                                $published = get_the_date('d.m.Y');
                                                $updated   = get_the_modified_date('d.m.Y');

                                                echo $published;

                                                if ($updated !== $published) {
                                                    echo ' (обновлено: ' . $updated . ')';
                                                }
                                                ?></div>
                        <div class="sd-views">
                            <?php set_post_views(get_the_ID()); ?>
                            <?php
                            $views_icon = '<svg width="16" height="11" viewBox="0 0 16 11" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M7.79167 3.1875C7.22808 3.1875 6.68758 3.41138 6.28906 3.8099C5.89055 4.20841 5.66667 4.74891 5.66667 5.3125C5.66667 5.87608 5.89055 6.41659 6.28906 6.8151C6.68758 7.21362 7.22808 7.4375 7.79167 7.4375C8.35525 7.4375 8.89575 7.21362 9.29427 6.8151C9.69278 6.41659 9.91667 5.87608 9.91667 5.3125C9.91667 4.74891 9.69278 4.20841 9.29427 3.8099C8.89575 3.41138 8.35525 3.1875 7.79167 3.1875ZM7.79167 8.85417C6.85236 8.85417 5.95152 8.48103 5.28733 7.81684C4.62314 7.15265 4.25 6.25181 4.25 5.3125C4.25 4.37319 4.62314 3.47235 5.28733 2.80816C5.95152 2.14397 6.85236 1.77083 7.79167 1.77083C8.73098 1.77083 9.63181 2.14397 10.296 2.80816C10.9602 3.47235 11.3333 4.37319 11.3333 5.3125C11.3333 6.25181 10.9602 7.15265 10.296 7.81684C9.63181 8.48103 8.73098 8.85417 7.79167 8.85417ZM7.79167 0C4.25 0 1.22542 2.20292 0 5.3125C1.22542 8.42208 4.25 10.625 7.79167 10.625C11.3333 10.625 14.3579 8.42208 15.5833 5.3125C14.3579 2.20292 11.3333 0 7.79167 0Z" fill="#CCCCCC"/>
</svg>';

                            echo $views_icon . get_post_views(get_the_ID()); ?>
                        </div>
                    </div>



                </div>

            </div>

            <?php the_content(); ?>
        </div>


    </div>
</section>


<?php get_footer() ?>