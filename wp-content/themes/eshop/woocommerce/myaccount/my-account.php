<?php
defined('ABSPATH') || exit;

do_action('woocommerce_before_account_navigation');
?>

<section class="page-content">
    <div class="fixed-container">
        <?php do_action('woocommerce_account_navigation'); ?>

        <div class="woocommerce-MyAccount-content">
            <?php
            /**
             * Контент текущего раздела:
             *
             * /my-account/form-edit-account
             * /my-account/favorites
             * /my-account/orders
             */
            do_action('woocommerce_account_content');
            ?>
        </div>


    </div>
</section>

<aside class="account-page__sidebar">

</aside>

<main class="account-page__content">


</main>

</div>

<?php
do_action('woocommerce_after_account_navigation');
