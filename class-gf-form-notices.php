<?php
/**
 * GF Form Notices Add-On Class
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

GFForms::include_feed_addon_framework();

class GF_Form_Notices extends GFFeedAddOn {

	protected $_version = GF_FORM_NOTICES_VERSION;
	protected $_min_gravityforms_version = '2.5';
	protected $_slug = 'gf-form-notices';
	protected $_path = 'gf-form-notices/gf-form-notices.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Form Notices';
	protected $_short_title = 'Form Notices';
	protected $_capabilities_settings_page = 'gf_form_notices_settings';
	protected $_capabilities_form_settings = 'gf_form_notices_form_settings';
	protected $_capabilities_uninstall = 'gf_form_notices_uninstall';
	protected $_capabilities = array(
		'gf_form_notices_settings',
		'gf_form_notices_form_settings',
		'gf_form_notices_uninstall',
	);
	protected $_supports_feed_ordering = true;

	/**
	 * Returns the menu icon for the plugin.
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return 'dashicons-admin-post';
	}

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GF_Form_Notices
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Initialize the add-on.
	 */
	public function init() {
		parent::init();
		add_filter( 'gform_get_form_filter', array( $this, 'handle_notice_messages' ), 10, 2 );
		add_filter( 'gform_custom_merge_tags', array( $this, 'add_custom_merge_tags' ), 10, 4 );
		add_action( 'wp_ajax_gf_form_notices_preview_date', array( $this, 'ajax_preview_date' ) );
		add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ), 10, 2 );
		
		// Register shortcode
		add_shortcode( 'gffn_notices', array( $this, 'shortcode_handler' ) );
		
		// Export/Import support
		add_filter( 'gform_export_form', array( $this, 'export_feeds' ) );
		add_action( 'gform_forms_post_import', array( $this, 'import_feeds' ) );
	}

	/**
	 * Define plugin settings fields.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'  => __( 'Display Settings', 'gf-form-notices' ),
				'fields' => array(
					array(
						'name'        => 'container_markup',
						'label'       => __( 'Container Markup', 'gf-form-notices' ),
						'type'        => 'textarea',
						'class'       => 'large code',
						'allow_html'  => true,
						'placeholder' => '<div class="gffn-notice">{content}</div>',
						'tooltip'     => '<h6>' . __( 'Container Markup', 'gf-form-notices' ) . '</h6>' . __( 'Specify custom HTML markup to wrap each notice message. Use {content} as a placeholder for the message content. If left empty, the default markup will be used: <div class="gffn-notice">{content}</div>', 'gf-form-notices' ),
						'description' => __( 'Use {content} as a placeholder for the message content.', 'gf-form-notices' ),
					),
				),
			),
			array(
				'title'  => __( 'Shortcode', 'gf-form-notices' ),
				'fields' => array(
					array(
						'name' => 'shortcode_usage',
						'type' => 'gffn_shortcode_usage',
					),
				),
			),
		);
	}

	/**
	 * Render shortcode usage field.
	 *
	 * @param array $field The field properties.
	 * @param bool  $echo  Whether to echo the field HTML.
	 *
	 * @return string
	 */
	public function settings_gffn_shortcode_usage( $field, $echo = true ) {
		ob_start();
		?>
		<p><?php esc_html_e( 'Use this shortcode to display applicable notices (based on their date range) anywhere on your site:', 'gf-form-notices' ); ?></p>
		<code style="display: block; padding: 10px; background: #f0f0f1; margin-bottom: 10px;">[gffn_notices form_id="123"]</code>
		<p><?php printf( esc_html__( 'To display a specific notice (if applicable), add the %s attribute:', 'gf-form-notices' ), '<code>notice_id</code>' ); ?></p>
		<code style="display: block; padding: 10px; background: #f0f0f1;">[gffn_notices form_id="123" notice_id="456"]</code>
		<p class="description" style="margin-top: 10px;"><?php esc_html_e( 'Replace 123 with your form ID and 456 with the notice (feed) ID.', 'gf-form-notices' ); ?></p>
		<?php
		$html = ob_get_clean();

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Define the feed settings fields.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		return array(
			array(
				'fields' => array(
					array(
						'name'     => 'feed_name',
						'label'    => __( 'Name', 'gf-form-notices' ),
						'type'     => 'text',
						'class'    => 'medium',
						'required' => true,
						'tooltip'  => '<h6>' . __( 'Name', 'gf-form-notices' ) . '</h6>' . __( 'Enter a name to identify this notice.', 'gf-form-notices' ),
					),
				),
			),
			array(
				'title'  => __( 'Display Dates', 'gf-form-notices' ),
				'fields' => array(
					array(
						'name'        => 'start_date',
						'label'       => __( 'Start Date', 'gf-form-notices' ),
						'type'        => 'gffn_date',
						'class'       => 'datepicker',
						'required'    => true,
						'placeholder' => __( 'YYYY-MM-DD', 'gf-form-notices' ),
						'tooltip'     => '<h6>' . __( 'Start Date', 'gf-form-notices' ) . '</h6>' . __( 'Enter a date in YYYY-MM-DD format. Use * as wildcards for recurring dates (e.g., *-12-25 for Christmas). Or enter a PHP date format string (e.g., "Last Thursday of November").', 'gf-form-notices' ),
					),
					array(
						'name'        => 'end_date',
						'label'       => __( 'End Date', 'gf-form-notices' ),
						'type'        => 'gffn_date',
						'class'       => 'datepicker',
						'required'    => true,
						'placeholder' => __( 'YYYY-MM-DD', 'gf-form-notices' ),
						'tooltip'     => '<h6>' . __( 'End Date', 'gf-form-notices' ) . '</h6>' . __( 'Enter a date in YYYY-MM-DD format. Use * as wildcards for recurring dates (e.g., *-12-25 for Christmas). Or enter a PHP date format string (e.g., "Last Thursday of November").', 'gf-form-notices' ),
					),
					array(
						'name'          => 'advance_days',
						'label'         => __( 'Advance Notice', 'gf-form-notices' ),
						'type'          => 'text',
						'input_type'    => 'number',
						'class'         => 'small',
						'default_value' => '0',
						'tooltip'       => '<h6>' . __( 'Advance Notice', 'gf-form-notices' ) . '</h6>' . __( 'Number of days before the start date to begin displaying the notice. Defaults to 0 (display only on and between the start and end dates). Set to 1 or more to display the notice that many days early.', 'gf-form-notices' ),
					),
				),
			),
			array(
				'title'  => __( 'Notice Message', 'gf-form-notices' ),
				'fields' => array(
					array(
						'name'       => 'message',
						'label'      => __( 'Message', 'gf-form-notices' ),
						'type'       => 'textarea',
						'class'      => 'large merge-tag-support mt-position-right mt-prepopulate',
						'use_editor' => true,
						'tooltip'    => '<h6>' . __( 'Message', 'gf-form-notices' ) . '</h6>' . __( 'Enter the message to display during this date range. You can use {start_date:FORMAT} and {end_date:FORMAT} merge tags where FORMAT is a PHP date format (e.g., {start_date:l, F jS}).', 'gf-form-notices' ),
					),
					array(
						'name'    => 'disable_default_styles',
						'label'   => __( 'Disable default styles', 'gf-form-notices' ),
						'type'    => 'toggle',
						'tooltip' => '<h6>' . __( 'Disable default styles', 'gf-form-notices' ) . '</h6>' . __( 'When enabled, the built-in Form Notices styles will not be applied to this notice.', 'gf-form-notices' ),
					),
				),
			),
		);
	}

	/**
	 * Get active notice messages for a given set of feeds.
	 *
	 * @param array $feeds The feeds array.
	 *
	 * @return array Array of message data.
	 */
	private function get_active_notice_messages( $feeds ) {
		if ( empty( $feeds ) ) {
			return array();
		}

		$timestamp = current_time( 'timestamp' );
		$messages  = array();

		foreach ( $feeds as $feed ) {
			$start_date   = rgar( $feed['meta'], 'start_date' );
			$end_date     = rgar( $feed['meta'], 'end_date' );
			$message      = rgar( $feed['meta'], 'message' );
			$advance_days = absint( rgar( $feed['meta'], 'advance_days', 0 ) );

			if ( empty( $start_date ) || empty( $end_date ) || empty( $message ) ) {
				continue;
			}

			$start_timestamp = $this->get_date_timestamp( $start_date );
			$end_timestamp   = $this->get_date_timestamp( $end_date, false );
			
			// Subtract advance days from start timestamp
			$display_start_timestamp = strtotime( '-' . $advance_days . ' days', $start_timestamp );

			if ( $timestamp >= $display_start_timestamp && $timestamp <= $end_timestamp ) {
				$messages[] = array(
					'content'                => $this->replace_date_merge_tags( $message, array(
						'start_date' => $start_date,
						'end_date'   => $end_date,
					) ),
					'disable_default_styles' => (bool) rgar( $feed['meta'], 'disable_default_styles' ),
					'feed_id'                => rgar( $feed, 'id' ),
					'feed_name'              => rgar( $feed['meta'], 'feed_name' ),
				);
			}
		}

		return $messages;
	}

	/**
	 * Handle displaying notice messages on forms.
	 *
	 * @param string $html The form HTML.
	 * @param array  $form The form object.
	 *
	 * @return string
	 */
	public function handle_notice_messages( $html, $form ) {
		if ( ! $this->is_form_editor() && ! is_admin() ) {
			$feeds = $this->get_active_feeds( $form['id'] );
			$messages = $this->get_active_notice_messages( $feeds );

			if ( ! empty( $messages ) ) {
				$html = $this->format_messages( $messages, $form ) . $html;
			}
		}

		return $html;
	}

	/**
	 * Shortcode handler for [form_notices] shortcode.
	 * 
	 * @param array $atts Shortcode attributes.
	 * 
	 * @return string The notices HTML or empty string if no notices.
	 */
	public function shortcode_handler( $atts ) {
		$atts = shortcode_atts(
			array(
				'form_id'   => '',
				'notice_id' => '',
			),
			$atts,
			'form_notices'
		);

		// Validate form ID
		$form_id = absint( $atts['form_id'] );
		if ( empty( $form_id ) ) {
			return '';
		}

		// Validate notice ID (optional)
		$notice_id = $atts['notice_id'] ? absint( $atts['notice_id'] ) : '';

		$form = GFAPI::get_form( $form_id );
		if ( ! $form ) {
			return '';
		}

		// Get active feeds for this form
		$feeds = $this->get_active_feeds( $form_id );
		if ( empty( $feeds ) ) {
			return '';
		}

		// Filter by specific notice_id if provided
		if ( $notice_id ) {
			$feeds = array_filter( $feeds, function( $feed ) use ( $notice_id ) {
				return absint( rgar( $feed, 'id' ) ) === $notice_id;
			});

			if ( empty( $feeds ) ) {
				return '';
			}
		}

		$messages = $this->get_active_notice_messages( $feeds );

		if ( ! empty( $messages ) ) {
			// Enqueue frontend styles when shortcode is used
			wp_enqueue_style(
				'gf-form-notices-frontend',
				$this->get_base_url() . '/css/frontend.css',
				array(),
				$this->_version
			);
			return $this->format_messages( $messages, $form );
		}

		return '';
	}

	/**
	 * Convert a date string to a timestamp.
	 *
	 * @param string $date     The date string (YYYY-MM-DD or *-MM-DD).
	 * @param bool   $midnight Whether to return midnight (true) or end of day (false).
	 *
	 * @return int
	 */
	public function get_date_timestamp( $date, $midnight = true ) {
		// Check if this is a dash-separated date format (YYYY-MM-DD or *-MM-DD)
		if ( preg_match( '/^[\d*]+-[\d*]+-[\d*]+$/', $date ) ) {
			$date_parts = explode( '-', $date );
			$date_parts[0] = str_replace( '*', date( 'Y' ), $date_parts[0] );
			$date_parts[1] = str_replace( '*', date( 'm' ), $date_parts[1] );
			$date_parts[2] = str_replace( '*', date( 'd' ), $date_parts[2] );
			$timestamp = strtotime( implode( '-', $date_parts ) );
		} else {
			// Try to parse as natural language date
			$timestamp = strtotime( $date );
		}

		if ( ! $midnight && $timestamp ) {
			$timestamp = strtotime( '11:59pm', $timestamp );
		}

		return $timestamp;
	}

	/**
	 * Format messages for display.
	 *
	 * @param array $messages Array of message data (each with 'content' and 'disable_default_styles').
	 * @param array $form     The form object.
	 *
	 * @return string
	 */
	public function format_messages( $messages, $form ) {
		// Get custom container markup from settings
		$container_markup = $this->get_plugin_setting( 'container_markup' );
		
		// Use default markup if not specified
		if ( empty( $container_markup ) ) {
			$container_markup = '<div class="gffn-notice">{content}</div>';
		}
		
		// Apply wpautop to each message and wrap in custom container
		$formatted_messages = array_map( function( $message ) use ( $container_markup ) {
			$content = wpautop( $message['content'] );
			$markup = str_replace( '{content}', $content, $container_markup );
			
			// Build additional classes
			$additional_classes = array();
			
			// Add default theme class unless default styles are disabled
			if ( empty( $message['disable_default_styles'] ) ) {
				$additional_classes[] = 'gffn-default-theme';
			}
			
			// Add feed ID class
			if ( ! empty( $message['feed_id'] ) ) {
				$additional_classes[] = 'gffn-notice-' . absint( $message['feed_id'] );
			}
			
			// Add slugged feed name class
			if ( ! empty( $message['feed_name'] ) ) {
				$slug = sanitize_title( $message['feed_name'] );
				$additional_classes[] = 'gffn-notice-' . $slug;
			}
			
			// Apply additional classes
			if ( ! empty( $additional_classes ) ) {
				$classes = 'gffn-notice ' . implode( ' ', $additional_classes );
				$markup = str_replace( 'class="gffn-notice"', 'class="' . esc_attr( $classes ) . '"', $markup );
			}
			
			return $markup;
		}, $messages );
		
		// Add theme classes to inherit GF custom properties
		$theme_classes = '';
		$form_theme = rgar( $form, 'theme' );
		if ( $form_theme === 'orbital' ) {
			$theme_classes = ' gform-theme gform-theme--framework';
		}
		
		return '<div class="gffn-notices' . $theme_classes . '">' . implode( '', $formatted_messages ) . '</div>';
	}

	/**
	 * Render custom date field.
	 *
	 * @param array $field     The field properties.
	 * @param bool  $echo      Whether to echo the field HTML.
	 *
	 * @return string
	 */
	public function settings_gffn_date( $field, $echo = true ) {
		$field['type'] = 'text';
		$html = $this->settings_text( $field, false );
		
		if ( $echo ) {
			echo $html;
		}
		
		return $html;
	}

	/**
	 * Enqueue frontend styles when a form is displayed.
	 *
	 * @param array $form The form object.
	 * @param bool  $is_ajax Whether the form is being loaded via AJAX.
	 */
	public function enqueue_frontend_styles( $form, $is_ajax ) {
		// Check if this form has any active feeds
		$feeds = $this->get_active_feeds( $form['id'] );
		
		if ( ! empty( $feeds ) ) {
			wp_enqueue_style(
				'gf-form-notices-frontend',
				$this->get_base_url() . '/css/frontend.css',
				array(),
				$this->_version
			);
		}
	}

	/**
	 * Define scripts to be enqueued.
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = array(
			array(
				'handle'   => 'gf-form-notices-feed',
				'src'      => $this->get_base_url() . '/js/feed-settings.js',
				'version'  => $this->_version,
				'deps'     => array( 'jquery', 'jquery-ui-datepicker' ),
				'enqueue'  => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => $this->_slug,
					),
				),
				'callback' => array( $this, 'localize_scripts' ),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Localize scripts.
	 */
	public function localize_scripts() {
		wp_localize_script(
			'gf-form-notices-feed',
			'gfFormNotices',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'gf_form_notices_preview' ),
			)
		);
	}

	/**
	 * Define styles to be enqueued.
	 *
	 * @return array
	 */
	public function styles() {
		$styles = array(
			array(
				'handle'  => 'gforms_datepicker_css',
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => $this->_slug,
					),
				),
			),
			array(
				'handle'  => 'gf-form-notices-feed',
				'src'     => $this->get_base_url() . '/css/feed-settings.css',
				'version' => $this->_version,
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => $this->_slug,
					),
				),
			),
		);

		return array_merge( parent::styles(), $styles );
	}

	/**
	 * AJAX handler for date preview.
	 */
	public function ajax_preview_date() {
		check_ajax_referer( 'gf_form_notices_preview', 'nonce' );

		$date = sanitize_text_field( $_POST['date'] ?? '' );

		if ( empty( $date ) ) {
			wp_send_json_error( array( 'message' => __( 'No date provided', 'gf-form-notices' ) ) );
		}

		$timestamp = $this->get_date_timestamp( $date );

		if ( ! $timestamp ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date format', 'gf-form-notices' ) ) );
		}

		wp_send_json_success( array(
			'preview' => date( 'l, F j, Y', $timestamp ),
		) );
	}

	/**
	 * Add custom merge tags to merge tag dropdown.
	 *
	 * @param array $merge_tags Existing merge tags.
	 * @param int   $form_id    Form ID.
	 * @param array $fields     Form fields.
	 * @param string $element_id Element ID.
	 *
	 * @return array
	 */
	public function add_custom_merge_tags( $merge_tags, $form_id, $fields, $element_id ) {
		// Only show on our add-on's pages
		if ( rgget( 'page' ) !== 'gf_edit_forms' || rgget( 'subview' ) !== $this->_slug ) {
			return $merge_tags;
		}

		$merge_tags[] = array(
			'label' => __( 'Start Date', 'gf-form-notices' ),
			'tag'   => '{start_date:l, F jS}',
		);

		$merge_tags[] = array(
			'label' => __( 'End Date', 'gf-form-notices' ),
			'tag'   => '{end_date:l, F jS}',
		);

		$merge_tags[] = array(
			'label' => __( 'Next Weekday', 'gf-form-notices' ),
			'tag'   => '{next_weekday:l, F jS}',
		);

		return $merge_tags;
	}

	/**
	 * Get the next weekday after a given date.
	 *
	 * @param string $date The date string.
	 *
	 * @return int The timestamp of the next weekday.
	 */
	public function get_next_weekday( $date ) {
		$timestamp = $this->get_date_timestamp( $date );
		
		// Start with the day after the end date
		$next_day = strtotime( '+1 day', $timestamp );
		
		// Keep adding days until we find a weekday (1 = Monday, 5 = Friday)
		while ( date( 'N', $next_day ) > 5 ) {
			$next_day = strtotime( '+1 day', $next_day );
		}
		
		return $next_day;
	}

	/**
	 * Replace date merge tags in message.
	 *
	 * @param string $message  The message with merge tags.
	 * @param array  $schedule The schedule data.
	 *
	 * @return string
	 */
	public function replace_date_merge_tags( $message, $schedule ) {
		// Parse merge tags for start_date and end_date
		preg_match_all('/\{(start_date|end_date):([^\}]+)\}/i', $message, $matches, PREG_SET_ORDER);

		foreach ( $matches as $match ) {
			$tag    = $match[1];
			$format = $match[2];
			$date   = rgar( $schedule, $tag );

			if ( ! $date ) {
				continue;
			}

			$message = str_replace( $match[0], date( $format, $this->get_date_timestamp( $date ) ), $message );
		}
		
		// Parse merge tags for next_weekday
		preg_match_all('/\{next_weekday:([^\}]+)\}/i', $message, $matches, PREG_SET_ORDER);
		
		foreach ( $matches as $match ) {
			$format   = $match[1];
			$end_date = rgar( $schedule, 'end_date' );
			
			if ( ! $end_date ) {
				continue;
			}
			
			$next_weekday = $this->get_next_weekday( $end_date );
			$message = str_replace( $match[0], date( $format, $next_weekday ), $message );
		}

		return $message;
	}

	/**
	 * Allow feeds to be duplicated.
	 *
	 * @param int $id Feed ID.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {
		return true;
	}

	/**
	 * Define columns for feed list.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'feed_name' => __( 'Name', 'gf-form-notices' ),
			'dates'     => __( 'Dates', 'gf-form-notices' ),
		);
	}

	/**
	 * Get the value for the dates column.
	 *
	 * @param array $feed The feed object.
	 *
	 * @return string
	 */
	public function get_column_value_dates( $feed ) {
		$start_date = rgar( $feed['meta'], 'start_date' );
		$end_date   = rgar( $feed['meta'], 'end_date' );

		if ( empty( $start_date ) || empty( $end_date ) ) {
			return '—';
		}

		// Format dates for display
		$start_timestamp = $this->get_date_timestamp( $start_date );
		$end_timestamp   = $this->get_date_timestamp( $end_date );

		if ( ! $start_timestamp || ! $end_timestamp ) {
			return esc_html( $start_date . ' – ' . $end_date );
		}

		$start_formatted = date( 'M j, Y', $start_timestamp );
		$end_formatted   = date( 'M j, Y', $end_timestamp );

		return esc_html( $start_formatted . ' – ' . $end_formatted );
	}

	/**
	 * Get the value for the feed name column.
	 *
	 * @param array $feed The feed object.
	 *
	 * @return string
	 */
	public function get_column_value_feed_name( $feed ) {
		return '<strong>' . esc_html( rgar( $feed['meta'], 'feed_name' ) ) . '</strong>';
	}

	/**
	 * Export feeds with the form.
	 *
	 * @param array $form The form being exported.
	 *
	 * @return array
	 */
	public function export_feeds( $form ) {
		$feeds = $this->get_feeds( $form['id'] );
		
		if ( ! empty( $feeds ) ) {
			$form['gf_form_notices_feeds'] = $feeds;
		}
		
		return $form;
	}

	/**
	 * Import feeds after forms are imported.
	 *
	 * @param array $forms Array of imported forms.
	 */
	public function import_feeds( $forms ) {
		foreach ( $forms as $form ) {
			if ( empty( $form['gf_form_notices_feeds'] ) ) {
				continue;
			}
			
			foreach ( $form['gf_form_notices_feeds'] as $feed ) {
				// Update the form_id to the new form's ID
				$feed['form_id'] = $form['id'];
				
				// Remove the old feed ID to create a new one
				unset( $feed['id'] );
				
				// Insert the feed
				$this->insert_feed( $form['id'], $feed['is_active'], $feed['meta'] );
			}
		}
	}

}
