<?php

use Premmerce\Filter\Filter\ItemRenderer;

if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="filter__properties-list">
	<?php foreach ($attribute->terms as $attrTerm) : ?>
	<?php $attrId = 'filter-checkgroup-id-' . $attribute->attribute_name . '-' . $attrTerm->slug; ?>
	<div class="filter__properties-item
	<?php
		if ($attrTerm->checked) :
			?>
		filter__properties-item--active
												 <?php
   endif
		?>
   ">
		<div class="filter__checkgroup" data-filter-control-checkgroup>
			<div class="filter__checkgroup-body">
				<div class="filter__checkgroup-link">
					<input class="filter__checkgroup-control"
					<?php
					echo $attrTerm->checked ? 'checked ' : ''; ?> type="checkbox" data-filter-control id="<?php echo esc_attr($attrId); ?>"
						<?php echo 0 === $attrTerm->count? 'disabled' : ''; ?> data-premmerce-filter-link="<?php echo esc_url($attrTerm->link); ?>" autocomplete="off">
					<label class="filter__checkgroup-check" data-filter-control-label for="<?php echo esc_attr($attrId); ?>"></label>
					<label class="filter__checkgroup-title <?php echo 0 === $attrTerm->count ? 'disabled' : ''; ?>" for="<?php echo esc_attr($attrId); ?>">
						<?php echo esc_attr(apply_filters('premmerce_filter_render_checkbox_title', $attrTerm->name, $attrTerm)); ?>
					</label>
				</div>
			</div>
			<div class="filter__checkgroup-aside">
				<?php if ($attribute->getSlug() === 'product_cat' && !empty($attrTerm->children) && is_array($attrTerm->children)) : ?>
				<div class="filter__inner-hierarchy-button">
					<a class="filter__inner-hierarchy-button-open-close" data-hierarchy-button
						data-hierarchy-id="<?php echo esc_attr($attrTerm->term_id); ?>" href="javascript:void(0);">&plus;</a>
				</div>
				<?php endif; ?>
				<span class="filter__checkgroup-count">
					<?php echo esc_attr( $attrTerm->count ); ?>
				</span>
			</div>

		</div>
	</div>

	<?php ItemRenderer::renderRecursiveChildren($this, $attrTerm, $attribute, $attrTerm->checked, $attrTerm->term_id); ?>
	<?php endforeach ?>
</div>
