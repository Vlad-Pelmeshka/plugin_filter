<?php
if ( ! defined('ABSPATH')) {
	exit;
}
?>
<h2><?php esc_attr_e('Cache', 'premmerce-filter'); ?></h2>
<table>
	<tbody>
	<tr>
		<td>
			<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
				<button class="button"><?php esc_attr_e('Clear cache', 'premmerce-filter'); ?></button>
				<input type="hidden" name="action" value="premmerce_filter_cache_clear">
				<?php wp_nonce_field('premmerce-cache-clear'); ?>
			</form>
		</td>
	</tr>
	</tbody>
</table>
