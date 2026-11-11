<?php

if (!defined('ABSPATH')) {
    exit;
}

class PWQB_Shortcode
{
    public static function init(): void
    {
        add_shortcode('pw_quiz', [self::class, 'render']);
    }

    /**
     * Подключение ресурсов фронтенда.
     */
    private static function enqueue_assets(): void
    {
        wp_enqueue_style(
            'pwqb-quiz',
            PWQB_URL . 'public/css/quiz.css',
            [],
            PWQB_VERSION
        );

        wp_enqueue_script(
            'pwqb-quiz',
            PWQB_URL . 'public/js/quiz.js',
            [],
            PWQB_VERSION,
            true
        );

        wp_localize_script(
            'pwqb-quiz',
            'PWQBQuiz',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
            ]
        );
    }

    /**
     * Получение изображения.
     */
    private static function render_image(int $image_id, string $class = ''): string
    {
        if (!$image_id || !wp_attachment_is_image($image_id)) {
            return '';
        }

        return (string) wp_get_attachment_image(
            $image_id,
            'medium',
            false,
            [
                'class'   => $class,
                'loading' => 'lazy',
                'alt'     => '',
            ]
        );
    }

    /**
     * Вывод квиза.
     */
    public static function render($atts): string
    {
        $atts = shortcode_atts(
            ['id' => 0],
            $atts,
            'pw_quiz'
        );

        $quiz_id = absint($atts['id']);

        if (!$quiz_id) {
            return '';
        }

        $quiz = get_post($quiz_id);

        if (
            !$quiz ||
            $quiz->post_type !== PWQB_Quiz::POST_TYPE ||
            $quiz->post_status !== 'publish'
        ) {
            return '';
        }

        $settings = get_post_meta(
            $quiz_id,
            PWQB_Quiz::META_SETTINGS,
            true
        );

        if (!is_array($settings)) {
            return '';
        }

        $questions = $settings['questions'] ?? [];

        if (!is_array($questions) || empty($questions)) {
            return '';
        }

        self::enqueue_assets();

        $description = $settings['description']
            ?? get_post_meta(
                $quiz_id,
                PWQB_Quiz::META_DESCRIPTION,
                true
            );

        $result_mode = ($settings['result_mode'] ?? 'open') === 'closed'
            ? 'closed'
            : 'open';


        ob_start();
?>

        <div
            class="pwqb-quiz"
            data-quiz-id="<?php echo esc_attr($quiz_id); ?>">

            <div class="pwqb-quiz__intro">
                <h2 class="pwqb-quiz__title">
                    <?php echo esc_html(get_the_title($quiz_id)); ?>
                </h2>

                <?php if ($description !== '') : ?>
                    <div class="pwqb-quiz__description">
                        <?php echo wpautop(esc_html($description)); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pwqb-quiz__progress" hidden>
                <div class="pwqb-quiz__progress-info">
                    <span data-pwqb-progress-label></span>
                    <span data-pwqb-progress-percent></span>
                </div>

                <div
                    class="pwqb-quiz__progress-track"
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="0"
                    data-pwqb-progress-bar>
                    <div class="pwqb-quiz__progress-fill"></div>
                </div>
            </div>

            <div class="pwqb-quiz__questions" data-pwqb-frontend-questions>

                <?php foreach ($questions as $question_index => $question) : ?>

                    <?php
                    if (!is_array($question)) {
                        continue;
                    }

                    $question_id = sanitize_key($question['id'] ?? '');

                    if ($question_id === '') {
                        continue;
                    }

                    $answers = $question['answers'] ?? [];

                    if (!is_array($answers)) {
                        $answers = [];
                    }

                    $question_type = $question['type'] ?? 'single';
                    $is_multiple = $question_type === 'multiple';
                    ?>

                    <section
                        class="pwqb-quiz__question"
                        data-pwqb-frontend-question
                        data-question-id="<?php echo esc_attr($question_id); ?>"
                        data-question-index="<?php echo esc_attr($question_index); ?>"
                        data-question-type="<?php echo $is_multiple ? 'multiple' : 'single'; ?>"
                        <?php echo $question_index > 0 ? 'hidden' : ''; ?>>

                        <div class="pwqb-quiz__question-heading">

                            <span class="pwqb-quiz__question-number">
                                Вопрос <?php echo esc_html($question_index + 1); ?>
                            </span>

                            <h3 class="pwqb-quiz__question-title">
                                <?php echo esc_html($question['title'] ?? ''); ?>
                            </h3>

                            <?php if (!empty($question['description'])) : ?>
                                <div class="pwqb-quiz__question-description">
                                    <?php echo wpautop(esc_html($question['description'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            echo self::render_image(
                                absint($question['image_id'] ?? 0),
                                'pwqb-quiz__question-image'
                            );
                            ?>

                        </div>

                        <div class="pwqb-quiz__answers">

                            <?php foreach ($answers as $answer) : ?>

                                <?php
                                if (!is_array($answer)) {
                                    continue;
                                }

                                $answer_id = sanitize_key($answer['id'] ?? '');

                                if ($answer_id === '') {
                                    continue;
                                }

                                $next_question = sanitize_key(
                                    $answer['next_question'] ?? ''
                                );
                                ?>

                                <label
                                    class="pwqb-quiz__answer"
                                    data-pwqb-frontend-answer
                                    data-answer-id="<?php echo esc_attr($answer_id); ?>"
                                    data-next-question="<?php echo esc_attr($next_question); ?>">

                                    <input
                                        type="<?php echo $is_multiple ? 'checkbox' : 'radio'; ?>"
                                        name="pwqb_answer_<?php echo esc_attr($quiz_id . '_' . $question_id); ?>"
                                        value="<?php echo esc_attr($answer_id); ?>"
                                        data-pwqb-answer-input>

                                    <span class="pwqb-quiz__answer-content">

                                        <?php
                                        echo self::render_image(
                                            absint($answer['image_id'] ?? 0),
                                            'pwqb-quiz__answer-image'
                                        );
                                        ?>

                                        <span class="pwqb-quiz__answer-title">
                                            <?php echo esc_html($answer['title'] ?? ''); ?>
                                        </span>

                                    </span>

                                </label>

                            <?php endforeach; ?>

                        </div>

                        <?php if ($is_multiple) : ?>
                            <p class="pwqb-quiz__hint">
                                Можно выбрать несколько вариантов.
                            </p>
                        <?php endif; ?>

                    </section>

                <?php endforeach; ?>

            </div>

            <div class="pwqb-quiz__navigation" data-pwqb-navigation hidden>

                <button
                    type="button"
                    class="pwqb-quiz__button pwqb-quiz__button--back"
                    data-pwqb-back
                    hidden>
                    Назад
                </button>

                <button
                    type="button"
                    class="pwqb-quiz__button pwqb-quiz__button--next"
                    data-pwqb-next
                    disabled>
                    Далее
                </button>

            </div>


            <div class="pwqb-quiz__result" data-pwqb-result hidden>

                <?php if ($result_mode === 'closed') : ?>

                    <div class="pwqb-quiz__closed-result">

                        <h3 class="pwqb-quiz__result-title">
                            Подборка почти готова!
                        </h3>

                        <p class="pwqb-quiz__result-description">
                            Оставьте контактные данные, и мы подготовим для вас подборку.
                        </p>

                        <form
                            class="pwqb-quiz__submission-form"
                            data-pwqb-submission-form>

                            <?php wp_nonce_field('pwqb_submit_quiz', 'pwqb_submission_nonce'); ?>

                            <?php
                            $quiz_started_at = time();

                            $quiz_start_token = hash_hmac(
                                'sha256',
                                $quiz_id . '|' . $quiz_started_at,
                                wp_salt('auth')
                            );
                            ?>

                            <input
                                type="hidden"
                                name="pwqb_started_at"
                                value="<?php echo esc_attr($quiz_started_at); ?>">

                            <input
                                type="hidden"
                                name="pwqb_start_token"
                                value="<?php echo esc_attr($quiz_start_token); ?>">

                            <div class="pwqb-quiz__honeypot" aria-hidden="true">
                                <label for="pwqb-website-<?php echo esc_attr($quiz_id); ?>">
                                    Не заполняйте это поле
                                </label>
                                <input
                                    type="text"
                                    id="pwqb-website-<?php echo esc_attr($quiz_id); ?>"
                                    name="pwqb_website"
                                    value=""
                                    tabindex="-1"
                                    autocomplete="off">
                            </div>

                            <div class="pwqb-quiz__form-field">
                                <label for="pwqb-name-<?php echo esc_attr($quiz_id); ?>">
                                    Ваше имя *
                                </label>
                                <input
                                    type="text"
                                    id="pwqb-name-<?php echo esc_attr($quiz_id); ?>"
                                    name="name"
                                    required
                                    maxlength="190">
                            </div>

                            <div class="pwqb-quiz__form-field">
                                <label for="pwqb-email-<?php echo esc_attr($quiz_id); ?>">
                                    Email *
                                </label>
                                <input
                                    type="email"
                                    id="pwqb-email-<?php echo esc_attr($quiz_id); ?>"
                                    name="email"
                                    required
                                    maxlength="190">
                            </div>

                            <div class="pwqb-quiz__form-field">
                                <label for="pwqb-send-to-<?php echo esc_attr($quiz_id); ?>">
                                    Куда отправить подборку?
                                </label>
                                <select
                                    id="pwqb-send-to-<?php echo esc_attr($quiz_id); ?>"
                                    name="send_to"
                                    required
                                    data-pwqb-send-to>
                                    <option value="email">На email</option>
                                    <option value="messenger">В мессенджер</option>
                                    <option value="both">На email и в мессенджер</option>
                                </select>
                            </div>

                            <div class="pwqb-quiz__messenger-fields" data-pwqb-messenger-fields hidden>

                                <fieldset class="pwqb-quiz__form-field" data-pwqb-messengers>
                                    <legend>Какими мессенджерами пользуетесь? *</legend>

                                    <label>
                                        <input type="checkbox" name="messengers[]" value="telegram">
                                        Telegram
                                    </label>

                                    <label>
                                        <input type="checkbox" name="messengers[]" value="whatsapp">
                                        WhatsApp
                                    </label>

                                    <label>
                                        <input type="checkbox" name="messengers[]" value="viber">
                                        Viber
                                    </label>
                                </fieldset>

                                <div class="pwqb-quiz__form-field">
                                    <label for="pwqb-phone-<?php echo esc_attr($quiz_id); ?>">
                                        Номер телефона *
                                    </label>

                                    <input
                                        type="tel"
                                        id="pwqb-phone-<?php echo esc_attr($quiz_id); ?>"
                                        name="phone"
                                        placeholder="+7 (___) ___-__-__"
                                        autocomplete="tel"
                                        data-pwqb-phone>
                                </div>

                            </div>

                            <div class="pwqb-quiz__form-field">
                                <label for="pwqb-comment-<?php echo esc_attr($quiz_id); ?>">
                                    Ваш вопрос или комментарий
                                </label>
                                <textarea
                                    id="pwqb-comment-<?php echo esc_attr($quiz_id); ?>"
                                    name="comment"
                                    rows="4"></textarea>
                            </div>

                            <div
                                class="pwqb-quiz__form-message"
                                data-pwqb-form-message
                                hidden>
                            </div>

                            <button
                                type="submit"
                                class="pwqb-quiz__button pwqb-quiz__button--next">
                                Отправить
                            </button>

                        </form>

                    </div>

                <?php else : ?>

                    <h3>Спасибо за ответы!</h3>
                    <p>Вы прошли все вопросы.</p>

                <?php endif; ?>

                <button
                    type="button"
                    class="pwqb-quiz__button"
                    data-pwqb-restart>
                    Пройти ещё раз
                </button>

            </div>


        </div>

<?php

        return (string) ob_get_clean();
    }
}
