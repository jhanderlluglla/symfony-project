//this part dynamically gets max breakpoint and max actual breakpoint from config
//(note: breakpoints, which are defined in header, work not as expected - for more
//detailes go see footable documentation)

var ourBreakpoints = [];
$.each($('#tableTransactions thead th[data-breakpoints]'), function(index, elem) {
    ourBreakpoints.push($(elem).attr('data-breakpoints'));
})

var maxBreakpoint = 0;

for(var i = 0; i < ourBreakpoints.length; i++) {
    if(footableConfig["breakpoints"][ourBreakpoints[i]] > maxBreakpoint) {
        maxBreakpoint = footableConfig["breakpoints"][ourBreakpoints[i]];
    }
}

var maxActualBreakpoint = maxBreakpoint;
var breakpointArray = [];

for(var key in footableConfig["breakpoints"]) {
    breakpointArray.push(footableConfig["breakpoints"][key]);
}

breakpointArray.sort(function(a, b) {
    return a - b;
})

maxActualBreakpoint = breakpointArray[breakpointArray.indexOf(maxBreakpoint)+1];

//  this is extended functionality which is needed for this table

var extendedConfig = {
    "on": {

        //  checking every single row on ready and hiding row togglers

        "ready.ft.table": function(e, ft) {
            $.each($('.more-details'), function(i, elem) {
                if($('#tableTransactions').width() >= maxActualBreakpoint) {
                    $(elem).parent('tr').find('.footable-toggle').addClass('hide-toggler');
                }
            })
        },

        //  preventing expanding of the row on click if there is nothing to actually show

        "expand.ft.row": function(e, ft, row) {
            if($('#tableTransactions').width() >= maxActualBreakpoint) {
                if($(row.$el[0]).find('.more-details').length) {
                    e.preventDefault();
                }
            }
        },

        //  hiding/showing row togglers when triggering table breakpoints

        "before.ft.breakpoints": function(e, ft, current, next) {

          var active = $('#tableTransactions').width() <= maxActualBreakpoint ? false : true;

          $.each($('.more-details'), function(i, elem) {
            $(elem).parent('tr').find('.footable-toggle').toggleClass('hide-toggler', active);
            if(active && $(elem).parents('.footable-detail-row').length) {
              $(elem).parents('.footable-detail-row').prev().find('.footable-toggle').addClass('hide-toggler');
            }
          })
        }
    }
}

//  pluging in exteded configuration

var newConfig = $.extend(true, {}, footableConfig, extendedConfig);

//  reiniting table with extended conf

$('#tableTransactions').footable(newConfig);
