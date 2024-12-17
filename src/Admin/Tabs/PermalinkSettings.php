<?php namespace Premmerce\Filter\Admin\Tabs;

use Premmerce\Filter\Admin\Tabs\Base\BaseSettings;
use Premmerce\Filter\Filter\Filter;
use Premmerce\Filter\FilterPlugin;
use Premmerce\Filter\Permalinks\PermalinksManager;

class PermalinkSettings extends BaseSettings
{
    /**
     * Page
     *
     * @var string
     */
    protected $page = 'premmerce-filter-admin-permalinks';

    /**
     * Group
     *
     * @var string
     */
    protected $group = 'premmerce_filter_permalinks';

    /**
     * Option Name
     *
     * @var string
     */
    protected $optionName = FilterPlugin::OPTION_PERMALINKS_SETTINGS;

    /**
     * Register hooks
     */
    public function init()
    {
        add_action('admin_init', array($this, 'initSettings'));
        add_action('pre_update_option_' . $this->optionName, array($this, 'checkBeforeSaveSettings'));
    }

    /**
     * Init tab settings
     */
    public function initSettings()
    {
        //in free version we don't have PermalinksManager class
        if (class_exists('PermalinksManager')) {
            $defaultPrefix = PermalinksManager::DEFAULT_PREFIX;
            $defaultOr     = PermalinksManager::DEFAULT_OR;
        } else {
            $defaultPrefix = 'attribute-';
            $defaultOr     = '-or-';
        }

        register_setting(
            $this->group,
            $this->optionName,
            array(
            'sanitize_callback' => array($this, 'sanitize'),
            )
        );

        $permalinkSettings = array(
            'permalinks' => array(
                'label'  => __('Permalinks', 'premmerce-filter'),
                'fields' => array(
                    'permalinks_on' => array(
                        'plan'  => FilterPlugin::PLAN_PREMIUM,
                        'type'  => 'checkbox',
                        'label' => __('Use permalinks', 'premmerce-filter'),
                    ),
                    'discourage_search_all' => array(
                        'plan'  => FilterPlugin::PLAN_PREMIUM,
                        'type'  => 'checkbox',
                        'label' => __('Discourage search engines from indexing pages created by the filter, except for pages with SEO rules.', 'premmerce-filter'),
                        'help' => __('Enable this rule, if you want to hide this page from Google Search Index.', 'premmerce-filter'),
                    ),
                    'slug_prefix'   => array(
                        'plan'        => FilterPlugin::PLAN_PREMIUM,
                        'type'        => 'text',
                        'placeholder' => $defaultPrefix,
                        'title'       => __('Attribute prefix', 'premmerce-filter'),
                    ),
                    'or_separator'  => array(
                        'plan'        => FilterPlugin::PLAN_PREMIUM,
                        'type'        => 'text',
                        'placeholder' => $defaultOr,
                        'title'       => __('Value separator', 'premmerce-filter'),
                    )
                ),
            ),
        );

        foreach (Filter::$taxonomies as $taxonomy) {
            $taxonomy_instance = get_taxonomy($taxonomy);
            $taxonomyName      = $taxonomy_instance->labels->singular_name;
            $slugPrefix        = $this->getOption('slug_prefix') ? $this->getOption('slug_prefix') : $defaultPrefix;
            $placeHolder       = $slugPrefix . $taxonomy . '-';


            $permalinkSettings['permalinks']['fields'][$taxonomy . '_prefix'] = array(
                'type'        => 'text',
                'plan'        => FilterPlugin::PLAN_PREMIUM,
                'placeholder' => $placeHolder,
                'title'       => sprintf(
                    /* translators: %s: prefix name */
                    __('%s prefix', 'premmerce-filter'),
                    $taxonomyName
                ),
            );
        }

        $permalinkSettings = apply_filters('premmerce_filter_permalink_settings_list', $permalinkSettings);
        $this->registerSettings($permalinkSettings, $this->page, $this->optionName);
    }

    /**
     * Sanitize
     *
     * @param array $data
     *
     * @return mixed
     */
    public function sanitize($data)
    {
        $prefixes = array('slug_prefix');

        foreach (Filter::$taxonomies as $taxonomy) {
            $prefixes[] = $taxonomy . '_prefix';
        }

        foreach ($data as $key => &$value) {
            if ('or_separator' === $key) {
                $value = trim(sanitize_title($value), '-');
                $value = $value ? '-' . $value . '-' : '';
            }

            if (in_array($key, $prefixes)) {
                $value = trim(sanitize_title($value), '-');
                $value = $value ? $value . '-' : '';
            }
        }

        $check = array();
        foreach ($prefixes as $prefix) {
            if ($data[$prefix]) {
                if (in_array($data[$prefix], $check)) {
                    add_settings_error('prefix', 'prefix', __('Prefixes should be unique', 'premmerce-filter'));
                    $data[$prefix] = '';
                }
                $check[] = $data[$prefix];
            }
        }

        return $data;
    }

    /**
     * Get Label
     *
     * @return string
     */
    public function getLabel()
    {
        $text = __('Permalinks', 'premmerce-filter');

        $permalinkLabel = BaseSettings::premiumForTabLabel($text);

        return $permalinkLabel;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'permalinks';
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
