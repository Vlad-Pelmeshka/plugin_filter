<?php

if ( ! defined('ABSPATH')) {
	exit;
}


/** Get brands @var array $brands */
/** Get brandsConfig @var array $brandsConfig */
?>
<h2><?php esc_attr_e('Brands', 'premmerce-filter'); ?></h2>

<div class="tablenav top">
	<?php require __DIR__ . '/actions.php'; ?>
</div>

<table class="widefat striped premmerce-filter-table">
	<thead>
	<tr>
		<td class="check-column">
			<label for="">
				<input type="checkbox" data-select-all="attribute">
			</label>
		</td>
		<th><?php esc_attr_e('Brand', 'premmerce-filter'); ?></th>
		<th class="premmerce-filter-table__align-center"><?php esc_attr_e('Visibility', 'premmerce-filter'); ?></th>
		<th class="premmerce-filter-table__align-right"></th>
	</tr>
	</thead>
	<tbody data-sortable="premmerce_filter_sort_brands">


	<?php if (count($brands) > 0) : ?>
		<?php foreach ($brands as $brandId => $label) : ?>
			<tr>
				<td>
					<input data-selectable="attribute" type="checkbox" data-id="<?php echo esc_attr($brandId); ?>">
				</td>
				<td><?php echo esc_attr($label); ?></td>

				<td class="premmerce-filter-table__align-center">
					<?php $active = $brandsConfig[ $brandId ]['active']; ?>
					<span data-single-action="premmerce_filter_bulk_action_brands" data-id="<?php echo esc_attr($brandId); ?>"
						  data-value="<?php echo $active? 'hide' : 'display'; ?>"
						  title="<?php $active? esc_attr_e('Hide', 'premmerce-filter') : esc_attr_e('Display', 'premmerce-filter'); ?>"
						  class="dashicons dashicons-<?php echo $active? 'visibility' : 'hidden'; ?> click-action-span"></span>
				</td>
				<td class="premmerce-filter-table__align-right">
					<span data-sortable-handle class="sortable-handle dashicons dashicons-menu"></span>
				</td>
			</tr>
		<?php endforeach ?>
	<?php else : ?>
		<tr>
			<td colspan="2">
				<?php esc_attr_e('No items found', 'premmerce-filter'); ?>
			</td>
		</tr>
	<?php endif ?>
	</tbody>
</table>

<div class="tablenav bottom">
	<?php require __DIR__ . '/actions.php'; ?>
</div>
