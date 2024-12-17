<?php namespace Premmerce\Filter\Seo;

use Premmerce\Filter\Admin\Tabs\Cache;
use Premmerce\SDK\V2\FileManager\FileManager;

/**
 * Class BundlesTable
 *
 * @package Premmerce\ProductBundles\Admin
 */
class RulesTable extends \WP_List_Table
{
    /**
     * Seo Model
     *
     * @var SeoModel
     */
    private $model;

    /**
     * File Manager
     *
     * @var FileManager
     */
    private $fileManager;

    /**
     * BundlesTable constructor.
     *
     * @param FileManager $fileManager
     * @param SeoModel    $model
     */
    public function __construct(FileManager $fileManager, SeoModel $model)
    {
        parent::__construct(
            array(
            'singular' => 'Rules',
            'plural'   => 'Rule',
            'ajax'     => false
            )
        );

        $this->fileManager = $fileManager;

        $this->model = $model;

        $this->prepare_items();
    }

    /**
     * Fill checkbox column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_cb($item)
    {
        $disabled = '';
        if (!premmerce_pwpf_fs()->can_use_premium_code()) {
            $disabled = 'disabled';
        }

        $checkbox = "<input type='checkbox' name='ids[]' id='cb-select-{$item['id']}' value='{$item['id']}' {$disabled}>";

        return $checkbox;
    }

    /**
     * Fill label column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_label($item)
    {
        return $item['label'];
    }

    /**
     * Fill category field
     *
     * @param array $item
     */
    protected function column_category($item)
    {
        $url = '';
        //This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            if (premmerce_pwpf_fs()->can_use_premium_code()) {
                $url = admin_url('admin.php?page=premmerce-filter-admin&tab=seo');
                $url = wp_nonce_url($url, 'bulk-rule');
            }
        }

        $this->fileManager->includeTemplate('admin/seo/table/column-h1.php', array('item' => $item, 'url' => $url));
    }

    /**
     * Fill enabled field
     *
     * @param $item
     *
     * @return string
     */
    protected function column_enabled($item)
    {
        if (1 == $item['enabled']) {
            return '<span class="dashicons dashicons-yes"></span>';
        }

        return '-';
    }

    /**
     * Fill discourage_search field
     *
     * @param $item
     *
     * @return string
     */
    protected function column_discourage_search($item)
    {
        if (1 == $item['discourage_search']) {
            return '<span class="dashicons dashicons-yes"></span>';
        }

        return '-';
    }

    /**
     * Return array with columns titles
     *
     * @return array
     */
    public function get_columns()
    {
        $data['cb']                = '<input type="checkbox">';
        $data['category']          = __('Category', 'premmerce-filter');
        $data['label']             = __('Label', 'premmerce-filter');
        $data['discourage_search'] = __('Discourage search engines', 'premmerce-filter');
        $data['enabled']           = __('Enabled', 'premmerce-filter');

        return $data;
    }

    /**
     * Set actions list for bulk
     *
     * @return array
     */
    protected function get_bulk_actions()
    {
        $data = array(
            'delete'  => __('Delete', 'premmerce-filter'),
            'enable'  => __('Enable', 'premmerce-filter'),
            'disable' => __('Disable', 'premmerce-filter'),
        );

        return $data;
    }

    /**
     * Set items data in table
     */
    public function prepare_items()
    {
        $this->_column_headers = array($this->get_columns());

        $this->handle_bulk_actions();

        $perPage = 20;

        $currentPage = $this->get_pagenum();

        $category = $this->get_query_filter('filter_product_cat');

        $search = $this->get_query_filter('s');


        $where = array();

        if ($category) {
            $where['term_id'] = $category;
        }

        $like = array();

        if ($search) {
            $like['label'] = "%{$search}%";
        }

        $data       = array();
        $totalItems = 0;

        // This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            $joinTranslation = WPMLHelper::joinTermWithWPMLCurrentTranslation('r.term_id');

            $data = $this->model->limit($perPage)
                ->alias('r')
                ->joinRaw($joinTranslation)
                ->offset(($currentPage - 1) * $perPage)
                ->where($where)
                ->like($like)
                ->get('r.*');

            $totalItems = $this->model->alias('r')->where($where)->joinRaw($joinTranslation)->like($like)->count();
        }

        $this->set_pagination_args(
            array(
            'total_items' => $totalItems,
            'per_page'    => $perPage,
            )
        );

        $this->items = $data;
    }

    /**
     * Render if no items
     */
    public function no_items()
    {
        esc_attr_e('No rules found.', 'premmerce-filter');
    }

    /**
     * Handle table bulk actions
     */
    public function handle_bulk_actions()
    {
        //This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            if (premmerce_pwpf_fs()->can_use_premium_code()) {
                if (empty($_REQUEST['_wpnonce']) || ! wp_verify_nonce(sanitize_text_field($_REQUEST['_wpnonce']), 'bulk-rule')) {
                    return;
                }
                if (empty($_REQUEST['action']) && empty($_REQUEST['action2'])) {
                    return;
                }
                if (empty($_REQUEST['ids'])) {
                    return;
                }

                $action = $this->current_action();

                $ids = isset($_REQUEST['ids']) ? wc_clean($_REQUEST['ids']) : array();

                switch ($action) {
                    case 'delete':
                        $this->model->remove($ids);
                        break;
                    case 'enable':
                        $this->model->enable($ids);
                        break;
                    case 'disable':
                        $this->model->disable($ids);
                        break;
                    default:
                        return;
                }

                //clear cache when change rules. Example, clear Math Rank Seo sitemap cache.
                Cache::clearOtherwisePluginsCache();

                if (in_array($action, array('delete', 'enable', 'disable'))) {
                    $httpReffer = isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : '';
                    wp_redirect($httpReffer);
                }
            }
        }
    }

    /**
     * Extra table navigation
     *
     * @param string $which
     */
    public function extra_tablenav($which)
    {
        if ('top' === $which) {
            echo '<div class="alignleft actions">';

            $this->categories_dropdown();


            submit_button(__('Filter'), 'button', 'filter_action', false, array('id' => 'post-query-submit'));

            echo '</div>';
        }
    }

    /**
     * Display categories dropdown
     */
    protected function categories_dropdown()
    {
        $dropdown_options = array(
            'show_option_all' => get_taxonomy('product_cat')->labels->all_items,
            'hide_empty'      => 0,
            'hierarchical'    => 1,
            'show_count'      => 0,
            'orderby'         => 'name',
            'taxonomy'        => 'product_cat',
            'name'            => 'filter_product_cat',
            'selected'        => $this->get_query_filter('filter_product_cat')
        );

        echo '<label class="screen-reader-text" for="cat">' . esc_attr__('Filter by category', 'premmerce-filter') . '</label>';
        wp_dropdown_categories($dropdown_options);
    }

    /**
     * Get query filter
     *
     * @param string $name
     *
     * @return string|null
     */
    private function get_query_filter($name)
    {
        if (! empty($_REQUEST[$name])) {
            return sanitize_text_field($_REQUEST[$name]);
        }

        return null;
    }
}
