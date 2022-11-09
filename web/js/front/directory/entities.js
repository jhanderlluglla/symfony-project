"use strict";

var form = $('form[name="filters"]');
var activeFilters = [];
var totalEstimationPrice;
var responseGlobalItems = [];
var countResults = '';
var projectsType = '';
var sortBy = '';
var sortDirection = 'asc';
var startPage = 1;
var customScroll = null;

var headerItems = [{
  "name": "name",
  "title": translations.name.type,
  "default": true,
  "visible": true,
  "sortable": false,
  "language": false
},{
  "name": "semrushTraffic",
  "title": translations.name.semrushTraffic,
  "default": true,
  "visible": true
},{
  "name": "majesticTrustFlow",
  "title": translations.name.trustFlow,
  "default": true,
  "visible": true
},{
  "name": "mozDomainAuthority",
  "title": translations.name.mozDomainAuthority,
  "default": true,
  "visible": true
},{
  "name": "bwaAge",
  "title": translations.name.age,
  "default": true,
  "visible": true
},{
  "name": "semrushKeyword",
  "title": translations.name.semrushKeyword,
  "default": true,
  "visible": true
},{
  "name": "price",
  "title": translations.name.price,
  "default": true,
  "visible": true
},{
  "name": "language",
  "title": translations.name.language,
  "visible": false
},{
  "name": "alexaRank",
  "title": translations.name.alexaRank,
  "visible": false
},{
  "name": "mozPageAuthority",
  "title": translations.name.mozPageAuthority,
  "visible": false
},{
  "name": "majesticCitation",
  "title": translations.name.majesticCitation,
  "visible": false
},{
  "name": "majesticTrustCitationRatio",
  "title": translations.name.majesticTrustCitationRatio,
  "visible": false,
  "sortable": false,
},{
  "name": "majesticRefDomains",
  "title": translations.name.majesticRefDomains,
  "default": true,
  "visible": true
},{
  "name": "majesticBacklinks",
  "title": translations.name.majesticBacklinks,
  "visible": false
},{
  "name": "majesticEduBacklinks",
  "title": translations.name.majesticEduBacklinks,
  "visible": false
},{
  "name": "majesticGovBacklinks",
  "title": translations.name.majesticGovBacklinks,
  "visible": false
},{
  "name": "majesticTtfCategories",
  "title": translations.name.majestic_ttf_categories,
  "visible": false,
  "sortable": false,
},{
  "name": "googleNews",
  "title": translations.name.googleNews,
  "visible": false
},{
  "name": "tag",
  "title": translations.name.tags,
  "visible": false,
  "sortable": false,
},{
  "name": "semrushTrafficCost",
  "title": translations.name.semrushTrafficCost,
  "visible": false
},{
  "name": "wordsCount",
  "title": translations.name.words,
  "visible": false,
  "sortable": true,
}, {
  "name": "preferences",
  "title": translations.name.preferences,
  "default": true,
  "visible": true,
  "sortable": false
}
];

function getAllRequestData(page) {
  var formData = form.serializeArray();
  var uri = {};


  for (var i in formData) {
    if (uri.hasOwnProperty(formData[i].name)) {
      if (!$.isArray(uri[formData[i].name])) {
        uri[formData[i].name] = [uri[formData[i].name]];
      }

      uri[formData[i].name].push(formData[i].value);
    } else {
      uri[formData[i].name] = formData[i].value;
    }
  }

  uri.id = directoryListId;
  uri.page = page;
  uri.type = projectsType;
  if(sortBy) {
    uri.sortBy = sortBy;
  }
  uri.sortDirection = sortDirection;

  return uri;
}

$('#filters_filter').on('click', function () {
  if(validateMinMax()) {
    return;
  }
  startPage = 1;
  renderTable(null, startPage);
});

$('.j-directories-list_nav').on('click', 'a', function(){
  if (projectsType != $(this).data('type')){
    projectsType = $(this).data('type');
    startPage = 1;
    renderTable(null, startPage);
  }
});

var correctValues = {
  'language': 'language',
  'mozPageAuthority': 'mozPageAuthority',
  'alexaRank': 'alexaRank',
  'majesticCitation': 'majesticCitation',
  'majesticTrustCitationRatio': 'majesticTrustCitationRatio',
  'majesticRefDomains': 'majesticRefDomains',
  'majesticBacklinks': 'majesticBacklinks',
  'majesticEduBacklinks': 'majesticEduBacklinks',
  'majesticGovBacklinks': 'majesticGovBacklinks',
  'majesticTtfCategories': 'majesticTtfCategories',
  'googleNews': 'googleNews',
  'tag': 'tag',
  'semrushTrafficCost': 'semrushTrafficCost',
  'wordsCount': 'wordsCount',
};

function removeActiveFilters(elemName) {

  var index_el = activeFilters.indexOf(elemName);
  if (index_el !== -1) {
    activeFilters.splice(index_el, 1);
  }
}

function refreshActiveFilters(elem) {
  var elemName = elem[0].name.split(']')[0].split('[')[1];
  var elemVal = elem.val();

  if (!correctValues.hasOwnProperty(elemName)) {
    return;
  }else {
    elemName = correctValues[elemName];
  }

  // add elem to activeFilters
  if ((elemVal != 0 && elemVal != '' && elemName != 'language') || (elemName == 'language' && elemVal == '')) {

    if (activeFilters.indexOf(elemName) == -1) {
      activeFilters.push(elemName);
    }else {
      return
    }
  }

  // remove elem from activeFilters
  if (elemName == 'age'){
    var ageYears = $('#filters_ageYears').val();
    var ageMonth = $('#filters_ageMonth').val();

    if(ageYears == '' && ageMonth == '') removeActiveFilters(elemName);

  } else if (elemName == 'language') {
    if (elemVal != '') {
      removeActiveFilters(elemName);
      headerItems[0].language = elemVal;
    }
    else {
      headerItems[0].language = false;
    }
  } else {
    // find pair inputs
    var elemPair;
    var elemPairVal;
    $(elem).closest('.form-group').find(".form-control").each(function () {
      if($(this).attr('id') !== elem[0].id) {
        elemPair = $(this);
        elemPairVal = elemPair.val();
      }
    });

    if( (elemVal == 0 || elemVal == '') && (elemPairVal == 0 || elemPairVal == '' || elemPairVal == undefined )) {
      removeActiveFilters(elemName);
    }
  }
}

form.find('.form-control').each(function (index, el) {
  if(($(this).val() != 0 && $(this).val() != '' )|| $(this).attr('id') == 'filters_language') {
    refreshActiveFilters($(this));
  }
});

form.on('change', '.form-control', function (e) {
  var elem = $(e.target);
  refreshActiveFilters(elem);
});

function changeTotalPrice (status, price) {
  if (status) {
    totalEstimationPrice['total_selected_price'] += price;
  } else {
    totalEstimationPrice['total_selected_price'] -= price;
  }
  $('#project_estimation').text(Math.round(totalEstimationPrice['total_selected_price'] * 100) / 100);
};

var availableBlogs = {};
var availableDirectories = {};

//  listener for checkbox in table

$('.ibox-content').on('change', ':checkbox', function (event) {
  event.preventDefault();

  var $this = $(event.target),
    checkedId = $this.attr('data-id'),
    checkedType = $this.attr('data-type'),
    checkedIdFully = checkedType + "_" + checkedId,
    status = $this.prop('checked');

  // this part makes sure that when checkbox in original first column is checked, it also will be checked in clone column and visaversa

  if ($this.closest('.table-freeze').length) $('.sticky-col :checkbox[id="' + checkedIdFully + '-copy"]').prop('checked', status);
  if ($this.closest('.sticky-col').length) $('.table-freeze :checkbox[id="' + checkedIdFully + '"]').prop('checked', status);

  // this part sets connection between select on top of table and table item checkbox -
  // so when checkbox is checked, it`s item shows up in select as selected.

  $('#directories_and_blogs option[value="' + checkedIdFully + '"]').prop('selected', status);
  $('#directories_and_blogs').trigger("chosen:updated");

  var IDprefix,
      type;

  if(checkedIdFully.search( /exchange/i ) === -1) {
    availableDirectories[checkedIdFully] = status;
    IDprefix = 'd';
    type = 'directories';
  } else {
    availableBlogs[checkedIdFully] = status;
    IDprefix = 'es';
    type = 'blogs';
  }

  totalEstimationPrice[type][checkedId] = responseGlobalItems[IDprefix + checkedId].price;
  changeTotalPrice(status, responseGlobalItems[IDprefix + checkedId].price);

  colorSelectedOptionsInSelect();
});

// listener to selects

var iboxContent = $('.ibox-content');

iboxContent.on('change', '#directories_and_blogs', function (event, params) {
  updateTableOnSelect(event, params);
  changePriceOnSelect(params);
  colorSelectedOptionsInSelect();
});

function changePriceOnSelect(params) {
  var type,
      id;

  if(params.selected) {
    if(params.selected.search( /exchange/i ) === -1) {
      type = 'directory';
    } else {
      type = 'blog';
    }
    id = params.selected.match( /\d+/ )[0];

    sendGetRequest(
      Routing.generate('admin_directories_list_price'),
      {'id': id, 'directory_list_id': directoryListId, 'type' : type},
      function (data) {
        changeTotalPrice(true, data.price);
        if(type === 'directory') {
          totalEstimationPrice.directories[id] = data.price;
        } else {
          totalEstimationPrice.blogs[id] = data.price;
        }
      },
      function() {
        $('#directories_and_blogs option[value="' + params.selected + '"]').prop('selected', false);
        $('#directories_and_blogs').trigger("chosen:updated");

        $('.table-freeze :checkbox[id="' + params.selected + '"]').prop('checked', false);
        $('.sticky-col :checkbox[id="' + params.selected + '"]').prop('checked', false);
        colorSelectedOptionsInSelect();
      }
    )
  } else {
    if(params.deselected.search( /exchange/i ) === -1) {
      changeTotalPrice(false, totalEstimationPrice.directories[params.deselected.match( /\d+/ )]);
    } else {
      changeTotalPrice(false, totalEstimationPrice.blogs[params.deselected.match( /\d+/ )]);
    }
  }
};

function updateTableOnSelect(event, params) {
  var param = params.selected ? params.selected : params.deselected;
  var status = $(event.target).find('option[value="' + param + '"]').prop('selected');
  $('.table-freeze :checkbox[id="' + param + '"]').prop('checked', status);
  $('.sticky-col :checkbox[id="' + param + '"]').prop('checked', status);

  if(param.search( /exchange/i ) === -1) {
    availableDirectories[param] = status;
  } else {
    availableBlogs[param] = status;
  }
}

function updateFormWithSelect(id, array) {
  $(id + ' option').each(function () {
    var currentFormItem = $(this).val();
    $(id + ' option[value="' + currentFormItem + '"]').prop('selected', array[currentFormItem]);
  });
  $(id).trigger("chosen:updated");
}

function renderCheckbox(data, available) {
  var genID = data._type + '_' + data.id;
  var inputCeckbox = '<div class="checkbox m-r-md"><label class="radio-inline" for="' + genID + '"></label>';
      inputCeckbox += '<span class="checkbox_radio_widget_wrapper"><input type="checkbox"';

  if (available[genID] !== false) {
    if (data.selected || available[genID]) {
      inputCeckbox += ' checked="true"';
    }
  }

  inputCeckbox += ' class="' + data.checkbox_class + '" id="' + genID + '" data-id="' + data.id + '" data-type="' + data._type + '">';
  inputCeckbox += '<label for="' + genID + '"></label></span></label></div>';
  return inputCeckbox;
}

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

function formattingAge(data) {
  var age = '';

  if(data.hasOwnProperty('d')) {
    delete data.d;
  }
  for (var item in data) {
    age += '<div>' + data[item] + ' ' + translations.name[item] + '</div>';
  }

  return age;
}

function renderTags(data) {
  if (!data.tags) return '';
  var tags = '';
  var tagsArr;
  tagsArr = data.tags.split(',');

  for (var item in tagsArr) {
    tags += '<div class="table-badge"><span>' + tagsArr[item] + '</span></div>';
  }

  return tags;
}

function renderAge(data) {
  var ageArchive = formattingAge(data.metrics.age.archiveAge);
  var ageWhois = formattingAge(data.metrics.age.whoisAge);
  var result = '<ul class="block-information age dark-titles">';

  if (ageArchive !== '') {
    result += '<li><div class="block-information__title">' + translations.name.archive + ':</div>' + '<div class="block-information__value"><span>' + ageArchive + '</span></div></li>';
  }

  if (ageWhois !== '') {
    result += '<li><div class="block-information__title">' + translations.name.whois + ':</div><div class="block-information__value">' + '<span>' + ageWhois + '</span></div></li>';
  }

  result += '</ul>';
  return result;
}

function renderLanguage(data) {
  if (data.language) {
    return '<div class="language-wrapper"><b>' + data.language + '</b><svg class="icon-flag"><use xlink:href="#' + data.language + '"></use></svg></div>'
  } else {
    return '';
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

function renderHeader(table, header) {

  var templateHeader = "<thead><tr>";

  for(var i = 0; i < header.length; i++){
    templateHeader += "<th data-visible=" + header[i]['visible'] + "";
    if(header[i]['sortable'] !== false){
      if (header[i]["name"] === sortBy) {
        templateHeader += " data-sortable=" + sortDirection;
      } else {
        templateHeader += " data-sortable=''";
      }
    }
    templateHeader += " data-name=" + header[i]["name"] + ">";
    templateHeader += "<div class='filter-icon-holder'>";
    templateHeader += header[i]["title"];
    if(i === 0 && header[i]["language"] !== false) {
      templateHeader += '<svg class="icon-flag m-b-n-xs"><use xlink:href="#' + header[i]["language"] + '"></use></svg>';
    }
    templateHeader += "</div></th>";
  }

  templateHeader += "</tr></thead>";

  table.append(templateHeader)
}

function renderTBody(table, header, blogs) {
  var templateBody = "<tbody>";

  for(var item_i in blogs){
    templateBody += '<tr>';

    for(var i = 0; i < header.length; i++){

      var columnName = header[i]["name"];
      var columnVisibility = header[i]["visible"];

      var tagName = "td";
      if (i === 0) tagName = "th";

      templateBody += "<" + tagName + " data-visible=" + columnVisibility + ">";
      templateBody += blogs[item_i][columnName];
      templateBody += "</" + tagName + ">";
    }
    templateBody += "</tr>";
  }

  templateBody += "</tbody>";

  table.append(templateBody)
}

function renderItemRow(blogs, item, key) {
  var newBlog = {},
      currentKey = key,
      fullId = item._type + '_' + item.id;

  var requirements = item.publicationRequirements;
  var mertrics = item.metrics;

  newBlog.price = '<b class="text-primary text-bigger no-wrap">' + checkData(item.price) + '€</b>';
  newBlog.bwaAge = renderAge(item);
  newBlog.mozDomainAuthority = checkData(mertrics.moz.domainAuthority);
  newBlog.semrushKeyword = checkData(mertrics.semrush.keyword);
  newBlog.semrushTraffic = checkData(mertrics.semrush.traffic);
  newBlog.semrushTrafficCost = checkData(mertrics.semrush.trafficCost);

  var availableItems,
      badge = '',
      typeTranslate = '',
      validationTimeRate = '',
      wordsPath = '';

  if (item._type == 'exchangeSite') {
    if (availableBlogs[fullId] === undefined) {
      availableBlogs[fullId] = item.selected;
    }
    badge = 'blogs-badge';
    typeTranslate = translations.name.blog;
    wordsPath = item.publicationRequirements.words;
    availableItems = availableBlogs;


    newBlog.preferences = '<ul class="preferences">' + '<li><div class="preferences-item">';
    newBlog.preferences += '<span>' + translations.name.words + ':</span class="preferences-item__title"> <b class="preferences-item__value">' + checkData(requirements.words) + '</b>';
    newBlog.preferences += '</div></li><li><div class="preferences-item"><span class="preferences-item__title">' + translations.name.links + ':</span>';
    newBlog.preferences += '<b class="preferences-item__value">' + checkData(requirements.links) + '</b></div></li>';
    newBlog.preferences += '<li><div class="preferences-item"><span class="preferences-item__title">' + translations.name.images + ':</span>';
    newBlog.preferences += '<b class="preferences-item__value">' + checkDouble(requirements.images.min, requirements.images.max) + '</b></div></li>';
    newBlog.preferences += '<li><div class="preferences-item"><span class="preferences-item__title">' + translations.name.plugin + ':</span>';
    newBlog.preferences += '<b class="preferences-item__value">' + item.plugin + '</b></div></li></ul>';


  } else {
    badge = 'directories-badge';
    typeTranslate = translations.name.directory;
    wordsPath = item.words;
    if (availableDirectories[fullId] === undefined) {
      availableDirectories[fullId] = item.selected;
    }

    availableItems = availableDirectories;
    newBlog.preferences = '<p class="text-left"><b class="text-info">' + translations.name.words + ': </b><span>' + checkData(wordsPath) + '</span></p>';

    if (item.subDomain) {
      newBlog.preferences += '<div class="preferences-info text-left"><span class="glyphicon glyphicon-ok text-info" aria-hidden="true"></span>';
      newBlog.preferences += '<p>' + translations.name.accept_subdomains + '</p></div>';
    } else {
      newBlog.preferences += '<div class="preferences-info text-left"><span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span>';
      newBlog.preferences += '<p>' + translations.name.reject_subdomains + '</p></div>';
    }

    if (item.legalInfo) {
      newBlog.preferences += '<div class="preferences-info text-left"><span class="glyphicon glyphicon-ok text-info" aria-hidden="true"></span>';
      newBlog.preferences += '<p>' + translations.name.legal + '</p></div>';
    } else {
      newBlog.preferences += '<div class="preferences-info text-left"><span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span>';
      newBlog.preferences += '<p>' + translations.name.dont_legal + '</p></div>';
    }


  }

  var href = '//' + item.name;
  if(href.indexOf("*******") != -1) {
      href = '#';
  }
  newBlog.name = '<div class="site-type">';
  newBlog.name += renderCheckbox(item, availableItems);
  newBlog.name += '<div class="main-site-info"><a  class="underlined-link" href="' + href + '" target="_blank">' + item.name + '</a><br>';
  newBlog.name += '<span class="directories-list_badge '+ badge + '"><b>' + typeTranslate + ':</b> ' + item.categories + '</span><br>';
  newBlog.name += '<button class="btn btn-link toggle-details" data-toggle="modal" data-target="#moreDetail" data-key=' + currentKey + '>';
  newBlog.name += '<span class="show-detail">' + translations.name.showDetails + '<i class="fa fa-chevron-down"></i></span></button>' + validationTimeRate + '</div></div>';

  newBlog.wordsCount = checkData(wordsPath);
  newBlog.tag = renderTags(item);
  newBlog.language = renderLanguage(item);

  newBlog.majesticTrustFlow = checkData(mertrics.majestic.trustFlow);
  newBlog.majesticRefDomains = checkData(mertrics.majestic.refDomains);
  newBlog.majesticBacklinks = checkData(mertrics.majestic.backlinks);
  newBlog.majesticEduBacklinks = checkData(mertrics.majestic.eduBacklinks);
  newBlog.majesticGovBacklinks = checkData(mertrics.majestic.govBacklinks);
  newBlog.majesticCitation = checkData(mertrics.majestic.citation);
  newBlog.majesticTrustCitationRatio = checkData(mertrics.majestic.citation);

  newBlog.alexaRank = checkData(mertrics.other.alexaRank);
  newBlog.mozPageAuthority = (mertrics.majestic.trustFlow / mertrics.majestic.citation).toFixed(2);

  newBlog.majesticTtfCategories = "";

  if (mertrics.majestic.categories !== "") {
    newBlog.majesticTtfCategories += '<h5 class="categories-name sub-categories_title">' + translations.name.majestic_ttf_categories + '</h5>';
    newBlog.majesticTtfCategories += '<ul class="sub-categories-list">';
    var ttfCategoriesArr = mertrics.majestic.categories.split(';');

    for (var index = 0; index < ttfCategoriesArr.length; index++) {
      var ttfCategory = ttfCategoriesArr[index].split(':');
      newBlog.majesticTtfCategories += '<li><b>' + ttfCategory[0].replace(/\//g, " / ") + '</b><span>' + ttfCategory[1] + '</span></li>';
    }
    newBlog.majesticTtfCategories += '</ul>';
  }
  newBlog.googleNews = checkData(mertrics.google.news);


  blogs.push(newBlog);
}

function renderTable(event, page) {
  var event = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
  var page = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;
  var blogsItems = [];
  var navLi = $('.j-directories-list_nav').find('li');

  //set active tab-nav
  if( projectsType == '' ){
    navLi.removeClass('active');
    $(navLi[0]).addClass('active');
  }

  // change "headerItems" for rendering header
  for (var header_i = 0; header_i < headerItems.length; header_i++) {

    if ( activeFilters.indexOf(headerItems[header_i]['name']) != -1 ) {
      headerItems[header_i]['visible'] = true;
    }else if (headerItems[header_i]['default'] !== true && headerItems[header_i]['visible'] == true) {
      headerItems[header_i]['visible'] = false;
    }
  }

  $.get(Routing.generate('admin_directories_list_relation', getAllRequestData(page)), function (response) {
    var tableAll = $('#tableAll');
    var tableAllWrap = $('.directories-table-wrap');

    //destroy old table and pagination
    $(".sticky-wrap").floatingScroll("destroy");
    tableAllWrap.find('.sticky-thead').detach();
    tableAllWrap.find('.sticky-col').detach();
    tableAllWrap.find('.sticky-intersect').detach();
    tableAll.unwrap('.sticky-wrap');
    tableAll.empty();
    $('#pagination').twbsPagination('destroy');

    responseGlobalItems = response.items;
    for (var key in response.items) {
      var data = response.items[key];
      renderItemRow(blogsItems, data, key);
    }

    if( blogsItems.length > 0 ){

      renderHeader(tableAll, headerItems);
      renderTBody(tableAll, headerItems, blogsItems);
      initFreeze();

      var totalPages = Math.ceil(response.countResults / maxPerPage);

      $('#pagination').twbsPagination({
        totalPages: totalPages,
        visiblePages: 5,
        initiateStartPageClick: false,
        startPage: startPage,
        first: null,
        last: null,
        onPageClick: function onPageClick(event, page) {
          startPage = page;
          renderTable(null, page);
        }
      });
    } else {
      if(customScroll !== null) customScroll.destroy();
      tableAllWrap.removeClass('vis-shadow');
      tableAll.append("<tbody><tr><td class='no_results'>" + translations.noResults + "</td></tr></tbody>")
    }

    // put form, which was returned as html from server into its place

    $('form[name="admin_directories_list_relation"]').parent().html(response.form);

    // update that form

    updateFormWithSelect('#directories_and_blogs', availableBlogs);
    updateFormWithSelect('#directories_and_blogs', availableDirectories);
    $('select.form-control').not("#filters_ageCondition").chosen({
      width: "100%"
    });

    // checking and setting price

    if(!totalEstimationPrice) {
      totalEstimationPrice = response.totalEstimationPrice;
    }
    if(Array.isArray(totalEstimationPrice.blogs)) {
      totalEstimationPrice.blogs = {};
    }
    if(Array.isArray(totalEstimationPrice.directories)) {
      totalEstimationPrice.directories = {};
    }
    $('#project_estimation').text(totalEstimationPrice['total_selected_price']);

    //

    $('#count_results').text(response.countResults);

    // coloring select options

    colorOptionsInSelect();
    colorSelectedOptionsInSelect();
  });
}

renderTable();

$('.directories-table-wrap').on('click', 'th[data-sortable]', function (e) {

  var thisStatus = $(this).attr('data-sortable');
  sortBy = $(this).attr('data-name');

  function setASC(el) {
    sortDirection = 'asc';
    $('.table-freeze thead th').eq(el.index()).attr('data-sortable', 'asc');
    el.attr('data-sortable', 'asc');
  }
  function setDESC(el) {
    sortDirection = 'desc';
    $('.table-freeze thead th').eq(el.index()).attr('data-sortable', 'desc');
    el.attr('data-sortable', 'desc');
  }

  if(thisStatus === 'asc') {
    setDESC($(this));
  } else if(thisStatus === 'desc') {
    setASC($(this));
  } else {
    $('.table-freeze, .sticky-thead').find('thead th[data-sortable]').attr('data-sortable', '');
    setASC($(this));
  }

  startPage = 1;
  renderTable(null, startPage);
});

function colorSelectedOptionsInSelect () {
  $('.chosen-choices .search-choice').each(function(index, elem) {
    var arrayIndex = $(elem).find('a.search-choice-close').attr('data-option-array-index'),
        type = $($('#directories_and_blogs option')[arrayIndex]).attr('value').match( /(exchangeSite|directory)/ )[0];
    $(elem).attr('type', type);
  })
}

function colorOptionsInSelect () {
  $('#directories_and_blogs option').each(function(index, elem) {
    var generatedOption = $('#directories_and_blogs_chosen .chosen-results li[data-option-array-index="' + index + '"]'),
        type = $(elem).attr('value').match( /(exchangeSite|directory)/ )[0];
    $(generatedOption).attr('type', type);
  })
}

iboxContent.on('chosen:showing_dropdown', '#directories_and_blogs', function (event, params) {
  colorOptionsInSelect();
});
