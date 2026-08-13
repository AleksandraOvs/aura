<?php
$our_partners_title = get_field('our_partners_title');
$our_partners_list  = get_field('our_partners_list');
?>

<?php if ($our_partners_title || $our_partners_list): ?>
    <section class="our-partners">
        <div class="container">
            <div class="our-partners__inner">

                <div class="our-partners__inner__left white-content">
                    <?php if ($our_partners_title): ?>
                        <h2>
                            <?= wp_kses_post($our_partners_title); ?>
                        </h2>
                    <?php endif; ?>
                </div>

                <?php if ($our_partners_list): ?>
                    <div class="our-partners__inner__right">
                        <ul class="our-partners__list">

                            <?php foreach ($our_partners_list as $item): ?>
                                <?php if (!empty($item['our_partners_list_item'])): ?>
                                    <li>
                                        <?= wp_kses_post($item['our_partners_list_item']); ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>

                        </ul>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </section>
<?php endif; ?>