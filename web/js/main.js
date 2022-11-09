"use strict";

$(document).ready(function () {
    var languageSelect = $('.topnav-lang-select');

    languageSelect.chosen({
        disable_search_threshold: 10,
        width: '40px'
    });

    languageSelect.on('change', function (e, selected) {
        var hostName = '';
        if (window.location.hostname.search('^([a-z]{2}\\.|www)') === 0) {
            var parts = window.location.hostname.split('.');
            parts.shift();
            hostName = parts.join('.');
        } else {
            hostName = window.location.hostname;
        }
        hostName =  selected.selected + '.' + hostName;
        window.location.href = window.location.href.replace(window.location.hostname, hostName);
    });
});


function errorHandler(code, response) {
    var type = 'warning'; //warning, error, success
    var message = translations.modal.error;


    if (response === undefined) {
        type = 'error';
        message = translations.modal.invalid_json;
    } else {
        message = response.message;
        switch (code) {
            case 500: type = 'error'; break;
            case 404: type = 'error'; break;
            case 403: type = 'warning'; break;
            case 401: type = 'warning'; break;
            case 422: type = 'warning'; break;
        }
    }
    if (Array.isArray(message)) {
        for(var key in message) {
            toastr[type](message[key]);
        }
    } else {
        toastr[type](message);
    }
}

var defaultOptionsRequest = {
    errorHandlerEnabled: true, // enabled default error handler (errors are displayed in flash notifications)
};
function sendRequest(url, type, data, successCallback, failCallback, options) {
    if (options === undefined) {
        options = {};
    }
    options = $.extend(defaultOptionsRequest, options);
    $.ajax(
        $.extend({
            url: url,
            type: type,
            data: data,
            complete: function (response) {
                if (response.status === 200 && response.responseJSON !== undefined) {
                    successCallback(response.responseJSON);
                } else {
                    if (options.errorHandlerEnabled) {
                        errorHandler(response.status, response.responseJSON);
                    }
                    if (typeof failCallback === 'function') {
                        failCallback(response);
                    }
                }
            }
        },
        options)
    );
}


function sendGetRequest(url, data, successCallback, failCallback, options) {
    if (typeof data === 'function') {
        failCallback = successCallback;
        successCallback = data;
    }

    return sendRequest(url, 'get', data, successCallback, failCallback, options);
}

function sendPostRequest(url, data, successCallback, failCallback, options) {
    return sendRequest(url, 'post', data, successCallback, failCallback, options);
}
