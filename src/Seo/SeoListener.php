<?php namespace Premmerce\Filter\Seo;

use Premmerce\Filter\FilterPlugin;
use Premmerce\Filter\Filter\Container;
use Premmerce\Filter\Seo\Sitemap\GeneralSitemap;
use Premmerce\Filter\Filter\Items\Types\FilterInterface;
use Premmerce\Filter\Seo\Sitemap\AllInOneSeoFilterSitemap;
use Premmerce\Filter\Seo\Sitemap\RankMathSeoFilterSitemap;
use Premmerce\Filter\Seo\Sitemap\SeoFilterSitemapProvider;

class SeoListener
{
    /**
     * Seo Model
     *
     * @var SeoModel
     */
    private $seoModel;

    /**
     * Rule
     *
     * @var array
     */
    private $rule;

    /**
     * Settings
     *
     * @var array
     */
    private $settings;

    /**
     * Formatted
     *
     * @var array
     */
    private $formatted = array();

    /**
     * Formatted Attributes Replacements
     *
     * @var array
     */
    private $formattedAttributesReplacements = array();

    /**
     * SeoListener constructor.
     */
    public function __construct()
    {
        add_action('woocommerce_product_query', array($this, 'findRule'));

        add_action('premmerce_filter_rule_found', array($this, 'registerActionsForRule'));

        $robotsMetaActions = array(
            'wp_robots',           // default WP robot meta
            'wpseo_robots_array', // Yoast SEO robot meta
            'aioseo_robots_meta', // All in One Seo robot meta
            'rank_math/frontend/robots' // Rank Math Seo robot meta
        );

        foreach ($robotsMetaActions as $robotMetaAction) {
            add_filter($robotMetaAction, array($this, 'addMetaDescourageSearch'), 10, 1);
        }

        //add discouraged seo rules to robots.txt
        add_action('robots_txt', array($this, 'discourageSearchRobotsTxt'), -1);

        //add seo rules to Yoast SEO sitemap
        add_filter('wpseo_sitemaps_providers', array($this, 'addSitemap'));
        //add seo rules to All in One Seo sitemap
        add_filter('aioseo_sitemap_additional_pages', array($this, 'addSitemapAllInOneSeo'));
        //add external providers (Seo Rules) in Rank Math Seo sitemap
        add_filter('rank_math/sitemap/providers', array($this, 'RankMathSeoSitemapProvider'));

        //add rel canonical for filter pages when we don't use main seo plugins
        add_action('wp_head', array($this, 'addCanonical'));

        $canonicalActionsForPlugins = array(
            'wpseo_canonical', // Yoast SEO canonical
            'aioseo_canonical_url', // All in One Seo canonical
            'rank_math/frontend/canonical' // Rank Math Seo canonical
        );

        foreach ($canonicalActionsForPlugins as $canonicalAction) {
            add_filter($canonicalAction, array($this, 'getCanonicalForSeoPlugins'));
        }

        $clearSitemapActions = array(
            'save_post_product',
            'premmerce_filter_seo_rule_created',
            'premmerce_filter_seo_rule_updated',
            'premmerce_filter_seo_bulk_rules_removed',
            'premmerce_filter_seo_bulk_rules_updated',
            'update_option_premmerce_filter_seo_settings',
        );

        foreach ($clearSitemapActions as $action) {
            add_action($action, array($this, 'clearSiteMap'));
        }

        $this->settings = get_option('premmerce_filter_seo_settings', array());

        $this->settings = array_merge(
            array(
                'use_default_settings' => null,
                'h1'                   => null,
                'title'                => null,
                'meta_description'     => null,
                'description'          => null
            ),
            $this->settings
        );

        $this->seoModel = new SeoModel();
    }

    /**
     * Add rules sitemap to YOAST SEO
     *
     * @param $array
     *
     * @return array
     */
    public function addSitemap($array)
    {
        $array[] = new SeoFilterSitemapProvider($this->seoModel);

        return $array;
    }

    /**
     * Add rules sitemap to All In One Seo
     *
     * @param $array
     *
     * @return array
     */
    public function addSitemapAllInOneSeo($pages)
    {
        $pages = ( new AllInOneSeoFilterSitemap($this->seoModel) )->getSitemapPages($pages);

        return $pages;
    }

    public function RankMathSeoSitemapProvider($external_providers)
    {
        $external_providers[GeneralSitemap::SITEMAP_TYPE] = new RankMathSeoFilterSitemap($this->seoModel);

        return $external_providers;
    }

    /**
     * Clear rules sitemap
     */
    public function clearSiteMap()
    {
        if (class_exists('WPSEO_Sitemaps_Cache')) {
            \WPSEO_Sitemaps_Cache::clear(array('filter_seo_rule'));
        }
    }

    /**
     * Get Rule data by path
     */
    public function ruleByPath($path)
    {
        $rule = $this->seoModel
            ->where(array('path' => trim($path, '/'), 'enabled' => 1))
            ->returnType(SeoModel::TYPE_ROW)
            ->limit(1)
            ->get();
        return $rule;
    }

    /**
     * Find Rule by path
     */
    public function findRule()
    {
        if (is_product_category() && is_filtered()) {
            $reqUri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
            $path   = wp_parse_url($reqUri)['path'];

            //if it paged (pagination page) take the same infor like in first page.
            if (is_paged()) {
                $path = self::getNopagingUrl();
            }

            global $original_REQUEST_attrubutes;
            $rule = $this->ruleByPath($original_REQUEST_attrubutes['original']);

            if (!$rule && !empty($this->settings['use_default_settings'])) {
                $rule = $this->settings;
            }

            if (is_array($rule)) {
                do_action('premmerce_filter_rule_found', array('rule' => $rule));
            }
        }
    }

    /**
     * Register actions for found rule
     *
     * @param array $args
     */
    public function registerActionsForRule($args)
    {
        $this->rule = $args['rule'];

        add_filter('pre_get_document_title', array($this, 'documentTitle'), 30);

        $pageTitleActionsForPlugins = array(
            'woocommerce_page_title', // Woocommerce page title
            'get_the_archive_title', // Default Archive Title
            'elementor/utils/get_the_archive_title' // Elementor Archive Page Title
        );

        foreach ($pageTitleActionsForPlugins as $pageTitleAction) {
            add_filter($pageTitleAction, array($this, 'pageTitle'));
        }

        //meta descriptions

        if($this->rule['title']){
            add_filter('wpseo_title', array($this, 'documentTitle'), 10000);
            add_filter('wpseo_opengraph_title', array($this, 'documentTitle'), 10000);
        }

        if($this->rule['meta_description']){
            add_action('wp_head', array($this, 'addMetaDescription'), 1);
            add_filter('wpseo_metadesc', array($this, 'addMetaDescriptionYoast'), 10000);
            add_filter('wpseo_opengraph_desc', array($this, 'addMetaDescriptionYoast'), 10000);
            add_filter('rank_math/frontend/description', array($this, 'addMetaDescriptionRankMath'));
        }

        // remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);
        add_action('woocommerce_archive_description', array($this, 'addDescription'), 20);
        add_action('custom_archive_description', array($this, 'addDescription'), 20);
    }

    /**
     * Replace term description
     */
    public function addDescription()
    {
        $description = $this->parseVariables($this->rule['description']);
        $title = $this->parseVariables($this->rule['h1']);
        $seo_block			= get_field('shop_seo_block', 'options');

        if ($description) {
            $text_woo 			= get_field('woocommerce', 'options');
            get_template_part('woocommerce/single-product/block', 'seo', [
                'seo_block' => [
                    'pretitle' => $seo_block['pretitle'],
                    'title' => $title,
                    'text' => wc_format_content($description),
                ],
                'text_woo'  => $text_woo
            ]);
            // printf('<div class="term-description">%s</div>', wp_kses_post(wc_format_content($description)));
        }
    }

    /**
     * Add meta description
     */
    public function addMetaDescription()
    {
        if (!defined('WPSEO_VERSION') && !defined('RANK_MATH_VERSION')) {
            $metaDescription = $this->escape($this->parseVariables($this->rule['meta_description']));

            printf("<meta name='description' content='%s'>", esc_attr($metaDescription));
        }
    }

    /**
     * Add meta description when Yoast is activated
     */
    public function addMetaDescriptionYoast($description)
    {
        $desc = $this->escape($this->parseVariables($this->rule['meta_description']));
        return $desc ?: $description;
    }

    /**
     * Add meta description when Rank Math is activated
     */
    public function addMetaDescriptionRankMath($description)
    {
        if (!empty($this->rule['meta_description'])) {
            return $this->escape($this->parseVariables($this->rule['meta_description']));
        }
        return $description;
    }

    /**
     * Add meta noindex, nofollow if checked `discourage_search` and not using Yoast Seo
     *
     * @param array $robots
     */
    public function addMetaDescourageSearch($robots)
    {
        if (is_filtered()) {
            //Discourage search engines from indexing pages created by the filter, except for pages with SEO rules.
            $isDiscourageSearchForAll = $this->isDiscourageSearchForAll();

            //get Descourage Search meta from current Seo Rule
            $metaDescourageSearch = $this->rule ? $this->escape($this->parseVariables($this->rule['discourage_search'])) : null;

            //define const of Seo Plugins
            $yoastSeo    = defined('WPSEO_VERSION');
            $allInOneSeo = defined('AIOSEO_VERSION');
            $rankMath    = defined('RANK_MATH_VERSION');

            //if not defined plugins
            $ifNotDefinedAll = !$yoastSeo && !$allInOneSeo && !$rankMath;

            //add index / follow by default for Seo Rule
            if (!empty($this->rule)) {
                $robots['index']  = ($ifNotDefinedAll) ? true : 'index';
                $robots['follow'] = ($ifNotDefinedAll) ? true : 'follow';
            }

            //add noindex/nofollow when checked Discourage search engines in Seo Rule
            //or when it isn't Seo Rule but is checked Discourage search engines from indexing pages created by the filter (on Permalinks Tab)
            if (1 === (int) $metaDescourageSearch || (empty($this->rule) && $isDiscourageSearchForAll)) {
                //add nofollow and noindex
                $robots['nofollow'] = ($ifNotDefinedAll) ? true : 'nofollow';
                $robots['noindex']  = ($ifNotDefinedAll) ? true : 'noindex';

                //remove index and follow
                unset($robots['index'], $robots['follow']);
            }
        }
        return $robots;
    }

    /**
     * Discourage search engines from indexing pages created by the filter,
     * except for pages with SEO rules.
     */
    public function isDiscourageSearchForAll()
    {
        $permalinkSettings        = get_option(FilterPlugin::OPTION_PERMALINKS_SETTINGS, array());
        $isDiscourageSearchForAll = (isset($permalinkSettings['discourage_search_all']) && 'on' === $permalinkSettings['discourage_search_all']) ? true : false;

        return $isDiscourageSearchForAll;
    }

    /**
     * Replace document title
     *
     * @return string
     */ 
    public function documentTitle($title)
    {
        $new_title = $this->escape($this->parseVariables($this->rule['title']));
        return $new_title ?: $title;
    }

    /**
     * Replace H1
     *
     * @return string
     */
    public function pageTitle()
    {
        return $this->parseVariables($this->rule['h1']);
    }

    /**
     * Add canonical for pagination and term if it isn't Seo Rule
     * And doesn't use any plugins
     */
    public function addCanonical()
    {
        if (is_filtered()) {
            //if we don't use Yoast Seo or All in One Seo or Rank Math Seo
            if (!defined('WPSEO_VERSION') && !defined('AIOSEO_VERSION') && !defined('RANK_MATH_VERSION')) {
                $canonical = $this->getCanonicalUrlForSeoRules();
                //if it is Seo Rule
                if (!empty($canonical)) {
                    printf('<link rel="canonical" href="%s" />', esc_url($canonical, null, null));
                } else {
                    $term = get_queried_object();
                    //if it is term - cannonical to term url
                    if ($term instanceof \WP_Term) {
                        $canonical = apply_filters('premmerce_filter_canonical_url', get_term_link($term), $term);
                        filter_var($canonical, FILTER_VALIDATE_URL);
                        if (is_string($canonical)) {
                            printf('<link rel="canonical" href="%s" />', esc_url($canonical, null, null));
                        }
                    }
                }
            }
        }
    }

    /**
     * Get Cannonical Url from Seo Rule
     */
    public function getCanonicalUrlForSeoRules($canonical = '')
    {
        $noPagingUrl = self::getNopagingUrl();
        $ruleByPath  = $this->ruleByPath($noPagingUrl);

        //if it is Seo Rule
        if (!empty($ruleByPath) && $ruleByPath['path'] === $noPagingUrl) {
            $canonical = esc_url(get_home_url(null, '/') . $noPagingUrl, null, null);
        }

        //add slash to the end if it is in permalinks settings (WP)
        $canonical = self::addSlashToLink($canonical);

        return $canonical;
    }

    /**
     * Add Slash to the end of link, based on Permalinks settins (WP)
     */
    public static function addSlashToLink($link = '')
    {
        $permalinkStructure = get_option('permalink_structure');
        $slash              = (!empty($permalinkStructure) && substr($permalinkStructure, -1) == '/') ? '/' : '';
        $linkWithSlash      = !empty($link) ? $link . $slash : '';

        return $linkWithSlash;
    }

    public function getCanonicalForSeoPlugins($canonical)
    {
        if (is_filtered()) {
            $canonical = $this->getCanonicalUrlForSeoRules($canonical);
        }
        return $canonical;
    }

    /**
     * Get Nopaging Url
     *
     * @return void
     */
    public static function getNopagingUrl()
    {
        $currentUrl = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
        $position   = strpos($currentUrl, '/page');

        $removeUrlPag = ($position) ? substr($currentUrl, 0, $position) : $currentUrl;
        $nopagingUrl  = trim($removeUrlPag, '/');

        return $nopagingUrl;
    }

    /**
     * Find all Discourage Rules from database
     */
    public static function findAllDiscourageRules()
    {
        global $wpdb;

        $discourageSearchRules = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, `path` FROM {$wpdb->prefix}premmerce_filter_seo WHERE discourage_search = %d",
                1
            )
        );

        return $discourageSearchRules;
    }

    /**
     * Add all discourage Rules to robots.txt
     */
    public function discourageSearchRobotsTxt($output)
    {
        $findAllDiscourageRules = self::findAllDiscourageRules();

        $array = array();

        foreach ($findAllDiscourageRules as $find) {
            $path    = $find->path;
            $array[] = 'Disallow: /' . $path;
        }

        $output .= implode("\r\n", $array);

        return $output;
    }

    /**
     * Parse variables in SEO text
     *
     * @param $text
     *
     * @return string
     */
    public function parseVariables($text)
    {
        if (empty($this->formatted)) {
            $items                                 = Container::getInstance()->getItemsManager()->getAllItems();
            $attributeItemsForSeo                  = array_filter($items, array($this, 'canBeUsedForSeoAttributeText'));
            $this->formatted['attributes']         = $this->formatAttributes($attributeItemsForSeo);
            $this->formattedAttributesReplacements = $this->formatIndividualAttributes($attributeItemsForSeo);

            $this->formatted['brands'] = $this->formatBrands($items);
            $this->formatted['prices'] = Container::getInstance()->getPriceQuery()->getPrices();
        }


        global $wp_query;

        $replacements = array(
            '{name}'        => get_queried_object()->name,
            '{count}'       => $wp_query->found_posts,
            '{description}' => get_queried_object()->description,
            '{attributes}'  => $this->formatted['attributes'],
            '{brands}'      => $this->formatted['brands'],
            '{min_price}'   => $this->formatted['prices']['min_selected'],
            '{max_price}'   => $this->formatted['prices']['max_selected'],
        );
        $replacements = array_merge($replacements, $this->formattedAttributesReplacements);
        $result       = strtr($text, $replacements);

        return trim($result);
    }

    /**
     * Format Individual Attributes
     *
     * @param  FilterInterface[] $filters
     * @return array
     */
    public function formatIndividualAttributes(array $filters)
    {
        $replacements = array();

        foreach ($filters as $filter) {
            $attributeNameVariable                = '{attribute_name_' . $filter->getSlug() . '}';
            $replacements[$attributeNameVariable] = $filter->getLabel();

            $activeItems = array_column($filter->getActiveItems(), 'title', 'id');

            $attributeValueVariable                = '{attribute_value_' . $filter->getSlug() . '}';
            $replacements[$attributeValueVariable] = implode(', ', array_values($activeItems));

            $terms = array_column($filter->getItems(), 'term_id', 'name');
            foreach ($activeItems as $id => $title) {
                $tid = isset($terms[$title]) ? $terms[$title] : $id;
                $replacements['{attribute_value_' . $filter->getSlug() . '_' . $tid . '}'] = $title;
            }
        }

        return $replacements;
    }

    /**
     * Format Brands
     *
     * @param FilterInterface[] $items
     *
     * @return string
     */
    protected function formatBrands($items)
    {
        foreach ($items as $item) {
            if (in_array($item->getId(), apply_filters('premmerce_product_filter_brand_taxonomies', array( 'product_brand' )))) {
                $item->init();
                $active = $item->getActiveItems();
                $names  = array_column($active, 'title');

                if (count($names)) {
                    return implode(', ', $names);
                }
            }
        }
    }

    /**
     * Format attributes for seo text variable
     *
     * @param $attributes
     *
     * @return string
     */
    protected function formatAttributes($attributes)
    {
        $attributeStrings = array();

        foreach ($attributes as $attribute) {
            $attribute->init();
            $items = $attribute->getActiveItems();
            $names = array_column($items, 'title');
            if (count($names)) {
                $attributeStrings[] = $attribute->getLabel() . ' (' . implode(', ', $names) . ')';
            }
        }

        return implode(', ', $attributeStrings);
    }

    /**
     * Can be Used For Seo Attribute Text
     *
     * @param FilterInterface $attribute
     *
     * @return bool
     */
    protected function canBeUsedForSeoAttributeText(FilterInterface $attribute)
    {
        return $attribute->isActive()
            && $attribute->getType() !== 'slider'
            && (taxonomy_is_product_attribute($attribute->getId()) || in_array($attribute->getSlug(), apply_filters('premmerce_product_filter_brand_taxonomies', array( 'product_brand' ))));
    }

    /**
     * Escape text for tag attribute
     *
     * @param $text
     *
     * @return string
     */
    private function escape($text)
    {
        return esc_attr(wp_strip_all_tags(stripslashes($text)));
    }
}
