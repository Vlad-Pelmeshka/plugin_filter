<?php namespace Premmerce\Filter\Filter\Items;

use Premmerce\Filter\FilterPlugin;
use Premmerce\Filter\Filter\Query\QueryHelper;
use Premmerce\Filter\Filter\Items\Types\OnSaleFilter;
use Premmerce\Filter\Filter\Items\Types\RatingFilter;
use Premmerce\Filter\Filter\Items\Types\SliderFilter;
use Premmerce\Filter\Filter\Items\Types\InStockFilter;
use Premmerce\Filter\Filter\Items\Types\TaxonomyFilter;
use Premmerce\Filter\Filter\Items\Types\AttributeFilter;
use Premmerce\Filter\Filter\Items\Types\FilterInterface;

class ItemFactory
{
    /**
     * Color Options
     *
     * @var array
     */
    private $colorOptions;

    /**
     * Image Options
     *
     * @var array
     */
    private $imageOptions;

    /**
     * Services
     *
     * @var array
     */
    private $services = array();

    /**
     * Attributes
     *
     * @var array
     */
    private $attributes;

    /**
     * Get Service
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getService($key)
    {
        if (isset($this->services[$key])) {
            return $this->services[$key];
        }
    }

    /**
     * Add Service
     *
     * @param string $key
     * @param mixed  $service
     */
    public function addService($key, $service)
    {
        $this->services[$key] = $service;
    }

    /**
     * Query Helper
     *
     * @return QueryHelper
     */
    public function getQueryHelper()
    {
        if (! isset($this->services['query_helper'])) {
            $this->addService('query_helper', new QueryHelper());
        }

        return $this->getService('query_helper');
    }

    /**
     * Construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->colorOptions = get_option(FilterPlugin::OPTION_COLORS, array());
        $this->imageOptions = get_option(FilterPlugin::OPTION_IMAGES, array());
    }

    /**
     * Create Item
     *
     * @param string $id
     * @param array  $config
     *
     * @return null|FilterInterface
     */
    public function createItem($id, $config)
    {
        $type = $config['type'];

        $taxonomy  = null;
        $attribute = null;
        $item      = null;

        $attribute = $this->getAttribute($id);

        if ($attribute) {
            $taxonomy = get_taxonomy(wc_attribute_taxonomy_name($attribute->attribute_name));
        } elseif (taxonomy_exists($id)) {
            $taxonomy = get_taxonomy($id);
        }

        //This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            if (premmerce_pwpf_fs()->can_use_premium_code()) {
                $inStock      = null;
                $onSale       = null;
                $ratingFilter = null;

                if ('show_in_stock' === $id) {
                    $inStock = true;
                } elseif ('show_on_sale' === $id) {
                    $onSale = true;
                } elseif ('show_rating_filter' === $id) {
                    $ratingFilter = true;
                }

                if (FilterPlugin::TYPE_COLOR === $type && $taxonomy) {
                    if (isset($this->colorOptions[$taxonomy->name])) {
                        $config['colors'] = $this->colorOptions[$taxonomy->name];
                    }
                }
                if (FilterPlugin::TYPE_IMAGE === $type && $taxonomy) {
                    if (isset($this->imageOptions[$taxonomy->name])) {
                        $config['images'] = $this->imageOptions[$taxonomy->name];
                    }
                }
            }
        }

        if (FilterPlugin::TYPE_SLIDER === $type && $taxonomy) {
            $item = new SliderFilter($config, $taxonomy);
        } elseif ($attribute && $taxonomy) {
            $item = new AttributeFilter($config, $attribute);
        } elseif ($taxonomy) {
            $item = new TaxonomyFilter($config, $taxonomy);
        }

        //This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            if (premmerce_pwpf_fs()->can_use_premium_code()) {
                if ($inStock) {
                    $item = new InStockFilter($config, $this->getQueryHelper());
                } elseif ($onSale) {
                    $item = new OnSaleFilter($config, $this->getQueryHelper());
                } elseif ($ratingFilter) {
                    $item = new RatingFilter($config, $this->getQueryHelper());
                }
            }
        }


        return apply_filters("filter_item_{$type}", $item, $config);
    }

    /**
     * Get Attribute
     *
     * @param $id
     *
     * @return mixed
     */
    private function getAttribute($id)
    {
        $at = $this->getAttributes();

        if (array_key_exists($id, $at)) {
            return $at[$id];
        }
    }

    /**
     * Get Attributes
     *
     * @return array
     */
    private function getAttributes()
    {
        if (null === $this->attributes) {
            $this->attributes = array();
            foreach (wc_get_attribute_taxonomies() as $item) {
                $this->attributes[$item->attribute_id] = $item;
            }
        }

        return $this->attributes;
    }
}
