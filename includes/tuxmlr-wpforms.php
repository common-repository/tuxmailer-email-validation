<?php
/**
 * This hook fire when form is submited
 * Validation of email by tux single validate API on submission.
 * After reciving response, it update email resposne meta to `wp_tuxmlr_email_meta` table
 */
add_action(
	'wpforms_process_validate_email',
	function ( $field_id, $field_value, $form_data ) {

		$wpforms_ids = get_option( 'tuxmlr_selected_wp_forms' );
		$wpforms_ids = map_deep( ( ! empty( $wpforms_ids ) ) ? $wpforms_ids : array(), 'intval' );
		if ( in_array( intval( $form_data['id'] ), $wpforms_ids, true ) ) {
			if ( is_array( $field_value ) ) {
				$field_value = $field_value['primary'];
			}

			if ( is_email( $field_value ) ) {
				$email = $field_value;

				$response_array = tuxmlr_single_api_call( $email );
				tuxmlr_update_email_meta( $email, $response_array );

				if ( is_array( $response_array ) && ! empty( $response_array ) ) {
					if ( 200 === intval( $response_array['response_code'] ) ) {
						if ( 1 !== intval( $response_array['valid_address'] ) && 'unknown' !== strval( $response_array['status'] ) ) {
							$message = get_option( 'tuxmlr_custom_error_message' ); // To get custom error message from settings page.
							if ( ! empty( $message ) ) {
								wpforms()->process->errors[ $form_data['id'] ][ $field_id ] = esc_attr( $message );
							} else {
								wpforms()->process->errors[ $form_data['id'] ][ $field_id ] = esc_attr( $response_array['details'] );
							}
						}
					}
				}
			}
		}
		return;
	},
	10,
	3
);


/**
 * This hook fire after form submission.
 * To update email response after form submission because after submission entry id is genereated
 * At this stag get email response meta from `wp_tuxmlr_email_meta` table and then update each email response in `wp_tuxmalr_response_meta`table.
 */

add_action(
	'wpforms_process_complete',
	function ( $fields, $entry, $form_data, $entry_id ) {

		$wpforms_ids = get_option( 'tuxmlr_selected_wp_forms' );
		$wpforms_ids = ( ! empty( $wpforms_ids ) ) ? $wpforms_ids : array();
		$wpforms_ids = map_deep( $wpforms_ids, 'intval' );
		if ( in_array( intval( $form_data['id'] ), $wpforms_ids, true ) ) {
			// Check if form has entries disabled.
			if ( ! isset( $form_data['settings']['disable_entries'] ) ) {
				foreach ( $fields as $field ) {
					if ( 'email' === strval( $field['type'] ) ) {
						$email = $field['value'];
						if ( is_email( $email ) ) {
							$response = tuxmlr_get_email_meta( $email );
							tuxmlr_response_meta( 'wpforms', $form_data['id'], $entry_id, $response, $email );
						}
					}
				}
			}
		}
	},
	10,
	4
);



/**
 * To return all forms for dropdown i.e tuxmailler setting page.
 *
 * @return array
 */
function tuxmlr_get_wp_form_title() {
	$args        = array(
		'post_type'   => 'wpforms',
		'post_status' => 'publish',
	);
	$posts       = wpforms()->form->get( '', $args );
	$forms_title = wp_list_pluck( $posts, 'post_title', 'ID' );

	return $forms_title;
}


/**
 * To update all old wpforms forms entries for selected forms from wpforms database to `wp_tuxmalr_response_meta` i.e initially
 *
 * @param array $form_ids .
 */
function tuxmlr_get_all_wpforms_entries( $form_ids ) {
	global $wpdb;
	$tuxmalr_response_meta_table = $wpdb->prefix . 'tuxmalr_response_meta';
	$response_array              = null;
	foreach ( $form_ids as $form_id ) {
		$entries      = wpforms()->entry->get_entries( array( 'form_id' => $form_id ) );
		$entries_list = wp_list_pluck( $entries, 'entry_id' );

		if ( ! empty( $entries_list ) ) {
			$tux_meta = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT entry_id FROM {$tuxmalr_response_meta_table} WHERE plugin_name=%s AND form_id=%d ", 'wpforms', $form_id ) ); // phpcs:ignore: unprepared SQL ok.

			$final_entries_list = array_diff( $entries_list, $tux_meta );
			if ( ! empty( $final_entries_list ) ) {

				foreach ( $final_entries_list as $entry_id ) {
					$entry        = wpforms()->entry->get( absint( $entry_id ) );
					$entry_fields = json_decode( $entry->fields, true );
					foreach ( $entry_fields as $entry_field ) {
						if ( 'email' === strval( $entry_field['type'] ) ) {
							$email = $entry_field['value'];
							if ( is_email( $email ) ) {
								tuxmlr_add_meta( 'wpforms', $form_id, $entry_id, $response_array, $email );
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
 * @param array $entries .
 * @param int   $form_id .
 * @return array
 */
function tuxmlr_wpforms_forms_anonymous_data( $entries, $form_id ) {
	$data = array();
	foreach ( $entries as $entry_id ) {
		$entry        = wpforms()->entry->get( absint( $entry_id ) );
		$entry_fields = json_decode( $entry->fields, true );
		foreach ( $entry_fields as $field ) {
			$field_type = $field['type'];
			if ( 'recaptcha' === $field_type ) {
				continue;
			} else {
				$data[ $entry_id ][ $field['name'] ] = $field['value'];
			}
		}
	}
	return $data;
}


/**
 * To return only selected forms for ajax dropdown in bulk view page.
 *
 * @return array
 */
function tuxmlr_get_wpforms_ids_settings() {
	$selected_form = array();
	$wpforms_ids   = get_option( 'tuxmlr_selected_wp_forms' );

	if ( ! empty( $wpforms_ids ) ) {
		foreach ( $wpforms_ids as $form_id ) {
			$form                      = wpforms()->form->get( $form_id );
			$selected_form[ $form_id ] = $form->post_title;
		}
	}
	return $selected_form;
}
