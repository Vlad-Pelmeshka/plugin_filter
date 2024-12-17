<?php namespace Premmerce\Filter\Admin\Tabs;

use Premmerce\Filter\FilterPlugin;
use Premmerce\Filter\Admin\Tabs\Base\BaseSettings;
use Premmerce\Filter\Shortcodes\FilterWidgetShortcodes;

class Settings extends BaseSettings
{
    /**
     * Page slug
     *
     * @var string
     */
    protected $page = 'premmerce-filter-admin-settings';

    /**
     * Group slug
     *
     * @var string
     */
    protected $group = 'premmerce_filter';

    /**
     * Option Name
     *
     * @var string
     */
    protected $optionName = FilterPlugin::OPTION_SETTINGS;

    /**
     * Register hooks
     */
    public function init()
    {
        add_action('admin_init', array($this, 'initSettings'));
        add_action('pre_update_option_' . $this->optionName, array($this, 'checkBeforeSaveSettings'));
    }

    public static function getMainStaticSettings($middleArray = array())
    {
        $settings = array(
            'behavior' => array(
                'label' => __('Behavior', 'premmerce-filter'),
                'fields' => array(
                    'hide_empty' => array(
                        'plan'  => FilterPlugin::PLAN_FREE,
                        'type'  => 'checkbox',
                        'label' => __('Hide empty terms', 'premmerce-filter'),
                    ),
                    'show_price_filter' => array(
                        'plan'  => FilterPlugin::PLAN_FREE,
                        'type'  => 'checkbox',
                        'label' => __('Show price filter', 'premmerce-filter'),
                    ),
                    'show_reset_filter' => array(
                        'plan'  => FilterPlugin::PLAN_FREE,
                        'type'  => 'checkbox',
                        /* translators: %s: button name */
                        'label' => sprintf(__('Show "%s" button', 'premmerce-filter'), __('Reset filter', 'premmerce-filter')),
                    ),
                    'enable_category_hierarchy' => array(
                        'plan'  => FilterPlugin::PLAN_FREE,
                        'type'  => 'checkbox',
                        'label' => __('Enable category hierarchy', 'premmerce-filter'),
                    ),
                    'expand_category_hierarchy' => array(
                        'plan'  => FilterPlugin::PLAN_FREE,
                        'type'  => 'checkbox',
                        'label' => __('Expand hierarchy by default', 'premmerce-filter'),
                    ),
                    'show_filter_button' => array(
                        'plan'  => FilterPlugin::PLAN_FREE,
                        'type'  => 'checkbox',
                        'label' => __('Show filter button', 'premmerce-filter'),
                    ),
                    'show_on_sale' => array(
                        'plan'  => FilterPlugin::PLAN_PREMIUM,
                        'type'  => 'checkbox',
                        'label' => __('Show "On sale"', 'premmerce-filter'),
                    ),
                    'show_in_stock' => array(
                        'plan'  => FilterPlugin::PLAN_PREMIUM,
                        'type'  => 'checkbox',
                        'label' => __('Show "In stock"', 'premmerce-filter'),
                    ),
                    'show_rating_filter' => array(
                        'plan'  => FilterPlugin::PLAN_PREMIUM,
                        'type'  => 'checkbox',
                        'label' => __('Show rating filter', 'premmerce-filter'),
                    ),
                    'disable_nofollow' => array(
                        'plan'  => FilterPlugin::PLAN_PREMIUM,
                        'type'  => 'checkbox',
                        'label' => __('Disable nofollow on active filter links', 'premmerce-filter'),
                    ),
                ),
            ),
            'show_on_pages' => array(
                'label' => __('Show filter on pages', 'premmerce-filter'),
                'fields' => array(
                    'product_cat' => array(
                        'type' => 'checkbox',
                        'label' => __('Product category', 'premmerce-filter'),
                    ),
                    'tag' => array(
                        'type' => 'checkbox',
                        'label' => __('Tag', 'premmerce-filter'),
                    ),
                    'search' => array(
                        'type' => 'checkbox',
                        'label' => __('Search', 'premmerce-filter'),
                    ),
                    'shop' => array(
                        'type' => 'checkbox',
                        'label' => __('Store', 'premmerce-filter'),
                    ),
                    'attribute' => array(
                        'type' => 'checkbox',
                        'label' => __('Attribute', 'premmerce-filter'),
                    ),
                ),
            ),
            $middleArray,
            'ajax' => array(
                'label' => __('AJAX', 'premmerce-filter'),
                'fields' => array(
                    'load_deferred' => array(
                        'type' => 'checkbox',
                        'label' => __('Load deferred', 'premmerce-filter'),
                    ),
                ),
            ),
            'styles' => array(
                'label' => __('Styles', 'premmerce-filter'),
                'fields' => array(
                    'custom_style_css' => array(
                        'title' => __('Custom css', 'premmerce-filter'),
                        'type' => 'textarea',
                    )
                )

            )
        );

        $brand_taxonomies = apply_filters('premmerce_product_filter_brand_taxonomies', array( 'product_brand' ));
        foreach ($brand_taxonomies as $brand_taxonomy) {
            if (taxonomy_exists($brand_taxonomy)) {
                $brandTaxonomy = get_taxonomy($brand_taxonomy);

                $settings['show_on_pages']['fields'][ $brandTaxonomy->name ] = array(
                    'type'  => 'checkbox',
                    'label' => $brandTaxonomy->label,
                );
            }
        }

        $settings['ajax']['fields']['use_ajax'] = array(
            'type'  => 'checkbox',
            'label' => __('Use ajax', 'premmerce-filter'),
        );

        //shortcodes info
        $instructionTitle    = __('Shortcodes instruction', 'premmerce-filter');
        $instructionContent  = FilterWidgetShortcodes::shortcodeInstruction();
        $buttonShortcodeInfo = "<button type='button' class='button premmerce-shortcode-instruction' id='open-shortcode-info'>{$instructionTitle}</button>";
        $dialogShortcodeInfo = "<div id='shortcode-info-dialog' title='{$instructionTitle}'>{$instructionContent}</div>";

        $canUsePremium = premmerce_pwpf_fs()->can_use_premium_code();
        $premiumLink  = $canUsePremium ? '' : ' <b>' . BaseSettings::premiumLink() . '</b>';
        $premiumClass = $canUsePremium ? '' : 'premmerce_settings_premium_only';

        $settings['shortcodes'] = array(
            'label'  => __('Shortcodes', 'premmerce-filter'),
            'fields' => array(
                'shortcodes_content' => array(
                    'title' => __('Active Shortcodes', 'premmerce-filter'),
                    'type'  => 'info',
                    'class' => $premiumClass,
                    'help'  => __('<b>[premmerce_filter]</b> - for showing main <b>Premmerce Filter</b> (Product attributes filter)', 'premmerce-filter') . $premiumLink
                            . '<br>' . __('<b>[premmerce_active_filters]</b> - for showing main <b>Premmerce active filters</b> (Product attributes active filters)', 'premmerce-filter') . $premiumLink
                            . '<br>' . __('<small>Shortcodes are working only on WooCommerce pages (shop, tags etc)</small>', 'premmerce-filter')
                            . '<br>' . $buttonShortcodeInfo
                            . '<br>' . $dialogShortcodeInfo
                ),
            )
        );

        return $settings;
    }

    /**
     * Init settings
     */
    public function initSettings()
    {
        register_setting($this->group, $this->optionName);

        $taxonomies = FilterPlugin::DEFAULT_TAXONOMIES;

        //This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            $taxonomies = get_taxonomies(array('object_type' => array('product'), 'public' => true));
        }

        $taxonomyOptions = array();

        foreach ($taxonomies as $taxonomy) {
            if (!taxonomy_is_product_attribute($taxonomy) && taxonomy_exists($taxonomy)) {
                $taxonomyOptions [$taxonomy] = get_taxonomy($taxonomy)->labels->singular_name;
            }
        }

        $settings['taxonomies'] = array(
            'label' => __('Taxonomies', 'premmerce-filter'),
            'fields' => array(
                'taxonomies' => array(
                    'title' => __('Use taxonomies', 'premmerce-filter'),
                    'type' => 'select',
                    'options' => $taxonomyOptions,
                    'multiple' => true,
                    'help' => __('Choose taxonomies used by filter.', 'premmerce-filter'),
                    'help_premium' => __('The use of custom taxonomies is only available in the premium version.', 'premmerce-filter'),
                )
            )
        );

        $settings = self::getMainStaticSettings($settings['taxonomies']);

        $strategies = array(
            'woocommerce_content' => __('Woocommerce content', 'premmerce-filter'),
            'product_archive' => __('Product archive', 'premmerce-filter'),
        );

        $currentStrategy = apply_filters('premmerce_filter_ajax_current_strategy', null);

        $configurableStrategies = apply_filters('premmerce_filter_ajax_configurable_strategies', array());

        if (in_array($currentStrategy, $configurableStrategies)) {
            $settings['ajax']['fields']['ajax_strategy'] = array(
                'type' => 'select',
                'title' => __('Ajax Strategy', 'premmerce-filter'),
                'help' =>
                    __(
                        'Choose the strategy for replacing content during ajax product filtering.',
                        'premmerce-filter'
                    )
                    . '<br>' . __(
                        '<b>Woocommerce content</b> strategy - has better performance and supported most of woocommerce themes, where archive page has default woocommerce layout.',
                        'premmerce-filter'
                    )
                    . '<br>' . __(
                        '<b>Product archive</b> strategy - replaces all content placed in product archive template except footer and header.',
                        'premmerce-filter'
                    ),
                'options' => $strategies,
            );
        }

        $this->registerSettings($settings, $this->page, $this->optionName);
    }

    /**
     * Get Label
     *
     * @return string
     */
    public function getLabel()
    {
        return __('Settings', 'premmerce-filter');
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'settings';
    }

    /**
     * Valid
     *
     * @return bool
     */
    public function valid()
    {
        return true;
    }
}
