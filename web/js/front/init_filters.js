"use strict";

$(document).ready(function () {
  initTouchSpin();
  var $mostUsedTab = $('.nav-tabs a[href="#tab-most-used"]');
  $mostUsedTab.on('shown.bs.tab', function (event) {
    $("#googleNews-mostused").append($("#googleNews-advanced").contents());
    $("#majesticTrustFlow-mostused").append($("#majesticTrustFlow-majestic").contents());
    $("#mozDomainAuthority-mostused").append($("#mozDomainAuthority-advanced").contents());
    $("#semrushTraffic-mostused").append($("#semrushTraffic-semrush").contents());
    $("#googleNews-advanced").empty();
    $("#majesticTrustFlow-majestic").empty();
    $("#mozDomainAuthority-advanced").empty();
    $("#semrushTraffic-semrush").empty();
  });
  $mostUsedTab.on('hide.bs.tab', function (event) {
    $("#googleNews-advanced").append($("#googleNews-mostused").contents());
    $("#majesticTrustFlow-majestic").append($("#majesticTrustFlow-mostused").contents());
    $("#mozDomainAuthority-advanced").append($("#mozDomainAuthority-mostused").contents());
    $("#semrushTraffic-semrush").append($("#semrushTraffic-mostused").contents());
    $("#googleNews-mostused").empty();
    $("#majesticTrustFlow-mostused").empty();
    $("#mozDomainAuthority-mostused").empty();
    $("#semrushTraffic-mostused").empty();
  });

  if (!$("#filters_amountType option:selected").val()) {
    $(".filter-amount *").prop('disabled', true);
  }

  $("#filters_amountType").chosen().change(function () {
    if ($(this).val()) {
      $(".filter-amount *").prop('disabled', false);
    } else {
      $(".filter-amount *").prop('disabled', true);
      $(".filter-amount input").val('');
    }
  });
  $('#filters_filter').click(function () {
    $('input:invalid').each(function () {
      // Find the tab-pane that this element is inside, and get the id
      var $closest = $(this).closest('.tab-pane');
      var id = $closest.attr('id'); // Find the link that corresponds to the pane and have it show

      $('.nav a[href="#' + id + '"]').tab('show'); // Only want to do it once

      return false;
    });
  });
});

function initTouchSpin() {
  $("input[type='number']").TouchSpin({
    verticalbuttons: true,
    max: 10000000,
    buttondown_class: 'btn btn-white',
    buttonup_class: 'btn btn-white'
  });
}
