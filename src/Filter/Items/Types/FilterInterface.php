<?php namespace Premmerce\Filter\Filter\Items\Types;

use stdClass;

interface FilterInterface
{
    /**
     * Unique item identifier
     *
     * @return string
     */
    public function getId();

    /**
     * Item label
     *
     * @return string
     */
    public function getLabel();

    /**
     * Slug used for get param name
     *
     * @return string
     */
    public function getSlug();

    /**
     * Filter type
     * checkbox|radio|select|label|color
     *
     * @return string
     */
    public function getType();

    /**
     * Item type
     *
     * Default|Dropdown|Scroll|Dropdown+Scroll
     *
     * @return string
     */
    public function getDisplay();

    /**
     * Get Items
     *
     * @string $item->term_id    - Filter label;
     * @string $item->name       - Filter label;
     * @string $item->slug       - Slug;
     * @string $item->count      - Count Items;
     * @string $item->checked    - Is selected;
     * @string $item->link       - Select link
     *
     * @return stdClass[]
     */
    public function getItems();

    /**
     * Get Active Items
     *
     * @return array
     */
    public function getActiveItems();

    /**
     * Is Visible
     *
     * @return boolean
     */
    public function isVisible();

    /**
     * Is Active
     *
     * @return boolean
     */
    public function isActive();

    /**
     * Get Options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Get Reset Url
     *
     * @return string
     */
    public function getResetUrl();

    /**
     * Init filter items
     *
     * @return void
     */
    public function init();
}
