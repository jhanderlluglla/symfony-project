"use strict";

$(document).ready(function () {
  function changeTime() {
    $('[data-express-timer]').each(function (index, element) {
      var timer = element.getAttribute('data-express-timer');
      element.setAttribute('data-express-timer', timer - 1);
      var hours = Math.floor(timer / 3600);
      timer -= hours * 3600;
      var minutes = Math.floor(timer / 60);
      var seconds = timer - minutes * 60;
      element.textContent = pad(hours) + ":" + pad(minutes) + ":" + pad(seconds);
    });
  }

  changeTime();
  setInterval(changeTime, 1000);

  function pad(value) {
    if (value < 10) {
      return '0' + value;
    } else {
      return value;
    }
  }
});