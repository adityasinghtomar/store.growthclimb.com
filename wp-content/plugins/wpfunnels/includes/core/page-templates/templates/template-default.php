<?php
/**
 * Template Name: WPFunnel - Default
 *
 * @package WPFunnels
 */

use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$is_divi_active = Wpfnl_functions::wpfnl_is_theme_active( 'Divi' );
if(!$is_divi_active){
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js wpfnl-template-default">
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php
			wp_head();
		?>
	</head>
	<?php }else get_header(); ?>

	<body <?php body_class(); ?>>

		<?php
			if ( function_exists( 'wp_body_open' ) ) {
				wp_body_open();
			}
			do_action( 'wpfunnels/template_body_top' );
			$atts_string = Wpfnl_functions::get_template_container_atts();
		?>
		<div class="wpfnl-template-wrap wpfnl-template-container" <?php echo trim( $atts_string ); ?>>
			<?php do_action( 'wpfunnels/template_container_top' ); ?>
			<div class="wpfnl-primary" id="wpfnl-primary">
				<?php  Wpfnl::$instance->page_templates->print_content(); ?>
			</div>
			<?php do_action( 'wpfunnels/template_container_bottom' ); ?>
		</div>
		<?php do_action( 'wpfunnels/template_wp_footer' ); ?>
		<?php
		$is_divi_active ? get_footer() : wp_footer()
		?>
	</body>
	<?php
	$is_divi_active ? '' : '</html>'
	?>


<?php
