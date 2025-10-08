if (typeof (wlps_jquery) === 'undefined') {
    var wlps_jquery = jQuery.noConflict();
}
wlps = window.wlps || {};

wlps.initSharePointsModal = function () {
    const openBtn = wlps_jquery('#wlps-open-share-modal');
    const modal = wlps_jquery('#wlps-share-points-modal');
    const closeBtn = modal.find(".wlps-close-modal");

    if (openBtn.length && modal.length) {

        // Open modal on click
        openBtn.on('click', function (e) {
            e.preventDefault();
            modal.show();
        });

        // Close Button
        if (closeBtn.length) {
            closeBtn.on('click', function () {
                modal.hide();
            });
        }

        // Handle form
        const form = modal.find('#wlps-transfer-form');
        const pointsInput = wlps_jquery('#transfer-points');
        const pointsError = wlps_jquery('#transfer-points-error');
        const submitBtn = form.find('button[type="submit"]');

        // Live validation while typing
        pointsInput.on('input', function () {
            const points = parseInt(pointsInput.val()) || 0;
            const availableUserPoints = wlps_frontend_data.available_user_points;
            const maxPoints = wlps_frontend_data.max_transfer_points;
            if (points > availableUserPoints) {
                pointsError.text(`You only have ${availableUserPoints} points available`).show();
                submitBtn.prop('disabled', true);
            } else if (points > maxPoints) {
                pointsError.text(`Maximum is ${maxPoints} points.`).show();
                submitBtn.prop('disabled', true);
            } else if (points < 1) {
                pointsError.text(` Points must be at least 1.`).show();
                submitBtn.prop('disabled', true);
            } else {
                pointsError.hide();
                submitBtn.prop('disabled', false);
            }
        });

        // Handle form submission
        form.on('submit', function (e) {
            e.preventDefault();

            wlps.showConfirmModal('Type CONFIRM to proceed with points transfer:', true, function (confirmed) {
                if (!confirmed) return;

                // Info toast
                alertify.set('notifier', 'position', 'top-right');

                let formArray = form.serializeArray();
                let formData = {};
                wlps_jquery.each(formArray, function (_, field) {
                    formData[field.name] = field.value;
                });
                formData['action'] = 'wlps_transfer_points';

                wlps_jquery.ajax({
                    url: wlps_frontend_data.ajax_url,
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        const message =
                            response.data?.message ||
                            response.message ||
                            'Something went wrong!';

                        if (response.success === false) {
                            alertify.error(message); //
                        } else {
                            alertify.success(message || 'Points transferred successfully!');
                            form[0].reset();
                            pointsError.hide();
                            submitBtn.prop('disabled', false);
                            modal.hide();
                        }
                    },
                    error: function (err) {
                        console.error(err);
                        alertify.error('Something went wrong. Please try again.');
                    }
                });
            });
        });

    } else {
        console.warn('Share Points modal or button not found in DOM');
    }
};

// Custom reusable confirm modal
wlps.showConfirmModal = function (message, requireInput, callback) {
    const modal = wlps_jquery('#wlps-alert-modal');
    const messageBox = modal.find('#wlps-alert-message');
    const inputBox = modal.find('#wlps-alert-input');
    const okBtn = modal.find('#wlps-alert-ok');
    const cancelBtn = modal.find('#wlps-alert-cancel');
    const closeBtn = modal.find(".wlps-close-alert-modal");

    // Set message
    messageBox.text(message);
    messageBox.show();

    // Show or hide input
    if (requireInput) {
        inputBox.show().val('');
    } else {
        inputBox.hide();
    }

    modal.show();

    // Handle OK
    okBtn.off('click').on('click', function () {
        if (requireInput) {
            const val = inputBox.val().trim();
            if (val !== 'CONFIRM') {
                messageBox.text('❌ You must type CONFIRM to proceed.');
                inputBox.val('').focus();
                return;
            }
        }
        modal.hide();
        callback(true);
    });

    // Handle Cancel
    cancelBtn.off('click').on('click', function () {
        modal.hide();
        callback(false);
    });

    // Handle Close (X button)
    closeBtn.off('click').on('click', function () {
        modal.hide();
        callback(false);
    });
};

// Initialize after DOM is ready
wlps_jquery(document).ready(function () {
    wlps.initSharePointsModal();
});
