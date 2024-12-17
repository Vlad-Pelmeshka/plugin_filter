<?php namespace Premmerce\Filter\Filter\Items\Types;

use Premmerce\Filter\Filter\Query\QueryHelper;
use WP_Query;
use WC_Query;
use WP_Tax_Query;
use WP_Meta_Query;

class OnSaleFilter extends BaseFilter
{
    /**
     * Prefix
     *
     * @var string
     */
    protected $prefix = 'meta_';

    /**
     * Key
     *
     * @var string
     */
    protected $key = '_sale_price';

    /**
     * Config
     *
     * @var mixed
     */
    protected $config;

    /**
     * Items
     *
     * @var array
     */
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

        add_filter('woocommerce_product_query', array($this, 'extendQuery'));
        $this->queryHelper = $queryHelper;
    }

    public function extendQuery($query)
    {
        $values = $this->getSelectedValues();

        if (! empty($values)) {
            //get all product's ids which are `On sale` (grouped too)
            $query->set('post__in', self::groupedOnSale());
        }

        return $query;
    }

    /**
     * Unique item identifier
     *
     * @return string
     */
    public function getId()
    {
        return '_on_sale';
    }

    /**
     * Get Label
     *
     * @return string
     */
    public function getLabel()
    {
        return 'On Sale';
    }

    /**
     * Get Slug
     *
     * @return string
     */
    public function getSlug()
    {
        return 'on_sale';
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
     * @return void
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

        $zeroCount = false;

        foreach ($items as $item) {
            $item->slug    = $this->getSlug();
            $item->checked = in_array($item->slug, $active);
            $item->link    = $this->getValueLink($item->slug);

            //check if count return 0.
            //it's need for hiding count filter
            if (0 == $item->count) {
                $zeroCount = true;
            }
        }

        if ($zeroCount) {
            $items = array();
        }

        $this->items = $items;
    }

    /**
     * Grouped On Sale
     * Get ids Grouped products which have On Sale products inside (onsale children products)
     *
     * @return void
     */
    public static function groupedOnSale()
    {
        //all products with status On Sale
        $allProductsOnSale = wc_get_product_ids_on_sale();

        //get all Groped Products from transient
        $allGroupedProductsIds = get_transient('premmerce_get_grouped_products');

        //if we don't have transient `premmerce_get_grouped_products` - take it from WP_Query and save
        if (false === $allGroupedProductsIds) {
            //get all grouped products
            $args                    = array(
                'post_type'       => 'product',
                'posts_per_page'  => -1,
                'fields'          => 'ids',
                'tax_query'       => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    => 'grouped',
                    )
                )
            );
            $allGroupedProductsQuery = new WP_Query($args);
            $allGroupedProductsIds   = $allGroupedProductsQuery->posts;

            //save in transient at 24 hours
            set_transient('premmerce_get_grouped_products', $allGroupedProductsIds, DAY_IN_SECONDS);
        }


        $groupedOnSale = array();

        foreach ($allGroupedProductsIds as $pr) {
            $childrenProducts = get_post_meta($pr, '_children', true);

            //check if childrenProducts are on sale
            if (array_intersect($childrenProducts, $allProductsOnSale)) {
                $groupedOnSale[] = $pr;
            }
        }

        $onSale = array_unique(array_merge($groupedOnSale, $allProductsOnSale));

        return $onSale;
    }

    /**
     * Count On Sale Products In Query
     *
     * @return int
     */
    private function countOnSaleProductsInQuery()
    {
        global $wpdb;

        //get all product's ids which are `On sale`
        $onSaleIds = implode(',', self::groupedOnSale());

        if (empty($onSaleIds)) {
            return 0;
        }

        $taxQuery  = WC_Query::get_main_tax_query();
        $metaQuery = WC_Query::get_main_meta_query();

        $taxQuery  = new WP_Tax_Query($taxQuery);
        $metaQuery = new WP_Meta_Query($metaQuery);

        $metaQuerySql = $metaQuery->get_sql('post', $wpdb->posts, 'ID');
        $taxQuerySql  = $taxQuery->get_sql($wpdb->posts, 'ID');

        $sql = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) FROM {$wpdb->posts} ";
        $sql .= $taxQuerySql['join'] . $metaQuerySql['join'];
        $sql .= " WHERE {$wpdb->posts}.ID IN ({$onSaleIds}) AND {$wpdb->posts}.post_type = 'product' AND {$wpdb->posts}.post_status = 'publish' ";
        $sql .= $taxQuerySql['where'] . $metaQuerySql['where'];

        $search = WC_Query::get_main_search_query_sql();
        if ($search) {
            $sql .= ' AND ' . $search;
        }

        $result = absint($wpdb->get_var($wpdb->prepare('%1$s', '') . $sql));

        return ! empty($result) ? $result : 0;
    }

    /**
     * Load Items
     *
     * @return void
     */
    private function loadItems()
    {
        $items[0] = new \stdClass();

        $items[0]->count   = $this->countOnSaleProductsInQuery();
        $items[0]->term_id = 'onsale';
        $items[0]->slug    = 'onsale';
        $items[0]->name    = __('On sale', 'premmerce-filter');

        return $items;
    }

    /**
     * Remove transient for OnSale Filter
     *
     * @return void
     */
    public static function removeTransients()
    {
        delete_transient('premmerce_get_grouped_products');
    }
}
