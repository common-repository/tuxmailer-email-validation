<?php

/**
 * On submission validation hook.
 *
 * @param object $result all entry data.
 * @param object $tag its basically field object in cf7.
 * @return array $result .
 */
function tuxmlr_cf7_email_on_submit_validation( $result, $tag ) {

	if ( isset( $_POST['_wpcf7'] ) ) { // phpcs:ignore
		$form_id = intval( wp_unslash( $_POST['_wpcf7'] ) ); // phpcs:ignore.
	} else {
		return $result;
	}

	$cf7_selected_ids = get_option( 'tuxmlr_selected_contact_forms' );
	$cf7_selected_ids = ( ! empty( $cf7_selected_ids ) ) ? $cf7_selected_ids : array();
	$cf7_selected_ids = map_deep( $cf7_selected_ids, 'intval' );
	if ( in_array( $form_id, $cf7_selected_ids, true ) ) {

		$name = $tag->name;

		$value = isset( $_POST[ $name ] ) ? sanitize_email( trim( wp_unslash( strtr( (string) $_POST[ $name ], "\n", ' ' ) ) ) ) : '';// phpcs:ignore
		if ( 'email' === strval( $tag->basetype ) && is_email( $value ) ) {
			$email          = $value;
			$response_array = tuxmlr_single_api_call( $email );

			tuxmlr_update_email_meta( $email, $response_array );

			if ( is_array( $response_array ) && ! empty( $response_array ) ) {
				if ( 200 === intval( $response_array['response_code'] ) ) {
					if ( 1 !== intval( $response_array['valid_address'] ) && 'unknown' !== strval( $response_array['status'] ) ) {
						$message = get_option( 'tuxmlr_custom_error_message' ); // To get custom error message from settings page.
						if ( ! empty( $message ) ) {
							$result->invalidate( $tag, esc_attr( $message ) );
						} else {
							$result->invalidate( $tag, esc_attr( $response_array['details'] ) );
						}
					}
				}
			}
		}
	}
	return $result;
}

add_filter( 'wpcf7_validate_email*', 'tuxmlr_cf7_email_on_submit_validation', 5, 2 );
add_filter( 'wpcf7_validate_email', 'tuxmlr_cf7_email_on_submit_validation', 5, 2 );


/**
 * After Submission hook to update `tuxmalr_response_meta` table for current entry with its email response meta
 *
 * @param array $result This hook return entry after submission.
 * @return void
 */
function tuxmlr_cf7_after_submission_flamingo( $result ) {
	$form_id          = intval( $result['contact_form_id'] );
	$cf7_selected_ids = get_option( 'tuxmlr_selected_contact_forms' );
	$cf7_selected_ids = ( ! empty( $cf7_selected_ids ) ) ? $cf7_selected_ids : array();
	$cf7_selected_ids = map_deep( $cf7_selected_ids, 'intval' );
	if ( in_array( $form_id, $cf7_selected_ids, true ) ) {
		$plugin_name = 'contact-form-7';
		$sub_id      = $result['flamingo_inbound_id'];
		$fields      = get_post_meta( $sub_id, '_fields', true );
		if ( ! empty( $fields ) ) {
			foreach ( (array) $fields as $key => $value ) {
				$meta_key = sanitize_key( '_field_' . $key );

				if ( metadata_exists( 'post', $sub_id, $meta_key ) ) {
					$value = get_post_meta( $sub_id, $meta_key, true );
					if ( is_email( $value ) ) {
						$email    = $value;
						$response = tuxmlr_get_email_meta( $email );
						tuxmlr_response_meta( $plugin_name, $form_id, $sub_id, $response, $email );
					}
				}
			}
		}
	}
}

add_action( 'wpcf7_after_flamingo', 'tuxmlr_cf7_after_submission_flamingo', 10, 1 );


/**
 * This function returns all forms title and Id for dropdown in tuxmailler setting page.
 *
 * @return array .
 */
function tuxmlr_get_cf7_title() {
	global $wpdb;

	$results = $wpdb->get_results( $wpdb->prepare( "SELECT ID,post_title,post_name FROM $wpdb->posts WHERE post_type= %s AND post_status =%s", 'wpcf7_contact_form', 'publish' ) );

	$forms_title = wp_list_pluck( $results, 'post_title', 'ID' );

	return $forms_title;
}


/**
 * To return all cf7 entry_ids for given form_id.
 *
 * @param int $form_id cf7 form Id.
 * @return array
 */
function tuxmlr_cf7_entry_ids( $form_id ) {
	if ( intval( $form_id ) <= 0 ) {
		return;
	}
	$entries_ids = array();

	if ( metadata_exists( 'post', $form_id, '_flamingo' ) ) {
		$flamingo_channel_id = get_post_meta( $form_id, '_flamingo', true );
		$texonomy_id         = ( $flamingo_channel_id )['channel'];
		$entries_ids         = get_objects_in_term( $texonomy_id, 'flamingo_inbound_channel' );
	}

	return $entries_ids;
}


/**
 * To return only selected forms for ajax dropdown in bulk view page.
 *
 * @return array
 */
function tuxmlr_get_cf7_ids_settings() {
	$selected_form = array();

	$cf7_selected_ids = get_option( 'tuxmlr_selected_contact_forms' );
	$cf7_selected_ids = ( ! empty( $cf7_selected_ids ) ) ? $cf7_selected_ids : array();

	if ( ! empty( $cf7_selected_ids ) ) {
		foreach ( $cf7_selected_ids as $form_id ) {
			$selected_form[ $form_id ] = get_the_title( $form_id );
		}
	}

	return $selected_form;
}


/**
 * To update all entries form selected forms in tux_mailler settings page.
 *
 * @param array $form_ids array of Form Id.
 * @return void
 */
function tuxmlr_get_all_cf7_ids_entries( $form_ids ) {
	global $wpdb;
	$tuxmalr_response_meta_table = $wpdb->prefix . 'tuxmalr_response_meta';
	$response_array              = null;
	foreach ( $form_ids as $form_id ) {
		$entries_list = tuxmlr_cf7_entry_ids( $form_id );

		if ( ! empty( $entries_list ) ) {
			$tux_meta           = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT entry_id FROM {$tuxmalr_response_meta_table} WHERE plugin_name=%s AND form_id=%d ", 'contact-form-7', $form_id ) );// phpcs:ignore
			$final_entries_list = array_diff( $entries_list, $tux_meta );
			if ( ! empty( $final_entries_list ) ) {

				foreach ( $final_entries_list as $sub_id ) {
					$fields = get_post_meta( $sub_id, '_fields', true );

					if ( ! empty( $fields ) ) {
						foreach ( (array) $fields as $key => $value ) {
							$meta_key = sanitize_key( '_field_' . $key );

							if ( metadata_exists( 'post', $sub_id, $meta_key ) ) {
								$value = get_post_meta( $sub_id, $meta_key, true );

								if ( is_email( $value ) || filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
									$email = $value;
									tuxmlr_add_meta( 'contact-form-7', $form_id, $sub_id, $response_array, $email );
								}
							}
						}
					}
				}
			}
		}
	}
}

/**
 * To return all selected entries data,for collecting anonymous data.
 *
 * @param array $entry_ids .
 * @param int   $form_id .
 * @return array
 */
function tuxmlr_cf7_anonymous_data( $entry_ids, $form_id ) {
	$data = array();
	foreach ( $entry_ids as $sub_id ) {
		$fields = get_post_meta( $sub_id, '_fields', true );

		if ( ! empty( $fields ) ) {
			foreach ( (array) $fields as $key => $value ) {
				$meta_key = sanitize_key( '_field_' . $key );

				if ( metadata_exists( 'post', $sub_id, $meta_key ) ) {
					$value                   = get_post_meta( $sub_id, $meta_key, true );
					$data[ $sub_id ][ $key ] = $value;
				}
			}
		}
	}
	return $data;
}
