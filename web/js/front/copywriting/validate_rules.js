"use strict";

$('#copywriting_project_submit').click(function (e) {
  e.preventDefault();
  var checkedCategory = $('input[name="copywriting_project[writer_category]"]:checked');

  if (checkedCategory.val() !== 'no_selection') {
    var writers = $('input.card-writer__checkbox');

    if (writers.length > 0) {
      var chosenWriters = $('input.card-writer__checkbox:checked');

      if (chosenWriters.length > 0) {
        checkFieldsAreEmpty();
      } else {
        swal({
          title: translations.popup.not_selected_message,
          type: "error",
          confirmButtonText: translations.popup.select_writer_text,
          confirmButtonColor: "#1ab394",
        }, function () {
          $("html, body").animate({
            scrollTop: $('.slick_writers').offset().top - 70
          }, 400);
        });
      }
    } else {
      swal({
        title: translations.popup.empty_category_message,
        type: "error",
        confirmButtonText: translations.popup.select_category_text,
        confirmButtonColor: "#1ab394",
      }, function () {
        $("html, body").animate({
          scrollTop: $('#copywriting_project_writer_category').offset().top - 70
        }, 400);
      });
    }
  } else {
    checkFieldsAreEmpty();
  }
});

function checkFieldsAreEmpty() {
  var statistics = ArticleModel.statisticsOfArticles();
  var projectForm = $('form[name="copywriting_project"]');

  $('.j-pair-num').each(function () {
    var minField = $(this).find('[data-pair="min"]');
    var maxField = $(this).find('[data-pair="max"]');

    var minVal = $(minField[0]).val() !== '' ? parseInt($(minField[0]).val()) : 0;
    var maxVal = $(maxField[0]).val() !== '' ? parseInt($(maxField[0]).val()) : 0;

    if (minVal > maxVal) {
      minField[0].setCustomValidity(translations.validation.wrongRange);
      maxField[0].setCustomValidity(translations.validation.wrongRange);
    } else {
      minField[0].setCustomValidity('');
      maxField[0].setCustomValidity('');
    }
  });

  if (projectForm[0].checkValidity()) {
    if (statistics.totalEmpty > statistics.totalNotEmpty) {
      swal({
        title: translations.popup.title,
        text: translations.popup.text,
        type: "warning",
        showCancelButton: true,
        cancelButtonText: translations.popup.cancel_button_text,
        confirmButtonColor: "#ed5565",
        confirmButtonText: translations.popup.confirm_button_text,
        closeOnConfirm: true
      }, function () {
        submitForm(projectForm);
      });
    } else {
      submitForm(projectForm);
    }
  } else {
    var formControls = projectForm.find(':input');

    $(formControls).on("change", function (e) {
      $(e.target).closest('.form-group').removeClass('has-error');
    });

    for (var inputs_i = 0; inputs_i < formControls.length; inputs_i ++){

      var inputCurrent = formControls[inputs_i];

      if (!inputCurrent.validity.valid) {

        $(inputCurrent).closest('.form-group').addClass('has-error');

        var tabPane = $(inputCurrent).closest('.tab-pane');
        if (tabPane.length !== 0 && !tabPane.hasClass('active')) {
          showInvalidTab($(inputCurrent));
        }
      }

      if ( inputs_i == formControls.length - 1 && $('.error').length > 0) {

        var topTagNavs = $('.tag-navs .nav.nav-tabs').offset().top;
        $("html, body").animate({
          scrollTop: topTagNavs - 50
        }, 400);
      }
    }
  }
}

function showInvalidTab(field) {
  var tabId = field.closest('.tab-pane').attr('id');
  var tab = $('a[href=\"' + '#' + tabId + '\"]');

  if (!tab.parent('li').hasClass('active') && tabId !== "idAll") {
    tab.addClass('error').on("click", function (e) {
      setTimeout(function () {
        var href = $(e.currentTarget).attr('href');
        var topLastErr = $(href).find('.has-error').last().offset().top;
        $("html, body").animate({
          scrollTop: topLastErr - 50
        }, 400);
        $(e.currentTarget).removeClass('error');
        $(e.currentTarget).unbind('click');
      }, 0);
    });
  }
}

function submitForm(form) {
  $('.j-submit').prop('disabled', true);
  var inputs = form.find('#idAll input');
  inputs.each(function (index, element) {
    $(element).removeAttr('name');
  });
  form.submit();
}
