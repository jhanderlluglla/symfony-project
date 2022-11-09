"use strict";

var WidgetSettings = function WidgetSettings() {
  var that = this;

  this.getSelectedItems = function () {
    var items = [];
    $('.table input:checkbox:checked').each(function () {
      items.push($(this).val());
    });
    return items;
  };

  this.setRead = function () {
    $('.table input:checkbox:checked').each(function () {
      $(this).prop('checked', false).closest('tr').removeClass('unread').addClass('read');
      $(this).iCheck('update');
    });
  };

  this.deleteItemsFromTable = function () {
    $('.table input:checkbox:checked').each(function () {
      $(this).closest('tr').remove();
    });
  };

  this.itemsWithClass = function (arr, type) {
    var resArr = [].filter.call(arr.rows, function (item) {
      return item.classList.contains(type);
    });
    return resArr.length;
  };

  this.resetInbox = function () {
    var table = document.querySelector('.table.table-hover.table-mail');
    document.getElementById('count').innerHTML = that.itemsWithClass(table, 'unread') + that.itemsWithClass(table, 'read');
  };

  this.clearMessage = function () {
    document.getElementById('message_subject').value = '';
    document.getElementById('message_content').value = '';
  };
};

$(document).ready(function () {
  var ajaxError = function ajaxError(XMLHttpRequest, textStatus, errorThrown, res) {
    var response = XMLHttpRequest.responseJSON;
    toastr.error(response.message);
  };

  toastr.options = {
    "closeButton": true,
    "debug": false,
    "progressBar": true,
    "preventDuplicates": false,
    "positionClass": "toast-top-right",
    "onclick": null,
    "showDuration": "400",
    "hideDuration": "1000",
    "timeOut": "7000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
  };
  var WidgetSettingsObj = new WidgetSettings();
  $('#setRead').on('click', function () {
    var selectedItems = WidgetSettingsObj.getSelectedItems();

    if (selectedItems.length > 0) {
      $.ajax({
        url: Routing.generate('message_ajax_read'),
        type: 'POST',
        data: {
          "messages": selectedItems
        },
        cache: false,
        dataType: 'json',
        success: function success(response) {
          toastr.success(response.message);
          WidgetSettingsObj.setRead();
        },
        error: ajaxError
      });
    }
  });
  $('#delete').on('click', function () {
    var selectedItems = WidgetSettingsObj.getSelectedItems();

    if (selectedItems.length > 0) {
      swal({
        title: translations.modal.delete.title,
        text: translations.modal.delete.text,
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ed5565",
        confirmButtonText: translations.modal.delete.confirmButtonText,
        closeOnConfirm: true
      }, function () {
        if (selectedItems.length > 0) {
          $.ajax({
            url: Routing.generate('message_ajax_delete'),
            type: 'POST',
            data: {
              "messages": selectedItems
            },
            cache: false,
            dataType: 'json',
            success: function success(response) {
              toastr.success(response.message);
              WidgetSettingsObj.deleteItemsFromTable();
              WidgetSettingsObj.resetInbox();
            },
            error: ajaxError
          });
        }
      });
    }
  });
  $('.discard').on('click', function () {
    WidgetSettingsObj.clearMessage();
  });
});