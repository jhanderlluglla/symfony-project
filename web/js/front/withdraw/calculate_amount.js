"use strict";

var withdrawAmount = $('.j-withdraw-amount input[type="text"]');
var withdrawAmountGroup = withdrawAmount.parents('.step-rows_flex-group');
var withdrawAmountBtn = $('.j-withdraw-amount_btn');
var withdrawConfirm = $('.j-withdraw-confirm');
var sendBtn = $('.j-send-btn');
sendBtn.prop('disabled', !withdrawConfirm.prop('checked'));
withdrawConfirm.on('change', function () {
  sendBtn.prop('disabled', !withdrawConfirm.prop('checked'));
});
withdrawAmountBtn.on('click', function (e) {
  var value = parseInt(withdrawAmount.val());
  var nextBtn = $(this).parents('.steps-item').find('[data-action="nextStep"]');
  var commission, billableAmount;

  if ($.isNumeric(value) && value > 0) {
    withdrawAmount.val(value);
    commission = value * withdrawPercent / 100;
    billableAmount = value - commission;
    withdrawAmountGroup.removeClass('has-error');
    nextBtn.prop('disabled', false);
  } else {
    withdrawAmount.val('');
    commission = '0';
    billableAmount = '0';
    withdrawAmountGroup.addClass('has-error');
    nextBtn.prop('disabled', true);
  }

  $('#commissionAmount').html(commission + "€");
  $('#billableAmount').html(billableAmount + "€");
  var template = $("[data-confirm-message]").data('confirm-message');
  withdrawConfirm.parents('label.required').contents().last()[0].textContent = template.replace('%billable_amount%', billableAmount);
});

function invoiceValidate(invoiceFileInput) {
  var fileExtError = $('#file_ext_error');
  fileExtError.addClass('hidden');
  var ext = invoiceFileInput.val().split('.').pop().toLowerCase();

  if (ext === 'pdf') {
    return true;
  }

  invoiceFileInput.val('');
  fileExtError.removeClass('hidden');
  return false;
}