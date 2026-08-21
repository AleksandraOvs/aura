<?php
if (! defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_before_customer_login_form');
?>

<div class="auth-tabs" id="customer_login">

    <div class="auth-tabs__nav">
        <div class="auth-tabs__title is-active" data-tab="login">
            <?php esc_html_e('Вход', 'woocommerce'); ?>
        </div>

        <?php if ('yes' === get_option('woocommerce_enable_myaccount_registration')) : ?>
            <div class="auth-tabs__title" data-tab="register">
                <?php esc_html_e('Регистрация', 'woocommerce'); ?>
            </div>
        <?php endif; ?>
    </div>


    <div class="auth-tabs__content">

        <!-- LOGIN -->
        <div class="auth-tabs__panel is-active" data-panel="login">

            <form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>

                <?php do_action('woocommerce_login_form_start'); ?>

                <p class="woocommerce-form-row form-row form-row-wide">
                    <input type="text"
                        class="woocommerce-Input input-text"
                        name="username"
                        id="username"
                        autocomplete="username"
                        placeholder="<?php esc_attr_e('Username or email address', 'woocommerce'); ?>"
                        value="<?php echo (! empty($_POST['username']) && is_string($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>"
                        required />
                </p>

                <p class="woocommerce-form-row form-row form-row-wide">
                    <input class="woocommerce-Input input-text"
                        type="password"
                        name="password"
                        id="password"
                        autocomplete="current-password"
                        placeholder="<?php esc_attr_e('Password', 'woocommerce'); ?>"
                        required />
                </p>

                <?php do_action('woocommerce_login_form'); ?>

                <p class="form-row">

                    <label class="woocommerce-form__label woocommerce-form-login__rememberme">
                        <input class="woocommerce-form__input-checkbox"
                            name="rememberme"
                            type="checkbox"
                            value="forever" />
                        <span><?php esc_html_e('Remember me', 'woocommerce'); ?></span>
                    </label>

                    <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>

                    <button type="submit"
                        class="woocommerce-button button woocommerce-form-login__submit"
                        name="login">
                        <?php esc_html_e('Log in', 'woocommerce'); ?>
                    </button>

                </p>

                <p class="woocommerce-LostPassword lost_password">
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">
                        <?php esc_html_e('Lost your password?', 'woocommerce'); ?>
                    </a>
                </p>

                <?php do_action('woocommerce_login_form_end'); ?>

            </form>

        </div>


        <?php if ('yes' === get_option('woocommerce_enable_myaccount_registration')) : ?>

            <!-- REGISTER -->
            <div class="auth-tabs__panel" data-panel="register">

                <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action('woocommerce_register_form_tag'); ?>>

                    <?php do_action('woocommerce_register_form_start'); ?>

                    <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>

                        <p class="woocommerce-form-row form-row form-row-wide">
                            <input type="text"
                                class="woocommerce-Input input-text"
                                name="username"
                                id="reg_username"
                                autocomplete="username"
                                placeholder="<?php esc_attr_e('Username', 'woocommerce'); ?>"
                                value="<?php echo (! empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>"
                                required />
                        </p>

                    <?php endif; ?>

                    <p class="woocommerce-form-row form-row form-row-wide">
                        <input type="email"
                            class="woocommerce-Input input-text"
                            name="email"
                            id="reg_email"
                            autocomplete="email"
                            placeholder="<?php esc_attr_e('Email address', 'woocommerce'); ?>"
                            value="<?php echo (! empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>"
                            required />
                    </p>

                    <?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>

                        <p class="woocommerce-form-row form-row form-row-wide">
                            <input type="password"
                                class="woocommerce-Input input-text"
                                name="password"
                                id="reg_password"
                                autocomplete="new-password"
                                placeholder="<?php esc_attr_e('Password', 'woocommerce'); ?>"
                                required />
                        </p>

                    <?php else : ?>

                        <p>
                            <?php esc_html_e('A link to set a new password will be sent to your email address.', 'woocommerce'); ?>
                        </p>

                    <?php endif; ?>

                    <?php do_action('woocommerce_register_form'); ?>

                    <p class="form-row">

                        <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>

                        <button type="submit"
                            class="woocommerce-button button woocommerce-form-register__submit"
                            name="register">
                            <?php esc_html_e('Register', 'woocommerce'); ?>
                        </button>

                    </p>

                    <?php do_action('woocommerce_register_form_end'); ?>

                </form>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php do_action('woocommerce_after_customer_login_form'); ?>