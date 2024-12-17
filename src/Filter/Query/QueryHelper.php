<?php namespace Premmerce\Filter\Filter\Query;

use WC_Query;
use WP_Tax_Query;
use WP_Meta_Query;

class QueryHelper
{
    /**
     * WP DB
     *
     * @var \wpdb
     */
    private $wpdb;

    public function __construct()
    {
        $this->wpdb = $GLOBALS['wpdb'];
    }

    /**
     * Search Query
     *
     * @var string
     */
    protected $searchQuery;

    /**
     * Set Search Query
     *
     * @param string $searchQuery
     */
    public function setSearchQuery($searchQuery)
    {
        $this->searchQuery = $searchQuery;
    }

    /**
     * Get Search Query
     *
     * @return string
     */
    public function getSearchQuery()
    {
        return $this->searchQuery;
    }

    /**
     * Main query to term relations table
     *
     * @param array $exceptTaxonomies
     *
     * @return array
     */
    public function getTaxQuerySql($exceptTaxonomies = array())
    {
        $taxQuery = array();
        foreach (WC_Query::get_main_tax_query() as $val) {
            $taxQuery[] = $val;
        }
        foreach (WC()->query->get_tax_query() as $val) {
            $taxQuery[] = $val;
        }

        if (! empty($exceptTaxonomies)) {
            foreach ($taxQuery as $key => $query) {
                if (is_array($query) && in_array($query['taxonomy'], $exceptTaxonomies, true)) {
                    unset($taxQuery[$key]);
                }
            }
        }

        $taxQuery = new WP_Tax_Query($taxQuery);

        return $taxQuery->get_sql($this->wpdb->posts, 'ID');
    }

    /**
     * Main query to post meta table
     *
     * @param null $remove
     *
     * @return array|false
     */
    public function getMetaQuerySql($remove = null)
    {
        $meta_query = WC_Query::get_main_meta_query();

        if (is_array($remove)) {
            foreach ($remove as $key) {
                if (isset($meta_query[$key])) {
                    unset($meta_query[$key]);
                }
            }
        }

        $meta_query = new WP_Meta_Query($meta_query);

        return $meta_query->get_sql('post', $this->wpdb->posts, 'ID');
    }

    /**
     * Get post__in ids from current query.
     * In filter in post__in we added OnSale products
     */
    public static function getPostInProducts()
    {
        $postInFromQuery = WC_Query::get_main_query()->query_vars['post__in'];
        $postInIds       = !empty($postInFromQuery) ? implode(',', $postInFromQuery) : null;

        return $postInIds;
    }

    /**
     * Get Post Where Query
     *
     * @return string
     */
    public function getPostWhereQuery()
    {
        $postType = $this->arraySql(apply_filters('woocommerce_price_filter_post_type', array('product')));

        //take On Sale ids if it's active
        $postInIds = self::getPostInProducts();

        $sql[] = "WHERE {$this->wpdb->posts}.post_type IN {$postType}";
        $sql[] = "AND {$this->wpdb->posts}.post_status = 'publish'";
        $sql[] = (!empty($postInIds)) ? "AND {$this->wpdb->posts}.ID IN ({$postInIds}) " : '';

        return implode(' ', $sql);
    }

    /**
     * Array Sql
     *
     * @param array $values
     *
     * @return string
     */
    public function arraySql($values)
    {
        return "('" . implode("','", array_map('esc_sql', $values)) . "')";
    }

    /**
     * Get queried object id and children ids
     *
     * @return array
     */
    public function getQueriedObjectIds()
    {
        $term    = get_queried_object();
        $termIds = array();
        if ($term instanceof \WP_Term) {
            $termIds = array($term->term_taxonomy_id);

            if (is_taxonomy_hierarchical($term->taxonomy)) {
                $children = get_term_children($term->term_id, $term->taxonomy);

                if (is_array($children)) {
                    foreach ($children as $child) {
                        $term      = get_term($child);
                        $termIds[] = $term->term_taxonomy_id;
                    }
                }
            }
        }

        return $termIds;
    }
}
