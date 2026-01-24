/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
/*!***************************************!*\
  !*** ./resources/js/legacy/blocks.js ***!
  \***************************************/
__webpack_require__.r(__webpack_exports__);
function out(msg, txt) {
  if (msg == undefined || msg == '' || $('.alert').length > 0) {
    return false;
  }
  var alert = document.createElement('div');
  $(alert).addClass('alert');
  var alertContent = document.createElement('div');
  $(alertContent).addClass('alert_content').appendTo(alert);
  var appendOverlay = document.createElement('div');
  $(appendOverlay).addClass('alert_overlay').appendTo(alert);
  var alertTitle = document.createElement('div');
  $(alertTitle).addClass('alert_title').text(msg.replace(/&#39;/g, "'")).appendTo(alertContent);
  if (txt != '') {
    var alertTxt = document.createElement('div');
    $(alertTxt).addClass('alert_message').html(txt).appendTo(alertContent);
  }
  var closeBtn = document.createElement('button');
  $(closeBtn).addClass('alert_ok').text(close_btn).appendTo(alertContent);
  $('body').append(alert);
  $(alert).fadeIn();
  $('.alert_ok,.alert_overlay').on('click', function () {
    $('.alert').fadeOut();
    setTimeout(function () {
      $('.alert').remove();
    }, 350);
  });
}
;
function initLoader() {
  $('body').prepend('<div class="loader"></div>');
}
;
function removeLoader() {
  $('.loader').remove();
}
;
function isEmail(email) {
  if (email.length < 5) {
    return false;
  }
  var parts = email.split('@');
  if (parts.length !== 2) {
    return false;
  }
  var domain = parts[1];
  if (domain.length < 4) {
    return false;
  }
  var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(email);
}
;
/******/ })()
;