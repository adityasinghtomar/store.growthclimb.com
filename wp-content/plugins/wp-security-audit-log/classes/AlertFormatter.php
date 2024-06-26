<?php
/**
 * Manager: Alert Formatter Class
 *
 * Class file for alert formatter.
 *
 * @since   4.2.1
 * @package wsal
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WSAL\Helpers\WP_Helper;
use WSAL\MainWP\MainWP_Helper;
use WSAL\Entities\Metadata_Entity;
use WSAL\Entities\Occurrences_Entity;

/**
 * WSAL_AlertFormatter class.
 *
 * Class for handling the formatting of alert message/UI widget in different contexts.
 *
 * Formatting rules are given by given formatter configuration.
 *
 * @package wsal
 * @since 4.2.1
 */
final class WSAL_AlertFormatter {

	/**
	 * Plugin instance.
	 *
	 * @var WpSecurityAuditLog
	 */
	protected $plugin;

	/**
	 * Alert formatter configuration.
	 *
	 * @var WSAL_AlertFormatterConfiguration
	 */
	private $configuration;

	/**
	 * Constructor.
	 *
	 * @param WpSecurityAuditLog               $plugin        Plugin instance.
	 * @param WSAL_AlertFormatterConfiguration $configuration Alert formatter configuration.
	 */
	public function __construct( $plugin, $configuration ) {
		$this->plugin        = $plugin;
		$this->configuration = $configuration;

		add_filter( 'wsal_truncate_alert_value', array( __CLASS__, 'data_truncate' ), 10, 4 );
	}

	/**
	 * Gets the end of line character sequence.
	 *
	 * @return string End of line character sequence.
	 */
	public function get_end_of_line() {
		return $this->configuration->get_end_of_line();
	}

	/**
	 * Updates the end of line character sequence.
	 *
	 * @param string $value End of line character sequence.
	 *
	 * @return WSAL_AlertFormatterConfiguration
	 */
	public function set_end_of_line( $value ) {
		return $this->configuration->set_end_of_line( $value );
	}

	/**
	 * Formats given meta expression.
	 *
	 * @param string   $expression    Meta expression including the surrounding percentage chars.
	 * @param string   $value         Meta value.
	 * @param int|null $occurrence_id Occurrence ID. Only present if the event was already written to the database.
	 * @param array    $metadata      Meta data.
	 * @param bool     $wrap          Should metadata be wrapped in highlighted markup.
	 *
	 * @return false|mixed|string|void|WP_Error
	 *
	 * @since 4.2.1
	 */
	public function format_meta_expression( $expression, $value, $occurrence_id = null, $metadata = array(), $wrap = true ) {

		$value = apply_filters( 'wsal_truncate_alert_value', $value, $expression, $this->configuration->get_max_meta_value_length(), $this->configuration->get_ellipses_sequence() );

		switch ( true ) {
			case '%Message%' === $expression:
				return esc_html( $value );

			case '%MetaLink%' === $expression:
				// NULL value check is here because events related to user meta fields didn't have the MetaLink meta prior to version 4.3.2.
				if ( $this->configuration->is_js_in_links_allowed() && 'NULL' !== $value ) {
					$label  = __( 'Exclude custom field from the monitoring', 'wp-security-audit-log' );
					$result = "<a href=\"#\" data-object-type='{$metadata['Object']}' data-disable-custom-nonce='" . wp_create_nonce( 'disable-custom-nonce' . $value ) . "' onclick=\"return WsalDisableCustom(this, '" . $value . "');\"> {$label}</a>";

					return $this->wrap_in_hightlight_markup( $result, true );
				}

				return '';

			case in_array( $expression, array( '%path%', '%old_path%', '%FilePath%' ), true ):
				// Concatenate directory and file paths.
				if ( $this->configuration->is_js_in_links_allowed() ) {
					$result = '<strong><span>' . $value . '</span>'; // phpcs:ignore
					$result .= "<a href=\"#\" data-shortened-text='{$value}'>" . $this->configuration->get_ellipses_sequence() . "</a></strong>"; // phpcs:ignore

					return $result;
				}

				return $value;

			case in_array( $expression, array( '%MetaValue%', '%MetaValueOld%', '%MetaValueNew%' ), true ):
				// Trim the meta value to the maximum length and append configured ellipses sequence.
				$result = $value;

				return $this->wrap_in_hightlight_markup( $result );

			case '%ClientIP%' === $expression:
			case '%IPAddress%' === $expression:
				if ( is_string( $value ) ) {
					$sanitized_ips = str_replace(
						array(
							'"',
							'[',
							']',
						),
						'',
						$value
					);

					return $this->wrap_in_hightlight_markup( $sanitized_ips );
				} else {
					return $this->wrap_in_emphasis_markup( __( 'unknown', 'wp-security-audit-log' ) );
				}

			case '%PostUrlIfPlublished%' === $expression:
				$post_id = null;
				if ( is_array( $metadata ) && array_key_exists( 'PostID', $metadata ) ) {
					$post_id = $metadata['PostID'];
				} else {
					$post_id = $this->get_occurrence_meta_item( $occurrence_id, 'PostID' );
				}

				$occurrence_data = Occurrences_Entity::load( 'id = %d', array( $occurrence_id ) );

				if ( isset( $occurrence_data ) && isset( $occurrence_data['site_id'] ) ) {
					if ( MainWP_Helper::SET_SITE_ID_NUMBER < $occurrence_data['site_id'] ) {
						if ( isset( $metadata['PostUrl'] ) ) {
							return $metadata['PostUrl'];
						} else {
							return '';
						}
					}
				}

				$occ_post = ! is_null( $post_id ) ? get_post( $post_id ) : null;
				if ( null !== $occ_post && 'publish' === $occ_post->post_status ) {
					return get_permalink( $occ_post->ID );
				}

				return '';

			case '%MenuUrl%' === $expression:
				$menu_id = null;
				if ( 0 === $occurrence_id && is_array( $metadata ) && array_key_exists( 'MenuID', $metadata ) ) {
					$menu_id = $metadata['MenuID'];
				} else {
					$menu_id = $this->get_occurrence_meta_item( $occurrence_id, 'MenuID' );
				}
				if ( null !== $menu_id ) {
					return add_query_arg(
						array(
							'action' => 'edit',
							'menu'   => $menu_id,
						),
						admin_url( 'nav-menus.php' )
					);
				}

				return '';

			case '%Attempts%' === $expression: // Failed login attempts.
				$check_value = (int) $value;
				if ( 0 === $check_value ) {
					return '';
				} else {
					return $value;
				}

			case '%Users%' === $expression: // Failed login attempts.
				if ( isset( $metadata['Users'] ) && is_array( $metadata['Users'] ) ) {
					if ( empty( $metadata['Users'] ) ) {
						return 'Unknown username';
					}
					return $metadata['Users'][0];
				} elseif ( isset( $metadata['Users'] ) ) {
					return $metadata['Users'];
				} else {
					return 'Unknown username';
				}
				$check_value = (int) $value;
				if ( 0 === $check_value ) {
					return '';
				} else {
					return $metadata['Users'][0];
				}

			case '%LogFileText%' === $expression: // Failed login file text.
				if ( $this->configuration->is_js_in_links_allowed() ) {
					$result = '<a href="javascript:;" onclick="download_failed_login_log( this )" data-download-nonce="' . esc_attr( wp_create_nonce( 'wsal-download-failed-logins' ) ) . '" title="' . esc_html__( 'Download the log file.', 'wp-security-audit-log' ) . '">' . esc_html__( 'Download the log file.', 'wp-security-audit-log' ) . '</a>';

					return $this->wrap_in_hightlight_markup( $result, true );
				}

				return '';

			case in_array( $expression, array( '%PostStatus%', '%ProductStatus%' ), true ):
				$result = ( ! empty( $value ) && 'publish' === $value ) ? __( 'published', 'wp-security-audit-log' ) : $value;

				return $this->wrap_in_hightlight_markup( $result );

			case '%multisite_text%' === $expression:
				if ( WP_Helper::is_multisite() && $value ) {
					$site_info = get_blog_details( $value, true );
					if ( $site_info ) {
						$site_url = $site_info->siteurl;

						return ' on site ' . $this->format_link( $expression, $site_info->blogname, $site_url );
					}
				}

				return '';

			case '%ReportText%' === $expression:
			case '%ChangeText%' === $expression:
				return '';

			case '%TableNames%' === $expression:
				$value = str_replace( ',', ', ', $value );

				return $this->wrap_in_hightlight_markup( esc_html( $value ) );

			case '%LineBreak%' === $expression:
				return $this->configuration->get_end_of_line();

			case '%PluginFile%' === $expression:
				return $this->wrap_in_hightlight_markup( dirname( $value ) );

			case '%OldVersion%' === $expression:
				$return = ( $value !== 'NULL' ) ? $value : esc_html__( 'Upgrade event prior to WP Activity Log 5.0.0.', 'wp-security-audit-log' );
				return $this->wrap_in_hightlight_markup( $return );

			default:
				/**
				 * Allows meta formatting via filter if no match was found.
				 *
				 * @param string $expression Meta expression including the surrounding percentage chars.
				 * @param string $value Meta value.
				 *
				 * @deprecated 4.3.0 Use 'wsal_format_custom_meta' instead.
				 */
				$result = apply_filters_deprecated(
					'wsal_meta_formatter_custom_formatter',
					array(
						$value,
						$expression,
					),
					'WSAL 4.3.0',
					'wsal_format_custom_meta'
				);

				/**
				 * Allows meta formatting via filter if no match was found. Runs after the legacy filter 'wsal_meta_formatter_custom_formatter' that is kept for backwards compatibility.
				 *
				 * @param string $value Meta value.
				 * @param string $expression Meta expression including the surrounding percentage chars.
				 * @param WSAL_AlertFormatter $this Alert formatter class.
				 * @param int|null $occurrence_id Occurrence ID. Only present if the event was already written to the database. Default null.
				 *
				 * @since 4.3.0
				 */
				// Ensure result is wrapped as expected.
				if ( $wrap ) {
					$result = $this->wrap_in_hightlight_markup( $result );
				}

				return apply_filters( 'wsal_format_custom_meta', $result, $expression, $this, $occurrence_id );
		}
	}

	/**
	 * Truncates the data to a specific number of characters.
	 *
	 * @param mixed   $value - The value to be truncated.
	 * @param string  $expression - The expression for that value.
	 * @param integer $length - Number of characters to truncate to.
	 * @param string  $ellipses_sequence - The sequence of ellipses suffix.
	 *
	 * @return string|mixed
	 *
	 * @since 4.4.2.1
	 */
	public static function data_truncate( $value, $expression, $length = 50, $ellipses_sequence = '...' ) {

		switch ( $expression ) {
			case '%path%':
			case '%old_path%':
			case '%FilePath%':
				if ( mb_strlen( $value ) > $length ) {
					$value = mb_substr( $value, 0, $length ); // phpcs:ignore
				}
				break;
			case '%MetaValue%':
			case '%MetaValueOld%':
			case '%MetaValueNew%':
				$value = mb_strlen( $value ) > $length ? ( mb_substr( $value, 0, $length ) . $ellipses_sequence ) : $value;
				break;
			default:
				break;
		}

		return $value;
	}

	/**
	 * Wraps given value in highlight markup.
	 *
	 * For example meta values displayed as <strong>{meta value}</strong> in the WP admin UI.
	 *
	 * @param string $value Value.
	 * @param bool   $no_esc - Do we need to escape the html in the message.
	 *
	 * @return string
	 */
	public function wrap_in_hightlight_markup( $value, $no_esc = false ) {
		if ( ! $no_esc ) {
			$value = esc_html( $value );
		}

		return $this->configuration->get_highlight_start_tag() . $value . $this->configuration->get_highlight_end_tag();
	}

	/**
	 * Wraps given value in emphasis markup.
	 *
	 * For example an unknown IP address is displayed as <i>unknown</i> in the WP admin UI.
	 *
	 * @param string $value Value.
	 *
	 * @return string
	 */
	public function wrap_in_emphasis_markup( $value ) {
		return $this->configuration->get_emphasis_start_tag() . $value . $this->configuration->get_emphasis_end_tag();
	}

	/**
	 * Helper function to get meta value from an occurrence.
	 *
	 * @param int    $occurrence_id Occurrence ID.
	 * @param string $meta_key      Meta key.
	 *
	 * @return mixed|null Meta value if exists. Otherwise null
	 * @since 4.2.1
	 */
	private function get_occurrence_meta_item( $occurrence_id, $meta_key ) {
		// get values needed.
		$meta_result = Metadata_Entity::load_by_name_and_occurrence_id( $meta_key, $occurrence_id );

		return isset( $meta_result['value'] ) ? maybe_unserialize( $meta_result['value'] ) : null;
	}

	/**
	 * Handles formatting of hyperlinks in the event messages.
	 *
	 * Contains:
	 * - check for empty values
	 * - check if the link is disabled
	 * - optional URL processing
	 *
	 * @param string $url URL.
	 * @param string $label Label.
	 * @param string $title Title.
	 * @param string $target Target attribute.
	 *
	 * @return string
	 * @see process_url())
	 */
	public function format_link( $url, $label, $title = '', $target = '_blank' ) {
		// Check for empty values.
		if ( null === $url || empty( $url ) ) {
			return '';
		}

		$processed_url = $this->process_url( $url );
		$result        = $this->build_link_markup( $processed_url, $label, $title, $target );

		return $this->wrap_in_hightlight_markup( $result, true );
	}

	/**
	 * Override this method to process the raw URL value in a subclass.
	 *
	 * An example would be URL shortening or adding tracking params to all or selected URL.
	 *
	 * Default implementation returns URL as is.
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	protected function process_url( $url ) {
		return $url;
	}

	/**
	 * Override this method in subclass to format hyperlinks differently.
	 *
	 * Default implementation returns HTML A tag. Only implementation at the moment. We used to have Slack as well, but
	 * we moved to a different implementation. Introducing another link markup would require adding link format with
	 * placeholders to the formatter configuration.
	 *
	 * @param string $url    URL.
	 * @param string $label  Label.
	 * @param string $title  Title.
	 * @param string $target Target attribute.
	 *
	 * @return string
	 */
	protected function build_link_markup( $url, $label, $title = '', $target = '_blank' ) {
		$title = empty( $title ) ? $label : $title;
		if ( $this->configuration->can_use_html_markup_for_links() ) {
			return '<a href="' . esc_url( $url ) . '" title="' . $title . '" target="' . $target . '">' . $label . '</a>';
		}

		return $label . ': ' . esc_url( $url );
	}

	/**
	 * Checks if formatter supports hyperlinks.
	 *
	 * @return bool True if formatter supports hyperlinks.
	 */
	public function supports_hyperlinks() {
		return $this->configuration->is_supports_hyperlinks();
	}

	/**
	 * Checks if the formatter supports metadata.
	 *
	 * @return bool True if formatter supports metadata.
	 */
	public function supports_metadata() {
		return $this->configuration->is_supports_metadata();
	}

	/**
	 * Message for some events contains HTML tags for highlighting certain parts of the message.
	 *
	 * This function replaces the original HTML tags with the correct highlight tags.
	 *
	 * It also strips any additional HTML tags apart from hyperlink and an end of line to support legacy messages.
	 *
	 * @param string $message Message text.
	 *
	 * @return string
	 */
	public function process_html_tags_in_message( $message ) {
		$result = preg_replace(
			array( '/<strong>/', '/<\/strong>/' ),
			array( $this->configuration->get_highlight_start_tag(), $this->configuration->get_highlight_end_tag() ),
			$message
		);

		return strip_tags( $result, $this->configuration->get_tags_allowed_in_message() );
	}
}
