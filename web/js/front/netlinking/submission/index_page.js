"use strict";

var table = $('.table');
var modal = $('#submission_modal');
table.on('click', '*[data-action="editComment"]', function (e) {
  var closestTr = $(e.target).closest('tr');
  var npcId = closestTr.data('comment-id');
  sendGetRequest(
    Routing.generate('project_comment_modify'),
    {
      commentId: npcId
    },
    function (response) {
      modal.find('.modal-title').html(response.title);
      modal.find('.modal-body').html(response.body);
      modal.modal('show');
      modal.find('.save_comment').click(function (e) {
        var comment = modal.find('form #netlinking_project_comment_comment').val();
        sendPostRequest(
          Routing.generate('project_comment_modify'),
          modal.find('form').serialize(),
          function (data) {
            modal.modal('hide');
            $('#npc_' + npcId).html(comment);
            toastr.success(data.message);
          }
        );
      });
  });
});

table.on('click', '*[data-action="deleteComment"]', function (e) {
  var closestTr = $(e.target).closest('tr');
  $.get(Routing.generate('project_comment_delete', {
    'commentId': closestTr.data('comment-id')
  }), function (response) {
    toastr.success(response.message);
  });
});
