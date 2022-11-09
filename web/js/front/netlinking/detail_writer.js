"use strict";

$(document).ready(function () {
  var detailWriter = $('#detailWriter');
  var erefererAlerts = $('#erefererAlerts');
  var taskId;

  var ajaxError = function ajaxError(XMLHttpRequest, textStatus, errorThrown, res) {
    var response = XMLHttpRequest.responseJSON;

    if (response === undefined) {
      response = {};
      generateModal_500_error(response);
    }

    detailWriter.modal('hide');
    erefererAlerts.find('.modal-title').html(response.title);
    erefererAlerts.find('.modal-body').html(response.body);
    erefererAlerts.modal('show');
  };

  function updateModalTaskDetails (scheduleTaskId) {
    sendGetRequest(
      Routing.generate('netlinking_detail_writer', {'taskId': scheduleTaskId}),
      function (response) {
        detailWriter.find('.modal-title').html(response.title);
        detailWriter.find('.modal-body').html(response.body);
        detailWriter.modal('show');
      }
    );
  }

  $('.wrapper-content').on('click', '.js_show_task', function () {
    var invoker = $(this).closest('tr');
    taskId = invoker.data('task-id');
    taskId = typeof taskId === 'undefined' ? null : taskId;
    updateModalTaskDetails(taskId);
  });

  $(document).on('click', '.confirm', function() {
    $('.why-impossible').addClass('hidden');
    $('.enter-description').removeClass('hidden');
  })

  detailWriter.on('hide.bs.modal', function (e) {
    $(this).find('.modal-title').empty();
    $(this).find('.modal-body').empty();
  }).on('click', '.job_action_impossible, .job_action_complete', function (event) {
    if($(event.target).hasClass('job_action_impossible')) {
      $('.why-impossible').removeClass('hidden');
      $('.enter-description').addClass('hidden');
    }
    event.preventDefault();

    var commentWrapper = detailWriter.find('.job_comment_wrapper');
    if (commentWrapper.hasClass('hidden')) {
      commentWrapper.hide().removeClass('hidden').slideDown(250);
      return;
    }

    var completed = $(this).hasClass('job_action_complete') ? 1 : 0;
    var comment = detailWriter.find('#comment');
    var jobId = $(this).data('jobid');

    if (comment.val().length === 0) {
      comment.parent().addClass('has-error');
      $('.comment-error').removeClass('hidden');
      return;
    }

    var url;
    if (completed === 1) {
      url = Routing.generate('netlinking_job_complete', {'jobId': jobId});
    } else {
      url = Routing.generate('netlinking_schedule_task_impossible', {'scheduleTaskId': taskId});
    }

    sendPostRequest(
      url,
      {
        comment: comment.val()
      },
      function (response) {
        if (response.status !== true) {
          comment.parent().addClass('has-error');
          var commentError = $('.comment-error');
          commentError.text(response.body);
          commentError.removeClass('hidden');
        } else {
          $('tr[data-task-id="' + taskId + '"]').remove();
          detailWriter.modal('hide');
          toastr.success(response.message);
          if(response.cost) {
            $('#user_balance').text((+$('#user_balance').text() + response.cost).toFixed(2));
          }

        }
      }
    );
  });

  $('.table').on('click', '.set_status', function (e) {
    e.preventDefault();
    var self = $(this);
    var backlink = self.parent().parent().find('input[type=text]').val();
    var backlinkId = self.parent().data('backlink-id');
    var status = self.data('status');
    swal({
      title: translations.modal.confirmation.title,
      text: translations.modal.confirmation.text,
      type: "warning",
      showCancelButton: true,
      confirmButtonColor: "#ed5565",
      confirmButtonText: translations.modal.confirmation.confirmButtonText,
      closeOnConfirm: false
    }, function () {
      $.ajax({
        type: 'POST',
        url: Routing.generate('backlinks_update_status'),
        data: {
          backlinkId: backlinkId,
          status: status,
          backlink: backlink
        },
        cache: false,
        dataType: 'json',
        success: function success(response) {
          swal({
            title: response.title,
            text: response.body,
            type: "success"
          });
          self.closest('tr').remove();
        },
        error: ajaxError
      });
    });
  });

  function hideInfoBlock() {
    $('[data-toggle="tooltip-float"]').removeClass('active');
    $('[data-info="detail-info"]').removeClass('visible');
  }

  $('[data-toggle="tooltip-float"]').on('click', function () {
    var title = $(this).attr('data-title'),
        content = $(this).attr('title'),
        topValue = $(this).offset().top + 60;

    if ($(this).hasClass('active')) {
      hideInfoBlock();
    } else {
      $('[data-info="detail-info"]').addClass('visible').css('top', topValue);
      $('[data-info="detail-info"] .info-box_title').html(title);
      $('[data-info="detail-info"] .info-box_content').html(content);
      $('[data-toggle="tooltip-float"]').removeClass('active');
      $(this).addClass('active');
    }
  });
  $(document).keyup(function (e) {
    if (e.keyCode === 27) {
      hideInfoBlock();
    }
  });
  $(window).on('load resize', function () {
    hideInfoBlock();
  });
});

/**
 * Use in "Do the Task" button: data-confirmation="takeToWorkAction"
 *
 * @param eventSource
 */
function takeToWorkAction(eventSource) {
  sendGetRequest(Routing.generate('schedule_task_do', {scheduleTaskId: eventSource.data('scheduletaskid')}), function (response) {
    if (response.status === true) {
      var modal = eventSource.closest('#detailWriter');
      toastr.success(response.message);
      modal.find('.job_comment_wrapper').removeClass('hidden');
      modal.find('.job_action_do').addClass('hidden');
      modal.find('.job_action_complete').removeClass('hidden').data('jobid', response.jobId);
    }
  });
}
