<?php

/**
 * This hook fire on form submit
 * To validate email by tux single email validate API and then update its response meta to `wp_tuxmlr_email_meta` table.
 *
 * @param array $form_data .
 * @return array $form_data .
 */
function tuxmlr_ninja_forms_submit_data( $form_data ) {

	$ninja_forms_ids = get_option( 'tuxmlr_selected_ninja_forms' );
	$ninja_forms_ids = ( ! empty( $ninja_forms_ids ) ) ? $ninja_forms_ids : array();
	$ninja_forms_ids = map_deep( $ninja_forms_ids, 'intval' );
	if ( in_array( intval( $form_data['id'] ), $ninja_forms_ids, true ) ) {
		foreach ( $form_data['fields'] as $field ) { // Field settigns, including the field key and value.

			$value = $field['value'];
			if ( preg_match( '/@.+\./', $value ) && is_email( $value ) ) {

				$email    = $value;
				$field_id = $field['id'];
				if ( isset( $form_data['errors']['fields'][ $field_id ] ) ) {
					$form_data['errors']['fields'][ $field_id ] = null;
				}
				$response_array = tuxmlr_single_api_call( $email );
				tuxmlr_update_email_meta( $email, $response_array );

				if ( is_array( $response_array ) && ! empty( $response_array ) ) {

					if ( 200 === intval( $response_array['response_code'] ) ) {
						if ( 1 !== intval( $response_array['valid_address'] ) && 'unknown' !== strval( $response_array['status'] ) ) {
							$message = get_option( 'tuxmlr_custom_error_message' ); // To get custom error message from settings page.
							if ( ! empty( $message ) ) {
								$form_data['errors']['fields'][ $field_id ] = esc_attr( $message );
							} else {
								$form_data['errors']['fields'][ $field_id ] = esc_attr( $response_array['details'] );
							}
						}
					}
				}
			}
		}
	}
	return $form_data;
}
add_filter( 'ninja_forms_submit_data', 'tuxmlr_ninja_forms_submit_data', 10, 1 );

/**
 * This hook fire after form Submission
 * To update email response after form submission because after submission entry id is genereated
 * At this stag get email response meta from `wp_tuxmlr_email_meta` table and then update each email response in `wp_tuxmalr_response_meta`table.
 */

add_action(
	'ninja_forms_after_submission',
	function ( $form_data ) {
		$ninja_forms_ids = get_option( 'tuxmlr_selected_ninja_forms' );
		$ninja_forms_ids = ( ! empty( $ninja_forms_ids ) ) ? $ninja_forms_ids : array();
		$ninja_forms_ids = map_deep( $ninja_forms_ids, 'intval' );
		$form_id         = intval( $form_data['form_id'] );
		if ( in_array( $form_id, $ninja_forms_ids, true ) ) {

			if ( isset( $form_data['actions']['save'] ) ) {

				$entry_id = $form_data['actions']['save']['sub_id'];

				foreach ( $form_data['fields'] as $field ) { // Field settigns, including the field key and value.
					$value = $field['value'];
					if ( preg_match( '/@.+\./', $value ) && is_email( $value ) ) {
						$response_array = tuxmlr_get_email_meta( $value );

						tuxmlr_response_meta( 'ninja-forms', $form_id, $entry_id, $response_array, $value );
					}
				}
			}
		}
	},
	10,
	1
);




/**
 * To return all forms title and Id for dropdown in tuxmailler setting page
 *
 * @return array
 */
function tuxmlr_get_ninja_form_title() {
	$forms       = Ninja_Forms()->form()->get_forms();
	$forms_title = array();
	foreach ( $forms as $form ) {
		$form_id                 = $form->get_id();
		$form_name               = $form->get_setting( 'title' );
		$forms_title[ $form_id ] = $form_name;
	}
	return $forms_title;
}



/**
 * To return only selected forms for ajax dropdown in bulk view page.
 *
 * @return array
 */
function tuxmlr_get_ninja_form_settings_ids_settings() {
	$selected_form      = array();
	$ninja_forms_ids    = get_option( 'tuxmlr_selected_ninja_forms' );
	$ninja_form_details = tuxmlr_get_ninja_form_title();

	if ( ! empty( $ninja_forms_ids ) ) {
		foreach ( $ninja_forms_ids as $form_id ) {
			$selected_form[ $form_id ] = $ninja_form_details[ $form_id ];
		}
	}
	return $selected_form;
}


/**
 * This function update all old ninja_forms forms entries for selected forms from ninja_forms database to `wp_tuxmalr_response_meta`.
 *
 * @param array $form_ids .
 */
function tuxmlr_get_all_ninja_forms_ids_entries( $form_ids ) {
	global $wpdb;
	$tuxmalr_response_meta_table = $wpdb->prefix . 'tuxmalr_response_meta';
	$response_array              = null;
	foreach ( $form_ids as $form_id ) {
		$entries      = Ninja_Forms()->form( $form_id )->get_subs();
		$entries_list = array();

		foreach ( $entries as $entrie ) {
			array_push( $entries_list, $entrie->get_id() );
		}

		if ( ! empty( $entries_list ) ) {
			$tux_meta = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT entry_id FROM {$tuxmalr_response_meta_table} WHERE plugin_name=%s AND form_id=%d ", 'ninja-forms', $form_id ) ); // phpcs:ignore: unprepared SQL ok.

			$final_entries_list = array_diff( $entries_list, $tux_meta );
			if ( ! empty( $final_entries_list ) ) {
				$fields = Ninja_Forms()->form( $form_id )->get_fields();

				foreach ( $final_entries_list as $entry_id ) {
					$submission = Ninja_Forms()->form()->get_sub( $entry_id );
					foreach ( $fields as $field ) {
						$values = $submission->get_field_value( $field->get_id() );

						if ( is_email( $values ) ) {
							$email = $values;
							tuxmlr_add_meta( 'ninja-forms', $form_id, $entry_id, $response_array, $email );
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
function tuxmlr_ninja_forms_anonymous_data( $entries, $form_id ) {
	$fields         = Ninja_Forms()->form( $form_id )->get_fields();
	$discard_fields = array( 'confirm', 'submit', 'html', 'recaptcha', 'spam', 'hr', 'repeater' );
	$data           = array();
	foreach ( $entries as $entry_id ) {
		$submission = Ninja_Forms()->form()->get_sub( $entry_id );
		foreach ( $fields as $field ) {
			$field_type = $field->get_setting( 'type' );
			if ( ! in_array( $field_type, $discard_fields, true ) ) {
				$values = $submission->get_field_value( $field->get_id() );
				$label  = $field->get_setting( 'label' );
				if ( '' !== $label ) {
					$data[ $entry_id ][ $label ] = $values;
				} else {
					$placeholder                       = $field->get_setting( 'placeholder' );
					$data[ $entry_id ][ $placeholder ] = $values;
				}
			}
		}
	}

	return $data;
}
