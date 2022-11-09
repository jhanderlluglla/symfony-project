"use strict";

$(document).ready(function () {
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
  var userProfile = $('#userProfile');
  var erefererAlerts = $('#erefererAlerts');

  var ajaxError = function ajaxError(XMLHttpRequest, textStatus, errorThrown, res) {
    var response = XMLHttpRequest.responseJSON;
    userProfile.modal('hide');
    erefererAlerts.find('.modal-title').html(response.title);
    erefererAlerts.find('.modal-body').html(response.body);
    erefererAlerts.modal('show');
  };

  userProfile.on('show.bs.modal', function (e) {
    var that = $(this);
    var $invoker = $(e.relatedTarget);
    var id = $invoker.data('id');
    var type = $invoker.data('type');
    id = typeof id === 'undefined' ? null : id;
    type = typeof type === 'undefined' ? null : type;
    $.ajax({
      type: 'GET',
      url: Routing.generate('user_modal'),
      data: {
        'id': id,
        'type': type
      },
      dataType: 'json',
      success: function success(response) {
        that.find('.modal-title').html(response.title);
        that.find('.modal-body').html(response.body);
        that.find('#eSid').val(id);
        that.find('#eStype').val(type);
      },
      error: function error(XMLHttpRequest, textStatus, errorThrown, res) {}
    });
  }).on('hide.bs.modal', function (e) {
    $(this).find('.modal-title').empty();
    $(this).find('.modal-body').empty();
  }).on('click', '.btn-primary', function (event) {
    event.preventDefault();
    var form = $('#add-new-record')[0];
    var data = new FormData(form);
    $.ajax({
      type: 'POST',
      url: Routing.generate('user_modal'),
      data: data,
      cache: false,
      dataType: 'json',
      processData: false,
      // Don't process the files
      contentType: false,
      // Set content type to false as jQuery will tell the server its a query string request
      success: function success(response) {
        if (response.result == 'success') {
          userProfile.modal('hide');

          switch (response.type) {
            case 'modify_balance':
              $('#balance' + response.id).html(response.message + '€');
              break;

            case 'send_message':
              toastr.success(response.message);
              break;
          }
        } else {
          userProfile.find('.modal-body').html(response.body);
        }
      },
      error: ajaxError
    });
  });

    var $permission = $('#user_permission'),
        $role = $('#form_user_role'),
        $onlyWiriter = $('.only_writer');

    function changeVisibilityPermission(roleValue) {
        if (roleValue !== role_writerAdmin) {
            $permission.slideUp(200);
        } else {
            $permission.slideDown(200);
        }
    }

    function showOnlyWriter(roleValue) {
        if (
            roleValue !== role_writer
            && roleValue !== role_writerNetlinking
            && roleValue !== role_writerCopywriting
        ) {
            $onlyWiriter.slideUp(200);
        } else {
            $onlyWiriter.slideDown(200);
        }
    }

    changeVisibilityPermission($role.val());
    showOnlyWriter($role.val());

    $role.on('change', function () {
        changeVisibilityPermission($(this).val());
        showOnlyWriter($(this).val());
    });
});