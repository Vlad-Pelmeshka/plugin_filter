<?php namespace Premmerce\Filter\Seo;

use WP_Error;

class SeoModel extends Query
{
    /**
     * Terms
     *
     * @var SeoTermModel
     */
    private $terms;

    const TABLE = 'premmerce_filter_seo';

    /**
     * SeoModel constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = $this->db->prefix . self::TABLE;

        $this->terms = new SeoTermModel();
    }

    /**
     * Find
     *
     * @param int $id
     *
     * @return array|null|object
     */
    public function find($id)
    {
        $rule = parent::find($id);

        if (is_array($rule)) {
            $rule['terms'] = $this->getTerms($id);

            return $rule;
        }

        return $rule;
    }

    /**
     * Get Terms
     *
     * @param int $id
     *
     * @return array
     */
    public function getTerms($id)
    {
        return $this->terms->getTermsTaxonomiesByRule($id);
    }

    /**
     * Get created_at field by ID
     *
     * @param  mixed $id
     * @param  mixed $timeType
     * @return void
     */
    public static function getSeoRuleTime($id, $timeType = 'created_at')
    {
        global $wpdb;

        if (! in_array($timeType, array( 'created_at', 'modified_at'))) {
            $timeType = 'created_at';
        }

        if ('created_at' === $timeType) {
            $result = $wpdb->get_results($wpdb->prepare("SELECT ID, created_at FROM {$wpdb->prefix}premmerce_filter_seo WHERE ID = %d", $id));
        } else {
            $result = $wpdb->get_results($wpdb->prepare("SELECT ID, modified_at FROM {$wpdb->prefix}premmerce_filter_seo WHERE ID = %d", $id));
        }

        //if time is 0000-00-00 - add current time.
        //it is for old fields created before.
        if (! isset($result[0]) || '0000-00-00 00:00:00' === $result[0]->{$timeType}) {
            $time = current_time('Y-m-d H:i:s');
        } else {
            $time = $result[0]->{$timeType};
        }

        return $time;
    }

    /**
     * Save
     *
     * @param array $data
     * @param bool  $validate
     *
     * @return array|int|WP_Error|null
     */
    public function save(array $data, $validate = true, $update = false)
    {
        if ($validate) {
            $data = $this->validate($data, ! $update);

            if (null === $data) {
                return null;
            }

            if ($data instanceof WP_Error) {
                return $data;
            }

            $id = empty($data['id']) ? null : $data['id'];
            $terms = $data['terms'];

            //if it is not premium plan - return null
            if (!premmerce_pwpf_fs()->can_use_premium_code()) {
                return null;
            }

            // This IF block will be auto removed from the Free version.
            if (premmerce_pwpf_fs()->is__premium_only()) {
                //add time fields (created_at and modified_at)
                $currentTime = current_time('Y-m-d H:i:s');
                //if $id exist take created_at from DB or update it to current time
                $dateArray = array(
                    'created_at' => ($id) ? self::getSeoRuleTime($id, 'created_at') : $currentTime,
                    'modified_at' => $currentTime
                );
                $data      = array_merge($data, $dateArray);

                //check if seo rule exist - update, if not - create new
                if ($id) {
                    $id = $this->update($id, $data);
                } else {
                    $id = $this->create($data);
                }


                if (false !== $id) {
                    $ids = call_user_func_array('array_merge', array_values($terms));

                    $this->terms->addTerms($id, $ids);

                    return $id;
                }
            }
        }
    }

    /**
     * Create
     *
     * @param array $data
     *
     * @return int|null
     */
    public function create(array $data)
    {
        $result = $this->db->insert($this->table, $this->sanitize($data, true));

        if (false !== $result) {
            $id = $this->db->insert_id;
            do_action('premmerce_filter_seo_rule_created', array('id' => $result));

            return $id;
        }
    }

    /**
     * Update
     *
     * @param int     $id
     * @param array   $data
     * @param boolean $defaultData - take default data for sanitize or not
     *
     * @return int|null
     */
    public function update($id, array $data, $defaultData = true)
    {
        $result = $this->db->update($this->table, $this->sanitize($data, $defaultData), array('id' => $id));

        if (false !== $result) {
            do_action('premmerce_filter_seo_rule_updated', array('id' => $id));

            return $id;
        }
    }

    /**
     * Enable
     *
     * @param array $ids
     *
     * @return false|int
     */
    public function enable($ids)
    {
        return $this->updateBulk($ids, array('enabled' => 1));
    }

    /**
     * Disable
     *
     * @param array $ids
     *
     * @return false|int
     */
    public function disable($ids)
    {
        return $this->updateBulk($ids, array('enabled' => 0));
    }

    /**
     * Validate
     *
     * @param $array
     *
     * @return array|WP_Error|null
     */
    private function validate($array, $validatee_count = true)
    {
        // check terms first
        if (! isset($array['terms']) || empty($array['terms'])) {
            return new WP_Error('terms_required', __('Terms are required', 'premmerce-filter'));
        }

        if (empty($array['term_id'])) {
            return new WP_Error('category_required', __('Category is required', 'premmerce-filter'));
        }

        $counter = $this->getCountFromRule($array);

        if ($validatee_count && 0 === $counter) {
            return null;
        }

        $path = $this->generatePath($array);
        $rule = $this->where(array('path' => $path))->returnType(self::TYPE_VAR)->get(array('id'));

        $idIsSame = isset($array['id']) && (int) $array['id'] === (int) $rule;

        if ($rule && !$idIsSame) {
            return new WP_Error('unique_rule', __('The same rule already exists', 'premmerce-filter'));
        }

        return $array;
    }

    /**
     * Get Count From Rule
     *
     * @param  array $rule
     * @return int
     */
    private function getCountFromRule($rule)
    {
        $taxQuery   = array(
            'relation' => 'AND'
        );
        $taxQuery[] = array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => (int) $rule['term_id']
        );

        foreach ($rule['terms'] as $term => $ids) {
            $taxQuery[] = array(
                'taxonomy' => $term,
                'field' => 'term_id',
                'terms' => $ids,
            );
        }

        $products = new \WP_Query(
            array(
                'post_type' => array('product'),
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'tax_query' => $taxQuery
            )
        );
        return $products->post_count;
    }

    /**
     * Sanitize rule and fill generated fields
     *
     * @param $array
     * @param bool $addDefaults
     *
     * @return array
     */
    private function sanitize($array, $addDefaults = true)
    {
        $data = array();
        if (isset($array['terms']) && isset($array['term_id'])) {
            $array['path']  = $this->generatePath($array);
            $array['label'] = $this->generateLabel($array);
        }

        $defaults = array(
            'term_id' => '',
            'h1' => '',
            'title' => '',
            'meta_description' => '',
            'description' => '',
            'enabled' => 0,
            'discourage_search' => 0,
            'data' => '',
            'label' => '',
            'path' => '',
            'created_at' => '',
            'modified_at' => '',

        );

        foreach ($defaults as $key => $default) {
            if (array_key_exists($key, $array)) {
                $data[$key] = $this->sanitizeField($key, $array[$key]);
            } elseif ($addDefaults) {
                $data[$key] = $default;
            }
        }

        return $data;
    }

    /**
     * Sanitize Field
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool|string
     */
    private function sanitizeField($key, $value)
    {
        switch ($key) {
            case 'enabled':
                return !empty($value);
            case 'discourage_search':
                return !empty($value);
            case 'title':
            case 'meta_description':
                return esc_html($value);
            case 'description':
                return wp_unslash($value);
            default:
                return $value;
        }
    }

    /**
     * Generate path for rule
     *
     * @param $ruleData
     *
     * @return mixed
     */
    public function generatePath($ruleData)
    {
        $link = get_term_link((int) $ruleData['term_id']);

        $terms = $ruleData['terms'];

        foreach ($terms as $tax => $termIds) {
            $filterName = $tax;


            if ((taxonomy_is_product_attribute($tax))) {
                $filterName = substr($tax, 3);
            }

            $taxonomyFilter    = 'filter_' . $filterName;
            $taxonomyQueryType = 'query_type_' . $filterName;

            $slugs = $this->getTermSlugsByIds($tax, $termIds);

            $link = add_query_arg($taxonomyFilter, implode(',', $slugs), $link);
            $link = add_query_arg($taxonomyQueryType, 'or', $link);
        }

        $link = apply_filters('premmerce_filter_term_link', $link);

        return trim(parse_url($link)['path'], '/');
    }

    /**
     * Generate text from rule attributes
     *
     * @param $data
     *
     * @return string
     */
    public function generateLabel($data)
    {
        $category = get_term($data['term_id']);

        $text = $category->name . ': ';


        foreach ($data['terms'] as $taxonomyName => $items) {
            $taxonomy = get_taxonomy($taxonomyName);

            $terms = get_terms(array('include' => $items, 'taxonomy' => $taxonomyName, 'hide_empty' => false));

            $terms = array_map(
                function ($term) {
                    return $term->name;
                },
                $terms
            );
            $text .= sprintf('<b>%s:</b> (%s) ', $taxonomy->labels->singular_name, implode(', ', $terms));
        }

        return trim($text);
    }

    /**
     * Remove
     *
     * @param  mixed $ids
     * @return void
     */
    public function remove($ids)
    {
        parent::remove($ids);

        $this->terms->removeByRuleIds($ids);

        do_action('premmerce_filter_seo_bulk_rules_removed', array('ids' => $ids));
    }

    public function updateBulk($ids, $values)
    {
        $result = parent::updateBulk($ids, $values);

        do_action('premmerce_filter_seo_bulk_rules_updated', array('ids' => $ids, 'values' => $values));

        return $result;
    }

    /**
     * Get Term Slugs By Ids
     *
     * @param $taxonomy
     * @param $termIds
     *
     * @return array|int|WP_Error
     */
    private function getTermSlugsByIds($taxonomy, $termIds)
    {
        $termIds = array_map('intval', $termIds);
        $slugs   = get_terms(
            array(
                'taxonomy' => $taxonomy,
                'fields' => 'id=>slug',
                'include' => $termIds,
                'hide_empty' => false,
            )
        );

        return $slugs;
    }

    /**
     * Create table
     *
     * @return array
     */
    public function install()
    {
        $charsetCollate = $this->db->get_charset_collate();

        $sql = "CREATE TABLE {$this->table} (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `term_id` INT(11) NOT NULL,
                `path` varchar(2083) NOT NULL,
                `h1` text ,
                `title` text ,
                `meta_description` text,
                `description` text,
                `label` text,
                `enabled` int(1) DEFAULT 0,
                `discourage_search` int(1) DEFAULT 0,
                `created_at` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
                `modified_at` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
                `data` text,
                PRIMARY KEY  (id)
            ) {$charsetCollate};";

        include_once ABSPATH . 'wp-admin/includes/upgrade.php';

        return dbDelta($sql);
    }

    /**
     * Drop table
     *
     * @return false|int
     */
    public function uninstall()
    {
        return $this->drop();
    }
}
