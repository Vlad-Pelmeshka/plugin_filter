<?php

if ( ! defined('ABSPATH')) {
	exit;
}

/** Get complete @var string $complete */
/** Get action @var string $action */
/** Get max @var int $max */
?>

<div class="wrap">
	<div class="progress-bar">
		<div class="progress-bar__widget"></div>
		<div class="progress-bar__text">
			<span class="progress-bar__text-current" data-progressbar-current>0</span>/<span
					class="progress-bar__text-max" data-progressbar-max><?php echo esc_attr($max); ?></span>
		</div>
		<input type="hidden"
			data-progress-complete-url
			value="<?php echo esc_attr($complete); ?>"
		>
		<input type="hidden"
			data-progress-action
			value="<?php echo esc_attr($action); ?>"
		>
	</div>
</div>
