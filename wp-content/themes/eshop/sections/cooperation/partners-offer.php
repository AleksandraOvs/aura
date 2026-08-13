<?php
$offer_title = get_field('offer_title');
$offers_list = get_field('offers_list');

$delay = 0.2;
?>

<?php if ($offer_title || $offers_list): ?>
    <section class="partners-offer white-content">
        <div class="fixed-container">

            <?php if ($offer_title): ?>
                <h2 class="small-heading">
                    <?= wp_kses_post($offer_title); ?>
                </h2>
            <?php endif; ?>

            <?php if ($offers_list): ?>
                <ul class="partners-offer__list">

                    <?php foreach ($offers_list as $index => $offer): ?>

                        <?php
                        // Нечётные элементы: fade-left
                        // Чётные элементы: fade-right
                        $animation = ($index % 2 === 0)
                            ? 'fade-left'
                            : 'fade-right';
                        ?>

                        <li
                            class="partners-offer__list__item">
                            <div class="partners-offer__list__item__inner" data-scroll-animation="<?= esc_attr($animation); ?>"
                                style="--animation-delay: <?= esc_attr($delay); ?>s;">

                                <?php if (!empty($offer['offers_list_title'])): ?>
                                    <div class="offer-title">
                                        <?= wp_kses_post($offer['offers_list_title']); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($offer['offers_list_text'])): ?>
                                    <div class="offer-text">
                                        <?= wp_kses_post($offer['offers_list_text']); ?>
                                    </div>
                                <?php endif; ?>

                            </div>

                            <span class="list-marker"></span>
                        </li>

                        <?php $delay += 0.2; ?>

                    <?php endforeach; ?>

                </ul>
            <?php endif; ?>

        </div>
    </section>
<?php endif; ?>