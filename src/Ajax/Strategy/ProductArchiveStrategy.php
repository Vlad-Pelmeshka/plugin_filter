<?php namespace Premmerce\Filter\Ajax\Strategy;

use Premmerce\Filter\Frontend\Frontend;

class ProductArchiveStrategy extends WidgetsStrategy
{
    public function __construct()
    {
        add_action('woocommerce_before_main_content', array($this, 'openContainer'), 0);
        add_action('woocommerce_after_main_content', array($this, 'closeContainer'), 999);
    }

    /**
     * Open Container
     *
     * @return void
     */
    public function openContainer()
    {
        echo '<div class="premmerce-filter-ajax-container">';
    }

    /**
     * Close Container
     *
     * @return void
     */
    public function closeContainer()
    {
        echo '</div>';
    }

    /**
     * Update Response
     *
     * @param array $response
     *
     * @return array $response
     */
    public function updateResponse(array $response, array $instance)
    {
        ob_start();
        $template = $this->getTemplate();
        if ($template) {
            echo '<div>';
            include $template;
            echo '</div>';
        } else {
            wc_get_template('archive-product.php');
        }

        $html = ob_get_clean();

        $instance = Frontend::getInstanceByRequest();

        $response[] = array(
            'selector' => '.premmerce-filter-ajax-container',
            'callback' => 'replacePart',
            'html'     => $html
        );

        return parent::updateResponse($response, $instance);
    }

    /**
     * Get Template
     *
     * @return void
     */
    protected function getTemplate()
    {
        $productArchiveTemplate = wc_locate_template('archive-product.php');
        $templateDir            = wp_upload_dir()['basedir'] . '/cache/premmerce_filter/' . md5($productArchiveTemplate);

        if (!file_exists($templateDir)) {
            if ($productArchiveTemplate) {
                $content = file_get_contents($productArchiveTemplate);

                $pattern = '~get_(header|footer)\s*\([^)]*\)\s*;~';

                $content = preg_replace($pattern, '', $content);

                file_put_contents($templateDir, $content);
            }
        }

        if (file_exists($templateDir)) {
            return $templateDir;
        }
    }
}
