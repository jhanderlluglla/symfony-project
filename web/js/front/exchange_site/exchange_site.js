"use strict";

var percents = {};

function hasOwnProperty(obj, prop) {
  var proto = obj.__proto__ || obj.constructor.prototype;
  return prop in obj && (!(prop in proto) || proto[prop] !== obj[prop]);
}

function getPrice() {
  var input = $('#exchange_site_url');
  var val = input.val();
  var id = input.data('id');
  var type = input.data('type');
  var language = $('#admin_exchange_site_language').val();
  var exchangeSiteUrlError = $('#exchange_site_url_error');
  var exchangeSiteUrlErrorBox = $('.info-box.exchange_site_url_error');
  exchangeSiteUrlErrorBox.addClass('hidden');
  input.parents('.form-group').removeClass('has-error');
  var form = $('form');
  var submit = form.find('button[type="submit"]');
  var nextStep = form.find('.steps-item').first();
  submit.prop('disabled', 1);

  if (nextStep.length) {
    nextStep.find('[data-action="nextStep"]').prop('disabled', 1);
  }

  $.ajax({
    type: 'GET',
    url: Routing.generate('admin_exchange_site_check_credits'),
    data: {
      'url': val,
      'id': id,
      'type': type,
      'language': language
    },
    dataType: 'json',
    success: function success(response) {
      $('#credits_maximum').parent().show();
      $('#credits_maximum').html(response.cred + "€");
      $('#exchange_site_maximum_credits').val(response.cred);
      $('#exchange_site_trust_flow').val(response.trust_flow);
      $('#exchange_site_ref_domains').val(response.ref_domains !== -1 ? response.ref_domains : 0);
      $('#exchange_site_alexa_rank').val(response.alexa_rank);
      $('#exchange_site_age').val(response.domain_creation);
      percents = response.percents;
      $('#admin_exchange_site_credits').change(calculateFinalPrice);
      if ($('#admin_exchange_site_credits').val() != 0) {
          calculateFinalPrice();
      }
      submit.prop('disabled', 0);
      input.parents('.form-group').find('.alert-danger').remove();

      if (nextStep.length) {
        nextStep.find('[data-action="nextStep"]').prop('disabled', 0);
      }
    },
    error: function error(XMLHttpRequest, textStatus, errorThrown, res) {
      var response = XMLHttpRequest.responseJSON;
      exchangeSiteUrlErrorBox.removeClass('hidden');
      input.parents('.form-group').addClass('has-error');
      exchangeSiteUrlError.html(response.message);

      if (response !== undefined) {
        switch (response.section) {
          case 'duplicate':
            if (hasOwnProperty(response, 'cred')) {
              $('#credits_maximum').html(response.cred + "€");
            }

            break;
        }
      }
    }
  });
}

function calculateFinalPrice() {
  var price = $('#admin_exchange_site_credits').val();

  if (price !== "") {
    var template = $('#final-price-template').text();
    var finalPrice = price - price * (percents['commission_percent'] / 100);
    var finalPriceMessage = template.replace('%percent%', percents['commission_percent']);
    finalPriceMessage = finalPriceMessage.replace('%final_price%', finalPrice.toFixed(2));
    $('.final-price').text(finalPriceMessage);
  }
}

$(document).ready(function () {
  $('form[name="admin_exchange_site"]').on('keyup keypress', function (e) {
    var keyCode = e.keyCode || e.which;

    if (keyCode === 13) {
      e.preventDefault();
      return false;
    }
  }).on('click', '.duplicate_copywriting, .duplicate_exchange', function (e) {
    var id = $(this).data("id");
    $.ajax({
      type: 'PATCH',
      url: Routing.generate('plugin_set_type', {
        id: id
      }),
      data: {
        type: 'universal'
      },
      dataType: 'json',
      success: function success(response) {
        if (response.status === "success") {
          toastr.success(response.message);

          if (response.location) {
            window.location = response.location;
          } else if (response.resultLocation) {
            window.location = response.resultLocation;
          }
        } else {
          toastr.error(response.message);
        }
      },
      error: function error(XMLHttpRequest, textStatus, errorThrown, res) {
        var response = XMLHttpRequest.responseJSON;
        toastr.error(response.message);
      }
    });
  });
  $('#exchange_site_tags').tagsinput({
    tagClass: 'label label-white',
    maxTags: 3
  });
  $('#exchange_site_url').blur(getPrice);
  $('#button_api_key').click(function (event) {
    $.ajax({
      type: 'GET',
      url: Routing.generate('admin_exchange_site_get_api_key'),
      dataType: 'json',
      success: function success(response) {
        $('.copy-action_right').val(response.api_key);
        $('.generated-key').removeClass('hidden');
        var clipboard = new Clipboard('#copy_api_key');
        clipboard.on('success', function (e) {
          toastr.success(translations.successCopied);
        });
        $('#api_key_hidden').val(response.api_key);
      },
      error: function error(XMLHttpRequest, textStatus, errorThrown, res) {}
    });
  });
  if ($('#admin_exchange_site_hideUrl').prop("checked")) $('#trusted_webmaster').css('display', 'block');
  $('#admin_exchange_site_hideUrl').change(function (event) {
    if ($(this).is(':checked')) {
      $('#trusted_webmaster').fadeIn(200);
    } else {
      $('#trusted_webmaster').fadeOut(200);
    }
  });
  $('#downloadRenamedPlugin').prop('disabled', $(this).val() === "");
  $('#fileName').on('change', function () {
    $('#downloadRenamedPlugin').prop('disabled', $(this).val() === "");
  });
  if($('#exchange_site_url').val()) {
      getPrice();
  }
  $('#admin_exchange_site_language').on('change', function () {
      if($('#exchange_site_url').val()) {
          getPrice();
      }
  });
  showHideAdditionalExternalLink($('#admin_exchange_site_additionalExternalLink').prop("checked"))
  $('#admin_exchange_site_additionalExternalLink').change(function (event) {
      showHideAdditionalExternalLink($(this).is(':checked'))
  });

  function updateCategoryList(language) {
    sendGetRequest(Routing.generate('category_list', {language: language}),
        {},function (data) {
          var categoryInput = $('#admin_exchange_site_categories');
          categoryInput.html('');
          for(var i in data) {
            categoryInput.append('<option value="'+data[i].id+'">'+('>'.repeat(data[i].lvl-1))+' '+data[i].name+'</option>');
          }
          categoryInput.trigger("chosen:updated");
      });
  }

  var languageInput = $('#admin_exchange_site_language');
  languageInput.on('change', function () { updateCategoryList(languageInput.val());});

  updateCategoryList(languageInput.val());
});

function showHideAdditionalExternalLink(isChecked) {
    if (isChecked) {
        $('#count_additional_external_link').removeClass('hidden');
    } else {
        $('#count_additional_external_link').addClass('hidden');
    }
}
