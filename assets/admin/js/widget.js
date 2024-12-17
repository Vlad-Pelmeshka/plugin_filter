jQuery(function ($) {
    function initColorPicker(widget) {
        widget.find('.premmerce-widget-color-picker').wpColorPicker({
            change: function (e, ui) {
                $(e.target).val(ui.color.toString());
                $(e.target).trigger('change');
            },
            clear: function (e, ui) {
                $(e.target).trigger('change');
            },
        });
    }

    $(document).ready(function () {
        $('.widget:has(.premmerce-widget-color-picker)').each(function () {
            initColorPicker($(this));
        });

        selectStyles();
    });

    function onFormUpdate(event, widget) {
        initColorPicker(widget);
    }

    function selectStyles() {
        var select = $(".premmerce-filter-widget-style");

        select.each(function () {
            var s = $(this);

            s.on('change', function () {
                if (s.val() == 'custom') {
                    s.closest('.widget-content').find('.premmerce-widget-filter-fields').show();
                } else {
                    s.closest('.widget-content').find('.premmerce-widget-filter-fields').hide();
                }
            });
        });
    }

    $(document).on('widget-added widget-updated', onFormUpdate);
    $(document).on('widget-added widget-updated', selectStyles);

});

