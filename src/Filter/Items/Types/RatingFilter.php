<?php namespace Premmerce\Filter\Filter\Items\Types;

use WC_Query;
use WP_Tax_Query;
use WP_Meta_Query;
use Premmerce\Filter\Filter\Query\QueryHelper;

class RatingFilter extends BaseFilter
{
    protected $prefix = 'rating_';

    protected $key = '_wc_average_rating';

    /**
     * Config
     *
     * @var
     */
    protected $config;

    /**
     * Items
     */
    private $items = array();

    /**
     * QueryHelper
     *
     * @var QueryHelper
     */
    private $queryHelper;

    /**
     * RatingFilter construct
     */
    public function __construct($config, QueryHelper $queryHelper)
    {
        $this->config    = $config;
        $this->hideEmpty = !empty($config['hide_empty']);

        if (in_array($this->getType(), array('radio', 'select'))) {
            $this->single = true;
        }

        $this->queryHelper = $queryHelper;
    }

    /**
     * Unique item identifier
     *
     * @return string
     */
    public function getId()
    {
        return 'rating';
    }

    /**
     * Get Label
     *
     * @return string
     */
    public function getLabel()
    {
        return 'Rating';
    }

    /**
     * Get Slug
     *
     * @return string
     */
    public function getSlug()
    {
        return 'filter';
    }

    /**
     * Get Items
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
     * Get Type
     *
     * Checkbox|radio|select|label|color
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

        $items = array();

        for ($rating = 5; $rating >= 1; $rating--) {
            $count = $this->getFilteredProductCount($rating);

            if (empty($count)) {
                continue;
            }

            if (!isset($items[$rating]) || !is_object($items[$rating])) {
                $items[$rating] = new \stdClass();
            }

            $items[$rating]->count   = $count;
            $items[$rating]->term_id = $rating;
            $items[$rating]->name    = $rating;
            $items[$rating]->slug    = $rating;
            $items[$rating]->checked = in_array($rating, $active);

            $items[$rating]->link = $this->getValueLink($rating);
        }

        $this->items = $items;
    }

    /**
     * Count products after other filters have occurred by adjusting the main query.
     *
     * @param  int $rating Rating.
     * @return int
     */
    protected function getFilteredProductCount($rating)
    {
        //get rating count from transient
        $getCountTransient = get_transient("premmerce_filtered_product_count_{$rating}");

        $taxQuery  = WC_Query::get_main_tax_query();
        $metaQuery = WC_Query::get_main_meta_query();

        $postInIds = QueryHelper::getPostInProducts();

        $transientQuery = $this->generateQueryTransientID($taxQuery, $metaQuery, $postInIds, 'rating_filter');

        $filteredProductCount = !empty($getCountTransient[$transientQuery]) ? $getCountTransient[$transientQuery] : null;

        if (!is_numeric($filteredProductCount)) {
            global $wpdb;

            // Set new rating filter.
            $productVisibilityTerms = wc_get_product_visibility_term_ids();
            $taxQuery[]             = array(
                'taxonomy'      => 'product_visibility',
                'field'         => 'term_taxonomy_id',
                'terms'         => $productVisibilityTerms[ 'rated-' . $rating ],
                'operator'      => 'IN',
                'rating_filter' => true,
            );

            $metaQuery = new WP_Meta_Query($metaQuery);
            $taxQuery  = new WP_Tax_Query($taxQuery);


            $metaQuerySql = $metaQuery->get_sql('post', $wpdb->posts, 'ID');
            $taxQuerySql  = $taxQuery->get_sql($wpdb->posts, 'ID');

            $joinQuery          = str_replace(array("\n", "\t"), ' ', $taxQuerySql['join'] . $metaQuerySql['join']) . ' ';
            $whereQuery         = str_replace(array("\n", "\t"), ' ', $taxQuerySql['where'] . $metaQuerySql['where']) . ' ';
            $helperGetPostQuery = $this->queryHelper->getPostWhereQuery();

            $search    = WC_Query::get_main_search_query_sql();
            $searchSql = !empty($search) ? ' AND ' . $search : '';

            $filteredProductCount = absint($wpdb->get_var(
                "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) FROM {$wpdb->posts} "
                . $wpdb->prepare('%1$s', '')
                . $joinQuery
                . $helperGetPostQuery
                . $whereQuery
                . $searchSql
            ));

            //save old data and new
            $getCountTransient[$transientQuery] = $filteredProductCount;
            //save in transient at 12 hours
            set_transient("premmerce_filtered_product_count_{$rating}", $getCountTransient, DAY_IN_SECONDS);
        }

        return $filteredProductCount;
    }
}
