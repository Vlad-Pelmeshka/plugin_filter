<?php use Premmerce\Filter\Filter\ItemRenderer;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Radio Field Type
 *
 * @var bool $isExpanded
 * @var bool $isRootChecked
 * @var int $rootId
 * @var \Premmerce\Filter\Filter\Items\Types\TaxonomyFilter $attribute
 * @var WP_Term $term
 */
?>
<div class="filter__checkgroup-inner
<?php
if ($isExpanded || $term->checked) :
	?>
 filter__checkgroup-inner-expanded
 <?php
else :
	?>
 filter__checkgroup-inner-collapsed
														  <?php
endif;?>"
	data-parent-id="<?php echo esc_attr($rootId); ?>">
	<?php $filterCheckGroupId = 'filter-checkgroup-id-' . $attribute->attribute_name . '-' . $term->slug; ?>
	<div class="filter__properties-item
	<?php
	if ($term->checked) :
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
					echo $term->checked ? 'checked ' : ''; ?> type="radio" data-filter-control id="<?php echo esc_attr($filterCheckGroupId); ?>"
						<?php echo 0 === $term->count  ? 'disabled' : ''; ?> data-premmerce-filter-link="<?php echo esc_url($term->link); ?>" autocomplete="off">
					<label class="filter__checkgroup-check" data-filter-control-label for="<?php echo esc_attr($filterCheckGroupId); ?>"></label>
					<label class="filter__checkgroup-title <?php echo 0 === $term->count ? 'disabled' : ''; ?>"
						for="<?php echo esc_attr($filterCheckGroupId); ?>">
						<?php echo esc_attr($term->name); ?>
					</label>
				</div>
			</div>
			<div class="filter__checkgroup-aside">
				<span class="filter__checkgroup-count">
					<?php echo esc_attr( $term->count ); ?>
				</span>
			</div>
		</div>
	</div>
	<?php ItemRenderer::renderRecursiveChildren($this, $term, $attribute, $isRootChecked, $rootId, $term->checked); ?>
</div>
