<?php

if ( ! defined('ABSPATH')) {
	exit;
}
use Premmerce\Filter\FilterPlugin;

/**
 * Filter values
 *
 * @var array  $activeFilters
 * @var array  $args
 * @var array  $instance
 * @var bool   $showResetFilter
 * @var string $resetFilter
 */

$nofollow = $disableNofollow ? '' : 'rel="nofollow"';
?>
<?php if (empty($activeFilters)) : ?>
	<div class="premmerce-active-filters-widget-wrapper"></div>
<?php else : ?>
	<?php echo ( !empty($args['before_widget']) ) ? wp_kses($args['before_widget'], FilterPlugin::HTML_TAGS ) : ''; ?>

	<?php if ( ! empty($instance['title'])) : ?>
		<?php echo wp_kses($args['before_title'], FilterPlugin::HTML_TAGS ) . esc_attr($instance['title']) . wp_kses($args['after_title'], FilterPlugin::HTML_TAGS ); ?>
	<?php endif; ?>

	<div class="pc-active-filter" data-premmerce-active-filter>
		<div class="pc-active-filter__list">
			<?php foreach ($activeFilters as $item) : ?>

				<?php do_action('premmerce_filter_render_active_item_before', $item); ?>

				<div class="pc-active-filter__list-item">
					<a data-premmerce-active-filter-link class="pc-active-filter__item-link" <?php echo wp_kses_post($nofollow); ?> aria-label="<?php echo esc_attr__('Remove filter', 'woocommerce'); ?>" href="<?php echo esc_url($item['link']); ?>">
					<span class="pc-active-filter__item-text-el">
						<?php echo wp_kses(apply_filters('premmerce_filter_render_active_item_title', $item['title']), FilterPlugin::HTML_TAGS); ?>
					</span>
						<span class="pc-active-filter__item-delete">
						<?php echo esc_attr(apply_filters('premmerce_filter_render_active_item_close', 'x')); ?>
					</span>
					</a>
				</div>

				<?php do_action('premmerce_filter_render_active_item_after', $item); ?>

			<?php endforeach; ?>

			<?php if ($showResetFilter) : ?>
			<div class="pc-active-filter__list-item">
				<a data-premmerce-active-filter-link class="pc-active-filter__item-link" <?php echo wp_kses_post($nofollow); ?>
				   aria-label="<?php echo esc_attr__('Reset filter', 'premmerce-filter'); ?>"
				   href="<?php echo esc_url($resetFilter); ?>">
					<span class="pc-active-filter__item-text-el">
						<?php
						echo esc_attr(apply_filters('premmerce_filter_render_active_item_title', __('Reset filter', 'premmerce-filter')));
						?>
					</span>
					<span class="pc-active-filter__item-delete">
						<?php echo esc_attr(apply_filters('premmerce_filter_render_active_item_close', 'x')); ?>
					</span>
				</a>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<?php echo ( !empty($args['after_widget']) ) ? wp_kses($args['after_widget'], FilterPlugin::HTML_TAGS ) : ''; ?>
<?php endif; ?>
