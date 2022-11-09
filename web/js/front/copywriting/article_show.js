"use strict";

var ajaxError = function ajaxError(XMLHttpRequest, textStatus, errorThrown, res) {
  var response = XMLHttpRequest.responseJSON;
  toastr.error(response.message);
};

$('.rating-clickable').on('click', function () {
  if ($(this).hasClass('rating-dislike')) {
    $('.rating-like').removeClass('hovered');
  } else {
    $('.rating-dislike').removeClass('hovered');
  }

  if ($(this).hasClass('hovered')) {
    $(this).removeClass('hovered');
    changeRating();
  } else {
    $(this).addClass('hovered');

    if ($(this).hasClass('rating-dislike')) {
      $('#dislikeModal').modal('show');
    } else {
      changeRating();
    }
  }
});
$('.rating-cancel').on('click', function () {
  $('.rating-dislike').removeClass('hovered');
});

function changeRating() {
  var rating = null;

  if ($('.rating-like').hasClass('hovered')) {
    rating = true;
  } else if ($('.rating-dislike').hasClass('hovered')) {
    rating = false;
  }

  var comment = $('#dislike-comment').val();
  var trimmedComment = comment.trim();
  if(!rating && comment !== "" && trimmedComment === ""){
    toastr.error(translations.errors['empty_comment']);
    return
  }

  $.ajax({
    url: Routing.generate('copywriting_order_ajax_change_rating', {
      id: orderId
    }),
    type: 'POST',
    data: {
      "rating": rating,
      "comment": $('#dislike-comment').val()
    },
    cache: false,
    dataType: 'json',
    success: function success(response) {
      $('#dislikeModal').modal('hide');
      toastr.success(response.message);
    },
    error: ajaxError
  });
}
