function validateMinMax() {
  if($('.minmaxrange.has-error').length) {
    return true;
  }
  return false;
}

$('body').on('keyup change', '.minmaxrange', function(e){
  var minMaxRange = e.currentTarget,
  minElem = $(minMaxRange).find('input[range-type="min"]'),
  maxElem = $(minMaxRange).find('input[range-type="max"]');
  var min = parseInt(minElem.val());
  var max = parseInt(maxElem.val());

  if (min && max && min > max) {
    $(minMaxRange).addClass('has-error');
    minElem[0].setCustomValidity(translations.errors.wrong_min_range);
    maxElem[0].setCustomValidity(translations.errors.wrong_max_range);
  } else {
    $(minMaxRange).removeClass('has-error');
    minElem[0].setCustomValidity('');
    maxElem[0].setCustomValidity('');
  }
});
