"use strict";

$('.wrapper-content').on('click', '[data-toggle="popover-floating"]', function () {
  var parentRow = $(this).parents('.j-floating-row'),
      curentInfoContainer = parentRow.find('.info-box.floating'),
      infoContentNew = $(this).attr('data-content'),
      infoTitleNew = $(this).attr('data-float-title'),
      infoTitleOld = curentInfoContainer.find('.info-box_title').text(),
      parentRowTop = parentRow.offset().top,
      thisTop = $(this).offset().top,
      offset = thisTop - parentRowTop - 20;
  curentInfoContainer.css('top', offset - 30);
  curentInfoContainer.fadeIn();
  curentInfoContainer.css('top', offset);

  function hideInfoContainer() {
    curentInfoContainer.css('top', offset - 30);
    curentInfoContainer.fadeOut(function () {
      curentInfoContainer.find('.info-box_title').html('');
    });
  }

  if (infoTitleNew === infoTitleOld) {
    hideInfoContainer();
  } else {
    curentInfoContainer.find('.info-box_title').html(infoTitleNew);
    curentInfoContainer.find('.info-box_content').html(infoContentNew);
  }
});

function clearFloatingInfo() {
  var visibleInfoBlock = '';
  $(".info-box.floating").each(function () {
    if ($(this).css('display') === 'block') {
      visibleInfoBlock = $(this);
    }
  });

  if (visibleInfoBlock !== '') {
    visibleInfoBlock.fadeOut(function () {
      visibleInfoBlock.find('.info-box_title').html('');
    });
  }
}

$(document).keyup(function (e) {
  if (e.keyCode === 27) {
    clearFloatingInfo();
  }
});
$(window).on('load resize', function () {
  var width = Math.max($(window).width(), window.innerWidth);

  if (width < 1200) {
    $('[data-toggle="popover-floating"]').attr('data-toggle', 'popover');
    clearFloatingInfo();
  } else {
    $(".popover").each(function () {
      if ($(this).css('display') === 'block') {
        $(this).css('display', 'none');
      }
    });
    $('[data-toggle="popover"]').attr('data-toggle', 'popover-floating');
  }
});