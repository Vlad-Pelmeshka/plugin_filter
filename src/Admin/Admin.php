<?php namespace Premmerce\Filter\Admin;

use Premmerce\Filter\FilterPlugin;
use Premmerce\Filter\Admin\Tabs\Cache;
use Premmerce\Filter\Admin\Tabs\SeoRules;
use Premmerce\Filter\Admin\Tabs\Settings;
use Premmerce\Filter\Admin\Tabs\Taxonomy;
use Premmerce\Filter\Admin\Tabs\SimpleTab;
use Premmerce\Filter\Admin\Tabs\Attributes;
use Premmerce\Filter\Admin\Tabs\TabRenderer;
use Premmerce\Filter\Integration\YITHBrands;
use Premmerce\SDK\V2\FileManager\FileManager;
use Premmerce\SDK\V2\Notifications\AdminNotifier;
use Premmerce\Filter\Admin\Tabs\BundleAndSave;
use Premmerce\Filter\Admin\Tabs\PermalinkSettings;

/**
 * Class Admin
 *
 * @package Premmerce\Filter\Admin
 */
class Admin
{
    /**
     * File Manager
     *
     * @var FileManager
     */
    private $fileManager;

    /**
     * Settings Page
     *
     * @var string
     */
    private $settingsPage;

    /**
     * Tab Renderer
     *
     * @var TabRenderer
     */
    private $tabRenderer;

    /**
     * Admin Notifier
     *
     * @var AdminNotifier
     */
    private $notifier;

    const ADMIN_FREE_PAGE_SLUG = 'premmerce_page_premmerce-filter-admin';

    const META_IGNORE_BANNER = 'premmerce_url_manager_ignore_banner';

    /**
     * Admin constructor.
     *
     * Register menu items and handlers
     *
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
        $this->notifier    = new AdminNotifier();
        $this->tabRenderer = new TabRenderer($this->fileManager);

        ( new YITHBrands() )->init();

        add_action(
            'init',
            function () {
                $this->initBanner();
                $this->initTabs();
                $this->checkPermalinks();
            },
            11
        );

        $this->settingsPage = 'premmerce-filter-admin';

        add_action('admin_enqueue_scripts', array($this, 'registerAssets'), 11);
        add_action('admin_menu', array($this, 'addMenuPage'), 80);


        add_filter(
            'set-screen-option',
            function ($status, $option, $value) {
                if ('filter_per_page' == $option) {
                    return $value;
                }
            },
            10,
            3
        );

        add_filter('admin_footer_text', array($this, 'removeFooterAdmin'));
    }

    /**
     * Options page
     */
    public function options()
    {
        $this->fileManager->includeTemplate('admin/macros.php');

        $this->tabRenderer->render();
    }

    /**
     * Check permalinks for seo tab
     */
    private function checkPermalinks()
    {
        $current = $this->tabRenderer->current();

        if (in_array($current, array('seo', 'seo_settings'))) {
            $permalinkOptions = get_option(FilterPlugin::OPTION_PERMALINKS_SETTINGS);
            $permalinksOff    = empty($permalinkOptions['permalinks_on']);

            if ($permalinksOff) {
                $this->notifier->push(
                    sprintf('%1$s %2$s', __('To use SEO rules, please enable permalinks for filtered URLs.', 'premmerce-filter'), ' <a href="' . admin_url('admin.php?page=premmerce-filter-admin&tab=permalinks') . '">' . __('Enable Permalinks', 'premmerce-filter') . '</a>'),
                    AdminNotifier::WARNING
                );
            }
        }
    }

    /**
     * Add submenu to premmerce menu page
     */
    public function addMenuPage()
    {
        global $admin_page_hooks;

        $premmerceMenuExists = isset($admin_page_hooks['premmerce']);

        if (! $premmerceMenuExists) {
            $svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="20" height="16" style="fill:#82878c" viewBox="0 0 20 16"><g id="Rectangle_7"> <path d="M17.8,4l-0.5,1C15.8,7.3,14.4,8,14,8c0,0,0,0,0,0H8h0V4.3C8,4.1,8.1,4,8.3,4H17.8 M4,0H1C0.4,0,0,0.4,0,1c0,0.6,0.4,1,1,1 h1.7C2.9,2,3,2.1,3,2.3V12c0,0.6,0.4,1,1,1c0.6,0,1-0.4,1-1V1C5,0.4,4.6,0,4,0L4,0z M18,2H7.3C6.6,2,6,2.6,6,3.3V12 c0,0.6,0.4,1,1,1c0.6,0,1-0.4,1-1v-1.7C8,10.1,8.1,10,8.3,10H14c1.1,0,3.2-1.1,5-4l0.7-1.4C20,4,20,3.2,19.5,2.6 C19.1,2.2,18.6,2,18,2L18,2z M14,11h-4c-0.6,0-1,0.4-1,1c0,0.6,0.4,1,1,1h4c0.6,0,1-0.4,1-1C15,11.4,14.6,11,14,11L14,11z M14,14 c-0.6,0-1,0.4-1,1c0,0.6,0.4,1,1,1c0.6,0,1-0.4,1-1C15,14.4,14.6,14,14,14L14,14z M4,14c-0.6,0-1,0.4-1,1c0,0.6,0.4,1,1,1 c0.6,0,1-0.4,1-1C5,14.4,4.6,14,4,14L4,14z"/></g></svg>';
            $svg = 'data:image/svg+xml;base64,' . base64_encode($svg);

            add_menu_page(
                'Premmerce',
                'Premmerce',
                'manage_options',
                'premmerce',
                '',
                $svg
            );
        }

        if ('freemius' === FilterPlugin::getMarketPlace()) {
            $page = add_submenu_page(
                'premmerce',
                __('Product Filter', 'premmerce-filter'),
                __('Product Filter', 'premmerce-filter'),
                'manage_options',
                $this->settingsPage,
                array($this, 'options')
            );
        }

        
        add_action(
            'load-' . $page,
            function () {
                add_screen_option(
                    'per_page',
                    array(
                    'label'   => __('Number of filters per page', 'premmerce-filter'),
                    'default' => 100,
                    'option'  => 'filter_per_page'
                    )
                );
            }
        );

        if (! $premmerceMenuExists) {
            global $submenu;
            unset($submenu['premmerce'][0]);
        }
    }

    /**
     * Admin footer modification
     *
     * @param $text - default WordPress footer thankyou text
     */
    public function removeFooterAdmin($text)
    {
        $screen         = get_current_screen();
        $premmercePages = array(
            self::ADMIN_FREE_PAGE_SLUG
        );

        if (in_array($screen->id, $premmercePages)) {
            $link   = 'https://wordpress.org/support/plugin/premmerce-woocommerce-product-filter/reviews/?filter=5';
            $target = 'target="_blank"';
            $text   = '<span id="footer-thankyou">';
            $text  .= sprintf(
                /* translators: %%1$s: plugin url, %2$s: target */
                __('Please rate our Premmerce Product Filter for WooCommerce on <a href="%1$s" %2$s>WordPress.org</a><br/>Thank you from the Premmerce team!', 'premmerce-filter'),
                $link,
                $target
            );
            $text .= '</span>';
        } else {
            $text = '<span id="footer-thankyou">' . $text . '</span>';
        }

        return $text;
    }

    /**
     * Register admin css and js
     *
     * @param $page
     */
    public function registerAssets($page)
    {
        //scripts and styles for filter settings tabs
        
        $adminPageFullSlug = self::ADMIN_FREE_PAGE_SLUG;

        if ($adminPageFullSlug === $page) {
            wp_enqueue_media();
            wp_enqueue_script('wc-enhanced-select');
            wp_enqueue_style('woocommerce_admin_styles');
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_style('wp-jquery-ui-dialog');

            wp_enqueue_style(
                'premmerce_filter_admin_style',
                $this->fileManager->locateAsset('admin/css/style.css'),
                array(),
                FilterPlugin::getVersion()
            );
            wp_enqueue_style(
                'premmerce_filter_admin_style_seo',
                $this->fileManager->locateAsset('admin/css/seo.css'),
                array(),
                FilterPlugin::getVersion()
            );

            wp_enqueue_script(
                'premmerce_filter_admin_seo',
                $this->fileManager->locateAsset('admin/js/seo.js'),
                array('select2', 'jquery-ui-dialog', 'jquery-ui-progressbar'),
                FilterPlugin::getVersion(),
                true
            );

            wp_enqueue_script(
                'premmerce_filter_admin_script',
                $this->fileManager->locateAsset('admin/js/script.js'),
                array('jquery-ui-sortable', 'jquery-ui-dialog', 'wp-color-picker','jquery-ui-droppable'),
                FilterPlugin::getVersion(),
                true
            );

            $localizeOptions = array();

            $localizeOptions['ajax_nonce'] = wp_create_nonce('filter-ajax-nonce');

            wp_localize_script('premmerce_filter_admin_script', 'adminLocOptions', $localizeOptions);
            wp_localize_script('premmerce_filter_admin_seo', 'adminLocOptions', $localizeOptions);
        }

        //scripts and styles for widget page
        if ('customize.php' === $page || 'widgets.php' === $page) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_style(
                'premmerce_filter_admin_style',
                $this->fileManager->locateAsset('admin/css/widget.css'),
                array(),
                FilterPlugin::getVersion()
            );

            wp_enqueue_script(
                'premmerce_filter_admin_script',
                $this->fileManager->locateAsset('admin/js/widget.js'),
                array('wp-color-picker'),
                FilterPlugin::getVersion(),
                true
            );
        }
    }

    /**
     * Init Banner
     *
     * @return void
     */
    public function initBanner()
    {
        if (premmerce_pwpf_fs()->is__premium_only()) {
            if (premmerce_pwpf_fs()->can_use_premium_code()) {
                $user     = wp_get_current_user();
                $user_id  = $user->ID;
                $isBanner = get_user_meta($user_id, self::META_IGNORE_BANNER, true);

                if (!$isBanner) {
                    add_user_meta($user_id, self::META_IGNORE_BANNER, true, true);
                }
            }
        }
    }

    /**
     * Register tabs and init tab renderer
     */
    private function initTabs()
    {
        $this->tabRenderer->register(new Attributes($this->fileManager));

        foreach (apply_filters('premmerce_filter_taxonomies', array()) as $taxonomy_name) {
            $this->tabRenderer->register(new Taxonomy($this->fileManager, $taxonomy_name));
        }

        $this->tabRenderer->register(new Settings());

        //this tabs are for premium but show in free
        $this->tabRenderer->register(new PermalinkSettings());
        $this->tabRenderer->register(new SeoRules($this->fileManager, $this->notifier));

        $this->tabRenderer->register(new Cache($this->fileManager, $this->notifier));

        $this->tabRenderer->register(new BundleAndSave($this->fileManager));

        $this->tabRenderer->register(
            new SimpleTab(
                'account',
                __('Account', 'premmerce-filter'),
                function () {
                    premmerce_pwpf_fs()->add_filter('hide_account_tabs', '__return_true');
                    premmerce_pwpf_fs()->_account_page_load();
                    premmerce_pwpf_fs()->_account_page_render();
                },
                function () {
                    return premmerce_pwpf_fs()->is_registered();
                }
            )
        );

        $this->tabRenderer->register(
            new SimpleTab(
                'contact',
                __('Contact Us', 'premmerce-filter'),
                function () {
                    premmerce_pwpf_fs()->_contact_page_render();
                }
            )
        );

        $this->tabRenderer->init();
    }
}
