(function () {
    'use strict';

    function updateRow(row) {
        var select = row.querySelector('.cwc-display-type');

        if (!select) {
            return;
        }

        var type = select.value;
        var intervalConfig = row.querySelector('.cwc-interval-config');
        var colorConfig = row.querySelector('.cwc-color-config');
        var rangeHelp = row.querySelector('.cwc-help-range');
        var noExtra = row.querySelector('.cwc-no-extra-config');

        if (intervalConfig) {
            intervalConfig.hidden = type !== 'intervals';
        }

        if (colorConfig) {
            colorConfig.hidden = type !== 'color';
        }

        if (rangeHelp) {
            rangeHelp.hidden = type !== 'range';
        }

        if (noExtra) {
            noExtra.hidden = !['legacy', 'checkbox', 'radio'].includes(type);
        }
    }

    document.addEventListener('change', function (event) {
        if (!event.target.matches('.cwc-display-type')) {
            return;
        }

        var row = event.target.closest('.cwc-attribute-row');

        if (row) {
            updateRow(row);
        }
    });

    document.addEventListener('click', function (event) {
        var addButton = event.target.closest('.cwc-add-interval');

        if (addButton) {
            event.preventDefault();

            var config = addButton.closest('.cwc-interval-config');
            if (!config) return;

            var template = config.querySelector('.cwc-interval-template');
            var tbody = config.querySelector('.cwc-interval-rows');

            if (!template || !tbody) {
                console.error('CWC: шаблон или таблица интервалов не найдены.');
                return;
            }

            var index = Date.now().toString();
            var fragment = template.content.cloneNode(true);

            fragment.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace(/__INDEX__/g, index);
            });

            var newRow = fragment.querySelector('.cwc-interval-row');

            if (!newRow) {
                console.error('CWC: в template.content не найдена строка интервала.');
                return;
            }

            tbody.appendChild(fragment);
            return;
        }

        var removeButton = event.target.closest('.cwc-remove-interval');

        if (removeButton) {
            event.preventDefault();

            var intervalRow = removeButton.closest('.cwc-interval-row');

            if (intervalRow) {
                intervalRow.remove();
            }
        }
    });

    function init() {
        document.querySelectorAll('.cwc-attribute-row').forEach(updateRow);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Переключение между цветом и изображением.
    document.addEventListener('change', function (event) {
        if (!(event.target instanceof Element)) {
            return;
        }

        var modeSelect = event.target.closest('.cwc-color-display-mode');

        if (!modeSelect) {
            return;
        }

        var term = modeSelect.closest('.cwc-color-term');

        if (!term) {
            return;
        }

        var colorEditor = term.querySelector('.cwc-color-editor--color');
        var imageEditor = term.querySelector('.cwc-color-editor--image');

        if (colorEditor) {
            colorEditor.hidden = modeSelect.value !== 'color';
        }

        if (imageEditor) {
            imageEditor.hidden = modeSelect.value !== 'image';
        }
    });

    // Одно переиспользуемое окно медиатеки.
    var colorImageFrame = null;
    var currentImageTerm = null;

    document.addEventListener('click', function (event) {
        if (!(event.target instanceof Element)) {
            return;
        }

        var selectButton = event.target.closest('.cwc-select-color-image');
        var removeButton = event.target.closest('.cwc-remove-color-image');

        if (selectButton) {
            event.preventDefault();

            if (typeof wp === 'undefined' || !wp.media) {
                console.error('CWC: медиатека WordPress не подключена.');
                return;
            }

            currentImageTerm = selectButton.closest('.cwc-color-term');

            if (!currentImageTerm) {
                return;
            }

            if (!colorImageFrame) {
                colorImageFrame = wp.media({
                    title: 'Выберите изображение для фильтра',
                    button: {
                        text: 'Использовать изображение'
                    },
                    library: {
                        type: 'image'
                    },
                    multiple: false
                });

                colorImageFrame.on('select', function () {
                    if (!currentImageTerm) {
                        return;
                    }

                    var selection = colorImageFrame.state().get('selection');
                    var attachment = selection.first();

                    if (!attachment) {
                        return;
                    }

                    var data = attachment.toJSON();

                    if (!data.id || !data.url) {
                        return;
                    }

                    var idInput = currentImageTerm.querySelector('.cwc-color-image-id');
                    var preview = currentImageTerm.querySelector('.cwc-color-image-preview');
                    var removeBtn = currentImageTerm.querySelector('.cwc-remove-color-image');

                    if (idInput) {
                        idInput.value = data.id;
                    }

                    var imageUrl = data.url;

                    if (
                        data.mime !== 'image/svg+xml' &&
                        data.sizes &&
                        data.sizes.thumbnail
                    ) {
                        imageUrl = data.sizes.thumbnail.url;
                    }

                    if (preview) {
                        preview.replaceChildren();

                        var image = document.createElement('img');
                        image.src = imageUrl;
                        image.alt = '';

                        preview.appendChild(image);
                    }

                    if (removeBtn) {
                        removeBtn.hidden = false;
                    }
                });
            }

            colorImageFrame.open();
            return;
        }

        if (removeButton) {
            event.preventDefault();

            var term = removeButton.closest('.cwc-color-term');

            if (!term) {
                return;
            }

            var idInput = term.querySelector('.cwc-color-image-id');
            var preview = term.querySelector('.cwc-color-image-preview');

            if (idInput) {
                idInput.value = '';
            }

            if (preview) {
                preview.replaceChildren();

                var message = document.createElement('span');
                message.textContent = 'Изображение не выбрано';

                preview.appendChild(message);
            }

            removeButton.hidden = true;
        }
    });

    // Сортировка строк атрибутов.
    function initFilterSorting() {
        if (
            typeof jQuery === 'undefined' ||
            !jQuery.fn.sortable
        ) {
            return;
        }

        var $tbody = jQuery('.cwc-display-table tbody');

        if (!$tbody.length || $tbody.data('cwc-sortable')) {
            return;
        }

        $tbody.sortable({
            items: '> tr.cwc-attribute-row',
            handle: '.cwc-filter-order-handle',
            axis: 'y',
            tolerance: 'pointer',
            helper: function (event, ui) {
                ui.children().each(function () {
                    jQuery(this).width(jQuery(this).width());
                });

                return ui;
            },
            update: function () {
                $tbody.find('tr.cwc-attribute-row').each(function () {
                    var taxonomy = this.getAttribute('data-taxonomy');
                    var input = document.querySelector(
                        '.cwc-filter-order-input[value="' + taxonomy + '"]'
                    );

                    if (input) {
                        input.value = taxonomy;
                    }
                });

                // Пересоздаём поля в фактическом порядке строк.
                var form = $tbody.closest('form')[0];

                if (!form) {
                    return;
                }

                var orderInputs = form.querySelectorAll('.cwc-filter-order-input');
                orderInputs.forEach(function (input) {
                    input.remove();
                });

                $tbody.find('tr.cwc-attribute-row').each(function () {
                    var input = document.createElement('input');

                    input.type = 'hidden';
                    input.name = 'cwc_filter_display_order[]';
                    input.value = this.getAttribute('data-taxonomy');
                    input.className = 'cwc-filter-order-input';

                    form.appendChild(input);
                });
            }
        });

        $tbody.data('cwc-sortable', true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFilterSorting);
    } else {
        initFilterSorting();
    }
}());
