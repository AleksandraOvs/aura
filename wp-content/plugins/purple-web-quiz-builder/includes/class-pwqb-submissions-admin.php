<?php

if (!defined('ABSPATH')) {
    exit;
}

class PWQB_Submissions_Admin
{
    private const PAGE_SLUG = 'pwqb-submissions';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_post_pwqb_clear_submissions', [self::class, 'clear_submissions']);
    }

    /**
     * Удаляет все заявки.
     */
    public static function clear_submissions(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав для выполнения действия.');
        }

        check_admin_referer('pwqb_clear_submissions');

        global $wpdb;

        $table = $wpdb->prefix . 'pwqb_submissions';

        $deleted = $wpdb->query("DELETE FROM {$table}");

        if ($deleted === false) {
            wp_die('Не удалось очистить таблицу заявок.');
        }

        $redirect_url = add_query_arg(
            [
                'post_type' => PWQB_Quiz::POST_TYPE,
                'page'      => self::PAGE_SLUG,
                'cleared'   => 1,
                'deleted'   => (int) $deleted,
            ],
            admin_url('edit.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Добавляет подпункт «Заявки» в меню квизов.
     */
    public static function add_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . PWQB_Quiz::POST_TYPE,
            'Заявки',
            'Заявки',
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'render_page']
        );
    }

    /**
     * Выводит список или отдельную заявку.
     */
    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав для просмотра заявок.');
        }

        $submission_id = isset($_GET['submission_id'])
            ? absint($_GET['submission_id'])
            : 0;

        if ($submission_id) {
            self::render_single($submission_id);
            return;
        }

        self::render_list();
    }

    /**
     * Список заявок.
     */
    private static function render_list(): void
    {
        $page = isset($_GET['paged'])
            ? max(1, absint($_GET['paged']))
            : 1;

        $result = PWQB_Submissions::get_list([
            'per_page' => 20,
            'page'     => $page,
        ]);

        $items = $result['items'];
?>
        <div class="wrap">
            <h1>Заявки</h1>

            <?php if (isset($_GET['cleared']) && $_GET['cleared'] === '1') : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        Таблица очищена. Удалено заявок:
                        <?php echo esc_html(absint($_GET['deleted'] ?? 0)); ?>.
                    </p>
                </div>
            <?php endif; ?>

            <form
                method="post"
                action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                style="margin: 15px 0;">

                <input type="hidden" name="action" value="pwqb_clear_submissions">

                <?php wp_nonce_field('pwqb_clear_submissions'); ?>

                <button
                    type="submit"
                    class="button button-secondary"
                    onclick="return confirm('Вы действительно хотите удалить ВСЕ заявки? Это действие нельзя отменить.');">
                    Очистить таблицу
                </button>

            </form>

            <?php if (empty($items)) : ?>

                <p>Заявок пока нет.</p>

            <?php else : ?>

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 145px;">Дата</th>
                            <th>Квиз</th>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Телефон</th>
                            <th style="width: 160px;">Посетитель</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($items as $item) : ?>
                            <?php
                            $details_url = add_query_arg(
                                [
                                    'post_type'    => PWQB_Quiz::POST_TYPE,
                                    'page'         => self::PAGE_SLUG,
                                    'submission_id' => absint($item['id']),
                                ],
                                admin_url('edit.php')
                            );
                            ?>

                            <tr>
                                <td>
                                    <?php
                                    echo esc_html(
                                        mysql2date(
                                            'd.m.Y H:i',
                                            $item['created_at']
                                        )
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php if (!empty($item['quiz_title'])) : ?>
                                        <?php echo esc_html($item['quiz_title']); ?>
                                    <?php else : ?>
                                        <span>Квиз удалён</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="<?php echo esc_url($details_url); ?>">
                                        <strong>
                                            <?php echo esc_html($item['name']); ?>
                                        </strong>
                                    </a>
                                </td>

                                <td>
                                    <a href="mailto:<?php echo esc_attr($item['email']); ?>">
                                        <?php echo esc_html($item['email']); ?>
                                    </a>
                                </td>

                                <td>
                                    <?php if (!empty($item['phone'])) : ?>
                                        <a href="tel:<?php echo esc_attr($item['phone']); ?>">
                                            <?php echo esc_html($item['phone']); ?>
                                        </a>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php
                                    $send_to_labels = [
                                        'email'     => 'На Email',
                                        'messenger' => 'В мессенджер',
                                        'both'      => 'На Email и в мессенджер',
                                    ];

                                    echo esc_html(
                                        $send_to_labels[$item['send_to'] ?? ''] ?? 'Не указано'
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php if (!empty($item['is_guest'])) : ?>
                                        <span>Без регистрации</span>
                                    <?php else : ?>
                                        <span>
                                            Пользователь #<?php echo absint($item['user_id']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($result['pages'] > 1) : ?>

                    <?php
                    $pagination_base = add_query_arg(
                        [
                            'post_type' => PWQB_Quiz::POST_TYPE,
                            'page'      => self::PAGE_SLUG,
                            'paged'     => '%#%',
                        ],
                        admin_url('edit.php')
                    );

                    echo wp_kses_post(
                        paginate_links([
                            'base'      => $pagination_base,
                            'format'    => '',
                            'current'   => $page,
                            'total'     => $result['pages'],
                            'prev_text' => '« Назад',
                            'next_text' => 'Далее »',
                        ])
                    );
                    ?>

                <?php endif; ?>

            <?php endif; ?>

            <span style="font-size: 12px; font-weight: 400; color: #787c82;">
                Заблокированных запросов:
                <?php echo esc_html(number_format_i18n(
                    (int) get_option('pwqb_blocked_requests', 0)
                )); ?>
            </span>
        </div>
    <?php
    }

    /**
     * Просмотр отдельной заявки.
     */
    private static function render_single(int $submission_id): void
    {
        $submission = PWQB_Submissions::get($submission_id);

        if (!$submission) {
            echo '<div class="wrap">';
            echo '<h1>Заявка не найдена</h1>';
            self::render_back_link();
            echo '</div>';
            return;
        }

    ?>
        <div class="wrap">
            <h1>
                Заявка №<?php echo absint($submission['id']); ?>
            </h1>

            <?php self::render_back_link(); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th>Дата отправки</th>
                        <td>
                            <?php
                            echo esc_html(
                                mysql2date(
                                    'd.m.Y H:i',
                                    $submission['created_at']
                                )
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Квиз</th>
                        <td>
                            <?php
                            echo esc_html(
                                $submission['quiz_title'] ?: 'Квиз удалён'
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Посетитель</th>
                        <td>
                            <?php if (!empty($submission['is_guest'])) : ?>
                                Без регистрации
                            <?php else : ?>
                                Пользователь #<?php echo absint($submission['user_id']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Имя</th>
                        <td><?php echo esc_html($submission['name']); ?></td>
                    </tr>

                    <tr>
                        <th>Email</th>
                        <td>
                            <a href="mailto:<?php echo esc_attr($submission['email']); ?>">
                                <?php echo esc_html($submission['email']); ?>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <th>Телефон</th>
                        <td>
                            <?php if (!empty($submission['phone'])) : ?>
                                <a href="tel:<?php echo esc_attr($submission['phone']); ?>">
                                    <?php echo esc_html($submission['phone']); ?>
                                </a>
                            <?php else : ?>
                                Не указан
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Куда отправить подборку</th>
                        <td>
                            <?php
                            $send_to_labels = [
                                'email'     => 'На Email',
                                'messenger' => 'В мессенджер',
                                'both'      => 'На Email и в мессенджер',
                            ];

                            echo esc_html(
                                $send_to_labels[$submission['send_to'] ?? ''] ?? 'Не указано'
                            );
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Мессенджеры</th>
                        <td>
                            <?php
                            $messenger_labels = [
                                'telegram' => 'Telegram',
                                'whatsapp' => 'WhatsApp',
                                'viber'    => 'Viber',
                            ];

                            $selected_messengers = [];

                            if (!empty($submission['messengers']) && is_array($submission['messengers'])) {
                                foreach ($submission['messengers'] as $messenger) {
                                    $selected_messengers[] = $messenger_labels[$messenger] ?? $messenger;
                                }
                            }

                            echo !empty($selected_messengers)
                                ? esc_html(implode(', ', $selected_messengers))
                                : 'Не выбраны';
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th>Комментарий</th>
                        <td>
                            <?php
                            echo $submission['comment'] !== ''
                                ? nl2br(esc_html($submission['comment']))
                                : 'Не указан';
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2>Ответы пользователя</h2>

            <?php self::render_answers($submission); ?>

            <?php self::render_back_link(); ?>
        </div>
<?php
    }

    /**
     * Выводит ответы с названиями вопросов и вариантов.
     */
    private static function render_answers(array $submission): void
    {
        $answers = $submission['answers'];

        if (empty($answers)) {
            echo '<p>Ответы не сохранены.</p>';
            return;
        }

        $settings = get_post_meta(
            absint($submission['quiz_id']),
            PWQB_Quiz::META_SETTINGS,
            true
        );

        $questions = is_array($settings)
            ? ($settings['questions'] ?? [])
            : [];

        $questions = is_array($questions)
            ? $questions
            : [];

        $questions_by_id = [];

        foreach ($questions as $question) {
            if (!empty($question['id'])) {
                $questions_by_id[(string) $question['id']] = $question;
            }
        }

        echo '<table class="widefat striped" style="max-width: 800px;">';
        echo '<thead><tr><th>Вопрос</th><th>Ответ</th></tr></thead>';
        echo '<tbody>';

        foreach ($answers as $question_id => $selected_answers) {
            $question = $questions_by_id[(string) $question_id] ?? null;

            $question_title = $question['title'] ?? (
                'Вопрос ' . sanitize_text_field((string) $question_id)
            );

            if (!is_array($selected_answers)) {
                $selected_answers = [$selected_answers];
            }

            $answer_titles = [];

            foreach ($selected_answers as $selected_id) {
                $selected_id = (string) $selected_id;
                $found = false;

                if (!empty($question['answers']) && is_array($question['answers'])) {
                    foreach ($question['answers'] as $answer) {
                        if ((string) ($answer['id'] ?? '') === $selected_id) {
                            $answer_titles[] = $answer['title'] ?? $selected_id;
                            $found = true;
                            break;
                        }
                    }
                }

                if (!$found) {
                    $answer_titles[] = $selected_id;
                }
            }

            echo '<tr>';
            echo '<td>' . esc_html($question_title) . '</td>';
            echo '<td>' . esc_html(implode(', ', $answer_titles)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Ссылка обратно к списку.
     */
    private static function render_back_link(): void
    {
        $url = add_query_arg(
            [
                'post_type' => PWQB_Quiz::POST_TYPE,
                'page'      => self::PAGE_SLUG,
            ],
            admin_url('edit.php')
        );

        echo '<p><a href="' . esc_url($url) . '">← Все заявки</a></p>';
    }
}
