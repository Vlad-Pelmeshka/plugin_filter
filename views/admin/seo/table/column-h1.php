<?php
use Premmerce\Filter\Seo\SeoListener;

if (! defined('ABSPATH')) {
	exit;
}

/** Item info @var array $item */
$getTerm  = get_term($item['term_id']);
$editLink = $url . '&action=edit&id=' . $item['id'];

if (empty($url)) {
	$editLink = get_edit_term_link($item['term_id']);
}

$itemPath = apply_filters('wpml_permalink', SeoListener::addSlashToLink(home_url($item['path'])));

?>
<strong><a href="<?php echo esc_url($editLink); ?>"><?php echo esc_attr($getTerm->name); ?></a></strong>

<div class="row-actions">
	<span class="edit">
		<a href="<?php echo esc_url($editLink); ?>">
			<?php esc_attr_e('Edit', 'premmerce-filter'); ?>
		</a> |
	</span>
	<span class="delete">
		<a data-id="<?php echo esc_attr($item['id']); ?>" data-link="delete"
			href="<?php echo esc_url($url) . '&action=delete&ids[]=' . esc_attr($item['id']); ?>">
			<?php esc_attr_e('Delete', 'premmerce-filter'); ?>
		</a> |
	</span>
	<span class="view">
		<a href="<?php echo esc_url($itemPath); ?>" target="_blank">
			<?php esc_attr_e('View', 'premmerce-filter'); ?>
		</a>
	</span>
</div>
