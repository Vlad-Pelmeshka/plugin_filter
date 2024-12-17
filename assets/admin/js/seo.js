jQuery(function ($) {
    function RuleTable(taxonomyManager) {

        this.statedAttributes = [];

        this.taxonomyManager = taxonomyManager;

        this.prototype = $('[data-prototype-table] tr');

        this.container = $('[data-row-container]');

        var self = this;

        this.addRow = function () {
            var row = self.prototype.clone();
            self.container.append(row);
            row.find('select').select2();
            self.checkAddButton();
        };

        this.removeRow = function () {
            var tr = $(this).closest('tr');
            var taxonomy = tr.find('[data-select-taxonomy]').val();
            tr.remove();
            taxonomyManager.enable(taxonomy);
            self.checkAddButton();
            self.updateAttributeOptions();
            $('[data-taxonomy-name="' + taxonomy.replace('pa_', '') + '"]').remove();
        };

        this.getNumRows = function () {
            return self.container.find('tr').length;
        };

        this.updateAttributeOptions = function (previous) {
            var availableOptions = $('[data-taxonomies-wrapper], [data-term-table]').find('[data-select-taxonomy]').find('option[value!=""]:selected').clone();
            const addAttributeValueSelector = this.addAttributeValueSelector;
            let attributeValueList = document.querySelectorAll('[data-attribute-value-list]');
            ['name', /*'value'*/].forEach(function (type, index) {
                var options = availableOptions.clone().filter(function (o, op) {
                    return op.value.length !== 0;
                });
                options.each(function (index, element) {
                    var attributeName = $(element).val().replace(/^pa_/, '');
                    if (attributeName.length > 0) {
                        $(element).val('{attribute_' + type + '_' + attributeName + '}');
                        if (document.querySelectorAll('[data-taxonomy-name="' + attributeName + '"]').length === 0) {
                            addAttributeValueSelector(element, attributeName, attributeValueList);
                            self.statedAttributes.push(attributeName);
                        }
                    }
                });

                $('[data-attribute-' + type + '-select]').each(function (index, select) {
                    $(select).find('option[value!=""]').remove();
                    $(select).append($(options).clone()).val('');
                });
            });
        };

        this.addAttributeValueSelector = function (element, attributeName, targets) {
            targets.forEach(function (target, t) {
                target = $(target);
                let attributeValueSelector = document.createElement('select');
                attributeValueSelector.style.width = '200px';
                attributeValueSelector.dataset.field = target.data().fieldValue;
                attributeValueSelector.dataset.taxonomyName = attributeName;
                attributeValueSelector.dataset.attributeValue = '';
                attributeValueSelector.dataset.var = '';

                attributeValueSelector.innerHTML += '<option value="">' + target.data().translatedOption.replace('{{attribute}}', element.innerText) + '</option>' +
                    '<option value="{attribute_value_' + attributeName + '}">All &laquo;' + element.innerText + '&raquo; values</option>';
                target.append(attributeValueSelector);
            });
        };

        this.checkAddButton = function () {
            if (self.getNumRows() >= self.taxonomyManager.getTaxonomies().length) {
                $('[data-add-row]').closest('tr').hide();
            } else {
                $('[data-add-row]').closest('tr').show();
            }

        };

        this.checkAddButton();
    }

    function TaxonomyManager() {

        var self = this;

        this.onTaxonomySelectChange = function () {
            var el = $(this);
            var taxonomy = el.val();
            var previous = el.attr('data-val');
            if (previous) {
                $(`[data-taxonomy-name ="${previous.replace(/^pa_/, '')}"`).remove();
            }
            if (taxonomy === previous) {
                return;
            }

            var termSelect = el.closest('tr, .premmerce-flex-form-fields').find('[data-select-term]');

            self.initTermSelect(termSelect, taxonomy, this.hasAttribute('data-generate-rule-taxonomy'));
            self.enable(previous);
            self.disable(taxonomy);

            table.updateAttributeOptions();

            el.attr('data-val', taxonomy);
        };

        this.initTermSelect = function (termSelect, taxonomy, isMultiple) {
            if (!isMultiple) {
                termSelect.attr('name', 'terms[' + taxonomy + '][]');
            }

            var selectedTerms = termSelect.attr('data-selected-value');
            var params = this.getAjaxParams(taxonomy);

            $.ajax(params).then(function (data) {
                let options = '';
                data.results.forEach(function (result, r) {
                    let isTermSelected = '';
                    if (termSelect.find(`option[value ="${result.id}"]:selected`).length !== 0) {
                        isTermSelected = 'selected';
                    }
                    options += `<option ${isTermSelected} value="${result.id}" data-parent="${result.taxonomy}">${result.text}</option>`;
                });

                termSelect.html(options);
                termSelect.select2();

                if (selectedTerms) {
                    selectedTerms = JSON.parse(selectedTerms);
                    termSelect.val(selectedTerms).trigger('change');
                }
            });
        };

        this.getAjaxParams = function (taxonomy) {
            return {
                url: ajaxurl,
                method: 'POST',
                dataType: "json",
                data: {
                    'taxonomy': taxonomy,
                    'action': 'get_taxonomy_terms',
                    'ajax_nonce': adminLocOptions.ajax_nonce
                }

            };
        };

        this.getTaxonomies = function () {
            return this.collectTaxonomies('[data-select-taxonomy]:first option');
        };

        this.collectTaxonomies = function (selector) {
            var taxonomies = [];

            $(selector).each(function () {
                if (this.value) {
                    taxonomies.push(this.value)
                }
            });

            return taxonomies;
        };

        this.enable = function (taxonomy) {
            $('[data-select-taxonomy] option[value="' + taxonomy + '"]').each(function () {
                $(this).removeAttr('disabled')
            });
        };
        this.disable = function (taxonomy) {
            if (!taxonomy) {
                return;
            }
            $('[data-select-taxonomy]').find('option[value="' + taxonomy + '"]:not(:selected)').each(function () {
                $(this).attr('disabled', 'disabled')
            });
        }

    }


    $(document).on('change', '[data-select-term]', function () {
        const
            targets = document.querySelectorAll('[data-attribute-value-list]'),
            $this = $(this),
            selected = $this.val();

        targets.forEach(function (target, t) {
            target = $(target);
            let values = {};
            if (selected) {
                selected.forEach(function (option, o) {
                    let tx = $(document).find('option[value="' + option + '"]');
                    let parent = tx.data().parent,
                        attributeName = parent.replace(/^pa_/, '');
                    if (!values[parent]) {
                        values[parent] = '';
                    }

                    values[parent] += '<option value="{attribute_value_' + attributeName + '_' + option + '}">' + tx.text() + '</option>';
                })
            }

            if (Object.keys(values).length) {
                Object.keys(values).forEach(function (value, v) {
                    let attributeTitle = document.querySelector(`option[value="${value}"]`).innerText;
                    let options =
                        '<option value="">' + target.data().translatedOption.replace('{{attribute}}', attributeTitle) + '</option><option value="{attribute_value_' + value.replace(/^pa_/, '') + '}">All &laquo;' + attributeTitle + '&raquo; values</option>'
                        + values[value];

                    $('[data-taxonomy-name="' + value.replace(/^pa_/, '') + '"]').html(options);
                });

            }
        });


    });

    $('[data-term-table] [data-select-two]').select2();

    let taxonomySelector = document.querySelector('[data-select-taxonomy]');
    if (taxonomySelector && taxonomySelector.hasAttribute('multiple')) {
        $(document).on('select2:unselect', '[data-select-taxonomy]', function (e) {
            $(document).find('[data-attribute-value-list]').find(`[data-taxonomy-name="${e.params.data.id.replace(/^pa_/, '')}"]`).remove();
        });
    }

    const tx = new TaxonomyManager();
    const table = new RuleTable(tx);

    $(document).on('change', '[data-select-taxonomy]', tx.onTaxonomySelectChange);

    $(document).on('click', '[data-add-row]', table.addRow);
    $(document).on('click', '[data-remove-row]', table.removeRow);

    //Trigger change to load terms to selects
    $('[data-term-table] [data-select-taxonomy]').trigger('change');

    //Insert variable into input
    $(document).on('click', 'button[data-var]', function () {
        var variable = $(this).attr('data-var');
        var field = $(this).attr('data-field');
        insertText(field, variable);
    });

    $(document).on('change', 'select[data-var]', function () {
        var variable = $(this).val();

        if ('' === variable) {
            return;
        }

        var field = $(this).attr('data-field');
        insertText(field, variable);

        $(this).val('');
    });

    //Insert text into input,textarea or text editor
    function insertText(fieldSelector, text) {

        var field = document.querySelector(fieldSelector);

        if (!field) {
            return;
        }

        field.focus();
        var value = field.value;
        var position = field.selectionStart;
        field.value = value.slice(0, position) + text + value.slice(position);
        position += text.length;
        field.setSelectionRange(position, position);

        if (field.classList.contains('wp-editor-area') && typeof tinyMCE !== 'undefined') {
            if (tinyMCE.get(field.id)) {
                tinyMCE.get(field.id).execCommand('mceInsertContent', false, text);
            }
        }

    }

    //Rules generation

    $('[data-generate-select-two]').each(function () {
        initSelect2WithPlaceholder($(this));
    });

    $(document).on('change', '[data-taxonomy-select]', function () {
        let targets = document.querySelectorAll('[data-attribute-name-select]'),
            values = '<option value="">Add attribute name</option>',
            selected = $('[data-taxonomy-select]').find('option:selected').toArray();

        selected.forEach(function (option, o) {
            if (selected.indexOf(option.value) === -1) {
                values += `<option value="{attribute_name_${option.value}}">${option.text}</option>`;
            }
        });

        targets.forEach(function (target, t) {
            target = $(target);
            target.html(values);
        });
    });

    $(document).on('click', '[data-add-taxonomy-button]', function () {
        var selectRow = $('[data-taxonomy-prototype]');
        var clone = selectRow.clone();
        var taxonomySelect = clone.find('[data-select-taxonomy]');
        var termSelect = clone.find('[data-select-term]');
        clone.removeAttr('data-taxonomy-prototype');
        clone.removeAttr('hidden');

        var num = $('[data-taxonomies-wrapper][data-select-taxonomy]').length;
        taxonomySelect.attr('name', 'filter_taxonomy[' + num + ']');
        termSelect.attr('name', 'filter_term[' + num + '][]');

        clone.addClass('premmerce-flex-form-fields');

        $('[data-taxonomies-wrapper]').append(clone);
        initSelect2WithPlaceholder(taxonomySelect);
        initSelect2WithPlaceholder(termSelect);
    });

    function initSelect2WithPlaceholder(select) {
        var placeholder = select.attr('placeholder');
        select.select2({
            placeholder: placeholder
        });
    }

    $(document).on('click', '[data-remove-element]', function () {

        let $this = $(this);
        let remove = $this.attr('data-remove-element');
        let closest = $this.closest(remove);
        let taxonomy = closest.find('[data-select-taxonomy]').val().replace(/^pa_/, '');
        $(document).find('[data-taxonomy-name="' + taxonomy + '"]').remove();
        tx.enable(closest.find('[data-select-taxonomy]').val());
        closest.remove();
        $('[data-taxonomies-wrapper][data-select-taxonomy]').each(function () {
            initSelect2WithPlaceholder($(this));
        });
    });

    //Progress bar
    function initProgressBar() {
        var widget = $(".progress-bar__widget");
        var max = $('[data-progressbar-max]').text();
        widget.progressbar({
            value: 0,
            max: max,
            create: function () {
                generateNextRule(widget);
            }
        });
    }

    function generateNextRule(widget) {

        var complete = $('[data-progress-complete-url]').val();
        var action = $('[data-progress-action]').val();
        $.ajax({
            url: ajaxurl,
            method: 'post',
            dataType: 'json',
            data: { action: action },
        }).then(function (data) {
            var value = widget.progressbar('value') + 1;
            widget.progressbar('value', value);
            $('[data-progressbar-current]').text(value);
            if (data.status === 'next') {
                generateNextRule(widget);
            } else {
                location.assign(complete);
            }
        });
    }

    initProgressBar();
});
