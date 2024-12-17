<?php namespace Premmerce\Filter\Ajax\Strategy;

class SaleszoneStrategy implements ThemeStrategyInterface
{
    public function updateResponse(array $response, array $instance)
    {
        ob_start();

        wc_get_template('loop/products-layout.php');

        $html = ob_get_clean();

        $response[] = array(
            'selector' => '.content__container',
            'callback' => 'html',
            'html' => $html,
        );

        return $response;
    }
}
