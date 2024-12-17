<?php namespace Premmerce\Filter\Admin\Tabs\Base;

use Premmerce\Filter\FilterPlugin;

abstract class BaseSettings implements TabInterface
{
    /**
     * Page
     *
     * @var string
     */
    protected $page;

    /**
     * Group
     *
     * @var string
     */
    protected $group;

    /**
     * Option Name
     *
     * @var string
     */
    protected $optionName;

    /**
     * Options
     *
     * @var array
     */
    protected $options;

    /**
     * Only Premium Tabs
     *
     * @var array
     */
    protected $onlyPremiumTabs = array('premmerce-filter-admin-permalinks');

    /**
     * Register Settings
     *
     * @param  mixed $settings
     * @param  mixed $page
     * @param  mixed $optionName
     * @return void
     */
    protected function registerSettings($settings, $page, $optionName)
    {
        $callbacks = array(
            'checkbox' => array($this, 'checkboxCallback'),
            'text'     => array($this, 'inputCallback'),
            'textarea' => array($this, 'textareaCallback'),
            'editor'   => array($this, 'textEditorCallback'),
            'select'   => array($this, 'selectCallback'),
            'info'     => array($this, 'infoCallback'),
        );

        foreach ($settings as $sectionId => $section) {
            $callback = isset($section['callback']) ? $section['callback'] : null;
            add_settings_section($sectionId, $section['label'], $callback, $page);

            foreach ($section['fields'] as $fieldId => $field) {
                $title                = isset($field['title']) ? $field['title'] : '';
                $field['label_for']   = $fieldId;
                $field['option_name'] = $optionName;
                $field['placeholder'] = isset($field['placeholder']) ? $field['placeholder'] : '';
                $field['id']          = isset($field['id']) ? $field['id'] : '';

                add_settings_field($fieldId, $title, $callbacks[$field['type']], $page, $sectionId, $field);
            }
        }
    }

    /**
     * Generate disable tag and link to buy premium page
     */
    public function disableInFree($args)
    {
        $disabled    = '';
        $premiumLink = '';

        //if it is not premium plan - disable checkbox and show premium link
        if (!premmerce_pwpf_fs()->can_use_premium_code() && !empty($args['plan']) && 'premium' === $args['plan']) {
            $disabled    = 'disabled';
            $premiumLink = self::premiumLink();
        }

        $disableInFree = array(
            'disabled'     => $disabled,
            'premium_link' => $premiumLink,
        );

        return $disableInFree;
    }

    /**
     * Generate link to buy premium page
     */
    public static function premiumLink()
    {
        $premiumLinkText    = __('Premium', 'premmerce-filter');
        $premiumPricingLink = admin_url('admin.php?page=premmerce-filter-admin-pricing');
        $premiumLink        = '<a class="premmerce-premium-blue" href="' . $premiumPricingLink . '">' . $premiumLinkText . '</a>';

        return $premiumLink;
    }

    /**
     * Generate text that tab is only for Premium plan
     *
     * @param  mixed $text
     * @return void
     */
    public static function premiumForTabLabel($text)
    {
        if (!premmerce_pwpf_fs()->can_use_premium_code()) {
            $text .= '<span class="premmerce-premium-blue">' . __(' (Premium)', 'premmerce-filter') . '</span>';
        }

        $label = __($text, 'premmerce-filter');

        return $label;
    }

    /**
     * Checkbox Callback
     *
     * @param array $args
     */
    public function checkBoxCallback($args)
    {
        $checked = $this->getOption($args['label_for']);

        if ('1' === $checked) {
            $checked = 'on';
        }

        //check plan and return disable tag and link to buy premium page
        $disableInFree = $this->disableInFree($args);

        //add help text if it is
        $helpText = (isset($args['help'])) ? '<p class="premmerce-help-text">' . $args['help'] . '</p>' : '';

        printf(
            '<label><input type="checkbox" name="%1$s[%2$s]" %3$s id="%4$s" %5$s>%6$s %7$s</label>%8$s',
            esc_attr($args['option_name']),
            esc_attr($args['label_for']),
            checked('on', $checked, false),
            esc_attr($args['id']),
            esc_attr($disableInFree['disabled']),
            esc_attr($args['label']),
            wp_kses($disableInFree['premium_link'], FilterPlugin::HTML_TAGS),
            wp_kses($helpText, FilterPlugin::HTML_TAGS)
        );

        do_action('premmerce_filter_settings_after_checkbox_callback', $args);
    }

    /**
     * Input Callback
     *
     * @param array $args
     */
    public function inputCallback($args)
    {
        //check plan and return disable tag and link to buy premium page
        $disableInFree = $this->disableInFree($args);

        printf(
            '<input class="filter-settings-input" type="text" name="%1$s[%2$s]" value="%3$s" placeholder="%4$s" id="%5$s" %6$s>',
            esc_attr($this->optionName),
            esc_attr($args['label_for']),
            esc_attr($this->getOption($args['label_for'])),
            esc_attr($args['placeholder']),
            esc_attr($args['id']),
            esc_attr($disableInFree['disabled'])
        );

        do_action('premmerce_filter_settings_after_input_callback', $args);
    }

    /**
     * Select Callback
     *
     * @param array $args
     */
    public function selectCallback($args)
    {
        $options = isset($args['options']) ? $args['options'] : array();

        $value = $this->getOption($args['label_for']);

        $multiple = ! empty($args['multiple']) ? 'multiple' : null;

        printf(
            '<select name="%1$s[%2$s]%3$s" class="filter-settings-select" placeholder="%4$s" id="%5$s" %6$s>',
            esc_attr($this->optionName),
            esc_attr($args['label_for']),
            $multiple ? '[]' : '',
            esc_attr($args['placeholder']),
            esc_attr($args['id']),
            esc_attr($multiple)
        );

        foreach ($options as $key => $option) {
            if (! empty($value)) {
                if (is_null($multiple)) {
                    $selected = $key === $value ? 'selected' : '';
                } else {
                    $selected = in_array($key, $value) ? 'selected' : '';
                }
            } else {
                $selected = '';
            }

            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($key),
                esc_attr($selected),
                esc_attr($option)
            );
        }

        print('</select>');

        if (isset($args['help'])) {
            $helpPremiumText = '';

            //show additional text about premium features for free plan
            if (!premmerce_pwpf_fs()->can_use_premium_code() && isset($args['help_premium'])) {
                $premiumLinkText = __('premium', 'premmerce-filter');
                $premiumLink     = '<a class="premmerce-premium-blue" href="' . admin_url('admin.php?page=premmerce-filter-admin-pricing') . '">' . $premiumLinkText . '</a>';

                $helpPremiumText = str_replace('premium', $premiumLink, $args['help_premium']);
            }

            printf(
                '<p>%1$s <strong>%2$s</strong></p>',
                wp_kses($args['help'], FilterPlugin::HTML_TAGS),
                wp_kses($helpPremiumText, FilterPlugin::HTML_TAGS)
            );
        }

        do_action('premmerce_filter_settings_after_input_callback', $args);
    }

    /**
     * Textarea Callback
     *
     * @param array $args
     */
    public function textareaCallback($args)
    {
        printf(
            '<textarea class="filter-settings-input" name="%1$s[%2$s]" placeholder="%3$s" cols="30" rows="10" id="%4$s">%5$s</textarea>',
            esc_attr($this->optionName),
            esc_attr($args['label_for']),
            esc_attr($args['placeholder']),
            esc_attr($args['id']),
            esc_attr($this->getOption($args['label_for']))
        );

        do_action('premmerce_filter_settings_after_textarea_callback', $args);
    }

    /**
     * Text Editor Callback
     *
     * @param  mixed $args
     * @return void
     */
    public function textEditorCallback($args)
    {
        wp_editor(
            $this->getOption($args['label_for']),
            $args['id'],
            array('textarea_name' => $this->optionName . '[' . esc_attr($args['label_for']) . ']')
        );
        do_action('premmerce_filter_settings_after_text_editor_callback', $args);
    }

    /**
     * Info Callback
     *
     * @param  mixed $args
     * @return void
     */
    public function infoCallback($args)
    {
        if (isset($args['help'])) {
            printf(
                '<p>%1$s</p>',
                wp_kses($args['help'], FilterPlugin::HTML_TAGS)
            );
        }
    }

    /**
     * Render page
     */
    public function render()
    {
        print('<form action="' . esc_url(admin_url('options.php')) . '" method="post">');

        settings_errors();

        settings_fields($this->group);

        do_settings_sections($this->page);

        //disable button in free version for pages from array onlyPremiumTabs
        if (!premmerce_pwpf_fs()->can_use_premium_code() && in_array($this->page, $this->onlyPremiumTabs)) {
            submit_button(null, 'primary', 'submit', true, 'disabled');
        } else {
            submit_button();
        }

        print('</form>');
    }

    /**
     * Get Option
     *
     * @param  mixed $key
     * @param  mixed $default
     * @return void
     */
    public function getOption($key, $default = null)
    {
        if (is_null($this->options)) {
            $this->options = get_option($this->optionName);
        }

        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Check data before save in DB
     */
    public function checkBeforeSaveSettings($newValue)
    {
        //if it is not premium plan
        //remove premium settings from array and don't save
        if (!premmerce_pwpf_fs()->can_use_premium_code()) {
            $premiumSettings = array(
                'show_on_sale',
                'show_in_stock',
                'show_rating_filter',
                'permalinks_on'
            );

            foreach ($newValue as $key => $value) {
                if (in_array($key, $premiumSettings)) {
                    unset($newValue[$key]);
                }
            }
        }

        return $newValue;
    }
}
