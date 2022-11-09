"use strict";

function getTagClass(tag) {
  return 'label label-info ' + tag;
}

var initialData = {
  articles: [],
  writers: [],
  writerCategory: 'no_selection',
  recurrent: "0"
};

var allKeywords = [];
var previousAllKeywords = allKeywords;
var allowedGeneral = true,
    allowedOther = true;

ko.utils.clone = function (obj) {
  var data = {};
  for (var prop in obj) {
    var propVal = obj[prop];
    if (ko.isObservable(propVal)) {
      var val = propVal();
      if ($.type(val) === 'array') {
        var arr = [];

        for(var item_i = 0; item_i < val.length; item_i++){
          var item = val[item_i]
          arr.push(ko.utils.clone(item));
        }
        data[prop] = arr;
        continue;
      }
      data[prop] = val;
    }
  }
  return data;
};

var AddArticlesModel = function(project) {
  var self = this;
  self.title = ko.observable(project.title);
  self.description = ko.observable(project.description);
  self.articlesCount = ko.observable(project.articles.length ? project.articles.length:'');
  self.wordsCount = ko.observable(project.wordsCount);
  self.articles = ko.observableArray(ko.utils.arrayMap(project.articles, function(article) {
    return new Article(article);
  }));
  self.selectedTemplate = ko.observable();
  self.exchangeSite = ko.observable(project.exchangeSite);
  self.recurrent = ko.observable(project.recurrent);
  self.recurrentPeriod = ko.observable(project.recurrentPeriod);
  self.recurrentTotal = ko.observable(project.recurrentTotal);
  self.writerCategory = ko.observable(project.writerCategory);
  self.writers = ko.observableArray(ko.utils.arrayMap(project.writers, function(writer) {
    return new Writer(writer);
  }));
  self.writersData = ko.observable({});

  self.recurrentDaysTotal = ko.pureComputed(function() {
    var res = (Math.ceil(self.recurrentTotal() / self.articlesCount())) * self.recurrentPeriod();
    return res ? res : 0;
  });

  self.showRecurrentMessage = ko.pureComputed(function() {
    return self.recurrentTotal() && self.recurrentPeriod() && self.articlesCount();
  });

  self.myTitle = ko.computed(function() {
    if(self.articles()[0]) return self.articles()[0].title();
  })

  self.recurrentCheck = function () {
    if (self.recurrent() === "1") {
      document.getElementById("copywriting_project_recurrent_period").required = true;
      document.getElementById("copywriting_project_recurrent_total").required = true;
    } else {
      document.getElementById("copywriting_project_recurrent_period").required = false;
      document.getElementById("copywriting_project_recurrent_total").required = false;
    }
    return true;
  };
  self.isRecurrent = function(){
    return self.recurrent() === "1";
  };

  // Article operations
  self.addArticle = function() {
    var data = {
      titleArticle: translations.articles['new_article'],
      wordsCount: self.wordsCount()
    };
    self.articles.push(new Article(data));
    $('.tagsinput').tagsinput('refresh');
    $(".data-tooltip").tooltip();
  };
  self.addMultiple = function() {
    var fields = $('#create-copywriting-project .form-control');

    var articlesField = $('#number_of_articles');
    var wordsField = $('#number_of_words');

    if( wordsField.val() < 100 && articlesField.val() > 100){
      articlesField[0].setCustomValidity(translations.validation.wrongRange);
      wordsField[0].setCustomValidity(translations.validation.wrongRange);
    } else {
      articlesField[0].setCustomValidity('');
      wordsField[0].setCustomValidity('');
      try {
        wordsField.closest('.form-group').toggleClass('has-error', !wordsField[0].reportValidity());
        articlesField.closest('.form-group').toggleClass('has-error', !articlesField[0].reportValidity());
      } catch(e) {

      }
    }

    for(var field_i = 0; field_i < fields.length; field_i++){

      var field = fields[field_i];

      try {
        if (!field.reportValidity()) {
          field.closest('.form-group').addClass('has-error');
        }

      } catch (e) {
        if($('#create-copywriting-project')[0].checkValidity() !== true){
          $('#copywriting_project_submit').trigger('click')
        }
      }
    }

    if($('#create-copywriting-project')[0].checkValidity() === true){
      self.loadWriters();
      self.calculatePrice();

      var underlyingArray = this.articles();
      var data = {};
      if(self.articlesCount() > 1) {
        underlyingArray.push(new Article({
          title: translations.articles['all_articles_placeholder'],
          instructions: translations.articles['all_articles_placeholder'],
          wordsNumber: self.wordsCount(),
          id: "All",
        }));
      }
      for (var i = 0, j = self.articlesCount(); i < j; i++) {
        data = {
          title: translations.articles['new_article'] + " " + (i + 1),
          wordsNumber: self.wordsCount()
        };

        underlyingArray.push(new Article(data));
      }
      self.articles.valueHasMutated();
      var tagsInput = $('.tagsinput');
      tagsInput.tagsinput({
        tagClass: getTagClass
      });
      tagsInput.tagsinput('refresh');
      tagsInput.css('display', 'block');
      $(".data-tooltip").tooltip();
    }
    $('#form_exchange_site, #form_language').trigger('chosen:updated').attr('disabled', false);
  };

  self.removeArticle = function(article) {
    if(self.articles().length > 2){
        self.articles.remove(article);
        $('.j-all-articles').removeClass('btn-outline');
        $('#idAll').addClass('active');
    }
  };

  self.statisticsOfArticles = function () {
    var resultStatistics = [];
    resultStatistics.totalEmpty = 0;
    resultStatistics.totalNotEmpty = 0;

    for (var article_i = 0; article_i < self.articles().length; article_i++) {
      var article = self.articles()[article_i];
      var statistics = article.statisticsOfEmptyFields();

      resultStatistics.totalEmpty += statistics.empty;
      resultStatistics.totalNotEmpty += statistics.notEmpty;
      resultStatistics.push(article.statisticsOfEmptyFields());
    }

    return resultStatistics;
  };

  self.copyRulesOnAnotherArticle = function (currentArticle, attributeName) {
    var articles = self.articles();

    var propVal = currentArticle[attributeName];
    var val = propVal();
    for (var i = 1; i < articles.length; ++i) {
      if (ko.isObservable(propVal)) {
        articles[i][attributeName].call(null,val);
        if ($.type(val) === 'array') {
          articles[i][attributeName].call(null,currentArticle[attributeName].slice(0));
        }
      }
    }
    self.articles.valueHasMutated();
  };

  self.loadTemplate = function () {
    if(self.selectedTemplate() !== undefined && self.selectedTemplate() !== ""){

      $('.steps-item').removeClass('disabled');

      var templateId = self.selectedTemplate();
      var url = Routing.generate("copywriting_template_fetch", {'id':templateId});

      $.get(url, function(templateProject){
        self.articles.removeAll();

        try {
          var project = JSON.parse(templateProject);
        }catch (error){
          toastr.error(translations.jsonParse['error_parse']);
        }

        self.title(project.title);
        self.description(project.description);

        var $languageField = $('#form_language');
        $languageField
              .val(project.language)
              .change();
        $languageField.trigger("chosen:updated");

        if(project.orders.length !== undefined && project.orders.length > 0){
          self.loadWriters();

          self.wordsCount(project.orders[0].wordsNumber);
          self.articlesCount(project.orders.length);

          if(project.orders.length > 1){
            var allArticle = Object.assign({}, project.orders[0]);
            allArticle['title'] = "All articles";
            allArticle['id'] = "All";
            allArticle['instructions'] = "";
            self.articles.push(new Article(allArticle));
          }
          project.orders.forEach(function(article){
            self.articles.push(new Article(article));
          });
        }
        self.calculatePrice();
        var tagsInput = $('.tagsinput');
        tagsInput.tagsinput({
          tagClass: getTagClass
        });
        tagsInput.css('display', 'block');
      });
    } else {
      self.articles.removeAll();
      self.articlesCount("");
      self.title("");
      self.description("");
      self.wordsCount("");
    }
  };

  $('#form_language').on('change', function(event) {
    siteLanguage = $(event.target).val();
  });

  self.loadWriters = function () {
    $.get(Routing.generate('copywriting_get_writers'), {"language": siteLanguage}, function (response) {
      try {
        var parsedResponse = JSON.parse(response);
        if(parsedResponse.status === "success"){
          self.writersData = parsedResponse.categories;
          for(var writerCategory in parsedResponse.categories){
            if(parsedResponse.categories.hasOwnProperty(writerCategory)){
              if(parsedResponse.categories[writerCategory].length === 0){
                var categoryInput = $('input[value=' + writerCategory + ']');
                categoryInput.prop( "disabled", true);
                categoryInput.closest('label.card-writer').addClass("card-disabled");
              }
            }
          }
        }else{
          toastr.error(response.message);
        }
      }catch (error) {
        toastr.error(translations.jsonParse['error_parse']);
      }
    });
  };

  self.categoryChanged = function () {
    setTimeout(function () {
      if($('.slick-initialized').length){
        var slickWriters = $('.slick_writers');
        slickWriters.slick('unslick');
        slickWriters.children().remove();
      }
      self.writers.removeAll();
      $('input[name="chosen_writers"]').val('');

      var writers = self.writers();
      var writerData = self.writersData[self.writerCategory()];

      self.calculatePrice();

      if(writerData !== undefined){
        for(var writer_i = 0; writer_i < writerData.length; writer_i++){
          var writer = writerData[writer_i];
          writers.push(new Writer(writer));
        }

        self.writers.valueHasMutated();
        $('.slick_writers').slick(slickConfig);
        $('input[name="checkbox-writer"]').on('change',function () {
          var resultIds = [];
          var chosenWriters = $('input[name="checkbox-writer"]:checked');
          chosenWriters.each(function (index, element) {
            resultIds.push(element.value)
          });
          $('input[name="chosen_writers"]').val(resultIds.join(','));
        })
      }
    }, 0);
  };

  self.changeOnAllTab = function(article, event){
    if(article.isAllTab()){
      var targetName = event.target.name;
      if(targetName !== ""){
        var formattedName = targetName.split(']')[2].slice(1);
        self.copyRulesOnAnotherArticle(article, formattedName);
      }
      self.calculatePrice();
    }
  };

  self.setKeywords = function(article, event) {
    event.stopPropagation();
    previousAllKeywords = allKeywords;

    var articles = self.articles();

    if(article.id === "idAll") {
      if(allowedOther) {
        allowedGeneral = false;
        passKeywordsToOther(article, articles);
        gatherStatistic();
        allowedGeneral = true;
      }
    } else {
      if(allowedGeneral) {
        allowedOther = false;
        passKeywordsToGeneral(article);
        gatherStatistic();
        allowedOther = true;
      }
    }
  }

function passKeywordsToOther(article, articles) {
  allKeywords = [];

  allKeywords = article.keywords().split(',');

  var status = '';

  if(allKeywords.length > previousAllKeywords.length) {
    var changedKeywords = $(allKeywords).not(previousAllKeywords).toArray();
    status = 'added';
  } else {
    var changedKeywords = $(previousAllKeywords).not(allKeywords).toArray();
    status = 'removed';
  }

  if(status === 'added') {
    for(var i = 1; i < articles.length; i++) {
      for(var j = 0; j < changedKeywords.length; j++) {
        $(articles[i].href).find(".tagsinput").tagsinput({
          tagClass: getTagClass
        });
        $(articles[i].href).find(".tagsinput").tagsinput('add', changedKeywords[j] );
      }
   }
  } else {
    for(var i = 1; i < articles.length; i++) {
      for(var j = 0; j < changedKeywords.length; j++) {
        $(articles[i].href).find(".tagsinput").tagsinput('remove', changedKeywords[j] );
      }
    }
  }
}

function passKeywordsToGeneral(article) {
  var keywordsNames = article.keywords().split(',');
  var newKeyword = keywordsNames[keywordsNames.length - 1];

  if(allKeywords.indexOf(newKeyword) === -1){
    allKeywords.push(newKeyword);
    $('#idAll').find(".tagsinput").tagsinput('add', newKeyword);
  }
}

function gatherStatistic () {
  var articles = self.articles();
  var keywordStatistic = {};
  allKeywords = [];

  var inputs = $('.bootstrap-tagsinput');

  for(var i = 1; i < inputs.length; i++) {
    var currentSpans = $(inputs[i]).find('span.label');
    for(var j = 0; j < currentSpans.length; j++) {
      var spanValue = $(currentSpans[j]).text();
      if(keywordStatistic[spanValue]) {
        keywordStatistic[spanValue].amount++;
      } else {
        keywordStatistic[spanValue] = {};
        keywordStatistic[spanValue].amount = 0;
        keywordStatistic[spanValue].hash = $(currentSpans[j]).attr('class')
      }

      if(allKeywords.indexOf(spanValue) === -1) {
        allKeywords.push(spanValue);
      }
    }
  }

  setTimeout(function() {
    allowedOther = false;
    $('#idAll').find('.tagsinput').tagsinput('removeAll');
    for(var i = 0; i < allKeywords.length; i++) {
      allowedOther = false;
      $('#idAll').find('.tagsinput').tagsinput('add', allKeywords[i]);
    }

    allowedOther = true;

    for(var key in keywordStatistic) {
      $.each($('#idAll .bootstrap-tagsinput > span'), function(index, value) {
        if($(value).attr('class') === keywordStatistic[key].hash){
          $(value).html(key + " (" + (keywordStatistic[key].amount + 1) + "/" + (articles.length - 1) + ")" + "<span data-role='remove'></span>");
        }
      })
    }
  },1);
}

  self.calculatePrice = function () {
    var wordsPrice = (self.articlesCount() * self.wordsCount() / 100) * prices['price_100_words'];
    var images = 0;
    var expressWords = 0;
    var metaDescription = 0;
    // var descriptionPrice = self.description;

    for(var article2_i = 0; article2_i < self.articles().length; article2_i++){
      var article2 = self.articles()[article2_i];
      if(article2.isAllTab()) continue;

      if(!isNaN(parseInt(article2.imagesPerArticleTo()))){
        images += parseInt(article2.imagesPerArticleTo());
      }

      if(article2.express() === true){
        expressWords += parseInt(article2.wordsNumber());
      }
      if (article2.metaDescription() === true) {
        ++metaDescription;
      }
    }
    var imagesPrice = images * prices['price_image'];
    var expressPrice = (expressWords / 100) * prices['express_rate'];
    var categoryPrice = prices['price_' + self.writerCategory()] ? prices['price_' + self.writerCategory()] : 0;
    var chooseWriterPrice = categoryPrice * (self.articlesCount() * self.wordsCount() / 100);
    var metaDescriptionPrice = metaDescription * prices['price_for_meta_description'];

    var result = Math.round((wordsPrice + imagesPrice + expressPrice + chooseWriterPrice + metaDescriptionPrice) * 100) / 100;

    $('#price').text(result + "€");
  };
  self.showNextStep = function(model, event){
    var nextItem = $(event.target).closest('.steps-item').next('.steps-item');
    var nextItemRow = nextItem.find('.step-rows');

    nextItemRow.slideDown(400);
    nextItem.removeClass('disabled');
  };

  self.buttonStatus =  ko.observable(true);
  self.showArticleNav = function(model, event){
    var articleNav = $(event.target).parents('.articles-nav').find('.nav-tabs');

    self.buttonStatus(!self.buttonStatus());
    articleNav.slideToggle(400);
  };

  self.clearNavTabs = function (model, event) {
    $('.j-all-articles').removeClass('btn-outline');
    var activeTab = $(event.target).parents('.articles-nav').find('.nav-tabs .active');
    if(activeTab.length === 0 ) return;
    activeTab.removeClass('active');
  };

  self.lastSavedJson = ko.observable("")
};
var ArticleModel = new AddArticlesModel(initialData);

ko.validatedObservable(ArticleModel);

ko.applyBindings(ArticleModel);

$('#copywriting_project_template').on('change', function () {
  $('label[for="copywriting_project_template"]').toggleClass('checked')
});

$('.articles-nav').on('click', '.nav.nav-tabs li', function () {
  $('.j-all-articles').addClass('btn-outline');
});

$('#form_exchange_site').on('change', function () {
    var language = $(this).find(':selected').data('language');
    var $languageField = $('#form_language');
    if (language) {
        $languageField
            .val(language)
            .change()
            .prop('disabled', true)
        ;
    } else {
        $languageField.prop('disabled', false);
    }
    $languageField.trigger("chosen:updated");
});