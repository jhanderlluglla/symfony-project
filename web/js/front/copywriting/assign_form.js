"use strict";

$(document).ready(function () {
    $('.js_assign_writer_form').each(function () {
        let language = $(this).closest('[data-language]').data('language');

        if (!language) {
          return;
        }

        $(this).find('option').hide();
        $(this).find('option[data-language="' + language + '"]').show();

        $(this).trigger('chosen:updated');
    });
});
