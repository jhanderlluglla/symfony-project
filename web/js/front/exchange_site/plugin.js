"use strict";

function changeExchangeSiteType(siteId, switcherType, status) {
  var $currentType = $('tr[data-site-id="' + siteId + '"]').find('.site-type_controls');
  var oldType = $currentType.data('currenttype');
  var newType = oldType;

  if (switcherType === oldType) {
    if (status === false) {
      newType = '';
    }
  } else if (oldType === exchangeType || oldType === copywritingType || (oldType === universalType && status === true)) {
    newType = universalType;
  } else {
    if (status === true) {
      newType = switcherType;
    } else {
      if (switcherType === exchangeType) {
        newType = copywritingType;
      } else {
        newType = exchangeType;
      }
    }
  }

  $.get(Routing.generate('plugin_set_type', {'id': siteId, 'type': newType}, false), function (response) {
    if (response.status === "success") {
      toastr.success(response.message);
      if (response.location) {
        window.location = response.location;
        return;
      }

      $currentType.data('currenttype', newType);

      if (switcherType === exchangeType && status === true) {
        $.get(Routing.generate('plugin_set_auto_publish', {'id': siteId}, false), {enable: 1}, function (response) {
          if (response.status !== "success") {
            toastr.error(response.message);
          }
        });
      }
    } else {
      toastr.error(response.message);
    }
  });
}

$(document).ready(function () {

  var siteId;
  var activeCheckbox;
  var activeType;
  var activeChecked;

  var confirmModal = $('#confirmExchangeType');
  var modalCancelBtn = $('#confirmExchangeType .modalCancelBtn');
  var modalConfirmBtn = $('#confirmExchangeType .modalConfirmBtn');
  var requestStatus = false;

  confirmModal.on('hide.bs.modal', function (e) {
    if (!requestStatus) activeCheckbox.prop('checked', true);
  });

  modalCancelBtn.on('click', function (e) {
    requestStatus = true;

    $.get(Routing.generate('plugin_set_auto_publish', {'id': siteId}, false), {enable: 0}, function (response) {
      if (response.status === "success") {
        toastr.success(response.message);
      } else {
        toastr.error(response.message);
      }
    });

    confirmModal.modal('hide');
  });

  modalConfirmBtn.on('click', function (e) {
    requestStatus = true;

    changeExchangeSiteType(siteId, activeType, activeChecked);

    confirmModal.modal('hide');
  });

  $('.footable').on('change', ':checkbox', function (e) {
    activeCheckbox = $(this);
    siteId = $(this).closest('[data-site-id]').data('site-id');
    activeType = $(this).data('type');
    activeChecked = $(this).prop('checked');

    if (activeType === exchangeType && activeChecked === false) {
      requestStatus = false;
      confirmModal.modal('show');
    } else {
      changeExchangeSiteType(siteId, activeType, activeChecked);
    }
  });
});
