<?php
/**
 * This hook fire on form submit
 * Validation of email using tux Single email validate api before submission.
 */
add_filter(
	'gform_field_validation',
	function ( $result, $value, $form, $field ) {

		$gform_ids = get_option( 'tuxmlr_selected_gravity_forms' );
		$gform_ids = ( ! empty( $gform_ids ) ) ? $gform_ids : array();
		$gform_ids = map_deep( $gform_ids, 'intval' );
		if ( in_array( intval( $form['id'] ), $gform_ids, true ) ) {
			if ( $field->get_input_type() === 'email' && $result['is_valid'] ) {
				$email = $value;

				if ( is_array( $email ) ) {
					$email = $email[0];
				}

				if ( is_email( $email ) ) {
					$response_array = tuxmlr_single_api_call( $email );

					tuxmlr_update_email_meta( $email, $response_array );

					if ( is_array( $response_array ) && ! empty( $response_array ) ) {
						if ( 200 === intval( $response_array['response_code'] ) ) {
							if ( 1 !== intval( $response_array['valid_address'] ) && 'unknown' !== strval( $response_array['status'] ) ) {
								$result['is_valid'] = false;
								$message            = get_option( 'tuxmlr_custom_error_message' ); // To get custom error message from settings page.
								if ( ! empty( $message ) ) {
									$result['message'] = esc_attr( $message );
								} else {
									$result['message'] = esc_attr( $response_array['details'] );
								}
							}
						}
					}
				}
			}
		}
		return $result;
	},
	10,
	4
);

/**
 * This hook fire after form Submission
 * To update email response after form submission because after submission entry id is genereated
 * At this stag get email response meta from `wp_tuxmlr_email_meta` table and then update each email response in `wp_tuxmalr_response_meta`table.
 */
add_action(
	'gform_after_submission',
	function ( $entry, $form ) {
		$form_id = $form['id'];

		$gform_ids = get_option( 'tuxmlr_selected_gravity_forms' );
		$gform_ids = ( ! empty( $gform_ids ) ) ? $gform_ids : array();
		$gform_ids = map_deep( $gform_ids, 'intval' );
		if ( in_array( intval( $form_id ), $gform_ids, true ) ) {
			$entry_id = $entry['id'];

			foreach ( $form['fields'] as $field ) {
				if ( 'email' === $field->type ) {
					$email = rgar( $entry, $field->id );
					if ( is_email( $email ) ) {
						$response = tuxmlr_get_email_meta( $email );
						tuxmlr_response_meta( 'gravityforms', $form_id, $entry_id, $response, $email );
					}
				}
			}
		}
	},
	10,
	2
);


/**
 * This function return all forms title and Id for dropdown in tuxmailler setting page.
 *
 * @return array
 */
function tuxmlr_get_gravity_form_title() {

	$forms       = GFAPI::get_forms( true, false, 'id', 'ASC' );
	$forms_title = wp_list_pluck( $forms, 'title', 'id' );

	return $forms_title;
}


/**
 * To return only selected forms for ajax dropdown in bulk view page.
 *
 * @return array
 */
function tuxmlr_get_gravityform_settings_ids_settings() {
	$selected_form = array();
	$gform_ids     = get_option( 'tuxmlr_selected_gravity_forms' );
	if ( ! empty( $gform_ids ) ) {
		foreach ( $gform_ids as $form_id ) {
			$form                      = GFAPI::get_form( $form_id );
			$selected_form[ $form_id ] = $form['title'];
		}
	}
	return $selected_form;
}


/**
 * To update all old gravityforms forms entries for selected forms from gravity database to `wp_tuxmalr_response_meta` i.e initially
 *
 * @param array $form_ids .
 */
function tuxmlr_get_all_gravityforms_entries( $form_ids ) {
	global $wpdb;
	$tuxmalr_response_meta_table = $wpdb->prefix . 'tuxmalr_response_meta';
	$response_array              = null;
	$search_criteria['status']   = 'active';

	foreach ( $form_ids as $form_id ) {
		$entries_list = GFAPI::get_entry_ids( $form_id, $search_criteria );

		$tux_meta = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT entry_id FROM {$tuxmalr_response_meta_table} WHERE plugin_name=%s AND form_id=%d ", 'gravityforms', $form_id ) ); // phpcs:ignore: unprepared SQL ok.

		$final_entries_list = array_diff( $entries_list, $tux_meta );

		if ( ! empty( $final_entries_list ) ) {

			$form = GFAPI::get_form( $form_id );
			foreach ( $final_entries_list as $entry_id ) {
				$entry = GFAPI::get_entry( $entry_id );
				foreach ( $form['fields'] as $field ) {
					if ( 'email' === strval( $field->type ) ) {
						$email = rgar( $entry, $field->id );
						if ( is_email( $email ) ) {
							tuxmlr_add_meta( 'gravityforms', $form_id, $entry_id, $response_array, $email );
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
function tuxmlr_gravity_forms_anonymous_data( $entries, $form_id ) {
	$form           = GFAPI::get_form( $form_id );
	$data           = array();
	$discard_fields = array( 'section', 'html', 'recaptcha' );
	foreach ( $entries as $entry_id ) {
		$entry = GFAPI::get_entry( $entry_id );

		foreach ( $form['fields'] as $field ) {
			$field_type = $field->type;
			if ( ! in_array( $field_type, $discard_fields, true ) ) {
				$value = $field->get_value_export( $entry );
				$label = $field['label'];
				if ( '' !== $label ) {
					$data[ $entry_id ][ $label ] = $value;
				} else {
					$placeholder                       = $field->placeholder;
					$data[ $entry_id ][ $placeholder ] = $value;
				}
			}
		}
	}
	return $data;
}

