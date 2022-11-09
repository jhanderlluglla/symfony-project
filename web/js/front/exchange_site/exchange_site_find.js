"use strict";

$(document).ready(function () {
  var exchangeSiteProposition = $('#exchangeSiteProposition');
  var erefererAlerts = $('#erefererAlerts');

  var ajaxError = function ajaxError(XMLHttpRequest, textStatus, errorThrown, res) {
    var response = XMLHttpRequest.responseJSON;
    exchangeSiteProposition.find('.sk-spinner_wrap').removeClass('sk-loading');
    exchangeSiteProposition.modal('hide');
    erefererAlerts.find('.modal-title').html(response.title);
    erefererAlerts.find('.modal-body').html("<span style='color: red'>" + response.body + "</span>");
    erefererAlerts.modal('show');
  };

  function validate() {
    exchangeSiteProposition.find('.alert-danger').addClass('hidden').empty();
    var error = true;
    var errorMessage = '';
    var type = exchangeSiteProposition.find('#eStype').val();

    var checkOnEmpty = function checkOnEmpty(findClass) {
      var empty = exchangeSiteProposition.find(findClass).filter(function () {
        return this.value === "";
      });

      if (exchangeSiteProposition.find(findClass).length > empty.length) {} else {
        error = false;
        errorMessage = translations.errors[type].fields;
      }

      return true;
    };

    var checkOnValidUrl = function checkOnValidUrl(findClass) {
      var regURL = /^(https?:\/\/)?([\w\.]+)\.([a-z]{2,6}\.?)(\/[\w\.]*)*\/?$/i;
      var invalidUrl = exchangeSiteProposition.find(findClass).filter(function () {
        return !regURL.test(this.value);
      });

      if (exchangeSiteProposition.find('.writing_ereferer_url').length > invalidUrl.length) {} else {
        error = false;
        errorMessage = translations.errors['wrong_url'];
      }

      return true;
    };

    switch (type) {
      case 'writing_webmaster':
      case 'writing_ereferer':
        checkOnEmpty('.writing_ereferer_url');

        if (error) {
          checkOnEmpty('.writing_ereferer_anchor');
        }

        if (error) {
          checkOnValidUrl('.writing_ereferer_url');
        }

        break;

      case 'submit_your_article':
        break;
    }

    if (!error) {
      exchangeSiteProposition.find('.alert-danger').removeClass('hidden').html(errorMessage);
    }

    return error;
  }

  exchangeSiteProposition.on('show.bs.modal', function (e) {
    var that = $(this);
    var submitButton = that.find('.btn-primary').prop('disabled', true);
    var $invoker = $(e.relatedTarget);
    var id = $invoker.data('id');
    var type = $invoker.data('type');
    var proposition = $invoker.data('proposition');
    var countWords = $invoker.data('countwords');
    exchangeSiteProposition.find('.modal-footer > .btn-primary').show();
    id = typeof id === 'undefined' ? null : id;
    type = typeof type === 'undefined' ? null : type;
    $.ajax({
      type: 'GET',
      url: Routing.generate('user_exchange_site_find_modal'),
      data: {
        'id': id,
        'type': type,
        'proposition_id': proposition,
        'count_words': countWords,
      },
      dataType: 'json',
      success: function success(response) {
        if (response.result !== "fail") {
          submitButton.prop('disabled', false);
        }

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
    $(this).find('.modal-errors').empty();
  }).on('click', '.modal-footer .btn-primary', function (event) {
    event.preventDefault();

    if (!validate()) {
      return;
    }

    var form = $('#add-new-record');
    var file = $(form).find('input[type="file"]');

    if (file.length !== 0 && file.get(0).files.length === 0) {
      $(form).find('.form-group').addClass('has-error');
      return;
    }

    if (form !== undefined) {
      exchangeSiteProposition.find('.sk-spinner_wrap').addClass('sk-loading');
      sendPostRequest(
          Routing.generate('user_exchange_site_find_modal'),
          new FormData(form[0]),
          function (response) {
            var responseBody = "";

            if (response.body !== undefined) {
              responseBody = '<p>' + response.body + '<p>';
            } else if (response.message !== undefined) {
              responseBody = '<p>' + response.message + '<p>';
            }

            if (response.valids !== undefined && response.valids.length > 0) {
              responseBody += '<ul>';
              $.each(response.valids, function (key, valid) {
                responseBody += '<li>' + valid + '</li>';
              });
              responseBody += '</ul>';
            }

            exchangeSiteProposition.find('.modal-body').html(responseBody);
            exchangeSiteProposition.find('.sk-spinner_wrap').removeClass('sk-loading');
            exchangeSiteProposition.find('.modal-footer > .btn-primary').hide();
          },
          function (response) {
            var data = response.responseJSON;
            if (data.message) {
              if (!Array.isArray(data.message)) {
                data.message = [data.message]
              }
              var errorHtml = '';
              for (var key in data.message) {
                errorHtml += '<li>' + data.message[key] + '</li>';
              }
              exchangeSiteProposition.find('.modal-errors').html(errorHtml);
            }
            exchangeSiteProposition.find('.sk-spinner_wrap').removeClass('sk-loading');
          },
          {
            processData: false,
            contentType: false,
            errorHandlerEnabled: false,
          }
        );
    } else {
      exchangeSiteProposition.modal('hide');
    }
  });

  $("#filters_ageMonth").on('input change',function(e){
    var monthPerYear = 12;
    var monthCnt = parseInt($(this).val());
    if (monthCnt > monthPerYear){
      $(this).val(monthPerYear);
    }
    if (monthCnt <= 0){ $(this).val(''); }
  });

});
