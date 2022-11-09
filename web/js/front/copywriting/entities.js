"use strict";

var counter = 0;

function Article(data) {
  var self = this;
  self.id = 'id' + (data.id !== undefined ? data.id : counter.toString());
  self.href = '#id' + (data.id !== undefined ? data.id : counter.toString());
  self.title = ko.observable(data.title);
  self.instructions = ko.observable(data.instructions);
  self.wordsNumber = ko.observable(data.wordsNumber);
  self.metaTitle = ko.observable(data.metaTitle);
  self.metaDescription = ko.observable(data.metaDescription);
  self.headerOneSet = ko.observable(data.headerOneSet);
  self.headerTwoStart = ko.observable(data.headerTwoStart);
  self.headerTwoEnd = ko.observable(data.headerTwoEnd);
  self.headerThreeStart = ko.observable(data.headerThreeStart);
  self.headerThreeEnd = ko.observable(data.headerThreeEnd);
  self.boldText = ko.observable(getValueForRadioButton(data.boldText));
  self.quotedText = ko.observable(getValueForRadioButton(data.quotedText));
  self.italicText = ko.observable(getValueForRadioButton(data.italicText));
  self.ulTag = ko.observable(getValueForRadioButton(data.ulTag));
  self.keywordsPerArticleFrom = ko.observable(data.keywordsPerArticleFrom);
  self.keywordsPerArticleTo = ko.observable(data.keywordsPerArticleTo);
  self.keywordInMetaTitle = ko.observable(data.keywordInMetaTitle);
  self.keywordInHeaderOne = ko.observable(data.keywordInHeaderOne);
  self.keywordInHeaderTwo = ko.observable(data.keywordInHeaderTwo);
  self.keywordInHeaderThree = ko.observable(data.keywordInHeaderThree);
  self.imagesPerArticleFrom = ko.observable(data.imagesPerArticleFrom);
  self.imagesPerArticleTo = ko.observable(data.imagesPerArticleTo);
  self.express = ko.observable(data.express);
  self.headerTwoEnabled= ko.computed(function () {
    return (!!(+self.headerTwoStart()) || !!(+self.headerTwoEnd()));
  });
  self.headerThreeEnabled= ko.computed(function () {
    return (!!(+self.headerThreeStart()) || !!(+self.headerThreeEnd()));
  });
  self.keywordsEnabled = ko.computed(function () {
    return (!!(+self.keywordsPerArticleFrom()) || !!(+self.keywordsPerArticleTo()));
  });
  self.titleTab = ko.computed(function () {
    return self.title() + " (" + self.wordsNumber() + ")";
  });
  self.isAllTab = ko.computed(function () {
    return self.id === 'idAll';
  });
  self.keywords = ko.observable(getKeywords(data.keywords));
  self.images = ko.observableArray(ko.utils.arrayMap(data.images, function (image) {
    return new Image(image, self);
  }));
  self.headerTwoRequired = ko.computed(function () {
    return !self.isAllTab() && (!!self.headerTwoStart() || !!self.headerTwoEnd());
  });
  self.headerThreeRequired = ko.computed(function () {
    return !self.isAllTab() && (!!self.headerThreeStart() || !!self.headerThreeEnd());
  });
  self.keywordsRequired = ko.computed(function () {
    return !self.isAllTab() && (self.keywords().length > 0 || !!self.keywordsPerArticleFrom() || !!self.keywordsPerArticleTo());
  });
  self.imagesRequired = ko.computed(function () {
    return  !self.isAllTab() && (!!self.imagesPerArticleTo() || !!self.imagesPerArticleFrom());
  });

  // Images operations
  self.addImage = function (article) {
    var data = {
      alt: '',
      url: ''
    };
    article.images.push(new Image(data, article));
  };

  self.removeImage = function (image) {
    image.article.images.remove(image);
  };

  self.statisticsOfEmptyFields = function () {
    var statistics = {
      'empty': 0,
      'notEmpty': -2 //remove id, href,

    };

    for (var key in self) {
      var propVal = self[key];

      if (ko.isObservable(propVal)) {
        var val = propVal();

        if (val === undefined || Array.isArray(val) && val.length === 0) {
          statistics.empty++;
        } else {
          statistics.notEmpty++;
        }
      }
    }

    return statistics;
  };

  if (data.id === undefined) {
    counter += 1;
  }
}

function Image(data, article) {
  var self = this;
  self.alt = ko.observable(data.alt);
  self.url = ko.observable(data.url);
  self.article = article;
}

function Writer(data) {
  var self = this;
  self.id = ko.observable(data[0].id);
  self.username = ko.observable(data[0].fullName);
  self.avatar = ko.observable(data.avatar);
  self.likes = ko.observable(data.likes);
  self.dislikes = ko.observable(data.dislikes);
  self.deadline = ko.observable(data.deadline);
  self.chosen = ko.observable(false);

  self.getDeadlineMessage = function () {
    if (self.deadline() === undefined) {
      return "";
    }

    if (self.deadline() === 0) {
      return translations.writers['start_today'];
    }

    return translations.writers['start_days'].replace('%days%', self.deadline());
  };

  self.isSetLikes = function () {
    return self.likes() !== undefined;
  };
}

function getValueForRadioButton($serverValue) {
  if ($serverValue === true || $serverValue === "0") {
    return "0";
  }

  if ($serverValue === false || $serverValue === "1") {
    return "1";
  }

  if ($serverValue === null || $serverValue === "2") {
    return "2";
  }

  return "2";
}

function getKeywords(keywords) {
  if (keywords === undefined) {
    return "";
  } else if (typeof keywords === "string") {
    return keywords;
  } else {
    var stringKeywords = keywords.reduce(function (accumulator, value) {
      return accumulator += value.word + ',';
    }, "");
    return stringKeywords.slice(0, -1);
  }
}
