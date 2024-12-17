<?php
if (! defined('ABSPATH')) {
	exit;
}
use Premmerce\Filter\FilterPlugin;
/**
 * Get taxonomy data
 *
 * @var array $terms
 * @var array $termsConfig
 * @var string $taxonomyName
 * @var string $dataAction
 * @var string $taxonomy
 * @var array $paginationArgs
 *
*/
?>
<h2><?php echo esc_attr($taxonomy->label); ?></h2>
<div class="tablenav top">
	<?php require __DIR__ . '/actions.php'; ?>
	<div class="tablenav-pages premmerce-filter-pagination"><?php echo wp_kses(paginate_links($paginationArgs), FilterPlugin::HTML_TAGS); ?></div>
</div>

<?php if ($prevId) : ?>
<div class="premmerce-filter-swap-container" data-swap-id="<?php echo esc_attr($prevId); ?>">
	<?php esc_attr_e('Move to previous page', 'premmerce-filter'); ?>
</div>
<?php endif; ?>

<table class="widefat premmerce-filter-table">
	<thead>
		<tr>
			<td class="check-column">
				<label for="">
					<input type="checkbox" data-select-all="attribute">
				</label>
			</td>
			<th><?php esc_attr_e('Terms', 'premmerce-filter'); ?></th>
			<th class="premmerce-filter-table__align-center"><?php esc_attr_e('Visibility', 'premmerce-filter'); ?></th>
			<th class="premmerce-filter-table__align-right"></th>
		</tr>
	</thead>
	<tbody data-sortable="premmerce_filter_sort_<?php echo esc_attr($taxonomyName); ?>">


		<?php if (count($terms) > 0) : ?>
		<?php foreach ($terms as $termId => $label) : ?>
		<tr>
			<td>
				<input data-selectable="attribute" type="checkbox" data-id="<?php echo esc_attr($termId); ?>">
			</td>
			<td><?php echo esc_attr($label); ?></td>

			<td class="premmerce-filter-table__align-center">
				<?php $active = $termsConfig[ $termId ]['active']; ?>
				<span data-single-action="<?php echo esc_attr($dataAction); ?>" data-id="<?php echo esc_attr($termId); ?>"
					data-value="<?php echo $active? 'hide' : 'display'; ?>"
					title="<?php $active? esc_attr_e('Hide', 'premmerce-filter') : esc_attr_e('Display', 'premmerce-filter'); ?>"
					class="dashicons dashicons-<?php echo $active? 'visibility' : 'hidden'; ?> click-action-span"></span>
			</td>
			<td class="premmerce-filter-table__align-right"><span data-sortable-handle
					class="sortable-handle dashicons dashicons-menu"></span>
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

<?php if ($nextId) : ?>
<div class="premmerce-filter-swap-container" data-swap-id="<?php echo esc_attr($nextId); ?>">
	<?php esc_attr_e('Move to next page', 'premmerce-filter'); ?>
</div>
<?php endif; ?>

<div class="tablenav bottom">
	<?php require __DIR__ . '/actions.php'; ?>
	<div class="tablenav-pages premmerce-filter-pagination"><?php echo wp_kses(paginate_links($paginationArgs), FilterPlugin::HTML_TAGS); ?></div>
</div>
