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
  !*** ./resources/js/legacy/script.js ***!
  \***************************************/
__webpack_require__.r(__webpack_exports__);
$('.bus_img').slick({
  dots: true,
  arrows: true,
  infinite: true,
  autoplay: true,
  speed: 300,
  slidesToShow: 1,
  adaptiveHeight: true
  /*responsive: [
      {
          breakpoint: 767,
          settings: {
              arrows: false,
          }
      }
  ]*/
});
var overlay = document.getElementById('popup-regular');
$('[data-open-popup-regular]').on('click', function () {
  var popup = document.getElementById('popup-regular');
  popup.style.display = 'flex';
  document.getElementById('step-country').style.display = 'block';
  document.getElementById('step-country').classList.add('show');
});
overlay.addEventListener('click', function (event) {
  if (event.target === overlay) {
    closePopup();
  }
});
function closePopup() {
  document.getElementById('popup-regular').style.display = 'none';
}
/******/ })()
;