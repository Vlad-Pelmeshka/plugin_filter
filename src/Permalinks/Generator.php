<?php namespace Premmerce\Filter\Permalinks;

class Generator
{
    /**
     * Permalinks Manager
     *
     * @var PermalinksManager
     */
    private $pm;

    /**
     * Generator constructor.
     *
     * @param PermalinksManager $permalinksManager
     */
    public function __construct(PermalinksManager $permalinksManager)
    {
        $this->pm = $permalinksManager;
    }

    /**
     * Generate
     *
     * @param string $link
     *
     * @return string
     */
    public function generate($link)
    {
        $parts = explode('?', $link);

        if (count($parts) !== 2) {
            return $link;
        }

        list($path, $get) = $parts;

        $query = array();
        $parts = array();

        parse_str($get, $query);


        $attributes = $this->getFilters($query);

        foreach ($attributes as $name => $value) {
            unset($query['filter_' . $name]);
            unset($query['query_type_' . $name]);

            if (strpos($value, ',')) {
                $values = explode(',', $value);
                $values = array_unique($values);
                sort($values);

                $separator = $this->pm->getValueSeparator();
                $value     = implode($separator, $values);
            }

            $prefix = $this->pm->getTaxonomyPrefix($name);

            if ($prefix) {
                $property = $prefix;
            } else {
                $property = $this->pm->getPrefix() . $name . $this->pm->getPropertySeparator();
            }

            $parts[] = apply_filters('premmerce_filter_permalink_filter_url_part', ($property . $value), $name, $value, $this->pm);
        }

        $customSort = apply_filters('premmerce_filter_permalink_sort_url', array(), $parts, $path);
        if (empty($customSort)) {
            sort($parts);
        } else {
            $parts = $customSort;
        }

        if (is_front_page()) {
            $path = get_permalink(wc_get_page_id('shop'));
        }

        $path = apply_filters('premmerce_filter_permalink_base_path', $path);
        $path = trailingslashit($path) . implode('/', $parts);
        $path = user_trailingslashit($path);

        $path = add_query_arg($query, $path);

        return apply_filters('premmerce_filter_permalink_filter_url', $path, $this, $this->pm);
    }

    /**
     * Get Filters
     *
     * @param array $query
     *
     * @return array
     */
    private function getFilters(array $query)
    {
        $filters = array();

        foreach ($query as $key => $value) {
            if (strpos($key, 'filter_') === 0) {
                $filter           = substr($key, strlen('filter_'));
                $filters[$filter] = $value;
            }
        }

        return $filters;
    }
}
