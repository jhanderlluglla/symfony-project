"use strict";

$(document).ready(function () {
  var exchangeSiteProposition = $('#exchangeSiteProposition');
  var erefererAlerts = $('#erefererAlerts');

  var ajaxError = function ajaxError(XMLHttpRequest, textStatus, errorThrown, res) {
    var response = XMLHttpRequest.responseJSON;

    if (response === undefined) {
      response = {};
      generateModal_500_error(response);
    }

    exchangeSiteProposition.modal('hide');
    erefererAlerts.find('.modal-title').html(response.title);
    erefererAlerts.find('.modal-body').html(response.body);
    erefererAlerts.modal('show');
  };
  $('#table-proposals-recieved').on('click', '.modification_made_correct', function(event){
    event.preventDefault();
    var id = $(this).data('id');
    $.ajax({
      type: 'GET',
      url: Routing.generate('user_exchange_site_proposals_modification_accept'),
      data: {
        'id': id
      },
      dataType: 'json',
      success: function success(response) {
        if (response.status === 'success') {
          toastr.success(response.message);
          $('#modification_proposition_' + id).remove();
        } else {
          toastr.error(response.message);
        }
      },
      error: ajaxError
    });
  });

  exchangeSiteProposition.on('show.bs.modal', function (e) {
    var that = $(this);
    var $invoker = $(e.relatedTarget);
    var id = $invoker.data('id');
    var mode = $invoker.data('mode');
    id = typeof id === 'undefined' ? null : id;
    mode = typeof mode === 'undefined' ? null : mode;
    if (mode === 'accept') return;
    $.ajax({
      type: 'GET',
      url: Routing.generate('user_exchange_site_proposals_modal'),
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
  }).on('click', '#validate_send', function (event) {
    event.preventDefault();
    var id = $('#proposition_id').val();
    var url = $('#url_validate').val();
    $.ajax({
      type: 'POST',
      url: Routing.generate('user_exchange_site_proposals_validate'),
      data: {
        'id': id,
        'url': url
      },
      cache: false,
      dataType: 'json',
      success: function success(response) {
        exchangeSiteProposition.find('.modal-title').html(response.title);
        exchangeSiteProposition.find('.modal-body').html(response.body);
        $('#proposition_' + response.id).remove();
      },
      error: function error(XMLHttpRequest, textStatus, errorThrown, res) {
        var response = XMLHttpRequest.responseJSON;
        $('.alert-danger').removeClass('hidden').html('<p>' + response.error + '</p>');
      }
    });
  }).on('click', '#refuse_send', function (event) {
    event.preventDefault();
    var id = $('#proposition_id').val();
    var comment = $('#comment_refuse').val();
    $.ajax({
      type: 'POST',
      url: Routing.generate('user_exchange_site_proposals_refuse'),
      data: {
        'id': id,
        'comment': comment
      },
      cache: false,
      dataType: 'json',
      success: function success(response) {
        exchangeSiteProposition.find('.modal-body').html(response.body);
      },
      error: ajaxError
    });
  }).on('click', '#modification_send', function (event) {
    event.preventDefault();
    var id = $('#proposition_id').val();
    var comment = $('#comment_modification').val();
    $.ajax({
      type: 'POST',
      url: Routing.generate('user_exchange_site_proposals_modification'),
      data: {
        'id': id,
        'comment': comment
      },
      cache: false,
      dataType: 'json',
      success: function success(response) {
        exchangeSiteProposition.find('.modal-body').html(response.body);
      },
      error: ajaxError
    });
  }).on('click', '#modification_refuse_send', function (event) {
    event.preventDefault();
    var id = $('#proposition_id').val();
    var comment = $('#comment_refuse').val();
    var final = $('#modification_refuse_final').is(':checked') ? 1 : 0;
    $.ajax({
      type: 'POST',
      url: Routing.generate('user_exchange_site_proposals_modification_refuse'),
      data: {
        'id': id,
        'comment': comment,
        'final': final
      },
      cache: false,
      dataType: 'json',
      success: function success(response) {
        $('#modification_proposition_' + id).remove();
        exchangeSiteProposition.find('.modal-body').html(response.body);
      },
      error: ajaxError
    });
  });

  $('#exchange_proposition_table').on('click', '.js-accept-send', function (event) {
    event.preventDefault();
      $.ajax({
        type: 'GET',
        url: Routing.generate('user_exchange_site_proposals_accept'),
        data: {
          'id': $(this).data('id')
        },
        cache: false,
        dataType: 'json',
        success: function success(response) {
          exchangeSiteProposition.find('.modal-title').html(response.title);
          exchangeSiteProposition.find('.modal-body').html(response.body);
          $(event.target).html( $(event.target).data('success-view'))
            .data('mode', 'validation')
            .removeClass('js-accept-send')
          ;
        },
        error: ajaxError
      });
    });

  $('.tab-content').on('click', '.delete_proposal', function (event) {
      event.preventDefault();
      var href = $(this).attr('href');
      var tr = $(this).closest('tr');
      swal({
          title: translations.modal.confirmation.title,
          text: translations.modal.confirmation.text,
          type: "warning",
          cancelButtonText: translations.modal.cancel.text,
          showCancelButton: true,
          confirmButtonColor: "#ed5565",
          confirmButtonText: translations.modal.confirmation.confirmButtonText,
          closeOnConfirm: true
      }, function () {
          $.get(href, function (data) {
              try {
                  if (data.status === 'success') {
                      tr.fadeOut(500, function() {
                          $(this).remove();
                      });
                  }
              } catch (e) {
              }
          });
      });
  });
});
