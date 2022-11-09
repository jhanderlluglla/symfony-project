"use strict";

$('#admin_exchange_site_save').click(checkFormOfWriting);
$('#admin_exchange_site_acceptEref, #admin_exchange_site_acceptWeb, #admin_exchange_site_acceptSelf').change(checkFormOfWriting);

function checkFormOfWriting() {
  var acceptErefCheckBox = $("#admin_exchange_site_acceptEref").prop("checked");
  var acceptWebCheckBox = $("#admin_exchange_site_acceptWeb").prop("checked");
  var acceptSelfCheckBox = $("#admin_exchange_site_acceptSelf").prop("checked");
  var exchangeSiteCheckBoxError = $('#exchange_site_check_box_error');
  exchangeSiteCheckBoxError.addClass('hidden');

  if (!acceptErefCheckBox && !acceptWebCheckBox && !acceptSelfCheckBox) {
    exchangeSiteCheckBoxError.removeClass('hidden');
    return false;
  }

  return true;
}