<?php namespace Premmerce\Filter\Filter\Items\Types;

use Premmerce\Filter\Filter\Query\QueryHelper;
use WC_Query;
use WP_Tax_Query;
use WP_Meta_Query;

class InStockFilter extends BaseFilter
{
    protected $prefix = 'meta_';

    protected $key = '_stock_status';

    /**
     * Config
     *
     * @var
     */
    protected $config;

    private $items = array();

    /**
     * Query Helper
     *
     * @var QueryHelper
     */
    private $queryHelper;

    public function __construct($config, QueryHelper $queryHelper)
    {
        $this->config    = $config;
        $this->hideEmpty = !empty($config['hide_empty']);

        if (in_array($this->getType(), array('radio', 'select'))) {
            $this->single = true;
        }

        add_filter('woocommerce_product_query_meta_query', array($this, 'extendMetaQuery'));
        $this->queryHelper = $queryHelper;
    }

    /**
     * Extend Meta Query
     *
     * @param  mixed $metaQuery
     * @return void
     */
    public function extendMetaQuery($metaQuery)
    {
        $values = $this->getSelectedValues();

        if (! empty($values)) {
            $metaQuery[$this->key] = array(
                'key'     => $this->key,
                'value'   => $values,
                'compare' => 'IN'

            );
        }

        return $metaQuery;
    }

    /**
     * Unique item identifier
     *
     * @return string
     */
    public function getId()
    {
        return '_stock';
    }

    /**
     * Get Label
     *
     * @return string
     */
    public function getLabel()
    {
        return 'Stock';
    }

    /**
     * Get Slug
     *
     * @return string
     */
    public function getSlug()
    {
        return 'stock';
    }

    /**
     * Get Items
     *
     * @return void
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Get Active Items
     *
     * @return array
     */
    public function getActiveItems()
    {
        $items = $this->getItems();

        $active = array();
        foreach ($items as $item) {
            if ($item->checked) {
                $active[] = array('title' => $item->name, 'link' => $item->link);
            }
        }

        return $active;
    }

    /**
     * Get Active Products
     *
     * @return array
     */
    public function getActiveProducts()
    {
        return array();
    }

    /**
     * Is Visible
     *
     * @return boolean
     */
    public function isVisible()
    {
        return count($this->getItems());
    }

    /**
     * Get Type checkbox|radio|select|label|color
     *
     * @return string
     */
    public function getType()
    {
        return isset($this->config['type']) ? $this->config['type'] : '';
    }

    /**
     * Init
     *
     * @return void
     */
    public function init()
    {
        $active = $this->getSelectedValues();

        $items = $this->loadItems();

        foreach ($items as $item) {
            $item->slug    = strtolower($item->slug);
            $item->checked = in_array($item->slug, $active);
            $item->link    = $this->getValueLink($item->slug);

            if ('instock' === $item->slug) {
                $item->name = __('In stock', 'premmerce-filter');
            } elseif ('outofstock' === $item->slug) {
                $item->name = __('Out of stock', 'premmerce-filter');
            } elseif ('onbackorder' === $item->slug) {
                $item->name = __('On Back Order', 'premmerce-filter');
            }
        }

        $this->items = $items;
    }

    /**
     * Load Items
     *
     * @return void
     */
    private function loadItems()
    {
        global $wpdb;

        $taxQuery  = WC_Query::get_main_tax_query();
        $metaQuery = WC_Query::get_main_meta_query();

        $taxQuery  = new WP_Tax_Query($taxQuery);
        $metaQuery = new WP_Meta_Query($metaQuery);

        $metaQuerySql = $metaQuery->get_sql('post', $wpdb->posts, 'ID');
        $taxQuerySql  = $taxQuery->get_sql($wpdb->posts, 'ID');

        $sql  = 'SELECT COUNT(DISTINCT stock_meta.post_id) as count, stock_meta.meta_id as term_id, stock_meta.meta_value as name, stock_meta.meta_value as slug';
        $sql .= " FROM {$wpdb->posts} ";
        $sql .= "LEFT JOIN {$wpdb->postmeta} as stock_meta ON {$wpdb->posts}.ID = stock_meta.post_id AND stock_meta.meta_key = '{$this->key}' ";
        $sql .= $taxQuerySql['join'] . $metaQuerySql['join'];
        $sql .= $this->queryHelper->getPostWhereQuery();
        $sql .= $taxQuerySql['where'] . $metaQuerySql['where'];
        $sql .= 'GROUP BY stock_meta.meta_value';

        $result = $wpdb->get_results($wpdb->prepare('%1$s', '') . $sql);

        return !empty($result) ? $result : array();
    }
}
