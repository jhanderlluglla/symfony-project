"use strict";

$(document).ready(function () {

  $('[data-action="nextStep"]').click(function (event) {

    var inputsRequired = $('input:visible, select:visible');

    for (var input_i = 0; input_i < inputsRequired.length; input_i++) {
      try {
        if (!inputsRequired[input_i].reportValidity()) {
          return;
        }
      }catch (e) {
        // console.log('catch', e)
      }
    }

    var button = $(event.target);
    var nextItem = button.closest('.steps-item').next('.steps-item');
    var nextItemRow = nextItem.find('.step-rows');

    nextItemRow.slideDown(400);
    nextItem.removeClass('disabled');
    setTimeout(function () {
      button.slideUp(100);
    }, 500);
  });
});