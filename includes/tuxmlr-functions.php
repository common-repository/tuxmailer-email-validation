<?php
/**
 * To validate single email by Tuxmailer api .
 *
 * @param string $email .
 * @return array
 */
function tuxmlr_single_api_call( $email ) {
	$response_array = array();
	$token          = get_option( 'tuxmlr_api_key' );

	$args = array(
		'timeout' => 120,
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		),
	);

	$account_type = get_option( 'tuxmlr_account_type' );
	$tag          = 'WordPress';

	// To check teamid for account selected or personal account.
	if ( 'team_account' === $account_type ) {
		$selected_team_name = get_option( 'tuxmlr_team_account' );
		$selected_team_id   = array_search( $selected_team_name, get_option( 'tuxmlr_team_details' ), true );

		$url = TUXMAILER_SINGLE_EMAIL_API_URL . "team_id={$selected_team_id}&team_name={$selected_team_name}&email={$email}&bypass_blacklist=false&tag={$tag}";
	} elseif ( 'personal_account' === $account_type || empty( $account_type ) ) {
		$url = TUXMAILER_SINGLE_EMAIL_API_URL . "email={$email}&bypass_blacklist=false&tag={$tag}";
	}

	$request = wp_remote_post( $url, $args );

	if ( is_wp_error( $request ) ) {
		$error_code = $request->get_error_code();
		return array(
			'response_code' => $error_code,
			'error_message' => esc_html__( 'We are unable to connect to our back-end servers. Please contact support.', 'tuxmailer-email-validation' ),
			'response_code' => $error_code,
			'valid_address' => 1,
		);
	} else {
		$http_code                       = wp_remote_retrieve_response_code( $request );
		$response_json                   = wp_remote_retrieve_body( $request );
		$response_array                  = json_decode( $response_json, 1 );
		$response_array['response_code'] = $http_code;
	}

	return $response_array;
}

/**
 * To validate bulk emails by Tuxmailer integration API.
 *
 * @param string  $plugin_name selected plugin name.
 * @param int     $form_id selected form id.
 * @param string  $form_name selected form name.
 * @param string  $uid UID for current request.
 * @param array   $entries all selected entries id.
 * @param array   $email_list selected entries emails list.
 * @param boolean $by_pass_black_list true or false.
 * @param boolean $priority_porcessing .
 * @param boolean $final_part .
 * @param int     $emails_count Total emails send.
 * @param int     $api_count Number of times API called i.e page number.
 * @return array
 */
function tuxmlr_bulk_integrations_api_call( $plugin_name, $form_id, $form_name, $uid, $entries, $email_list, $by_pass_black_list, $priority_porcessing, $final_part, $emails_count, $api_count ) {
	$token           = get_option( 'tuxmlr_api_key' );
	$account_type    = get_option( 'tuxmlr_account_type' );
	$account_details = array(); // To update account details for email validation.For click 'here' link.
	$bulk_response   = '';

	if ( empty( $token ) ) {
		return array(
			'response_code' => 77,
			'error_message' => esc_html__( 'You need to enter a valid token in the plugin settings page.', 'tuxmailer-email-validation' ),
		);
	}

	$f_name  = strtolower( str_replace( ' ', '-', $form_name ) );
	$list_id = $plugin_name . '-' . $f_name;
	$tag     = $plugin_name . '-' . $f_name;

	$data = array(
		'list_id'     => $list_id,
		'integration' => 'Wordpress',
		'email_list'  => $email_list,
	);

	$anonymous_checked = get_option( 'tuxmlr_anonymous_data' ); // Appending anonymous data. if user agree to share data.
	if ( 1 === intval( $anonymous_checked ) ) {
		$message         = tuxmlr_get_anonomous_data( $plugin_name, $form_id, $entries );
		$data['message'] = $message;
	}

	$args = array(
		'timeout' => 120,
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		),
		'body'    => wp_json_encode( $data ),
	);

	// To check team_id for account selected or personal account.
	if ( 'team_account' === $account_type ) {
		$selected_team_name = get_option( 'tuxmlr_team_account' );
		$selected_team_id   = array_search( $selected_team_name, get_option( 'tuxmlr_team_details' ), true );

		$account_details['account_type'] = 'team_account';
		$account_details['team_id']      = $selected_team_id;
		$account_details['team_name']    = $selected_team_name;

		$url = TUXMAILER_BULK_INTEGRATIONS_API_URL . "uid={$uid}&team_id={$selected_team_id}&team_name={$selected_team_name}&bypass_blacklist={$by_pass_black_list}&priority={$priority_porcessing}&tag={$tag}&final_part=$final_part";
	} elseif ( 'personal_account' === $account_type || empty( $account_type ) ) {
		$account_details['account_type'] = 'personal_account';
		$url                             = TUXMAILER_BULK_INTEGRATIONS_API_URL . "uid={$uid}&bypass_blacklist={$by_pass_black_list}&priority={$priority_porcessing}&tag={$tag}&final_part=$final_part";
	}

	$request = wp_remote_post( $url, $args ); // API call for bulk email validation.

	if ( is_wp_error( $request ) ) {
		$error_code = $request->get_error_code();

		return array(
			'response_code' => $error_code,
			'error_message' => esc_html__( 'We are unable to connect to our back-end servers. Please contact support.', 'tuxmailer-email-validation' ),
		);
	} else {
		$http_code      = wp_remote_retrieve_response_code( $request );
		$response_json  = wp_remote_retrieve_body( $request );
		$response_array = json_decode( $response_json, 1 );

		if ( 422 === $http_code ) {
			$error422      = array_shift( $response_array['detail'] );
			$bulk_response = array(
				'response_code' => $http_code,
				'error_message' => $error422['msg'],
				'type'          => $error422['type'],
				'loc'           => implode( ' ', $error422['loc'] ),
			);
		} elseif ( 401 === $http_code ) {
			$bulk_response = array(
				'response_code' => $http_code,

				'error_message' => sprintf(
					/* translators: 1: API Key URL */
					__( 'You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
					esc_url( TUXMAILER_API_KEY_MANAGEMENT )
				),
			);
		} elseif ( 409 === $http_code ) {
			$bulk_response = array(
				'response_code' => $http_code,
				'error_message' => esc_html__( 'Oops... something went wrong,Please contact support.', 'tuxmailer-email-validation' ),
			);
		} elseif ( 200 === $http_code ) {
			$bulk_response = array(
				'response_code' => $http_code,
				'form_name'     => $form_name,
				'uid'           => $uid,
				'time'          => current_time( 'mysql', false ),
			);
			if ( 'true' === $final_part ) {
				tuxmlr_update_bulk_request_log( $plugin_name, $form_id, $form_name, $entries, $api_count, $uid, $list_id, $emails_count, $account_details );
			}
		} else {
			$bulk_response = array(
				'response_code' => 101,
				'error_message' => esc_html__( 'Oops... something went wrong,Please contact support.', 'tuxmailer-email-validation' ),
			);
		}
	}

	return $bulk_response;
}


/**
 * To get bulk response
 *
 * @param string $uid UID to retrive response.
 * @param int    $page page number.
 * @return string
 */
function tuxmlr_get_bulk_email_response_api( $uid, $page ) {
	$size  = TUXMAILER_MAX_EMAILS_PER_REQUEST;
	$token = get_option( 'tuxmlr_api_key' );

	$args = array(
		'timeout' => 120,
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		),
	);

	$url     = TUXMAILER_GET_BULK_API_URL . "page={$page}&size={$size}&bulk_type=integration&uid=$uid&sort_by=uid&sort_type=1";
	$request = wp_remote_get( $url, $args );

	$http_code = wp_remote_retrieve_response_code( $request );

	if ( is_wp_error( $request ) ) {

		return wp_json_encode( array( 'items' => '' ) );
	} else {
		if ( 200 === $http_code ) {

			return wp_remote_retrieve_body( $request );
		} else {
			return wp_json_encode( array( 'items' => '' ) );
		}
	}
}

add_action( 'wp_ajax_tuxmlr_verify_api', 'tuxmlr_verify_token_api_callback' );
add_action( 'wp_ajax_nopriv_tuxmlr_verify_api', 'tuxmlr_verify_token_api_callback' );

/**
 * To validate token from settings page API when clicked on 'Validate token'
 *
 * @return void
 */
function tuxmlr_verify_token_api_callback() {
	if ( isset( $_POST['nonce'] ) && isset( $_POST['tuxmlr_api_key'] ) && ! empty( $_POST['tuxmlr_api_key'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tuxmailer-emails-validation' ) ) {
			die( 'Permission Denied.' );
		}
		$token = sanitize_text_field( wp_unslash( $_POST['tuxmlr_api_key'] ) );

		if ( ! ( '' === $token ) ) {
			$args = array(
				'timeout' => 120,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				),
			);

			$request = wp_remote_get( TUXMAILER_WELCOME_API_URL, $args );

			if ( is_wp_error( $request ) ) {
				$error_code    = $request->get_error_code();
				$error_message = $request->get_error_message( $error_code );
				$error         = array(
					'response_code' => $error_code,
					'error_message' => esc_html__( 'We are unable to connect to our back-end servers. Please contact support.', 'tuxmailer-email-validation' ),
				);
				update_option(
					'tuxmlr_token_status',
					array(
						'response_code'  => $error_code,
						'is_valid_token' => false,
						'message'        => esc_html__( 'We are unable to connect to our back-end servers. Please contact support.', 'tuxmailer-email-validation' ),
					),
					true
				);
				echo wp_json_encode( $error );
				wp_die();
			} else {
				$http_code      = wp_remote_retrieve_response_code( $request );
				$response_json  = wp_remote_retrieve_body( $request );
				$response_array = json_decode( $response_json, 1 );

				if ( 422 === $http_code ) {

					$error422 = array_shift( $response_array['detail'] );

					$response_array = sprintf(
						/* translators: 1: API Key URL */
						__( 'You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
						esc_url( TUXMAILER_API_KEY_MANAGEMENT )
					);
					echo wp_json_encode(
						array(
							'response_code' => $http_code,
							'error_message' => $error422['msg'],
						)
					);
				} elseif ( 401 === $http_code ) {
					echo wp_json_encode(
						array(
							'response_code' => $http_code,
							'error_message' => sprintf(
								/* translators: 1: API Key URL */
								__( 'You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
								esc_url( TUXMAILER_API_KEY_MANAGEMENT )
							),
						)
					);
				} elseif ( 200 === $http_code ) {
					update_option( 'tuxmlr_api_key', $token );
					update_option( 'welcome_message', $response_array['Message'] );

					update_option(
						'tuxmlr_token_status',
						array(
							'response_code'  => 200,
							'is_valid_token' => true,
							'message'        => $response_array['Message'],
						),
						true
					);
					$balance      = tuxmlr_get_api_balance( null, null );
					$team_metas   = tuxmlr_get_term_credits();
					$team_details = '<option value="-1">Select Team</option>';
					if ( ! empty( $team_metas ) && 200 === intval( $team_metas['response_code'] ) ) {
						unset( $team_metas['response_code'] );
						foreach ( $team_metas as $team_meta ) {
							$team_details .= '<option id ="' . $team_meta['team_id'] . '" value = "' . $team_meta['team_name'] . '" >' . $team_meta['team_name'] . ' </option>';
						}
					}

					echo wp_json_encode(
						array(
							'response_code' => $http_code,
							'message'       => $response_array['Message'],
							'team_meta'     => $team_details,
							'balance'       => $balance,
						)
					);
				} else {

					echo wp_json_encode( array( 'message' => $response_array['detail'] ) );
					update_option(
						'tuxmlr_token_status',
						array(
							'response_code'  => $http_code,
							'is_valid_token' => false,
							'message'        => $response_array['detail'],
						),
						true
					);
				}
			}
		} else {
			echo wp_json_encode( array( 'message' => 'Please enter the api key' ) );
		}
	}
	wp_die();
}




/**
 * API function to get credit remaining
 *
 * @param string $key Team Id.
 * @param string $value Team Name .
 * @return array
 */
function tuxmlr_get_api_balance( $key, $value ) {
	$token = get_option( 'tuxmlr_api_key' );

	$response_array = '';

	$args = array(
		'timeout' => 120,
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		),
	);

	$team_id   = intval( $key );
	$team_name = $value;

	if ( null === $key && null === $value ) {
		$url = TUXMAILER_BALANCE_API_URL;
	} else {
		$url = TUXMAILER_BALANCE_API_URL . "?team_id=$team_id&team_name=$team_name";
	}

	if ( ! empty( $token ) ) {
		$request = wp_remote_get( $url, $args );
	} else {
		return null;
	}

	if ( is_wp_error( $request ) ) {
		$error_code    = $request->get_error_code();
		$error_message = esc_html__( 'We are unable to connect to our back-end servers. Please contact support.', 'tuxmailer-email-validation' );

		update_option(
			'tuxmlr_token_status',
			array(
				'response_code'  => $error_code,
				'is_valid_token' => false,
				'message'        => $error_message,
			)
		);
		return null;
	} else {
		$http_code     = intval( wp_remote_retrieve_response_code( $request ) );
		$response_json = wp_remote_retrieve_body( $request );

		if ( 200 === $http_code ) {
			$resp_array     = json_decode( $response_json, 1 );
			$response_array = intval( $resp_array['term_credits'] ) + intval( $resp_array['pay_as_you_go_credits'] );

			$welcome_message = get_option( 'welcome_message' );
			update_option(
				'tuxmlr_token_status',
				array(
					'response_code'  => $http_code,
					'is_valid_token' => true,
					'message'        => $welcome_message,
				)
			);
		} elseif ( 401 === $http_code ) {
			$response_array = sprintf(
				/* translators: 1: API Key URL */
				__( 'You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
				esc_url( TUXMAILER_API_KEY_MANAGEMENT )
			);
			update_option(
				'tuxmlr_token_status',
				array(
					'response_code'  => $http_code,
					'is_valid_token' => false,
					'message'        => sprintf(
						/* translators: 1: API Key URL */
						__( 'You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
						esc_url( TUXMAILER_API_KEY_MANAGEMENT )
					),
				)
			);
		} elseif ( 422 === $http_code ) {
			$response_array = sprintf(
				/* translators: 1: API Key URL */
				__( 'You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
				esc_url( TUXMAILER_API_KEY_MANAGEMENT )
			);
			update_option(
				'tuxmlr_token_status',
				array(
					'response_code'  => $http_code,
					'is_valid_token' => false,
					'message'        => sprintf(
						/* translators: 1: API Key URL */
						__( 'You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
						esc_url( TUXMAILER_API_KEY_MANAGEMENT )
					),
				)
			);
		} else {
			$response_array = esc_html__( 'We are unable to connect to our back-end servers. Please contact support.', 'tuxmailer-email-validation' );
		}
	}

	return ( 200 === $http_code ) ? $response_array : '0';
}


/**
 * To get all tux teams info
 *
 * @return array
 */
function tuxmlr_get_teams() {
	$token                = get_option( 'tuxmlr_api_key' );
	$final_response_array = array();
	$args                 = array(
		'timeout' => 120,
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		),
	);

	$request = wp_remote_get( TUXMAILER_TEAM_API_URL, $args );

	if ( is_wp_error( $request ) ) {
		return $final_response_array;
	} else {
		$response_json  = wp_remote_retrieve_body( $request );
		$response_array = json_decode( $response_json, 1 );
		$response_code  = intval( wp_remote_retrieve_response_code( $request ) );

		if ( 200 === $response_code ) {
			$final_response_array = wp_list_pluck( $response_array, 'team_name', 'team_id' );
		} elseif ( 422 === $response_code ) {
			$final_response_array = sprintf(
				/* translators: 1: API Key URL */
				__( 'You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
				esc_url( TUXMAILER_API_KEY_MANAGEMENT )
			);
		}
	}

	return $final_response_array;
}


/**
 * To get credit remaining for team account when page is refresh.
 *
 * @return array
 */
function tuxmlr_get_term_credits() {
	$token      = get_option( 'tuxmlr_api_key' );
	$args       = array(
		'timeout' => 120,
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		),
	);
	$teams_meta = array();
	$request    = wp_remote_get( TUXMAILER_TEAM_API_URL, $args );

	if ( is_wp_error( $request ) ) {
		$error_code = $request->get_error_code();
		update_option(
			'tuxmlr_token_status',
			array(
				'response_code'  => $error_code,
				'is_valid_token' => false,
				'message'        => esc_html__( 'We are unable to connect to our back-end servers. Please contact support.', 'tuxmailer-email-validation' ),
				'team2'          => 'wpError',
			)
		);

		return array( 'response_code' => $error_code );
	} else {
		$response_code = intval( wp_remote_retrieve_response_code( $request ) );

		if ( 200 === $response_code ) {
			$response_json  = wp_remote_retrieve_body( $request );
			$response_array = json_decode( $response_json, 1 );

			if ( ! empty( $response_array ) ) {
				$team_details = array();
				foreach ( $response_array as $team ) {
					$team_name                          = $team['team_name'];
					$team_id                            = $team['team_id'];
					$term_credits_main                  = $team['subscriptions']['email_verification']['term_credits'];
					$term_credits_pay_as_you_go_credits = $team['subscriptions']['email_verification']['pay_as_you_go_credits'];
					$team_details[ $team_name ]         = array(
						'team_id'      => $team_id,
						'team_name'    => $team_name,
						'term_credits' => ( intval( $term_credits_main ) + intval( $term_credits_pay_as_you_go_credits ) ),
					);
				}
			}
			$team_details['response_code'] = 200;
			$teams_meta                    = $team_details;

			$welcome_message = get_option( 'welcome_message' );
			update_option(
				'tuxmlr_token_status',
				array(
					'response_code'  => $response_code,
					'is_valid_token' => true,
					'message'        => $welcome_message,
				)
			);
		} elseif ( 401 === $response_code ) {
			update_option(
				'tuxmlr_token_status',
				array(
					'response_code'  => $response_code,
					'is_valid_token' => false,
					'message'        => sprintf(
						/* translators: 1: API Key URL */
						__( 'You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
						esc_url( TUXMAILER_API_KEY_MANAGEMENT )
					),
				)
			);
			return array( 'response_code' => $response_code );
		} elseif ( 422 === $response_code ) {
			update_option(
				'tuxmlr_token_status',
				array(
					'response_code'  => $response_code,
					'is_valid_token' => false,
					'message'        => sprintf(
						/* translators: 1: API Key URL */
						__( 'You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation' ),
						esc_url( TUXMAILER_API_KEY_MANAGEMENT )
					),
				)
			);
			return array( 'response_code' => $response_code );
		} else {
			return array( 'response_code' => $response_code );
		}
	}

	return ( 200 === $response_code ) ? $teams_meta : array();
}


/****************  Extra function to support bulk function     **********************/

/**
 * To update `completed` after success response recived for UUID in `wp_tuxmlr_api_request` table
 *
 * @param string $uuid .
 * @return boolean
 */
function tuxmlr_update_uid_status( $uuid ) {
	global $wpdb;

	$status = $wpdb->update( "{$wpdb->prefix}tuxmlr_api_request", array( 'response_status' => 'completed' ), array( 'uuid' => $uuid ), array( '%s' ), array( '%s' ) );
	if ( is_wp_error( $status ) ) {
		return false;
	}
	// Need to add more validation.
}


/**
 * To get entries ID by UUID form `wp_tuxmlr_api_request` table
 *
 * @param string $uuid .
 * @return array
 */
function tuxmlr_get_entries_ids_by_uid( $uuid ) {
	global $wpdb;

	$result = $wpdb->get_results( $wpdb->prepare( "SELECT plugin_name, form_id, entry_ids FROM {$wpdb->prefix}tuxmlr_api_request WHERE uuid=%s ", $uuid ) );
	return $result;
}

/**
 * To delete UUID after response is updated in table `wp_tuxmalr_response_meta` table from `wp_tuxmlr_api_request` table
 *
 * @param string $uuid .
 */
function tuxmlr_delete_tux_uuid( $uuid ) {
	global $wpdb;

	$status = $wpdb->delete(
		"{$wpdb->prefix}tuxmlr_api_request",
		array(
			'uuid'            => $uuid,
			'response_status' => 'completed',
		),
		array( '%s', '%s' )
	);
}

/**
 * To sync all plugin old records.
 */
function tuxmlr_sync_previous_entries() {
	$active_plugins = get_option( 'tux_active_plugin' );

	if ( empty( $active_plugins ) ) {
		return;
	}

	foreach ( $active_plugins as  $active_plugin ) {
		switch ( $active_plugin['tux_key'] ) {
			case 'tuxmlr_selected_gravity_forms':
				$gform_ids = get_option( 'tuxmlr_selected_gravity_forms' );
				if ( ! empty( $gform_ids ) ) {
					tuxmlr_get_all_gravityforms_entries( $gform_ids );
				}

				break;

			case 'tuxmlr_selected_wp_forms':
				$wpform_ids = get_option( 'tuxmlr_selected_wp_forms' );

				if ( ! empty( $wpform_ids ) ) {
					tuxmlr_get_all_wpforms_entries( $wpform_ids );
				}

				break;

			case 'tuxmlr_selected_formidable_forms':
				$formidable_form_ids = get_option( 'tuxmlr_selected_formidable_forms' );

				if ( ! empty( $formidable_form_ids ) ) {
					tuxmlr_get_all_formidable_forms_entries( $formidable_form_ids );
				}

				break;

			case 'tuxmlr_selected_ninja_forms':
				$ninja_forms_ids = get_option( 'tuxmlr_selected_ninja_forms' );

				if ( ! empty( $ninja_forms_ids ) ) {
					tuxmlr_get_all_ninja_forms_ids_entries( $ninja_forms_ids );
				}
				break;

			case 'select-cf7-flamingo':
				$all_active_plugins = get_option( 'active_plugins' );
				$cf7_ids            = get_option( 'tuxmlr_selected_contact_forms' );

				if ( ! empty( $cf7_ids ) && in_array( 'contact-form-7/wp-contact-form-7.php', $all_active_plugins, true ) && in_array( 'flamingo/flamingo.php', $all_active_plugins, true ) ) {
					tuxmlr_get_all_cf7_ids_entries( $cf7_ids );
				}
				break;
		}
	}
}

/**
 * To store tux response before submission i.e During Validation.
 *
 * @param string $email Email entried in form.
 * @param array  $response Response recived for respected email .
 * @return void
 */
function tuxmlr_update_email_meta( $email, $response ) {
	global $wpdb;

	if ( empty( $response ) ) {
		if ( ! empty( tuxmlr_get_email_meta( $email ) ) ) {
			return;
		} else {
			$response = __( 'Could not validate your API key. Please check your API key and try again.', 'tuxmailer-email-validation' );
		}
	}

	$serialized_response = maybe_serialize( $response );
	$email_exists        = tuxmlr_get_email_meta( $email ) !== false;

	if ( $email_exists ) {
		$wpdb->update( "{$wpdb->prefix}tuxmlr_email_meta", array( 'response' => $serialized_response ), array( 'email' => $email ), array( '%s' ), array( '%s' ) );
	} else {
		$wpdb->insert(
			"{$wpdb->prefix}tuxmlr_email_meta",
			array(
				'email'    => $email,
				'response' => $serialized_response,
			),
			array( '%s', '%s' )
		);
	}
}


/**
 * To check email meta already exist or not in `wp_tuxmlr_email_meta` table.
 *
 * @param string $email .
 * @return array
 */
function tuxmlr_get_email_meta( $email ) {
	global $wpdb;

	$results  = $wpdb->get_results( $wpdb->prepare( "SELECT response FROM {$wpdb->prefix}tuxmlr_email_meta WHERE email=%s", $email ) );
	$value    = isset( $results[0] ) ? $results[0]->response : null;
	$response = null === $value ? false : maybe_unserialize( $value );

	return $response;
}

/**
 * To add email meta after submission in wp_tuxmalr_response_meta table .
 *
 * @param string $plugin_name .
 * @param int    $form_id .
 * @param int    $entry_id .
 * @param array  $response_array .
 * @param string $email .
 */
function tuxmlr_response_meta( $plugin_name, $form_id, $entry_id, $response_array, $email ) {
	global $wpdb;

	if ( intval( $entry_id ) <= 0 && intval( $form_id ) <= 0 && is_email( $email ) ) {
		return;
	}

	$tuxmalr_response_meta_table = $wpdb->prefix . 'tuxmalr_response_meta';
	$meta_exists                 = tuxmlr_get_entry_response_meta_status( $plugin_name, $form_id, $entry_id, $email );
	$response_code               = intval( $response_array['response_code'] );
	// If entry already exist and response code is 200 then update that entry or row.
	if ( $meta_exists && ( 200 === $response_code ) ) {
		$wpdb->update(
			$tuxmalr_response_meta_table,
			array(
				'domain'                          => ! empty( $response_array['domain'] ) ? $response_array['domain'] : '0',
				'is_catchall_domain'              => ! empty( $response_array['is_catchall_domain'] ) ? 'Yes' : 'No',
				'is_free_email_provider'          => ! empty( $response_array['is_free_email_provider'] ) ? 'Free' : 'Chargeable',
				'mail_server_used_for_validation' => ! empty( $response_array['mail_server_used_for_validation'] ) ? $response_array['mail_server_used_for_validation'] : '0',
				'valid_address'                   => ! empty( $response_array['valid_address'] ) ? 'Valid' : 'Invalid',
				'valid_domain'                    => ! empty( $response_array['valid_domain'] ) ? 'Valid' : 'Invalid',
				'valid_smtp'                      => ! empty( $response_array['valid_smtp'] ) ? 'Valid' : 'Invalid',
				'valid_syntax'                    => ! empty( $response_array['valid_syntax'] ) ? 'Valid' : 'Invalid',
				'is_role_based'                   => ! empty( $response_array['is_role_based'] ) ? 'Yes' : 'No',
				'has_full_inbox'                  => ! empty( $response_array['has_full_inbox'] ) ? 'Full' : 'No',
				'is_disabled'                     => ! empty( $response_array['is_disabled'] ) ? 'Yes' : 'No',
				'tux_status'                      => ! empty( $response_array['status'] ) ? $response_array['status'] : 'invalid',
				'details'                         => ! empty( $response_array['details'] ) ? $response_array['details'] : 'Not availabe',
				'blacklisted'                     => ! empty( $response_array['blacklisted'] ) ? 'Yes' : 'No',
				'billable'                        => ! empty( $response_array['billable'] ) ? 'Yes' : 'No',
			),
			array(
				'plugin_name' => $plugin_name,
				'form_id'     => $form_id,
				'entry_id'    => $entry_id,
				'email'       => $email,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%s', '%d', '%d', '%s' )
		);

		// Insert new recode for response code 200 .
	} elseif ( ( 200 === $response_code ) && ( null === $meta_exists ) ) {
		$wpdb->insert(
			$tuxmalr_response_meta_table,
			array(
				'plugin_name'                     => $plugin_name,
				'form_id'                         => $form_id,
				'entry_id'                        => $entry_id,
				'email'                           => $email,
				'domain'                          => ! empty( $response_array['domain'] ) ? $response_array['domain'] : '0',
				'is_catchall_domain'              => ! empty( $response_array['is_catchall_domain'] ) ? 'Yes' : 'No',
				'is_free_email_provider'          => ! empty( $response_array['is_free_email_provider'] ) ? 'Free' : 'Chargeable',
				'mail_server_used_for_validation' => ! empty( $response_array['mail_server_used_for_validation'] ) ? $response_array['mail_server_used_for_validation'] : '0',
				'valid_address'                   => ! empty( $response_array['valid_address'] ) ? 'Valid' : 'Invalid',
				'valid_domain'                    => ! empty( $response_array['valid_domain'] ) ? 'Valid' : 'Invalid',
				'valid_smtp'                      => ! empty( $response_array['valid_smtp'] ) ? 'Valid' : 'Invalid',
				'valid_syntax'                    => ! empty( $response_array['valid_syntax'] ) ? 'Valid' : 'Invalid',
				'is_role_based'                   => ! empty( $response_array['is_role_based'] ) ? 'Yes' : 'No',
				'has_full_inbox'                  => ! empty( $response_array['has_full_inbox'] ) ? 'Full' : 'No',
				'is_disabled'                     => ! empty( $response_array['is_disabled'] ) ? 'Yes' : 'No',
				'tux_status'                      => ! empty( $response_array['status'] ) ? $response_array['status'] : 'invalid',
				'details'                         => ! empty( $response_array['details'] ) ? $response_array['details'] : 'Not availabe',
				'blacklisted'                     => ! empty( $response_array['blacklisted'] ) ? 'Yes' : 'No',
				'billable'                        => ! empty( $response_array['billable'] ) ? 'Yes' : 'No',
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	// Just create entry for other resposne code with `Not varified` status .

	if ( 200 !== $response_code ) {
		$wpdb->insert(
			$tuxmalr_response_meta_table,
			array(
				'plugin_name'                     => $plugin_name,
				'form_id'                         => $form_id,
				'entry_id'                        => $entry_id,
				'email'                           => $email,
				'domain'                          => null,
				'is_catchall_domain'              => null,
				'is_free_email_provider'          => null,
				'mail_server_used_for_validation' => null,
				'valid_address'                   => null,
				'valid_domain'                    => null,
				'valid_smtp'                      => null,
				'valid_syntax'                    => null,
				'is_role_based'                   => null,
				'has_full_inbox'                  => null,
				'is_disabled'                     => null,
				'tux_status'                      => 'not-verified',
				'details'                         => null,
				'blacklisted'                     => null,
				'billable'                        => null,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		return;
	} else {
		if ( 17 > count( $response_array ) ) {
			return;
		}
	}
}

/**
 * To Update UUID details with plugin_name, form_id and entries_id etc in `wp_tuxmlr_api_request` to keep track of UUID resquest send.
 *
 * @param string $plugin_name .
 * @param int    $form_id .
 * @param string $form_name .
 * @param array  $entries .
 * @param int    $tracking_id .
 * @param string $uuid .
 * @param string $list_id .
 * @param array  $email_list .
 * @param array  $account_details .
 * @return void
 */
function tuxmlr_update_bulk_request_log( $plugin_name, $form_id, $form_name, $entries, $tracking_id, $uuid, $list_id, $email_list, $account_details ) {
	global $wpdb;

	$serialized_email_list      = wp_json_encode( $email_list );
	$serialized_entries         = wp_json_encode( $entries );
	$serialized_account_details = wp_json_encode( $account_details );

	$wpdb->insert(
		"{$wpdb->prefix}tuxmlr_api_request",
		array(
			'plugin_name'        => $plugin_name,
			'form_id'            => $form_id,
			'form_name'          => $form_name,
			'entry_ids'          => $serialized_entries,
			'api_counts'         => $tracking_id,
			'uuid'               => $uuid,
			'list_id'            => $list_id,
			'resquest_timestamp' => current_time( 'mysql', false ),
			'response_status'    => 'Pending',
			'total_emails'       => $serialized_email_list,
			'account_detail'     => $serialized_account_details,
		),
		array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
}

/**
 * To add all old entries in  `tuxmalr_response_meta` table as 'tux_status' = 'not-verified' for all selected forms.
 *
 * @param string $plugin_name .
 * @param int    $form_id .
 * @param int    $entry_id .
 * @param array  $response_array .
 * @param string $email .
 * @return void
 */
function tuxmlr_add_meta( $plugin_name, $form_id, $entry_id, $response_array, $email ) {
	global $wpdb;

	if ( intval( $entry_id ) <= 0 && intval( $form_id ) <= 0 && is_email( $email ) ) {
		return;
	}

	$meta_exists = tuxmlr_get_entry_response_meta_status( $plugin_name, $form_id, $entry_id, $email );

	if ( null === $meta_exists ) {
		$wpdb->insert(
			"{$wpdb->prefix}tuxmalr_response_meta",
			array(
				'plugin_name'                     => $plugin_name,
				'form_id'                         => $form_id,
				'entry_id'                        => $entry_id,
				'email'                           => $email,
				'domain'                          => null,
				'is_catchall_domain'              => null,
				'is_free_email_provider'          => null,
				'mail_server_used_for_validation' => null,
				'valid_address'                   => null,
				'valid_domain'                    => null,
				'valid_smtp'                      => null,
				'valid_syntax'                    => null,
				'is_role_based'                   => null,
				'has_full_inbox'                  => null,
				'is_disabled'                     => null,
				'tux_status'                      => 'not-verified',
				'details'                         => null,
				'blacklisted'                     => null,
				'billable'                        => null,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}
}

/**
 * To check is entry already exist when after submission entry update is done
 * It will return Tux_status or 'null' for entry not found
 *
 * @param sting  $plugin_name .
 * @param int    $form_id .
 * @param int    $entry_id .
 * @param string $email .
 * @return array
 */
function tuxmlr_get_entry_response_meta_status( $plugin_name, $form_id, $entry_id, $email ) {
	global $wpdb;

	$results = $wpdb->get_var( $wpdb->prepare( "SELECT tux_status FROM {$wpdb->prefix}tuxmalr_response_meta WHERE plugin_name=%s AND form_id=%d AND entry_id=%d AND email=%s", $plugin_name, $form_id, $entry_id, $email ) );

	return $results;
}


/**
 * To return all entries data.
 *
 * @param string $plugin_name .
 * @param int    $form_id .
 * @param array  $entries .
 * @return array
 */
function tuxmlr_get_anonomous_data( $plugin_name, $form_id, $entries ) {
	$anonymous_data = array();

	switch ( $plugin_name ) {
		case 'gravityforms':
			$anonymous_data = tuxmlr_gravity_forms_anonymous_data( $entries, $form_id );
			break;

		case 'wpforms':
			$anonymous_data = tuxmlr_wpforms_forms_anonymous_data( $entries, $form_id );
			break;

		case 'formidable':
			$anonymous_data = tuxmlr_formidable_forms_anonymous_data( $entries, $form_id );
			break;

		case 'ninja-forms':
			$anonymous_data = tuxmlr_ninja_forms_anonymous_data( $entries, $form_id );

			break;
		case 'contact-form-7':
			$anonymous_data = tuxmlr_cf7_anonymous_data( $entries, $form_id );
			break;
	}

	return $anonymous_data;
}
