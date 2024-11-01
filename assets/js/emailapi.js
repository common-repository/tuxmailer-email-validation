jQuery(document).ready(function () {

  const { __ } = wp.i18n;

  jQuery("#my-select").multipleSelect({
    filter: false,
  });

  /*************************** By Front-End  **********************************/

  jQuery("#closePopup").on("click", function () {
    jQuery(this).closest(".popupSection").removeClass("active");
  });

  jQuery(".openPop").on("click", function (e) {
    e.preventDefault();
    jQuery(".popupSection").addClass("active");
  });

  jQuery(".clickAccordion").on("click", function () {
    var $prntTd = jQuery(this).closest("tr.grup");
    $prntTd.next().toggleClass("active");

    jQuery(this).toggleClass("active");
    if (jQuery(this).text() == "+") {
      jQuery(this).text("-");
    } else {
      jQuery(this).text("+");
    }
  });

  jQuery("#selectValidity2").change(function () {
    if (jQuery(this).prop("checked") == true) {
      //do something
      jQuery(".validationFlag span i").text("5");
    } else {
      jQuery(".validationFlag span i").text("1");
    }
  });

  jQuery("#allChecked").change(function () {
    if (jQuery(this).prop("checked") == true) {
      //do something

      jQuery(".chkItm").prop("checked", true);
    } else {
      jQuery(".chkItm").prop("checked", false);
    }
  });

  /*************************** End Front-End  **********************************/

  jQuery("#tuxmlr_selected_ninja_forms").multipleSelect({
    filter: false,
  });

  jQuery("#tuxmlr_selected_wp_forms").multipleSelect({
    filter: false,
  });
  jQuery("#tuxmlr_selected_wp_forms-paid").multipleSelect({
    filter: false,
  });

  jQuery("#tuxmlr_selected_gravity_forms").multipleSelect({
    filter: false,
  });

  jQuery("#tuxmlr_selected_formidable_forms").multipleSelect({
    filter: false,
  });
  jQuery("#tuxmlr_selected_contact_forms").multipleSelect({
    filter: false,
  });
  // For disabled plugins.
  jQuery(".tuxmlr_select_deactivated_plugin").multipleSelect({
    filter: false,
  });

  jQuery("#doaction, #doaction2").click(function () {
    var action = jQuery(this).siblings("select").val();

    if (action == -1) {
      return;
    }
    var defaultModalOptions = "";
    var entryIds = getLeadIds();

    if (entryIds.length != 0) {
      var value = jQuery(
        "select#bulk-action-selector-top option:selected"
      ).val();
      if (value == "bulk_email_validation") {
        // jQuery(".modal, .modal-content, .modal-description").addClass("active");
        // jQuery(".modal, .modal-content, .modal-description").show();
        jQuery(".pop-outer").fadeIn("slow");
      }
    }
  });

  /**************************** Integrating AJAX function for dynamic forms and entries  *********************************/

  jQuery("#selectAction").click(function () {
    if (jQuery("#all-entries").prop("selected") == true) {
      jQuery("#allChecked").prop("checked", true);
      jQuery(".chkItm").prop("checked", true);
    }
  });

  // To popupate all forms for selected plugin by AJAX
  // Plugin-name dependent ajax

  jQuery("#selectPlugin").on("change", function () {
    var plugin_name = jQuery("#selectPlugin option:selected").attr("id");
    jQuery("#table-id").html("");

    jQuery.ajax({
      url: tuxajaxapi.url,
      type: "POST",
      cache: false,
      data: {
        nonce: tuxajaxapi.nonce,
        action: "tuxmlr_selected_plugin",
        plugin_name: plugin_name,
      },
      beforeSend: function () {
        jQuery("#selectForm").html("");
        jQuery(".loader-form").show();
      },
      complete: function () {
        jQuery(".loader-form").hide();
      },
      success: function (data) {
        jQuery("#selectForm").html(data);
      },
    });
  });

  // To display entries table for selected form

  jQuery("#selectForm").on("change", function () {
    // var form_id = jQuery(this).children(":selected").attr("id");

    var selectFormId = jQuery("#selectForm option:selected").attr("id");
    var selectPluginName = jQuery("#selectPlugin option:selected").attr("id");

    jQuery("#filtrby").val("filter-by").trigger("change");
    jQuery("#selectAction #selected-entries").text("Selected");
    jQuery.ajax({
      url: tuxajaxapi.url,
      type: "POST",
      cache: false,
      data: {
        nonce: tuxajaxapi.nonce,
        action: "tuxmlr_filter_selected_entries",
        selectFormId: selectFormId,
        selectPluginName: selectPluginName,
        filtrby: "filter-by",
      },
      beforeSend: function () {
        jQuery("#table-id").html("");
        jQuery(".loader-center").show();
      },
      complete: function () {
        jQuery(".loader-center").hide();
      },
      success: function (data) {
        // jQuery("#form_name option:selected").text(selectedVal);

        jQuery("#table-container").html(data);

        // jQuery("#table-id > tbody ").html(data);

        toSetEntriesCountInSelectAction();

        toApplyjQuery();
      },
    });
    // toApplyjQuery();
  });

  // }

  // To display entries table for selected form with filterby

  jQuery(".filtrBttn").click(function () {
    var filtrby = jQuery("#filtrby option:selected").val();

    var selectFormId = jQuery("#selectForm option:selected").attr("id");
    var selectPluginName = jQuery("#selectPlugin option:selected").attr("id");

    if ("-1" === selectPluginName) {
      Swal.fire(__("Select Plugin", 'tuxmailer-email-validation'), '', "info");
      return;
    } else if ("-1" === selectFormId) {
      Swal.fire(__("Select Form", 'tuxmailer-email-validation'), '', "info");
      return;
    } else {

      jQuery.ajax({
        url: tuxajaxapi.url,
        type: "POST",
        cache: false,
        data: {
          nonce: tuxajaxapi.nonce,
          action: "tuxmlr_filter_selected_entries",
          filtrby: filtrby,
          selectFormId: selectFormId,
          selectPluginName: selectPluginName,
        },
        beforeSend: function () {
          jQuery("#table-id").html("");
          jQuery(".loader-center").show();
        },
        complete: function () {
          jQuery(".loader-center").hide();
        },
        success: function (data) {
          jQuery("#table-container").html(data);
          // jQuery("#table-id > tbody ").html(data);

          toSetEntriesCountInSelectAction();

          toApplyjQuery();
        },
      });
    }
  });

  function toSetEntriesCountInSelectAction() {
    var entriesCount = jQuery(".new-pagination #list-current-page").data(
      "entriescount"
    );
    entriesCount = typeof entriesCount !== "undefined" ? entriesCount : "0";

    jQuery("#selectAction #all-entries").attr(
      "data-entries-count",
      entriesCount
    );
    jQuery("#selectAction #all-entries").text(
      "Select all " + entriesCount
    );
  }

  // To apply jQuery to table after ajax call

  function toApplyjQuery() {
    // To enable popup after display table.
    jQuery(".openPop").on("click", function (e) {
      e.preventDefault();
      jQuery(".popupSection").addClass("active");
    });

    jQuery(".clickAccordion").on("click", function () {
      var $prntTd = jQuery(this).closest("tr.grup");
      $prntTd.next().toggleClass("active");

      jQuery(this).toggleClass("active");
      if (jQuery(this).text() == "+") {
        jQuery(this).text("-");
      } else {
        jQuery(this).text("+");
      }
    });

    jQuery(".multiEmail").change(function () {
      if (jQuery(this).prop("checked") == true) {
        var prntTd = jQuery(this).closest("tr.grup");
        prntTd.next().find(":checkbox").prop("checked", true);
      } else {
        var prntTd = jQuery(this).closest("tr.grup");
        prntTd.next().find(":checkbox").prop("checked", false);
      }
    });

    // all checked form table header

    jQuery("#allChecked").change(function () {
      jQuery("#selectAction").val("selected-entries").trigger("change");
      var checkedbox = [];

      if (jQuery(this).prop("checked") == true) {
        //do something
        jQuery(".chkItm").prop("checked", true);

        jQuery.each(jQuery("input[name='tuxmlr-entries']:checked"), function () {
          checkedbox.push(jQuery(this).val());
        });

        var chkItem = checkedbox.filter((x, y) => checkedbox.indexOf(x) == y);
        count = chkItem.length;

        jQuery("#selectAction #selected-entries").text(
          count + " Selected"
        );
      } else {
        jQuery(".chkItm").prop("checked", false);
        jQuery("#selectAction #selected-entries").text("Selected");
      }
    });

    // To get array response saved in data-atribute for popup to show all details

    jQuery("#table-id").on("click", ".openPop", function () {
      var dataResponse = jQuery(this).data("response");

      jQuery(".popupContainer #col1 #tux-domain").html(dataResponse.domain);
      jQuery(".popupContainer #col1 #tux-catchall_domain").html(
        dataResponse.is_catchall_domain
      );
      jQuery(".popupContainer #col1 #tux-disable").html(
        dataResponse.is_disabled
      );
      jQuery(".popupContainer #col1 #tux-email-type").html(
        dataResponse.is_free_email_provider
      );
      jQuery(".popupContainer #col1 #tux-mail-server-used").html(
        dataResponse.mail_server_used_for_validation.replaceAll('_', ' ')
      );
      jQuery(".popupContainer #col2 #tux-domain-status").html(
        dataResponse.valid_domain
      );
      jQuery(".popupContainer #col2 #tux-address").html(
        dataResponse.valid_address
      );
      jQuery(".popupContainer #col2 #tux-syntax").html(
        dataResponse.valid_syntax
      );
      jQuery(".popupContainer #col2 #tux-smtp").html(dataResponse.valid_smtp);
      jQuery(".popupContainer #col2 #tux-role-based").html(
        dataResponse.is_role_based
      );
      jQuery(".popupContainer #col3 #tux-billable").html(dataResponse.billable);
      jQuery(".popupContainer #col3 #tux-inbox-status").html(
        dataResponse.has_full_inbox
      );
      jQuery(".popupContainer #col3 #tux-disable").html(
        dataResponse.is_disabled
      );
      jQuery(".popupContainer #col3 #tux-blacklisted").html(
        dataResponse.blacklisted
      );
    });
    // string.replace('_',' ')
    // For pagination

    jQuery(".new-pagination ul li").click(function () {
      // pageNumber = jQuery(this).attr("id");
      pageNumber = jQuery(this).attr("data-page_number");
      totalPage = jQuery(this).attr("data-totalpages");
      totalEntries = jQuery(this).attr("data-entriescount");

      var filtrby = jQuery("#filtrby option:selected").val();
      var selectFormId = jQuery("#selectForm option:selected").attr("id");
      var selectPluginName = jQuery("#selectPlugin option:selected").attr("id");

      jQuery.ajax({
        url: tuxajaxapi.url,
        type: "POST",
        cache: false,
        data: {
          nonce: tuxajaxapi.nonce,
          action: "tuxmlr_filter_selected_entries",
          filtrby: filtrby,
          selectFormId: selectFormId,
          selectPluginName: selectPluginName,
          pageNumber: pageNumber,
          totalPage: totalPage,
          totalEntries: totalEntries,
        },
        success: function (data) {
          jQuery("#table-container").html(data);

          toApplyjQuery();
        },
      });
    });

    // Change "select all" to "Selected" when any checkbox is unchecked.
    jQuery(".chkItm").change(function () {
      jQuery("#selectAction").val("selected-entries").trigger("change");
      var checkedbox = [];

      jQuery.each(jQuery("input[name='tuxmlr-entries']:checked"), function () {
        checkedbox.push(jQuery(this).val());
      });

      var chkItem = checkedbox.filter((x, y) => checkedbox.indexOf(x) == y);
      count = chkItem.length;
      jQuery("#selectAction #selected-entries").text(
        count + " Selected"
      );

      var numberOfChecked = jQuery("input[name='tuxmlr-entries']:checked").length;
      var totalCheckboxes = jQuery("input[name='tuxmlr-entries']").length;
      if (totalCheckboxes == numberOfChecked) {
        jQuery("#allChecked").prop("checked", true);
      } else {
        jQuery("#allChecked").prop("checked", false);
      }

      // var count = jQuery(".chkItm").filter(":checked").length;
    });

    // jQuery('input[type="checkbox"]').change(function () {

    //   var total_checked = jQuery(".chkItm input[type='checkbox']:checked").length

    //   // jQuery("#d1").html("Total Number of checkbox checked  = " + total_checked );
    // });
  }

  // To get all entries ids for checked

  jQuery("#validationAction").click(function () {
    var selectedAction = jQuery("#selectAction").val();
    jQuery("#selectAction option:selected").attr("id");
    var plugin_name = jQuery("#selectPlugin option:selected").attr("id");
    var filtrby = jQuery("#filtrby option:selected").val();
    var form_id = jQuery("#selectForm option:selected").attr("id");
    var form_name = jQuery("#selectForm option:selected").text();
    var byPassBlackList = jQuery("#selectValidity1").is(":checked");
    var priorityPorcessing = jQuery("#selectValidity2").is(":checked");
    var checkedId = [];
    var checkedEmails = [];
    var count = null;

    if ("-1" == plugin_name) {
      Swal.fire(__("Select Plugin", 'tuxmailer-email-validation'), '', "info");
      return;
    } else if ("-1" == form_id) {
      Swal.fire(__("Select Form", 'tuxmailer-email-validation'), '', "info");
      return;
    }

    // To check number of entries for a form. if its Zero then show 'Entries Not Found'. 
    if (0 == jQuery("input[name='tuxmlr-entries']").length) {
      Swal.fire(__("Entries Not Found", 'tuxmailer-email-validation'), '', "info");
      return;
    }

    jQuery.each(jQuery("input[name='tuxmlr-entries']:checked"), function () {
      checkedId.push(jQuery(this).val());
      checkedEmails.push(jQuery(this).closest("tr").find("td:eq(2)").text());
    });

    var newarr = checkedId.filter((x, y) => checkedId.indexOf(x) == y);
    count = newarr.length;

    if ("selected-entries" == selectedAction && "" == checkedId) {

      Swal.fire(
        '',
        __("Please select the records that you wish to validate", 'tuxmailer-email-validation'),
        "info"
      );
      return;
    } else if (
      "all-entries" == selectedAction ||
      ("selected-entries" == selectedAction && "" != checkedId)
    ) {
      if ("all-entries" == selectedAction) {
        count = jQuery("#selectAction #all-entries").attr("data-entries-count");
      }

      Swal.fire({
        title: __("Are you sure?", 'tuxmailer-email-validation'),
        text: sprintf(
          /* translators: 1: Form name 2: Timestamp */
          __("You will be validating %1$s records", 'tuxmailer-email-validation'),
          count
        ),
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: __('Yes, validate it!', 'tuxmailer-email-validation'),
        cancelButtonText: __("No, cancel!", 'tuxmailer-email-validation'),
      }).then((result) => {
        if (result.isConfirmed) {
          jQuery.ajax({
            url: tuxajaxapi.url,
            type: "POST",
            cache: false,
            data: {
              nonce: tuxajaxapi.nonce,
              action: "bulk_validation_action",
              selectedAction: selectedAction,
              checkedId: checkedId,
              checkedEmails: checkedEmails,
              plugin_name: plugin_name,
              form_id: form_id,
              form_name: form_name,
              byPassBlackList: byPassBlackList,
              priorityPorcessing: priorityPorcessing,
              filtrby: filtrby,
            },

            success: function (response) {

              data = JSON.parse(response);

              if (data.response_code == "http_request_failed") {
                Swal.fire(
                  '', __("We are unable to connect to our back-end servers. Please contact support.", 'tuxmailer-email-validation'),
                  "warning"
                );
              } else if (data.response_code == 422) {
                Swal.fire(
                  '', __("Oops... something went wrong", 'tuxmailer-email-validation') + '<br>' +
                  data.loc +
                  "  \n\n" +
                  data.error_message +
                  "   \n\n" +
                data.type,
                  "error"
                );
              } else if (data.response_code == 401) {

                Swal.fire('', data.error_message, "error"); // If API token is not valid. 
              } else if (data.response_code == 409) {
                Swal.fire('', data.error_message, "error");
              } else if (data.response_code == 200) {
                Swal.fire(
                  __("Request Accepted!", 'tuxmailer-email-validation'),
                  '"uid": "' + data.uid + '"',
                  "success"
                );

                jQuery(".notificationGrup").append(
                  '<div class="notice precessNoti "><p>' +
                  sprintf(
                    /* translators: 1: Form name 2:UID 3: Timestamp */
                    __(
                      "Form Name - %1$s is <strong>processing</strong> and UID is %2$s %3$s. Please refresh the page after some time.",
                      'tuxmailer-email-validation'
                    ),
                    data.form_name,
                    "<i>" + data.uid + "</i>",
                    " ( " + data.time + " )"
                  ) +
                  "</p></div>"
                );
              } else {
                Swal.fire('', __("Oops... something went wrong", 'tuxmailer-email-validation'), "error");
              }

            },

            error: function (jqXHR, exception) {
              var msg = "";
              if (jqXHR.status === 0) {
                msg = __("We are unable to connect to our back-end servers. Please contact support.", 'tuxmailer-email-validation');
              } else if (jqXHR.status == 404) {
                msg = __("Oops... Something went wrong. Please contact support.", 'tuxmailer-email-validation');
              } else if (jqXHR.status == 500) {
                msg = __("Oops... Something went wrong. Please contact support.", 'tuxmailer-email-validation');
              } else if (exception === "parsererror") {
                msg = __("Oops... Something went wrong. Please contact support.", 'tuxmailer-email-validation');
              } else if (exception === "timeout") {
                msg = __("Oops... Something went wrong. Please contact support.", 'tuxmailer-email-validation');
              } else if (exception === "abort") {
                msg = __("Oops... Something went wrong. Please contact support.", 'tuxmailer-email-validation');
              } else {
                msg = __("Oops... Something went wrong. Please contact support.", 'tuxmailer-email-validation');
              }
              Swal.fire('', msg, "error");
            },
          });
        }
      });
    } else {
      Swal.fire(
        '',
        __("Oops... Something went wrong", 'tuxmailer-email-validation'),
        "error"
      );
    }
  });

  function updateValues() {
    var api_key_value = jQuery("#tuxmlr_api_key").val();
    if ("" == api_key_value) {
      // jQuery("#show_message").html(__("Please validate your API key", 'tuxmailer-email-validation'));
    } else {
      jQuery.ajax({
        url: tuxajaxapi.url,
        type: "POST",
        cache: false,
        data: {
          action: "tuxmlr_updated_token_status",
        },
        success: function (response) {

          data = JSON.parse(response);

          if (200 == data.response_code) {
            jQuery("#tuxmlr_api_key").attr("class", "tuxmailer-token-valid");
            jQuery("#tuxmlr_api_key").attr("data-response-code", data.response_code);
          }
          // jQuery("#show_message").html(data.message);
        },
      });
    }
  }
  // Refrence: https://stackoverflow.com/questions/2926227/how-to-do-jquery-code-after-page-loading
  // This Event fire when Dom ,css and images fully loaded.
  jQuery(window).on("load", function () {

    // To delete complete process when clicked on 'x'.
    jQuery(".validateNoti .notice-dismiss").click(function () {
      var uuid = jQuery(this).parent("div").attr("id");

      jQuery.ajax({
        url: tuxajaxapi.url,
        type: "POST",
        cache: false,
        data: {
          nonce: tuxajaxapi.nonce,
          action: "dismiss_completed_notice",
          uuid: uuid,
        },
        success: function (data) {
          console.log(data);
        },
      });
    });


    updateValues();
  });
});
