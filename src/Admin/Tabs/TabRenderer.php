<?php namespace Premmerce\Filter\Admin\Tabs;

use Premmerce\Filter\Admin\Tabs\Base\TabInterface;
use Premmerce\SDK\V2\FileManager\FileManager;

class TabRenderer
{
    /**
     * Tabs
     *
     * @var TabInterface[]
     */
    private $tabs = array();

    /**
     * File Manager
     *
     * @var FileManager
     */
    private $fileManager;

    /**
     * TabRenderer constructor.
     *
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Register
     *
     * @param TabInterface $tab
     */
    public function register(TabInterface $tab)
    {
        $this->tabs[$tab->getName()] = $tab;
    }

    /**
     * Register hooks
     */
    public function init()
    {
        foreach ($this->tabs as $tab) {
            $tab->init();
        }
    }

    /**
     * Render current tab
     */
    public function render()
    {
        $currentTab = $this->get($this->current());
        $tab        = $currentTab ? $currentTab : reset($this->tabs);

        if ($tab && $tab->valid()) {
            $this->fileManager->includeTemplate('admin/options.php', array('tabs' => $this->tabs, 'current' => $tab));
        }
    }

    /**
     * Get
     *
     * @param $name
     *
     * @return null|TabInterface
     */
    public function get($name)
    {
        return $this->has($name) ? $this->tabs[$name] : null;
    }

    /**
     * Has
     *
     * @param $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->tabs[$name]);
    }

    /**
     * Current
     *
     * @return mixed|TabInterface
     */
    public function current()
    {
        return isset($_GET['tab']) ? wc_clean(wp_unslash($_GET['tab'])) : null;
    }
}
