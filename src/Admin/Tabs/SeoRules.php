<?php namespace Premmerce\Filter\Admin\Tabs;

use Premmerce\Filter\Seo\SeoModel;
use Premmerce\Filter\Seo\RulesTable;
use Premmerce\Filter\Seo\WPMLHelper;
use Premmerce\Filter\Seo\RulesGenerator;
use Premmerce\SDK\V2\FileManager\FileManager;
use Premmerce\SDK\V2\Notifications\AdminNotifier;
use Premmerce\Filter\Admin\Tabs\Base\BaseSettings;
use Premmerce\Filter\Admin\Tabs\Base\TabInterface;

class SeoRules implements TabInterface
{
    /**
     * File Manager
     *
     * @var FileManager
     */
    private $fileManager;

    /**
     * Model
     *
     * @var SeoModel
     */
    private $model;

    /**
     * Admin Notifier
     *
     * @var AdminNotifier
     */
    private $notifier;

    /**
     * Rules Generator
     *
     * @var RulesGenerator
     */
    private $generator;

    const KEY_UPDATE_PATHS = 'premmerce_filter_update_paths';

    /**
     * SeoRules constructor.
     *
     * @param FileManager   $fileManager
     * @param AdminNotifier $notifier
     */
    public function __construct(FileManager $fileManager, AdminNotifier $notifier)
    {
        $this->fileManager = $fileManager;
        $this->model       = new SeoModel();
        $this->notifier    = $notifier;

        // This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            $this->generator = new RulesGenerator($this->model);
        }
    }

    /**
     * Register hooks
     */
    public function init()
    {
        // This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            add_action('admin_post_premmerce_filter_seo_create', array($this, 'create__premium_only'));
            add_action('admin_post_premmerce_filter_seo_update', array($this, 'update__premium_only'));
            add_action('wp_ajax_premmerce_filter_seo_generate_next', array($this, 'generateNext__premium_only'));
            add_action('wp_ajax_premmerce_filter_seo_update_next', array($this, 'updateNext__premium_only'));
        }
        add_action('wp_ajax_get_taxonomy_terms', array($this, 'getTaxonomyTerms'));
    }

    /**
     * Create rule
     */
    public function create__premium_only()
    {
        $postData = isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field($_POST['_wpnonce'])) ? $_POST : null;
        $result   = $this->model->save($postData);

        if (null === $result) {
            $this->notifier->flash(__('Rule does not contains products', 'premmerce-filter'), AdminNotifier::WARNING);
        } elseif ($result instanceof \WP_Error) {
            $this->notifier->flash($result->get_error_message(), AdminNotifier::ERROR);
        } else {
            $this->notifier->flash(__('Rule created', 'premmerce-filter'), AdminNotifier::SUCCESS);
        }

        //clear cache when change rules. Example, clear Math Rank Seo sitemap cache.
        Cache::clearOtherwisePluginsCache();

        $this->redirectBack();
    }

    /**
     * Update rule
     */
    public function update__premium_only()
    {
        $postData = isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field($_POST['_wpnonce'])) ? $_POST : array();
        $id       = $this->model->save($postData, true, true);

        if (null === $id) {
            $this->notifier->flash(__('Rule does not contains products', 'premmerce-filter'), AdminNotifier::WARNING);
        } elseif ($id instanceof \WP_Error) {
            $this->notifier->flash($id->get_error_message(), AdminNotifier::ERROR);
        } else {
            $this->notifier->flash(__('Rule updated', 'premmerce-filter'), AdminNotifier::SUCCESS);
        }

        //clear cache when change rules. Example, clear Math Rank Seo sitemap cache.
        Cache::clearOtherwisePluginsCache();

        $this->redirectBack();
    }

    /**
     * Ajax get terms
     */
    public function getTaxonomyTerms()
    {
        $terms = get_terms(
            array(
                'taxonomy'   => isset($_POST['taxonomy']) && isset($_POST['ajax_nonce']) && wp_verify_nonce(sanitize_text_field($_POST['ajax_nonce']), 'filter-ajax-nonce') ? wc_clean(wp_unslash($_POST['taxonomy'])) : null,
                'hide_empty' => false,
            )
        );

        if ($terms instanceof \WP_Error) {
            $terms = array();
        }

        $output = array('results' => array());

        foreach ($terms as $term) {
            list($id, $text, $slug) = array_values((array) $term);
            $output['results'][]    = array('id' => $id, 'text' => $text, 'slug' => $slug, 'taxonomy' => $term->taxonomy);
        }

        echo json_encode($output);
        wp_die();
    }

    /**
     * Render tab content
     */
    public function render()
    {
        $action = isset($_REQUEST['action']) ? wc_clean(wp_unslash($_REQUEST['action'])) : null;

        switch ($action) {
            case 'edit':
                $this->renderEdit__premium_only();
                break;
            case 'generate_rules':
                $this->renderGenerate__premium_only();
                break;
            case 'update_paths':
                $this->startUpdatePathsProgress__premium_only();
                break;
            case 'generation_progress':
                $this->startGenerationProgress__premium_only();
                break;
            default:
                $this->renderList();
                break;
        }
    }

    /**
     * Generate next rule
     */
    public function generateNext__premium_only()
    {
        $count = $this->generator->next();

        if (!$count) {
            $this->notifier->flash(__('Generation completed', 'premmerce-filter'), AdminNotifier::SUCCESS);
        }

        $this->handleProgress__premium_only($count);
    }

    /**
     * Update next rule
     */
    public function updateNext__premium_only()
    {
        $rules = get_option(self::KEY_UPDATE_PATHS, array());

        $ruleId = array_shift($rules);

        update_option(self::KEY_UPDATE_PATHS, $rules);

        $rule = $this->model->returnType(SeoModel::TYPE_ROW)->where(array('id' => $ruleId))->get(array('id', 'term_id'));

        if ($rule) {
            $this->model->update(
                $rule['id'],
                array(
                    'terms'   => $this->model->getTerms($rule['id']),
                    'term_id' => $rule['term_id']
                ),
                false
            );
        }

        $count = count($rules);

        if (!$count) {
            delete_option(self::KEY_UPDATE_PATHS);
            $this->notifier->flash(__('Rules are updated', 'premmerce-filter'), AdminNotifier::SUCCESS);
        }

        $this->handleProgress__premium_only($count);
    }

    /**
     * Handle Progress
     *
     * @param $count
     */
    public function handleProgress__premium_only($count)
    {
        if ($count > 0) {
            echo json_encode(array('status' => 'next'));
            wp_die();
        }
        echo json_encode(array('status' => 'complete'));
        wp_die();
    }

    /**
     * Page with update progress bar
     */
    public function startUpdatePathsProgress__premium_only()
    {
        $join = WPMLHelper::joinTermWithWPMLCurrentTranslation('r.term_id');

        $rules = $this->model->alias('r')->joinRaw($join)->returnType(SeoModel::TYPE_COLUMN)->get(array('id'));
        $max   = count($rules);

        if (!$max) {
            $this->redirectBack();
        }

        update_option(self::KEY_UPDATE_PATHS, $rules);


        $action   = 'premmerce_filter_seo_update_next';
        $complete = menu_page_url('premmerce-filter-admin', false) . '&tab=seo';

        $this->fileManager->includeTemplate(
            'admin/seo/generate-progress.php',
            compact('max', 'action', 'complete')
        );
    }

    /**
     * Page with generation progress bar
     */
    public function startGenerationProgress__premium_only()
    {
        $results = $this->generator->start($_REQUEST);

        $max = count($results);


        if (!$max) {
            $this->redirectBack();
        }


        $action   = 'premmerce_filter_seo_generate_next';
        $complete = menu_page_url('premmerce-filter-admin', false) . '&tab=seo';

        $this->fileManager->includeTemplate(
            'admin/seo/generate-progress.php',
            compact('max', 'action', 'complete')
        );
    }

    /**
     * Bulk generation
     */
    public function renderGenerate__premium_only()
    {
        $attributes = $this->getAttributes();

        $categories = get_terms(
            array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'fields' => 'id=>name'
            )
        );

        $this->fileManager->includeTemplate(
            'admin/seo/generate-rules.php',
            compact('attributes', 'categories')
        );
    }

    /**
     * Render edit page
     */
    public function renderEdit__premium_only()
    {
        $id   = isset($_GET['id']) ? wc_clean(wp_unslash($_GET['id'])) : null;
        $rule = $this->model->find($id);

        $categoriesDropDownArgs = $this->getCategoryDropdownArgs();

        $categoriesDropDownArgs['selected'] = $rule['term_id'];

        $attributes = $this->getAttributes();

        $this->fileManager->includeTemplate(
            'admin/seo/form.php',
            compact('rule', 'categoriesDropDownArgs', 'attributes')
        );
    }

    /**
     * Render rules list
     */
    public function renderList()
    {
        $categoriesDropDownArgs = $this->getCategoryDropdownArgs();
        $attributes             = $this->getAttributes();

        $table = new RulesTable($this->fileManager, $this->model);

        $rule = array(
            'id'                => '',
            'term_id'           => '',
            'path'              => '',
            'h1'                => '',
            'title'             => '',
            'meta_description'  => '',
            'description'       => '',
            'enabled'           => 1,
            'discourage_search' => 0,
            'data'              => null,
        );

        $this->fileManager->includeTemplate(
            'admin/tabs/seo.php',
            array(
                'categoriesDropDownArgs' => $categoriesDropDownArgs,
                'attributes' => $attributes,
                'rulesTable' => $table,
                'rule' => $rule,
                'fm' => $this->fileManager,
            )
        );
    }

    /**
     * Tab label
     *
     * @return string
     */
    public function getLabel()
    {
        $text = __('SEO Rules', 'premmerce-filter');

        $seoLabel = BaseSettings::premiumForTabLabel($text);

        return $seoLabel;
    }

    /**
     * Tab name
     *
     * @return string
     */
    public function getName()
    {
        return 'seo';
    }

    /**
     * Is tab valid
     *
     * @return bool
     */
    public function valid()
    {
        return true;
    }

    /**
     * Arguments for category select
     *
     * @return array
     */
    private function getCategoryDropdownArgs()
    {
        $categoriesDropDownArgs = array(
            'hide_empty' => 0,
            'hide_if_empty' => false,
            'taxonomy' => 'product_cat',
            'name' => 'term_id',
            'orderby' => 'name',
            'hierarchical' => true,
            'show_option_none' => false,
            'echo' => 0
        );

        $categoriesDropDownArgs = apply_filters(
            'taxonomy_parent_dropdown_args',
            $categoriesDropDownArgs,
            'product_cat',
            'new'
        );

        return $categoriesDropDownArgs;
    }

    /**
     * Get attributes for term selects
     *
     * @return array
     */
    private function getAttributes()
    {
        $wcAttributes = wc_get_attribute_taxonomies();

        $attributes = array();
        foreach ($wcAttributes as $attribute) {
            $attributes['pa_' . $attribute->attribute_name] = $attribute->attribute_label;
        }

        $brand_taxonomies = apply_filters('premmerce_product_filter_brand_taxonomies', array( 'product_brand' ));

        foreach ($brand_taxonomies as $brand_taxonomy) {
            if (taxonomy_exists($brand_taxonomy)) {
                $brandTaxonomy                      = get_taxonomy($brand_taxonomy);
                $attributes[ $brandTaxonomy->name ] = $brandTaxonomy->label;
            }
        }

        return $attributes;
    }

    /**
     * Redirect to previous page
     */
    private function redirectBack()
    {
        wp_safe_redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null);
        die;
    }
}
