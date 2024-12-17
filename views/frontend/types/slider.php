<?php if ( ! defined('ABSPATH')) {
	exit;
}

$values = $attribute->values;
?>
<div class="filter__inner" data-premmerce-slider-scope>
	<form action="<?php echo esc_url(apply_filters('premmerce_product_filter_form_action', '')); ?>" method="GET"
		class="filter__slider-form" data-premmerce-filter-slider-form>
		<div class="filter__slider-control-group">
			<div class="filter__slider-control-column">
				<input class="filter__slider-control" type="number" autocomplete="off"
					data-premmerce-filter-slider-min="<?php echo esc_attr($values['min']); ?>"
					name="min_<?php echo esc_attr($attribute->attribute_name); ?>"
					value="<?php echo esc_attr($values['min_selected']); ?>" data-premmerce-slider-trigger-change>
			</div>
			<div class="filter__slider-control-column">
				<input class="filter__slider-control" type="number" autocomplete="off"
					data-premmerce-filter-slider-max="<?php echo esc_attr($values['max']); ?>"
					name="max_<?php echo esc_attr($attribute->attribute_name); ?>"
					value="<?php echo esc_attr($values['max_selected']); ?>" data-premmerce-slider-trigger-change>
			</div>
		</div>
		<div class="filter__range-slider">
			<div class="pc-range-slider">
				<div class="pc-range-slider__wrapper">
					<div class="pc-range-slider__control" data-premmerce-filter-range-slider></div>
				</div>
			</div>
		</div>

		<?php
		wc_query_string_form_fields(
			apply_filters(
				'premmerce_product_filter_slider_include_fields',
				$_GET,
				['max_' . $attribute->attribute_name, 'min_' . $attribute->attribute_name])
			)
		?>
	</form>
</div>
