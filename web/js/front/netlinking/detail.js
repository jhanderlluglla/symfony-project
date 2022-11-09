"use strict";

$(document).ready(function () {
  $('.table').on('click', '.rating-like', changeStatus);
  $('.table').on('click', '.rating-dislike', changeStatus);

  function changeStatus(e) {
    var thisRating = $(e.target).parents('.raiting');
    var tr = thisRating.parents("tr");
    var like = thisRating.find(".rating-like");
    var dislike = thisRating.find(".rating-dislike");

    if (like.hasClass('hovered') || dislike.hasClass('hovered')) {
      like.toggleClass('hovered');
      dislike.toggleClass('hovered');
    } else {
      $(e.target).toggleClass('hovered');
    }

    var jobId = tr.data('job-id');

    if (like.hasClass('hovered')) {
      sendRating(jobId, true);
    } else {
      var dislikeModal = $('#dislikeModal');
      dislikeModal.modal('show');
      dislikeModal.find("[name='cancel']").click(function () {
        dislike.removeClass('hovered');
      });
      dislikeModal.find("[name='confirm']").click(function () {
        sendRating(jobId, false, dislikeModal.find("[name='comment']").val());
      });
    }
  }

  function sendRating(jobId, rating) {
    var comment = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
    $.ajax({
      url: Routing.generate('job_ajax_change_rating', {
        jobId: jobId
      }),
      type: 'POST',
      data: {
        "rating": rating,
        "comment": comment
      },
      cache: false,
      dataType: 'json',
      success: function success(response) {
        $('#dislikeModal').modal('hide');
        toastr.success(response.message);
      },
      error: function error() {
        $('#dislikeModal').modal('hide');
        toastr.error(errorMessage);
      }
    });
  }
});