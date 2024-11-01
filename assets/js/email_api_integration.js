jQuery(document).ready(function () {

  /**To disable the fields for deactivated plugins */
  const { __ } = wp.i18n;

  let uninstalled_plugin = tuxuninstall.uninstall;

  jQuery.each(uninstalled_plugin, function (i, val) {
    jQuery("#" + val).addClass("active");
  });

  /**Hide the team member dropdown */
  show_dropdown_field();
  jQuery('input[name="tuxmlr_account_type"]').change(show_dropdown_field);
  function show_dropdown_field() {
    var selected_value = jQuery(
      "input[name='tuxmlr_account_type']:checked"
    ).val();
    if (selected_value == "personal_account") {
      // jQuery("#tuxmlr_team_account").hide();
      jQuery("#tuxmlr_team_account").closest("tr").hide();
    } else {
      jQuery("#tuxmlr_team_account").closest("tr").show();
    }
  }

  jQuery("#tuxmailer_validate_api_key").click(function () {
    var tuxmlr_api_key = jQuery("#tuxmlr_api_key").val();

    if ("" == tuxmlr_api_key) {
      Swal.fire("", __("Please enter a valid TuxMailer API key", "tfmi"), "info");
    } else {
      jQuery.ajax({
        url: tuxajaxapi.url,
        method: "POST",
        data: {
          nonce: tuxajaxapi.nonce,
          action: "tuxmlr_verify_api", // load function hooked to: "wp_ajax_*" action hook
          tuxmlr_api_key: tuxmlr_api_key,
        },
        success: function (response) {
          data = JSON.parse(response);
          var msg = "";
          if (data.response_code == "http_request_failed") {
            Swal.fire(
              '', __('	We are unable to connect to our back-end servers. Please contact support.', 'tuxmailer-email-validation'),
              "warning"
            );
          } else if (data.response_code == 422) {

            Swal.fire('', __('Oops... Something went wrong. Please contact support.', 'tuxmailer-email-validation'), "error");
          } else if (data.response_code == 401) {

            Swal.fire("", __("Could not validate your API key. <br> Please check your API key and try again.", 'tuxmailer-email-validation'), "error");
          } else if (data.response_code == 200) {
            msg = data.message;
            Swal.fire(__("Verified", 'tuxmailer-email-validation'), data.message, "success");

            // To Set Tux token valid if response code is 200.
            if (200 == data.response_code) {
              jQuery("#tuxmlr_api_key").attr("class", "tuxmailer-token-valid");
              // jQuery("#tuxmlr_api_key").attr("data-response-code",data.response_code);
            }
          } else {
            Swal.fire('', __('Oops... Something went wrong. Please contact support.', 'tuxmailer-email-validation'), "error");
          }

          //Set Tux token status response code to token field.
          jQuery("#tuxmlr_api_key").attr("data-response-code", data.response_code);


          jQuery(".headerRight ul #tuxmailer-credits i").text(data.balance);
          jQuery("#tuxmlr_team_account")
            .find("option")
            .remove()
            .end()
            .append(data.team_meta)
            .val("-1");

          // jQuery("#show_message").html(data);
        },

        error: function (errorThrown) {
        },
      });
    }
  });

  jQuery("#tuxmailer-submit-button").click(function (event) {
    var team_account_value = jQuery("#tuxmlr_team_account").val();
    var responseStatus = jQuery("#tuxmlr_api_key").attr("class");
    var responseCode = jQuery("#tuxmlr_api_key").attr("data-response-code");
    var account_type = jQuery('input[name="tuxmlr_account_type"]:checked').val();
    var api_key_value = jQuery("#tuxmlr_api_key").val();

    // If API key field is empty.
    if ("" == api_key_value) {
      Swal.fire("", __("Please enter a valid TuxMailer API key", "tfmi"), "info");
      event.preventDefault();
      return;
    }

    //If team account selected and not selected anu team then this popup.
    if ('-1' == team_account_value && 'team_account' == account_type) {
      Swal.fire('', __("Please select the appropriate team", 'tuxmailer-email-validation'), 'error');
      event.preventDefault();
      return;
    }

    // //If token key is not valid .
    // if (200 != responseCode || "tuxmailer-token-valid" != responseStatus) {
    //   Swal.fire(
    //     '',
    //     sprintf(
    //       /* translators: 1: API Key URL */
    //       __('You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation'), tuxuninstall.tuxmlrApiKeyManagement
    //     ),
    //     "error"
    //   );
    //   event.preventDefault();
    //   return;
    // }

    // If anonymous checkbox is not checked.
    if (jQuery("#tuxmlr_anonymous_data").prop("checked") == false) {
      event.preventDefault();
      Swal.fire({
        title: __("Are you sure?", 'tuxmailer-email-validation'),
        html: __("<br>You have disagreed to transfer data to the TuxMailer App. You will not be able to view your data in the TuxMailer App!", 'tuxmailer-email-validation'),
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: __("Yes, I understand!", 'tuxmailer-email-validation'),
        cancelButtonText: __("No, cancel", 'tuxmailer-email-validation'),
      }).then((result) => {


        if (result.isConfirmed) {
          // jQuery("#tuxmlr_anonymous_data").prop("checked", true);
          jQuery(this).unbind("click");
          event.currentTarget.click(); // To auto continue current event
        } else {
          jQuery(this).unbind("click");
        }
        // if (!(team_account_value == -1 && account_type == "team_account") && !(api_key_value == "")) {

        //   if (200 !== responseCode && "tuxmailer-token-valid" !== responseStatus) {
        //     jQuery(this).unbind("click");
        //     event.currentTarget.click(); // To auto continue current event

        //   }
        //   else {
        //     Swal.fire(
        //       '',
        //       sprintf(
        //         /* translators: 1: API Key URL */
        //         __('You need to enter a valid token in the plugin settings page. You can get a token from the TuxMailer app <a href="%1$s" target="_blank">here</a>.', 'tuxmailer-email-validation'), tuxuninstall.tuxmlrApiKeyManagement
        //       ),
        //       "error"
        //     );
        //   }
        // }

      });
    }

  });


  jQuery(".notice-dismiss").click(function (event) {

    var dismissNoti = null;
    dismissNoti = jQuery(this).parents('.remove-notification').attr('data-tuxNotification');
    if ('' != dismissNoti) {
      jQuery.ajax({
        url: tuxajaxapi.url,
        method: "POST",
        data: {
          nonce: tuxajaxapi.nonce,
          action: "remove_admin_notifications", // load function hooked to: "wp_ajax_*" action hook
          dismissNoti: dismissNoti,
        },
      });
    }
  });
});
