<?php namespace Premmerce\Filter\Admin\Tabs;

use Premmerce\Filter\FilterPlugin;
use Premmerce\Filter\Admin\Tabs\Base\BaseSettings;

class SeoSettings extends BaseSettings
{
    /**
     * Page
     *
     * @var string
     */
    protected $page = 'premmerce-filter-admin-seo-settings';

    /**
     * Group
     *
     * @var string
     */
    protected $group = 'premmerce_filter-seo-settings';

    /**
     * Option Name
     *
     * @var string
     */
    protected $optionName = 'premmerce_filter_seo_settings';

    /**
     * Register hooks
     */
    public function init()
    {
        add_action('admin_init', array($this, 'initSettings'));
    }

    /**
     * Init settings
     */
    public function initSettings()
    {
        add_action('premmerce_filter_settings_after_textarea_callback', array($this, 'renderVariableButtons'));
        add_action('premmerce_filter_settings_after_input_callback', array($this, 'renderVariableButtons'));
        add_action('premmerce_filter_settings_after_text_editor_callback', array($this, 'renderVariableButtons'));

        register_setting($this->group, $this->optionName);


        $settings = array(
            'default' => array(
                'label'  => __('Default', 'premmerce-filter'),
                'fields' => array(
                    'use_default_settings' => array(
                        'type'  => 'checkbox',
                        'label' => __('Use default seo settings', 'premmerce-filter'),
                    ),
                ),
            ),
            'meta'    => array(
                'label'    => __('Default Metadata', 'premmerce-filter'),
                'callback' => function () {
                    $sectionDescription = __(
                        'This settings are used to setup default meta data for filter pages that are not defined in rules',
                        'premmerce-filter'
                    );
                    print(wp_kses("<p>{$sectionDescription}</p>", FilterPlugin::HTML_TAGS));
                },
                'fields'   => array(
                    'h1'               => array(
                        'type'  => 'text',
                        'title' => __('H1', 'premmerce-filter'),
                        'id'    => 'rule-h1'
                    ),
                    'title'            => array(
                        'type'  => 'text',
                        'title' => __('Document title', 'premmerce-filter'),
                        'id'    => 'rule-title'
                    ),
                    'meta_description' => array(
                        'type'  => 'textarea',
                        'title' => __('Meta description', 'premmerce-filter'),
                        'id'    => 'rule-meta-description'
                    ),
                    'description'      => array(
                        'type'  => 'editor',
                        'title' => __('Category description', 'premmerce-filter'),
                        'id'    => 'rule-description'
                    ),
                ),
            )
        );

        $this->registerSettings($settings, $this->page, $this->optionName);
    }

    /**
     * Render Variable Buttons
     *
     * @param array $args
     */
    public function renderVariableButtons($args)
    {
        if (isset($args['id']) && in_array(
            $args['id'],
            array('rule-h1', 'rule-title', 'rule-meta-description', 'rule-description')
        )
        ) {
            premmerce_filter_admin_variables('#' . $args['id']);
        }
    }

    /**
     * Get Label
     *
     * @return string
     */
    public function getLabel()
    {
        return __('SEO Settings', 'premmerce-filter');
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'seo_settings';
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
