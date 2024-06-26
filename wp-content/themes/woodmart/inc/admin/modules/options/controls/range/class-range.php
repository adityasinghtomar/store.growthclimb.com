<?php
/**
 * Range slider.
 *
 * @package xts
 */

namespace XTS\Admin\Modules\Options\Controls;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Direct access not allowed.
}

use XTS\Admin\Modules\Options\Field;

/**
 * Range slider control.
 */
class Range extends Field {


	/**
	 * Displays the field control HTML.
	 *
	 * @since 1.0.0
	 *
	 * @return void.
	 */
	public function render_control() {
		?>
			<div class="xts-range-slider-wrap">
				<div class="xts-range-slider"></div>
				<input type="hidden" class="xts-range-value" data-start="<?php echo esc_attr( $this->get_field_value() ); ?>" data-min="<?php echo esc_attr( $this->args['min'] ); ?>" data-max="<?php echo esc_attr( $this->args['max'] ); ?>" data-step="<?php echo esc_attr( $this->args['step'] ); ?>" name="<?php echo esc_attr( $this->get_input_name() ); ?>" value="<?php echo esc_attr( $this->get_field_value() ); ?>">
				<span class="xts-range-field-value-display"><span class="xts-range-field-value-text"></span></span>
				<?php if ( ! empty( $this->args['unit'] ) ) : ?>
					<span class="xts-slider-units"><span class="wd-slider-unit-control xts-active"><?php echo esc_html( $this->args['unit'] ); ?></span></span>
				<?php endif; ?>
			</div>
		<?php
	}

	/**
	 * Enqueue slider jquery ui.
	 *
	 * @since 1.0.0
	 */
	public function enqueue() {
		wp_enqueue_script( 'jquery-ui-slider' );
		wp_enqueue_style( 'xts-jquery-ui', WOODMART_ASSETS . '/css/jquery-ui.css', array(), woodmart_get_theme_info( 'Version' ) );
	}
}


