<?php if ( ! defined('ABSPATH')) {
	exit;
}
?>
<div class="filter__colors-box">
	<?php foreach ($attribute->terms as $attrTerm) : ?>
		<?php $attrId = 'filter-checkgroup-id-' . $attribute->attribute_name . '-' . $attrTerm->slug; ?>

		<div class="filter__colors-item">
			<input class="filter__checkgroup-control "
				   id="<?php echo esc_attr($attrId); ?>"
				   type="checkbox"
				   autocomplete="off"
				   data-premmerce-filter-link="<?php echo esc_url($attrTerm->link); ?>"
				<?php echo 0 === $attrTerm->count ? 'disabled' : ''; ?>
				   <?php
					if ($attrTerm->checked) :
						?>
						checked<?php endif ?>
			>
			<?php $color = $attrTerm->color ? $attrTerm->color: '#96588a'; ?>
			<label class="filter__color-button"
				   for="<?php echo esc_attr($attrId); ?>"
				   style="background-color: <?php echo esc_attr($color); ?>; "
				   title="<?php echo esc_attr($attrTerm->name); ?>"
			>
			</label>
		</div>
	<?php endforeach ?>
</div>
