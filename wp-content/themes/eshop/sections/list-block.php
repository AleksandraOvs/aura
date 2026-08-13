<?php
$list_title = get_field('list_title');
$list_items = get_field('list_items');
?>

<?php if ($list_items): ?>
    <section class="about-us white-content">
        <div class="fixed-container">

            <?php if ($list_title): ?>
                <h2 data-scroll-animation="scale">
                    <?= wp_kses_post($list_title); ?>
                </h2>
            <?php endif; ?>

            <ul class="about-us__list">
                <?php $delay = 0; ?>
                <?php foreach ($list_items as $item): ?>
                    <?php
                    $item_text = $item['list_items_item'] ?? '';
                    ?>
                    <?php if ($item_text): ?>
                        <li
                            class="about-us__list__item"
                            data-scroll-animation="fade"
                            style="--animation-delay: <?= $delay; ?>s;">
                            <p>
                                <?= wp_kses_post($item_text); ?>
                            </p>
                        </li>
                        <?php $delay += 0.2; ?>
                    <?php endif; ?>

                <?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>