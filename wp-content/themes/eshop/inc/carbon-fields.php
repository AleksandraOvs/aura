<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', 'site_carbon');
function site_carbon()
{
    Container::make('theme_options', 'Контакты')

        ->set_page_menu_position(2)
        ->set_icon('dashicons-megaphone')
        ->add_tab(__('Контакты'), array(

            Field::make('text', 'crb_tel_text', 'Номер телефона')
                ->set_width(33),
            Field::make('image', 'crb_tel_img', 'Номер телефона')
                ->set_width(33),
            Field::make('text', 'crb_tel_link', 'Ссылка телефона')
                ->set_width(33),

            Field::make('text', 'crb_email_text', 'Эл.почта')
                ->set_width(33),
            Field::make('image', 'crb_email_img', 'Номер телефона')
                ->set_width(33),
            Field::make('text', 'crb_email_link', 'Ссылка телефона')
                ->set_width(33),

            Field::make('complex', 'crb_contacts', 'Мессенджеры')

                ->add_fields(array(
                    Field::make('image', 'crb_contact_image', 'Иконка')
                        ->set_width(25),
                    Field::make('image', 'crb_contact_image_alt', 'Доп. Иконка')
                        ->set_width(25),
                    Field::make('text', 'crb_contact_name', 'Название')
                        ->set_width(25),
                    Field::make('text', 'crb_contact_link', 'Ссылка')
                        ->set_width(25),
                )),

            Field::make('text', 'crb_button_text', 'Кнопка')
                ->set_width(50),
            Field::make('text', 'crb_button_link', 'Ссылка кнопки')
                ->set_width(50)
                ->help_text('для вызова попап окна, необходимо поставить ссылку #main-form'),
        ));

    //prefooter
    Container::make('theme_options', 'Настройки темы')
        ->add_tab(__('Общие'), array(
            Field::make('association', 'footer_page', 'Выбор шаблона для префутера')
                ->set_types([
                    [
                        'type' => 'post',
                        'post_type' => 'page',
                    ]
                ])
                ->help_text('Выбор из имеющихся блоков (в разделе "Страницы") для вывода префутера - отображается на всех страницах сайта, кросе главной и страницы оформления заказа перед футером')
        ))

        ->add_tab(__('Товары'), array(
            Field::make('rich_text', 'delivery_info', 'Текст с информацией о доставке')

                ->help_text('Этот текст выводится во вкладке Доставка на карточке товара')
        ));
}
