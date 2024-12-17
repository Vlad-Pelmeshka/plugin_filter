<?php namespace Premmerce\Filter\Seo;

class RulesGenerator
{
    /**
     * Seo Model
     *
     * @var SeoModel
     */
    private $model;

    const KEY_GENERATION_ITEMS = 'premmerce_filter_generation_items';

    const KEY_GENERATION_SETTINGS = 'premmerce_filter_generation_settings';

    /**
     * RulesGenerator constructor.
     *
     * @param SeoModel $model
     */
    public function __construct(SeoModel $model)
    {
        $this->model = $model;
    }

    /**
     * Process next item
     *
     * @return int
     */
    public function next()
    {
        $items    = get_option(self::KEY_GENERATION_ITEMS);
        $settings = get_option(self::KEY_GENERATION_SETTINGS);

        $item = array_shift($items);

        update_option(self::KEY_GENERATION_ITEMS, $items);

        $rule          = array_merge($item, $settings);
        $rule['terms'] = $this->termsToRuleFormat($rule['terms']);

        $this->model->save($rule);

        $count = count($items);

        if (0 === $count) {
            delete_option(self::KEY_GENERATION_ITEMS);
            delete_option(self::KEY_GENERATION_SETTINGS);
        }

        return $count;
    }

    /**
     * Terms To Rule Format
     *
     * @param array $terms
     *
     * @return array
     */
    public function termsToRuleFormat($terms)
    {
        $result = array();

        foreach ($terms as $termId) {
            $term = get_term((int) $termId);

            if ($term instanceof \WP_Term) {
                $result[$term->taxonomy][] = $termId;
            }
        }

        return $result;
    }

    /**
     * Start
     *
     * @param array $request
     *
     * @return array
     */
    public function start($request)
    {
        $items = $this->generateTermCombinations($request);

        update_option(self::KEY_GENERATION_ITEMS, $items);
        update_option(
            self::KEY_GENERATION_SETTINGS,
            array(
                'h1'                => isset($request['h1']) ? $request['h1'] : '',
                'title'             => isset($request['title']) ? $request['title'] : '',
                'meta_description'  => isset($request['meta_description']) ? $request['meta_description'] : '',
                'description'       => isset($request['description']) ? $request['description'] : '',
                'discourage_search' => isset($request['discourage_search']) ? $request['discourage_search'] : '',
                'enabled'           => isset($request['enabled']) ? $request['enabled'] : ''
            )
        );

        return $items;
    }

    /**
     * Generate Term Combinations
     *
     * @param array $data
     *
     * @return array
     */
    public function generateTermCombinations($data)
    {
        $taxonomies    = $data['filter_taxonomy'];
        $categories    = $data['filter_category'];
        $selectedTerms = $data['filter_term'];

        $terms = $this->getTermsByTaxonomy($taxonomies, $selectedTerms);
        $terms = $this->createRelations($terms);

        return $this->addCategories($categories, $terms);
    }

    /**
     * Create Relations
     *
     * @param array $terms
     *
     * @return array
     */
    private function createRelations($terms)
    {
        $list = array();
        foreach ($terms as $ids) {
            $list = array_merge($list, $ids);
        }

        return $this->powerSet($list, 1, $terms);
    }

    /**
     * Power Set
     *
     * @param $in
     * @param int   $minLength
     * @param array $matrix
     *
     * @return array
     */
    private function powerSet($in, $minLength = 1, $matrix = array())
    {
        $return = array();
        for ($i = 1 << count($in); --$i;) {
            $out = array();
            foreach ($in as $j => $jValue) {
                if ($i >> $j & 1) {
                    $out[] = $jValue;
                }
            }
            if ((count($out) >= $minLength) && $this->isUnique($out, $matrix)) {
                $return[] = $out;
            }
        }

        return $return;
    }

    /**
     * Is Unique
     *
     * @param array $out
     * @param array $matrix
     *
     * @return bool
     */
    private function isUnique($out, $matrix)
    {
        foreach ($matrix as $dimension) {
            $matches = 0;
            foreach ($out as $item) {
                $matches += (int) in_array($item, $dimension, true);
            }
            if ($matches > 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add categories
     *
     * @param array $categories
     * @param array $terms
     *
     * @return array
     */
    private function addCategories($categories, $terms)
    {
        $result = array();
        foreach ($categories as $category) {
            foreach ($terms as $items) {
                $result[] = array('term_id' => $category, 'terms' => $items);
            }
        }

        return $result;
    }

    /**
     * Get Terms By Taxonomy
     *
     * @param array $taxonomies
     * @param array $selectedTerms
     *
     * @return array
     */
    public function getTermsByTaxonomy($taxonomies, $selectedTerms)
    {
        $result = array();
        foreach ($taxonomies as $index => $taxonomy) {
            if (! isset($result[$index])) {
                $result[$index] = array();
            }
            if (! empty($selectedTerms[$index])) {
                $result[$index] = $selectedTerms[$index];
                continue;
            }

            $terms = get_terms(
                array(
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                    'fields'     => 'ids',
                )
            );

            $result[$index] = array_merge($result[$index], $terms);
        }

        return $result;
    }
}
