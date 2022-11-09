"use strict";

var id, status;
$('.footable').on('click', '[data-action="acceptRequest"], [data-action="rejectRequest"]', function (event) {
  id = $(this).closest('tr').data('id');
  status = $(event.currentTarget).data('status');
  status === 'accept' ? status = 1 : status = 0;

  if (status == 1) {
    sendData();
  }
});
$('#commentModal .btn-primary').click(function (e) {
  var commentInput = $('#comment');
  var comment = commentInput.val();
  commentInput.val("");
  sendData(comment);
});

function sendData() {
  var comment = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
  var data = {
    'status': status
  };

  if (comment !== null) {
    data.comment = comment;
  }

  $.post(Routing.generate('change_status', {
    'id': id
  }), data, function (response) {
    toastr.success(response.message);
    $('#commentModal').modal('hide');
  }).fail(function () {
    toastr.error(translations.errors['change_status']);
    $('#commentModal').modal('hide');
  });
}