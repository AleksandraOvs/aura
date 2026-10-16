(function ($) {
    'use strict';

    // Радиокнопки должны оставлять выбранным только одно значение внутри своего атрибута.
    // Чекбоксы, цвета и обычные списки сохраняют существующее поведение.
    $(document).on('click', '.cwc-filter-list--radio .cwc-filter-option', function () {
        const $item = $(this);
        const $list = $item.closest('.cwc-filter-list--radio');
        $list.find('.cwc-filter-option.active').not($item).removeClass('active');
    });
})(jQuery);
