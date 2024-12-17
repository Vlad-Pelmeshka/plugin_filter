<?php namespace Premmerce\Filter\Ajax\Strategy;

interface ThemeStrategyInterface
{
    /**
     * UpdateResponse
     *
     * @param array $response
     *
     * @return array $response
     */
    public function updateResponse(array $response, array $instance);
}
