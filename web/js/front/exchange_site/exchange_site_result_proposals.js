"use strict";

$(document).ready(function () {
  var exchangeSiteResultProposals = $('#exchangeSiteResultProposals');
  var erefererAlerts = $('#erefererAlerts');

  var ajaxError = function ajaxError(XMLHttpRequest, textStatus, errorThrown, res) {
    var response = XMLHttpRequest.responseJSON;
    exchangeSiteResultProposals.modal('hide');
    erefererAlerts.find('.modal-title').html(response.title);
    erefererAlerts.find('.modal-body').html(response.body);
    erefererAlerts.modal('show');
  };

  exchangeSiteResultProposals.on('show.bs.modal', function (e) {
    var that = $(this);
    var $invoker = $(e.relatedTarget);
    var id = $invoker.data('id');
    var mode = $invoker.data('mode');
    id = typeof id === 'undefined' ? null : id;
    mode = typeof mode === 'undefined' ? null : mode;
    $.ajax({
      type: 'GET',
      url: Routing.generate('user_exchange_site_result_proposals_modal'),
      data: {
        'id': id,
        'mode': mode
      },
      dataType: 'json',
      success: function success(response) {
        that.find('.modal-title').html(response.title);
        that.find('.modal-body').html(response.body);
      },
      error: ajaxError
    });
  }).on('hide.bs.modal', function (e) {
    $(this).find('.modal-title').empty();
    $(this).find('.modal-body').empty();
  }).on('click', '#re_propose_send', function (event) {
    event.preventDefault();
    var id = $('#proposition_id').val();
    $.ajax({
      type: 'GET',
      url: Routing.generate('user_exchange_site_proposals_accept'),
      data: {
        'id': id
      },
      cache: false,
      dataType: 'json',
      success: function success(response) {
        exchangeSiteResultProposals.find('.modal-title').html(response.title);
        exchangeSiteResultProposals.find('.modal-body').html(response.body);
      },
      error: ajaxError
    });
  }).on('click', '#vote_send', function (event) {
    event.preventDefault();
    var id = $('#proposition_id').val();
    var rating = $('#rating_input').val();
    var comment = $('#comment_vote').val();
    $.ajax({
      type: 'POST',
      url: Routing.generate('user_exchange_site_result_proposals_vote'),
      data: {
        'id': id,
        'rating': rating,
        'comment': comment
      },
      cache: false,
      dataType: 'json',
      success: function success(response) {
        exchangeSiteResultProposals.find('.modal-title').html(response.title);
        exchangeSiteResultProposals.find('.modal-body').html(response.body);
      },
      error: ajaxError
    });
  }).on('click', '#modification_send', function (event) {
    event.preventDefault();
    var id = $('#proposition_id').val();
    var comment = $('#comment_modification').val();
    $.ajax({
      type: 'POST',
      url: Routing.generate('user_exchange_site_result_proposals_modification'),
      data: {
        'id': id,
        'comment': comment
      },
      cache: false,
      dataType: 'json',
      success: function success(response) {
        exchangeSiteResultProposals.find('.modal-body').html(response.body);
        $('#action' + id).html(response.message);
      },
      error: ajaxError
    });
  });
});