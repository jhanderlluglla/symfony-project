"use strict";

var paypal = $('.j-replenish_paypal'),
    wireTransfer = $('.j-replenish_wire_transfer'),
    paymentMethod = 'paypal',
    //default payment method
replenishAmount = $('.j-replenish_amount-input'),
    amountBtn = $('.j-replenish_amount-btn'),
    resultTable = $('.j-replenish_amount-table'),
    amountGroup = replenishAmount.parents('.step-rows_flex-group'),
    // for "has-error" class
errMessage = amountGroup.siblings('.error-message'),
    nextBtn = $('.j-amount'),
    // "next step"-btn at Step 2
confirmCheckbox = $('#wireTransfer-confirm'),
    finishBtn = $('.j-finish'),
    commission,
    vatAmount,
    billableAmount,
    value,
    minValue,
    valueValidity = false;
paypal.parents('.radio').append('<div class="helper-text">' + translations.replenish.min_paypal + '</div>');
wireTransfer.parents('.radio').append('<div class="helper-text">' + translations.replenish.min_wire_transfer + '</div>');

function toggleIban(time) {
  if (wireTransfer.prop('checked')) {
    $('#iban').slideDown(time);
    $('.j-paypal-content, .j-paypal-content-table').css('display', 'none');
    $('.j-wireTransfer-content').css('display', 'block');
    paymentMethod = 'wireTransfer';
    confirmCheckbox.prop('required', true);
  }

  if (paypal.prop('checked')) {
    $('#iban').slideUp(time);
    $('.j-paypal-content').css('display', 'block');
    $('.j-paypal-content-table').css('display', 'table-row');
    $('.j-wireTransfer-content').css('display', 'none');
    paymentMethod = 'paypal';
    confirmCheckbox.prop('required', false);
  }
}

toggleIban(0);
$('.j-replenish_requestType :radio').change(function () {
  toggleIban(200);
  replenishAmount.val('');
  $('[data-result="finish-wireTransfer"], [data-result="amount-paypal"]').html("-");
  finishBtn.prop('disabled', true);
});

function checkAmount() {
  value = +replenishAmount.val();
  minValue = paymentMethod === 'paypal' ? paypalMinVal : wireTransferMinVal;

  if (value < minValue) {
    errMessage.removeClass('hidden');
    nextBtn.prop('disabled', true);
  } else {
    errMessage.addClass('hidden');
    amountGroup.removeClass('has-error');
    valueValidity = true;
  }
}

replenishAmount.on('change', function () {
  checkAmount();
  $('.j-payment-verify').toggleClass('hidden', !valueValidity);
  confirmCheckbox.prop('checked', false);
  finishBtn.prop('disabled', true);
  resultTable.addClass('hidden');
  $('[data-result="amount-billable"]').html(value + "€");
});

function calculateAmount() {
  value = +replenishAmount.val();
  minValue = paymentMethod === 'paypal' ? paypalMinVal : wireTransferMinVal;

  if(!$.isNumeric(value) && value < minValue) {
    commission = '0';
    vatAmount = '0';
    billableAmount = '0';
    nextBtn.prop('disabled', true);
    amountGroup.addClass('has-error');
    return;
  }

  billableAmount = value;
  if(paymentMethod === 'paypal') {
    commission = +(value * paypalPercent / 100).toFixed(2);
    billableAmount += commission;
  }
  vatAmount = +(billableAmount * vatPercent / 100).toFixed(2);
  billableAmount += vatAmount;
  resultTable.removeClass('hidden');
  nextBtn.prop('disabled', false);
  amountGroup.removeClass('has-error');
}

amountBtn.on('click', function (e) {
  calculateAmount();
  if (paypal.prop('checked')) {
    finishBtn.prop('disabled', false);
  }
  $('#commissionAmount, [data-result="commission-paypal"]').html(commission + "€");
  $('#vatAmount, [data-result="vat-amount"]').html(vatAmount + "€");
  $('#billableAmount, [data-result="finish-paypal"]').html(billableAmount + "€");
});
confirmCheckbox.on('change', function () {
  checkAmount();
  if (valueValidity) finishBtn.prop('disabled', !confirmCheckbox.prop('checked'));
});
