"use strict";

$(document).ready(function () {
  var assignModal = $('#assignModal');
  var assignModalSelect = assignModal.find('select');
  var submitBtn = $('#assign');

  function ajaxError(XMLHttpRequest, textStatus, errorThrown, res) {
    var response = XMLHttpRequest.responseJSON;
    toastr.error(response.message);
    assignModal.modal('hide');
  }

  function resetIchecks() {
    $('.i-checks').prop('checked', false).iCheck({
      checkboxClass: 'icheckbox_square-green',
      radioClass: 'iradio_square-green'
    });
  }

  assignModal.on('show.bs.modal', function (e) {
    submitBtn.prop('disabled', assignModalSelect.val() === '');
  });
  assignModalSelect.on('change', function () {
    submitBtn.prop('disabled', assignModalSelect.val() === '');
  });
  $('[name="copywriting_assign_order[copywriter]"]').not('.modal-dialog [name="copywriting_assign_order[copywriter]"]').change(function (e) {
    var order = $(e.target).closest('[data-project-id]');
    var orderId = order.data('project-id');
    var writerId = e.target.value;
    assignWriter([orderId], writerId, function (response) {
      toastr.success(response.message);

      if (response.writers) {
        var writerLink = order.find('.j-writer .statistick-data_value a');
        writerLink.text(response.writers[orderId].fullName);
        writerLink.attr('href', response.writers[orderId].editWriterUrl);
        writerLink.removeClass('not-selected');
      }

      resetIchecks();
    });
  });
  $('.copywriting-project').on('ifChanged', '.i-checks', function () {
    if ($('.i-checks:checked').length) {
      $('.multiple-action').removeAttr('disabled');
    } else {
      $('.multiple-action').attr('disabled', 'disabled');
    }
  });
  submitBtn.on('click', function () {
    var selectedItems = $('.copywriting-project .i-checks:checked');
    var orderIds = [];
    selectedItems.map(function (index, element) {
      orderIds.push(element.value);
    });
    var writerId = $(".modal-dialog #copywriting_assign_order_copywriter option:selected").val();

    if (orderIds.length > 0) {
      assignWriter(orderIds, writerId, function (response) {
        assignModal.modal('hide');
        toastr.success(response.message);

        if (response.writers) {
          for (var _i = 0; _i < orderIds.length; _i++) {
            var orderId = orderIds[_i];
            var writerLink = $("[data-project-id=\"" + orderId + "\"] .j-writer .statistick-data_value a");
            writerLink.text(response.writers[orderId].fullName);
            writerLink.attr('href', response.writers[orderId].editWriterUrl);
          }
        }

        resetIchecks();
      });
    }
  });

  function assignWriter(orderIds, writerId, responseCallback) {
    $.ajax({
      url: Routing.generate('copywriting_order_ajax_assign'),
      type: 'POST',
      data: {
        "orderIds": orderIds,
        "copywriter": writerId
      },
      cache: false,
      dataType: 'json',
      success: responseCallback,
      error: ajaxError
    });
  }
});