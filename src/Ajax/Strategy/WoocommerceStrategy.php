<?php namespace Premmerce\Filter\Ajax\Strategy;

use Premmerce\Filter\Frontend\Frontend;

class WoocommerceStrategy extends WidgetsStrategy
{
    public function __construct()
    {
        add_action('woocommerce_before_shop_loop', array($this, 'openContainer'), 0);

        add_action('woocommerce_after_shop_loop', array($this, 'closeContainer'), 999);

        add_action('woocommerce_no_products_found', array($this, 'openContainer'), 0);

        add_action('woocommerce_no_products_found', array($this, 'closeContainer'), 999);
    }

    /**
     * Update Response
     *
     * @param  mixed $response
     * @param  mixed $instance
     * @return void
     */
    public function updateResponse(array $response, array $instance = array())
    {
        $instance = Frontend::getInstanceByRequest();

        return parent::updateResponse($this->loadContent($response), $instance);
    }

    /**
     * Load Content
     *
     * @param $response
     *
     * @return array
     */
    public function loadContent($response)
    {
        add_filter('woocommerce_show_page_title', '__return_false');
        remove_all_actions('woocommerce_archive_description');

        ob_start();
        echo '<div>';
        woocommerce_content();
        echo '</div>';
        $html = ob_get_clean();

        $response[] = array(
            'selector' => '.premmerce-filter-ajax-container',
            'callback' => 'replacePart',
            'html'     => $html
        );

        return $response;
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
}
