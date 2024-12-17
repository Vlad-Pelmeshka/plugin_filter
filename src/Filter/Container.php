<?php namespace Premmerce\Filter\Filter;

use Premmerce\Filter\Cache\Cache;
use Premmerce\Filter\Filter\Items\ItemFactory;
use Premmerce\Filter\Filter\Items\ItemsManager;
use Premmerce\Filter\Filter\Query\PriceQuery;
use Premmerce\Filter\Filter\Query\ProductsQuery;
use Premmerce\Filter\Filter\Query\QueryHelper;
use Premmerce\Filter\FilterPlugin;
use Premmerce\SDK\V2\FileManager\FileManager;

class Container
{
    /**
     * Instance
     *
     * @var Container
     */
    private static $instance;

    /**
     * Services
     *
     * @var array
     */
    private $services = array();

    /**
     * Options
     *
     * @var array
     */
    private $options = array();

    /**
     * Get Instance
     *
     * @return Container
     */
    public static function getInstance()
    {
        $getInstance = self::$instance ? self::$instance : self::$instance = new self();
        return $getInstance;
    }

    /**
     * Container constructor.
     */
    private function __construct()
    {
        $this->options['items']      = get_option(FilterPlugin::OPTION_ATTRIBUTES, array());
        $this->options['colors']     = get_option(FilterPlugin::OPTION_COLORS, array());
        $this->options['images']     = get_option(FilterPlugin::OPTION_IMAGES, array());
        $this->options['settings']   = get_option(FilterPlugin::OPTION_SETTINGS, array());
        $this->options['permalinks'] = get_option(FilterPlugin::OPTION_PERMALINKS_SETTINGS, array());
    }

    /**
     * Get File Manager
     */
    public function getFileManager()
    {
        if (! isset($this->services['file_manager'])) {
            $this->addService('file_manager', new FileManager($this->getService('file_manager')));
        }

        return $this->getService('file_manager');
    }

    /**
     * Get Item Renderer
     *
     * @return mixed
     */
    public function getItemRenderer()
    {
        if (! isset($this->services['renderer'])) {
            $this->addService('renderer', new ItemRenderer($this->getFileManager()));
        }

        return $this->getService('renderer');
    }

    /**
     * Get Query Helper
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
     * Get Product Query
     *
     * @return ProductsQuery
     */
    public function getProductQuery()
    {
        if (! isset($this->services['product_query'])) {
            $this->addService('product_query', new ProductsQuery($this->getCache(), $this->getQueryHelper()));
        }

        return $this->getService('product_query');
    }

    /**
     * Get Price Query
     *
     * @return PriceQuery
     */
    public function getPriceQuery()
    {
        if (! isset($this->services['price_query'])) {
            $this->addService('price_query', new PriceQuery($this->getCache(), $this->getQueryHelper()));
        }

        return $this->getService('price_query');
    }

    /**
     * Get Cache
     *
     * @return Cache
     */
    public function getCache()
    {
        if (! isset($this->services['cache'])) {
            $this->addService('cache', new Cache());
        }

        return $this->getService('cache');
    }

    /**
     * Get Item Factory
     *
     * @return ItemFactory
     */
    public function getItemFactory()
    {
        if (! isset($this->services['item_factory'])) {
            $this->addService('item_factory', new ItemFactory());
        }

        return $this->getService('item_factory');
    }

    /**
     * Get Items Manager
     *
     * @return ItemsManager
     */
    public function getItemsManager()
    {
        if (! isset($this->services['items_manager'])) {
            $this->addService('items_manager', new ItemsManager($this));
        }

        return $this->getService('items_manager');
    }

    /**
     * Get Service
     *
     * @param  mixed $key
     * @return void
     */
    public function getService($key)
    {
        if (isset($this->services[$key])) {
            return $this->services[$key];
        }
    }

    /*
     * Add Service
     *
     * @param  mixed $key
     * @param  mixed $service
     * @return void
     */
    public function addService($key, $service)
    {
        $this->services[$key] = $service;
    }

    /**
     * Get Option
     *
     * @param  mixed $key
     * @return void
     */
    public function getOption($key)
    {
        return $this->options[$key];
    }

    /**
     * Add Option
     *
     * @param  mixed $key
     * @param  mixed $option
     * @return void
     */
    public function addOption($key, $option)
    {
        $this->options[$key] = $option;
    }
}
