<?php namespace Premmerce\Filter\Admin\Tabs;

use Premmerce\Filter\Admin\Admin;
use Premmerce\Filter\FilterPlugin;
use Premmerce\SDK\V2\FileManager\FileManager;
use Premmerce\Filter\Admin\Tabs\Base\TabInterface;

class BundleAndSave implements TabInterface
{
    /**
     * File Manager
     *
     * @var FileManager
     */
    private $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
        $this->hooks();
    }

    /**
     * Init
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Hooks
     *
     * @return void
     */
    public function hooks()
    {
        add_action('admin_enqueue_scripts', array($this, 'registerBundlesAssets'), 11);
    }

    /**
     * Render
     *
     * @return void
     */
    public function render()
    {
        $images = array(
            'header_img'     => $this->fileManager->locateAsset('admin/img/premmerce_bundle.jpg'),
            'premmerce_logo' => $this->fileManager->locateAsset('admin/img/premmerce.png'),
            'permalink_img'  => $this->fileManager->locateAsset('admin/img/permalink.png'),
            'filter_img'     => $this->fileManager->locateAsset('admin/img/filter.png'),
            'variants_img'   => $this->fileManager->locateAsset('admin/img/variants.png'),
            'search_img'     => $this->fileManager->locateAsset('admin/img/search.png'),
            'riley_pearcy'   => $this->fileManager->locateAsset('admin/img/riley_pearcy.jpg'),
            'lian_perry'     => $this->fileManager->locateAsset('admin/img/lian_perry.jpg'),
            'rommie_mercer'  => $this->fileManager->locateAsset('admin/img/rommie_mercer.jpg'),
            'svg'            => $this->fileManager->locateAsset('admin/img/svg.svg'),
            'wp_logo'        => $this->fileManager->locateAsset('admin/img/wp.png'),
            'wp_rocket_logo' => $this->fileManager->locateAsset('admin/img/wp-rocket.jpg'),
            'learnwoo_logo'  => $this->fileManager->locateAsset('admin/img/learnwoo.png'),
            'wp_lift_logo'   => $this->fileManager->locateAsset('admin/img/wp-lift.png'),
            'mayor_logo'     => $this->fileManager->locateAsset('admin/img/mayor.png'),
            'managewp_logo'  => $this->fileManager->locateAsset('admin/img/managewp.png'),
            'thanks_img'     => $this->fileManager->locateAsset('admin/img/thanks.png'),
        );

        //sections on Bundles tab
        $this->fileManager->includeTemplate('admin/tabs/bundle-and-save/header.php', $images);
        $this->fileManager->includeTemplate('admin/tabs/bundle-and-save/pricing.php', $images);
        $this->fileManager->includeTemplate('admin/tabs/bundle-and-save/texts.php', $images);
        $this->fileManager->includeTemplate('admin/tabs/bundle-and-save/testimonials.php', $images);
        $this->fileManager->includeTemplate('admin/tabs/bundle-and-save/faq.php', $images);
        $this->fileManager->includeTemplate('admin/tabs/bundle-and-save/featured-in.php', $images);
        $this->fileManager->includeTemplate('admin/tabs/bundle-and-save/thanks.php', $images);
    }

    /**
     * Register css and js only for Bundles tab
     *
     * @param $page
     */
    public function registerBundlesAssets($page)
    {
        if (Admin::ADMIN_FREE_PAGE_SLUG === $page) {
            //scripts and styles for bundle tab
            if (isset($_GET['tab']) && 'bundle_and_save' === $_GET['tab']) {
                //css and js for bundle tab
                wp_enqueue_style(
                    'premmerce_filter_admin_premmerce_style',
                    $this->fileManager->locateAsset('admin/css/premmerce.min.css'),
                    array(),
                    FilterPlugin::getVersion()
                );
                wp_enqueue_script(
                    'premmerce_filter_admin_bundles_freemius_jquery_for_checkout_script',
                    'https://code.jquery.com/jquery-1.12.4.min.js',
                    array(),
                    FilterPlugin::getVersion(),
                    true
                );
                wp_enqueue_script(
                    'premmerce_filter_admin_bundles_freemius_checkout_script',
                    'https://checkout.freemius.com/checkout.min.js',
                    array(),
                    FilterPlugin::getVersion(),
                    true
                );
                wp_enqueue_script(
                    'premmerce_filter_admin_bundles_script',
                    $this->fileManager->locateAsset('admin/js/bundles.js'),
                    array(),
                    FilterPlugin::getVersion(),
                    true
                );
            }
        }
    }

    public function getLabel()
    {
        return __('Bundle & Save', 'premmerce-filter');
    }

    public function getName()
    {
        return 'bundle_and_save';
    }

    public function valid()
    {
        return true;
    }

    public static function premmerce_use_svg_symbol($svg_url, $name, $echo = true)
    {
        $ico = '<use xlink:href="' . $svg_url . '#svg-icon__' . $name . '"></use>';

        $ksesDefaults = wp_kses_allowed_html('post');

        $icoArgs = array(
            'use'   => array(
                'href'       => true,
                'xlink:href' => true,
            )
        );

        if ($echo) {
            echo wp_kses($ico, array_merge($ksesDefaults, $icoArgs));
        } else {
            return $ico;
        }
    }
}
