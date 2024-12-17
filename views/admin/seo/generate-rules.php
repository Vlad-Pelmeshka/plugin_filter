<?php

if ( ! defined('ABSPATH')) {
	exit;
}

/** Get categories @var array $categories */
/** Get attributes @var array $attributes */
/** Get brands @var array $brands */
?>

<div class="wrap">
	<div>
		<a href="<?php echo esc_url(menu_page_url('premmerce-filter-admin', false)) . '&tab=seo'; ?>">
			<?php esc_attr_e('Back', 'premmerce-filter'); ?>
		</a>
	</div>
	<form data-generation-form method="post">

		<input type="hidden" name="action" value="generation_progress">

		<div class="form-wrap">
			<h3><?php esc_attr_e('Generate rules', 'premmerce-filter'); ?></h3>
			<div class="form-field form-required">
				<label><?php esc_attr_e('Categories', 'premmerce-filter'); ?></label>
				<select multiple
						data-generate-select-two
						placeholder="<?php esc_attr_e('Select category', 'premmerce-filter'); ?>"
						name="filter_category[]"
						style="width: 200px"
				>
					<?php foreach ($categories as $catId => $category) : ?>
						<option value="<?php echo esc_attr($catId); ?>"><?php echo esc_attr($category); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div data-taxonomies-wrapper>
				<div class="premmerce-flex-form-fields">
					<div class="premmerce-flex-form-field">
						<label><?php esc_attr_e('Taxonomies', 'premmerce-filter'); ?></label>
						<select
								class="premmerce-filer-bulk-taxonomy"
								data-generate-select-two
								data-generate-rule-taxonomy
								data-select-taxonomy
								placeholder="<?php esc_attr_e('Select taxonomy', 'premmerce-filter'); ?>"
								name="filter_taxonomy[1]"
								style="width: 200px"
						>
							<option></option>
							<?php foreach ($attributes as $taxonomyID => $attribute) : ?>
								<option value="<?php echo esc_attr($taxonomyID); ?>"><?php echo esc_attr($attribute); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="premmerce-flex-form-field">
						<label><?php esc_attr_e('Terms', 'premmerce-filter'); ?></label>
						<select multiple
								name="filter_term[1][]"
								data-generate-select-two
								data-select-term
								style="width: 200px"
								data-allow-clear="true"
								data-placeholder="<?php esc_attr_e('Select term', 'premmerce-filter'); ?>"
								data-selected-value="
								<?php echo isset($dataTermIds) ? esc_attr(htmlspecialchars(wp_json_encode($dataTermIds), ENT_QUOTES, 'UTF-8')) : ''; ?>
								">
							<option value="">
								<?php esc_attr_e('Select term', 'premmerce-filter'); ?>
							</option>
						</select>
					</div>
				</div>
			</div>
			<button type="button" class="button" data-add-taxonomy-button>
				<?php esc_attr_e('Add taxonomy', 'premmerce-filter'); ?>
			</button>

			<?php premmerce_filter_admin_seo_variable_inputs(); ?>
			<button data-generate-button type="submit" class="button">
				<?php esc_attr_e('Generate', 'premmerce-filter'); ?>
			</button>
		</div>
	</form>
</div>

<div class="generation-taxonomy" data-taxonomy-prototype data-select-field hidden>
	<div class="premmerce-flex-form-field">
		<label><?php esc_attr_e('Taxonomies', 'premmerce-filter'); ?></label>
		<div class="generation-taxonomy__select-wrapper">
			<select
					class="premmerce-filer-bulk-taxonomy"
					placeholder="<?php esc_attr_e('Select taxonomy', 'premmerce-filter'); ?>"
					data-select-taxonomy
					data-generate-rule-taxonomy
					style="width: 200px"
			>
				<option></option>
				<?php foreach ($attributes as $taxonomyID => $attribute) : ?>
					<option value="<?php echo esc_attr($taxonomyID); ?>"><?php echo esc_attr($attribute); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div class="premmerce-flex-form-field">
		<label><?php esc_attr_e('Terms', 'premmerce-filter'); ?></label>
		<select multiple
				data-select-term
				style="width: 200px"
				data-allow-clear="true"
				data-placeholder="<?php esc_attr_e('Select terms', 'premmerce-filter'); ?>"
				data-selected-value="">
			<option value="">
				<?php esc_attr_e('Select term', 'premmerce-filter'); ?>
			</option>
		</select>
	</div>
	<div class="premmerce-flex-form-field">
	<span style="margin-top: 35px;" class="remove-icon dashicons dashicons-no-alt"
		  data-remove-element="[data-select-field]"></span>
	</div>
</div>
