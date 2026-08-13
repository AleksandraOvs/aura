<section class="partners-form">

    <img src=" <?php echo get_stylesheet_directory_uri() . '/imgs/lights-bg.webp' ?>" alt="" />
    <div class="fixed-container">
        <form class="wpcf7-form">
            <h2 class="small-heading">Стать партнером</h2>
            <p>
                <span class="wpcf7-form-control-wrap" data-name="your-name">
                    <input
                        type="text"
                        name="your-name"
                        class="wpcf7-form-control wpcf7-text"
                        placeholder="Имя*" />
                </span>
            </p>

            <p>
                <span class="wpcf7-form-control-wrap" data-name="your-phone">
                    <input
                        type="tel"
                        name="your-phone"
                        class="wpcf7-form-control wpcf7-tel"
                        placeholder="Телефон*" />
                </span>
            </p>

            <p>
                <span class="wpcf7-form-control-wrap" data-name="your-phone">
                    <input
                        type="tel"
                        name="your-phone"
                        class="wpcf7-form-control wpcf7-tel"
                        placeholder="E-mail*" />
                </span>
            </p>

            <p>
                <span class="wpcf7-form-control-wrap" data-name="your-phone">
                    <textarea
                        type="textarea"
                        name="your-phone"
                        class="wpcf7-form-control wpcf7-tel"
                        placeholder="Комментарий"></textarea>
                </span>
            </p>

            <div class="form-description">*обязательное заполнение</div>

            <p class="contacts-form__agreement">
                <span class="wpcf7-form-control-wrap" data-name="acceptance">
                    <span class="wpcf7-form-control wpcf7-acceptance">
                        <span class="wpcf7-list-item first last">
                            <label>
                                <input type="checkbox" name="acceptance" value="1" />
                                <span class="wpcf7-list-item-label">
                                    Я выражаю согласие на передачу и обработку
                                    персональных данных, в соответствии с
                                    <a href="/privacy-policy/">
                                        Политикой конфиденциальности
                                    </a>
                                    .
                                </span>
                            </label>
                        </span>
                    </span>
                </span>
            </p>

            <p class="contacts-form__submit">
                <input
                    type="submit"
                    value="Отправить"
                    class="wpcf7-form-control wpcf7-submit button button-white" />
            </p>
        </form>
    </div>
</section>