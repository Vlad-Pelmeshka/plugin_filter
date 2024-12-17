<?php namespace Premmerce\Filter\Filter\Items\Types;

use stdClass;
use WP_Taxonomy;
use Premmerce\Filter\FilterPlugin;

class TaxonomyFilter extends BaseFilter
{
    /**
     * Taxonomy
     *
     * @var WP_Taxonomy
     */
    protected $taxonomy;

    /**
     * Slug
     *
     * @var string
     */
    protected $slug;

    /**
     * Terms
     *
     * @var stdClass[]
     */
    protected $terms;

    /**
     * Hide Empty
     *
     * @var bool
     */
    protected $hideEmpty;

    /**
     * Config
     *
     * @var
     */
    protected $config;

    /**
     * TaxonomyFilter constructor.
     *
     * @param $config
     * @param $taxonomy
     */
    public function __construct($config, $taxonomy)
    {
        $this->config    = $config;
        $this->taxonomy  = $taxonomy;
        $this->hideEmpty = !empty($config['hide_empty']);

        if (in_array($this->getType(), array('radio', 'select'))) {
            $this->single = true;
        }

        $this->slug = taxonomy_is_product_attribute($this->getId()) ? substr($this->getId(), 3) : $this->getId();

        add_filter('woocommerce_product_query_tax_query', array($this, 'extendTaxQuery'));
    }

    /**
     * Unique item identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->taxonomy->name;
    }

    /**
     * Get Label
     *
     * @return string
     */
    public function getLabel()
    {
        if (taxonomy_is_product_attribute($this->taxonomy->name)) {
            return wc_attribute_label($this->taxonomy->name);
        }

        return $this->taxonomy->labels->singular_name;
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
     * Get Type
     *
     * Checkbox|radio|select|label|color
     *
     * @return string
     */
    public function getType()
    {
        return isset($this->config['type']) ? $this->config['type'] : '';
    }

    /**
     * Get Display
     *
     * Default|Dropdown|Scroll|Dropdown+Scroll
     *
     * @return string
     */
    public function getDisplay()
    {
        return isset($this->config['display_type']) ? $this->config['display_type'] : '';
    }

    /**
     * Is Visible
     *
     * @return bool
     */
    public function isVisible()
    {
        $filters = $this->getItems();

        return !empty($filters);
    }

    /**
     * Get Items
     *
     * @return array
     */
    public function getItems()
    {
        $filters = array();

        foreach ($this->getTerms() as $termKey => $term) {
            $displayCurrent = !empty($term->children) || apply_filters(
                'premmerce_filter_display_current_term_filter',
                false
            );

            if (!$this->hideEmpty || $term->count || $term->checked || $displayCurrent) {
                $filters[] = $term;
            }
        }

        return $filters;
    }

    /**
     * Get Terms
     *
     * @return array
     */
    public function getTerms()
    {
        return $this->terms ? $this->terms : array();
    }

    /**
     * Process Term
     *
     * @param $term
     *
     * @return mixed
     */
    protected function processTerm($term)
    {
        if ($this->getType() === FilterPlugin::TYPE_COLOR) {
            $term->color = null;
            if (isset($this->config['colors'][$term->term_id])) {
                $term->color = $this->config['colors'][$term->term_id];
            }
        } elseif ($this->getType() === FilterPlugin::TYPE_IMAGE) {
            $term->image = null;
            if (isset($this->config['images'][$term->term_id])) {
                $term->image = $this->config['images'][$term->term_id];
            }
        } elseif ($this->getSlug() === 'product_cat') {
            $term->children = $this->getCategoryChildren($term);
        }

        return $term;
    }

    /**
     * Get Category Children
     *
     * @param \WP_Term $term
     * @return array
     */
    protected function getCategoryChildren($term)
    {
        $taxonomyChildren = array_map('get_term', get_term_children($term->term_id, $this->getSlug()));
        $children         = array();
        $settings         = get_option(FilterPlugin::OPTION_SETTINGS, array());
        $visibleTermIds   = array_keys($this->getSettings());

        if (!empty($settings['enable_category_hierarchy'])) {
            foreach ($taxonomyChildren as $taxonomyChild) {
                if (($this->hideEmpty && 0 === $taxonomyChild->count) || !in_array($taxonomyChild->term_id, $visibleTermIds)) {
                    continue;
                }

                if ($taxonomyChild->parent === $term->term_id) {
                    $taxonomyChild->checked  = in_array($taxonomyChild->slug, $this->getSelectedValues(), true);
                    $taxonomyChild->link     = $this->getValueLink($taxonomyChild->slug);
                    $taxonomyChild->isChild  = true;
                    $taxonomyChild->products = array();
                    $this->processTerm($taxonomyChild);
                    $children[] = $taxonomyChild;
                }
            }
        }

        return $children;
    }

    /**
     * Active items for Active filters Widget
     *
     * @return array
     */
    public function getActiveItems($terms = array())
    {
        $active = array();

        if ($this->isActive()) {
            $terms = !empty($terms) ? $terms : $this->getTerms();
            foreach ($terms as $term) {
                if (!empty($term->children)) {
                    $active = array_merge($active, $this->getActiveItems($term->children));
                }


                if ($term->checked) {
                    $active[] = array(
                        'title' => $term->name,
                        'link' => $term->link,
                    );
                }
            }
        }

        return $active;
    }

    /**
     * Get Active Products
     *
     * @return array
     */
    public function getActiveProducts(array $terms = array())
    {
        $products = array();
        $terms    = !empty($terms) ? $terms : $this->terms;

        foreach ($terms as $term) {
            if ($term->checked) {
                $products += $term->products;
            }

            if (!empty($term->children)) {
                $products += $this->getActiveProducts($term->children);
            }
        }

        return $products;
    }

    /**
     * Extend Tax Query
     *
     * @param array $taxQuery
     *
     * @return mixed
     */
    public function extendTaxQuery($taxQuery)
    {
        $values = $this->getSelectedValues();

        if (!empty($values)) {
            $taxonomyQuery = array(
                'taxonomy'         => $this->getId(),
                'field'            => 'slug',
                'terms'            => $values,
                'operator'         => 'IN',
                'include_children' => true,
                'hide_empty'       => false
            );

            $taxQuery[] = $taxonomyQuery;
        }

        return $taxQuery;
    }

    /**
     * Init
     *
     * @return void
     */
    public function init()
    {
        if (null === $this->terms) {
            $terms       = $this->loadTerms();
            $activeTerms = $this->getSelectedValues();

            foreach ($terms as $key => $term) {
                $term->checked  = in_array($term->slug, $activeTerms, true);
                $term->link     = $this->getValueLink($term->slug);
                $term->products = array();
                $term->isChild  = false;
                $this->processTerm($term);
            }

            $this->terms = $terms;
        }
    }

    /**
     * Load Terms
     *
     * @return array
     */
    protected function loadTerms()
    {
        $settings = $this->getSettings();
        $options  = get_option(FilterPlugin::OPTION_SETTINGS, array());
        $termIds  = array_keys($settings);

        $terms = array();

        if (count($termIds)) {
            if (!empty($options['enable_category_hierarchy'])) {
                $query['parent'] = 0;
            }

            $query['taxonomy']   = $this->taxonomy->name;
            $query['orderby']    = 'include';
            $query['include']    = $termIds;
            $query['hide_empty'] = isset($options['hide_empty']) && !empty($options['hide_empty']) ? true : false;

            $terms = get_terms(
                apply_filters(
                    'premmerce_filter_get_terms_' . $this->taxonomy->name . '_query',
                    $query,
                    $this->getSelectedValues()
                )
            );
        }

        return is_array($terms) ? $terms : array();
    }

    public function getResetUrl()
    {
        return apply_filters('premmerce_filter_get_reset_url_' . $this->taxonomy->name, parent::getResetUrl());
    }

    /**
     * Get Settings
     *
     * @return array
     */
    protected function getSettings()
    {
        return array_filter(
            get_option('premmerce_filter_tax_' . $this->taxonomy->name . '_options', array()),
            static function ($item) {
                return isset($item['active']) && $item['active'];
            }
        );
    }

    /**
     * Get Config
     */
    public function getConfig()
    {
        return $this->config;
    }
}
