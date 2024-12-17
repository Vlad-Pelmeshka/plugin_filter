<?php namespace Premmerce\Filter\Admin\Tabs;

use Premmerce\Filter\Admin\Tabs\Base\SortableListTab;
use Premmerce\Filter\Filter\Filter;
use Premmerce\Filter\FilterPlugin;
use Premmerce\SDK\V2\FileManager\FileManager;

class Attributes extends SortableListTab
{
    /**
     * File Manager
     *
     * @var FileManager
     */
    private $fileManager;

    /**
     * Default Attribute
     *
     * @var array
     */
    private $defaultAttribute = array('active' => false, 'type' => 'checkbox', 'display_type' => '');

    /**
     * Premium Attributes
     *
     * @var array
     */
    private $premiumAttributes = array('show_on_sale', 'show_in_stock', 'show_rating_filter');

    /**
     * Premium Types
     *
     * @var array
     */
    private $premiumTypes = array(
        FilterPlugin::TYPE_COLOR,
        FilterPlugin::TYPE_IMAGE,
        FilterPlugin::TYPE_SLIDER,
        FilterPlugin::TYPE_LABEL
    );

    /**
     * Premium Display
     *
     * @var array
     */
    private $premiumDisplay = array(
        'display_dropdown_hover'
    );

    /**
     * Attributes constructor.
     *
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        parent::__construct();
        $this->fileManager = $fileManager;
        $this->bulkActions = apply_filters('premmerce_filter_bulk_actions_attributes', $this->bulkActions);
    }

    /**
     * Register action handlers
     */
    public function init()
    {
        add_action('wp_ajax_premmerce_filter_bulk_action_attributes', array($this, 'bulkActionAttributes'));
        add_action('wp_ajax_premmerce_filter_sort_attributes', array($this, 'sortAttributes'));

        add_filter('premmerce_filter_display', array($this, 'extendItemDisplay'));
        add_filter('premmerce_filter_item_types', array($this, 'extendItemTypes'));
        add_action('pre_update_option_' . FilterPlugin::OPTION_ATTRIBUTES, array($this, 'checkBeforeSaveOption'));

        //This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            add_action('wp_ajax_premmerce_filter_get_colors', array($this, 'getColors__premium_only'));
            add_action('wp_ajax_premmerce_filter_save_colors', array($this, 'saveColors__premium_only'));
            add_action('wp_ajax_premmerce_filter_get_images', array($this, 'getImages__premium_only'));
            add_action('wp_ajax_premmerce_filter_save_images', array($this, 'saveImages__premium_only'));
            add_action('woocommerce_attribute_deleted', array($this, 'onDeleteAttribute__premium_only'), 10, 3);
        }
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'attributes';
    }

    /**
     * Get Label
     *
     * @return string
     */
    public function getLabel()
    {
        return __('Attributes', 'premmerce-filter');
    }

    /**
     * Valid
     *
     * @return bool
     */
    public function valid()
    {
        return function_exists('wc_get_attribute_taxonomies');
    }

    /**
     * Check Before Save Option
     *
     * @param  mixed $newValue
     * @return void
     */
    public function checkBeforeSaveOption($newValue)
    {
        //check if it isn't premium - change premium type to default (checkbox)
        if (!premmerce_pwpf_fs()->can_use_premium_code()) {
            foreach ($newValue as $key => $value) {
                if (in_array($value['type'], $this->premiumTypes)) {
                    $newValue[$key]['type'] = 'checkbox';
                }
            }
        }

        return $newValue;
    }

    /**
     * Extend items types
     *
     * @param  mixed $types
     * @return void
     */
    public function extendItemTypes($types)
    {
        $premiumText = '';

        //if it is not premium plan - show text Premium (mean only in Premium)
        if (!premmerce_pwpf_fs()->can_use_premium_code()) {
            $premiumText = ' (' . __('Premium', 'premmerce-filter') . ')';
        }

        //premium types list
        $premiumTypes = $this->premiumTypes;

        //add to main types array
        foreach ($premiumTypes as $premType) {
            $types[$premType] = array(
                'plan' => FilterPlugin::PLAN_PREMIUM,
                'text' => __(ucfirst($premType), 'premmerce-filter') . $premiumText
            );
        }

        return $types;
    }

    /**
     * Extend item Display
     *
     * @param  mixed $display
     * @return void
     */
    public function extendItemDisplay($display)
    {
        $premiumText = '';

        //if it is not premium plan - show text Premium (mean only in Premium)
        if (!premmerce_pwpf_fs()->can_use_premium_code()) {
            $premiumText = ' (' . __('Premium', 'premmerce-filter') . ')';
        }

        //premium types list
        $premiumDisplay = $this->premiumDisplay;

        //add to main types array
        foreach ($premiumDisplay as $premDisplay) {
            $display[$premDisplay] = array(
                'plan' => FilterPlugin::PLAN_PREMIUM,
                'text' => __(ucfirst($display[$premDisplay]['text']), 'premmerce-filter') . $premiumText
            );
        }

        return $display;
    }

    /**
     * Swap Items
     *
     * @param  mixed $swap
     * @param  mixed $actual
     * @return void
     */
    public function swapItems($swap, $actual)
    {
        $actualKeys = array_keys($actual);

        $place  = $swap[0];
        $target = $swap[1];

        $placePos  = array_search($place, $actualKeys);
        $targetPos = array_search($target, $actualKeys);

        if ($placePos < $targetPos) {
            //        if place is before move target before place
            $items = array(
                $target => $actual[$target],
                $place  => $actual[$place],
            );
        } else {
            //        if place is after move target after place
            $items = array(
                $place  => $actual[$place],
                $target => $actual[$target],
            );
        }
        unset($actual[$swap[1]]);

        $before = array_slice($actual, 0, $placePos, true);
        $after  = array_slice($actual, $placePos, null, true);

        return $before + $items + $after;
    }

    public function sortItems($ids, $actual)
    {
        $actualKeys = array_keys($actual);

        $prevKeyPosition = 0;
        $before          = array();
        $after           = array();

        $prev = isset($_POST['prev']) && isset($_POST['ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_POST['ajax_nonce']), 'filter-ajax-nonce') ? wc_clean(wp_unslash($_POST['prev'])) : null;
        if (! empty($prev)) {
            $prevKeyPosition = array_search($prev, $actualKeys);
            $before          = array_slice($actual, 0, $prevKeyPosition ? $prevKeyPosition + 1 : 0, true);
        }

        $next = isset($_POST['next']) ? wc_clean(wp_unslash($_POST['next'])) : null;
        if (! empty($next)) {
            $nextKeyPosition = array_search($next, $actualKeys);
            $after           = array_slice($actual, $nextKeyPosition, null, true);
        }


        $sorted = array_slice($actual, $prevKeyPosition ? $prevKeyPosition + 1 : 0, count($ids), true);

        $ids = array_combine($ids, $ids);

        $sorted = array_replace($ids, $sorted);

        return $before + $sorted + $after;
    }

    /**
     * Ajax update attributes ordering
     */
    public function sortAttributes()
    {
        $actual = $this->getAttributesConfig();

        $items = array();
        $swap  = isset($_POST['swap']) && isset($_POST['ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_POST['ajax_nonce']), 'filter-ajax-nonce') ? wc_clean(wp_unslash($_POST['swap'])) : null;
        $ids   = isset($_POST['ids']) && isset($_POST['ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_POST['ajax_nonce']), 'filter-ajax-nonce') ? wc_clean(wp_unslash($_POST['ids'])) : null;

        if (!empty($swap)) {
            $swap = explode(',', $swap);
            $swap = array_filter($swap);

            if (count($swap) === 2) {
                $items = $this->swapItems($swap, $actual);
            }
        } elseif (!empty($ids)) {
            if (is_array($ids)) {
                $items = $this->sortItems($ids, $actual);
            }
        }

        if (count($items) === count($actual)) {
            update_option(FilterPlugin::OPTION_ATTRIBUTES, $items);
        }
        wp_die();
    }

    /**
     * Ajax bulk update attributes
     */
    public function bulkActionAttributes()
    {
        $this->bulkActionsHandler(FilterPlugin::OPTION_ATTRIBUTES, $this->getAttributesConfig());
    }

    public function render()
    {
        $attributesConfig  = $this->getAttributesConfig();
        $attributes        = array_replace($attributesConfig, $this->getAttributes());
        $premiumAttributes = $this->premiumAttributes;

        //pagination data
        $paginationData = $this->paginationDataForTabs($attributes);
        //new data from paginationArgsForTabs()
        $attributes     = $paginationData['attr'];
        $paginationArgs = $paginationData['args'];
        $prevId         = $paginationData['prevId'];
        $nextId         = $paginationData['nextId'];

        $visibility = array(
            'display' => array(
                'plan' => FilterPlugin::PLAN_FREE,
                'text' => __('Display', 'premmerce-filter')
            ),
            'hide'    => array(
                'plan' => FilterPlugin::PLAN_FREE,
                'text' => __('Hide', 'premmerce-filter')
            )
        );

        $types = array(
            'checkbox' => array(
                'plan' => FilterPlugin::PLAN_FREE,
                'text' => __('Checkbox', 'premmerce-filter')
            ),
            'radio'    => array(
                'plan' => FilterPlugin::PLAN_FREE,
                'text' => __('Radio', 'premmerce-filter')
            ),
            'select'   => array(
                'plan' => FilterPlugin::PLAN_FREE,
                'text' => __('Select', 'premmerce-filter')
            )
        );

        //add premium types from extendItemTypes()
        $types = apply_filters('premmerce_filter_item_types', $types);

        $display = array(
            'display_' => array(
                'plan' => FilterPlugin::PLAN_FREE,
                'text' => __('Default', 'premmerce-filter')
            ),
            'display_dropdown' => array(
                'plan' => FilterPlugin::PLAN_FREE,
                'text' => __('Dropdown', 'premmerce-filter')
            ),
            'display_scroll' => array(
                'plan' => FilterPlugin::PLAN_FREE,
                'text' => __('Scroll', 'premmerce-filter')
            ),
            'display_scroll_dropdown' => array(
                'plan' => FilterPlugin::PLAN_FREE,
                'text' => __('Scroll + Dropdown', 'premmerce-filter')
            ),
            'display_dropdown_hover' => array(
                'plan' => FilterPlugin::PLAN_PREMIUM,
                'text' => __('Hoverable Dropdown', 'premmerce-filter')
            )
        );

        $display = apply_filters('premmerce_filter_display', $display);

        $actions = array(
            '-1'                                 => __('Bulk Actions', 'premmerce-filter'),
            __('Visibility', 'premmerce-filter') => $visibility,
            __('Field type', 'premmerce-filter') => $types,
            __('Display as', 'premmerce-filter') => $display,
        );

        $actions = apply_filters('premmerce_filter_item_actions', $actions);

        $dataAction = 'premmerce_filter_bulk_action_attributes';

        $this->fileManager->includeTemplate(
            'admin/tabs/attributes.php',
            compact('attributes', 'premiumAttributes', 'attributesConfig', 'types', 'actions', 'dataAction', 'display', 'paginationArgs', 'prevId', 'nextId')
        );
    }

    /**
     * Remove color and image options when attribute deleted
     *
     * @param int    $id
     * @param string $attribute_name
     * @param string $taxonomy
     */
    public function onDeleteAttribute__premium_only($id, $attribute_name, $taxonomy)
    {
        $this->removeOptionsByType__premium_only($taxonomy, 'colors');
        $this->removeOptionsByType__premium_only($taxonomy, 'images');
    }

    /**
     * Ajax general method for saving images and colors
     */
    public static function saveOption__premium_only($type)
    {
        $taxonomy = isset($_POST['taxonomy']) && isset($_POST['ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_POST['ajax_nonce']), 'filter-ajax-nonce') ? wc_clean(wp_unslash($_POST['taxonomy'])) : null;
        $data     = isset($_POST['data']) && isset($_POST['ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_POST['ajax_nonce']), 'filter-ajax-nonce') ? wc_clean(wp_unslash($_POST['data'])) : null;

        if (taxonomy_exists($taxonomy) && is_array($data)) {
            $getOption = get_option(FilterPlugin::OPTION_ . $type, array());

            foreach ($data as $item) {
                $getOption[$taxonomy][$item['id']] = $item['value'];
            }
            update_option(FilterPlugin::OPTION_ . $type, $getOption);
        }
    }

    /**
     * Ajax save colors
     */
    public function saveColors__premium_only()
    {
        self::saveOption__premium_only('colors');
    }

    /**
     * Ajax save colors
     */
    public function saveImages__premium_only()
    {
        self::saveOption__premium_only('images');
    }

    /**
     * Пeneral function for images and colors
     *
     * @param  mixed $type
     * @return void
     */
    public static function getTaxonomyData($type)
    {
        //if id - is number - take taxonomy slug, else take id (it is already slug)
        $id       = isset($_GET['id']) ? wc_clean(wp_unslash($_GET['id'])) : null;
        $taxonomy = is_numeric($id) ? self::getTaxonomyById((int) $id) : $id;
        $taxonomy = get_taxonomy($taxonomy);

        if ($taxonomy) {
            $terms = get_terms(array(
                'taxonomy'   => $taxonomy->name,
                'hide_empty' => false,
                'fields'     => 'id=>name',
            ));

            if ($terms instanceof \WP_Error) {
                $terms = array();
            }

            $output = array(
                'taxonomyName'  => $taxonomy->name,
                'taxonomyLabel' => $taxonomy->label,
                'fieldType'     => $type,
                'results'       => array()
            );

            //get data from wp_option by type name
            $typeData = get_option(FilterPlugin::OPTION_ . $type, array());

            //if isset
            $data = isset($typeData[$taxonomy->name]) ? $typeData[$taxonomy->name] : array();

            foreach ($terms as $id => $text) {
                $value       = isset($data[$id]) ? $data[$id] : null;
                $term_result = array(
                    'id'    => $id,
                    'text'  => $text,
                    'value' => $value
                );

                if ('images' === $type) {
                    $term_result['img_url'] = wp_get_attachment_image_url($value, 'thumbnail');
                }

                $output['results'][] = $term_result;
            }

            echo json_encode($output);
            wp_die();
        }
    }

    /**
     * Ajax get terms for image settings
     */
    public function getImages__premium_only()
    {
        self::getTaxonomyData('images');
    }

    /**
     * Ajax get terms for color settings
     */
    public function getColors__premium_only()
    {
        self::getTaxonomyData('colors');
    }

    /**
     * Remove Options By Type
     *
     * @param $taxonomy
     */
    private function removeOptionsByType__premium_only($taxonomy, $fieldType)
    {
        //get old data
        $getOption = get_option(FilterPlugin::OPTION_ . $fieldType, array());

        if (isset($getOption[$taxonomy])) {
            //remove data for current option
            unset($getOption[$taxonomy]);
            //update option without removed data
            update_option(FilterPlugin::OPTION_ . $fieldType, $getOption);
        }
    }

    /**
     * Get Taxonomy By Id
     *
     * @param $id
     *
     * @return mixed
     */
    private static function getTaxonomyById($id)
    {
        $attribute = wc_get_attribute($id);
        if ($attribute) {
            return $attribute->slug;
        }

        return $id;
    }

    /**
     * Get attributes configuration
     *
     * @return mixed
     */
    private function getAttributesConfig()
    {
        return $this->getConfig(FilterPlugin::OPTION_ATTRIBUTES, $this->getAttributes(), $this->defaultAttribute);
    }

    /**
     * Woocommerce attributes id=>title array and custom taxonomies if exist
     *
     * @return array
     */
    private function getAttributes()
    {
        $wcAttributes = wc_get_attribute_taxonomies();
        $settings     = get_option(FilterPlugin::OPTION_SETTINGS, array());

        $attributes = array();

        //get Show in stock or Show on sale attributes from setting
        foreach ($settings as $key => $setting) {
            if (in_array($key, $this->premiumAttributes, true)) {
                $attributes[$key] = str_replace('_', ' ', $key);
            }
        }

        foreach ($wcAttributes as $attribute) {
            $attributes[$attribute->attribute_id] = $attribute->attribute_label;
        }
        foreach (Filter::$taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $taxonomy                    = get_taxonomy($taxonomy);
                $attributes[$taxonomy->name] = $taxonomy->labels->menu_name;
            }
        }

        return $attributes;
    }
}
