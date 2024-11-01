<?php
/**
 * To Adds a submenu page.
 *
 * @return void
 */
function tuxmlr_bulk_validation_menu() {
	add_submenu_page(
		'tuxmailer-options-page',
		'Bulk Validations',
		'Bulk Email Validation',
		'manage_options',
		'tuxmailer-bulk-validation',
		'tuxmlr_bulk_validation_section'
	);
}
add_action( 'admin_menu', 'tuxmlr_bulk_validation_menu' );

/**
 * Undocumented function
 */
function tuxmlr_bulk_validation_section() {    ?>


<div class="pluginHeadingwrap">


	<section class="pluginHeader">
		<div>
			<img src="<?php echo esc_url( plugins_url( 'assets/images/tuxmailer-logo-dark.svg', dirname( __FILE__ ) ) ); ?>"
				alt="">

		</div>
		<div class="headerRight">
			<ul>
				<?php

				$tux_credits = tuxmlr_get_credits_remaining();
				?>
				<li><?php echo esc_html_e( 'Credits', 'tuxmailer-email-validation' ); ?>:
					<i><?php echo esc_attr( number_format_i18n( $tux_credits ) ); ?></i></li>
				<li><a href="<?php echo esc_url( TUXMAILER_SUBCRIPTION_PLANS ); ?>"
						target="_blank"><?php echo esc_html_e( 'Buy More', 'tuxmailer-email-validation' ); ?></a></li>
			</ul>
			<a href="<?php echo esc_url( TUXMAILER_HELP ); ?>" target="_blank"><img
					src="<?php echo esc_url( plugins_url( 'assets/images/questionmark.png', dirname( __FILE__ ) ) ); ?>"
					alt=""></a>
		</div>
	</section>


	<main>

		<div class="notificationGrup">

			<?php
			tuxmlr_validation_processing_notice();
			?>
		</div>
		<section class="topBar" id="topBar">
			<h3><?php echo esc_html_e( 'Bulk Validations', 'tuxmailer-email-validation' ); ?></h3>
			<a href="<?php echo esc_url( TUXMAILER_DOWNLOAD ); ?>"
				target="_blank"><?php echo esc_html_e( 'Take Me To TuxMailer', 'tuxmailer-email-validation' ); ?></a>
		</section>

		<section class="selectOptions">
			<div>
				<label
					for="selectPlugin"><?php echo esc_html_e( 'Select Plugin', 'tuxmailer-email-validation' ); ?></label>
				<select name="" id="selectPlugin">
					<option id="-1"><?php echo esc_html_e( 'Select', 'tuxmailer-email-validation' ); ?></option>
					<?php

					// To display all active and selected plugin name from tux mailler setting page.
					// also need to handle empty array.
					$active_plugins  = get_option( 'tux_active_plugin' );
					$selected_plugin = '';
					if ( ! empty( $active_plugins ) ) {

						foreach ( $active_plugins as $active_plugin ) {
							$is_form_selected = get_option( $active_plugin['tux_key'] );
							if ( ! empty( $is_form_selected ) ) {

								$selected_plugin .= '<option id=' . esc_attr( $active_plugin['slug'] ) . ' >' . esc_attr( $active_plugin['plugin_title'] ) . '</option>';
							}
						}
					} else {
						$selected_plugin .= '<option>' . esc_html__( 'Select plugin from setting page', 'tuxmailer-email-validation' ) . '</option>';
					}
					$allowed_html = array(
						'option' => array(
							'id'       => array(),
							'value'    => array(),
							'selected' => array(),
						),
					);
					echo wp_kses( $selected_plugin, $allowed_html );
					?>
				</select>
			</div>
			<div>
				<label for="selectForm"><?php echo esc_html_e( 'Select Form', 'tuxmailer-email-validation' ); ?></label>
				<select name="" id="selectForm">
					<option id='-1'><?php echo esc_html_e( 'Select', 'tuxmailer-email-validation' ); ?></option>
				</select>
				<div class="loader-form" style="display: none">
					<img alt="loading"
						src="<?php echo esc_url( plugins_url( 'assets/images/form-loader.gif', dirname( __FILE__ ) ) ); ?>" />
				</div>

			</div>
		</section>

		<section class="tableGroup">
			<h2> <?php echo esc_attr__( 'Bulk Validation', 'tuxmailer-email-validation' ); ?></h2>
			<div class="tableGroup__tbleTop">
				<div class="left">
					<select name="" id="selectAction">
						<option id="selected-entries" value="selected-entries">
							<?php echo esc_html_e( 'Selected', 'tuxmailer-email-validation' ); ?></option>
						<option id="all-entries" value="all-entries">
							<?php echo esc_html_e( 'Select all', 'tuxmailer-email-validation' ); ?>
						</option>
					</select>
					<div class="validateSec">
						<ul>
							<li>
								<input type="checkbox" id="selectValidity1"> <label
									for="selectValidity1"><?php echo esc_html_e( 'Bypass Blacklist', 'tuxmailer-email-validation' ); ?>
								</label>
							</li>
							<li>
								<input type="checkbox" id="selectValidity2"> <label
									for="selectValidity2"><?php echo esc_html_e( 'Priority Processing', 'tuxmailer-email-validation' ); ?>
								</label>
							</li>
						</ul>
						<button class="validationFlag" id="validationAction">
							<span class="imag"></span>
							<strong><?php echo esc_html_e( 'Validate', 'tuxmailer-email-validation' ); ?></strong>
							<span>(1 email / <i>1</i> credit )</span>
						</button>
					</div>
				</div>
				<div class="right">
					<select name="filtrby" id="filtrby">
						<option id='-1' value="filter-by">
							<?php echo esc_html_e( 'Filter by', 'tuxmailer-email-validation' ); ?></option>
						<option value='valid'><?php echo esc_html_e( 'Valid', 'tuxmailer-email-validation' ); ?>
						</option>
						<option value='invalid'><?php echo esc_html_e( 'Invalid', 'tuxmailer-email-validation' ); ?>
						</option>
						<option value='blacklisted'>
							<?php echo esc_html_e( 'Blacklisted', 'tuxmailer-email-validation' ); ?></option>
						<option value='unverifiable'>
							<?php echo esc_html_e( 'Unverifiable', 'tuxmailer-email-validation' ); ?></option>
						<option value='not-verified'>
							<?php echo esc_html_e( 'Not-Verified', 'tuxmailer-email-validation' ); ?></option>
						<option value='unknown'><?php echo esc_html_e( 'Unknown', 'tuxmailer-email-validation' ); ?>
						</option>
					</select>
					<button class="filtrBttn">
						<img src="filterIcon.png" alt="">
						<?php echo esc_html_e( 'Filter', 'tuxmailer-email-validation' ); ?>
					</button>
				</div>

			</div>



			<div id="table-container">
				<!-- Loader -->

			</div>
			<div class="loader-center" style="display: none">
				<img alt="loading"
					src="<?php echo esc_url( plugins_url( 'assets/images/tuxmailer-ajax-loader.gif', dirname( __FILE__ ) ) ); ?>" />
			</div>


			<!-- End Pagination -->

			<!-- Start Pagination -->
			<div class="pagination-container" style="display:none">

				<nav>
					<ul class="pagination">
						<li data-page="prev">
							<span>
								< </span>
						</li>
						<li data-page="next" id="prev">
							<span> >
							</span>
						</li>
					</ul>
				</nav>
			</div>
			<!-- END Pagination -->
		</section>



		<!-- popup Start -->
		<div class="popupSection">
			<div class="popupContainer">
				<div class="wrpAll">
					<button type="button" id="closePopup" class="notice-dismiss"></button>
					<div class="scroldiv">
						<div>
							<aside>
								<table id="col1" name="col1">
									<tbody>
										<tr>
											<td>Domain</td>
											<td id="tux-domain"></td>
										</tr>
										<tr>
											<td>Catch All Domain</td>
											<td id="tux-catchall_domain"></td>
										</tr>
										<tr>
											<td>Disposable</td>
											<td id="tux-disable"></td>
										</tr>
										<tr>
											<td>Email Type</td>
											<td id="tux-email-type"></td>
										</tr>
										<tr>
											<td>Validation Mail Server Used</td>
											<td id="tux-mail-server-used"></td>
										</tr>
									</tbody>
								</table>
							</aside>
							<aside>
								<table id="col2" name="col2">
									<tbody>
										<tr>
											<td>Domain Status</td>
											<td id="tux-domain-status"></td>
										</tr>
										<tr>
											<td>Address</td>
											<td id="tux-address"></td>
										</tr>
										<tr>
											<td>Syntax</td>
											<td id="tux-syntax"></td>
										</tr>
										<tr>
											<td>SMTP</td>
											<td id="tux-smtp"></td>
										</tr>
										<tr>
											<td>Role Based</td>
											<td id="tux-role-based"></td>
										</tr>
									</tbody>
								</table>
							</aside>
							<aside>
								<table id="col3" name="col3">
									<tbody>
										<tr>
											<td>Billable</td>
											<td id="tux-billable"></td>
										</tr>
										<tr>
											<td>Inbox Status</td>
											<td id="tux-inbox-status"></td>
										</tr>
										<tr>
											<td>Disabled</td>
											<td id="tux-disable"></td>
										</tr>
										<tr>
											<td>Blacklisted</td>
											<td id="tux-blacklisted"></td>
										</tr>
									</tbody>
								</table>
							</aside>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- Popup END -->
	</main>
</div>

	<?php

	tuxmlr_sync_previous_entries();  // This function will sync all old records for selected plugin and update entries in `wp_tuxmalr_response_meta` table.

}



/**
 * To prepare table for selected form or filter by ajax
 *
 * @param array $results .
 * @param array $unqiue_entry_ids unqiue entry ids for selected forms.
 * @param int   $total_entries Count of all entries for selected forms.
 * @param int   $page_no  Curent page number.
 * @param int   $total_pages Total pages.
 * @return array $row.
 */
function tuxmlr_prepare_bulk_list_table( $results, $unqiue_entry_ids, $total_entries, $page_no, $total_pages ) {
	$new_tux_meta = array();

	foreach ( $results as $result ) {
		$new_tux_meta[ $result->entry_id ][] = $result;
	}

	$row  = '';
	$row .= ' <table class="table table-striped table-class" id= "table-id">
    <thead>
        <tr>
            <th><input type="checkbox" id="allChecked" data-checked-parent=""></th>
            <th id="entries-count" data-entries-count ="' . esc_attr( $total_entries ) . '" >Entry ID</th>
            <th>' . esc_html__( 'Email Address', 'tuxmailer-email-validation' ) . '</th>
            <th>' . esc_html__( 'Status', 'tuxmailer-email-validation' ) . '</th>
            <th>' . esc_html__( 'Details', 'tuxmailer-email-validation' ) . '</th>
            <th> &nbsp; </th>
        </tr>
    </thead>
   <tbody>';
	$i    = 1;
	foreach ( $new_tux_meta as $tux_key => $tux_meta ) {

		$row_class = ( 0 === $i % 2 ) ? 'tuxmlr-evenRow' : 'tuxmlr-oldRow';

		if ( in_array( intval( $tux_key ), $unqiue_entry_ids, true ) ) {
			$parent = true;
			foreach ( $tux_meta as $childtable ) {
				$tux_array = (array) $childtable;
				unset( $tux_array['tux_status'] );
				unset( $tux_array['details'] );
				$tux_array['mail_server_used_for_validation'] = strtolower( str_replace( ' ', '_', $tux_array['mail_server_used_for_validation'] ) );

				if ( true === $parent ) {
					$row .= '<tr class="grup ' . esc_attr( $row_class ) . '">
                    <td><input type="checkbox" class="chkItm multiEmail" name="tuxmlr-entries" value=' . esc_attr( $childtable->entry_id ) . '></td>
                    <td>' . esc_attr( $childtable->entry_id ) . '<span class="clickAccordion">+</span></td>
                    <td>' . esc_attr( $childtable->email ) . '</td>
                    <td>' . esc_attr( ucwords( str_replace( '-', ' ', $childtable->tux_status ) ) ) . '</td>
                    <td>' . esc_attr( ( null !== $childtable->details ) ? $childtable->details : '-' ) . '</td>';
					if ( 'not-verified' !== strval( $childtable->tux_status ) ) {
						$email_json_meta = _wp_specialchars( wp_json_encode( $tux_array ), ENT_QUOTES, 'UTF-8', true );
						$row            .= '<td><a href="#" data-response= ' . esc_attr( $email_json_meta ) . ' class="openPop">' . esc_html__( 'View Details', 'tuxmailer-email-validation' ) . '</a></td>';
					} else {
						$row .= '<td></td>';
					}
					$row .= '</tr> 
                    <tr class="childtable  ' . esc_attr( $row_class ) . '" style="display:none;">
                    <td colspan="6">
                        <table class="table ">
                            <tbody>';

					$parent = false;
				} else {
					$row .= '<tr class="' . esc_attr( $row_class ) . '">
                    <td><input type="checkbox" class="chkItm multiChild" name="tuxmlr-entries" value=' . esc_attr( $childtable->entry_id ) . ' ></td>
                    <td>' . esc_attr( $childtable->entry_id ) . '</td>
                    <td>' . esc_attr( $childtable->email ) . '</td>
                    <td>' . esc_attr( ucwords( str_replace( '-', ' ', $childtable->tux_status ) ) ) . '</td>
                    <td>' . esc_attr( ( null !== $childtable->details ) ? $childtable->details : '-' ) . '</td>';
					if ( 'not-verified' !== strval( $childtable->tux_status ) ) {

						$email_json_meta = _wp_specialchars( wp_json_encode( $tux_array ), ENT_QUOTES, 'UTF-8', true );
						$row            .= '<td><a href="#" data-response=' . esc_attr( $email_json_meta ) . ' class="openPop">' . esc_html__( 'View Details', 'tuxmailer-email-validation' ) . '</a></td>';
					} else {
						$row .= '<td></td>';
					}
					$row .= ' </tr>';
				}
			}
			$row .= '</tbody>
                </table>
            </td>
        </tr>';
		} else {
			$tux_meta  = array_shift( $tux_meta );
			$tux_array = (array) $tux_meta;
			unset( $tux_array['tux_status'] );
			unset( $tux_array['details'] );
			$tux_array['mail_server_used_for_validation'] = strtolower( str_replace( ' ', '_', $tux_array['mail_server_used_for_validation'] ) );

			$row .= '<tr class="' . esc_attr( $row_class ) . '" >
                     <td><input type="checkbox" class="chkItm" name="tuxmlr-entries" value=' . esc_attr( $tux_meta->entry_id ) . ' ></td>
                     <td>' . esc_attr( $tux_meta->entry_id ) . '</td>
                     <td>' . esc_attr( $tux_meta->email ) . '</td>
                     <td>' . esc_attr( ucwords( str_replace( '-', ' ', $tux_meta->tux_status ) ) ) . '</td>
                     <td>' . esc_attr( ( null !== $tux_meta->details ) ? $tux_meta->details : '-' ) . '</td>';
			if ( 'not-verified' !== strval( $tux_meta->tux_status ) ) {
				$email_json_meta = _wp_specialchars( wp_json_encode( $tux_array ), ENT_QUOTES, 'UTF-8', true );
				$row            .= '  <td><a href="#" data-response=' . esc_attr( $email_json_meta ) . ' class="openPop">' . esc_html__( 'View Details', 'tuxmailer-email-validation' ) . '</a></td>';
			} else {
				$row .= '<td></td>';
			}
			$row .= ' </tr>';
		}

		$i++;
	}

	$row .= '  </tbody> 
</table>

<div class="new-pagination">
                <nav>
                    <ul>
                        <li>' . esc_attr( $total_entries ) . ' ' . esc_html__( 'Entries', 'tuxmailer-email-validation' ) . ' </li>';

	if ( 1 === $page_no ) {
		$row .= ' <li class = "deactive-button" id="start" data-page_number = "1" data-entriescount = "' . esc_attr( $total_entries ) . '" data-totalpages = "' . esc_attr( $total_pages ) . '"  ><button disabled><<</button></li>
										<li class = "deactive-button" id="pre" data-page_number = "' . esc_attr( $page_no ) . '" data-entriescount = "' . esc_attr( $total_entries ) . '"data-totalpages = "' . esc_attr( $total_pages ) . '"  ><button disabled><</button></li>';
	} else {
		$row .= ' <li id="start" data-page_number = "1" data-entriescount = "' . esc_attr( $total_entries ) . '" data-totalpages = "' . esc_attr( $total_pages ) . '"  ><button ><<</button></li>
								<li id="pre" data-page_number = "' . esc_attr( $page_no - 1 ) . '" data-entriescount = "' . esc_attr( $total_entries ) . '" data-totalpages = "' . esc_attr( $total_pages ) . '"  ><button><</button></li>';
	}

		$row .= ' <li id="list-current-page"   data-page_number = "' . esc_attr( $page_no ) . '"  data-entriescount = "' . esc_attr( $total_entries ) . '" data-totalpages = "' . esc_attr( $total_pages ) . '"  >' . esc_attr( $page_no ) . ' ' . esc_html__( 'of', 'tuxmailer-email-validation' ) . ' ' . esc_attr( $total_pages ) . '</li>';

	if ( $total_pages === $page_no ) {
		$row .= '
							<li class = "deactive-button" id="next" data-page_number = "' . esc_attr( $page_no ) . '" data-entriescount = "' . esc_attr( $total_entries ) . '" data-totalpages = "' . esc_attr( $total_pages ) . '"  ><button disabled>></button></li>
							<li class = "deactive-button" id="last" data-page_number = "' . esc_attr( $total_pages ) . '"  data-entriescount = "' . esc_attr( $total_entries ) . '" data-totalpages = "' . esc_attr( $total_pages ) . '"  ><button disabled>>></button></li>';
	} else {
		$row .= '<li id="next" data-page_number = "' . esc_attr( $page_no + 1 ) . '" data-entriescount = "' . esc_attr( $total_entries ) . '" data-totalpages = "' . esc_attr( $total_pages ) . '"  ><button>></button></li>
								<li id="last" data-page_number = "' . esc_attr( $total_pages ) . '"  data-entriescount = "' . esc_attr( $total_entries ) . '" data-totalpages = "' . esc_attr( $total_pages ) . '"   ><button >>></button></li>';
	}

		$row .= '
		</ul>
                </nav>
            </div>
            </div>';
	return $row;
}


/************  AJAX function for bulk validation page */


add_action( 'wp_ajax_tuxmlr_selected_plugin', 'tuxmlr_selected_plugin_forms' );
add_action( 'wp_ajax_nopriv_tuxmlr_selected_plugin', 'tuxmlr_selected_plugin_forms' );

/**
 * To populate all selected Forms for selected plugin in dropdown by ajax call
 *
 * @return void
 */
function tuxmlr_selected_plugin_forms() {
	if ( isset( $_POST['nonce'] ) && isset( $_POST['plugin_name'] ) && ! empty( $_POST['plugin_name'] ) ) {

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tuxmailer-emails-validation' ) ) {
			die( 'Permission Denied.' );
		}
		$plugin_name = sanitize_text_field( wp_unslash( $_POST['plugin_name'] ) );

		$allowed_html = array(
			'select' => array(
				'id'    => array(),
				'name'  => array(),
				'class' => array(),
				'value' => array(),
			),
			'option' => array(
				'id'       => array(),
				'value'    => array(),
				'selected' => array(),
			),
		);

		switch ( $plugin_name ) {
			case 'gravityforms':
				$forms = tuxmlr_get_gravityform_settings_ids_settings();
				break;

			case 'wpforms':
				$forms = tuxmlr_get_wpforms_ids_settings();

				break;

			case 'formidable':
				$forms = tuxmlr_get_formidable_form_settings_ids_settings();

				break;

			case 'ninja-forms':
				$forms = tuxmlr_get_ninja_form_settings_ids_settings();

				break;
			case 'contact-form-7':
				$forms = tuxmlr_get_cf7_ids_settings();

				break;
		}

		if ( ! empty( $forms ) ) {
			$option = '<option id ="-1" value="">Select Form</option>';
			foreach ( $forms as $form_id => $title ) {
				$option .= '<option id= ' . $form_id . ' value="' . $form_id . '">' . $title . '</option>';
			}

			echo wp_kses( $option, $allowed_html );

		}
	}

	die();
}


add_action( 'wp_ajax_tuxmlr_filter_selected_entries', 'tuxmlr_display_selected_form_entries' );
add_action( 'wp_ajax_nopriv_tuxmlr_filter_selected_entries', 'tuxmlr_display_selected_form_entries' );

/**
 * To list table for selected forms and filter with pagination logic.
 */
function tuxmlr_display_selected_form_entries() {
	if ( isset( $_POST['nonce'] ) && isset( $_POST['filtrby'] ) && isset( $_POST['selectFormId'] ) && isset( $_POST['selectPluginName'] ) && ! empty( $_POST['selectPluginName'] ) && ! empty( $_POST['selectFormId'] ) ) {

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tuxmailer-emails-validation' ) ) {
			die( 'Permission Denied.' );
		}

		global $wpdb;
		$tuxmalr_response_meta_table = $wpdb->prefix . 'tuxmalr_response_meta';

		$filtrby     = sanitize_text_field( wp_unslash( $_POST['filtrby'] ) );
		$form_id     = intval( sanitize_text_field( wp_unslash( $_POST['selectFormId'] ) ) );
		$plugin_name = sanitize_text_field( wp_unslash( $_POST['selectPluginName'] ) );

		if ( isset( $_POST['pageNumber'] ) ) {
			$page_no = intval( sanitize_text_field( wp_unslash( $_POST['pageNumber'] ) ) );
		} else {
			$page_no = 1;
		}

		if ( isset( $_POST['totalPage'] ) ) {
			$total_pages = intval( sanitize_text_field( wp_unslash( $_POST['totalPage'] ) ) );
		} else {
			$total_pages = 1;
		}

		if ( isset( $_POST['totalEntries'] ) ) {
			$total_entries = intval( sanitize_text_field( wp_unslash( $_POST['totalEntries'] ) ) );
		} else {
			$total_entries = null;
		}

		$limit_per_page = TUXMAILER_ENTRIES_PER_PAGE;
		$page_number    = $page_no;
		$offset         = intval( ( $page_number - 1 ) * $limit_per_page );

		switch ( $filtrby ) {

			case 'valid':
			case 'invalid':
			case 'unverifiable':
			case 'not-verified':
			case 'unknown':
				if ( 1 === $page_no ) {
					$total_entries = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( DISTINCT entry_id) FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d AND tux_status=%s", $plugin_name, $form_id, $filtrby ) ); // phpcs:ignore: unprepared SQL ok.
					if ( ! empty( $total_entries ) ) {
						$total_pages = ceil( $total_entries / $limit_per_page );
					}
				}

				$entry_results = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT entry_id FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d AND tux_status=%s ORDER BY entry_id DESC LIMIT %d, %d ", $plugin_name, $form_id, $filtrby, $offset, $limit_per_page ) ); // phpcs:ignore: unprepared SQL ok.
				if ( ! empty( $entry_results ) ) {
					$distinct_entry_id = implode( ',', $entry_results );
					$results           = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d AND tux_status=%s AND  entry_id IN ($distinct_entry_id) ORDER BY entry_id DESC ", $plugin_name, $form_id, $filtrby ) ); // phpcs:ignore: unprepared SQL ok.
				} else {
					echo '<h2 style="text-align: center;">' . esc_html__( 'Entries Not Found', 'tuxmailer-email-validation' ) . '</h2>';
					die();
				}

				break;

			case 'blacklisted':
				if ( 1 === $page_no ) {
					$total_entries = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( DISTINCT entry_id) FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d AND blacklisted=%s", $plugin_name, $form_id, 'Yes' ) ); // phpcs:ignore: unprepared SQL ok.
					$total_pages   = ceil( $total_entries / $limit_per_page );
				}

				$entry_results = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT entry_id FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d AND blacklisted=%s ORDER BY entry_id DESC LIMIT %d, %d ", $plugin_name, $form_id, 'Yes', $offset, $limit_per_page ) ); // phpcs:ignore: unprepared SQL ok.

				if ( ! empty( $entry_results ) ) {
					$distinct_entry_id = implode( ',', $entry_results );
					$results           = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d AND blacklisted=%s AND  entry_id IN ($distinct_entry_id) ORDER BY entry_id DESC ", $plugin_name, $form_id, 'Yes' ) ); // phpcs:ignore: unprepared SQL ok.
				} else {
					echo '<h2 style="text-align: center;">' . esc_html__( 'Entries Not Found', 'tuxmailer-email-validation' ) . '</h2>';
					die();
				}
				break;

			default:
				if ( 1 === $page_no ) {
					$total_entries = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( DISTINCT entry_id) FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d", $plugin_name, $form_id ) ); // phpcs:ignore: unprepared SQL ok.
					if ( ! empty( $total_entries ) ) {
						$total_pages = ceil( $total_entries / $limit_per_page );
					}
				}

				$entry_results = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT entry_id FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d ORDER BY entry_id DESC LIMIT %d, %d", $plugin_name, $form_id, $offset, $limit_per_page ) ); // phpcs:ignore: unprepared SQL ok.
				if ( ! empty( $entry_results ) ) {
					$distinct_entry_id = implode( ',', $entry_results );
					$results           = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d AND entry_id IN ($distinct_entry_id) ORDER BY entry_id DESC ", $plugin_name, $form_id ) ); // phpcs:ignore: unprepared SQL ok.
				} else {
					echo '<h2 style="text-align: center;">' . esc_html__( 'Entries Not Found', 'tuxmailer-email-validation' ) . '</h2>';
					die();
				}
				break;

		}

		$entry_ids = wp_list_pluck( $results, 'entry_id', 'id' );
		// refernce: https://www.daniweb.com/programming/web-development/threads/432473/show-only-duplicate-values-from-array-without-builtin-function .
		// eliminating duplicates entry ids.

		$unqiue_entry_ids = array_unique( array_values( array_diff_assoc( $entry_ids, array_unique( $entry_ids ) ) ) );  // eliminating duplicates.
		$unqiue_entry_ids = map_deep( $unqiue_entry_ids, 'intval' );
		$table            = tuxmlr_prepare_bulk_list_table( $results, $unqiue_entry_ids, $total_entries, $page_no, intval( $total_pages ) );

		$allowed_html = array(
			'style'    => array(),
			'ul'       => array(
				'id'    => array(),
				'name'  => array(),
				'class' => array(),
			),
			'li'       => array(
				'id'                => array(),
				'name'              => array(),
				'class'             => array(),
				'data-entriescount' => array(),
				'data-totalpages'   => array(),
				'data-page_number'  => array(),
			),
			'a'        => array(
				'href'          => array(),
				'title'         => array(),
				'class'         => array(),
				'data-response' => array(),
				'id'            => array(),
			),
			'div'      => array(
				'class' => array(),
				'style' => array(),
				'id'    => array(),
				'name'  => array(),
			),
			'input'    => array(
				'type'               => array(),
				'name'               => array(),
				'id'                 => array(),
				'class'              => array(),
				'value'              => array(),
				'checked'            => array(),
				'selected'           => array(),
				'data-response-code' => array(),
			),
			'button'   => array(
				'class'    => array(),
				'button'   => array(),
				'name'     => array(),
				'id'       => array(),
				'disabled' => array(),
				'type'     => array(),
			),
			'table'    => array(
				'class'  => array(),
				'button' => array(),
				'id'     => array(),
				'role'   => array(),
			),
			'&nbsp;'   => array(),
			'tbody'    => array(),
			'thead'    => array(),
			'th'       => array(
				'scope'              => array(),
				'class'              => array(),
				'name'               => array(),
				'id'                 => array(),
				'data-entries-count' => array(),
			),
			'tr'       => array(
				'class' => array(),
				'name'  => array(),
				'id'    => array(),
				'style' => array(),
			),
			'td'       => array(
				'class'         => array(),
				'name'          => array(),
				'id'            => array(),
				'colspan'       => array(),
				'data-response' => array(),
			),
			'br'       => array(),
			'checkbox' => array(
				'class' => array(),
				'id'    => array(),
				'value' => array(),
				'name'  => array(),
			),
			'span'     => array(
				'class' => array(),
				'id'    => array(),
			),
			'nav'      => array(
				'class' => array(),
				'id'    => array(),
			),
		);

		echo wp_kses( $table, $allowed_html );
		die();
	}
}


/**
 * To generate UUID for php version below 7.00
 * Credit: https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
 *
 * @param string $data .
 * @return array.
 */
function guidv4( $data ) {
	assert( strlen( $data ) === 16 );

	$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 ); // set version to 0100.
	$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 ); // set bits 6-7 to 10.

	return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
}


add_action( 'wp_ajax_bulk_validation_action', 'tuxmlr_bulk_validation_callback' );
add_action( 'wp_ajax_nopriv_bulk_validation_action', 'tuxmlr_bulk_validation_callback' );

/**
 * To process bulk email validation action.
 *
 * @return void
 */
function tuxmlr_bulk_validation_callback() {
	if ( isset( $_POST['nonce'] ) && isset( $_POST['selectedAction'] ) && isset( $_POST['filtrby'] ) && isset( $_POST['plugin_name'] ) && ! empty( $_POST['plugin_name'] ) && ! empty( $_POST['form_id'] ) && ! empty( $_POST['form_name'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tuxmailer-emails-validation' ) ) {
			die( 'Permission Denied.' );
		}

		$action  = strval( sanitize_text_field( wp_unslash( $_POST['selectedAction'] ) ) );
		$filtrby = strval( sanitize_text_field( wp_unslash( $_POST['filtrby'] ) ) );

		$plugin_name         = sanitize_text_field( wp_unslash( $_POST['plugin_name'] ) );
		$form_id             = intval( sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) );
		$entries_list        = isset( $_POST['checkedId'] ) ? array_unique( map_deep( wp_unslash( $_POST['checkedId'] ), 'intval' ) ) : array();
		$email_list          = isset( $_POST['checkedEmails'] ) ? map_deep( wp_unslash( $_POST['checkedEmails'] ), 'sanitize_email' ) : array();
		$form_name           = sanitize_text_field( wp_unslash( $_POST['form_name'] ) );
		$by_pass_black_list  = isset( $_POST['byPassBlackList'] ) ? sanitize_text_field( wp_unslash( $_POST['byPassBlackList'] ) ) : 'false';
		$priority_porcessing = isset( $_POST['priorityPorcessing'] ) ? sanitize_text_field( wp_unslash( $_POST['priorityPorcessing'] ) ) : 'false';

		/**
		 * $uid = To generate unique ID for all bulk validation request
		 */

		$uid = ( version_compare( phpversion(), '7.00', '>=' ) ) ? bin2hex( random_bytes( 15 ) ) : guidv4( openssl_random_pseudo_bytes( 16 ) );

		if ( 'all-entries' === $action ) {
			global $wpdb;
			$tuxmalr_response_meta_table = $wpdb->prefix . 'tuxmalr_response_meta';

			$per_request = TUXMAILER_MAX_EMAILS_PER_REQUEST;
			$final_part  = 'false';
			$entries_id  = array();

			switch ( $filtrby ) {

				case 'valid':
				case 'invalid':
				case 'unverifiable':
				case 'not-verified':
				case 'unknown':
					$entry_ids    = $wpdb->get_col( $wpdb->prepare( "SELECT entry_id FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d AND tux_status=%s", $plugin_name, $form_id, $filtrby ) ); // phpcs:ignore: unprepared SQL ok.
					$emails_count = count( $entry_ids );

					if ( $emails_count > 0 ) {

						$c = intval( $emails_count ) / TUXMAILER_MAX_EMAILS_PER_REQUEST;

						$count     = intval( is_float( $c ) ? floor( $c ) : ( floor( $c ) - 1 ) );
						$api_count = $count;
						for ( $i = 0; $i <= $count; $i++ ) {
							$offset = $i * TUXMAILER_MAX_EMAILS_PER_REQUEST;

							$results    = $wpdb->get_results( $wpdb->prepare( "SELECT entry_id,email FROM $tuxmalr_response_meta_table WHERE plugin_name= %s AND form_id=%d AND tux_status=%s LIMIT %d,%d", $plugin_name, $form_id, $filtrby, $offset, $per_request ) ); // phpcs:ignore: unprepared SQL ok.
							$emails     = wp_list_pluck( $results, 'email' );
							$entries_id = array_values( array_unique( $entry_ids ) );

							if ( $i === $count ) {
								$final_part = 'true';
							}

							$all_entries = tuxmlr_bulk_integrations_api_call( $plugin_name, $form_id, $form_name, $uid, $entries_id, $emails, $by_pass_black_list, $priority_porcessing, $final_part, $emails_count, $api_count + 1 );
							if ( 200 === $all_entries['response_code'] ) {
								if ( 'true' === $final_part ) {
									echo wp_json_encode( $all_entries, JSON_FORCE_OBJECT );
									if ( false === as_has_scheduled_action( 'sync_pending_bulk_api_response' ) ) {
										as_schedule_recurring_action( strtotime( 'now' ), TUXMAILER_TWO_MINUTES_IN_SECONDS, 'sync_pending_bulk_api_response' );
									}
								}
							} else {
								echo wp_json_encode( $all_entries, JSON_FORCE_OBJECT );
								die();
							}
						}
					}

					break;

				case 'blacklisted':
					$entry_ids    = $wpdb->get_col( $wpdb->prepare( "SELECT entry_id FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d AND blacklisted=%s", $plugin_name, $form_id, 'Yes' ) ); // phpcs:ignore: unprepared SQL ok.
					$emails_count = count( $entry_ids );

					if ( $emails_count > 0 ) {

						$c = intval( $emails_count ) / TUXMAILER_MAX_EMAILS_PER_REQUEST;

						$count     = intval( is_float( $c ) ? floor( $c ) : ( floor( $c ) - 1 ) );
						$api_count = $count;
						for ( $i = 0; $i <= $count; $i++ ) {
							$offset = $i * TUXMAILER_MAX_EMAILS_PER_REQUEST;

							$results    = $wpdb->get_results( $wpdb->prepare( "SELECT entry_id,email FROM $tuxmalr_response_meta_table WHERE plugin_name= %s AND form_id=%d AND blacklisted=%s LIMIT %d,%d", $plugin_name, $form_id, 'Yes', $offset, $per_request ) ); // phpcs:ignore: unprepared SQL ok.
							$emails     = wp_list_pluck( $results, 'email' );
							$entries_id = array_values( array_unique( $entry_ids ) );

							if ( $count === $i ) {
								$final_part = 'true';
							}

							$all_entries = tuxmlr_bulk_integrations_api_call( $plugin_name, $form_id, $form_name, $uid, $entries_id, $emails, $by_pass_black_list, $priority_porcessing, $final_part, $emails_count, $api_count + 1 );
							if ( 200 === $all_entries['response_code'] ) {
								if ( 'true' === $final_part ) {
									echo wp_json_encode( $all_entries, JSON_FORCE_OBJECT );
									if ( false === as_has_scheduled_action( 'sync_pending_bulk_api_response' ) ) {
										as_schedule_recurring_action( strtotime( 'now' ), TUXMAILER_TWO_MINUTES_IN_SECONDS, 'sync_pending_bulk_api_response' );
									}
								}
							} else {
								echo wp_json_encode( $all_entries, JSON_FORCE_OBJECT );
								die();
							}
						}
					}
					break;

				default:
					$entry_ids    = $wpdb->get_col( $wpdb->prepare( "SELECT entry_id FROM {$tuxmalr_response_meta_table} WHERE plugin_name= %s AND form_id=%d ", $plugin_name, $form_id ) ); // phpcs:ignore: unprepared SQL ok.
					$emails_count = count( $entry_ids );

					if ( $emails_count > 0 ) {

						$c = intval( $emails_count ) / TUXMAILER_MAX_EMAILS_PER_REQUEST;

						$count     = intval( is_float( $c ) ? floor( $c ) : ( floor( $c ) - 1 ) );
						$api_count = $count;
						for ( $i = 0; $i <= $count; $i++ ) {
							$offset = $i * TUXMAILER_MAX_EMAILS_PER_REQUEST;

							$results    = $wpdb->get_results( $wpdb->prepare( "SELECT entry_id,email FROM $tuxmalr_response_meta_table WHERE plugin_name=%s AND form_id=%d LIMIT %d,%d", $plugin_name, $form_id, $offset, $per_request ) ); // phpcs:ignore: unprepared SQL ok.
							$emails     = wp_list_pluck( $results, 'email' );
							$entries_id = array_values( array_unique( $entry_ids ) );

							if ( $i === $count ) {
								$final_part = 'true';
							}

							$all_entries = tuxmlr_bulk_integrations_api_call( $plugin_name, $form_id, $form_name, $uid, $entries_id, $emails, $by_pass_black_list, $priority_porcessing, $final_part, $emails_count, $api_count + 1 );
							if ( 200 === $all_entries['response_code'] ) {
								if ( 'true' === $final_part ) {
									echo wp_json_encode( $all_entries, JSON_FORCE_OBJECT );

									if ( false === as_has_scheduled_action( 'sync_pending_bulk_api_response' ) ) {
										// as_schedule_recurring_action(strtotime("+1 minutes"), TUXMAILER_TWO_MINUTES_IN_SECONDS, 'sync_pending_bulk_api_response');
										// To sync or get bulk response after bulk validation is process.
										// Updating recived bulk response works on bankend using `action schedule` like WP_Cron job in every 2 minutes still all request reponse is not updated.
										as_schedule_recurring_action( strtotime( 'now' ), TUXMAILER_TWO_MINUTES_IN_SECONDS, 'sync_pending_bulk_api_response' );
									}
								}
							} else {
								echo wp_json_encode( $all_entries, JSON_FORCE_OBJECT );
								die();
							}
						}
					}
					break;

			}
		} elseif ( 'selected-entries' === $action && ! empty( $entries_list ) ) {
			$selected_entries = tuxmlr_bulk_integrations_api_call( $plugin_name, $form_id, $form_name, $uid, array_values( $entries_list ), $email_list, $by_pass_black_list, $priority_porcessing, 'true', count( $email_list ), 1 );
			if ( 200 === $selected_entries['response_code'] ) {
				echo wp_json_encode( $selected_entries, JSON_FORCE_OBJECT );
				if ( false === as_has_scheduled_action( 'sync_pending_bulk_api_response' ) ) {
					as_schedule_recurring_action( strtotime( 'now' ), TUXMAILER_TWO_MINUTES_IN_SECONDS, 'sync_pending_bulk_api_response' );
				}
			} else {
				echo wp_json_encode( $selected_entries, JSON_FORCE_OBJECT );
				die();
			}
		} else {
			echo esc_html_e( 'Some Error Occurred', 'tuxmailer-email-validation' );
			die();
		}
	}

	die();
}



/**
 * To check API response for all pending request
 * Schedule an action with the hook 'sync_pending_bulk_api_response' to run at every 2 mimutes after bulk request is process
 * so that our callback is run then.
 * This will run in back ground and update response recived with stoping any other function like WP_Cron job
 * A callback to when the 'sync_pending_bulk_api_response' scheduled action is run.
 */
function tuxmlr_checking_get_api_response() {
	global $wpdb;

	$pending_requests = $wpdb->get_results( $wpdb->prepare( "SELECT uuid,api_counts,total_emails FROM {$wpdb->prefix}tuxmlr_api_request WHERE response_status=%s", 'Pending' ), ARRAY_A );

	if ( ! empty( $pending_requests ) && ( 0 !== count( $pending_requests ) ) ) {

		$tuxmalr_response_meta_table = $wpdb->prefix . 'tuxmalr_response_meta';
		foreach ( $pending_requests as $pending_request ) {
			$uuid         = $pending_request['uuid'];
			$uuid_deatils = tuxmlr_get_entries_ids_by_uid( $uuid );

			$entry_info  = array_shift( $uuid_deatils );
			$plugin_name = $entry_info->plugin_name;
			$form_id     = $entry_info->form_id;
			$entry_ids   = json_decode( $entry_info->entry_ids );
			$entry_ids   = implode( ',', $entry_ids );

			$uuid_response = json_decode( tuxmlr_get_bulk_email_response_api( $uuid, 1 ) );
			if ( ! empty( $uuid_response->items ) && ( intval( $uuid_response->total ) === intval( $pending_request['total_emails'] ) ) ) {
				for ( $page_no = 1; $page_no <= $pending_request['api_counts']; $page_no++ ) {
					$uuid_response = json_decode( tuxmlr_get_bulk_email_response_api( $uuid, $page_no ) );

					foreach ( $uuid_response->items as $item ) {
						$email          = $item->email;
						$response_array = json_decode( wp_json_encode( $item ), true );

						$domain                          = ! empty( $response_array['domain'] ) ? $response_array['domain'] : '0';
						$is_catchall_domain              = ! empty( $response_array['is_catchall_domain'] ) ? 'Yes' : 'No';
						$is_free_email_provider          = ! empty( $response_array['is_free_email_provider'] ) ? 'Free' : 'Chargeable';
						$mail_server_used_for_validation = ! empty( $response_array['mail_server_used_for_validation'] ) ? $response_array['mail_server_used_for_validation'] : '0';
						$valid_address                   = ! empty( $response_array['valid_address'] ) ? 'Valid' : 'Invalid';
						$valid_domain                    = ! empty( $response_array['valid_domain'] ) ? 'Valid' : 'Invalid';
						$valid_smtp                      = ! empty( $response_array['valid_smtp'] ) ? 'Valid' : 'Invalid';
						$valid_syntax                    = ! empty( $response_array['valid_syntax'] ) ? 'Valid' : 'Invalid';
						$is_role_based                   = ! empty( $response_array['is_role_based'] ) ? 'Yes' : 'No';
						$has_full_inbox                  = ! empty( $response_array['has_full_inbox'] ) ? 'Full' : 'No';
						$is_disabled                     = ! empty( $response_array['is_disabled'] ) ? 'Yes' : 'No';
						$tux_status                      = ! empty( $response_array['status'] ) ? $response_array['status'] : 'not-verified';
						$details                         = ! empty( $response_array['details'] ) ? $response_array['details'] : 'Not availabe';
						$blacklisted                     = ! empty( $response_array['blacklisted'] ) ? 'Yes' : 'No';
						$billable                        = ! empty( $response_array['billable'] ) ? 'Yes' : 'No';

						$result = $wpdb->query( $wpdb->prepare( "UPDATE $tuxmalr_response_meta_table SET domain=%s,is_catchall_domain=%s,is_free_email_provider=%s,mail_server_used_for_validation=%s,valid_address=%s,valid_domain= %s,valid_smtp=%s,valid_syntax=%s,is_role_based=%s,has_full_inbox=%s,is_disabled=%s,tux_status=%s,details=%s,blacklisted=%s,billable=%s WHERE plugin_name=%s AND form_id=%d AND email=%s AND `entry_id` IN ($entry_ids)", $domain, $is_catchall_domain, $is_free_email_provider, $mail_server_used_for_validation, $valid_address, $valid_domain, $valid_smtp, $valid_syntax, $is_role_based, $has_full_inbox, $is_disabled, $tux_status, $details, $blacklisted, $billable, $plugin_name, intval( $form_id ), $email ) ); // phpcs:ignore: unprepared SQL ok.

						if ( is_wp_error( $result ) ) {
							$error_code    = $result->get_error_code();
							$error_message = $result->get_error_message( $error_code );
							return array(
								'response_code' => $error_code,
								'error_message' => $error_message,
							);
						}
					}

					if ( ( intval( $pending_request['api_counts'] ) === $page_no ) && ! is_wp_error( $result ) ) {
						tuxmlr_update_uid_status( $uuid );
					}
				}
			}
		}
	}
}

add_action( 'sync_pending_bulk_api_response', 'tuxmlr_checking_get_api_response' );


/**
 * To unschedule Sync Bulk Tux API Response.
 *
 * @return void
 */
function tuxmlr_unschedule_actions() {
	if ( true === as_has_scheduled_action( 'sync_pending_bulk_api_response' ) ) {
		global $wpdb;

		$pending_requests = $wpdb->get_col( $wpdb->prepare( "SELECT uuid FROM {$wpdb->prefix}tuxmlr_api_request WHERE response_status=%s", 'Pending' ), ARRAY_A );

		if ( empty( $pending_requests ) && ( 0 === count( $pending_requests ) ) ) {
			as_unschedule_all_actions( 'sync_pending_bulk_api_response' );
		}
	}
}
