(function ($) {
    'use strict';

    function clamp(value, min, max) {
        value = Number(value);
        if (!Number.isFinite(value)) value = min;
        return Math.min(max, Math.max(min, value));
    }

    function numberFrom($input, fallback) {
        const value = parseFloat(String($input.val()).replace(',', '.'));
        return Number.isFinite(value) ? value : fallback;
    }

    function syncInputsFromSlider($range, values) {
        const $min = $range.find('input[name$="_min"]');
        const $max = $range.find('input[name$="_max"]');
        $min.val(values[0]);
        $max.val(values[1]);
    }

    function syncSliderFromInputs($range, triggerFilter) {
        const $slider = $range.find('.cwc-range-slider');
        if (!$slider.length || !$slider.hasClass('ui-slider')) return;

        const min = Number($slider.data('min'));
        const max = Number($slider.data('max'));
        let from = clamp(numberFrom($range.find('input[name$="_min"]'), min), min, max);
        let to = clamp(numberFrom($range.find('input[name$="_max"]'), max), min, max);
        if (from > to) {
            const swap = from;
            from = to;
            to = swap;
        }

        $slider.slider('values', [from, to]);
        syncInputsFromSlider($range, [from, to]);
        if (triggerFilter) {
            $range.find('input[name$="_min"]').trigger('change');
        }
    }

    function initNumericRangeSliders(context) {
        const $context = $(context || document);
        $context.find('.cwc-numeric-range .cwc-range-slider').addBack('.cwc-numeric-range .cwc-range-slider').each(function () {
            const $slider = $(this);
            if ($slider.hasClass('ui-slider')) return;

            const min = Number($slider.data('min'));
            const max = Number($slider.data('max'));
            const step = Number($slider.data('step')) || 1;
            const $range = $slider.closest('.cwc-numeric-range');
            const $minInput = $range.find('input[name$="_min"]');
            const $maxInput = $range.find('input[name$="_max"]');
            const startMin = clamp(numberFrom($minInput, min), min, max);
            const startMax = clamp(numberFrom($maxInput, max), min, max);

            $slider.slider({
                range: true,
                min: min,
                max: max,
                step: step,
                values: [Math.min(startMin, startMax), Math.max(startMin, startMax)],
                disabled: min === max,
                slide: function (event, ui) {
                    syncInputsFromSlider($range, ui.values);
                },
                stop: function (event, ui) {
                    syncInputsFromSlider($range, ui.values);
                    // ajax-filters.js already listens for change on .range-inputs inputs.
                    $minInput.trigger('change');
                }
            });
        });
    }

    // Intervals behave like radio options: only one interval can be active.
    $(document).on('click', '.cwc-filter-list--intervals .cwc-interval-option', function () {
        const $option = $(this);
        $option.closest('.cwc-filter-list--intervals')
            .find('.cwc-interval-option.active')
            .not($option)
            .removeClass('active');
    });

    // Keep the slider and number fields synchronized when a user edits a field.
    $(document).on('change', '.cwc-numeric-range input[name$="_min"], .cwc-numeric-range input[name$="_max"]', function () {
        syncSliderFromInputs($(this).closest('.cwc-numeric-range'), false);
    });

    // Exposed only so the existing reset handler can sync the visual slider after resetting fields.
    window.cwcSyncNumericRangeSliders = function (context) {
        const $context = $(context || document);
        $context.find('.cwc-numeric-range').each(function () {
            syncSliderFromInputs($(this), false);
        });
    };

    $(function () {
        initNumericRangeSliders(document);
    });

    $(document).on('cwc:products-updated', function () {
        initNumericRangeSliders(document);
    });
})(jQuery);
