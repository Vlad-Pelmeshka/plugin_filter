<?php namespace Premmerce\Filter\Seo\Sitemap;

use Premmerce\Filter\Seo\SeoModel;

class GeneralSitemap
{
    const SITEMAP_TYPE = 'filter_seo_rule';

    /**
     * Сategory modified
     *
     * @var array
     */
    public $category_modified = array();

    /**
     * Type
     *
     * @var string
     */
    public $type = self::SITEMAP_TYPE;

    /**
     * SeoFilterSitemapProvider constructor.
     *
     * @param SeoModel $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Select unique categories for sitemap by page number
     *
     * @param $max_entries
     * @param $current_page
     *
     * @return array
     */
    public function get_categories_for_rules_sitemap_page($max_entries, $current_page)
    {
        global $wpdb;

        $offset = $this->get_offset($max_entries, $current_page);

        return $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT term_id FROM (select term_id from {$wpdb->prefix}premmerce_filter_seo LIMIT %d OFFSET %d) pf",
                $max_entries,
                $offset
            )
        );
    }

    /**
     * Get set of sitemaps index link data.
     *
     * @param int $max_entries Entries per sitemap.
     *
     * @return array
     */
    public function get_index_links($max_entries)
    {
        $count = $this->model->where(array('enabled' => true))->count();

        $numPages = $count ? 1 : 0;
        if ($count > $max_entries) {
            $numPages = (int) ceil($count / $max_entries);
        }

        $index = array();
        for ($i = 1; $i <= $numPages; $i++) {
            $categories = $this->get_categories_for_rules_sitemap_page($max_entries, $i);

            $last = $this->get_last_modified($categories);

            //if seo rule updated later then taxonomy - take mod time from rule `modified_at`
            if (strtotime(self::getLastModifiedSeoRule()) > strtotime($last)) {
                $last = self::getLastModifiedSeoRule();
            }

            $page = $numPages > 1 ? $i : '';

            $index[] = array(
                'loc'     => self::getLocLinkForAllSeoRules(),
                'lastmod' => $last
            );
        }

        return $index;
    }

    /**
     * Get general link for Seo Rules
     *
     * @return string
     */
    public static function getLocLinkForAllSeoRules()
    {
        return get_home_url(null, '/') . self::SITEMAP_TYPE . '-sitemap.xml';
    }

    /**
     * Get set of sitemap link data.
     *
     * @param string $type         Sitemap type.
     * @param int    $max_entries  Entries per sitemap.
     * @param int    $current_page Current page of the sitemap.
     *
     * @return array
     */
    public function get_sitemap_links($type, $max_entries, $current_page)
    {
        $links = array();

        $rules = $this->get_rules_by_page($max_entries, $current_page);


        foreach ($rules as $rule) {
            //if seo rule updated later then taxonomy - take mod time from rule `modified_at`
            $mod = $this->get_last_modified(array($rule['term_id']));
            if (strtotime($rule['modified_at']) > strtotime($mod)) {
                $mod = $rule['modified_at'];
            }

            $links[] = array(
                'loc' => user_trailingslashit(home_url($rule['path'])),
                'mod' => $mod,
            );
        }

        return $links;
    }

    /**
     * Get offset by limit and page
     *
     * @param int $max_entries
     * @param int $current_page
     *
     * @return int
     */
    public function get_offset($max_entries, $current_page)
    {
        return 1 === $current_page ? 0 : ($current_page - 1) * $max_entries;
    }

    /**
     * Get last modified post for categories array
     *
     * @param array $category_ids
     *
     * @return mixed
     */
    public function get_last_modified($category_ids)
    {
        $key = implode('_', $category_ids);

        if (isset($this->category_modified[$key])) {
            return $this->category_modified[$key];
        }

        global $wpdb;

        $this->category_modified[$key] = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(p.post_modified_gmt) AS lm
			FROM	{$wpdb->posts} AS p
			INNER JOIN {$wpdb->term_relationships} AS tr
			  ON		tr.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} AS tt
			  ON		tt.term_taxonomy_id = tr.term_taxonomy_id
				AND		tt.taxonomy = 'product_cat'
				AND		tt.term_id in (%d)
			WHERE	p.post_status IN ('publish','inherit')
			  AND		p.post_password = ''",
            $category_ids
        ));

        return $this->category_modified[$key];
    }

    /**
     * Select rules by page number
     *
     * @param int   $max_entries
     * @param int   $current_page
     * @param array $select
     *
     * @return array|null|object
     */
    public function get_rules_by_page(
        $max_entries,
        $current_page,
        $select = array('path', 'term_id', 'title', 'meta_description', 'modified_at')
    ) {
        $offset = $this->get_offset($max_entries, $current_page);

        $rules = apply_filters(
            'premmerce_filter_seo_sitemap_rules',
            $this
                ->model
                ->where(array('enabled' => true, 'discourage_search' => false))
                ->offset($offset)
                ->limit($max_entries)
                ->get($select),
            $this->model,
            $offset,
            $max_entries,
            $current_page,
            $select
        );

        return $rules;
    }

    /**
     * Take last modified date from Seo Rule
     */
    public static function getLastModifiedSeoRule()
    {
        //take last modified date from transient (if no, take it from DB)
        $getLastModifiedSeoRule = get_transient('premmerce_get_last_modified_seo_rules');

        if (false === $getLastModifiedSeoRule) {
            global $wpdb;

            //take all rules wich are enabled and `discourage_search is disabled`
            //and sort by modified_at
            $result = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID, `modified_at` FROM {$wpdb->prefix}premmerce_filter_seo WHERE `enabled` = %d AND `discourage_search` = %d ORDER BY STR_TO_DATE(`modified_at`, 'Y-m-d H:i:s') DESC",
                    1,
                    0
                )
            );

            //take first rule, because it is newest (last modified)
            $getLastModifiedSeoRule = $result[0]->modified_at;

            //save in transient at 24 hours
            set_transient('premmerce_get_last_modified_seo_rules', $getLastModifiedSeoRule, DAY_IN_SECONDS);
        }

        return $getLastModifiedSeoRule;
    }

    /**
     * Remove transient for Seo Filter Sitemap
     */
    public static function removeTransients()
    {
        delete_transient('premmerce_get_last_modified_seo_rules');
    }
}
