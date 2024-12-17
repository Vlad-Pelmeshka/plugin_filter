<?php namespace Premmerce\Filter\Seo\Sitemap;

use Premmerce\Filter\Seo\SeoModel;
use RankMath\Sitemap\Providers\Provider;

class RankMathSeoFilterSitemap implements Provider
{
    /**
     * Seo Model
     *
     * @var SeoModel
     */
    private $model;

    /**
     * Type
     *
     * @var string
     */
    public $type = GeneralSitemap::SITEMAP_TYPE;

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
     * Check if provider supports given item type.
     *
     * @param string $type Type string to check for.
     *
     * @return boolean
     */
    public function handles_type($type)
    {
        return $type === $this->type;
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
        $index = ( new GeneralSitemap($this->model) )->get_index_links($max_entries);

        return $index;
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
}
