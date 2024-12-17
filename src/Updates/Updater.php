<?php namespace Premmerce\Filter\Updates;

use Premmerce\Filter\FilterPlugin;
use Premmerce\Filter\Seo\SeoModel;
use Premmerce\Filter\Seo\SeoTermModel;
use Premmerce\SDK\V2\FileManager\FileManager;

class Updater
{
    const DB_OPTION = 'premmerce_filter_db_version';

    /**
     * File Manager
     *
     * @var FileManager
     */
    private $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Check For Updates
     *
     * @return void
     */
    public function checkForUpdates()
    {
        return $this->compare(FilterPlugin::getVersion());
    }

    /**
     * Compare
     *
     * @param  mixed $version
     * @return void
     */
    private function compare($version)
    {
        $dbVersion = get_option(self::DB_OPTION, '1.1');

        return version_compare($dbVersion, $version, '<');
    }

    /**
     * Update
     *
     * @return void
     */
    public function update()
    {
        if ($this->checkForUpdates()) {
            $this->installDb();
            foreach ($this->getUpdates() as $version => $callback) {
                if ($this->compare($version)) {
                    call_user_func($callback);
                }
            }
            update_option(self::DB_OPTION, FilterPlugin::getVersion());
        }
    }

    /**
     * Install DB
     *
     * @return void
     */
    public function installDb()
    {
        //This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            ( new SeoModel() )->install();
            ( new SeoTermModel() )->install();
        }
    }

    /**
     * Get Updates
     *
     * @return void
     */
    public function getUpdates()
    {
        $updateVersions = array(
            '2.0' => array($this, 'update2_0'),
            '3.1' => array($this, 'update3_1'),
            '3.4' => array($this, 'update3_4')
        );
        //for Woocommerce MarketPlace
        if ('woo' === FilterPlugin::getMarketPlace()) {
            $updateVersions = array(
                '1.0' => array($this, 'update1_0_woo'),
            );
        }

        return $updateVersions;
    }

    /**
     * Update 2.0 version and upper
     *
     * @return void
     */
    public function update2_0()
    {
        update_option(self::DB_OPTION, '2.0');
    }

    /**
     * Update 3.1 version and upper
     *
     * @return void
     */
    public function update3_1()
    {
        $settings = get_option(FilterPlugin::OPTION_SETTINGS);

        $settings['taxonomies']    = FilterPlugin::DEFAULT_TAXONOMIES;
        $settings['style']         = 'premmerce';
        $settings['ajax_strategy'] = 'woocommerce_content';

        update_option(FilterPlugin::OPTION_SETTINGS, $settings);

        update_option(self::DB_OPTION, FilterPlugin::getVersion());
    }

    /**
     * Update 3.4 version and upper
     *
     * @return void
     */
    public function update3_4()
    {
        //This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            global $wpdb;
            $migration = file_get_contents($this->fileManager->getPluginDirectory() . '/var/migration/seo/3_4.sql');
            $migration = str_replace('{{table}}', $wpdb->prefix . SeoModel::TABLE, $migration);
            dbDelta($migration);
        }
        update_option(self::DB_OPTION, FilterPlugin::getVersion());
    }

    /** General methods for all marketpalaces **/
    /**
     * Add new settings fields
     */
    public function addSettingsFields()
    {
        $settings = get_option(FilterPlugin::OPTION_SETTINGS);

        $settings['taxonomies']    = FilterPlugin::DEFAULT_TAXONOMIES;
        $settings['style']         = 'premmerce';
        $settings['ajax_strategy'] = 'woocommerce_content';

        update_option(FilterPlugin::OPTION_SETTINGS, $settings);
    }

    /**
     * Add discourage_search in DB
     */
    public function addDiscourageSearchInDB()
    {
        global $wpdb;
        $migration = file_get_contents($this->fileManager->getPluginDirectory() . '/var/migration/seo/3_4.sql');
        $migration = str_replace('{{table}}', $wpdb->prefix . SeoModel::TABLE, $migration);
        dbDelta($migration);
    }
    /** END general methods for all marketpalaces **/

    /**
     * Woocommerce versions updates
     *
    **/
    public function update1_0_woo()
    {
        $this->addSettingsFields();
        $this->addDiscourageSearchInDB();
        update_option(self::DB_OPTION, FilterPlugin::getVersion());
    }
}
