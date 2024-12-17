<?php if ( ! defined('ABSPATH')) {
	exit;
}
?>
<div class="filter__labels-box">
	<?php foreach ($attribute->terms as $attrTerm) : ?>
	<?php $filterCheckgroupId = 'filter-checkgroup-id-' . $attribute->attribute_name . '-' . $attrTerm->slug; ?>

	<div class="filter__label-item">
		<input class="filter__checkgroup-control" id="<?php echo esc_attr($filterCheckgroupId); ?>" type="checkbox" autocomplete="off"
			data-premmerce-filter-link="<?php echo esc_url($attrTerm->link); ?>" <?php echo 0 === $attrTerm->count ? 'disabled' : ''; ?>
			<?php
			if ($attrTerm->checked) :
				?>
				checked<?php endif ?>>
		<label class="filter__label-button" for="<?php echo esc_attr($filterCheckgroupId); ?>" title="<?php echo esc_attr($attrTerm->name); ?>">
			<?php echo esc_attr($attrTerm->name); ?>
		</label>
	</div>
	<?php endforeach ?>
</div>
