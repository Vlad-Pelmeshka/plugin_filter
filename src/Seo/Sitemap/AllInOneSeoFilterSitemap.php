<?php namespace Premmerce\Filter\Seo\Sitemap;

use Premmerce\Filter\Seo\SeoModel;

class AllInOneSeoFilterSitemap
{
    /**
     * Seo Model
     *
     * @var SeoModel
     */
    private $model;

    /**
     * SeoFilterSitemapProvider constructor.
     *
     * @param SeoModel $model
     */
    public function __construct(SeoModel $model)
    {
        $this->model = $model;
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
    public function get_sitemap_links($type, $max_entries, $current_page)
    {
        $rules = ( new GeneralSitemap($this->model) )->get_sitemap_links($type, $max_entries, $current_page);

        return $rules;
    }

    /**
     * Get links for rules
     *
     * @param array $pages
     *
     * @return array
     */
    public function getSitemapPages($pages)
    {
        $rules = $this->get_sitemap_links(GeneralSitemap::SITEMAP_TYPE, -1, 1);

        foreach ($rules as $key => $rule) {
            $seoRulesPages[] = array(
                'loc'        => $rule['loc'],
                'lastmod'    => $rule['mod'],
                'changefreq' => 'always',
                'priority'   => '1.0',
            );
        }

        $pages = array_merge($pages, $seoRulesPages);

        return $pages;
    }
}
