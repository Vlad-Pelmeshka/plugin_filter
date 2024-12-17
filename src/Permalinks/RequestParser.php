<?php namespace Premmerce\Filter\Permalinks;

use Premmerce\Filter\Exceptions\FilterException;
use Premmerce\Filter\Exceptions\InvalidSlugsOrderException;
use Premmerce\Filter\Exceptions\TermNotFoundException;

class RequestParser
{
    /**
     * Permalinks Manager
     *
     * @var PermalinksManager
     */
    private $pm;

    /**
     * Woo Attribute Prefix
     *
     * @var string
     */
    private $wooAttributePrefix = 'pa_';

    /**
     * Woo Filter Prefix
     *
     * @var string
     */
    private $wooFilterPrefix = 'filter_';

    /**
     * Woo Query Type Prefix
     *
     * @var string
     */
    private $wooQueryTypePrefix = 'query_type_';

    /**
     * Real Path Info
     *
     * @var string
     */
    private $realPathInfo;

    /**
     * RequestParser constructor.
     *
     * @param PermalinksManager $permalinksManager
     */
    public function __construct(PermalinksManager $permalinksManager)
    {
        $this->pm = $permalinksManager;
    }

    /**
     * 'parse_request' action handler
     */
    public function resetPathInfo()
    {
        if (null !== $this->realPathInfo) {
            $this->setRequestUri($this->realPathInfo);
        }
    }

    /**
     * 'do_parse_request' action handler
     *
     * @param bool $parse
     *
     * @return bool
     */
    public function parse($parse)
    {
        if (! $parse) {
            return $parse;
        }

        $path = $this->getPathInfo();

        list($filterSegments, $extraSegments) = $this->getFilterSegmentsFromPath($path);


        if (empty($filterSegments)) {
            return $parse;
        }

        try {
            $this->checkCorrectOrder($filterSegments);

            foreach ($filterSegments as $taxonomy => $propertySegment) {
                $valueSeparator = $this->pm->getValueSeparator();

                $values = $this->getValues($propertySegment, $taxonomy, $valueSeparator);

                $this->addFilterToQuery($taxonomy, $values);
            }
        } catch (FilterException $e) {
            return $parse;
        }

        $this->fixPathInfo(reset($filterSegments), $extraSegments);

        return $parse;
    }

    /**
     * Check Correct Order
     *
     * @param $filterSegments
     *
     * @throws InvalidSlugsOrderException
     */
    private function checkCorrectOrder($filterSegments)
    {
        $values = array_values($filterSegments);

        $path       = implode('/', $values);
        $customSort = apply_filters('premmerce_filter_permalink_sort_slug', array(), $values, $path);
        if (empty($customSort)) {
            sort($values);
        } else {
            $values = $customSort;
        }
        $sortedPath = implode('/', $values);

        if (apply_filters('premmerce_filter_permalink_sort_slug_valid', $path !== $sortedPath, $path, $sortedPath)) {
            throw new InvalidSlugsOrderException();
        }
    }

    /**
     * Fix Path Info
     *
     * @param string $firstSegment
     * @param array  $extraSegments
     */
    private function fixPathInfo($firstSegment, $extraSegments)
    {
        $path = $this->getPathInfo();

        $firstFilterSegmentPosition = mb_strpos(mb_strtolower($path), $firstSegment);

        if (false !== $firstFilterSegmentPosition) {
            $path = mb_substr($path, 0, $firstFilterSegmentPosition);
        }

        $path = trailingslashit($path) . implode('/', $extraSegments);

        $path = user_trailingslashit($path);

        $this->realPathInfo = apply_filters('premmerce_filter_permalink_realpath', $this->getRequestUri());

        $this->setRequestUri($path . $this->getQueryString());
    }

    /**
     * Get Path Info
     *
     * @return string
     */
    private function getPathInfo()
    {
        $path     = $this->getRequestUri();
        $queryPos = mb_strpos($path, '?');

        if (false !== $queryPos) {
            $path = mb_substr($path, 0, $queryPos);
        }

        return apply_filters('premmerce_filter_permalink_pathinfo', $path, $this);
    }

    /**
     * Get Query String
     *
     * @return bool|string
     */
    private function getQueryString()
    {
        $path = $this->getRequestUri();

        $queryPos = mb_strpos($path, '?');

        $string = '';
        if (false !== $queryPos) {
            $string = mb_substr($path, $queryPos);
        }

        return $string;
    }

    /**
     * Set Request Uri
     *
     * @param string $path
     */
    public function setRequestUri($path)
    {
        $_SERVER['REQUEST_URI'] = $path;
    }

    /**
     * Get Request Uri
     *
     * @return mixed
     */
    public function getRequestUri()
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';

        return $requestUri;
    }

    /**
     * Get Filter Segments From Path
     *
     * @param string $path
     *
     * @return array|bool
     */
    private function getFilterSegmentsFromPath($path)
    {
        $filteredSegments = apply_filters('premmerce_filter_permalink_segments_early', array(), $path, $this);

        if (!empty($filteredSegments)) {
            return $filteredSegments;
        }

        $firstFilterPosition = mb_strpos($path, '/' . $this->pm->getPrefix());
        $taxonomyPrefixes    = $this->pm->getTaxonomyPrefixes();


        $taxonomyPrefixes = array_filter($taxonomyPrefixes);

        $positions = array();

        if (false !== $firstFilterPosition) {
            $positions[] = $firstFilterPosition;
        }

        foreach ($taxonomyPrefixes as $prefix) {
            $position = mb_strpos($path, '/' . $prefix);
            if (false !== $position) {
                $positions[] = $position;
            }
        }

        if (empty($positions)) {
            return false;
        }

        $firstFilterPosition = min(array_values($positions));

        $propertyUrlPart = trim(mb_substr($path, $firstFilterPosition), '/');

        $urlSegments = explode('/', $propertyUrlPart);

        $urlSegments = array_map('sanitize_title', $urlSegments);

        $filterSegments = array();
        $extraSegments  = array();

        foreach ($urlSegments as $pos => $segment) {
            $isPrefixed = mb_strpos($segment, $this->pm->getPrefix()) === 0;

            $currentTaxonomy = null;

            foreach ($taxonomyPrefixes as $taxonomy => $value) {
                if (mb_strpos($segment, $value) === 0) {
                    $currentTaxonomy = $taxonomy;
                }
            }

            $taxonomy = $this->getTaxonomy($segment);

            if (! $isPrefixed && ! $currentTaxonomy) {
                $extraSegments[] = $segment;
            } elseif ($currentTaxonomy) {
                $filterSegments[$currentTaxonomy] = $segment;
            } elseif ($taxonomy) {
                $filterSegments[$taxonomy] = $segment;
            }
        }

        return apply_filters('premmerce_filter_permalink_segments', array($filterSegments, $extraSegments), $path, $this);
    }

    /**
     * Add Filter To Query
     *
     * @param string $taxonomy
     * @param array  $values
     */
    private function addFilterToQuery($taxonomy, $values)
    {
        $values = array_map('urldecode', $values);

        $_GET[$this->wooFilterPrefix . $taxonomy]    = implode(',', $values);
        $_GET[$this->wooQueryTypePrefix . $taxonomy] = 'or';

        $_REQUEST[$this->wooFilterPrefix . $taxonomy]    = implode(',', $values);
        $_REQUEST[$this->wooQueryTypePrefix . $taxonomy] = 'or';
    }

    /**
     * Get Values
     *
     * @param $propertySegment
     * @param $taxonomy
     * @param $valueSeparator
     *
     * @return array
     * @throws TermNotFoundException
     */
    private function getValues($propertySegment, $taxonomy, $valueSeparator)
    {
        $propertyString = $this->pm->getPrefix() . $taxonomy . $this->pm->getPropertySeparator();

        if ($this->isTaxonomy($taxonomy)) {
            $taxonomyName = $taxonomy;
            if ($this->pm->getTaxonomyPrefix($taxonomy)) {
                $propertyString = $this->pm->getTaxonomyPrefix($taxonomy);
            }
        } else {
            $taxonomyName = 'pa_' . $taxonomy;
        }


        $terms = get_terms(array('taxonomy' => $taxonomyName, 'hide_empty' => false, 'fields' => 'id=>slug'));

        $valuesString = sanitize_title(mb_substr($propertySegment, mb_strlen($propertyString)));

        /* replacement for cases where value slug contains separator:
         * - slug-separator
         * - slug-separator-slug
         * - separator-slug
         *
         * replacement for cases where value slug contains other value slug and separator:
         * slug 1: aa
         * slug 2: aa-separator
         *
         */
        foreach ($terms as $term) {
            //term has separator
            if (mb_strpos($term, '-') !== false) {
                $pos = strrpos($valuesString, $term);

                //term slug in string
                if (false !== $pos) {
                    $replacement = str_replace('-', '?', $term);
                    //replace only one occurrence
                    $valuesString = substr_replace($valuesString, $replacement, $pos, mb_strlen($term));
                }
            }
        }

        $values = explode($valueSeparator, $valuesString);


        // replace back:
        array_walk(
            $values,
            function (&$value) use ($terms) {
                $value = str_replace('?', '-', $value);
            }
        );

        foreach ($values as $value) {
            if (! in_array($value, $terms)) {
                throw new TermNotFoundException("term {$value} not found");
            }
        }

        return $values;
    }

    /**
     * Get Taxonomy
     *
     * @param string $urlSegment
     *
     * @return string
     */
    private function getTaxonomy($urlSegment)
    {
        $prefix = $this->pm->getPrefix();

        $urlSegment = mb_substr($urlSegment, mb_strlen($prefix));

        while (false !== ($pos = mb_strripos($urlSegment, $this->pm->getPropertySeparator()))) {
            $urlSegment = mb_substr($urlSegment, 0, $pos);

            if ($this->attributeExists($urlSegment)) {
                return $urlSegment;
            }
        }
    }

    /**
     * Attribute Exists
     *
     * @param string $attributeName
     *
     * @return bool
     */
    private function attributeExists($attributeName)
    {
        if ($this->isTaxonomy($attributeName)) {
            return true;
        }

        return taxonomy_exists($this->wooAttributePrefix . $attributeName);
    }

    public function isTaxonomy($taxonomy)
    {
        return taxonomy_exists($taxonomy);
    }

    /**
     * Get Permalink Manager
     *
     * @return PermalinksManager
     */
    public function getPermalinkManager()
    {
        return $this->pm;
    }
}
