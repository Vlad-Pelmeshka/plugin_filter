<?php

use Premmerce\Filter\Widget\FilterWidget;

if (! defined('ABSPATH')) {
	exit;
}
/**
 * Get widget
 *
 * @var  WP_Widget $widget
 */

?>

<!-- Title -->
<p>
	<label for="<?php echo esc_attr($widget->get_field_id('title')); ?>"><?php esc_attr_e('Title'); ?></label>
	<input class="widefat" id="<?php echo esc_attr($widget->get_field_id('title')); ?>"
		name="<?php echo esc_attr($widget->get_field_name('title')); ?>" type="text"
		value="<?php echo esc_attr($title); ?>">
</p>

<?php if ( FilterWidget::FILTER_WIDGET_ID === $widget->id_base) : //only for filter widget. ?>
<!-- Style -->
<?php FilterWidget::renderWidgetSelect($widget, 'style', 'Style', $style, $filterStyles, 'premmerce-filter-widget-style'); ?>

<div class="premmerce-widget-filter-fields <?php echo esc_attr($currentPlan); ?>"
	<?php echo ( 'custom' !== $style ) ? 'hidden' : ''; ?>>

	<!-- Main styles -->
	<div class="premmerce-widget premmerce-widget-main-styles">
		<h3 class="premmerce-widget-heading"><?php esc_attr_e('Main styles', 'premmerce-filter'); ?></h3>
		<div class="premmerce-widget-row">
			<div class="premmerce-widget-column">
				<!-- Bold filters title -->
				<?php FilterWidget::renderWidgetCheckbox($widget, 'bold_title', 'Bold filters title', $boldTitle, $currentPlan); ?>
			</div>
			<div class="premmerce-widget-column">
				<!-- Filter Background Color -->
				<?php FilterWidget::renderWidgetInput($widget, 'bg_color', 'Background Color', $bgColor, 'premmerce-widget-color-picker', 'text', $currentPlan); ?>
			</div>
		</div>
		<div class="premmerce-widget-row">
			<div class="premmerce-widget-column">
				<!--  Add border for filters -->
				<?php FilterWidget::renderWidgetCheckbox($widget, 'add_border', 'Add border for filters', $addBorder, $currentPlan); ?>
			</div>
			<div class="premmerce-widget-column">
				<!-- Border color picker-->
				<?php FilterWidget::renderWidgetInput($widget, 'border_color', 'Border Color', $borderColor, 'premmerce-widget-color-picker', 'text', $currentPlan); ?>
			</div>
		</div>
	</div>


	<!-- Filter by Price -->
	<div class="premmerce-widget premmerce-widget-filter-by-price">
		<h3 class="premmerce-widget-heading"><?php esc_attr_e('Price filter styles', 'premmerce-filter'); ?></h3>
		<div class="premmerce-widget-row">
			<div class="premmerce-widget-column">
				<!-- Price filter input background -->
				<?php FilterWidget::renderWidgetInput($widget, 'price_input_bg', 'Input Background', $priceInputBg, 'premmerce-widget-color-picker', 'text', $currentPlan); ?>
			</div>
			<div class="premmerce-widget-column">
				<!-- Price filter input text Color -->
				<?php FilterWidget::renderWidgetInput($widget, 'price_input_text', 'Input Text Color', $priceInputText, 'premmerce-widget-color-picker', 'text', $currentPlan); ?>
			</div>
		</div>
		<div class="premmerce-widget-row">
			<div class="premmerce-widget-column">
				<!-- Price filter input background -->
				<?php FilterWidget::renderWidgetInput($widget, 'price_slider_range', 'Slider Range Color', $priceSliderRange, 'premmerce-widget-color-picker', 'text', $currentPlan); ?>
			</div>
			<div class="premmerce-widget-column">
				<!-- Filter Background Color -->
				<?php FilterWidget::renderWidgetInput($widget, 'price_slider_handle', 'Slider Handle Color', $priceSliderHandle, 'premmerce-widget-color-picker', 'text', $currentPlan); ?>
			</div>
		</div>
	</div>


	<div class="premmerce-widget premmerce-widget-filter-titles">
		<h3 class="premmerce-widget-heading">
			<?php esc_attr_e('Filter titles and checkboxes styles', 'premmerce-filter'); ?>
		</h3>
		<div class="premmerce-widget-row">
			<div class="premmerce-widget-column">
				<!-- Filters title size -->
				<?php FilterWidget::renderWidgetInput($widget, 'title_size', 'Filters title size', $titleSize, 'premmerce-widget-number-input', 'number', $currentPlan); ?>
			</div>
			<div class="premmerce-widget-column">
				<!-- Filters title color -->
				<?php FilterWidget::renderWidgetInput($widget, 'title_color', 'Filters title color', $titleColor, 'premmerce-widget-color-picker', 'text', $currentPlan); ?>
			</div>
		</div>
		<div class="premmerce-widget-row">
			<div class="premmerce-widget-column">
				<!-- Terms title size -->
				<?php FilterWidget::renderWidgetInput($widget, 'terms_title_size', 'Terms title size', $termsTitleSize, 'premmerce-widget-number-input', 'number', $currentPlan); ?>
			</div>
			<div class="premmerce-widget-column">
				<!-- Terms title color -->
				<?php FilterWidget::renderWidgetInput($widget, 'terms_title_color', 'Terms title color', $termsTitleColor, 'premmerce-widget-color-picker', 'text', $currentPlan); ?>
			</div>
		</div>
		<div class="premmerce-widget-row">
			<div class="premmerce-widget-column">
				<!-- Checkbox Background Color -->
				<?php FilterWidget::renderWidgetInput($widget, 'checkbox_border_color', 'Checkbox Border Color', $checkboxBorderColor, 'premmerce-widget-color-picker', 'text', $currentPlan); ?>
			</div>
			<div class="premmerce-widget-column">
				<!-- Checkbox Background Color -->
				<?php FilterWidget::renderWidgetInput($widget, 'checkbox_color', 'Checkbox Color', $checkboxColor, 'premmerce-widget-color-picker', 'text', $currentPlan); ?>
			</div>
		</div>
	</div>

	<div class="premmerce-widget premmerce-widget-filter-appearance">
		<!-- Сheckbox appearance -->
		<?php FilterWidget::renderWidgetSelect($widget, 'checkbox_appearance', 'Сheckbox appearance', $checkboxAppearance, $checkboxAppVariables, '', $currentPlan); ?>
		<!-- Filters title appearance -->
		<?php FilterWidget::renderWidgetSelect($widget, 'title_appearance', 'Filters title appearance', $titleAppearance, $titleAppVariables, '', $currentPlan); ?>
	</div>
</div>

<?php endif; //endif only for filter widget. ?>
