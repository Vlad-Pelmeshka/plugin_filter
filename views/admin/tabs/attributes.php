<?php

if (! defined('ABSPATH')) {
	exit;
}

use Premmerce\Filter\FilterPlugin;

/**
 * Attributes variables
 *
 * @var array $attributes
 * @var array $attributesConfig
 * @var array $types
 * @var array $actions
 * @var array $display
 * @var array $premiumAttributes
 * @var array $paginationArgs
 */

?>

<h2><?php esc_attr_e('Attributes', 'premmerce-filter'); ?></h2>


<div class="tablenav top">
	<?php require __DIR__ . '/actions.php'; ?>
	<div class="tablenav-pages premmerce-filter-pagination"><?php echo wp_kses(paginate_links($paginationArgs), FilterPlugin::HTML_TAGS); ?></div>
</div>

<?php if ($prevId) : ?>
	<div class="premmerce-filter-swap-container" data-swap-id="<?php echo esc_attr($prevId); ?>">
	<?php esc_attr_e('Move to previous page', 'premmerce-filter'); ?>
</div>
<?php endif; ?>
<table class="widefat striped premmerce-filter-table">
	<thead>
		<tr>
			<td width="5%" class="check-column">
				<label for="">
					<input type="checkbox" data-select-all="attribute">
				</label>
			</td>
			<th width="20%"><?php esc_attr_e('Field type', 'premmerce-filter'); ?></th>
			<th width="20%"><?php esc_attr_e('Display as', 'premmerce-filter'); ?></th>
			<th width="25%"><?php esc_attr_e('Attribute', 'premmerce-filter'); ?></th>
			<th width="20%" class="premmerce-filter-table__align-center">
				<?php esc_attr_e('Visibility', 'premmerce-filter'); ?>
			</th>
			<?php foreach (apply_filters('premmerce-filter-table-attributes-columns-header', []) as $columnArgs) : ?>
				<th width="<?php echo isset($columnArgs[ 'width' ]) ? esc_attr($columnArgs[ 'width' ]) : '10%'; ?>" class="premmerce-filter-table__align-
								  <?php
			echo isset($columnArgs[ 'align' ]) ? esc_attr($columnArgs[ 'align' ]) : 'left';
				echo isset($columnArgs['class']) ? ' ' . esc_attr($columnArgs['class']) : '';
									?>
				">
				<?php echo esc_attr($columnArgs['label']); ?>
			</th>
			<?php endforeach; ?>
			<th width="10%" class="premmerce-filter-table__align-right"></th>
		</tr>
	</thead>
	<tbody data-sortable="premmerce_filter_sort_attributes" data-prev="<?php echo esc_attr($prevId); ?>"
		data-next="<?php echo esc_attr($nextId); ?>" data-swap="">

		<?php if (count($attributes) > 0) : ?>
			<?php
			foreach ($attributes as $attrId => $label) :

				$freeDisabled = '';
				$premiumLink = '';
				//check if attribute is premium on free plan
				if (!premmerce_pwpf_fs()->can_use_premium_code()) {
					$freeDisabled    = ( in_array($attrId, $premiumAttributes) ) ? 'disabled' : '';
					$premiumLinkText = __('Premium', 'premmerce-filter');
					$premiumLink     = '<a class="premmerce-premium-blue" href="' . admin_url('admin.php?page=premmerce-filter-admin-pricing') . '">' . $premiumLinkText . '</a>';
				}
				?>

		<tr>
			<td>
				<input data-selectable="attribute" type="checkbox" data-id="<?php echo esc_attr($attrId); ?>"
					<?php echo esc_attr($freeDisabled); ?>>
			</td>

			<td>
				<?php
					$selectTypes = $types;
					//remove image/slider/color types from select for Show on sale / in stock / rating filter (PremiumAttributes)
				if (in_array($attrId, $premiumAttributes, true)) :
					$selectTypes = array_diff_key(
						$types,
						array_flip([FilterPlugin::TYPE_COLOR, FilterPlugin::TYPE_SLIDER, FilterPlugin::TYPE_IMAGE])
					);
				endif;
				?>

				<select data-single-action="premmerce_filter_bulk_action_attributes" data-id="<?php echo esc_attr($attrId); ?>"
					<?php echo esc_attr($freeDisabled); ?>>
					<?php
					foreach ($selectTypes as $key => $selectType) :
						$disabled = '';
						echo ( ! premmerce_pwpf_fs()->can_use_premium_code() && 'premium' === $selectType['plan'] ) ? 'disabled' : '';
						?>
					<option <?php echo selected($key, $attributesConfig[$attrId]['type']); ?> value="<?php echo esc_attr($key); ?>" <?php echo esc_attr($disabled); ?>>
						<?php echo esc_attr($selectType['text']); ?>
					</option>
					<?php endforeach; ?>
				</select>

				<?php
				if (premmerce_pwpf_fs()->is__premium_only()) :
					if (premmerce_pwpf_fs()->can_use_premium_code()) :
						if ( FilterPlugin::TYPE_COLOR === $attributesConfig[$attrId]['type'] || FilterPlugin::TYPE_IMAGE === $attributesConfig[$attrId]['type'] ) :
							?>
				<a class="button" href="#" data-open-dialog="[data-<?php echo esc_attr($attributesConfig[$attrId]['type']); ?>-dialog]"
					data-attribute-id="<?php echo esc_attr($attrId); ?>">
					<?php
						/* translators: %s: Setup name */
						$setupName = sprintf(__('Setup %ss', 'premmerce-filter'), esc_attr($attributesConfig[$attrId]['type']));
						echo esc_attr($setupName);
							?>
				</a>
					<?php endif; ?>
					<?php
					endif; ?>
				<?php endif;
				?>
			</td>
			<td>
				<select data-single-action="premmerce_filter_bulk_action_attributes" data-id="<?php echo esc_attr($attrId); ?>"
				<?php echo esc_attr($freeDisabled); ?>>
					<?php foreach ($display as $key => $selectType) : ?>
						<?php
						$disabled = '';
						$disabled = ( !premmerce_pwpf_fs()->can_use_premium_code() && 'premium' === $selectType['plan'] ) ? 'disabled' : '';
						?>
						<?php $displayValue = substr($key, strlen('display_')); ?>
					<option <?php echo selected($displayValue, $attributesConfig[$attrId]['display_type']); ?>
						value="<?php echo esc_attr($key); ?>" <?php echo esc_attr($disabled); ?>>
						<?php echo esc_attr($selectType['text']); ?>
					</option>
					<?php endforeach; ?>
				</select>

			</td>
			<td class="premmerce-filter-table__capitalize"><?php echo esc_attr($label); ?></td>
			<td class="premmerce-filter-table__align-center">
				<?php $active = $attributesConfig[$attrId]['active']; ?>

				<?php if ('disabled' === $freeDisabled ) : ?>
					<?php echo wp_kses($premiumLink, FilterPlugin::HTML_TAGS); ?>
				<?php else : ?>
				<span data-single-action="premmerce_filter_bulk_action_attributes" data-id="<?php echo esc_attr($attrId); ?>"
					data-value="<?php echo $active ? 'hide' : 'display'; ?>"
					title="<?php $active ? esc_attr_e('Hide', 'premmerce-filter') : esc_attr_e('Display', 'premmerce-filter'); ?>"
					class="dashicons dashicons-<?php echo $active ? 'visibility' : 'hidden'; ?> click-action-span">
				</span>
				<?php endif; ?>

			</td>
				<?php foreach (apply_filters('premmerce-filter-table-attributes-columns-row', [], $attributesConfig, $attrId) as $columnArgs) : ?>
			<td class="premmerce-filter-table__align-
			<?php
			echo isset($columnArgs[ 'align' ]) ? esc_attr($columnArgs[ 'align' ]) : 'left';
				echo isset($columnArgs['class']) ? ' ' . esc_attr($columnArgs['class']) : '';
					?>
						">
					<?php echo esc_attr($columnArgs['content']); ?>
			</td>
				<?php endforeach; ?>
			<td class="premmerce-filter-table__align-right">
				<?php if ('disabled' !== $freeDisabled ) : ?>
					<span data-sortable-handle class="sortable-handle dashicons dashicons-menu"></span>
				<?php endif; ?>
			</td>
		</tr>
			<?php endforeach ?>
		<tr>
			<input type="hidden" name="replace-next">
		</tr>
		<?php else : ?>
		<tr>
			<td colspan="5">
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


<?php
//show color or image popups only for premium plan
//This IF block will be auto removed from the Free version.
if (premmerce_pwpf_fs()->is__premium_only()) :
	if (premmerce_pwpf_fs()->can_use_premium_code()) :
		foreach ($types as $key => $attrType) :
			if (in_array($key, [FilterPlugin::TYPE_IMAGE, FilterPlugin::TYPE_COLOR], true)) :
				?>
<div data-<?php echo esc_attr($key); ?>-dialog title="" class="hidden" data-save-text="<?php esc_attr_e('Save', 'premmerce-filter'); ?>">

</div>
			<?php
				endif;
		endforeach;
	endif;
endif;
