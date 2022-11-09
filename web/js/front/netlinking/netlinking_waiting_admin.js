"use strict";

$(document).ready(function () {
  var assignModal = $('#assignProject');
  var assignModalSelect = $('#assignProject select');
  var $invokerID;
  var submitBtn = $('#assign');

  var ajaxError = function ajaxError(XMLHttpRequest, textStatus, errorThrown, res) {
    var response = XMLHttpRequest.responseJSON;
    if (response === undefined) return;
    assignModal.modal('hide');
    toastr.error(response.body);
  };

  function resetIchecks() {
    $('.i-checks').prop('checked', false).iCheck({
      checkboxClass: 'icheckbox_square-green',
      radioClass: 'iradio_square-green'
    });
  }

  assignModal.on('show.bs.modal', function (e) {
    $invokerID = $(e.relatedTarget).closest('.project-card').find('.i-checks').val();
    submitBtn.prop('disabled', assignModalSelect.val() === '');
  });
  assignModalSelect.on('change', function () {
    submitBtn.prop('disabled', assignModalSelect.val() === '');
  });
  $('.netlink-project').on('ifChanged', '.i-checks', function () {
    if ($('.i-checks:checked').length) {
      $('.multiple-action').removeAttr('disabled');
    } else {
      $('.multiple-action').attr('disabled', 'disabled');
    }
  });
  $('#copy_writer_select select.assign_writer_form').change(function (e) {
    var project = $(e.target).closest('[data-project-id]');
    var projectId = project.data('project-id');
    var writerLink = project.find('.j-writer .block-information__value a');
    var writerId = e.target.value;
    assignWriters([projectId], writerId, function (response) {
      toastr.success(response.message);

      if (response.writers) {
        writerLink.text(response.writers[projectId].fullName);
        writerLink.attr('href', response.writers[projectId].editWriterUrl);
      }

      resetIchecks();
    });
  });
  submitBtn.on('click', function () {
    $(this).prop('disabled', true);
    var projectIds = [];

    if ($invokerID) {
      projectIds.push($invokerID);
    } else {
      var selectedItems = $('.netlink-project .i-checks:checked');
      selectedItems.map(function (index, element) {
        projectIds.push(element.value);
      });
    }

    var writerId = $(".modal-dialog #copy_writer_select_copywriter option:selected").val();

    if (projectIds.length > 0) {
      assignWriters(projectIds, writerId, function (response) {
        assignModal.modal('hide');
        toastr.success(response.message);

        if (response.writers) {
          for (var _i = 0; _i < projectIds.length; _i++) {
            var projectId = projectIds[_i];
            var writerLink = $("[data-project-id=\"" + projectId + "\"] .j-writer .block-information__value a");
            writerLink.text(response.writers[projectId].fullName);
            writerLink.attr('href', response.writers[projectId].editWriterUrl);
          }
        }

        resetIchecks();
      });
    }
  });

  function assignWriters(projectIds, writerId, responseCallback) {
    $.ajax({
      url: Routing.generate('netlinking_assign_mass'),
      type: 'POST',
      data: {
        "projectIds": projectIds,
        "writerId": writerId
      },
      cache: false,
      dataType: 'json',
      success: responseCallback,
      error: ajaxError
    });
  }
});