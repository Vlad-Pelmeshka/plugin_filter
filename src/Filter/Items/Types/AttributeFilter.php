<?php namespace Premmerce\Filter\Filter\Items\Types;

use Premmerce\Filter\FilterPlugin;

class AttributeFilter extends TaxonomyFilter
{
    /**
     * Attribute
     */
    private $attribute;

    /**
     * AttributeFilter construct
     */
    public function __construct($config, $attribute)
    {
        $this->attribute = $attribute;
        parent::__construct($config, get_taxonomy(wc_attribute_taxonomy_name($attribute->attribute_name)));
    }

    /**
     * Load Terms
     *
     * @return array
     */
    public function loadTerms()
    {
        $options = get_option(FilterPlugin::OPTION_SETTINGS, array());
        $query   = array('taxonomy' => $this->taxonomy->name);

        $orderBy = $this->attribute->attribute_orderby;

        if (in_array($orderBy, array('id', 'name'))) {
            $query['orderby']    = $orderBy;
            $query['menu_order'] = false;
        }

        $query['hide_empty'] = isset($options['hide_empty']) && !empty($options['hide_empty']) ? true : false;

        $terms = get_terms($query);

        if (! is_array($terms)) {
            return array();
        }

        switch ($orderBy) {
            case 'parent':
                usort($terms, '_wc_get_product_terms_parent_usort_callback');
                break;

            case 'name_num':
                usort($terms, '_wc_get_product_terms_name_num_usort_callback');
                break;
        }

        return $terms;
    }

    /**
     * Extend Tax Query
     *
     * @param array $taxQuery
     *
     * @return array|mixed
     */
    public function extendTaxQuery($taxQuery)
    {
        return $taxQuery;
    }
}
