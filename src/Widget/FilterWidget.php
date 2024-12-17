<?php namespace Premmerce\Filter\Widget;

use WP_Widget;
use Premmerce\Filter\FilterPlugin;
use Premmerce\Filter\Filter\Container;

class FilterWidget extends WP_Widget
{
    const FILTER_WIDGET_ID = 'premmerce_filter_filter_widget';

    /**
     * FilterWidget constructor.
     */
    public function __construct()
    {
        parent::__construct(
            self::FILTER_WIDGET_ID,
            __('Premmerce Filter', 'premmerce-filter'),
            array(
                'description' => __('Product attributes filter', 'premmerce-filter'),
            )
        );
    }

    /**
     * Render widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance)
    {
        if (apply_filters('premmerce_product_filter_active', false)) {
            $data = self::getFilterWidgetContent($args, $instance);
            do_action('premmerce_product_filter_render', $data);
        }
    }

    /**
     * Get Filter Widget data
     *
     * @param array $args
     * @param array $instance
     */
    public static function getFilterWidgetContent($args = array(), $instance = array())
    {
        $items            = Container::getInstance()->getItemsManager()->getFilters();
        $items            = apply_filters('premmerce_product_filter_items', $items);
        $settings         = get_option(FilterPlugin::OPTION_SETTINGS, array());
        $style            = isset($instance['style']) ? $instance['style'] : 'default';
        $showFilterButton = !empty($settings['show_filter_button']);

        //default styles
        $border          = '';
        $boldTitle       = '';
        $titleAppearance = '';

        //premmerce styles
        if ('default' !== $style) {
            //border styles

            if ((isset($instance['add_border']) && ('on' === $instance['add_border'] || true === $instance['add_border'])) || ('premmerce' === $style)) {
                $border = ' filter__item-border';
            }

            //title styles
            if ((isset($instance['bold_title']) && ('on' === $instance['bold_title'] || true === $instance['bold_title'])) || ('premmerce' === $style)) {
                $boldTitle = 'bold';
            }
            if ((isset($instance['title_appearance']) && 'uppercase' === $instance['title_appearance']) || ('premmerce' === $style)) {
                $titleAppearance = 'uppercase';
            }
        }

        $data = array(
            'args'             => $args,
            'style'            => $style,
            'showFilterButton' => $showFilterButton,
            'attributes'       => $items,
            'formAction'       => apply_filters('premmerce_product_filter_form_action', ''),
            'instance'         => $instance,
            'border'           => $border,
            'boldTitle'        => $boldTitle,
            'titleAppearance'  => $titleAppearance
        );

        return $data;
    }

    /**
     * Update
     *
     * @param array $new_instance
     * @param array $old_instance
     *
     * @return array
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();

        $instance['title'] = filter_var($new_instance['title'], FILTER_SANITIZE_STRING);
        $instance['style'] = filter_var($new_instance['style'], FILTER_SANITIZE_STRING);

        // This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            //border styles
            $instance['add_border']   = isset($new_instance['add_border']) ? filter_var($new_instance['add_border'], FILTER_SANITIZE_STRING) : '';
            $instance['border_color'] = isset($new_instance['border_color']) ? filter_var($new_instance['border_color'], FILTER_SANITIZE_STRING) : '';

            //Filter by Price
            $instance['price_input_bg']      = isset($new_instance['price_input_bg']) ? filter_var($new_instance['price_input_bg'], FILTER_SANITIZE_STRING) : '';
            $instance['price_input_text']    = isset($new_instance['price_input_text']) ? filter_var($new_instance['price_input_text'], FILTER_SANITIZE_STRING) : '';
            $instance['price_slider_range']  = isset($new_instance['price_slider_range']) ? filter_var($new_instance['price_slider_range'], FILTER_SANITIZE_STRING) : '';
            $instance['price_slider_handle'] = isset($new_instance['price_slider_handle']) ? filter_var($new_instance['price_slider_handle'], FILTER_SANITIZE_STRING) : '';

            //Appearance
            $instance['checkbox_appearance'] = isset($new_instance['checkbox_appearance']) ? filter_var($new_instance['checkbox_appearance'], FILTER_SANITIZE_STRING) : 'ballotBox';
            $instance['title_appearance']    = isset($new_instance['title_appearance']) ? filter_var($new_instance['title_appearance'], FILTER_SANITIZE_STRING) : 'default';

            //title and background
            $instance['bold_title'] = isset($new_instance['bold_title']) ? filter_var($new_instance['bold_title'], FILTER_SANITIZE_STRING) : '';
            $instance['bg_color']   = isset($new_instance['bg_color']) ? filter_var($new_instance['bg_color'], FILTER_SANITIZE_STRING) : '';

            //title styles
            $instance['title_size']  = isset($new_instance['title_size']) ? filter_var($new_instance['title_size'], FILTER_SANITIZE_STRING) : '';
            $instance['title_color'] = isset($new_instance['title_color']) ? filter_var($new_instance['title_color'], FILTER_SANITIZE_STRING) : '';

            //terms styles
            $instance['terms_title_size']  = isset($new_instance['terms_title_size']) ? filter_var($new_instance['terms_title_size'], FILTER_SANITIZE_STRING) : '';
            $instance['terms_title_color'] = isset($new_instance['terms_title_color']) ? filter_var($new_instance['terms_title_color'], FILTER_SANITIZE_STRING) : '';

            //checkboxes styles
            $instance['checkbox_border_color'] = isset($new_instance['checkbox_border_color']) ? filter_var($new_instance['checkbox_border_color'], FILTER_SANITIZE_STRING) : '';
            $instance['checkbox_color']        = isset($new_instance['checkbox_color']) ? filter_var($new_instance['checkbox_color'], FILTER_SANITIZE_STRING) : '';
        }

        return $instance;
    }

    /**
     * Form
     *
     * @param array $instance
     *
     * @return string|void
     */
    public function form($instance)
    {
        $settings = get_option(FilterPlugin::OPTION_SETTINGS, array());
        //check plan
        $premiumOnly = !premmerce_pwpf_fs()->can_use_premium_code() ? __(' (Premium)', 'premmerce-filter') : '';
        $currentPlan = !premmerce_pwpf_fs()->can_use_premium_code() ? FilterPlugin::PLAN_FREE : FilterPlugin::PLAN_PREMIUM;
        //options from settings page
        $filterStyles = array('default' => __('Default', 'premmerce-filter'), 'premmerce' => 'Premmerce');
        //add custom option
        $filterStyles['custom'] = 'Custom' . $premiumOnly;

        //default variables
        $checkboxAppVariables = array(
            '0'    => 'BALLOT BOX',
            '2713' => 'BALLOT BOX WITH CHECK',
            '2715' => 'BALLOT BOX WITH X',
        );
        $titleAppVariables    = array(
            'default'   => 'Default',
            'uppercase' => 'Uppercase',
        );

        do_action('premmerce_product_filter_widget_form_render', array(
            'settings'             => $settings,
            'title'                => isset($instance['title']) ? $instance['title'] : '',
            'filterStyles'         => $filterStyles,
            'style'                => isset($instance['style']) ? $instance['style'] : '',
            'addBorder'            => isset($instance['add_border']) ? $instance['add_border'] : 'on',
            'borderColor'          => isset($instance['border_color']) ? $instance['border_color'] : '',
            'priceInputBg'         => isset($instance['price_input_bg']) ? $instance['price_input_bg'] : '',
            'priceInputText'       => isset($instance['price_input_text']) ? $instance['price_input_text'] : '',
            'priceSliderRange'     => isset($instance['price_slider_range']) ? $instance['price_slider_range'] : '',
            'priceSliderHandle'    => isset($instance['price_slider_handle']) ? $instance['price_slider_handle'] : '',
            'checkboxAppVariables' => $checkboxAppVariables,
            'checkboxAppearance'   => isset($instance['checkbox_appearance']) ? $instance['checkbox_appearance'] : '',
            'titleAppVariables'    => $titleAppVariables,
            'titleAppearance'      => isset($instance['title_appearance']) ? $instance['title_appearance'] : 'default',
            'boldTitle'            => isset($instance['bold_title']) ? $instance['bold_title'] : '',
            'titleSize'            => isset($instance['title_size']) ? $instance['title_size'] : '',
            'titleColor'           => isset($instance['title_color']) ? $instance['title_color'] : '',
            'termsTitleSize'       => isset($instance['terms_title_size']) ? $instance['terms_title_size'] : '',
            'termsTitleColor'      => isset($instance['terms_title_color']) ? $instance['terms_title_color'] : '',
            'bgColor'              => isset($instance['bg_color']) ? $instance['bg_color'] : '',
            'checkboxColor'        => isset($instance['checkbox_color']) ? $instance['checkbox_color'] : '',
            'checkboxBorderColor'  => isset($instance['checkbox_border_color']) ? $instance['checkbox_border_color'] : '',
            'currentPlan'          => $currentPlan,
            'widget'               => $this,
        ));
    }

    /**
     * Render ColorPicker for widget
     */
    public static function renderWidgetInput($widget, $id, $title, $value, $class, $type = 'text', $plan = 'premium')
    {
        $checkbox = '<p><label for="%1$s">%2$s</label><input class="widefat %3$s %4$s" type="%5$s" name="%6$s" id="%7$s" value="%8$s" %9$s></p>';
        $fieldID  = esc_attr($widget->get_field_id($id));

        $disabled = '';
        //if it is not premium plan - disable input
        if (FilterPlugin::PLAN_FREE === $plan) {
            $disabled = 'disabled';
        }

        printf(
            '<p><label for="%1$s">%2$s</label><input class="widefat %3$s %4$s" type="%5$s" name="%6$s" id="%7$s" value="%8$s" %9$s></p>',
            esc_attr($fieldID),
            esc_attr($title),
            esc_attr($class),
            esc_attr($disabled),
            esc_attr($type),
            esc_attr($widget->get_field_name($id)),
            esc_attr($fieldID),
            esc_attr($value),
            esc_attr($disabled)
        );
    }

    /**
     * Render checkbox for widget
     */
    public static function renderWidgetCheckbox($widget, $id, $title, $value, $plan)
    {
        $checked = checked($value, 'on', false);
        $fieldID = esc_attr($widget->get_field_id($id));

        $disabled = '';
        //if it is not premium plan - disable checkbox
        if (FilterPlugin::PLAN_FREE === $plan) {
            $disabled = 'disabled';
        }

        printf(
            '<p><input class="widefat" type="checkbox" name="%1$s" id="%2$s" %3$s %4$s><label for="%5$s">%6$s</label></p>',
            esc_attr($widget->get_field_name($id)),
            esc_attr($fieldID),
            esc_attr($checked),
            esc_attr($disabled),
            esc_attr($fieldID),
            esc_attr($title)
        );
    }

    /**
     * Render select for widget
     */
    public static function renderWidgetSelect($widget, $id, $title, $value, $options, $class = '', $plan = 'premium')
    {
        $fieldID = esc_attr($widget->get_field_id($id));

        $disabled = '';
        //if it is not premium plan - disable select
        if (FilterPlugin::PLAN_FREE === $plan) {
            $disabled = 'disabled';
        }

        printf(
            '<p><label for="%1$s">%2$s</label><select name="%3$s" class="widefat %4$s" id="%5$s" %6$s></p>',
            esc_attr($fieldID),
            esc_attr($title),
            esc_attr($widget->get_field_name($id)),
            esc_attr($class),
            esc_attr($fieldID),
            esc_attr($disabled)
        );

        foreach ($options as $key => $option) {
            $selected = $key === $value ? 'selected' : '';
            printf(
                '<option value="%1$s" %2$s>%3$s</option>',
                esc_attr($key),
                esc_attr($selected),
                esc_attr($option)
            );
        }

        print('</select>');
    }

    /**
     * Render current widget inline styles
     *
     * This method will be only included in the premium version.
     */
    public static function renderWidgetInlineStyles__premium_only()
    {
        $widgetOptionName = 'widget_' . self::FILTER_WIDGET_ID;
        $widgetsData      = get_option($widgetOptionName);
        $allWidgetsStyles = '';

        //for premium only
        if (premmerce_pwpf_fs()->can_use_premium_code()) {
            foreach ($widgetsData as $key => $styles) {
                $widgetId          = self::FILTER_WIDGET_ID . '-' . $key;
                $allWidgetsStyles .= self::renderInlineStyles__premium_only($styles, $widgetId);
            }
        }
        return $allWidgetsStyles;
    }

    /**
     * Render inline styles and we can use this for widget or shortcodes
     *
     * This method will be only included in the premium version.
     */
    public static function renderInlineStyles__premium_only($styles = array(), $id)
    {
        $allWidgetsInlineStyles   = '';
        $bgColorStyles            = '';
        $filterBorderStyles       = '';
        $borderColorStyles        = '';
        $borderStyles             = '';
        $checkboxStyles           = '';
        $titleStyles              = '';
        $titleAppearanceStyles    = '';
        $termsTitleStyles         = '';
        $checkboxAppearance       = '';
        $checkboxAppearanceStyles = '';
        $priceStyles              = '';

        if (!empty($styles['style']) && 'custom' === $styles['style']) {
            //background styles
            if (!empty($styles['bg_color'])) {
                $bgColorSelector = "#{$id} .premmerce-filter-body";
                $bgColorCss      = "background-color: {$styles['bg_color']};";
                $bgColorStyles   = "{$bgColorSelector} { {$bgColorCss} } ";
            }

            //border styles
            if (!empty($styles['add_border']) && ('on' === $styles['add_border'] || true === $styles['add_border'])) {
                $filterBorderSelector = "#{$id} .filter__item";
                $filterBorderCss      = 'border: 1px solid #c7c7c7;';
                $filterBorderStyles   = "{$filterBorderSelector} { {$filterBorderCss} } ";
            }
            //border color (if border trun on)
            if (!empty($styles['border_color']) && ('on' === $styles['add_border'] || true === $styles['add_border'])) {
                $borderColorSelector = "#{$id} .filter__item";
                $borderColorCss      = "border-color: {$styles['border_color']};";
                $borderColorStyles   = "{$borderColorSelector} { {$borderColorCss} } ";
            }

            //title styles
            if (!empty($styles['title_size']) || !empty($styles['title_color']) || !empty($styles['bold_title'])) {
                $titleSelector = "#{$id} .filter__title";

                $titleSize  = !empty($styles['title_size']) ? 'font-size:' . $styles['title_size'] . 'px;' : '';
                $titleColor = !empty($styles['title_color']) ? 'color:' . $styles['title_color'] . ';' : '';
                $titleBold  = !empty($styles['bold_title'] && ('on' === $styles['bold_title'] || true === $styles['bold_title'])) ? 'font-weight: bold;' : '';

                $titleDropdownSelector = "#{$id} .filter__icon-minus:before, #{$id} .filter__icon-plus:before, #{$id} .filter__icon-plus:after";
                $titleDropdownColor    = !empty($styles['title_color']) ? 'background:' . $styles['title_color'] . ';' : '';

                $titleStyles = "{$titleSelector} { {$titleSize} {$titleColor} {$titleBold} } $titleDropdownSelector { {$titleDropdownColor} }";
            }

            if (!empty($styles['title_appearance']) || (!empty($styles['style']) && 'premmerce' === $styles['style'])) {
                $titleAppearanceSelector = "#{$id} .filter__title";

                $titleAppearance = '';
                if ((!empty($styles['title_appearance']) && 'uppercase' === $styles['title_appearance']) || 'premmerce' === $styles['style']) {
                    $titleAppearance = 'text-transform: uppercase;';
                }

                $titleAppearanceStyles = "{$titleAppearanceSelector} { {$titleAppearance} }";
            }


            //terms styles
            if (!empty($styles['terms_title_size']) || !empty($styles['terms_title_color'])) {
                $termsTitleSelector  = "#{$id} .filter__checkgroup-count, ";
                $termsTitleSelector .= "#{$id} .filter__checkgroup-title, ";
                $termsTitleSelector .= "#{$id} .filter__inner-hierarchy-button-open-close, ";
                $termsTitleSelector .= "#{$id} .filter__label-button, ";
                $termsTitleSelector .= "#{$id} .filter__select";
                $termsTitleSize      = !empty($styles['terms_title_size']) ? 'font-size:' . $styles['terms_title_size'] . 'px;' : '';
                $termsTitleColor     = !empty($styles['terms_title_color']) ? 'color:' . $styles['terms_title_color'] . ';' : '';

                //terms checkbox/radio styles
                $termsCheckboxSelector = "#{$id} .filter__checkgroup-check";
                $termsCheckboxSize     = !empty($styles['terms_title_size']) ? 'width:' . $styles['terms_title_size'] . 'px;' : '';
                $termsCheckboxSize    .= !empty($styles['terms_title_size']) ? 'height:' . $styles['terms_title_size'] . 'px;' : '';

                $termsCheckboxSizeSelector = "#{$id} .filter__checkgroup-check::before";
                $termsCheckboxCss          = "font-size: {$styles['terms_title_size']}px;";

                $termsTitleStyles  = "{$termsTitleSelector} { {$termsTitleSize} {$termsTitleColor} } {$termsCheckboxSelector} { {$termsCheckboxSize} } ";
                $termsTitleStyles .= "{$termsCheckboxSizeSelector} { {$termsCheckboxCss} }";
            }

            //checkbox border styles
            if (!empty($styles['checkbox_border_color'])) {
                $borderSelector  = "#{$id} .filter__checkgroup-check, ";
                $borderSelector .= "#{$id} .filter__label-button, ";
                $borderSelector .= "#{$id} .filter__select ";
                $borderCss       = "border-color: {$styles['checkbox_border_color']};";

                $borderSelectorForColorImage  = "#{$id} .filter__checkgroup-control:checked + .filter__color-button, ";
                $borderSelectorForColorImage .= "#{$id} .filter__checkgroup-control:not([disabled]) + .filter__color-button:hover";

                $borderCssForColorImages = "outline-color: {$styles['checkbox_border_color']};";

                $borderStyles = "{$borderSelector} { {$borderCss} } {$borderSelectorForColorImage} { {$borderCssForColorImages} }";
            }

            //checkbox color styles
            if (!empty($styles['checkbox_color'])) {
                $checkboxSelector = "#{$id} .filter__checkgroup-check::before";
                $checkboxCss      = "background: {$styles['checkbox_color']};";

                $checkboxSelectorForColorImage = "#{$id} .filter__checkgroup-control:checked+.filter__color-button::before";
                $checkboxCssForColorImages     = "color: {$styles['checkbox_color']};";

                $checkboxStyles = "{$checkboxSelector} { {$checkboxCss} } {$checkboxSelectorForColorImage} { {$checkboxCssForColorImages} } ";
            }

            //checkbox Appearance styles
            if (isset($styles['checkbox_appearance'])) {
                $checkboxAppSelector  = "#{$id} .filter__checkgroup-link .filter__checkgroup-control:checked+.filter__color-button::before, ";
                $checkboxAppSelector .= "#{$id} .filter__checkgroup-link .filter__checkgroup-control[type='radio']:checked+.filter__checkgroup-check:before, ";
                $checkboxAppSelector .= "#{$id} .filter__checkgroup-link .filter__checkgroup-control[type='checkbox']:checked+.filter__checkgroup-check:before";

                $checkboxAppAddSelector  = "#{$id} .filter__checkgroup-control:checked+.filter__checkgroup-check::before, ";
                $checkboxAppAddSelector .= "#{$id} .filter__checkgroup-control:checked+.filter__color-button::before ";

                $checkboxAppAddCss     = '';
                $checkboxAppearanceCss = '';

                if ('0' !== $styles['checkbox_appearance']) {
                    $checkboxAppearanceCss .= 'background: none;';
                    $checkboxAppearanceCss .= 'width: auto;';
                    $checkboxAppearanceCss .= 'height: auto;';
                    if (!empty($styles['checkbox_color'])) {
                        $checkboxAppearanceCss .= "color: {$styles['checkbox_color']};";
                    }
                    $checkboxAppAddCss = "content: '\\{$styles['checkbox_appearance']}';";
                } elseif ('0' === $styles['checkbox_appearance']) {
                    if (isset($styles['terms_title_size']) && is_numeric($styles['terms_title_size'])) {
                        $checkboxAppearanceCss .= 'width:' . ($styles['terms_title_size'] - 6) . 'px;';
                        $checkboxAppearanceCss .= 'height:' . ($styles['terms_title_size'] - 6) . 'px;';
                    }
                }

                $checkboxAppearanceStyles = "{$checkboxAppSelector} { {$checkboxAppearanceCss} } {$checkboxAppAddSelector} { {$checkboxAppAddCss} }";
            }

            //price filter styles
            if (!empty($styles['price_input_bg'])) {
                $priceInputBgSelector = "#{$id} .filter__slider-control-column .filter__slider-control";
                $priceInputBgCss      = "background-color: {$styles['price_input_bg']};";

                $priceStyles .= "{$priceInputBgSelector} { {$priceInputBgCss} } ";
            }
            if (!empty($styles['price_input_text'])) {
                $priceInputTextSelector = "#{$id} .filter__slider-control-column .filter__slider-control";
                $priceInputTextCss      = "color: {$styles['price_input_text']};";

                $priceStyles .= "{$priceInputTextSelector} { {$priceInputTextCss} } ";
            }
            if (!empty($styles['price_slider_range'])) {
                $priceSliderRangeSelector = "#{$id} .filter__range-slider .pc-range-slider__control .ui-slider-range";
                $priceSliderRangeCss      = "background: {$styles['price_slider_range']};";

                $priceStyles .= "{$priceSliderRangeSelector} { {$priceSliderRangeCss} } ";
            }
            if (!empty($styles['price_slider_handle'])) {
                $priceSliderHandleSelector = "#{$id} .filter__range-slider .pc-range-slider__control .ui-slider-handle";
                $priceSliderHandleCss      = "background: {$styles['price_slider_handle']};";
                $priceSliderHandleCss     .= "border-color: {$styles['price_slider_handle']};";

                $priceStyles .= "{$priceSliderHandleSelector} { {$priceSliderHandleCss} } ";
            }

            //all styles in one inline style
            $allWidgetsInlineStyles .= $bgColorStyles . $borderColorStyles . $filterBorderStyles . $borderStyles;
            $allWidgetsInlineStyles .= $titleStyles . $titleAppearanceStyles . $termsTitleStyles . $checkboxStyles;
            $allWidgetsInlineStyles .= $priceStyles . $checkboxAppearanceStyles;
        }

        return $allWidgetsInlineStyles;
    }
}
