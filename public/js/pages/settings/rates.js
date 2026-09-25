/**
 * Rates settings component
 */
"use strict";
/* global GeneralSettings */
$(function () {
    $("#profile_access_offer_date").on('change',function () {
        $("#is_offer").prop('checked',true);
    });

    $("#is_offer").on('change',function () {
        $("#profile_access_offer_date").val('');
    });

    $('#paid-profile').on('change', function () {
        const key = $(this).attr('id');
        const val = $(this).prop('checked');
        GeneralSettings.updateFlagSetting(key, val);

        if (val) {
            $('.paid-profile-rates').removeClass('d-none');
        } else {
            $('.paid-profile-rates').addClass('d-none');
        }
    });
});

// eslint-disable-next-line no-unused-vars
var RatesSettings = {

};
