<?php namespace Premmerce\Filter\Seo;

class SeoTermModel extends Query
{
    /**
     * SeoTermModel constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->table = $this->db->prefix . 'premmerce_filter_seo_terms';
    }

    /**
     * Add Terms
     *
     * @param int   $ruleId
     * @param array $termIds
     *
     * @return bool
     */
    public function addTerms($ruleId, $termIds)
    {
        $dbTerms = $this->where(array('rule_id' => $ruleId))->returnType(SeoModel::TYPE_COLUMN)->get(array('term_id'));

        $add    = array_diff($termIds, $dbTerms);
        $remove = array_diff($dbTerms, $termIds);

        $result = true;
        foreach ($remove as $id) {
            $result = $result && (bool) $this->db->delete($this->table, array('rule_id' => $ruleId, 'term_id' => $id));
        }

        foreach ($add as $id) {
            $result = $result && (bool) $this->db->insert($this->table, array('rule_id' => $ruleId, 'term_id' => $id));
        }

        return $result;
    }

    /**
     * Get terms with taxonomies by rule id
     *
     * @param $ruleId
     *
     * @return array
     */
    public function getTermsTaxonomiesByRule($ruleId)
    {
        $results = $this
            ->alias('t')
            ->join($this->db->term_taxonomy . ' AS tt', 't.term_id', 'tt.term_id')
            ->where(array('rule_id' => $ruleId))
            ->get(array('t.term_id', 'taxonomy'));

        $taxonomyTerms = array();

        foreach ($results as $termTaxonomy) {
            $taxonomyTerms[$termTaxonomy['taxonomy']][] = $termTaxonomy['term_id'];
        }

        return $taxonomyTerms;
    }

    /**
     * Remove terms by rule ids
     *
     * @param $ids
     *
     * @return false|int
     */
    public function removeByRuleIds($ids)
    {
        $placeholders = $this->generatePlaceholders($ids, '%d');

        $sql = $this->db->prepare("DELETE FROM {$this->table} WHERE rule_id IN {$placeholders}", $ids);

        return $this->db->query($sql);
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
                `rule_id` INT(11) NOT NULL,
                `term_id` INT(11) NOT NULL,
                PRIMARY KEY (rule_id, term_id)
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
