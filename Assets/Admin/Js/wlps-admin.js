if (typeof (wlps_jquery) == 'undefined') {
    wlps_jquery = jQuery.noConflict();
}

wlps = window.wlps || {};
(function (wlps) {
    wlps.saveSettings = function () {
        let data = wlps_jquery('#wlps-main #wlps-settings #wlps-settings_form').serializeArray();
        wlps_jquery('#wlps-main #wlps-settings #wlps-setting-submit-button').attr('disabled', true);
        wlps_jquery('#wlps-main #wlps-settings .wlps-error').remove();
        wlps_jquery("#wlps-main #wlps-settings #wlps-setting-submit-button span").html(wlps_localize_data.saving_button_label);
        wlps_jquery("#wlps-main #wlps-settings .wlps-button-block .spinner").addClass("is-active");
        data.push({name: 'wlps_nonce', value: wlps_localize_data.wlps_setting_nonce});
        wlps_jquery.ajax({
            data: data,
            type: 'post',
            url: wlps_localize_data.ajax_url,
            error: function (request, error) {
            },
            success: function (json) {
                alertify.set('notifier', 'position', 'top-right');
                wlps_jquery('#wlps-main #wlps-settings #wlps-setting-submit-button').attr('disabled', false);
                wlps_jquery("#wlps-main #wlps-settings #wlps-setting-submit-button span").html(wlps_localize_data.saved_button_label);
                wlps_jquery("#wlps-main #wlps-settings .wlps-button-block .spinner").removeClass("is-active");
                if (json.error) {
                    if (json.message) {
                        alertify.error(json.message);
                    }

                    if (json.field_error) {
                        wlps_jquery.each(json.field_error, function (index, value) {
                            //alertify.error(value);
                            wlps_jquery(`#wlps-main #wlps-settings #wlps-settings_form .wlps_${index}_value_block`).after('<span class="wlps-error" style="color: red;">' + value + '</span>');
                        });
                    }
                } else {
                    alertify.success(json.message);
                    setTimeout(function () {
                        wlps_jquery("#wlps-main #wlps-settings .wlps-button-block .spinner").removeClass("is-active");
                        location.reload();
                    }, 800);
                }
                if (json.redirect) {
                    window.location.href = json.redirect;
                }
            }
        });
    };

    wlps.enableSharePoint = function (id) {
        if (wlps_jquery('#wlps-main #wlps-settings #' + id).is(':checked')) {
            wlps_jquery('#wlps-main #wlps-settings #' + id).val('yes')
            wlps_jquery('#wlps-main #wlps-settings #' + id + '_section').css('display', 'block');
        } else {
            wlps_jquery('#wlps-main #wlps-settings #' + id).val('no')
            wlps_jquery('#wlps-main #wlps-settings #' + id + '_section').css('display', 'none');
        }
    }
    /**
     * filter status form action block
     */
    wlps.filterPoints = function (form_id, value) {
        wlps_jquery(form_id + " input[name=\"status_sort\"]").val(value);
        wlps_jquery(form_id).submit();
    }

})
(wlps);

