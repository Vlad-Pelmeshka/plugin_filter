<?php if ( ! defined('ABSPATH')) {
	exit;
}
?>
<div class="filter__colors-box filter__images-box">
	<?php foreach ($attribute->terms as $attrTerm) : ?>
		<?php $filterId = 'filter-checkgroup-id-' . $attribute->attribute_name . '-' . $attrTerm->slug; ?>

		<div class="filter__colors-item filter__images-item">
			<input class="filter__checkgroup-control "
				   id="<?php echo esc_attr($filterId); ?>"
				   type="checkbox"
				   autocomplete="off"
				   data-premmerce-filter-link="<?php echo esc_url($attrTerm->link); ?>"
				<?php echo 0 === $attrTerm->count ? 'disabled' : ''; ?>
				   <?php
					if ($attrTerm->checked) :
						?>
						checked<?php endif ?>
			>
			<?php
			$image_id = $attrTerm->image ? $attrTerm->image: '';
			$style    = 'background-color: #96588a';
			if (!empty($image_id)) {
				$style = 'background-image: url(' . wp_get_attachment_image_url( $image_id, 'thumbnail' ) . ');';
			}

			?>
			<label class="filter__color-button filter__image-button"
				   for="<?php echo esc_attr($filterId); ?>"
				   style="<?php echo esc_attr($style); ?>"
				   title="<?php echo esc_attr($attrTerm->name); ?>"
			>
			</label>
		</div>
	<?php endforeach ?>
</div>
