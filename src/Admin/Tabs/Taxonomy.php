<?php namespace Premmerce\Filter\Admin\Tabs;

use Premmerce\Filter\Admin\Tabs\Base\SortableListTab;
use Premmerce\SDK\V2\FileManager\FileManager;

class Taxonomy extends SortableListTab
{
    /**
     * File Manager
     *
     * @var FileManager
     */
    private $fileManager;

    /**
     * Taxonomy
     *
     * @var string
     */
    private $taxonomy;

    /**
     * Default Taxonomy
     *
     * @var array
     */
    private $defaultTaxonomy = array('active' => false);

    /**
     * Items
     *
     * @var array
     */
    private $items;

    /**
     * Switched Language
     *
     * @var string
     */
    private $switchedLanguage;

    /**
     * Taxonomy constructor.
     *
     * @param FileManager $fileManager
     * @param string      $taxonomy
     */
    public function __construct(FileManager $fileManager, $taxonomy)
    {
        parent::__construct();

        $this->fileManager = $fileManager;
        $this->taxonomy    = $taxonomy;
    }

    /**
     * Add hooks
     */
    public function init()
    {
        add_action('wp_ajax_premmerce_filter_bulk_action_' . $this->taxonomy, array($this, 'bulkActionTaxonomy'));
        add_action('wp_ajax_premmerce_filter_sort_' . $this->taxonomy, array($this, 'sortTaxonomy'));
    }

    /**
     * Render tab content
     */
    public function render()
    {
        $taxonomy_instance = get_taxonomy($this->taxonomy);
        $termsConfig       = $this->getTaxonomyConfig();
        $terms             = array_replace($termsConfig, $this->getItems());
        $taxonomy          = $taxonomy_instance;
        $taxonomyName      = $this->taxonomy;
        $dataAction        = 'premmerce_filter_bulk_action_' . $this->taxonomy;
        $actions           = array(
            '-1'      => __('Bulk Actions'),
            'display' => __('Display', 'premmerce-filter'),
            'hide'    => __('Hide', 'premmerce-filter'),
        );


        //pagination data
        $paginationData = $this->paginationDataForTabs($terms);
        //new data from paginationArgsForTabs()
        $terms          = $paginationData['attr'];
        $paginationArgs = $paginationData['args'];
        $prevId         = $paginationData['prevId'];
        $nextId         = $paginationData['nextId'];

        $this->fileManager->includeTemplate(
            'admin/tabs/taxonomy.php',
            compact('terms', 'termsConfig', 'actions', 'dataAction', 'taxonomy', 'taxonomyName', 'paginationArgs', 'prevId', 'nextId')
        );
    }

    /**
     * Get Label
     *
     * @return string
     */
    public function getLabel()
    {
        $taxonomy = get_taxonomy($this->taxonomy);

        return $taxonomy->labels->menu_name;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->taxonomy;
    }

    /**
     * Valid
     *
     * @return bool
     */
    public function valid()
    {
        return taxonomy_exists($this->taxonomy);
    }

    /**
     * Ajax update taxonomy ordering
     */
    public function sortTaxonomy()
    {
        $this->sortHandler('premmerce_filter_tax_' . $this->taxonomy . '_options', $this->getTaxonomyConfig());
    }

    /**
     * Ajax bulk update taxonomy
     */
    public function bulkActionTaxonomy()
    {
        $this->bulkActionsHandler('premmerce_filter_tax_' . $this->taxonomy . '_options', $this->getTaxonomyConfig());
    }

    /**
     * Premmerce taxonomy id=>title array
     *
     * @return array
     */
    private function getItems()
    {
        if (is_null($this->items)) {
            $this->switchToDefaultLanguage();

            $terms = get_terms(
                array(
                'taxonomy'   => $this->taxonomy,
                'fields'     => 'id=>name',
                'orderby'    => 'name',
                'order'      => 'ASC',
                'hide_empty' => false,
                )
            );

            $this->switchToCurrentLanguage();

            $this->items = is_array($terms) ? $terms : array();
        }

        return $this->items;
    }

    /**
     * Get taxonomy configuration
     *
     * @return mixed
     */
    private function getTaxonomyConfig()
    {
        return $this->getConfig(
            'premmerce_filter_tax_' . $this->taxonomy . '_options',
            $this->getItems(),
            $this->defaultTaxonomy
        );
    }

    /**
     * Switch WPML to default language
     */
    private function switchToDefaultLanguage()
    {
        //check if ICL_LANGUAGE_CODE and sitepress take from WPML
        if (defined('ICL_LANGUAGE_CODE') && !empty($GLOBALS['sitepress']) && ($GLOBALS['sitepress'] instanceof \SitePress)) {
            global $sitepress;
            $adminLanguage = $sitepress->get_admin_language();

            if ($adminLanguage !== $sitepress->get_default_language()) {
                $this->switchedLanguage = $adminLanguage;
                $sitepress->switch_lang($sitepress->get_default_language());
            }
        }
    }

    /**
     * Switch WPML to current language
     */
    private function switchToCurrentLanguage()
    {
        //check if ICL_LANGUAGE_CODE and sitepress take from WPML
        if (defined('ICL_LANGUAGE_CODE') && !empty($GLOBALS['sitepress']) && ($GLOBALS['sitepress'] instanceof \SitePress)) {
            if ($this->switchedLanguage) {
                global $sitepress;
                $sitepress->switch_lang($this->switchedLanguage);
            }
        }
    }
}
