function checkDouble(min, max) {
  if (min && max) {
    return min + ' ' + translations.name.to + ' ' + max;
  } else if (min && (max === null || max === 0)) {
    return translations.name.min + ' ' + min;
  } else if (max && (min === null || min === 0)) {
    return translations.name.max + ' ' + max;
  } else {
    return ' - ';
  }
}

function checkData(item, type) {
  if (type === undefined) {
    return item ? item : ' - ';
  } else if (type === 'text') {
    return item ? translations.name.yes : translations.name.no;
  } else if (type === 'opt') {
    if (item === false) return translations.name.no;
    if (item === null) return translations.name.optional;
    return translations.name.yes;
  }
}

function renderPublicationRequirements(data) {
  var requirements = data.publicationRequirements;
  return '<ul class="categories-wrapper without-categories-name">' + '<li><table class="categories-table"><tr><td>' + translations.name.words + '</td><td>' + checkData(requirements.words) + '</td></tr>' + '<tr><td>' + translations.name.links + '</td><td>' + checkData(requirements.links) + '</td></tr>' + '<tr><td>' + translations.name.images + '</td><td>' + checkDouble(requirements.images.min, requirements.images.max) + '</td></tr>' + '<tr><td>' + translations.name.metaTitle + '</td><td>' + checkData(requirements.metaTitle, 'text') + '</td></tr>' + '<tr><td>' + translations.name.metaDescription + '</td><td>' + checkData(requirements.metaDescription, 'text') + '</td></tr></table></li>' + '<li><table class="categories-table"><tr><td>' + translations.name.headerOneSet + '</td><td>' + checkData(requirements.h1, 'text') + '</td></tr>' + '<tr><td>' + translations.name.headerTwoStartEnd + '</td><td>' + checkDouble(requirements.h2.min, requirements.h2.max) + '</td></tr>' + '<tr><td>' + translations.name.headerThreeStartEnd + '</td><td>' + checkDouble(requirements.h3.min, requirements.h3.max) + '</td></tr>' + '<tr><td>' + translations.name.boldText + '</td><td>' + checkData(requirements.boldText, 'opt') + '</td></tr>' + '<tr><td>' + translations.name.quotedText + '</td><td>' + checkData(requirements.quotedText, 'opt') + '</td></tr></table></li>' + '<li><table class="categories-table"><tr><td>' + translations.name.italicText + '</td><td>' + checkData(requirements.italicText, 'opt') + '</td></tr>' + '<tr><td>' + translations.name.ulTag + '</td><td>' + checkData(requirements.ulTag, 'opt') + '</td></tr>' + '<tr><td>' + translations.name.authorizedAnchor + '</td><td>' + checkData(requirements.authorizedAnchor) + '</td></tr>' + '</table></li></ul>';
}

function renderMetrics(data) {
  var metrics;
  var majestic = data.metrics.majestic;
  var semrush = data.metrics.semrush;
  metrics = '<ul class="categories-wrapper">' + '<li><h5 class="categories-name">' + translations.name.majestic_metrics + '</h5>' + '<table class="categories-table">' + '<tr><td>' + translations.name.majesticCitation + '</td><td>' + checkData(majestic.citation) + '</td></tr>' + '<tr><td>' + translations.name.majesticBacklinks + '</td><td>' + checkData(majestic.backlinks) + '</td></tr>' + '<tr><td>' + translations.name.majesticEduBacklinks + '</td><td>' + checkData(majestic.eduBacklinks) + '</td></tr>' + '<tr><td>' + translations.name.majesticGovBacklinks + '</td><td>' + checkData(majestic.govBacklinks) + '</td></tr>' + '<tr><td>' + translations.name.trustFlow + '</td><td>' + checkData(majestic.trustFlow) + '</td></tr>' + '<tr><td>' + translations.name.majesticRefDomains + '</td><td>' + checkData(majestic.refDomains) + '</td></tr></table>';

  if (majestic.categories !== "") {
    metrics += '<h5 class="categories-name sub-categories_title">' + translations.name.majestic_ttf_categories + '</h5>';
    metrics += '<ul class="sub-categories-list">';
    var ttfCategoriesArr = majestic.categories.split(';');

    for (var item in ttfCategoriesArr) {
      var ttfCategory = ttfCategoriesArr[item].split(':');
      metrics += '<li><b>' + ttfCategory[0].replace(/\//g, " / ") + '</b><span>' + ttfCategory[1] + '</span></li>';
    }

    metrics += '</ul>';
  }

  metrics += '</li><li><h5 class="categories-name">' + translations.name.semrush_metrics + '</h5>' + '<table class="categories-table">' + '<tr><td>' + translations.name.semrushTraffic + '</td><td>' + checkData(semrush.traffic) + '</td></tr>' + '<tr><td>' + translations.name.semrushKeyword + '</td><td>' + checkData(semrush.keyword) + '</td></tr>' + '<tr><td>' + translations.name.semrushTrafficCost + '</td><td>' + checkData(semrush.trafficCost) + '</td></tr>' + '</table>' + '<h5 class="categories-name">' + translations.name.moz_metrics + '</h5>' + '<table class="categories-table">' + '<tr><td>' + translations.name.mozPageAuthority + '</td><td>' + checkData(data.metrics.moz.pageAuthority) + '</td></tr>' + '<tr><td>' + translations.name.mozDomainAuthority + '</td><td>' + checkData(data.metrics.moz.domainAuthority) + '</td></tr>' + '</table>' + '<h5 class="categories-name">' + translations.name.google_metrics + '</h5>' + '<table class="categories-table">' + '<tr><td>' + translations.name.googleNews + '</td><td>' + checkData(data.metrics.google.news) + '</td></tr>' + '<tr><td>' + translations.name.googleAnalytics + '</td><td>' + checkData(data.metrics.google.analytics, 'text') + '</td></tr>' + '</table></li>' + '<li><h5 class="categories-name">' + translations.name.other_metrics + '</h5>' + '<table class="categories-table">' + '<tr><td>' + translations.name.alexaRank + '</td><td>' + checkData(data.metrics.other.alexaRank) + '</td></tr>' + '</table></li>' + '</ul>';
  return metrics;
}

$(document).on('show.bs.modal', '#moreDetail, #details', function (e) {
  var that = $(this);

  var itemKey = $(e.relatedTarget).data('key');
  var item = responseGlobalItems[itemKey];
  var itemType = item['_type'] == 'exchangeSite' ? translations.name.blog : translations.name.directory;

  var modalTitle = '<h3 class="m-t-sm"><b>' + itemType + ':</b> ';
    modalTitle += '<a  class="underlined-link" href="//' + item.name + '" target="_blank">' + item.name + '</a></h3>';

  var modalMetrics = '<h3>' + translations.name.metrics + '</h3>';
    modalMetrics += renderMetrics(item);

  that.find('.modal-title').html(modalTitle);
  var $body = that.find('.modal-body');
  if (item._type === 'directory') {
    if (item.validationRate) $body.append('<p>' + translations.name.validation_time + ': <span>' + item.validationRate + '<span></p>');
    if (item.validationTime) $body.append('<p>' + translations.name.validation_rate + ': <span>' + item.validationTime + '<span></p>');
    if (item.validationRate || item.validationTime)
    $body.append('<br>');
  }

  $body.append(modalMetrics);

  if(item['_type'] == 'exchangeSite') {
    var modalPublicationRequirements = '<h3 class="m-t-md">' + translations.name.publicationRequirements + '</h3>';
    modalPublicationRequirements += renderPublicationRequirements(item);

    that.find('.modal-body').append(modalPublicationRequirements);
  }
}).on('hide.bs.modal', function (e) {
  $(this).find('.modal-title').empty();
  $(this).find('.modal-body').empty();
});
