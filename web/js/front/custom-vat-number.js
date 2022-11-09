$(document).ready(function () {
    var countrySelect = $('.user_country');

    function checkVat() {
        var countryVal = $(countrySelect).val();
        var isEuropeanCountry = +$(countrySelect).find('option[value="' + countryVal + '"]').attr('data-european-country') ? true : false;
        $('.user_vat-number').parents('.form-group').toggleClass('hidden', !isEuropeanCountry);
    }

    checkVat();
    $(countrySelect).on('change', checkVat);
});
