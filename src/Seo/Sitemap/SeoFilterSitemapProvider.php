<?php namespace Premmerce\Filter\Seo\Sitemap;

use WPSEO_Sitemap_Provider;
use Premmerce\Filter\Seo\SeoModel;

class SeoFilterSitemapProvider implements WPSEO_Sitemap_Provider
{
    /**
     * Seo Model
     *
     * @var SeoModel
     */
    public $model;

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
        $links = ( new GeneralSitemap($this->model) )->get_sitemap_links($type, $max_entries, $current_page);

        return $links;
    }
}
