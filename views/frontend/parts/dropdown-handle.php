<?php if (! defined('ABSPATH')) {
	exit;
}
?>

<?php if (in_array($attribute->display_type, ['dropdown', 'scroll_dropdown', 'dropdown_hover'])) : ?>
	<div class="filter__handle">
		<div class="filter__handle-ico filter__handle-ico--plus <?php echo $attribute->has_checked ? 'hidden' : ''; ?>" data-premmerce-filter-drop-ico>
			<div class="filter__icon-plus"></div>
		</div>
		<div class="filter__handle-ico filter__handle-ico--minus <?php echo $attribute->has_checked ? '' : 'hidden'; ?>" data-premmerce-filter-drop-ico>
			<div class="filter__icon-minus"></div>
		</div>
	</div>
<?php
endif;
