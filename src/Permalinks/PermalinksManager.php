<?php namespace Premmerce\Filter\Permalinks;

use Premmerce\Filter\Filter\Filter;

class PermalinksManager
{
    const DEFAULT_PREFIX = 'attribute-';

    const DEFAULT_OR = '-or-';

    /**
     * Prefix
     *
     * @var string
     */
    private $prefix;

    /**
     * Taxonomy Prefixes
     *
     * @var array
     */
    private $taxonomyPrefixes;

    /**
     * Value Separator
     *
     * @var array
     */
    private $valueSeparator;

    /**
     * Default Query Type
     *
     * @var string
     */
    private $defaultQueryType = 'or';

    /**
     * Property Separator
     *
     * @var string
     */
    private $propertySeparator = '-';

    /**
     * Generator
     *
     * @var Generator
     */
    private $generator;

    /**
     * Request Parser
     *
     * @var RequestParser
     */
    private $parser;

    /**
     * Settings
     *
     * @var mixed
     */
    private $settings;

    /**
     * Permalinks Manager constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings = array())
    {
        $this->valueSeparator = ! empty($settings['or_separator']) ? $settings['or_separator'] : self::DEFAULT_OR;

        $this->prefix = ! empty($settings['slug_prefix']) ? $settings['slug_prefix'] : self::DEFAULT_PREFIX;

        $this->settings  = $settings;
        $this->generator = new Generator($this);
        $this->parser    = new RequestParser($this);

        add_filter('premmerce_filter_term_link', array($this->generator, 'generate'));
        add_filter('parse_request', array($this->parser, 'resetPathInfo'));
        add_filter('do_parse_request', array($this->parser, 'parse'));
    }

    /**
     * Get Prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Get Value Separator
     *
     * @return string
     */
    public function getValueSeparator()
    {
        return $this->valueSeparator;
    }

    /**
     * Get Property Separator
     *
     * @return string
     */
    public function getPropertySeparator()
    {
        return $this->propertySeparator;
    }

    /**
     * Get Default Query Type
     *
     * @return string
     */
    public function getDefaultQueryType()
    {
        return $this->defaultQueryType;
    }

    /**
     * Get Taxonomy Prefixes
     *
     * @return void
     */
    public function getTaxonomyPrefixes()
    {
        if (is_null($this->taxonomyPrefixes)) {
            $this->taxonomyPrefixes = array();


            foreach (Filter::$taxonomies as $taxonomy) {
                if (! empty($this->settings[$taxonomy . '_prefix'])) {
                    $this->taxonomyPrefixes[$taxonomy] = $this->settings[$taxonomy . '_prefix'];
                }
            }
        }

        return $this->taxonomyPrefixes;
    }

    /**
     * Get Taxonomy Prefix
     *
     * @param string $taxonomy
     *
     * @return string|null
     */
    public function getTaxonomyPrefix($taxonomy)
    {
        $prefixes = $this->getTaxonomyPrefixes();

        if (key_exists($taxonomy, $prefixes)) {
            return $prefixes[$taxonomy];
        }

        return null;
    }
}
