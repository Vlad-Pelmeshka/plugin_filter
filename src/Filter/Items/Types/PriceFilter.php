<?php namespace Premmerce\Filter\Filter\Items\Types;

use Premmerce\Filter\FilterPlugin;
use Premmerce\Filter\Filter\Query\PriceQuery;

class PriceFilter extends BaseFilter
{
    /**
     * Slug
     *
     * @var string
     */
    protected $slug = 'price';

    /**
     * Options
     *
     * @var array
     */
    protected $options = array('min' => 0, 'max' => 0);

    /**
     * Price Query
     *
     * @var PriceQuery
     */
    private $priceQuery;

    /**
     * PriceFilter constructor.
     *
     * @param PriceQuery $priceQuery
     */
    public function __construct($priceQuery)
    {
        $this->priceQuery = $priceQuery;
    }

    /**
     * Unique item identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->slug;
    }

    /**
     * Get Label
     *
     * @return string
     */
    public function getLabel()
    {
        return __('Filter by price', 'premmerce-filter');
    }

    /**
     * Get Slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Get Type checkbox|radio|select|label|color
     *
     * @return string
     */
    public function getType()
    {
        return FilterPlugin::TYPE_SLIDER;
    }

    /**
     * Get Active Items
     *
     * @return array
     */
    public function getActiveItems()
    {
        $url = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';

        $values = $this->getSelectedValues();

        $activeFilters = array();

        if (key_exists('min_selected', $values)) {
            $link = remove_query_arg('min_' . $this->getSlug(), $url);
            /* translators: %s: min number */
            $title           = sprintf(__('Min %s', 'premmerce-filter'), wc_price($values['min_selected']));
            $activeFilters[] = array('title' => $title, 'link' => esc_url($link), 'id' => $this->getId());
        }

        if (key_exists('max_selected', $values)) {
            $link = remove_query_arg('max_' . $this->getSlug(), $url);
            /* translators: %s: max number */
            $title           = sprintf(__('Max %s', 'premmerce-filter'), wc_price($values['max_selected']));
            $activeFilters[] = array('title' => $title, 'link' => esc_url($link), 'id' => $this->getId());
        }

        return $activeFilters;
    }

    /**
     * Get Selected Values
     *
     * @return void
     */
    public function getSelectedValues()
    {
        return $this->priceQuery->getSelectedValues();
    }

    /**
     * Init
     *
     * @return void
     */
    public function init()
    {
        $this->options = $this->priceQuery->getPrices();
    }

    /**
     * Get Options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Is Visible
     *
     * @return boolean
     */
    public function isVisible()
    {
        $options = $this->getOptions();

        return $options['min'] !== $options['max'];
    }
}
