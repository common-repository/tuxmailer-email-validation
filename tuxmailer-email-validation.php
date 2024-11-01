<?php
/*
 *  Plugin Name: TuxMailer Email Validation
 *  Description: TuxMailer email validation plugin integrates with popular form builders like CF7, Gravity Forms, Ninja Forms and more to provide you real-time email validation and bulk validation directly from WordPress.
 *  Author URI: https://tuxmailer.com/
 *  Author: TuxMailer
 *  Text Domain: tuxmailer-email-validation
 *  Domain Path: /languages
 *  Requires at least: 5.8
 *  Requires PHP: 7.0
 *  Version: 1.2
*/


define( 'TUXMAILER_ENTRIES_PER_PAGE', 50 );
define( 'TUXMAILER_MAX_EMAILS_PER_REQUEST', 500 );
define( 'TUXMAILER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'TUXMAILER_DOCS', 'https://docs.tuxmailer.com/integrations-guide' );
define( 'TUXMAILER_HELP', 'https://docs.tuxmailer.com/integrations-guide' );
define( 'TUXMAILER_API_KEY_MANAGEMENT', 'https://app.tuxmailer.com/email-validation/api-key-management' );
define( 'TUXMAILER_SUBCRIPTION_PLANS', 'https://app.tuxmailer.com/subscription/plans' );
define( 'TUXMAILER_DOWNLOAD', 'https://app.tuxmailer.com/email-validation/integrations' );
define( 'TUXMAILER_WELCOME_API_URL', 'https://integrations.tuxmailer.com/common/welcome' );
define( 'TUXMAILER_BULK_INTEGRATIONS_API_URL', 'https://integrations.tuxmailer.com/common/v1/user/validate/integrations?' );
define( 'TUXMAILER_GET_BULK_API_URL', 'https://integrations.tuxmailer.com/common/v1/user/bulk/logs?' );
define( 'TUXMAILER_SINGLE_EMAIL_API_URL', 'https://integrations.tuxmailer.com/common/v1/user/validate/email?' );
define( 'TUXMAILER_BALANCE_API_URL', 'https://integrations.tuxmailer.com/common/v1/user/email-verification/balance' );
define( 'TUXMAILER_TEAM_API_URL', 'https://integrations.tuxmailer.com/common/v1/teams' );
define( 'TUXMAILER_PRIVACY_POLICY', 'https://tuxmailer.com/privacy-policy/' );
define( 'TUXMAILER_TERMS_OF_SERVICE', 'https://tuxmailer.com/terms-of-service/' );
define( 'TUXMAILER_TWO_MINUTES_IN_SECONDS', 120 );

define( 'TUXMAILER_GRAVITYFORMS_URL', 'https://www.gravityforms.com' );
define( 'TUXMAILER_WPFORMS_PRO_URL', 'https://wpforms.com' );
define( 'TUXMAILER_FORMIDABLE_FORMS_URL', 'https://wordpress.org/plugins/formidable' );
define( 'TUXMAILER_NINJA_FORMS_URL', 'https://wordpress.org/plugins/ninja-forms' );
define( 'TUXMAILER_CONTACT_FORM_7_URL', 'https://wordpress.org/plugins/contact-form-7' );
define( 'TUXMAILER_FLAMINGO_URL', 'https://wordpress.org/plugins/flamingo' );

register_activation_hook( __FILE__, 'tuxmlr_email_validation_database_creation' );
register_deactivation_hook( __FILE__, 'tuxmlr_delete_admin_notification_options' );
require_once plugin_dir_path( __FILE__ ) . '/libraries/action-scheduler/action-scheduler.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';  // This file required for creating table while plugin is activated .

/**
 * To load tfi-plugin file only for active plugin.
 *
 * @return void
 */
function tuxmlr_include_file() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ( file_exists( WP_PLUGIN_DIR . '/gravityforms/gravityforms.php' ) ) && is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
		require_once 'includes/tuxmlr-gravity-forms.php';
	}

	if ( ( file_exists( WP_PLUGIN_DIR . '/wpforms/wpforms.php' ) ) && is_plugin_active( 'wpforms/wpforms.php' ) ) {
		require_once 'includes/tuxmlr-wpforms.php';
	}

	if ( ( file_exists( WP_PLUGIN_DIR . '/formidable/formidable.php' ) ) && is_plugin_active( 'formidable/formidable.php' ) ) {
		require_once 'includes/tuxmlr-formidable-forms.php';
	}
	if ( ( file_exists( WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php' ) ) && is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) && ( file_exists( WP_PLUGIN_DIR . '/flamingo/flamingo.php' ) ) && is_plugin_active( 'flamingo/flamingo.php' ) ) {
		require_once 'includes/tuxmlr-contact-form7.php';
	}
	if ( ( file_exists( WP_PLUGIN_DIR . '/ninja-forms/ninja-forms.php' ) ) && is_plugin_active( 'ninja-forms/ninja-forms.php' ) ) {
		require_once 'includes/tuxmlr-ninja-forms.php';
	}

	require_once 'includes/tuxmlr-settings-tab.php';
	require_once 'includes/tuxmlr-functions.php';
	require_once 'includes/tuxmlr-admin-notification.php';
	require_once 'includes/tuxmlr-bulk-validation-tab.php';

}

add_action( 'init', 'tuxmlr_include_file' );
add_action( 'init', 'tuxmlr_unschedule_actions' );  // To unschedule actions.

/**
 * To enqueue all required files.
 *
 * @return void
 */
function tuxmlr_email_validation_assets() {
	wp_enqueue_style( 'tuxmlr_emailapi_css', plugins_url( 'assets/css/emailapi.css', __FILE__ ), array(), '1.2' );
	wp_enqueue_style( 'tuxmlr_multiselect_css', plugins_url( 'assets/css/multiselect.css', __FILE__ ), array(), '1.2.1' );
	wp_enqueue_style( 'tuxmlr_sweetalert2_css', 'https://cdn.jsdelivr.net/npm/sweetalert2@10.10.1/dist/sweetalert2.min.css', array(), '10.10.1' );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'tuxmlr_multiselect_js', plugins_url( 'assets/js/multiselect.js', __FILE__ ), array( 'jquery' ), '1.2.1', 'false' );
	wp_register_script( 'tuxmlr_emailapi_js', plugins_url( 'assets/js/emailapi.js', __FILE__ ), array( 'jquery', 'tuxmlr_multiselect_js', 'wp-i18n' ), '1.0', 'false' );
	wp_enqueue_script( 'tuxmlr__sweetalert2_js', 'https://cdn.jsdelivr.net/npm/sweetalert2@10.10.1/dist/sweetalert2.all.min.js', array(), '1.0', 'false' );
	wp_enqueue_script( 'tuxmlr_emailapi_js' );
	wp_enqueue_script( 'tuxmlr_api_integration_js', plugins_url( 'assets/js/email_api_integration.js', __FILE__ ), array( 'jquery', 'tuxmlr_emailapi_js', 'tuxmlr_multiselect_js' ), '1.0', 'false' );

	wp_localize_script(
		'tuxmlr_emailapi_js',
		'tuxajaxapi',
		array(
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'tuxmailer-emails-validation' ),
		)
	);
	wp_localize_script(
		'tuxmlr_api_integration_js',
		'tuxuninstall',
		array(
			'uninstall'              => get_option( 'uninstalled_plugins' ),
			'tuxmlrApiKeyManagement' => esc_url( TUXMAILER_API_KEY_MANAGEMENT ),
		)
	);
	wp_set_script_translations( 'tuxmlr_emailapi_js', 'tuxmailer-email-validation', plugin_dir_path( __FILE__ ) . 'languages/' );
	wp_set_script_translations( 'tuxmlr_api_integration_js', 'tuxmailer-email-validation', plugin_dir_path( __FILE__ ) . 'languages/' );
	load_plugin_textdomain( 'tuxmailer-email-validation', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'admin_init', 'tuxmlr_email_validation_assets' );


global $tuxmlr_db_version;
$tuxmlr_db_version = '1.0';

/**
 * To Create database table on plugin activation.
 *
 * @return void
 */
function tuxmlr_email_validation_database_creation() {

	global $wpdb;
	global $tuxmlr_db_version;

	$tuxmlr_response_meta = $wpdb->prefix . 'tuxmalr_response_meta';
	$tuxmlr_email_meta    = $wpdb->prefix . 'tuxmlr_email_meta';
	$tuxmlr_api_request   = $wpdb->prefix . 'tuxmlr_api_request';

	$charset_collate = $wpdb->get_charset_collate();

	$response_meta = "CREATE TABLE IF NOT EXISTS $tuxmlr_response_meta (
		id mediumint(9) unsigned NOT NULL auto_increment,
        plugin_name varchar (100),
        form_id mediumint(8) unsigned NOT NULL default 0,
        entry_id mediumint(9) unsigned NOT NULL,
		email varchar(255),
		domain varchar(255),
		is_catchall_domain varchar(255),
		is_free_email_provider varchar(20),
		mail_server_used_for_validation varchar(255),
		valid_address varchar(15),
		valid_domain varchar(100),
		valid_smtp varchar(15),
		valid_syntax varchar(15),
		is_role_based varchar(10),
		has_full_inbox varchar(50),
		is_disabled varchar(10),
		tux_status varchar(20),
		details varchar(255),
		blacklisted varchar(10),
		billable varchar(10),
		PRIMARY KEY  (id)
	) $charset_collate;";

	$email_meta = "CREATE TABLE IF NOT EXISTS $tuxmlr_email_meta (
        id mediumint(9) unsigned NOT NULL auto_increment,
        email varchar (255),
        response mediumtext,
        PRIMARY KEY  (id)
    ) $charset_collate;";

	$api_request_logs = "CREATE TABLE IF NOT EXISTS $tuxmlr_api_request (
		id 	mediumint(9) unsigned NOT NULL auto_increment,
        plugin_name varchar (100),
        form_id mediumint(8) unsigned NOT NULL default 0,
        form_name varchar(150),	
        entry_ids mediumtext,
        api_counts tinyint(4) NOT NULL,
        uuid varchar(255),
        list_id varchar(255),
        resquest_timestamp datetime not null,
		response_status varchar(255),
		total_emails mediumint(8),
        account_detail varchar(255),
		PRIMARY KEY  (id)
	) $charset_collate;";

	// require_once(ABSPATH . 'wp-admin/includes/upgrade.php');.

	dbDelta( $response_meta );
	dbDelta( $email_meta );
	dbDelta( $api_request_logs );

	add_option( 'tuxmlr_db_version', $tuxmlr_db_version );

	add_option( 'tuxmailer_cf7_notification', true );
	add_option( 'tuxmailer_wpforms_notification', true );
	add_option( 'tuxmailer_install_notification', true );
	add_option( 'tuxmailer_formidable_sub_notification', true );
	add_option( 'tuxmailer_ninja_sub_notification', true );
	add_option( 'tuxmailer_wpforms_sub_notification', true );
	add_option( 'tuxmailer_apikey_notification', true );
}

/**
 * To delete  database table on plugin activation.
 *
 * @return void
 */
function tuxmlr_delete_admin_notification_options() {

	delete_option( 'tuxmailer_cf7_notification' );
	delete_option( 'tuxmailer_wpforms_notification' );
	delete_option( 'tuxmailer_install_notification' );
	delete_option( 'tuxmailer_formidable_sub_notification' );
	delete_option( 'tuxmailer_ninja_sub_notification' );
	delete_option( 'tuxmailer_wpforms_sub_notification' );
	delete_option( 'tuxmailer_apikey_notification' );
}
