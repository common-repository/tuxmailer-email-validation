<?php
/**
 * Validation of email field on submission with tux single `/v1/user/validate/email` API call.
 * After reciving email response, using `tuxmlr_update_email_meta` function update `wp_tuxmlr_email_meta` table.
 */
add_filter(
	'frm_validate_email_field_entry',
	function ( $errors, $field, $field_value ) {

		$formidableforms_ids = get_option( 'tuxmlr_selected_formidable_forms' );
		$formidableforms_ids = ( ! empty( $formidableforms_ids ) ) ? $formidableforms_ids : array();
		$formidableforms_ids = map_deep( $formidableforms_ids, 'intval' );
		if ( in_array( intval( $field->form_id ), $formidableforms_ids, true ) ) {
			if ( 'email' === $field->type && empty( $errors ) ) {
				if ( is_array( $field_value ) ) {
					$field_value = $field_value[0];
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
									$errors[ 'field' . $field->id ] = esc_attr( $message );
								} else {
									$errors[ 'field' . $field->id ] = esc_attr( $response_array['details'] );
								}
							}
						}
					}
				}
			}
		}

		return $errors;
	},
	10,
	3
);


/**
 * To update email response after form submission because after submission entry id is genereated
 * At this stag get email response meta from `wp_tuxmlr_email_meta` table and then update each email response in `wp_tuxmalr_response_meta`table.
 */
add_action(
	'frm_after_create_entry',
	function ( $entry_id, $form_id ) {
		$formidableforms_ids = get_option( 'tuxmlr_selected_formidable_forms' );
		$formidableforms_ids = ( ! empty( $formidableforms_ids ) ) ? $formidableforms_ids : array();
		$formidableforms_ids = map_deep( $formidableforms_ids, 'intval' );
		if ( in_array( intval( $form_id ), $formidableforms_ids, true ) ) {
			$form = FrmForm::getOne( $form_id );
			if ( $form && isset( $form->options['no_save'] ) && ( 0 === intval( $form->options['no_save'] ) ) ) {
				$entry  = FrmEntry::getOne( $entry_id, true );
				$fields = FrmField::get_all_for_form( $form_id, '', 'include' );
				foreach ( $fields as $field ) {
					if ( 'email' === $field->type ) {
						$email = $entry->metas[ $field->id ];
						if ( is_array( $email ) ) {                   // To found any option for Confirmation Field email.
							$email = $email[0];
						}
						if ( is_email( $email ) ) {
							$response = tuxmlr_get_email_meta( $email );
							tuxmlr_response_meta( 'formidable', $form_id, $entry_id, $response, $email );
						}
					}
				}
			}
		}

	},
	10,
	2
);


/**
 * This function returs all formidable form title and its form_Id as associative array
 *
 * @return array
 */
function tuxmlr_get_formidable_form_title() {
	$forms       = FrmForm::get_published_forms();
	$forms_title = wp_list_pluck( $forms, 'name', 'id' );

	return $forms_title;
}



/**
 * To return only selected forms for ajax dropdown in bulk view page.
 *
 * @return array
 */
function tuxmlr_get_formidable_form_settings_ids_settings() {
	$selected_form       = array();
	$formidableforms_ids = get_option( 'tuxmlr_selected_formidable_forms' );

	if ( ! empty( $formidableforms_ids ) ) {
		foreach ( $formidableforms_ids as $form_id ) {
			$form                      = FrmForm::getOne( $form_id );
			$selected_form[ $form_id ] = $form->name;
		}
	}
	return $selected_form;
}


/**
 * To update all old formidable forms entries for selected forms from formidable database to `wp_tuxmalr_response_meta`.
 *
 * @param array $form_ids .
 */
function tuxmlr_get_all_formidable_forms_entries( $form_ids ) {
	global $wpdb;
	$tuxmalr_response_meta_table = $wpdb->prefix . 'tuxmalr_response_meta';
	$response_array              = null;
	foreach ( $form_ids as $form_id ) {
		$entries      = FrmEntry::getAll( array( 'it.form_id' => $form_id ) );
		$entries_list = wp_list_pluck( $entries, 'id' );

		if ( ! empty( $entries_list ) ) {
			$tux_meta           = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT entry_id FROM {$tuxmalr_response_meta_table} WHERE plugin_name=%s AND form_id=%d ", 'formidable', $form_id ) ); // phpcs:ignore: unprepared SQL ok.
			$final_entries_list = array_diff( $entries_list, $tux_meta );
			if ( ! empty( $final_entries_list ) ) {
				foreach ( $final_entries_list as $entry_id ) {
					$entry  = FrmEntry::getOne( $entry_id, true );
					$fields = FrmField::get_all_for_form( $form_id, '', 'include' );
					foreach ( $fields as $field ) {
						if ( 'email' == $field->type ) {
							$email = $entry->metas[ $field->id ];
							if ( is_email( $email ) ) {
								tuxmlr_add_meta( 'formidable', $form_id, $entry_id, $response_array, $email );
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
function tuxmlr_formidable_forms_anonymous_data( $entries, $form_id ) {
	$data = array();
	foreach ( $entries as $entry_id ) {
		$entry  = FrmEntry::getOne( $entry_id, true );
		$fields = FrmField::get_all_for_form( $form_id, '', 'include' );
		foreach ( $fields as $field ) {
			$field_type = $field->type;

			if ( 'hidden' === $field_type || 'captcha' === $field_type || 'html' === $field_type ) {
				continue;
			} else {
				$field_value = $entry->metas[ $field->id ];

				if ( is_array( $field_value ) ) {
					if ( 'email' === $field_type ) {
						$field_value = $field_value[0];
					} else {
						$field_value = implode( ' ', $field_value );
					}
				}
				$label = $field->name;
				if ( '' !== strval( $label ) ) {
					$data[ $entry_id ][ $label ] = $field_value;
				} else {
					$placeholder                       = FrmField::get_option( $field, 'placeholder' );
					$data[ $entry_id ][ $placeholder ] = $field_value;
				}
			}
		}
	}
	return $data;
}

