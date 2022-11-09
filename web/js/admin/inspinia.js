"use strict";

/*
 *
 *   INSPINIA - Responsive Admin Theme
 *   version 2.7.1
 *
 */
function destroyStickyTableHeaders() {
  $('.footable').stickyTableHeaders('destroy');
  $('.footable > thead').removeAttr('style');
  $('.footable-header > th').css({
    'min-width': 'initial',
    'max-width': 'initial'
  });
}

function redrawStickyTableHeaders() {
  if ($('.tableFloatingHeader').length) destroyStickyTableHeaders();
  if ($('.footable').length && $(window).width() >= stickyHeadersBP) setTimeout(function () {
    $('.footable').stickyTableHeaders();
  }, 600);
}

function showMenu() {
  $("body").addClass("visible-sidebar");
  setTimeout(function () {
    $(".sidebar-collapse").css('left', '0');
  }, 400);
  redrawStickyTableHeaders();
  if ($('.footable').length) $('.footable').trigger('resize');
}

function hideMenu() {
  $("body").removeClass("visible-sidebar");
  redrawStickyTableHeaders();
}

function checkChosenPosition(evt) {
  var select = evt.currentTarget.nextSibling,
    dropDownH = $(select).find('.chosen-drop').height(),
    winScrollTop = $(window).scrollTop(),
    dropOffset = $(select).offset().top + $(select).height(),
    bottomY = dropDownH + dropOffset - winScrollTop;
  $(select).toggleClass('reverse', bottomY > $(window).height());
}

function footableInit() {
  if ($('.footable').length == 0) return

  $('.footable').each(function (){
    if($(this).hasClass('enabled')) {
      $(window).trigger('resize');
      $('.j-tabs-with-footable li').css('pointer-events', 'initial');
    } else {
      $(this).not('#tableTransactions').footable(footableConfig);
    }
  });
}

var footableConfig = {
  "breakpoints": {
    "xxs": 220,
    "xs": 400,
    "s": 500,
    "m": 620,
    "l": 768,
    "xl": 960,
    "xxl": 1020,
    "xxxl": 1200,
    "xxxxl": 1400
  },
  "cascade": true,
  "useParentWidth": true,
  "on": {
    "ready.ft.table": function readyFtTable(e, ft) {
      ft.$el.addClass('enabled');
      $('.j-tabs-with-footable li').css('pointer-events', 'initial');
      $('.i-checks').iCheck({
        checkboxClass: 'icheckbox_square-green',
        radioClass: 'iradio_square-green'
      });
      $('select.form-control[multiple!=multiple]').not("#filters_ageCondition").chosen({
        width: "100%"
      });

      if ($(window).width() >= stickyHeadersBP) {
        if ($('.tableFloatingHeader').length) destroyStickyTableHeaders();
        $('.footable').stickyTableHeaders();
        $('.tableFloatingHeader').css('opacity', '1'); // fixing plugin's bug
      }
    },
    "before.ft.breakpoints": function beforeFtBreakpoints() {
      if ($('.j-netlinking-comment_hide').length) {
        $('.j-netlinking-comment_hide').text(show_comment).removeClass('j-netlinking-comment_hide').addClass('j-netlinking-comment_show');
      }

      if ($('.j-temporary-row').length) $('.j-temporary-row').remove(); //clear comments-row

      if ($('.tableFloatingHeader').length) destroyStickyTableHeaders();
    },
    "after.ft.breakpoints": function afterFtBreakpoints(e, ft) {
      if ($(window).width() >= stickyHeadersBP) $('.footable').not('#quickPurchaseTable').stickyTableHeaders();
    }
  }
};

var stickyHeadersBP = 769,
  handleClosedNav = false;

$(document).ready(function () {
  // Minimalize menu
  $('.navbar-minimalize').on('click', function (event) {
    event.preventDefault();

    if ($("body").hasClass("visible-sidebar")) {
      handleClosedNav = true;
      hideMenu();
    } else {
      handleClosedNav = false;
      showMenu();
    }
  });
  footableInit();
  $('.i-checks').not('.footable .i-checks').iCheck({
    checkboxClass: 'icheckbox_square-green',
    radioClass: 'iradio_square-green'
  });
  $('#erefererAlerts').on('hide.bs.modal', function (e) {
    $(this).find('.modal-title').empty();
    $(this).find('.modal-body').empty();
  });
  $('.table').on('click', '.state', function (event) {
    event.preventDefault();
    var href = $(this).attr('href');
    var self = $(this);
    var span = $(this).find('span');
    var icon = $(this).find('i');
    $.ajax({
      type: 'GET',
      url: href,
      dataType: 'json',
      success: function success(response) {
        toastr.success(span.text());
        self.attr({
          'title': response.text
        });

        if (response.action == 'activate') {
          icon.removeClass('fa-eye-slash');
          icon.addClass('fa fa-eye');
        } else {
          icon.removeClass('fa-eye');
          icon.addClass('fa-eye-slash');
        }

        span.text(response.text);
      },
      error: function error(XMLHttpRequest, textStatus, errorThrown, res) {
        toastr.error(res.text);
      }
    });
  });
  $('body').on('click', '.delete', function (event) {
    event.preventDefault();
    var href = $(this).attr('href');
    swal({
      title: translations.modal.delete.title,
      text: translations.modal.delete.text,
      type: "warning",
      showCancelButton: true,
      cancelButtonText: translations.modal.cancel.text,
      confirmButtonColor: "#ed5565",
      confirmButtonText: translations.modal.delete.confirmButtonText,
      closeOnConfirm: false
    }, function () {
      window.location.href = href;
    });
  });

  function defaultConfirmationAction($this) {
    window.location.href = $this.attr('href');
  }

  $('#page-wrapper').on('click', '.confirmation', function (event) {
    event.preventDefault();
    var $this = $(this);
    swal({
      title: $(this).data('title') !== undefined ? $(this).data('title') : translations.modal.confirmation.title,
      text: $(this).data('text') !== undefined ? $(this).data('text') : translations.modal.confirmation.text,
      type: "warning",
      cancelButtonText: translations.modal.cancel.text,
      showCancelButton: true,
      confirmButtonColor: "#ed5565",
      confirmButtonText: translations.modal.confirmation.confirmButtonText,
      closeOnConfirm: !($(this).data('closeOnConfirm') && $(this).data('closeOnConfirm') === 'false')
    }, function () {
        if ($this.data('confirmation') !== undefined) {
          window[$this.data('confirmation')]($this);
        } else {
          defaultConfirmationAction($this);
        }
    });
  });

  // MetisMenu
  $('#side-menu').metisMenu();

  // Collapse ibox function
  $('.collapse-link').on('click', function () {
    var ibox = $(this).closest('div.ibox');
    var button = $(this).find('i');
    var content = ibox.children('.ibox-content');
    content.slideToggle(200);
    button.toggleClass('fa-chevron-up').toggleClass('fa-chevron-down');
    ibox.toggleClass('').toggleClass('border-bottom');
    setTimeout(function () {
      ibox.resize();
      ibox.find('[id^=map-]').resize();
    }, 50);
  }); // Close ibox function

  $('.close-link').on('click', function () {
    var content = $(this).closest('div.ibox');
    content.remove();
  }); // Fullscreen ibox function

  $('.fullscreen-link').on('click', function () {
    var ibox = $(this).closest('div.ibox');
    var button = $(this).find('i');
    $('body').toggleClass('fullscreen-ibox-mode');
    button.toggleClass('fa-expand').toggleClass('fa-compress');
    ibox.toggleClass('fullscreen');
    setTimeout(function () {
      $(window).trigger('resize');
    }, 100);
  }); // Run menu of canvas

  $('body.canvas-menu .sidebar-collapse').slimScroll({
    height: '100%',
    railOpacity: 0.9
  }); // Open close right sidebar

  $('.right-sidebar-toggle').on('click', function () {
    $('#right-sidebar').toggleClass('sidebar-open');
  }); // Initialize slimscroll for right sidebar

  $('.sidebar-container').slimScroll({
    height: '100%',
    railOpacity: 0.4,
    wheelStep: 10
  }); // Open close small chat

  $('.open-small-chat').on('click', function () {
    $(this).children().toggleClass('fa-comments').toggleClass('fa-remove');
    $('.small-chat-box').toggleClass('active');
  }); // Initialize slimscroll for small chat

  $('.small-chat-box .content').slimScroll({
    height: '234px',
    railOpacity: 0.4
  }); // Small todo handler

  $('.check-link').on('click', function () {
    var button = $(this).find('i');
    var label = $(this).next('span');
    button.toggleClass('fa-check-square').toggleClass('fa-square-o');
    label.toggleClass('todo-completed');
    return false;
  });

  // Tooltips demo
  $('[data-toggle=tooltip]').tooltip({
    container: "body"
  });
  var popOverSettings = {
    // placement: 'auto top',
    // container: 'body',
    placement: 'right',
    //local
    container: '.wrapper-content',
    //local
    // container: 'body', //from server
    html: true,
    selector: '[data-toggle=popover]'
  };
  $('body').popover(popOverSettings);

  // Add slimscroll to element
  $('.full-height-scroll').slimscroll({
    height: '100%'
  });

  $('.j-tabs-with-footable li').on('click', function () {
    if ($(this).hasClass('active')) return;
    $('.j-tabs-with-footable li').css('pointer-events', 'none');
    footableInit();
  });

  $('body').on('click', '.toggle-details', function (e) {
    $(e.currentTarget).closest("tr").trigger('click');
  });
});

// Minimalize menu when screen is less than 768px
function toggleSidebar() {
  if ($(window).width() < 769) {
    hideMenu();
  } else {
    if (!handleClosedNav) showMenu();
  }
}

var currentWindowWidth = $(window).outerWidth();
$(window).bind("resize", function (e) {
  var newWindowWidth = $(window).outerWidth();
  if (currentWindowWidth === newWindowWidth) return;
  currentWindowWidth = newWindowWidth;
  toggleSidebar();
  if ($(this).width() < stickyHeadersBP && $('.tableFloatingHeader').length) destroyStickyTableHeaders();
});

// Local Storage functions
// Set proper body class and plugins based on user configuration
$(document).ready(function () {

  if ($(window).width() < 769) {
    hideMenu();
  }

  var a = new StickySidebar('.navbar-static-side', {
    //   topSpacing: 20,
    //   bottomSpacing: 50,
    containerSelector: '#wrapper',
    innerWrapperSelector: '.sidebar-collapse'
  });

  if (localStorageSupport()) {
    var collapse = localStorage.getItem("collapse_menu");
    var fixedsidebar = localStorage.getItem("fixedsidebar");
    var fixednavbar = localStorage.getItem("fixednavbar");
    var boxedlayout = localStorage.getItem("boxedlayout");
    var fixedfooter = localStorage.getItem("fixedfooter");
    var body = $('body');
    fixedsidebar = 'on';

    if (fixedsidebar == 'on') {// body.addClass('fixed-sidebar');
      // $('.sidebar-collapse').slimScroll({
      //     height: '100%',
      //     position: 'left',
      //     wheelStep: 5,
      //     railVisible: true,
      //     railOpacity: .7,
      //     opacity: 1,
      //     size: '8px',
      //     color: '#1ab394',
      // });
    }

    if (collapse == 'on') {
      if (body.hasClass('fixed-sidebar')) {
        if (!body.hasClass('body-small')) {// body.addClass('mini-navbar');
        }
      } else {
        if (!body.hasClass('body-small')) {// body.addClass('mini-navbar');
        }
      }
    }

    if (fixednavbar == 'on') {
      $(".navbar-static-top").removeClass('navbar-static-top').addClass('navbar-fixed-top');
      body.addClass('fixed-nav');
    }

    if (boxedlayout == 'on') {
      body.addClass('boxed-layout');
    }

    if (fixedfooter == 'on') {
      $(".footer").addClass('fixed');
    }
  }

  // searchbar toggler
  $('.j-search-trigger').on('click', function () {
    $('.j-navbar-search').slideToggle(200);
  });

  // submit form by "Enter"-hit for IE-11
  $('.navbar-header form').on('keypress', function (e) {
    var form = $(this);
    var serchQuery = $('#serch-query').val();

    if (e.keyCode === 13) {
      if (serchQuery !== "") {
        form.submit()
      } else {
        e.preventDefault()
      }
    }
  });

});

var touchstartX = 0;
var touchstartY = 0;
var touchendX = 0;
var touchendY = 0;
var gestureZone = document.getElementById('swipe-area');
gestureZone.addEventListener('touchstart', function (event) {
  touchstartX = event.changedTouches[0].screenX;
  touchstartY = event.changedTouches[0].screenY;
}, false);
gestureZone.addEventListener('touchend', function (event) {
  touchendX = event.changedTouches[0].screenX;
  touchendY = event.changedTouches[0].screenY;

  if (Math.abs(touchstartX - touchendX) > Math.abs(touchstartY - touchendY) + 20) {
    if (touchendX > touchstartX) {
      showMenu();
    } else if (touchendX < touchstartX) {
      hideMenu();
    }
  }
}, false);

// check if browser support HTML5 local storage
function localStorageSupport() {
  return 'localStorage' in window && window['localStorage'] !== null;
}

// For demo purpose - animation css script
function animationHover(element, animation) {
  element = $(element);
  element.hover(function () {
    element.addClass('animated ' + animation);
  }, function () {
    //wait for animation to finish before removing classes
    window.setTimeout(function () {
      element.removeClass('animated ' + animation);
    }, 2000);
  });
} // Dragable panels


function WinMove() {
  var element = "[class*=col]";
  var handle = ".ibox-title";
  var connect = "[class*=col]";
  $(element).sortable({
    handle: handle,
    connectWith: connect,
    tolerance: 'pointer',
    forcePlaceholderSize: true,
    opacity: 0.8
  }).disableSelection();
} // init multiple selects for mobile


function initMultipleSelect(select, max_selected) {
  var last_valid_selection = select.val() ? select.val() : null;
  select.chosen({
    max_selected_options: max_selected,
    width: "100%"
  });
  select.change(function (event) {
    if ($(this).val().length > max_selected) {
      $(this).val(last_valid_selection);
    } else {
      last_valid_selection = $(this).val();
    }
  });
}

function generateModal_500_error(response) {
  response.title = translations.modal.error;
  response.body = translations.modal.error_500;
}
