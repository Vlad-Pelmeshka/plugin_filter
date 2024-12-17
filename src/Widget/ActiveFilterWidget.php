<?php namespace Premmerce\Filter\Widget;

use WP_Widget;
use Premmerce\Filter\FilterPlugin;
use Premmerce\Filter\Filter\Container;

class ActiveFilterWidget extends WP_Widget
{
    const ACTIVE_WIDGET_ID = 'premmerce_filter_active_filters_widget';

    /**
     * FilterWidget constructor.
     */
    public function __construct()
    {
        parent::__construct(
            self::ACTIVE_WIDGET_ID,
            __('Premmerce Active Filters', 'premmerce-filter'),
            array(
                'description' => __('Product attributes active filters', 'premmerce-filter'),
            )
        );
        $this->widget_options['classname'] .= ' premmerce-active-filters-widget-wrapper';
    }

    /**
     * Widget
     *
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance)
    {
        if (apply_filters('premmerce_product_filter_active', false)) {
            $data = $this->getActiveFilterWidgetContent($args, $instance);
            do_action('premmerce_product_active_filters_render', $data);
        }
    }

    public function getActiveFilterWidgetContent($args = array(), $instance = array())
    {
        global $wp;

        $url = !empty($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';

        $items = Container::getInstance()->getItemsManager()->getActiveFilters();
        $items = apply_filters('premmerce_product_filter_active_items', $items);

        $settings = get_option(FilterPlugin::OPTION_SETTINGS, array());

        $ratings = $this->getRatingFilters();

        if ((is_array($items) && count($items)) || count($ratings)) {
            /* translators: %s: number of rate */
            $ratingTitle = __('Rated %s out of 5', 'woocommerce');
            foreach ($ratings as $rating) {
                $link_ratings = implode(',', array_diff($ratings, array($rating)));
                $link         = $link_ratings ? add_query_arg(
                    'rating_filter',
                    $link_ratings
                ) : remove_query_arg('rating_filter', $url);

                $items['rating_filter_' . $rating] = array(
                    'title' => sprintf($ratingTitle, $rating),
                    'link'  => $link,
                );
            }
        }

        $data = array(
            'activeFilters'   => $items,
            'resetFilter'     => home_url($wp->request . '/'),
            'showResetFilter' => isset($settings['show_reset_filter']) && ! empty($settings['show_reset_filter']),
            'disableNofollow' => isset($settings['disable_nofollow']) && ! empty($settings['disable_nofollow']),
            'args'            => $args,
            'instance'        => $instance
        );

        return $data;
    }

    /**
     * Get Rating Filters
     *
     * @return array
     */
    private function getRatingFilters()
    {
        $ratingFilter = isset($_GET['rating_filter']) ? wc_clean(wp_unslash($_GET['rating_filter'])) : null; // phpcs:ignore WordPress.Security.NonceVerification
        $ratings      = !empty($ratingFilter) ? array_filter(array_map(
            'absint',
            explode(',', $ratingFilter)
        )) : array();

        return $ratings;
    }

    /**
     * Update
     *
     * @param array $new_instance
     * @param array $old_instance
     *
     * @return array
     */
    public function update($new_instance, $old_instance)
    {
        $instance          = array();
        $instance['title'] = strip_tags($new_instance['title']);

        return $instance;
    }

    /**
     * Form
     *
     * @param array $instance
     *
     * @return string|void
     */
    public function form($instance)
    {
        do_action(
            'premmerce_product_filter_widget_form_render',
            array(
            'title'  => isset($instance['title']) ? $instance['title'] : '',
            'widget' => $this,
            )
        );
    }
}
