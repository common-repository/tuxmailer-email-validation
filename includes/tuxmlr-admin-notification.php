<?php


/**
 * This function print all admin notice and bulk prosess notification.
 *
 * @return void
 */
function tuxmlr_validation_processing_notice() {
	global $wpdb;
	$process_notice           = '';
	$tuxmlr_api_request_table = $wpdb->prefix . 'tuxmlr_api_request';
	$page                     = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null; // // phpcs:ignore

	if ( 'tuxmailer-bulk-validation' === $page || 'tuxmailer-options-page' === $page ) {

		$pending_responses = $wpdb->get_results( $wpdb->prepare( "SELECT form_name, uuid, resquest_timestamp FROM {$tuxmlr_api_request_table} WHERE response_status=%s ", 'Pending' ) ); // phpcs:ignore: unprepared SQL ok.

		foreach ( $pending_responses as $pending_resquest ) {
			$process_notice .= '<div class="notice precessNoti "><p>' .
			sprintf(
				/* translators: 1: Form name 2:UID 3: Timestamp */
				__( 'Form Name - %1$s is <strong>processing</strong> and UID is %2$s %3$s. Please refresh the page after some time. ', 'tuxmailer-email-validation' ),
				esc_attr( $pending_resquest->form_name ),
				'<i>' . esc_attr( $pending_resquest->uuid ) . '</i>',
				'&nbsp;( ' . esc_attr( esc_attr( $pending_resquest->resquest_timestamp ) ) . ' )'
			) . '</p>
        </div>';
		}
		echo wp_kses_post( $process_notice );
	}
}


/**
 * This function shows completed validation on admin notice section.
 *
 * @return void
 */
function tuxmlr_validation_completed_admin_notice() {
	global $wpdb;
	$process_notice           = '';
	$tuxmlr_api_request_table = $wpdb->prefix . 'tuxmlr_api_request';

		$complete_responses = $wpdb->get_results( $wpdb->prepare( "SELECT form_name, uuid, resquest_timestamp , account_detail  FROM {$tuxmlr_api_request_table} WHERE response_status=%s ", 'completed' ) );// phpcs:ignore: unprepared SQL ok.
	if ( ! empty( $complete_responses ) ) {
		foreach ( $complete_responses as $complete_response ) {

			$url            = TUXMAILER_DOWNLOAD . '/dashboard?uid=' . $complete_response->uuid;
			$account_detail = json_decode( $complete_response->account_detail );
			if ( 'team_account' === strval( $account_detail->account_type ) ) {
				$url = TUXMAILER_DOWNLOAD . "/dashboard?uid={$complete_response->uuid}&team_id={$account_detail->team_id}";
				// $url = TUXMAILER_DOWNLOAD . "/dashboard?uid={$complete_response->uuid}&team_id={$account_detail->team_id}&team_name={$account_detail->team_name}";
			}

			$process_notice .= '<div class="notice validateNoti is-dismissible" id="' . esc_attr( $complete_response->uuid ) . '"><p>' .
			sprintf(
				/* translators: 1: Form name 2: Timestamp */
				__( 'Form Name - %1$s validation completed %2$s. You can download your list from <a href="%3$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
				esc_attr( $complete_response->form_name ),
				'( ' . esc_attr( $complete_response->resquest_timestamp ) . ' )',
				esc_url( $url )
			) . '</p></div>';
		}
		echo wp_kses_post( $process_notice );
	}
}
add_action( 'admin_notices', 'tuxmlr_validation_completed_admin_notice' );



/**
 * To remove dismiss_completed_notice when clicked on dismiss or `x` button. i.e to delete UUID for completed bulk validation after displaying notice in admin page.
 */
function tuxmlr_dismiss_completed_notice() {
	if ( isset( $_POST['nonce'] ) && isset( $_POST['uuid'] ) && ! empty( $_POST['uuid'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tuxmailer-emails-validation' ) ) {
			wp_die( esc_html__( 'Permission Denied.', 'tuxmailer-email-validation' ) );
		}

		$uuid = sanitize_text_field( wp_unslash( $_POST['uuid'] ) );

		tuxmlr_delete_tux_uuid( $uuid );
		echo 'UID : ' . esc_attr( $uuid ) . ' deleted';
	}
	wp_die();
}
add_action( 'wp_ajax_dismiss_completed_notice', 'tuxmlr_dismiss_completed_notice' );
add_action( 'wp_ajax_nopriv_dismiss_completed_notice', 'tuxmlr_dismiss_completed_notice' );

/**
 * To remove admin notification once clicked on dismiss 'x';
 *
 * @return void
 */
function tuxmlr_remove_admin_notifications() {
	if ( isset( $_POST['nonce'] ) && isset( $_POST['dismissNoti'] ) && ! empty( $_POST['dismissNoti'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tuxmailer-emails-validation' ) ) {
			wp_die( esc_html__( 'Permission Denied.', 'tuxmailer-email-validation' ) );
		}
		$all_notification     = array( 'tuxmailer_cf7_notification', 'tuxmailer_wpforms_notification', 'tuxmailer_install_notification', 'tuxmailer_formidable_sub_notification', 'tuxmailer_ninja_sub_notification', 'tuxmailer_wpforms_sub_notification', 'tuxmailer_apikey_notification' );
		$dismiss_notification = sanitize_text_field( wp_unslash( $_POST['dismissNoti'] ) );
		if ( in_array( $dismiss_notification, $all_notification, true ) ) {
			update_option( $dismiss_notification, 0 );
		}
	}
	wp_die();
}
add_action( 'wp_ajax_remove_admin_notifications', 'tuxmlr_remove_admin_notifications' );
add_action( 'wp_ajax_nopriv_remove_admin_notifications', 'tuxmlr_remove_admin_notifications' );

/**
 * To set API call is active or not.
 *
 * @return void
 */
function tuxmlr_updated_tokenapi_status() {
	echo wp_json_encode(
		get_option(
			'tuxmlr_token_status',
			array(
				'response_code'  => 07,
				'is_valid_token' => false,
				'message'        => sprintf(
					/* translators: 1: API Key URL */
					__( 'You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
					esc_url( TUXMAILER_API_KEY_MANAGEMENT )
				),
			)
		)
	);
	wp_die();
}
add_action( 'wp_ajax_tuxmlr_updated_token_status', 'tuxmlr_updated_tokenapi_status' );
add_action( 'wp_ajax_nopriv_tuxmlr_updated_token_status', 'tuxmlr_updated_tokenapi_status' );



/*----------------------------------------- Admin Notice Section Ends Starts -----------------------------------------  */

/**
 * Returns not install plugin notice.
 *
 * @return array
 */
function tuxmlr_plugin_installation_notice() {
	$enable_entry_submission = '';
	$required_plugins        = array(
		'gravityforms'   => array(
			'name'     => 'Gravity Forms',
			'download' => esc_url( TUXMAILER_GRAVITYFORMS_URL ),
			'path'     => 'gravityforms/gravityforms.php',
			'tux_key'  => 'tuxmlr_selected_gravity_forms',
			'slug'     => 'gravityforms',
		),
		'wpforms'        => array(
			'name'     => 'WPForms Pro',
			'download' => esc_url( TUXMAILER_WPFORMS_PRO_URL ),
			'path'     => 'wpforms/wpforms.php',
			'tux_key'  => 'tuxmlr_selected_wp_forms',
			'slug'     => 'wpforms',
		),
		'ninja-forms'    => array(
			'name'     => 'Ninja Forms',
			'download' => esc_url( TUXMAILER_NINJA_FORMS_URL ),
			'path'     => 'ninja-forms/ninja-forms.php',
			'tux_key'  => 'tuxmlr_selected_ninja_forms',
			'slug'     => 'ninja-forms',
		),
		'formidable'     => array(
			'name'     => 'Formidable Forms',
			'download' => esc_url( TUXMAILER_FORMIDABLE_FORMS_URL ),
			'path'     => 'formidable/formidable.php',
			'tux_key'  => 'tuxmlr_selected_formidable_forms',
			'slug'     => 'formidable',
		),
		'contact-form-7' => array(
			'name'     => 'Contact Form 7',
			'download' => esc_url( TUXMAILER_CONTACT_FORM_7_URL ),
			'path'     => 'contact-form-7/wp-contact-form-7.php',
			'tux_key'  => 'tuxmlr_selected_contact_forms',
			'slug'     => 'contact-form-7',
		),
		'flamingo'       => array(
			'name'     => 'Flamingo',
			'download' => esc_url( TUXMAILER_FLAMINGO_URL ),
			'path'     => 'flamingo/flamingo.php',
			'tux_key'  => 'select-cf7-flamingo',
			'slug'     => 'contact-form-7',
		),
	);
	// Gravity Forms, WPForms Pro, Ninja Forms,  Formidable Forms, Contact Form 7 + Flamingo.

	$not_installed_plugins = '';
	$final_inactive_plugin = '';
	$not_installed         = array();
	$active_plugin         = array();
	$tux_is_installed      = true;
	$tux_is_active_any     = true;
	$to_install_message    = '';
	foreach ( $required_plugins as $plugin_key => $plugin ) {
		$isinstalled = file_exists( WP_PLUGIN_DIR . '/' . $plugin['path'] );

		if ( false === $isinstalled ) {
			$not_installed = '<a href=' . $plugin['download'] . ' target="_blank" >' . $plugin['name'] . '</a>';

			$not_installed_plugins .= $not_installed . '  ';
		} elseif ( ( true === $isinstalled ) && ! ( is_plugin_active( $plugin['path'] ) ) && current_user_can( 'activate_plugins' ) ) {

			$inactive_plugin  = '<a href=' . get_admin_url() . 'plugins.php target="_blank" >' . $plugin['name'] . '</a>';
			$not_install[]    = $plugin['tux_key'];
			$tux_is_installed = false;
			update_option( 'uninstalled_plugins', $not_install );
			$final_inactive_plugin .= $inactive_plugin . '  ';

		} else {
			$active_plugin[ $plugin_key ] = array(
				'tux_key'      => $plugin['tux_key'],
				'plugin_title' => $plugin['name'],
				'slug'         => $plugin['slug'],
			);
			$tux_is_active_any            = false;
			$tux_is_installed             = false;
			continue;
		}
	}

	// To unset cf7 key from array if flamingo is not active.
	if ( ! ( array_key_exists( 'contact-form-7', $active_plugin ) && array_key_exists( 'flamingo', $active_plugin ) ) ) {
		unset( $active_plugin['contact-form-7'] );
	}
	update_option( 'tux_active_plugin', $active_plugin );
	foreach ( $required_plugins as $plugin ) {

		$plugin_url = '<a href=' . $plugin['download'] . ' target="_blank" >' . $plugin['name'] . '</a>';
		if ( 'flamingo/flamingo.php' === $plugin['path'] ) {
			$to_install_message .= '+ ';
		}
			$to_install_message .= $plugin_url . '  ';
	}

	$final_message = '';
	if ( get_option( 'tuxmailer_cf7_notification' ) && ( ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) && ! file_exists( WP_PLUGIN_DIR . '/flamingo/flamingo.php' ) ) || is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) && ! is_plugin_active( 'flamingo/flamingo.php' ) ) ) {
		$final_message .= "<div class='notice tuxmailer-error-notice is-dismissible remove-notification' data-tuxNotification='tuxmailer_cf7_notification' ><p>" . sprintf(
		/* translators: 1: Plugins name */
			__( 'You need to install/activate %1$s plugin along with <strong>Contact Form 7</strong> for TuxMailer plugin to work properly.', 'tuxmailer-email-validation' ),
			'<strong><i><a href="' . esc_url( TUXMAILER_FLAMINGO_URL ) . '" target="_blank" >Flamingo</a></i></strong>'
		) . '</p></div>';
		$tux_is_active_any = false;
	}
	if ( get_option( 'tuxmailer_wpforms_notification' ) && ( ( is_plugin_active( 'wpforms-lite/wpforms.php' ) && ! file_exists( WP_PLUGIN_DIR . '/wpforms/wpforms.php' ) ) || ( is_plugin_active( 'wpforms-lite/wpforms.php' ) && ! is_plugin_active( 'wpforms/wpforms.php' ) ) ) ) {
		$final_message .= "<div class='notice tuxmailer-error-notice is-dismissible remove-notification' data-tuxNotification='tuxmailer_wpforms_notification'><p>" . sprintf(
			/* translators: 1: Plugins name */
			__( 'You need to install/activate %1$s plugin for Tux Mailer plugin to work properly.', 'tuxmailer-email-validation' ),
			'<strong><i><a href="' . esc_url( TUXMAILER_WPFORMS_PRO_URL ) . '" target="_blank" >WPForms Pro</a></i></strong>'
		) . '</p></div>';
		$tux_is_active_any = false;
	}

	// All plugin are active so need to set null for 'uninstalled_plugins' in option table.
	if ( empty( $not_install ) ) {
		update_option( 'uninstalled_plugins', null );
	}
	// All plugin are deactivated so need to set null for 'tux_active_plugin' in option table.
	if ( empty( $active_plugin ) ) {
		update_option( 'tux_active_plugin', null );
	}

	if ( ( $tux_is_installed || $tux_is_active_any ) && get_option( 'tuxmailer_install_notification' ) ) {

			$final_message .= "<div class='notice tuxmailer-error-notice is-dismissible remove-notification' data-tuxNotification='tuxmailer_install_notification'><p>" . sprintf(
			/* translators: 1: Plugins name */
				__( 'You need to install any of the following plugins and activate it for the TuxMailer plugin to work: %1$s ', 'tuxmailer-email-validation' ),
				'<strong><i>' . $to_install_message . '.</strong></i>'
			) . '</p></div>';

	}

	echo wp_kses_post( $final_message );

	/**
	 * Notice for enable Store submission.
	 */
	$active_plugins = get_option( 'active_plugins' );
	if ( in_array( 'formidable/formidable.php', $active_plugins, true ) && get_option( 'tuxmailer_formidable_sub_notification' ) ) {
		$selected_frm_form_ids = get_option( 'tuxmlr_selected_formidable_forms', array() );
		if ( ! empty( $selected_frm_form_ids ) ) {
			foreach ( $selected_frm_form_ids as $frm_id ) {
				$form = FrmForm::getOne( $frm_id );
				if ( ! $form ) {
					return;
				}
				if ( $form && isset( $form->options['no_save'] ) && ( 1 === intval( $form->options['no_save'] ) ) ) {
					$enable_entry_submission .= '<div class="notice tuxmailer-error-notice is-dismissible remove-notification" data-tuxNotification="tuxmailer_formidable_sub_notification"><p>' .
					sprintf(
					/* translators: 1: Label of setting 2: Form Name */
						__( 'Please disable %1$s settings for form %2$s in Formidable Forms for Tux Mailer plugin to work properly.', 'tuxmailer-email-validation' ),
						'<strong><i><a href="https://formidableforms.com/knowledgebase/general-form-settings/#kb-storing-entries" target="_blank" >Do not store entries submitted from this form</a></i></strong>',
						'"<strong>' . esc_attr( $form->name ) . '</strong>"'
					) . '</p></div>';
				}
			}
		}
	}

	if ( in_array( 'ninja-forms/ninja-forms.php', $active_plugins, true ) && get_option( 'tuxmailer_ninja_sub_notification' ) ) {
		$selected_nj_ids = get_option( 'tuxmlr_selected_ninja_forms', array() );
		if ( ! empty( $selected_nj_ids ) ) {
			foreach ( $selected_nj_ids as $ninja_id ) {
				$form    = Ninja_Forms()->form( $ninja_id )->get();
				$actions = Ninja_Forms()->form( $ninja_id )->get_actions();

				foreach ( $actions as $action ) {
					if ( 'save' === $action->get_settings( 'type' ) && 0 === intval( $action->get_setting( 'active' ) ) ) {
						$enable_entry_submission .= '<div class="notice tuxmailer-error-notice is-dismissible remove-notification" data-tuxNotification="tuxmailer_ninja_sub_notification"><p>' .
						sprintf(
						/* translators: 1: Label of setting 2: Form Name */
							__( 'Please enable %1$s settings for form %2$s in Ninja Forms for Tux Mailer plugin to work properly.', 'tuxmailer-email-validation' ),
							'<strong><i><a href="https://ninjaforms.com/blog/ninja-forms-feature-spotlight-save-action/" target="_blank" >Store Submission</a></i></strong>',
							'"<strong>' . esc_attr( $form->get_setting( 'title' ) ) . '</strong>"'
						) . '</p></div>';
					}
				}
			}
		}
	}
	if ( in_array( 'wpforms/wpforms.php', $active_plugins, true ) && get_option( 'tuxmailer_wpforms_sub_notification' ) ) {
		$selected_wpforms_ids = get_option( 'tuxmlr_selected_wp_forms', array() );
		if ( ! empty( $selected_wpforms_ids ) ) {
			foreach ( $selected_wpforms_ids as $wpforms_id ) {
				$form = wpforms()->form->get( $wpforms_id );
				if ( ! $form ) {
					return;
				}
				$form_data = ! empty( $form->post_content ) ? wpforms_decode( $form->post_content ) : '';
				if ( isset( $form_data['settings']['disable_entries'] ) ) {
					$enable_entry_submission .= '<div class="notice tuxmailer-error-notice is-dismissible remove-notification" data-tuxNotification="tuxmailer_wpforms_sub_notification"><p>' .
					sprintf(
					/* translators: 1: Label of setting 2: Form Name */
						__( 'Please disable %1$s settings for form %2$s in WPForms for Tux Mailer plugin to work properly.', 'tuxmailer-email-validation' ),
						'<strong><i><a href="https://wpforms.com/how-to-save-your-contact-form-data-in-wordpress-database/#howtomanageentries" target="_blank" >Storing entry information in WordPress</a></i></strong>',
						'"<strong>' . esc_attr( $form_data['settings']['form_title'] ) . '</strong>"'
					) . '</p></div>';
				}
			}
		}
	}

	echo wp_kses_post( $enable_entry_submission );

	// If there is no token.

	$key = get_option( 'tuxmlr_api_key' );

	if ( ! $key && get_option( 'tuxmailer_apikey_notification' ) ) {

		echo '<div class="notice tuxmailer-error-notice is-dismissible remove-notification" data-tuxNotification="tuxmailer_apikey_notification"><p>' . sprintf(
			wp_kses( /* translators: %s - plugin settings page URL. */
				__( 'Please <a href="%1$s" target="_blank">enter and validate</a>  your API key. <br>Hint: You can generate your API key from within the <a href="%2$s" target="_blank">TuxMailer App</a>.', 'tuxmailer-email-validation' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
					),
				)
			),
			esc_url( add_query_arg( array( 'page' => 'tuxmailer-options-page' ), admin_url( 'admin.php' ) ) ),
			esc_url( TUXMAILER_API_KEY_MANAGEMENT )
		) . '</p></div>';
	}

	return $not_installed;
}

add_action( 'admin_notices', 'tuxmlr_plugin_installation_notice' );
