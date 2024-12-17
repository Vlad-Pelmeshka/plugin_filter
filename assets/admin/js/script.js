jQuery(function ($) {

    if ($().select2) {
        $('.filter-settings-select').select2({ 'allowClear': false, 'minimumResultsForSearch': 10 });
    }

    //fix column sizes on
    $('[data-sortable] td').each(function () {
        $(this).css('width', $(this).width() + 'px');
    });

    //follow main checkbox by selectable
    $('[data-select-all]').change(function () {
        var main = $(this);
        var name = main.data('select-all');
        $('[data-selectable="' + name + '"]').prop('checked', main.prop('checked'));
    });

    var table = $('[data-sortable]');

    $('[data-swap-id]').droppable({
        accept: '[data-sortable] tr',
        hoverClass: "premmerce-filter-swap-hover",
        drop: function (e, ui) {

            var container = $(this);

            var dropped = ui.draggable;


            container.html('');

            table.attr('data-swap', [container.attr('data-swap-id'), dropped.find('[data-id]').attr('data-id')]);


            container.append(dropped.clone().removeAttr('style').removeClass("item").addClass("item-container"));
            dropped.remove();
        }
    });


    table.sortable({
        handle: '[data-sortable-handle]',
        axis: "y",
        connectWith: "[data-swap-id]",
        update: function () {
            var ids = [];
            var action = table.data('sortable');
            var prev = table.data('prev');
            var next = table.data('next');
            $('input[data-id]').each(function () {
                ids.push($(this).data('id'));
            });

            showPreloader($('[data-bulk-actions]'));

            var data = {
                ids: ids,
                action: action,
                prev: prev,
                next: next,
                swap: table.attr('data-swap'),
                ajax_nonce: adminLocOptions.ajax_nonce
            };

            $.post(ajaxurl, data, function () {
                window.location.reload();
            });
        },
        start: function () {
            $('[data-swap-id]').show();
        },
        stop: function () {
            $('[data-swap-id]').hide();
        }
    }).disableSelection();

    $('select[data-single-action]').change(function () {
        var $this = $(this);
        showPreloader($this.closest('td'));
        update([$this.data('id')], $this.data('single-action'), $this.val());
    });

    $('span[data-single-action]').click(function () {
        var $this = $(this);
        showPreloader($this.closest('td'));
        update([$this.data('id')], $this.data('single-action'), $this.data('value'));
    });

    $('button[data-action]').click(function () {
        var $this = $(this);

        showPreloader($this.closest('[data-bulk-actions]'));

        var action = $this.data('action');
        var value = $this.parent('.bulkactions').find('[data-bulk-action-select]').val();

        var ids = [];
        $('input[data-id]:checked').each(function () {
            ids.push($(this).data('id'));
        });

        update(ids, action, value);

    });

    function update(ids, action, value) {
        $.post(ajaxurl, { ids: ids, action: action, value: value, ajax_nonce: adminLocOptions.ajax_nonce }, function () {
            window.location.reload();
        })
    }

    // Init color dialog
    var colorDialog = $('[data-color-dialog]');


    //General function for dialog pop-up (color and image functionality)
    var dialogButtonOption = function (keyWord) {
        return [
            {
                text: colorDialog.attr('data-save-text'),
                click: function () {
                    var data = [];
                    var $this = $(this);
                    $(`[data-${keyWord}-dialog] [data-fieldtype-input]`).each(function () {
                        var $input = $(this);
                        data.push({ 'id': $input.attr('name'), 'value': $input.val() });
                    });

                    showPreloader($('.ui-dialog-buttonset'), 'prepend');

                    var taxonomy = $(`[data-${keyWord}-dialog] [name="taxonomy"]`).val();

                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: `premmerce_filter_save_${keyWord}s`,
                            taxonomy: taxonomy,
                            data: data,
                            ajax_nonce: adminLocOptions.ajax_nonce
                        },
                        dataType: 'json'
                    }).success(function (data) {
                        $this.dialog('close');
                    });
                }
            }
        ]
    }

    //Dialog pop-up for color
    colorDialog.dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: true,
        closeText: '',
        dialogClass: 'wp-dialog',
        minWidth: 600,
        buttons: dialogButtonOption('color')
    });

    // Color and image dialog open logic
    $(document).on('click', '[data-open-dialog]', function (e) {
        e.preventDefault();

        var dialogSelector = $(this).data('open-dialog');
        var attributeId = $(this).data('attribute-id');

        // This is RegEx pattern for finding key word inside data-{key_word}-dialog selector
        var dialogKeyWordPattern = `data-\s*(.*?)\s*-dialog`
        // Now we attempt the actual match. If successful, keyWordMatch[1] will be the key word.
        var keyWordMatch = dialogSelector.match(dialogKeyWordPattern)

        //it can be `color` or `image` field type
        var keyWord = keyWordMatch[1];

        showPreloader($(this).closest('td'));

        $.ajax({
            url: ajaxurl,
            method: 'GET',
            data: {
                action: `premmerce_filter_get_${keyWord}s`,
                id: attributeId,
                ajax_nonce: adminLocOptions.ajax_nonce
            },
            dataType: 'json'
        }).success(function (data) {

            hideLoader();

            var dialog = $(dialogSelector);

            //generate ul with class pc-{colors/images}-list
            var ul = $("<ul>", {
                class: `pc-${keyWord}s-list`
            });

            for (var key in data.results) {
                var item = data.results[key];

                //generate li with class pc-{colors/images}-list__item
                var li = $('<li>', {
                    class: `pc-${keyWord}s-list__item`
                });

                //item name
                var nameSpan = $('<span>', {
                    text: item.text,
                    class: 'term-name'
                });

                //add item name inside <li>
                li.append(nameSpan);

                //generate input with class term-{color/image}-picker
                var input = $('<input>', {
                    type: 'text',
                    name: item.id,
                    value: item.value,
                    class: `term-${keyWord}-picker`,
                    'data-fieldtype-input': `data-${keyWord}-input`
                });

                //generate upload button for image
                if ('image' == keyWord) {

                    input = $('<button/>', {
                        type: 'button',
                        text: 'Select image',
                        name: item.id,
                        value: item.value,
                        class: `button term-${keyWord}-picker`,
                        'data-fieldtype-input': `data-${keyWord}-input`
                    });

                    //if have image -
                    if (item.img_url.length > 0) {
                        li.append(
                            '<div class="term-image">' +
                            `<img src    ="${item.img_url}">` +
                            `<span class ="dashicons dashicons-no-alt term-${keyWord}-picker__rmv"></span>` +
                            '</div>'
                        );
                    } else {
                        li.append(
                            '<div class="term-image"></div>'
                        );
                    }

                }

                li.append(input);
                ul.append(li);

            }

            dialog.empty().append(ul);
            dialog.append($('<input>', { type: 'hidden', name: 'taxonomy', value: data.taxonomyName }));

            dialog.dialog({
                title: data.taxonomyLabel,
                modal: true,
                autoOpen: false,
                closeOnEscape: true,
                closeText: '',
                dialogClass: 'wp-dialog',
                buttons: dialogButtonOption(keyWord)
            }).dialog('open');

            //if it is Color type field - we are using Color Picker
            if ('color' == keyWord) {
                //add color Picker for each term-color-picker input
                $(`.term-${keyWord}-picker`).wpColorPicker();
            }

            //if it is Image type field - we are using wp.media (look at uploadMediaButton())
            if ('image' == keyWord) {
                //add upload media button for each term-image-picker
                if ($(`.term-${keyWord}-picker`).length > 0) {
                    if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
                        uploadMediaButton(keyWord);
                    }
                }
            }

        });
    });

    //functionality for media button wich we are using in Image Field Type
    function uploadMediaButton(keyWord) {
        //add image
        $(`.term-${keyWord}-picker`).on('click', function (e) {
            e.preventDefault();

            //media button
            var button = $(this)

            //register WP Media Library
            mediaUploader = wp.media({
                title: 'Insert image',
                library: {
                    type: 'image'
                },
                button: {
                    text: 'Select image' // button label text
                },
                multiple: false
            });

            //when open Media pop-up
            mediaUploader.on('open', function () {
                if (button.val()) {
                    mediaUploader.state().get('selection').add(wp.media.attachment(button.val()));
                }

            });

            //when select image in Media pop-up
            mediaUploader.on('select', function () {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                var li = button.parent();
                var termImageContent = `<span class="dashicons dashicons-no-alt term-${keyWord}-picker__rmv"></span>` +
                    `<img src="${attachment.url}"></img>`;

                li.children('.term-image').children('img').remove();
                li.children('.term-image').prepend(termImageContent).next().val(attachment.id).next().show();

                button.attr('value', attachment.id);
            });

            mediaUploader.open();
        });

        //remove image
        $(document).on('click', `.term-${keyWord}-picker__rmv`, function (e) {
            e.preventDefault();

            var removeButton = $(this);
            var li = removeButton.closest('li');

            li.find('button.term-image-picker').attr('value', ''); // emptying the hidden field
            li.find('.term-image').empty();
        });

    }

    //show preloader
    function showPreloader(element, position) {

        position = position || 'append';

        var loader = $('<span>', {
            class: 'spinner is-active pc-filter-loader'
        });

        if (position === 'append') {
            element.append(loader);
        } else if (position === 'prepend') {
            element.prepend(loader);
        }

    }

    //hide preloader
    function hideLoader() {
        $('.pc-filter-loader').remove();
    }

    //open shortcode dialog in setting page
    $("#shortcode-info-dialog").dialog({
        modal: true,
        autoOpen: false,
        closeOnEscape: true,
        closeText: '',
        dialogClass: 'wp-dialog',
        minWidth: 800,
        height: 510
    });

    $("#open-shortcode-info").on("click", function () {
        $("#shortcode-info-dialog").dialog("open");
    });

});
