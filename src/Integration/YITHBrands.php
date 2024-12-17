<?php

namespace Premmerce\Filter\Integration;

class YITHBrands
{
    public function init()
    {
        add_filter('premmerce_product_filter_brand_taxonomies', function ($taxonomies) {
            $taxonomies[] = 'yith_product_brand';

            return $taxonomies;
        });
    }
}
