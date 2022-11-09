"use strict";

var $collectionHolder = $('#admin_image_form');

jQuery(document).ready(function () {

  $('form[name="admin_image_add_form"]').submit(function (e) {
    $('.text-empty').hide();
    $('.note-editor').removeClass('has-error');
  });

  // fileInput = $('#copywriting_article_frontImage'),
  var imagePreviewWrap = $('.image-preview__wrap'),
      imagePreview = $('#image-preview'),
      admin_image_filename = $('#admin_image_filename'),
      admin_image_url = $('#admin_image_url'),
      placeholderBase = '/img/no_image_placeholder_2.png';

  $('#upload_image').change(function (e) {
      var file = e.target.files[0];

      var reader = new FileReader();
      reader.onload = function(e) {
          var img = document.createElement('img');
          img.onload = function() {
              uploadImage(file, function (response) {
                  imagePreview.attr('src', response.urlTemp);
                  imagePreviewWrap.addClass('uploaded');
                  admin_image_url.val(response.url),
                  admin_image_filename.val(response.filename)
              });
          };
          img.src = e.target.result;
      };
      reader.readAsDataURL(this.files[0]);
  });

  function refreshPreviewImage() {
    var placeholderNew = admin_image_url.val(),
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
        $('#upload_image').val("");
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

    // running preview image on edition
    if (admin_image_url.val()){
        refreshPreviewImage();
    }

});
