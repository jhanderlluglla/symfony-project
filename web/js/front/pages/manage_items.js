"use strict";

var form = $('form'),
    removeButton = '<span class="input-group-btn"><button class="btn btn-danger remove-btn"><i class="fa fa-times"></i></button></span>',
    topListMaxIndex = 0,
    buttonBlockMaxIndex = 0,
    listBlockMaxIndex = 0;

function getMaxIndex(selector, setIndex) {
  selector.each(function () {
    var currentId = $(this).attr('id').split('_').pop();
    setIndex = setIndex < currentId ? currentId : setIndex;
  });
  return setIndex;
}

topListMaxIndex = getMaxIndex($('#homepage_topList .form-control'), topListMaxIndex);
buttonBlockMaxIndex = getMaxIndex($('#homepage_blockContainer_buttonBlocks .homepage_blockContainer__content > div'), buttonBlockMaxIndex);
listBlockMaxIndex = getMaxIndex($('#homepage_listBlock_items .form-control'), listBlockMaxIndex);
$('.add-item-list').click(function (event) {
  topListMaxIndex++;
  event.preventDefault();
  addListItem('homepage_topList', topListMaxIndex);
});
$('.add-block-with-button').click(function (event) {
  buttonBlockMaxIndex++;
  event.preventDefault();
  addBlockItem('homepage_blockContainer_buttonBlocks', buttonBlockMaxIndex);
  $('.summernote').summernote(summernoteOptions);
});
$('.add-item-to-list').click(function (event) {
  event.preventDefault();
  $('#homepage_listBlock').removeClass('show-tooltip');
  $('#homepage_listBlock > .form-group').slideDown(200);
  listBlockMaxIndex++;
  addListItem('homepage_listBlock_items', listBlockMaxIndex);
});
form.on('click', '.remove-btn', removeItem);
form.on('click', '.homepage_blockContainer__label > .control-label', showBlockContent);
form.on('click', '#homepage_listBlock .remove-btn', checkListBlockItems);

function addListItem(container, maxIndex) {
  var current = addFromPrototype(container, maxIndex);
  current.wrap("<div class='input-group'></div>").parent().append(removeButton);
}

function addBlockItem(container, maxIndex) {
  var current = addFromPrototype(container, maxIndex),
      thisLabelWrap = current.closest('.homepage_blockContainer__group').find('.homepage_blockContainer__label'),
      thisLabel = thisLabelWrap.find('.control-label');
  changeLabel(thisLabel);
  thisLabelWrap.append(removeButton);
}

function addFromPrototype(container, maxIndex) {
  var $collectionHolder = $('#' + container),
      prototype = $collectionHolder.data('prototype'),
      newInput = $(prototype.replace(/__name__/g, maxIndex));
  $collectionHolder.append(newInput);
  return $("#" + container + "_" + maxIndex);
}

function removeItem(event) {
  event.preventDefault();
  $(event.target).closest('.form-group').remove();
  $(event.target).closest('.homepage_blockContainer__group').remove();
}

function changeLabel(label) {
  var labelNum = label.closest('.homepage_blockContainer__group').index() + 1;
  label.append(' - ' + labelNum + '<i class="fa fa-chevron-down"></i>');
}

var blockLabel = $('.homepage_blockContainer__label > .control-label'),
    blockContent = $('.homepage_blockContainer__content');
blockContent.css('display', 'none');
blockLabel.each(function () {
  changeLabel($(this));
});

function showBlockContent(event) {
  event.preventDefault();
  $(event.target).toggleClass('active').parent().next().slideToggle(400);
}

function checkListBlockItems() {
  if (!$('#homepage_listBlock_items .form-group').length) {
    $('#homepage_listBlock_title').val('');
    $('#homepage_listBlock > .form-group').slideUp(200);
    $('#homepage_listBlock').addClass('show-tooltip');
  }
}

checkListBlockItems();