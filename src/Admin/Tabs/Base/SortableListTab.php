<?php namespace Premmerce\Filter\Admin\Tabs\Base;

abstract class SortableListTab implements TabInterface
{
    /**
     * How to handle bulk actions
     *
     * @var array
     */
    protected $bulkActions = array();

    public function __construct()
    {
        $this->bulkActions['display']  = array('active' => 1);
        $this->bulkActions['hide']     = array('active' => 0);
        $this->bulkActions['checkbox'] = array('type' => 'checkbox');
        $this->bulkActions['select']   = array('type' => 'select');
        $this->bulkActions['radio']    = array('type' => 'radio');


        //this variables is for premium plan
        $this->bulkActions['color']  = array('type' => 'color');
        $this->bulkActions['image']  = array('type' => 'image');
        $this->bulkActions['label']  = array('type' => 'label');
        $this->bulkActions['slider'] = array('type' => 'slider');


        $this->bulkActions['display_']                = array('display_type' => '');
        $this->bulkActions['display_dropdown']        = array('display_type' => 'dropdown');
        $this->bulkActions['display_scroll']          = array('display_type' => 'scroll');
        $this->bulkActions['display_scroll_dropdown'] = array('display_type' => 'scroll_dropdown');
        $this->bulkActions['display_dropdown_hover']  = array('display_type' => 'dropdown_hover');
    }

    /**
     * Ajax order by ids handler
     *
     * @param string $key    - options key to update
     * @param array  $actual - actual data
     */
    protected function sortHandler($key, $actual)
    {
        $ids = isset($_POST['ids']) && isset($_POST['ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_POST['ajax_nonce']), 'filter-ajax-nonce') ? wc_clean(wp_unslash($_POST['ids'])) : array();

        if (is_array($ids)) {
            $ids = array_combine($ids, $ids);

            $config = array_replace($ids, $actual);

            update_option($key, $config);
        }

        wp_die();
    }

    /**
     * Bulk update entities
     *
     * @param string $key    - config key
     * @param array  $config - initial config
     */
    protected function bulkActionsHandler($key, $config)
    {
        $action = isset($_POST['value']) && isset($_POST['ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_POST['ajax_nonce']), 'filter-ajax-nonce') ? wc_clean(wp_unslash($_POST['value'])) : null;
        $ids    = isset($_POST['ids']) && isset($_POST['ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_POST['ajax_nonce']), 'filter-ajax-nonce') ? wc_clean(wp_unslash($_POST['ids'])) : array();

        if (array_key_exists($action, $this->bulkActions)) {
            $update = $this->bulkActions[$action];

            foreach ($ids as $id) {
                if (array_key_exists($id, $config)) {
                    do_action('premmerce_filter_item_updated', $id, $config[$id], $update);
                    $config[$id] = array_merge($config[$id], $update);
                }
            }
            update_option($key, $config);
        }

        wp_die();
    }

    /**
     * Get config with actual values
     *
     * @param $name
     * @param $actual
     * @param $default
     *
     * @return array
     */
    protected function getConfig($name, $actual, $default)
    {
        $config = get_option($name, array());

        if (! is_array($config)) {
            $config = array();
        }

        $ids       = array_keys($actual);
        $configIds = array_keys($config);

        $removed = array_diff($configIds, $ids);

        foreach ($removed as $id) {
            unset($config[$id]);
        }


        $new = array_diff($ids, $configIds);

        foreach ($config as &$item) {
            if (! is_array($item)) {
                $item = array();
            }
            $item = array_merge($default, $item);
        }

        foreach ($new as $id) {
            $config[$id] = $default;
        }

        return $config;
    }

    /**
     * Get pagination attributes and args
     *
     * @param $attributes
     *
     * @return array
     */
    public function paginationDataForTabs($attributes)
    {
        $screen_option = get_current_screen()->get_option('per_page', 'option');
        $itemsPerPage  = get_user_meta(get_current_user_id(), $screen_option, true);

        if (! $itemsPerPage) {
            $itemsPerPage = 100;
        }

        $page = isset($_GET['p']) ? absint($_GET['p']) : 1;

        $offset = ($page - 1) * $itemsPerPage;

        $total = ceil(count($attributes) / $itemsPerPage);

        $keys = array_keys($attributes);

        $prevId = isset($keys[$offset - 1]) ? $keys[$offset - 1] : null;
        $nextId = isset($keys[$offset + $itemsPerPage]) ? $keys[$offset + $itemsPerPage] : null;

        $attributes = array_slice($attributes, $offset, $itemsPerPage, true);

        $paginationArgs = array(
            'base'               => '%_%',
            'format'             => '?p=%#%', // : %#% is replaced by the page number
            'total'              => $total,
            'current'            => $page,
            'aria_current'       => 'page',
            'show_all'           => false,
            'prev_next'          => true,
            'prev_text'          => '&larr;',
            'next_text'          => '&rarr;',
            'end_size'           => 1,
            'mid_size'           => 10,
            'add_args'           => array(), // array of query args to add
            'add_fragment'       => '',
            'before_page_number' => '',
            'after_page_number'  => '',
        );

        $paginationReturn = array(
            'attr'   => $attributes,
            'args'   => $paginationArgs,
            'prevId' => $prevId,
            'nextId' => $nextId
        );

        return $paginationReturn;
    }
}
