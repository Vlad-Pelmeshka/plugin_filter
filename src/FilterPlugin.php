<?php namespace Premmerce\Filter;

use Premmerce\Filter\Admin\Admin;
use Premmerce\Filter\Cache\Cache;
use Premmerce\Filter\Filter\Filter;
use Premmerce\Filter\Updates\Updater;
use Premmerce\Filter\Filter\Container;
use Premmerce\Filter\Frontend\Frontend;
use Premmerce\Filter\Filter\ItemRenderer;
use Premmerce\SDK\V2\Plugin\PluginInterface;
use Premmerce\SDK\V2\FileManager\FileManager;
use Premmerce\SDK\V2\Notifications\AdminNotifier;
use Premmerce\Filter\Admin\Tabs\Base\BaseSettings;
use Premmerce\Filter\Shortcodes\FilterWidgetShortcodes;

/**
 * Class FilterPlugin
 *
 * @package Premmerce\Filter
 */
class FilterPlugin implements PluginInterface
{
    const VERSION = '3.7';

    const DOMAIN = 'premmerce-filter';

    const OPTION_ = 'premmerce_filter_';

    const OPTION_ATTRIBUTES = 'premmerce_filter_attributes';

    const OPTION_COLORS = 'premmerce_filter_colors';

    const OPTION_IMAGES = 'premmerce_filter_images';

    const OPTION_SETTINGS = 'premmerce_filter_settings';

    const LOCALIZE_BLOCK = 'premmerce_filter_localize';

    const OPTION_PERMALINKS_SETTINGS = 'premmerce_filter_permalink_settings';

    const DEFAULT_TAXONOMIES = array('product_cat', 'product_tag', 'product_brand');

    const PLAN_FREE = 'free';

    const PLAN_PREMIUM = 'premium';

    const TYPE_COLOR = 'color';

    const TYPE_IMAGE = 'image';

    const TYPE_SLIDER = 'slider';

    const TYPE_LABEL = 'label';

    const HTML_TAGS = array(
        'a' => array(
            'href'  => array(),
            'title' => array()
        ),
        'b'      => array(),
        'small'  => array(),
        'br'     => array(),
        'em'     => array(),
        'strong' => array(),
        'p'      => array(),
        'h1'     => array(),
        'h2'     => array(),
        'h3'     => array(),
        'img'    => array(
            'alt'   => array(),
            'src'   => array(),
            'class' => array()
        ),
        'style' => array(),
        'div'   => array(
            'id'    => array(),
            'class' => array(),
            'title' => array(),
            'style' => array(),
        ),
        'table'   => array(
            'class' => array()
        ),
        'tr'     => array(
            'class' => array()
        ),
        'td'     => array(
            'class' => array()
        ),
        'th'     => array(
            'class' => array()
        ),
        'span'   => array(
            'class' => array()
        ),
        'code'   => array(),
        'button' => array(
            'type'  => array(),
            'class' => array(),
            'id'    => array(),

        ),
        'select' => array(
            'id'    => array(),
            'class' => array(),
            'name'  => array(),
            'value'  => array(),
        ),
        'option' => array(
            'class' => array(),
            'value' => array(),
        ),
    );

    /**
     * File Manager
     *
     * @var FileManager
     */
    private $fileManager;

    /**
     * Notifier
     *
     * @var AdminNotifier
     */
    private $notifier;

    /**
     * PluginManager constructor.
     *
     * @param string $mainFile
     */
    public function __construct($mainFile)
    {
        $this->fileManager = new FileManager($mainFile, 'premmerce-woocommerce-product-filter');
        $this->notifier    = new AdminNotifier();

        Container::getInstance()->addService('file_manager', $this->fileManager);

        $this->registerHooks();
    }

    /**
     * Run plugin part
     */
    public function run()
    {
        $valid = count($this->validateRequiredPlugins()) === 0;

        if ($valid) {
            ( new Updater($this->fileManager) )->update();
            $filter = new Filter(Container::getInstance());

            do_action('premmerce_filter_core_loaded', $filter);

            if (is_admin()) {
                new Admin($this->fileManager);
            } else {
                new Frontend(Container::getInstance());
            }
        }
    }

    public function registerHooks()
    {
        add_action('plugins_loaded', array($this, 'loadTextDomain'));
        add_action('init', array( $this, 'register_blocks' ));
        add_action('admin_init', array($this, 'checkRequirePlugins'));
        add_action('comment_post', array($this, 'clearRatingCountTransients'));
        premmerce_pwpf_fs()->add_filter('freemius_pricing_js_path', array($this, 'cutomFreemiusPricingPage'));
        add_action('before_woocommerce_init', array( $this, 'declareHPOSCompatibility' ));
    }

    /**
     * Add custom Freemius Pricing Page js file.
     */
    public function cutomFreemiusPricingPage($default_pricing_js_path)
    {
        $pluginDir       = $this->fileManager->getPluginDirectory();
        $pricing_js_path = $pluginDir . '/assets/admin/js/pricing-page/freemius-pricing.js';

        return $pricing_js_path;
    }

    /**
     * Fired when the plugin is activated
     */
    public function activate()
    {
        flush_rewrite_rules();

        if (!get_option(self::OPTION_SETTINGS)) {
            $defaultOptions = array(
                'show_price_filter' => 'on',
                'hide_empty'        => 'on',
                'product_cat'       => 'on',
                'tag'               => 'on',
                'product_brand'     => 'on',
                'search'            => 'on',
                'shop'              => 'on',
                'attribute'         => 'on',
            );
            add_option(self::OPTION_SETTINGS, $defaultOptions);
        }

        ( new Updater($this->fileManager) )->installDb();
    }

    /**
     * Fired when the plugin is deactivated
     */
    public function deactivate()
    {
        $cache = new Cache();
        $cache->clear();
        rmdir($cache->getCacheDir());
    }

    /**
     * Fired during plugin uninstall
     */
    public static function uninstall()
    {
        delete_option(self::OPTION_ATTRIBUTES);
        delete_option(self::OPTION_COLORS);
        delete_option(self::OPTION_IMAGES);
        delete_option(self::OPTION_);
        delete_option(self::OPTION_SETTINGS);
        delete_option(self::OPTION_PERMALINKS_SETTINGS);
        delete_option(Updater::DB_OPTION);
    }

    /**
     * Check required plugins and push notifications
     */
    public function checkRequirePlugins()
    {
        $plugins = $this->validateRequiredPlugins();

        if (count($plugins)) {
            foreach ($plugins as $plugin) {
                $error = sprintf(
                    /* translators: %%1$s: our plugin name, %2$s another plugin name */
                    __('The %1$s plugin requires %2$s plugin to be active!', 'premmerce-filter'),
                    'SEO Product Filter for Woocommerce',
                    $plugin
                );
                $this->notifier->push($error, AdminNotifier::ERROR, false);
            }
        }
    }

    /**
     * Validate required plugins
     *
     * @return array
     */
    private function validateRequiredPlugins()
    {
        $plugins = array();

        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        /**
         * Check if WooCommerce is active
         **/
        if (!(is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active_for_network('woocommerce/woocommerce.php'))) {
            $plugins[] = '<a target="_blank" href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a>';
        }

        return $plugins;
    }

    /**
     * Load plugin translations
     */
    public function loadTextDomain()
    {
        $name = $this->fileManager->getPluginName();
        load_plugin_textdomain('premmerce-filter', false, $name . '/languages/');
    }

    public static function clearRatingCountTransients()
    {
        for ($rating = 5; $rating >= 1; $rating--) {
            delete_transient("premmerce_filtered_product_count_{$rating}");
        }
    }

    /**
     * Register theme Gutenberg blocks.
     */
    public function register_blocks()
    {
        // register our JavaScript
        wp_register_script(
            'premmerce_filter_admin_blocks',
            $this->fileManager->locateAsset('blocks/index.js'),
            plugins_url('/build/index.js', __FILE__),
            array( 'jquery', 'jquery-ui-slider', 'jquery-touch-punch', 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-api' ),
            self::getVersion(),
            true
        );
        // register our front-end styles
        wp_register_style(
            'premmerce_filter_admin_blocks_style',
            $this->fileManager->locateAsset('blocks/style.css'),
            array(),
            self::getVersion()
        );
        // register our editor styles
        wp_register_style(
            'premmerce_filter_admin_blocks_edit_style',
            $this->fileManager->locateAsset('blocks/editor.css'),
            array('wp-edit-blocks'),
            self::getVersion()
        );

        $localizeOptions = array();
        $localizeOptions['isPremium'] = premmerce_pwpf_fs()->can_use_premium_code();
        $localizeOptions['pricingPageLink'] = BaseSettings::premiumLink();

        wp_localize_script('premmerce_filter_admin_blocks', self::LOCALIZE_BLOCK, $localizeOptions);

        // register our block
        register_block_type(
            'premmerce/filter',
            array(
            'editor_script' => 'premmerce_filter_admin_blocks',
            'editor_style'  => 'premmerce_filter_admin_blocks_edit_style',
            'style'         => 'premmerce_filter_admin_blocks_style',
            'render_callback' => array($this, 'renderFilterBlock'),
            'attributes' => array(
                'isEditing' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'style' => array(
                    'type'    => 'string',
                    'default' => 'default',
                ),
                'bold_title' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'bg_color' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'add_border' => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'border_color' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'price_input_bg' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'price_input_text' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'price_slider_range' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'price_slider_handle' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'title_size' => array(
                    'type'    => 'string',
                    'default' => '16'
                ),
                'terms_title_size' => array(
                    'type'    => 'string',
                    'default' => '16'
                ),
                'title_color' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'terms_title_color' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'checkbox_border_color' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'checkbox_color' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'checkbox_appearance' => array(
                    'type'    => 'string',
                    'default' => '0',
                ),
                'title_appearance' => array(
                    'type'    => 'string',
                    'default' => 'default'
                ),
            ),
            )
        );
    }

    /**
     * Render Filter Block
     *
     * @param  mixed $attr
     * @return void
     */
    public function renderFilterBlock($attr)
    {
        $isRender = false;
        //for rendering in gutenberg block
        if (wp_is_json_request()) {
            //load items without settings load attributes
            add_filter(
                'premmerce_items_load',
                function ($load) {
                    return true;
                }
            );
            //init hooks from itemRender
            new ItemRenderer($this->fileManager);
            $isRender = true;
        }

        $filterBlock = ( new FilterWidgetShortcodes($this->fileManager) )->premmerceShortcodeFilter($attr, 'filterblock', $isRender);
        $filterBlock = str_replace(array("\n", "\t"), '', $filterBlock);
        $filterBlock = str_replace(array('for='), ' for=', $filterBlock);
        return $filterBlock;
    }

    /**
     * Get Market Place
     *
     * @return void
     */
    public static function getMarketPlace()
    {
        $marketPlace = 'freemius';

        return $marketPlace;
    }

    /**
     * Get Version
     *
     * @return void
     */
    public static function getVersion()
    {
        $version = '3.7';

        return $version;
    }

    /**
     * Declare compatibility for WC HPOS.
     *
     * @link https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#declaring-extension-incompatibility
     * @return void
     */
    public function declareHPOSCompatibility()
    {
        if (! class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            return;
        }
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            $this->fileManager->getMainFile(),
            true
        );
    }
}
