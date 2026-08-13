<?php

$contacts = carbon_get_theme_option('crb_contacts');

$phone_num = carbon_get_theme_option('crb_tel_text');
$phone_link = carbon_get_theme_option('crb_tel_link');

$header_button = carbon_get_theme_option('crb_button_link');
$header_button_text = carbon_get_theme_option('crb_button_text');

?>

<div class="header-contacts">
    <?php
    if (!empty($phone_link)) {
    ?>
        <a href="<?php echo $phone_link ?>">
            <?php
            if (!empty($phone_num)) {
                echo $phone_num;
            } else {
                echo 'Позвонить нам';
            }
            ?>
        </a>
    <?php
    }

    ?>
    <ul class="contacts-list">
        <?php
        if (!empty($contacts)) {
            foreach ($contacts as $contact) {
                $contact_icon_url = wp_get_attachment_image_url($contact['crb_contact_image'], 'full');
                echo '<li class="contacts-list__item">';
                echo '<a class="contact-link" href="' . $contact['crb_contact_link'] . '"><img class="contacts-list__item__img" src="' . $contact_icon_url . '" alt="' . $contact['crb_contact_name'] . '"/></a>';
                echo '</li>';
            }
        }
        ?>
    </ul>

    <?php
    if (!empty($header_button)) {
        echo '<a class="button outline">';
        if (!empty($header_button_text)) {
            echo $header_button_text;
        } else {
            echo 'Перейти';
        }
        echo '</a>';
    }
    ?>
</div>