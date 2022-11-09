"use strict";

$('.j-tabs-with-footable-filter a').click(function (e) {
  var target = e.target;
  var tabId = target.getAttribute("href");
  var tabType = target.dataset.filter;
  var resultContainer = $(tabId);

  if (tabId !== "#tab-all" && resultContainer.html() === "") {
    $.get(Routing.generate('netlinking_detail', {
      'id': netlinkingId,
      'tab-type': tabType
    }), function (response) {
      resultContainer.html(response);
      $('.footable').footable(footableConfig);
    });
  } else {
    $('.footable').footable(footableConfig);
  }
});