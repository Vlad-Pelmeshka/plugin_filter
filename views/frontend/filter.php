<?php

if (!defined('ABSPATH')) {
	exit;
}
use Premmerce\Filter\FilterPlugin;
use Premmerce\Filter\Widget\FilterWidget;
use Premmerce\Filter\Shortcodes\FilterWidgetShortcodes;

/**
 * Get values
 *
 * @var array $attributes
 * @var string $style
 * @var bool $showFilterButton
 * @var array $args
 * @var array $prices
 * @var array $includeFields
 * @var string $title
 * @var string $formAction
 *
 * use instance['title'] to show widget title
 *
 * $attribute->display_type = '' - Default , 'scroll' - Scroll ,'dropdown' - Dropdown ,'scroll_dropdown' - Scroll + Dropdown
 * $attribute->has_checked = true, false
 * $attribute->html_type = 'select', 'color', 'image', 'label', 'radio'
 */

if (!empty($args['name']) && ( 'shortcode' === $args['name'] || 'filterblock' === $args['name'] )) :
	//This IF block will be auto removed from the Free version.
	if (premmerce_pwpf_fs()->is__premium_only()) {
		if (premmerce_pwpf_fs()->can_use_premium_code()) {
				FilterWidgetShortcodes::renderInlineStyle__premium_only($instance, $args);
		}
	}
	$filterWidgetId = FilterWidget::FILTER_WIDGET_ID;
	printf(
		'<div id="%1$s" class="widget_%2$s shortcode-style-%3$s">',
		esc_attr($args['id']),
		esc_attr($filterWidgetId),
		esc_attr($instance['style'])
	);
endif;

$dropdownList = ['dropdown', 'scroll_dropdown', 'dropdown_hover'];
$scrollList   = ['scroll', 'scroll_dropdown'];

?>

<?php echo ( !empty($args['before_widget']) ) ? wp_kses($args['before_widget'], FilterPlugin::HTML_TAGS) : ''; ?>

<?php if (!empty($instance['title'])) : ?>
	<?php echo wp_kses($args['before_title'], FilterPlugin::HTML_TAGS) . esc_attr($instance['title']) . wp_kses($args['after_title'], FilterPlugin::HTML_TAGS); ?>
<?php endif; ?>

<div class="filter filter--style-<?php echo esc_attr($style); ?> premmerce-filter-body" data-premmerce-filter>
	<?php
	foreach ($attributes as $attribute) :

		do_action_ref_array('premmerce_filter_render_item_before', [&$attribute]);

		$filterItemAdditionalClasses  = '';
		$filterItemAdditionalClasses .= ' filter__item-' . $attribute->display_type;
		$filterItemAdditionalClasses .= ' filter__item--type-' . $attribute->html_type . $border;
		$filterItemAdditionalClasses .= $attribute->has_checked ? ' filter__item--has-checked' : '';

		if (premmerce_pwpf_fs()->is__premium_only()) {
			//add dropdown_hover display type in premium plan
			if (premmerce_pwpf_fs()->can_use_premium_code()) {
				if ( 'dropdown_hover' === $attribute->display_type ) {
					$filterItemAdditionalClasses .= ' premmerce_filter_dropdown_hover';
				}
			}
		}
		?>

	<div class="filter__item <?php echo esc_attr($filterItemAdditionalClasses); ?>" data-premmerce-filter-drop-scope>
		<?php
			$dropdown = in_array($attribute->display_type, $dropdownList);
			$scroll   = in_array($attribute->display_type, $scrollList);
		?>

		<div class="filter__header filter__header-<?php echo esc_attr($attribute->display_type); ?>"
			<?php echo $dropdown ? 'data-premmerce-filter-drop-handle' : ''; ?>>
			<div class="filter__title <?php echo esc_attr($boldTitle) . ' ' . esc_attr($titleAppearance); ?>">
				<?php
				echo esc_attr(apply_filters('premmerce_filter_render_item_title', $attribute->attribute_label, $attribute));
				?>
			</div>
			<?php do_action('premmerce_filter_render_item_after_title', $attribute); ?>
		</div>
		<?php
			$filterInnerAdditionalClasses  = '';
			$filterInnerAdditionalClasses .= 'filter__inner-' . $attribute->display_type;
			$filterInnerAdditionalClasses .= ( $dropdown && !$attribute->has_checked ) ? ' filter__inner--js-hidden' : '';
			$filterInnerAdditionalClasses .= $scroll ? ' filter__inner--scroll' : '';
		?>
		<div class="filter__inner <?php echo esc_attr($filterInnerAdditionalClasses); ?>" data-premmerce-filter-inner <?php echo $scroll ? 'data-filter-scroll' : ''; ?>>
			<?php do_action('premmerce_filter_render_item_' . $attribute->html_type, $attribute); ?>
		</div>
	</div>
		<?php do_action_ref_array('premmerce_filter_render_item_after', [&$attribute]); ?>
	<?php endforeach ?>
	<?php if ($showFilterButton) : ?>
	<div class="filter__item filter__item--type-submit-button">
		<?php do_action('premmerce_filter_submit_button_before'); ?>
		<button data-filter-button data-filter-url="" type="button" class="button button-filter-submit">
			<?php echo esc_attr(apply_filters('premmerce_filter_submit_button_label', __('Filter', 'premmerce-filter'))); ?>
		</button>
		<?php do_action('premmerce_filter_submit_button_after'); ?>
	</div>
	<?php endif; ?>
</div>

<?php echo ( !empty($args['after_widget']) ) ? wp_kses($args['after_widget'], FilterPlugin::HTML_TAGS) : ''; ?>

<?php echo ( !empty($args['name']) && ( 'shortcode' === $args['name'] || 'filterblock' === $args['name'] ) ) ? '</div>' : ''; ?>
