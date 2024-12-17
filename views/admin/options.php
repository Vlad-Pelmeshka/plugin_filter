<?php

use Premmerce\Filter\FilterPlugin;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Get tabs @var \Premmerce\Filter\Admin\Tabs\Base\TabInterface[] $tabs
 */
?>
<div class="wrap">
	<h1>
		<?php
		esc_attr_e( 'Premmerce Product Filter for WooCommerce', 'premmerce-filter' );
		?>
	</h1>
	<h2 class="nav-tab-wrapper">
		<?php foreach ($tabs as $tabInfo) : ?>
			<?php if ($tabInfo->valid()) : ?>
				<?php $class = ( $tabInfo == $current ) ? ' nav-tab-active' : ''; ?>
				<a class='nav-tab<?php echo esc_attr($class); ?>'
					href='?page=premmerce-filter-admin&tab=<?php echo esc_attr($tabInfo->getName()); ?>'>
					<?php echo wp_kses($tabInfo->getLabel(), FilterPlugin::HTML_TAGS); ?>
			</a>
			<?php endif; ?>
		<?php endforeach; ?>

		<?php
		if (!premmerce_pwpf_fs()->can_use_premium_code()) : //if it is not Premium plan. ?>
			<a class="nav-tab premmerce-upgrate-to-premium-button"
				href="<?php echo esc_url(admin_url('admin.php?page=premmerce-filter-admin-pricing')); ?>">
					<?php esc_attr_e('Upgrate to Premium', 'premmerce-filter'); ?>
			</a>
		<?php
		endif;
		?>
	</h2>

	<?php echo wp_kses_data($current->render()); ?>

</div>
