<?php namespace Premmerce\Filter\Admin\Tabs;

use Premmerce\Filter\FilterPlugin;
use Premmerce\SDK\V2\FileManager\FileManager;
use Premmerce\Filter\Seo\Sitemap\GeneralSitemap;
use Premmerce\Filter\Admin\Tabs\Base\TabInterface;
use Premmerce\Filter\Filter\Items\Types\OnSaleFilter;
use Premmerce\SDK\V2\Notifications\AdminNotifier;

class Cache implements TabInterface
{
    /**
     * File Manager
     *
     * @var FileManager
     */
    private $fileManager;

    /**
     * Admin Notifier
     *
     * @var AdminNotifier
     */
    private $notifier;

    public function __construct(FileManager $fileManager, AdminNotifier $notifier)
    {
        $this->fileManager = $fileManager;
        $this->notifier    = $notifier;
    }

    public function init()
    {
        add_action(
            'admin_post_premmerce_filter_cache_clear',
            function () {
                if (! current_user_can('manage_options')) {
                    wp_die(__('Incorrect permissions', 'premmerce-filter'));
                }

                if (! check_admin_referer('premmerce-cache-clear')) {
                    wp_die(__('Something went wrong', 'premmerce-filter'));
                }

                ( new \Premmerce\Filter\Cache\Cache() )->clear();

                //remove transients
                if (premmerce_pwpf_fs()->is__premium_only()) {
                    FilterPlugin::clearRatingCountTransients();
                    OnSaleFilter::removeTransients();
                    if (defined('WPSEO_VERSION')) {
                        GeneralSitemap::removeTransients();
                    }
                }

                self::clearOtherwisePluginsCache();
                wp_safe_redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] . '&cache=cleared' : null);
                die;
            }
        );

        add_action('admin_init', function () {
            if (isset($_GET['cache']) && 'cleared' === $_GET['cache']) {
                $this->notifier->push(
                    __('Product Filter cache cleared!', 'premmerce-filter'),
                    AdminNotifier::SUCCESS
                );
            }
        });
    }

    /**
     * Clear sitemap cache in Rank Math Seo plugin
     */
    public static function clearRankMathCache()
    {
        if (defined('RANK_MATH_VERSION')) {
            \RankMath\Sitemap\Cache::invalidate_storage();
        }
    }

     /**
      * Clear cache in otherwise plugins
      */
    public static function clearOtherwisePluginsCache()
    {
        //clear sitemap cache in Rank Math Seo plugin
        self::clearRankMathCache();
    }

    public function render()
    {
        $this->fileManager->includeTemplate('admin/tabs/cache.php');
    }

    public function getLabel()
    {
        return __('Cache', 'premmerce-filter');
    }

    public function getName()
    {
        return 'cache';
    }

    public function valid()
    {
        return true;
    }
}
