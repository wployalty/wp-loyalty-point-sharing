if (typeof wlps_jquery === 'undefined') {
    var wlps_jquery = jQuery.noConflict();
}
wlps = window.wlps || {};
(function (wlps) {

    /** Points Validation Function **/
    wlps.validatePoints = function (points) {
        const availableUserPoints = wlps_frontend_data.available_user_points;
        const maxPoints = wlps_frontend_data.max_transfer_points;

        if (points < 1) return 'Points must be at least 1.';
        if (points > availableUserPoints) return `You only have ${availableUserPoints} points available.`;
        if (points > maxPoints) return `Maximum is ${maxPoints} points.`;
        return ''; // valid
    };

    /** Open / Close Modal Functions **/
    wlps.openSharePointsModal = function (event) {
        if (event) event.preventDefault();
        wlps_jquery('#wlps-share-points-modal').show();
    };

    wlps.closeSharePointsModal = function () {
        const form = wlps_jquery('#wlps-transfer-form');
        wlps_jquery('#wlps-share-points-modal').hide();
        form[0].reset();
    };

    /** Form Submission Handler **/
    wlps.handleTransferFormSubmit = function (event) {
        if (event) event.preventDefault();
        const form = wlps_jquery('#wlps-transfer-form');
        const pointsInput = wlps_jquery('#transfer-points');
        const pointsError = wlps_jquery('#transfer-points-error');
        const submitBtn = form.find('button[type="submit"]');

        const points = parseInt(pointsInput.val()) || 0;
        const errorMsg = wlps.validatePoints(points);

        if (errorMsg) {
            pointsError.text(errorMsg).show();
            submitBtn.prop('disabled', true);
            return;
        } else {
            pointsError.hide();
            submitBtn.prop('disabled', false);
        }

        // Show confirmation modal
        wlps.showConfirmModal('Type CONFIRM to proceed with points transfer:', true, function (confirmed) {
            if (!confirmed) return;

            alertify.set('notifier', 'position', 'top-right');

            const formArray = form.serializeArray();
            const formData = {};
            wlps_jquery.each(formArray, function (_, field) {
                formData[field.name] = field.value;
            });
            formData['action'] = 'wlps_transfer_points';
            formData['wlps_transfer_points_nonce'] = wlps_frontend_data.wlps_transfer_points_nonce;

            wlps_jquery.ajax({
                url: wlps_frontend_data.ajax_url,
                method: 'POST',
                data: formData,
                success: function (response) {
                    const data = response.data || {};
                    const message = data.message || 'Something went wrong!';

                    if (!response.success) {
                        if (data.field_error) {
                            for (const key in data.field_error) {
                                const errorText = data.field_error[key];
                                wlps_jquery(`#wlps-transfer-form .wlps_${key}_value_block`).after(
                                    `<span class="wlps-error" style="color:red;">${errorText}</span>`
                                );
                            }
                        }
                        alertify.error(message);
                    } else {
                        alertify.success(message);
                        form[0].reset();
                        pointsError.hide();
                        submitBtn.prop('disabled', false);
                        wlps.closeSharePointsModal();
                    }
                },
                error: function (err) {
                    console.error(err);
                    alertify.error('Something went wrong. Please try again.');
                }
            });
        });
    };

    /** Reusable Confirm Modal **/
    wlps.showConfirmModal = function (message, requireInput, callback) {
        const modal = wlps_jquery('#wlps-alert-modal');
        const messageBox = modal.find('#wlps-alert-message');
        const inputBox = modal.find('#wlps-alert-input');
        const okBtn = modal.find('#wlps-alert-ok');
        const cancelBtn = modal.find('#wlps-alert-cancel');
        const closeBtn = modal.find('.wlps-close-alert-modal');

        messageBox.text(message).show();
        if (requireInput) inputBox.show().val(''); else inputBox.hide();

        modal.show();

        okBtn.off('click').on('click', function () {
            if (requireInput && inputBox.val().trim() !== 'CONFIRM') {
                messageBox.text('You must type CONFIRM to proceed.');
                inputBox.val('').focus();
                return;
            }
            modal.hide();
            callback(true);
        });

        cancelBtn.off('click').on('click', function () {
            modal.hide();
            callback(false);
        });
        closeBtn.off('click').on('click', function () {
            modal.hide();
            callback(false);
        });
    };

    /** Live Validation for points input while typing **/
    wlps_jquery(document).ready(function () {
        const pointsInput = wlps_jquery('#transfer-points');
        const pointsError = wlps_jquery('#transfer-points-error');
        const submitBtn = wlps_jquery('#wlps-transfer-form button[type="submit"]');

        pointsInput.on('input', function () {
            const points = parseInt(pointsInput.val()) || 0;
            const errorMsg = wlps.validatePoints(points);

            if (errorMsg) {
                pointsError.text(errorMsg).show();
                submitBtn.prop('disabled', true);
            } else {
                pointsError.hide();
                submitBtn.prop('disabled', false);
            }
        });
    });

})(wlps);
