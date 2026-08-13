<?php
$tel_text = carbon_get_theme_option('crb_tel_text');
$tel_link = carbon_get_theme_option('crb_tel_link');
$tel_img = carbon_get_theme_option('crb_tel_img');

$email_text = carbon_get_theme_option('crb_email_text');
$email_link = carbon_get_theme_option('crb_email_link');
$email_img = carbon_get_theme_option('crb_email_img');

$phone_ico = '<svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M3.05882 0H9.17647L12.2353 7.64706L8.41177 9.94118C10.0497 13.2623 12.7377 15.9503 16.0588 17.5882L18.3529 13.7647L26 16.8235V22.9412C26 23.7524 25.6777 24.5305 25.1041 25.1041C24.5305 25.6777 23.7524 26 22.9412 26C16.9753 25.6375 11.3485 23.1041 7.12221 18.8778C2.89595 14.6515 0.362546 9.02465 0 3.05882C0 2.24757 0.322268 1.46955 0.895909 0.895909C1.46955 0.322268 2.24757 0 3.05882 0Z" fill="#FFC21A"/>
</svg>
';
$email_ico = '<svg width="29" height="24" viewBox="0 0 29 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M1 4.14286C1 3.30932 1.31607 2.50992 1.87868 1.92052C2.44129 1.33112 3.20435 1 4 1H25C25.7957 1 26.5587 1.33112 27.1213 1.92052C27.6839 2.50992 28 3.30932 28 4.14286V19.8571C28 20.6907 27.6839 21.4901 27.1213 22.0795C26.5587 22.6689 25.7957 23 25 23H4C3.20435 23 2.44129 22.6689 1.87868 22.0795C1.31607 21.4901 1 20.6907 1 19.8571V4.14286Z" fill="#FFC21A"/>
<path d="M1 4.14286L14.5 13.5714L28 4.14286" fill="#FFC21A"/>
<path d="M1 4.14286C1 3.30932 1.31607 2.50992 1.87868 1.92052C2.44129 1.33112 3.20435 1 4 1H25C25.7957 1 26.5587 1.33112 27.1213 1.92052C27.6839 2.50992 28 3.30932 28 4.14286M1 4.14286V19.8571C1 20.6907 1.31607 21.4901 1.87868 22.0795C2.44129 22.6689 3.20435 23 4 23H25C25.7957 23 26.5587 22.6689 27.1213 22.0795C27.6839 21.4901 28 20.6907 28 19.8571V4.14286M1 4.14286L14.5 13.5714L28 4.14286" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';
?>


<div class="contacts__list">
    <h3>Получить консультацию</h3>

    <?php
    $messengers = carbon_get_theme_option('crb_contacts');
    if (!empty($messengers)) :
    ?>
        <div class="contacts-list__messengers">


            <?php

            foreach ($messengers as $messenger) :
                // Получаем ссылку и изображение для каждого мессенджера
                $mes_link = isset($messenger['crb_contact_link']) ? $messenger['crb_contact_link'] : '';
                $mes_img_id = isset($messenger['crb_contact_image']) ? $messenger['crb_contact_image'] : '';
                $mes_img_url = $mes_img_id ? wp_get_attachment_image_url($mes_img_id, 'full') : '';
                $mes_img_alt_id = isset($messenger['crb_contact_image_alt']) ? $messenger['crb_contact_image_alt'] : '';

                $mes_img_alt_url = $mes_img_alt_id ? wp_get_attachment_image_url($mes_img_alt_id, 'full') : '';
                $mes_text = isset($messenger['crb_contact_name']) ? $messenger['crb_contact_name'] : '';
            ?>


                <?php if ($mes_link) : ?>

                    <a class="item__link" href="<?php echo esc_url($mes_link); ?>">

                        <?php if ($mes_img_alt_url) { ?>
                            <img src="<?php echo esc_url($mes_img_alt_url); ?>" alt="<?php echo esc_html($mes_text); ?>">
                        <?php } elseif ($mes_img_url) { ?>
                            <img src="<?php echo esc_url($mes_img_url); ?>" alt="<?php echo esc_html($mes_text); ?>">
                        <?php
                        } ?>
                    </a>
                <?php endif; ?>

            <?php
            endforeach;
            ?>
        </div>

    <?php
    endif;
    ?>

    <?php if (!empty($tel_link)) :
        $tel_img_url = wp_get_attachment_image_url($tel_img, 'full');
    ?>


        <a href="<?php echo esc_url($tel_link); ?>" class="phone__link">
            <div class="contact-icon">
                <?php echo $phone_ico ?>
            </div>
            <span><?php echo esc_html($tel_text);
                    ?></span>

        </a>

    <?php endif; ?>
    <?php if (!empty($email_link)) :
        $email_img_url = wp_get_attachment_image_url($email_img, 'full');
    ?>


        <a href="mailto:<?php echo esc_attr($email_link); ?>" class="email__link">
            <div class="contact-icon">
                <?php echo $email_ico ?>
            </div>
            <span><?php echo esc_html($email_text);
                    ?></span>

        </a>

    <?php endif; ?>
</div>