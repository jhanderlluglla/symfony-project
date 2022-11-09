"use strict";

var $collectionHolder = $('#copywriting_article_nonconforms');
jQuery(document).ready(function () {
  $('.copywriting_article_rubrics').chosen({width: "100%"});
  $('form[name="copywriting_article"]').submit(function (e) {
    $('.text-empty').hide();
    $('.note-editor').removeClass('has-error');
  });
  $(".issue-reason-btn").click(function () {
    var button = $(this);

    if (button.next().length) {
      button.text(translations.nonconform);
      removeForm(button.parent());
    } else {
      button.text(translations.cancel);
      addForm(button.parent());
    }
  });
  $('#myModal').on('hidden.bs.modal', function (e) {// do something...
  });
  $('.note-imageAttributes-btn').click(function () {});
  var fileInput = $('#copywriting_article_frontImage'),
      imagePreviewWrap = $('.image-preview__wrap'),
      imagePreview = $('#image-preview'),
      placeholderBase = '/img/no_image_placeholder_2.png';
  $('#uploadFrontImage').change(function (e) {
      var file = e.target.files[0];
      var reader = new FileReader();
      reader.onload = function(e) {
          var img = document.createElement('img');
          img.onload = function() {
              if(imageWidthValidate(this.width)) {
                  uploadImage(file, function (response) {
                      fileInput.val(response.url);
                      imagePreview.attr('src', response.url);
                      imagePreviewWrap.addClass('uploaded');
                  });
              }
          };
          img.src = e.target.result;
      };
      reader.readAsDataURL(this.files[0]);
  });
  if (fileInput.val()) refreshPreviewImage();
  fileInput.on('change', refreshPreviewImage);

  function refreshPreviewImage() {
    var placeholderNew = fileInput.val(),
        image = new Image();
    image.src = placeholderNew;

    image.onload = function () {
        if(imageWidthValidate(this.width)) {
            imagePreview.attr('src', placeholderNew);
            imagePreviewWrap.addClass('uploaded');
        }
    };

    image.onerror = imageError;
  }

    function imageError() {
        imagePreviewWrap.removeClass('uploaded');
        fileInput.val("");
        $('#uploadFrontImage').val("");
        setTimeout(function () {
            imagePreview.attr('src', placeholderBase);
        }, 200);
    }

    function imageWidthValidate(width) {
        if (width < 650 || width > 2000) {
            imageError();
            toastr.error(translations.wrongImageWidth);

            return false;
        }

        return true;
    }

  var countedControls = $('.j-has-count .form-control');

  countedControls.keyup(function (e) {
    updateCounter(e.target);
  });

  countedControls.each(function () {
    $($(this).parent()).append('<span class="symbol_counter"></span>');
    updateCounter($(this)[0])
  });

    $("[data-action='add-image']").click(function () {
        showPixabayModal(function (url) {
          $('.j-font-image-input').val(url);
            $('#uploadFrontImage').val("");
          refreshPreviewImage();
        })
    });
});

function updateCounter(field) {

  var strLength = $(field).val().length;
  var count = strLength > 0 ? strLength : '';

  $(field).siblings('.symbol_counter').text(count);

  return false;
}

function addForm($nonconformBox) {
  var prototype = $collectionHolder.data('prototype');
  var index = $nonconformBox.data('index');
  var newForm = prototype.replace(/__name__/g, index);
  var $newForm = $('<div class="nonconform"></div>').append(newForm);
  $nonconformBox.append($newForm);
  $('#copywriting_article_nonconforms_' + index + '_rule').val($nonconformBox.data('rule'));
  $('#copywriting_article_nonconforms_' + index + '_error').val($nonconformBox.children('.error-text').text());
}

function removeForm($nonconformBox) {
  $nonconformBox.children('div.nonconform').remove();
}

function updateArticleReviewAt() {
    $.get(Routing.generate('copywriting_article_toggle_review', {'id': articleId}));
}

function startReviewTracker() {
    updateArticleReviewAt();
    setInterval(updateArticleReviewAt, 30000); // 30000ms = 30s
}