<?php namespace Premmerce\Filter\Seo;

class Query
{
    const TYPE_RESULTS = 1;

    const TYPE_ROW = 2;

    const TYPE_COLUMN = 3;

    const TYPE_VAR = 4;

    /**
     * Where
     *
     * @var array
     */
    protected $where;

    /**
     * Offset
     *
     * @var int
     */
    protected $offset;

    /**
     * Limit
     *
     * @var int
     */
    protected $limit;

    /**
     * Return Type
     *
     * @var int
     */
    protected $returnType;

    /**
     * Join
     *
     * @var string
     */
    protected $join;

    /**
     * DB
     *
     * @var wpdb
     */
    protected $db;

    /**
     * Table
     *
     * @var string
     */
    protected $table;

    /**
     * Alias
     *
     * @var string
     */
    protected $alias;

    /**
     * Query constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->db = $wpdb;
    }

    /**
     * Reset
     *
     * @return $this
     */
    public function reset()
    {
        $this->where      = null;
        $this->offset     = null;
        $this->limit      = null;
        $this->returnType = null;
        $this->join       = null;

        return $this;
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
        $rule = $this->where(array('id' => $id))->returnType(self::TYPE_ROW)->get();

        return $rule;
    }

    /**
     * Where
     *
     * @param array  $columns
     *
     * @param string $separator
     *
     * @return $this
     */
    public function where($columns, $separator = '=')
    {
        if (count($columns)) {
            $where = $this->implodeKeys($columns, ' ' . $separator . ' %s', ' AND ');

            if (! $this->where) {
                $whereString = ' WHERE ';
            } else {
                $whereString = ' AND ';
            }

            $this->where .= $this->db->prepare($whereString . $where, $columns) . ' ';
        }

        return $this;
    }

    /**
     * Like
     *
     * @param array $columns
     *
     * @return $this
     */
    public function like($columns)
    {
        return $this->where($columns, 'LIKE');
    }

    /**
     * Alias
     *
     * @param string $alias
     *
     * @return $this
     */
    public function alias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Offset
     *
     * @param int $offset
     *
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Limit
     *
     * @param int $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Join
     *
     * @param string $table
     * @param string $internalKey
     * @param string $externalKey
     * @param string $joinType
     *
     * @return Query
     */
    public function join($table, $internalKey, $externalKey = null, $joinType = 'INNER')
    {
        if (is_null($externalKey)) {
            $externalKey = $internalKey;
        }

        $this->join .= " $joinType JOIN $table  ON {$internalKey} = {$externalKey} ";

        return $this;
    }

    /**
     * Join Raw
     *
     * @param $rawQuery
     *
     * @return Query
     */
    public function joinRaw($rawQuery)
    {
        $this->join .= " {$rawQuery} ";

        return $this;
    }

    /**
     * Return Type
     *
     * @param int $return
     *
     * @return $this
     */
    public function returnType($return)
    {
        $this->returnType = $return;

        return $this;
    }

    /**
     * Get
     *
     * @param $fields
     *
     * @return mixed
     */
    public function get($fields = null)
    {
        $sql = $this->getSql($fields);

        $returnType = $this->returnType;

        $this->reset();

        switch ($returnType) {
            case self::TYPE_ROW:
                return $this->db->get_row($sql, ARRAY_A);
            case self::TYPE_COLUMN:
                return $this->db->get_col($sql);
            case self::TYPE_VAR:
                return $this->db->get_var($sql);
            case self::TYPE_RESULTS:
            default:
                return $this->db->get_results($sql, ARRAY_A);
        }
    }

    public function getSql($fields = null)
    {
        $sql = $this->select($fields) . ' FROM ' . $this->table . $this->getAliasQuery() . $this->join . $this->where . $this->limitOffset();

        return $sql;
    }

    /**
     * Remove
     *
     * @param array $ids
     *
     * @return false|int
     */
    public function remove($ids)
    {
        $placeholders = $this->generatePlaceholders($ids, '%d');

        $sql = $this->db->prepare("DELETE FROM {$this->table} WHERE id IN {$placeholders}", $ids);

        return $this->db->query($sql);
    }

    /**
     * Update Bulk
     *
     * @param array $ids
     * @param array $values
     *
     * @return false|int
     */
    public function updateBulk($ids, $values)
    {
        $placeholders = $this->generatePlaceholders($ids, '%d');

        $set = $this->implodeKeys($values, '=%s', ',');

        $placeholderValues = array_merge(array_values($values), $ids);

        $sql = $this->db->prepare("UPDATE {$this->table} SET {$set} WHERE id IN {$placeholders}", $placeholderValues);

        return $this->db->query($sql);
    }

    /**
     * Count
     *
     * @return null|string
     */
    public function count()
    {
        return $this->returnType(self::TYPE_VAR)->get(array('count(id)'));
    }

    /**
     * Get Alias Query
     *
     * @return null|string
     */
    protected function getAliasQuery()
    {
        return $this->alias ? ' AS ' . $this->alias . ' ' : '';
    }

    /**
     * Limit Offset
     *
     * @return string
     */
    protected function limitOffset()
    {
        $offsetLimit = '';

        if ($this->limit > 0) {
            $offsetLimit .= " LIMIT {$this->limit} ";
        }
        if ($this->offset > 0) {
            $offsetLimit .= " OFFSET {$this->offset} ";
        }

        return $offsetLimit;
    }

    /**
     * Generate Placeholders
     *
     * @param array  $values
     * @param string $placeholder
     *
     * @return string
     */
    protected function generatePlaceholders($values, $placeholder = '%s')
    {
        return '(' . implode(', ', array_fill(0, count($values), $placeholder)) . ')';
    }

    /**
     * Implode Keys
     *
     * @param array  $columns
     * @param string $separator
     * @param string $and
     *
     * @return string
     */
    protected function implodeKeys($columns, $separator, $and)
    {
        return implode("{$separator}{$and}", array_keys($columns)) . $separator;
    }

    /**
     * Select
     *
     * @param string|array $fields
     *
     * @return string
     */
    protected function select($fields)
    {
        if (is_array($fields)) {
            $fields = implode(', ', $fields);
        } elseif (is_null($fields)) {
            $fields = '*';
        }


        return ' SELECT ' . $fields . ' ';
    }

    /**
     * Drop
     *
     * @return false|int
     */
    protected function drop()
    {
        $sql = "DELETE TABLE IF EXISTS {$this->table}";

        return $this->db->query($sql);
    }
}
