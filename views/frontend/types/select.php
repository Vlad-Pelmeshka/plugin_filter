<?php if ( ! defined('ABSPATH')) {
	exit;
}
?>
<select class="filter__select" data-filter-control-select class="filter__scroll form-control input-sm">
	<option value="<?php echo esc_url($attribute->reset_url); ?>">
	<?php
		/* translators: %s: label */
		$optionText = sprintf(__('Any %s', 'woocommerce'), $attribute->attribute_label);
		echo esc_attr($optionText);
	?>
			</option>
	<?php foreach ($attribute->terms as $attrTerm) : ?>
	<?php $selected = $attrTerm->checked ? 'selected' : ''; ?>
	<option <?php echo esc_attr($selected); ?> value="<?php echo esc_url($attrTerm->link); ?>">
		<?php echo esc_attr($attrTerm->name) . ' (' . ( esc_attr($attrTerm->count) ) . ')'; ?></option>
	<?php endforeach ?>
</select>
