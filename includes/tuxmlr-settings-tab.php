<?php
/**
 * To add a Tuxmailer menu page.
 *
 * @return void
 */
function tuxmlr_email_api_integration_menu_page() {
	add_menu_page(
		__( 'TuxMailer Settings', 'tuxmailer-email-validation' ),
		__( 'TuxMailer', 'tuxmailer-email-validation' ),
		'manage_options',
		'tuxmailer-options-page',
		'tuxmlr_api_integration_settings_section',
		esc_url( plugins_url( 'assets/images/tuxmailer-logo-greyscale.svg', dirname( __FILE__ ) ) )
	);
}
add_action( 'admin_menu', 'tuxmlr_email_api_integration_menu_page' );

/**
 * To add a Tuxmailer menu page.
 *
 * @return void
 */
function tuxmlr_email_api_integration_sub_menu_page() {
	add_submenu_page(
		'tuxmailer-options-page',
		'TuxMailer Settings',
		'Settings',
		'manage_options',
		'tuxmailer-options-page',
		'tuxmlr_api_integration_settings_section'
	);
}
add_action( 'admin_menu', 'tuxmlr_email_api_integration_sub_menu_page' );



/**
 * Function call for add menu .
 */
function tuxmlr_api_integration_settings_section() {

	$tuxmailer_credits = tuxmlr_get_credits_remaining();
	?>
	<div class="pluginHeadingwrap">
		<section class="pluginHeader">
			<div>
				<img src="<?php echo esc_url( plugins_url( 'assets/images/tuxmailer-logo-dark.svg', dirname( __FILE__ ) ) ); ?>" alt="">
			</div>
			<div class="headerRight">
				<ul>
					<li id="tuxmailer-credits"><?php echo esc_html_e( 'Credits', 'tuxmailer-email-validation' ); ?>: <i><?php echo esc_attr( number_format_i18n( $tuxmailer_credits ) ); ?></i></li>
					<li><a href="<?php echo esc_url( TUXMAILER_SUBCRIPTION_PLANS ); ?>" target="_blank"><?php echo esc_html_e( 'Buy More', 'tuxmailer-email-validation' ); ?></a></li>
				</ul>
				<a href="<?php echo esc_url( TUXMAILER_HELP ); ?>" target="_blank"><img src="<?php echo esc_url( plugins_url( 'assets/images/questionmark.png', dirname( __FILE__ ) ) ); ?>" alt=""></a>
			</div>
		</section>

		<main>
			<?php

			tuxmlr_validation_processing_notice();

			echo '<h2>' . esc_html( __( 'Email Validation Settings', 'tuxmailer-email-validation' ) ) . '</h2>';
			?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'tuxmlr-field-setting' );
				do_settings_sections( 'tuxmailer-settings-options' );

				$other_attributes = array( 'id' => 'tuxmailer-submit-button' );
				submit_button( __( 'Save Changes', 'tuxmailer-email-validation' ), 'primary', 'tuxmailer-button', true, $other_attributes );
				?>
			</form>
		</main>
	</div>
	<?php

	echo '<br>';
	tuxmlr_sync_previous_entries();
}

/**
 * To return credit remaining for selected Account Type.
 *
 * @return string credits_remaining.
 */
function tuxmlr_get_credits_remaining() {
	$credits_remaining = 0;

	$token = get_option( 'tuxmlr_api_key' );
	if ( '' === $token ) {
		update_option(
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
		);
		return $credits_remaining;
	}

	$selected_account = get_option( 'tuxmlr_account_type' );
	if ( 'team_account' === $selected_account ) {
		$teams = tuxmlr_get_term_credits();
		if ( ! empty( $teams ) ) {
			$teams_name = get_option( 'tuxmlr_team_account' );
			if ( '-1' === strval( $teams_name ) ) {
				return $credits_remaining;
			} elseif ( 200 === intval( $teams['response_code'] ) ) {
				$credits_remaining = $teams[ $teams_name ]['term_credits'];
			}
		}
	} else {
		$key               = null;
		$value             = null;
		$credits_remaining = tuxmlr_get_api_balance( $key, $value );
	}
	return $credits_remaining;
}


/**
 * To display To get API Key Click Here for instruction.
 *
 * @return void
 */
function tuxmlr_get_api_key() {
	?>
	<input type="password" class= "tuxmailer-token-invalid" data-response-code = "07" name="tuxmlr_api_key" id="tuxmlr_api_key" value="<?php echo esc_attr( get_option( 'tuxmlr_api_key' ) ); ?>" />  
	<input type="button" name="tuxmailer_validate_api_key" id="tuxmailer_validate_api_key" value="<?php echo esc_html_e( 'Validate API Key', 'tuxmailer-email-validation' ); ?>" /> 
	<p>
	<?php

	$click_here = sprintf(
		wp_kses( /* translators: %s - plugin settings page URL. */
			__( 'You can create/retrieve your API key from <a href="%1$s" target="_blank">here</a>. Please check our <a href="%2$s" target="_blank">docs</a> for additional information.', 'tuxmailer-email-validation' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
			)
		),
		esc_url( TUXMAILER_API_KEY_MANAGEMENT ),
		esc_url( TUXMAILER_DOCS )
	);
	echo wp_kses_post( $click_here );
	?>
	</p>
	<!-- <p id="show_message"></p> -->
	<?php
}

/**
 * To show select option for personal or team acccount.
 *
 * @return void
 */
function tuxmlr_get_account_type() {
	$default_account_type = empty( get_option( 'tuxmlr_account_type' ) ) ? 'checked' : '';
	?>
		<input type="radio" name="tuxmlr_account_type" id="personal_account" value="personal_account" <?php echo esc_attr( $default_account_type ); ?> <?php checked( 'personal_account', get_option( 'tuxmlr_account_type' ), true ); ?> ><?php echo esc_html_e( 'Personal Account', 'tuxmailer-email-validation' ); ?> 
		<input type="radio" name="tuxmlr_account_type" id="team_account" value="team_account" <?php checked( 'team_account', get_option( 'tuxmlr_account_type' ), true ); ?>><?php echo esc_html_e( 'Team Account', 'tuxmailer-email-validation' ); ?>
	<?php
}

/**
 * To populate all team account name in Select Team Account dropdown.
 *
 * @return void
 */
function tuxmlr_get_team_account() {
	$is_valid_token = get_option( 'tuxmlr_token_status' );
	if ( true === $is_valid_token['is_valid_token'] ) {
		$tuxmlr_team_details = tuxmlr_get_teams();
		update_option( 'tuxmlr_team_details', $tuxmlr_team_details, true );
	} else {
		$tuxmlr_team_details = array();
	}
	?>
	<select name = "tuxmlr_team_account" id = "tuxmlr_team_account"><option value = "-1">Select Team</option>
	<?php
	if ( ! empty( $tuxmlr_team_details ) ) {
		foreach ( $tuxmlr_team_details as $key => $value ) {

			?>
			<option id = "<?php echo esc_attr( $key ); ?>" value = "<?php echo esc_attr( $value ); ?>" <?php selected( get_option( 'tuxmlr_team_account' ), $value ); ?> ><?php echo esc_attr( $value ); ?></option>
			<?php

		}
	}
	?>
	</select>
	<?php
}

/**
 * To populate all ninja-forms Forms in settings page.
 *
 * @return void
 */
function tuxmlr_get_ninja_form_settings() {
	$active_plugins = get_option( 'active_plugins' );
	if ( in_array( 'ninja-forms/ninja-forms.php', $active_plugins, true ) ) {
		$ninja_form_details   = tuxmlr_get_ninja_form_title();
		$selected_ninja_forms = ! empty( get_option( 'tuxmlr_selected_ninja_forms' ) ) ? get_option( 'tuxmlr_selected_ninja_forms' ) : array();
		$selected_ninja_forms = map_deep( $selected_ninja_forms, 'intval' );
		?>
		<select name = "tuxmlr_selected_ninja_forms[]" id = "tuxmlr_selected_ninja_forms" multiple="multiple">
		<?php
		foreach ( $ninja_form_details as $key => $value ) {
			$selected = in_array( $key, $selected_ninja_forms, true ) ? ' selected' : '';
			?>
				<option value = "<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $value ); ?></option>
				<?php
		}
		?>
			</select>		
		<?php
	}
}


/**
 * To populate all WPForms in settings page.
 *
 * @return void
 */
function tuxmlr_get_wp_form_settings() {
	$active_plugins = get_option( 'active_plugins' );
	if ( in_array( 'wpforms/wpforms.php', $active_plugins, true ) ) {
		$wp_form_details   = tuxmlr_get_wp_form_title();
		$selected_wp_forms = ! empty( get_option( 'tuxmlr_selected_wp_forms' ) ) ? get_option( 'tuxmlr_selected_wp_forms' ) : array();
		$selected_wp_forms = map_deep( $selected_wp_forms, 'intval' );
		?>
		<select name = "tuxmlr_selected_wp_forms[]" id = "tuxmlr_selected_wp_forms" multiple="multiple">
		<?php

		foreach ( $wp_form_details as $key => $value ) {
			$selected = in_array( $key, $selected_wp_forms, true ) ? ' selected' : '';
			?>
			<option  value = "<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $selected ); ?> ><?php echo esc_attr( $value ); ?></option>
			<?php
		}
		?>
		</select>
		<?php
	}
}

/**
 * To populate all Gravityforms forms in settings page.
 *
 * @return void
 */
function tuxmlr_get_gravityform_settings() {
	$active_plugins = get_option( 'active_plugins' );
	if ( in_array( 'gravityforms/gravityforms.php', $active_plugins, true ) ) {
		$gravity_form_details   = tuxmlr_get_gravity_form_title();
		$selected_gravity_forms = ! empty( get_option( 'tuxmlr_selected_gravity_forms' ) ) ? get_option( 'tuxmlr_selected_gravity_forms' ) : array();
		$selected_gravity_forms = map_deep( $selected_gravity_forms, 'intval' );
		?>
		<select name = "tuxmlr_selected_gravity_forms[]" id = "tuxmlr_selected_gravity_forms" multiple="multiple">
		<?php
		foreach ( $gravity_form_details as $key => $value ) {
			$selected = in_array( $key, $selected_gravity_forms, true ) ? ' selected' : '';
			?>
			<option value = "<?php echo esc_attr( $key ); ?>" <?php echo wp_kses_post( $selected ); ?>><?php echo esc_attr( $value ); ?></option>
			<?php
		}
		?>
		</select>
		<?php
	}
}

/**
 * To populate all Formidable forms in settings page.
 *
 * @return void
 */
function tuxmlr_get_formidable_form_settings() {
	$active_plugins = get_option( 'active_plugins' );
	if ( in_array( 'formidable/formidable.php', $active_plugins, true ) ) {
		$formidable_form_details   = tuxmlr_get_formidable_form_title();
		$selected_formidable_forms = ! empty( get_option( 'tuxmlr_selected_formidable_forms' ) ) ? get_option( 'tuxmlr_selected_formidable_forms' ) : array();
		$selected_formidable_forms = map_deep( $selected_formidable_forms, 'intval' );
		?>
		<select name = "tuxmlr_selected_formidable_forms[]" id = "tuxmlr_selected_formidable_forms" multiple="multiple">
		<?php
		foreach ( $formidable_form_details as $key => $value ) {
			$selected = in_array( $key, $selected_formidable_forms, true ) ? ' selected' : '';
			?>
			<option value = "<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $value ); ?></option>
			<?php
		}
		?>
		</select>
		<?php
	}
}

/**
 * To populate all Contact form 7, forms in settings page.
 *
 * @return void
 */
function tuxmlr_get_cf7_settings() {
	$active_plugins = get_option( 'active_plugins' );
	if ( in_array( 'contact-form-7/wp-contact-form-7.php', $active_plugins, true ) && in_array( 'flamingo/flamingo.php', $active_plugins, true ) ) {
		$contact_form_7_details = tuxmlr_get_cf7_title();
		$selected_cf7           = ! empty( get_option( 'tuxmlr_selected_contact_forms' ) ) ? get_option( 'tuxmlr_selected_contact_forms' ) : array();
		$selected_cf7           = map_deep( $selected_cf7, 'intval' );
		?>
		<select name = "tuxmlr_selected_contact_forms[]" id = "tuxmlr_selected_contact_forms" multiple="multiple">
		<?php
		foreach ( $contact_form_7_details as $key => $value ) {
			$selected = in_array( $key, $selected_cf7, true ) ? ' selected' : '';
			?>
			<option value = "<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $value ); ?></option>
			<?php
		}
		?>
		</select>
		<?php
	}
}

/**
 * To get Custom error message for email validation.
 *
 * @return void
 */
function tuxmlr_custom_error_message() {
	$custom_message = ! empty( get_option( 'tuxmlr_custom_error_message' ) ) ? get_option( 'tuxmlr_custom_error_message' ) : '';
	?>
	<input type="text" class= "tuxmlr_custom_error_message" name="tuxmlr_custom_error_message" id="tuxmlr_custom_error_message" value="<?php echo esc_attr( $custom_message ); ?>" />  
	<p>Custom error message for all invalid email validation</p>
	<?php
}

/**
 * Anonymous data permission.
 *
 * @return void
 */
function tuxmlr_allow_anonymous_data() {
	?>
	<input type="checkbox" name="tuxmlr_anonymous_data" value="1" id="tuxmlr_anonymous_data" <?php checked( 1, get_option( 'tuxmlr_anonymous_data' ), true ); ?> />
	<label for="tuxmlr_anonymous_data">
	<?php
	echo sprintf(
		/* translators: 1: API Key URL */
		__( 'I agree to send my data to TuxMailer for validation and analytics. Please refer to our  <a href="%1$s" target="_blank">Privacy Policy</a> and <a href="%2$s" target="_blank">Terms of Service</a> for more information.', 'tuxmailer-email-validation' ),
		esc_url( TUXMAILER_PRIVACY_POLICY ),
		esc_url( TUXMAILER_TERMS_OF_SERVICE )
	);
	?>
		</label><br>
	<?php
}


/**
 * This function is called for all inactive or not install plugins to add "tuxmlr_select_deactivated_plugin" class.
 */
function tuxmlr_inactive_callback() {
	?>
		<select name = "tuxmlr_select_deactivated_plugin" class = "tuxmlr_select_deactivated_plugin" multiple="multiple">
		</select>
		<?php
}


/**
 * TuxMailer settings page.
 *
 * @return void
 */
function tuxmlr_display_theme_panel_field() {
	$active_plugins = get_option( 'active_plugins' );

	add_settings_section( 'tuxmailer-account-settings-group', '', null, 'tuxmailer-settings-options' );
	add_settings_section( 'tuxmailer-forms-settings-group', 'Add Email Validation on Forms', null, 'tuxmailer-settings-options' );

	add_settings_field( 'tuxmlr_api_key', esc_attr__( 'Enter API Key', 'tuxmailer-email-validation' ), 'tuxmlr_get_api_key', 'tuxmailer-settings-options', 'tuxmailer-account-settings-group' );
	add_settings_field( 'tuxmlr_account_type', esc_attr__( 'Select Account Type', 'tuxmailer-email-validation' ), 'tuxmlr_get_account_type', 'tuxmailer-settings-options', 'tuxmailer-account-settings-group' );
	add_settings_field( 'tuxmlr_team_account', esc_attr__( 'Select Team Account', 'tuxmailer-email-validation' ), 'tuxmlr_get_team_account', 'tuxmailer-settings-options', 'tuxmailer-account-settings-group' );

	if ( in_array( 'gravityforms/gravityforms.php', $active_plugins ) ) { // phpcs:ignore
		add_settings_field( 'tuxmlr_selected_gravity_forms', esc_attr__( 'Gravity Forms', 'tuxmailer-email-validation' ), 'tuxmlr_get_gravityform_settings', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group', array( 'class' => 'tuxmlr-active-plugin' ) );
	} else {
		add_settings_field( 'tuxmlr_selected_gravity_forms', esc_attr__( 'Gravity Forms', 'tuxmailer-email-validation' ), 'tuxmlr_inactive_callback', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group', array( 'class' => 'tuxmlr-inactive-plugin' ) );
	}

	if ( in_array( 'wpforms/wpforms.php', $active_plugins ) ) { // phpcs:ignore
		add_settings_field( 'tuxmlr_selected_wp_forms', esc_attr__( 'WPForms Pro', 'tuxmailer-email-validation' ), 'tuxmlr_get_wp_form_settings', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group', array( 'class' => 'tuxmlr-active-plugin' ) );
	} else {
		add_settings_field( 'tuxmlr_selected_wp_forms', esc_attr__( 'WPForms Pro', 'tuxmailer-email-validation' ), 'tuxmlr_inactive_callback', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group', array( 'class' => 'tuxmlr-inactive-plugin' ) );
	}

	if ( in_array( 'formidable/formidable.php', $active_plugins ) ) { // phpcs:ignore
		add_settings_field( 'tuxmlr_selected_formidable_forms', esc_attr__( 'Formidable Forms', 'tuxmailer-email-validation' ), 'tuxmlr_get_formidable_form_settings', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group', array( 'class' => 'tuxmlr-active-plugin' ) );
	} else {
		add_settings_field( 'tuxmlr_selected_formidable_forms', esc_attr__( 'Formidable Forms', 'tuxmailer-email-validation' ), 'tuxmlr_inactive_callback', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group', array( 'class' => 'tuxmlr-inactive-plugin' ) );
	}

	if ( in_array( 'ninja-forms/ninja-forms.php', $active_plugins ) ) { // phpcs:ignore
		add_settings_field( 'tuxmlr_selected_ninja_forms', esc_attr__( 'Ninja Forms', 'tuxmailer-email-validation' ), 'tuxmlr_get_ninja_form_settings', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group', array( 'class' => 'tuxmlr-active-plugin' ) );
	} else {
		add_settings_field( 'tuxmlr_selected_ninja_forms', esc_attr__( 'Ninja Forms', 'tuxmailer-email-validation' ), 'tuxmlr_inactive_callback', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group', array( 'class' => 'tuxmlr-inactive-plugin' ) );

	}
	if ( in_array( 'contact-form-7/wp-contact-form-7.php', $active_plugins ) && in_array( 'flamingo/flamingo.php', $active_plugins ) ) { // phpcs:ignore
		add_settings_field( 'tuxmlr_selected_contact_forms', esc_attr__( 'Contact Form 7', 'tuxmailer-email-validation' ), 'tuxmlr_get_cf7_settings', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group', array( 'class' => 'tuxmlr-active-plugin' ) );
	} else {
		add_settings_field( 'tuxmlr_selected_contact_forms', esc_attr__( 'Contact Form 7', 'tuxmailer-email-validation' ), 'tuxmlr_inactive_callback', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group', array( 'class' => 'tuxmlr-inactive-plugin' ) );
	}

	add_settings_field( 'tuxmlr_custom_error_message', esc_attr__( 'Custom Message', 'tuxmailer-email-validation' ), 'tuxmlr_custom_error_message', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group' );
	add_settings_field( 'tuxmlr_anonymous_data', esc_attr__( 'Data Transfer', 'tuxmailer-email-validation' ), 'tuxmlr_allow_anonymous_data', 'tuxmailer-settings-options', 'tuxmailer-forms-settings-group' );

	register_setting( 'tuxmlr-field-setting', 'tuxmlr_api_key' );
	register_setting( 'tuxmlr-field-setting', 'tuxmlr_account_type' );
	register_setting( 'tuxmlr-field-setting', 'tuxmlr_team_account' );
	register_setting( 'tuxmlr-field-setting', 'tuxmlr_selected_ninja_forms' );
	register_setting( 'tuxmlr-field-setting', 'tuxmlr_selected_wp_forms' );
	register_setting( 'tuxmlr-field-setting', 'tuxmlr_selected_gravity_forms' );
	register_setting( 'tuxmlr-field-setting', 'tuxmlr_selected_formidable_forms' );
	register_setting( 'tuxmlr-field-setting', 'tuxmlr_selected_contact_forms' );
	register_setting( 'tuxmlr-field-setting', 'tuxmlr_custom_error_message' );
	register_setting( 'tuxmlr-field-setting', 'tuxmlr_anonymous_data' );
}

add_action( 'admin_init', 'tuxmlr_display_theme_panel_field' );



/**
 * Show 'Settings' action links on the plugin screen.
 *
 * @param mixed $links Plugin Action links.
 *
 * @return array
 */
function tuxmlr_settings_plugin_action_links( $links ) {
	$action_links = array(
		'settings' => '<a href="' . admin_url( 'admin.php?page=tuxmailer-options-page' ) . '" aria-label="' . esc_attr__( 'View TuxMailer settings', 'tuxmailer-email-validation' ) . '">' . esc_html__( 'Settings', 'tuxmailer-email-validation' ) . '</a>',
	);
	return array_merge( $action_links, $links );
}

add_filter( 'plugin_action_links_' . TUXMAILER_PLUGIN_BASENAME, 'tuxmlr_settings_plugin_action_links', 10, 1 );





/**
 * Show row meta on the plugin screen.
 *
 * @param mixed $links Plugin Row Meta.
 * @param mixed $file  Plugin Base file.
 *
 * @return array
 */
function tuxmlr_plugin_row_meta( $links, $file ) {
	if ( TUXMAILER_PLUGIN_BASENAME !== $file ) {
		return $links;
	}

	$row_meta = array(
		'docs' => '<a href="' . esc_url( TUXMAILER_DOCS ) . '" aria-label="' . esc_attr__( 'View TuxMailer documentation', 'tuxmailer-email-validation' ) . '">' . esc_html__( 'Docs', 'tuxmailer-email-validation' ) . '</a>',
	);

	return array_merge( $links, $row_meta );
}

add_filter( 'plugin_row_meta', 'tuxmlr_plugin_row_meta', 10, 2 );

