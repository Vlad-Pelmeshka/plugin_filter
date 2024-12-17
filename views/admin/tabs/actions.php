<?php

if (! defined('ABSPATH')) {
	exit;
}

/** Get actions @var array $actions */
/** Get dataAction @var string $dataAction */
?>
<div class="alignleft actions bulkactions" data-bulk-actions>
	<label for="bulk-action-selector-top"
		class="screen-reader-text"><?php esc_attr_e('Select bulk action', 'premmerce-filter'); ?></label>
	<select data-bulk-action-select>
		<?php foreach ($actions as $key => $actionTitle) : ?>
		<?php if (is_array($actionTitle)) : ?>
		<optgroup label="<?php echo esc_attr($key); ?>">
			<?php foreach ($actionTitle as $itemKey => $itemTitle) : ?>
			<option value="<?php echo esc_attr($itemKey); ?>">
				<?php echo esc_attr($itemTitle['text']); ?>
			</option>
			<?php endforeach; ?>
		</optgroup>
		<?php else : ?>
		<option value="<?php echo esc_attr($key); ?>"><?php echo esc_attr($actionTitle); ?></option>
		<?php endif; ?>
		<?php endforeach; ?>
	</select>
	<button type="button" data-action="<?php echo esc_attr($dataAction); ?>"
		class="button"><?php esc_attr_e('Apply', 'premmerce-filter'); ?></button>
</div>
