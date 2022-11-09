"use strict";

var $addUrl = $('<button href="#" class="btn btn-primary add-article m-t-md"><i class="fa fa-plus"></i></button>');
var $removeUrl = '<span class="input-group-btn"><button href="#" class="btn btn-danger remove-btn"><i class="fa fa-times"></i></button></span>';

var $collectionHolder;

$(document).ready(function () {
  $collectionHolder = $('#netlinking_add_first_step_urls');

  if (mode == 'add' && errors == 0) {
    var $newLinkLi = $('<div class="m-b-md">').append($addUrl);
  }

  $collectionHolder.append($newLinkLi);
  $collectionHolder.data('index', $collectionHolder.find(':input').length);
  $addUrl.on('click', function (e) {
    e.preventDefault();
    addForm($collectionHolder, $newLinkLi);
  });

  if (mode == 'add' && errors == 0) {
    $addUrl.trigger('click');
  }

  $('.netlinking_url').blur(function () {
    var val = $(this).val();
    $.ajax({
      type: 'GET',
      url: Routing.generate('netlinking_check_url'),
      data: {
        'url': val
      },
      dataType: 'json',
      success: function success(response) {
        var infoBox = $('.info-box.floating');

        if (response.result == 'fail') {
          $('[data-toggle="popover-floating"]').prop('disabled', 1);
          infoBox.find('h3').addClass('attention').find('.info-box_title').html(translations.infoTitle);
          infoBox.find('.info-box_content').html(response.message);
          infoBox.css({
            'display': 'block',
            'top': '35px'
          });
          //$('form').find('button[type="submit"]').prop('disabled', 1);
        } else {
          $('[data-toggle="popover-floating"]').prop('disabled', 0);
          infoBox.find('h3').removeClass('attention').find('.info-box_title').html('');
          infoBox.css({
            'display': 'none',
          });
          //$('form').find('button[type="submit"]').prop('disabled', 0);
        }
      }
    });
  });
  $('#netlinking_directory_list').change(function () {
    var wordsCount = $(this).find('option:selected').data('words-count');
    var netlinkingWordsCount = $('.netlinking_words_count');

    if (wordsCount > 0) {
      netlinkingWordsCount.removeClass('hidden');
      netlinkingWordsCount.find('.font-bold').html(wordsCount);
      netlinkingWordsCount.find('.modify-list > a').attr('href', Routing.generate('admin_directories_list_relation', {
        'id': $(this).val()
      }));
    } else {
      netlinkingWordsCount.addClass('hidden');
    }
  });
});

function addForm($collectionHolder, $newLinkLi) {
  var prototype = $collectionHolder.data('prototype');
  var index = $collectionHolder.data('index');
  var newForm = prototype.replace(/__parentId__/g, index);
  $collectionHolder.data('index', index + 1);
  var $newFormLi = $('<div class="netlinking_url"></div>').append(newForm);
  $($newFormLi).find('.netlinking_url.form-control').wrap('<div class="netlinking_url__wrap"></div>');
  $($newFormLi).find('.netlinking_url__wrap').addClass('input-group').append($removeUrl);
  $newLinkLi.before($newFormLi);
  $newFormLi.find('.remove-btn').click(deleteUrl);
}

function deleteUrl(event)
{
    event.preventDefault();

    if ($collectionHolder.find('.netlinking_url__item').length <= 1) {
        return false;
    }

    $(event.target).parents('.netlinking_url').remove();

    return false;
}

$('.remove-btn').click(deleteUrl);
