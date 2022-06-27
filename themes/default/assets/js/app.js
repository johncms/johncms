(self["webpackChunkjohncms"] = self["webpackChunkjohncms"] || []).push([["/themes/default/assets/js/app"],{

/***/ "./themes/default/src/js/app.ts":
/*!**************************************!*\
  !*** ./themes/default/src/js/app.ts ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var swiper_css_bundle__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! swiper/css/bundle */ "./node_modules/swiper/swiper-bundle.min.css");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm-bundler.js");


__webpack_require__(/*! ./bootstrap */ "./themes/default/src/js/bootstrap.js");

__webpack_require__(/*! ./jquery.magnific-popup */ "./themes/default/src/js/jquery.magnific-popup.js");

__webpack_require__(/*! flatpickr */ "./node_modules/flatpickr/dist/esm/index.js");

__webpack_require__(/*! ./menu */ "./themes/default/src/js/menu.js");

__webpack_require__(/*! ./prism */ "./themes/default/src/js/prism.js");

__webpack_require__(/*! ./forum */ "./themes/default/src/js/forum.js");

__webpack_require__(/*! ./modals */ "./themes/default/src/js/modals.js");

__webpack_require__(/*! ./slider */ "./themes/default/src/js/slider.js");

__webpack_require__(/*! ./progress */ "./themes/default/src/js/progress.js");

__webpack_require__(/*! ./wysibb */ "./themes/default/src/js/wysibb.js");

__webpack_require__(/*! ./main */ "./themes/default/src/js/main.js");



var app = function app() {
  return (0,vue__WEBPACK_IMPORTED_MODULE_1__.createApp)({});
};

var vue_apps = document.querySelectorAll('.vue_app');
vue_apps.forEach(function (el) {
  var vueApp = app();
  vueApp.component('LikesComponent', (0,vue__WEBPACK_IMPORTED_MODULE_1__.defineAsyncComponent)(function () {
    return Promise.all(/*! import() */[__webpack_require__.e("/themes/default/assets/js/vendor"), __webpack_require__.e("themes_default_src_js_components_LikesComponent_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! @/components/LikesComponent.vue */ "./themes/default/src/js/components/LikesComponent.vue"));
  }));
  vueApp.component('CommentsComponent', (0,vue__WEBPACK_IMPORTED_MODULE_1__.defineAsyncComponent)(function () {
    return Promise.all(/*! import() */[__webpack_require__.e("/themes/default/assets/js/vendor"), __webpack_require__.e("themes_default_src_js_components_CommentsComponent_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! @/components/CommentsComponent.vue */ "./themes/default/src/js/components/CommentsComponent.vue"));
  }));
  vueApp.component('pagination', (0,vue__WEBPACK_IMPORTED_MODULE_1__.defineAsyncComponent)(function () {
    return Promise.all(/*! import() */[__webpack_require__.e("/themes/default/assets/js/vendor"), __webpack_require__.e("themes_default_src_js_components_Pagination_VuePagination_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! @/components/Pagination/VuePagination.vue */ "./themes/default/src/js/components/Pagination/VuePagination.vue"));
  }));
  vueApp.component('CkeditorInputComponent', (0,vue__WEBPACK_IMPORTED_MODULE_1__.defineAsyncComponent)(function () {
    return Promise.all(/*! import() */[__webpack_require__.e("/themes/default/assets/js/vendor"), __webpack_require__.e("themes_default_src_js_components_CkeditorInputComponent_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! @/components/CkeditorInputComponent.vue */ "./themes/default/src/js/components/CkeditorInputComponent.vue"));
  }));
  vueApp.component('AvatarUploader', (0,vue__WEBPACK_IMPORTED_MODULE_1__.defineAsyncComponent)(function () {
    return Promise.all(/*! import() */[__webpack_require__.e("/themes/default/assets/js/vendor"), __webpack_require__.e("themes_default_src_js_components_AvatarUploader_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! @/components/AvatarUploader.vue */ "./themes/default/src/js/components/AvatarUploader.vue"));
  }));
  vueApp.mount(el);
});

/***/ }),

/***/ "./themes/default/src/js/bootstrap.js":
/*!********************************************!*\
  !*** ./themes/default/src/js/bootstrap.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */
try {
  window.Popper = (__webpack_require__(/*! popper.js */ "./node_modules/popper.js/lib/index.js")["default"]);
  window.$ = window.jQuery = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
  window.axios = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
  window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
  var token = document.head.querySelector('meta[name="csrf-token"]');

  if (token) {
    window.axios.defaults.headers.common['X-CSRF-Token'] = token.content;
  }

  var _ = __webpack_require__(/*! lodash */ "./node_modules/lodash/lodash.js");

  var bootstrap = __webpack_require__(/*! bootstrap */ "./node_modules/bootstrap/dist/js/bootstrap.esm.js");

  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
} catch (e) {}

/***/ }),

/***/ "./themes/default/src/js/forum.js":
/*!****************************************!*\
  !*** ./themes/default/src/js/forum.js ***!
  \****************************************/
/***/ (() => {

$('#first_post').on('hide.bs.collapse', function (e) {
  togglePreview();
}).on('shown.bs.collapse', function () {
  togglePreview();
});

function togglePreview() {
  $('#first_post_block .post-preview').toggle(0);
}

$(function () {
  $('.image-gallery').each(function () {
    $(this).magnificPopup({
      delegate: '.gallery-item',
      type: 'image',
      tLoading: 'Loading image #%curr%...',
      mainClass: 'mfp-img-mobile',
      gallery: {
        enabled: true,
        navigateByImgClick: true,
        preload: [0, 1]
      },
      image: {
        tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
        titleSrc: function titleSrc(item) {
          return item.el.attr('title') + ' &middot; <a class="image-source-link" href="' + item.el.attr('data-source') + '" target="_blank">Download</a>';
        }
      },
      zoom: {
        enabled: true,
        duration: 300,
        opener: function opener(element) {
          return element.find('img');
        }
      }
    });
  });
  $('.image-preview').magnificPopup({
    type: 'image',
    image: {
      verticalFit: true,
      titleSrc: function titleSrc(item) {
        return item.el.attr('title') + ' &middot; <a class="image-source-link" href="' + item.el.attr('data-source') + '" target="_blank">Download</a>';
      }
    },
    zoom: {
      enabled: true,
      duration: 300,
      opener: function opener(element) {
        return element.find('img');
      }
    }
  });
  $('[data-toggle="tooltip"]').tooltip();
});
$(".custom-file-input").on("change", function () {
  var fileName = $(this).val().split("\\").pop();
  $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
});

/***/ }),

/***/ "./themes/default/src/js/jquery.magnific-popup.js":
/*!********************************************************!*\
  !*** ./themes/default/src/js/jquery.magnific-popup.js ***!
  \********************************************************/
/***/ ((module, exports, __webpack_require__) => {

var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

/*! Magnific Popup - v1.1.0 - 2016-02-20
* http://dimsemenov.com/plugins/magnific-popup/
* Copyright (c) 2016 Dmitry Semenov; */
;

(function (factory) {
  if (true) {
    // AMD. Register as an anonymous module.
    !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js")], __WEBPACK_AMD_DEFINE_FACTORY__ = (factory),
		__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
		(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
  } else {}
})(function ($) {
  /*>>core*/

  /**
   *
   * Magnific Popup Core JS file
   *
   */

  /**
   * Private static constants
   */
  var CLOSE_EVENT = 'Close',
      BEFORE_CLOSE_EVENT = 'BeforeClose',
      AFTER_CLOSE_EVENT = 'AfterClose',
      BEFORE_APPEND_EVENT = 'BeforeAppend',
      MARKUP_PARSE_EVENT = 'MarkupParse',
      OPEN_EVENT = 'Open',
      CHANGE_EVENT = 'Change',
      NS = 'mfp',
      EVENT_NS = '.' + NS,
      READY_CLASS = 'mfp-ready',
      REMOVING_CLASS = 'mfp-removing',
      PREVENT_CLOSE_CLASS = 'mfp-prevent-close';
  /**
   * Private vars
   */

  /*jshint -W079 */

  var mfp,
      // As we have only one instance of MagnificPopup object, we define it locally to not to use 'this'
  MagnificPopup = function MagnificPopup() {},
      _isJQ = !!window.jQuery,
      _prevStatus,
      _window = $(window),
      _document,
      _prevContentType,
      _wrapClasses,
      _currPopupType;
  /**
   * Private functions
   */


  var _mfpOn = function _mfpOn(name, f) {
    mfp.ev.on(NS + name + EVENT_NS, f);
  },
      _getEl = function _getEl(className, appendTo, html, raw) {
    var el = document.createElement('div');
    el.className = 'mfp-' + className;

    if (html) {
      el.innerHTML = html;
    }

    if (!raw) {
      el = $(el);

      if (appendTo) {
        el.appendTo(appendTo);
      }
    } else if (appendTo) {
      appendTo.appendChild(el);
    }

    return el;
  },
      _mfpTrigger = function _mfpTrigger(e, data) {
    mfp.ev.triggerHandler(NS + e, data);

    if (mfp.st.callbacks) {
      // converts "mfpEventName" to "eventName" callback and triggers it if it's present
      e = e.charAt(0).toLowerCase() + e.slice(1);

      if (mfp.st.callbacks[e]) {
        mfp.st.callbacks[e].apply(mfp, $.isArray(data) ? data : [data]);
      }
    }
  },
      _getCloseBtn = function _getCloseBtn(type) {
    if (type !== _currPopupType || !mfp.currTemplate.closeBtn) {
      mfp.currTemplate.closeBtn = $(mfp.st.closeMarkup.replace('%title%', mfp.st.tClose));
      _currPopupType = type;
    }

    return mfp.currTemplate.closeBtn;
  },
      // Initialize Magnific Popup only when called at least once
  _checkInstance = function _checkInstance() {
    if (!$.magnificPopup.instance) {
      /*jshint -W020 */
      mfp = new MagnificPopup();
      mfp.init();
      $.magnificPopup.instance = mfp;
    }
  },
      // CSS transition detection, http://stackoverflow.com/questions/7264899/detect-css-transitions-using-javascript-and-without-modernizr
  supportsTransitions = function supportsTransitions() {
    var s = document.createElement('p').style,
        // 's' for style. better to create an element if body yet to exist
    v = ['ms', 'O', 'Moz', 'Webkit']; // 'v' for vendor

    if (s['transition'] !== undefined) {
      return true;
    }

    while (v.length) {
      if (v.pop() + 'Transition' in s) {
        return true;
      }
    }

    return false;
  };
  /**
   * Public functions
   */


  MagnificPopup.prototype = {
    constructor: MagnificPopup,

    /**
     * Initializes Magnific Popup plugin.
     * This function is triggered only once when $.fn.magnificPopup or $.magnificPopup is executed
     */
    init: function init() {
      var appVersion = navigator.appVersion;
      mfp.isLowIE = mfp.isIE8 = document.all && !document.addEventListener;
      mfp.isAndroid = /android/gi.test(appVersion);
      mfp.isIOS = /iphone|ipad|ipod/gi.test(appVersion);
      mfp.supportsTransition = supportsTransitions(); // We disable fixed positioned lightbox on devices that don't handle it nicely.
      // If you know a better way of detecting this - let me know.

      mfp.probablyMobile = mfp.isAndroid || mfp.isIOS || /(Opera Mini)|Kindle|webOS|BlackBerry|(Opera Mobi)|(Windows Phone)|IEMobile/i.test(navigator.userAgent);
      _document = $(document);
      mfp.popupsCache = {};
    },

    /**
     * Opens popup
     * @param  data [description]
     */
    open: function open(data) {
      var i;

      if (data.isObj === false) {
        // convert jQuery collection to array to avoid conflicts later
        mfp.items = data.items.toArray();
        mfp.index = 0;
        var items = data.items,
            item;

        for (i = 0; i < items.length; i++) {
          item = items[i];

          if (item.parsed) {
            item = item.el[0];
          }

          if (item === data.el[0]) {
            mfp.index = i;
            break;
          }
        }
      } else {
        mfp.items = $.isArray(data.items) ? data.items : [data.items];
        mfp.index = data.index || 0;
      } // if popup is already opened - we just update the content


      if (mfp.isOpen) {
        mfp.updateItemHTML();
        return;
      }

      mfp.types = [];
      _wrapClasses = '';

      if (data.mainEl && data.mainEl.length) {
        mfp.ev = data.mainEl.eq(0);
      } else {
        mfp.ev = _document;
      }

      if (data.key) {
        if (!mfp.popupsCache[data.key]) {
          mfp.popupsCache[data.key] = {};
        }

        mfp.currTemplate = mfp.popupsCache[data.key];
      } else {
        mfp.currTemplate = {};
      }

      mfp.st = $.extend(true, {}, $.magnificPopup.defaults, data);
      mfp.fixedContentPos = mfp.st.fixedContentPos === 'auto' ? !mfp.probablyMobile : mfp.st.fixedContentPos;

      if (mfp.st.modal) {
        mfp.st.closeOnContentClick = false;
        mfp.st.closeOnBgClick = false;
        mfp.st.showCloseBtn = false;
        mfp.st.enableEscapeKey = false;
      } // Building markup
      // main containers are created only once


      if (!mfp.bgOverlay) {
        // Dark overlay
        mfp.bgOverlay = _getEl('bg').on('click' + EVENT_NS, function () {
          mfp.close();
        });
        mfp.wrap = _getEl('wrap').attr('tabindex', -1).on('click' + EVENT_NS, function (e) {
          if (mfp._checkIfClose(e.target)) {
            mfp.close();
          }
        });
        mfp.container = _getEl('container', mfp.wrap);
      }

      mfp.contentContainer = _getEl('content');

      if (mfp.st.preloader) {
        mfp.preloader = _getEl('preloader', mfp.container, mfp.st.tLoading);
      } // Initializing modules


      var modules = $.magnificPopup.modules;

      for (i = 0; i < modules.length; i++) {
        var n = modules[i];
        n = n.charAt(0).toUpperCase() + n.slice(1);
        mfp['init' + n].call(mfp);
      }

      _mfpTrigger('BeforeOpen');

      if (mfp.st.showCloseBtn) {
        // Close button
        if (!mfp.st.closeBtnInside) {
          mfp.wrap.append(_getCloseBtn());
        } else {
          _mfpOn(MARKUP_PARSE_EVENT, function (e, template, values, item) {
            values.close_replaceWith = _getCloseBtn(item.type);
          });

          _wrapClasses += ' mfp-close-btn-in';
        }
      }

      if (mfp.st.alignTop) {
        _wrapClasses += ' mfp-align-top';
      }

      if (mfp.fixedContentPos) {
        mfp.wrap.css({
          overflow: mfp.st.overflowY,
          overflowX: 'hidden',
          overflowY: mfp.st.overflowY
        });
      } else {
        mfp.wrap.css({
          top: _window.scrollTop(),
          position: 'absolute'
        });
      }

      if (mfp.st.fixedBgPos === false || mfp.st.fixedBgPos === 'auto' && !mfp.fixedContentPos) {
        mfp.bgOverlay.css({
          height: _document.height(),
          position: 'absolute'
        });
      }

      if (mfp.st.enableEscapeKey) {
        // Close on ESC key
        _document.on('keyup' + EVENT_NS, function (e) {
          if (e.keyCode === 27) {
            mfp.close();
          }
        });
      }

      _window.on('resize' + EVENT_NS, function () {
        mfp.updateSize();
      });

      if (!mfp.st.closeOnContentClick) {
        _wrapClasses += ' mfp-auto-cursor';
      }

      if (_wrapClasses) mfp.wrap.addClass(_wrapClasses); // this triggers recalculation of layout, so we get it once to not to trigger twice

      var windowHeight = mfp.wH = _window.height();

      var windowStyles = {};

      if (mfp.fixedContentPos) {
        if (mfp._hasScrollBar(windowHeight)) {
          var s = mfp._getScrollbarSize();

          if (s) {
            windowStyles.marginRight = s;
          }
        }
      }

      if (mfp.fixedContentPos) {
        if (!mfp.isIE7) {
          windowStyles.overflow = 'hidden';
        } else {
          // ie7 double-scroll bug
          $('body, html').css('overflow', 'hidden');
        }
      }

      var classesToadd = mfp.st.mainClass;

      if (mfp.isIE7) {
        classesToadd += ' mfp-ie7';
      }

      if (classesToadd) {
        mfp._addClassToMFP(classesToadd);
      } // add content


      mfp.updateItemHTML();

      _mfpTrigger('BuildControls'); // remove scrollbar, add margin e.t.c


      $('html').css(windowStyles); // add everything to DOM

      mfp.bgOverlay.add(mfp.wrap).prependTo(mfp.st.prependTo || $(document.body)); // Save last focused element

      mfp._lastFocusedEl = document.activeElement; // Wait for next cycle to allow CSS transition

      setTimeout(function () {
        if (mfp.content) {
          mfp._addClassToMFP(READY_CLASS);

          mfp._setFocus();
        } else {
          // if content is not defined (not loaded e.t.c) we add class only for BG
          mfp.bgOverlay.addClass(READY_CLASS);
        } // Trap the focus in popup


        _document.on('focusin' + EVENT_NS, mfp._onFocusIn);
      }, 16);
      mfp.isOpen = true;
      mfp.updateSize(windowHeight);

      _mfpTrigger(OPEN_EVENT);

      return data;
    },

    /**
     * Closes the popup
     */
    close: function close() {
      if (!mfp.isOpen) return;

      _mfpTrigger(BEFORE_CLOSE_EVENT);

      mfp.isOpen = false; // for CSS3 animation

      if (mfp.st.removalDelay && !mfp.isLowIE && mfp.supportsTransition) {
        mfp._addClassToMFP(REMOVING_CLASS);

        setTimeout(function () {
          mfp._close();
        }, mfp.st.removalDelay);
      } else {
        mfp._close();
      }
    },

    /**
     * Helper for close() function
     */
    _close: function _close() {
      _mfpTrigger(CLOSE_EVENT);

      var classesToRemove = REMOVING_CLASS + ' ' + READY_CLASS + ' ';
      mfp.bgOverlay.detach();
      mfp.wrap.detach();
      mfp.container.empty();

      if (mfp.st.mainClass) {
        classesToRemove += mfp.st.mainClass + ' ';
      }

      mfp._removeClassFromMFP(classesToRemove);

      if (mfp.fixedContentPos) {
        var windowStyles = {
          marginRight: ''
        };

        if (mfp.isIE7) {
          $('body, html').css('overflow', '');
        } else {
          windowStyles.overflow = '';
        }

        $('html').css(windowStyles);
      }

      _document.off('keyup' + EVENT_NS + ' focusin' + EVENT_NS);

      mfp.ev.off(EVENT_NS); // clean up DOM elements that aren't removed

      mfp.wrap.attr('class', 'mfp-wrap').removeAttr('style');
      mfp.bgOverlay.attr('class', 'mfp-bg');
      mfp.container.attr('class', 'mfp-container'); // remove close button from target element

      if (mfp.st.showCloseBtn && (!mfp.st.closeBtnInside || mfp.currTemplate[mfp.currItem.type] === true)) {
        if (mfp.currTemplate.closeBtn) mfp.currTemplate.closeBtn.detach();
      }

      if (mfp.st.autoFocusLast && mfp._lastFocusedEl) {
        $(mfp._lastFocusedEl).focus(); // put tab focus back
      }

      mfp.currItem = null;
      mfp.content = null;
      mfp.currTemplate = null;
      mfp.prevHeight = 0;

      _mfpTrigger(AFTER_CLOSE_EVENT);
    },
    updateSize: function updateSize(winHeight) {
      if (mfp.isIOS) {
        // fixes iOS nav bars https://github.com/dimsemenov/Magnific-Popup/issues/2
        var zoomLevel = document.documentElement.clientWidth / window.innerWidth;
        var height = window.innerHeight * zoomLevel;
        mfp.wrap.css('height', height);
        mfp.wH = height;
      } else {
        mfp.wH = winHeight || _window.height();
      } // Fixes #84: popup incorrectly positioned with position:relative on body


      if (!mfp.fixedContentPos) {
        mfp.wrap.css('height', mfp.wH);
      }

      _mfpTrigger('Resize');
    },

    /**
     * Set content of popup based on current index
     */
    updateItemHTML: function updateItemHTML() {
      var item = mfp.items[mfp.index]; // Detach and perform modifications

      mfp.contentContainer.detach();
      if (mfp.content) mfp.content.detach();

      if (!item.parsed) {
        item = mfp.parseEl(mfp.index);
      }

      var type = item.type;

      _mfpTrigger('BeforeChange', [mfp.currItem ? mfp.currItem.type : '', type]); // BeforeChange event works like so:
      // _mfpOn('BeforeChange', function(e, prevType, newType) { });


      mfp.currItem = item;

      if (!mfp.currTemplate[type]) {
        var markup = mfp.st[type] ? mfp.st[type].markup : false; // allows to modify markup

        _mfpTrigger('FirstMarkupParse', markup);

        if (markup) {
          mfp.currTemplate[type] = $(markup);
        } else {
          // if there is no markup found we just define that template is parsed
          mfp.currTemplate[type] = true;
        }
      }

      if (_prevContentType && _prevContentType !== item.type) {
        mfp.container.removeClass('mfp-' + _prevContentType + '-holder');
      }

      var newContent = mfp['get' + type.charAt(0).toUpperCase() + type.slice(1)](item, mfp.currTemplate[type]);
      mfp.appendContent(newContent, type);
      item.preloaded = true;

      _mfpTrigger(CHANGE_EVENT, item);

      _prevContentType = item.type; // Append container back after its content changed

      mfp.container.prepend(mfp.contentContainer);

      _mfpTrigger('AfterChange');
    },

    /**
     * Set HTML content of popup
     */
    appendContent: function appendContent(newContent, type) {
      mfp.content = newContent;

      if (newContent) {
        if (mfp.st.showCloseBtn && mfp.st.closeBtnInside && mfp.currTemplate[type] === true) {
          // if there is no markup, we just append close button element inside
          if (!mfp.content.find('.mfp-close').length) {
            mfp.content.append(_getCloseBtn());
          }
        } else {
          mfp.content = newContent;
        }
      } else {
        mfp.content = '';
      }

      _mfpTrigger(BEFORE_APPEND_EVENT);

      mfp.container.addClass('mfp-' + type + '-holder');
      mfp.contentContainer.append(mfp.content);
    },

    /**
     * Creates Magnific Popup data object based on given data
     * @param  {int} index Index of item to parse
     */
    parseEl: function parseEl(index) {
      var item = mfp.items[index],
          type;

      if (item.tagName) {
        item = {
          el: $(item)
        };
      } else {
        type = item.type;
        item = {
          data: item,
          src: item.src
        };
      }

      if (item.el) {
        var types = mfp.types; // check for 'mfp-TYPE' class

        for (var i = 0; i < types.length; i++) {
          if (item.el.hasClass('mfp-' + types[i])) {
            type = types[i];
            break;
          }
        }

        item.src = item.el.attr('data-mfp-src');

        if (!item.src) {
          item.src = item.el.attr('href');
        }
      }

      item.type = type || mfp.st.type || 'inline';
      item.index = index;
      item.parsed = true;
      mfp.items[index] = item;

      _mfpTrigger('ElementParse', item);

      return mfp.items[index];
    },

    /**
     * Initializes single popup or a group of popups
     */
    addGroup: function addGroup(el, options) {
      var eHandler = function eHandler(e) {
        e.mfpEl = this;

        mfp._openClick(e, el, options);
      };

      if (!options) {
        options = {};
      }

      var eName = 'click.magnificPopup';
      options.mainEl = el;

      if (options.items) {
        options.isObj = true;
        el.off(eName).on(eName, eHandler);
      } else {
        options.isObj = false;

        if (options.delegate) {
          el.off(eName).on(eName, options.delegate, eHandler);
        } else {
          options.items = el;
          el.off(eName).on(eName, eHandler);
        }
      }
    },
    _openClick: function _openClick(e, el, options) {
      var midClick = options.midClick !== undefined ? options.midClick : $.magnificPopup.defaults.midClick;

      if (!midClick && (e.which === 2 || e.ctrlKey || e.metaKey || e.altKey || e.shiftKey)) {
        return;
      }

      var disableOn = options.disableOn !== undefined ? options.disableOn : $.magnificPopup.defaults.disableOn;

      if (disableOn) {
        if ($.isFunction(disableOn)) {
          if (!disableOn.call(mfp)) {
            return true;
          }
        } else {
          // else it's number
          if (_window.width() < disableOn) {
            return true;
          }
        }
      }

      if (e.type) {
        e.preventDefault(); // This will prevent popup from closing if element is inside and popup is already opened

        if (mfp.isOpen) {
          e.stopPropagation();
        }
      }

      options.el = $(e.mfpEl);

      if (options.delegate) {
        options.items = el.find(options.delegate);
      }

      mfp.open(options);
    },

    /**
     * Updates text on preloader
     */
    updateStatus: function updateStatus(status, text) {
      if (mfp.preloader) {
        if (_prevStatus !== status) {
          mfp.container.removeClass('mfp-s-' + _prevStatus);
        }

        if (!text && status === 'loading') {
          text = mfp.st.tLoading;
        }

        var data = {
          status: status,
          text: text
        }; // allows to modify status

        _mfpTrigger('UpdateStatus', data);

        status = data.status;
        text = data.text;
        mfp.preloader.html(text);
        mfp.preloader.find('a').on('click', function (e) {
          e.stopImmediatePropagation();
        });
        mfp.container.addClass('mfp-s-' + status);
        _prevStatus = status;
      }
    },

    /*
      "Private" helpers that aren't private at all
     */
    // Check to close popup or not
    // "target" is an element that was clicked
    _checkIfClose: function _checkIfClose(target) {
      if ($(target).hasClass(PREVENT_CLOSE_CLASS)) {
        return;
      }

      var closeOnContent = mfp.st.closeOnContentClick;
      var closeOnBg = mfp.st.closeOnBgClick;

      if (closeOnContent && closeOnBg) {
        return true;
      } else {
        // We close the popup if click is on close button or on preloader. Or if there is no content.
        if (!mfp.content || $(target).hasClass('mfp-close') || mfp.preloader && target === mfp.preloader[0]) {
          return true;
        } // if click is outside the content


        if (target !== mfp.content[0] && !$.contains(mfp.content[0], target)) {
          if (closeOnBg) {
            // last check, if the clicked element is in DOM, (in case it's removed onclick)
            if ($.contains(document, target)) {
              return true;
            }
          }
        } else if (closeOnContent) {
          return true;
        }
      }

      return false;
    },
    _addClassToMFP: function _addClassToMFP(cName) {
      mfp.bgOverlay.addClass(cName);
      mfp.wrap.addClass(cName);
    },
    _removeClassFromMFP: function _removeClassFromMFP(cName) {
      this.bgOverlay.removeClass(cName);
      mfp.wrap.removeClass(cName);
    },
    _hasScrollBar: function _hasScrollBar(winHeight) {
      return (mfp.isIE7 ? _document.height() : document.body.scrollHeight) > (winHeight || _window.height());
    },
    _setFocus: function _setFocus() {
      (mfp.st.focus ? mfp.content.find(mfp.st.focus).eq(0) : mfp.wrap).focus();
    },
    _onFocusIn: function _onFocusIn(e) {
      if (e.target !== mfp.wrap[0] && !$.contains(mfp.wrap[0], e.target)) {
        mfp._setFocus();

        return false;
      }
    },
    _parseMarkup: function _parseMarkup(template, values, item) {
      var arr;

      if (item.data) {
        values = $.extend(item.data, values);
      }

      _mfpTrigger(MARKUP_PARSE_EVENT, [template, values, item]);

      $.each(values, function (key, value) {
        if (value === undefined || value === false) {
          return true;
        }

        arr = key.split('_');

        if (arr.length > 1) {
          var el = template.find(EVENT_NS + '-' + arr[0]);

          if (el.length > 0) {
            var attr = arr[1];

            if (attr === 'replaceWith') {
              if (el[0] !== value[0]) {
                el.replaceWith(value);
              }
            } else if (attr === 'img') {
              if (el.is('img')) {
                el.attr('src', value);
              } else {
                el.replaceWith($('<img>').attr('src', value).attr('class', el.attr('class')));
              }
            } else {
              el.attr(arr[1], value);
            }
          }
        } else {
          template.find(EVENT_NS + '-' + key).html(value);
        }
      });
    },
    _getScrollbarSize: function _getScrollbarSize() {
      // thx David
      if (mfp.scrollbarSize === undefined) {
        var scrollDiv = document.createElement("div");
        scrollDiv.style.cssText = 'width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;';
        document.body.appendChild(scrollDiv);
        mfp.scrollbarSize = scrollDiv.offsetWidth - scrollDiv.clientWidth;
        document.body.removeChild(scrollDiv);
      }

      return mfp.scrollbarSize;
    }
  };
  /* MagnificPopup core prototype end */

  /**
   * Public static functions
   */

  $.magnificPopup = {
    instance: null,
    proto: MagnificPopup.prototype,
    modules: [],
    open: function open(options, index) {
      _checkInstance();

      if (!options) {
        options = {};
      } else {
        options = $.extend(true, {}, options);
      }

      options.isObj = true;
      options.index = index || 0;
      return this.instance.open(options);
    },
    close: function close() {
      return $.magnificPopup.instance && $.magnificPopup.instance.close();
    },
    registerModule: function registerModule(name, module) {
      if (module.options) {
        $.magnificPopup.defaults[name] = module.options;
      }

      $.extend(this.proto, module.proto);
      this.modules.push(name);
    },
    defaults: {
      // Info about options is in docs:
      // http://dimsemenov.com/plugins/magnific-popup/documentation.html#options
      disableOn: 0,
      key: null,
      midClick: false,
      mainClass: '',
      preloader: true,
      focus: '',
      // CSS selector of input to focus after popup is opened
      closeOnContentClick: false,
      closeOnBgClick: true,
      closeBtnInside: true,
      showCloseBtn: true,
      enableEscapeKey: true,
      modal: false,
      alignTop: false,
      removalDelay: 0,
      prependTo: null,
      fixedContentPos: 'auto',
      fixedBgPos: 'auto',
      overflowY: 'auto',
      closeMarkup: '<button title="%title%" type="button" class="mfp-close">&#215;</button>',
      tClose: 'Close (Esc)',
      tLoading: 'Loading...',
      autoFocusLast: true
    }
  };

  $.fn.magnificPopup = function (options) {
    _checkInstance();

    var jqEl = $(this); // We call some API method of first param is a string

    if (typeof options === "string") {
      if (options === 'open') {
        var items,
            itemOpts = _isJQ ? jqEl.data('magnificPopup') : jqEl[0].magnificPopup,
            index = parseInt(arguments[1], 10) || 0;

        if (itemOpts.items) {
          items = itemOpts.items[index];
        } else {
          items = jqEl;

          if (itemOpts.delegate) {
            items = items.find(itemOpts.delegate);
          }

          items = items.eq(index);
        }

        mfp._openClick({
          mfpEl: items
        }, jqEl, itemOpts);
      } else {
        if (mfp.isOpen) mfp[options].apply(mfp, Array.prototype.slice.call(arguments, 1));
      }
    } else {
      // clone options obj
      options = $.extend(true, {}, options);
      /*
       * As Zepto doesn't support .data() method for objects
       * and it works only in normal browsers
       * we assign "options" object directly to the DOM element. FTW!
       */

      if (_isJQ) {
        jqEl.data('magnificPopup', options);
      } else {
        jqEl[0].magnificPopup = options;
      }

      mfp.addGroup(jqEl, options);
    }

    return jqEl;
  };
  /*>>core*/

  /*>>inline*/


  var INLINE_NS = 'inline',
      _hiddenClass,
      _inlinePlaceholder,
      _lastInlineElement,
      _putInlineElementsBack = function _putInlineElementsBack() {
    if (_lastInlineElement) {
      _inlinePlaceholder.after(_lastInlineElement.addClass(_hiddenClass)).detach();

      _lastInlineElement = null;
    }
  };

  $.magnificPopup.registerModule(INLINE_NS, {
    options: {
      hiddenClass: 'hide',
      // will be appended with `mfp-` prefix
      markup: '',
      tNotFound: 'Content not found'
    },
    proto: {
      initInline: function initInline() {
        mfp.types.push(INLINE_NS);

        _mfpOn(CLOSE_EVENT + '.' + INLINE_NS, function () {
          _putInlineElementsBack();
        });
      },
      getInline: function getInline(item, template) {
        _putInlineElementsBack();

        if (item.src) {
          var inlineSt = mfp.st.inline,
              el = $(item.src);

          if (el.length) {
            // If target element has parent - we replace it with placeholder and put it back after popup is closed
            var parent = el[0].parentNode;

            if (parent && parent.tagName) {
              if (!_inlinePlaceholder) {
                _hiddenClass = inlineSt.hiddenClass;
                _inlinePlaceholder = _getEl(_hiddenClass);
                _hiddenClass = 'mfp-' + _hiddenClass;
              } // replace target inline element with placeholder


              _lastInlineElement = el.after(_inlinePlaceholder).detach().removeClass(_hiddenClass);
            }

            mfp.updateStatus('ready');
          } else {
            mfp.updateStatus('error', inlineSt.tNotFound);
            el = $('<div>');
          }

          item.inlineElement = el;
          return el;
        }

        mfp.updateStatus('ready');

        mfp._parseMarkup(template, {}, item);

        return template;
      }
    }
  });
  /*>>inline*/

  /*>>ajax*/

  var AJAX_NS = 'ajax',
      _ajaxCur,
      _removeAjaxCursor = function _removeAjaxCursor() {
    if (_ajaxCur) {
      $(document.body).removeClass(_ajaxCur);
    }
  },
      _destroyAjaxRequest = function _destroyAjaxRequest() {
    _removeAjaxCursor();

    if (mfp.req) {
      mfp.req.abort();
    }
  };

  $.magnificPopup.registerModule(AJAX_NS, {
    options: {
      settings: null,
      cursor: 'mfp-ajax-cur',
      tError: '<a href="%url%">The content</a> could not be loaded.'
    },
    proto: {
      initAjax: function initAjax() {
        mfp.types.push(AJAX_NS);
        _ajaxCur = mfp.st.ajax.cursor;

        _mfpOn(CLOSE_EVENT + '.' + AJAX_NS, _destroyAjaxRequest);

        _mfpOn('BeforeChange.' + AJAX_NS, _destroyAjaxRequest);
      },
      getAjax: function getAjax(item) {
        if (_ajaxCur) {
          $(document.body).addClass(_ajaxCur);
        }

        mfp.updateStatus('loading');
        var opts = $.extend({
          url: item.src,
          success: function success(data, textStatus, jqXHR) {
            var temp = {
              data: data,
              xhr: jqXHR
            };

            _mfpTrigger('ParseAjax', temp);

            mfp.appendContent($(temp.data), AJAX_NS);
            item.finished = true;

            _removeAjaxCursor();

            mfp._setFocus();

            setTimeout(function () {
              mfp.wrap.addClass(READY_CLASS);
            }, 16);
            mfp.updateStatus('ready');

            _mfpTrigger('AjaxContentAdded');
          },
          error: function error() {
            _removeAjaxCursor();

            item.finished = item.loadError = true;
            mfp.updateStatus('error', mfp.st.ajax.tError.replace('%url%', item.src));
          }
        }, mfp.st.ajax.settings);
        mfp.req = $.ajax(opts);
        return '';
      }
    }
  });
  /*>>ajax*/

  /*>>image*/

  var _imgInterval,
      _getTitle = function _getTitle(item) {
    if (item.data && item.data.title !== undefined) return item.data.title;
    var src = mfp.st.image.titleSrc;

    if (src) {
      if ($.isFunction(src)) {
        return src.call(mfp, item);
      } else if (item.el) {
        return item.el.attr(src) || '';
      }
    }

    return '';
  };

  $.magnificPopup.registerModule('image', {
    options: {
      markup: '<div class="mfp-figure">' + '<div class="mfp-close"></div>' + '<figure>' + '<div class="mfp-img"></div>' + '<figcaption>' + '<div class="mfp-bottom-bar">' + '<div class="mfp-title"></div>' + '<div class="mfp-counter"></div>' + '</div>' + '</figcaption>' + '</figure>' + '</div>',
      cursor: 'mfp-zoom-out-cur',
      titleSrc: 'title',
      verticalFit: true,
      tError: '<a href="%url%">The image</a> could not be loaded.'
    },
    proto: {
      initImage: function initImage() {
        var imgSt = mfp.st.image,
            ns = '.image';
        mfp.types.push('image');

        _mfpOn(OPEN_EVENT + ns, function () {
          if (mfp.currItem.type === 'image' && imgSt.cursor) {
            $(document.body).addClass(imgSt.cursor);
          }
        });

        _mfpOn(CLOSE_EVENT + ns, function () {
          if (imgSt.cursor) {
            $(document.body).removeClass(imgSt.cursor);
          }

          _window.off('resize' + EVENT_NS);
        });

        _mfpOn('Resize' + ns, mfp.resizeImage);

        if (mfp.isLowIE) {
          _mfpOn('AfterChange', mfp.resizeImage);
        }
      },
      resizeImage: function resizeImage() {
        var item = mfp.currItem;
        if (!item || !item.img) return;

        if (mfp.st.image.verticalFit) {
          var decr = 0; // fix box-sizing in ie7/8

          if (mfp.isLowIE) {
            decr = parseInt(item.img.css('padding-top'), 10) + parseInt(item.img.css('padding-bottom'), 10);
          }

          item.img.css('max-height', mfp.wH - decr);
        }
      },
      _onImageHasSize: function _onImageHasSize(item) {
        if (item.img) {
          item.hasSize = true;

          if (_imgInterval) {
            clearInterval(_imgInterval);
          }

          item.isCheckingImgSize = false;

          _mfpTrigger('ImageHasSize', item);

          if (item.imgHidden) {
            if (mfp.content) mfp.content.removeClass('mfp-loading');
            item.imgHidden = false;
          }
        }
      },

      /**
       * Function that loops until the image has size to display elements that rely on it asap
       */
      findImageSize: function findImageSize(item) {
        var counter = 0,
            img = item.img[0],
            mfpSetInterval = function mfpSetInterval(delay) {
          if (_imgInterval) {
            clearInterval(_imgInterval);
          } // decelerating interval that checks for size of an image


          _imgInterval = setInterval(function () {
            if (img.naturalWidth > 0) {
              mfp._onImageHasSize(item);

              return;
            }

            if (counter > 200) {
              clearInterval(_imgInterval);
            }

            counter++;

            if (counter === 3) {
              mfpSetInterval(10);
            } else if (counter === 40) {
              mfpSetInterval(50);
            } else if (counter === 100) {
              mfpSetInterval(500);
            }
          }, delay);
        };

        mfpSetInterval(1);
      },
      getImage: function getImage(item, template) {
        var guard = 0,
            // image load complete handler
        onLoadComplete = function onLoadComplete() {
          if (item) {
            if (item.img[0].complete) {
              item.img.off('.mfploader');

              if (item === mfp.currItem) {
                mfp._onImageHasSize(item);

                mfp.updateStatus('ready');
              }

              item.hasSize = true;
              item.loaded = true;

              _mfpTrigger('ImageLoadComplete');
            } else {
              // if image complete check fails 200 times (20 sec), we assume that there was an error.
              guard++;

              if (guard < 200) {
                setTimeout(onLoadComplete, 100);
              } else {
                onLoadError();
              }
            }
          }
        },
            // image error handler
        onLoadError = function onLoadError() {
          if (item) {
            item.img.off('.mfploader');

            if (item === mfp.currItem) {
              mfp._onImageHasSize(item);

              mfp.updateStatus('error', imgSt.tError.replace('%url%', item.src));
            }

            item.hasSize = true;
            item.loaded = true;
            item.loadError = true;
          }
        },
            imgSt = mfp.st.image;

        var el = template.find('.mfp-img');

        if (el.length) {
          var img = document.createElement('img');
          img.className = 'mfp-img';

          if (item.el && item.el.find('img').length) {
            img.alt = item.el.find('img').attr('alt');
          }

          item.img = $(img).on('load.mfploader', onLoadComplete).on('error.mfploader', onLoadError);
          img.src = item.src; // without clone() "error" event is not firing when IMG is replaced by new IMG
          // TODO: find a way to avoid such cloning

          if (el.is('img')) {
            item.img = item.img.clone();
          }

          img = item.img[0];

          if (img.naturalWidth > 0) {
            item.hasSize = true;
          } else if (!img.width) {
            item.hasSize = false;
          }
        }

        mfp._parseMarkup(template, {
          title: _getTitle(item),
          img_replaceWith: item.img
        }, item);

        mfp.resizeImage();

        if (item.hasSize) {
          if (_imgInterval) clearInterval(_imgInterval);

          if (item.loadError) {
            template.addClass('mfp-loading');
            mfp.updateStatus('error', imgSt.tError.replace('%url%', item.src));
          } else {
            template.removeClass('mfp-loading');
            mfp.updateStatus('ready');
          }

          return template;
        }

        mfp.updateStatus('loading');
        item.loading = true;

        if (!item.hasSize) {
          item.imgHidden = true;
          template.addClass('mfp-loading');
          mfp.findImageSize(item);
        }

        return template;
      }
    }
  });
  /*>>image*/

  /*>>zoom*/

  var hasMozTransform,
      getHasMozTransform = function getHasMozTransform() {
    if (hasMozTransform === undefined) {
      hasMozTransform = document.createElement('p').style.MozTransform !== undefined;
    }

    return hasMozTransform;
  };

  $.magnificPopup.registerModule('zoom', {
    options: {
      enabled: false,
      easing: 'ease-in-out',
      duration: 300,
      opener: function opener(element) {
        return element.is('img') ? element : element.find('img');
      }
    },
    proto: {
      initZoom: function initZoom() {
        var zoomSt = mfp.st.zoom,
            ns = '.zoom',
            image;

        if (!zoomSt.enabled || !mfp.supportsTransition) {
          return;
        }

        var duration = zoomSt.duration,
            getElToAnimate = function getElToAnimate(image) {
          var newImg = image.clone().removeAttr('style').removeAttr('class').addClass('mfp-animated-image'),
              transition = 'all ' + zoomSt.duration / 1000 + 's ' + zoomSt.easing,
              cssObj = {
            position: 'fixed',
            zIndex: 9999,
            left: 0,
            top: 0,
            '-webkit-backface-visibility': 'hidden'
          },
              t = 'transition';
          cssObj['-webkit-' + t] = cssObj['-moz-' + t] = cssObj['-o-' + t] = cssObj[t] = transition;
          newImg.css(cssObj);
          return newImg;
        },
            showMainContent = function showMainContent() {
          mfp.content.css('visibility', 'visible');
        },
            openTimeout,
            animatedImg;

        _mfpOn('BuildControls' + ns, function () {
          if (mfp._allowZoom()) {
            clearTimeout(openTimeout);
            mfp.content.css('visibility', 'hidden'); // Basically, all code below does is clones existing image, puts in on top of the current one and animated it

            image = mfp._getItemToZoom();

            if (!image) {
              showMainContent();
              return;
            }

            animatedImg = getElToAnimate(image);
            animatedImg.css(mfp._getOffset());
            mfp.wrap.append(animatedImg);
            openTimeout = setTimeout(function () {
              animatedImg.css(mfp._getOffset(true));
              openTimeout = setTimeout(function () {
                showMainContent();
                setTimeout(function () {
                  animatedImg.remove();
                  image = animatedImg = null;

                  _mfpTrigger('ZoomAnimationEnded');
                }, 16); // avoid blink when switching images
              }, duration); // this timeout equals animation duration
            }, 16); // by adding this timeout we avoid short glitch at the beginning of animation
            // Lots of timeouts...
          }
        });

        _mfpOn(BEFORE_CLOSE_EVENT + ns, function () {
          if (mfp._allowZoom()) {
            clearTimeout(openTimeout);
            mfp.st.removalDelay = duration;

            if (!image) {
              image = mfp._getItemToZoom();

              if (!image) {
                return;
              }

              animatedImg = getElToAnimate(image);
            }

            animatedImg.css(mfp._getOffset(true));
            mfp.wrap.append(animatedImg);
            mfp.content.css('visibility', 'hidden');
            setTimeout(function () {
              animatedImg.css(mfp._getOffset());
            }, 16);
          }
        });

        _mfpOn(CLOSE_EVENT + ns, function () {
          if (mfp._allowZoom()) {
            showMainContent();

            if (animatedImg) {
              animatedImg.remove();
            }

            image = null;
          }
        });
      },
      _allowZoom: function _allowZoom() {
        return mfp.currItem.type === 'image';
      },
      _getItemToZoom: function _getItemToZoom() {
        if (mfp.currItem.hasSize) {
          return mfp.currItem.img;
        } else {
          return false;
        }
      },
      // Get element postion relative to viewport
      _getOffset: function _getOffset(isLarge) {
        var el;

        if (isLarge) {
          el = mfp.currItem.img;
        } else {
          el = mfp.st.zoom.opener(mfp.currItem.el || mfp.currItem);
        }

        var offset = el.offset();
        var paddingTop = parseInt(el.css('padding-top'), 10);
        var paddingBottom = parseInt(el.css('padding-bottom'), 10);
        offset.top -= $(window).scrollTop() - paddingTop;
        /*
         Animating left + top + width/height looks glitchy in Firefox, but perfect in Chrome. And vice-versa.
          */

        var obj = {
          width: el.width(),
          // fix Zepto height+padding issue
          height: (_isJQ ? el.innerHeight() : el[0].offsetHeight) - paddingBottom - paddingTop
        }; // I hate to do this, but there is no another option

        if (getHasMozTransform()) {
          obj['-moz-transform'] = obj['transform'] = 'translate(' + offset.left + 'px,' + offset.top + 'px)';
        } else {
          obj.left = offset.left;
          obj.top = offset.top;
        }

        return obj;
      }
    }
  });
  /*>>zoom*/

  /*>>iframe*/

  var IFRAME_NS = 'iframe',
      _emptyPage = '//about:blank',
      _fixIframeBugs = function _fixIframeBugs(isShowing) {
    if (mfp.currTemplate[IFRAME_NS]) {
      var el = mfp.currTemplate[IFRAME_NS].find('iframe');

      if (el.length) {
        // reset src after the popup is closed to avoid "video keeps playing after popup is closed" bug
        if (!isShowing) {
          el[0].src = _emptyPage;
        } // IE8 black screen bug fix


        if (mfp.isIE8) {
          el.css('display', isShowing ? 'block' : 'none');
        }
      }
    }
  };

  $.magnificPopup.registerModule(IFRAME_NS, {
    options: {
      markup: '<div class="mfp-iframe-scaler">' + '<div class="mfp-close"></div>' + '<iframe class="mfp-iframe" src="//about:blank" frameborder="0" allowfullscreen></iframe>' + '</div>',
      srcAction: 'iframe_src',
      // we don't care and support only one default type of URL by default
      patterns: {
        youtube: {
          index: 'youtube.com',
          id: 'v=',
          src: '//www.youtube.com/embed/%id%?autoplay=1'
        },
        vimeo: {
          index: 'vimeo.com/',
          id: '/',
          src: '//player.vimeo.com/video/%id%?autoplay=1'
        },
        gmaps: {
          index: '//maps.google.',
          src: '%id%&output=embed'
        }
      }
    },
    proto: {
      initIframe: function initIframe() {
        mfp.types.push(IFRAME_NS);

        _mfpOn('BeforeChange', function (e, prevType, newType) {
          if (prevType !== newType) {
            if (prevType === IFRAME_NS) {
              _fixIframeBugs(); // iframe if removed

            } else if (newType === IFRAME_NS) {
              _fixIframeBugs(true); // iframe is showing

            }
          } // else {
          // iframe source is switched, don't do anything
          //}

        });

        _mfpOn(CLOSE_EVENT + '.' + IFRAME_NS, function () {
          _fixIframeBugs();
        });
      },
      getIframe: function getIframe(item, template) {
        var embedSrc = item.src;
        var iframeSt = mfp.st.iframe;
        $.each(iframeSt.patterns, function () {
          if (embedSrc.indexOf(this.index) > -1) {
            if (this.id) {
              if (typeof this.id === 'string') {
                embedSrc = embedSrc.substr(embedSrc.lastIndexOf(this.id) + this.id.length, embedSrc.length);
              } else {
                embedSrc = this.id.call(this, embedSrc);
              }
            }

            embedSrc = this.src.replace('%id%', embedSrc);
            return false; // break;
          }
        });
        var dataObj = {};

        if (iframeSt.srcAction) {
          dataObj[iframeSt.srcAction] = embedSrc;
        }

        mfp._parseMarkup(template, dataObj, item);

        mfp.updateStatus('ready');
        return template;
      }
    }
  });
  /*>>iframe*/

  /*>>gallery*/

  /**
   * Get looped index depending on number of slides
   */

  var _getLoopedId = function _getLoopedId(index) {
    var numSlides = mfp.items.length;

    if (index > numSlides - 1) {
      return index - numSlides;
    } else if (index < 0) {
      return numSlides + index;
    }

    return index;
  },
      _replaceCurrTotal = function _replaceCurrTotal(text, curr, total) {
    return text.replace(/%curr%/gi, curr + 1).replace(/%total%/gi, total);
  };

  $.magnificPopup.registerModule('gallery', {
    options: {
      enabled: false,
      arrowMarkup: '<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>',
      preload: [0, 2],
      navigateByImgClick: true,
      arrows: true,
      tPrev: 'Previous (Left arrow key)',
      tNext: 'Next (Right arrow key)',
      tCounter: '%curr% of %total%'
    },
    proto: {
      initGallery: function initGallery() {
        var gSt = mfp.st.gallery,
            ns = '.mfp-gallery';
        mfp.direction = true; // true - next, false - prev

        if (!gSt || !gSt.enabled) return false;
        _wrapClasses += ' mfp-gallery';

        _mfpOn(OPEN_EVENT + ns, function () {
          if (gSt.navigateByImgClick) {
            mfp.wrap.on('click' + ns, '.mfp-img', function () {
              if (mfp.items.length > 1) {
                mfp.next();
                return false;
              }
            });
          }

          _document.on('keydown' + ns, function (e) {
            if (e.keyCode === 37) {
              mfp.prev();
            } else if (e.keyCode === 39) {
              mfp.next();
            }
          });
        });

        _mfpOn('UpdateStatus' + ns, function (e, data) {
          if (data.text) {
            data.text = _replaceCurrTotal(data.text, mfp.currItem.index, mfp.items.length);
          }
        });

        _mfpOn(MARKUP_PARSE_EVENT + ns, function (e, element, values, item) {
          var l = mfp.items.length;
          values.counter = l > 1 ? _replaceCurrTotal(gSt.tCounter, item.index, l) : '';
        });

        _mfpOn('BuildControls' + ns, function () {
          if (mfp.items.length > 1 && gSt.arrows && !mfp.arrowLeft) {
            var markup = gSt.arrowMarkup,
                arrowLeft = mfp.arrowLeft = $(markup.replace(/%title%/gi, gSt.tPrev).replace(/%dir%/gi, 'left')).addClass(PREVENT_CLOSE_CLASS),
                arrowRight = mfp.arrowRight = $(markup.replace(/%title%/gi, gSt.tNext).replace(/%dir%/gi, 'right')).addClass(PREVENT_CLOSE_CLASS);
            arrowLeft.click(function () {
              mfp.prev();
            });
            arrowRight.click(function () {
              mfp.next();
            });
            mfp.container.append(arrowLeft.add(arrowRight));
          }
        });

        _mfpOn(CHANGE_EVENT + ns, function () {
          if (mfp._preloadTimeout) clearTimeout(mfp._preloadTimeout);
          mfp._preloadTimeout = setTimeout(function () {
            mfp.preloadNearbyImages();
            mfp._preloadTimeout = null;
          }, 16);
        });

        _mfpOn(CLOSE_EVENT + ns, function () {
          _document.off(ns);

          mfp.wrap.off('click' + ns);
          mfp.arrowRight = mfp.arrowLeft = null;
        });
      },
      next: function next() {
        mfp.direction = true;
        mfp.index = _getLoopedId(mfp.index + 1);
        mfp.updateItemHTML();
      },
      prev: function prev() {
        mfp.direction = false;
        mfp.index = _getLoopedId(mfp.index - 1);
        mfp.updateItemHTML();
      },
      goTo: function goTo(newIndex) {
        mfp.direction = newIndex >= mfp.index;
        mfp.index = newIndex;
        mfp.updateItemHTML();
      },
      preloadNearbyImages: function preloadNearbyImages() {
        var p = mfp.st.gallery.preload,
            preloadBefore = Math.min(p[0], mfp.items.length),
            preloadAfter = Math.min(p[1], mfp.items.length),
            i;

        for (i = 1; i <= (mfp.direction ? preloadAfter : preloadBefore); i++) {
          mfp._preloadItem(mfp.index + i);
        }

        for (i = 1; i <= (mfp.direction ? preloadBefore : preloadAfter); i++) {
          mfp._preloadItem(mfp.index - i);
        }
      },
      _preloadItem: function _preloadItem(index) {
        index = _getLoopedId(index);

        if (mfp.items[index].preloaded) {
          return;
        }

        var item = mfp.items[index];

        if (!item.parsed) {
          item = mfp.parseEl(index);
        }

        _mfpTrigger('LazyLoad', item);

        if (item.type === 'image') {
          item.img = $('<img class="mfp-img" />').on('load.mfploader', function () {
            item.hasSize = true;
          }).on('error.mfploader', function () {
            item.hasSize = true;
            item.loadError = true;

            _mfpTrigger('LazyLoadError', item);
          }).attr('src', item.src);
        }

        item.preloaded = true;
      }
    }
  });
  /*>>gallery*/

  /*>>retina*/

  var RETINA_NS = 'retina';
  $.magnificPopup.registerModule(RETINA_NS, {
    options: {
      replaceSrc: function replaceSrc(item) {
        return item.src.replace(/\.\w+$/, function (m) {
          return '@2x' + m;
        });
      },
      ratio: 1 // Function or number.  Set to 1 to disable.

    },
    proto: {
      initRetina: function initRetina() {
        if (window.devicePixelRatio > 1) {
          var st = mfp.st.retina,
              ratio = st.ratio;
          ratio = !isNaN(ratio) ? ratio : ratio();

          if (ratio > 1) {
            _mfpOn('ImageHasSize' + '.' + RETINA_NS, function (e, item) {
              item.img.css({
                'max-width': item.img[0].naturalWidth / ratio,
                'width': '100%'
              });
            });

            _mfpOn('ElementParse' + '.' + RETINA_NS, function (e, item) {
              item.src = st.replaceSrc(item, ratio);
            });
          }
        }
      }
    }
  });
  /*>>retina*/

  _checkInstance();
});

/***/ }),

/***/ "./themes/default/src/js/main.js":
/*!***************************************!*\
  !*** ./themes/default/src/js/main.js ***!
  \***************************************/
/***/ (() => {

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */
Prism.manual = true;
$(function () {
  var scroll_button = $('.to-top');
  $(".post-body").each(function () {
    Prism.highlightAllUnder(this);
  });

  if ($(document).height() > $(window).height() && $(this).scrollTop() < 50) {
    scroll_button.addClass('to-bottom').removeClass('to-top_hidden');
  }

  $(window).scroll(function () {
    if ($(this).scrollTop() > 50) {
      scroll_button.removeClass('to-bottom');
      scroll_button.addClass('to-header');
    } else {
      scroll_button.addClass('to-bottom');
      scroll_button.removeClass('to-header');
    }
  });
  $(".to-top").click(function (event) {
    event.preventDefault();

    if ($(this).hasClass('to-header')) {
      $('body,html').animate({
        scrollTop: 0
      }, 800);
    } else {
      $('body,html').animate({
        scrollTop: $(document).height()
      }, 800);
    }
  });
});
$(document).ready(function () {
  if (typeof wysibb_input != "undefined") {
    $(wysibb_input).wysibb(wysibb_settings);
  }

  $(".flatpickr").flatpickr({
    dateFormat: 'd.m.Y'
  });
  $(".flatpickr_time").flatpickr({
    dateFormat: 'd.m.Y H:i',
    enableTime: true
  });
});

/***/ }),

/***/ "./themes/default/src/js/menu.js":
/*!***************************************!*\
  !*** ./themes/default/src/js/menu.js ***!
  \***************************************/
/***/ (() => {

$(document).on('click', '.navbar-toggler, .show_menu_btn', function () {
  toggle_menu();
}).on('click', '.sidebar_opened .overlay', function () {
  var body = $('body');

  if (body.hasClass('sidebar_opened')) {
    toggle_menu();
  }
}); // Открытие/закрытие меню для мобильной версии

function toggle_menu() {
  var body = $('body');

  if (body.hasClass('sidebar_opened')) {
    body.removeClass('sidebar_opened');
    setTimeout(function () {
      $('.top_nav .navbar-toggle').removeClass('toggled');
    }, 500);
  } else {
    body.addClass('sidebar_opened');
    setTimeout(function () {
      $('.top_nav .navbar-toggle').addClass('toggled');
    }, 500);
  }
}

/***/ }),

/***/ "./themes/default/src/js/modals.js":
/*!*****************************************!*\
  !*** ./themes/default/src/js/modals.js ***!
  \*****************************************/
/***/ (() => {

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */
function getSpinner() {
  return '<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
}

$(function () {
  var ajax_modal = $('.ajax_modal');
  ajax_modal.on('show.bs.modal', function (event) {
    $('.ajax_modal .modal-content').html(getSpinner());
  });
  ajax_modal.on('shown.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var params = button.data();
    $.ajax({
      type: "GET",
      url: params.url,
      dataType: "html",
      data: params,
      success: function success(html) {
        $('.ajax_modal .modal-content').html(html);
      }
    });
  });
});
$(document).on('click', '.select_language', function (event) {
  event.preventDefault();
  var select_language_form = $('form[name="select_language"]');
  $.ajax({
    type: "POST",
    url: select_language_form.attr('action'),
    dataType: "html",
    data: select_language_form.serialize(),
    success: function success(html) {
      $('.ajax_modal').modal('hide');
      document.location.href = document.location.href;
    }
  });
});

/***/ }),

/***/ "./themes/default/src/js/prism.js":
/*!****************************************!*\
  !*** ./themes/default/src/js/prism.js ***!
  \****************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/* PrismJS 1.17.1
https://prismjs.com/download.html#themes=prism&languages=markup+css+clike+javascript+markup-templating+php+javadoclike+phpdoc+php-extras+sql&plugins=line-numbers */
var _self = "undefined" != typeof window ? window : "undefined" != typeof WorkerGlobalScope && self instanceof WorkerGlobalScope ? self : {},
    Prism = function (u) {
  var c = /\blang(?:uage)?-([\w-]+)\b/i,
      r = 0;
  var _ = {
    manual: u.Prism && u.Prism.manual,
    disableWorkerMessageHandler: u.Prism && u.Prism.disableWorkerMessageHandler,
    util: {
      encode: function encode(e) {
        return e instanceof L ? new L(e.type, _.util.encode(e.content), e.alias) : Array.isArray(e) ? e.map(_.util.encode) : e.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/\u00a0/g, " ");
      },
      type: function type(e) {
        return Object.prototype.toString.call(e).slice(8, -1);
      },
      objId: function objId(e) {
        return e.__id || Object.defineProperty(e, "__id", {
          value: ++r
        }), e.__id;
      },
      clone: function n(e, t) {
        var a,
            r,
            i = _.util.type(e);

        switch (t = t || {}, i) {
          case "Object":
            if (r = _.util.objId(e), t[r]) return t[r];

            for (var o in a = {}, t[r] = a, e) {
              e.hasOwnProperty(o) && (a[o] = n(e[o], t));
            }

            return a;

          case "Array":
            return r = _.util.objId(e), t[r] ? t[r] : (a = [], t[r] = a, e.forEach(function (e, r) {
              a[r] = n(e, t);
            }), a);

          default:
            return e;
        }
      },
      currentScript: function currentScript() {
        if ("undefined" == typeof document) return null;
        if ("currentScript" in document) return document.currentScript;

        try {
          throw new Error();
        } catch (e) {
          var r = (/at [^(\r\n]*\((.*):.+:.+\)$/i.exec(e.stack) || [])[1];

          if (r) {
            var n = document.getElementsByTagName("script");

            for (var t in n) {
              if (n[t].src == r) return n[t];
            }
          }

          return null;
        }
      }
    },
    languages: {
      extend: function extend(e, r) {
        var n = _.util.clone(_.languages[e]);

        for (var t in r) {
          n[t] = r[t];
        }

        return n;
      },
      insertBefore: function insertBefore(n, e, r, t) {
        var a = (t = t || _.languages)[n],
            i = {};

        for (var o in a) {
          if (a.hasOwnProperty(o)) {
            if (o == e) for (var l in r) {
              r.hasOwnProperty(l) && (i[l] = r[l]);
            }
            r.hasOwnProperty(o) || (i[o] = a[o]);
          }
        }

        var s = t[n];
        return t[n] = i, _.languages.DFS(_.languages, function (e, r) {
          r === s && e != n && (this[e] = i);
        }), i;
      },
      DFS: function e(r, n, t, a) {
        a = a || {};
        var i = _.util.objId;

        for (var o in r) {
          if (r.hasOwnProperty(o)) {
            n.call(r, o, r[o], t || o);

            var l = r[o],
                s = _.util.type(l);

            "Object" !== s || a[i(l)] ? "Array" !== s || a[i(l)] || (a[i(l)] = !0, e(l, n, o, a)) : (a[i(l)] = !0, e(l, n, null, a));
          }
        }
      }
    },
    plugins: {},
    highlightAll: function highlightAll(e, r) {
      _.highlightAllUnder(document, e, r);
    },
    highlightAllUnder: function highlightAllUnder(e, r, n) {
      var t = {
        callback: n,
        selector: 'code[class*="language-"], [class*="language-"] code, code[class*="lang-"], [class*="lang-"] code'
      };

      _.hooks.run("before-highlightall", t);

      for (var a, i = e.querySelectorAll(t.selector), o = 0; a = i[o++];) {
        _.highlightElement(a, !0 === r, t.callback);
      }
    },
    highlightElement: function highlightElement(e, r, n) {
      var t = function (e) {
        for (; e && !c.test(e.className);) {
          e = e.parentNode;
        }

        return e ? (e.className.match(c) || [, "none"])[1].toLowerCase() : "none";
      }(e),
          a = _.languages[t];

      e.className = e.className.replace(c, "").replace(/\s+/g, " ") + " language-" + t;
      var i = e.parentNode;
      i && "pre" === i.nodeName.toLowerCase() && (i.className = i.className.replace(c, "").replace(/\s+/g, " ") + " language-" + t);
      var o = {
        element: e,
        language: t,
        grammar: a,
        code: e.textContent
      };

      function l(e) {
        o.highlightedCode = e, _.hooks.run("before-insert", o), o.element.innerHTML = o.highlightedCode, _.hooks.run("after-highlight", o), _.hooks.run("complete", o), n && n.call(o.element);
      }

      if (_.hooks.run("before-sanity-check", o), !o.code) return _.hooks.run("complete", o), void (n && n.call(o.element));
      if (_.hooks.run("before-highlight", o), o.grammar) {
        if (r && u.Worker) {
          var s = new Worker(_.filename);
          s.onmessage = function (e) {
            l(e.data);
          }, s.postMessage(JSON.stringify({
            language: o.language,
            code: o.code,
            immediateClose: !0
          }));
        } else l(_.highlight(o.code, o.grammar, o.language));
      } else l(_.util.encode(o.code));
    },
    highlight: function highlight(e, r, n) {
      var t = {
        code: e,
        grammar: r,
        language: n
      };
      return _.hooks.run("before-tokenize", t), t.tokens = _.tokenize(t.code, t.grammar), _.hooks.run("after-tokenize", t), L.stringify(_.util.encode(t.tokens), t.language);
    },
    matchGrammar: function matchGrammar(e, r, n, t, a, i, o) {
      for (var l in n) {
        if (n.hasOwnProperty(l) && n[l]) {
          var s = n[l];
          s = Array.isArray(s) ? s : [s];

          for (var u = 0; u < s.length; ++u) {
            if (o && o == l + "," + u) return;
            var c = s[u],
                g = c.inside,
                f = !!c.lookbehind,
                d = !!c.greedy,
                h = 0,
                m = c.alias;

            if (d && !c.pattern.global) {
              var p = c.pattern.toString().match(/[imsuy]*$/)[0];
              c.pattern = RegExp(c.pattern.source, p + "g");
            }

            c = c.pattern || c;

            for (var y = t, v = a; y < r.length; v += r[y].length, ++y) {
              var k = r[y];
              if (r.length > e.length) return;

              if (!(k instanceof L)) {
                if (d && y != r.length - 1) {
                  if (c.lastIndex = v, !(O = c.exec(e))) break;

                  for (var b = O.index + (f && O[1] ? O[1].length : 0), w = O.index + O[0].length, A = y, P = v, x = r.length; A < x && (P < w || !r[A].type && !r[A - 1].greedy); ++A) {
                    (P += r[A].length) <= b && (++y, v = P);
                  }

                  if (r[y] instanceof L) continue;
                  S = A - y, k = e.slice(v, P), O.index -= v;
                } else {
                  c.lastIndex = 0;
                  var O = c.exec(k),
                      S = 1;
                }

                if (O) {
                  f && (h = O[1] ? O[1].length : 0);
                  w = (b = O.index + h) + (O = O[0].slice(h)).length;
                  var j = k.slice(0, b),
                      N = k.slice(w),
                      E = [y, S];
                  j && (++y, v += j.length, E.push(j));
                  var C = new L(l, g ? _.tokenize(O, g) : O, m, O, d);
                  if (E.push(C), N && E.push(N), Array.prototype.splice.apply(r, E), 1 != S && _.matchGrammar(e, r, n, y, v, !0, l + "," + u), i) break;
                } else if (i) break;
              }
            }
          }
        }
      }
    },
    tokenize: function tokenize(e, r) {
      var n = [e],
          t = r.rest;

      if (t) {
        for (var a in t) {
          r[a] = t[a];
        }

        delete r.rest;
      }

      return _.matchGrammar(e, n, r, 0, 0, !1), n;
    },
    hooks: {
      all: {},
      add: function add(e, r) {
        var n = _.hooks.all;
        n[e] = n[e] || [], n[e].push(r);
      },
      run: function run(e, r) {
        var n = _.hooks.all[e];
        if (n && n.length) for (var t, a = 0; t = n[a++];) {
          t(r);
        }
      }
    },
    Token: L
  };

  function L(e, r, n, t, a) {
    this.type = e, this.content = r, this.alias = n, this.length = 0 | (t || "").length, this.greedy = !!a;
  }

  if (u.Prism = _, L.stringify = function (e, r) {
    if ("string" == typeof e) return e;
    if (Array.isArray(e)) return e.map(function (e) {
      return L.stringify(e, r);
    }).join("");
    var n = {
      type: e.type,
      content: L.stringify(e.content, r),
      tag: "span",
      classes: ["token", e.type],
      attributes: {},
      language: r
    };

    if (e.alias) {
      var t = Array.isArray(e.alias) ? e.alias : [e.alias];
      Array.prototype.push.apply(n.classes, t);
    }

    _.hooks.run("wrap", n);

    var a = Object.keys(n.attributes).map(function (e) {
      return e + '="' + (n.attributes[e] || "").replace(/"/g, "&quot;") + '"';
    }).join(" ");
    return "<" + n.tag + ' class="' + n.classes.join(" ") + '"' + (a ? " " + a : "") + ">" + n.content + "</" + n.tag + ">";
  }, !u.document) return u.addEventListener && (_.disableWorkerMessageHandler || u.addEventListener("message", function (e) {
    var r = JSON.parse(e.data),
        n = r.language,
        t = r.code,
        a = r.immediateClose;
    u.postMessage(_.highlight(t, _.languages[n], n)), a && u.close();
  }, !1)), _;

  var e = _.util.currentScript();

  if (e && (_.filename = e.src, e.hasAttribute("data-manual") && (_.manual = !0)), !_.manual) {
    var n = function n() {
      _.manual || _.highlightAll();
    };

    var t = document.readyState;
    "loading" === t || "interactive" === t && e.defer ? document.addEventListener("DOMContentLoaded", n) : window.requestAnimationFrame ? window.requestAnimationFrame(n) : window.setTimeout(n, 16);
  }

  return _;
}(_self);

 true && module.exports && (module.exports = Prism), "undefined" != typeof __webpack_require__.g && (__webpack_require__.g.Prism = Prism);
Prism.languages.markup = {
  comment: /<!--[\s\S]*?-->/,
  prolog: /<\?[\s\S]+?\?>/,
  doctype: {
    pattern: /<!DOCTYPE(?:[^>"'[\]]|"[^"]*"|'[^']*')+(?:\[(?:(?!<!--)[^"'\]]|"[^"]*"|'[^']*'|<!--[\s\S]*?-->)*\]\s*)?>/i,
    greedy: !0
  },
  cdata: /<!\[CDATA\[[\s\S]*?]]>/i,
  tag: {
    pattern: /<\/?(?!\d)[^\s>\/=$<%]+(?:\s(?:\s*[^\s>\/=]+(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s'">=]+(?=[\s>]))|(?=[\s/>])))+)?\s*\/?>/i,
    greedy: !0,
    inside: {
      tag: {
        pattern: /^<\/?[^\s>\/]+/i,
        inside: {
          punctuation: /^<\/?/,
          namespace: /^[^\s>\/:]+:/
        }
      },
      "attr-value": {
        pattern: /=\s*(?:"[^"]*"|'[^']*'|[^\s'">=]+)/i,
        inside: {
          punctuation: [/^=/, {
            pattern: /^(\s*)["']|["']$/,
            lookbehind: !0
          }]
        }
      },
      punctuation: /\/?>/,
      "attr-name": {
        pattern: /[^\s>\/]+/,
        inside: {
          namespace: /^[^\s>\/:]+:/
        }
      }
    }
  },
  entity: /&#?[\da-z]{1,8};/i
}, Prism.languages.markup.tag.inside["attr-value"].inside.entity = Prism.languages.markup.entity, Prism.hooks.add("wrap", function (a) {
  "entity" === a.type && (a.attributes.title = a.content.replace(/&amp;/, "&"));
}), Object.defineProperty(Prism.languages.markup.tag, "addInlined", {
  value: function value(a, e) {
    var s = {};
    s["language-" + e] = {
      pattern: /(^<!\[CDATA\[)[\s\S]+?(?=\]\]>$)/i,
      lookbehind: !0,
      inside: Prism.languages[e]
    }, s.cdata = /^<!\[CDATA\[|\]\]>$/i;
    var n = {
      "included-cdata": {
        pattern: /<!\[CDATA\[[\s\S]*?\]\]>/i,
        inside: s
      }
    };
    n["language-" + e] = {
      pattern: /[\s\S]+/,
      inside: Prism.languages[e]
    };
    var t = {};
    t[a] = {
      pattern: RegExp("(<__[\\s\\S]*?>)(?:<!\\[CDATA\\[[\\s\\S]*?\\]\\]>\\s*|[\\s\\S])*?(?=<\\/__>)".replace(/__/g, a), "i"),
      lookbehind: !0,
      greedy: !0,
      inside: n
    }, Prism.languages.insertBefore("markup", "cdata", t);
  }
}), Prism.languages.xml = Prism.languages.extend("markup", {}), Prism.languages.html = Prism.languages.markup, Prism.languages.mathml = Prism.languages.markup, Prism.languages.svg = Prism.languages.markup;
!function (s) {
  var t = /("|')(?:\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1/;
  s.languages.css = {
    comment: /\/\*[\s\S]*?\*\//,
    atrule: {
      pattern: /@[\w-]+[\s\S]*?(?:;|(?=\s*\{))/,
      inside: {
        rule: /@[\w-]+/
      }
    },
    url: {
      pattern: RegExp("url\\((?:" + t.source + "|[^\n\r()]*)\\)", "i"),
      inside: {
        "function": /^url/i,
        punctuation: /^\(|\)$/
      }
    },
    selector: RegExp("[^{}\\s](?:[^{};\"']|" + t.source + ")*?(?=\\s*\\{)"),
    string: {
      pattern: t,
      greedy: !0
    },
    property: /[-_a-z\xA0-\uFFFF][-\w\xA0-\uFFFF]*(?=\s*:)/i,
    important: /!important\b/i,
    "function": /[-a-z0-9]+(?=\()/i,
    punctuation: /[(){};:,]/
  }, s.languages.css.atrule.inside.rest = s.languages.css;
  var e = s.languages.markup;
  e && (e.tag.addInlined("style", "css"), s.languages.insertBefore("inside", "attr-value", {
    "style-attr": {
      pattern: /\s*style=("|')(?:\\[\s\S]|(?!\1)[^\\])*\1/i,
      inside: {
        "attr-name": {
          pattern: /^\s*style/i,
          inside: e.tag.inside
        },
        punctuation: /^\s*=\s*['"]|['"]\s*$/,
        "attr-value": {
          pattern: /.+/i,
          inside: s.languages.css
        }
      },
      alias: "language-css"
    }
  }, e.tag));
}(Prism);
Prism.languages.clike = {
  comment: [{
    pattern: /(^|[^\\])\/\*[\s\S]*?(?:\*\/|$)/,
    lookbehind: !0
  }, {
    pattern: /(^|[^\\:])\/\/.*/,
    lookbehind: !0,
    greedy: !0
  }],
  string: {
    pattern: /(["'])(?:\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1/,
    greedy: !0
  },
  "class-name": {
    pattern: /(\b(?:class|interface|extends|implements|trait|instanceof|new)\s+|\bcatch\s+\()[\w.\\]+/i,
    lookbehind: !0,
    inside: {
      punctuation: /[.\\]/
    }
  },
  keyword: /\b(?:if|else|while|do|for|return|in|instanceof|function|new|try|throw|catch|finally|null|break|continue)\b/,
  "boolean": /\b(?:true|false)\b/,
  "function": /\w+(?=\()/,
  number: /\b0x[\da-f]+\b|(?:\b\d+\.?\d*|\B\.\d+)(?:e[+-]?\d+)?/i,
  operator: /[<>]=?|[!=]=?=?|--?|\+\+?|&&?|\|\|?|[?*/~^%]/,
  punctuation: /[{}[\];(),.:]/
};
Prism.languages.javascript = Prism.languages.extend("clike", {
  "class-name": [Prism.languages.clike["class-name"], {
    pattern: /(^|[^$\w\xA0-\uFFFF])[_$A-Z\xA0-\uFFFF][$\w\xA0-\uFFFF]*(?=\.(?:prototype|constructor))/,
    lookbehind: !0
  }],
  keyword: [{
    pattern: /((?:^|})\s*)(?:catch|finally)\b/,
    lookbehind: !0
  }, {
    pattern: /(^|[^.])\b(?:as|async(?=\s*(?:function\b|\(|[$\w\xA0-\uFFFF]|$))|await|break|case|class|const|continue|debugger|default|delete|do|else|enum|export|extends|for|from|function|get|if|implements|import|in|instanceof|interface|let|new|null|of|package|private|protected|public|return|set|static|super|switch|this|throw|try|typeof|undefined|var|void|while|with|yield)\b/,
    lookbehind: !0
  }],
  number: /\b(?:(?:0[xX](?:[\dA-Fa-f](?:_[\dA-Fa-f])?)+|0[bB](?:[01](?:_[01])?)+|0[oO](?:[0-7](?:_[0-7])?)+)n?|(?:\d(?:_\d)?)+n|NaN|Infinity)\b|(?:\b(?:\d(?:_\d)?)+\.?(?:\d(?:_\d)?)*|\B\.(?:\d(?:_\d)?)+)(?:[Ee][+-]?(?:\d(?:_\d)?)+)?/,
  "function": /#?[_$a-zA-Z\xA0-\uFFFF][$\w\xA0-\uFFFF]*(?=\s*(?:\.\s*(?:apply|bind|call)\s*)?\()/,
  operator: /--|\+\+|\*\*=?|=>|&&|\|\||[!=]==|<<=?|>>>?=?|[-+*/%&|^!=<>]=?|\.{3}|\?[.?]?|[~:]/
}), Prism.languages.javascript["class-name"][0].pattern = /(\b(?:class|interface|extends|implements|instanceof|new)\s+)[\w.\\]+/, Prism.languages.insertBefore("javascript", "keyword", {
  regex: {
    pattern: /((?:^|[^$\w\xA0-\uFFFF."'\])\s])\s*)\/(?:\[(?:[^\]\\\r\n]|\\.)*]|\\.|[^/\\\[\r\n])+\/[gimyus]{0,6}(?=\s*(?:$|[\r\n,.;})\]]))/,
    lookbehind: !0,
    greedy: !0
  },
  "function-variable": {
    pattern: /#?[_$a-zA-Z\xA0-\uFFFF][$\w\xA0-\uFFFF]*(?=\s*[=:]\s*(?:async\s*)?(?:\bfunction\b|(?:\((?:[^()]|\([^()]*\))*\)|[_$a-zA-Z\xA0-\uFFFF][$\w\xA0-\uFFFF]*)\s*=>))/,
    alias: "function"
  },
  parameter: [{
    pattern: /(function(?:\s+[_$A-Za-z\xA0-\uFFFF][$\w\xA0-\uFFFF]*)?\s*\(\s*)(?!\s)(?:[^()]|\([^()]*\))+?(?=\s*\))/,
    lookbehind: !0,
    inside: Prism.languages.javascript
  }, {
    pattern: /[_$a-z\xA0-\uFFFF][$\w\xA0-\uFFFF]*(?=\s*=>)/i,
    inside: Prism.languages.javascript
  }, {
    pattern: /(\(\s*)(?!\s)(?:[^()]|\([^()]*\))+?(?=\s*\)\s*=>)/,
    lookbehind: !0,
    inside: Prism.languages.javascript
  }, {
    pattern: /((?:\b|\s|^)(?!(?:as|async|await|break|case|catch|class|const|continue|debugger|default|delete|do|else|enum|export|extends|finally|for|from|function|get|if|implements|import|in|instanceof|interface|let|new|null|of|package|private|protected|public|return|set|static|super|switch|this|throw|try|typeof|undefined|var|void|while|with|yield)(?![$\w\xA0-\uFFFF]))(?:[_$A-Za-z\xA0-\uFFFF][$\w\xA0-\uFFFF]*\s*)\(\s*)(?!\s)(?:[^()]|\([^()]*\))+?(?=\s*\)\s*\{)/,
    lookbehind: !0,
    inside: Prism.languages.javascript
  }],
  constant: /\b[A-Z](?:[A-Z_]|\dx?)*\b/
}), Prism.languages.insertBefore("javascript", "string", {
  "template-string": {
    pattern: /`(?:\\[\s\S]|\${(?:[^{}]|{(?:[^{}]|{[^}]*})*})+}|(?!\${)[^\\`])*`/,
    greedy: !0,
    inside: {
      "template-punctuation": {
        pattern: /^`|`$/,
        alias: "string"
      },
      interpolation: {
        pattern: /((?:^|[^\\])(?:\\{2})*)\${(?:[^{}]|{(?:[^{}]|{[^}]*})*})+}/,
        lookbehind: !0,
        inside: {
          "interpolation-punctuation": {
            pattern: /^\${|}$/,
            alias: "punctuation"
          },
          rest: Prism.languages.javascript
        }
      },
      string: /[\s\S]+/
    }
  }
}), Prism.languages.markup && Prism.languages.markup.tag.addInlined("script", "javascript"), Prism.languages.js = Prism.languages.javascript;
!function (h) {
  function v(e, n) {
    return "___" + e.toUpperCase() + n + "___";
  }

  Object.defineProperties(h.languages["markup-templating"] = {}, {
    buildPlaceholders: {
      value: function value(a, r, e, o) {
        if (a.language === r) {
          var c = a.tokenStack = [];
          a.code = a.code.replace(e, function (e) {
            if ("function" == typeof o && !o(e)) return e;

            for (var n, t = c.length; -1 !== a.code.indexOf(n = v(r, t));) {
              ++t;
            }

            return c[t] = e, n;
          }), a.grammar = h.languages.markup;
        }
      }
    },
    tokenizePlaceholders: {
      value: function value(p, k) {
        if (p.language === k && p.tokenStack) {
          p.grammar = h.languages[k];
          var m = 0,
              d = Object.keys(p.tokenStack);
          !function e(n) {
            for (var t = 0; t < n.length && !(m >= d.length); t++) {
              var a = n[t];

              if ("string" == typeof a || a.content && "string" == typeof a.content) {
                var r = d[m],
                    o = p.tokenStack[r],
                    c = "string" == typeof a ? a : a.content,
                    i = v(k, r),
                    u = c.indexOf(i);

                if (-1 < u) {
                  ++m;
                  var g = c.substring(0, u),
                      l = new h.Token(k, h.tokenize(o, p.grammar), "language-" + k, o),
                      s = c.substring(u + i.length),
                      f = [];
                  g && f.push.apply(f, e([g])), f.push(l), s && f.push.apply(f, e([s])), "string" == typeof a ? n.splice.apply(n, [t, 1].concat(f)) : a.content = f;
                }
              } else a.content && e(a.content);
            }

            return n;
          }(p.tokens);
        }
      }
    }
  });
}(Prism);
!function (n) {
  n.languages.php = n.languages.extend("clike", {
    keyword: /\b(?:__halt_compiler|abstract|and|array|as|break|callable|case|catch|class|clone|const|continue|declare|default|die|do|echo|else|elseif|empty|enddeclare|endfor|endforeach|endif|endswitch|endwhile|eval|exit|extends|final|finally|for|foreach|function|global|goto|if|implements|include|include_once|instanceof|insteadof|interface|isset|list|namespace|new|or|parent|print|private|protected|public|require|require_once|return|static|switch|throw|trait|try|unset|use|var|while|xor|yield)\b/i,
    "boolean": {
      pattern: /\b(?:false|true)\b/i,
      alias: "constant"
    },
    constant: [/\b[A-Z_][A-Z0-9_]*\b/, /\b(?:null)\b/i],
    comment: {
      pattern: /(^|[^\\])(?:\/\*[\s\S]*?\*\/|\/\/.*)/,
      lookbehind: !0
    }
  }), n.languages.insertBefore("php", "string", {
    "shell-comment": {
      pattern: /(^|[^\\])#.*/,
      lookbehind: !0,
      alias: "comment"
    }
  }), n.languages.insertBefore("php", "comment", {
    delimiter: {
      pattern: /\?>$|^<\?(?:php(?=\s)|=)?/i,
      alias: "important"
    }
  }), n.languages.insertBefore("php", "keyword", {
    variable: /\$+(?:\w+\b|(?={))/i,
    "package": {
      pattern: /(\\|namespace\s+|use\s+)[\w\\]+/,
      lookbehind: !0,
      inside: {
        punctuation: /\\/
      }
    }
  }), n.languages.insertBefore("php", "operator", {
    property: {
      pattern: /(->)[\w]+/,
      lookbehind: !0
    }
  });
  var e = {
    pattern: /{\$(?:{(?:{[^{}]+}|[^{}]+)}|[^{}])+}|(^|[^\\{])\$+(?:\w+(?:\[.+?]|->\w+)*)/,
    lookbehind: !0,
    inside: n.languages.php
  };
  n.languages.insertBefore("php", "string", {
    "nowdoc-string": {
      pattern: /<<<'([^']+)'(?:\r\n?|\n)(?:.*(?:\r\n?|\n))*?\1;/,
      greedy: !0,
      alias: "string",
      inside: {
        delimiter: {
          pattern: /^<<<'[^']+'|[a-z_]\w*;$/i,
          alias: "symbol",
          inside: {
            punctuation: /^<<<'?|[';]$/
          }
        }
      }
    },
    "heredoc-string": {
      pattern: /<<<(?:"([^"]+)"(?:\r\n?|\n)(?:.*(?:\r\n?|\n))*?\1;|([a-z_]\w*)(?:\r\n?|\n)(?:.*(?:\r\n?|\n))*?\2;)/i,
      greedy: !0,
      alias: "string",
      inside: {
        delimiter: {
          pattern: /^<<<(?:"[^"]+"|[a-z_]\w*)|[a-z_]\w*;$/i,
          alias: "symbol",
          inside: {
            punctuation: /^<<<"?|[";]$/
          }
        },
        interpolation: e
      }
    },
    "single-quoted-string": {
      pattern: /'(?:\\[\s\S]|[^\\'])*'/,
      greedy: !0,
      alias: "string"
    },
    "double-quoted-string": {
      pattern: /"(?:\\[\s\S]|[^\\"])*"/,
      greedy: !0,
      alias: "string",
      inside: {
        interpolation: e
      }
    }
  }), delete n.languages.php.string, n.hooks.add("before-tokenize", function (e) {
    if (/<\?/.test(e.code)) {
      n.languages["markup-templating"].buildPlaceholders(e, "php", /<\?(?:[^"'/#]|\/(?![*/])|("|')(?:\\[\s\S]|(?!\1)[^\\])*\1|(?:\/\/|#)(?:[^?\n\r]|\?(?!>))*|\/\*[\s\S]*?(?:\*\/|$))*?(?:\?>|$)/gi);
    }
  }), n.hooks.add("after-tokenize", function (e) {
    n.languages["markup-templating"].tokenizePlaceholders(e, "php");
  });
}(Prism);
!function (p) {
  var a = p.languages.javadoclike = {
    parameter: {
      pattern: /(^\s*(?:\/{3}|\*|\/\*\*)\s*@(?:param|arg|arguments)\s+)\w+/m,
      lookbehind: !0
    },
    keyword: {
      pattern: /(^\s*(?:\/{3}|\*|\/\*\*)\s*|\{)@[a-z][a-zA-Z-]+\b/m,
      lookbehind: !0
    },
    punctuation: /[{}]/
  };
  Object.defineProperty(a, "addSupport", {
    value: function value(a, e) {
      "string" == typeof a && (a = [a]), a.forEach(function (a) {
        !function (a, e) {
          var n = "doc-comment",
              t = p.languages[a];

          if (t) {
            var r = t[n];

            if (!r) {
              var o = {
                "doc-comment": {
                  pattern: /(^|[^\\])\/\*\*[^/][\s\S]*?(?:\*\/|$)/,
                  lookbehind: !0,
                  alias: "comment"
                }
              };
              r = (t = p.languages.insertBefore(a, "comment", o))[n];
            }

            if (r instanceof RegExp && (r = t[n] = {
              pattern: r
            }), Array.isArray(r)) for (var i = 0, s = r.length; i < s; i++) {
              r[i] instanceof RegExp && (r[i] = {
                pattern: r[i]
              }), e(r[i]);
            } else e(r);
          }
        }(a, function (a) {
          a.inside || (a.inside = {}), a.inside.rest = e;
        });
      });
    }
  }), a.addSupport(["java", "javascript", "php"], a);
}(Prism);
!function (a) {
  var e = "(?:[a-zA-Z]\\w*|[|\\\\[\\]])+";
  a.languages.phpdoc = a.languages.extend("javadoclike", {
    parameter: {
      pattern: RegExp("(@(?:global|param|property(?:-read|-write)?|var)\\s+(?:" + e + "\\s+)?)\\$\\w+"),
      lookbehind: !0
    }
  }), a.languages.insertBefore("phpdoc", "keyword", {
    "class-name": [{
      pattern: RegExp("(@(?:global|package|param|property(?:-read|-write)?|return|subpackage|throws|var)\\s+)" + e),
      lookbehind: !0,
      inside: {
        keyword: /\b(?:callback|resource|boolean|integer|double|object|string|array|false|float|mixed|bool|null|self|true|void|int)\b/,
        punctuation: /[|\\[\]()]/
      }
    }]
  }), a.languages.javadoclike.addSupport("php", a.languages.phpdoc);
}(Prism);
Prism.languages.insertBefore("php", "variable", {
  "this": /\$this\b/,
  global: /\$(?:_(?:SERVER|GET|POST|FILES|REQUEST|SESSION|ENV|COOKIE)|GLOBALS|HTTP_RAW_POST_DATA|argc|argv|php_errormsg|http_response_header)\b/,
  scope: {
    pattern: /\b[\w\\]+::/,
    inside: {
      keyword: /static|self|parent/,
      punctuation: /::|\\/
    }
  }
});
Prism.languages.sql = {
  comment: {
    pattern: /(^|[^\\])(?:\/\*[\s\S]*?\*\/|(?:--|\/\/|#).*)/,
    lookbehind: !0
  },
  variable: [{
    pattern: /@(["'`])(?:\\[\s\S]|(?!\1)[^\\])+\1/,
    greedy: !0
  }, /@[\w.$]+/],
  string: {
    pattern: /(^|[^@\\])("|')(?:\\[\s\S]|(?!\2)[^\\]|\2\2)*\2/,
    greedy: !0,
    lookbehind: !0
  },
  "function": /\b(?:AVG|COUNT|FIRST|FORMAT|LAST|LCASE|LEN|MAX|MID|MIN|MOD|NOW|ROUND|SUM|UCASE)(?=\s*\()/i,
  keyword: /\b(?:ACTION|ADD|AFTER|ALGORITHM|ALL|ALTER|ANALYZE|ANY|APPLY|AS|ASC|AUTHORIZATION|AUTO_INCREMENT|BACKUP|BDB|BEGIN|BERKELEYDB|BIGINT|BINARY|BIT|BLOB|BOOL|BOOLEAN|BREAK|BROWSE|BTREE|BULK|BY|CALL|CASCADED?|CASE|CHAIN|CHAR(?:ACTER|SET)?|CHECK(?:POINT)?|CLOSE|CLUSTERED|COALESCE|COLLATE|COLUMNS?|COMMENT|COMMIT(?:TED)?|COMPUTE|CONNECT|CONSISTENT|CONSTRAINT|CONTAINS(?:TABLE)?|CONTINUE|CONVERT|CREATE|CROSS|CURRENT(?:_DATE|_TIME|_TIMESTAMP|_USER)?|CURSOR|CYCLE|DATA(?:BASES?)?|DATE(?:TIME)?|DAY|DBCC|DEALLOCATE|DEC|DECIMAL|DECLARE|DEFAULT|DEFINER|DELAYED|DELETE|DELIMITERS?|DENY|DESC|DESCRIBE|DETERMINISTIC|DISABLE|DISCARD|DISK|DISTINCT|DISTINCTROW|DISTRIBUTED|DO|DOUBLE|DROP|DUMMY|DUMP(?:FILE)?|DUPLICATE|ELSE(?:IF)?|ENABLE|ENCLOSED|END|ENGINE|ENUM|ERRLVL|ERRORS|ESCAPED?|EXCEPT|EXEC(?:UTE)?|EXISTS|EXIT|EXPLAIN|EXTENDED|FETCH|FIELDS|FILE|FILLFACTOR|FIRST|FIXED|FLOAT|FOLLOWING|FOR(?: EACH ROW)?|FORCE|FOREIGN|FREETEXT(?:TABLE)?|FROM|FULL|FUNCTION|GEOMETRY(?:COLLECTION)?|GLOBAL|GOTO|GRANT|GROUP|HANDLER|HASH|HAVING|HOLDLOCK|HOUR|IDENTITY(?:_INSERT|COL)?|IF|IGNORE|IMPORT|INDEX|INFILE|INNER|INNODB|INOUT|INSERT|INT|INTEGER|INTERSECT|INTERVAL|INTO|INVOKER|ISOLATION|ITERATE|JOIN|KEYS?|KILL|LANGUAGE|LAST|LEAVE|LEFT|LEVEL|LIMIT|LINENO|LINES|LINESTRING|LOAD|LOCAL|LOCK|LONG(?:BLOB|TEXT)|LOOP|MATCH(?:ED)?|MEDIUM(?:BLOB|INT|TEXT)|MERGE|MIDDLEINT|MINUTE|MODE|MODIFIES|MODIFY|MONTH|MULTI(?:LINESTRING|POINT|POLYGON)|NATIONAL|NATURAL|NCHAR|NEXT|NO|NONCLUSTERED|NULLIF|NUMERIC|OFF?|OFFSETS?|ON|OPEN(?:DATASOURCE|QUERY|ROWSET)?|OPTIMIZE|OPTION(?:ALLY)?|ORDER|OUT(?:ER|FILE)?|OVER|PARTIAL|PARTITION|PERCENT|PIVOT|PLAN|POINT|POLYGON|PRECEDING|PRECISION|PREPARE|PREV|PRIMARY|PRINT|PRIVILEGES|PROC(?:EDURE)?|PUBLIC|PURGE|QUICK|RAISERROR|READS?|REAL|RECONFIGURE|REFERENCES|RELEASE|RENAME|REPEAT(?:ABLE)?|REPLACE|REPLICATION|REQUIRE|RESIGNAL|RESTORE|RESTRICT|RETURNS?|REVOKE|RIGHT|ROLLBACK|ROUTINE|ROW(?:COUNT|GUIDCOL|S)?|RTREE|RULE|SAVE(?:POINT)?|SCHEMA|SECOND|SELECT|SERIAL(?:IZABLE)?|SESSION(?:_USER)?|SET(?:USER)?|SHARE|SHOW|SHUTDOWN|SIMPLE|SMALLINT|SNAPSHOT|SOME|SONAME|SQL|START(?:ING)?|STATISTICS|STATUS|STRIPED|SYSTEM_USER|TABLES?|TABLESPACE|TEMP(?:ORARY|TABLE)?|TERMINATED|TEXT(?:SIZE)?|THEN|TIME(?:STAMP)?|TINY(?:BLOB|INT|TEXT)|TOP?|TRAN(?:SACTIONS?)?|TRIGGER|TRUNCATE|TSEQUAL|TYPES?|UNBOUNDED|UNCOMMITTED|UNDEFINED|UNION|UNIQUE|UNLOCK|UNPIVOT|UNSIGNED|UPDATE(?:TEXT)?|USAGE|USE|USER|USING|VALUES?|VAR(?:BINARY|CHAR|CHARACTER|YING)|VIEW|WAITFOR|WARNINGS|WHEN|WHERE|WHILE|WITH(?: ROLLUP|IN)?|WORK|WRITE(?:TEXT)?|YEAR)\b/i,
  "boolean": /\b(?:TRUE|FALSE|NULL)\b/i,
  number: /\b0x[\da-f]+\b|\b\d+\.?\d*|\B\.\d+\b/i,
  operator: /[-+*\/=%^~]|&&?|\|\|?|!=?|<(?:=>?|<|>)?|>[>=]?|\b(?:AND|BETWEEN|IN|LIKE|NOT|OR|IS|DIV|REGEXP|RLIKE|SOUNDS LIKE|XOR)\b/i,
  punctuation: /[;[\]()`,.]/
};
!function () {
  if ("undefined" != typeof self && self.Prism && self.document) {
    var l = "line-numbers",
        c = /\n(?!$)/g,
        m = function m(e) {
      var t = a(e)["white-space"];

      if ("pre-wrap" === t || "pre-line" === t) {
        var n = e.querySelector("code"),
            r = e.querySelector(".line-numbers-rows"),
            s = e.querySelector(".line-numbers-sizer"),
            i = n.textContent.split(c);
        s || ((s = document.createElement("span")).className = "line-numbers-sizer", n.appendChild(s)), s.style.display = "block", i.forEach(function (e, t) {
          s.textContent = e || "\n";
          var n = s.getBoundingClientRect().height;
          r.children[t].style.height = n + "px";
        }), s.textContent = "", s.style.display = "none";
      }
    },
        a = function a(e) {
      return e ? window.getComputedStyle ? getComputedStyle(e) : e.currentStyle || null : null;
    };

    window.addEventListener("resize", function () {
      Array.prototype.forEach.call(document.querySelectorAll("pre." + l), m);
    }), Prism.hooks.add("complete", function (e) {
      if (e.code) {
        var t = e.element,
            n = t.parentNode;

        if (n && /pre/i.test(n.nodeName) && !t.querySelector(".line-numbers-rows")) {
          for (var r = !1, s = /(?:^|\s)line-numbers(?:\s|$)/, i = t; i; i = i.parentNode) {
            if (s.test(i.className)) {
              r = !0;
              break;
            }
          }

          if (r) {
            t.className = t.className.replace(s, " "), s.test(n.className) || (n.className += " line-numbers");
            var l,
                a = e.code.match(c),
                o = a ? a.length + 1 : 1,
                u = new Array(o + 1).join("<span></span>");
            (l = document.createElement("span")).setAttribute("aria-hidden", "true"), l.className = "line-numbers-rows", l.innerHTML = u, n.hasAttribute("data-start") && (n.style.counterReset = "linenumber " + (parseInt(n.getAttribute("data-start"), 10) - 1)), e.element.appendChild(l), m(n), Prism.hooks.run("line-numbers", e);
          }
        }
      }
    }), Prism.hooks.add("line-numbers", function (e) {
      e.plugins = e.plugins || {}, e.plugins.lineNumbers = !0;
    }), Prism.plugins.lineNumbers = {
      getLine: function getLine(e, t) {
        if ("PRE" === e.tagName && e.classList.contains(l)) {
          var n = e.querySelector(".line-numbers-rows"),
              r = parseInt(e.getAttribute("data-start"), 10) || 1,
              s = r + (n.children.length - 1);
          t < r && (t = r), s < t && (t = s);
          var i = t - r;
          return n.children[i];
        }
      }
    };
  }
}();

/***/ }),

/***/ "./themes/default/src/js/progress.js":
/*!*******************************************!*\
  !*** ./themes/default/src/js/progress.js ***!
  \*******************************************/
/***/ (() => {

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */
$(function () {
  $(".rounded-progress").each(function () {
    var value = $(this).attr('data-value');
    var left = $(this).find('.progress-left .progress-bar');
    var right = $(this).find('.progress-right .progress-bar');

    if (value > 0) {
      if (value <= 50) {
        right.css('transform', 'rotate(' + percentageToDegrees(value) + 'deg)');
      } else {
        right.css('transform', 'rotate(180deg)');
        left.css('transform', 'rotate(' + percentageToDegrees(value - 50) + 'deg)');
      }
    }
  });

  function percentageToDegrees(percentage) {
    return percentage / 100 * 360;
  }
});

/***/ }),

/***/ "./themes/default/src/js/slider.js":
/*!*****************************************!*\
  !*** ./themes/default/src/js/slider.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var swiper__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! swiper */ "./node_modules/swiper/swiper.esm.js");
/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

var swiperSlider = new swiper__WEBPACK_IMPORTED_MODULE_0__["default"]('.screenshots', {
  slidesPerView: 1,
  spaceBetween: 10,
  // init: false,
  pagination: {
    el: '.swiper-pagination',
    clickable: true
  },
  breakpoints: {
    640: {
      slidesPerView: 2,
      spaceBetween: 20
    },
    768: {
      slidesPerView: 2,
      spaceBetween: 40
    },
    1024: {
      slidesPerView: 3,
      spaceBetween: 20
    }
  }
});

/***/ }),

/***/ "./themes/default/src/js/wysibb.js":
/*!*****************************************!*\
  !*** ./themes/default/src/js/wysibb.js ***!
  \*****************************************/
/***/ (() => {

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

/*! WysiBB v1.5.1 2014-03-26
    Author: Vadim Dobroskok
 */
if (typeof WBBLANG == "undefined") {
  WBBLANG = {};
}

WBBLANG['en'] = CURLANG = {
  bold: "Bold",
  italic: "Italic",
  underline: "Underline",
  strike: "Strike",
  link: "Link",
  img: "Insert image",
  sup: "Superscript",
  sub: "Subscript",
  justifyleft: "Align left",
  justifycenter: "Align center",
  justifyright: "Align right",
  table: "Insert table",
  bullist: "• Unordered list",
  numlist: "1. Ordered list",
  quote: "Quote",
  offtop: "Offtop",
  code: "Code",
  spoiler: "Spoiler",
  fontcolor: "Font color",
  fontsize: "Font size",
  fontfamily: "Font family",
  fs_verysmall: "Very small",
  fs_small: "Small",
  fs_normal: "Normal",
  fs_big: "Big",
  fs_verybig: "Very big",
  smilebox: "Insert emoticon",
  video: "Insert YouTube",
  removeFormat: "Remove Format",
  modal_link_title: "Insert link",
  modal_link_text: "Display text",
  modal_link_url: "URL",
  modal_email_text: "Display email",
  modal_email_url: "Email",
  modal_link_tab1: "Insert URL",
  modal_img_title: "Insert image",
  modal_img_tab1: "Insert URL",
  modal_img_tab2: "Upload image",
  modal_imgsrc_text: "Enter image URL",
  modal_img_btn: "Choose file",
  add_attach: "Add Attachment",
  modal_video_text: "Enter the URL of the video",
  close: "Close",
  save: "Save",
  cancel: "Cancel",
  remove: "Delete",
  validation_err: "The entered data is invalid",
  error_onupload: "Error during file upload",
  fileupload_text1: "Drop file here",
  fileupload_text2: "or",
  loading: "Loading",
  auto: "Auto",
  views: "Views",
  downloads: "Downloads",
  //smiles
  sm1: "Smile",
  sm2: "Laughter",
  sm3: "Wink",
  sm4: "Thank you",
  sm5: "Scold",
  sm6: "Shock",
  sm7: "Angry",
  sm8: "Pain",
  sm9: "Sick"
};
wbbdebug = true;

(function ($) {
  'use strict';

  $.wysibb = function (txtArea, settings) {
    $(txtArea).data("wbb", this);

    if (settings && settings.deflang && typeof WBBLANG[settings.deflang] != "undefined") {
      CURLANG = WBBLANG[settings.deflang];
    }

    if (settings && settings.lang && typeof WBBLANG[settings.lang] != "undefined") {
      CURLANG = WBBLANG[settings.lang];
    }

    this.txtArea = txtArea;
    this.$txtArea = $(txtArea);
    var id = this.$txtArea.attr("id") || this.setUID(this.txtArea);
    this.options = {
      bbmode: false,
      onlyBBmode: false,
      themeName: "default",
      bodyClass: "",
      lang: "ru",
      tabInsert: true,
      //			toolbar:			false,
      //img upload config
      imgupload: false,
      img_uploadurl: "/iupload.php",
      img_maxwidth: 800,
      img_maxheight: 800,
      hotkeys: true,
      showHotkeys: true,
      autoresize: true,
      resize_maxheight: 800,
      loadPageStyles: true,
      traceTextarea: true,
      //			direction:			"ltr",
      smileConversion: true,
      //END img upload config
      buttons: "bold,italic,underline,strike,sup,sub,|,img,video,link,|,bullist,numlist,|,fontcolor,fontsize,fontfamily,|,justifyleft,justifycenter,justifyright,|,quote,code,table,removeFormat",
      allButtons: {
        bold: {
          title: CURLANG.bold,
          buttonHTML: "<span class=\"fonticon ve-tlb-bold1\">\uE018</span>",
          excmd: 'bold',
          hotkey: 'ctrl+b',
          transform: {
            '<b>{SELTEXT}</b>': "[b]{SELTEXT}[/b]",
            '<strong>{SELTEXT}</strong>': "[b]{SELTEXT}[/b]"
          }
        },
        italic: {
          title: CURLANG.italic,
          buttonHTML: "<span class=\"fonticon ve-tlb-italic1\">\uE001</span>",
          excmd: 'italic',
          hotkey: 'ctrl+i',
          transform: {
            '<i>{SELTEXT}</i>': "[i]{SELTEXT}[/i]",
            '<em>{SELTEXT}</em>': "[i]{SELTEXT}[/i]"
          }
        },
        underline: {
          title: CURLANG.underline,
          buttonHTML: "<span class=\"fonticon ve-tlb-underline1\">\uE002</span>",
          excmd: 'underline',
          hotkey: 'ctrl+u',
          transform: {
            '<u>{SELTEXT}</u>': "[u]{SELTEXT}[/u]"
          }
        },
        strike: {
          title: CURLANG.strike,
          buttonHTML: "<span class=\"fonticon fi-stroke1 ve-tlb-strike1\">\uE003</span>",
          excmd: 'strikeThrough',
          transform: {
            '<strike>{SELTEXT}</strike>': "[s]{SELTEXT}[/s]",
            '<s>{SELTEXT}</s>': "[s]{SELTEXT}[/s]"
          }
        },
        sup: {
          title: CURLANG.sup,
          buttonHTML: "<span class=\"fonticon ve-tlb-sup1\">\uE005</span>",
          excmd: 'superscript',
          transform: {
            '<sup>{SELTEXT}</sup>': "[sup]{SELTEXT}[/sup]"
          }
        },
        sub: {
          title: CURLANG.sub,
          buttonHTML: "<span class=\"fonticon ve-tlb-sub1\">\uE004</span>",
          excmd: 'subscript',
          transform: {
            '<sub>{SELTEXT}</sub>': "[sub]{SELTEXT}[/sub]"
          }
        },
        link: {
          title: CURLANG.link,
          buttonHTML: "<span class=\"fonticon ve-tlb-link1\">\uE007</span>",
          hotkey: 'ctrl+shift+2',
          modal: {
            title: CURLANG.modal_link_title,
            width: "500px",
            tabs: [{
              input: [{
                param: "SELTEXT",
                title: CURLANG.modal_link_text,
                type: "div"
              }, {
                param: "URL",
                title: CURLANG.modal_link_url,
                validation: '^http(s)?://'
              }]
            }]
          },
          transform: {
            '<a href="{URL}">{SELTEXT}</a>': "[url={URL}]{SELTEXT}[/url]",
            '<a href="{URL}">{URL}</a>': "[url]{URL}[/url]"
          }
        },
        img: {
          title: CURLANG.img,
          buttonHTML: "<span class=\"fonticon ve-tlb-img1\">\uE006</span>",
          hotkey: 'ctrl+shift+1',
          addWrap: true,
          modal: {
            title: CURLANG.modal_img_title,
            width: "600px",
            tabs: [{
              title: CURLANG.modal_img_tab1,
              input: [{
                param: "SRC",
                title: CURLANG.modal_imgsrc_text,
                validation: '^http(s)?://.*?\.(jpg|png|gif|jpeg)$'
              }]
            }],
            onLoad: this.imgLoadModal
          },
          transform: {
            '<img src="{SRC}" />': "[img]{SRC}[/img]",
            '<img src="{SRC}" width="{WIDTH}" height="{HEIGHT}"/>': "[img width={WIDTH},height={HEIGHT}]{SRC}[/img]"
          }
        },
        bullist: {
          title: CURLANG.bullist,
          buttonHTML: "<span class=\"fonticon ve-tlb-list1\">\uE009</span>",
          excmd: 'insertUnorderedList',
          transform: {
            '<ul>{SELTEXT}</ul>': "[list]{SELTEXT}[/list]",
            '<li>{SELTEXT}</li>': "[*]{SELTEXT}[/*]"
          }
        },
        numlist: {
          title: CURLANG.numlist,
          buttonHTML: "<span class=\"fonticon ve-tlb-numlist1\">\uE00A</span>",
          excmd: 'insertOrderedList',
          transform: {
            '<ol>{SELTEXT}</ol>': "[list=1]{SELTEXT}[/list]",
            '<li>{SELTEXT}</li>': "[*]{SELTEXT}[/*]"
          }
        },
        quote: {
          title: CURLANG.quote,
          buttonHTML: "<span class=\"fonticon ve-tlb-quote1\">\uE00C</span>",
          hotkey: 'ctrl+shift+3',
          //subInsert: true,
          transform: {
            '<blockquote class="blockquote post-quote p-2 bg-light border rounded d-inline-block">{SELTEXT}</blockquote>': "[quote]{SELTEXT}[/quote]"
          }
        },
        code: {
          title: CURLANG.code,
          buttonText: '[code]',

          /* buttonHTML: '<span class="fonticon">\uE00d</span>', */
          hotkey: 'ctrl+shift+4',
          onlyClearText: true,
          transform: {
            '<code>{SELTEXT}</code>': "[code=php]{SELTEXT}[/code]"
          }
        },
        offtop: {
          title: CURLANG.offtop,
          buttonText: 'offtop',
          transform: {
            '<span style="font-size:10px;color:#ccc">{SELTEXT}</span>': "[offtop]{SELTEXT}[/offtop]"
          }
        },
        fontcolor: {
          type: "colorpicker",
          title: CURLANG.fontcolor,
          excmd: "foreColor",
          valueBBname: "color",
          subInsert: true,
          colors: "#000000,#444444,#666666,#999999,#b6b6b6,#cccccc,#d8d8d8,#efefef,#f4f4f4,#ffffff,-, \
							 #ff0000,#980000,#ff7700,#ffff00,#00ff00,#00ffff,#1e84cc,#0000ff,#9900ff,#ff00ff,-, \
							 #f4cccc,#dbb0a7,#fce5cd,#fff2cc,#d9ead3,#d0e0e3,#c9daf8,#cfe2f3,#d9d2e9,#ead1dc, \
							 #ea9999,#dd7e6b,#f9cb9c,#ffe599,#b6d7a8,#a2c4c9,#a4c2f4,#9fc5e8,#b4a7d6,#d5a6bd, \
							 #e06666,#cc4125,#f6b26b,#ffd966,#93c47d,#76a5af,#6d9eeb,#6fa8dc,#8e7cc3,#c27ba0, \
							 #cc0000,#a61c00,#e69138,#f1c232,#6aa84f,#45818e,#3c78d8,#3d85c6,#674ea7,#a64d79, \
							 #900000,#85200C,#B45F06,#BF9000,#38761D,#134F5C,#1155Cc,#0B5394,#351C75,#741B47, \
							 #660000,#5B0F00,#783F04,#7F6000,#274E13,#0C343D,#1C4587,#073763,#20124D,#4C1130",
          transform: {
            '<font color="{COLOR}">{SELTEXT}</font>': '[color={COLOR}]{SELTEXT}[/color]'
          }
        },
        table: {
          type: "table",
          title: CURLANG.table,
          cols: 10,
          rows: 10,
          cellwidth: 20,
          transform: {
            '<td>{SELTEXT}</td>': '[td]{SELTEXT}[/td]',
            '<tr>{SELTEXT}</tr>': '[tr]{SELTEXT}[/tr]',
            '<table class="wbb-table">{SELTEXT}</table>': '[table]{SELTEXT}[/table]'
          },
          skipRules: true
        },
        fontsize: {
          type: 'select',
          title: CURLANG.fontsize,
          options: "fs_verysmall,fs_small,fs_normal,fs_big,fs_verybig"
        },
        fontfamily: {
          type: 'select',
          title: CURLANG.fontfamily,
          excmd: 'fontName',
          valueBBname: "font",
          options: [{
            title: "Arial",
            exvalue: "Arial"
          }, {
            title: "Comic Sans MS",
            exvalue: "Comic Sans MS"
          }, {
            title: "Courier New",
            exvalue: "Courier New"
          }, {
            title: "Georgia",
            exvalue: "Georgia"
          }, {
            title: "Lucida Sans Unicode",
            exvalue: "Lucida Sans Unicode"
          }, {
            title: "Tahoma",
            exvalue: "Tahoma"
          }, {
            title: "Times New Roman",
            exvalue: "Times New Roman"
          }, {
            title: "Trebuchet MS",
            exvalue: "Trebuchet MS"
          }, {
            title: "Verdana",
            exvalue: "Verdana"
          }],
          transform: {
            '<font face="{FONT}">{SELTEXT}</font>': '[font={FONT}]{SELTEXT}[/font]'
          }
        },
        smilebox: {
          type: 'smilebox',
          title: CURLANG.smilebox,
          buttonHTML: "<span class=\"fonticon ve-tlb-smilebox1\">\uE00B</span>"
        },
        justifyleft: {
          title: CURLANG.justifyleft,
          buttonHTML: "<span class=\"fonticon ve-tlb-textleft1\">\uE015</span>",
          groupkey: 'align',
          transform: {
            '<p style="text-align:left">{SELTEXT}</p>': '[left]{SELTEXT}[/left]'
          }
        },
        justifyright: {
          title: CURLANG.justifyright,
          buttonHTML: "<span class=\"fonticon ve-tlb-textright1\">\uE016</span>",
          groupkey: 'align',
          transform: {
            '<p style="text-align:right">{SELTEXT}</p>': '[right]{SELTEXT}[/right]'
          }
        },
        justifycenter: {
          title: CURLANG.justifycenter,
          buttonHTML: "<span class=\"fonticon ve-tlb-textcenter1\">\uE014</span>",
          groupkey: 'align',
          transform: {
            '<p style="text-align:center">{SELTEXT}</p>': '[center]{SELTEXT}[/center]'
          }
        },
        video: {
          title: CURLANG.video,
          buttonHTML: "<span class=\"fonticon ve-tlb-video1\">\uE008</span>",
          modal: {
            title: CURLANG.video,
            width: "600px",
            tabs: [{
              title: CURLANG.video,
              input: [{
                param: "SRC",
                title: CURLANG.modal_video_text
              }]
            }],
            onSubmit: function onSubmit(cmd, opt, queryState) {
              var url = this.$modal.find('input[name="SRC"]').val();

              if (url) {
                url = url.replace(/^\s+/, "").replace(/\s+$/, "");
              }

              var a;

              if (url.indexOf("youtu.be") != -1) {
                a = url.match(/^http[s]*:\/\/youtu\.be\/([a-z0-9_-]+)/i);
              } else {
                a = url.match(/^http[s]*:\/\/www\.youtube\.com\/watch\?.*?v=([a-z0-9_-]+)/i);
              }

              if (a && a.length == 2) {
                var code = a[1];
                this.insertAtCursor(this.getCodeByCommand(cmd, {
                  src: code
                }));
              }

              this.closeModal();
              this.updateUI();
              return false;
            }
          },
          transform: {
            '<div style="max-width: 600px"><div class="embed-responsive embed-responsive-16by9"><iframe src="http://www.youtube.com/embed/{SRC}" frameborder="0"></iframe></div></div>': '[youtube]https://www.youtube.com/watch?v={SRC}[/youtube]'
          }
        },
        //select options
        fs_verysmall: {
          title: CURLANG.fs_verysmall,
          buttonText: "fs1",
          excmd: 'fontSize',
          exvalue: "1",
          transform: {
            '<font size="1">{SELTEXT}</font>': '[size=50]{SELTEXT}[/size]'
          }
        },
        fs_small: {
          title: CURLANG.fs_small,
          buttonText: "fs2",
          excmd: 'fontSize',
          exvalue: "2",
          transform: {
            '<font size="2">{SELTEXT}</font>': '[size=85]{SELTEXT}[/size]'
          }
        },
        fs_normal: {
          title: CURLANG.fs_normal,
          buttonText: "fs3",
          excmd: 'fontSize',
          exvalue: "3",
          transform: {
            '<font size="3">{SELTEXT}</font>': '[size=100]{SELTEXT}[/size]'
          }
        },
        fs_big: {
          title: CURLANG.fs_big,
          buttonText: "fs4",
          excmd: 'fontSize',
          exvalue: "4",
          transform: {
            '<font size="4">{SELTEXT}</font>': '[size=150]{SELTEXT}[/size]'
          }
        },
        fs_verybig: {
          title: CURLANG.fs_verybig,
          buttonText: "fs5",
          excmd: 'fontSize',
          exvalue: "6",
          transform: {
            '<font size="6">{SELTEXT}</font>': '[size=200]{SELTEXT}[/size]'
          }
        },
        removeformat: {
          title: CURLANG.removeFormat,
          buttonHTML: "<span class=\"fonticon ve-tlb-removeformat1\">\uE00F</span>",
          excmd: "removeFormat"
        }
      },
      systr: {
        '<br/>': "\n",
        '<span class="wbbtab">{SELTEXT}</span>': '   {SELTEXT}'
      },
      customRules: {
        td: [["[td]{SELTEXT}[/td]", {
          seltext: {
            rgx: false,
            attr: false,
            sel: false
          }
        }]],
        tr: [["[tr]{SELTEXT}[/tr]", {
          seltext: {
            rgx: false,
            attr: false,
            sel: false
          }
        }]],
        table: [["[table]{SELTEXT}[/table]", {
          seltext: {
            rgx: false,
            attr: false,
            sel: false
          }
        }]] //blockquote: [["   {SELTEXT}",{seltext: {rgx:false,attr:false,sel:false}}]]

      },
      smileList: [//{title:CURLANG.sm1, img: '<img src="{themePrefix}{themeName}/img/smiles/sm1.png" class="sm">', bbcode:":)"},
      ],
      attrWrap: ['src', 'color', 'href'] //use becouse FF and IE change values for this attr, modify [attr] to _[attr]

    }; //FIX for Opera. Wait while iframe loaded

    this.inited = this.options.onlyBBmode; //init css prefix, if not set

    if (!this.options.themePrefix) {
      $('link').each($.proxy(function (idx, el) {
        var sriptMatch = $(el).get(0).href.match(/(.*\/)(.*)\/wbbtheme\.css.*$/);

        if (sriptMatch !== null) {
          this.options.themeName = sriptMatch[2];
          this.options.themePrefix = sriptMatch[1];
        }
      }, this));
    } //check for preset


    if (typeof WBBPRESET != "undefined") {
      if (WBBPRESET.allButtons) {
        //clear transform
        $.each(WBBPRESET.allButtons, $.proxy(function (k, v) {
          if (v.transform && this.options.allButtons[k]) {
            delete this.options.allButtons[k].transform;
          }
        }, this));
      }

      $.extend(true, this.options, WBBPRESET);
    }

    if (settings && settings.allButtons) {
      $.each(settings.allButtons, $.proxy(function (k, v) {
        if (v.transform && this.options.allButtons[k]) {
          delete this.options.allButtons[k].transform;
        }
      }, this));
    }

    $.extend(true, this.options, settings);
    this.init();
  };

  $.wysibb.prototype = {
    lastid: 1,
    init: function init() {
      $.log("Init", this); //check for mobile

      this.isMobile = function (a) {
        /android|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|meego.+mobile|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(a);
      }(navigator.userAgent || navigator.vendor || window.opera); //use bbmode on mobile devices
      //this.isMobile = true; //TEMP


      if (this.options.onlyBBmode === true) {
        this.options.bbmode = true;
      } //create array of controls, for queryState


      this.controllers = []; //convert button string to array

      this.options.buttons = this.options.buttons.toLowerCase();
      this.options.buttons = this.options.buttons.split(","); //init system transforms

      this.options.allButtons["_systr"] = {};
      this.options.allButtons["_systr"]["transform"] = this.options.systr;
      this.smileFind();
      this.initTransforms();
      this.build();
      this.initModal();

      if (this.options.hotkeys === true && !this.isMobile) {
        this.initHotkeys();
      } //sort smiles


      if (this.options.smileList && this.options.smileList.length > 0) {
        this.options.smileList.sort(function (a, b) {
          return b.bbcode.length - a.bbcode.length;
        });
      }

      this.$txtArea.parents("form").bind("submit", $.proxy(function () {
        this.sync();
        return true;
      }, this)); //phpbb2

      this.$txtArea.parents("form").find("input[id*='preview'],input[id*='submit'],input[class*='preview'],input[class*='submit'],input[name*='preview'],input[name*='submit']").bind("mousedown", $.proxy(function () {
        this.sync();
        setTimeout($.proxy(function () {
          if (this.options.bbmode === false) {
            this.$txtArea.removeAttr("wbbsync").val("");
          }
        }, this), 1000);
      }, this)); //end phpbb2

      if (this.options.initCallback) {
        this.options.initCallback.call(this);
      }

      $.log(this);
    },
    initTransforms: function initTransforms() {
      $.log("Create rules for transform HTML=>BB");
      var o = this.options; //need to check for active buttons

      if (!o.rules) {
        o.rules = {};
      }

      if (!o.groups) {
        o.groups = {};
      } //use for groupkey, For example: justifyleft,justifyright,justifycenter. It is must replace each other.


      var btnlist = o.buttons.slice(); //add system transform

      btnlist.push("_systr");

      for (var bidx = 0; bidx < btnlist.length; bidx++) {
        var ob = o.allButtons[btnlist[bidx]];

        if (!ob) {
          continue;
        }

        ob.en = true; //check for simplebbcode

        if (ob.simplebbcode && $.isArray(ob.simplebbcode) && ob.simplebbcode.length == 2) {
          ob.bbcode = ob.html = ob.simplebbcode[0] + "{SELTEXT}" + ob.simplebbcode[1];
          if (ob.transform) delete ob.transform;
          if (ob.modal) delete ob.modal;
        } //add transforms to option list


        if (ob.type == "select" && typeof ob.options == "string") {
          var olist = ob.options.split(",");
          $.each(olist, function (i, op) {
            if ($.inArray(op, btnlist) == -1) {
              btnlist.push(op);
            }
          });
        }

        if (ob.transform && ob.skipRules !== true) {
          var obtr = $.extend({}, ob.transform);
          /* if (ob.addWrap) {
          //addWrap
          $.log("needWrap");
          for (var bhtml in obtr) {
          var bbcode = ob.transform[bhtml];
          var newhtml = '<span wbb="'+btnlist[bidx]+'">'+bhtml+'</span>';
          obtr[newhtml] = bbcode;
          }
          } */

          for (var bhtml in obtr) {
            var orightml = bhtml;
            var bbcode = obtr[bhtml]; //create root selector for isContain bbmode

            if (!ob.bbSelector) {
              ob.bbSelector = [];
            }

            if ($.inArray(bbcode, ob.bbSelector) == -1) {
              ob.bbSelector.push(bbcode);
            }

            if (this.options.onlyBBmode === false) {
              //wrap attributes
              bhtml = this.wrapAttrs(bhtml);
              var $bel = $(document.createElement('DIV')).append($(this.elFromString(bhtml, document)));
              var rootSelector = this.filterByNode($bel.children()); //check if current rootSelector is exist, create unique selector for each transform (1.2.2)

              if (rootSelector == "div" || typeof o.rules[rootSelector] != "undefined") {
                //create unique selector
                $.log("create unique selector: " + rootSelector);
                this.setUID($bel.children());
                rootSelector = this.filterByNode($bel.children());
                $.log("New rootSelector: " + rootSelector); //replace transform with unique selector

                var nhtml2 = $bel.html();
                nhtml2 = this.unwrapAttrs(nhtml2);
                var obhtml = this.unwrapAttrs(bhtml);
                ob.transform[nhtml2] = bbcode;
                delete ob.transform[obhtml];
                bhtml = nhtml2;
                orightml = nhtml2;
              } //create root selector for isContain


              if (!ob.excmd) {
                if (!ob.rootSelector) {
                  ob.rootSelector = [];
                }

                ob.rootSelector.push(rootSelector);
              } //check for rules on this rootSeletor


              if (typeof o.rules[rootSelector] == "undefined") {
                o.rules[rootSelector] = [];
              }

              var crules = {};

              if (bhtml.match(/\{\S+?\}/)) {
                $bel.find('*').each($.proxy(function (idx, el) {
                  //check attributes
                  var attributes = this.getAttributeList(el);
                  $.each(attributes, $.proxy(function (i, item) {
                    var attr = $(el).attr(item);

                    if (item.substr(0, 1) == '_') {
                      item = item.substr(1);
                    }

                    var r = attr.match(/\{\S+?\}/g);

                    if (r) {
                      for (var a = 0; a < r.length; a++) {
                        var rname = r[a].substr(1, r[a].length - 2);
                        rname = rname.replace(this.getValidationRGX(rname), "");
                        var p = this.relFilterByNode(el, rootSelector);
                        var regRepl = attr != r[a] ? this.getRegexpReplace(attr, r[a]) : false;
                        crules[rname.toLowerCase()] = {
                          sel: p ? $.trim(p) : false,
                          attr: item,
                          rgx: regRepl
                        };
                      }
                    }
                  }, this)); //check for text

                  var sl = [];

                  if (!$(el).is("iframe")) {
                    $(el).contents().filter(function () {
                      return this.nodeType === 3;
                    }).each($.proxy(function (i, rel) {
                      var txt = rel.textContent || rel.data;

                      if (typeof txt == "undefined") {
                        return true;
                      }

                      var r = txt.match(/\{\S+?\}/g);

                      if (r) {
                        for (var a = 0; a < r.length; a++) {
                          var rname = r[a].substr(1, r[a].length - 2);
                          rname = rname.replace(this.getValidationRGX(rname), "");
                          var p = this.relFilterByNode(el, rootSelector);
                          var regRepl = txt != r[a] ? this.getRegexpReplace(txt, r[a]) : false;
                          var sel = p ? $.trim(p) : false;

                          if ($.inArray(sel, sl) > -1 || $(rel).parent().contents().length > 1) {
                            //has dublicate and not one children, need wrap
                            var nel = $("<span>").html("{" + rname + "}");
                            this.setUID(nel, "wbb");
                            var start = txt.indexOf(rname) + rname.length + 1;
                            var after_txt = txt.substr(start, txt.length - start); //create wrap element

                            rel.data = txt.substr(0, txt.indexOf(rname) - 1);
                            $(rel).after(this.elFromString(after_txt, document)).after(nel);
                            sel = (sel ? sel + " " : "") + this.filterByNode(nel);
                            regRepl = false;
                          }

                          crules[rname.toLowerCase()] = {
                            sel: sel,
                            attr: false,
                            rgx: regRepl
                          };
                          sl[sl.length] = sel;
                        }
                      }
                    }, this));
                  }

                  sl = null;
                }, this));
                var nbhtml = $bel.html(); //UnWrap attributes

                nbhtml = this.unwrapAttrs(nbhtml);

                if (orightml != nbhtml) {
                  //if we modify html, replace it
                  delete ob.transform[orightml];
                  ob.transform[nbhtml] = bbcode;
                  bhtml = nbhtml;
                }
              }

              o.rules[rootSelector].push([bbcode, crules]); //check for onlyClearText

              if (ob.onlyClearText === true) {
                if (!this.cleartext) {
                  this.cleartext = {};
                }

                this.cleartext[rootSelector] = btnlist[bidx];
              } //check for groupkey


              if (ob.groupkey) {
                if (!o.groups[ob.groupkey]) {
                  o.groups[ob.groupkey] = [];
                }

                o.groups[ob.groupkey].push(rootSelector);
              }
            }
          } //sort rootSelector


          if (ob.rootSelector) {
            this.sortArray(ob.rootSelector, -1);
          }

          var htmll = $.map(ob.transform, function (bb, html) {
            return html;
          }).sort(function (a, b) {
            return (b[0] || "").length - (a[0] || "").length;
          });
          ob.bbcode = ob.transform[htmll[0]];
          ob.html = htmll[0];
        }
      }

      ;
      this.options.btnlist = btnlist; //use for transforms, becouse select elements not present in buttons
      //add custom rules, for table,tr,td and other

      $.extend(o.rules, this.options.customRules); //smile rules

      o.srules = {};

      if (this.options.smileList) {
        $.each(o.smileList, $.proxy(function (i, sm) {
          var $sm = $(this.strf(sm.img, o));
          var f = this.filterByNode($sm);
          o.srules[f] = [sm.bbcode, sm.img];
        }, this));
      } //sort transforms by bbcode length desc


      for (var rootsel in o.rules) {
        this.options.rules[rootsel].sort(function (a, b) {
          return b[0].length - a[0].length;
        });
      } //create rootsel list


      this.rsellist = [];

      for (var rootsel in this.options.rules) {
        this.rsellist.push(rootsel);
      }

      this.sortArray(this.rsellist, -1);
    },
    //BUILD
    build: function build() {
      $.log("Build editor"); //this.$editor = $('<div class="wysibb">');

      this.$editor = $('<div>').addClass("wysibb");

      if (this.isMobile) {
        this.$editor.addClass("wysibb-mobile");
      } //set direction if defined


      if (this.options.direction) {
        this.$editor.css("direction", this.options.direction);
      }

      this.$editor.insertAfter(this.txtArea).append(this.txtArea);
      this.startHeight = this.$txtArea.outerHeight();
      this.$txtArea.addClass("wysibb-texarea");
      this.buildToolbar(); //Build iframe if needed

      this.$txtArea.wrap('<div class="wysibb-text">');

      if (this.options.onlyBBmode === false) {
        var height = this.options.minheight || this.$txtArea.outerHeight();
        var maxheight = this.options.resize_maxheight;
        var mheight = this.options.autoresize === true ? this.options.resize_maxheight : height;
        this.$body = $(this.strf('<div class="wysibb-text-editor" style="max-height:{maxheight}px;min-height:{height}px"></div>', {
          maxheight: mheight,
          height: height
        })).insertAfter(this.$txtArea);
        this.body = this.$body[0];
        this.$txtArea.hide();

        if (height > 32) {
          this.$toolbar.css("max-height", height);
        }

        $.log("WysiBB loaded");
        this.$body.addClass("wysibb-body").addClass(this.options.bodyClass); //set direction if defined

        if (this.options.direction) {
          this.$body.css("direction", this.options.direction);
        }

        if ('contentEditable' in this.body) {
          this.body.contentEditable = true;

          try {
            //fix for mfirefox
            //document.execCommand('enableObjectResizing', false, 'false'); //disable image resizing
            document.execCommand('StyleWithCSS', false, false); //document.designMode = "on";

            this.$body.append("<span></span>");
          } catch (e) {}
        } else {
          //use onlybbmode
          this.options.onlyBBmode = this.options.bbmode = true;
        } //check for exist content in textarea


        if (this.txtArea.value.length > 0) {
          this.txtAreaInitContent();
        } //clear html on paste from external editors


        this.$body.bind('keydown', $.proxy(function (e) {
          if (e.which == 86 && (e.ctrlKey == true || e.metaKey == true) || e.which == 45 && (e.shiftKey == true || e.metaKey == true)) {
            if (!this.$pasteBlock) {
              this.saveRange();
              this.$pasteBlock = $(this.elFromString("<div style=\"opacity:0;\" contenteditable=\"true\">\uFEFF</div>"));
              this.$pasteBlock.appendTo(this.body); //if (!$.support.search?type=2) {this.$pasteBlock.focus();} //IE 7,8 FIX

              setTimeout($.proxy(function () {
                this.clearPaste(this.$pasteBlock);
                var rdata = '<span>' + this.$pasteBlock.html() + '</span>';
                this.$body.attr("contentEditable", "true");
                this.$pasteBlock.blur().remove();
                this.body.focus();

                if (this.cleartext) {
                  $.log("Check if paste to clearText Block");

                  if (this.isInClearTextBlock()) {
                    rdata = this.toBB(rdata).replace(/\n/g, "<br/>").replace(/\s{3}/g, '<span class="wbbtab"></span>');
                  }
                }

                rdata = rdata.replace(/\t/g, '<span class="wbbtab"></span>');
                this.selectRange(this.lastRange);
                this.insertAtCursor(rdata, false);
                this.lastRange = false;
                this.$pasteBlock = false;
              }, this), 1);
              this.selectNode(this.$pasteBlock[0]);
            }

            return true;
          }
        }, this)); //insert BR on press enter

        this.$body.bind('keydown', $.proxy(function (e) {
          if (e.which == 13) {
            var isLi = this.isContain(this.getSelectNode(), 'li');

            if (!isLi) {
              if (e.preventDefault) {
                e.preventDefault();
              }

              this.checkForLastBR(this.getSelectNode());
              this.insertAtCursor('<br/>', false);
            }
          }
        }, this)); //tabInsert

        if (this.options.tabInsert === true) {
          this.$body.bind('keydown', $.proxy(this.pressTab, this));
        } //add event listeners


        this.$body.bind('mouseup keyup', $.proxy(this.updateUI, this));
        this.$body.bind('mousedown', $.proxy(function (e) {
          this.clearLastRange();
          this.checkForLastBR(e.target);
        }, this)); //trace Textarea

        if (this.options.traceTextarea === true) {
          $(document).bind("mousedown", $.proxy(this.traceTextareaEvent, this));
          this.$txtArea.val("");
        } //attach hotkeys


        if (this.options.hotkeys === true) {
          this.$body.bind('keydown', $.proxy(this.presskey, this));
        } //smileConversion


        if (this.options.smileConversion === true) {
          this.$body.bind('keyup', $.proxy(this.smileConversion, this));
        }

        this.inited = true; //create resize lines

        if (this.options.autoresize === true) {
          this.$bresize = $(this.elFromString('<div class="bottom-resize-line"></div>')).appendTo(this.$editor).wdrag({
            scope: this,
            axisY: true,
            height: height
          });
        }

        this.imgListeners();
      } //this.$editor.append('<span class="powered">Powered by <a href="http://www.wysibb.com" target="_blank">WysiBB<a/></span>');
      //add event listeners to textarea


      this.$txtArea.bind('mouseup keyup', $.proxy(function () {
        clearTimeout(this.uitimer);
        this.uitimer = setTimeout($.proxy(this.updateUI, this), 100);
      }, this)); //attach hotkeys

      if (this.options.hotkeys === true) {
        $(document).bind('keydown', $.proxy(this.presskey, this));
      }
    },
    buildToolbar: function buildToolbar() {
      if (this.options.toolbar === false) {
        return false;
      } //this.$toolbar = $('<div class="wysibb-toolbar">').prependTo(this.$editor);


      this.$toolbar = $('<div>').addClass("wysibb-toolbar").prependTo(this.$editor);
      var $btnContainer;
      $.each(this.options.buttons, $.proxy(function (i, bn) {
        var opt = this.options.allButtons[bn];

        if (i == 0 || bn == "|" || bn == "-") {
          if (bn == "-") {
            this.$toolbar.append("<div>");
          }

          $btnContainer = $('<div class="wysibb-toolbar-container">').appendTo(this.$toolbar);
        }

        if (opt) {
          if (opt.type == "colorpicker") {
            this.buildColorpicker($btnContainer, bn, opt);
          } else if (opt.type == "table") {
            this.buildTablepicker($btnContainer, bn, opt);
          } else if (opt.type == "select") {
            this.buildSelect($btnContainer, bn, opt);
          } else if (opt.type == "smilebox") {
            this.buildSmilebox($btnContainer, bn, opt);
          } else {
            this.buildButton($btnContainer, bn, opt);
          }
        }
      }, this)); //fix for hide tooltip on quick mouse over

      this.$toolbar.find(".btn-tooltip").hover(function () {
        $(this).parent().css("overflow", "hidden");
      }, function () {
        $(this).parent().css("overflow", "visible");
      }); //build bbcode switch button
      //var $bbsw = $('<div class="wysibb-toolbar-container modeSwitch"><div class="wysibb-toolbar-btn" unselectable="on"><span class="btn-inner ve-tlb-bbcode" unselectable="on"></span></div></div>').appendTo(this.$toolbar);

      var $bbsw = $(document.createElement('div')).addClass("wysibb-toolbar-container modeSwitch").html('<div class="wysibb-toolbar-btn mswitch" unselectable="on"><span class="btn-inner modesw" unselectable="on">[bbcode]</span></div>').appendTo(this.$toolbar);

      if (this.options.bbmode == true) {
        $bbsw.children(".wysibb-toolbar-btn").addClass("on");
      }

      if (this.options.onlyBBmode === false) {
        $bbsw.children(".wysibb-toolbar-btn").click($.proxy(function (e) {
          $(e.currentTarget).toggleClass("on");
          this.modeSwitch();
        }, this));
      }
    },
    buildButton: function buildButton(container, bn, opt) {
      if (_typeof(container) != "object") {
        container = this.$toolbar;
      }

      var btnHTML = opt.buttonHTML ? $(this.strf(opt.buttonHTML, this.options)).addClass("btn-inner") : this.strf('<span class="btn-inner btn-text">{text}</span>', {
        text: opt.buttonText.replace(/</g, "&lt;")
      });
      var hotkey = this.options.hotkeys === true && this.options.showHotkeys === true && opt.hotkey ? ' <span class="tthotkey">[' + opt.hotkey + ']</span>' : "";
      var $btn = $('<div class="wysibb-toolbar-btn wbb-' + bn + '">').appendTo(container).append(btnHTML).append(this.strf('<span class="btn-tooltip">{title}<ins/>{hotkey}</span>', {
        title: opt.title,
        hotkey: hotkey
      })); //attach events

      this.controllers.push($btn);
      $btn.bind('queryState', $.proxy(function (e) {
        this.queryState(bn) ? $(e.currentTarget).addClass("on") : $(e.currentTarget).removeClass("on");
      }, this));
      $btn.mousedown($.proxy(function (e) {
        e.preventDefault();
        this.execCommand(bn, opt.exvalue || false);
        $(e.currentTarget).trigger('queryState');
      }, this));
    },
    buildColorpicker: function buildColorpicker(container, bn, opt) {
      var $btn = $('<div class="wysibb-toolbar-btn wbb-dropdown wbb-cp">').appendTo(container).append("<div class=\"ve-tlb-colorpick\"><span class=\"fonticon\">\uE010</span><span class=\"cp-line\"></span></div><ins class=\"fonticon ar\">\uE011</ins>").append(this.strf('<span class="btn-tooltip">{title}<ins/></span>', {
        title: opt.title
      }));
      var $cpline = $btn.find(".cp-line");
      var $dropblock = $('<div class="wbb-list">').appendTo($btn);
      $dropblock.append('<div class="nc">' + CURLANG.auto + '</div>');
      var colorlist = opt.colors ? opt.colors.split(",") : [];

      for (var j = 0; j < colorlist.length; j++) {
        colorlist[j] = $.trim(colorlist[j]);

        if (colorlist[j] == "-") {
          //insert padding
          $dropblock.append('<span class="pl"></span>');
        } else {
          $dropblock.append(this.strf('<div class="sc" style="background:{color}" title="{color}"></div>', {
            color: colorlist[j]
          }));
        }
      }

      var basecolor = $(document.body).css("color"); //attach events

      this.controllers.push($btn);
      $btn.bind('queryState', $.proxy(function (e) {
        //queryState
        $cpline.css("background-color", basecolor);
        var r = this.queryState(bn, true);

        if (r) {
          $cpline.css("background-color", this.options.bbmode ? r.color : r);
          $btn.find(".ve-tlb-colorpick span.fonticon").css("color", this.options.bbmode ? r.color : r);
        }
      }, this));
      $btn.mousedown($.proxy(function (e) {
        e.preventDefault();
        this.dropdownclick(".wbb-cp", ".wbb-list", e);
      }, this));
      $btn.find(".sc").mousedown($.proxy(function (e) {
        e.preventDefault();
        this.selectLastRange();
        var c = $(e.currentTarget).attr("title");
        this.execCommand(bn, c);
        $btn.trigger('queryState');
      }, this));
      $btn.find(".nc").mousedown($.proxy(function (e) {
        e.preventDefault();
        this.selectLastRange();
        this.execCommand(bn, basecolor);
        $btn.trigger('queryState');
      }, this));
      $btn.mousedown(function (e) {
        if (e.preventDefault) e.preventDefault();
      });
    },
    buildTablepicker: function buildTablepicker(container, bn, opt) {
      var $btn = $('<div class="wysibb-toolbar-btn wbb-dropdown wbb-tbl">').appendTo(container).append("<span class=\"btn-inner fonticon ve-tlb-table1\">\uE00E</span><ins class=\"fonticon ar\">\uE011</ins>").append(this.strf('<span class="btn-tooltip">{title}<ins/></span>', {
        title: opt.title
      }));
      var $listblock = $('<div class="wbb-list">').appendTo($btn);
      var $dropblock = $('<div>').css({
        "position": "relative",
        "box-sizing": "border-box"
      }).appendTo($listblock);
      var rows = opt.rows || 10;
      var cols = opt.cols || 10;
      var allcount = rows * cols;
      $dropblock.css("height", rows * opt.cellwidth + 2 + "px");

      for (var j = 1; j <= cols; j++) {
        for (var h = 1; h <= rows; h++) {
          //var html = this.strf('<div class="tbl-sel" style="width:{width}px;height:{height}px;z-index:{zindex}" title="{row},{col}"></div>',{width: (j*opt.cellwidth),height: (h*opt.cellwidth),zindex: --allcount,row:h,col:j});
          var html = '<div class="tbl-sel" style="width:' + j * 100 / cols + '%;height:' + h * 100 / rows + '%;z-index:' + --allcount + '" title="' + h + ',' + j + '"></div>';
          $dropblock.append(html);
        }
      } //this.debug("Attach event on: tbl-sel");


      $btn.find(".tbl-sel").mousedown($.proxy(function (e) {
        e.preventDefault();
        var t = $(e.currentTarget).attr("title");
        var rc = t.split(",");
        var code = this.options.bbmode ? '[table]' : '<table class="wbb-table" cellspacing="5" cellpadding="0">';

        for (var i = 1; i <= rc[0]; i++) {
          code += this.options.bbmode ? ' [tr]\n' : '<tr>';

          for (var j = 1; j <= rc[1]; j++) {
            code += this.options.bbmode ? '  [td][/td]\n' : "<td>\uFEFF</td>";
          }

          code += this.options.bbmode ? '[/tr]\n' : '</tr>';
        }

        code += this.options.bbmode ? '[/table]' : '</table>';
        this.insertAtCursor(code);
      }, this)); //this.debug("END Attach event on: tbl-sel");

      $btn.mousedown($.proxy(function (e) {
        e.preventDefault();
        this.dropdownclick(".wbb-tbl", ".wbb-list", e);
      }, this));
    },
    buildSelect: function buildSelect(container, bn, opt) {
      var $btn = $('<div class="wysibb-toolbar-btn wbb-select wbb-' + bn + '">').appendTo(container).append(this.strf("<span class=\"val\">{title}</span><ins class=\"fonticon sar\">\uE012</ins>", opt)).append(this.strf('<span class="btn-tooltip">{title}<ins/></span>', {
        title: opt.title
      }));
      var $sblock = $('<div class="wbb-list">').appendTo($btn);
      var $sval = $btn.find("span.val");
      var olist = $.isArray(opt.options) ? opt.options : opt.options.split(",");
      var $selectbox = this.isMobile ? $("<select>").addClass("wbb-selectbox") : "";

      for (var i = 0; i < olist.length; i++) {
        var oname = olist[i];

        if (typeof oname == "string") {
          var option = this.options.allButtons[oname];

          if (option) {
            //$.log("create: "+oname);
            if (option.html) {
              $('<span>').addClass("option").attr("oid", oname).attr("cmdvalue", option.exvalue).appendTo($sblock).append(this.strf(option.html, {
                seltext: option.title
              }));
            } else {
              $sblock.append(this.strf('<span class="option" oid="' + oname + '" cmdvalue="' + option.exvalue + '">{title}</span>', option));
            } //SelectBox for mobile devices


            if (this.isMobile) {
              $selectbox.append($('<option>').attr("oid", oname).attr("cmdvalue", option.exvalue).append(option.title));
            }
          }
        } else {
          //build option list from array
          var params = {
            seltext: oname.title
          };
          params[opt.valueBBname] = oname.exvalue;
          $('<span>').addClass("option").attr("oid", bn).attr("cmdvalue", oname.exvalue).appendTo($sblock).append(this.strf(opt.html, params));

          if (this.isMobile) {
            $selectbox.append($('<option>').attr("oid", bn).attr("cmdvalue", oname.exvalue).append(oname.exvalue));
          }
        }
      } //$sblock.append($selectbox);


      if (this.isMobile) {
        $selectbox.appendTo(container);
        this.controllers.push($selectbox);
        $selectbox.bind('queryState', $.proxy(function (e) {
          //queryState
          $selectbox.find("option").each($.proxy(function (i, el) {
            var $el = $(el);
            var r = this.queryState($el.attr("oid"), true);
            var cmdvalue = $el.attr("cmdvalue");

            if (cmdvalue && r == $el.attr("cmdvalue") || !cmdvalue && r) {
              $el.prop("selected", true);
              return false;
            }
          }, this));
        }, this));
        $selectbox.change($.proxy(function (e) {
          e.preventDefault();
          var $o = $(e.currentTarget).find(":selected");
          var oid = $o.attr("oid");
          var cmdvalue = $o.attr("cmdvalue");
          var opt = this.options.allButtons[oid];
          this.execCommand(oid, opt.exvalue || cmdvalue || false);
          $(e.currentTarget).trigger('queryState');
        }, this));
      }

      this.controllers.push($btn);
      $btn.bind('queryState', $.proxy(function (e) {
        //queryState
        $sval.text(opt.title);
        $btn.find(".option.selected").removeClass("selected");
        $btn.find(".option").each($.proxy(function (i, el) {
          var $el = $(el);
          var r = this.queryState($el.attr("oid"), true);
          var cmdvalue = $el.attr("cmdvalue");

          if (cmdvalue && r == $el.attr("cmdvalue") || !cmdvalue && r) {
            $sval.text($el.text());
            $el.addClass("selected");
            return false;
          }
        }, this));
      }, this));
      $btn.mousedown($.proxy(function (e) {
        e.preventDefault();
        this.dropdownclick(".wbb-select", ".wbb-list", e);
      }, this));
      $btn.find(".option").mousedown($.proxy(function (e) {
        e.preventDefault();
        var oid = $(e.currentTarget).attr("oid");
        var cmdvalue = $(e.currentTarget).attr("cmdvalue");
        var opt = this.options.allButtons[oid];
        this.execCommand(oid, opt.exvalue || cmdvalue || false);
        $(e.currentTarget).trigger('queryState');
      }, this));
    },
    buildSmilebox: function buildSmilebox(container, bn, opt) {
      if (this.options.smileList && this.options.smileList.length > 0) {
        var $btnHTML = $(this.strf(opt.buttonHTML, opt)).addClass("btn-inner");
        var $btn = $('<div class="wysibb-toolbar-btn wbb-smilebox wbb-' + bn + '">').appendTo(container).append($btnHTML).append(this.strf('<span class="btn-tooltip">{title}<ins/></span>', {
          title: opt.title
        }));
        var $sblock = $('<div class="wbb-list">').appendTo($btn);

        if ($.isArray(this.options.smileList)) {
          $.each(this.options.smileList, $.proxy(function (i, sm) {
            $('<span>').addClass("smile").appendTo($sblock).append($(this.strf(sm.img, this.options)).attr("title", sm.title));
          }, this));
        }

        $btn.mousedown($.proxy(function (e) {
          e.preventDefault();
          this.dropdownclick(".wbb-smilebox", ".wbb-list", e);
        }, this));
        $btn.find('.smile').mousedown($.proxy(function (e) {
          e.preventDefault(); //this.selectLastRange();

          this.insertAtCursor(this.options.bbmode ? this.toBB($(e.currentTarget).html()) : $($(e.currentTarget).html()));
        }, this));
      }
    },
    updateUI: function updateUI(e) {
      if (!e || e.which >= 8 && e.which <= 46 || e.which > 90 || e.type == "mouseup") {
        $.each(this.controllers, $.proxy(function (i, $btn) {
          $btn.trigger('queryState');
        }, this));
      } //check for onlyClearText


      this.disNonActiveButtons();
    },
    initModal: function initModal() {
      this.$modal = $("#wbbmodal");

      if (this.$modal.length == 0) {
        $.log("Init modal");
        this.$modal = $('<div>').attr("id", "wbbmodal").prependTo(document.body).html('<div class="wbbm"><div class="wbbm-title"><span class="wbbm-title-text"></span><span class="wbbclose" title="' + CURLANG.close + '">×</span></div><div class="wbbm-content"></div><div class="wbbm-bottom"><button id="wbbm-submit" class="wbb-button">' + CURLANG.save + '</button><button id="wbbm-cancel" class="wbb-cancel-button">' + CURLANG.cancel + '</button><button id="wbbm-remove" class="wbb-remove-button">' + CURLANG.remove + '</button></div></div>').hide();
        this.$modal.find('#wbbm-cancel,.wbbclose').click($.proxy(this.closeModal, this));
        this.$modal.bind('click', $.proxy(function (e) {
          if ($(e.target).parents(".wbbm").length == 0) {
            this.closeModal();
          }
        }, this));
        $(document).bind("keydown", $.proxy(this.escModal, this)); //ESC key close modal
      }
    },
    initHotkeys: function initHotkeys() {
      $.log("initHotkeys");
      this.hotkeys = [];
      var klist = "0123456789       abcdefghijklmnopqrstuvwxyz";
      $.each(this.options.allButtons, $.proxy(function (cmd, opt) {
        if (opt.hotkey) {
          var keys = opt.hotkey.split("+");

          if (keys && keys.length >= 2) {
            var metasum = 0;
            var key = keys.pop();
            $.each(keys, function (i, k) {
              switch ($.trim(k.toLowerCase())) {
                case "ctrl":
                  {
                    metasum += 1;
                    break;
                  }

                case "shift":
                  {
                    metasum += 4;
                    break;
                  }

                case "alt":
                  {
                    metasum += 7;
                    break;
                  }
              }
            }); //$.log("metasum: "+metasum+" key: "+key+" code: "+(klist.indexOf(key)+48));

            if (metasum > 0) {
              if (!this.hotkeys["m" + metasum]) {
                this.hotkeys["m" + metasum] = [];
              }

              this.hotkeys["m" + metasum]["k" + (klist.indexOf(key) + 48)] = cmd;
            }
          }
        }
      }, this));
    },
    presskey: function presskey(e) {
      if (e.ctrlKey == true || e.shiftKey == true || e.altKey == true) {
        var metasum = (e.ctrlKey == true ? 1 : 0) + (e.shiftKey == true ? 4 : 0) + (e.altKey == true ? 7 : 0);

        if (this.hotkeys["m" + metasum] && this.hotkeys["m" + metasum]["k" + e.which]) {
          this.execCommand(this.hotkeys["m" + metasum]["k" + e.which], false);
          e.preventDefault();
          return false;
        }
      }
    },
    //COgdfMMAND FUNCTIONS
    execCommand: function execCommand(command, value) {
      $.log("execCommand: " + command);
      var opt = this.options.allButtons[command];

      if (opt.en !== true) {
        return false;
      }

      var queryState = this.queryState(command, value); //check for onlyClearText

      var skipcmd = this.isInClearTextBlock();

      if (skipcmd && skipcmd != command) {
        return;
      }

      if (opt.excmd) {
        //use NativeCommand
        if (this.options.bbmode) {
          $.log("Native command in bbmode: " + command);

          if (queryState && opt.subInsert != true) {
            //remove bbcode
            this.wbbRemoveCallback(command, value);
          } else {
            //insert bbcode
            var v = {};

            if (opt.valueBBname && value) {
              v[opt.valueBBname] = value;
            }

            this.insertAtCursor(this.getBBCodeByCommand(command, v));
          }
        } else {
          this.execNativeCommand(opt.excmd, value || false);
        }
      } else if (!opt.cmd) {
        //wbbCommand
        //this.wbbExecCommand(command,value,queryState,$.proxy(this.wbbInsertCallback,this),$.proxy(this.wbbRemoveCallback,this));
        this.wbbExecCommand.call(this, command, value, queryState);
      } else {
        //user custom command
        opt.cmd.call(this, command, value, queryState);
      }

      this.updateUI();
    },
    queryState: function queryState(command, withvalue) {
      var opt = this.options.allButtons[command];

      if (opt.en !== true) {
        return false;
      } //if (opt.subInsert===true && opt.type!="colorpicker") {return false;}


      if (this.options.bbmode) {
        //bbmode
        if (opt.bbSelector) {
          for (var i = 0; i < opt.bbSelector.length; i++) {
            var b = this.isBBContain(opt.bbSelector[i]);

            if (b) {
              return this.getParams(b, opt.bbSelector[i], b[1]);
            }
          }
        }

        return false;
      } else {
        if (opt.excmd) {
          //native command
          if (withvalue) {
            try {
              //Firefox fix
              var v = (document.queryCommandValue(opt.excmd) + "").replace(/\'/g, "");

              if (opt.excmd == "foreColor") {
                v = this.rgbToHex(v);
              } //return (v==value);


              return v;
            } catch (e) {
              return false;
            }
          } else {
            try {
              //Firefox fix, exception while get queryState for UnorderedList
              if ((opt.excmd == "bold" || opt.excmd == "italic" || opt.excmd == "underline" || opt.excmd == "strikeThrough") && $(this.getSelectNode()).is("img")) {
                //Fix, when img selected
                return false;
              } else if (opt.excmd == "underline" && $(this.getSelectNode()).closest("a").length > 0) {
                //fix, when link select
                return false;
              }

              return document.queryCommandState(opt.excmd);
            } catch (e) {
              return false;
            }
          }
        } else {
          //custom command
          if ($.isArray(opt.rootSelector)) {
            for (var i = 0; i < opt.rootSelector.length; i++) {
              var n = this.isContain(this.getSelectNode(), opt.rootSelector[i]);

              if (n) {
                return this.getParams(n, opt.rootSelector[i]);
              }
            }
          }

          return false;
        }
      }
    },
    wbbExecCommand: function wbbExecCommand(command, value, queryState) {
      //default command for custom bbcodes
      $.log("wbbExecCommand");
      var opt = this.options.allButtons[command];

      if (opt) {
        if (opt.modal) {
          if ($.isFunction(opt.modal)) {
            //custom modal function
            //opt.modal(command,opt.modal,queryState,new clbk(this));
            opt.modal.call(this, command, opt.modal, queryState);
          } else {
            this.showModal.call(this, command, opt.modal, queryState);
          }
        } else {
          if (queryState && opt.subInsert != true) {
            //remove formatting
            //removeCallback(command,value);
            this.wbbRemoveCallback(command);
          } else {
            //insert format
            if (opt.groupkey) {
              var groupsel = this.options.groups[opt.groupkey];

              if (groupsel) {
                var snode = this.getSelectNode();
                $.each(groupsel, $.proxy(function (i, sel) {
                  var is = this.isContain(snode, sel);

                  if (is) {
                    var $sp = $('<span>').html(is.innerHTML);
                    var id = this.setUID($sp);
                    $(is).replaceWith($sp);
                    this.selectNode(this.$editor.find("#" + id)[0]);
                    return false;
                  }
                }, this));
              }
            }

            this.wbbInsertCallback(command, value);
          }
        }
      }
    },
    wbbInsertCallback: function wbbInsertCallback(command, paramobj) {
      if (_typeof(paramobj) != "object") {
        paramobj = {};
      }

      ;
      $.log("wbbInsertCallback: " + command);
      var data = this.getCodeByCommand(command, paramobj);
      this.insertAtCursor(data);

      if (this.seltextID && data.indexOf(this.seltextID) != -1) {
        var snode = this.$body.find("#" + this.seltextID)[0];
        this.selectNode(snode);
        $(snode).removeAttr("id");
        this.seltextID = false;
      }
    },
    wbbRemoveCallback: function wbbRemoveCallback(command, clear) {
      $.log("wbbRemoveCallback: " + command);
      var opt = this.options.allButtons[command];

      if (this.options.bbmode) {
        //bbmode
        //REMOVE BBCODE
        var pos = this.getCursorPosBB();
        var stextnum = 0;
        $.each(opt.bbSelector, $.proxy(function (i, bbcode) {
          var stext = bbcode.match(/\{[\s\S]+?\}/g);
          $.each(stext, function (n, s) {
            if (s.toLowerCase() == "{seltext}") {
              stextnum = n;
              return false;
            }
          });
          var a = this.isBBContain(bbcode);

          if (a) {
            this.txtArea.value = this.txtArea.value.substr(0, a[1]) + this.txtArea.value.substr(a[1], this.txtArea.value.length - a[1]).replace(a[0][0], clear === true ? '' : a[0][stextnum + 1]);
            this.setCursorPosBB(a[1]);
            return false;
          }
        }, this));
      } else {
        var node = this.getSelectNode();
        $.each(opt.rootSelector, $.proxy(function (i, s) {
          //$.log("RS: "+s);
          var root = this.isContain(node, s);

          if (!root) {
            return true;
          }

          var $root = $(root);
          var cs = this.options.rules[s][0][1];

          if ($root.is("span[wbb]") || !$root.is("span,font")) {
            //remove only blocks
            if (clear === true || !cs || !cs["seltext"]) {
              this.setCursorByEl($root);
              $root.remove();
            } else {
              if (cs && cs["seltext"] && cs["seltext"]["sel"]) {
                var htmldata = $root.find(cs["seltext"]["sel"]).html();

                if (opt.onlyClearText === true) {
                  htmldata = this.getHTML(htmldata, true, true);
                  htmldata = htmldata.replace(/\&#123;/g, "{").replace(/\&#125;/g, "}");
                }

                $root.replaceWith(htmldata);
              } else {
                var htmldata = $root.html();

                if (opt.onlyClearText === true) {
                  htmldata = this.getHTML(htmldata, true);
                  htmldata = htmldata.replace(/\&lt;/g, "<").replace(/\&gt;/g, ">").replace(/\&#123;/g, "{").replace(/\&#125;/g, "}");
                }

                $root.replaceWith(htmldata);
              }
            }

            return false;
          } else {
            //span,font - extract select content from this span,font
            var rng = this.getRange();
            var shtml = this.getSelectText();
            var rnode = this.getSelectNode();

            if (shtml == "") {
              shtml = "\uFEFF";
            } else {
              shtml = this.clearFromSubInsert(shtml, command);
            }

            var ins = this.elFromString(shtml);
            var before_rng = window.getSelection ? rng.cloneRange() : this.body.createTextRange();
            var after_rng = window.getSelection ? rng.cloneRange() : this.body.createTextRange();

            if (window.getSelection) {
              this.insertAtCursor('<span id="wbbdivide"></span>');
              var div = $root.find('span#wbbdivide').get(0);
              before_rng.setStart(root.firstChild, 0);
              before_rng.setEndBefore(div);
              after_rng.setStartAfter(div);
              after_rng.setEndAfter(root.lastChild);
            } else {
              before_rng.moveToElementText(root);
              after_rng.moveToElementText(root);
              before_rng.setEndPoint('EndToStart', rng);
              after_rng.setEndPoint('StartToEnd', rng);
            }

            var bf = this.getSelectText(false, before_rng);
            var af = this.getSelectText(false, after_rng);

            if (af != "") {
              var $af = $root.clone().html(af);
              $root.after($af);
            }

            if (clear !== true) $root.after(ins); //insert select html

            if (window.getSelection) {
              $root.html(bf);
              if (clear !== true) this.selectNode(ins);
            } else {
              $root.replaceWith(bf);
            }

            return false;
          }
        }, this));
      }
    },
    execNativeCommand: function execNativeCommand(cmd, param) {
      //$.log("execNativeCommand: '"+cmd+"' : "+param);
      this.body.focus(); //set focus to frame body

      if (cmd == "insertHTML" && !window.getSelection) {
        //IE does't support insertHTML
        var r = this.lastRange ? this.lastRange : document.selection.createRange(); //IE 7,8 range lost fix

        r.pasteHTML(param);
        var txt = $('<div>').html(param).text(); //for ie selection inside block

        var brsp = txt.indexOf("\uFEFF");

        if (brsp > -1) {
          r.moveStart('character', -1 * (txt.length - brsp));
          r.select();
        }

        this.lastRange = false;
      } else if (cmd == "insertHTML") {
        //fix webkit bug with insertHTML
        var sel = this.getSelection();
        var e = this.elFromString(param);
        var rng = this.lastRange ? this.lastRange : this.getRange();
        rng.deleteContents();
        rng.insertNode(e);
        rng.collapse(false);
        sel.removeAllRanges();
        sel.addRange(rng);
      } else {
        if (typeof param == "undefined") {
          param = false;
        }

        if (this.lastRange) {
          $.log("Last range select");
          this.selectLastRange();
        }

        document.execCommand(cmd, false, param);
      }
    },
    getCodeByCommand: function getCodeByCommand(command, paramobj) {
      return this.options.bbmode ? this.getBBCodeByCommand(command, paramobj) : this.getHTMLByCommand(command, paramobj);
    },
    getBBCodeByCommand: function getBBCodeByCommand(command, params) {
      if (!this.options.allButtons[command]) {
        return "";
      }

      if (typeof params == "undefined") {
        params = {};
      }

      params = this.keysToLower(params);

      if (!params["seltext"]) {
        //get selected text
        params["seltext"] = this.getSelectText(true);
      }

      var bbcode = this.options.allButtons[command].bbcode; //bbcode = this.strf(bbcode,params);

      bbcode = bbcode.replace(/\{(.*?)(\[.*?\])*\}/g, function (str, p, vrgx) {
        if (vrgx) {
          var vrgxp;

          if (vrgx) {
            vrgxp = new RegExp(vrgx + "+", "i");
          }

          if (typeof params[p.toLowerCase()] != "undefined" && params[p.toLowerCase()].toString().match(vrgxp) === null) {
            //not valid value
            return "";
          }
        }

        return typeof params[p.toLowerCase()] == "undefined" ? "" : params[p.toLowerCase()];
      }); //insert first with max params

      var rbbcode = null,
          maxpcount = 0;

      if (this.options.allButtons[command].transform) {
        var tr = [];
        $.each(this.options.allButtons[command].transform, function (html, bb) {
          tr.push(bb);
        });
        tr = this.sortArray(tr, -1);
        $.each(tr, function (i, v) {
          var valid = true,
              pcount = 0,
              pname = {};
          ;
          v = v.replace(/\{(.*?)(\[.*?\])*\}/g, function (str, p, vrgx) {
            var vrgxp;
            p = p.toLowerCase();

            if (vrgx) {
              vrgxp = new RegExp(vrgx + "+", "i");
            }

            if (typeof params[p.toLowerCase()] == "undefined" || vrgx && params[p.toLowerCase()].toString().match(vrgxp) === null) {
              valid = false;
            }

            ;

            if (typeof params[p] != "undefined" && !pname[p]) {
              pname[p] = 1;
              pcount++;
            }

            return typeof params[p.toLowerCase()] == "undefined" ? "" : params[p.toLowerCase()];
          });

          if (valid && pcount > maxpcount) {
            rbbcode = v;
            maxpcount = pcount;
          }
        });
      }

      return rbbcode || bbcode;
    },
    getHTMLByCommand: function getHTMLByCommand(command, params) {
      if (!this.options.allButtons[command]) {
        return "";
      }

      params = this.keysToLower(params);

      if (typeof params == "undefined") {
        params = {};
      }

      if (!params["seltext"]) {
        //get selected text
        params["seltext"] = this.getSelectText(false); //$.log("seltext: '"+params["seltext"]+"'");

        if (params["seltext"] == "") {
          params["seltext"] = "\uFEFF";
        } else {
          //clear selection from current command tags
          params["seltext"] = this.clearFromSubInsert(params["seltext"], command); //toBB if params onlyClearText=true

          if (this.options.allButtons[command].onlyClearText === true) {
            params["seltext"] = this.toBB(params["seltext"]).replace(/\</g, "&lt;").replace(/\n/g, "<br/>").replace(/\s{3}/g, '<span class="wbbtab"></span>');
          }
        }
      }

      var postsel = "";
      this.seltextID = "wbbid_" + ++this.lastid;

      if (command != "link" && command != "img") {
        params["seltext"] = '<span id="' + this.seltextID + '">' + params["seltext"] + '</span>'; //use for select seltext
      } else {
        postsel = '<span id="' + this.seltextID + "\">\uFEFF</span>";
      }

      var html = this.options.allButtons[command].html;
      html = html.replace(/\{(.*?)(\[.*?\])*\}/g, function (str, p, vrgx) {
        if (vrgx) {
          var vrgxp = new RegExp(vrgx + "+", "i");

          if (typeof params[p.toLowerCase()] != "undefined" && params[p.toLowerCase()].toString().match(vrgxp) === null) {
            //not valid value
            return "";
          }
        }

        return typeof params[p.toLowerCase()] == "undefined" ? "" : params[p.toLowerCase()];
      }); //insert first with max params

      var rhtml = null,
          maxpcount = 0;

      if (this.options.allButtons[command].transform) {
        var tr = [];
        $.each(this.options.allButtons[command].transform, function (html, bb) {
          tr.push(html);
        });
        tr = this.sortArray(tr, -1);
        $.each(tr, function (i, v) {
          var valid = true,
              pcount = 0,
              pname = {};
          v = v.replace(/\{(.*?)(\[.*?\])*\}/g, function (str, p, vrgx) {
            var vrgxp;
            p = p.toLowerCase();

            if (vrgx) {
              vrgxp = new RegExp(vrgx + "+", "i");
            }

            if (typeof params[p] == "undefined" || vrgx && params[p].toString().match(vrgxp) === null) {
              valid = false;
            }

            ;

            if (typeof params[p] != "undefined" && !pname[p]) {
              pname[p] = 1;
              pcount++;
            }

            return typeof params[p] == "undefined" ? "" : params[p];
          });

          if (valid && pcount > maxpcount) {
            rhtml = v;
            maxpcount = pcount;
          }
        });
      }

      return (rhtml || html) + postsel;
    },
    //SELECTION FUNCTIONS
    getSelection: function getSelection() {
      if (window.getSelection) {
        return window.getSelection();
      } else if (document.selection) {
        return this.options.bbmode ? document.selection.createRange() : document.selection.createRange();
      }
    },
    getSelectText: function getSelectText(fromTxtArea, range) {
      if (fromTxtArea) {
        //return select text from textarea
        this.txtArea.focus();

        if ('selectionStart' in this.txtArea) {
          var l = this.txtArea.selectionEnd - this.txtArea.selectionStart;
          return this.txtArea.value.substr(this.txtArea.selectionStart, l);
        } else {
          //IE
          var r = document.selection.createRange();
          return r.text;
        }
      } else {
        //return select html from body
        this.body.focus();

        if (!range) {
          range = this.getRange();
        }

        ;

        if (window.getSelection) {
          //w3c
          if (range) {
            return $('<div>').append(range.cloneContents()).html();
          }
        } else {
          //ie
          return range.htmlText;
        }
      }

      return "";
    },
    getRange: function getRange() {
      if (window.getSelection) {
        var sel = this.getSelection();

        if (sel.getRangeAt && sel.rangeCount > 0) {
          return sel.getRangeAt(0);
        } else if (sel.anchorNode) {
          var range = this.options.bbmode ? document.createRange() : document.createRange();
          range.setStart(sel.anchorNode, sel.anchorOffset);
          range.setEnd(sel.focusNode, sel.focusOffset);
          return range;
        }
      } else {
        return this.options.bbmode === true ? document.selection.createRange() : document.selection.createRange();
      }
    },
    insertAtCursor: function insertAtCursor(code, forceBBMode) {
      if (typeof code != "string") {
        code = $("<div>").append(code).html();
      }

      if (this.options.bbmode && typeof forceBBMode == "undefined" || forceBBMode === true) {
        var clbb = code.replace(/.*(\[\/\S+?\])$/, "$1");
        var p = this.getCursorPosBB() + (code.indexOf(clbb) != -1 && code.match(/\[.*\]/) ? code.indexOf(clbb) : code.length);

        if (document.selection) {
          //IE
          this.txtArea.focus();
          this.getSelection().text = code;
        } else if (this.txtArea.selectionStart || this.txtArea.selectionStart == '0') {
          this.txtArea.value = this.txtArea.value.substring(0, this.txtArea.selectionStart) + code + this.txtArea.value.substring(this.txtArea.selectionEnd, this.txtArea.value.length);
        }

        if (p < 0) {
          p = 0;
        }

        this.setCursorPosBB(p);
      } else {
        this.execNativeCommand("insertHTML", code);
        var node = this.getSelectNode();

        if (!$(node).closest("table,tr,td")) {
          this.splitPrevNext(node);
        }
      }
    },
    getSelectNode: function getSelectNode(rng) {
      this.body.focus();

      if (!rng) {
        rng = this.getRange();
      }

      if (!rng) {
        return this.$body;
      } //return (window.getSelection) ? rng.commonAncestorContainer:rng.parentElement();


      var sn = window.getSelection ? rng.commonAncestorContainer : rng.parentElement();

      if ($(sn).is(".imgWrap")) {
        sn = $(sn).children("img")[0];
      }

      return sn;
    },
    getCursorPosBB: function getCursorPosBB() {
      var pos = 0;

      if ('selectionStart' in this.txtArea) {
        pos = this.txtArea.selectionStart;
      } else {
        this.txtArea.focus();
        var r = this.getRange();
        var rt = document.body.createTextRange();
        rt.moveToElementText(this.txtArea);
        rt.setEndPoint('EndToStart', r);
        pos = rt.text.length;
      }

      return pos;
    },
    setCursorPosBB: function setCursorPosBB(pos) {
      if (this.options.bbmode) {
        if (window.getSelection) {
          this.txtArea.selectionStart = pos;
          this.txtArea.selectionEnd = pos;
        } else {
          var range = this.txtArea.createTextRange();
          range.collapse(true);
          range.move('character', pos);
          range.select();
        }
      }
    },
    selectNode: function selectNode(node, rng) {
      if (!rng) {
        rng = this.getRange();
      }

      if (!rng) {
        return;
      }

      if (window.getSelection) {
        var sel = this.getSelection();
        rng.selectNodeContents(node);
        sel.removeAllRanges();
        sel.addRange(rng);
      } else {
        rng.moveToElementText(node);
        rng.select();
      }
    },
    selectRange: function selectRange(rng) {
      if (rng) {
        if (!window.getSelection) {
          rng.select();
        } else {
          var sel = this.getSelection();
          sel.removeAllRanges();
          sel.addRange(rng);
        }
      }
    },
    cloneRange: function cloneRange(rng) {
      if (rng) {
        if (!window.getSelection) {
          return rng.duplicate();
        } else {
          return rng.cloneRange();
        }
      }
    },
    getRangeClone: function getRangeClone() {
      return this.cloneRange(this.getRange());
    },
    saveRange: function saveRange() {
      this.setBodyFocus(); //this.lastRange=(this.options.bbmode) ? this.getCursorPosBB():this.getRangeClone();

      this.lastRange = this.getRangeClone();
    },
    selectLastRange: function selectLastRange() {
      if (this.lastRange) {
        this.body.focus();
        this.selectRange(this.lastRange);
        this.lastRange = false;
      }
    },
    setBodyFocus: function setBodyFocus() {
      $.log("Set focus to WysiBB editor");

      if (this.options.bbmode) {
        if (!this.$txtArea.is(":focus")) {
          this.$txtArea.focus();
        }
      } else {
        if (!this.$body.is(":focus")) {
          this.$body.focus();
        }
      }
    },
    clearLastRange: function clearLastRange() {
      this.lastRange = false;
    },
    //TRANSFORM FUNCTIONS
    filterByNode: function filterByNode(node) {
      var $n = $(node);
      var tagName = $n.get(0).tagName.toLowerCase();
      var filter = tagName;
      var attributes = this.getAttributeList($n.get(0));
      $.each(attributes, $.proxy(function (i, item) {
        var v = $n.attr(item);
        /* $.log("v: "+v);
        if ($.inArray(item,this.options.attrWrap)!=-1) {
        item = '_'+item;
        } */
        //$.log(item);

        if (item.substr(0, 1) == "_") {
          item = item.substr(1, item.length);
        }

        if (v && !v.match(/\{.*?\}/)) {
          //$.log("I1: "+item);
          if (item == "style") {
            var v = $n.attr(item);
            var va = v.split(";");
            $.each(va, function (i, f) {
              if (f && f.length > 0) {
                filter += '[' + item + '*="' + $.trim(f) + '"]';
              }
            });
          } else {
            filter += '[' + item + '="' + v + '"]';
          }
        } else if (v && item == "style") {
          //$.log("I2: "+item);
          var vf = v.substr(0, v.indexOf("{"));

          if (vf && vf != "") {
            var v = v.substr(0, v.indexOf("{"));
            var va = v.split(";");
            $.each(va, function (i, f) {
              filter += '[' + item + '*="' + f + '"]';
            }); //filter+='['+item+'*="'+v.substr(0,v.indexOf("{"))+'"]';
          }
        } else {
          //1.2.2
          //$.log("I3: "+item);
          filter += '[' + item + ']';
        }
      }, this)); //index

      var idx = $n.parent().children(filter).index($n);

      if (idx > 0) {
        filter += ":eq(" + $n.index() + ")";
      }

      return filter;
    },
    relFilterByNode: function relFilterByNode(node, stop) {
      var p = "";
      $.each(this.options.attrWrap, function (i, a) {
        stop = stop.replace('[' + a, '[_' + a);
      });

      while (node && node.tagName != "BODY" && !$(node).is(stop)) {
        p = this.filterByNode(node) + " " + p;

        if (node) {
          node = node.parentNode;
        }
      }

      return p;
    },
    getRegexpReplace: function getRegexpReplace(str, validname) {
      str = str.replace(/(\(|\)|\[|\]|\.|\*|\?|\:|\\)/g, "\\$1").replace(/\s+/g, "\\s+").replace(validname.replace(/(\(|\)|\[|\]|\.|\*|\?|\:|\\)/g, "\\$1"), "(.+)").replace(/\{\S+?\}/g, ".*");
      return str;
    },
    getBBCode: function getBBCode() {
      if (!this.options.rules) {
        return this.$txtArea.val();
      }

      if (this.options.bbmode) {
        return this.$txtArea.val();
      }

      this.clearEmpty();
      this.removeLastBodyBR();
      return this.toBB(this.$body.html());
    },
    toBB: function toBB(data) {
      if (!data) {
        return "";
      }

      ;
      var $e = typeof data == "string" ? $('<span>').html(data) : $(data); //remove last BR

      $e.find("div,blockquote,p").each(function () {
        if (this.nodeType != 3 && this.lastChild && this.lastChild.tagName == "BR") {
          $(this.lastChild).remove();
        }
      });

      if ($e.is("div,blockquote,p") && $e[0].nodeType != 3 && $e[0].lastChild && $e[0].lastChild.tagName == "BR") {
        $($e[0].lastChild).remove();
      } //END remove last BR
      //Remove BR


      $e.find("ul > br, table > br, tr > br").remove(); //IE

      var outbb = ""; //transform smiles

      $.each(this.options.srules, $.proxy(function (s, bb) {
        $e.find(s).replaceWith(bb[0]);
      }, this));
      $e.contents().each($.proxy(function (i, el) {
        var $el = $(el);

        if (el.nodeType === 3) {
          outbb += el.data.replace(/\n+/, "").replace(/\t/g, "   ");
        } else {
          //process html tag
          var rpl,
              processed = false; //for (var rootsel in this.options.rules) {

          for (var j = 0; j < this.rsellist.length; j++) {
            var rootsel = this.rsellist[j];

            if ($el && $el.is(rootsel)) {
              //it is root sel
              var rlist = this.options.rules[rootsel];

              for (var i = 0; i < rlist.length; i++) {
                var bbcode = rlist[i][0];
                var crules = rlist[i][1];
                var skip = false,
                    keepElement = false,
                    keepAttr = false;

                if (!$el.is("br")) {
                  bbcode = bbcode.replace(/\n/g, "<br>");
                }

                bbcode = bbcode.replace(/\{(.*?)(\[.*?\])*\}/g, $.proxy(function (str, s, vrgx) {
                  var c = crules[s.toLowerCase()]; //if (typeof(c)=="undefined") {$.log("Param: {"+s+"} not found in HTML representation.");skip=true;return s;}

                  if (typeof c == "undefined") {
                    $.log("Param: {" + s + "} not found in HTML representation.");
                    skip = true;
                  }

                  var $cel = c.sel ? $(el).find(c.sel) : $(el);

                  if (c.attr && !$cel.attr(c.attr)) {
                    skip = true;
                    return s;
                  } //skip if needed attribute not present, maybe other bbcode


                  var cont = c.attr ? $cel.attr(c.attr) : $cel.html();

                  if (typeof cont == "undefined" || cont == null) {
                    skip = true;
                    return s;
                  }

                  var regexp = c.rgx; //style fix

                  if (regexp && c.attr == "style" && regexp.substr(regexp.length - 1, 1) != ";") {
                    regexp += ";";
                  }

                  if (c.attr == "style" && cont && cont.substr(cont.length - 1, 1) != ";") {
                    cont += ";";
                  } //prepare regexp


                  var rgx = regexp ? new RegExp(regexp, "") : false;

                  if (rgx) {
                    if (cont.match(rgx)) {
                      var m = cont.match(rgx);

                      if (m && m.length == 2) {
                        cont = m[1];
                      }
                    } else {
                      cont = "";
                    }
                  } //if it is style attr, then keep tag alive, remove this style


                  if (c.attr && skip === false) {
                    if (c.attr == "style") {
                      keepElement = true;
                      var nstyle = "";
                      var r = c.rgx.replace(/^\.\*\?/, "").replace(/\.\*$/, "").replace(/;$/, "");
                      $($cel.attr("style").split(";")).each(function (idx, style) {
                        if (style && style != "") {
                          if (!style.match(r)) {
                            nstyle += style + ";";
                          }
                        }
                      });

                      if (nstyle == "") {
                        $cel.removeAttr("style");
                      } else {
                        $cel.attr("style", nstyle);
                      }
                    } else if (c.rgx === false) {
                      keepElement = true;
                      keepAttr = true;
                      $cel.removeAttr(c.attr);
                    }
                  }

                  if ($el.is('table,tr,td,font')) {
                    keepElement = true;
                  }

                  return cont || "";
                }, this));

                if (skip) {
                  continue;
                }

                if ($el.is("img,br,hr")) {
                  //replace element
                  outbb += bbcode;
                  $el = null;
                  break;
                } else {
                  if (keepElement && !$el.attr("notkeep")) {
                    if ($el.is("table,tr,td")) {
                      bbcode = this.fixTableTransform(bbcode);
                      outbb += this.toBB($('<span>').html(bbcode));
                      $el = null;
                    } else {
                      $el.empty().html('<span>' + bbcode + '</span>');
                    }
                  } else {
                    if ($el.is("iframe")) {
                      outbb += bbcode;
                    } else {
                      $el.empty().html(bbcode);
                      outbb += this.toBB($el);
                      $el = null;
                    }

                    break;
                  }
                }
              }
            }
          }

          if (!$el || $el.is("iframe,img")) {
            return true;
          }

          outbb += this.toBB($el);
        }
      }, this));
      outbb.replace(/\uFEFF/g, "");
      return outbb;
    },
    getHTML: function getHTML(bbdata, init, skiplt) {
      if (!this.options.bbmode && !init) {
        return this.$body.html();
      }

      if (!skiplt) {
        bbdata = bbdata.replace(/</g, "&lt;").replace(/\{/g, "&#123;").replace(/\}/g, "&#125;");
      }

      bbdata = bbdata.replace(/\[code\]([\s\S]*?)\[\/code\]/g, function (s) {
        s = s.substr("[code]".length, s.length - "[code]".length - "[/code]".length).replace(/\[/g, "&#91;").replace(/\]/g, "&#93;");
        return "[code]" + s + "[/code]";
      });
      $.each(this.options.btnlist, $.proxy(function (i, b) {
        if (b != "|" && b != "-") {
          var find = true;

          if (!this.options.allButtons[b] || !this.options.allButtons[b].transform) {
            return true;
          }

          $.each(this.options.allButtons[b].transform, $.proxy(function (html, bb) {
            html = html.replace(/\n/g, ""); //IE 7,8 FIX

            var a = [];
            bb = bb.replace(/(\(|\)|\[|\]|\.|\*|\?|\:|\\|\\)/g, "\\$1"); //.replace(/\s/g,"\\s");

            bb = bb.replace(/\{(.*?)(\\\[.*?\\\])*\}/gi, $.proxy(function (str, s, vrgx) {
              a.push(s);

              if (vrgx) {
                //has validation regexp
                vrgx = vrgx.replace(/\\/g, "");
                return "(" + vrgx + "*?)";
              }

              return "([\\s\\S]*?)";
            }, this));
            var n = 0,
                am;

            while ((am = new RegExp(bb, "mgi").exec(bbdata)) != null) {
              if (am) {
                var r = {};
                $.each(a, $.proxy(function (i, k) {
                  r[k] = am[i + 1];
                }, this));
                var nhtml = html;
                nhtml = nhtml.replace(/\{(.*?)(\[.*?\])\}/g, "{$1}");
                nhtml = this.strf(nhtml, r);
                bbdata = bbdata.replace(am[0], nhtml);
              }
            }
          }, this));
        }
      }, this)); //transform system codes

      $.each(this.options.systr, function (html, bb) {
        bb = bb.replace(/(\(|\)|\[|\]|\.|\*|\?|\:|\\|\\)/g, "\\$1").replace(" ", "\\s");
        bbdata = bbdata.replace(new RegExp(bb, "g"), html);
      });
      var $wrap = $(this.elFromString("<div>" + bbdata + "</div>")); //transform smiles

      /* $wrap.contents().filter(function() {return this.nodeType==3}).each($.proxy(smilerpl,this)).end().find("*").contents().filter(function() {return this.nodeType==3}).each($.proxy(smilerpl,this));
      function smilerpl(i,el) {
      var ndata = el.data;
      $.each(this.options.smileList,$.proxy(function(i,row) {
      var fidx = ndata.indexOf(row.bbcode);
      if (fidx!=-1) {
      var afternode_txt = ndata.substring(fidx+row.bbcode.length,ndata.length);
      var afternode = document.createTextNode(afternode_txt);
      el.data = ndata = el.data.substr(0,fidx);
      $(el).after(afternode).after(this.strf(row.img,this.options));
      }
      },this));
      } */

      this.getHTMLSmiles($wrap); //$wrap.contents().filter(function() {return this.nodeType==3}).each($.proxy(this,smileRPL,this));

      return $wrap.html();
    },
    getHTMLSmiles: function getHTMLSmiles(rel) {
      $(rel).contents().filter(function () {
        return this.nodeType == 3;
      }).each($.proxy(this.smileRPL, this));
    },
    smileRPL: function smileRPL(i, el) {
      var ndata = el.data;
      $.each(this.options.smileList, $.proxy(function (i, row) {
        var fidx = ndata.indexOf(row.bbcode);

        if (fidx != -1) {
          var afternode_txt = ndata.substring(fidx + row.bbcode.length, ndata.length);
          var afternode = document.createTextNode(afternode_txt);
          el.data = ndata = el.data.substr(0, fidx);
          $(el).after(afternode).after(this.strf(row.img, this.options));
          this.getHTMLSmiles(el.parentNode);
          return false;
        }

        this.getHTMLSmiles(el);
      }, this));
    },
    //UTILS
    setUID: function setUID(el, attr) {
      var id = "wbbid_" + ++this.lastid;

      if (el) {
        $(el).attr(attr || "id", id);
      }

      return id;
    },
    keysToLower: function keysToLower(o) {
      $.each(o, function (k, v) {
        if (k != k.toLowerCase()) {
          delete o[k];
          o[k.toLowerCase()] = v;
        }
      });
      return o;
    },
    strf: function strf(str, data) {
      data = this.keysToLower($.extend({}, data));
      return str.replace(/\{([\w\.]*)\}/g, function (str, key) {
        key = key.toLowerCase();
        var keys = key.split("."),
            value = data[keys.shift().toLowerCase()];
        $.each(keys, function () {
          value = value[this];
        });
        return value === null || value === undefined ? "" : value;
      });
    },
    elFromString: function elFromString(str) {
      if (str.indexOf("<") != -1 && str.indexOf(">") != -1) {
        //create tag
        var wr = document.createElement("SPAN");
        $(wr).html(str);
        this.setUID(wr, "wbb");
        return $(wr).contents().length > 1 ? wr : wr.firstChild;
      } else {
        //create text node
        return document.createTextNode(str);
      }
    },
    isContain: function isContain(node, sel) {
      while (node && !$(node).hasClass("wysibb")) {
        if ($(node).is(sel)) {
          return node;
        }

        ;

        if (node) {
          node = node.parentNode;
        } else {
          return null;
        }
      }
    },
    isBBContain: function isBBContain(bbcode) {
      var pos = this.getCursorPosBB();
      var b = this.prepareRGX(bbcode);
      var bbrgx = new RegExp(b, "g");
      var a;
      var lastindex = 0;

      while ((a = bbrgx.exec(this.txtArea.value)) != null) {
        var p = this.txtArea.value.indexOf(a[0], lastindex);

        if (pos > p && pos < p + a[0].length) {
          return [a, p];
        }

        lastindex = p + 1;
      }
    },
    prepareRGX: function prepareRGX(r) {
      return r.replace(/(\[|\]|\)|\(|\.|\*|\?|\:|\||\\)/g, "\\$1").replace(/\{.*?\}/g, "([\\s\\S]*?)"); //return r.replace(/([^a-z0-9)/ig,"\\$1").replace(/\{.*?\}/g,"([\\s\\S]*?)");
    },
    checkForLastBR: function checkForLastBR(node) {
      if (!node) {
        $node = this.body;
      }

      if (node.nodeType == 3) {
        node = node.parentNode;
      }

      var $node = $(node);

      if ($node.is("span[id*='wbbid']")) {
        $node = $node.parent();
      }

      if (this.options.bbmode === false && $node.is('div,blockquote,code') && $node.contents().length > 0) {
        var l = $node[0].lastChild;

        if (!l || l && l.tagName != "BR") {
          $node.append("<br/>");
        }
      }

      if (this.$body.contents().length > 0 && this.body.lastChild.tagName != "BR") {
        this.$body.append('<br/>');
      }
    },
    getAttributeList: function getAttributeList(el) {
      var a = [];
      $.each(el.attributes, function (i, attr) {
        if (attr.specified) {
          a.push(attr.name);
        }
      });
      return a;
    },
    clearFromSubInsert: function clearFromSubInsert(html, cmd) {
      if (this.options.allButtons[cmd] && this.options.allButtons[cmd].rootSelector) {
        var $wr = $('<div>').html(html);
        $.each(this.options.allButtons[cmd].rootSelector, $.proxy(function (i, s) {
          var seltext = false;

          if (typeof this.options.rules[s][0][1]["seltext"] != "undefined") {
            seltext = this.options.rules[s][0][1]["seltext"]["sel"];
          }

          var res = true;
          $wr.find("*").each(function () {
            //work with find("*") and "is", becouse in ie7-8 find is case sensitive
            if ($(this).is(s)) {
              if (seltext && seltext["sel"]) {
                $(this).replaceWith($(this).find(seltext["sel"].toLowerCase()).html());
              } else {
                $(this).replaceWith($(this).html());
              }

              res = false;
            }
          });
          return res;
        }, this));
        return $wr.html();
      }

      return html;
    },
    splitPrevNext: function splitPrevNext(node) {
      if (node.nodeType == 3) {
        node = node.parentNode;
      }

      ;
      var f = this.filterByNode(node).replace(/\:eq.*$/g, "");

      if ($(node.nextSibling).is(f)) {
        $(node).append($(node.nextSibling).html());
        $(node.nextSibling).remove();
      }

      if ($(node.previousSibling).is(f)) {
        $(node).prepend($(node.previousSibling).html());
        $(node.previousSibling).remove();
      }
    },
    modeSwitch: function modeSwitch() {
      if (this.options.bbmode) {
        //to HTML
        this.$body.html(this.getHTML(this.$txtArea.val()));
        this.$txtArea.hide().removeAttr("wbbsync").val("");
        this.$body.css("min-height", this.$txtArea.height()).show().focus();
      } else {
        //to bbcode
        this.$txtArea.val(this.getBBCode()).css("min-height", this.$body.height());
        this.$body.hide();
        this.$txtArea.show().focus();
      }

      this.options.bbmode = !this.options.bbmode;
    },
    clearEmpty: function clearEmpty() {
      this.$body.children().filter(emptyFilter).remove();

      function emptyFilter() {
        if (!$(this).is("span,font,a,b,i,u,s")) {
          //clear empty only for span,font
          return false;
        }

        if (!$(this).hasClass("wbbtab") && $.trim($(this).html()).length == 0) {
          return true;
        } else if ($(this).children().length > 0) {
          $(this).children().filter(emptyFilter).remove();

          if ($(this).html().length == 0 && this.tagName != "BODY") {
            return true;
          }
        }
      }
    },
    dropdownclick: function dropdownclick(bsel, tsel, e) {
      //this.body.focus();
      var $btn = $(e.currentTarget).closest(bsel);

      if ($btn.hasClass("dis")) {
        return;
      }

      if ($btn.attr("wbbshow")) {
        //hide dropdown
        $btn.removeAttr("wbbshow");
        $(document).unbind("mousedown", this.dropdownhandler);

        if (document) {
          $(document).unbind("mousedown", this.dropdownhandler);
        }

        this.lastRange = false;
      } else {
        this.saveRange();
        this.$editor.find("*[wbbshow]").each(function (i, el) {
          $(el).removeClass("on").find($(el).attr("wbbshow")).hide().end().removeAttr("wbbshow");
        });
        $btn.attr("wbbshow", tsel);
        $(document.body).bind("mousedown", $.proxy(function (evt) {
          this.dropdownhandler($btn, bsel, tsel, evt);
        }, this));

        if (this.$body) {
          this.$body.bind("mousedown", $.proxy(function (evt) {
            this.dropdownhandler($btn, bsel, tsel, evt);
          }, this));
        }
      }

      $btn.find(tsel).toggle();
      $btn.toggleClass("on");
    },
    dropdownhandler: function dropdownhandler($btn, bsel, tsel, e) {
      if ($(e.target).parents(bsel).length == 0) {
        $btn.removeClass("on").find(tsel).hide();
        $(document).unbind('mousedown', this.dropdownhandler);

        if (this.$body) {
          this.$body.unbind('mousedown', this.dropdownhandler);
        }
      }
    },
    rgbToHex: function rgbToHex(rgb) {
      if (rgb.substr(0, 1) == '#') {
        return rgb;
      } //if (rgb.indexOf("rgb")==-1) {return rgb;}


      if (rgb.indexOf("rgb") == -1) {
        //IE
        var color = parseInt(rgb);
        color = (color & 0x0000ff) << 16 | color & 0x00ff00 | (color & 0xff0000) >>> 16;
        return '#' + color.toString(16);
      }

      var digits = /(.*?)rgb\((\d+),\s*(\d+),\s*(\d+)\)/.exec(rgb);
      return "#" + this.dec2hex(parseInt(digits[2])) + this.dec2hex(parseInt(digits[3])) + this.dec2hex(parseInt(digits[4]));
    },
    dec2hex: function dec2hex(d) {
      if (d > 15) {
        return d.toString(16);
      } else {
        return "0" + d.toString(16);
      }
    },
    sync: function sync() {
      if (this.options.bbmode) {
        this.$body.html(this.getHTML(this.txtArea.value, true));
      } else {
        this.$txtArea.attr("wbbsync", 1).val(this.getBBCode());
      }
    },
    clearPaste: function clearPaste(el) {
      var $block = $(el); //NEW

      $.each(this.options.rules, $.proxy(function (s, ar) {
        var $sf = $block.find(s).attr("wbbkeep", 1);

        if ($sf.length > 0) {
          var s2 = ar[0][1];
          $.each(s2, function (i, v) {
            if (v.sel) {
              $sf.find(v.sel).attr("wbbkeep", 1);
            }
          });
        }
      }, this));
      $block.find("*[wbbkeep!='1']").each($.proxy(function (i, el) {
        var $this = $(el);

        if ($this.is('div,p') && ($this.children().length == 0 || el.lastChild.tagName != "BR")) {
          $this.after("<br/>");
        }
      }, this));
      $block.find("*[wbbkeep]").removeAttr("wbbkeep").removeAttr("style");
      $.log($block.html()); //$.log("BBCODE: "+this.toBB($block.clone(true)));

      $block.html(this.getHTML(this.toBB($block), true));
      $.log($block.html()); //OLD

      /* $.each(this.options.rules,$.proxy(function(s,bb) {
      $block.find(s).attr("wbbkeep",1);
      },this));
      //replace div and p without last br to html()+br
      $block.find("*[wbbkeep!='1']").each($.proxy(function(i,el) {
      var $this = $(el);
      if ($this.is('div,p') && ($this.children().length==0 || el.lastChild.tagName!="BR")) {
      $this.after("<br/>").after($this.contents()).remove();
      }else{
      $this.after($this.contents()).remove();
      }
      },this));
      $block.find("*[wbbkeep]").removeAttr("wbbkeep").removeAttr("style"); */
    },
    sortArray: function sortArray(ar, asc) {
      ar.sort(function (a, b) {
        return (a.length - b.length) * (asc || 1);
      });
      return ar;
    },
    smileFind: function smileFind() {
      if (this.options.smilefind) {
        var $smlist = $(this.options.smilefind).find('img[alt]');

        if ($smlist.length > 0) {
          this.options.smileList = [];
          $smlist.each($.proxy(function (i, el) {
            var $el = $(el);
            this.options.smileList.push({
              title: $el.attr("title"),
              bbcode: $el.attr("alt"),
              img: $el.removeAttr("alt").removeAttr("title")[0].outerHTML
            });
          }, this));
        }
      }
    },
    destroy: function destroy() {
      this.$editor.replaceWith(this.$txtArea);
      this.$txtArea.removeClass("wysibb-texarea").show();
      this.$modal.remove();
      this.$txtArea.data("wbb", null);
    },
    pressTab: function pressTab(e) {
      if (e && e.which == 9) {
        //insert tab
        if (e.preventDefault) {
          e.preventDefault();
        }

        if (this.options.bbmode) {
          this.insertAtCursor('   ', false);
        } else {
          this.insertAtCursor("<span class=\"wbbtab\">\uFEFF</span>", false); //this.execNativeCommand("indent",false);
        }
      }
    },
    removeLastBodyBR: function removeLastBodyBR() {
      if (this.body.lastChild && this.body.lastChild.nodeType != 3 && this.body.lastChild.tagName == "BR") {
        this.body.removeChild(this.body.lastChild);
        this.removeLastBodyBR();
      }
    },
    traceTextareaEvent: function traceTextareaEvent(e) {
      if ($(e.target).closest("div.wysibb").length == 0) {
        if ($(document.activeElement).is("div.wysibb-body")) {
          this.saveRange();
        }

        setTimeout($.proxy(function () {
          var data = this.$txtArea.val();

          if (this.options.bbmode === false && data != "" && $(e.target).closest("div.wysibb").length == 0 && !this.$txtArea.attr("wbbsync")) {
            this.selectLastRange();
            this.insertAtCursor(this.getHTML(data, true));
            this.$txtArea.val("");
          }

          if ($(document.activeElement).is("div.wysibb-body")) {
            this.lastRange = false;
          }
        }, this), 100);
      }
    },
    txtAreaInitContent: function txtAreaInitContent() {
      //$.log(this.txtArea.value);
      this.$body.html(this.getHTML(this.txtArea.value, true));
    },
    getValidationRGX: function getValidationRGX(s) {
      if (s.match(/\[\S+\]/)) {
        return s.replace(/.*(\\*\[\S+\]).*/, "$1");
      }

      return "";
    },
    smileConversion: function smileConversion() {
      if (this.options.smileList && this.options.smileList.length > 0) {
        var snode = this.getSelectNode();

        if (snode.nodeType == 3) {
          var ndata = snode.data;

          if (ndata.length >= 2 && !this.isInClearTextBlock(snode) && $(snode).parents("a").length == 0) {
            $.each(this.options.srules, $.proxy(function (i, sar) {
              var smbb = sar[0];
              var fidx = ndata.indexOf(smbb);

              if (fidx != -1) {
                var afternode_txt = ndata.substring(fidx + smbb.length, ndata.length);
                var afternode = document.createTextNode(afternode_txt);
                var afternode_cursor = document.createElement("SPAN");
                snode.data = snode.data.substr(0, fidx);
                $(snode).after(afternode).after(afternode_cursor).after(this.strf(sar[1], this.options));
                this.selectNode(afternode_cursor);
                return false;
              }
            }, this));
          }
        }
      }
    },
    isInClearTextBlock: function isInClearTextBlock() {
      if (this.cleartext) {
        var find = false;
        $.each(this.cleartext, $.proxy(function (sel, command) {
          if (this.queryState(command)) {
            find = command;
            return false;
          }
        }, this));
        return find;
      }

      return false;
    },
    wrapAttrs: function wrapAttrs(html) {
      $.each(this.options.attrWrap, function (i, a) {
        html = html.replace(a + '="', '_' + a + '="');
      });
      return html;
    },
    unwrapAttrs: function unwrapAttrs(html) {
      $.each(this.options.attrWrap, function (i, a) {
        html = html.replace('_' + a + '="', a + '="');
      });
      return html;
    },
    disNonActiveButtons: function disNonActiveButtons() {
      if (this.isInClearTextBlock()) {
        this.$toolbar.find(".wysibb-toolbar-btn:not(.on,.mswitch)").addClass("dis");
      } else {
        this.$toolbar.find(".wysibb-toolbar-btn.dis").removeClass("dis");
      }
    },
    setCursorByEl: function setCursorByEl(el) {
      var sl = document.createTextNode("\uFEFF");
      $(el).after(sl);
      this.selectNode(sl);
    },
    //img listeners
    imgListeners: function imgListeners() {
      $(document).on("mousedown", $.proxy(this.imgEventHandler, this));
    },
    imgEventHandler: function imgEventHandler(e) {
      var $e = $(e.target);

      if (this.hasWrapedImage && ($e.closest(".wbb-img,#wbbmodal").length == 0 || $e.hasClass("wbb-cancel-button"))) {
        this.$body.find(".imgWrap ").each(function () {
          $.log("Removed imgWrap block");
          $(this).replaceWith($(this).find("img"));
        });
        this.hasWrapedImage = false;
        this.updateUI();
      }

      if ($e.is("img") && $e.closest(".wysibb-body").length > 0) {
        $e.wrap("<span class='imgWrap'></span>");
        this.hasWrapedImage = $e;
        this.$body.focus();
        this.selectNode($e.parent()[0]);
      }
    },
    //MODAL WINDOW
    showModal: function showModal(cmd, opt, queryState) {
      $.log("showModal: " + cmd);
      this.saveRange();
      var $cont = this.$modal.find(".wbbm-content").html("");
      var $wbbm = this.$modal.find(".wbbm").removeClass("hastabs");
      this.$modal.find("span.wbbm-title-text").html(opt.title);

      if (opt.tabs && opt.tabs.length > 1) {
        //has tabs, create
        $wbbm.addClass("hastabs");
        var $ul = $('<div class="wbbm-tablist">').appendTo($cont).append("<ul>").children("ul");
        $.each(opt.tabs, $.proxy(function (i, row) {
          if (i == 0) {
            row['on'] = "on";
          }

          $ul.append(this.strf('<li class="{on}" onClick="$(this).parent().find(\'.on\').removeClass(\'on\');$(this).addClass(\'on\');$(this).parents(\'.wbbm-content\').find(\'.tab-cont\').hide();$(this).parents(\'.wbbm-content\').find(\'.tab' + i + '\').show()">{title}</li>', row));
        }, this));
      }

      if (opt.width) {
        $wbbm.css("width", opt.width);
      }

      var $cnt = $('<div class="wbbm-cont">').appendTo($cont);

      if (queryState) {
        $wbbm.find('#wbbm-remove').show();
      } else {
        $wbbm.find('#wbbm-remove').hide();
      }

      $.each(opt.tabs, $.proxy(function (i, r) {
        var $c = $('<div>').addClass("tab-cont tab" + i).attr("tid", i).appendTo($cnt);

        if (i > 0) {
          $c.hide();
        }

        if (r.html) {
          $c.html(this.strf(r.html, this.options));
        } else {
          $.each(r.input, $.proxy(function (j, inp) {
            inp["value"] = queryState[inp.param.toLowerCase()];

            if (inp.param.toLowerCase() == "seltext" && (!inp["value"] || inp["value"] == "")) {
              inp["value"] = this.getSelectText(this.options.bbmode);
            }

            if (inp["value"] && inp["value"].indexOf("<span id='wbbid") == 0 && $(inp["value"]).is("span[id*='wbbid']")) {
              inp["value"] = $(inp["value"]).html();
            }

            if (inp.type && inp.type == "div") {
              //div input, support wysiwyg input
              $c.append(this.strf('<div class="wbbm-inp-row"><label>{title}</label><div class="inp-text div-modal-text" contenteditable="true" name="{param}">{value}</div></div>', inp));
            } else {
              //default input
              $c.append(this.strf('<div class="wbbm-inp-row"><label>{title}</label><input class="inp-text modal-text" type="text" name="{param}" value="{value}"/></div>', inp));
            }
          }, this));
        }
      }, this)); //this.lastRange=this.getRange();

      if ($.isFunction(opt.onLoad)) {
        opt.onLoad.call(this, cmd, opt, queryState);
      }

      $wbbm.find('#wbbm-submit').click($.proxy(function () {
        if ($.isFunction(opt.onSubmit)) {
          //custom submit function, if return false, then don't process our function
          var r = opt.onSubmit.call(this, cmd, opt, queryState);

          if (r === false) {
            return;
          }
        }

        var params = {};
        var valid = true;
        this.$modal.find(".wbbm-inperr").remove();
        this.$modal.find(".wbbm-brdred").removeClass("wbbm-brdred"); //$.each(this.$modal.find(".tab-cont:visible input"),$.proxy(function(i,el) {

        $.each(this.$modal.find(".tab-cont:visible .inp-text"), $.proxy(function (i, el) {
          var tid = $(el).parents(".tab-cont").attr("tid");
          var pname = $(el).attr("name").toLowerCase();
          var pval = "";

          if ($(el).is("input,textrea,select")) {
            pval = $(el).val();
          } else {
            pval = $(el).html();
          }

          var validation = opt.tabs[tid]["input"][i]["validation"];

          if (typeof validation != "undefined") {
            if (!pval.match(new RegExp(validation, "i"))) {
              valid = false;
              $(el).after('<span class="wbbm-inperr">' + CURLANG.validation_err + '</span>').addClass("wbbm-brdred");
            }
          }

          params[pname] = pval;
        }, this));

        if (valid) {
          $.log("Last range: " + this.lastRange);
          this.selectLastRange(); //insert callback

          if (queryState) {
            this.wbbRemoveCallback(cmd, true);
          }

          this.wbbInsertCallback(cmd, params); //END insert callback

          this.closeModal();
          this.updateUI();
        }
      }, this));
      $wbbm.find('#wbbm-remove').click($.proxy(function () {
        //clbk.remove();
        this.selectLastRange();
        this.wbbRemoveCallback(cmd); //remove callback

        this.closeModal();
        this.updateUI();
      }, this));
      $(document.body).css("overflow", "hidden"); //lock the screen, remove scroll on body

      if ($("body").height() > $(window).height()) {
        //if body has scroll, add padding-right 18px
        $(document.body).css("padding-right", "18px");
      }

      this.$modal.show(); //if (window.getSelection)

      if (this.isMobile) {
        $wbbm.css("margin-top", "10px");
      } else {
        $wbbm.css("margin-top", ($(window).height() - $wbbm.outerHeight()) / 3 + "px");
      } //setTimeout($.proxy(function() {this.$modal.find("input:visible")[0].focus()},this),10);


      setTimeout($.proxy(function () {
        this.$modal.find(".inp-text:visible")[0].focus();
      }, this), 10);
    },
    escModal: function escModal(e) {
      if (e.which == 27) {
        this.closeModal();
      }
    },
    closeModal: function closeModal() {
      $(document.body).css("overflow", "auto").css("padding-right", "0").unbind("keyup", this.escModal); //ESC key close modal;

      this.$modal.find('#wbbm-submit,#wbbm-remove').unbind('click');
      this.$modal.hide();
      this.lastRange = false;
      return this;
    },
    getParams: function getParams(src, s, offset) {
      var params = {};

      if (this.options.bbmode) {
        //bbmode
        var stext = s.match(/\{[\s\S]+?\}/g);
        s = this.prepareRGX(s);
        var rgx = new RegExp(s, "g");
        var val = this.txtArea.value;

        if (offset > 0) {
          val = val.substr(offset, val.length - offset);
        }

        var a = rgx.exec(val);

        if (a) {
          $.each(stext, function (i, n) {
            params[n.replace(/\{|\}/g, "").replace(/"/g, "'").toLowerCase()] = a[i + 1];
          });
        }
      } else {
        var rules = this.options.rules[s][0][1];
        $.each(rules, $.proxy(function (k, v) {
          var value = "";
          var $v = v.sel !== false ? value = $(src).find(v.sel) : $(src);

          if (v.attr !== false) {
            value = $v.attr(v.attr);
          } else {
            value = $v.html();
          }

          if (value) {
            if (v.rgx !== false) {
              var m = value.match(new RegExp(v.rgx));

              if (m && m.length == 2) {
                value = m[1];
              }
            }

            params[k] = value.replace(/"/g, "'");
          }
        }, this));
      }

      return params;
    },
    //imgUploader
    imgLoadModal: function imgLoadModal() {
      $.log("imgLoadModal");

      if (this.options.imgupload === true) {
        this.$modal.find("#imguploader").dragfileupload({
          url: this.strf(this.options.img_uploadurl, this.options),
          extraParams: {
            maxwidth: this.options.img_maxwidth,
            maxheight: this.options.img_maxheight
          },
          themePrefix: this.options.themePrefix,
          themeName: this.options.themeName,
          success: $.proxy(function (data) {
            this.$txtArea.insertImage(data.image_link, data.thumb_link);
            this.closeModal();
            this.updateUI();
          }, this)
        });
        this.$modal.find("#fileupl").bind("change", function () {
          $("#fupform").submit();
        });
        this.$modal.find("#fupform").bind("submit", $.proxy(function (e) {
          $(e.target).parents("#imguploader").hide().after('<div class="loader"><img src="' + this.options.themePrefix + '/' + this.options.themeName + '/img/loader.gif" /><br/><span>' + CURLANG.loading + '</span></div>').parent().css("text-align", "center");
        }, this));
      } else {
        this.$modal.find(".hastabs").removeClass("hastabs");
        this.$modal.find("#imguploader").parents(".tab-cont").remove();
        this.$modal.find(".wbbm-tablist").remove();
      }
    },
    imgSubmitModal: function imgSubmitModal() {
      $.log("imgSubmitModal");
    },
    //DEBUG
    printObjectInIE: function printObjectInIE(obj) {
      try {
        $.log(JSON.stringify(obj));
      } catch (e) {}
    },
    checkFilter: function checkFilter(node, filter) {
      $.log("node: " + $(node).get(0).outerHTML + " filter: " + filter + " res: " + $(node).is(filter.toLowerCase()));
    },
    debug: function debug(msg) {
      if (this.options.debug === true) {
        var time = new Date().getTime();

        if (typeof console != "undefined") {
          console.log(time - this.startTime + " ms: " + msg);
        } else {
          $("#exlog").append('<p>' + (time - this.startTime) + " ms: " + msg + '</p>');
        }

        this.startTime = time;
      }
    },
    //Browser fixes
    isChrome: function isChrome() {
      return window.chrome ? true : false;
    },
    fixTableTransform: function fixTableTransform(html) {
      if (!html) {
        return "";
      }

      if ($.inArray("table", this.options.buttons) == -1) {
        return html.replace(/\<(\/*?(table|tr|td|tbody))[^>]*\>/ig, "");
      } else {
        return html.replace(/\<(\/*?(table|tr|td))[^>]*\>/ig, "[$1]".toLowerCase()).replace(/\<\/*tbody[^>]*\>/ig, "");
      }
    }
  };

  $.log = function (msg) {
    if (typeof wbbdebug != "undefined" && wbbdebug === true) {
      if (typeof console != "undefined") {
        console.log(msg);
      } else {
        $("#exlog").append('<p>' + msg + '</p>');
      }
    }
  };

  $.fn.wysibb = function (settings) {
    return this.each(function () {
      var data = $(this).data("wbb");

      if (!data) {
        new $.wysibb(this, settings);
      }
    });
  };

  $.fn.wdrag = function (opt) {
    if (!opt.scope) {
      opt.scope = this;
    }

    var start = {
      x: 0,
      y: 0,
      height: 0
    };
    var drag;

    opt.scope.drag_mousedown = function (e) {
      e.preventDefault();
      start = {
        x: e.pageX,
        y: e.pageY,
        height: opt.height,
        sheight: opt.scope.$body.height()
      };
      drag = true;
      $(document).bind("mousemove", $.proxy(opt.scope.drag_mousemove, this));
      $(this).addClass("drag");
    };

    opt.scope.drag_mouseup = function (e) {
      if (drag === true) {
        e.preventDefault();
        $(document).unbind("mousemove", opt.scope.drag_mousemove);
        $(this).removeClass("drag");
        drag = false;
      }
    };

    opt.scope.drag_mousemove = function (e) {
      e.preventDefault();
      var axisX = 0,
          axisY = 0;

      if (opt.axisX) {
        axisX = e.pageX - start.x;
      }

      if (opt.axisY) {
        axisY = e.pageY - start.y;
      }

      if (axisY != 0) {
        var nheight = start.sheight + axisY;

        if (nheight > start.height && nheight <= opt.scope.options.resize_maxheight) {
          if (opt.scope.options.bbmode == true) {
            opt.scope.$txtArea.css(opt.scope.options.autoresize === true ? "min-height" : "height", nheight + "px");
          } else {
            opt.scope.$body.css(opt.scope.options.autoresize === true ? "min-height" : "height", nheight + "px");
          }
        }
      }
    };

    $(this).bind("mousedown", opt.scope.drag_mousedown);
    $(document).bind("mouseup", $.proxy(opt.scope.drag_mouseup, this));
  }, //API
  $.fn.getDoc = function () {
    return this.data('wbb').doc;
  };

  $.fn.getSelectText = function (fromTextArea) {
    return this.data('wbb').getSelectText(fromTextArea);
  };

  $.fn.bbcode = function (data) {
    if (typeof data != "undefined") {
      if (this.data('wbb').options.bbmode) {
        this.data('wbb').$txtArea.val(data);
      } else {
        this.data('wbb').$body.html(this.data("wbb").getHTML(data));
      }

      return this;
    } else {
      return this.data('wbb').getBBCode();
    }
  };

  $.fn.htmlcode = function (data) {
    if (!this.data('wbb').options.onlyBBMode && this.data('wbb').inited === true) {
      if (typeof data != "undefined") {
        this.data('wbb').$body.html(data);
        return this;
      } else {
        return this.data('wbb').getHTML(this.data('wbb').$txtArea.val());
      }
    }
  };

  $.fn.getBBCode = function () {
    return this.data('wbb').getBBCode();
  };

  $.fn.getHTML = function () {
    var wbb = this.data('wbb');
    return wbb.getHTML(wbb.$txtArea.val());
  };

  $.fn.getHTMLByCommand = function (command, params) {
    return this.data("wbb").getHTMLByCommand(command, params);
  };

  $.fn.getBBCodeByCommand = function (command, params) {
    return this.data("wbb").getBBCodeByCommand(command, params);
  };

  $.fn.insertAtCursor = function (data, forceBBMode) {
    this.data("wbb").insertAtCursor(data, forceBBMode);
    return this.data("wbb");
  };

  $.fn.execCommand = function (command, value) {
    this.data("wbb").execCommand(command, value);
    return this.data("wbb");
  };

  $.fn.insertImage = function (imgurl, thumburl) {
    var editor = this.data("wbb");
    var code = thumburl ? editor.getCodeByCommand('link', {
      url: imgurl,
      seltext: editor.getCodeByCommand('img', {
        src: thumburl
      })
    }) : editor.getCodeByCommand('img', {
      src: imgurl
    });
    this.insertAtCursor(code);
    return editor;
  };

  $.fn.sync = function () {
    this.data("wbb").sync();
    return this.data("wbb");
  };

  $.fn.destroy = function () {
    this.data("wbb").destroy();
  };

  $.fn.queryState = function (command) {
    return this.data("wbb").queryState(command);
  };
})(jQuery); //Drag&Drop file uploader


(function ($) {
  'use strict';

  $.fn.dragfileupload = function (options) {
    return this.each(function () {
      var upl = new FileUpload(this, options);
      upl.init();
    });
  };

  function FileUpload(e, options) {
    this.$block = $(e);
    this.opt = $.extend({
      url: false,
      success: false,
      extraParams: false,
      fileParam: 'img',
      validation: '\.(jpg|png|gif|jpeg)$',
      t1: CURLANG.fileupload_text1,
      t2: CURLANG.fileupload_text2
    }, options);
  }

  FileUpload.prototype = {
    init: function init() {
      if (window.FormData != null) {
        this.$block.addClass("drag");
        this.$block.prepend('<div class="p2">' + this.opt.t2 + '</div>');
        this.$block.prepend('<div class="p">' + this.opt.t1 + '</div>');
        this.$block.bind('dragover', function () {
          $(this).addClass('dragover');
          return false;
        });
        this.$block.bind('dragleave', function () {
          $(this).removeClass('dragover');
          return false;
        }); //upload progress

        var uploadProgress = $.proxy(function (e) {
          var p = parseInt(e.loaded / e.total * 100, 10);
          this.$loader.children("span").text(CURLANG.loading + ': ' + p + '%');
        }, this);

        var _xhr = jQuery.ajaxSettings.xhr();

        if (_xhr.upload) {
          _xhr.upload.addEventListener('progress', uploadProgress, false);
        }

        this.$block[0].ondrop = $.proxy(function (e) {
          e.preventDefault();
          this.$block.removeClass('dragover');
          var ufile = e.dataTransfer.files[0];

          if (this.opt.validation && !ufile.name.match(new RegExp(this.opt.validation))) {
            this.error(CURLANG.validation_err);
            return false;
          }

          var fData = new FormData();
          fData.append(this.opt.fileParam, ufile);

          if (this.opt.extraParams) {
            //check for extraParams to upload
            $.each(this.opt.extraParams, function (k, v) {
              fData.append(k, v);
            });
          }

          this.$loader = $('<div class="loader"><img src="' + this.opt.themePrefix + '/' + this.opt.themeName + '/img/loader.gif" /><br/><span>' + CURLANG.loading + '</span></div>');
          this.$block.html(this.$loader);
          $.ajax({
            type: 'POST',
            url: this.opt.url,
            data: fData,
            processData: false,
            contentType: false,
            xhr: function xhr() {
              return _xhr;
            },
            dataType: 'json',
            success: $.proxy(function (data) {
              if (data && data.status == 1) {
                this.opt.success(data);
              } else {
                this.error(data.msg || CURLANG.error_onupload);
              }
            }, this),
            error: $.proxy(function (xhr, txt, thr) {
              this.error(CURLANG.error_onupload);
            }, this)
          });
        }, this);
      }
    },
    error: function error(msg) {
      this.$block.find(".upl-error").remove().end().append('<span class="upl-error">' + msg + '</span>').addClass("wbbm-brdred");
    }
  };
})(jQuery);

/***/ }),

/***/ "./themes/default/src/scss/app.scss":
/*!******************************************!*\
  !*** ./themes/default/src/scss/app.scss ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["themes/default/assets/css/app","/themes/default/assets/js/vendor"], () => (__webpack_exec__("./themes/default/src/js/app.ts"), __webpack_exec__("./themes/default/src/scss/app.scss")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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cUQsNkJBQTZCNjRCLEdBQTdCLEdBQW1DLFNBQXhGLEVBQW1HMy9CLFFBQW5HLENBQTRHLGFBQTVHO0FBQ0Q7QUF6RW9CLEdBQXZCO0FBMkVELENBckdELEVBcUdHbkQsTUFyR0g7Ozs7Ozs7Ozs7OztBQzNrR0EiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly9qb2huY21zLy4vdGhlbWVzL2RlZmF1bHQvc3JjL2pzL2FwcC50cyIsIndlYnBhY2s6Ly9qb2huY21zLy4vdGhlbWVzL2RlZmF1bHQvc3JjL2pzL2Jvb3RzdHJhcC5qcyIsIndlYnBhY2s6Ly9qb2huY21zLy4vdGhlbWVzL2RlZmF1bHQvc3JjL2pzL2ZvcnVtLmpzIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvanF1ZXJ5Lm1hZ25pZmljLXBvcHVwLmpzIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvbWFpbi5qcyIsIndlYnBhY2s6Ly9qb2huY21zLy4vdGhlbWVzL2RlZmF1bHQvc3JjL2pzL21lbnUuanMiLCJ3ZWJwYWNrOi8vam9obmNtcy8uL3RoZW1lcy9kZWZhdWx0L3NyYy9qcy9tb2RhbHMuanMiLCJ3ZWJwYWNrOi8vam9obmNtcy8uL3RoZW1lcy9kZWZhdWx0L3NyYy9qcy9wcmlzbS5qcyIsIndlYnBhY2s6Ly9qb2huY21zLy4vdGhlbWVzL2RlZmF1bHQvc3JjL2pzL3Byb2dyZXNzLmpzIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvc2xpZGVyLmpzIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvd3lzaWJiLmpzIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvc2Nzcy9hcHAuc2Nzcz9jNDZhIl0sInNvdXJjZXNDb250ZW50IjpbImltcG9ydCAnc3dpcGVyL2Nzcy9idW5kbGUnO1xuXG5yZXF1aXJlKCcuL2Jvb3RzdHJhcCcpO1xucmVxdWlyZSgnLi9qcXVlcnkubWFnbmlmaWMtcG9wdXAnKTtcbnJlcXVpcmUoXCJmbGF0cGlja3JcIik7XG5yZXF1aXJlKCcuL21lbnUnKTtcbnJlcXVpcmUoJy4vcHJpc20nKTtcbnJlcXVpcmUoJy4vZm9ydW0nKTtcbnJlcXVpcmUoJy4vbW9kYWxzJyk7XG5yZXF1aXJlKCcuL3NsaWRlcicpO1xucmVxdWlyZSgnLi9wcm9ncmVzcycpO1xucmVxdWlyZSgnLi93eXNpYmInKTtcbnJlcXVpcmUoJy4vbWFpbicpO1xuXG5pbXBvcnQge2NyZWF0ZUFwcCwgZGVmaW5lQXN5bmNDb21wb25lbnR9IGZyb20gJ3Z1ZSdcblxuY29uc3QgYXBwID0gKCkgPT4gY3JlYXRlQXBwKHt9KVxuXG5jb25zdCB2dWVfYXBwcyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy52dWVfYXBwJyk7XG52dWVfYXBwcy5mb3JFYWNoKGZ1bmN0aW9uIChlbCkge1xuICBsZXQgdnVlQXBwID0gYXBwKCk7XG4gIHZ1ZUFwcC5jb21wb25lbnQoJ0xpa2VzQ29tcG9uZW50JywgZGVmaW5lQXN5bmNDb21wb25lbnQoKCkgPT4gaW1wb3J0KCdAL2NvbXBvbmVudHMvTGlrZXNDb21wb25lbnQudnVlJykpKTtcbiAgdnVlQXBwLmNvbXBvbmVudCgnQ29tbWVudHNDb21wb25lbnQnLCBkZWZpbmVBc3luY0NvbXBvbmVudCgoKSA9PiBpbXBvcnQoJ0AvY29tcG9uZW50cy9Db21tZW50c0NvbXBvbmVudC52dWUnKSkpO1xuICB2dWVBcHAuY29tcG9uZW50KCdwYWdpbmF0aW9uJywgZGVmaW5lQXN5bmNDb21wb25lbnQoKCkgPT4gaW1wb3J0KCdAL2NvbXBvbmVudHMvUGFnaW5hdGlvbi9WdWVQYWdpbmF0aW9uLnZ1ZScpKSk7XG4gIHZ1ZUFwcC5jb21wb25lbnQoJ0NrZWRpdG9ySW5wdXRDb21wb25lbnQnLCBkZWZpbmVBc3luY0NvbXBvbmVudCgoKSA9PiBpbXBvcnQoJ0AvY29tcG9uZW50cy9Da2VkaXRvcklucHV0Q29tcG9uZW50LnZ1ZScpKSk7XG4gIHZ1ZUFwcC5jb21wb25lbnQoJ0F2YXRhclVwbG9hZGVyJywgZGVmaW5lQXN5bmNDb21wb25lbnQoKCkgPT4gaW1wb3J0KCdAL2NvbXBvbmVudHMvQXZhdGFyVXBsb2FkZXIudnVlJykpKTtcbiAgdnVlQXBwLm1vdW50KGVsKTtcbn0pO1xuIiwiLyoqXG4gKiBXZSdsbCBsb2FkIGpRdWVyeSBhbmQgdGhlIEJvb3RzdHJhcCBqUXVlcnkgcGx1Z2luIHdoaWNoIHByb3ZpZGVzIHN1cHBvcnRcbiAqIGZvciBKYXZhU2NyaXB0IGJhc2VkIEJvb3RzdHJhcCBmZWF0dXJlcyBzdWNoIGFzIG1vZGFscyBhbmQgdGFicy4gVGhpc1xuICogY29kZSBtYXkgYmUgbW9kaWZpZWQgdG8gZml0IHRoZSBzcGVjaWZpYyBuZWVkcyBvZiB5b3VyIGFwcGxpY2F0aW9uLlxuICovXG5cbnRyeSB7XG4gIHdpbmRvdy5Qb3BwZXIgPSByZXF1aXJlKCdwb3BwZXIuanMnKS5kZWZhdWx0O1xuICB3aW5kb3cuJCA9IHdpbmRvdy5qUXVlcnkgPSByZXF1aXJlKCdqcXVlcnknKTtcbiAgd2luZG93LmF4aW9zID0gcmVxdWlyZSgnYXhpb3MnKTtcbiAgd2luZG93LmF4aW9zLmRlZmF1bHRzLmhlYWRlcnMuY29tbW9uWydYLVJlcXVlc3RlZC1XaXRoJ10gPSAnWE1MSHR0cFJlcXVlc3QnO1xuXG4gIGxldCB0b2tlbiA9IGRvY3VtZW50LmhlYWQucXVlcnlTZWxlY3RvcignbWV0YVtuYW1lPVwiY3NyZi10b2tlblwiXScpO1xuICBpZiAodG9rZW4pIHtcbiAgICB3aW5kb3cuYXhpb3MuZGVmYXVsdHMuaGVhZGVycy5jb21tb25bJ1gtQ1NSRi1Ub2tlbiddID0gdG9rZW4uY29udGVudDtcbiAgfVxuICBjb25zdCBfID0gcmVxdWlyZSgnbG9kYXNoJyk7XG4gIGNvbnN0IGJvb3RzdHJhcCA9IHJlcXVpcmUoJ2Jvb3RzdHJhcCcpO1xuXG4gIGNvbnN0IHRvb2x0aXBUcmlnZ2VyTGlzdCA9IFtdLnNsaWNlLmNhbGwoZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCgnW2RhdGEtYnMtdG9nZ2xlPVwidG9vbHRpcFwiXScpKTtcbiAgdG9vbHRpcFRyaWdnZXJMaXN0Lm1hcChmdW5jdGlvbiAodG9vbHRpcFRyaWdnZXJFbCkge1xuICAgIHJldHVybiBuZXcgYm9vdHN0cmFwLlRvb2x0aXAodG9vbHRpcFRyaWdnZXJFbClcbiAgfSk7XG59IGNhdGNoIChlKSB7XG59XG5cblxuIiwiJCgnI2ZpcnN0X3Bvc3QnKVxuICAub24oJ2hpZGUuYnMuY29sbGFwc2UnLCBmdW5jdGlvbiAoZSkge1xuICAgIHRvZ2dsZVByZXZpZXcoKTtcbiAgfSlcbiAgLm9uKCdzaG93bi5icy5jb2xsYXBzZScsIGZ1bmN0aW9uICgpIHtcbiAgICB0b2dnbGVQcmV2aWV3KCk7XG4gIH0pO1xuXG5mdW5jdGlvbiB0b2dnbGVQcmV2aWV3KClcbntcbiAgJCgnI2ZpcnN0X3Bvc3RfYmxvY2sgLnBvc3QtcHJldmlldycpLnRvZ2dsZSgwKTtcbn1cblxuJChmdW5jdGlvbiAoKSB7XG4gICQoJy5pbWFnZS1nYWxsZXJ5JykuZWFjaChmdW5jdGlvbiAoKSB7XG4gICAgJCh0aGlzKS5tYWduaWZpY1BvcHVwKHtcbiAgICAgIGRlbGVnYXRlOiAnLmdhbGxlcnktaXRlbScsXG4gICAgICB0eXBlOiAnaW1hZ2UnLFxuICAgICAgdExvYWRpbmc6ICdMb2FkaW5nIGltYWdlICMlY3VyciUuLi4nLFxuICAgICAgbWFpbkNsYXNzOiAnbWZwLWltZy1tb2JpbGUnLFxuICAgICAgZ2FsbGVyeToge1xuICAgICAgICBlbmFibGVkOiB0cnVlLFxuICAgICAgICBuYXZpZ2F0ZUJ5SW1nQ2xpY2s6IHRydWUsXG4gICAgICAgIHByZWxvYWQ6IFswLCAxXVxuICAgICAgfSxcbiAgICAgIGltYWdlOiB7XG4gICAgICAgIHRFcnJvcjogJzxhIGhyZWY9XCIldXJsJVwiPlRoZSBpbWFnZSAjJWN1cnIlPC9hPiBjb3VsZCBub3QgYmUgbG9hZGVkLicsXG4gICAgICAgIHRpdGxlU3JjOiBmdW5jdGlvbiAoaXRlbSkge1xuICAgICAgICAgIHJldHVybiBpdGVtLmVsLmF0dHIoJ3RpdGxlJykgKyAnICZtaWRkb3Q7IDxhIGNsYXNzPVwiaW1hZ2Utc291cmNlLWxpbmtcIiBocmVmPVwiJyArIGl0ZW0uZWwuYXR0cignZGF0YS1zb3VyY2UnKSArICdcIiB0YXJnZXQ9XCJfYmxhbmtcIj5Eb3dubG9hZDwvYT4nO1xuICAgICAgICB9XG4gICAgICB9LFxuICAgICAgem9vbToge1xuICAgICAgICBlbmFibGVkOiB0cnVlLFxuICAgICAgICBkdXJhdGlvbjogMzAwLFxuICAgICAgICBvcGVuZXI6IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgICAgcmV0dXJuIGVsZW1lbnQuZmluZCgnaW1nJyk7XG4gICAgICAgIH1cbiAgICAgIH0sXG4gICAgfSk7XG4gIH0pO1xuICAkKCcuaW1hZ2UtcHJldmlldycpLm1hZ25pZmljUG9wdXAoe1xuICAgIHR5cGU6ICdpbWFnZScsXG4gICAgaW1hZ2U6IHtcbiAgICAgIHZlcnRpY2FsRml0OiB0cnVlLFxuICAgICAgdGl0bGVTcmM6IGZ1bmN0aW9uIChpdGVtKSB7XG4gICAgICAgIHJldHVybiBpdGVtLmVsLmF0dHIoJ3RpdGxlJykgKyAnICZtaWRkb3Q7IDxhIGNsYXNzPVwiaW1hZ2Utc291cmNlLWxpbmtcIiBocmVmPVwiJyArIGl0ZW0uZWwuYXR0cignZGF0YS1zb3VyY2UnKSArICdcIiB0YXJnZXQ9XCJfYmxhbmtcIj5Eb3dubG9hZDwvYT4nO1xuICAgICAgfVxuICAgIH0sXG4gICAgem9vbToge1xuICAgICAgZW5hYmxlZDogdHJ1ZSxcbiAgICAgIGR1cmF0aW9uOiAzMDAsXG4gICAgICBvcGVuZXI6IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgIHJldHVybiBlbGVtZW50LmZpbmQoJ2ltZycpO1xuICAgICAgfVxuICAgIH1cbiAgfSk7XG4gICQoJ1tkYXRhLXRvZ2dsZT1cInRvb2x0aXBcIl0nKS50b29sdGlwKCk7XG59KTtcblxuJChcIi5jdXN0b20tZmlsZS1pbnB1dFwiKS5vbihcImNoYW5nZVwiLCBmdW5jdGlvbiAoKSB7XG4gIHZhciBmaWxlTmFtZSA9ICQodGhpcykudmFsKCkuc3BsaXQoXCJcXFxcXCIpLnBvcCgpO1xuICAkKHRoaXMpLnNpYmxpbmdzKFwiLmN1c3RvbS1maWxlLWxhYmVsXCIpLmFkZENsYXNzKFwic2VsZWN0ZWRcIikuaHRtbChmaWxlTmFtZSk7XG59KTtcbiIsIi8qISBNYWduaWZpYyBQb3B1cCAtIHYxLjEuMCAtIDIwMTYtMDItMjBcbiogaHR0cDovL2RpbXNlbWVub3YuY29tL3BsdWdpbnMvbWFnbmlmaWMtcG9wdXAvXG4qIENvcHlyaWdodCAoYykgMjAxNiBEbWl0cnkgU2VtZW5vdjsgKi9cbjsoZnVuY3Rpb24gKGZhY3RvcnkpIHtcbiAgaWYgKHR5cGVvZiBkZWZpbmUgPT09ICdmdW5jdGlvbicgJiYgZGVmaW5lLmFtZCkge1xuICAgIC8vIEFNRC4gUmVnaXN0ZXIgYXMgYW4gYW5vbnltb3VzIG1vZHVsZS5cbiAgICBkZWZpbmUoWydqcXVlcnknXSwgZmFjdG9yeSk7XG4gIH0gZWxzZSBpZiAodHlwZW9mIGV4cG9ydHMgPT09ICdvYmplY3QnKSB7XG4gICAgLy8gTm9kZS9Db21tb25KU1xuICAgIGZhY3RvcnkocmVxdWlyZSgnanF1ZXJ5JykpO1xuICB9IGVsc2Uge1xuICAgIC8vIEJyb3dzZXIgZ2xvYmFsc1xuICAgIGZhY3Rvcnkod2luZG93LmpRdWVyeSB8fCB3aW5kb3cuWmVwdG8pO1xuICB9XG59KGZ1bmN0aW9uICgkKSB7XG5cbiAgLyo+PmNvcmUqL1xuICAvKipcbiAgICpcbiAgICogTWFnbmlmaWMgUG9wdXAgQ29yZSBKUyBmaWxlXG4gICAqXG4gICAqL1xuXG5cbiAgLyoqXG4gICAqIFByaXZhdGUgc3RhdGljIGNvbnN0YW50c1xuICAgKi9cbiAgdmFyIENMT1NFX0VWRU5UID0gJ0Nsb3NlJyxcbiAgICBCRUZPUkVfQ0xPU0VfRVZFTlQgPSAnQmVmb3JlQ2xvc2UnLFxuICAgIEFGVEVSX0NMT1NFX0VWRU5UID0gJ0FmdGVyQ2xvc2UnLFxuICAgIEJFRk9SRV9BUFBFTkRfRVZFTlQgPSAnQmVmb3JlQXBwZW5kJyxcbiAgICBNQVJLVVBfUEFSU0VfRVZFTlQgPSAnTWFya3VwUGFyc2UnLFxuICAgIE9QRU5fRVZFTlQgPSAnT3BlbicsXG4gICAgQ0hBTkdFX0VWRU5UID0gJ0NoYW5nZScsXG4gICAgTlMgPSAnbWZwJyxcbiAgICBFVkVOVF9OUyA9ICcuJyArIE5TLFxuICAgIFJFQURZX0NMQVNTID0gJ21mcC1yZWFkeScsXG4gICAgUkVNT1ZJTkdfQ0xBU1MgPSAnbWZwLXJlbW92aW5nJyxcbiAgICBQUkVWRU5UX0NMT1NFX0NMQVNTID0gJ21mcC1wcmV2ZW50LWNsb3NlJztcblxuXG4gIC8qKlxuICAgKiBQcml2YXRlIHZhcnNcbiAgICovXG4gIC8qanNoaW50IC1XMDc5ICovXG4gIHZhciBtZnAsIC8vIEFzIHdlIGhhdmUgb25seSBvbmUgaW5zdGFuY2Ugb2YgTWFnbmlmaWNQb3B1cCBvYmplY3QsIHdlIGRlZmluZSBpdCBsb2NhbGx5IHRvIG5vdCB0byB1c2UgJ3RoaXMnXG4gICAgTWFnbmlmaWNQb3B1cCA9IGZ1bmN0aW9uICgpIHtcbiAgICB9LFxuICAgIF9pc0pRID0gISEod2luZG93LmpRdWVyeSksXG4gICAgX3ByZXZTdGF0dXMsXG4gICAgX3dpbmRvdyA9ICQod2luZG93KSxcbiAgICBfZG9jdW1lbnQsXG4gICAgX3ByZXZDb250ZW50VHlwZSxcbiAgICBfd3JhcENsYXNzZXMsXG4gICAgX2N1cnJQb3B1cFR5cGU7XG5cblxuICAvKipcbiAgICogUHJpdmF0ZSBmdW5jdGlvbnNcbiAgICovXG4gIHZhciBfbWZwT24gPSBmdW5jdGlvbiAobmFtZSwgZikge1xuICAgICAgbWZwLmV2Lm9uKE5TICsgbmFtZSArIEVWRU5UX05TLCBmKTtcbiAgICB9LFxuICAgIF9nZXRFbCA9IGZ1bmN0aW9uIChjbGFzc05hbWUsIGFwcGVuZFRvLCBodG1sLCByYXcpIHtcbiAgICAgIHZhciBlbCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuICAgICAgZWwuY2xhc3NOYW1lID0gJ21mcC0nICsgY2xhc3NOYW1lO1xuICAgICAgaWYgKGh0bWwpIHtcbiAgICAgICAgZWwuaW5uZXJIVE1MID0gaHRtbDtcbiAgICAgIH1cbiAgICAgIGlmICghcmF3KSB7XG4gICAgICAgIGVsID0gJChlbCk7XG4gICAgICAgIGlmIChhcHBlbmRUbykge1xuICAgICAgICAgIGVsLmFwcGVuZFRvKGFwcGVuZFRvKTtcbiAgICAgICAgfVxuICAgICAgfSBlbHNlIGlmIChhcHBlbmRUbykge1xuICAgICAgICBhcHBlbmRUby5hcHBlbmRDaGlsZChlbCk7XG4gICAgICB9XG4gICAgICByZXR1cm4gZWw7XG4gICAgfSxcbiAgICBfbWZwVHJpZ2dlciA9IGZ1bmN0aW9uIChlLCBkYXRhKSB7XG4gICAgICBtZnAuZXYudHJpZ2dlckhhbmRsZXIoTlMgKyBlLCBkYXRhKTtcblxuICAgICAgaWYgKG1mcC5zdC5jYWxsYmFja3MpIHtcbiAgICAgICAgLy8gY29udmVydHMgXCJtZnBFdmVudE5hbWVcIiB0byBcImV2ZW50TmFtZVwiIGNhbGxiYWNrIGFuZCB0cmlnZ2VycyBpdCBpZiBpdCdzIHByZXNlbnRcbiAgICAgICAgZSA9IGUuY2hhckF0KDApLnRvTG93ZXJDYXNlKCkgKyBlLnNsaWNlKDEpO1xuICAgICAgICBpZiAobWZwLnN0LmNhbGxiYWNrc1tlXSkge1xuICAgICAgICAgIG1mcC5zdC5jYWxsYmFja3NbZV0uYXBwbHkobWZwLCAkLmlzQXJyYXkoZGF0YSkgPyBkYXRhIDogW2RhdGFdKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgX2dldENsb3NlQnRuID0gZnVuY3Rpb24gKHR5cGUpIHtcbiAgICAgIGlmICh0eXBlICE9PSBfY3VyclBvcHVwVHlwZSB8fCAhbWZwLmN1cnJUZW1wbGF0ZS5jbG9zZUJ0bikge1xuICAgICAgICBtZnAuY3VyclRlbXBsYXRlLmNsb3NlQnRuID0gJChtZnAuc3QuY2xvc2VNYXJrdXAucmVwbGFjZSgnJXRpdGxlJScsIG1mcC5zdC50Q2xvc2UpKTtcbiAgICAgICAgX2N1cnJQb3B1cFR5cGUgPSB0eXBlO1xuICAgICAgfVxuICAgICAgcmV0dXJuIG1mcC5jdXJyVGVtcGxhdGUuY2xvc2VCdG47XG4gICAgfSxcbiAgICAvLyBJbml0aWFsaXplIE1hZ25pZmljIFBvcHVwIG9ubHkgd2hlbiBjYWxsZWQgYXQgbGVhc3Qgb25jZVxuICAgIF9jaGVja0luc3RhbmNlID0gZnVuY3Rpb24gKCkge1xuICAgICAgaWYgKCEkLm1hZ25pZmljUG9wdXAuaW5zdGFuY2UpIHtcbiAgICAgICAgLypqc2hpbnQgLVcwMjAgKi9cbiAgICAgICAgbWZwID0gbmV3IE1hZ25pZmljUG9wdXAoKTtcbiAgICAgICAgbWZwLmluaXQoKTtcbiAgICAgICAgJC5tYWduaWZpY1BvcHVwLmluc3RhbmNlID0gbWZwO1xuICAgICAgfVxuICAgIH0sXG4gICAgLy8gQ1NTIHRyYW5zaXRpb24gZGV0ZWN0aW9uLCBodHRwOi8vc3RhY2tvdmVyZmxvdy5jb20vcXVlc3Rpb25zLzcyNjQ4OTkvZGV0ZWN0LWNzcy10cmFuc2l0aW9ucy11c2luZy1qYXZhc2NyaXB0LWFuZC13aXRob3V0LW1vZGVybml6clxuICAgIHN1cHBvcnRzVHJhbnNpdGlvbnMgPSBmdW5jdGlvbiAoKSB7XG4gICAgICB2YXIgcyA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ3AnKS5zdHlsZSwgLy8gJ3MnIGZvciBzdHlsZS4gYmV0dGVyIHRvIGNyZWF0ZSBhbiBlbGVtZW50IGlmIGJvZHkgeWV0IHRvIGV4aXN0XG4gICAgICAgIHYgPSBbJ21zJywgJ08nLCAnTW96JywgJ1dlYmtpdCddOyAvLyAndicgZm9yIHZlbmRvclxuXG4gICAgICBpZiAoc1sndHJhbnNpdGlvbiddICE9PSB1bmRlZmluZWQpIHtcbiAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICB9XG5cbiAgICAgIHdoaWxlICh2Lmxlbmd0aCkge1xuICAgICAgICBpZiAodi5wb3AoKSArICdUcmFuc2l0aW9uJyBpbiBzKSB7XG4gICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cbiAgICAgIH1cblxuICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH07XG5cblxuICAvKipcbiAgICogUHVibGljIGZ1bmN0aW9uc1xuICAgKi9cbiAgTWFnbmlmaWNQb3B1cC5wcm90b3R5cGUgPSB7XG5cbiAgICBjb25zdHJ1Y3RvcjogTWFnbmlmaWNQb3B1cCxcblxuICAgIC8qKlxuICAgICAqIEluaXRpYWxpemVzIE1hZ25pZmljIFBvcHVwIHBsdWdpbi5cbiAgICAgKiBUaGlzIGZ1bmN0aW9uIGlzIHRyaWdnZXJlZCBvbmx5IG9uY2Ugd2hlbiAkLmZuLm1hZ25pZmljUG9wdXAgb3IgJC5tYWduaWZpY1BvcHVwIGlzIGV4ZWN1dGVkXG4gICAgICovXG4gICAgaW5pdDogZnVuY3Rpb24gKCkge1xuICAgICAgdmFyIGFwcFZlcnNpb24gPSBuYXZpZ2F0b3IuYXBwVmVyc2lvbjtcbiAgICAgIG1mcC5pc0xvd0lFID0gbWZwLmlzSUU4ID0gZG9jdW1lbnQuYWxsICYmICFkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyO1xuICAgICAgbWZwLmlzQW5kcm9pZCA9ICgvYW5kcm9pZC9naSkudGVzdChhcHBWZXJzaW9uKTtcbiAgICAgIG1mcC5pc0lPUyA9ICgvaXBob25lfGlwYWR8aXBvZC9naSkudGVzdChhcHBWZXJzaW9uKTtcbiAgICAgIG1mcC5zdXBwb3J0c1RyYW5zaXRpb24gPSBzdXBwb3J0c1RyYW5zaXRpb25zKCk7XG5cbiAgICAgIC8vIFdlIGRpc2FibGUgZml4ZWQgcG9zaXRpb25lZCBsaWdodGJveCBvbiBkZXZpY2VzIHRoYXQgZG9uJ3QgaGFuZGxlIGl0IG5pY2VseS5cbiAgICAgIC8vIElmIHlvdSBrbm93IGEgYmV0dGVyIHdheSBvZiBkZXRlY3RpbmcgdGhpcyAtIGxldCBtZSBrbm93LlxuICAgICAgbWZwLnByb2JhYmx5TW9iaWxlID0gKG1mcC5pc0FuZHJvaWQgfHwgbWZwLmlzSU9TIHx8IC8oT3BlcmEgTWluaSl8S2luZGxlfHdlYk9TfEJsYWNrQmVycnl8KE9wZXJhIE1vYmkpfChXaW5kb3dzIFBob25lKXxJRU1vYmlsZS9pLnRlc3QobmF2aWdhdG9yLnVzZXJBZ2VudCkpO1xuICAgICAgX2RvY3VtZW50ID0gJChkb2N1bWVudCk7XG5cbiAgICAgIG1mcC5wb3B1cHNDYWNoZSA9IHt9O1xuICAgIH0sXG5cbiAgICAvKipcbiAgICAgKiBPcGVucyBwb3B1cFxuICAgICAqIEBwYXJhbSAgZGF0YSBbZGVzY3JpcHRpb25dXG4gICAgICovXG4gICAgb3BlbjogZnVuY3Rpb24gKGRhdGEpIHtcblxuICAgICAgdmFyIGk7XG5cbiAgICAgIGlmIChkYXRhLmlzT2JqID09PSBmYWxzZSkge1xuICAgICAgICAvLyBjb252ZXJ0IGpRdWVyeSBjb2xsZWN0aW9uIHRvIGFycmF5IHRvIGF2b2lkIGNvbmZsaWN0cyBsYXRlclxuICAgICAgICBtZnAuaXRlbXMgPSBkYXRhLml0ZW1zLnRvQXJyYXkoKTtcblxuICAgICAgICBtZnAuaW5kZXggPSAwO1xuICAgICAgICB2YXIgaXRlbXMgPSBkYXRhLml0ZW1zLFxuICAgICAgICAgIGl0ZW07XG4gICAgICAgIGZvciAoaSA9IDA7IGkgPCBpdGVtcy5sZW5ndGg7IGkrKykge1xuICAgICAgICAgIGl0ZW0gPSBpdGVtc1tpXTtcbiAgICAgICAgICBpZiAoaXRlbS5wYXJzZWQpIHtcbiAgICAgICAgICAgIGl0ZW0gPSBpdGVtLmVsWzBdO1xuICAgICAgICAgIH1cbiAgICAgICAgICBpZiAoaXRlbSA9PT0gZGF0YS5lbFswXSkge1xuICAgICAgICAgICAgbWZwLmluZGV4ID0gaTtcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgbWZwLml0ZW1zID0gJC5pc0FycmF5KGRhdGEuaXRlbXMpID8gZGF0YS5pdGVtcyA6IFtkYXRhLml0ZW1zXTtcbiAgICAgICAgbWZwLmluZGV4ID0gZGF0YS5pbmRleCB8fCAwO1xuICAgICAgfVxuXG4gICAgICAvLyBpZiBwb3B1cCBpcyBhbHJlYWR5IG9wZW5lZCAtIHdlIGp1c3QgdXBkYXRlIHRoZSBjb250ZW50XG4gICAgICBpZiAobWZwLmlzT3Blbikge1xuICAgICAgICBtZnAudXBkYXRlSXRlbUhUTUwoKTtcbiAgICAgICAgcmV0dXJuO1xuICAgICAgfVxuXG4gICAgICBtZnAudHlwZXMgPSBbXTtcbiAgICAgIF93cmFwQ2xhc3NlcyA9ICcnO1xuICAgICAgaWYgKGRhdGEubWFpbkVsICYmIGRhdGEubWFpbkVsLmxlbmd0aCkge1xuICAgICAgICBtZnAuZXYgPSBkYXRhLm1haW5FbC5lcSgwKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIG1mcC5ldiA9IF9kb2N1bWVudDtcbiAgICAgIH1cblxuICAgICAgaWYgKGRhdGEua2V5KSB7XG4gICAgICAgIGlmICghbWZwLnBvcHVwc0NhY2hlW2RhdGEua2V5XSkge1xuICAgICAgICAgIG1mcC5wb3B1cHNDYWNoZVtkYXRhLmtleV0gPSB7fTtcbiAgICAgICAgfVxuICAgICAgICBtZnAuY3VyclRlbXBsYXRlID0gbWZwLnBvcHVwc0NhY2hlW2RhdGEua2V5XTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIG1mcC5jdXJyVGVtcGxhdGUgPSB7fTtcbiAgICAgIH1cblxuXG4gICAgICBtZnAuc3QgPSAkLmV4dGVuZCh0cnVlLCB7fSwgJC5tYWduaWZpY1BvcHVwLmRlZmF1bHRzLCBkYXRhKTtcbiAgICAgIG1mcC5maXhlZENvbnRlbnRQb3MgPSBtZnAuc3QuZml4ZWRDb250ZW50UG9zID09PSAnYXV0bycgPyAhbWZwLnByb2JhYmx5TW9iaWxlIDogbWZwLnN0LmZpeGVkQ29udGVudFBvcztcblxuICAgICAgaWYgKG1mcC5zdC5tb2RhbCkge1xuICAgICAgICBtZnAuc3QuY2xvc2VPbkNvbnRlbnRDbGljayA9IGZhbHNlO1xuICAgICAgICBtZnAuc3QuY2xvc2VPbkJnQ2xpY2sgPSBmYWxzZTtcbiAgICAgICAgbWZwLnN0LnNob3dDbG9zZUJ0biA9IGZhbHNlO1xuICAgICAgICBtZnAuc3QuZW5hYmxlRXNjYXBlS2V5ID0gZmFsc2U7XG4gICAgICB9XG5cblxuICAgICAgLy8gQnVpbGRpbmcgbWFya3VwXG4gICAgICAvLyBtYWluIGNvbnRhaW5lcnMgYXJlIGNyZWF0ZWQgb25seSBvbmNlXG4gICAgICBpZiAoIW1mcC5iZ092ZXJsYXkpIHtcblxuICAgICAgICAvLyBEYXJrIG92ZXJsYXlcbiAgICAgICAgbWZwLmJnT3ZlcmxheSA9IF9nZXRFbCgnYmcnKS5vbignY2xpY2snICsgRVZFTlRfTlMsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICBtZnAuY2xvc2UoKTtcbiAgICAgICAgfSk7XG5cbiAgICAgICAgbWZwLndyYXAgPSBfZ2V0RWwoJ3dyYXAnKS5hdHRyKCd0YWJpbmRleCcsIC0xKS5vbignY2xpY2snICsgRVZFTlRfTlMsIGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgaWYgKG1mcC5fY2hlY2tJZkNsb3NlKGUudGFyZ2V0KSkge1xuICAgICAgICAgICAgbWZwLmNsb3NlKCk7XG4gICAgICAgICAgfVxuICAgICAgICB9KTtcblxuICAgICAgICBtZnAuY29udGFpbmVyID0gX2dldEVsKCdjb250YWluZXInLCBtZnAud3JhcCk7XG4gICAgICB9XG5cbiAgICAgIG1mcC5jb250ZW50Q29udGFpbmVyID0gX2dldEVsKCdjb250ZW50Jyk7XG4gICAgICBpZiAobWZwLnN0LnByZWxvYWRlcikge1xuICAgICAgICBtZnAucHJlbG9hZGVyID0gX2dldEVsKCdwcmVsb2FkZXInLCBtZnAuY29udGFpbmVyLCBtZnAuc3QudExvYWRpbmcpO1xuICAgICAgfVxuXG5cbiAgICAgIC8vIEluaXRpYWxpemluZyBtb2R1bGVzXG4gICAgICB2YXIgbW9kdWxlcyA9ICQubWFnbmlmaWNQb3B1cC5tb2R1bGVzO1xuICAgICAgZm9yIChpID0gMDsgaSA8IG1vZHVsZXMubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgdmFyIG4gPSBtb2R1bGVzW2ldO1xuICAgICAgICBuID0gbi5jaGFyQXQoMCkudG9VcHBlckNhc2UoKSArIG4uc2xpY2UoMSk7XG4gICAgICAgIG1mcFsnaW5pdCcgKyBuXS5jYWxsKG1mcCk7XG4gICAgICB9XG4gICAgICBfbWZwVHJpZ2dlcignQmVmb3JlT3BlbicpO1xuXG5cbiAgICAgIGlmIChtZnAuc3Quc2hvd0Nsb3NlQnRuKSB7XG4gICAgICAgIC8vIENsb3NlIGJ1dHRvblxuICAgICAgICBpZiAoIW1mcC5zdC5jbG9zZUJ0bkluc2lkZSkge1xuICAgICAgICAgIG1mcC53cmFwLmFwcGVuZChfZ2V0Q2xvc2VCdG4oKSk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgX21mcE9uKE1BUktVUF9QQVJTRV9FVkVOVCwgZnVuY3Rpb24gKGUsIHRlbXBsYXRlLCB2YWx1ZXMsIGl0ZW0pIHtcbiAgICAgICAgICAgIHZhbHVlcy5jbG9zZV9yZXBsYWNlV2l0aCA9IF9nZXRDbG9zZUJ0bihpdGVtLnR5cGUpO1xuICAgICAgICAgIH0pO1xuICAgICAgICAgIF93cmFwQ2xhc3NlcyArPSAnIG1mcC1jbG9zZS1idG4taW4nO1xuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIGlmIChtZnAuc3QuYWxpZ25Ub3ApIHtcbiAgICAgICAgX3dyYXBDbGFzc2VzICs9ICcgbWZwLWFsaWduLXRvcCc7XG4gICAgICB9XG5cblxuICAgICAgaWYgKG1mcC5maXhlZENvbnRlbnRQb3MpIHtcbiAgICAgICAgbWZwLndyYXAuY3NzKHtcbiAgICAgICAgICBvdmVyZmxvdzogbWZwLnN0Lm92ZXJmbG93WSxcbiAgICAgICAgICBvdmVyZmxvd1g6ICdoaWRkZW4nLFxuICAgICAgICAgIG92ZXJmbG93WTogbWZwLnN0Lm92ZXJmbG93WVxuICAgICAgICB9KTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIG1mcC53cmFwLmNzcyh7XG4gICAgICAgICAgdG9wOiBfd2luZG93LnNjcm9sbFRvcCgpLFxuICAgICAgICAgIHBvc2l0aW9uOiAnYWJzb2x1dGUnXG4gICAgICAgIH0pO1xuICAgICAgfVxuICAgICAgaWYgKG1mcC5zdC5maXhlZEJnUG9zID09PSBmYWxzZSB8fCAobWZwLnN0LmZpeGVkQmdQb3MgPT09ICdhdXRvJyAmJiAhbWZwLmZpeGVkQ29udGVudFBvcykpIHtcbiAgICAgICAgbWZwLmJnT3ZlcmxheS5jc3Moe1xuICAgICAgICAgIGhlaWdodDogX2RvY3VtZW50LmhlaWdodCgpLFxuICAgICAgICAgIHBvc2l0aW9uOiAnYWJzb2x1dGUnXG4gICAgICAgIH0pO1xuICAgICAgfVxuXG5cbiAgICAgIGlmIChtZnAuc3QuZW5hYmxlRXNjYXBlS2V5KSB7XG4gICAgICAgIC8vIENsb3NlIG9uIEVTQyBrZXlcbiAgICAgICAgX2RvY3VtZW50Lm9uKCdrZXl1cCcgKyBFVkVOVF9OUywgZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICBpZiAoZS5rZXlDb2RlID09PSAyNykge1xuICAgICAgICAgICAgbWZwLmNsb3NlKCk7XG4gICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgIH1cblxuICAgICAgX3dpbmRvdy5vbigncmVzaXplJyArIEVWRU5UX05TLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgIG1mcC51cGRhdGVTaXplKCk7XG4gICAgICB9KTtcblxuXG4gICAgICBpZiAoIW1mcC5zdC5jbG9zZU9uQ29udGVudENsaWNrKSB7XG4gICAgICAgIF93cmFwQ2xhc3NlcyArPSAnIG1mcC1hdXRvLWN1cnNvcic7XG4gICAgICB9XG5cbiAgICAgIGlmIChfd3JhcENsYXNzZXMpXG4gICAgICAgIG1mcC53cmFwLmFkZENsYXNzKF93cmFwQ2xhc3Nlcyk7XG5cblxuICAgICAgLy8gdGhpcyB0cmlnZ2VycyByZWNhbGN1bGF0aW9uIG9mIGxheW91dCwgc28gd2UgZ2V0IGl0IG9uY2UgdG8gbm90IHRvIHRyaWdnZXIgdHdpY2VcbiAgICAgIHZhciB3aW5kb3dIZWlnaHQgPSBtZnAud0ggPSBfd2luZG93LmhlaWdodCgpO1xuXG5cbiAgICAgIHZhciB3aW5kb3dTdHlsZXMgPSB7fTtcblxuICAgICAgaWYgKG1mcC5maXhlZENvbnRlbnRQb3MpIHtcbiAgICAgICAgaWYgKG1mcC5faGFzU2Nyb2xsQmFyKHdpbmRvd0hlaWdodCkpIHtcbiAgICAgICAgICB2YXIgcyA9IG1mcC5fZ2V0U2Nyb2xsYmFyU2l6ZSgpO1xuICAgICAgICAgIGlmIChzKSB7XG4gICAgICAgICAgICB3aW5kb3dTdHlsZXMubWFyZ2luUmlnaHQgPSBzO1xuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfVxuXG4gICAgICBpZiAobWZwLmZpeGVkQ29udGVudFBvcykge1xuICAgICAgICBpZiAoIW1mcC5pc0lFNykge1xuICAgICAgICAgIHdpbmRvd1N0eWxlcy5vdmVyZmxvdyA9ICdoaWRkZW4nO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIC8vIGllNyBkb3VibGUtc2Nyb2xsIGJ1Z1xuICAgICAgICAgICQoJ2JvZHksIGh0bWwnKS5jc3MoJ292ZXJmbG93JywgJ2hpZGRlbicpO1xuICAgICAgICB9XG4gICAgICB9XG5cblxuICAgICAgdmFyIGNsYXNzZXNUb2FkZCA9IG1mcC5zdC5tYWluQ2xhc3M7XG4gICAgICBpZiAobWZwLmlzSUU3KSB7XG4gICAgICAgIGNsYXNzZXNUb2FkZCArPSAnIG1mcC1pZTcnO1xuICAgICAgfVxuICAgICAgaWYgKGNsYXNzZXNUb2FkZCkge1xuICAgICAgICBtZnAuX2FkZENsYXNzVG9NRlAoY2xhc3Nlc1RvYWRkKTtcbiAgICAgIH1cblxuICAgICAgLy8gYWRkIGNvbnRlbnRcbiAgICAgIG1mcC51cGRhdGVJdGVtSFRNTCgpO1xuXG4gICAgICBfbWZwVHJpZ2dlcignQnVpbGRDb250cm9scycpO1xuXG4gICAgICAvLyByZW1vdmUgc2Nyb2xsYmFyLCBhZGQgbWFyZ2luIGUudC5jXG4gICAgICAkKCdodG1sJykuY3NzKHdpbmRvd1N0eWxlcyk7XG5cbiAgICAgIC8vIGFkZCBldmVyeXRoaW5nIHRvIERPTVxuICAgICAgbWZwLmJnT3ZlcmxheS5hZGQobWZwLndyYXApLnByZXBlbmRUbyhtZnAuc3QucHJlcGVuZFRvIHx8ICQoZG9jdW1lbnQuYm9keSkpO1xuXG4gICAgICAvLyBTYXZlIGxhc3QgZm9jdXNlZCBlbGVtZW50XG4gICAgICBtZnAuX2xhc3RGb2N1c2VkRWwgPSBkb2N1bWVudC5hY3RpdmVFbGVtZW50O1xuXG4gICAgICAvLyBXYWl0IGZvciBuZXh0IGN5Y2xlIHRvIGFsbG93IENTUyB0cmFuc2l0aW9uXG4gICAgICBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcblxuICAgICAgICBpZiAobWZwLmNvbnRlbnQpIHtcbiAgICAgICAgICBtZnAuX2FkZENsYXNzVG9NRlAoUkVBRFlfQ0xBU1MpO1xuICAgICAgICAgIG1mcC5fc2V0Rm9jdXMoKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAvLyBpZiBjb250ZW50IGlzIG5vdCBkZWZpbmVkIChub3QgbG9hZGVkIGUudC5jKSB3ZSBhZGQgY2xhc3Mgb25seSBmb3IgQkdcbiAgICAgICAgICBtZnAuYmdPdmVybGF5LmFkZENsYXNzKFJFQURZX0NMQVNTKTtcbiAgICAgICAgfVxuXG4gICAgICAgIC8vIFRyYXAgdGhlIGZvY3VzIGluIHBvcHVwXG4gICAgICAgIF9kb2N1bWVudC5vbignZm9jdXNpbicgKyBFVkVOVF9OUywgbWZwLl9vbkZvY3VzSW4pO1xuXG4gICAgICB9LCAxNik7XG5cbiAgICAgIG1mcC5pc09wZW4gPSB0cnVlO1xuICAgICAgbWZwLnVwZGF0ZVNpemUod2luZG93SGVpZ2h0KTtcbiAgICAgIF9tZnBUcmlnZ2VyKE9QRU5fRVZFTlQpO1xuXG4gICAgICByZXR1cm4gZGF0YTtcbiAgICB9LFxuXG4gICAgLyoqXG4gICAgICogQ2xvc2VzIHRoZSBwb3B1cFxuICAgICAqL1xuICAgIGNsb3NlOiBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAoIW1mcC5pc09wZW4pIHJldHVybjtcbiAgICAgIF9tZnBUcmlnZ2VyKEJFRk9SRV9DTE9TRV9FVkVOVCk7XG5cbiAgICAgIG1mcC5pc09wZW4gPSBmYWxzZTtcbiAgICAgIC8vIGZvciBDU1MzIGFuaW1hdGlvblxuICAgICAgaWYgKG1mcC5zdC5yZW1vdmFsRGVsYXkgJiYgIW1mcC5pc0xvd0lFICYmIG1mcC5zdXBwb3J0c1RyYW5zaXRpb24pIHtcbiAgICAgICAgbWZwLl9hZGRDbGFzc1RvTUZQKFJFTU9WSU5HX0NMQVNTKTtcbiAgICAgICAgc2V0VGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgbWZwLl9jbG9zZSgpO1xuICAgICAgICB9LCBtZnAuc3QucmVtb3ZhbERlbGF5KTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIG1mcC5fY2xvc2UoKTtcbiAgICAgIH1cbiAgICB9LFxuXG4gICAgLyoqXG4gICAgICogSGVscGVyIGZvciBjbG9zZSgpIGZ1bmN0aW9uXG4gICAgICovXG4gICAgX2Nsb3NlOiBmdW5jdGlvbiAoKSB7XG4gICAgICBfbWZwVHJpZ2dlcihDTE9TRV9FVkVOVCk7XG5cbiAgICAgIHZhciBjbGFzc2VzVG9SZW1vdmUgPSBSRU1PVklOR19DTEFTUyArICcgJyArIFJFQURZX0NMQVNTICsgJyAnO1xuXG4gICAgICBtZnAuYmdPdmVybGF5LmRldGFjaCgpO1xuICAgICAgbWZwLndyYXAuZGV0YWNoKCk7XG4gICAgICBtZnAuY29udGFpbmVyLmVtcHR5KCk7XG5cbiAgICAgIGlmIChtZnAuc3QubWFpbkNsYXNzKSB7XG4gICAgICAgIGNsYXNzZXNUb1JlbW92ZSArPSBtZnAuc3QubWFpbkNsYXNzICsgJyAnO1xuICAgICAgfVxuXG4gICAgICBtZnAuX3JlbW92ZUNsYXNzRnJvbU1GUChjbGFzc2VzVG9SZW1vdmUpO1xuXG4gICAgICBpZiAobWZwLmZpeGVkQ29udGVudFBvcykge1xuICAgICAgICB2YXIgd2luZG93U3R5bGVzID0ge21hcmdpblJpZ2h0OiAnJ307XG4gICAgICAgIGlmIChtZnAuaXNJRTcpIHtcbiAgICAgICAgICAkKCdib2R5LCBodG1sJykuY3NzKCdvdmVyZmxvdycsICcnKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICB3aW5kb3dTdHlsZXMub3ZlcmZsb3cgPSAnJztcbiAgICAgICAgfVxuICAgICAgICAkKCdodG1sJykuY3NzKHdpbmRvd1N0eWxlcyk7XG4gICAgICB9XG5cbiAgICAgIF9kb2N1bWVudC5vZmYoJ2tleXVwJyArIEVWRU5UX05TICsgJyBmb2N1c2luJyArIEVWRU5UX05TKTtcbiAgICAgIG1mcC5ldi5vZmYoRVZFTlRfTlMpO1xuXG4gICAgICAvLyBjbGVhbiB1cCBET00gZWxlbWVudHMgdGhhdCBhcmVuJ3QgcmVtb3ZlZFxuICAgICAgbWZwLndyYXAuYXR0cignY2xhc3MnLCAnbWZwLXdyYXAnKS5yZW1vdmVBdHRyKCdzdHlsZScpO1xuICAgICAgbWZwLmJnT3ZlcmxheS5hdHRyKCdjbGFzcycsICdtZnAtYmcnKTtcbiAgICAgIG1mcC5jb250YWluZXIuYXR0cignY2xhc3MnLCAnbWZwLWNvbnRhaW5lcicpO1xuXG4gICAgICAvLyByZW1vdmUgY2xvc2UgYnV0dG9uIGZyb20gdGFyZ2V0IGVsZW1lbnRcbiAgICAgIGlmIChtZnAuc3Quc2hvd0Nsb3NlQnRuICYmXG4gICAgICAgICghbWZwLnN0LmNsb3NlQnRuSW5zaWRlIHx8IG1mcC5jdXJyVGVtcGxhdGVbbWZwLmN1cnJJdGVtLnR5cGVdID09PSB0cnVlKSkge1xuICAgICAgICBpZiAobWZwLmN1cnJUZW1wbGF0ZS5jbG9zZUJ0bilcbiAgICAgICAgICBtZnAuY3VyclRlbXBsYXRlLmNsb3NlQnRuLmRldGFjaCgpO1xuICAgICAgfVxuXG5cbiAgICAgIGlmIChtZnAuc3QuYXV0b0ZvY3VzTGFzdCAmJiBtZnAuX2xhc3RGb2N1c2VkRWwpIHtcbiAgICAgICAgJChtZnAuX2xhc3RGb2N1c2VkRWwpLmZvY3VzKCk7IC8vIHB1dCB0YWIgZm9jdXMgYmFja1xuICAgICAgfVxuICAgICAgbWZwLmN1cnJJdGVtID0gbnVsbDtcbiAgICAgIG1mcC5jb250ZW50ID0gbnVsbDtcbiAgICAgIG1mcC5jdXJyVGVtcGxhdGUgPSBudWxsO1xuICAgICAgbWZwLnByZXZIZWlnaHQgPSAwO1xuXG4gICAgICBfbWZwVHJpZ2dlcihBRlRFUl9DTE9TRV9FVkVOVCk7XG4gICAgfSxcblxuICAgIHVwZGF0ZVNpemU6IGZ1bmN0aW9uICh3aW5IZWlnaHQpIHtcblxuICAgICAgaWYgKG1mcC5pc0lPUykge1xuICAgICAgICAvLyBmaXhlcyBpT1MgbmF2IGJhcnMgaHR0cHM6Ly9naXRodWIuY29tL2RpbXNlbWVub3YvTWFnbmlmaWMtUG9wdXAvaXNzdWVzLzJcbiAgICAgICAgdmFyIHpvb21MZXZlbCA9IGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5jbGllbnRXaWR0aCAvIHdpbmRvdy5pbm5lcldpZHRoO1xuICAgICAgICB2YXIgaGVpZ2h0ID0gd2luZG93LmlubmVySGVpZ2h0ICogem9vbUxldmVsO1xuICAgICAgICBtZnAud3JhcC5jc3MoJ2hlaWdodCcsIGhlaWdodCk7XG4gICAgICAgIG1mcC53SCA9IGhlaWdodDtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIG1mcC53SCA9IHdpbkhlaWdodCB8fCBfd2luZG93LmhlaWdodCgpO1xuICAgICAgfVxuICAgICAgLy8gRml4ZXMgIzg0OiBwb3B1cCBpbmNvcnJlY3RseSBwb3NpdGlvbmVkIHdpdGggcG9zaXRpb246cmVsYXRpdmUgb24gYm9keVxuICAgICAgaWYgKCFtZnAuZml4ZWRDb250ZW50UG9zKSB7XG4gICAgICAgIG1mcC53cmFwLmNzcygnaGVpZ2h0JywgbWZwLndIKTtcbiAgICAgIH1cblxuICAgICAgX21mcFRyaWdnZXIoJ1Jlc2l6ZScpO1xuXG4gICAgfSxcblxuICAgIC8qKlxuICAgICAqIFNldCBjb250ZW50IG9mIHBvcHVwIGJhc2VkIG9uIGN1cnJlbnQgaW5kZXhcbiAgICAgKi9cbiAgICB1cGRhdGVJdGVtSFRNTDogZnVuY3Rpb24gKCkge1xuICAgICAgdmFyIGl0ZW0gPSBtZnAuaXRlbXNbbWZwLmluZGV4XTtcblxuICAgICAgLy8gRGV0YWNoIGFuZCBwZXJmb3JtIG1vZGlmaWNhdGlvbnNcbiAgICAgIG1mcC5jb250ZW50Q29udGFpbmVyLmRldGFjaCgpO1xuXG4gICAgICBpZiAobWZwLmNvbnRlbnQpXG4gICAgICAgIG1mcC5jb250ZW50LmRldGFjaCgpO1xuXG4gICAgICBpZiAoIWl0ZW0ucGFyc2VkKSB7XG4gICAgICAgIGl0ZW0gPSBtZnAucGFyc2VFbChtZnAuaW5kZXgpO1xuICAgICAgfVxuXG4gICAgICB2YXIgdHlwZSA9IGl0ZW0udHlwZTtcblxuICAgICAgX21mcFRyaWdnZXIoJ0JlZm9yZUNoYW5nZScsIFttZnAuY3Vyckl0ZW0gPyBtZnAuY3Vyckl0ZW0udHlwZSA6ICcnLCB0eXBlXSk7XG4gICAgICAvLyBCZWZvcmVDaGFuZ2UgZXZlbnQgd29ya3MgbGlrZSBzbzpcbiAgICAgIC8vIF9tZnBPbignQmVmb3JlQ2hhbmdlJywgZnVuY3Rpb24oZSwgcHJldlR5cGUsIG5ld1R5cGUpIHsgfSk7XG5cbiAgICAgIG1mcC5jdXJySXRlbSA9IGl0ZW07XG5cbiAgICAgIGlmICghbWZwLmN1cnJUZW1wbGF0ZVt0eXBlXSkge1xuICAgICAgICB2YXIgbWFya3VwID0gbWZwLnN0W3R5cGVdID8gbWZwLnN0W3R5cGVdLm1hcmt1cCA6IGZhbHNlO1xuXG4gICAgICAgIC8vIGFsbG93cyB0byBtb2RpZnkgbWFya3VwXG4gICAgICAgIF9tZnBUcmlnZ2VyKCdGaXJzdE1hcmt1cFBhcnNlJywgbWFya3VwKTtcblxuICAgICAgICBpZiAobWFya3VwKSB7XG4gICAgICAgICAgbWZwLmN1cnJUZW1wbGF0ZVt0eXBlXSA9ICQobWFya3VwKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAvLyBpZiB0aGVyZSBpcyBubyBtYXJrdXAgZm91bmQgd2UganVzdCBkZWZpbmUgdGhhdCB0ZW1wbGF0ZSBpcyBwYXJzZWRcbiAgICAgICAgICBtZnAuY3VyclRlbXBsYXRlW3R5cGVdID0gdHJ1ZTtcbiAgICAgICAgfVxuICAgICAgfVxuXG4gICAgICBpZiAoX3ByZXZDb250ZW50VHlwZSAmJiBfcHJldkNvbnRlbnRUeXBlICE9PSBpdGVtLnR5cGUpIHtcbiAgICAgICAgbWZwLmNvbnRhaW5lci5yZW1vdmVDbGFzcygnbWZwLScgKyBfcHJldkNvbnRlbnRUeXBlICsgJy1ob2xkZXInKTtcbiAgICAgIH1cblxuICAgICAgdmFyIG5ld0NvbnRlbnQgPSBtZnBbJ2dldCcgKyB0eXBlLmNoYXJBdCgwKS50b1VwcGVyQ2FzZSgpICsgdHlwZS5zbGljZSgxKV0oaXRlbSwgbWZwLmN1cnJUZW1wbGF0ZVt0eXBlXSk7XG4gICAgICBtZnAuYXBwZW5kQ29udGVudChuZXdDb250ZW50LCB0eXBlKTtcblxuICAgICAgaXRlbS5wcmVsb2FkZWQgPSB0cnVlO1xuXG4gICAgICBfbWZwVHJpZ2dlcihDSEFOR0VfRVZFTlQsIGl0ZW0pO1xuICAgICAgX3ByZXZDb250ZW50VHlwZSA9IGl0ZW0udHlwZTtcblxuICAgICAgLy8gQXBwZW5kIGNvbnRhaW5lciBiYWNrIGFmdGVyIGl0cyBjb250ZW50IGNoYW5nZWRcbiAgICAgIG1mcC5jb250YWluZXIucHJlcGVuZChtZnAuY29udGVudENvbnRhaW5lcik7XG5cbiAgICAgIF9tZnBUcmlnZ2VyKCdBZnRlckNoYW5nZScpO1xuICAgIH0sXG5cblxuICAgIC8qKlxuICAgICAqIFNldCBIVE1MIGNvbnRlbnQgb2YgcG9wdXBcbiAgICAgKi9cbiAgICBhcHBlbmRDb250ZW50OiBmdW5jdGlvbiAobmV3Q29udGVudCwgdHlwZSkge1xuICAgICAgbWZwLmNvbnRlbnQgPSBuZXdDb250ZW50O1xuXG4gICAgICBpZiAobmV3Q29udGVudCkge1xuICAgICAgICBpZiAobWZwLnN0LnNob3dDbG9zZUJ0biAmJiBtZnAuc3QuY2xvc2VCdG5JbnNpZGUgJiZcbiAgICAgICAgICBtZnAuY3VyclRlbXBsYXRlW3R5cGVdID09PSB0cnVlKSB7XG4gICAgICAgICAgLy8gaWYgdGhlcmUgaXMgbm8gbWFya3VwLCB3ZSBqdXN0IGFwcGVuZCBjbG9zZSBidXR0b24gZWxlbWVudCBpbnNpZGVcbiAgICAgICAgICBpZiAoIW1mcC5jb250ZW50LmZpbmQoJy5tZnAtY2xvc2UnKS5sZW5ndGgpIHtcbiAgICAgICAgICAgIG1mcC5jb250ZW50LmFwcGVuZChfZ2V0Q2xvc2VCdG4oKSk7XG4gICAgICAgICAgfVxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIG1mcC5jb250ZW50ID0gbmV3Q29udGVudDtcbiAgICAgICAgfVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgbWZwLmNvbnRlbnQgPSAnJztcbiAgICAgIH1cblxuICAgICAgX21mcFRyaWdnZXIoQkVGT1JFX0FQUEVORF9FVkVOVCk7XG4gICAgICBtZnAuY29udGFpbmVyLmFkZENsYXNzKCdtZnAtJyArIHR5cGUgKyAnLWhvbGRlcicpO1xuXG4gICAgICBtZnAuY29udGVudENvbnRhaW5lci5hcHBlbmQobWZwLmNvbnRlbnQpO1xuICAgIH0sXG5cblxuICAgIC8qKlxuICAgICAqIENyZWF0ZXMgTWFnbmlmaWMgUG9wdXAgZGF0YSBvYmplY3QgYmFzZWQgb24gZ2l2ZW4gZGF0YVxuICAgICAqIEBwYXJhbSAge2ludH0gaW5kZXggSW5kZXggb2YgaXRlbSB0byBwYXJzZVxuICAgICAqL1xuICAgIHBhcnNlRWw6IGZ1bmN0aW9uIChpbmRleCkge1xuICAgICAgdmFyIGl0ZW0gPSBtZnAuaXRlbXNbaW5kZXhdLFxuICAgICAgICB0eXBlO1xuXG4gICAgICBpZiAoaXRlbS50YWdOYW1lKSB7XG4gICAgICAgIGl0ZW0gPSB7ZWw6ICQoaXRlbSl9O1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgdHlwZSA9IGl0ZW0udHlwZTtcbiAgICAgICAgaXRlbSA9IHtkYXRhOiBpdGVtLCBzcmM6IGl0ZW0uc3JjfTtcbiAgICAgIH1cblxuICAgICAgaWYgKGl0ZW0uZWwpIHtcbiAgICAgICAgdmFyIHR5cGVzID0gbWZwLnR5cGVzO1xuXG4gICAgICAgIC8vIGNoZWNrIGZvciAnbWZwLVRZUEUnIGNsYXNzXG4gICAgICAgIGZvciAodmFyIGkgPSAwOyBpIDwgdHlwZXMubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgICBpZiAoaXRlbS5lbC5oYXNDbGFzcygnbWZwLScgKyB0eXBlc1tpXSkpIHtcbiAgICAgICAgICAgIHR5cGUgPSB0eXBlc1tpXTtcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIGl0ZW0uc3JjID0gaXRlbS5lbC5hdHRyKCdkYXRhLW1mcC1zcmMnKTtcbiAgICAgICAgaWYgKCFpdGVtLnNyYykge1xuICAgICAgICAgIGl0ZW0uc3JjID0gaXRlbS5lbC5hdHRyKCdocmVmJyk7XG4gICAgICAgIH1cbiAgICAgIH1cblxuICAgICAgaXRlbS50eXBlID0gdHlwZSB8fCBtZnAuc3QudHlwZSB8fCAnaW5saW5lJztcbiAgICAgIGl0ZW0uaW5kZXggPSBpbmRleDtcbiAgICAgIGl0ZW0ucGFyc2VkID0gdHJ1ZTtcbiAgICAgIG1mcC5pdGVtc1tpbmRleF0gPSBpdGVtO1xuICAgICAgX21mcFRyaWdnZXIoJ0VsZW1lbnRQYXJzZScsIGl0ZW0pO1xuXG4gICAgICByZXR1cm4gbWZwLml0ZW1zW2luZGV4XTtcbiAgICB9LFxuXG5cbiAgICAvKipcbiAgICAgKiBJbml0aWFsaXplcyBzaW5nbGUgcG9wdXAgb3IgYSBncm91cCBvZiBwb3B1cHNcbiAgICAgKi9cbiAgICBhZGRHcm91cDogZnVuY3Rpb24gKGVsLCBvcHRpb25zKSB7XG4gICAgICB2YXIgZUhhbmRsZXIgPSBmdW5jdGlvbiAoZSkge1xuICAgICAgICBlLm1mcEVsID0gdGhpcztcbiAgICAgICAgbWZwLl9vcGVuQ2xpY2soZSwgZWwsIG9wdGlvbnMpO1xuICAgICAgfTtcblxuICAgICAgaWYgKCFvcHRpb25zKSB7XG4gICAgICAgIG9wdGlvbnMgPSB7fTtcbiAgICAgIH1cblxuICAgICAgdmFyIGVOYW1lID0gJ2NsaWNrLm1hZ25pZmljUG9wdXAnO1xuICAgICAgb3B0aW9ucy5tYWluRWwgPSBlbDtcblxuICAgICAgaWYgKG9wdGlvbnMuaXRlbXMpIHtcbiAgICAgICAgb3B0aW9ucy5pc09iaiA9IHRydWU7XG4gICAgICAgIGVsLm9mZihlTmFtZSkub24oZU5hbWUsIGVIYW5kbGVyKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIG9wdGlvbnMuaXNPYmogPSBmYWxzZTtcbiAgICAgICAgaWYgKG9wdGlvbnMuZGVsZWdhdGUpIHtcbiAgICAgICAgICBlbC5vZmYoZU5hbWUpLm9uKGVOYW1lLCBvcHRpb25zLmRlbGVnYXRlLCBlSGFuZGxlcik7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgb3B0aW9ucy5pdGVtcyA9IGVsO1xuICAgICAgICAgIGVsLm9mZihlTmFtZSkub24oZU5hbWUsIGVIYW5kbGVyKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgX29wZW5DbGljazogZnVuY3Rpb24gKGUsIGVsLCBvcHRpb25zKSB7XG4gICAgICB2YXIgbWlkQ2xpY2sgPSBvcHRpb25zLm1pZENsaWNrICE9PSB1bmRlZmluZWQgPyBvcHRpb25zLm1pZENsaWNrIDogJC5tYWduaWZpY1BvcHVwLmRlZmF1bHRzLm1pZENsaWNrO1xuXG5cbiAgICAgIGlmICghbWlkQ2xpY2sgJiYgKGUud2hpY2ggPT09IDIgfHwgZS5jdHJsS2V5IHx8IGUubWV0YUtleSB8fCBlLmFsdEtleSB8fCBlLnNoaWZ0S2V5KSkge1xuICAgICAgICByZXR1cm47XG4gICAgICB9XG5cbiAgICAgIHZhciBkaXNhYmxlT24gPSBvcHRpb25zLmRpc2FibGVPbiAhPT0gdW5kZWZpbmVkID8gb3B0aW9ucy5kaXNhYmxlT24gOiAkLm1hZ25pZmljUG9wdXAuZGVmYXVsdHMuZGlzYWJsZU9uO1xuXG4gICAgICBpZiAoZGlzYWJsZU9uKSB7XG4gICAgICAgIGlmICgkLmlzRnVuY3Rpb24oZGlzYWJsZU9uKSkge1xuICAgICAgICAgIGlmICghZGlzYWJsZU9uLmNhbGwobWZwKSkge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgICAgfVxuICAgICAgICB9IGVsc2UgeyAvLyBlbHNlIGl0J3MgbnVtYmVyXG4gICAgICAgICAgaWYgKF93aW5kb3cud2lkdGgoKSA8IGRpc2FibGVPbikge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIGlmIChlLnR5cGUpIHtcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuXG4gICAgICAgIC8vIFRoaXMgd2lsbCBwcmV2ZW50IHBvcHVwIGZyb20gY2xvc2luZyBpZiBlbGVtZW50IGlzIGluc2lkZSBhbmQgcG9wdXAgaXMgYWxyZWFkeSBvcGVuZWRcbiAgICAgICAgaWYgKG1mcC5pc09wZW4pIHtcbiAgICAgICAgICBlLnN0b3BQcm9wYWdhdGlvbigpO1xuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIG9wdGlvbnMuZWwgPSAkKGUubWZwRWwpO1xuICAgICAgaWYgKG9wdGlvbnMuZGVsZWdhdGUpIHtcbiAgICAgICAgb3B0aW9ucy5pdGVtcyA9IGVsLmZpbmQob3B0aW9ucy5kZWxlZ2F0ZSk7XG4gICAgICB9XG4gICAgICBtZnAub3BlbihvcHRpb25zKTtcbiAgICB9LFxuXG5cbiAgICAvKipcbiAgICAgKiBVcGRhdGVzIHRleHQgb24gcHJlbG9hZGVyXG4gICAgICovXG4gICAgdXBkYXRlU3RhdHVzOiBmdW5jdGlvbiAoc3RhdHVzLCB0ZXh0KSB7XG5cbiAgICAgIGlmIChtZnAucHJlbG9hZGVyKSB7XG4gICAgICAgIGlmIChfcHJldlN0YXR1cyAhPT0gc3RhdHVzKSB7XG4gICAgICAgICAgbWZwLmNvbnRhaW5lci5yZW1vdmVDbGFzcygnbWZwLXMtJyArIF9wcmV2U3RhdHVzKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmICghdGV4dCAmJiBzdGF0dXMgPT09ICdsb2FkaW5nJykge1xuICAgICAgICAgIHRleHQgPSBtZnAuc3QudExvYWRpbmc7XG4gICAgICAgIH1cblxuICAgICAgICB2YXIgZGF0YSA9IHtcbiAgICAgICAgICBzdGF0dXM6IHN0YXR1cyxcbiAgICAgICAgICB0ZXh0OiB0ZXh0XG4gICAgICAgIH07XG4gICAgICAgIC8vIGFsbG93cyB0byBtb2RpZnkgc3RhdHVzXG4gICAgICAgIF9tZnBUcmlnZ2VyKCdVcGRhdGVTdGF0dXMnLCBkYXRhKTtcblxuICAgICAgICBzdGF0dXMgPSBkYXRhLnN0YXR1cztcbiAgICAgICAgdGV4dCA9IGRhdGEudGV4dDtcblxuICAgICAgICBtZnAucHJlbG9hZGVyLmh0bWwodGV4dCk7XG5cbiAgICAgICAgbWZwLnByZWxvYWRlci5maW5kKCdhJykub24oJ2NsaWNrJywgZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICBlLnN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbigpO1xuICAgICAgICB9KTtcblxuICAgICAgICBtZnAuY29udGFpbmVyLmFkZENsYXNzKCdtZnAtcy0nICsgc3RhdHVzKTtcbiAgICAgICAgX3ByZXZTdGF0dXMgPSBzdGF0dXM7XG4gICAgICB9XG4gICAgfSxcblxuXG4gICAgLypcbiAgICAgIFwiUHJpdmF0ZVwiIGhlbHBlcnMgdGhhdCBhcmVuJ3QgcHJpdmF0ZSBhdCBhbGxcbiAgICAgKi9cbiAgICAvLyBDaGVjayB0byBjbG9zZSBwb3B1cCBvciBub3RcbiAgICAvLyBcInRhcmdldFwiIGlzIGFuIGVsZW1lbnQgdGhhdCB3YXMgY2xpY2tlZFxuICAgIF9jaGVja0lmQ2xvc2U6IGZ1bmN0aW9uICh0YXJnZXQpIHtcblxuICAgICAgaWYgKCQodGFyZ2V0KS5oYXNDbGFzcyhQUkVWRU5UX0NMT1NFX0NMQVNTKSkge1xuICAgICAgICByZXR1cm47XG4gICAgICB9XG5cbiAgICAgIHZhciBjbG9zZU9uQ29udGVudCA9IG1mcC5zdC5jbG9zZU9uQ29udGVudENsaWNrO1xuICAgICAgdmFyIGNsb3NlT25CZyA9IG1mcC5zdC5jbG9zZU9uQmdDbGljaztcblxuICAgICAgaWYgKGNsb3NlT25Db250ZW50ICYmIGNsb3NlT25CZykge1xuICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgIH0gZWxzZSB7XG5cbiAgICAgICAgLy8gV2UgY2xvc2UgdGhlIHBvcHVwIGlmIGNsaWNrIGlzIG9uIGNsb3NlIGJ1dHRvbiBvciBvbiBwcmVsb2FkZXIuIE9yIGlmIHRoZXJlIGlzIG5vIGNvbnRlbnQuXG4gICAgICAgIGlmICghbWZwLmNvbnRlbnQgfHwgJCh0YXJnZXQpLmhhc0NsYXNzKCdtZnAtY2xvc2UnKSB8fCAobWZwLnByZWxvYWRlciAmJiB0YXJnZXQgPT09IG1mcC5wcmVsb2FkZXJbMF0pKSB7XG4gICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBpZiBjbGljayBpcyBvdXRzaWRlIHRoZSBjb250ZW50XG4gICAgICAgIGlmICgodGFyZ2V0ICE9PSBtZnAuY29udGVudFswXSAmJiAhJC5jb250YWlucyhtZnAuY29udGVudFswXSwgdGFyZ2V0KSkpIHtcbiAgICAgICAgICBpZiAoY2xvc2VPbkJnKSB7XG4gICAgICAgICAgICAvLyBsYXN0IGNoZWNrLCBpZiB0aGUgY2xpY2tlZCBlbGVtZW50IGlzIGluIERPTSwgKGluIGNhc2UgaXQncyByZW1vdmVkIG9uY2xpY2spXG4gICAgICAgICAgICBpZiAoJC5jb250YWlucyhkb2N1bWVudCwgdGFyZ2V0KSkge1xuICAgICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSBpZiAoY2xvc2VPbkNvbnRlbnQpIHtcbiAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfVxuXG4gICAgICB9XG4gICAgICByZXR1cm4gZmFsc2U7XG4gICAgfSxcbiAgICBfYWRkQ2xhc3NUb01GUDogZnVuY3Rpb24gKGNOYW1lKSB7XG4gICAgICBtZnAuYmdPdmVybGF5LmFkZENsYXNzKGNOYW1lKTtcbiAgICAgIG1mcC53cmFwLmFkZENsYXNzKGNOYW1lKTtcbiAgICB9LFxuICAgIF9yZW1vdmVDbGFzc0Zyb21NRlA6IGZ1bmN0aW9uIChjTmFtZSkge1xuICAgICAgdGhpcy5iZ092ZXJsYXkucmVtb3ZlQ2xhc3MoY05hbWUpO1xuICAgICAgbWZwLndyYXAucmVtb3ZlQ2xhc3MoY05hbWUpO1xuICAgIH0sXG4gICAgX2hhc1Njcm9sbEJhcjogZnVuY3Rpb24gKHdpbkhlaWdodCkge1xuICAgICAgcmV0dXJuICgobWZwLmlzSUU3ID8gX2RvY3VtZW50LmhlaWdodCgpIDogZG9jdW1lbnQuYm9keS5zY3JvbGxIZWlnaHQpID4gKHdpbkhlaWdodCB8fCBfd2luZG93LmhlaWdodCgpKSk7XG4gICAgfSxcbiAgICBfc2V0Rm9jdXM6IGZ1bmN0aW9uICgpIHtcbiAgICAgIChtZnAuc3QuZm9jdXMgPyBtZnAuY29udGVudC5maW5kKG1mcC5zdC5mb2N1cykuZXEoMCkgOiBtZnAud3JhcCkuZm9jdXMoKTtcbiAgICB9LFxuICAgIF9vbkZvY3VzSW46IGZ1bmN0aW9uIChlKSB7XG4gICAgICBpZiAoZS50YXJnZXQgIT09IG1mcC53cmFwWzBdICYmICEkLmNvbnRhaW5zKG1mcC53cmFwWzBdLCBlLnRhcmdldCkpIHtcbiAgICAgICAgbWZwLl9zZXRGb2N1cygpO1xuICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICB9XG4gICAgfSxcbiAgICBfcGFyc2VNYXJrdXA6IGZ1bmN0aW9uICh0ZW1wbGF0ZSwgdmFsdWVzLCBpdGVtKSB7XG4gICAgICB2YXIgYXJyO1xuICAgICAgaWYgKGl0ZW0uZGF0YSkge1xuICAgICAgICB2YWx1ZXMgPSAkLmV4dGVuZChpdGVtLmRhdGEsIHZhbHVlcyk7XG4gICAgICB9XG4gICAgICBfbWZwVHJpZ2dlcihNQVJLVVBfUEFSU0VfRVZFTlQsIFt0ZW1wbGF0ZSwgdmFsdWVzLCBpdGVtXSk7XG5cbiAgICAgICQuZWFjaCh2YWx1ZXMsIGZ1bmN0aW9uIChrZXksIHZhbHVlKSB7XG4gICAgICAgIGlmICh2YWx1ZSA9PT0gdW5kZWZpbmVkIHx8IHZhbHVlID09PSBmYWxzZSkge1xuICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9XG4gICAgICAgIGFyciA9IGtleS5zcGxpdCgnXycpO1xuICAgICAgICBpZiAoYXJyLmxlbmd0aCA+IDEpIHtcbiAgICAgICAgICB2YXIgZWwgPSB0ZW1wbGF0ZS5maW5kKEVWRU5UX05TICsgJy0nICsgYXJyWzBdKTtcblxuICAgICAgICAgIGlmIChlbC5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgICB2YXIgYXR0ciA9IGFyclsxXTtcbiAgICAgICAgICAgIGlmIChhdHRyID09PSAncmVwbGFjZVdpdGgnKSB7XG4gICAgICAgICAgICAgIGlmIChlbFswXSAhPT0gdmFsdWVbMF0pIHtcbiAgICAgICAgICAgICAgICBlbC5yZXBsYWNlV2l0aCh2YWx1ZSk7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0gZWxzZSBpZiAoYXR0ciA9PT0gJ2ltZycpIHtcbiAgICAgICAgICAgICAgaWYgKGVsLmlzKCdpbWcnKSkge1xuICAgICAgICAgICAgICAgIGVsLmF0dHIoJ3NyYycsIHZhbHVlKTtcbiAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICBlbC5yZXBsYWNlV2l0aCgkKCc8aW1nPicpLmF0dHIoJ3NyYycsIHZhbHVlKS5hdHRyKCdjbGFzcycsIGVsLmF0dHIoJ2NsYXNzJykpKTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgZWwuYXR0cihhcnJbMV0sIHZhbHVlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9XG5cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICB0ZW1wbGF0ZS5maW5kKEVWRU5UX05TICsgJy0nICsga2V5KS5odG1sKHZhbHVlKTtcbiAgICAgICAgfVxuICAgICAgfSk7XG4gICAgfSxcblxuICAgIF9nZXRTY3JvbGxiYXJTaXplOiBmdW5jdGlvbiAoKSB7XG4gICAgICAvLyB0aHggRGF2aWRcbiAgICAgIGlmIChtZnAuc2Nyb2xsYmFyU2l6ZSA9PT0gdW5kZWZpbmVkKSB7XG4gICAgICAgIHZhciBzY3JvbGxEaXYgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KFwiZGl2XCIpO1xuICAgICAgICBzY3JvbGxEaXYuc3R5bGUuY3NzVGV4dCA9ICd3aWR0aDogOTlweDsgaGVpZ2h0OiA5OXB4OyBvdmVyZmxvdzogc2Nyb2xsOyBwb3NpdGlvbjogYWJzb2x1dGU7IHRvcDogLTk5OTlweDsnO1xuICAgICAgICBkb2N1bWVudC5ib2R5LmFwcGVuZENoaWxkKHNjcm9sbERpdik7XG4gICAgICAgIG1mcC5zY3JvbGxiYXJTaXplID0gc2Nyb2xsRGl2Lm9mZnNldFdpZHRoIC0gc2Nyb2xsRGl2LmNsaWVudFdpZHRoO1xuICAgICAgICBkb2N1bWVudC5ib2R5LnJlbW92ZUNoaWxkKHNjcm9sbERpdik7XG4gICAgICB9XG4gICAgICByZXR1cm4gbWZwLnNjcm9sbGJhclNpemU7XG4gICAgfVxuXG4gIH07IC8qIE1hZ25pZmljUG9wdXAgY29yZSBwcm90b3R5cGUgZW5kICovXG5cblxuICAvKipcbiAgICogUHVibGljIHN0YXRpYyBmdW5jdGlvbnNcbiAgICovXG4gICQubWFnbmlmaWNQb3B1cCA9IHtcbiAgICBpbnN0YW5jZTogbnVsbCxcbiAgICBwcm90bzogTWFnbmlmaWNQb3B1cC5wcm90b3R5cGUsXG4gICAgbW9kdWxlczogW10sXG5cbiAgICBvcGVuOiBmdW5jdGlvbiAob3B0aW9ucywgaW5kZXgpIHtcbiAgICAgIF9jaGVja0luc3RhbmNlKCk7XG5cbiAgICAgIGlmICghb3B0aW9ucykge1xuICAgICAgICBvcHRpb25zID0ge307XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBvcHRpb25zID0gJC5leHRlbmQodHJ1ZSwge30sIG9wdGlvbnMpO1xuICAgICAgfVxuXG4gICAgICBvcHRpb25zLmlzT2JqID0gdHJ1ZTtcbiAgICAgIG9wdGlvbnMuaW5kZXggPSBpbmRleCB8fCAwO1xuICAgICAgcmV0dXJuIHRoaXMuaW5zdGFuY2Uub3BlbihvcHRpb25zKTtcbiAgICB9LFxuXG4gICAgY2xvc2U6IGZ1bmN0aW9uICgpIHtcbiAgICAgIHJldHVybiAkLm1hZ25pZmljUG9wdXAuaW5zdGFuY2UgJiYgJC5tYWduaWZpY1BvcHVwLmluc3RhbmNlLmNsb3NlKCk7XG4gICAgfSxcblxuICAgIHJlZ2lzdGVyTW9kdWxlOiBmdW5jdGlvbiAobmFtZSwgbW9kdWxlKSB7XG4gICAgICBpZiAobW9kdWxlLm9wdGlvbnMpIHtcbiAgICAgICAgJC5tYWduaWZpY1BvcHVwLmRlZmF1bHRzW25hbWVdID0gbW9kdWxlLm9wdGlvbnM7XG4gICAgICB9XG4gICAgICAkLmV4dGVuZCh0aGlzLnByb3RvLCBtb2R1bGUucHJvdG8pO1xuICAgICAgdGhpcy5tb2R1bGVzLnB1c2gobmFtZSk7XG4gICAgfSxcblxuICAgIGRlZmF1bHRzOiB7XG5cbiAgICAgIC8vIEluZm8gYWJvdXQgb3B0aW9ucyBpcyBpbiBkb2NzOlxuICAgICAgLy8gaHR0cDovL2RpbXNlbWVub3YuY29tL3BsdWdpbnMvbWFnbmlmaWMtcG9wdXAvZG9jdW1lbnRhdGlvbi5odG1sI29wdGlvbnNcblxuICAgICAgZGlzYWJsZU9uOiAwLFxuXG4gICAgICBrZXk6IG51bGwsXG5cbiAgICAgIG1pZENsaWNrOiBmYWxzZSxcblxuICAgICAgbWFpbkNsYXNzOiAnJyxcblxuICAgICAgcHJlbG9hZGVyOiB0cnVlLFxuXG4gICAgICBmb2N1czogJycsIC8vIENTUyBzZWxlY3RvciBvZiBpbnB1dCB0byBmb2N1cyBhZnRlciBwb3B1cCBpcyBvcGVuZWRcblxuICAgICAgY2xvc2VPbkNvbnRlbnRDbGljazogZmFsc2UsXG5cbiAgICAgIGNsb3NlT25CZ0NsaWNrOiB0cnVlLFxuXG4gICAgICBjbG9zZUJ0bkluc2lkZTogdHJ1ZSxcblxuICAgICAgc2hvd0Nsb3NlQnRuOiB0cnVlLFxuXG4gICAgICBlbmFibGVFc2NhcGVLZXk6IHRydWUsXG5cbiAgICAgIG1vZGFsOiBmYWxzZSxcblxuICAgICAgYWxpZ25Ub3A6IGZhbHNlLFxuXG4gICAgICByZW1vdmFsRGVsYXk6IDAsXG5cbiAgICAgIHByZXBlbmRUbzogbnVsbCxcblxuICAgICAgZml4ZWRDb250ZW50UG9zOiAnYXV0bycsXG5cbiAgICAgIGZpeGVkQmdQb3M6ICdhdXRvJyxcblxuICAgICAgb3ZlcmZsb3dZOiAnYXV0bycsXG5cbiAgICAgIGNsb3NlTWFya3VwOiAnPGJ1dHRvbiB0aXRsZT1cIiV0aXRsZSVcIiB0eXBlPVwiYnV0dG9uXCIgY2xhc3M9XCJtZnAtY2xvc2VcIj4mIzIxNTs8L2J1dHRvbj4nLFxuXG4gICAgICB0Q2xvc2U6ICdDbG9zZSAoRXNjKScsXG5cbiAgICAgIHRMb2FkaW5nOiAnTG9hZGluZy4uLicsXG5cbiAgICAgIGF1dG9Gb2N1c0xhc3Q6IHRydWVcblxuICAgIH1cbiAgfTtcblxuXG4gICQuZm4ubWFnbmlmaWNQb3B1cCA9IGZ1bmN0aW9uIChvcHRpb25zKSB7XG4gICAgX2NoZWNrSW5zdGFuY2UoKTtcblxuICAgIHZhciBqcUVsID0gJCh0aGlzKTtcblxuICAgIC8vIFdlIGNhbGwgc29tZSBBUEkgbWV0aG9kIG9mIGZpcnN0IHBhcmFtIGlzIGEgc3RyaW5nXG4gICAgaWYgKHR5cGVvZiBvcHRpb25zID09PSBcInN0cmluZ1wiKSB7XG5cbiAgICAgIGlmIChvcHRpb25zID09PSAnb3BlbicpIHtcbiAgICAgICAgdmFyIGl0ZW1zLFxuICAgICAgICAgIGl0ZW1PcHRzID0gX2lzSlEgPyBqcUVsLmRhdGEoJ21hZ25pZmljUG9wdXAnKSA6IGpxRWxbMF0ubWFnbmlmaWNQb3B1cCxcbiAgICAgICAgICBpbmRleCA9IHBhcnNlSW50KGFyZ3VtZW50c1sxXSwgMTApIHx8IDA7XG5cbiAgICAgICAgaWYgKGl0ZW1PcHRzLml0ZW1zKSB7XG4gICAgICAgICAgaXRlbXMgPSBpdGVtT3B0cy5pdGVtc1tpbmRleF07XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgaXRlbXMgPSBqcUVsO1xuICAgICAgICAgIGlmIChpdGVtT3B0cy5kZWxlZ2F0ZSkge1xuICAgICAgICAgICAgaXRlbXMgPSBpdGVtcy5maW5kKGl0ZW1PcHRzLmRlbGVnYXRlKTtcbiAgICAgICAgICB9XG4gICAgICAgICAgaXRlbXMgPSBpdGVtcy5lcShpbmRleCk7XG4gICAgICAgIH1cbiAgICAgICAgbWZwLl9vcGVuQ2xpY2soe21mcEVsOiBpdGVtc30sIGpxRWwsIGl0ZW1PcHRzKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIGlmIChtZnAuaXNPcGVuKVxuICAgICAgICAgIG1mcFtvcHRpb25zXS5hcHBseShtZnAsIEFycmF5LnByb3RvdHlwZS5zbGljZS5jYWxsKGFyZ3VtZW50cywgMSkpO1xuICAgICAgfVxuXG4gICAgfSBlbHNlIHtcbiAgICAgIC8vIGNsb25lIG9wdGlvbnMgb2JqXG4gICAgICBvcHRpb25zID0gJC5leHRlbmQodHJ1ZSwge30sIG9wdGlvbnMpO1xuXG4gICAgICAvKlxuICAgICAgICogQXMgWmVwdG8gZG9lc24ndCBzdXBwb3J0IC5kYXRhKCkgbWV0aG9kIGZvciBvYmplY3RzXG4gICAgICAgKiBhbmQgaXQgd29ya3Mgb25seSBpbiBub3JtYWwgYnJvd3NlcnNcbiAgICAgICAqIHdlIGFzc2lnbiBcIm9wdGlvbnNcIiBvYmplY3QgZGlyZWN0bHkgdG8gdGhlIERPTSBlbGVtZW50LiBGVFchXG4gICAgICAgKi9cbiAgICAgIGlmIChfaXNKUSkge1xuICAgICAgICBqcUVsLmRhdGEoJ21hZ25pZmljUG9wdXAnLCBvcHRpb25zKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIGpxRWxbMF0ubWFnbmlmaWNQb3B1cCA9IG9wdGlvbnM7XG4gICAgICB9XG5cbiAgICAgIG1mcC5hZGRHcm91cChqcUVsLCBvcHRpb25zKTtcblxuICAgIH1cbiAgICByZXR1cm4ganFFbDtcbiAgfTtcblxuICAvKj4+Y29yZSovXG5cbiAgLyo+PmlubGluZSovXG5cbiAgdmFyIElOTElORV9OUyA9ICdpbmxpbmUnLFxuICAgIF9oaWRkZW5DbGFzcyxcbiAgICBfaW5saW5lUGxhY2Vob2xkZXIsXG4gICAgX2xhc3RJbmxpbmVFbGVtZW50LFxuICAgIF9wdXRJbmxpbmVFbGVtZW50c0JhY2sgPSBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAoX2xhc3RJbmxpbmVFbGVtZW50KSB7XG4gICAgICAgIF9pbmxpbmVQbGFjZWhvbGRlci5hZnRlcihfbGFzdElubGluZUVsZW1lbnQuYWRkQ2xhc3MoX2hpZGRlbkNsYXNzKSkuZGV0YWNoKCk7XG4gICAgICAgIF9sYXN0SW5saW5lRWxlbWVudCA9IG51bGw7XG4gICAgICB9XG4gICAgfTtcblxuICAkLm1hZ25pZmljUG9wdXAucmVnaXN0ZXJNb2R1bGUoSU5MSU5FX05TLCB7XG4gICAgb3B0aW9uczoge1xuICAgICAgaGlkZGVuQ2xhc3M6ICdoaWRlJywgLy8gd2lsbCBiZSBhcHBlbmRlZCB3aXRoIGBtZnAtYCBwcmVmaXhcbiAgICAgIG1hcmt1cDogJycsXG4gICAgICB0Tm90Rm91bmQ6ICdDb250ZW50IG5vdCBmb3VuZCdcbiAgICB9LFxuICAgIHByb3RvOiB7XG5cbiAgICAgIGluaXRJbmxpbmU6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgbWZwLnR5cGVzLnB1c2goSU5MSU5FX05TKTtcblxuICAgICAgICBfbWZwT24oQ0xPU0VfRVZFTlQgKyAnLicgKyBJTkxJTkVfTlMsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICBfcHV0SW5saW5lRWxlbWVudHNCYWNrKCk7XG4gICAgICAgIH0pO1xuICAgICAgfSxcblxuICAgICAgZ2V0SW5saW5lOiBmdW5jdGlvbiAoaXRlbSwgdGVtcGxhdGUpIHtcblxuICAgICAgICBfcHV0SW5saW5lRWxlbWVudHNCYWNrKCk7XG5cbiAgICAgICAgaWYgKGl0ZW0uc3JjKSB7XG4gICAgICAgICAgdmFyIGlubGluZVN0ID0gbWZwLnN0LmlubGluZSxcbiAgICAgICAgICAgIGVsID0gJChpdGVtLnNyYyk7XG5cbiAgICAgICAgICBpZiAoZWwubGVuZ3RoKSB7XG5cbiAgICAgICAgICAgIC8vIElmIHRhcmdldCBlbGVtZW50IGhhcyBwYXJlbnQgLSB3ZSByZXBsYWNlIGl0IHdpdGggcGxhY2Vob2xkZXIgYW5kIHB1dCBpdCBiYWNrIGFmdGVyIHBvcHVwIGlzIGNsb3NlZFxuICAgICAgICAgICAgdmFyIHBhcmVudCA9IGVsWzBdLnBhcmVudE5vZGU7XG4gICAgICAgICAgICBpZiAocGFyZW50ICYmIHBhcmVudC50YWdOYW1lKSB7XG4gICAgICAgICAgICAgIGlmICghX2lubGluZVBsYWNlaG9sZGVyKSB7XG4gICAgICAgICAgICAgICAgX2hpZGRlbkNsYXNzID0gaW5saW5lU3QuaGlkZGVuQ2xhc3M7XG4gICAgICAgICAgICAgICAgX2lubGluZVBsYWNlaG9sZGVyID0gX2dldEVsKF9oaWRkZW5DbGFzcyk7XG4gICAgICAgICAgICAgICAgX2hpZGRlbkNsYXNzID0gJ21mcC0nICsgX2hpZGRlbkNsYXNzO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIC8vIHJlcGxhY2UgdGFyZ2V0IGlubGluZSBlbGVtZW50IHdpdGggcGxhY2Vob2xkZXJcbiAgICAgICAgICAgICAgX2xhc3RJbmxpbmVFbGVtZW50ID0gZWwuYWZ0ZXIoX2lubGluZVBsYWNlaG9sZGVyKS5kZXRhY2goKS5yZW1vdmVDbGFzcyhfaGlkZGVuQ2xhc3MpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBtZnAudXBkYXRlU3RhdHVzKCdyZWFkeScpO1xuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBtZnAudXBkYXRlU3RhdHVzKCdlcnJvcicsIGlubGluZVN0LnROb3RGb3VuZCk7XG4gICAgICAgICAgICBlbCA9ICQoJzxkaXY+Jyk7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgaXRlbS5pbmxpbmVFbGVtZW50ID0gZWw7XG4gICAgICAgICAgcmV0dXJuIGVsO1xuICAgICAgICB9XG5cbiAgICAgICAgbWZwLnVwZGF0ZVN0YXR1cygncmVhZHknKTtcbiAgICAgICAgbWZwLl9wYXJzZU1hcmt1cCh0ZW1wbGF0ZSwge30sIGl0ZW0pO1xuICAgICAgICByZXR1cm4gdGVtcGxhdGU7XG4gICAgICB9XG4gICAgfVxuICB9KTtcblxuICAvKj4+aW5saW5lKi9cblxuICAvKj4+YWpheCovXG4gIHZhciBBSkFYX05TID0gJ2FqYXgnLFxuICAgIF9hamF4Q3VyLFxuICAgIF9yZW1vdmVBamF4Q3Vyc29yID0gZnVuY3Rpb24gKCkge1xuICAgICAgaWYgKF9hamF4Q3VyKSB7XG4gICAgICAgICQoZG9jdW1lbnQuYm9keSkucmVtb3ZlQ2xhc3MoX2FqYXhDdXIpO1xuICAgICAgfVxuICAgIH0sXG4gICAgX2Rlc3Ryb3lBamF4UmVxdWVzdCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgIF9yZW1vdmVBamF4Q3Vyc29yKCk7XG4gICAgICBpZiAobWZwLnJlcSkge1xuICAgICAgICBtZnAucmVxLmFib3J0KCk7XG4gICAgICB9XG4gICAgfTtcblxuICAkLm1hZ25pZmljUG9wdXAucmVnaXN0ZXJNb2R1bGUoQUpBWF9OUywge1xuXG4gICAgb3B0aW9uczoge1xuICAgICAgc2V0dGluZ3M6IG51bGwsXG4gICAgICBjdXJzb3I6ICdtZnAtYWpheC1jdXInLFxuICAgICAgdEVycm9yOiAnPGEgaHJlZj1cIiV1cmwlXCI+VGhlIGNvbnRlbnQ8L2E+IGNvdWxkIG5vdCBiZSBsb2FkZWQuJ1xuICAgIH0sXG5cbiAgICBwcm90bzoge1xuICAgICAgaW5pdEFqYXg6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgbWZwLnR5cGVzLnB1c2goQUpBWF9OUyk7XG4gICAgICAgIF9hamF4Q3VyID0gbWZwLnN0LmFqYXguY3Vyc29yO1xuXG4gICAgICAgIF9tZnBPbihDTE9TRV9FVkVOVCArICcuJyArIEFKQVhfTlMsIF9kZXN0cm95QWpheFJlcXVlc3QpO1xuICAgICAgICBfbWZwT24oJ0JlZm9yZUNoYW5nZS4nICsgQUpBWF9OUywgX2Rlc3Ryb3lBamF4UmVxdWVzdCk7XG4gICAgICB9LFxuICAgICAgZ2V0QWpheDogZnVuY3Rpb24gKGl0ZW0pIHtcblxuICAgICAgICBpZiAoX2FqYXhDdXIpIHtcbiAgICAgICAgICAkKGRvY3VtZW50LmJvZHkpLmFkZENsYXNzKF9hamF4Q3VyKTtcbiAgICAgICAgfVxuXG4gICAgICAgIG1mcC51cGRhdGVTdGF0dXMoJ2xvYWRpbmcnKTtcblxuICAgICAgICB2YXIgb3B0cyA9ICQuZXh0ZW5kKHtcbiAgICAgICAgICB1cmw6IGl0ZW0uc3JjLFxuICAgICAgICAgIHN1Y2Nlc3M6IGZ1bmN0aW9uIChkYXRhLCB0ZXh0U3RhdHVzLCBqcVhIUikge1xuICAgICAgICAgICAgdmFyIHRlbXAgPSB7XG4gICAgICAgICAgICAgIGRhdGE6IGRhdGEsXG4gICAgICAgICAgICAgIHhocjoganFYSFJcbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgIF9tZnBUcmlnZ2VyKCdQYXJzZUFqYXgnLCB0ZW1wKTtcblxuICAgICAgICAgICAgbWZwLmFwcGVuZENvbnRlbnQoJCh0ZW1wLmRhdGEpLCBBSkFYX05TKTtcblxuICAgICAgICAgICAgaXRlbS5maW5pc2hlZCA9IHRydWU7XG5cbiAgICAgICAgICAgIF9yZW1vdmVBamF4Q3Vyc29yKCk7XG5cbiAgICAgICAgICAgIG1mcC5fc2V0Rm9jdXMoKTtcblxuICAgICAgICAgICAgc2V0VGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgIG1mcC53cmFwLmFkZENsYXNzKFJFQURZX0NMQVNTKTtcbiAgICAgICAgICAgIH0sIDE2KTtcblxuICAgICAgICAgICAgbWZwLnVwZGF0ZVN0YXR1cygncmVhZHknKTtcblxuICAgICAgICAgICAgX21mcFRyaWdnZXIoJ0FqYXhDb250ZW50QWRkZWQnKTtcbiAgICAgICAgICB9LFxuICAgICAgICAgIGVycm9yOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICBfcmVtb3ZlQWpheEN1cnNvcigpO1xuICAgICAgICAgICAgaXRlbS5maW5pc2hlZCA9IGl0ZW0ubG9hZEVycm9yID0gdHJ1ZTtcbiAgICAgICAgICAgIG1mcC51cGRhdGVTdGF0dXMoJ2Vycm9yJywgbWZwLnN0LmFqYXgudEVycm9yLnJlcGxhY2UoJyV1cmwlJywgaXRlbS5zcmMpKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0sIG1mcC5zdC5hamF4LnNldHRpbmdzKTtcblxuICAgICAgICBtZnAucmVxID0gJC5hamF4KG9wdHMpO1xuXG4gICAgICAgIHJldHVybiAnJztcbiAgICAgIH1cbiAgICB9XG4gIH0pO1xuXG4gIC8qPj5hamF4Ki9cblxuICAvKj4+aW1hZ2UqL1xuICB2YXIgX2ltZ0ludGVydmFsLFxuICAgIF9nZXRUaXRsZSA9IGZ1bmN0aW9uIChpdGVtKSB7XG4gICAgICBpZiAoaXRlbS5kYXRhICYmIGl0ZW0uZGF0YS50aXRsZSAhPT0gdW5kZWZpbmVkKVxuICAgICAgICByZXR1cm4gaXRlbS5kYXRhLnRpdGxlO1xuXG4gICAgICB2YXIgc3JjID0gbWZwLnN0LmltYWdlLnRpdGxlU3JjO1xuXG4gICAgICBpZiAoc3JjKSB7XG4gICAgICAgIGlmICgkLmlzRnVuY3Rpb24oc3JjKSkge1xuICAgICAgICAgIHJldHVybiBzcmMuY2FsbChtZnAsIGl0ZW0pO1xuICAgICAgICB9IGVsc2UgaWYgKGl0ZW0uZWwpIHtcbiAgICAgICAgICByZXR1cm4gaXRlbS5lbC5hdHRyKHNyYykgfHwgJyc7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICAgIHJldHVybiAnJztcbiAgICB9O1xuXG4gICQubWFnbmlmaWNQb3B1cC5yZWdpc3Rlck1vZHVsZSgnaW1hZ2UnLCB7XG5cbiAgICBvcHRpb25zOiB7XG4gICAgICBtYXJrdXA6ICc8ZGl2IGNsYXNzPVwibWZwLWZpZ3VyZVwiPicgK1xuICAgICAgICAnPGRpdiBjbGFzcz1cIm1mcC1jbG9zZVwiPjwvZGl2PicgK1xuICAgICAgICAnPGZpZ3VyZT4nICtcbiAgICAgICAgJzxkaXYgY2xhc3M9XCJtZnAtaW1nXCI+PC9kaXY+JyArXG4gICAgICAgICc8ZmlnY2FwdGlvbj4nICtcbiAgICAgICAgJzxkaXYgY2xhc3M9XCJtZnAtYm90dG9tLWJhclwiPicgK1xuICAgICAgICAnPGRpdiBjbGFzcz1cIm1mcC10aXRsZVwiPjwvZGl2PicgK1xuICAgICAgICAnPGRpdiBjbGFzcz1cIm1mcC1jb3VudGVyXCI+PC9kaXY+JyArXG4gICAgICAgICc8L2Rpdj4nICtcbiAgICAgICAgJzwvZmlnY2FwdGlvbj4nICtcbiAgICAgICAgJzwvZmlndXJlPicgK1xuICAgICAgICAnPC9kaXY+JyxcbiAgICAgIGN1cnNvcjogJ21mcC16b29tLW91dC1jdXInLFxuICAgICAgdGl0bGVTcmM6ICd0aXRsZScsXG4gICAgICB2ZXJ0aWNhbEZpdDogdHJ1ZSxcbiAgICAgIHRFcnJvcjogJzxhIGhyZWY9XCIldXJsJVwiPlRoZSBpbWFnZTwvYT4gY291bGQgbm90IGJlIGxvYWRlZC4nXG4gICAgfSxcblxuICAgIHByb3RvOiB7XG4gICAgICBpbml0SW1hZ2U6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdmFyIGltZ1N0ID0gbWZwLnN0LmltYWdlLFxuICAgICAgICAgIG5zID0gJy5pbWFnZSc7XG5cbiAgICAgICAgbWZwLnR5cGVzLnB1c2goJ2ltYWdlJyk7XG5cbiAgICAgICAgX21mcE9uKE9QRU5fRVZFTlQgKyBucywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIGlmIChtZnAuY3Vyckl0ZW0udHlwZSA9PT0gJ2ltYWdlJyAmJiBpbWdTdC5jdXJzb3IpIHtcbiAgICAgICAgICAgICQoZG9jdW1lbnQuYm9keSkuYWRkQ2xhc3MoaW1nU3QuY3Vyc29yKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuXG4gICAgICAgIF9tZnBPbihDTE9TRV9FVkVOVCArIG5zLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgaWYgKGltZ1N0LmN1cnNvcikge1xuICAgICAgICAgICAgJChkb2N1bWVudC5ib2R5KS5yZW1vdmVDbGFzcyhpbWdTdC5jdXJzb3IpO1xuICAgICAgICAgIH1cbiAgICAgICAgICBfd2luZG93Lm9mZigncmVzaXplJyArIEVWRU5UX05TKTtcbiAgICAgICAgfSk7XG5cbiAgICAgICAgX21mcE9uKCdSZXNpemUnICsgbnMsIG1mcC5yZXNpemVJbWFnZSk7XG4gICAgICAgIGlmIChtZnAuaXNMb3dJRSkge1xuICAgICAgICAgIF9tZnBPbignQWZ0ZXJDaGFuZ2UnLCBtZnAucmVzaXplSW1hZ2UpO1xuICAgICAgICB9XG4gICAgICB9LFxuICAgICAgcmVzaXplSW1hZ2U6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdmFyIGl0ZW0gPSBtZnAuY3Vyckl0ZW07XG4gICAgICAgIGlmICghaXRlbSB8fCAhaXRlbS5pbWcpIHJldHVybjtcblxuICAgICAgICBpZiAobWZwLnN0LmltYWdlLnZlcnRpY2FsRml0KSB7XG4gICAgICAgICAgdmFyIGRlY3IgPSAwO1xuICAgICAgICAgIC8vIGZpeCBib3gtc2l6aW5nIGluIGllNy84XG4gICAgICAgICAgaWYgKG1mcC5pc0xvd0lFKSB7XG4gICAgICAgICAgICBkZWNyID0gcGFyc2VJbnQoaXRlbS5pbWcuY3NzKCdwYWRkaW5nLXRvcCcpLCAxMCkgKyBwYXJzZUludChpdGVtLmltZy5jc3MoJ3BhZGRpbmctYm90dG9tJyksIDEwKTtcbiAgICAgICAgICB9XG4gICAgICAgICAgaXRlbS5pbWcuY3NzKCdtYXgtaGVpZ2h0JywgbWZwLndIIC0gZGVjcik7XG4gICAgICAgIH1cbiAgICAgIH0sXG4gICAgICBfb25JbWFnZUhhc1NpemU6IGZ1bmN0aW9uIChpdGVtKSB7XG4gICAgICAgIGlmIChpdGVtLmltZykge1xuXG4gICAgICAgICAgaXRlbS5oYXNTaXplID0gdHJ1ZTtcblxuICAgICAgICAgIGlmIChfaW1nSW50ZXJ2YWwpIHtcbiAgICAgICAgICAgIGNsZWFySW50ZXJ2YWwoX2ltZ0ludGVydmFsKTtcbiAgICAgICAgICB9XG5cbiAgICAgICAgICBpdGVtLmlzQ2hlY2tpbmdJbWdTaXplID0gZmFsc2U7XG5cbiAgICAgICAgICBfbWZwVHJpZ2dlcignSW1hZ2VIYXNTaXplJywgaXRlbSk7XG5cbiAgICAgICAgICBpZiAoaXRlbS5pbWdIaWRkZW4pIHtcbiAgICAgICAgICAgIGlmIChtZnAuY29udGVudClcbiAgICAgICAgICAgICAgbWZwLmNvbnRlbnQucmVtb3ZlQ2xhc3MoJ21mcC1sb2FkaW5nJyk7XG5cbiAgICAgICAgICAgIGl0ZW0uaW1nSGlkZGVuID0gZmFsc2U7XG4gICAgICAgICAgfVxuXG4gICAgICAgIH1cbiAgICAgIH0sXG5cbiAgICAgIC8qKlxuICAgICAgICogRnVuY3Rpb24gdGhhdCBsb29wcyB1bnRpbCB0aGUgaW1hZ2UgaGFzIHNpemUgdG8gZGlzcGxheSBlbGVtZW50cyB0aGF0IHJlbHkgb24gaXQgYXNhcFxuICAgICAgICovXG4gICAgICBmaW5kSW1hZ2VTaXplOiBmdW5jdGlvbiAoaXRlbSkge1xuXG4gICAgICAgIHZhciBjb3VudGVyID0gMCxcbiAgICAgICAgICBpbWcgPSBpdGVtLmltZ1swXSxcbiAgICAgICAgICBtZnBTZXRJbnRlcnZhbCA9IGZ1bmN0aW9uIChkZWxheSkge1xuXG4gICAgICAgICAgICBpZiAoX2ltZ0ludGVydmFsKSB7XG4gICAgICAgICAgICAgIGNsZWFySW50ZXJ2YWwoX2ltZ0ludGVydmFsKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIC8vIGRlY2VsZXJhdGluZyBpbnRlcnZhbCB0aGF0IGNoZWNrcyBmb3Igc2l6ZSBvZiBhbiBpbWFnZVxuICAgICAgICAgICAgX2ltZ0ludGVydmFsID0gc2V0SW50ZXJ2YWwoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICBpZiAoaW1nLm5hdHVyYWxXaWR0aCA+IDApIHtcbiAgICAgICAgICAgICAgICBtZnAuX29uSW1hZ2VIYXNTaXplKGl0ZW0pO1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgIGlmIChjb3VudGVyID4gMjAwKSB7XG4gICAgICAgICAgICAgICAgY2xlYXJJbnRlcnZhbChfaW1nSW50ZXJ2YWwpO1xuICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgY291bnRlcisrO1xuICAgICAgICAgICAgICBpZiAoY291bnRlciA9PT0gMykge1xuICAgICAgICAgICAgICAgIG1mcFNldEludGVydmFsKDEwKTtcbiAgICAgICAgICAgICAgfSBlbHNlIGlmIChjb3VudGVyID09PSA0MCkge1xuICAgICAgICAgICAgICAgIG1mcFNldEludGVydmFsKDUwKTtcbiAgICAgICAgICAgICAgfSBlbHNlIGlmIChjb3VudGVyID09PSAxMDApIHtcbiAgICAgICAgICAgICAgICBtZnBTZXRJbnRlcnZhbCg1MDApO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9LCBkZWxheSk7XG4gICAgICAgICAgfTtcblxuICAgICAgICBtZnBTZXRJbnRlcnZhbCgxKTtcbiAgICAgIH0sXG5cbiAgICAgIGdldEltYWdlOiBmdW5jdGlvbiAoaXRlbSwgdGVtcGxhdGUpIHtcblxuICAgICAgICB2YXIgZ3VhcmQgPSAwLFxuXG4gICAgICAgICAgLy8gaW1hZ2UgbG9hZCBjb21wbGV0ZSBoYW5kbGVyXG4gICAgICAgICAgb25Mb2FkQ29tcGxldGUgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICBpZiAoaXRlbSkge1xuICAgICAgICAgICAgICBpZiAoaXRlbS5pbWdbMF0uY29tcGxldGUpIHtcbiAgICAgICAgICAgICAgICBpdGVtLmltZy5vZmYoJy5tZnBsb2FkZXInKTtcblxuICAgICAgICAgICAgICAgIGlmIChpdGVtID09PSBtZnAuY3Vyckl0ZW0pIHtcbiAgICAgICAgICAgICAgICAgIG1mcC5fb25JbWFnZUhhc1NpemUoaXRlbSk7XG5cbiAgICAgICAgICAgICAgICAgIG1mcC51cGRhdGVTdGF0dXMoJ3JlYWR5Jyk7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgaXRlbS5oYXNTaXplID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICBpdGVtLmxvYWRlZCA9IHRydWU7XG5cbiAgICAgICAgICAgICAgICBfbWZwVHJpZ2dlcignSW1hZ2VMb2FkQ29tcGxldGUnKTtcblxuICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIC8vIGlmIGltYWdlIGNvbXBsZXRlIGNoZWNrIGZhaWxzIDIwMCB0aW1lcyAoMjAgc2VjKSwgd2UgYXNzdW1lIHRoYXQgdGhlcmUgd2FzIGFuIGVycm9yLlxuICAgICAgICAgICAgICAgIGd1YXJkKys7XG4gICAgICAgICAgICAgICAgaWYgKGd1YXJkIDwgMjAwKSB7XG4gICAgICAgICAgICAgICAgICBzZXRUaW1lb3V0KG9uTG9hZENvbXBsZXRlLCAxMDApO1xuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICBvbkxvYWRFcnJvcigpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICAgIH0sXG5cbiAgICAgICAgICAvLyBpbWFnZSBlcnJvciBoYW5kbGVyXG4gICAgICAgICAgb25Mb2FkRXJyb3IgPSBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICBpZiAoaXRlbSkge1xuICAgICAgICAgICAgICBpdGVtLmltZy5vZmYoJy5tZnBsb2FkZXInKTtcbiAgICAgICAgICAgICAgaWYgKGl0ZW0gPT09IG1mcC5jdXJySXRlbSkge1xuICAgICAgICAgICAgICAgIG1mcC5fb25JbWFnZUhhc1NpemUoaXRlbSk7XG4gICAgICAgICAgICAgICAgbWZwLnVwZGF0ZVN0YXR1cygnZXJyb3InLCBpbWdTdC50RXJyb3IucmVwbGFjZSgnJXVybCUnLCBpdGVtLnNyYykpO1xuICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgaXRlbS5oYXNTaXplID0gdHJ1ZTtcbiAgICAgICAgICAgICAgaXRlbS5sb2FkZWQgPSB0cnVlO1xuICAgICAgICAgICAgICBpdGVtLmxvYWRFcnJvciA9IHRydWU7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSxcbiAgICAgICAgICBpbWdTdCA9IG1mcC5zdC5pbWFnZTtcblxuXG4gICAgICAgIHZhciBlbCA9IHRlbXBsYXRlLmZpbmQoJy5tZnAtaW1nJyk7XG4gICAgICAgIGlmIChlbC5sZW5ndGgpIHtcbiAgICAgICAgICB2YXIgaW1nID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnaW1nJyk7XG4gICAgICAgICAgaW1nLmNsYXNzTmFtZSA9ICdtZnAtaW1nJztcbiAgICAgICAgICBpZiAoaXRlbS5lbCAmJiBpdGVtLmVsLmZpbmQoJ2ltZycpLmxlbmd0aCkge1xuICAgICAgICAgICAgaW1nLmFsdCA9IGl0ZW0uZWwuZmluZCgnaW1nJykuYXR0cignYWx0Jyk7XG4gICAgICAgICAgfVxuICAgICAgICAgIGl0ZW0uaW1nID0gJChpbWcpLm9uKCdsb2FkLm1mcGxvYWRlcicsIG9uTG9hZENvbXBsZXRlKS5vbignZXJyb3IubWZwbG9hZGVyJywgb25Mb2FkRXJyb3IpO1xuICAgICAgICAgIGltZy5zcmMgPSBpdGVtLnNyYztcblxuICAgICAgICAgIC8vIHdpdGhvdXQgY2xvbmUoKSBcImVycm9yXCIgZXZlbnQgaXMgbm90IGZpcmluZyB3aGVuIElNRyBpcyByZXBsYWNlZCBieSBuZXcgSU1HXG4gICAgICAgICAgLy8gVE9ETzogZmluZCBhIHdheSB0byBhdm9pZCBzdWNoIGNsb25pbmdcbiAgICAgICAgICBpZiAoZWwuaXMoJ2ltZycpKSB7XG4gICAgICAgICAgICBpdGVtLmltZyA9IGl0ZW0uaW1nLmNsb25lKCk7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgaW1nID0gaXRlbS5pbWdbMF07XG4gICAgICAgICAgaWYgKGltZy5uYXR1cmFsV2lkdGggPiAwKSB7XG4gICAgICAgICAgICBpdGVtLmhhc1NpemUgPSB0cnVlO1xuICAgICAgICAgIH0gZWxzZSBpZiAoIWltZy53aWR0aCkge1xuICAgICAgICAgICAgaXRlbS5oYXNTaXplID0gZmFsc2U7XG4gICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgbWZwLl9wYXJzZU1hcmt1cCh0ZW1wbGF0ZSwge1xuICAgICAgICAgIHRpdGxlOiBfZ2V0VGl0bGUoaXRlbSksXG4gICAgICAgICAgaW1nX3JlcGxhY2VXaXRoOiBpdGVtLmltZ1xuICAgICAgICB9LCBpdGVtKTtcblxuICAgICAgICBtZnAucmVzaXplSW1hZ2UoKTtcblxuICAgICAgICBpZiAoaXRlbS5oYXNTaXplKSB7XG4gICAgICAgICAgaWYgKF9pbWdJbnRlcnZhbCkgY2xlYXJJbnRlcnZhbChfaW1nSW50ZXJ2YWwpO1xuXG4gICAgICAgICAgaWYgKGl0ZW0ubG9hZEVycm9yKSB7XG4gICAgICAgICAgICB0ZW1wbGF0ZS5hZGRDbGFzcygnbWZwLWxvYWRpbmcnKTtcbiAgICAgICAgICAgIG1mcC51cGRhdGVTdGF0dXMoJ2Vycm9yJywgaW1nU3QudEVycm9yLnJlcGxhY2UoJyV1cmwlJywgaXRlbS5zcmMpKTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdGVtcGxhdGUucmVtb3ZlQ2xhc3MoJ21mcC1sb2FkaW5nJyk7XG4gICAgICAgICAgICBtZnAudXBkYXRlU3RhdHVzKCdyZWFkeScpO1xuICAgICAgICAgIH1cbiAgICAgICAgICByZXR1cm4gdGVtcGxhdGU7XG4gICAgICAgIH1cblxuICAgICAgICBtZnAudXBkYXRlU3RhdHVzKCdsb2FkaW5nJyk7XG4gICAgICAgIGl0ZW0ubG9hZGluZyA9IHRydWU7XG5cbiAgICAgICAgaWYgKCFpdGVtLmhhc1NpemUpIHtcbiAgICAgICAgICBpdGVtLmltZ0hpZGRlbiA9IHRydWU7XG4gICAgICAgICAgdGVtcGxhdGUuYWRkQ2xhc3MoJ21mcC1sb2FkaW5nJyk7XG4gICAgICAgICAgbWZwLmZpbmRJbWFnZVNpemUoaXRlbSk7XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gdGVtcGxhdGU7XG4gICAgICB9XG4gICAgfVxuICB9KTtcblxuICAvKj4+aW1hZ2UqL1xuXG4gIC8qPj56b29tKi9cbiAgdmFyIGhhc01velRyYW5zZm9ybSxcbiAgICBnZXRIYXNNb3pUcmFuc2Zvcm0gPSBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAoaGFzTW96VHJhbnNmb3JtID09PSB1bmRlZmluZWQpIHtcbiAgICAgICAgaGFzTW96VHJhbnNmb3JtID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgncCcpLnN0eWxlLk1velRyYW5zZm9ybSAhPT0gdW5kZWZpbmVkO1xuICAgICAgfVxuICAgICAgcmV0dXJuIGhhc01velRyYW5zZm9ybTtcbiAgICB9O1xuXG4gICQubWFnbmlmaWNQb3B1cC5yZWdpc3Rlck1vZHVsZSgnem9vbScsIHtcblxuICAgIG9wdGlvbnM6IHtcbiAgICAgIGVuYWJsZWQ6IGZhbHNlLFxuICAgICAgZWFzaW5nOiAnZWFzZS1pbi1vdXQnLFxuICAgICAgZHVyYXRpb246IDMwMCxcbiAgICAgIG9wZW5lcjogZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIGVsZW1lbnQuaXMoJ2ltZycpID8gZWxlbWVudCA6IGVsZW1lbnQuZmluZCgnaW1nJyk7XG4gICAgICB9XG4gICAgfSxcblxuICAgIHByb3RvOiB7XG5cbiAgICAgIGluaXRab29tOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHZhciB6b29tU3QgPSBtZnAuc3Quem9vbSxcbiAgICAgICAgICBucyA9ICcuem9vbScsXG4gICAgICAgICAgaW1hZ2U7XG5cbiAgICAgICAgaWYgKCF6b29tU3QuZW5hYmxlZCB8fCAhbWZwLnN1cHBvcnRzVHJhbnNpdGlvbikge1xuICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIHZhciBkdXJhdGlvbiA9IHpvb21TdC5kdXJhdGlvbixcbiAgICAgICAgICBnZXRFbFRvQW5pbWF0ZSA9IGZ1bmN0aW9uIChpbWFnZSkge1xuICAgICAgICAgICAgdmFyIG5ld0ltZyA9IGltYWdlLmNsb25lKCkucmVtb3ZlQXR0cignc3R5bGUnKS5yZW1vdmVBdHRyKCdjbGFzcycpLmFkZENsYXNzKCdtZnAtYW5pbWF0ZWQtaW1hZ2UnKSxcbiAgICAgICAgICAgICAgdHJhbnNpdGlvbiA9ICdhbGwgJyArICh6b29tU3QuZHVyYXRpb24gLyAxMDAwKSArICdzICcgKyB6b29tU3QuZWFzaW5nLFxuICAgICAgICAgICAgICBjc3NPYmogPSB7XG4gICAgICAgICAgICAgICAgcG9zaXRpb246ICdmaXhlZCcsXG4gICAgICAgICAgICAgICAgekluZGV4OiA5OTk5LFxuICAgICAgICAgICAgICAgIGxlZnQ6IDAsXG4gICAgICAgICAgICAgICAgdG9wOiAwLFxuICAgICAgICAgICAgICAgICctd2Via2l0LWJhY2tmYWNlLXZpc2liaWxpdHknOiAnaGlkZGVuJ1xuICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICB0ID0gJ3RyYW5zaXRpb24nO1xuXG4gICAgICAgICAgICBjc3NPYmpbJy13ZWJraXQtJyArIHRdID0gY3NzT2JqWyctbW96LScgKyB0XSA9IGNzc09ialsnLW8tJyArIHRdID0gY3NzT2JqW3RdID0gdHJhbnNpdGlvbjtcblxuICAgICAgICAgICAgbmV3SW1nLmNzcyhjc3NPYmopO1xuICAgICAgICAgICAgcmV0dXJuIG5ld0ltZztcbiAgICAgICAgICB9LFxuICAgICAgICAgIHNob3dNYWluQ29udGVudCA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIG1mcC5jb250ZW50LmNzcygndmlzaWJpbGl0eScsICd2aXNpYmxlJyk7XG4gICAgICAgICAgfSxcbiAgICAgICAgICBvcGVuVGltZW91dCxcbiAgICAgICAgICBhbmltYXRlZEltZztcblxuICAgICAgICBfbWZwT24oJ0J1aWxkQ29udHJvbHMnICsgbnMsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICBpZiAobWZwLl9hbGxvd1pvb20oKSkge1xuXG4gICAgICAgICAgICBjbGVhclRpbWVvdXQob3BlblRpbWVvdXQpO1xuICAgICAgICAgICAgbWZwLmNvbnRlbnQuY3NzKCd2aXNpYmlsaXR5JywgJ2hpZGRlbicpO1xuXG4gICAgICAgICAgICAvLyBCYXNpY2FsbHksIGFsbCBjb2RlIGJlbG93IGRvZXMgaXMgY2xvbmVzIGV4aXN0aW5nIGltYWdlLCBwdXRzIGluIG9uIHRvcCBvZiB0aGUgY3VycmVudCBvbmUgYW5kIGFuaW1hdGVkIGl0XG5cbiAgICAgICAgICAgIGltYWdlID0gbWZwLl9nZXRJdGVtVG9ab29tKCk7XG5cbiAgICAgICAgICAgIGlmICghaW1hZ2UpIHtcbiAgICAgICAgICAgICAgc2hvd01haW5Db250ZW50KCk7XG4gICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgYW5pbWF0ZWRJbWcgPSBnZXRFbFRvQW5pbWF0ZShpbWFnZSk7XG5cbiAgICAgICAgICAgIGFuaW1hdGVkSW1nLmNzcyhtZnAuX2dldE9mZnNldCgpKTtcblxuICAgICAgICAgICAgbWZwLndyYXAuYXBwZW5kKGFuaW1hdGVkSW1nKTtcblxuICAgICAgICAgICAgb3BlblRpbWVvdXQgPSBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgYW5pbWF0ZWRJbWcuY3NzKG1mcC5fZ2V0T2Zmc2V0KHRydWUpKTtcbiAgICAgICAgICAgICAgb3BlblRpbWVvdXQgPSBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcblxuICAgICAgICAgICAgICAgIHNob3dNYWluQ29udGVudCgpO1xuXG4gICAgICAgICAgICAgICAgc2V0VGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgICAgICBhbmltYXRlZEltZy5yZW1vdmUoKTtcbiAgICAgICAgICAgICAgICAgIGltYWdlID0gYW5pbWF0ZWRJbWcgPSBudWxsO1xuICAgICAgICAgICAgICAgICAgX21mcFRyaWdnZXIoJ1pvb21BbmltYXRpb25FbmRlZCcpO1xuICAgICAgICAgICAgICAgIH0sIDE2KTsgLy8gYXZvaWQgYmxpbmsgd2hlbiBzd2l0Y2hpbmcgaW1hZ2VzXG5cbiAgICAgICAgICAgICAgfSwgZHVyYXRpb24pOyAvLyB0aGlzIHRpbWVvdXQgZXF1YWxzIGFuaW1hdGlvbiBkdXJhdGlvblxuXG4gICAgICAgICAgICB9LCAxNik7IC8vIGJ5IGFkZGluZyB0aGlzIHRpbWVvdXQgd2UgYXZvaWQgc2hvcnQgZ2xpdGNoIGF0IHRoZSBiZWdpbm5pbmcgb2YgYW5pbWF0aW9uXG5cblxuICAgICAgICAgICAgLy8gTG90cyBvZiB0aW1lb3V0cy4uLlxuICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgICAgIF9tZnBPbihCRUZPUkVfQ0xPU0VfRVZFTlQgKyBucywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIGlmIChtZnAuX2FsbG93Wm9vbSgpKSB7XG5cbiAgICAgICAgICAgIGNsZWFyVGltZW91dChvcGVuVGltZW91dCk7XG5cbiAgICAgICAgICAgIG1mcC5zdC5yZW1vdmFsRGVsYXkgPSBkdXJhdGlvbjtcblxuICAgICAgICAgICAgaWYgKCFpbWFnZSkge1xuICAgICAgICAgICAgICBpbWFnZSA9IG1mcC5fZ2V0SXRlbVRvWm9vbSgpO1xuICAgICAgICAgICAgICBpZiAoIWltYWdlKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIGFuaW1hdGVkSW1nID0gZ2V0RWxUb0FuaW1hdGUoaW1hZ2UpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBhbmltYXRlZEltZy5jc3MobWZwLl9nZXRPZmZzZXQodHJ1ZSkpO1xuICAgICAgICAgICAgbWZwLndyYXAuYXBwZW5kKGFuaW1hdGVkSW1nKTtcbiAgICAgICAgICAgIG1mcC5jb250ZW50LmNzcygndmlzaWJpbGl0eScsICdoaWRkZW4nKTtcblxuICAgICAgICAgICAgc2V0VGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgIGFuaW1hdGVkSW1nLmNzcyhtZnAuX2dldE9mZnNldCgpKTtcbiAgICAgICAgICAgIH0sIDE2KTtcbiAgICAgICAgICB9XG5cbiAgICAgICAgfSk7XG5cbiAgICAgICAgX21mcE9uKENMT1NFX0VWRU5UICsgbnMsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICBpZiAobWZwLl9hbGxvd1pvb20oKSkge1xuICAgICAgICAgICAgc2hvd01haW5Db250ZW50KCk7XG4gICAgICAgICAgICBpZiAoYW5pbWF0ZWRJbWcpIHtcbiAgICAgICAgICAgICAgYW5pbWF0ZWRJbWcucmVtb3ZlKCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpbWFnZSA9IG51bGw7XG4gICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgIH0sXG5cbiAgICAgIF9hbGxvd1pvb206IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgcmV0dXJuIG1mcC5jdXJySXRlbS50eXBlID09PSAnaW1hZ2UnO1xuICAgICAgfSxcblxuICAgICAgX2dldEl0ZW1Ub1pvb206IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKG1mcC5jdXJySXRlbS5oYXNTaXplKSB7XG4gICAgICAgICAgcmV0dXJuIG1mcC5jdXJySXRlbS5pbWc7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgICB9LFxuXG4gICAgICAvLyBHZXQgZWxlbWVudCBwb3N0aW9uIHJlbGF0aXZlIHRvIHZpZXdwb3J0XG4gICAgICBfZ2V0T2Zmc2V0OiBmdW5jdGlvbiAoaXNMYXJnZSkge1xuICAgICAgICB2YXIgZWw7XG4gICAgICAgIGlmIChpc0xhcmdlKSB7XG4gICAgICAgICAgZWwgPSBtZnAuY3Vyckl0ZW0uaW1nO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIGVsID0gbWZwLnN0Lnpvb20ub3BlbmVyKG1mcC5jdXJySXRlbS5lbCB8fCBtZnAuY3Vyckl0ZW0pO1xuICAgICAgICB9XG5cbiAgICAgICAgdmFyIG9mZnNldCA9IGVsLm9mZnNldCgpO1xuICAgICAgICB2YXIgcGFkZGluZ1RvcCA9IHBhcnNlSW50KGVsLmNzcygncGFkZGluZy10b3AnKSwgMTApO1xuICAgICAgICB2YXIgcGFkZGluZ0JvdHRvbSA9IHBhcnNlSW50KGVsLmNzcygncGFkZGluZy1ib3R0b20nKSwgMTApO1xuICAgICAgICBvZmZzZXQudG9wIC09ICgkKHdpbmRvdykuc2Nyb2xsVG9wKCkgLSBwYWRkaW5nVG9wKTtcblxuXG4gICAgICAgIC8qXG5cbiAgICAgICAgQW5pbWF0aW5nIGxlZnQgKyB0b3AgKyB3aWR0aC9oZWlnaHQgbG9va3MgZ2xpdGNoeSBpbiBGaXJlZm94LCBidXQgcGVyZmVjdCBpbiBDaHJvbWUuIEFuZCB2aWNlLXZlcnNhLlxuXG4gICAgICAgICAqL1xuICAgICAgICB2YXIgb2JqID0ge1xuICAgICAgICAgIHdpZHRoOiBlbC53aWR0aCgpLFxuICAgICAgICAgIC8vIGZpeCBaZXB0byBoZWlnaHQrcGFkZGluZyBpc3N1ZVxuICAgICAgICAgIGhlaWdodDogKF9pc0pRID8gZWwuaW5uZXJIZWlnaHQoKSA6IGVsWzBdLm9mZnNldEhlaWdodCkgLSBwYWRkaW5nQm90dG9tIC0gcGFkZGluZ1RvcFxuICAgICAgICB9O1xuXG4gICAgICAgIC8vIEkgaGF0ZSB0byBkbyB0aGlzLCBidXQgdGhlcmUgaXMgbm8gYW5vdGhlciBvcHRpb25cbiAgICAgICAgaWYgKGdldEhhc01velRyYW5zZm9ybSgpKSB7XG4gICAgICAgICAgb2JqWyctbW96LXRyYW5zZm9ybSddID0gb2JqWyd0cmFuc2Zvcm0nXSA9ICd0cmFuc2xhdGUoJyArIG9mZnNldC5sZWZ0ICsgJ3B4LCcgKyBvZmZzZXQudG9wICsgJ3B4KSc7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgb2JqLmxlZnQgPSBvZmZzZXQubGVmdDtcbiAgICAgICAgICBvYmoudG9wID0gb2Zmc2V0LnRvcDtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gb2JqO1xuICAgICAgfVxuXG4gICAgfVxuICB9KTtcblxuXG4gIC8qPj56b29tKi9cblxuICAvKj4+aWZyYW1lKi9cblxuICB2YXIgSUZSQU1FX05TID0gJ2lmcmFtZScsXG4gICAgX2VtcHR5UGFnZSA9ICcvL2Fib3V0OmJsYW5rJyxcblxuICAgIF9maXhJZnJhbWVCdWdzID0gZnVuY3Rpb24gKGlzU2hvd2luZykge1xuICAgICAgaWYgKG1mcC5jdXJyVGVtcGxhdGVbSUZSQU1FX05TXSkge1xuICAgICAgICB2YXIgZWwgPSBtZnAuY3VyclRlbXBsYXRlW0lGUkFNRV9OU10uZmluZCgnaWZyYW1lJyk7XG4gICAgICAgIGlmIChlbC5sZW5ndGgpIHtcbiAgICAgICAgICAvLyByZXNldCBzcmMgYWZ0ZXIgdGhlIHBvcHVwIGlzIGNsb3NlZCB0byBhdm9pZCBcInZpZGVvIGtlZXBzIHBsYXlpbmcgYWZ0ZXIgcG9wdXAgaXMgY2xvc2VkXCIgYnVnXG4gICAgICAgICAgaWYgKCFpc1Nob3dpbmcpIHtcbiAgICAgICAgICAgIGVsWzBdLnNyYyA9IF9lbXB0eVBhZ2U7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgLy8gSUU4IGJsYWNrIHNjcmVlbiBidWcgZml4XG4gICAgICAgICAgaWYgKG1mcC5pc0lFOCkge1xuICAgICAgICAgICAgZWwuY3NzKCdkaXNwbGF5JywgaXNTaG93aW5nID8gJ2Jsb2NrJyA6ICdub25lJyk7XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9XG4gICAgfTtcblxuICAkLm1hZ25pZmljUG9wdXAucmVnaXN0ZXJNb2R1bGUoSUZSQU1FX05TLCB7XG5cbiAgICBvcHRpb25zOiB7XG4gICAgICBtYXJrdXA6ICc8ZGl2IGNsYXNzPVwibWZwLWlmcmFtZS1zY2FsZXJcIj4nICtcbiAgICAgICAgJzxkaXYgY2xhc3M9XCJtZnAtY2xvc2VcIj48L2Rpdj4nICtcbiAgICAgICAgJzxpZnJhbWUgY2xhc3M9XCJtZnAtaWZyYW1lXCIgc3JjPVwiLy9hYm91dDpibGFua1wiIGZyYW1lYm9yZGVyPVwiMFwiIGFsbG93ZnVsbHNjcmVlbj48L2lmcmFtZT4nICtcbiAgICAgICAgJzwvZGl2PicsXG5cbiAgICAgIHNyY0FjdGlvbjogJ2lmcmFtZV9zcmMnLFxuXG4gICAgICAvLyB3ZSBkb24ndCBjYXJlIGFuZCBzdXBwb3J0IG9ubHkgb25lIGRlZmF1bHQgdHlwZSBvZiBVUkwgYnkgZGVmYXVsdFxuICAgICAgcGF0dGVybnM6IHtcbiAgICAgICAgeW91dHViZToge1xuICAgICAgICAgIGluZGV4OiAneW91dHViZS5jb20nLFxuICAgICAgICAgIGlkOiAndj0nLFxuICAgICAgICAgIHNyYzogJy8vd3d3LnlvdXR1YmUuY29tL2VtYmVkLyVpZCU/YXV0b3BsYXk9MSdcbiAgICAgICAgfSxcbiAgICAgICAgdmltZW86IHtcbiAgICAgICAgICBpbmRleDogJ3ZpbWVvLmNvbS8nLFxuICAgICAgICAgIGlkOiAnLycsXG4gICAgICAgICAgc3JjOiAnLy9wbGF5ZXIudmltZW8uY29tL3ZpZGVvLyVpZCU/YXV0b3BsYXk9MSdcbiAgICAgICAgfSxcbiAgICAgICAgZ21hcHM6IHtcbiAgICAgICAgICBpbmRleDogJy8vbWFwcy5nb29nbGUuJyxcbiAgICAgICAgICBzcmM6ICclaWQlJm91dHB1dD1lbWJlZCdcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG5cbiAgICBwcm90bzoge1xuICAgICAgaW5pdElmcmFtZTogZnVuY3Rpb24gKCkge1xuICAgICAgICBtZnAudHlwZXMucHVzaChJRlJBTUVfTlMpO1xuXG4gICAgICAgIF9tZnBPbignQmVmb3JlQ2hhbmdlJywgZnVuY3Rpb24gKGUsIHByZXZUeXBlLCBuZXdUeXBlKSB7XG4gICAgICAgICAgaWYgKHByZXZUeXBlICE9PSBuZXdUeXBlKSB7XG4gICAgICAgICAgICBpZiAocHJldlR5cGUgPT09IElGUkFNRV9OUykge1xuICAgICAgICAgICAgICBfZml4SWZyYW1lQnVncygpOyAvLyBpZnJhbWUgaWYgcmVtb3ZlZFxuICAgICAgICAgICAgfSBlbHNlIGlmIChuZXdUeXBlID09PSBJRlJBTUVfTlMpIHtcbiAgICAgICAgICAgICAgX2ZpeElmcmFtZUJ1Z3ModHJ1ZSk7IC8vIGlmcmFtZSBpcyBzaG93aW5nXG4gICAgICAgICAgICB9XG4gICAgICAgICAgfS8vIGVsc2Uge1xuICAgICAgICAgIC8vIGlmcmFtZSBzb3VyY2UgaXMgc3dpdGNoZWQsIGRvbid0IGRvIGFueXRoaW5nXG4gICAgICAgICAgLy99XG4gICAgICAgIH0pO1xuXG4gICAgICAgIF9tZnBPbihDTE9TRV9FVkVOVCArICcuJyArIElGUkFNRV9OUywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIF9maXhJZnJhbWVCdWdzKCk7XG4gICAgICAgIH0pO1xuICAgICAgfSxcblxuICAgICAgZ2V0SWZyYW1lOiBmdW5jdGlvbiAoaXRlbSwgdGVtcGxhdGUpIHtcbiAgICAgICAgdmFyIGVtYmVkU3JjID0gaXRlbS5zcmM7XG4gICAgICAgIHZhciBpZnJhbWVTdCA9IG1mcC5zdC5pZnJhbWU7XG5cbiAgICAgICAgJC5lYWNoKGlmcmFtZVN0LnBhdHRlcm5zLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgaWYgKGVtYmVkU3JjLmluZGV4T2YodGhpcy5pbmRleCkgPiAtMSkge1xuICAgICAgICAgICAgaWYgKHRoaXMuaWQpIHtcbiAgICAgICAgICAgICAgaWYgKHR5cGVvZiB0aGlzLmlkID09PSAnc3RyaW5nJykge1xuICAgICAgICAgICAgICAgIGVtYmVkU3JjID0gZW1iZWRTcmMuc3Vic3RyKGVtYmVkU3JjLmxhc3RJbmRleE9mKHRoaXMuaWQpICsgdGhpcy5pZC5sZW5ndGgsIGVtYmVkU3JjLmxlbmd0aCk7XG4gICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgZW1iZWRTcmMgPSB0aGlzLmlkLmNhbGwodGhpcywgZW1iZWRTcmMpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbWJlZFNyYyA9IHRoaXMuc3JjLnJlcGxhY2UoJyVpZCUnLCBlbWJlZFNyYyk7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7IC8vIGJyZWFrO1xuICAgICAgICAgIH1cbiAgICAgICAgfSk7XG5cbiAgICAgICAgdmFyIGRhdGFPYmogPSB7fTtcbiAgICAgICAgaWYgKGlmcmFtZVN0LnNyY0FjdGlvbikge1xuICAgICAgICAgIGRhdGFPYmpbaWZyYW1lU3Quc3JjQWN0aW9uXSA9IGVtYmVkU3JjO1xuICAgICAgICB9XG4gICAgICAgIG1mcC5fcGFyc2VNYXJrdXAodGVtcGxhdGUsIGRhdGFPYmosIGl0ZW0pO1xuXG4gICAgICAgIG1mcC51cGRhdGVTdGF0dXMoJ3JlYWR5Jyk7XG5cbiAgICAgICAgcmV0dXJuIHRlbXBsYXRlO1xuICAgICAgfVxuICAgIH1cbiAgfSk7XG5cblxuICAvKj4+aWZyYW1lKi9cblxuICAvKj4+Z2FsbGVyeSovXG4gIC8qKlxuICAgKiBHZXQgbG9vcGVkIGluZGV4IGRlcGVuZGluZyBvbiBudW1iZXIgb2Ygc2xpZGVzXG4gICAqL1xuICB2YXIgX2dldExvb3BlZElkID0gZnVuY3Rpb24gKGluZGV4KSB7XG4gICAgICB2YXIgbnVtU2xpZGVzID0gbWZwLml0ZW1zLmxlbmd0aDtcbiAgICAgIGlmIChpbmRleCA+IG51bVNsaWRlcyAtIDEpIHtcbiAgICAgICAgcmV0dXJuIGluZGV4IC0gbnVtU2xpZGVzO1xuICAgICAgfSBlbHNlIGlmIChpbmRleCA8IDApIHtcbiAgICAgICAgcmV0dXJuIG51bVNsaWRlcyArIGluZGV4O1xuICAgICAgfVxuICAgICAgcmV0dXJuIGluZGV4O1xuICAgIH0sXG4gICAgX3JlcGxhY2VDdXJyVG90YWwgPSBmdW5jdGlvbiAodGV4dCwgY3VyciwgdG90YWwpIHtcbiAgICAgIHJldHVybiB0ZXh0LnJlcGxhY2UoLyVjdXJyJS9naSwgY3VyciArIDEpLnJlcGxhY2UoLyV0b3RhbCUvZ2ksIHRvdGFsKTtcbiAgICB9O1xuXG4gICQubWFnbmlmaWNQb3B1cC5yZWdpc3Rlck1vZHVsZSgnZ2FsbGVyeScsIHtcblxuICAgIG9wdGlvbnM6IHtcbiAgICAgIGVuYWJsZWQ6IGZhbHNlLFxuICAgICAgYXJyb3dNYXJrdXA6ICc8YnV0dG9uIHRpdGxlPVwiJXRpdGxlJVwiIHR5cGU9XCJidXR0b25cIiBjbGFzcz1cIm1mcC1hcnJvdyBtZnAtYXJyb3ctJWRpciVcIj48L2J1dHRvbj4nLFxuICAgICAgcHJlbG9hZDogWzAsIDJdLFxuICAgICAgbmF2aWdhdGVCeUltZ0NsaWNrOiB0cnVlLFxuICAgICAgYXJyb3dzOiB0cnVlLFxuXG4gICAgICB0UHJldjogJ1ByZXZpb3VzIChMZWZ0IGFycm93IGtleSknLFxuICAgICAgdE5leHQ6ICdOZXh0IChSaWdodCBhcnJvdyBrZXkpJyxcbiAgICAgIHRDb3VudGVyOiAnJWN1cnIlIG9mICV0b3RhbCUnXG4gICAgfSxcblxuICAgIHByb3RvOiB7XG4gICAgICBpbml0R2FsbGVyeTogZnVuY3Rpb24gKCkge1xuXG4gICAgICAgIHZhciBnU3QgPSBtZnAuc3QuZ2FsbGVyeSxcbiAgICAgICAgICBucyA9ICcubWZwLWdhbGxlcnknO1xuXG4gICAgICAgIG1mcC5kaXJlY3Rpb24gPSB0cnVlOyAvLyB0cnVlIC0gbmV4dCwgZmFsc2UgLSBwcmV2XG5cbiAgICAgICAgaWYgKCFnU3QgfHwgIWdTdC5lbmFibGVkKSByZXR1cm4gZmFsc2U7XG5cbiAgICAgICAgX3dyYXBDbGFzc2VzICs9ICcgbWZwLWdhbGxlcnknO1xuXG4gICAgICAgIF9tZnBPbihPUEVOX0VWRU5UICsgbnMsIGZ1bmN0aW9uICgpIHtcblxuICAgICAgICAgIGlmIChnU3QubmF2aWdhdGVCeUltZ0NsaWNrKSB7XG4gICAgICAgICAgICBtZnAud3JhcC5vbignY2xpY2snICsgbnMsICcubWZwLWltZycsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgaWYgKG1mcC5pdGVtcy5sZW5ndGggPiAxKSB7XG4gICAgICAgICAgICAgICAgbWZwLm5leHQoKTtcbiAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgIH1cblxuICAgICAgICAgIF9kb2N1bWVudC5vbigna2V5ZG93bicgKyBucywgZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICAgIGlmIChlLmtleUNvZGUgPT09IDM3KSB7XG4gICAgICAgICAgICAgIG1mcC5wcmV2KCk7XG4gICAgICAgICAgICB9IGVsc2UgaWYgKGUua2V5Q29kZSA9PT0gMzkpIHtcbiAgICAgICAgICAgICAgbWZwLm5leHQoKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9KTtcbiAgICAgICAgfSk7XG5cbiAgICAgICAgX21mcE9uKCdVcGRhdGVTdGF0dXMnICsgbnMsIGZ1bmN0aW9uIChlLCBkYXRhKSB7XG4gICAgICAgICAgaWYgKGRhdGEudGV4dCkge1xuICAgICAgICAgICAgZGF0YS50ZXh0ID0gX3JlcGxhY2VDdXJyVG90YWwoZGF0YS50ZXh0LCBtZnAuY3Vyckl0ZW0uaW5kZXgsIG1mcC5pdGVtcy5sZW5ndGgpO1xuICAgICAgICAgIH1cbiAgICAgICAgfSk7XG5cbiAgICAgICAgX21mcE9uKE1BUktVUF9QQVJTRV9FVkVOVCArIG5zLCBmdW5jdGlvbiAoZSwgZWxlbWVudCwgdmFsdWVzLCBpdGVtKSB7XG4gICAgICAgICAgdmFyIGwgPSBtZnAuaXRlbXMubGVuZ3RoO1xuICAgICAgICAgIHZhbHVlcy5jb3VudGVyID0gbCA+IDEgPyBfcmVwbGFjZUN1cnJUb3RhbChnU3QudENvdW50ZXIsIGl0ZW0uaW5kZXgsIGwpIDogJyc7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIF9tZnBPbignQnVpbGRDb250cm9scycgKyBucywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIGlmIChtZnAuaXRlbXMubGVuZ3RoID4gMSAmJiBnU3QuYXJyb3dzICYmICFtZnAuYXJyb3dMZWZ0KSB7XG4gICAgICAgICAgICB2YXIgbWFya3VwID0gZ1N0LmFycm93TWFya3VwLFxuICAgICAgICAgICAgICBhcnJvd0xlZnQgPSBtZnAuYXJyb3dMZWZ0ID0gJChtYXJrdXAucmVwbGFjZSgvJXRpdGxlJS9naSwgZ1N0LnRQcmV2KS5yZXBsYWNlKC8lZGlyJS9naSwgJ2xlZnQnKSkuYWRkQ2xhc3MoUFJFVkVOVF9DTE9TRV9DTEFTUyksXG4gICAgICAgICAgICAgIGFycm93UmlnaHQgPSBtZnAuYXJyb3dSaWdodCA9ICQobWFya3VwLnJlcGxhY2UoLyV0aXRsZSUvZ2ksIGdTdC50TmV4dCkucmVwbGFjZSgvJWRpciUvZ2ksICdyaWdodCcpKS5hZGRDbGFzcyhQUkVWRU5UX0NMT1NFX0NMQVNTKTtcblxuICAgICAgICAgICAgYXJyb3dMZWZ0LmNsaWNrKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgbWZwLnByZXYoKTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgYXJyb3dSaWdodC5jbGljayhmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgIG1mcC5uZXh0KCk7XG4gICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgbWZwLmNvbnRhaW5lci5hcHBlbmQoYXJyb3dMZWZ0LmFkZChhcnJvd1JpZ2h0KSk7XG4gICAgICAgICAgfVxuICAgICAgICB9KTtcblxuICAgICAgICBfbWZwT24oQ0hBTkdFX0VWRU5UICsgbnMsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICBpZiAobWZwLl9wcmVsb2FkVGltZW91dCkgY2xlYXJUaW1lb3V0KG1mcC5fcHJlbG9hZFRpbWVvdXQpO1xuXG4gICAgICAgICAgbWZwLl9wcmVsb2FkVGltZW91dCA9IHNldFRpbWVvdXQoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgbWZwLnByZWxvYWROZWFyYnlJbWFnZXMoKTtcbiAgICAgICAgICAgIG1mcC5fcHJlbG9hZFRpbWVvdXQgPSBudWxsO1xuICAgICAgICAgIH0sIDE2KTtcbiAgICAgICAgfSk7XG5cblxuICAgICAgICBfbWZwT24oQ0xPU0VfRVZFTlQgKyBucywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIF9kb2N1bWVudC5vZmYobnMpO1xuICAgICAgICAgIG1mcC53cmFwLm9mZignY2xpY2snICsgbnMpO1xuICAgICAgICAgIG1mcC5hcnJvd1JpZ2h0ID0gbWZwLmFycm93TGVmdCA9IG51bGw7XG4gICAgICAgIH0pO1xuXG4gICAgICB9LFxuICAgICAgbmV4dDogZnVuY3Rpb24gKCkge1xuICAgICAgICBtZnAuZGlyZWN0aW9uID0gdHJ1ZTtcbiAgICAgICAgbWZwLmluZGV4ID0gX2dldExvb3BlZElkKG1mcC5pbmRleCArIDEpO1xuICAgICAgICBtZnAudXBkYXRlSXRlbUhUTUwoKTtcbiAgICAgIH0sXG4gICAgICBwcmV2OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgIG1mcC5kaXJlY3Rpb24gPSBmYWxzZTtcbiAgICAgICAgbWZwLmluZGV4ID0gX2dldExvb3BlZElkKG1mcC5pbmRleCAtIDEpO1xuICAgICAgICBtZnAudXBkYXRlSXRlbUhUTUwoKTtcbiAgICAgIH0sXG4gICAgICBnb1RvOiBmdW5jdGlvbiAobmV3SW5kZXgpIHtcbiAgICAgICAgbWZwLmRpcmVjdGlvbiA9IChuZXdJbmRleCA+PSBtZnAuaW5kZXgpO1xuICAgICAgICBtZnAuaW5kZXggPSBuZXdJbmRleDtcbiAgICAgICAgbWZwLnVwZGF0ZUl0ZW1IVE1MKCk7XG4gICAgICB9LFxuICAgICAgcHJlbG9hZE5lYXJieUltYWdlczogZnVuY3Rpb24gKCkge1xuICAgICAgICB2YXIgcCA9IG1mcC5zdC5nYWxsZXJ5LnByZWxvYWQsXG4gICAgICAgICAgcHJlbG9hZEJlZm9yZSA9IE1hdGgubWluKHBbMF0sIG1mcC5pdGVtcy5sZW5ndGgpLFxuICAgICAgICAgIHByZWxvYWRBZnRlciA9IE1hdGgubWluKHBbMV0sIG1mcC5pdGVtcy5sZW5ndGgpLFxuICAgICAgICAgIGk7XG5cbiAgICAgICAgZm9yIChpID0gMTsgaSA8PSAobWZwLmRpcmVjdGlvbiA/IHByZWxvYWRBZnRlciA6IHByZWxvYWRCZWZvcmUpOyBpKyspIHtcbiAgICAgICAgICBtZnAuX3ByZWxvYWRJdGVtKG1mcC5pbmRleCArIGkpO1xuICAgICAgICB9XG4gICAgICAgIGZvciAoaSA9IDE7IGkgPD0gKG1mcC5kaXJlY3Rpb24gPyBwcmVsb2FkQmVmb3JlIDogcHJlbG9hZEFmdGVyKTsgaSsrKSB7XG4gICAgICAgICAgbWZwLl9wcmVsb2FkSXRlbShtZnAuaW5kZXggLSBpKTtcbiAgICAgICAgfVxuICAgICAgfSxcbiAgICAgIF9wcmVsb2FkSXRlbTogZnVuY3Rpb24gKGluZGV4KSB7XG4gICAgICAgIGluZGV4ID0gX2dldExvb3BlZElkKGluZGV4KTtcblxuICAgICAgICBpZiAobWZwLml0ZW1zW2luZGV4XS5wcmVsb2FkZWQpIHtcbiAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICB2YXIgaXRlbSA9IG1mcC5pdGVtc1tpbmRleF07XG4gICAgICAgIGlmICghaXRlbS5wYXJzZWQpIHtcbiAgICAgICAgICBpdGVtID0gbWZwLnBhcnNlRWwoaW5kZXgpO1xuICAgICAgICB9XG5cbiAgICAgICAgX21mcFRyaWdnZXIoJ0xhenlMb2FkJywgaXRlbSk7XG5cbiAgICAgICAgaWYgKGl0ZW0udHlwZSA9PT0gJ2ltYWdlJykge1xuICAgICAgICAgIGl0ZW0uaW1nID0gJCgnPGltZyBjbGFzcz1cIm1mcC1pbWdcIiAvPicpLm9uKCdsb2FkLm1mcGxvYWRlcicsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIGl0ZW0uaGFzU2l6ZSA9IHRydWU7XG4gICAgICAgICAgfSkub24oJ2Vycm9yLm1mcGxvYWRlcicsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIGl0ZW0uaGFzU2l6ZSA9IHRydWU7XG4gICAgICAgICAgICBpdGVtLmxvYWRFcnJvciA9IHRydWU7XG4gICAgICAgICAgICBfbWZwVHJpZ2dlcignTGF6eUxvYWRFcnJvcicsIGl0ZW0pO1xuICAgICAgICAgIH0pLmF0dHIoJ3NyYycsIGl0ZW0uc3JjKTtcbiAgICAgICAgfVxuXG5cbiAgICAgICAgaXRlbS5wcmVsb2FkZWQgPSB0cnVlO1xuICAgICAgfVxuICAgIH1cbiAgfSk7XG5cbiAgLyo+PmdhbGxlcnkqL1xuXG4gIC8qPj5yZXRpbmEqL1xuXG4gIHZhciBSRVRJTkFfTlMgPSAncmV0aW5hJztcblxuICAkLm1hZ25pZmljUG9wdXAucmVnaXN0ZXJNb2R1bGUoUkVUSU5BX05TLCB7XG4gICAgb3B0aW9uczoge1xuICAgICAgcmVwbGFjZVNyYzogZnVuY3Rpb24gKGl0ZW0pIHtcbiAgICAgICAgcmV0dXJuIGl0ZW0uc3JjLnJlcGxhY2UoL1xcLlxcdyskLywgZnVuY3Rpb24gKG0pIHtcbiAgICAgICAgICByZXR1cm4gJ0AyeCcgKyBtO1xuICAgICAgICB9KTtcbiAgICAgIH0sXG4gICAgICByYXRpbzogMSAvLyBGdW5jdGlvbiBvciBudW1iZXIuICBTZXQgdG8gMSB0byBkaXNhYmxlLlxuICAgIH0sXG4gICAgcHJvdG86IHtcbiAgICAgIGluaXRSZXRpbmE6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKHdpbmRvdy5kZXZpY2VQaXhlbFJhdGlvID4gMSkge1xuXG4gICAgICAgICAgdmFyIHN0ID0gbWZwLnN0LnJldGluYSxcbiAgICAgICAgICAgIHJhdGlvID0gc3QucmF0aW87XG5cbiAgICAgICAgICByYXRpbyA9ICFpc05hTihyYXRpbykgPyByYXRpbyA6IHJhdGlvKCk7XG5cbiAgICAgICAgICBpZiAocmF0aW8gPiAxKSB7XG4gICAgICAgICAgICBfbWZwT24oJ0ltYWdlSGFzU2l6ZScgKyAnLicgKyBSRVRJTkFfTlMsIGZ1bmN0aW9uIChlLCBpdGVtKSB7XG4gICAgICAgICAgICAgIGl0ZW0uaW1nLmNzcyh7XG4gICAgICAgICAgICAgICAgJ21heC13aWR0aCc6IGl0ZW0uaW1nWzBdLm5hdHVyYWxXaWR0aCAvIHJhdGlvLFxuICAgICAgICAgICAgICAgICd3aWR0aCc6ICcxMDAlJ1xuICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgX21mcE9uKCdFbGVtZW50UGFyc2UnICsgJy4nICsgUkVUSU5BX05TLCBmdW5jdGlvbiAoZSwgaXRlbSkge1xuICAgICAgICAgICAgICBpdGVtLnNyYyA9IHN0LnJlcGxhY2VTcmMoaXRlbSwgcmF0aW8pO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgIH1cbiAgICB9XG4gIH0pO1xuXG4gIC8qPj5yZXRpbmEqL1xuICBfY2hlY2tJbnN0YW5jZSgpO1xufSkpO1xuIiwiLypcbiAqIFRoaXMgZmlsZSBpcyBwYXJ0IG9mIEpvaG5DTVMgQ29udGVudCBNYW5hZ2VtZW50IFN5c3RlbS5cbiAqXG4gKiBAY29weXJpZ2h0IEpvaG5DTVMgQ29tbXVuaXR5XG4gKiBAbGljZW5zZSAgIGh0dHBzOi8vb3BlbnNvdXJjZS5vcmcvbGljZW5zZXMvR1BMLTMuMCBHUEwtMy4wXG4gKiBAbGluayAgICAgIGh0dHBzOi8vam9obmNtcy5jb20gSm9obkNNUyBQcm9qZWN0XG4gKi9cblxuUHJpc20ubWFudWFsID0gdHJ1ZTtcblxuJChmdW5jdGlvbiAoKSB7XG4gIGNvbnN0IHNjcm9sbF9idXR0b24gPSAkKCcudG8tdG9wJyk7XG5cbiAgJChcIi5wb3N0LWJvZHlcIikuZWFjaChmdW5jdGlvbiAoKSB7XG4gICAgUHJpc20uaGlnaGxpZ2h0QWxsVW5kZXIodGhpcyk7XG4gIH0pO1xuXG4gIGlmICgkKGRvY3VtZW50KS5oZWlnaHQoKSA+ICQod2luZG93KS5oZWlnaHQoKSAmJiAkKHRoaXMpLnNjcm9sbFRvcCgpIDwgNTApIHtcbiAgICBzY3JvbGxfYnV0dG9uLmFkZENsYXNzKCd0by1ib3R0b20nKS5yZW1vdmVDbGFzcygndG8tdG9wX2hpZGRlbicpO1xuICB9XG5cbiAgJCh3aW5kb3cpLnNjcm9sbChmdW5jdGlvbiAoKSB7XG4gICAgaWYgKCQodGhpcykuc2Nyb2xsVG9wKCkgPiA1MCkge1xuICAgICAgc2Nyb2xsX2J1dHRvbi5yZW1vdmVDbGFzcygndG8tYm90dG9tJyk7XG4gICAgICBzY3JvbGxfYnV0dG9uLmFkZENsYXNzKCd0by1oZWFkZXInKTtcbiAgICB9IGVsc2Uge1xuICAgICAgc2Nyb2xsX2J1dHRvbi5hZGRDbGFzcygndG8tYm90dG9tJyk7XG4gICAgICBzY3JvbGxfYnV0dG9uLnJlbW92ZUNsYXNzKCd0by1oZWFkZXInKTtcbiAgICB9XG4gIH0pO1xuXG4gICQoXCIudG8tdG9wXCIpLmNsaWNrKGZ1bmN0aW9uIChldmVudCkge1xuICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgaWYgKCQodGhpcykuaGFzQ2xhc3MoJ3RvLWhlYWRlcicpKSB7XG4gICAgICAkKCdib2R5LGh0bWwnKS5hbmltYXRlKHtzY3JvbGxUb3A6IDB9LCA4MDApO1xuICAgIH0gZWxzZSB7XG4gICAgICAkKCdib2R5LGh0bWwnKS5hbmltYXRlKHtzY3JvbGxUb3A6ICQoZG9jdW1lbnQpLmhlaWdodCgpfSwgODAwKTtcbiAgICB9XG4gIH0pO1xufSk7XG5cbiQoZG9jdW1lbnQpLnJlYWR5KGZ1bmN0aW9uICgpIHtcbiAgaWYgKHR5cGVvZiB3eXNpYmJfaW5wdXQgIT0gXCJ1bmRlZmluZWRcIikge1xuICAgICQod3lzaWJiX2lucHV0KS53eXNpYmIod3lzaWJiX3NldHRpbmdzKTtcbiAgfVxuXG4gICQoXCIuZmxhdHBpY2tyXCIpLmZsYXRwaWNrcih7XG4gICAgZGF0ZUZvcm1hdDogJ2QubS5ZJyxcbiAgfSk7XG4gICQoXCIuZmxhdHBpY2tyX3RpbWVcIikuZmxhdHBpY2tyKHtcbiAgICBkYXRlRm9ybWF0OiAnZC5tLlkgSDppJyxcbiAgICBlbmFibGVUaW1lOiB0cnVlLFxuICB9KTtcbn0pXG4iLCIkKGRvY3VtZW50KVxuICAub24oJ2NsaWNrJywgJy5uYXZiYXItdG9nZ2xlciwgLnNob3dfbWVudV9idG4nLCBmdW5jdGlvbiAoKSB7XG4gICAgdG9nZ2xlX21lbnUoKTtcbiAgfSlcbiAgLm9uKCdjbGljaycsICcuc2lkZWJhcl9vcGVuZWQgLm92ZXJsYXknLCBmdW5jdGlvbiAoKSB7XG4gICAgdmFyIGJvZHkgPSAkKCdib2R5Jyk7XG4gICAgaWYgKGJvZHkuaGFzQ2xhc3MoJ3NpZGViYXJfb3BlbmVkJykpIHtcbiAgICAgIHRvZ2dsZV9tZW51KCk7XG4gICAgfVxuICB9KTtcblxuLy8g0J7RgtC60YDRi9GC0LjQtS/Qt9Cw0LrRgNGL0YLQuNC1INC80LXQvdGOINC00LvRjyDQvNC+0LHQuNC70YzQvdC+0Lkg0LLQtdGA0YHQuNC4XG5mdW5jdGlvbiB0b2dnbGVfbWVudSgpXG57XG4gIHZhciBib2R5ID0gJCgnYm9keScpO1xuICBpZiAoYm9keS5oYXNDbGFzcygnc2lkZWJhcl9vcGVuZWQnKSkge1xuICAgIGJvZHkucmVtb3ZlQ2xhc3MoJ3NpZGViYXJfb3BlbmVkJyk7XG4gICAgc2V0VGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAkKCcudG9wX25hdiAubmF2YmFyLXRvZ2dsZScpLnJlbW92ZUNsYXNzKCd0b2dnbGVkJyk7XG4gICAgfSwgNTAwKTtcblxuICB9IGVsc2Uge1xuICAgIGJvZHkuYWRkQ2xhc3MoJ3NpZGViYXJfb3BlbmVkJyk7XG4gICAgc2V0VGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAkKCcudG9wX25hdiAubmF2YmFyLXRvZ2dsZScpLmFkZENsYXNzKCd0b2dnbGVkJyk7XG4gICAgfSwgNTAwKTtcbiAgfVxufVxuIiwiLyoqXG4gKiBUaGlzIGZpbGUgaXMgcGFydCBvZiBKb2huQ01TIENvbnRlbnQgTWFuYWdlbWVudCBTeXN0ZW0uXG4gKlxuICogQGNvcHlyaWdodCBKb2huQ01TIENvbW11bml0eVxuICogQGxpY2Vuc2UgICBodHRwczovL29wZW5zb3VyY2Uub3JnL2xpY2Vuc2VzL0dQTC0zLjAgR1BMLTMuMFxuICogQGxpbmsgICAgICBodHRwczovL2pvaG5jbXMuY29tIEpvaG5DTVMgUHJvamVjdFxuICovXG5cbmZ1bmN0aW9uIGdldFNwaW5uZXIoKVxue1xuICByZXR1cm4gJzxkaXYgY2xhc3M9XCJ0ZXh0LWNlbnRlciBwLTVcIj48ZGl2IGNsYXNzPVwic3Bpbm5lci1ib3JkZXJcIiByb2xlPVwic3RhdHVzXCI+PHNwYW4gY2xhc3M9XCJ2aXN1YWxseS1oaWRkZW5cIj5Mb2FkaW5nLi4uPC9zcGFuPjwvZGl2PjwvZGl2Pic7XG59XG5cbiQoZnVuY3Rpb24gKCkge1xuICBsZXQgYWpheF9tb2RhbCA9ICQoJy5hamF4X21vZGFsJyk7XG5cbiAgYWpheF9tb2RhbC5vbignc2hvdy5icy5tb2RhbCcsIGZ1bmN0aW9uIChldmVudCkge1xuICAgICQoJy5hamF4X21vZGFsIC5tb2RhbC1jb250ZW50JykuaHRtbChnZXRTcGlubmVyKCkpO1xuICB9KTtcblxuICBhamF4X21vZGFsLm9uKCdzaG93bi5icy5tb2RhbCcsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIGxldCBidXR0b24gPSAkKGV2ZW50LnJlbGF0ZWRUYXJnZXQpO1xuICAgIGxldCBwYXJhbXMgPSBidXR0b24uZGF0YSgpO1xuICAgICQuYWpheCh7XG4gICAgICB0eXBlOiBcIkdFVFwiLFxuICAgICAgdXJsOiBwYXJhbXMudXJsLFxuICAgICAgZGF0YVR5cGU6IFwiaHRtbFwiLFxuICAgICAgZGF0YTogcGFyYW1zLFxuICAgICAgc3VjY2VzczogZnVuY3Rpb24gKGh0bWwpIHtcbiAgICAgICAgJCgnLmFqYXhfbW9kYWwgLm1vZGFsLWNvbnRlbnQnKS5odG1sKGh0bWwpO1xuICAgICAgfVxuICAgIH0pO1xuICB9KTtcbn0pO1xuXG4kKGRvY3VtZW50KS5vbignY2xpY2snLCAnLnNlbGVjdF9sYW5ndWFnZScsIGZ1bmN0aW9uIChldmVudCkge1xuICBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuICBsZXQgc2VsZWN0X2xhbmd1YWdlX2Zvcm0gPSAkKCdmb3JtW25hbWU9XCJzZWxlY3RfbGFuZ3VhZ2VcIl0nKTtcblxuICAkLmFqYXgoe1xuICAgIHR5cGU6IFwiUE9TVFwiLFxuICAgIHVybDogc2VsZWN0X2xhbmd1YWdlX2Zvcm0uYXR0cignYWN0aW9uJyksXG4gICAgZGF0YVR5cGU6IFwiaHRtbFwiLFxuICAgIGRhdGE6IHNlbGVjdF9sYW5ndWFnZV9mb3JtLnNlcmlhbGl6ZSgpLFxuICAgIHN1Y2Nlc3M6IGZ1bmN0aW9uIChodG1sKSB7XG4gICAgICAkKCcuYWpheF9tb2RhbCcpLm1vZGFsKCdoaWRlJyk7XG4gICAgICBkb2N1bWVudC5sb2NhdGlvbi5ocmVmID0gZG9jdW1lbnQubG9jYXRpb24uaHJlZjtcbiAgICB9XG4gIH0pO1xufSk7XG4iLCIvKiBQcmlzbUpTIDEuMTcuMVxuaHR0cHM6Ly9wcmlzbWpzLmNvbS9kb3dubG9hZC5odG1sI3RoZW1lcz1wcmlzbSZsYW5ndWFnZXM9bWFya3VwK2NzcytjbGlrZStqYXZhc2NyaXB0K21hcmt1cC10ZW1wbGF0aW5nK3BocCtqYXZhZG9jbGlrZStwaHBkb2MrcGhwLWV4dHJhcytzcWwmcGx1Z2lucz1saW5lLW51bWJlcnMgKi9cbnZhciBfc2VsZj1cInVuZGVmaW5lZFwiIT10eXBlb2Ygd2luZG93P3dpbmRvdzpcInVuZGVmaW5lZFwiIT10eXBlb2YgV29ya2VyR2xvYmFsU2NvcGUmJnNlbGYgaW5zdGFuY2VvZiBXb3JrZXJHbG9iYWxTY29wZT9zZWxmOnt9LFByaXNtPWZ1bmN0aW9uKHUpe3ZhciBjPS9cXGJsYW5nKD86dWFnZSk/LShbXFx3LV0rKVxcYi9pLHI9MDt2YXIgXz17bWFudWFsOnUuUHJpc20mJnUuUHJpc20ubWFudWFsLGRpc2FibGVXb3JrZXJNZXNzYWdlSGFuZGxlcjp1LlByaXNtJiZ1LlByaXNtLmRpc2FibGVXb3JrZXJNZXNzYWdlSGFuZGxlcix1dGlsOntlbmNvZGU6ZnVuY3Rpb24oZSl7cmV0dXJuIGUgaW5zdGFuY2VvZiBMP25ldyBMKGUudHlwZSxfLnV0aWwuZW5jb2RlKGUuY29udGVudCksZS5hbGlhcyk6QXJyYXkuaXNBcnJheShlKT9lLm1hcChfLnV0aWwuZW5jb2RlKTplLnJlcGxhY2UoLyYvZyxcIiZhbXA7XCIpLnJlcGxhY2UoLzwvZyxcIiZsdDtcIikucmVwbGFjZSgvXFx1MDBhMC9nLFwiIFwiKX0sdHlwZTpmdW5jdGlvbihlKXtyZXR1cm4gT2JqZWN0LnByb3RvdHlwZS50b1N0cmluZy5jYWxsKGUpLnNsaWNlKDgsLTEpfSxvYmpJZDpmdW5jdGlvbihlKXtyZXR1cm4gZS5fX2lkfHxPYmplY3QuZGVmaW5lUHJvcGVydHkoZSxcIl9faWRcIix7dmFsdWU6KytyfSksZS5fX2lkfSxjbG9uZTpmdW5jdGlvbiBuKGUsdCl7dmFyIGEscixpPV8udXRpbC50eXBlKGUpO3N3aXRjaCh0PXR8fHt9LGkpe2Nhc2VcIk9iamVjdFwiOmlmKHI9Xy51dGlsLm9iaklkKGUpLHRbcl0pcmV0dXJuIHRbcl07Zm9yKHZhciBvIGluIGE9e30sdFtyXT1hLGUpZS5oYXNPd25Qcm9wZXJ0eShvKSYmKGFbb109bihlW29dLHQpKTtyZXR1cm4gYTtjYXNlXCJBcnJheVwiOnJldHVybiByPV8udXRpbC5vYmpJZChlKSx0W3JdP3Rbcl06KGE9W10sdFtyXT1hLGUuZm9yRWFjaChmdW5jdGlvbihlLHIpe2Fbcl09bihlLHQpfSksYSk7ZGVmYXVsdDpyZXR1cm4gZX19LGN1cnJlbnRTY3JpcHQ6ZnVuY3Rpb24oKXtpZihcInVuZGVmaW5lZFwiPT10eXBlb2YgZG9jdW1lbnQpcmV0dXJuIG51bGw7aWYoXCJjdXJyZW50U2NyaXB0XCJpbiBkb2N1bWVudClyZXR1cm4gZG9jdW1lbnQuY3VycmVudFNjcmlwdDt0cnl7dGhyb3cgbmV3IEVycm9yfWNhdGNoKGUpe3ZhciByPSgvYXQgW14oXFxyXFxuXSpcXCgoLiopOi4rOi4rXFwpJC9pLmV4ZWMoZS5zdGFjayl8fFtdKVsxXTtpZihyKXt2YXIgbj1kb2N1bWVudC5nZXRFbGVtZW50c0J5VGFnTmFtZShcInNjcmlwdFwiKTtmb3IodmFyIHQgaW4gbilpZihuW3RdLnNyYz09cilyZXR1cm4gblt0XX1yZXR1cm4gbnVsbH19fSxsYW5ndWFnZXM6e2V4dGVuZDpmdW5jdGlvbihlLHIpe3ZhciBuPV8udXRpbC5jbG9uZShfLmxhbmd1YWdlc1tlXSk7Zm9yKHZhciB0IGluIHIpblt0XT1yW3RdO3JldHVybiBufSxpbnNlcnRCZWZvcmU6ZnVuY3Rpb24obixlLHIsdCl7dmFyIGE9KHQ9dHx8Xy5sYW5ndWFnZXMpW25dLGk9e307Zm9yKHZhciBvIGluIGEpaWYoYS5oYXNPd25Qcm9wZXJ0eShvKSl7aWYobz09ZSlmb3IodmFyIGwgaW4gcilyLmhhc093blByb3BlcnR5KGwpJiYoaVtsXT1yW2xdKTtyLmhhc093blByb3BlcnR5KG8pfHwoaVtvXT1hW29dKX12YXIgcz10W25dO3JldHVybiB0W25dPWksXy5sYW5ndWFnZXMuREZTKF8ubGFuZ3VhZ2VzLGZ1bmN0aW9uKGUscil7cj09PXMmJmUhPW4mJih0aGlzW2VdPWkpfSksaX0sREZTOmZ1bmN0aW9uIGUocixuLHQsYSl7YT1hfHx7fTt2YXIgaT1fLnV0aWwub2JqSWQ7Zm9yKHZhciBvIGluIHIpaWYoci5oYXNPd25Qcm9wZXJ0eShvKSl7bi5jYWxsKHIsbyxyW29dLHR8fG8pO3ZhciBsPXJbb10scz1fLnV0aWwudHlwZShsKTtcIk9iamVjdFwiIT09c3x8YVtpKGwpXT9cIkFycmF5XCIhPT1zfHxhW2kobCldfHwoYVtpKGwpXT0hMCxlKGwsbixvLGEpKTooYVtpKGwpXT0hMCxlKGwsbixudWxsLGEpKX19fSxwbHVnaW5zOnt9LGhpZ2hsaWdodEFsbDpmdW5jdGlvbihlLHIpe18uaGlnaGxpZ2h0QWxsVW5kZXIoZG9jdW1lbnQsZSxyKX0saGlnaGxpZ2h0QWxsVW5kZXI6ZnVuY3Rpb24oZSxyLG4pe3ZhciB0PXtjYWxsYmFjazpuLHNlbGVjdG9yOidjb2RlW2NsYXNzKj1cImxhbmd1YWdlLVwiXSwgW2NsYXNzKj1cImxhbmd1YWdlLVwiXSBjb2RlLCBjb2RlW2NsYXNzKj1cImxhbmctXCJdLCBbY2xhc3MqPVwibGFuZy1cIl0gY29kZSd9O18uaG9va3MucnVuKFwiYmVmb3JlLWhpZ2hsaWdodGFsbFwiLHQpO2Zvcih2YXIgYSxpPWUucXVlcnlTZWxlY3RvckFsbCh0LnNlbGVjdG9yKSxvPTA7YT1pW28rK107KV8uaGlnaGxpZ2h0RWxlbWVudChhLCEwPT09cix0LmNhbGxiYWNrKX0saGlnaGxpZ2h0RWxlbWVudDpmdW5jdGlvbihlLHIsbil7dmFyIHQ9ZnVuY3Rpb24oZSl7Zm9yKDtlJiYhYy50ZXN0KGUuY2xhc3NOYW1lKTspZT1lLnBhcmVudE5vZGU7cmV0dXJuIGU/KGUuY2xhc3NOYW1lLm1hdGNoKGMpfHxbLFwibm9uZVwiXSlbMV0udG9Mb3dlckNhc2UoKTpcIm5vbmVcIn0oZSksYT1fLmxhbmd1YWdlc1t0XTtlLmNsYXNzTmFtZT1lLmNsYXNzTmFtZS5yZXBsYWNlKGMsXCJcIikucmVwbGFjZSgvXFxzKy9nLFwiIFwiKStcIiBsYW5ndWFnZS1cIit0O3ZhciBpPWUucGFyZW50Tm9kZTtpJiZcInByZVwiPT09aS5ub2RlTmFtZS50b0xvd2VyQ2FzZSgpJiYoaS5jbGFzc05hbWU9aS5jbGFzc05hbWUucmVwbGFjZShjLFwiXCIpLnJlcGxhY2UoL1xccysvZyxcIiBcIikrXCIgbGFuZ3VhZ2UtXCIrdCk7dmFyIG89e2VsZW1lbnQ6ZSxsYW5ndWFnZTp0LGdyYW1tYXI6YSxjb2RlOmUudGV4dENvbnRlbnR9O2Z1bmN0aW9uIGwoZSl7by5oaWdobGlnaHRlZENvZGU9ZSxfLmhvb2tzLnJ1bihcImJlZm9yZS1pbnNlcnRcIixvKSxvLmVsZW1lbnQuaW5uZXJIVE1MPW8uaGlnaGxpZ2h0ZWRDb2RlLF8uaG9va3MucnVuKFwiYWZ0ZXItaGlnaGxpZ2h0XCIsbyksXy5ob29rcy5ydW4oXCJjb21wbGV0ZVwiLG8pLG4mJm4uY2FsbChvLmVsZW1lbnQpfWlmKF8uaG9va3MucnVuKFwiYmVmb3JlLXNhbml0eS1jaGVja1wiLG8pLCFvLmNvZGUpcmV0dXJuIF8uaG9va3MucnVuKFwiY29tcGxldGVcIixvKSx2b2lkKG4mJm4uY2FsbChvLmVsZW1lbnQpKTtpZihfLmhvb2tzLnJ1bihcImJlZm9yZS1oaWdobGlnaHRcIixvKSxvLmdyYW1tYXIpaWYociYmdS5Xb3JrZXIpe3ZhciBzPW5ldyBXb3JrZXIoXy5maWxlbmFtZSk7cy5vbm1lc3NhZ2U9ZnVuY3Rpb24oZSl7bChlLmRhdGEpfSxzLnBvc3RNZXNzYWdlKEpTT04uc3RyaW5naWZ5KHtsYW5ndWFnZTpvLmxhbmd1YWdlLGNvZGU6by5jb2RlLGltbWVkaWF0ZUNsb3NlOiEwfSkpfWVsc2UgbChfLmhpZ2hsaWdodChvLmNvZGUsby5ncmFtbWFyLG8ubGFuZ3VhZ2UpKTtlbHNlIGwoXy51dGlsLmVuY29kZShvLmNvZGUpKX0saGlnaGxpZ2h0OmZ1bmN0aW9uKGUscixuKXt2YXIgdD17Y29kZTplLGdyYW1tYXI6cixsYW5ndWFnZTpufTtyZXR1cm4gXy5ob29rcy5ydW4oXCJiZWZvcmUtdG9rZW5pemVcIix0KSx0LnRva2Vucz1fLnRva2VuaXplKHQuY29kZSx0LmdyYW1tYXIpLF8uaG9va3MucnVuKFwiYWZ0ZXItdG9rZW5pemVcIix0KSxMLnN0cmluZ2lmeShfLnV0aWwuZW5jb2RlKHQudG9rZW5zKSx0Lmxhbmd1YWdlKX0sbWF0Y2hHcmFtbWFyOmZ1bmN0aW9uKGUscixuLHQsYSxpLG8pe2Zvcih2YXIgbCBpbiBuKWlmKG4uaGFzT3duUHJvcGVydHkobCkmJm5bbF0pe3ZhciBzPW5bbF07cz1BcnJheS5pc0FycmF5KHMpP3M6W3NdO2Zvcih2YXIgdT0wO3U8cy5sZW5ndGg7Kyt1KXtpZihvJiZvPT1sK1wiLFwiK3UpcmV0dXJuO3ZhciBjPXNbdV0sZz1jLmluc2lkZSxmPSEhYy5sb29rYmVoaW5kLGQ9ISFjLmdyZWVkeSxoPTAsbT1jLmFsaWFzO2lmKGQmJiFjLnBhdHRlcm4uZ2xvYmFsKXt2YXIgcD1jLnBhdHRlcm4udG9TdHJpbmcoKS5tYXRjaCgvW2ltc3V5XSokLylbMF07Yy5wYXR0ZXJuPVJlZ0V4cChjLnBhdHRlcm4uc291cmNlLHArXCJnXCIpfWM9Yy5wYXR0ZXJufHxjO2Zvcih2YXIgeT10LHY9YTt5PHIubGVuZ3RoO3YrPXJbeV0ubGVuZ3RoLCsreSl7dmFyIGs9clt5XTtpZihyLmxlbmd0aD5lLmxlbmd0aClyZXR1cm47aWYoIShrIGluc3RhbmNlb2YgTCkpe2lmKGQmJnkhPXIubGVuZ3RoLTEpe2lmKGMubGFzdEluZGV4PXYsIShPPWMuZXhlYyhlKSkpYnJlYWs7Zm9yKHZhciBiPU8uaW5kZXgrKGYmJk9bMV0/T1sxXS5sZW5ndGg6MCksdz1PLmluZGV4K09bMF0ubGVuZ3RoLEE9eSxQPXYseD1yLmxlbmd0aDtBPHgmJihQPHd8fCFyW0FdLnR5cGUmJiFyW0EtMV0uZ3JlZWR5KTsrK0EpKFArPXJbQV0ubGVuZ3RoKTw9YiYmKCsreSx2PVApO2lmKHJbeV1pbnN0YW5jZW9mIEwpY29udGludWU7Uz1BLXksaz1lLnNsaWNlKHYsUCksTy5pbmRleC09dn1lbHNle2MubGFzdEluZGV4PTA7dmFyIE89Yy5leGVjKGspLFM9MX1pZihPKXtmJiYoaD1PWzFdP09bMV0ubGVuZ3RoOjApO3c9KGI9Ty5pbmRleCtoKSsoTz1PWzBdLnNsaWNlKGgpKS5sZW5ndGg7dmFyIGo9ay5zbGljZSgwLGIpLE49ay5zbGljZSh3KSxFPVt5LFNdO2omJigrK3ksdis9ai5sZW5ndGgsRS5wdXNoKGopKTt2YXIgQz1uZXcgTChsLGc/Xy50b2tlbml6ZShPLGcpOk8sbSxPLGQpO2lmKEUucHVzaChDKSxOJiZFLnB1c2goTiksQXJyYXkucHJvdG90eXBlLnNwbGljZS5hcHBseShyLEUpLDEhPVMmJl8ubWF0Y2hHcmFtbWFyKGUscixuLHksdiwhMCxsK1wiLFwiK3UpLGkpYnJlYWt9ZWxzZSBpZihpKWJyZWFrfX19fX0sdG9rZW5pemU6ZnVuY3Rpb24oZSxyKXt2YXIgbj1bZV0sdD1yLnJlc3Q7aWYodCl7Zm9yKHZhciBhIGluIHQpclthXT10W2FdO2RlbGV0ZSByLnJlc3R9cmV0dXJuIF8ubWF0Y2hHcmFtbWFyKGUsbixyLDAsMCwhMSksbn0saG9va3M6e2FsbDp7fSxhZGQ6ZnVuY3Rpb24oZSxyKXt2YXIgbj1fLmhvb2tzLmFsbDtuW2VdPW5bZV18fFtdLG5bZV0ucHVzaChyKX0scnVuOmZ1bmN0aW9uKGUscil7dmFyIG49Xy5ob29rcy5hbGxbZV07aWYobiYmbi5sZW5ndGgpZm9yKHZhciB0LGE9MDt0PW5bYSsrXTspdChyKX19LFRva2VuOkx9O2Z1bmN0aW9uIEwoZSxyLG4sdCxhKXt0aGlzLnR5cGU9ZSx0aGlzLmNvbnRlbnQ9cix0aGlzLmFsaWFzPW4sdGhpcy5sZW5ndGg9MHwodHx8XCJcIikubGVuZ3RoLHRoaXMuZ3JlZWR5PSEhYX1pZih1LlByaXNtPV8sTC5zdHJpbmdpZnk9ZnVuY3Rpb24oZSxyKXtpZihcInN0cmluZ1wiPT10eXBlb2YgZSlyZXR1cm4gZTtpZihBcnJheS5pc0FycmF5KGUpKXJldHVybiBlLm1hcChmdW5jdGlvbihlKXtyZXR1cm4gTC5zdHJpbmdpZnkoZSxyKX0pLmpvaW4oXCJcIik7dmFyIG49e3R5cGU6ZS50eXBlLGNvbnRlbnQ6TC5zdHJpbmdpZnkoZS5jb250ZW50LHIpLHRhZzpcInNwYW5cIixjbGFzc2VzOltcInRva2VuXCIsZS50eXBlXSxhdHRyaWJ1dGVzOnt9LGxhbmd1YWdlOnJ9O2lmKGUuYWxpYXMpe3ZhciB0PUFycmF5LmlzQXJyYXkoZS5hbGlhcyk/ZS5hbGlhczpbZS5hbGlhc107QXJyYXkucHJvdG90eXBlLnB1c2guYXBwbHkobi5jbGFzc2VzLHQpfV8uaG9va3MucnVuKFwid3JhcFwiLG4pO3ZhciBhPU9iamVjdC5rZXlzKG4uYXR0cmlidXRlcykubWFwKGZ1bmN0aW9uKGUpe3JldHVybiBlKyc9XCInKyhuLmF0dHJpYnV0ZXNbZV18fFwiXCIpLnJlcGxhY2UoL1wiL2csXCImcXVvdDtcIikrJ1wiJ30pLmpvaW4oXCIgXCIpO3JldHVyblwiPFwiK24udGFnKycgY2xhc3M9XCInK24uY2xhc3Nlcy5qb2luKFwiIFwiKSsnXCInKyhhP1wiIFwiK2E6XCJcIikrXCI+XCIrbi5jb250ZW50K1wiPC9cIituLnRhZytcIj5cIn0sIXUuZG9jdW1lbnQpcmV0dXJuIHUuYWRkRXZlbnRMaXN0ZW5lciYmKF8uZGlzYWJsZVdvcmtlck1lc3NhZ2VIYW5kbGVyfHx1LmFkZEV2ZW50TGlzdGVuZXIoXCJtZXNzYWdlXCIsZnVuY3Rpb24oZSl7dmFyIHI9SlNPTi5wYXJzZShlLmRhdGEpLG49ci5sYW5ndWFnZSx0PXIuY29kZSxhPXIuaW1tZWRpYXRlQ2xvc2U7dS5wb3N0TWVzc2FnZShfLmhpZ2hsaWdodCh0LF8ubGFuZ3VhZ2VzW25dLG4pKSxhJiZ1LmNsb3NlKCl9LCExKSksXzt2YXIgZT1fLnV0aWwuY3VycmVudFNjcmlwdCgpO2lmKGUmJihfLmZpbGVuYW1lPWUuc3JjLGUuaGFzQXR0cmlidXRlKFwiZGF0YS1tYW51YWxcIikmJihfLm1hbnVhbD0hMCkpLCFfLm1hbnVhbCl7ZnVuY3Rpb24gbigpe18ubWFudWFsfHxfLmhpZ2hsaWdodEFsbCgpfXZhciB0PWRvY3VtZW50LnJlYWR5U3RhdGU7XCJsb2FkaW5nXCI9PT10fHxcImludGVyYWN0aXZlXCI9PT10JiZlLmRlZmVyP2RvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoXCJET01Db250ZW50TG9hZGVkXCIsbik6d2luZG93LnJlcXVlc3RBbmltYXRpb25GcmFtZT93aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lKG4pOndpbmRvdy5zZXRUaW1lb3V0KG4sMTYpfXJldHVybiBffShfc2VsZik7XCJ1bmRlZmluZWRcIiE9dHlwZW9mIG1vZHVsZSYmbW9kdWxlLmV4cG9ydHMmJihtb2R1bGUuZXhwb3J0cz1QcmlzbSksXCJ1bmRlZmluZWRcIiE9dHlwZW9mIGdsb2JhbCYmKGdsb2JhbC5QcmlzbT1QcmlzbSk7XG5QcmlzbS5sYW5ndWFnZXMubWFya3VwPXtjb21tZW50Oi88IS0tW1xcc1xcU10qPy0tPi8scHJvbG9nOi88XFw/W1xcc1xcU10rP1xcPz4vLGRvY3R5cGU6e3BhdHRlcm46LzwhRE9DVFlQRSg/OltePlwiJ1tcXF1dfFwiW15cIl0qXCJ8J1teJ10qJykrKD86XFxbKD86KD8hPCEtLSlbXlwiJ1xcXV18XCJbXlwiXSpcInwnW14nXSonfDwhLS1bXFxzXFxTXSo/LS0+KSpcXF1cXHMqKT8+L2ksZ3JlZWR5OiEwfSxjZGF0YTovPCFcXFtDREFUQVxcW1tcXHNcXFNdKj9dXT4vaSx0YWc6e3BhdHRlcm46LzxcXC8/KD8hXFxkKVteXFxzPlxcLz0kPCVdKyg/Olxccyg/OlxccypbXlxccz5cXC89XSsoPzpcXHMqPVxccyooPzpcIlteXCJdKlwifCdbXiddKid8W15cXHMnXCI+PV0rKD89W1xccz5dKSl8KD89W1xccy8+XSkpKSspP1xccypcXC8/Pi9pLGdyZWVkeTohMCxpbnNpZGU6e3RhZzp7cGF0dGVybjovXjxcXC8/W15cXHM+XFwvXSsvaSxpbnNpZGU6e3B1bmN0dWF0aW9uOi9ePFxcLz8vLG5hbWVzcGFjZTovXlteXFxzPlxcLzpdKzovfX0sXCJhdHRyLXZhbHVlXCI6e3BhdHRlcm46Lz1cXHMqKD86XCJbXlwiXSpcInwnW14nXSonfFteXFxzJ1wiPj1dKykvaSxpbnNpZGU6e3B1bmN0dWF0aW9uOlsvXj0vLHtwYXR0ZXJuOi9eKFxccyopW1wiJ118W1wiJ10kLyxsb29rYmVoaW5kOiEwfV19fSxwdW5jdHVhdGlvbjovXFwvPz4vLFwiYXR0ci1uYW1lXCI6e3BhdHRlcm46L1teXFxzPlxcL10rLyxpbnNpZGU6e25hbWVzcGFjZTovXlteXFxzPlxcLzpdKzovfX19fSxlbnRpdHk6LyYjP1tcXGRhLXpdezEsOH07L2l9LFByaXNtLmxhbmd1YWdlcy5tYXJrdXAudGFnLmluc2lkZVtcImF0dHItdmFsdWVcIl0uaW5zaWRlLmVudGl0eT1QcmlzbS5sYW5ndWFnZXMubWFya3VwLmVudGl0eSxQcmlzbS5ob29rcy5hZGQoXCJ3cmFwXCIsZnVuY3Rpb24oYSl7XCJlbnRpdHlcIj09PWEudHlwZSYmKGEuYXR0cmlidXRlcy50aXRsZT1hLmNvbnRlbnQucmVwbGFjZSgvJmFtcDsvLFwiJlwiKSl9KSxPYmplY3QuZGVmaW5lUHJvcGVydHkoUHJpc20ubGFuZ3VhZ2VzLm1hcmt1cC50YWcsXCJhZGRJbmxpbmVkXCIse3ZhbHVlOmZ1bmN0aW9uKGEsZSl7dmFyIHM9e307c1tcImxhbmd1YWdlLVwiK2VdPXtwYXR0ZXJuOi8oXjwhXFxbQ0RBVEFcXFspW1xcc1xcU10rPyg/PVxcXVxcXT4kKS9pLGxvb2tiZWhpbmQ6ITAsaW5zaWRlOlByaXNtLmxhbmd1YWdlc1tlXX0scy5jZGF0YT0vXjwhXFxbQ0RBVEFcXFt8XFxdXFxdPiQvaTt2YXIgbj17XCJpbmNsdWRlZC1jZGF0YVwiOntwYXR0ZXJuOi88IVxcW0NEQVRBXFxbW1xcc1xcU10qP1xcXVxcXT4vaSxpbnNpZGU6c319O25bXCJsYW5ndWFnZS1cIitlXT17cGF0dGVybjovW1xcc1xcU10rLyxpbnNpZGU6UHJpc20ubGFuZ3VhZ2VzW2VdfTt2YXIgdD17fTt0W2FdPXtwYXR0ZXJuOlJlZ0V4cChcIig8X19bXFxcXHNcXFxcU10qPz4pKD86PCFcXFxcW0NEQVRBXFxcXFtbXFxcXHNcXFxcU10qP1xcXFxdXFxcXF0+XFxcXHMqfFtcXFxcc1xcXFxTXSkqPyg/PTxcXFxcL19fPilcIi5yZXBsYWNlKC9fXy9nLGEpLFwiaVwiKSxsb29rYmVoaW5kOiEwLGdyZWVkeTohMCxpbnNpZGU6bn0sUHJpc20ubGFuZ3VhZ2VzLmluc2VydEJlZm9yZShcIm1hcmt1cFwiLFwiY2RhdGFcIix0KX19KSxQcmlzbS5sYW5ndWFnZXMueG1sPVByaXNtLmxhbmd1YWdlcy5leHRlbmQoXCJtYXJrdXBcIix7fSksUHJpc20ubGFuZ3VhZ2VzLmh0bWw9UHJpc20ubGFuZ3VhZ2VzLm1hcmt1cCxQcmlzbS5sYW5ndWFnZXMubWF0aG1sPVByaXNtLmxhbmd1YWdlcy5tYXJrdXAsUHJpc20ubGFuZ3VhZ2VzLnN2Zz1QcmlzbS5sYW5ndWFnZXMubWFya3VwO1xuIWZ1bmN0aW9uKHMpe3ZhciB0PS8oXCJ8JykoPzpcXFxcKD86XFxyXFxufFtcXHNcXFNdKXwoPyFcXDEpW15cXFxcXFxyXFxuXSkqXFwxLztzLmxhbmd1YWdlcy5jc3M9e2NvbW1lbnQ6L1xcL1xcKltcXHNcXFNdKj9cXCpcXC8vLGF0cnVsZTp7cGF0dGVybjovQFtcXHctXStbXFxzXFxTXSo/KD86O3woPz1cXHMqXFx7KSkvLGluc2lkZTp7cnVsZTovQFtcXHctXSsvfX0sdXJsOntwYXR0ZXJuOlJlZ0V4cChcInVybFxcXFwoKD86XCIrdC5zb3VyY2UrXCJ8W15cXG5cXHIoKV0qKVxcXFwpXCIsXCJpXCIpLGluc2lkZTp7ZnVuY3Rpb246L151cmwvaSxwdW5jdHVhdGlvbjovXlxcKHxcXCkkL319LHNlbGVjdG9yOlJlZ0V4cChcIltee31cXFxcc10oPzpbXnt9O1xcXCInXXxcIit0LnNvdXJjZStcIikqPyg/PVxcXFxzKlxcXFx7KVwiKSxzdHJpbmc6e3BhdHRlcm46dCxncmVlZHk6ITB9LHByb3BlcnR5Oi9bLV9hLXpcXHhBMC1cXHVGRkZGXVstXFx3XFx4QTAtXFx1RkZGRl0qKD89XFxzKjopL2ksaW1wb3J0YW50Oi8haW1wb3J0YW50XFxiL2ksZnVuY3Rpb246L1stYS16MC05XSsoPz1cXCgpL2kscHVuY3R1YXRpb246L1soKXt9OzosXS99LHMubGFuZ3VhZ2VzLmNzcy5hdHJ1bGUuaW5zaWRlLnJlc3Q9cy5sYW5ndWFnZXMuY3NzO3ZhciBlPXMubGFuZ3VhZ2VzLm1hcmt1cDtlJiYoZS50YWcuYWRkSW5saW5lZChcInN0eWxlXCIsXCJjc3NcIikscy5sYW5ndWFnZXMuaW5zZXJ0QmVmb3JlKFwiaW5zaWRlXCIsXCJhdHRyLXZhbHVlXCIse1wic3R5bGUtYXR0clwiOntwYXR0ZXJuOi9cXHMqc3R5bGU9KFwifCcpKD86XFxcXFtcXHNcXFNdfCg/IVxcMSlbXlxcXFxdKSpcXDEvaSxpbnNpZGU6e1wiYXR0ci1uYW1lXCI6e3BhdHRlcm46L15cXHMqc3R5bGUvaSxpbnNpZGU6ZS50YWcuaW5zaWRlfSxwdW5jdHVhdGlvbjovXlxccyo9XFxzKlsnXCJdfFsnXCJdXFxzKiQvLFwiYXR0ci12YWx1ZVwiOntwYXR0ZXJuOi8uKy9pLGluc2lkZTpzLmxhbmd1YWdlcy5jc3N9fSxhbGlhczpcImxhbmd1YWdlLWNzc1wifX0sZS50YWcpKX0oUHJpc20pO1xuUHJpc20ubGFuZ3VhZ2VzLmNsaWtlPXtjb21tZW50Olt7cGF0dGVybjovKF58W15cXFxcXSlcXC9cXCpbXFxzXFxTXSo/KD86XFwqXFwvfCQpLyxsb29rYmVoaW5kOiEwfSx7cGF0dGVybjovKF58W15cXFxcOl0pXFwvXFwvLiovLGxvb2tiZWhpbmQ6ITAsZ3JlZWR5OiEwfV0sc3RyaW5nOntwYXR0ZXJuOi8oW1wiJ10pKD86XFxcXCg/OlxcclxcbnxbXFxzXFxTXSl8KD8hXFwxKVteXFxcXFxcclxcbl0pKlxcMS8sZ3JlZWR5OiEwfSxcImNsYXNzLW5hbWVcIjp7cGF0dGVybjovKFxcYig/OmNsYXNzfGludGVyZmFjZXxleHRlbmRzfGltcGxlbWVudHN8dHJhaXR8aW5zdGFuY2VvZnxuZXcpXFxzK3xcXGJjYXRjaFxccytcXCgpW1xcdy5cXFxcXSsvaSxsb29rYmVoaW5kOiEwLGluc2lkZTp7cHVuY3R1YXRpb246L1suXFxcXF0vfX0sa2V5d29yZDovXFxiKD86aWZ8ZWxzZXx3aGlsZXxkb3xmb3J8cmV0dXJufGlufGluc3RhbmNlb2Z8ZnVuY3Rpb258bmV3fHRyeXx0aHJvd3xjYXRjaHxmaW5hbGx5fG51bGx8YnJlYWt8Y29udGludWUpXFxiLyxib29sZWFuOi9cXGIoPzp0cnVlfGZhbHNlKVxcYi8sZnVuY3Rpb246L1xcdysoPz1cXCgpLyxudW1iZXI6L1xcYjB4W1xcZGEtZl0rXFxifCg/OlxcYlxcZCtcXC4/XFxkKnxcXEJcXC5cXGQrKSg/OmVbKy1dP1xcZCspPy9pLG9wZXJhdG9yOi9bPD5dPT98WyE9XT0/PT98LS0/fFxcK1xcKz98JiY/fFxcfFxcfD98Wz8qL35eJV0vLHB1bmN0dWF0aW9uOi9be31bXFxdOygpLC46XS99O1xuUHJpc20ubGFuZ3VhZ2VzLmphdmFzY3JpcHQ9UHJpc20ubGFuZ3VhZ2VzLmV4dGVuZChcImNsaWtlXCIse1wiY2xhc3MtbmFtZVwiOltQcmlzbS5sYW5ndWFnZXMuY2xpa2VbXCJjbGFzcy1uYW1lXCJdLHtwYXR0ZXJuOi8oXnxbXiRcXHdcXHhBMC1cXHVGRkZGXSlbXyRBLVpcXHhBMC1cXHVGRkZGXVskXFx3XFx4QTAtXFx1RkZGRl0qKD89XFwuKD86cHJvdG90eXBlfGNvbnN0cnVjdG9yKSkvLGxvb2tiZWhpbmQ6ITB9XSxrZXl3b3JkOlt7cGF0dGVybjovKCg/Ol58fSlcXHMqKSg/OmNhdGNofGZpbmFsbHkpXFxiLyxsb29rYmVoaW5kOiEwfSx7cGF0dGVybjovKF58W14uXSlcXGIoPzphc3xhc3luYyg/PVxccyooPzpmdW5jdGlvblxcYnxcXCh8WyRcXHdcXHhBMC1cXHVGRkZGXXwkKSl8YXdhaXR8YnJlYWt8Y2FzZXxjbGFzc3xjb25zdHxjb250aW51ZXxkZWJ1Z2dlcnxkZWZhdWx0fGRlbGV0ZXxkb3xlbHNlfGVudW18ZXhwb3J0fGV4dGVuZHN8Zm9yfGZyb218ZnVuY3Rpb258Z2V0fGlmfGltcGxlbWVudHN8aW1wb3J0fGlufGluc3RhbmNlb2Z8aW50ZXJmYWNlfGxldHxuZXd8bnVsbHxvZnxwYWNrYWdlfHByaXZhdGV8cHJvdGVjdGVkfHB1YmxpY3xyZXR1cm58c2V0fHN0YXRpY3xzdXBlcnxzd2l0Y2h8dGhpc3x0aHJvd3x0cnl8dHlwZW9mfHVuZGVmaW5lZHx2YXJ8dm9pZHx3aGlsZXx3aXRofHlpZWxkKVxcYi8sbG9va2JlaGluZDohMH1dLG51bWJlcjovXFxiKD86KD86MFt4WF0oPzpbXFxkQS1GYS1mXSg/Ol9bXFxkQS1GYS1mXSk/KSt8MFtiQl0oPzpbMDFdKD86X1swMV0pPykrfDBbb09dKD86WzAtN10oPzpfWzAtN10pPykrKW4/fCg/OlxcZCg/Ol9cXGQpPykrbnxOYU58SW5maW5pdHkpXFxifCg/OlxcYig/OlxcZCg/Ol9cXGQpPykrXFwuPyg/OlxcZCg/Ol9cXGQpPykqfFxcQlxcLig/OlxcZCg/Ol9cXGQpPykrKSg/OltFZV1bKy1dPyg/OlxcZCg/Ol9cXGQpPykrKT8vLGZ1bmN0aW9uOi8jP1tfJGEtekEtWlxceEEwLVxcdUZGRkZdWyRcXHdcXHhBMC1cXHVGRkZGXSooPz1cXHMqKD86XFwuXFxzKig/OmFwcGx5fGJpbmR8Y2FsbClcXHMqKT9cXCgpLyxvcGVyYXRvcjovLS18XFwrXFwrfFxcKlxcKj0/fD0+fCYmfFxcfFxcfHxbIT1dPT18PDw9P3w+Pj4/PT98Wy0rKi8lJnxeIT08Pl09P3xcXC57M318XFw/Wy4/XT98W346XS99KSxQcmlzbS5sYW5ndWFnZXMuamF2YXNjcmlwdFtcImNsYXNzLW5hbWVcIl1bMF0ucGF0dGVybj0vKFxcYig/OmNsYXNzfGludGVyZmFjZXxleHRlbmRzfGltcGxlbWVudHN8aW5zdGFuY2VvZnxuZXcpXFxzKylbXFx3LlxcXFxdKy8sUHJpc20ubGFuZ3VhZ2VzLmluc2VydEJlZm9yZShcImphdmFzY3JpcHRcIixcImtleXdvcmRcIix7cmVnZXg6e3BhdHRlcm46LygoPzpefFteJFxcd1xceEEwLVxcdUZGRkYuXCInXFxdKVxcc10pXFxzKilcXC8oPzpcXFsoPzpbXlxcXVxcXFxcXHJcXG5dfFxcXFwuKSpdfFxcXFwufFteL1xcXFxcXFtcXHJcXG5dKStcXC9bZ2lteXVzXXswLDZ9KD89XFxzKig/OiR8W1xcclxcbiwuO30pXFxdXSkpLyxsb29rYmVoaW5kOiEwLGdyZWVkeTohMH0sXCJmdW5jdGlvbi12YXJpYWJsZVwiOntwYXR0ZXJuOi8jP1tfJGEtekEtWlxceEEwLVxcdUZGRkZdWyRcXHdcXHhBMC1cXHVGRkZGXSooPz1cXHMqWz06XVxccyooPzphc3luY1xccyopPyg/OlxcYmZ1bmN0aW9uXFxifCg/OlxcKCg/OlteKCldfFxcKFteKCldKlxcKSkqXFwpfFtfJGEtekEtWlxceEEwLVxcdUZGRkZdWyRcXHdcXHhBMC1cXHVGRkZGXSopXFxzKj0+KSkvLGFsaWFzOlwiZnVuY3Rpb25cIn0scGFyYW1ldGVyOlt7cGF0dGVybjovKGZ1bmN0aW9uKD86XFxzK1tfJEEtWmEtelxceEEwLVxcdUZGRkZdWyRcXHdcXHhBMC1cXHVGRkZGXSopP1xccypcXChcXHMqKSg/IVxccykoPzpbXigpXXxcXChbXigpXSpcXCkpKz8oPz1cXHMqXFwpKS8sbG9va2JlaGluZDohMCxpbnNpZGU6UHJpc20ubGFuZ3VhZ2VzLmphdmFzY3JpcHR9LHtwYXR0ZXJuOi9bXyRhLXpcXHhBMC1cXHVGRkZGXVskXFx3XFx4QTAtXFx1RkZGRl0qKD89XFxzKj0+KS9pLGluc2lkZTpQcmlzbS5sYW5ndWFnZXMuamF2YXNjcmlwdH0se3BhdHRlcm46LyhcXChcXHMqKSg/IVxccykoPzpbXigpXXxcXChbXigpXSpcXCkpKz8oPz1cXHMqXFwpXFxzKj0+KS8sbG9va2JlaGluZDohMCxpbnNpZGU6UHJpc20ubGFuZ3VhZ2VzLmphdmFzY3JpcHR9LHtwYXR0ZXJuOi8oKD86XFxifFxcc3xeKSg/ISg/OmFzfGFzeW5jfGF3YWl0fGJyZWFrfGNhc2V8Y2F0Y2h8Y2xhc3N8Y29uc3R8Y29udGludWV8ZGVidWdnZXJ8ZGVmYXVsdHxkZWxldGV8ZG98ZWxzZXxlbnVtfGV4cG9ydHxleHRlbmRzfGZpbmFsbHl8Zm9yfGZyb218ZnVuY3Rpb258Z2V0fGlmfGltcGxlbWVudHN8aW1wb3J0fGlufGluc3RhbmNlb2Z8aW50ZXJmYWNlfGxldHxuZXd8bnVsbHxvZnxwYWNrYWdlfHByaXZhdGV8cHJvdGVjdGVkfHB1YmxpY3xyZXR1cm58c2V0fHN0YXRpY3xzdXBlcnxzd2l0Y2h8dGhpc3x0aHJvd3x0cnl8dHlwZW9mfHVuZGVmaW5lZHx2YXJ8dm9pZHx3aGlsZXx3aXRofHlpZWxkKSg/IVskXFx3XFx4QTAtXFx1RkZGRl0pKSg/OltfJEEtWmEtelxceEEwLVxcdUZGRkZdWyRcXHdcXHhBMC1cXHVGRkZGXSpcXHMqKVxcKFxccyopKD8hXFxzKSg/OlteKCldfFxcKFteKCldKlxcKSkrPyg/PVxccypcXClcXHMqXFx7KS8sbG9va2JlaGluZDohMCxpbnNpZGU6UHJpc20ubGFuZ3VhZ2VzLmphdmFzY3JpcHR9XSxjb25zdGFudDovXFxiW0EtWl0oPzpbQS1aX118XFxkeD8pKlxcYi99KSxQcmlzbS5sYW5ndWFnZXMuaW5zZXJ0QmVmb3JlKFwiamF2YXNjcmlwdFwiLFwic3RyaW5nXCIse1widGVtcGxhdGUtc3RyaW5nXCI6e3BhdHRlcm46L2AoPzpcXFxcW1xcc1xcU118XFwkeyg/Oltee31dfHsoPzpbXnt9XXx7W159XSp9KSp9KSt9fCg/IVxcJHspW15cXFxcYF0pKmAvLGdyZWVkeTohMCxpbnNpZGU6e1widGVtcGxhdGUtcHVuY3R1YXRpb25cIjp7cGF0dGVybjovXmB8YCQvLGFsaWFzOlwic3RyaW5nXCJ9LGludGVycG9sYXRpb246e3BhdHRlcm46LygoPzpefFteXFxcXF0pKD86XFxcXHsyfSkqKVxcJHsoPzpbXnt9XXx7KD86W157fV18e1tefV0qfSkqfSkrfS8sbG9va2JlaGluZDohMCxpbnNpZGU6e1wiaW50ZXJwb2xhdGlvbi1wdW5jdHVhdGlvblwiOntwYXR0ZXJuOi9eXFwke3x9JC8sYWxpYXM6XCJwdW5jdHVhdGlvblwifSxyZXN0OlByaXNtLmxhbmd1YWdlcy5qYXZhc2NyaXB0fX0sc3RyaW5nOi9bXFxzXFxTXSsvfX19KSxQcmlzbS5sYW5ndWFnZXMubWFya3VwJiZQcmlzbS5sYW5ndWFnZXMubWFya3VwLnRhZy5hZGRJbmxpbmVkKFwic2NyaXB0XCIsXCJqYXZhc2NyaXB0XCIpLFByaXNtLmxhbmd1YWdlcy5qcz1QcmlzbS5sYW5ndWFnZXMuamF2YXNjcmlwdDtcbiFmdW5jdGlvbihoKXtmdW5jdGlvbiB2KGUsbil7cmV0dXJuXCJfX19cIitlLnRvVXBwZXJDYXNlKCkrbitcIl9fX1wifU9iamVjdC5kZWZpbmVQcm9wZXJ0aWVzKGgubGFuZ3VhZ2VzW1wibWFya3VwLXRlbXBsYXRpbmdcIl09e30se2J1aWxkUGxhY2Vob2xkZXJzOnt2YWx1ZTpmdW5jdGlvbihhLHIsZSxvKXtpZihhLmxhbmd1YWdlPT09cil7dmFyIGM9YS50b2tlblN0YWNrPVtdO2EuY29kZT1hLmNvZGUucmVwbGFjZShlLGZ1bmN0aW9uKGUpe2lmKFwiZnVuY3Rpb25cIj09dHlwZW9mIG8mJiFvKGUpKXJldHVybiBlO2Zvcih2YXIgbix0PWMubGVuZ3RoOy0xIT09YS5jb2RlLmluZGV4T2Yobj12KHIsdCkpOykrK3Q7cmV0dXJuIGNbdF09ZSxufSksYS5ncmFtbWFyPWgubGFuZ3VhZ2VzLm1hcmt1cH19fSx0b2tlbml6ZVBsYWNlaG9sZGVyczp7dmFsdWU6ZnVuY3Rpb24ocCxrKXtpZihwLmxhbmd1YWdlPT09ayYmcC50b2tlblN0YWNrKXtwLmdyYW1tYXI9aC5sYW5ndWFnZXNba107dmFyIG09MCxkPU9iamVjdC5rZXlzKHAudG9rZW5TdGFjayk7IWZ1bmN0aW9uIGUobil7Zm9yKHZhciB0PTA7dDxuLmxlbmd0aCYmIShtPj1kLmxlbmd0aCk7dCsrKXt2YXIgYT1uW3RdO2lmKFwic3RyaW5nXCI9PXR5cGVvZiBhfHxhLmNvbnRlbnQmJlwic3RyaW5nXCI9PXR5cGVvZiBhLmNvbnRlbnQpe3ZhciByPWRbbV0sbz1wLnRva2VuU3RhY2tbcl0sYz1cInN0cmluZ1wiPT10eXBlb2YgYT9hOmEuY29udGVudCxpPXYoayxyKSx1PWMuaW5kZXhPZihpKTtpZigtMTx1KXsrK207dmFyIGc9Yy5zdWJzdHJpbmcoMCx1KSxsPW5ldyBoLlRva2VuKGssaC50b2tlbml6ZShvLHAuZ3JhbW1hciksXCJsYW5ndWFnZS1cIitrLG8pLHM9Yy5zdWJzdHJpbmcodStpLmxlbmd0aCksZj1bXTtnJiZmLnB1c2guYXBwbHkoZixlKFtnXSkpLGYucHVzaChsKSxzJiZmLnB1c2guYXBwbHkoZixlKFtzXSkpLFwic3RyaW5nXCI9PXR5cGVvZiBhP24uc3BsaWNlLmFwcGx5KG4sW3QsMV0uY29uY2F0KGYpKTphLmNvbnRlbnQ9Zn19ZWxzZSBhLmNvbnRlbnQmJmUoYS5jb250ZW50KX1yZXR1cm4gbn0ocC50b2tlbnMpfX19fSl9KFByaXNtKTtcbiFmdW5jdGlvbihuKXtuLmxhbmd1YWdlcy5waHA9bi5sYW5ndWFnZXMuZXh0ZW5kKFwiY2xpa2VcIix7a2V5d29yZDovXFxiKD86X19oYWx0X2NvbXBpbGVyfGFic3RyYWN0fGFuZHxhcnJheXxhc3xicmVha3xjYWxsYWJsZXxjYXNlfGNhdGNofGNsYXNzfGNsb25lfGNvbnN0fGNvbnRpbnVlfGRlY2xhcmV8ZGVmYXVsdHxkaWV8ZG98ZWNob3xlbHNlfGVsc2VpZnxlbXB0eXxlbmRkZWNsYXJlfGVuZGZvcnxlbmRmb3JlYWNofGVuZGlmfGVuZHN3aXRjaHxlbmR3aGlsZXxldmFsfGV4aXR8ZXh0ZW5kc3xmaW5hbHxmaW5hbGx5fGZvcnxmb3JlYWNofGZ1bmN0aW9ufGdsb2JhbHxnb3RvfGlmfGltcGxlbWVudHN8aW5jbHVkZXxpbmNsdWRlX29uY2V8aW5zdGFuY2VvZnxpbnN0ZWFkb2Z8aW50ZXJmYWNlfGlzc2V0fGxpc3R8bmFtZXNwYWNlfG5ld3xvcnxwYXJlbnR8cHJpbnR8cHJpdmF0ZXxwcm90ZWN0ZWR8cHVibGljfHJlcXVpcmV8cmVxdWlyZV9vbmNlfHJldHVybnxzdGF0aWN8c3dpdGNofHRocm93fHRyYWl0fHRyeXx1bnNldHx1c2V8dmFyfHdoaWxlfHhvcnx5aWVsZClcXGIvaSxib29sZWFuOntwYXR0ZXJuOi9cXGIoPzpmYWxzZXx0cnVlKVxcYi9pLGFsaWFzOlwiY29uc3RhbnRcIn0sY29uc3RhbnQ6Wy9cXGJbQS1aX11bQS1aMC05X10qXFxiLywvXFxiKD86bnVsbClcXGIvaV0sY29tbWVudDp7cGF0dGVybjovKF58W15cXFxcXSkoPzpcXC9cXCpbXFxzXFxTXSo/XFwqXFwvfFxcL1xcLy4qKS8sbG9va2JlaGluZDohMH19KSxuLmxhbmd1YWdlcy5pbnNlcnRCZWZvcmUoXCJwaHBcIixcInN0cmluZ1wiLHtcInNoZWxsLWNvbW1lbnRcIjp7cGF0dGVybjovKF58W15cXFxcXSkjLiovLGxvb2tiZWhpbmQ6ITAsYWxpYXM6XCJjb21tZW50XCJ9fSksbi5sYW5ndWFnZXMuaW5zZXJ0QmVmb3JlKFwicGhwXCIsXCJjb21tZW50XCIse2RlbGltaXRlcjp7cGF0dGVybjovXFw/PiR8XjxcXD8oPzpwaHAoPz1cXHMpfD0pPy9pLGFsaWFzOlwiaW1wb3J0YW50XCJ9fSksbi5sYW5ndWFnZXMuaW5zZXJ0QmVmb3JlKFwicGhwXCIsXCJrZXl3b3JkXCIse3ZhcmlhYmxlOi9cXCQrKD86XFx3K1xcYnwoPz17KSkvaSxwYWNrYWdlOntwYXR0ZXJuOi8oXFxcXHxuYW1lc3BhY2VcXHMrfHVzZVxccyspW1xcd1xcXFxdKy8sbG9va2JlaGluZDohMCxpbnNpZGU6e3B1bmN0dWF0aW9uOi9cXFxcL319fSksbi5sYW5ndWFnZXMuaW5zZXJ0QmVmb3JlKFwicGhwXCIsXCJvcGVyYXRvclwiLHtwcm9wZXJ0eTp7cGF0dGVybjovKC0+KVtcXHddKy8sbG9va2JlaGluZDohMH19KTt2YXIgZT17cGF0dGVybjove1xcJCg/OnsoPzp7W157fV0rfXxbXnt9XSspfXxbXnt9XSkrfXwoXnxbXlxcXFx7XSlcXCQrKD86XFx3Kyg/OlxcWy4rP118LT5cXHcrKSopLyxsb29rYmVoaW5kOiEwLGluc2lkZTpuLmxhbmd1YWdlcy5waHB9O24ubGFuZ3VhZ2VzLmluc2VydEJlZm9yZShcInBocFwiLFwic3RyaW5nXCIse1wibm93ZG9jLXN0cmluZ1wiOntwYXR0ZXJuOi88PDwnKFteJ10rKScoPzpcXHJcXG4/fFxcbikoPzouKig/Olxcclxcbj98XFxuKSkqP1xcMTsvLGdyZWVkeTohMCxhbGlhczpcInN0cmluZ1wiLGluc2lkZTp7ZGVsaW1pdGVyOntwYXR0ZXJuOi9ePDw8J1teJ10rJ3xbYS16X11cXHcqOyQvaSxhbGlhczpcInN5bWJvbFwiLGluc2lkZTp7cHVuY3R1YXRpb246L148PDwnP3xbJztdJC99fX19LFwiaGVyZWRvYy1zdHJpbmdcIjp7cGF0dGVybjovPDw8KD86XCIoW15cIl0rKVwiKD86XFxyXFxuP3xcXG4pKD86LiooPzpcXHJcXG4/fFxcbikpKj9cXDE7fChbYS16X11cXHcqKSg/Olxcclxcbj98XFxuKSg/Oi4qKD86XFxyXFxuP3xcXG4pKSo/XFwyOykvaSxncmVlZHk6ITAsYWxpYXM6XCJzdHJpbmdcIixpbnNpZGU6e2RlbGltaXRlcjp7cGF0dGVybjovXjw8PCg/OlwiW15cIl0rXCJ8W2Etel9dXFx3Kil8W2Etel9dXFx3KjskL2ksYWxpYXM6XCJzeW1ib2xcIixpbnNpZGU6e3B1bmN0dWF0aW9uOi9ePDw8XCI/fFtcIjtdJC99fSxpbnRlcnBvbGF0aW9uOmV9fSxcInNpbmdsZS1xdW90ZWQtc3RyaW5nXCI6e3BhdHRlcm46LycoPzpcXFxcW1xcc1xcU118W15cXFxcJ10pKicvLGdyZWVkeTohMCxhbGlhczpcInN0cmluZ1wifSxcImRvdWJsZS1xdW90ZWQtc3RyaW5nXCI6e3BhdHRlcm46L1wiKD86XFxcXFtcXHNcXFNdfFteXFxcXFwiXSkqXCIvLGdyZWVkeTohMCxhbGlhczpcInN0cmluZ1wiLGluc2lkZTp7aW50ZXJwb2xhdGlvbjplfX19KSxkZWxldGUgbi5sYW5ndWFnZXMucGhwLnN0cmluZyxuLmhvb2tzLmFkZChcImJlZm9yZS10b2tlbml6ZVwiLGZ1bmN0aW9uKGUpe2lmKC88XFw/Ly50ZXN0KGUuY29kZSkpe24ubGFuZ3VhZ2VzW1wibWFya3VwLXRlbXBsYXRpbmdcIl0uYnVpbGRQbGFjZWhvbGRlcnMoZSxcInBocFwiLC88XFw/KD86W15cIicvI118XFwvKD8hWyovXSl8KFwifCcpKD86XFxcXFtcXHNcXFNdfCg/IVxcMSlbXlxcXFxdKSpcXDF8KD86XFwvXFwvfCMpKD86W14/XFxuXFxyXXxcXD8oPyE+KSkqfFxcL1xcKltcXHNcXFNdKj8oPzpcXCpcXC98JCkpKj8oPzpcXD8+fCQpL2dpKX19KSxuLmhvb2tzLmFkZChcImFmdGVyLXRva2VuaXplXCIsZnVuY3Rpb24oZSl7bi5sYW5ndWFnZXNbXCJtYXJrdXAtdGVtcGxhdGluZ1wiXS50b2tlbml6ZVBsYWNlaG9sZGVycyhlLFwicGhwXCIpfSl9KFByaXNtKTtcbiFmdW5jdGlvbihwKXt2YXIgYT1wLmxhbmd1YWdlcy5qYXZhZG9jbGlrZT17cGFyYW1ldGVyOntwYXR0ZXJuOi8oXlxccyooPzpcXC97M318XFwqfFxcL1xcKlxcKilcXHMqQCg/OnBhcmFtfGFyZ3xhcmd1bWVudHMpXFxzKylcXHcrL20sbG9va2JlaGluZDohMH0sa2V5d29yZDp7cGF0dGVybjovKF5cXHMqKD86XFwvezN9fFxcKnxcXC9cXCpcXCopXFxzKnxcXHspQFthLXpdW2EtekEtWi1dK1xcYi9tLGxvb2tiZWhpbmQ6ITB9LHB1bmN0dWF0aW9uOi9be31dL307T2JqZWN0LmRlZmluZVByb3BlcnR5KGEsXCJhZGRTdXBwb3J0XCIse3ZhbHVlOmZ1bmN0aW9uKGEsZSl7XCJzdHJpbmdcIj09dHlwZW9mIGEmJihhPVthXSksYS5mb3JFYWNoKGZ1bmN0aW9uKGEpeyFmdW5jdGlvbihhLGUpe3ZhciBuPVwiZG9jLWNvbW1lbnRcIix0PXAubGFuZ3VhZ2VzW2FdO2lmKHQpe3ZhciByPXRbbl07aWYoIXIpe3ZhciBvPXtcImRvYy1jb21tZW50XCI6e3BhdHRlcm46LyhefFteXFxcXF0pXFwvXFwqXFwqW14vXVtcXHNcXFNdKj8oPzpcXCpcXC98JCkvLGxvb2tiZWhpbmQ6ITAsYWxpYXM6XCJjb21tZW50XCJ9fTtyPSh0PXAubGFuZ3VhZ2VzLmluc2VydEJlZm9yZShhLFwiY29tbWVudFwiLG8pKVtuXX1pZihyIGluc3RhbmNlb2YgUmVnRXhwJiYocj10W25dPXtwYXR0ZXJuOnJ9KSxBcnJheS5pc0FycmF5KHIpKWZvcih2YXIgaT0wLHM9ci5sZW5ndGg7aTxzO2krKylyW2ldaW5zdGFuY2VvZiBSZWdFeHAmJihyW2ldPXtwYXR0ZXJuOnJbaV19KSxlKHJbaV0pO2Vsc2UgZShyKX19KGEsZnVuY3Rpb24oYSl7YS5pbnNpZGV8fChhLmluc2lkZT17fSksYS5pbnNpZGUucmVzdD1lfSl9KX19KSxhLmFkZFN1cHBvcnQoW1wiamF2YVwiLFwiamF2YXNjcmlwdFwiLFwicGhwXCJdLGEpfShQcmlzbSk7XG4hZnVuY3Rpb24oYSl7dmFyIGU9XCIoPzpbYS16QS1aXVxcXFx3KnxbfFxcXFxcXFxcW1xcXFxdXSkrXCI7YS5sYW5ndWFnZXMucGhwZG9jPWEubGFuZ3VhZ2VzLmV4dGVuZChcImphdmFkb2NsaWtlXCIse3BhcmFtZXRlcjp7cGF0dGVybjpSZWdFeHAoXCIoQCg/Omdsb2JhbHxwYXJhbXxwcm9wZXJ0eSg/Oi1yZWFkfC13cml0ZSk/fHZhcilcXFxccysoPzpcIitlK1wiXFxcXHMrKT8pXFxcXCRcXFxcdytcIiksbG9va2JlaGluZDohMH19KSxhLmxhbmd1YWdlcy5pbnNlcnRCZWZvcmUoXCJwaHBkb2NcIixcImtleXdvcmRcIix7XCJjbGFzcy1uYW1lXCI6W3twYXR0ZXJuOlJlZ0V4cChcIihAKD86Z2xvYmFsfHBhY2thZ2V8cGFyYW18cHJvcGVydHkoPzotcmVhZHwtd3JpdGUpP3xyZXR1cm58c3VicGFja2FnZXx0aHJvd3N8dmFyKVxcXFxzKylcIitlKSxsb29rYmVoaW5kOiEwLGluc2lkZTp7a2V5d29yZDovXFxiKD86Y2FsbGJhY2t8cmVzb3VyY2V8Ym9vbGVhbnxpbnRlZ2VyfGRvdWJsZXxvYmplY3R8c3RyaW5nfGFycmF5fGZhbHNlfGZsb2F0fG1peGVkfGJvb2x8bnVsbHxzZWxmfHRydWV8dm9pZHxpbnQpXFxiLyxwdW5jdHVhdGlvbjovW3xcXFxcW1xcXSgpXS99fV19KSxhLmxhbmd1YWdlcy5qYXZhZG9jbGlrZS5hZGRTdXBwb3J0KFwicGhwXCIsYS5sYW5ndWFnZXMucGhwZG9jKX0oUHJpc20pO1xuUHJpc20ubGFuZ3VhZ2VzLmluc2VydEJlZm9yZShcInBocFwiLFwidmFyaWFibGVcIix7dGhpczovXFwkdGhpc1xcYi8sZ2xvYmFsOi9cXCQoPzpfKD86U0VSVkVSfEdFVHxQT1NUfEZJTEVTfFJFUVVFU1R8U0VTU0lPTnxFTlZ8Q09PS0lFKXxHTE9CQUxTfEhUVFBfUkFXX1BPU1RfREFUQXxhcmdjfGFyZ3Z8cGhwX2Vycm9ybXNnfGh0dHBfcmVzcG9uc2VfaGVhZGVyKVxcYi8sc2NvcGU6e3BhdHRlcm46L1xcYltcXHdcXFxcXSs6Oi8saW5zaWRlOntrZXl3b3JkOi9zdGF0aWN8c2VsZnxwYXJlbnQvLHB1bmN0dWF0aW9uOi86OnxcXFxcL319fSk7XG5QcmlzbS5sYW5ndWFnZXMuc3FsPXtjb21tZW50OntwYXR0ZXJuOi8oXnxbXlxcXFxdKSg/OlxcL1xcKltcXHNcXFNdKj9cXCpcXC98KD86LS18XFwvXFwvfCMpLiopLyxsb29rYmVoaW5kOiEwfSx2YXJpYWJsZTpbe3BhdHRlcm46L0AoW1wiJ2BdKSg/OlxcXFxbXFxzXFxTXXwoPyFcXDEpW15cXFxcXSkrXFwxLyxncmVlZHk6ITB9LC9AW1xcdy4kXSsvXSxzdHJpbmc6e3BhdHRlcm46LyhefFteQFxcXFxdKShcInwnKSg/OlxcXFxbXFxzXFxTXXwoPyFcXDIpW15cXFxcXXxcXDJcXDIpKlxcMi8sZ3JlZWR5OiEwLGxvb2tiZWhpbmQ6ITB9LGZ1bmN0aW9uOi9cXGIoPzpBVkd8Q09VTlR8RklSU1R8Rk9STUFUfExBU1R8TENBU0V8TEVOfE1BWHxNSUR8TUlOfE1PRHxOT1d8Uk9VTkR8U1VNfFVDQVNFKSg/PVxccypcXCgpL2ksa2V5d29yZDovXFxiKD86QUNUSU9OfEFERHxBRlRFUnxBTEdPUklUSE18QUxMfEFMVEVSfEFOQUxZWkV8QU5ZfEFQUExZfEFTfEFTQ3xBVVRIT1JJWkFUSU9OfEFVVE9fSU5DUkVNRU5UfEJBQ0tVUHxCREJ8QkVHSU58QkVSS0VMRVlEQnxCSUdJTlR8QklOQVJZfEJJVHxCTE9CfEJPT0x8Qk9PTEVBTnxCUkVBS3xCUk9XU0V8QlRSRUV8QlVMS3xCWXxDQUxMfENBU0NBREVEP3xDQVNFfENIQUlOfENIQVIoPzpBQ1RFUnxTRVQpP3xDSEVDSyg/OlBPSU5UKT98Q0xPU0V8Q0xVU1RFUkVEfENPQUxFU0NFfENPTExBVEV8Q09MVU1OUz98Q09NTUVOVHxDT01NSVQoPzpURUQpP3xDT01QVVRFfENPTk5FQ1R8Q09OU0lTVEVOVHxDT05TVFJBSU5UfENPTlRBSU5TKD86VEFCTEUpP3xDT05USU5VRXxDT05WRVJUfENSRUFURXxDUk9TU3xDVVJSRU5UKD86X0RBVEV8X1RJTUV8X1RJTUVTVEFNUHxfVVNFUik/fENVUlNPUnxDWUNMRXxEQVRBKD86QkFTRVM/KT98REFURSg/OlRJTUUpP3xEQVl8REJDQ3xERUFMTE9DQVRFfERFQ3xERUNJTUFMfERFQ0xBUkV8REVGQVVMVHxERUZJTkVSfERFTEFZRUR8REVMRVRFfERFTElNSVRFUlM/fERFTll8REVTQ3xERVNDUklCRXxERVRFUk1JTklTVElDfERJU0FCTEV8RElTQ0FSRHxESVNLfERJU1RJTkNUfERJU1RJTkNUUk9XfERJU1RSSUJVVEVEfERPfERPVUJMRXxEUk9QfERVTU1ZfERVTVAoPzpGSUxFKT98RFVQTElDQVRFfEVMU0UoPzpJRik/fEVOQUJMRXxFTkNMT1NFRHxFTkR8RU5HSU5FfEVOVU18RVJSTFZMfEVSUk9SU3xFU0NBUEVEP3xFWENFUFR8RVhFQyg/OlVURSk/fEVYSVNUU3xFWElUfEVYUExBSU58RVhURU5ERUR8RkVUQ0h8RklFTERTfEZJTEV8RklMTEZBQ1RPUnxGSVJTVHxGSVhFRHxGTE9BVHxGT0xMT1dJTkd8Rk9SKD86IEVBQ0ggUk9XKT98Rk9SQ0V8Rk9SRUlHTnxGUkVFVEVYVCg/OlRBQkxFKT98RlJPTXxGVUxMfEZVTkNUSU9OfEdFT01FVFJZKD86Q09MTEVDVElPTik/fEdMT0JBTHxHT1RPfEdSQU5UfEdST1VQfEhBTkRMRVJ8SEFTSHxIQVZJTkd8SE9MRExPQ0t8SE9VUnxJREVOVElUWSg/Ol9JTlNFUlR8Q09MKT98SUZ8SUdOT1JFfElNUE9SVHxJTkRFWHxJTkZJTEV8SU5ORVJ8SU5OT0RCfElOT1VUfElOU0VSVHxJTlR8SU5URUdFUnxJTlRFUlNFQ1R8SU5URVJWQUx8SU5UT3xJTlZPS0VSfElTT0xBVElPTnxJVEVSQVRFfEpPSU58S0VZUz98S0lMTHxMQU5HVUFHRXxMQVNUfExFQVZFfExFRlR8TEVWRUx8TElNSVR8TElORU5PfExJTkVTfExJTkVTVFJJTkd8TE9BRHxMT0NBTHxMT0NLfExPTkcoPzpCTE9CfFRFWFQpfExPT1B8TUFUQ0goPzpFRCk/fE1FRElVTSg/OkJMT0J8SU5UfFRFWFQpfE1FUkdFfE1JRERMRUlOVHxNSU5VVEV8TU9ERXxNT0RJRklFU3xNT0RJRll8TU9OVEh8TVVMVEkoPzpMSU5FU1RSSU5HfFBPSU5UfFBPTFlHT04pfE5BVElPTkFMfE5BVFVSQUx8TkNIQVJ8TkVYVHxOT3xOT05DTFVTVEVSRUR8TlVMTElGfE5VTUVSSUN8T0ZGP3xPRkZTRVRTP3xPTnxPUEVOKD86REFUQVNPVVJDRXxRVUVSWXxST1dTRVQpP3xPUFRJTUlaRXxPUFRJT04oPzpBTExZKT98T1JERVJ8T1VUKD86RVJ8RklMRSk/fE9WRVJ8UEFSVElBTHxQQVJUSVRJT058UEVSQ0VOVHxQSVZPVHxQTEFOfFBPSU5UfFBPTFlHT058UFJFQ0VESU5HfFBSRUNJU0lPTnxQUkVQQVJFfFBSRVZ8UFJJTUFSWXxQUklOVHxQUklWSUxFR0VTfFBST0MoPzpFRFVSRSk/fFBVQkxJQ3xQVVJHRXxRVUlDS3xSQUlTRVJST1J8UkVBRFM/fFJFQUx8UkVDT05GSUdVUkV8UkVGRVJFTkNFU3xSRUxFQVNFfFJFTkFNRXxSRVBFQVQoPzpBQkxFKT98UkVQTEFDRXxSRVBMSUNBVElPTnxSRVFVSVJFfFJFU0lHTkFMfFJFU1RPUkV8UkVTVFJJQ1R8UkVUVVJOUz98UkVWT0tFfFJJR0hUfFJPTExCQUNLfFJPVVRJTkV8Uk9XKD86Q09VTlR8R1VJRENPTHxTKT98UlRSRUV8UlVMRXxTQVZFKD86UE9JTlQpP3xTQ0hFTUF8U0VDT05EfFNFTEVDVHxTRVJJQUwoPzpJWkFCTEUpP3xTRVNTSU9OKD86X1VTRVIpP3xTRVQoPzpVU0VSKT98U0hBUkV8U0hPV3xTSFVURE9XTnxTSU1QTEV8U01BTExJTlR8U05BUFNIT1R8U09NRXxTT05BTUV8U1FMfFNUQVJUKD86SU5HKT98U1RBVElTVElDU3xTVEFUVVN8U1RSSVBFRHxTWVNURU1fVVNFUnxUQUJMRVM/fFRBQkxFU1BBQ0V8VEVNUCg/Ok9SQVJZfFRBQkxFKT98VEVSTUlOQVRFRHxURVhUKD86U0laRSk/fFRIRU58VElNRSg/OlNUQU1QKT98VElOWSg/OkJMT0J8SU5UfFRFWFQpfFRPUD98VFJBTig/OlNBQ1RJT05TPyk/fFRSSUdHRVJ8VFJVTkNBVEV8VFNFUVVBTHxUWVBFUz98VU5CT1VOREVEfFVOQ09NTUlUVEVEfFVOREVGSU5FRHxVTklPTnxVTklRVUV8VU5MT0NLfFVOUElWT1R8VU5TSUdORUR8VVBEQVRFKD86VEVYVCk/fFVTQUdFfFVTRXxVU0VSfFVTSU5HfFZBTFVFUz98VkFSKD86QklOQVJZfENIQVJ8Q0hBUkFDVEVSfFlJTkcpfFZJRVd8V0FJVEZPUnxXQVJOSU5HU3xXSEVOfFdIRVJFfFdISUxFfFdJVEgoPzogUk9MTFVQfElOKT98V09SS3xXUklURSg/OlRFWFQpP3xZRUFSKVxcYi9pLGJvb2xlYW46L1xcYig/OlRSVUV8RkFMU0V8TlVMTClcXGIvaSxudW1iZXI6L1xcYjB4W1xcZGEtZl0rXFxifFxcYlxcZCtcXC4/XFxkKnxcXEJcXC5cXGQrXFxiL2ksb3BlcmF0b3I6L1stKypcXC89JV5+XXwmJj98XFx8XFx8P3whPT98PCg/Oj0+P3w8fD4pP3w+Wz49XT98XFxiKD86QU5EfEJFVFdFRU58SU58TElLRXxOT1R8T1J8SVN8RElWfFJFR0VYUHxSTElLRXxTT1VORFMgTElLRXxYT1IpXFxiL2kscHVuY3R1YXRpb246L1s7W1xcXSgpYCwuXS99O1xuIWZ1bmN0aW9uKCl7aWYoXCJ1bmRlZmluZWRcIiE9dHlwZW9mIHNlbGYmJnNlbGYuUHJpc20mJnNlbGYuZG9jdW1lbnQpe3ZhciBsPVwibGluZS1udW1iZXJzXCIsYz0vXFxuKD8hJCkvZyxtPWZ1bmN0aW9uKGUpe3ZhciB0PWEoZSlbXCJ3aGl0ZS1zcGFjZVwiXTtpZihcInByZS13cmFwXCI9PT10fHxcInByZS1saW5lXCI9PT10KXt2YXIgbj1lLnF1ZXJ5U2VsZWN0b3IoXCJjb2RlXCIpLHI9ZS5xdWVyeVNlbGVjdG9yKFwiLmxpbmUtbnVtYmVycy1yb3dzXCIpLHM9ZS5xdWVyeVNlbGVjdG9yKFwiLmxpbmUtbnVtYmVycy1zaXplclwiKSxpPW4udGV4dENvbnRlbnQuc3BsaXQoYyk7c3x8KChzPWRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJzcGFuXCIpKS5jbGFzc05hbWU9XCJsaW5lLW51bWJlcnMtc2l6ZXJcIixuLmFwcGVuZENoaWxkKHMpKSxzLnN0eWxlLmRpc3BsYXk9XCJibG9ja1wiLGkuZm9yRWFjaChmdW5jdGlvbihlLHQpe3MudGV4dENvbnRlbnQ9ZXx8XCJcXG5cIjt2YXIgbj1zLmdldEJvdW5kaW5nQ2xpZW50UmVjdCgpLmhlaWdodDtyLmNoaWxkcmVuW3RdLnN0eWxlLmhlaWdodD1uK1wicHhcIn0pLHMudGV4dENvbnRlbnQ9XCJcIixzLnN0eWxlLmRpc3BsYXk9XCJub25lXCJ9fSxhPWZ1bmN0aW9uKGUpe3JldHVybiBlP3dpbmRvdy5nZXRDb21wdXRlZFN0eWxlP2dldENvbXB1dGVkU3R5bGUoZSk6ZS5jdXJyZW50U3R5bGV8fG51bGw6bnVsbH07d2luZG93LmFkZEV2ZW50TGlzdGVuZXIoXCJyZXNpemVcIixmdW5jdGlvbigpe0FycmF5LnByb3RvdHlwZS5mb3JFYWNoLmNhbGwoZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbChcInByZS5cIitsKSxtKX0pLFByaXNtLmhvb2tzLmFkZChcImNvbXBsZXRlXCIsZnVuY3Rpb24oZSl7aWYoZS5jb2RlKXt2YXIgdD1lLmVsZW1lbnQsbj10LnBhcmVudE5vZGU7aWYobiYmL3ByZS9pLnRlc3Qobi5ub2RlTmFtZSkmJiF0LnF1ZXJ5U2VsZWN0b3IoXCIubGluZS1udW1iZXJzLXJvd3NcIikpe2Zvcih2YXIgcj0hMSxzPS8oPzpefFxccylsaW5lLW51bWJlcnMoPzpcXHN8JCkvLGk9dDtpO2k9aS5wYXJlbnROb2RlKWlmKHMudGVzdChpLmNsYXNzTmFtZSkpe3I9ITA7YnJlYWt9aWYocil7dC5jbGFzc05hbWU9dC5jbGFzc05hbWUucmVwbGFjZShzLFwiIFwiKSxzLnRlc3Qobi5jbGFzc05hbWUpfHwobi5jbGFzc05hbWUrPVwiIGxpbmUtbnVtYmVyc1wiKTt2YXIgbCxhPWUuY29kZS5tYXRjaChjKSxvPWE/YS5sZW5ndGgrMToxLHU9bmV3IEFycmF5KG8rMSkuam9pbihcIjxzcGFuPjwvc3Bhbj5cIik7KGw9ZG9jdW1lbnQuY3JlYXRlRWxlbWVudChcInNwYW5cIikpLnNldEF0dHJpYnV0ZShcImFyaWEtaGlkZGVuXCIsXCJ0cnVlXCIpLGwuY2xhc3NOYW1lPVwibGluZS1udW1iZXJzLXJvd3NcIixsLmlubmVySFRNTD11LG4uaGFzQXR0cmlidXRlKFwiZGF0YS1zdGFydFwiKSYmKG4uc3R5bGUuY291bnRlclJlc2V0PVwibGluZW51bWJlciBcIisocGFyc2VJbnQobi5nZXRBdHRyaWJ1dGUoXCJkYXRhLXN0YXJ0XCIpLDEwKS0xKSksZS5lbGVtZW50LmFwcGVuZENoaWxkKGwpLG0obiksUHJpc20uaG9va3MucnVuKFwibGluZS1udW1iZXJzXCIsZSl9fX19KSxQcmlzbS5ob29rcy5hZGQoXCJsaW5lLW51bWJlcnNcIixmdW5jdGlvbihlKXtlLnBsdWdpbnM9ZS5wbHVnaW5zfHx7fSxlLnBsdWdpbnMubGluZU51bWJlcnM9ITB9KSxQcmlzbS5wbHVnaW5zLmxpbmVOdW1iZXJzPXtnZXRMaW5lOmZ1bmN0aW9uKGUsdCl7aWYoXCJQUkVcIj09PWUudGFnTmFtZSYmZS5jbGFzc0xpc3QuY29udGFpbnMobCkpe3ZhciBuPWUucXVlcnlTZWxlY3RvcihcIi5saW5lLW51bWJlcnMtcm93c1wiKSxyPXBhcnNlSW50KGUuZ2V0QXR0cmlidXRlKFwiZGF0YS1zdGFydFwiKSwxMCl8fDEscz1yKyhuLmNoaWxkcmVuLmxlbmd0aC0xKTt0PHImJih0PXIpLHM8dCYmKHQ9cyk7dmFyIGk9dC1yO3JldHVybiBuLmNoaWxkcmVuW2ldfX19fX0oKTtcbiIsIi8qKlxuICogVGhpcyBmaWxlIGlzIHBhcnQgb2YgSm9obkNNUyBDb250ZW50IE1hbmFnZW1lbnQgU3lzdGVtLlxuICpcbiAqIEBjb3B5cmlnaHQgSm9obkNNUyBDb21tdW5pdHlcbiAqIEBsaWNlbnNlICAgaHR0cHM6Ly9vcGVuc291cmNlLm9yZy9saWNlbnNlcy9HUEwtMy4wIEdQTC0zLjBcbiAqIEBsaW5rICAgICAgaHR0cHM6Ly9qb2huY21zLmNvbSBKb2huQ01TIFByb2plY3RcbiAqL1xuXG4kKGZ1bmN0aW9uICgpIHtcbiAgJChcIi5yb3VuZGVkLXByb2dyZXNzXCIpLmVhY2goZnVuY3Rpb24gKCkge1xuXG4gICAgY29uc3QgdmFsdWUgPSAkKHRoaXMpLmF0dHIoJ2RhdGEtdmFsdWUnKTtcbiAgICBjb25zdCBsZWZ0ID0gJCh0aGlzKS5maW5kKCcucHJvZ3Jlc3MtbGVmdCAucHJvZ3Jlc3MtYmFyJyk7XG4gICAgY29uc3QgcmlnaHQgPSAkKHRoaXMpLmZpbmQoJy5wcm9ncmVzcy1yaWdodCAucHJvZ3Jlc3MtYmFyJyk7XG5cbiAgICBpZiAodmFsdWUgPiAwKSB7XG4gICAgICBpZiAodmFsdWUgPD0gNTApIHtcbiAgICAgICAgcmlnaHQuY3NzKCd0cmFuc2Zvcm0nLCAncm90YXRlKCcgKyBwZXJjZW50YWdlVG9EZWdyZWVzKHZhbHVlKSArICdkZWcpJylcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHJpZ2h0LmNzcygndHJhbnNmb3JtJywgJ3JvdGF0ZSgxODBkZWcpJyk7XG4gICAgICAgIGxlZnQuY3NzKCd0cmFuc2Zvcm0nLCAncm90YXRlKCcgKyBwZXJjZW50YWdlVG9EZWdyZWVzKHZhbHVlIC0gNTApICsgJ2RlZyknKVxuICAgICAgfVxuICAgIH1cbiAgfSk7XG5cbiAgZnVuY3Rpb24gcGVyY2VudGFnZVRvRGVncmVlcyhwZXJjZW50YWdlKVxuICB7XG4gICAgcmV0dXJuIHBlcmNlbnRhZ2UgLyAxMDAgKiAzNjBcbiAgfVxufSk7XG4iLCIvKipcbiAqIFRoaXMgZmlsZSBpcyBwYXJ0IG9mIEpvaG5DTVMgQ29udGVudCBNYW5hZ2VtZW50IFN5c3RlbS5cbiAqXG4gKiBAY29weXJpZ2h0IEpvaG5DTVMgQ29tbXVuaXR5XG4gKiBAbGljZW5zZSAgIGh0dHBzOi8vb3BlbnNvdXJjZS5vcmcvbGljZW5zZXMvR1BMLTMuMCBHUEwtMy4wXG4gKiBAbGluayAgICAgIGh0dHBzOi8vam9obmNtcy5jb20gSm9obkNNUyBQcm9qZWN0XG4gKi9cblxuaW1wb3J0IFN3aXBlciBmcm9tICdzd2lwZXInO1xuXG5jb25zdCBzd2lwZXJTbGlkZXIgPSBuZXcgU3dpcGVyKCcuc2NyZWVuc2hvdHMnLCB7XG4gIHNsaWRlc1BlclZpZXc6IDEsXG4gIHNwYWNlQmV0d2VlbjogMTAsXG4gIC8vIGluaXQ6IGZhbHNlLFxuICBwYWdpbmF0aW9uOiB7XG4gICAgZWw6ICcuc3dpcGVyLXBhZ2luYXRpb24nLFxuICAgIGNsaWNrYWJsZTogdHJ1ZSxcbiAgfSxcbiAgYnJlYWtwb2ludHM6IHtcbiAgICA2NDA6IHtcbiAgICAgIHNsaWRlc1BlclZpZXc6IDIsXG4gICAgICBzcGFjZUJldHdlZW46IDIwLFxuICAgIH0sXG4gICAgNzY4OiB7XG4gICAgICBzbGlkZXNQZXJWaWV3OiAyLFxuICAgICAgc3BhY2VCZXR3ZWVuOiA0MCxcbiAgICB9LFxuICAgIDEwMjQ6IHtcbiAgICAgIHNsaWRlc1BlclZpZXc6IDMsXG4gICAgICBzcGFjZUJldHdlZW46IDIwLFxuICAgIH0sXG4gIH1cbn0pO1xuIiwiLyohIFd5c2lCQiB2MS41LjEgMjAxNC0wMy0yNlxuICAgIEF1dGhvcjogVmFkaW0gRG9icm9za29rXG4gKi9cbmlmICh0eXBlb2YgKFdCQkxBTkcpID09IFwidW5kZWZpbmVkXCIpIHtcbiAgV0JCTEFORyA9IHt9O1xufVxuV0JCTEFOR1snZW4nXSA9IENVUkxBTkcgPSB7XG4gIGJvbGQ6IFwiQm9sZFwiLFxuICBpdGFsaWM6IFwiSXRhbGljXCIsXG4gIHVuZGVybGluZTogXCJVbmRlcmxpbmVcIixcbiAgc3RyaWtlOiBcIlN0cmlrZVwiLFxuICBsaW5rOiBcIkxpbmtcIixcbiAgaW1nOiBcIkluc2VydCBpbWFnZVwiLFxuICBzdXA6IFwiU3VwZXJzY3JpcHRcIixcbiAgc3ViOiBcIlN1YnNjcmlwdFwiLFxuICBqdXN0aWZ5bGVmdDogXCJBbGlnbiBsZWZ0XCIsXG4gIGp1c3RpZnljZW50ZXI6IFwiQWxpZ24gY2VudGVyXCIsXG4gIGp1c3RpZnlyaWdodDogXCJBbGlnbiByaWdodFwiLFxuICB0YWJsZTogXCJJbnNlcnQgdGFibGVcIixcbiAgYnVsbGlzdDogXCLigKIgVW5vcmRlcmVkIGxpc3RcIixcbiAgbnVtbGlzdDogXCIxLiBPcmRlcmVkIGxpc3RcIixcbiAgcXVvdGU6IFwiUXVvdGVcIixcbiAgb2ZmdG9wOiBcIk9mZnRvcFwiLFxuICBjb2RlOiBcIkNvZGVcIixcbiAgc3BvaWxlcjogXCJTcG9pbGVyXCIsXG4gIGZvbnRjb2xvcjogXCJGb250IGNvbG9yXCIsXG4gIGZvbnRzaXplOiBcIkZvbnQgc2l6ZVwiLFxuICBmb250ZmFtaWx5OiBcIkZvbnQgZmFtaWx5XCIsXG4gIGZzX3ZlcnlzbWFsbDogXCJWZXJ5IHNtYWxsXCIsXG4gIGZzX3NtYWxsOiBcIlNtYWxsXCIsXG4gIGZzX25vcm1hbDogXCJOb3JtYWxcIixcbiAgZnNfYmlnOiBcIkJpZ1wiLFxuICBmc192ZXJ5YmlnOiBcIlZlcnkgYmlnXCIsXG4gIHNtaWxlYm94OiBcIkluc2VydCBlbW90aWNvblwiLFxuICB2aWRlbzogXCJJbnNlcnQgWW91VHViZVwiLFxuICByZW1vdmVGb3JtYXQ6IFwiUmVtb3ZlIEZvcm1hdFwiLFxuXG4gIG1vZGFsX2xpbmtfdGl0bGU6IFwiSW5zZXJ0IGxpbmtcIixcbiAgbW9kYWxfbGlua190ZXh0OiBcIkRpc3BsYXkgdGV4dFwiLFxuICBtb2RhbF9saW5rX3VybDogXCJVUkxcIixcbiAgbW9kYWxfZW1haWxfdGV4dDogXCJEaXNwbGF5IGVtYWlsXCIsXG4gIG1vZGFsX2VtYWlsX3VybDogXCJFbWFpbFwiLFxuICBtb2RhbF9saW5rX3RhYjE6IFwiSW5zZXJ0IFVSTFwiLFxuXG4gIG1vZGFsX2ltZ190aXRsZTogXCJJbnNlcnQgaW1hZ2VcIixcbiAgbW9kYWxfaW1nX3RhYjE6IFwiSW5zZXJ0IFVSTFwiLFxuICBtb2RhbF9pbWdfdGFiMjogXCJVcGxvYWQgaW1hZ2VcIixcbiAgbW9kYWxfaW1nc3JjX3RleHQ6IFwiRW50ZXIgaW1hZ2UgVVJMXCIsXG4gIG1vZGFsX2ltZ19idG46IFwiQ2hvb3NlIGZpbGVcIixcbiAgYWRkX2F0dGFjaDogXCJBZGQgQXR0YWNobWVudFwiLFxuXG4gIG1vZGFsX3ZpZGVvX3RleHQ6IFwiRW50ZXIgdGhlIFVSTCBvZiB0aGUgdmlkZW9cIixcblxuICBjbG9zZTogXCJDbG9zZVwiLFxuICBzYXZlOiBcIlNhdmVcIixcbiAgY2FuY2VsOiBcIkNhbmNlbFwiLFxuICByZW1vdmU6IFwiRGVsZXRlXCIsXG5cbiAgdmFsaWRhdGlvbl9lcnI6IFwiVGhlIGVudGVyZWQgZGF0YSBpcyBpbnZhbGlkXCIsXG4gIGVycm9yX29udXBsb2FkOiBcIkVycm9yIGR1cmluZyBmaWxlIHVwbG9hZFwiLFxuXG4gIGZpbGV1cGxvYWRfdGV4dDE6IFwiRHJvcCBmaWxlIGhlcmVcIixcbiAgZmlsZXVwbG9hZF90ZXh0MjogXCJvclwiLFxuXG4gIGxvYWRpbmc6IFwiTG9hZGluZ1wiLFxuICBhdXRvOiBcIkF1dG9cIixcbiAgdmlld3M6IFwiVmlld3NcIixcbiAgZG93bmxvYWRzOiBcIkRvd25sb2Fkc1wiLFxuXG4gIC8vc21pbGVzXG4gIHNtMTogXCJTbWlsZVwiLFxuICBzbTI6IFwiTGF1Z2h0ZXJcIixcbiAgc20zOiBcIldpbmtcIixcbiAgc200OiBcIlRoYW5rIHlvdVwiLFxuICBzbTU6IFwiU2NvbGRcIixcbiAgc202OiBcIlNob2NrXCIsXG4gIHNtNzogXCJBbmdyeVwiLFxuICBzbTg6IFwiUGFpblwiLFxuICBzbTk6IFwiU2lja1wiXG59O1xud2JiZGVidWcgPSB0cnVlO1xuKGZ1bmN0aW9uICgkKSB7XG4gICd1c2Ugc3RyaWN0JztcbiAgJC53eXNpYmIgPSBmdW5jdGlvbiAodHh0QXJlYSwgc2V0dGluZ3MpIHtcbiAgICAkKHR4dEFyZWEpLmRhdGEoXCJ3YmJcIiwgdGhpcyk7XG5cbiAgICBpZiAoc2V0dGluZ3MgJiYgc2V0dGluZ3MuZGVmbGFuZyAmJiB0eXBlb2YgKFdCQkxBTkdbc2V0dGluZ3MuZGVmbGFuZ10pICE9IFwidW5kZWZpbmVkXCIpIHtcbiAgICAgIENVUkxBTkcgPSBXQkJMQU5HW3NldHRpbmdzLmRlZmxhbmddO1xuICAgIH1cbiAgICBpZiAoc2V0dGluZ3MgJiYgc2V0dGluZ3MubGFuZyAmJiB0eXBlb2YgKFdCQkxBTkdbc2V0dGluZ3MubGFuZ10pICE9IFwidW5kZWZpbmVkXCIpIHtcbiAgICAgIENVUkxBTkcgPSBXQkJMQU5HW3NldHRpbmdzLmxhbmddO1xuICAgIH1cbiAgICB0aGlzLnR4dEFyZWEgPSB0eHRBcmVhO1xuICAgIHRoaXMuJHR4dEFyZWEgPSAkKHR4dEFyZWEpO1xuICAgIHZhciBpZCA9IHRoaXMuJHR4dEFyZWEuYXR0cihcImlkXCIpIHx8IHRoaXMuc2V0VUlEKHRoaXMudHh0QXJlYSk7XG4gICAgdGhpcy5vcHRpb25zID0ge1xuICAgICAgYmJtb2RlOiBmYWxzZSxcbiAgICAgIG9ubHlCQm1vZGU6IGZhbHNlLFxuICAgICAgdGhlbWVOYW1lOiBcImRlZmF1bHRcIixcbiAgICAgIGJvZHlDbGFzczogXCJcIixcbiAgICAgIGxhbmc6IFwicnVcIixcbiAgICAgIHRhYkluc2VydDogdHJ1ZSxcbi8vXHRcdFx0dG9vbGJhcjpcdFx0XHRmYWxzZSxcbiAgICAgIC8vaW1nIHVwbG9hZCBjb25maWdcbiAgICAgIGltZ3VwbG9hZDogZmFsc2UsXG4gICAgICBpbWdfdXBsb2FkdXJsOiBcIi9pdXBsb2FkLnBocFwiLFxuICAgICAgaW1nX21heHdpZHRoOiA4MDAsXG4gICAgICBpbWdfbWF4aGVpZ2h0OiA4MDAsXG4gICAgICBob3RrZXlzOiB0cnVlLFxuICAgICAgc2hvd0hvdGtleXM6IHRydWUsXG4gICAgICBhdXRvcmVzaXplOiB0cnVlLFxuICAgICAgcmVzaXplX21heGhlaWdodDogODAwLFxuICAgICAgbG9hZFBhZ2VTdHlsZXM6IHRydWUsXG4gICAgICB0cmFjZVRleHRhcmVhOiB0cnVlLFxuLy9cdFx0XHRkaXJlY3Rpb246XHRcdFx0XCJsdHJcIixcbiAgICAgIHNtaWxlQ29udmVyc2lvbjogdHJ1ZSxcblxuICAgICAgLy9FTkQgaW1nIHVwbG9hZCBjb25maWdcbiAgICAgIGJ1dHRvbnM6IFwiYm9sZCxpdGFsaWMsdW5kZXJsaW5lLHN0cmlrZSxzdXAsc3ViLHwsaW1nLHZpZGVvLGxpbmssfCxidWxsaXN0LG51bWxpc3QsfCxmb250Y29sb3IsZm9udHNpemUsZm9udGZhbWlseSx8LGp1c3RpZnlsZWZ0LGp1c3RpZnljZW50ZXIsanVzdGlmeXJpZ2h0LHwscXVvdGUsY29kZSx0YWJsZSxyZW1vdmVGb3JtYXRcIixcbiAgICAgIGFsbEJ1dHRvbnM6IHtcbiAgICAgICAgYm9sZDoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLmJvbGQsXG4gICAgICAgICAgYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb24gdmUtdGxiLWJvbGQxXCI+XFx1RTAxODwvc3Bhbj4nLFxuICAgICAgICAgIGV4Y21kOiAnYm9sZCcsXG4gICAgICAgICAgaG90a2V5OiAnY3RybCtiJyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8Yj57U0VMVEVYVH08L2I+JzogXCJbYl17U0VMVEVYVH1bL2JdXCIsXG4gICAgICAgICAgICAnPHN0cm9uZz57U0VMVEVYVH08L3N0cm9uZz4nOiBcIltiXXtTRUxURVhUfVsvYl1cIlxuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgaXRhbGljOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuaXRhbGljLFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi1pdGFsaWMxXCI+XFx1RTAwMTwvc3Bhbj4nLFxuICAgICAgICAgIGV4Y21kOiAnaXRhbGljJyxcbiAgICAgICAgICBob3RrZXk6ICdjdHJsK2knLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxpPntTRUxURVhUfTwvaT4nOiBcIltpXXtTRUxURVhUfVsvaV1cIixcbiAgICAgICAgICAgICc8ZW0+e1NFTFRFWFR9PC9lbT4nOiBcIltpXXtTRUxURVhUfVsvaV1cIlxuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgdW5kZXJsaW5lOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcudW5kZXJsaW5lLFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi11bmRlcmxpbmUxXCI+XFx1RTAwMjwvc3Bhbj4nLFxuICAgICAgICAgIGV4Y21kOiAndW5kZXJsaW5lJyxcbiAgICAgICAgICBob3RrZXk6ICdjdHJsK3UnLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzx1PntTRUxURVhUfTwvdT4nOiBcIlt1XXtTRUxURVhUfVsvdV1cIlxuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgc3RyaWtlOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuc3RyaWtlLFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIGZpLXN0cm9rZTEgdmUtdGxiLXN0cmlrZTFcIj5cXHVFMDAzPC9zcGFuPicsXG4gICAgICAgICAgZXhjbWQ6ICdzdHJpa2VUaHJvdWdoJyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8c3RyaWtlPntTRUxURVhUfTwvc3RyaWtlPic6IFwiW3Nde1NFTFRFWFR9Wy9zXVwiLFxuICAgICAgICAgICAgJzxzPntTRUxURVhUfTwvcz4nOiBcIltzXXtTRUxURVhUfVsvc11cIlxuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgc3VwOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuc3VwLFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi1zdXAxXCI+XFx1RTAwNTwvc3Bhbj4nLFxuICAgICAgICAgIGV4Y21kOiAnc3VwZXJzY3JpcHQnLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxzdXA+e1NFTFRFWFR9PC9zdXA+JzogXCJbc3VwXXtTRUxURVhUfVsvc3VwXVwiXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBzdWI6IHtcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5zdWIsXG4gICAgICAgICAgYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb24gdmUtdGxiLXN1YjFcIj5cXHVFMDA0PC9zcGFuPicsXG4gICAgICAgICAgZXhjbWQ6ICdzdWJzY3JpcHQnLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxzdWI+e1NFTFRFWFR9PC9zdWI+JzogXCJbc3ViXXtTRUxURVhUfVsvc3ViXVwiXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBsaW5rOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcubGluayxcbiAgICAgICAgICBidXR0b25IVE1MOiAnPHNwYW4gY2xhc3M9XCJmb250aWNvbiB2ZS10bGItbGluazFcIj5cXHVFMDA3PC9zcGFuPicsXG4gICAgICAgICAgaG90a2V5OiAnY3RybCtzaGlmdCsyJyxcbiAgICAgICAgICBtb2RhbDoge1xuICAgICAgICAgICAgdGl0bGU6IENVUkxBTkcubW9kYWxfbGlua190aXRsZSxcbiAgICAgICAgICAgIHdpZHRoOiBcIjUwMHB4XCIsXG4gICAgICAgICAgICB0YWJzOiBbXG4gICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBpbnB1dDogW1xuICAgICAgICAgICAgICAgICAge3BhcmFtOiBcIlNFTFRFWFRcIiwgdGl0bGU6IENVUkxBTkcubW9kYWxfbGlua190ZXh0LCB0eXBlOiBcImRpdlwifSxcbiAgICAgICAgICAgICAgICAgIHtwYXJhbTogXCJVUkxcIiwgdGl0bGU6IENVUkxBTkcubW9kYWxfbGlua191cmwsIHZhbGlkYXRpb246ICdeaHR0cChzKT86Ly8nfVxuICAgICAgICAgICAgICAgIF1cbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgXVxuICAgICAgICAgIH0sXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPGEgaHJlZj1cIntVUkx9XCI+e1NFTFRFWFR9PC9hPic6IFwiW3VybD17VVJMfV17U0VMVEVYVH1bL3VybF1cIixcbiAgICAgICAgICAgICc8YSBocmVmPVwie1VSTH1cIj57VVJMfTwvYT4nOiBcIlt1cmxde1VSTH1bL3VybF1cIlxuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgaW1nOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuaW1nLFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi1pbWcxXCI+XFx1RTAwNjwvc3Bhbj4nLFxuICAgICAgICAgIGhvdGtleTogJ2N0cmwrc2hpZnQrMScsXG4gICAgICAgICAgYWRkV3JhcDogdHJ1ZSxcbiAgICAgICAgICBtb2RhbDoge1xuICAgICAgICAgICAgdGl0bGU6IENVUkxBTkcubW9kYWxfaW1nX3RpdGxlLFxuICAgICAgICAgICAgd2lkdGg6IFwiNjAwcHhcIixcbiAgICAgICAgICAgIHRhYnM6IFtcbiAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLm1vZGFsX2ltZ190YWIxLFxuICAgICAgICAgICAgICAgIGlucHV0OiBbXG4gICAgICAgICAgICAgICAgICB7cGFyYW06IFwiU1JDXCIsIHRpdGxlOiBDVVJMQU5HLm1vZGFsX2ltZ3NyY190ZXh0LCB2YWxpZGF0aW9uOiAnXmh0dHAocyk/Oi8vLio/XFwuKGpwZ3xwbmd8Z2lmfGpwZWcpJCd9XG4gICAgICAgICAgICAgICAgXVxuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICBdLFxuICAgICAgICAgICAgb25Mb2FkOiB0aGlzLmltZ0xvYWRNb2RhbFxuICAgICAgICAgIH0sXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPGltZyBzcmM9XCJ7U1JDfVwiIC8+JzogXCJbaW1nXXtTUkN9Wy9pbWddXCIsXG4gICAgICAgICAgICAnPGltZyBzcmM9XCJ7U1JDfVwiIHdpZHRoPVwie1dJRFRIfVwiIGhlaWdodD1cIntIRUlHSFR9XCIvPic6IFwiW2ltZyB3aWR0aD17V0lEVEh9LGhlaWdodD17SEVJR0hUfV17U1JDfVsvaW1nXVwiXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBidWxsaXN0OiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuYnVsbGlzdCxcbiAgICAgICAgICBidXR0b25IVE1MOiAnPHNwYW4gY2xhc3M9XCJmb250aWNvbiB2ZS10bGItbGlzdDFcIj5cXHVFMDA5PC9zcGFuPicsXG4gICAgICAgICAgZXhjbWQ6ICdpbnNlcnRVbm9yZGVyZWRMaXN0JyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8dWw+e1NFTFRFWFR9PC91bD4nOiBcIltsaXN0XXtTRUxURVhUfVsvbGlzdF1cIixcbiAgICAgICAgICAgICc8bGk+e1NFTFRFWFR9PC9saT4nOiBcIlsqXXtTRUxURVhUfVsvKl1cIlxuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgbnVtbGlzdDoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLm51bWxpc3QsXG4gICAgICAgICAgYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb24gdmUtdGxiLW51bWxpc3QxXCI+XFx1RTAwYTwvc3Bhbj4nLFxuICAgICAgICAgIGV4Y21kOiAnaW5zZXJ0T3JkZXJlZExpc3QnLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxvbD57U0VMVEVYVH08L29sPic6IFwiW2xpc3Q9MV17U0VMVEVYVH1bL2xpc3RdXCIsXG4gICAgICAgICAgICAnPGxpPntTRUxURVhUfTwvbGk+JzogXCJbKl17U0VMVEVYVH1bLypdXCJcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIHF1b3RlOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcucXVvdGUsXG4gICAgICAgICAgYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb24gdmUtdGxiLXF1b3RlMVwiPlxcdUUwMGM8L3NwYW4+JyxcbiAgICAgICAgICBob3RrZXk6ICdjdHJsK3NoaWZ0KzMnLFxuICAgICAgICAgIC8vc3ViSW5zZXJ0OiB0cnVlLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxibG9ja3F1b3RlIGNsYXNzPVwiYmxvY2txdW90ZSBwb3N0LXF1b3RlIHAtMiBiZy1saWdodCBib3JkZXIgcm91bmRlZCBkLWlubGluZS1ibG9ja1wiPntTRUxURVhUfTwvYmxvY2txdW90ZT4nOiBcIltxdW90ZV17U0VMVEVYVH1bL3F1b3RlXVwiXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBjb2RlOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuY29kZSxcbiAgICAgICAgICBidXR0b25UZXh0OiAnW2NvZGVdJyxcbiAgICAgICAgICAvKiBidXR0b25IVE1MOiAnPHNwYW4gY2xhc3M9XCJmb250aWNvblwiPlxcdUUwMGQ8L3NwYW4+JywgKi9cbiAgICAgICAgICBob3RrZXk6ICdjdHJsK3NoaWZ0KzQnLFxuICAgICAgICAgIG9ubHlDbGVhclRleHQ6IHRydWUsXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPGNvZGU+e1NFTFRFWFR9PC9jb2RlPic6IFwiW2NvZGU9cGhwXXtTRUxURVhUfVsvY29kZV1cIlxuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgb2ZmdG9wOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcub2ZmdG9wLFxuICAgICAgICAgIGJ1dHRvblRleHQ6ICdvZmZ0b3AnLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxzcGFuIHN0eWxlPVwiZm9udC1zaXplOjEwcHg7Y29sb3I6I2NjY1wiPntTRUxURVhUfTwvc3Bhbj4nOiBcIltvZmZ0b3Bde1NFTFRFWFR9Wy9vZmZ0b3BdXCJcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIGZvbnRjb2xvcjoge1xuICAgICAgICAgIHR5cGU6IFwiY29sb3JwaWNrZXJcIixcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5mb250Y29sb3IsXG4gICAgICAgICAgZXhjbWQ6IFwiZm9yZUNvbG9yXCIsXG4gICAgICAgICAgdmFsdWVCQm5hbWU6IFwiY29sb3JcIixcbiAgICAgICAgICBzdWJJbnNlcnQ6IHRydWUsXG4gICAgICAgICAgY29sb3JzOiBcIiMwMDAwMDAsIzQ0NDQ0NCwjNjY2NjY2LCM5OTk5OTksI2I2YjZiNiwjY2NjY2NjLCNkOGQ4ZDgsI2VmZWZlZiwjZjRmNGY0LCNmZmZmZmYsLSwgXFxcblx0XHRcdFx0XHRcdFx0ICNmZjAwMDAsIzk4MDAwMCwjZmY3NzAwLCNmZmZmMDAsIzAwZmYwMCwjMDBmZmZmLCMxZTg0Y2MsIzAwMDBmZiwjOTkwMGZmLCNmZjAwZmYsLSwgXFxcblx0XHRcdFx0XHRcdFx0ICNmNGNjY2MsI2RiYjBhNywjZmNlNWNkLCNmZmYyY2MsI2Q5ZWFkMywjZDBlMGUzLCNjOWRhZjgsI2NmZTJmMywjZDlkMmU5LCNlYWQxZGMsIFxcXG5cdFx0XHRcdFx0XHRcdCAjZWE5OTk5LCNkZDdlNmIsI2Y5Y2I5YywjZmZlNTk5LCNiNmQ3YTgsI2EyYzRjOSwjYTRjMmY0LCM5ZmM1ZTgsI2I0YTdkNiwjZDVhNmJkLCBcXFxuXHRcdFx0XHRcdFx0XHQgI2UwNjY2NiwjY2M0MTI1LCNmNmIyNmIsI2ZmZDk2NiwjOTNjNDdkLCM3NmE1YWYsIzZkOWVlYiwjNmZhOGRjLCM4ZTdjYzMsI2MyN2JhMCwgXFxcblx0XHRcdFx0XHRcdFx0ICNjYzAwMDAsI2E2MWMwMCwjZTY5MTM4LCNmMWMyMzIsIzZhYTg0ZiwjNDU4MThlLCMzYzc4ZDgsIzNkODVjNiwjNjc0ZWE3LCNhNjRkNzksIFxcXG5cdFx0XHRcdFx0XHRcdCAjOTAwMDAwLCM4NTIwMEMsI0I0NUYwNiwjQkY5MDAwLCMzODc2MUQsIzEzNEY1QywjMTE1NUNjLCMwQjUzOTQsIzM1MUM3NSwjNzQxQjQ3LCBcXFxuXHRcdFx0XHRcdFx0XHQgIzY2MDAwMCwjNUIwRjAwLCM3ODNGMDQsIzdGNjAwMCwjMjc0RTEzLCMwQzM0M0QsIzFDNDU4NywjMDczNzYzLCMyMDEyNEQsIzRDMTEzMFwiLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxmb250IGNvbG9yPVwie0NPTE9SfVwiPntTRUxURVhUfTwvZm9udD4nOiAnW2NvbG9yPXtDT0xPUn1de1NFTFRFWFR9Wy9jb2xvcl0nXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICB0YWJsZToge1xuICAgICAgICAgIHR5cGU6IFwidGFibGVcIixcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy50YWJsZSxcbiAgICAgICAgICBjb2xzOiAxMCxcbiAgICAgICAgICByb3dzOiAxMCxcbiAgICAgICAgICBjZWxsd2lkdGg6IDIwLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzx0ZD57U0VMVEVYVH08L3RkPic6ICdbdGRde1NFTFRFWFR9Wy90ZF0nLFxuICAgICAgICAgICAgJzx0cj57U0VMVEVYVH08L3RyPic6ICdbdHJde1NFTFRFWFR9Wy90cl0nLFxuICAgICAgICAgICAgJzx0YWJsZSBjbGFzcz1cIndiYi10YWJsZVwiPntTRUxURVhUfTwvdGFibGU+JzogJ1t0YWJsZV17U0VMVEVYVH1bL3RhYmxlXSdcbiAgICAgICAgICB9LFxuICAgICAgICAgIHNraXBSdWxlczogdHJ1ZVxuICAgICAgICB9LFxuICAgICAgICBmb250c2l6ZToge1xuICAgICAgICAgIHR5cGU6ICdzZWxlY3QnLFxuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLmZvbnRzaXplLFxuICAgICAgICAgIG9wdGlvbnM6IFwiZnNfdmVyeXNtYWxsLGZzX3NtYWxsLGZzX25vcm1hbCxmc19iaWcsZnNfdmVyeWJpZ1wiXG4gICAgICAgIH0sXG4gICAgICAgIGZvbnRmYW1pbHk6IHtcbiAgICAgICAgICB0eXBlOiAnc2VsZWN0JyxcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5mb250ZmFtaWx5LFxuICAgICAgICAgIGV4Y21kOiAnZm9udE5hbWUnLFxuICAgICAgICAgIHZhbHVlQkJuYW1lOiBcImZvbnRcIixcbiAgICAgICAgICBvcHRpb25zOiBbXG4gICAgICAgICAgICB7dGl0bGU6IFwiQXJpYWxcIiwgZXh2YWx1ZTogXCJBcmlhbFwifSxcbiAgICAgICAgICAgIHt0aXRsZTogXCJDb21pYyBTYW5zIE1TXCIsIGV4dmFsdWU6IFwiQ29taWMgU2FucyBNU1wifSxcbiAgICAgICAgICAgIHt0aXRsZTogXCJDb3VyaWVyIE5ld1wiLCBleHZhbHVlOiBcIkNvdXJpZXIgTmV3XCJ9LFxuICAgICAgICAgICAge3RpdGxlOiBcIkdlb3JnaWFcIiwgZXh2YWx1ZTogXCJHZW9yZ2lhXCJ9LFxuICAgICAgICAgICAge3RpdGxlOiBcIkx1Y2lkYSBTYW5zIFVuaWNvZGVcIiwgZXh2YWx1ZTogXCJMdWNpZGEgU2FucyBVbmljb2RlXCJ9LFxuICAgICAgICAgICAge3RpdGxlOiBcIlRhaG9tYVwiLCBleHZhbHVlOiBcIlRhaG9tYVwifSxcbiAgICAgICAgICAgIHt0aXRsZTogXCJUaW1lcyBOZXcgUm9tYW5cIiwgZXh2YWx1ZTogXCJUaW1lcyBOZXcgUm9tYW5cIn0sXG4gICAgICAgICAgICB7dGl0bGU6IFwiVHJlYnVjaGV0IE1TXCIsIGV4dmFsdWU6IFwiVHJlYnVjaGV0IE1TXCJ9LFxuICAgICAgICAgICAge3RpdGxlOiBcIlZlcmRhbmFcIiwgZXh2YWx1ZTogXCJWZXJkYW5hXCJ9XG4gICAgICAgICAgXSxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8Zm9udCBmYWNlPVwie0ZPTlR9XCI+e1NFTFRFWFR9PC9mb250Pic6ICdbZm9udD17Rk9OVH1de1NFTFRFWFR9Wy9mb250XSdcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIHNtaWxlYm94OiB7XG4gICAgICAgICAgdHlwZTogJ3NtaWxlYm94JyxcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5zbWlsZWJveCxcbiAgICAgICAgICBidXR0b25IVE1MOiAnPHNwYW4gY2xhc3M9XCJmb250aWNvbiB2ZS10bGItc21pbGVib3gxXCI+XFx1RTAwYjwvc3Bhbj4nXG4gICAgICAgIH0sXG4gICAgICAgIGp1c3RpZnlsZWZ0OiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuanVzdGlmeWxlZnQsXG4gICAgICAgICAgYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb24gdmUtdGxiLXRleHRsZWZ0MVwiPlxcdUUwMTU8L3NwYW4+JyxcbiAgICAgICAgICBncm91cGtleTogJ2FsaWduJyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8cCBzdHlsZT1cInRleHQtYWxpZ246bGVmdFwiPntTRUxURVhUfTwvcD4nOiAnW2xlZnRde1NFTFRFWFR9Wy9sZWZ0XSdcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIGp1c3RpZnlyaWdodDoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLmp1c3RpZnlyaWdodCxcbiAgICAgICAgICBidXR0b25IVE1MOiAnPHNwYW4gY2xhc3M9XCJmb250aWNvbiB2ZS10bGItdGV4dHJpZ2h0MVwiPlxcdUUwMTY8L3NwYW4+JyxcbiAgICAgICAgICBncm91cGtleTogJ2FsaWduJyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8cCBzdHlsZT1cInRleHQtYWxpZ246cmlnaHRcIj57U0VMVEVYVH08L3A+JzogJ1tyaWdodF17U0VMVEVYVH1bL3JpZ2h0XSdcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIGp1c3RpZnljZW50ZXI6IHtcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5qdXN0aWZ5Y2VudGVyLFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi10ZXh0Y2VudGVyMVwiPlxcdUUwMTQ8L3NwYW4+JyxcbiAgICAgICAgICBncm91cGtleTogJ2FsaWduJyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8cCBzdHlsZT1cInRleHQtYWxpZ246Y2VudGVyXCI+e1NFTFRFWFR9PC9wPic6ICdbY2VudGVyXXtTRUxURVhUfVsvY2VudGVyXSdcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIHZpZGVvOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcudmlkZW8sXG4gICAgICAgICAgYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb24gdmUtdGxiLXZpZGVvMVwiPlxcdUUwMDg8L3NwYW4+JyxcbiAgICAgICAgICBtb2RhbDoge1xuICAgICAgICAgICAgdGl0bGU6IENVUkxBTkcudmlkZW8sXG4gICAgICAgICAgICB3aWR0aDogXCI2MDBweFwiLFxuICAgICAgICAgICAgdGFiczogW1xuICAgICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgdGl0bGU6IENVUkxBTkcudmlkZW8sXG4gICAgICAgICAgICAgICAgaW5wdXQ6IFtcbiAgICAgICAgICAgICAgICAgIHtwYXJhbTogXCJTUkNcIiwgdGl0bGU6IENVUkxBTkcubW9kYWxfdmlkZW9fdGV4dH1cbiAgICAgICAgICAgICAgICBdXG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIF0sXG4gICAgICAgICAgICBvblN1Ym1pdDogZnVuY3Rpb24gKGNtZCwgb3B0LCBxdWVyeVN0YXRlKSB7XG4gICAgICAgICAgICAgIHZhciB1cmwgPSB0aGlzLiRtb2RhbC5maW5kKCdpbnB1dFtuYW1lPVwiU1JDXCJdJykudmFsKCk7XG4gICAgICAgICAgICAgIGlmICh1cmwpIHtcbiAgICAgICAgICAgICAgICB1cmwgPSB1cmwucmVwbGFjZSgvXlxccysvLCBcIlwiKS5yZXBsYWNlKC9cXHMrJC8sIFwiXCIpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIHZhciBhO1xuICAgICAgICAgICAgICBpZiAodXJsLmluZGV4T2YoXCJ5b3V0dS5iZVwiKSAhPSAtMSkge1xuICAgICAgICAgICAgICAgIGEgPSB1cmwubWF0Y2goL15odHRwW3NdKjpcXC9cXC95b3V0dVxcLmJlXFwvKFthLXowLTlfLV0rKS9pKTtcbiAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICBhID0gdXJsLm1hdGNoKC9eaHR0cFtzXSo6XFwvXFwvd3d3XFwueW91dHViZVxcLmNvbVxcL3dhdGNoXFw/Lio/dj0oW2EtejAtOV8tXSspL2kpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIGlmIChhICYmIGEubGVuZ3RoID09IDIpIHtcbiAgICAgICAgICAgICAgICB2YXIgY29kZSA9IGFbMV07XG4gICAgICAgICAgICAgICAgdGhpcy5pbnNlcnRBdEN1cnNvcih0aGlzLmdldENvZGVCeUNvbW1hbmQoY21kLCB7c3JjOiBjb2RlfSkpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIHRoaXMuY2xvc2VNb2RhbCgpO1xuICAgICAgICAgICAgICB0aGlzLnVwZGF0ZVVJKCk7XG4gICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9LFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxkaXYgc3R5bGU9XCJtYXgtd2lkdGg6IDYwMHB4XCI+PGRpdiBjbGFzcz1cImVtYmVkLXJlc3BvbnNpdmUgZW1iZWQtcmVzcG9uc2l2ZS0xNmJ5OVwiPjxpZnJhbWUgc3JjPVwiaHR0cDovL3d3dy55b3V0dWJlLmNvbS9lbWJlZC97U1JDfVwiIGZyYW1lYm9yZGVyPVwiMFwiPjwvaWZyYW1lPjwvZGl2PjwvZGl2Pic6ICdbeW91dHViZV1odHRwczovL3d3dy55b3V0dWJlLmNvbS93YXRjaD92PXtTUkN9Wy95b3V0dWJlXSdcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG5cbiAgICAgICAgLy9zZWxlY3Qgb3B0aW9uc1xuICAgICAgICBmc192ZXJ5c21hbGw6IHtcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5mc192ZXJ5c21hbGwsXG4gICAgICAgICAgYnV0dG9uVGV4dDogXCJmczFcIixcbiAgICAgICAgICBleGNtZDogJ2ZvbnRTaXplJyxcbiAgICAgICAgICBleHZhbHVlOiBcIjFcIixcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8Zm9udCBzaXplPVwiMVwiPntTRUxURVhUfTwvZm9udD4nOiAnW3NpemU9NTBde1NFTFRFWFR9Wy9zaXplXSdcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIGZzX3NtYWxsOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuZnNfc21hbGwsXG4gICAgICAgICAgYnV0dG9uVGV4dDogXCJmczJcIixcbiAgICAgICAgICBleGNtZDogJ2ZvbnRTaXplJyxcbiAgICAgICAgICBleHZhbHVlOiBcIjJcIixcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8Zm9udCBzaXplPVwiMlwiPntTRUxURVhUfTwvZm9udD4nOiAnW3NpemU9ODVde1NFTFRFWFR9Wy9zaXplXSdcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIGZzX25vcm1hbDoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLmZzX25vcm1hbCxcbiAgICAgICAgICBidXR0b25UZXh0OiBcImZzM1wiLFxuICAgICAgICAgIGV4Y21kOiAnZm9udFNpemUnLFxuICAgICAgICAgIGV4dmFsdWU6IFwiM1wiLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxmb250IHNpemU9XCIzXCI+e1NFTFRFWFR9PC9mb250Pic6ICdbc2l6ZT0xMDBde1NFTFRFWFR9Wy9zaXplXSdcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIGZzX2JpZzoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLmZzX2JpZyxcbiAgICAgICAgICBidXR0b25UZXh0OiBcImZzNFwiLFxuICAgICAgICAgIGV4Y21kOiAnZm9udFNpemUnLFxuICAgICAgICAgIGV4dmFsdWU6IFwiNFwiLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxmb250IHNpemU9XCI0XCI+e1NFTFRFWFR9PC9mb250Pic6ICdbc2l6ZT0xNTBde1NFTFRFWFR9Wy9zaXplXSdcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIGZzX3ZlcnliaWc6IHtcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5mc192ZXJ5YmlnLFxuICAgICAgICAgIGJ1dHRvblRleHQ6IFwiZnM1XCIsXG4gICAgICAgICAgZXhjbWQ6ICdmb250U2l6ZScsXG4gICAgICAgICAgZXh2YWx1ZTogXCI2XCIsXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPGZvbnQgc2l6ZT1cIjZcIj57U0VMVEVYVH08L2ZvbnQ+JzogJ1tzaXplPTIwMF17U0VMVEVYVH1bL3NpemVdJ1xuICAgICAgICAgIH1cbiAgICAgICAgfSxcblxuICAgICAgICByZW1vdmVmb3JtYXQ6IHtcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5yZW1vdmVGb3JtYXQsXG4gICAgICAgICAgYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb24gdmUtdGxiLXJlbW92ZWZvcm1hdDFcIj5cXHVFMDBmPC9zcGFuPicsXG4gICAgICAgICAgZXhjbWQ6IFwicmVtb3ZlRm9ybWF0XCJcbiAgICAgICAgfVxuICAgICAgfSxcbiAgICAgIHN5c3RyOiB7XG4gICAgICAgICc8YnIvPic6IFwiXFxuXCIsXG4gICAgICAgICc8c3BhbiBjbGFzcz1cIndiYnRhYlwiPntTRUxURVhUfTwvc3Bhbj4nOiAnICAge1NFTFRFWFR9J1xuICAgICAgfSxcbiAgICAgIGN1c3RvbVJ1bGVzOiB7XG4gICAgICAgIHRkOiBbW1wiW3RkXXtTRUxURVhUfVsvdGRdXCIsIHtzZWx0ZXh0OiB7cmd4OiBmYWxzZSwgYXR0cjogZmFsc2UsIHNlbDogZmFsc2V9fV1dLFxuICAgICAgICB0cjogW1tcIlt0cl17U0VMVEVYVH1bL3RyXVwiLCB7c2VsdGV4dDoge3JneDogZmFsc2UsIGF0dHI6IGZhbHNlLCBzZWw6IGZhbHNlfX1dXSxcbiAgICAgICAgdGFibGU6IFtbXCJbdGFibGVde1NFTFRFWFR9Wy90YWJsZV1cIiwge3NlbHRleHQ6IHtyZ3g6IGZhbHNlLCBhdHRyOiBmYWxzZSwgc2VsOiBmYWxzZX19XV1cbiAgICAgICAgLy9ibG9ja3F1b3RlOiBbW1wiICAge1NFTFRFWFR9XCIse3NlbHRleHQ6IHtyZ3g6ZmFsc2UsYXR0cjpmYWxzZSxzZWw6ZmFsc2V9fV1dXG4gICAgICB9LFxuICAgICAgc21pbGVMaXN0OiBbXG4gICAgICAgIC8ve3RpdGxlOkNVUkxBTkcuc20xLCBpbWc6ICc8aW1nIHNyYz1cInt0aGVtZVByZWZpeH17dGhlbWVOYW1lfS9pbWcvc21pbGVzL3NtMS5wbmdcIiBjbGFzcz1cInNtXCI+JywgYmJjb2RlOlwiOilcIn0sXG4gICAgICBdLFxuICAgICAgYXR0cldyYXA6IFsnc3JjJywgJ2NvbG9yJywgJ2hyZWYnXSAvL3VzZSBiZWNvdXNlIEZGIGFuZCBJRSBjaGFuZ2UgdmFsdWVzIGZvciB0aGlzIGF0dHIsIG1vZGlmeSBbYXR0cl0gdG8gX1thdHRyXVxuICAgIH1cblxuICAgIC8vRklYIGZvciBPcGVyYS4gV2FpdCB3aGlsZSBpZnJhbWUgbG9hZGVkXG4gICAgdGhpcy5pbml0ZWQgPSB0aGlzLm9wdGlvbnMub25seUJCbW9kZTtcblxuICAgIC8vaW5pdCBjc3MgcHJlZml4LCBpZiBub3Qgc2V0XG4gICAgaWYgKCF0aGlzLm9wdGlvbnMudGhlbWVQcmVmaXgpIHtcbiAgICAgICQoJ2xpbmsnKS5lYWNoKCQucHJveHkoZnVuY3Rpb24gKGlkeCwgZWwpIHtcbiAgICAgICAgdmFyIHNyaXB0TWF0Y2ggPSAkKGVsKS5nZXQoMCkuaHJlZi5tYXRjaCgvKC4qXFwvKSguKilcXC93YmJ0aGVtZVxcLmNzcy4qJC8pO1xuICAgICAgICBpZiAoc3JpcHRNYXRjaCAhPT0gbnVsbCkge1xuICAgICAgICAgIHRoaXMub3B0aW9ucy50aGVtZU5hbWUgPSBzcmlwdE1hdGNoWzJdO1xuICAgICAgICAgIHRoaXMub3B0aW9ucy50aGVtZVByZWZpeCA9IHNyaXB0TWF0Y2hbMV07XG4gICAgICAgIH1cbiAgICAgIH0sIHRoaXMpKTtcbiAgICB9XG5cbiAgICAvL2NoZWNrIGZvciBwcmVzZXRcbiAgICBpZiAodHlwZW9mIChXQkJQUkVTRVQpICE9IFwidW5kZWZpbmVkXCIpIHtcbiAgICAgIGlmIChXQkJQUkVTRVQuYWxsQnV0dG9ucykge1xuICAgICAgICAvL2NsZWFyIHRyYW5zZm9ybVxuICAgICAgICAkLmVhY2goV0JCUFJFU0VULmFsbEJ1dHRvbnMsICQucHJveHkoZnVuY3Rpb24gKGssIHYpIHtcbiAgICAgICAgICBpZiAodi50cmFuc2Zvcm0gJiYgdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNba10pIHtcbiAgICAgICAgICAgIGRlbGV0ZSB0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1trXS50cmFuc2Zvcm07XG4gICAgICAgICAgfVxuICAgICAgICB9LCB0aGlzKSk7XG4gICAgICB9XG4gICAgICAkLmV4dGVuZCh0cnVlLCB0aGlzLm9wdGlvbnMsIFdCQlBSRVNFVCk7XG4gICAgfVxuXG4gICAgaWYgKHNldHRpbmdzICYmIHNldHRpbmdzLmFsbEJ1dHRvbnMpIHtcbiAgICAgICQuZWFjaChzZXR0aW5ncy5hbGxCdXR0b25zLCAkLnByb3h5KGZ1bmN0aW9uIChrLCB2KSB7XG4gICAgICAgIGlmICh2LnRyYW5zZm9ybSAmJiB0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1trXSkge1xuICAgICAgICAgIGRlbGV0ZSB0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1trXS50cmFuc2Zvcm07XG4gICAgICAgIH1cbiAgICAgIH0sIHRoaXMpKTtcbiAgICB9XG4gICAgJC5leHRlbmQodHJ1ZSwgdGhpcy5vcHRpb25zLCBzZXR0aW5ncyk7XG4gICAgdGhpcy5pbml0KCk7XG4gIH1cblxuICAkLnd5c2liYi5wcm90b3R5cGUgPSB7XG4gICAgbGFzdGlkOiAxLFxuICAgIGluaXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgICQubG9nKFwiSW5pdFwiLCB0aGlzKTtcbiAgICAgIC8vY2hlY2sgZm9yIG1vYmlsZVxuICAgICAgdGhpcy5pc01vYmlsZSA9IGZ1bmN0aW9uIChhKSB7XG4gICAgICAgICgvYW5kcm9pZHxhdmFudGdvfGJhZGFcXC98YmxhY2tiZXJyeXxibGF6ZXJ8Y29tcGFsfGVsYWluZXxmZW5uZWN8aGlwdG9wfGllbW9iaWxlfGlwKGhvbmV8b2QpfGlyaXN8a2luZGxlfGxnZSB8bWFlbW98bWVlZ28uK21vYmlsZXxtaWRwfG1tcHxuZXRmcm9udHxvcGVyYSBtKG9ifGluKWl8cGFsbSggb3MpP3xwaG9uZXxwKGl4aXxyZSlcXC98cGx1Y2tlcnxwb2NrZXR8cHNwfHNlcmllcyg0fDYpMHxzeW1iaWFufHRyZW98dXBcXC4oYnJvd3NlcnxsaW5rKXx2b2RhZm9uZXx3YXB8d2luZG93cyAoY2V8cGhvbmUpfHhkYXx4aWluby9pLnRlc3QoYSkpXG4gICAgICB9KG5hdmlnYXRvci51c2VyQWdlbnQgfHwgbmF2aWdhdG9yLnZlbmRvciB8fCB3aW5kb3cub3BlcmEpO1xuXG4gICAgICAvL3VzZSBiYm1vZGUgb24gbW9iaWxlIGRldmljZXNcbiAgICAgIC8vdGhpcy5pc01vYmlsZSA9IHRydWU7IC8vVEVNUFxuICAgICAgaWYgKHRoaXMub3B0aW9ucy5vbmx5QkJtb2RlID09PSB0cnVlKSB7XG4gICAgICAgIHRoaXMub3B0aW9ucy5iYm1vZGUgPSB0cnVlO1xuICAgICAgfVxuICAgICAgLy9jcmVhdGUgYXJyYXkgb2YgY29udHJvbHMsIGZvciBxdWVyeVN0YXRlXG4gICAgICB0aGlzLmNvbnRyb2xsZXJzID0gW107XG5cbiAgICAgIC8vY29udmVydCBidXR0b24gc3RyaW5nIHRvIGFycmF5XG4gICAgICB0aGlzLm9wdGlvbnMuYnV0dG9ucyA9IHRoaXMub3B0aW9ucy5idXR0b25zLnRvTG93ZXJDYXNlKCk7XG4gICAgICB0aGlzLm9wdGlvbnMuYnV0dG9ucyA9IHRoaXMub3B0aW9ucy5idXR0b25zLnNwbGl0KFwiLFwiKTtcblxuICAgICAgLy9pbml0IHN5c3RlbSB0cmFuc2Zvcm1zXG4gICAgICB0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tcIl9zeXN0clwiXSA9IHt9O1xuICAgICAgdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbXCJfc3lzdHJcIl1bXCJ0cmFuc2Zvcm1cIl0gPSB0aGlzLm9wdGlvbnMuc3lzdHI7XG5cbiAgICAgIHRoaXMuc21pbGVGaW5kKCk7XG4gICAgICB0aGlzLmluaXRUcmFuc2Zvcm1zKCk7XG4gICAgICB0aGlzLmJ1aWxkKCk7XG4gICAgICB0aGlzLmluaXRNb2RhbCgpO1xuICAgICAgaWYgKHRoaXMub3B0aW9ucy5ob3RrZXlzID09PSB0cnVlICYmICF0aGlzLmlzTW9iaWxlKSB7XG4gICAgICAgIHRoaXMuaW5pdEhvdGtleXMoKTtcbiAgICAgIH1cblxuICAgICAgLy9zb3J0IHNtaWxlc1xuICAgICAgaWYgKHRoaXMub3B0aW9ucy5zbWlsZUxpc3QgJiYgdGhpcy5vcHRpb25zLnNtaWxlTGlzdC5sZW5ndGggPiAwKSB7XG4gICAgICAgIHRoaXMub3B0aW9ucy5zbWlsZUxpc3Quc29ydChmdW5jdGlvbiAoYSwgYikge1xuICAgICAgICAgIHJldHVybiAoYi5iYmNvZGUubGVuZ3RoIC0gYS5iYmNvZGUubGVuZ3RoKTtcbiAgICAgICAgfSlcbiAgICAgIH1cblxuICAgICAgdGhpcy4kdHh0QXJlYS5wYXJlbnRzKFwiZm9ybVwiKS5iaW5kKFwic3VibWl0XCIsICQucHJveHkoZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLnN5bmMoKTtcbiAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICB9LCB0aGlzKSk7XG5cblxuICAgICAgLy9waHBiYjJcbiAgICAgIHRoaXMuJHR4dEFyZWEucGFyZW50cyhcImZvcm1cIikuZmluZChcImlucHV0W2lkKj0ncHJldmlldyddLGlucHV0W2lkKj0nc3VibWl0J10saW5wdXRbY2xhc3MqPSdwcmV2aWV3J10saW5wdXRbY2xhc3MqPSdzdWJtaXQnXSxpbnB1dFtuYW1lKj0ncHJldmlldyddLGlucHV0W25hbWUqPSdzdWJtaXQnXVwiKS5iaW5kKFwibW91c2Vkb3duXCIsICQucHJveHkoZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLnN5bmMoKTtcbiAgICAgICAgc2V0VGltZW91dCgkLnByb3h5KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICBpZiAodGhpcy5vcHRpb25zLmJibW9kZSA9PT0gZmFsc2UpIHtcbiAgICAgICAgICAgIHRoaXMuJHR4dEFyZWEucmVtb3ZlQXR0cihcIndiYnN5bmNcIikudmFsKFwiXCIpO1xuICAgICAgICAgIH1cbiAgICAgICAgfSwgdGhpcyksIDEwMDApO1xuICAgICAgfSwgdGhpcykpO1xuICAgICAgLy9lbmQgcGhwYmIyXG5cbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuaW5pdENhbGxiYWNrKSB7XG4gICAgICAgIHRoaXMub3B0aW9ucy5pbml0Q2FsbGJhY2suY2FsbCh0aGlzKTtcbiAgICAgIH1cblxuICAgICAgJC5sb2codGhpcyk7XG5cbiAgICB9LFxuICAgIGluaXRUcmFuc2Zvcm1zOiBmdW5jdGlvbiAoKSB7XG4gICAgICAkLmxvZyhcIkNyZWF0ZSBydWxlcyBmb3IgdHJhbnNmb3JtIEhUTUw9PkJCXCIpO1xuICAgICAgdmFyIG8gPSB0aGlzLm9wdGlvbnM7XG4gICAgICAvL25lZWQgdG8gY2hlY2sgZm9yIGFjdGl2ZSBidXR0b25zXG4gICAgICBpZiAoIW8ucnVsZXMpIHtcbiAgICAgICAgby5ydWxlcyA9IHt9O1xuICAgICAgfVxuICAgICAgaWYgKCFvLmdyb3Vwcykge1xuICAgICAgICBvLmdyb3VwcyA9IHt9O1xuICAgICAgfSAvL3VzZSBmb3IgZ3JvdXBrZXksIEZvciBleGFtcGxlOiBqdXN0aWZ5bGVmdCxqdXN0aWZ5cmlnaHQsanVzdGlmeWNlbnRlci4gSXQgaXMgbXVzdCByZXBsYWNlIGVhY2ggb3RoZXIuXG4gICAgICB2YXIgYnRubGlzdCA9IG8uYnV0dG9ucy5zbGljZSgpO1xuXG4gICAgICAvL2FkZCBzeXN0ZW0gdHJhbnNmb3JtXG4gICAgICBidG5saXN0LnB1c2goXCJfc3lzdHJcIik7XG4gICAgICBmb3IgKHZhciBiaWR4ID0gMDsgYmlkeCA8IGJ0bmxpc3QubGVuZ3RoOyBiaWR4KyspIHtcbiAgICAgICAgdmFyIG9iID0gby5hbGxCdXR0b25zW2J0bmxpc3RbYmlkeF1dO1xuICAgICAgICBpZiAoIW9iKSB7XG4gICAgICAgICAgY29udGludWU7XG4gICAgICAgIH1cbiAgICAgICAgb2IuZW4gPSB0cnVlO1xuXG4gICAgICAgIC8vY2hlY2sgZm9yIHNpbXBsZWJiY29kZVxuICAgICAgICBpZiAob2Iuc2ltcGxlYmJjb2RlICYmICQuaXNBcnJheShvYi5zaW1wbGViYmNvZGUpICYmIG9iLnNpbXBsZWJiY29kZS5sZW5ndGggPT0gMikge1xuICAgICAgICAgIG9iLmJiY29kZSA9IG9iLmh0bWwgPSBvYi5zaW1wbGViYmNvZGVbMF0gKyBcIntTRUxURVhUfVwiICsgb2Iuc2ltcGxlYmJjb2RlWzFdO1xuICAgICAgICAgIGlmIChvYi50cmFuc2Zvcm0pIGRlbGV0ZSBvYi50cmFuc2Zvcm07XG4gICAgICAgICAgaWYgKG9iLm1vZGFsKSBkZWxldGUgb2IubW9kYWw7XG4gICAgICAgIH1cblxuICAgICAgICAvL2FkZCB0cmFuc2Zvcm1zIHRvIG9wdGlvbiBsaXN0XG4gICAgICAgIGlmIChvYi50eXBlID09IFwic2VsZWN0XCIgJiYgdHlwZW9mIChvYi5vcHRpb25zKSA9PSBcInN0cmluZ1wiKSB7XG4gICAgICAgICAgdmFyIG9saXN0ID0gb2Iub3B0aW9ucy5zcGxpdChcIixcIik7XG4gICAgICAgICAgJC5lYWNoKG9saXN0LCBmdW5jdGlvbiAoaSwgb3ApIHtcbiAgICAgICAgICAgIGlmICgkLmluQXJyYXkob3AsIGJ0bmxpc3QpID09IC0xKSB7XG4gICAgICAgICAgICAgIGJ0bmxpc3QucHVzaChvcCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKG9iLnRyYW5zZm9ybSAmJiBvYi5za2lwUnVsZXMgIT09IHRydWUpIHtcbiAgICAgICAgICB2YXIgb2J0ciA9ICQuZXh0ZW5kKHt9LCBvYi50cmFuc2Zvcm0pO1xuXG4gICAgICAgICAgLyogaWYgKG9iLmFkZFdyYXApIHtcblx0XHRcdFx0XHRcdC8vYWRkV3JhcFxuXHRcdFx0XHRcdFx0JC5sb2coXCJuZWVkV3JhcFwiKTtcblx0XHRcdFx0XHRcdGZvciAodmFyIGJodG1sIGluIG9idHIpIHtcblx0XHRcdFx0XHRcdFx0dmFyIGJiY29kZSA9IG9iLnRyYW5zZm9ybVtiaHRtbF07XG5cdFx0XHRcdFx0XHRcdHZhciBuZXdodG1sID0gJzxzcGFuIHdiYj1cIicrYnRubGlzdFtiaWR4XSsnXCI+JytiaHRtbCsnPC9zcGFuPic7XG5cdFx0XHRcdFx0XHRcdG9idHJbbmV3aHRtbF0gPSBiYmNvZGU7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fSAqL1xuXG4gICAgICAgICAgZm9yICh2YXIgYmh0bWwgaW4gb2J0cikge1xuICAgICAgICAgICAgdmFyIG9yaWdodG1sID0gYmh0bWw7XG4gICAgICAgICAgICB2YXIgYmJjb2RlID0gb2J0cltiaHRtbF07XG5cbiAgICAgICAgICAgIC8vY3JlYXRlIHJvb3Qgc2VsZWN0b3IgZm9yIGlzQ29udGFpbiBiYm1vZGVcbiAgICAgICAgICAgIGlmICghb2IuYmJTZWxlY3Rvcikge1xuICAgICAgICAgICAgICBvYi5iYlNlbGVjdG9yID0gW107XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoJC5pbkFycmF5KGJiY29kZSwgb2IuYmJTZWxlY3RvcikgPT0gLTEpIHtcbiAgICAgICAgICAgICAgb2IuYmJTZWxlY3Rvci5wdXNoKGJiY29kZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAodGhpcy5vcHRpb25zLm9ubHlCQm1vZGUgPT09IGZhbHNlKSB7XG5cbiAgICAgICAgICAgICAgLy93cmFwIGF0dHJpYnV0ZXNcbiAgICAgICAgICAgICAgYmh0bWwgPSB0aGlzLndyYXBBdHRycyhiaHRtbCk7XG5cblxuICAgICAgICAgICAgICB2YXIgJGJlbCA9ICQoZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnRElWJykpLmFwcGVuZCgkKHRoaXMuZWxGcm9tU3RyaW5nKGJodG1sLCBkb2N1bWVudCkpKTtcbiAgICAgICAgICAgICAgdmFyIHJvb3RTZWxlY3RvciA9IHRoaXMuZmlsdGVyQnlOb2RlKCRiZWwuY2hpbGRyZW4oKSk7XG5cblxuICAgICAgICAgICAgICAvL2NoZWNrIGlmIGN1cnJlbnQgcm9vdFNlbGVjdG9yIGlzIGV4aXN0LCBjcmVhdGUgdW5pcXVlIHNlbGVjdG9yIGZvciBlYWNoIHRyYW5zZm9ybSAoMS4yLjIpXG4gICAgICAgICAgICAgIGlmIChyb290U2VsZWN0b3IgPT0gXCJkaXZcIiB8fCB0eXBlb2YgKG8ucnVsZXNbcm9vdFNlbGVjdG9yXSkgIT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICAgICAgICAgIC8vY3JlYXRlIHVuaXF1ZSBzZWxlY3RvclxuICAgICAgICAgICAgICAgICQubG9nKFwiY3JlYXRlIHVuaXF1ZSBzZWxlY3RvcjogXCIgKyByb290U2VsZWN0b3IpO1xuICAgICAgICAgICAgICAgIHRoaXMuc2V0VUlEKCRiZWwuY2hpbGRyZW4oKSk7XG4gICAgICAgICAgICAgICAgcm9vdFNlbGVjdG9yID0gdGhpcy5maWx0ZXJCeU5vZGUoJGJlbC5jaGlsZHJlbigpKTtcbiAgICAgICAgICAgICAgICAkLmxvZyhcIk5ldyByb290U2VsZWN0b3I6IFwiICsgcm9vdFNlbGVjdG9yKTtcbiAgICAgICAgICAgICAgICAvL3JlcGxhY2UgdHJhbnNmb3JtIHdpdGggdW5pcXVlIHNlbGVjdG9yXG4gICAgICAgICAgICAgICAgdmFyIG5odG1sMiA9ICRiZWwuaHRtbCgpO1xuICAgICAgICAgICAgICAgIG5odG1sMiA9IHRoaXMudW53cmFwQXR0cnMobmh0bWwyKTtcbiAgICAgICAgICAgICAgICB2YXIgb2JodG1sID0gdGhpcy51bndyYXBBdHRycyhiaHRtbCk7XG5cblxuICAgICAgICAgICAgICAgIG9iLnRyYW5zZm9ybVtuaHRtbDJdID0gYmJjb2RlO1xuICAgICAgICAgICAgICAgIGRlbGV0ZSBvYi50cmFuc2Zvcm1bb2JodG1sXTtcblxuICAgICAgICAgICAgICAgIGJodG1sID0gbmh0bWwyO1xuICAgICAgICAgICAgICAgIG9yaWdodG1sID0gbmh0bWwyO1xuICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgLy9jcmVhdGUgcm9vdCBzZWxlY3RvciBmb3IgaXNDb250YWluXG4gICAgICAgICAgICAgIGlmICghb2IuZXhjbWQpIHtcbiAgICAgICAgICAgICAgICBpZiAoIW9iLnJvb3RTZWxlY3Rvcikge1xuICAgICAgICAgICAgICAgICAgb2Iucm9vdFNlbGVjdG9yID0gW107XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIG9iLnJvb3RTZWxlY3Rvci5wdXNoKHJvb3RTZWxlY3Rvcik7XG4gICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAvL2NoZWNrIGZvciBydWxlcyBvbiB0aGlzIHJvb3RTZWxldG9yXG4gICAgICAgICAgICAgIGlmICh0eXBlb2YgKG8ucnVsZXNbcm9vdFNlbGVjdG9yXSkgPT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICAgICAgICAgIG8ucnVsZXNbcm9vdFNlbGVjdG9yXSA9IFtdO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIHZhciBjcnVsZXMgPSB7fTtcblxuICAgICAgICAgICAgICBpZiAoYmh0bWwubWF0Y2goL1xce1xcUys/XFx9LykpIHtcbiAgICAgICAgICAgICAgICAkYmVsLmZpbmQoJyonKS5lYWNoKCQucHJveHkoZnVuY3Rpb24gKGlkeCwgZWwpIHtcbiAgICAgICAgICAgICAgICAgIC8vY2hlY2sgYXR0cmlidXRlc1xuXG4gICAgICAgICAgICAgICAgICB2YXIgYXR0cmlidXRlcyA9IHRoaXMuZ2V0QXR0cmlidXRlTGlzdChlbCk7XG4gICAgICAgICAgICAgICAgICAkLmVhY2goYXR0cmlidXRlcywgJC5wcm94eShmdW5jdGlvbiAoaSwgaXRlbSkge1xuICAgICAgICAgICAgICAgICAgICB2YXIgYXR0ciA9ICQoZWwpLmF0dHIoaXRlbSk7XG4gICAgICAgICAgICAgICAgICAgIGlmIChpdGVtLnN1YnN0cigwLCAxKSA9PSAnXycpIHtcbiAgICAgICAgICAgICAgICAgICAgICBpdGVtID0gaXRlbS5zdWJzdHIoMSk7XG4gICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICB2YXIgciA9IGF0dHIubWF0Y2goL1xce1xcUys/XFx9L2cpO1xuICAgICAgICAgICAgICAgICAgICBpZiAocikge1xuICAgICAgICAgICAgICAgICAgICAgIGZvciAodmFyIGEgPSAwOyBhIDwgci5sZW5ndGg7IGErKykge1xuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHJuYW1lID0gclthXS5zdWJzdHIoMSwgclthXS5sZW5ndGggLSAyKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJuYW1lID0gcm5hbWUucmVwbGFjZSh0aGlzLmdldFZhbGlkYXRpb25SR1gocm5hbWUpLCBcIlwiKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBwID0gdGhpcy5yZWxGaWx0ZXJCeU5vZGUoZWwsIHJvb3RTZWxlY3Rvcik7XG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgcmVnUmVwbCA9IChhdHRyICE9IHJbYV0pID8gdGhpcy5nZXRSZWdleHBSZXBsYWNlKGF0dHIsIHJbYV0pIDogZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICBjcnVsZXNbcm5hbWUudG9Mb3dlckNhc2UoKV0gPSB7c2VsOiAocCkgPyAkLnRyaW0ocCkgOiBmYWxzZSwgYXR0cjogaXRlbSwgcmd4OiByZWdSZXBsfVxuICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAgICAgICAgICAgICAvL2NoZWNrIGZvciB0ZXh0XG4gICAgICAgICAgICAgICAgICB2YXIgc2wgPSBbXTtcbiAgICAgICAgICAgICAgICAgIGlmICghJChlbCkuaXMoXCJpZnJhbWVcIikpIHtcbiAgICAgICAgICAgICAgICAgICAgJChlbCkuY29udGVudHMoKS5maWx0ZXIoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLm5vZGVUeXBlID09PSAzXG4gICAgICAgICAgICAgICAgICAgIH0pLmVhY2goJC5wcm94eShmdW5jdGlvbiAoaSwgcmVsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgdmFyIHR4dCA9IHJlbC50ZXh0Q29udGVudCB8fCByZWwuZGF0YTtcbiAgICAgICAgICAgICAgICAgICAgICBpZiAodHlwZW9mICh0eHQpID09IFwidW5kZWZpbmVkXCIpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICB2YXIgciA9IHR4dC5tYXRjaCgvXFx7XFxTKz9cXH0vZylcbiAgICAgICAgICAgICAgICAgICAgICBpZiAocikge1xuICAgICAgICAgICAgICAgICAgICAgICAgZm9yICh2YXIgYSA9IDA7IGEgPCByLmxlbmd0aDsgYSsrKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBybmFtZSA9IHJbYV0uc3Vic3RyKDEsIHJbYV0ubGVuZ3RoIC0gMik7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIHJuYW1lID0gcm5hbWUucmVwbGFjZSh0aGlzLmdldFZhbGlkYXRpb25SR1gocm5hbWUpLCBcIlwiKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHAgPSB0aGlzLnJlbEZpbHRlckJ5Tm9kZShlbCwgcm9vdFNlbGVjdG9yKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHJlZ1JlcGwgPSAodHh0ICE9IHJbYV0pID8gdGhpcy5nZXRSZWdleHBSZXBsYWNlKHR4dCwgclthXSkgOiBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHNlbCA9IChwKSA/ICQudHJpbShwKSA6IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoJC5pbkFycmF5KHNlbCwgc2wpID4gLTEgfHwgJChyZWwpLnBhcmVudCgpLmNvbnRlbnRzKCkubGVuZ3RoID4gMSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vaGFzIGR1YmxpY2F0ZSBhbmQgbm90IG9uZSBjaGlsZHJlbiwgbmVlZCB3cmFwXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIG5lbCA9ICQoXCI8c3Bhbj5cIikuaHRtbChcIntcIiArIHJuYW1lICsgXCJ9XCIpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRoaXMuc2V0VUlEKG5lbCwgXCJ3YmJcIik7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHN0YXJ0ID0gKHR4dC5pbmRleE9mKHJuYW1lKSArIHJuYW1lLmxlbmd0aCkgKyAxO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBhZnRlcl90eHQgPSB0eHQuc3Vic3RyKHN0YXJ0LCB0eHQubGVuZ3RoIC0gc3RhcnQpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vY3JlYXRlIHdyYXAgZWxlbWVudFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJlbC5kYXRhID0gdHh0LnN1YnN0cigwLCB0eHQuaW5kZXhPZihybmFtZSkgLSAxKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKHJlbCkuYWZ0ZXIodGhpcy5lbEZyb21TdHJpbmcoYWZ0ZXJfdHh0LCBkb2N1bWVudCkpLmFmdGVyKG5lbCk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBzZWwgPSAoKHNlbCkgPyBzZWwgKyBcIiBcIiA6IFwiXCIpICsgdGhpcy5maWx0ZXJCeU5vZGUobmVsKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZWdSZXBsID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgY3J1bGVzW3JuYW1lLnRvTG93ZXJDYXNlKCldID0ge3NlbDogc2VsLCBhdHRyOiBmYWxzZSwgcmd4OiByZWdSZXBsfVxuICAgICAgICAgICAgICAgICAgICAgICAgICBzbFtzbC5sZW5ndGhdID0gc2VsO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgc2wgPSBudWxsO1xuXG5cbiAgICAgICAgICAgICAgICB9LCB0aGlzKSk7XG5cbiAgICAgICAgICAgICAgICB2YXIgbmJodG1sID0gJGJlbC5odG1sKCk7XG4gICAgICAgICAgICAgICAgLy9VbldyYXAgYXR0cmlidXRlc1xuICAgICAgICAgICAgICAgIG5iaHRtbCA9IHRoaXMudW53cmFwQXR0cnMobmJodG1sKTtcbiAgICAgICAgICAgICAgICBpZiAob3JpZ2h0bWwgIT0gbmJodG1sKSB7XG4gICAgICAgICAgICAgICAgICAvL2lmIHdlIG1vZGlmeSBodG1sLCByZXBsYWNlIGl0XG4gICAgICAgICAgICAgICAgICBkZWxldGUgb2IudHJhbnNmb3JtW29yaWdodG1sXTtcbiAgICAgICAgICAgICAgICAgIG9iLnRyYW5zZm9ybVtuYmh0bWxdID0gYmJjb2RlO1xuICAgICAgICAgICAgICAgICAgYmh0bWwgPSBuYmh0bWw7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgby5ydWxlc1tyb290U2VsZWN0b3JdLnB1c2goW2JiY29kZSwgY3J1bGVzXSk7XG5cbiAgICAgICAgICAgICAgLy9jaGVjayBmb3Igb25seUNsZWFyVGV4dFxuICAgICAgICAgICAgICBpZiAob2Iub25seUNsZWFyVGV4dCA9PT0gdHJ1ZSkge1xuICAgICAgICAgICAgICAgIGlmICghdGhpcy5jbGVhcnRleHQpIHtcbiAgICAgICAgICAgICAgICAgIHRoaXMuY2xlYXJ0ZXh0ID0ge307XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIHRoaXMuY2xlYXJ0ZXh0W3Jvb3RTZWxlY3Rvcl0gPSBidG5saXN0W2JpZHhdO1xuICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgLy9jaGVjayBmb3IgZ3JvdXBrZXlcbiAgICAgICAgICAgICAgaWYgKG9iLmdyb3Vwa2V5KSB7XG4gICAgICAgICAgICAgICAgaWYgKCFvLmdyb3Vwc1tvYi5ncm91cGtleV0pIHtcbiAgICAgICAgICAgICAgICAgIG8uZ3JvdXBzW29iLmdyb3Vwa2V5XSA9IFtdXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIG8uZ3JvdXBzW29iLmdyb3Vwa2V5XS5wdXNoKHJvb3RTZWxlY3Rvcik7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9XG5cbiAgICAgICAgICAvL3NvcnQgcm9vdFNlbGVjdG9yXG4gICAgICAgICAgaWYgKG9iLnJvb3RTZWxlY3Rvcikge1xuICAgICAgICAgICAgdGhpcy5zb3J0QXJyYXkob2Iucm9vdFNlbGVjdG9yLCAtMSk7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgdmFyIGh0bWxsID0gJC5tYXAob2IudHJhbnNmb3JtLCBmdW5jdGlvbiAoYmIsIGh0bWwpIHtcbiAgICAgICAgICAgIHJldHVybiBodG1sXG4gICAgICAgICAgfSkuc29ydChmdW5jdGlvbiAoYSwgYikge1xuICAgICAgICAgICAgcmV0dXJuICgoYlswXSB8fCBcIlwiKS5sZW5ndGggLSAoYVswXSB8fCBcIlwiKS5sZW5ndGgpXG4gICAgICAgICAgfSk7XG4gICAgICAgICAgb2IuYmJjb2RlID0gb2IudHJhbnNmb3JtW2h0bWxsWzBdXTtcbiAgICAgICAgICBvYi5odG1sID0gaHRtbGxbMF07XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICAgIDtcblxuICAgICAgdGhpcy5vcHRpb25zLmJ0bmxpc3QgPSBidG5saXN0OyAvL3VzZSBmb3IgdHJhbnNmb3JtcywgYmVjb3VzZSBzZWxlY3QgZWxlbWVudHMgbm90IHByZXNlbnQgaW4gYnV0dG9uc1xuXG4gICAgICAvL2FkZCBjdXN0b20gcnVsZXMsIGZvciB0YWJsZSx0cix0ZCBhbmQgb3RoZXJcbiAgICAgICQuZXh0ZW5kKG8ucnVsZXMsIHRoaXMub3B0aW9ucy5jdXN0b21SdWxlcyk7XG5cbiAgICAgIC8vc21pbGUgcnVsZXNcbiAgICAgIG8uc3J1bGVzID0ge307XG4gICAgICBpZiAodGhpcy5vcHRpb25zLnNtaWxlTGlzdCkge1xuICAgICAgICAkLmVhY2goby5zbWlsZUxpc3QsICQucHJveHkoZnVuY3Rpb24gKGksIHNtKSB7XG4gICAgICAgICAgdmFyICRzbSA9ICQodGhpcy5zdHJmKHNtLmltZywgbykpO1xuICAgICAgICAgIHZhciBmID0gdGhpcy5maWx0ZXJCeU5vZGUoJHNtKTtcbiAgICAgICAgICBvLnNydWxlc1tmXSA9IFtzbS5iYmNvZGUsIHNtLmltZ107XG4gICAgICAgIH0sIHRoaXMpKTtcbiAgICAgIH1cblxuICAgICAgLy9zb3J0IHRyYW5zZm9ybXMgYnkgYmJjb2RlIGxlbmd0aCBkZXNjXG4gICAgICBmb3IgKHZhciByb290c2VsIGluIG8ucnVsZXMpIHtcbiAgICAgICAgdGhpcy5vcHRpb25zLnJ1bGVzW3Jvb3RzZWxdLnNvcnQoZnVuY3Rpb24gKGEsIGIpIHtcbiAgICAgICAgICByZXR1cm4gKGJbMF0ubGVuZ3RoIC0gYVswXS5sZW5ndGgpXG4gICAgICAgIH0pO1xuICAgICAgfVxuXG4gICAgICAvL2NyZWF0ZSByb290c2VsIGxpc3RcbiAgICAgIHRoaXMucnNlbGxpc3QgPSBbXTtcbiAgICAgIGZvciAodmFyIHJvb3RzZWwgaW4gdGhpcy5vcHRpb25zLnJ1bGVzKSB7XG4gICAgICAgIHRoaXMucnNlbGxpc3QucHVzaChyb290c2VsKTtcbiAgICAgIH1cbiAgICAgIHRoaXMuc29ydEFycmF5KHRoaXMucnNlbGxpc3QsIC0xKTtcbiAgICB9LFxuXG4gICAgLy9CVUlMRFxuICAgIGJ1aWxkOiBmdW5jdGlvbiAoKSB7XG4gICAgICAkLmxvZyhcIkJ1aWxkIGVkaXRvclwiKTtcblxuICAgICAgLy90aGlzLiRlZGl0b3IgPSAkKCc8ZGl2IGNsYXNzPVwid3lzaWJiXCI+Jyk7XG4gICAgICB0aGlzLiRlZGl0b3IgPSAkKCc8ZGl2PicpLmFkZENsYXNzKFwid3lzaWJiXCIpO1xuXG4gICAgICBpZiAodGhpcy5pc01vYmlsZSkge1xuICAgICAgICB0aGlzLiRlZGl0b3IuYWRkQ2xhc3MoXCJ3eXNpYmItbW9iaWxlXCIpO1xuICAgICAgfVxuXG4gICAgICAvL3NldCBkaXJlY3Rpb24gaWYgZGVmaW5lZFxuICAgICAgaWYgKHRoaXMub3B0aW9ucy5kaXJlY3Rpb24pIHtcbiAgICAgICAgdGhpcy4kZWRpdG9yLmNzcyhcImRpcmVjdGlvblwiLCB0aGlzLm9wdGlvbnMuZGlyZWN0aW9uKVxuICAgICAgfVxuXG4gICAgICB0aGlzLiRlZGl0b3IuaW5zZXJ0QWZ0ZXIodGhpcy50eHRBcmVhKS5hcHBlbmQodGhpcy50eHRBcmVhKTtcblxuICAgICAgdGhpcy5zdGFydEhlaWdodCA9IHRoaXMuJHR4dEFyZWEub3V0ZXJIZWlnaHQoKTtcbiAgICAgIHRoaXMuJHR4dEFyZWEuYWRkQ2xhc3MoXCJ3eXNpYmItdGV4YXJlYVwiKTtcbiAgICAgIHRoaXMuYnVpbGRUb29sYmFyKCk7XG4gICAgICAvL0J1aWxkIGlmcmFtZSBpZiBuZWVkZWRcbiAgICAgIHRoaXMuJHR4dEFyZWEud3JhcCgnPGRpdiBjbGFzcz1cInd5c2liYi10ZXh0XCI+Jyk7XG5cbiAgICAgIGlmICh0aGlzLm9wdGlvbnMub25seUJCbW9kZSA9PT0gZmFsc2UpIHtcbiAgICAgICAgdmFyIGhlaWdodCA9IHRoaXMub3B0aW9ucy5taW5oZWlnaHQgfHwgdGhpcy4kdHh0QXJlYS5vdXRlckhlaWdodCgpO1xuICAgICAgICB2YXIgbWF4aGVpZ2h0ID0gdGhpcy5vcHRpb25zLnJlc2l6ZV9tYXhoZWlnaHQ7XG4gICAgICAgIHZhciBtaGVpZ2h0ID0gKHRoaXMub3B0aW9ucy5hdXRvcmVzaXplID09PSB0cnVlKSA/IHRoaXMub3B0aW9ucy5yZXNpemVfbWF4aGVpZ2h0IDogaGVpZ2h0O1xuICAgICAgICB0aGlzLiRib2R5ID0gJCh0aGlzLnN0cmYoJzxkaXYgY2xhc3M9XCJ3eXNpYmItdGV4dC1lZGl0b3JcIiBzdHlsZT1cIm1heC1oZWlnaHQ6e21heGhlaWdodH1weDttaW4taGVpZ2h0OntoZWlnaHR9cHhcIj48L2Rpdj4nLCB7bWF4aGVpZ2h0OiBtaGVpZ2h0LCBoZWlnaHQ6IGhlaWdodH0pKS5pbnNlcnRBZnRlcih0aGlzLiR0eHRBcmVhKTtcbiAgICAgICAgdGhpcy5ib2R5ID0gdGhpcy4kYm9keVswXTtcbiAgICAgICAgdGhpcy4kdHh0QXJlYS5oaWRlKCk7XG5cbiAgICAgICAgaWYgKGhlaWdodCA+IDMyKSB7XG4gICAgICAgICAgdGhpcy4kdG9vbGJhci5jc3MoXCJtYXgtaGVpZ2h0XCIsIGhlaWdodCk7XG4gICAgICAgIH1cblxuICAgICAgICAkLmxvZyhcIld5c2lCQiBsb2FkZWRcIik7XG5cbiAgICAgICAgdGhpcy4kYm9keS5hZGRDbGFzcyhcInd5c2liYi1ib2R5XCIpLmFkZENsYXNzKHRoaXMub3B0aW9ucy5ib2R5Q2xhc3MpO1xuXG4gICAgICAgIC8vc2V0IGRpcmVjdGlvbiBpZiBkZWZpbmVkXG4gICAgICAgIGlmICh0aGlzLm9wdGlvbnMuZGlyZWN0aW9uKSB7XG4gICAgICAgICAgdGhpcy4kYm9keS5jc3MoXCJkaXJlY3Rpb25cIiwgdGhpcy5vcHRpb25zLmRpcmVjdGlvbilcbiAgICAgICAgfVxuXG5cbiAgICAgICAgaWYgKCdjb250ZW50RWRpdGFibGUnIGluIHRoaXMuYm9keSkge1xuICAgICAgICAgIHRoaXMuYm9keS5jb250ZW50RWRpdGFibGUgPSB0cnVlO1xuICAgICAgICAgIHRyeSB7XG4gICAgICAgICAgICAvL2ZpeCBmb3IgbWZpcmVmb3hcbiAgICAgICAgICAgIC8vZG9jdW1lbnQuZXhlY0NvbW1hbmQoJ2VuYWJsZU9iamVjdFJlc2l6aW5nJywgZmFsc2UsICdmYWxzZScpOyAvL2Rpc2FibGUgaW1hZ2UgcmVzaXppbmdcbiAgICAgICAgICAgIGRvY3VtZW50LmV4ZWNDb21tYW5kKCdTdHlsZVdpdGhDU1MnLCBmYWxzZSwgZmFsc2UpO1xuICAgICAgICAgICAgLy9kb2N1bWVudC5kZXNpZ25Nb2RlID0gXCJvblwiO1xuICAgICAgICAgICAgdGhpcy4kYm9keS5hcHBlbmQoXCI8c3Bhbj48L3NwYW4+XCIpO1xuICAgICAgICAgIH0gY2F0Y2ggKGUpIHtcbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgLy91c2Ugb25seWJibW9kZVxuICAgICAgICAgIHRoaXMub3B0aW9ucy5vbmx5QkJtb2RlID0gdGhpcy5vcHRpb25zLmJibW9kZSA9IHRydWU7XG4gICAgICAgIH1cblxuICAgICAgICAvL2NoZWNrIGZvciBleGlzdCBjb250ZW50IGluIHRleHRhcmVhXG4gICAgICAgIGlmICh0aGlzLnR4dEFyZWEudmFsdWUubGVuZ3RoID4gMCkge1xuICAgICAgICAgIHRoaXMudHh0QXJlYUluaXRDb250ZW50KCk7XG4gICAgICAgIH1cblxuXG4gICAgICAgIC8vY2xlYXIgaHRtbCBvbiBwYXN0ZSBmcm9tIGV4dGVybmFsIGVkaXRvcnNcbiAgICAgICAgdGhpcy4kYm9keS5iaW5kKCdrZXlkb3duJywgJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICAgIGlmICgoZS53aGljaCA9PSA4NiAmJiAoZS5jdHJsS2V5ID09IHRydWUgfHwgZS5tZXRhS2V5ID09IHRydWUpKSB8fCAoZS53aGljaCA9PSA0NSAmJiAoZS5zaGlmdEtleSA9PSB0cnVlIHx8IGUubWV0YUtleSA9PSB0cnVlKSkpIHtcbiAgICAgICAgICAgIGlmICghdGhpcy4kcGFzdGVCbG9jaykge1xuICAgICAgICAgICAgICB0aGlzLnNhdmVSYW5nZSgpO1xuICAgICAgICAgICAgICB0aGlzLiRwYXN0ZUJsb2NrID0gJCh0aGlzLmVsRnJvbVN0cmluZygnPGRpdiBzdHlsZT1cIm9wYWNpdHk6MDtcIiBjb250ZW50ZWRpdGFibGU9XCJ0cnVlXCI+XFx1RkVGRjwvZGl2PicpKTtcblxuICAgICAgICAgICAgICB0aGlzLiRwYXN0ZUJsb2NrLmFwcGVuZFRvKHRoaXMuYm9keSk7XG4gICAgICAgICAgICAgIC8vaWYgKCEkLnN1cHBvcnQuc2VhcmNoP3R5cGU9Mikge3RoaXMuJHBhc3RlQmxvY2suZm9jdXMoKTt9IC8vSUUgNyw4IEZJWFxuICAgICAgICAgICAgICBzZXRUaW1lb3V0KCQucHJveHkoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICAgdGhpcy5jbGVhclBhc3RlKHRoaXMuJHBhc3RlQmxvY2spO1xuICAgICAgICAgICAgICAgICAgdmFyIHJkYXRhID0gJzxzcGFuPicgKyB0aGlzLiRwYXN0ZUJsb2NrLmh0bWwoKSArICc8L3NwYW4+JztcbiAgICAgICAgICAgICAgICAgIHRoaXMuJGJvZHkuYXR0cihcImNvbnRlbnRFZGl0YWJsZVwiLCBcInRydWVcIik7XG4gICAgICAgICAgICAgICAgICB0aGlzLiRwYXN0ZUJsb2NrLmJsdXIoKS5yZW1vdmUoKTtcbiAgICAgICAgICAgICAgICAgIHRoaXMuYm9keS5mb2N1cygpO1xuXG4gICAgICAgICAgICAgICAgICBpZiAodGhpcy5jbGVhcnRleHQpIHtcbiAgICAgICAgICAgICAgICAgICAgJC5sb2coXCJDaGVjayBpZiBwYXN0ZSB0byBjbGVhclRleHQgQmxvY2tcIik7XG4gICAgICAgICAgICAgICAgICAgIGlmICh0aGlzLmlzSW5DbGVhclRleHRCbG9jaygpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgcmRhdGEgPSB0aGlzLnRvQkIocmRhdGEpLnJlcGxhY2UoL1xcbi9nLCBcIjxici8+XCIpLnJlcGxhY2UoL1xcc3szfS9nLCAnPHNwYW4gY2xhc3M9XCJ3YmJ0YWJcIj48L3NwYW4+Jyk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgIHJkYXRhID0gcmRhdGEucmVwbGFjZSgvXFx0L2csICc8c3BhbiBjbGFzcz1cIndiYnRhYlwiPjwvc3Bhbj4nKTtcbiAgICAgICAgICAgICAgICAgIHRoaXMuc2VsZWN0UmFuZ2UodGhpcy5sYXN0UmFuZ2UpO1xuICAgICAgICAgICAgICAgICAgdGhpcy5pbnNlcnRBdEN1cnNvcihyZGF0YSwgZmFsc2UpO1xuICAgICAgICAgICAgICAgICAgdGhpcy5sYXN0UmFuZ2UgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAgIHRoaXMuJHBhc3RlQmxvY2sgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgLCB0aGlzKSwgMSk7XG4gICAgICAgICAgICAgIHRoaXMuc2VsZWN0Tm9kZSh0aGlzLiRwYXN0ZUJsb2NrWzBdKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgIH1cbiAgICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAgIC8vaW5zZXJ0IEJSIG9uIHByZXNzIGVudGVyXG4gICAgICAgIHRoaXMuJGJvZHkuYmluZCgna2V5ZG93bicsICQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICBpZiAoZS53aGljaCA9PSAxMykge1xuICAgICAgICAgICAgdmFyIGlzTGkgPSB0aGlzLmlzQ29udGFpbih0aGlzLmdldFNlbGVjdE5vZGUoKSwgJ2xpJyk7XG4gICAgICAgICAgICBpZiAoIWlzTGkpIHtcbiAgICAgICAgICAgICAgaWYgKGUucHJldmVudERlZmF1bHQpIHtcbiAgICAgICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgdGhpcy5jaGVja0Zvckxhc3RCUih0aGlzLmdldFNlbGVjdE5vZGUoKSk7XG4gICAgICAgICAgICAgIHRoaXMuaW5zZXJ0QXRDdXJzb3IoJzxici8+JywgZmFsc2UpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cbiAgICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAgIC8vdGFiSW5zZXJ0XG4gICAgICAgIGlmICh0aGlzLm9wdGlvbnMudGFiSW5zZXJ0ID09PSB0cnVlKSB7XG4gICAgICAgICAgdGhpcy4kYm9keS5iaW5kKCdrZXlkb3duJywgJC5wcm94eSh0aGlzLnByZXNzVGFiLCB0aGlzKSk7XG4gICAgICAgIH1cblxuICAgICAgICAvL2FkZCBldmVudCBsaXN0ZW5lcnNcbiAgICAgICAgdGhpcy4kYm9keS5iaW5kKCdtb3VzZXVwIGtleXVwJywgJC5wcm94eSh0aGlzLnVwZGF0ZVVJLCB0aGlzKSk7XG4gICAgICAgIHRoaXMuJGJvZHkuYmluZCgnbW91c2Vkb3duJywgJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICAgIHRoaXMuY2xlYXJMYXN0UmFuZ2UoKTtcbiAgICAgICAgICB0aGlzLmNoZWNrRm9yTGFzdEJSKGUudGFyZ2V0KVxuICAgICAgICB9LCB0aGlzKSk7XG5cbiAgICAgICAgLy90cmFjZSBUZXh0YXJlYVxuICAgICAgICBpZiAodGhpcy5vcHRpb25zLnRyYWNlVGV4dGFyZWEgPT09IHRydWUpIHtcbiAgICAgICAgICAkKGRvY3VtZW50KS5iaW5kKFwibW91c2Vkb3duXCIsICQucHJveHkodGhpcy50cmFjZVRleHRhcmVhRXZlbnQsIHRoaXMpKTtcbiAgICAgICAgICB0aGlzLiR0eHRBcmVhLnZhbChcIlwiKTtcbiAgICAgICAgfVxuXG4gICAgICAgIC8vYXR0YWNoIGhvdGtleXNcbiAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5ob3RrZXlzID09PSB0cnVlKSB7XG4gICAgICAgICAgdGhpcy4kYm9keS5iaW5kKCdrZXlkb3duJywgJC5wcm94eSh0aGlzLnByZXNza2V5LCB0aGlzKSk7XG4gICAgICAgIH1cblxuICAgICAgICAvL3NtaWxlQ29udmVyc2lvblxuICAgICAgICBpZiAodGhpcy5vcHRpb25zLnNtaWxlQ29udmVyc2lvbiA9PT0gdHJ1ZSkge1xuICAgICAgICAgIHRoaXMuJGJvZHkuYmluZCgna2V5dXAnLCAkLnByb3h5KHRoaXMuc21pbGVDb252ZXJzaW9uLCB0aGlzKSk7XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzLmluaXRlZCA9IHRydWU7XG5cbiAgICAgICAgLy9jcmVhdGUgcmVzaXplIGxpbmVzXG4gICAgICAgIGlmICh0aGlzLm9wdGlvbnMuYXV0b3Jlc2l6ZSA9PT0gdHJ1ZSkge1xuICAgICAgICAgIHRoaXMuJGJyZXNpemUgPSAkKHRoaXMuZWxGcm9tU3RyaW5nKCc8ZGl2IGNsYXNzPVwiYm90dG9tLXJlc2l6ZS1saW5lXCI+PC9kaXY+JykpLmFwcGVuZFRvKHRoaXMuJGVkaXRvcilcbiAgICAgICAgICAgIC53ZHJhZyh7XG4gICAgICAgICAgICAgIHNjb3BlOiB0aGlzLFxuICAgICAgICAgICAgICBheGlzWTogdHJ1ZSxcbiAgICAgICAgICAgICAgaGVpZ2h0OiBoZWlnaHRcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG5cbiAgICAgICAgdGhpcy5pbWdMaXN0ZW5lcnMoKTtcbiAgICAgIH1cblxuXG4gICAgICAvL3RoaXMuJGVkaXRvci5hcHBlbmQoJzxzcGFuIGNsYXNzPVwicG93ZXJlZFwiPlBvd2VyZWQgYnkgPGEgaHJlZj1cImh0dHA6Ly93d3cud3lzaWJiLmNvbVwiIHRhcmdldD1cIl9ibGFua1wiPld5c2lCQjxhLz48L3NwYW4+Jyk7XG5cbiAgICAgIC8vYWRkIGV2ZW50IGxpc3RlbmVycyB0byB0ZXh0YXJlYVxuICAgICAgdGhpcy4kdHh0QXJlYS5iaW5kKCdtb3VzZXVwIGtleXVwJywgJC5wcm94eShmdW5jdGlvbiAoKSB7XG4gICAgICAgIGNsZWFyVGltZW91dCh0aGlzLnVpdGltZXIpO1xuICAgICAgICB0aGlzLnVpdGltZXIgPSBzZXRUaW1lb3V0KCQucHJveHkodGhpcy51cGRhdGVVSSwgdGhpcyksIDEwMCk7XG4gICAgICB9LCB0aGlzKSk7XG5cbiAgICAgIC8vYXR0YWNoIGhvdGtleXNcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuaG90a2V5cyA9PT0gdHJ1ZSkge1xuICAgICAgICAkKGRvY3VtZW50KS5iaW5kKCdrZXlkb3duJywgJC5wcm94eSh0aGlzLnByZXNza2V5LCB0aGlzKSk7XG4gICAgICB9XG4gICAgfSxcbiAgICBidWlsZFRvb2xiYXI6IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMudG9vbGJhciA9PT0gZmFsc2UpIHtcbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgfVxuXG4gICAgICAvL3RoaXMuJHRvb2xiYXIgPSAkKCc8ZGl2IGNsYXNzPVwid3lzaWJiLXRvb2xiYXJcIj4nKS5wcmVwZW5kVG8odGhpcy4kZWRpdG9yKTtcbiAgICAgIHRoaXMuJHRvb2xiYXIgPSAkKCc8ZGl2PicpLmFkZENsYXNzKFwid3lzaWJiLXRvb2xiYXJcIikucHJlcGVuZFRvKHRoaXMuJGVkaXRvcik7XG5cbiAgICAgIHZhciAkYnRuQ29udGFpbmVyO1xuICAgICAgJC5lYWNoKHRoaXMub3B0aW9ucy5idXR0b25zLCAkLnByb3h5KGZ1bmN0aW9uIChpLCBibikge1xuICAgICAgICB2YXIgb3B0ID0gdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbYm5dO1xuICAgICAgICBpZiAoaSA9PSAwIHx8IGJuID09IFwifFwiIHx8IGJuID09IFwiLVwiKSB7XG4gICAgICAgICAgaWYgKGJuID09IFwiLVwiKSB7XG4gICAgICAgICAgICB0aGlzLiR0b29sYmFyLmFwcGVuZChcIjxkaXY+XCIpO1xuICAgICAgICAgIH1cbiAgICAgICAgICAkYnRuQ29udGFpbmVyID0gJCgnPGRpdiBjbGFzcz1cInd5c2liYi10b29sYmFyLWNvbnRhaW5lclwiPicpLmFwcGVuZFRvKHRoaXMuJHRvb2xiYXIpO1xuICAgICAgICB9XG4gICAgICAgIGlmIChvcHQpIHtcbiAgICAgICAgICBpZiAob3B0LnR5cGUgPT0gXCJjb2xvcnBpY2tlclwiKSB7XG4gICAgICAgICAgICB0aGlzLmJ1aWxkQ29sb3JwaWNrZXIoJGJ0bkNvbnRhaW5lciwgYm4sIG9wdCk7XG4gICAgICAgICAgfSBlbHNlIGlmIChvcHQudHlwZSA9PSBcInRhYmxlXCIpIHtcbiAgICAgICAgICAgIHRoaXMuYnVpbGRUYWJsZXBpY2tlcigkYnRuQ29udGFpbmVyLCBibiwgb3B0KTtcbiAgICAgICAgICB9IGVsc2UgaWYgKG9wdC50eXBlID09IFwic2VsZWN0XCIpIHtcbiAgICAgICAgICAgIHRoaXMuYnVpbGRTZWxlY3QoJGJ0bkNvbnRhaW5lciwgYm4sIG9wdCk7XG4gICAgICAgICAgfSBlbHNlIGlmIChvcHQudHlwZSA9PSBcInNtaWxlYm94XCIpIHtcbiAgICAgICAgICAgIHRoaXMuYnVpbGRTbWlsZWJveCgkYnRuQ29udGFpbmVyLCBibiwgb3B0KTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5idWlsZEJ1dHRvbigkYnRuQ29udGFpbmVyLCBibiwgb3B0KTtcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgLy9maXggZm9yIGhpZGUgdG9vbHRpcCBvbiBxdWljayBtb3VzZSBvdmVyXG4gICAgICB0aGlzLiR0b29sYmFyLmZpbmQoXCIuYnRuLXRvb2x0aXBcIikuaG92ZXIoZnVuY3Rpb24gKCkge1xuICAgICAgICAkKHRoaXMpLnBhcmVudCgpLmNzcyhcIm92ZXJmbG93XCIsIFwiaGlkZGVuXCIpXG4gICAgICB9LCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICQodGhpcykucGFyZW50KCkuY3NzKFwib3ZlcmZsb3dcIiwgXCJ2aXNpYmxlXCIpXG4gICAgICB9KTtcblxuICAgICAgLy9idWlsZCBiYmNvZGUgc3dpdGNoIGJ1dHRvblxuICAgICAgLy92YXIgJGJic3cgPSAkKCc8ZGl2IGNsYXNzPVwid3lzaWJiLXRvb2xiYXItY29udGFpbmVyIG1vZGVTd2l0Y2hcIj48ZGl2IGNsYXNzPVwid3lzaWJiLXRvb2xiYXItYnRuXCIgdW5zZWxlY3RhYmxlPVwib25cIj48c3BhbiBjbGFzcz1cImJ0bi1pbm5lciB2ZS10bGItYmJjb2RlXCIgdW5zZWxlY3RhYmxlPVwib25cIj48L3NwYW4+PC9kaXY+PC9kaXY+JykuYXBwZW5kVG8odGhpcy4kdG9vbGJhcik7XG4gICAgICB2YXIgJGJic3cgPSAkKGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpKS5hZGRDbGFzcyhcInd5c2liYi10b29sYmFyLWNvbnRhaW5lciBtb2RlU3dpdGNoXCIpLmh0bWwoJzxkaXYgY2xhc3M9XCJ3eXNpYmItdG9vbGJhci1idG4gbXN3aXRjaFwiIHVuc2VsZWN0YWJsZT1cIm9uXCI+PHNwYW4gY2xhc3M9XCJidG4taW5uZXIgbW9kZXN3XCIgdW5zZWxlY3RhYmxlPVwib25cIj5bYmJjb2RlXTwvc3Bhbj48L2Rpdj4nKS5hcHBlbmRUbyh0aGlzLiR0b29sYmFyKTtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuYmJtb2RlID09IHRydWUpIHtcbiAgICAgICAgJGJic3cuY2hpbGRyZW4oXCIud3lzaWJiLXRvb2xiYXItYnRuXCIpLmFkZENsYXNzKFwib25cIik7XG4gICAgICB9XG4gICAgICBpZiAodGhpcy5vcHRpb25zLm9ubHlCQm1vZGUgPT09IGZhbHNlKSB7XG4gICAgICAgICRiYnN3LmNoaWxkcmVuKFwiLnd5c2liYi10b29sYmFyLWJ0blwiKS5jbGljaygkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgJChlLmN1cnJlbnRUYXJnZXQpLnRvZ2dsZUNsYXNzKFwib25cIik7XG4gICAgICAgICAgdGhpcy5tb2RlU3dpdGNoKCk7XG4gICAgICAgIH0sIHRoaXMpKTtcbiAgICAgIH1cbiAgICB9LFxuICAgIGJ1aWxkQnV0dG9uOiBmdW5jdGlvbiAoY29udGFpbmVyLCBibiwgb3B0KSB7XG4gICAgICBpZiAodHlwZW9mIChjb250YWluZXIpICE9IFwib2JqZWN0XCIpIHtcbiAgICAgICAgY29udGFpbmVyID0gdGhpcy4kdG9vbGJhcjtcbiAgICAgIH1cbiAgICAgIHZhciBidG5IVE1MID0gKG9wdC5idXR0b25IVE1MKSA/ICQodGhpcy5zdHJmKG9wdC5idXR0b25IVE1MLCB0aGlzLm9wdGlvbnMpKS5hZGRDbGFzcyhcImJ0bi1pbm5lclwiKSA6IHRoaXMuc3RyZignPHNwYW4gY2xhc3M9XCJidG4taW5uZXIgYnRuLXRleHRcIj57dGV4dH08L3NwYW4+Jywge3RleHQ6IG9wdC5idXR0b25UZXh0LnJlcGxhY2UoLzwvZywgXCImbHQ7XCIpfSk7XG4gICAgICB2YXIgaG90a2V5ID0gKHRoaXMub3B0aW9ucy5ob3RrZXlzID09PSB0cnVlICYmIHRoaXMub3B0aW9ucy5zaG93SG90a2V5cyA9PT0gdHJ1ZSAmJiBvcHQuaG90a2V5KSA/ICgnIDxzcGFuIGNsYXNzPVwidHRob3RrZXlcIj5bJyArIG9wdC5ob3RrZXkgKyAnXTwvc3Bhbj4nKSA6IFwiXCJcbiAgICAgIHZhciAkYnRuID0gJCgnPGRpdiBjbGFzcz1cInd5c2liYi10b29sYmFyLWJ0biB3YmItJyArIGJuICsgJ1wiPicpLmFwcGVuZFRvKGNvbnRhaW5lcikuYXBwZW5kKGJ0bkhUTUwpLmFwcGVuZCh0aGlzLnN0cmYoJzxzcGFuIGNsYXNzPVwiYnRuLXRvb2x0aXBcIj57dGl0bGV9PGlucy8+e2hvdGtleX08L3NwYW4+Jywge3RpdGxlOiBvcHQudGl0bGUsIGhvdGtleTogaG90a2V5fSkpO1xuXG4gICAgICAvL2F0dGFjaCBldmVudHNcbiAgICAgIHRoaXMuY29udHJvbGxlcnMucHVzaCgkYnRuKTtcbiAgICAgICRidG4uYmluZCgncXVlcnlTdGF0ZScsICQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgKHRoaXMucXVlcnlTdGF0ZShibikpID8gJChlLmN1cnJlbnRUYXJnZXQpLmFkZENsYXNzKFwib25cIikgOiAkKGUuY3VycmVudFRhcmdldCkucmVtb3ZlQ2xhc3MoXCJvblwiKTtcbiAgICAgIH0sIHRoaXMpKTtcbiAgICAgICRidG4ubW91c2Vkb3duKCQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICB0aGlzLmV4ZWNDb21tYW5kKGJuLCBvcHQuZXh2YWx1ZSB8fCBmYWxzZSk7XG4gICAgICAgICQoZS5jdXJyZW50VGFyZ2V0KS50cmlnZ2VyKCdxdWVyeVN0YXRlJyk7XG4gICAgICB9LCB0aGlzKSk7XG4gICAgfSxcbiAgICBidWlsZENvbG9ycGlja2VyOiBmdW5jdGlvbiAoY29udGFpbmVyLCBibiwgb3B0KSB7XG4gICAgICB2YXIgJGJ0biA9ICQoJzxkaXYgY2xhc3M9XCJ3eXNpYmItdG9vbGJhci1idG4gd2JiLWRyb3Bkb3duIHdiYi1jcFwiPicpLmFwcGVuZFRvKGNvbnRhaW5lcikuYXBwZW5kKCc8ZGl2IGNsYXNzPVwidmUtdGxiLWNvbG9ycGlja1wiPjxzcGFuIGNsYXNzPVwiZm9udGljb25cIj5cXHVFMDEwPC9zcGFuPjxzcGFuIGNsYXNzPVwiY3AtbGluZVwiPjwvc3Bhbj48L2Rpdj48aW5zIGNsYXNzPVwiZm9udGljb24gYXJcIj5cXHVFMDExPC9pbnM+JykuYXBwZW5kKHRoaXMuc3RyZignPHNwYW4gY2xhc3M9XCJidG4tdG9vbHRpcFwiPnt0aXRsZX08aW5zLz48L3NwYW4+Jywge3RpdGxlOiBvcHQudGl0bGV9KSk7XG4gICAgICB2YXIgJGNwbGluZSA9ICRidG4uZmluZChcIi5jcC1saW5lXCIpO1xuXG4gICAgICB2YXIgJGRyb3BibG9jayA9ICQoJzxkaXYgY2xhc3M9XCJ3YmItbGlzdFwiPicpLmFwcGVuZFRvKCRidG4pO1xuICAgICAgJGRyb3BibG9jay5hcHBlbmQoJzxkaXYgY2xhc3M9XCJuY1wiPicgKyBDVVJMQU5HLmF1dG8gKyAnPC9kaXY+Jyk7XG4gICAgICB2YXIgY29sb3JsaXN0ID0gKG9wdC5jb2xvcnMpID8gb3B0LmNvbG9ycy5zcGxpdChcIixcIikgOiBbXTtcbiAgICAgIGZvciAodmFyIGogPSAwOyBqIDwgY29sb3JsaXN0Lmxlbmd0aDsgaisrKSB7XG4gICAgICAgIGNvbG9ybGlzdFtqXSA9ICQudHJpbShjb2xvcmxpc3Rbal0pO1xuICAgICAgICBpZiAoY29sb3JsaXN0W2pdID09IFwiLVwiKSB7XG4gICAgICAgICAgLy9pbnNlcnQgcGFkZGluZ1xuICAgICAgICAgICRkcm9wYmxvY2suYXBwZW5kKCc8c3BhbiBjbGFzcz1cInBsXCI+PC9zcGFuPicpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICRkcm9wYmxvY2suYXBwZW5kKHRoaXMuc3RyZignPGRpdiBjbGFzcz1cInNjXCIgc3R5bGU9XCJiYWNrZ3JvdW5kOntjb2xvcn1cIiB0aXRsZT1cIntjb2xvcn1cIj48L2Rpdj4nLCB7Y29sb3I6IGNvbG9ybGlzdFtqXX0pKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgICAgdmFyIGJhc2Vjb2xvciA9ICQoZG9jdW1lbnQuYm9keSkuY3NzKFwiY29sb3JcIik7XG4gICAgICAvL2F0dGFjaCBldmVudHNcbiAgICAgIHRoaXMuY29udHJvbGxlcnMucHVzaCgkYnRuKTtcbiAgICAgICRidG4uYmluZCgncXVlcnlTdGF0ZScsICQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgLy9xdWVyeVN0YXRlXG4gICAgICAgICRjcGxpbmUuY3NzKFwiYmFja2dyb3VuZC1jb2xvclwiLCBiYXNlY29sb3IpO1xuICAgICAgICB2YXIgciA9IHRoaXMucXVlcnlTdGF0ZShibiwgdHJ1ZSk7XG4gICAgICAgIGlmIChyKSB7XG4gICAgICAgICAgJGNwbGluZS5jc3MoXCJiYWNrZ3JvdW5kLWNvbG9yXCIsICh0aGlzLm9wdGlvbnMuYmJtb2RlKSA/IHIuY29sb3IgOiByKTtcbiAgICAgICAgICAkYnRuLmZpbmQoXCIudmUtdGxiLWNvbG9ycGljayBzcGFuLmZvbnRpY29uXCIpLmNzcyhcImNvbG9yXCIsICh0aGlzLm9wdGlvbnMuYmJtb2RlKSA/IHIuY29sb3IgOiByKTtcbiAgICAgICAgfVxuICAgICAgfSwgdGhpcykpO1xuICAgICAgJGJ0bi5tb3VzZWRvd24oJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgIHRoaXMuZHJvcGRvd25jbGljayhcIi53YmItY3BcIiwgXCIud2JiLWxpc3RcIiwgZSk7XG4gICAgICB9LCB0aGlzKSk7XG4gICAgICAkYnRuLmZpbmQoXCIuc2NcIikubW91c2Vkb3duKCQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICB0aGlzLnNlbGVjdExhc3RSYW5nZSgpO1xuICAgICAgICB2YXIgYyA9ICQoZS5jdXJyZW50VGFyZ2V0KS5hdHRyKFwidGl0bGVcIik7XG4gICAgICAgIHRoaXMuZXhlY0NvbW1hbmQoYm4sIGMpO1xuICAgICAgICAkYnRuLnRyaWdnZXIoJ3F1ZXJ5U3RhdGUnKTtcbiAgICAgIH0sIHRoaXMpKTtcbiAgICAgICRidG4uZmluZChcIi5uY1wiKS5tb3VzZWRvd24oJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgIHRoaXMuc2VsZWN0TGFzdFJhbmdlKCk7XG4gICAgICAgIHRoaXMuZXhlY0NvbW1hbmQoYm4sIGJhc2Vjb2xvcik7XG4gICAgICAgICRidG4udHJpZ2dlcigncXVlcnlTdGF0ZScpO1xuICAgICAgfSwgdGhpcykpO1xuICAgICAgJGJ0bi5tb3VzZWRvd24oZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgaWYgKGUucHJldmVudERlZmF1bHQpIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgIH0pO1xuICAgIH0sXG4gICAgYnVpbGRUYWJsZXBpY2tlcjogZnVuY3Rpb24gKGNvbnRhaW5lciwgYm4sIG9wdCkge1xuICAgICAgdmFyICRidG4gPSAkKCc8ZGl2IGNsYXNzPVwid3lzaWJiLXRvb2xiYXItYnRuIHdiYi1kcm9wZG93biB3YmItdGJsXCI+JykuYXBwZW5kVG8oY29udGFpbmVyKS5hcHBlbmQoJzxzcGFuIGNsYXNzPVwiYnRuLWlubmVyIGZvbnRpY29uIHZlLXRsYi10YWJsZTFcIj5cXHVFMDBlPC9zcGFuPjxpbnMgY2xhc3M9XCJmb250aWNvbiBhclwiPlxcdUUwMTE8L2lucz4nKS5hcHBlbmQodGhpcy5zdHJmKCc8c3BhbiBjbGFzcz1cImJ0bi10b29sdGlwXCI+e3RpdGxlfTxpbnMvPjwvc3Bhbj4nLCB7dGl0bGU6IG9wdC50aXRsZX0pKTtcblxuICAgICAgdmFyICRsaXN0YmxvY2sgPSAkKCc8ZGl2IGNsYXNzPVwid2JiLWxpc3RcIj4nKS5hcHBlbmRUbygkYnRuKTtcbiAgICAgIHZhciAkZHJvcGJsb2NrID0gJCgnPGRpdj4nKS5jc3Moe1wicG9zaXRpb25cIjogXCJyZWxhdGl2ZVwiLCBcImJveC1zaXppbmdcIjogXCJib3JkZXItYm94XCJ9KS5hcHBlbmRUbygkbGlzdGJsb2NrKTtcbiAgICAgIHZhciByb3dzID0gb3B0LnJvd3MgfHwgMTA7XG4gICAgICB2YXIgY29scyA9IG9wdC5jb2xzIHx8IDEwO1xuICAgICAgdmFyIGFsbGNvdW50ID0gcm93cyAqIGNvbHM7XG4gICAgICAkZHJvcGJsb2NrLmNzcyhcImhlaWdodFwiLCAocm93cyAqIG9wdC5jZWxsd2lkdGggKyAyKSArIFwicHhcIik7XG4gICAgICBmb3IgKHZhciBqID0gMTsgaiA8PSBjb2xzOyBqKyspIHtcbiAgICAgICAgZm9yICh2YXIgaCA9IDE7IGggPD0gcm93czsgaCsrKSB7XG4gICAgICAgICAgLy92YXIgaHRtbCA9IHRoaXMuc3RyZignPGRpdiBjbGFzcz1cInRibC1zZWxcIiBzdHlsZT1cIndpZHRoOnt3aWR0aH1weDtoZWlnaHQ6e2hlaWdodH1weDt6LWluZGV4Ont6aW5kZXh9XCIgdGl0bGU9XCJ7cm93fSx7Y29sfVwiPjwvZGl2Picse3dpZHRoOiAoaipvcHQuY2VsbHdpZHRoKSxoZWlnaHQ6IChoKm9wdC5jZWxsd2lkdGgpLHppbmRleDogLS1hbGxjb3VudCxyb3c6aCxjb2w6an0pO1xuICAgICAgICAgIHZhciBodG1sID0gJzxkaXYgY2xhc3M9XCJ0Ymwtc2VsXCIgc3R5bGU9XCJ3aWR0aDonICsgKGogKiAxMDAgLyBjb2xzKSArICclO2hlaWdodDonICsgKGggKiAxMDAgLyByb3dzKSArICclO3otaW5kZXg6JyArICgtLWFsbGNvdW50KSArICdcIiB0aXRsZT1cIicgKyBoICsgJywnICsgaiArICdcIj48L2Rpdj4nO1xuICAgICAgICAgICRkcm9wYmxvY2suYXBwZW5kKGh0bWwpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgICAvL3RoaXMuZGVidWcoXCJBdHRhY2ggZXZlbnQgb246IHRibC1zZWxcIik7XG4gICAgICAkYnRuLmZpbmQoXCIudGJsLXNlbFwiKS5tb3VzZWRvd24oJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgIHZhciB0ID0gJChlLmN1cnJlbnRUYXJnZXQpLmF0dHIoXCJ0aXRsZVwiKTtcbiAgICAgICAgdmFyIHJjID0gdC5zcGxpdChcIixcIik7XG4gICAgICAgIHZhciBjb2RlID0gKHRoaXMub3B0aW9ucy5iYm1vZGUpID8gJ1t0YWJsZV0nIDogJzx0YWJsZSBjbGFzcz1cIndiYi10YWJsZVwiIGNlbGxzcGFjaW5nPVwiNVwiIGNlbGxwYWRkaW5nPVwiMFwiPic7XG4gICAgICAgIGZvciAodmFyIGkgPSAxOyBpIDw9IHJjWzBdOyBpKyspIHtcbiAgICAgICAgICBjb2RlICs9ICh0aGlzLm9wdGlvbnMuYmJtb2RlKSA/ICcgW3RyXVxcbicgOiAnPHRyPic7XG4gICAgICAgICAgZm9yICh2YXIgaiA9IDE7IGogPD0gcmNbMV07IGorKykge1xuICAgICAgICAgICAgY29kZSArPSAodGhpcy5vcHRpb25zLmJibW9kZSkgPyAnICBbdGRdWy90ZF1cXG4nIDogJzx0ZD5cXHVGRUZGPC90ZD4nO1xuICAgICAgICAgIH1cbiAgICAgICAgICBjb2RlICs9ICh0aGlzLm9wdGlvbnMuYmJtb2RlKSA/ICdbL3RyXVxcbicgOiAnPC90cj4nO1xuICAgICAgICB9XG4gICAgICAgIGNvZGUgKz0gKHRoaXMub3B0aW9ucy5iYm1vZGUpID8gJ1svdGFibGVdJyA6ICc8L3RhYmxlPic7XG4gICAgICAgIHRoaXMuaW5zZXJ0QXRDdXJzb3IoY29kZSk7XG4gICAgICB9LCB0aGlzKSk7XG4gICAgICAvL3RoaXMuZGVidWcoXCJFTkQgQXR0YWNoIGV2ZW50IG9uOiB0Ymwtc2VsXCIpO1xuICAgICAgJGJ0bi5tb3VzZWRvd24oJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgIHRoaXMuZHJvcGRvd25jbGljayhcIi53YmItdGJsXCIsIFwiLndiYi1saXN0XCIsIGUpO1xuICAgICAgfSwgdGhpcykpO1xuXG4gICAgfSxcbiAgICBidWlsZFNlbGVjdDogZnVuY3Rpb24gKGNvbnRhaW5lciwgYm4sIG9wdCkge1xuICAgICAgdmFyICRidG4gPSAkKCc8ZGl2IGNsYXNzPVwid3lzaWJiLXRvb2xiYXItYnRuIHdiYi1zZWxlY3Qgd2JiLScgKyBibiArICdcIj4nKS5hcHBlbmRUbyhjb250YWluZXIpLmFwcGVuZCh0aGlzLnN0cmYoJzxzcGFuIGNsYXNzPVwidmFsXCI+e3RpdGxlfTwvc3Bhbj48aW5zIGNsYXNzPVwiZm9udGljb24gc2FyXCI+XFx1RTAxMjwvaW5zPicsIG9wdCkpLmFwcGVuZCh0aGlzLnN0cmYoJzxzcGFuIGNsYXNzPVwiYnRuLXRvb2x0aXBcIj57dGl0bGV9PGlucy8+PC9zcGFuPicsIHt0aXRsZTogb3B0LnRpdGxlfSkpO1xuICAgICAgdmFyICRzYmxvY2sgPSAkKCc8ZGl2IGNsYXNzPVwid2JiLWxpc3RcIj4nKS5hcHBlbmRUbygkYnRuKTtcbiAgICAgIHZhciAkc3ZhbCA9ICRidG4uZmluZChcInNwYW4udmFsXCIpO1xuXG4gICAgICB2YXIgb2xpc3QgPSAoJC5pc0FycmF5KG9wdC5vcHRpb25zKSkgPyBvcHQub3B0aW9ucyA6IG9wdC5vcHRpb25zLnNwbGl0KFwiLFwiKTtcbiAgICAgIHZhciAkc2VsZWN0Ym94ID0gKHRoaXMuaXNNb2JpbGUpID8gJChcIjxzZWxlY3Q+XCIpLmFkZENsYXNzKFwid2JiLXNlbGVjdGJveFwiKSA6IFwiXCI7XG4gICAgICBmb3IgKHZhciBpID0gMDsgaSA8IG9saXN0Lmxlbmd0aDsgaSsrKSB7XG4gICAgICAgIHZhciBvbmFtZSA9IG9saXN0W2ldO1xuICAgICAgICBpZiAodHlwZW9mIChvbmFtZSkgPT0gXCJzdHJpbmdcIikge1xuICAgICAgICAgIHZhciBvcHRpb24gPSB0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tvbmFtZV07XG4gICAgICAgICAgaWYgKG9wdGlvbikge1xuICAgICAgICAgICAgLy8kLmxvZyhcImNyZWF0ZTogXCIrb25hbWUpO1xuICAgICAgICAgICAgaWYgKG9wdGlvbi5odG1sKSB7XG4gICAgICAgICAgICAgICQoJzxzcGFuPicpLmFkZENsYXNzKFwib3B0aW9uXCIpLmF0dHIoXCJvaWRcIiwgb25hbWUpLmF0dHIoXCJjbWR2YWx1ZVwiLCBvcHRpb24uZXh2YWx1ZSkuYXBwZW5kVG8oJHNibG9jaykuYXBwZW5kKHRoaXMuc3RyZihvcHRpb24uaHRtbCwge3NlbHRleHQ6IG9wdGlvbi50aXRsZX0pKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICRzYmxvY2suYXBwZW5kKHRoaXMuc3RyZignPHNwYW4gY2xhc3M9XCJvcHRpb25cIiBvaWQ9XCInICsgb25hbWUgKyAnXCIgY21kdmFsdWU9XCInICsgb3B0aW9uLmV4dmFsdWUgKyAnXCI+e3RpdGxlfTwvc3Bhbj4nLCBvcHRpb24pKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgLy9TZWxlY3RCb3ggZm9yIG1vYmlsZSBkZXZpY2VzXG4gICAgICAgICAgICBpZiAodGhpcy5pc01vYmlsZSkge1xuICAgICAgICAgICAgICAkc2VsZWN0Ym94LmFwcGVuZCgkKCc8b3B0aW9uPicpLmF0dHIoXCJvaWRcIiwgb25hbWUpLmF0dHIoXCJjbWR2YWx1ZVwiLCBvcHRpb24uZXh2YWx1ZSkuYXBwZW5kKG9wdGlvbi50aXRsZSkpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAvL2J1aWxkIG9wdGlvbiBsaXN0IGZyb20gYXJyYXlcbiAgICAgICAgICB2YXIgcGFyYW1zID0ge1xuICAgICAgICAgICAgc2VsdGV4dDogb25hbWUudGl0bGVcbiAgICAgICAgICB9XG4gICAgICAgICAgcGFyYW1zW29wdC52YWx1ZUJCbmFtZV0gPSBvbmFtZS5leHZhbHVlO1xuICAgICAgICAgICQoJzxzcGFuPicpLmFkZENsYXNzKFwib3B0aW9uXCIpLmF0dHIoXCJvaWRcIiwgYm4pLmF0dHIoXCJjbWR2YWx1ZVwiLCBvbmFtZS5leHZhbHVlKS5hcHBlbmRUbygkc2Jsb2NrKS5hcHBlbmQodGhpcy5zdHJmKG9wdC5odG1sLCBwYXJhbXMpKTtcblxuICAgICAgICAgIGlmICh0aGlzLmlzTW9iaWxlKSB7XG4gICAgICAgICAgICAkc2VsZWN0Ym94LmFwcGVuZCgkKCc8b3B0aW9uPicpLmF0dHIoXCJvaWRcIiwgYm4pLmF0dHIoXCJjbWR2YWx1ZVwiLCBvbmFtZS5leHZhbHVlKS5hcHBlbmQob25hbWUuZXh2YWx1ZSkpXG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9XG4gICAgICAvLyRzYmxvY2suYXBwZW5kKCRzZWxlY3Rib3gpO1xuICAgICAgaWYgKHRoaXMuaXNNb2JpbGUpIHtcbiAgICAgICAgJHNlbGVjdGJveC5hcHBlbmRUbyhjb250YWluZXIpO1xuICAgICAgICB0aGlzLmNvbnRyb2xsZXJzLnB1c2goJHNlbGVjdGJveCk7XG5cbiAgICAgICAgJHNlbGVjdGJveC5iaW5kKCdxdWVyeVN0YXRlJywgJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICAgIC8vcXVlcnlTdGF0ZVxuICAgICAgICAgICRzZWxlY3Rib3guZmluZChcIm9wdGlvblwiKS5lYWNoKCQucHJveHkoZnVuY3Rpb24gKGksIGVsKSB7XG4gICAgICAgICAgICB2YXIgJGVsID0gJChlbCk7XG4gICAgICAgICAgICB2YXIgciA9IHRoaXMucXVlcnlTdGF0ZSgkZWwuYXR0cihcIm9pZFwiKSwgdHJ1ZSk7XG4gICAgICAgICAgICB2YXIgY21kdmFsdWUgPSAkZWwuYXR0cihcImNtZHZhbHVlXCIpO1xuICAgICAgICAgICAgaWYgKChjbWR2YWx1ZSAmJiByID09ICRlbC5hdHRyKFwiY21kdmFsdWVcIikpIHx8ICghY21kdmFsdWUgJiYgcikpIHtcbiAgICAgICAgICAgICAgJGVsLnByb3AoXCJzZWxlY3RlZFwiLCB0cnVlKTtcbiAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAgICRzZWxlY3Rib3guY2hhbmdlKCQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgdmFyICRvID0gJChlLmN1cnJlbnRUYXJnZXQpLmZpbmQoXCI6c2VsZWN0ZWRcIik7XG4gICAgICAgICAgdmFyIG9pZCA9ICRvLmF0dHIoXCJvaWRcIik7XG4gICAgICAgICAgdmFyIGNtZHZhbHVlID0gJG8uYXR0cihcImNtZHZhbHVlXCIpO1xuICAgICAgICAgIHZhciBvcHQgPSB0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tvaWRdO1xuICAgICAgICAgIHRoaXMuZXhlY0NvbW1hbmQob2lkLCBvcHQuZXh2YWx1ZSB8fCBjbWR2YWx1ZSB8fCBmYWxzZSk7XG4gICAgICAgICAgJChlLmN1cnJlbnRUYXJnZXQpLnRyaWdnZXIoJ3F1ZXJ5U3RhdGUnKTtcbiAgICAgICAgfSwgdGhpcykpO1xuXG4gICAgICB9XG4gICAgICB0aGlzLmNvbnRyb2xsZXJzLnB1c2goJGJ0bik7XG4gICAgICAkYnRuLmJpbmQoJ3F1ZXJ5U3RhdGUnLCAkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgIC8vcXVlcnlTdGF0ZVxuICAgICAgICAkc3ZhbC50ZXh0KG9wdC50aXRsZSk7XG4gICAgICAgICRidG4uZmluZChcIi5vcHRpb24uc2VsZWN0ZWRcIikucmVtb3ZlQ2xhc3MoXCJzZWxlY3RlZFwiKTtcbiAgICAgICAgJGJ0bi5maW5kKFwiLm9wdGlvblwiKS5lYWNoKCQucHJveHkoZnVuY3Rpb24gKGksIGVsKSB7XG4gICAgICAgICAgdmFyICRlbCA9ICQoZWwpO1xuICAgICAgICAgIHZhciByID0gdGhpcy5xdWVyeVN0YXRlKCRlbC5hdHRyKFwib2lkXCIpLCB0cnVlKTtcbiAgICAgICAgICB2YXIgY21kdmFsdWUgPSAkZWwuYXR0cihcImNtZHZhbHVlXCIpO1xuICAgICAgICAgIGlmICgoY21kdmFsdWUgJiYgciA9PSAkZWwuYXR0cihcImNtZHZhbHVlXCIpKSB8fCAoIWNtZHZhbHVlICYmIHIpKSB7XG4gICAgICAgICAgICAkc3ZhbC50ZXh0KCRlbC50ZXh0KCkpO1xuICAgICAgICAgICAgJGVsLmFkZENsYXNzKFwic2VsZWN0ZWRcIik7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgfVxuICAgICAgICB9LCB0aGlzKSk7XG4gICAgICB9LCB0aGlzKSk7XG4gICAgICAkYnRuLm1vdXNlZG93bigkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgdGhpcy5kcm9wZG93bmNsaWNrKFwiLndiYi1zZWxlY3RcIiwgXCIud2JiLWxpc3RcIiwgZSk7XG4gICAgICB9LCB0aGlzKSk7XG4gICAgICAkYnRuLmZpbmQoXCIub3B0aW9uXCIpLm1vdXNlZG93bigkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgdmFyIG9pZCA9ICQoZS5jdXJyZW50VGFyZ2V0KS5hdHRyKFwib2lkXCIpO1xuICAgICAgICB2YXIgY21kdmFsdWUgPSAkKGUuY3VycmVudFRhcmdldCkuYXR0cihcImNtZHZhbHVlXCIpO1xuICAgICAgICB2YXIgb3B0ID0gdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbb2lkXTtcbiAgICAgICAgdGhpcy5leGVjQ29tbWFuZChvaWQsIG9wdC5leHZhbHVlIHx8IGNtZHZhbHVlIHx8IGZhbHNlKTtcbiAgICAgICAgJChlLmN1cnJlbnRUYXJnZXQpLnRyaWdnZXIoJ3F1ZXJ5U3RhdGUnKTtcbiAgICAgIH0sIHRoaXMpKTtcbiAgICB9LFxuICAgIGJ1aWxkU21pbGVib3g6IGZ1bmN0aW9uIChjb250YWluZXIsIGJuLCBvcHQpIHtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuc21pbGVMaXN0ICYmIHRoaXMub3B0aW9ucy5zbWlsZUxpc3QubGVuZ3RoID4gMCkge1xuICAgICAgICB2YXIgJGJ0bkhUTUwgPSAkKHRoaXMuc3RyZihvcHQuYnV0dG9uSFRNTCwgb3B0KSkuYWRkQ2xhc3MoXCJidG4taW5uZXJcIik7XG4gICAgICAgIHZhciAkYnRuID0gJCgnPGRpdiBjbGFzcz1cInd5c2liYi10b29sYmFyLWJ0biB3YmItc21pbGVib3ggd2JiLScgKyBibiArICdcIj4nKS5hcHBlbmRUbyhjb250YWluZXIpLmFwcGVuZCgkYnRuSFRNTCkuYXBwZW5kKHRoaXMuc3RyZignPHNwYW4gY2xhc3M9XCJidG4tdG9vbHRpcFwiPnt0aXRsZX08aW5zLz48L3NwYW4+Jywge3RpdGxlOiBvcHQudGl0bGV9KSk7XG4gICAgICAgIHZhciAkc2Jsb2NrID0gJCgnPGRpdiBjbGFzcz1cIndiYi1saXN0XCI+JykuYXBwZW5kVG8oJGJ0bik7XG4gICAgICAgIGlmICgkLmlzQXJyYXkodGhpcy5vcHRpb25zLnNtaWxlTGlzdCkpIHtcbiAgICAgICAgICAkLmVhY2godGhpcy5vcHRpb25zLnNtaWxlTGlzdCwgJC5wcm94eShmdW5jdGlvbiAoaSwgc20pIHtcbiAgICAgICAgICAgICQoJzxzcGFuPicpLmFkZENsYXNzKFwic21pbGVcIikuYXBwZW5kVG8oJHNibG9jaykuYXBwZW5kKCQodGhpcy5zdHJmKHNtLmltZywgdGhpcy5vcHRpb25zKSkuYXR0cihcInRpdGxlXCIsIHNtLnRpdGxlKSk7XG4gICAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICB9XG4gICAgICAgICRidG4ubW91c2Vkb3duKCQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgdGhpcy5kcm9wZG93bmNsaWNrKFwiLndiYi1zbWlsZWJveFwiLCBcIi53YmItbGlzdFwiLCBlKTtcbiAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICAkYnRuLmZpbmQoJy5zbWlsZScpLm1vdXNlZG93bigkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgIC8vdGhpcy5zZWxlY3RMYXN0UmFuZ2UoKTtcbiAgICAgICAgICB0aGlzLmluc2VydEF0Q3Vyc29yKCh0aGlzLm9wdGlvbnMuYmJtb2RlKSA/IHRoaXMudG9CQigkKGUuY3VycmVudFRhcmdldCkuaHRtbCgpKSA6ICQoJChlLmN1cnJlbnRUYXJnZXQpLmh0bWwoKSkpO1xuICAgICAgICB9LCB0aGlzKSlcbiAgICAgIH1cbiAgICB9LFxuICAgIHVwZGF0ZVVJOiBmdW5jdGlvbiAoZSkge1xuICAgICAgaWYgKCFlIHx8ICgoZS53aGljaCA+PSA4ICYmIGUud2hpY2ggPD0gNDYpIHx8IGUud2hpY2ggPiA5MCB8fCBlLnR5cGUgPT0gXCJtb3VzZXVwXCIpKSB7XG4gICAgICAgICQuZWFjaCh0aGlzLmNvbnRyb2xsZXJzLCAkLnByb3h5KGZ1bmN0aW9uIChpLCAkYnRuKSB7XG4gICAgICAgICAgJGJ0bi50cmlnZ2VyKCdxdWVyeVN0YXRlJyk7XG4gICAgICAgIH0sIHRoaXMpKTtcbiAgICAgIH1cblxuICAgICAgLy9jaGVjayBmb3Igb25seUNsZWFyVGV4dFxuICAgICAgdGhpcy5kaXNOb25BY3RpdmVCdXR0b25zKCk7XG5cbiAgICB9LFxuICAgIGluaXRNb2RhbDogZnVuY3Rpb24gKCkge1xuICAgICAgdGhpcy4kbW9kYWwgPSAkKFwiI3diYm1vZGFsXCIpO1xuICAgICAgaWYgKHRoaXMuJG1vZGFsLmxlbmd0aCA9PSAwKSB7XG4gICAgICAgICQubG9nKFwiSW5pdCBtb2RhbFwiKTtcbiAgICAgICAgdGhpcy4kbW9kYWwgPSAkKCc8ZGl2PicpLmF0dHIoXCJpZFwiLCBcIndiYm1vZGFsXCIpLnByZXBlbmRUbyhkb2N1bWVudC5ib2R5KVxuICAgICAgICAgIC5odG1sKCc8ZGl2IGNsYXNzPVwid2JibVwiPjxkaXYgY2xhc3M9XCJ3YmJtLXRpdGxlXCI+PHNwYW4gY2xhc3M9XCJ3YmJtLXRpdGxlLXRleHRcIj48L3NwYW4+PHNwYW4gY2xhc3M9XCJ3YmJjbG9zZVwiIHRpdGxlPVwiJyArIENVUkxBTkcuY2xvc2UgKyAnXCI+w5c8L3NwYW4+PC9kaXY+PGRpdiBjbGFzcz1cIndiYm0tY29udGVudFwiPjwvZGl2PjxkaXYgY2xhc3M9XCJ3YmJtLWJvdHRvbVwiPjxidXR0b24gaWQ9XCJ3YmJtLXN1Ym1pdFwiIGNsYXNzPVwid2JiLWJ1dHRvblwiPicgKyBDVVJMQU5HLnNhdmUgKyAnPC9idXR0b24+PGJ1dHRvbiBpZD1cIndiYm0tY2FuY2VsXCIgY2xhc3M9XCJ3YmItY2FuY2VsLWJ1dHRvblwiPicgKyBDVVJMQU5HLmNhbmNlbCArICc8L2J1dHRvbj48YnV0dG9uIGlkPVwid2JibS1yZW1vdmVcIiBjbGFzcz1cIndiYi1yZW1vdmUtYnV0dG9uXCI+JyArIENVUkxBTkcucmVtb3ZlICsgJzwvYnV0dG9uPjwvZGl2PjwvZGl2PicpLmhpZGUoKTtcblxuICAgICAgICB0aGlzLiRtb2RhbC5maW5kKCcjd2JibS1jYW5jZWwsLndiYmNsb3NlJykuY2xpY2soJC5wcm94eSh0aGlzLmNsb3NlTW9kYWwsIHRoaXMpKTtcbiAgICAgICAgdGhpcy4kbW9kYWwuYmluZCgnY2xpY2snLCAkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgaWYgKCQoZS50YXJnZXQpLnBhcmVudHMoXCIud2JibVwiKS5sZW5ndGggPT0gMCkge1xuICAgICAgICAgICAgdGhpcy5jbG9zZU1vZGFsKCk7XG4gICAgICAgICAgfVxuICAgICAgICB9LCB0aGlzKSk7XG5cbiAgICAgICAgJChkb2N1bWVudCkuYmluZChcImtleWRvd25cIiwgJC5wcm94eSh0aGlzLmVzY01vZGFsLCB0aGlzKSk7IC8vRVNDIGtleSBjbG9zZSBtb2RhbFxuICAgICAgfVxuICAgIH0sXG4gICAgaW5pdEhvdGtleXM6IGZ1bmN0aW9uICgpIHtcbiAgICAgICQubG9nKFwiaW5pdEhvdGtleXNcIik7XG4gICAgICB0aGlzLmhvdGtleXMgPSBbXTtcbiAgICAgIHZhciBrbGlzdCA9IFwiMDEyMzQ1Njc4OSAgICAgICBhYmNkZWZnaGlqa2xtbm9wcXJzdHV2d3h5elwiO1xuICAgICAgJC5lYWNoKHRoaXMub3B0aW9ucy5hbGxCdXR0b25zLCAkLnByb3h5KGZ1bmN0aW9uIChjbWQsIG9wdCkge1xuICAgICAgICBpZiAob3B0LmhvdGtleSkge1xuICAgICAgICAgIHZhciBrZXlzID0gb3B0LmhvdGtleS5zcGxpdChcIitcIik7XG4gICAgICAgICAgaWYgKGtleXMgJiYga2V5cy5sZW5ndGggPj0gMikge1xuICAgICAgICAgICAgdmFyIG1ldGFzdW0gPSAwO1xuICAgICAgICAgICAgdmFyIGtleSA9IGtleXMucG9wKCk7XG4gICAgICAgICAgICAkLmVhY2goa2V5cywgZnVuY3Rpb24gKGksIGspIHtcbiAgICAgICAgICAgICAgc3dpdGNoICgkLnRyaW0oay50b0xvd2VyQ2FzZSgpKSkge1xuICAgICAgICAgICAgICAgIGNhc2UgXCJjdHJsXCI6IHtcbiAgICAgICAgICAgICAgICAgIG1ldGFzdW0gKz0gMTtcbiAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBjYXNlIFwic2hpZnRcIjoge1xuICAgICAgICAgICAgICAgICAgbWV0YXN1bSArPSA0O1xuICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGNhc2UgXCJhbHRcIjoge1xuICAgICAgICAgICAgICAgICAgbWV0YXN1bSArPSA3O1xuICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KVxuICAgICAgICAgICAgLy8kLmxvZyhcIm1ldGFzdW06IFwiK21ldGFzdW0rXCIga2V5OiBcIitrZXkrXCIgY29kZTogXCIrKGtsaXN0LmluZGV4T2Yoa2V5KSs0OCkpO1xuICAgICAgICAgICAgaWYgKG1ldGFzdW0gPiAwKSB7XG4gICAgICAgICAgICAgIGlmICghdGhpcy5ob3RrZXlzW1wibVwiICsgbWV0YXN1bV0pIHtcbiAgICAgICAgICAgICAgICB0aGlzLmhvdGtleXNbXCJtXCIgKyBtZXRhc3VtXSA9IFtdO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIHRoaXMuaG90a2V5c1tcIm1cIiArIG1ldGFzdW1dW1wia1wiICsgKGtsaXN0LmluZGV4T2Yoa2V5KSArIDQ4KV0gPSBjbWQ7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9LCB0aGlzKSlcbiAgICB9LFxuICAgIHByZXNza2V5OiBmdW5jdGlvbiAoZSkge1xuICAgICAgaWYgKGUuY3RybEtleSA9PSB0cnVlIHx8IGUuc2hpZnRLZXkgPT0gdHJ1ZSB8fCBlLmFsdEtleSA9PSB0cnVlKSB7XG4gICAgICAgIHZhciBtZXRhc3VtID0gKChlLmN0cmxLZXkgPT0gdHJ1ZSkgPyAxIDogMCkgKyAoKGUuc2hpZnRLZXkgPT0gdHJ1ZSkgPyA0IDogMCkgKyAoKGUuYWx0S2V5ID09IHRydWUpID8gNyA6IDApO1xuICAgICAgICBpZiAodGhpcy5ob3RrZXlzW1wibVwiICsgbWV0YXN1bV0gJiYgdGhpcy5ob3RrZXlzW1wibVwiICsgbWV0YXN1bV1bXCJrXCIgKyBlLndoaWNoXSkge1xuICAgICAgICAgIHRoaXMuZXhlY0NvbW1hbmQodGhpcy5ob3RrZXlzW1wibVwiICsgbWV0YXN1bV1bXCJrXCIgKyBlLndoaWNoXSwgZmFsc2UpO1xuICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9LFxuXG4gICAgLy9DT2dkZk1NQU5EIEZVTkNUSU9OU1xuICAgIGV4ZWNDb21tYW5kOiBmdW5jdGlvbiAoY29tbWFuZCwgdmFsdWUpIHtcbiAgICAgICQubG9nKFwiZXhlY0NvbW1hbmQ6IFwiICsgY29tbWFuZCk7XG4gICAgICB2YXIgb3B0ID0gdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbY29tbWFuZF07XG4gICAgICBpZiAob3B0LmVuICE9PSB0cnVlKSB7XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgIH1cbiAgICAgIHZhciBxdWVyeVN0YXRlID0gdGhpcy5xdWVyeVN0YXRlKGNvbW1hbmQsIHZhbHVlKTtcblxuICAgICAgLy9jaGVjayBmb3Igb25seUNsZWFyVGV4dFxuICAgICAgdmFyIHNraXBjbWQgPSB0aGlzLmlzSW5DbGVhclRleHRCbG9jaygpO1xuICAgICAgaWYgKHNraXBjbWQgJiYgc2tpcGNtZCAhPSBjb21tYW5kKSB7XG4gICAgICAgIHJldHVybjtcbiAgICAgIH1cblxuXG4gICAgICBpZiAob3B0LmV4Y21kKSB7XG4gICAgICAgIC8vdXNlIE5hdGl2ZUNvbW1hbmRcbiAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5iYm1vZGUpIHtcbiAgICAgICAgICAkLmxvZyhcIk5hdGl2ZSBjb21tYW5kIGluIGJibW9kZTogXCIgKyBjb21tYW5kKTtcbiAgICAgICAgICBpZiAocXVlcnlTdGF0ZSAmJiBvcHQuc3ViSW5zZXJ0ICE9IHRydWUpIHtcbiAgICAgICAgICAgIC8vcmVtb3ZlIGJiY29kZVxuICAgICAgICAgICAgdGhpcy53YmJSZW1vdmVDYWxsYmFjayhjb21tYW5kLCB2YWx1ZSk7XG4gICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIC8vaW5zZXJ0IGJiY29kZVxuICAgICAgICAgICAgdmFyIHYgPSB7fTtcbiAgICAgICAgICAgIGlmIChvcHQudmFsdWVCQm5hbWUgJiYgdmFsdWUpIHtcbiAgICAgICAgICAgICAgdltvcHQudmFsdWVCQm5hbWVdID0gdmFsdWU7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB0aGlzLmluc2VydEF0Q3Vyc29yKHRoaXMuZ2V0QkJDb2RlQnlDb21tYW5kKGNvbW1hbmQsIHYpKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgdGhpcy5leGVjTmF0aXZlQ29tbWFuZChvcHQuZXhjbWQsIHZhbHVlIHx8IGZhbHNlKTtcbiAgICAgICAgfVxuICAgICAgfSBlbHNlIGlmICghb3B0LmNtZCkge1xuICAgICAgICAvL3diYkNvbW1hbmRcbiAgICAgICAgLy90aGlzLndiYkV4ZWNDb21tYW5kKGNvbW1hbmQsdmFsdWUscXVlcnlTdGF0ZSwkLnByb3h5KHRoaXMud2JiSW5zZXJ0Q2FsbGJhY2ssdGhpcyksJC5wcm94eSh0aGlzLndiYlJlbW92ZUNhbGxiYWNrLHRoaXMpKTtcbiAgICAgICAgdGhpcy53YmJFeGVjQ29tbWFuZC5jYWxsKHRoaXMsIGNvbW1hbmQsIHZhbHVlLCBxdWVyeVN0YXRlKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIC8vdXNlciBjdXN0b20gY29tbWFuZFxuICAgICAgICBvcHQuY21kLmNhbGwodGhpcywgY29tbWFuZCwgdmFsdWUsIHF1ZXJ5U3RhdGUpO1xuICAgICAgfVxuICAgICAgdGhpcy51cGRhdGVVSSgpO1xuICAgIH0sXG4gICAgcXVlcnlTdGF0ZTogZnVuY3Rpb24gKGNvbW1hbmQsIHdpdGh2YWx1ZSkge1xuICAgICAgdmFyIG9wdCA9IHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2NvbW1hbmRdO1xuICAgICAgaWYgKG9wdC5lbiAhPT0gdHJ1ZSkge1xuICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICB9XG4gICAgICAvL2lmIChvcHQuc3ViSW5zZXJ0PT09dHJ1ZSAmJiBvcHQudHlwZSE9XCJjb2xvcnBpY2tlclwiKSB7cmV0dXJuIGZhbHNlO31cbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuYmJtb2RlKSB7XG4gICAgICAgIC8vYmJtb2RlXG4gICAgICAgIGlmIChvcHQuYmJTZWxlY3Rvcikge1xuICAgICAgICAgIGZvciAodmFyIGkgPSAwOyBpIDwgb3B0LmJiU2VsZWN0b3IubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgICAgIHZhciBiID0gdGhpcy5pc0JCQ29udGFpbihvcHQuYmJTZWxlY3RvcltpXSk7XG4gICAgICAgICAgICBpZiAoYikge1xuICAgICAgICAgICAgICByZXR1cm4gdGhpcy5nZXRQYXJhbXMoYiwgb3B0LmJiU2VsZWN0b3JbaV0sIGJbMV0pO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBpZiAob3B0LmV4Y21kKSB7XG4gICAgICAgICAgLy9uYXRpdmUgY29tbWFuZFxuICAgICAgICAgIGlmICh3aXRodmFsdWUpIHtcbiAgICAgICAgICAgIHRyeSB7XG4gICAgICAgICAgICAgIC8vRmlyZWZveCBmaXhcbiAgICAgICAgICAgICAgdmFyIHYgPSAoZG9jdW1lbnQucXVlcnlDb21tYW5kVmFsdWUob3B0LmV4Y21kKSArIFwiXCIpLnJlcGxhY2UoL1xcJy9nLCBcIlwiKTtcbiAgICAgICAgICAgICAgaWYgKG9wdC5leGNtZCA9PSBcImZvcmVDb2xvclwiKSB7XG4gICAgICAgICAgICAgICAgdiA9IHRoaXMucmdiVG9IZXgodik7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgLy9yZXR1cm4gKHY9PXZhbHVlKTtcbiAgICAgICAgICAgICAgcmV0dXJuIHY7XG4gICAgICAgICAgICB9IGNhdGNoIChlKSB7XG4gICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdHJ5IHsgLy9GaXJlZm94IGZpeCwgZXhjZXB0aW9uIHdoaWxlIGdldCBxdWVyeVN0YXRlIGZvciBVbm9yZGVyZWRMaXN0XG4gICAgICAgICAgICAgIGlmICgob3B0LmV4Y21kID09IFwiYm9sZFwiIHx8IG9wdC5leGNtZCA9PSBcIml0YWxpY1wiIHx8IG9wdC5leGNtZCA9PSBcInVuZGVybGluZVwiIHx8IG9wdC5leGNtZCA9PSBcInN0cmlrZVRocm91Z2hcIikgJiYgJCh0aGlzLmdldFNlbGVjdE5vZGUoKSkuaXMoXCJpbWdcIikpIHsgLy9GaXgsIHdoZW4gaW1nIHNlbGVjdGVkXG4gICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgICB9IGVsc2UgaWYgKG9wdC5leGNtZCA9PSBcInVuZGVybGluZVwiICYmICQodGhpcy5nZXRTZWxlY3ROb2RlKCkpLmNsb3Nlc3QoXCJhXCIpLmxlbmd0aCA+IDApIHsgLy9maXgsIHdoZW4gbGluayBzZWxlY3RcbiAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgcmV0dXJuIGRvY3VtZW50LnF1ZXJ5Q29tbWFuZFN0YXRlKG9wdC5leGNtZCk7XG4gICAgICAgICAgICB9IGNhdGNoIChlKSB7XG4gICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgLy9jdXN0b20gY29tbWFuZFxuICAgICAgICAgIGlmICgkLmlzQXJyYXkob3B0LnJvb3RTZWxlY3RvcikpIHtcbiAgICAgICAgICAgIGZvciAodmFyIGkgPSAwOyBpIDwgb3B0LnJvb3RTZWxlY3Rvci5sZW5ndGg7IGkrKykge1xuICAgICAgICAgICAgICB2YXIgbiA9IHRoaXMuaXNDb250YWluKHRoaXMuZ2V0U2VsZWN0Tm9kZSgpLCBvcHQucm9vdFNlbGVjdG9yW2ldKTtcbiAgICAgICAgICAgICAgaWYgKG4pIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy5nZXRQYXJhbXMobiwgb3B0LnJvb3RTZWxlY3RvcltpXSk7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9XG4gICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcbiAgICB3YmJFeGVjQ29tbWFuZDogZnVuY3Rpb24gKGNvbW1hbmQsIHZhbHVlLCBxdWVyeVN0YXRlKSB7IC8vZGVmYXVsdCBjb21tYW5kIGZvciBjdXN0b20gYmJjb2Rlc1xuICAgICAgJC5sb2coXCJ3YmJFeGVjQ29tbWFuZFwiKTtcbiAgICAgIHZhciBvcHQgPSB0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXTtcbiAgICAgIGlmIChvcHQpIHtcbiAgICAgICAgaWYgKG9wdC5tb2RhbCkge1xuICAgICAgICAgIGlmICgkLmlzRnVuY3Rpb24ob3B0Lm1vZGFsKSkge1xuICAgICAgICAgICAgLy9jdXN0b20gbW9kYWwgZnVuY3Rpb25cbiAgICAgICAgICAgIC8vb3B0Lm1vZGFsKGNvbW1hbmQsb3B0Lm1vZGFsLHF1ZXJ5U3RhdGUsbmV3IGNsYmsodGhpcykpO1xuICAgICAgICAgICAgb3B0Lm1vZGFsLmNhbGwodGhpcywgY29tbWFuZCwgb3B0Lm1vZGFsLCBxdWVyeVN0YXRlKTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5zaG93TW9kYWwuY2FsbCh0aGlzLCBjb21tYW5kLCBvcHQubW9kYWwsIHF1ZXJ5U3RhdGUpO1xuICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICBpZiAocXVlcnlTdGF0ZSAmJiBvcHQuc3ViSW5zZXJ0ICE9IHRydWUpIHtcbiAgICAgICAgICAgIC8vcmVtb3ZlIGZvcm1hdHRpbmdcbiAgICAgICAgICAgIC8vcmVtb3ZlQ2FsbGJhY2soY29tbWFuZCx2YWx1ZSk7XG4gICAgICAgICAgICB0aGlzLndiYlJlbW92ZUNhbGxiYWNrKGNvbW1hbmQpO1xuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAvL2luc2VydCBmb3JtYXRcbiAgICAgICAgICAgIGlmIChvcHQuZ3JvdXBrZXkpIHtcbiAgICAgICAgICAgICAgdmFyIGdyb3Vwc2VsID0gdGhpcy5vcHRpb25zLmdyb3Vwc1tvcHQuZ3JvdXBrZXldO1xuICAgICAgICAgICAgICBpZiAoZ3JvdXBzZWwpIHtcbiAgICAgICAgICAgICAgICB2YXIgc25vZGUgPSB0aGlzLmdldFNlbGVjdE5vZGUoKTtcbiAgICAgICAgICAgICAgICAkLmVhY2goZ3JvdXBzZWwsICQucHJveHkoZnVuY3Rpb24gKGksIHNlbCkge1xuICAgICAgICAgICAgICAgICAgdmFyIGlzID0gdGhpcy5pc0NvbnRhaW4oc25vZGUsIHNlbCk7XG4gICAgICAgICAgICAgICAgICBpZiAoaXMpIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyICRzcCA9ICQoJzxzcGFuPicpLmh0bWwoaXMuaW5uZXJIVE1MKVxuICAgICAgICAgICAgICAgICAgICB2YXIgaWQgPSB0aGlzLnNldFVJRCgkc3ApO1xuICAgICAgICAgICAgICAgICAgICAkKGlzKS5yZXBsYWNlV2l0aCgkc3ApO1xuICAgICAgICAgICAgICAgICAgICB0aGlzLnNlbGVjdE5vZGUodGhpcy4kZWRpdG9yLmZpbmQoXCIjXCIgKyBpZClbMF0pO1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB0aGlzLndiYkluc2VydENhbGxiYWNrKGNvbW1hbmQsIHZhbHVlKVxuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgd2JiSW5zZXJ0Q2FsbGJhY2s6IGZ1bmN0aW9uIChjb21tYW5kLCBwYXJhbW9iaikge1xuICAgICAgaWYgKHR5cGVvZiAocGFyYW1vYmopICE9IFwib2JqZWN0XCIpIHtcbiAgICAgICAgcGFyYW1vYmogPSB7fVxuICAgICAgfVxuICAgICAgO1xuICAgICAgJC5sb2coXCJ3YmJJbnNlcnRDYWxsYmFjazogXCIgKyBjb21tYW5kKTtcbiAgICAgIHZhciBkYXRhID0gdGhpcy5nZXRDb2RlQnlDb21tYW5kKGNvbW1hbmQsIHBhcmFtb2JqKTtcbiAgICAgIHRoaXMuaW5zZXJ0QXRDdXJzb3IoZGF0YSk7XG5cbiAgICAgIGlmICh0aGlzLnNlbHRleHRJRCAmJiBkYXRhLmluZGV4T2YodGhpcy5zZWx0ZXh0SUQpICE9IC0xKSB7XG4gICAgICAgIHZhciBzbm9kZSA9IHRoaXMuJGJvZHkuZmluZChcIiNcIiArIHRoaXMuc2VsdGV4dElEKVswXTtcbiAgICAgICAgdGhpcy5zZWxlY3ROb2RlKHNub2RlKTtcbiAgICAgICAgJChzbm9kZSkucmVtb3ZlQXR0cihcImlkXCIpO1xuICAgICAgICB0aGlzLnNlbHRleHRJRCA9IGZhbHNlO1xuICAgICAgfVxuICAgIH0sXG4gICAgd2JiUmVtb3ZlQ2FsbGJhY2s6IGZ1bmN0aW9uIChjb21tYW5kLCBjbGVhcikge1xuICAgICAgJC5sb2coXCJ3YmJSZW1vdmVDYWxsYmFjazogXCIgKyBjb21tYW5kKTtcbiAgICAgIHZhciBvcHQgPSB0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXTtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuYmJtb2RlKSB7XG4gICAgICAgIC8vYmJtb2RlXG4gICAgICAgIC8vUkVNT1ZFIEJCQ09ERVxuICAgICAgICB2YXIgcG9zID0gdGhpcy5nZXRDdXJzb3JQb3NCQigpO1xuICAgICAgICB2YXIgc3RleHRudW0gPSAwO1xuICAgICAgICAkLmVhY2gob3B0LmJiU2VsZWN0b3IsICQucHJveHkoZnVuY3Rpb24gKGksIGJiY29kZSkge1xuICAgICAgICAgIHZhciBzdGV4dCA9IGJiY29kZS5tYXRjaCgvXFx7W1xcc1xcU10rP1xcfS9nKTtcbiAgICAgICAgICAkLmVhY2goc3RleHQsIGZ1bmN0aW9uIChuLCBzKSB7XG4gICAgICAgICAgICBpZiAocy50b0xvd2VyQ2FzZSgpID09IFwie3NlbHRleHR9XCIpIHtcbiAgICAgICAgICAgICAgc3RleHRudW0gPSBuO1xuICAgICAgICAgICAgICByZXR1cm4gZmFsc2VcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9KTtcbiAgICAgICAgICB2YXIgYSA9IHRoaXMuaXNCQkNvbnRhaW4oYmJjb2RlKTtcbiAgICAgICAgICBpZiAoYSkge1xuICAgICAgICAgICAgdGhpcy50eHRBcmVhLnZhbHVlID0gdGhpcy50eHRBcmVhLnZhbHVlLnN1YnN0cigwLCBhWzFdKSArIHRoaXMudHh0QXJlYS52YWx1ZS5zdWJzdHIoYVsxXSwgdGhpcy50eHRBcmVhLnZhbHVlLmxlbmd0aCAtIGFbMV0pLnJlcGxhY2UoYVswXVswXSwgKGNsZWFyID09PSB0cnVlKSA/ICcnIDogYVswXVtzdGV4dG51bSArIDFdKTtcbiAgICAgICAgICAgIHRoaXMuc2V0Q3Vyc29yUG9zQkIoYVsxXSk7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgfVxuICAgICAgICB9LCB0aGlzKSk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICB2YXIgbm9kZSA9IHRoaXMuZ2V0U2VsZWN0Tm9kZSgpO1xuICAgICAgICAkLmVhY2gob3B0LnJvb3RTZWxlY3RvciwgJC5wcm94eShmdW5jdGlvbiAoaSwgcykge1xuICAgICAgICAgIC8vJC5sb2coXCJSUzogXCIrcyk7XG4gICAgICAgICAgdmFyIHJvb3QgPSB0aGlzLmlzQ29udGFpbihub2RlLCBzKTtcbiAgICAgICAgICBpZiAoIXJvb3QpIHtcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgIH1cbiAgICAgICAgICB2YXIgJHJvb3QgPSAkKHJvb3QpO1xuICAgICAgICAgIHZhciBjcyA9IHRoaXMub3B0aW9ucy5ydWxlc1tzXVswXVsxXTtcbiAgICAgICAgICBpZiAoJHJvb3QuaXMoXCJzcGFuW3diYl1cIikgfHwgISRyb290LmlzKFwic3Bhbixmb250XCIpKSB7IC8vcmVtb3ZlIG9ubHkgYmxvY2tzXG4gICAgICAgICAgICBpZiAoY2xlYXIgPT09IHRydWUgfHwgKCFjcyB8fCAhY3NbXCJzZWx0ZXh0XCJdKSkge1xuICAgICAgICAgICAgICB0aGlzLnNldEN1cnNvckJ5RWwoJHJvb3QpO1xuICAgICAgICAgICAgICAkcm9vdC5yZW1vdmUoKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgIGlmIChjcyAmJiBjc1tcInNlbHRleHRcIl0gJiYgY3NbXCJzZWx0ZXh0XCJdW1wic2VsXCJdKSB7XG4gICAgICAgICAgICAgICAgdmFyIGh0bWxkYXRhID0gJHJvb3QuZmluZChjc1tcInNlbHRleHRcIl1bXCJzZWxcIl0pLmh0bWwoKTtcbiAgICAgICAgICAgICAgICBpZiAob3B0Lm9ubHlDbGVhclRleHQgPT09IHRydWUpIHtcbiAgICAgICAgICAgICAgICAgIGh0bWxkYXRhID0gdGhpcy5nZXRIVE1MKGh0bWxkYXRhLCB0cnVlLCB0cnVlKTtcbiAgICAgICAgICAgICAgICAgIGh0bWxkYXRhID0gaHRtbGRhdGEucmVwbGFjZSgvXFwmIzEyMzsvZywgXCJ7XCIpLnJlcGxhY2UoL1xcJiMxMjU7L2csIFwifVwiKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgJHJvb3QucmVwbGFjZVdpdGgoaHRtbGRhdGEpO1xuICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIHZhciBodG1sZGF0YSA9ICRyb290Lmh0bWwoKTtcbiAgICAgICAgICAgICAgICBpZiAob3B0Lm9ubHlDbGVhclRleHQgPT09IHRydWUpIHtcbiAgICAgICAgICAgICAgICAgIGh0bWxkYXRhID0gdGhpcy5nZXRIVE1MKGh0bWxkYXRhLCB0cnVlKTtcbiAgICAgICAgICAgICAgICAgIGh0bWxkYXRhID0gaHRtbGRhdGEucmVwbGFjZSgvXFwmbHQ7L2csIFwiPFwiKS5yZXBsYWNlKC9cXCZndDsvZywgXCI+XCIpLnJlcGxhY2UoL1xcJiMxMjM7L2csIFwie1wiKS5yZXBsYWNlKC9cXCYjMTI1Oy9nLCBcIn1cIik7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICRyb290LnJlcGxhY2VXaXRoKGh0bWxkYXRhKTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAvL3NwYW4sZm9udCAtIGV4dHJhY3Qgc2VsZWN0IGNvbnRlbnQgZnJvbSB0aGlzIHNwYW4sZm9udFxuICAgICAgICAgICAgdmFyIHJuZyA9IHRoaXMuZ2V0UmFuZ2UoKTtcbiAgICAgICAgICAgIHZhciBzaHRtbCA9IHRoaXMuZ2V0U2VsZWN0VGV4dCgpO1xuICAgICAgICAgICAgdmFyIHJub2RlID0gdGhpcy5nZXRTZWxlY3ROb2RlKCk7XG4gICAgICAgICAgICBpZiAoc2h0bWwgPT0gXCJcIikge1xuICAgICAgICAgICAgICBzaHRtbCA9IFwiXFx1RkVGRlwiO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgc2h0bWwgPSB0aGlzLmNsZWFyRnJvbVN1Ykluc2VydChzaHRtbCwgY29tbWFuZCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB2YXIgaW5zID0gdGhpcy5lbEZyb21TdHJpbmcoc2h0bWwpO1xuXG4gICAgICAgICAgICB2YXIgYmVmb3JlX3JuZyA9ICh3aW5kb3cuZ2V0U2VsZWN0aW9uKSA/IHJuZy5jbG9uZVJhbmdlKCkgOiB0aGlzLmJvZHkuY3JlYXRlVGV4dFJhbmdlKCk7XG4gICAgICAgICAgICB2YXIgYWZ0ZXJfcm5nID0gKHdpbmRvdy5nZXRTZWxlY3Rpb24pID8gcm5nLmNsb25lUmFuZ2UoKSA6IHRoaXMuYm9keS5jcmVhdGVUZXh0UmFuZ2UoKTtcblxuICAgICAgICAgICAgaWYgKHdpbmRvdy5nZXRTZWxlY3Rpb24pIHtcbiAgICAgICAgICAgICAgdGhpcy5pbnNlcnRBdEN1cnNvcignPHNwYW4gaWQ9XCJ3YmJkaXZpZGVcIj48L3NwYW4+Jyk7XG4gICAgICAgICAgICAgIHZhciBkaXYgPSAkcm9vdC5maW5kKCdzcGFuI3diYmRpdmlkZScpLmdldCgwKTtcbiAgICAgICAgICAgICAgYmVmb3JlX3JuZy5zZXRTdGFydChyb290LmZpcnN0Q2hpbGQsIDApO1xuICAgICAgICAgICAgICBiZWZvcmVfcm5nLnNldEVuZEJlZm9yZShkaXYpO1xuICAgICAgICAgICAgICBhZnRlcl9ybmcuc2V0U3RhcnRBZnRlcihkaXYpO1xuICAgICAgICAgICAgICBhZnRlcl9ybmcuc2V0RW5kQWZ0ZXIocm9vdC5sYXN0Q2hpbGQpO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgYmVmb3JlX3JuZy5tb3ZlVG9FbGVtZW50VGV4dChyb290KTtcbiAgICAgICAgICAgICAgYWZ0ZXJfcm5nLm1vdmVUb0VsZW1lbnRUZXh0KHJvb3QpO1xuICAgICAgICAgICAgICBiZWZvcmVfcm5nLnNldEVuZFBvaW50KCdFbmRUb1N0YXJ0Jywgcm5nKTtcbiAgICAgICAgICAgICAgYWZ0ZXJfcm5nLnNldEVuZFBvaW50KCdTdGFydFRvRW5kJywgcm5nKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHZhciBiZiA9IHRoaXMuZ2V0U2VsZWN0VGV4dChmYWxzZSwgYmVmb3JlX3JuZyk7XG4gICAgICAgICAgICB2YXIgYWYgPSB0aGlzLmdldFNlbGVjdFRleHQoZmFsc2UsIGFmdGVyX3JuZyk7XG4gICAgICAgICAgICBpZiAoYWYgIT0gXCJcIikge1xuICAgICAgICAgICAgICB2YXIgJGFmID0gJHJvb3QuY2xvbmUoKS5odG1sKGFmKTtcbiAgICAgICAgICAgICAgJHJvb3QuYWZ0ZXIoJGFmKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmIChjbGVhciAhPT0gdHJ1ZSkgJHJvb3QuYWZ0ZXIoaW5zKTsgLy9pbnNlcnQgc2VsZWN0IGh0bWxcbiAgICAgICAgICAgIGlmICh3aW5kb3cuZ2V0U2VsZWN0aW9uKSB7XG4gICAgICAgICAgICAgICRyb290Lmh0bWwoYmYpO1xuICAgICAgICAgICAgICBpZiAoY2xlYXIgIT09IHRydWUpIHRoaXMuc2VsZWN0Tm9kZShpbnMpO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgJHJvb3QucmVwbGFjZVdpdGgoYmYpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgIH1cbiAgICAgICAgfSwgdGhpcykpO1xuICAgICAgfVxuICAgIH0sXG4gICAgZXhlY05hdGl2ZUNvbW1hbmQ6IGZ1bmN0aW9uIChjbWQsIHBhcmFtKSB7XG4gICAgICAvLyQubG9nKFwiZXhlY05hdGl2ZUNvbW1hbmQ6ICdcIitjbWQrXCInIDogXCIrcGFyYW0pO1xuICAgICAgdGhpcy5ib2R5LmZvY3VzKCk7IC8vc2V0IGZvY3VzIHRvIGZyYW1lIGJvZHlcbiAgICAgIGlmIChjbWQgPT0gXCJpbnNlcnRIVE1MXCIgJiYgIXdpbmRvdy5nZXRTZWxlY3Rpb24pIHsgLy9JRSBkb2VzJ3Qgc3VwcG9ydCBpbnNlcnRIVE1MXG4gICAgICAgIHZhciByID0gKHRoaXMubGFzdFJhbmdlKSA/IHRoaXMubGFzdFJhbmdlIDogZG9jdW1lbnQuc2VsZWN0aW9uLmNyZWF0ZVJhbmdlKCk7IC8vSUUgNyw4IHJhbmdlIGxvc3QgZml4XG4gICAgICAgIHIucGFzdGVIVE1MKHBhcmFtKTtcbiAgICAgICAgdmFyIHR4dCA9ICQoJzxkaXY+JykuaHRtbChwYXJhbSkudGV4dCgpOyAvL2ZvciBpZSBzZWxlY3Rpb24gaW5zaWRlIGJsb2NrXG4gICAgICAgIHZhciBicnNwID0gdHh0LmluZGV4T2YoXCJcXHVGRUZGXCIpO1xuICAgICAgICBpZiAoYnJzcCA+IC0xKSB7XG4gICAgICAgICAgci5tb3ZlU3RhcnQoJ2NoYXJhY3RlcicsICgtMSkgKiAodHh0Lmxlbmd0aCAtIGJyc3ApKTtcbiAgICAgICAgICByLnNlbGVjdCgpO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMubGFzdFJhbmdlID0gZmFsc2U7XG4gICAgICB9IGVsc2UgaWYgKGNtZCA9PSBcImluc2VydEhUTUxcIikgeyAvL2ZpeCB3ZWJraXQgYnVnIHdpdGggaW5zZXJ0SFRNTFxuICAgICAgICB2YXIgc2VsID0gdGhpcy5nZXRTZWxlY3Rpb24oKTtcbiAgICAgICAgdmFyIGUgPSB0aGlzLmVsRnJvbVN0cmluZyhwYXJhbSk7XG4gICAgICAgIHZhciBybmcgPSAodGhpcy5sYXN0UmFuZ2UpID8gdGhpcy5sYXN0UmFuZ2UgOiB0aGlzLmdldFJhbmdlKCk7XG4gICAgICAgIHJuZy5kZWxldGVDb250ZW50cygpO1xuICAgICAgICBybmcuaW5zZXJ0Tm9kZShlKTtcbiAgICAgICAgcm5nLmNvbGxhcHNlKGZhbHNlKTtcbiAgICAgICAgc2VsLnJlbW92ZUFsbFJhbmdlcygpO1xuICAgICAgICBzZWwuYWRkUmFuZ2Uocm5nKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIGlmICh0eXBlb2YgcGFyYW0gPT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICAgIHBhcmFtID0gZmFsc2U7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMubGFzdFJhbmdlKSB7XG4gICAgICAgICAgJC5sb2coXCJMYXN0IHJhbmdlIHNlbGVjdFwiKTtcbiAgICAgICAgICB0aGlzLnNlbGVjdExhc3RSYW5nZSgpXG4gICAgICAgIH1cbiAgICAgICAgZG9jdW1lbnQuZXhlY0NvbW1hbmQoY21kLCBmYWxzZSwgcGFyYW0pO1xuICAgICAgfVxuXG4gICAgfSxcbiAgICBnZXRDb2RlQnlDb21tYW5kOiBmdW5jdGlvbiAoY29tbWFuZCwgcGFyYW1vYmopIHtcbiAgICAgIHJldHVybiAodGhpcy5vcHRpb25zLmJibW9kZSkgPyB0aGlzLmdldEJCQ29kZUJ5Q29tbWFuZChjb21tYW5kLCBwYXJhbW9iaikgOiB0aGlzLmdldEhUTUxCeUNvbW1hbmQoY29tbWFuZCwgcGFyYW1vYmopO1xuICAgIH0sXG4gICAgZ2V0QkJDb2RlQnlDb21tYW5kOiBmdW5jdGlvbiAoY29tbWFuZCwgcGFyYW1zKSB7XG4gICAgICBpZiAoIXRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2NvbW1hbmRdKSB7XG4gICAgICAgIHJldHVybiBcIlwiO1xuICAgICAgfVxuICAgICAgaWYgKHR5cGVvZiAocGFyYW1zKSA9PSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICAgIHBhcmFtcyA9IHt9O1xuICAgICAgfVxuICAgICAgcGFyYW1zID0gdGhpcy5rZXlzVG9Mb3dlcihwYXJhbXMpO1xuICAgICAgaWYgKCFwYXJhbXNbXCJzZWx0ZXh0XCJdKSB7XG4gICAgICAgIC8vZ2V0IHNlbGVjdGVkIHRleHRcbiAgICAgICAgcGFyYW1zW1wic2VsdGV4dFwiXSA9IHRoaXMuZ2V0U2VsZWN0VGV4dCh0cnVlKTtcbiAgICAgIH1cblxuICAgICAgdmFyIGJiY29kZSA9IHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2NvbW1hbmRdLmJiY29kZTtcbiAgICAgIC8vYmJjb2RlID0gdGhpcy5zdHJmKGJiY29kZSxwYXJhbXMpO1xuICAgICAgYmJjb2RlID0gYmJjb2RlLnJlcGxhY2UoL1xceyguKj8pKFxcWy4qP1xcXSkqXFx9L2csIGZ1bmN0aW9uIChzdHIsIHAsIHZyZ3gpIHtcbiAgICAgICAgaWYgKHZyZ3gpIHtcbiAgICAgICAgICB2YXIgdnJneHA7XG4gICAgICAgICAgaWYgKHZyZ3gpIHtcbiAgICAgICAgICAgIHZyZ3hwID0gbmV3IFJlZ0V4cCh2cmd4ICsgXCIrXCIsIFwiaVwiKTtcbiAgICAgICAgICB9XG4gICAgICAgICAgaWYgKHR5cGVvZiAocGFyYW1zW3AudG9Mb3dlckNhc2UoKV0pICE9IFwidW5kZWZpbmVkXCIgJiYgcGFyYW1zW3AudG9Mb3dlckNhc2UoKV0udG9TdHJpbmcoKS5tYXRjaCh2cmd4cCkgPT09IG51bGwpIHtcbiAgICAgICAgICAgIC8vbm90IHZhbGlkIHZhbHVlXG4gICAgICAgICAgICByZXR1cm4gXCJcIjtcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuICh0eXBlb2YgKHBhcmFtc1twLnRvTG93ZXJDYXNlKCldKSA9PSBcInVuZGVmaW5lZFwiKSA/IFwiXCIgOiBwYXJhbXNbcC50b0xvd2VyQ2FzZSgpXTtcbiAgICAgIH0pO1xuXG4gICAgICAvL2luc2VydCBmaXJzdCB3aXRoIG1heCBwYXJhbXNcbiAgICAgIHZhciByYmJjb2RlID0gbnVsbCwgbWF4cGNvdW50ID0gMDtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXS50cmFuc2Zvcm0pIHtcbiAgICAgICAgdmFyIHRyID0gW107XG4gICAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXS50cmFuc2Zvcm0sIGZ1bmN0aW9uIChodG1sLCBiYikge1xuICAgICAgICAgIHRyLnB1c2goYmIpO1xuICAgICAgICB9KTtcbiAgICAgICAgdHIgPSB0aGlzLnNvcnRBcnJheSh0ciwgLTEpO1xuICAgICAgICAkLmVhY2godHIsIGZ1bmN0aW9uIChpLCB2KSB7XG4gICAgICAgICAgdmFyIHZhbGlkID0gdHJ1ZSwgcGNvdW50ID0gMCwgcG5hbWUgPSB7fTtcbiAgICAgICAgICA7XG4gICAgICAgICAgdiA9IHYucmVwbGFjZSgvXFx7KC4qPykoXFxbLio/XFxdKSpcXH0vZywgZnVuY3Rpb24gKHN0ciwgcCwgdnJneCkge1xuICAgICAgICAgICAgdmFyIHZyZ3hwO1xuICAgICAgICAgICAgcCA9IHAudG9Mb3dlckNhc2UoKTtcbiAgICAgICAgICAgIGlmICh2cmd4KSB7XG4gICAgICAgICAgICAgIHZyZ3hwID0gbmV3IFJlZ0V4cCh2cmd4ICsgXCIrXCIsIFwiaVwiKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmICh0eXBlb2YgKHBhcmFtc1twLnRvTG93ZXJDYXNlKCldKSA9PSBcInVuZGVmaW5lZFwiIHx8ICh2cmd4ICYmIHBhcmFtc1twLnRvTG93ZXJDYXNlKCldLnRvU3RyaW5nKCkubWF0Y2godnJneHApID09PSBudWxsKSkge1xuICAgICAgICAgICAgICB2YWxpZCA9IGZhbHNlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgO1xuICAgICAgICAgICAgaWYgKHR5cGVvZiAocGFyYW1zW3BdKSAhPSBcInVuZGVmaW5lZFwiICYmICFwbmFtZVtwXSkge1xuICAgICAgICAgICAgICBwbmFtZVtwXSA9IDE7XG4gICAgICAgICAgICAgIHBjb3VudCsrO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuICh0eXBlb2YgKHBhcmFtc1twLnRvTG93ZXJDYXNlKCldKSA9PSBcInVuZGVmaW5lZFwiKSA/IFwiXCIgOiBwYXJhbXNbcC50b0xvd2VyQ2FzZSgpXTtcbiAgICAgICAgICB9KTtcbiAgICAgICAgICBpZiAodmFsaWQgJiYgKHBjb3VudCA+IG1heHBjb3VudCkpIHtcbiAgICAgICAgICAgIHJiYmNvZGUgPSB2O1xuICAgICAgICAgICAgbWF4cGNvdW50ID0gcGNvdW50O1xuICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgICB9XG4gICAgICByZXR1cm4gcmJiY29kZSB8fCBiYmNvZGU7XG4gICAgfSxcbiAgICBnZXRIVE1MQnlDb21tYW5kOiBmdW5jdGlvbiAoY29tbWFuZCwgcGFyYW1zKSB7XG4gICAgICBpZiAoIXRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2NvbW1hbmRdKSB7XG4gICAgICAgIHJldHVybiBcIlwiO1xuICAgICAgfVxuICAgICAgcGFyYW1zID0gdGhpcy5rZXlzVG9Mb3dlcihwYXJhbXMpO1xuICAgICAgaWYgKHR5cGVvZiAocGFyYW1zKSA9PSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICAgIHBhcmFtcyA9IHt9O1xuICAgICAgfVxuICAgICAgaWYgKCFwYXJhbXNbXCJzZWx0ZXh0XCJdKSB7XG4gICAgICAgIC8vZ2V0IHNlbGVjdGVkIHRleHRcbiAgICAgICAgcGFyYW1zW1wic2VsdGV4dFwiXSA9IHRoaXMuZ2V0U2VsZWN0VGV4dChmYWxzZSk7XG4gICAgICAgIC8vJC5sb2coXCJzZWx0ZXh0OiAnXCIrcGFyYW1zW1wic2VsdGV4dFwiXStcIidcIik7XG4gICAgICAgIGlmIChwYXJhbXNbXCJzZWx0ZXh0XCJdID09IFwiXCIpIHtcbiAgICAgICAgICBwYXJhbXNbXCJzZWx0ZXh0XCJdID0gXCJcXHVGRUZGXCI7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgLy9jbGVhciBzZWxlY3Rpb24gZnJvbSBjdXJyZW50IGNvbW1hbmQgdGFnc1xuICAgICAgICAgIHBhcmFtc1tcInNlbHRleHRcIl0gPSB0aGlzLmNsZWFyRnJvbVN1Ykluc2VydChwYXJhbXNbXCJzZWx0ZXh0XCJdLCBjb21tYW5kKTtcblxuICAgICAgICAgIC8vdG9CQiBpZiBwYXJhbXMgb25seUNsZWFyVGV4dD10cnVlXG4gICAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2NvbW1hbmRdLm9ubHlDbGVhclRleHQgPT09IHRydWUpIHtcbiAgICAgICAgICAgIHBhcmFtc1tcInNlbHRleHRcIl0gPSB0aGlzLnRvQkIocGFyYW1zW1wic2VsdGV4dFwiXSkucmVwbGFjZSgvXFw8L2csIFwiJmx0O1wiKS5yZXBsYWNlKC9cXG4vZywgXCI8YnIvPlwiKS5yZXBsYWNlKC9cXHN7M30vZywgJzxzcGFuIGNsYXNzPVwid2JidGFiXCI+PC9zcGFuPicpO1xuICAgICAgICAgIH1cblxuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIHZhciBwb3N0c2VsID0gXCJcIjtcbiAgICAgIHRoaXMuc2VsdGV4dElEID0gXCJ3YmJpZF9cIiArICgrK3RoaXMubGFzdGlkKTtcbiAgICAgIGlmIChjb21tYW5kICE9IFwibGlua1wiICYmIGNvbW1hbmQgIT0gXCJpbWdcIikge1xuICAgICAgICBwYXJhbXNbXCJzZWx0ZXh0XCJdID0gJzxzcGFuIGlkPVwiJyArIHRoaXMuc2VsdGV4dElEICsgJ1wiPicgKyBwYXJhbXNbXCJzZWx0ZXh0XCJdICsgJzwvc3Bhbj4nOyAvL3VzZSBmb3Igc2VsZWN0IHNlbHRleHRcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHBvc3RzZWwgPSAnPHNwYW4gaWQ9XCInICsgdGhpcy5zZWx0ZXh0SUQgKyAnXCI+XFx1RkVGRjwvc3Bhbj4nXG4gICAgICB9XG4gICAgICB2YXIgaHRtbCA9IHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2NvbW1hbmRdLmh0bWw7XG4gICAgICBodG1sID0gaHRtbC5yZXBsYWNlKC9cXHsoLio/KShcXFsuKj9cXF0pKlxcfS9nLCBmdW5jdGlvbiAoc3RyLCBwLCB2cmd4KSB7XG4gICAgICAgIGlmICh2cmd4KSB7XG4gICAgICAgICAgdmFyIHZyZ3hwID0gbmV3IFJlZ0V4cCh2cmd4ICsgXCIrXCIsIFwiaVwiKTtcbiAgICAgICAgICBpZiAodHlwZW9mIChwYXJhbXNbcC50b0xvd2VyQ2FzZSgpXSkgIT0gXCJ1bmRlZmluZWRcIiAmJiBwYXJhbXNbcC50b0xvd2VyQ2FzZSgpXS50b1N0cmluZygpLm1hdGNoKHZyZ3hwKSA9PT0gbnVsbCkge1xuICAgICAgICAgICAgLy9ub3QgdmFsaWQgdmFsdWVcbiAgICAgICAgICAgIHJldHVybiBcIlwiO1xuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gKHR5cGVvZiAocGFyYW1zW3AudG9Mb3dlckNhc2UoKV0pID09IFwidW5kZWZpbmVkXCIpID8gXCJcIiA6IHBhcmFtc1twLnRvTG93ZXJDYXNlKCldO1xuICAgICAgfSk7XG5cbiAgICAgIC8vaW5zZXJ0IGZpcnN0IHdpdGggbWF4IHBhcmFtc1xuICAgICAgdmFyIHJodG1sID0gbnVsbCwgbWF4cGNvdW50ID0gMDtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXS50cmFuc2Zvcm0pIHtcbiAgICAgICAgdmFyIHRyID0gW107XG4gICAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXS50cmFuc2Zvcm0sIGZ1bmN0aW9uIChodG1sLCBiYikge1xuICAgICAgICAgIHRyLnB1c2goaHRtbCk7XG4gICAgICAgIH0pO1xuICAgICAgICB0ciA9IHRoaXMuc29ydEFycmF5KHRyLCAtMSk7XG4gICAgICAgICQuZWFjaCh0ciwgZnVuY3Rpb24gKGksIHYpIHtcbiAgICAgICAgICB2YXIgdmFsaWQgPSB0cnVlLCBwY291bnQgPSAwLCBwbmFtZSA9IHt9O1xuICAgICAgICAgIHYgPSB2LnJlcGxhY2UoL1xceyguKj8pKFxcWy4qP1xcXSkqXFx9L2csIGZ1bmN0aW9uIChzdHIsIHAsIHZyZ3gpIHtcbiAgICAgICAgICAgIHZhciB2cmd4cDtcbiAgICAgICAgICAgIHAgPSBwLnRvTG93ZXJDYXNlKCk7XG4gICAgICAgICAgICBpZiAodnJneCkge1xuICAgICAgICAgICAgICB2cmd4cCA9IG5ldyBSZWdFeHAodnJneCArIFwiK1wiLCBcImlcIik7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAodHlwZW9mIChwYXJhbXNbcF0pID09IFwidW5kZWZpbmVkXCIgfHwgKHZyZ3ggJiYgcGFyYW1zW3BdLnRvU3RyaW5nKCkubWF0Y2godnJneHApID09PSBudWxsKSkge1xuICAgICAgICAgICAgICB2YWxpZCA9IGZhbHNlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgO1xuICAgICAgICAgICAgaWYgKHR5cGVvZiAocGFyYW1zW3BdKSAhPSBcInVuZGVmaW5lZFwiICYmICFwbmFtZVtwXSkge1xuICAgICAgICAgICAgICBwbmFtZVtwXSA9IDE7XG4gICAgICAgICAgICAgIHBjb3VudCsrO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuICh0eXBlb2YgKHBhcmFtc1twXSkgPT0gXCJ1bmRlZmluZWRcIikgPyBcIlwiIDogcGFyYW1zW3BdO1xuICAgICAgICAgIH0pO1xuICAgICAgICAgIGlmICh2YWxpZCAmJiAocGNvdW50ID4gbWF4cGNvdW50KSkge1xuICAgICAgICAgICAgcmh0bWwgPSB2O1xuICAgICAgICAgICAgbWF4cGNvdW50ID0gcGNvdW50O1xuICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgICB9XG4gICAgICByZXR1cm4gKHJodG1sIHx8IGh0bWwpICsgcG9zdHNlbDtcbiAgICB9LFxuXG4gICAgLy9TRUxFQ1RJT04gRlVOQ1RJT05TXG4gICAgZ2V0U2VsZWN0aW9uOiBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAod2luZG93LmdldFNlbGVjdGlvbikge1xuICAgICAgICByZXR1cm4gd2luZG93LmdldFNlbGVjdGlvbigpO1xuICAgICAgfSBlbHNlIGlmIChkb2N1bWVudC5zZWxlY3Rpb24pIHtcbiAgICAgICAgcmV0dXJuICh0aGlzLm9wdGlvbnMuYmJtb2RlKSA/IGRvY3VtZW50LnNlbGVjdGlvbi5jcmVhdGVSYW5nZSgpIDogZG9jdW1lbnQuc2VsZWN0aW9uLmNyZWF0ZVJhbmdlKCk7XG4gICAgICB9XG4gICAgfSxcbiAgICBnZXRTZWxlY3RUZXh0OiBmdW5jdGlvbiAoZnJvbVR4dEFyZWEsIHJhbmdlKSB7XG4gICAgICBpZiAoZnJvbVR4dEFyZWEpIHtcbiAgICAgICAgLy9yZXR1cm4gc2VsZWN0IHRleHQgZnJvbSB0ZXh0YXJlYVxuICAgICAgICB0aGlzLnR4dEFyZWEuZm9jdXMoKTtcbiAgICAgICAgaWYgKCdzZWxlY3Rpb25TdGFydCcgaW4gdGhpcy50eHRBcmVhKSB7XG4gICAgICAgICAgdmFyIGwgPSB0aGlzLnR4dEFyZWEuc2VsZWN0aW9uRW5kIC0gdGhpcy50eHRBcmVhLnNlbGVjdGlvblN0YXJ0O1xuICAgICAgICAgIHJldHVybiB0aGlzLnR4dEFyZWEudmFsdWUuc3Vic3RyKHRoaXMudHh0QXJlYS5zZWxlY3Rpb25TdGFydCwgbCk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgLy9JRVxuICAgICAgICAgIHZhciByID0gZG9jdW1lbnQuc2VsZWN0aW9uLmNyZWF0ZVJhbmdlKCk7XG4gICAgICAgICAgcmV0dXJuIHIudGV4dDtcbiAgICAgICAgfVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgLy9yZXR1cm4gc2VsZWN0IGh0bWwgZnJvbSBib2R5XG4gICAgICAgIHRoaXMuYm9keS5mb2N1cygpO1xuICAgICAgICBpZiAoIXJhbmdlKSB7XG4gICAgICAgICAgcmFuZ2UgPSB0aGlzLmdldFJhbmdlKClcbiAgICAgICAgfVxuICAgICAgICA7XG4gICAgICAgIGlmICh3aW5kb3cuZ2V0U2VsZWN0aW9uKSB7XG4gICAgICAgICAgLy93M2NcbiAgICAgICAgICBpZiAocmFuZ2UpIHtcbiAgICAgICAgICAgIHJldHVybiAkKCc8ZGl2PicpLmFwcGVuZChyYW5nZS5jbG9uZUNvbnRlbnRzKCkpLmh0bWwoKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgLy9pZVxuICAgICAgICAgIHJldHVybiByYW5nZS5odG1sVGV4dDtcbiAgICAgICAgfVxuICAgICAgfVxuICAgICAgcmV0dXJuIFwiXCI7XG4gICAgfSxcbiAgICBnZXRSYW5nZTogZnVuY3Rpb24gKCkge1xuICAgICAgaWYgKHdpbmRvdy5nZXRTZWxlY3Rpb24pIHtcbiAgICAgICAgdmFyIHNlbCA9IHRoaXMuZ2V0U2VsZWN0aW9uKCk7XG4gICAgICAgIGlmIChzZWwuZ2V0UmFuZ2VBdCAmJiBzZWwucmFuZ2VDb3VudCA+IDApIHtcbiAgICAgICAgICByZXR1cm4gc2VsLmdldFJhbmdlQXQoMCk7XG4gICAgICAgIH0gZWxzZSBpZiAoc2VsLmFuY2hvck5vZGUpIHtcbiAgICAgICAgICB2YXIgcmFuZ2UgPSAodGhpcy5vcHRpb25zLmJibW9kZSkgPyBkb2N1bWVudC5jcmVhdGVSYW5nZSgpIDogZG9jdW1lbnQuY3JlYXRlUmFuZ2UoKTtcbiAgICAgICAgICByYW5nZS5zZXRTdGFydChzZWwuYW5jaG9yTm9kZSwgc2VsLmFuY2hvck9mZnNldCk7XG4gICAgICAgICAgcmFuZ2Uuc2V0RW5kKHNlbC5mb2N1c05vZGUsIHNlbC5mb2N1c09mZnNldCk7XG4gICAgICAgICAgcmV0dXJuIHJhbmdlO1xuICAgICAgICB9XG4gICAgICB9IGVsc2Uge1xuICAgICAgICByZXR1cm4gKHRoaXMub3B0aW9ucy5iYm1vZGUgPT09IHRydWUpID8gZG9jdW1lbnQuc2VsZWN0aW9uLmNyZWF0ZVJhbmdlKCkgOiBkb2N1bWVudC5zZWxlY3Rpb24uY3JlYXRlUmFuZ2UoKTtcbiAgICAgIH1cbiAgICB9LFxuICAgIGluc2VydEF0Q3Vyc29yOiBmdW5jdGlvbiAoY29kZSwgZm9yY2VCQk1vZGUpIHtcbiAgICAgIGlmICh0eXBlb2YgKGNvZGUpICE9IFwic3RyaW5nXCIpIHtcbiAgICAgICAgY29kZSA9ICQoXCI8ZGl2PlwiKS5hcHBlbmQoY29kZSkuaHRtbCgpO1xuICAgICAgfVxuICAgICAgaWYgKCh0aGlzLm9wdGlvbnMuYmJtb2RlICYmIHR5cGVvZiAoZm9yY2VCQk1vZGUpID09IFwidW5kZWZpbmVkXCIpIHx8IGZvcmNlQkJNb2RlID09PSB0cnVlKSB7XG4gICAgICAgIHZhciBjbGJiID0gY29kZS5yZXBsYWNlKC8uKihcXFtcXC9cXFMrP1xcXSkkLywgXCIkMVwiKTtcbiAgICAgICAgdmFyIHAgPSB0aGlzLmdldEN1cnNvclBvc0JCKCkgKyAoKGNvZGUuaW5kZXhPZihjbGJiKSAhPSAtMSAmJiBjb2RlLm1hdGNoKC9cXFsuKlxcXS8pKSA/IGNvZGUuaW5kZXhPZihjbGJiKSA6IGNvZGUubGVuZ3RoKTtcbiAgICAgICAgaWYgKGRvY3VtZW50LnNlbGVjdGlvbikge1xuICAgICAgICAgIC8vSUVcbiAgICAgICAgICB0aGlzLnR4dEFyZWEuZm9jdXMoKTtcbiAgICAgICAgICB0aGlzLmdldFNlbGVjdGlvbigpLnRleHQgPSBjb2RlO1xuICAgICAgICB9IGVsc2UgaWYgKHRoaXMudHh0QXJlYS5zZWxlY3Rpb25TdGFydCB8fCB0aGlzLnR4dEFyZWEuc2VsZWN0aW9uU3RhcnQgPT0gJzAnKSB7XG4gICAgICAgICAgdGhpcy50eHRBcmVhLnZhbHVlID0gdGhpcy50eHRBcmVhLnZhbHVlLnN1YnN0cmluZygwLCB0aGlzLnR4dEFyZWEuc2VsZWN0aW9uU3RhcnQpICsgY29kZSArIHRoaXMudHh0QXJlYS52YWx1ZS5zdWJzdHJpbmcodGhpcy50eHRBcmVhLnNlbGVjdGlvbkVuZCwgdGhpcy50eHRBcmVhLnZhbHVlLmxlbmd0aCk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHAgPCAwKSB7XG4gICAgICAgICAgcCA9IDA7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5zZXRDdXJzb3JQb3NCQihwKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHRoaXMuZXhlY05hdGl2ZUNvbW1hbmQoXCJpbnNlcnRIVE1MXCIsIGNvZGUpO1xuICAgICAgICB2YXIgbm9kZSA9IHRoaXMuZ2V0U2VsZWN0Tm9kZSgpO1xuICAgICAgICBpZiAoISQobm9kZSkuY2xvc2VzdChcInRhYmxlLHRyLHRkXCIpKSB7XG4gICAgICAgICAgdGhpcy5zcGxpdFByZXZOZXh0KG5vZGUpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcbiAgICBnZXRTZWxlY3ROb2RlOiBmdW5jdGlvbiAocm5nKSB7XG4gICAgICB0aGlzLmJvZHkuZm9jdXMoKTtcbiAgICAgIGlmICghcm5nKSB7XG4gICAgICAgIHJuZyA9IHRoaXMuZ2V0UmFuZ2UoKTtcbiAgICAgIH1cbiAgICAgIGlmICghcm5nKSB7XG4gICAgICAgIHJldHVybiB0aGlzLiRib2R5O1xuICAgICAgfVxuICAgICAgLy9yZXR1cm4gKHdpbmRvdy5nZXRTZWxlY3Rpb24pID8gcm5nLmNvbW1vbkFuY2VzdG9yQ29udGFpbmVyOnJuZy5wYXJlbnRFbGVtZW50KCk7XG4gICAgICB2YXIgc24gPSAod2luZG93LmdldFNlbGVjdGlvbikgPyBybmcuY29tbW9uQW5jZXN0b3JDb250YWluZXIgOiBybmcucGFyZW50RWxlbWVudCgpO1xuICAgICAgaWYgKCQoc24pLmlzKFwiLmltZ1dyYXBcIikpIHtcbiAgICAgICAgc24gPSAkKHNuKS5jaGlsZHJlbihcImltZ1wiKVswXTtcbiAgICAgIH1cbiAgICAgIHJldHVybiBzbjtcbiAgICB9LFxuICAgIGdldEN1cnNvclBvc0JCOiBmdW5jdGlvbiAoKSB7XG4gICAgICB2YXIgcG9zID0gMDtcbiAgICAgIGlmICgnc2VsZWN0aW9uU3RhcnQnIGluIHRoaXMudHh0QXJlYSkge1xuICAgICAgICBwb3MgPSB0aGlzLnR4dEFyZWEuc2VsZWN0aW9uU3RhcnQ7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICB0aGlzLnR4dEFyZWEuZm9jdXMoKTtcbiAgICAgICAgdmFyIHIgPSB0aGlzLmdldFJhbmdlKCk7XG4gICAgICAgIHZhciBydCA9IGRvY3VtZW50LmJvZHkuY3JlYXRlVGV4dFJhbmdlKCk7XG4gICAgICAgIHJ0Lm1vdmVUb0VsZW1lbnRUZXh0KHRoaXMudHh0QXJlYSk7XG4gICAgICAgIHJ0LnNldEVuZFBvaW50KCdFbmRUb1N0YXJ0Jywgcik7XG4gICAgICAgIHBvcyA9IHJ0LnRleHQubGVuZ3RoO1xuICAgICAgfVxuICAgICAgcmV0dXJuIHBvcztcbiAgICB9LFxuICAgIHNldEN1cnNvclBvc0JCOiBmdW5jdGlvbiAocG9zKSB7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmJibW9kZSkge1xuICAgICAgICBpZiAod2luZG93LmdldFNlbGVjdGlvbikge1xuICAgICAgICAgIHRoaXMudHh0QXJlYS5zZWxlY3Rpb25TdGFydCA9IHBvcztcbiAgICAgICAgICB0aGlzLnR4dEFyZWEuc2VsZWN0aW9uRW5kID0gcG9zO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIHZhciByYW5nZSA9IHRoaXMudHh0QXJlYS5jcmVhdGVUZXh0UmFuZ2UoKTtcbiAgICAgICAgICByYW5nZS5jb2xsYXBzZSh0cnVlKTtcbiAgICAgICAgICByYW5nZS5tb3ZlKCdjaGFyYWN0ZXInLCBwb3MpO1xuICAgICAgICAgIHJhbmdlLnNlbGVjdCgpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcbiAgICBzZWxlY3ROb2RlOiBmdW5jdGlvbiAobm9kZSwgcm5nKSB7XG4gICAgICBpZiAoIXJuZykge1xuICAgICAgICBybmcgPSB0aGlzLmdldFJhbmdlKCk7XG4gICAgICB9XG4gICAgICBpZiAoIXJuZykge1xuICAgICAgICByZXR1cm47XG4gICAgICB9XG4gICAgICBpZiAod2luZG93LmdldFNlbGVjdGlvbikge1xuICAgICAgICB2YXIgc2VsID0gdGhpcy5nZXRTZWxlY3Rpb24oKTtcbiAgICAgICAgcm5nLnNlbGVjdE5vZGVDb250ZW50cyhub2RlKVxuICAgICAgICBzZWwucmVtb3ZlQWxsUmFuZ2VzKCk7XG4gICAgICAgIHNlbC5hZGRSYW5nZShybmcpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgcm5nLm1vdmVUb0VsZW1lbnRUZXh0KG5vZGUpO1xuICAgICAgICBybmcuc2VsZWN0KCk7XG4gICAgICB9XG4gICAgfSxcbiAgICBzZWxlY3RSYW5nZTogZnVuY3Rpb24gKHJuZykge1xuICAgICAgaWYgKHJuZykge1xuICAgICAgICBpZiAoIXdpbmRvdy5nZXRTZWxlY3Rpb24pIHtcbiAgICAgICAgICBybmcuc2VsZWN0KCk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgdmFyIHNlbCA9IHRoaXMuZ2V0U2VsZWN0aW9uKCk7XG4gICAgICAgICAgc2VsLnJlbW92ZUFsbFJhbmdlcygpO1xuICAgICAgICAgIHNlbC5hZGRSYW5nZShybmcpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcbiAgICBjbG9uZVJhbmdlOiBmdW5jdGlvbiAocm5nKSB7XG4gICAgICBpZiAocm5nKSB7XG4gICAgICAgIGlmICghd2luZG93LmdldFNlbGVjdGlvbikge1xuICAgICAgICAgIHJldHVybiBybmcuZHVwbGljYXRlKCk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgcmV0dXJuIHJuZy5jbG9uZVJhbmdlKCk7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9LFxuICAgIGdldFJhbmdlQ2xvbmU6IGZ1bmN0aW9uICgpIHtcbiAgICAgIHJldHVybiB0aGlzLmNsb25lUmFuZ2UodGhpcy5nZXRSYW5nZSgpKTtcbiAgICB9LFxuICAgIHNhdmVSYW5nZTogZnVuY3Rpb24gKCkge1xuICAgICAgdGhpcy5zZXRCb2R5Rm9jdXMoKTtcbiAgICAgIC8vdGhpcy5sYXN0UmFuZ2U9KHRoaXMub3B0aW9ucy5iYm1vZGUpID8gdGhpcy5nZXRDdXJzb3JQb3NCQigpOnRoaXMuZ2V0UmFuZ2VDbG9uZSgpO1xuICAgICAgdGhpcy5sYXN0UmFuZ2UgPSB0aGlzLmdldFJhbmdlQ2xvbmUoKTtcbiAgICB9LFxuICAgIHNlbGVjdExhc3RSYW5nZTogZnVuY3Rpb24gKCkge1xuICAgICAgaWYgKHRoaXMubGFzdFJhbmdlKSB7XG4gICAgICAgIHRoaXMuYm9keS5mb2N1cygpO1xuICAgICAgICB0aGlzLnNlbGVjdFJhbmdlKHRoaXMubGFzdFJhbmdlKTtcbiAgICAgICAgdGhpcy5sYXN0UmFuZ2UgPSBmYWxzZTtcbiAgICAgIH1cbiAgICB9LFxuICAgIHNldEJvZHlGb2N1czogZnVuY3Rpb24gKCkge1xuICAgICAgJC5sb2coXCJTZXQgZm9jdXMgdG8gV3lzaUJCIGVkaXRvclwiKTtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuYmJtb2RlKSB7XG4gICAgICAgIGlmICghdGhpcy4kdHh0QXJlYS5pcyhcIjpmb2N1c1wiKSkge1xuICAgICAgICAgIHRoaXMuJHR4dEFyZWEuZm9jdXMoKTtcbiAgICAgICAgfVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgaWYgKCF0aGlzLiRib2R5LmlzKFwiOmZvY3VzXCIpKSB7XG4gICAgICAgICAgdGhpcy4kYm9keS5mb2N1cygpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcbiAgICBjbGVhckxhc3RSYW5nZTogZnVuY3Rpb24gKCkge1xuICAgICAgdGhpcy5sYXN0UmFuZ2UgPSBmYWxzZTtcbiAgICB9LFxuXG4gICAgLy9UUkFOU0ZPUk0gRlVOQ1RJT05TXG4gICAgZmlsdGVyQnlOb2RlOiBmdW5jdGlvbiAobm9kZSkge1xuICAgICAgdmFyICRuID0gJChub2RlKTtcbiAgICAgIHZhciB0YWdOYW1lID0gJG4uZ2V0KDApLnRhZ05hbWUudG9Mb3dlckNhc2UoKTtcbiAgICAgIHZhciBmaWx0ZXIgPSB0YWdOYW1lO1xuICAgICAgdmFyIGF0dHJpYnV0ZXMgPSB0aGlzLmdldEF0dHJpYnV0ZUxpc3QoJG4uZ2V0KDApKTtcbiAgICAgICQuZWFjaChhdHRyaWJ1dGVzLCAkLnByb3h5KGZ1bmN0aW9uIChpLCBpdGVtKSB7XG4gICAgICAgIHZhciB2ID0gJG4uYXR0cihpdGVtKTtcbiAgICAgICAgLyogJC5sb2coXCJ2OiBcIit2KTtcblx0XHRcdFx0aWYgKCQuaW5BcnJheShpdGVtLHRoaXMub3B0aW9ucy5hdHRyV3JhcCkhPS0xKSB7XG5cdFx0XHRcdFx0aXRlbSA9ICdfJytpdGVtO1xuXHRcdFx0XHR9ICovXG4gICAgICAgIC8vJC5sb2coaXRlbSk7XG4gICAgICAgIGlmIChpdGVtLnN1YnN0cigwLCAxKSA9PSBcIl9cIikge1xuICAgICAgICAgIGl0ZW0gPSBpdGVtLnN1YnN0cigxLCBpdGVtLmxlbmd0aClcbiAgICAgICAgfVxuICAgICAgICBpZiAodiAmJiAhdi5tYXRjaCgvXFx7Lio/XFx9LykpIHtcbiAgICAgICAgICAvLyQubG9nKFwiSTE6IFwiK2l0ZW0pO1xuICAgICAgICAgIGlmIChpdGVtID09IFwic3R5bGVcIikge1xuICAgICAgICAgICAgdmFyIHYgPSAkbi5hdHRyKGl0ZW0pO1xuICAgICAgICAgICAgdmFyIHZhID0gdi5zcGxpdChcIjtcIik7XG4gICAgICAgICAgICAkLmVhY2godmEsIGZ1bmN0aW9uIChpLCBmKSB7XG4gICAgICAgICAgICAgIGlmIChmICYmIGYubGVuZ3RoID4gMCkge1xuICAgICAgICAgICAgICAgIGZpbHRlciArPSAnWycgKyBpdGVtICsgJyo9XCInICsgJC50cmltKGYpICsgJ1wiXSc7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBmaWx0ZXIgKz0gJ1snICsgaXRlbSArICc9XCInICsgdiArICdcIl0nO1xuICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIGlmICh2ICYmIGl0ZW0gPT0gXCJzdHlsZVwiKSB7XG4gICAgICAgICAgLy8kLmxvZyhcIkkyOiBcIitpdGVtKTtcbiAgICAgICAgICB2YXIgdmYgPSB2LnN1YnN0cigwLCB2LmluZGV4T2YoXCJ7XCIpKTtcbiAgICAgICAgICBpZiAodmYgJiYgdmYgIT0gXCJcIikge1xuICAgICAgICAgICAgdmFyIHYgPSB2LnN1YnN0cigwLCB2LmluZGV4T2YoXCJ7XCIpKTtcbiAgICAgICAgICAgIHZhciB2YSA9IHYuc3BsaXQoXCI7XCIpO1xuICAgICAgICAgICAgJC5lYWNoKHZhLCBmdW5jdGlvbiAoaSwgZikge1xuICAgICAgICAgICAgICBmaWx0ZXIgKz0gJ1snICsgaXRlbSArICcqPVwiJyArIGYgKyAnXCJdJztcbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgLy9maWx0ZXIrPSdbJytpdGVtKycqPVwiJyt2LnN1YnN0cigwLHYuaW5kZXhPZihcIntcIikpKydcIl0nO1xuICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIHsgLy8xLjIuMlxuICAgICAgICAgIC8vJC5sb2coXCJJMzogXCIraXRlbSk7XG4gICAgICAgICAgZmlsdGVyICs9ICdbJyArIGl0ZW0gKyAnXSc7XG4gICAgICAgIH1cbiAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgLy9pbmRleFxuICAgICAgdmFyIGlkeCA9ICRuLnBhcmVudCgpLmNoaWxkcmVuKGZpbHRlcikuaW5kZXgoJG4pO1xuICAgICAgaWYgKGlkeCA+IDApIHtcbiAgICAgICAgZmlsdGVyICs9IFwiOmVxKFwiICsgJG4uaW5kZXgoKSArIFwiKVwiO1xuICAgICAgfVxuICAgICAgcmV0dXJuIGZpbHRlcjtcbiAgICB9LFxuICAgIHJlbEZpbHRlckJ5Tm9kZTogZnVuY3Rpb24gKG5vZGUsIHN0b3ApIHtcbiAgICAgIHZhciBwID0gXCJcIjtcbiAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMuYXR0cldyYXAsIGZ1bmN0aW9uIChpLCBhKSB7XG4gICAgICAgIHN0b3AgPSBzdG9wLnJlcGxhY2UoJ1snICsgYSwgJ1tfJyArIGEpO1xuICAgICAgfSk7XG4gICAgICB3aGlsZSAobm9kZSAmJiBub2RlLnRhZ05hbWUgIT0gXCJCT0RZXCIgJiYgISQobm9kZSkuaXMoc3RvcCkpIHtcbiAgICAgICAgcCA9IHRoaXMuZmlsdGVyQnlOb2RlKG5vZGUpICsgXCIgXCIgKyBwO1xuICAgICAgICBpZiAobm9kZSkge1xuICAgICAgICAgIG5vZGUgPSBub2RlLnBhcmVudE5vZGU7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICAgIHJldHVybiBwO1xuICAgIH0sXG4gICAgZ2V0UmVnZXhwUmVwbGFjZTogZnVuY3Rpb24gKHN0ciwgdmFsaWRuYW1lKSB7XG4gICAgICBzdHIgPSBzdHIucmVwbGFjZSgvKFxcKHxcXCl8XFxbfFxcXXxcXC58XFwqfFxcP3xcXDp8XFxcXCkvZywgXCJcXFxcJDFcIilcbiAgICAgICAgLnJlcGxhY2UoL1xccysvZywgXCJcXFxccytcIilcbiAgICAgICAgLnJlcGxhY2UodmFsaWRuYW1lLnJlcGxhY2UoLyhcXCh8XFwpfFxcW3xcXF18XFwufFxcKnxcXD98XFw6fFxcXFwpL2csIFwiXFxcXCQxXCIpLCBcIiguKylcIilcbiAgICAgICAgLnJlcGxhY2UoL1xce1xcUys/XFx9L2csIFwiLipcIik7XG4gICAgICByZXR1cm4gKHN0cik7XG4gICAgfSxcbiAgICBnZXRCQkNvZGU6IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICghdGhpcy5vcHRpb25zLnJ1bGVzKSB7XG4gICAgICAgIHJldHVybiB0aGlzLiR0eHRBcmVhLnZhbCgpO1xuICAgICAgfVxuICAgICAgaWYgKHRoaXMub3B0aW9ucy5iYm1vZGUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuJHR4dEFyZWEudmFsKCk7XG4gICAgICB9XG4gICAgICB0aGlzLmNsZWFyRW1wdHkoKTtcbiAgICAgIHRoaXMucmVtb3ZlTGFzdEJvZHlCUigpO1xuICAgICAgcmV0dXJuIHRoaXMudG9CQih0aGlzLiRib2R5Lmh0bWwoKSk7XG4gICAgfSxcbiAgICB0b0JCOiBmdW5jdGlvbiAoZGF0YSkge1xuICAgICAgaWYgKCFkYXRhKSB7XG4gICAgICAgIHJldHVybiBcIlwiO1xuICAgICAgfVxuICAgICAgO1xuICAgICAgdmFyICRlID0gKHR5cGVvZiAoZGF0YSkgPT0gXCJzdHJpbmdcIikgPyAkKCc8c3Bhbj4nKS5odG1sKGRhdGEpIDogJChkYXRhKTtcbiAgICAgIC8vcmVtb3ZlIGxhc3QgQlJcbiAgICAgICRlLmZpbmQoXCJkaXYsYmxvY2txdW90ZSxwXCIpLmVhY2goZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAodGhpcy5ub2RlVHlwZSAhPSAzICYmIHRoaXMubGFzdENoaWxkICYmIHRoaXMubGFzdENoaWxkLnRhZ05hbWUgPT0gXCJCUlwiKSB7XG4gICAgICAgICAgJCh0aGlzLmxhc3RDaGlsZCkucmVtb3ZlKCk7XG4gICAgICAgIH1cbiAgICAgIH0pXG4gICAgICBpZiAoJGUuaXMoXCJkaXYsYmxvY2txdW90ZSxwXCIpICYmICRlWzBdLm5vZGVUeXBlICE9IDMgJiYgJGVbMF0ubGFzdENoaWxkICYmICRlWzBdLmxhc3RDaGlsZC50YWdOYW1lID09IFwiQlJcIikge1xuICAgICAgICAkKCRlWzBdLmxhc3RDaGlsZCkucmVtb3ZlKCk7XG4gICAgICB9XG4gICAgICAvL0VORCByZW1vdmUgbGFzdCBCUlxuXG4gICAgICAvL1JlbW92ZSBCUlxuICAgICAgJGUuZmluZChcInVsID4gYnIsIHRhYmxlID4gYnIsIHRyID4gYnJcIikucmVtb3ZlKCk7XG4gICAgICAvL0lFXG5cbiAgICAgIHZhciBvdXRiYiA9IFwiXCI7XG5cbiAgICAgIC8vdHJhbnNmb3JtIHNtaWxlc1xuICAgICAgJC5lYWNoKHRoaXMub3B0aW9ucy5zcnVsZXMsICQucHJveHkoZnVuY3Rpb24gKHMsIGJiKSB7XG4gICAgICAgICRlLmZpbmQocykucmVwbGFjZVdpdGgoYmJbMF0pO1xuICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAkZS5jb250ZW50cygpLmVhY2goJC5wcm94eShmdW5jdGlvbiAoaSwgZWwpIHtcbiAgICAgICAgdmFyICRlbCA9ICQoZWwpO1xuICAgICAgICBpZiAoZWwubm9kZVR5cGUgPT09IDMpIHtcbiAgICAgICAgICBvdXRiYiArPSBlbC5kYXRhLnJlcGxhY2UoL1xcbisvLCBcIlwiKS5yZXBsYWNlKC9cXHQvZywgXCIgICBcIik7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgLy9wcm9jZXNzIGh0bWwgdGFnXG4gICAgICAgICAgdmFyIHJwbCwgcHJvY2Vzc2VkID0gZmFsc2U7XG5cbiAgICAgICAgICAvL2ZvciAodmFyIHJvb3RzZWwgaW4gdGhpcy5vcHRpb25zLnJ1bGVzKSB7XG4gICAgICAgICAgZm9yICh2YXIgaiA9IDA7IGogPCB0aGlzLnJzZWxsaXN0Lmxlbmd0aDsgaisrKSB7XG4gICAgICAgICAgICB2YXIgcm9vdHNlbCA9IHRoaXMucnNlbGxpc3Rbal07XG4gICAgICAgICAgICBpZiAoJGVsICYmICRlbC5pcyhyb290c2VsKSkge1xuICAgICAgICAgICAgICAvL2l0IGlzIHJvb3Qgc2VsXG4gICAgICAgICAgICAgIHZhciBybGlzdCA9IHRoaXMub3B0aW9ucy5ydWxlc1tyb290c2VsXTtcbiAgICAgICAgICAgICAgZm9yICh2YXIgaSA9IDA7IGkgPCBybGlzdC5sZW5ndGg7IGkrKykge1xuICAgICAgICAgICAgICAgIHZhciBiYmNvZGUgPSBybGlzdFtpXVswXTtcbiAgICAgICAgICAgICAgICB2YXIgY3J1bGVzID0gcmxpc3RbaV1bMV07XG4gICAgICAgICAgICAgICAgdmFyIHNraXAgPSBmYWxzZSwga2VlcEVsZW1lbnQgPSBmYWxzZSwga2VlcEF0dHIgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICBpZiAoISRlbC5pcyhcImJyXCIpKSB7XG4gICAgICAgICAgICAgICAgICBiYmNvZGUgPSBiYmNvZGUucmVwbGFjZSgvXFxuL2csIFwiPGJyPlwiKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgYmJjb2RlID0gYmJjb2RlLnJlcGxhY2UoL1xceyguKj8pKFxcWy4qP1xcXSkqXFx9L2csICQucHJveHkoZnVuY3Rpb24gKHN0ciwgcywgdnJneCkge1xuICAgICAgICAgICAgICAgICAgdmFyIGMgPSBjcnVsZXNbcy50b0xvd2VyQ2FzZSgpXTtcbiAgICAgICAgICAgICAgICAgIC8vaWYgKHR5cGVvZihjKT09XCJ1bmRlZmluZWRcIikgeyQubG9nKFwiUGFyYW06IHtcIitzK1wifSBub3QgZm91bmQgaW4gSFRNTCByZXByZXNlbnRhdGlvbi5cIik7c2tpcD10cnVlO3JldHVybiBzO31cbiAgICAgICAgICAgICAgICAgIGlmICh0eXBlb2YgKGMpID09IFwidW5kZWZpbmVkXCIpIHtcbiAgICAgICAgICAgICAgICAgICAgJC5sb2coXCJQYXJhbToge1wiICsgcyArIFwifSBub3QgZm91bmQgaW4gSFRNTCByZXByZXNlbnRhdGlvbi5cIik7XG4gICAgICAgICAgICAgICAgICAgIHNraXAgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgdmFyICRjZWwgPSAoYy5zZWwpID8gJChlbCkuZmluZChjLnNlbCkgOiAkKGVsKTtcbiAgICAgICAgICAgICAgICAgIGlmIChjLmF0dHIgJiYgISRjZWwuYXR0cihjLmF0dHIpKSB7XG4gICAgICAgICAgICAgICAgICAgIHNraXAgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gcztcbiAgICAgICAgICAgICAgICAgIH0gLy9za2lwIGlmIG5lZWRlZCBhdHRyaWJ1dGUgbm90IHByZXNlbnQsIG1heWJlIG90aGVyIGJiY29kZVxuICAgICAgICAgICAgICAgICAgdmFyIGNvbnQgPSAoYy5hdHRyKSA/ICRjZWwuYXR0cihjLmF0dHIpIDogJGNlbC5odG1sKCk7XG4gICAgICAgICAgICAgICAgICBpZiAodHlwZW9mIChjb250KSA9PSBcInVuZGVmaW5lZFwiIHx8IGNvbnQgPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgICAgICBza2lwID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHM7XG4gICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICB2YXIgcmVnZXhwID0gYy5yZ3g7XG5cbiAgICAgICAgICAgICAgICAgIC8vc3R5bGUgZml4XG4gICAgICAgICAgICAgICAgICBpZiAocmVnZXhwICYmIGMuYXR0ciA9PSBcInN0eWxlXCIgJiYgcmVnZXhwLnN1YnN0cihyZWdleHAubGVuZ3RoIC0gMSwgMSkgIT0gXCI7XCIpIHtcbiAgICAgICAgICAgICAgICAgICAgcmVnZXhwICs9IFwiO1wiO1xuICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgaWYgKGMuYXR0ciA9PSBcInN0eWxlXCIgJiYgY29udCAmJiBjb250LnN1YnN0cihjb250Lmxlbmd0aCAtIDEsIDEpICE9IFwiO1wiKSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnQgKz0gXCI7XCJcbiAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgIC8vcHJlcGFyZSByZWdleHBcbiAgICAgICAgICAgICAgICAgIHZhciByZ3ggPSAocmVnZXhwKSA/IG5ldyBSZWdFeHAocmVnZXhwLCBcIlwiKSA6IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgaWYgKHJneCkge1xuICAgICAgICAgICAgICAgICAgICBpZiAoY29udC5tYXRjaChyZ3gpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgdmFyIG0gPSBjb250Lm1hdGNoKHJneCk7XG4gICAgICAgICAgICAgICAgICAgICAgaWYgKG0gJiYgbS5sZW5ndGggPT0gMikge1xuICAgICAgICAgICAgICAgICAgICAgICAgY29udCA9IG1bMV07XG4gICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgIGNvbnQgPSBcIlwiO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgIC8vaWYgaXQgaXMgc3R5bGUgYXR0ciwgdGhlbiBrZWVwIHRhZyBhbGl2ZSwgcmVtb3ZlIHRoaXMgc3R5bGVcbiAgICAgICAgICAgICAgICAgIGlmIChjLmF0dHIgJiYgc2tpcCA9PT0gZmFsc2UpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGMuYXR0ciA9PSBcInN0eWxlXCIpIHtcbiAgICAgICAgICAgICAgICAgICAgICBrZWVwRWxlbWVudCA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAgdmFyIG5zdHlsZSA9IFwiXCI7XG4gICAgICAgICAgICAgICAgICAgICAgdmFyIHIgPSBjLnJneC5yZXBsYWNlKC9eXFwuXFwqXFw/LywgXCJcIikucmVwbGFjZSgvXFwuXFwqJC8sIFwiXCIpLnJlcGxhY2UoLzskLywgXCJcIik7XG4gICAgICAgICAgICAgICAgICAgICAgJCgkY2VsLmF0dHIoXCJzdHlsZVwiKS5zcGxpdChcIjtcIikpLmVhY2goZnVuY3Rpb24gKGlkeCwgc3R5bGUpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChzdHlsZSAmJiBzdHlsZSAhPSBcIlwiKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIGlmICghc3R5bGUubWF0Y2gocikpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBuc3R5bGUgKz0gc3R5bGUgKyBcIjtcIjtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgIGlmIChuc3R5bGUgPT0gXCJcIikge1xuICAgICAgICAgICAgICAgICAgICAgICAgJGNlbC5yZW1vdmVBdHRyKFwic3R5bGVcIik7XG4gICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICRjZWwuYXR0cihcInN0eWxlXCIsIG5zdHlsZSk7XG4gICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9IGVsc2UgaWYgKGMucmd4ID09PSBmYWxzZSkge1xuICAgICAgICAgICAgICAgICAgICAgIGtlZXBFbGVtZW50ID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgICBrZWVwQXR0ciA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAgJGNlbC5yZW1vdmVBdHRyKGMuYXR0cik7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgIGlmICgkZWwuaXMoJ3RhYmxlLHRyLHRkLGZvbnQnKSkge1xuICAgICAgICAgICAgICAgICAgICBrZWVwRWxlbWVudCA9IHRydWU7XG4gICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgIHJldHVybiBjb250IHx8IFwiXCI7XG4gICAgICAgICAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICAgICAgICAgIGlmIChza2lwKSB7XG4gICAgICAgICAgICAgICAgICBjb250aW51ZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgaWYgKCRlbC5pcyhcImltZyxicixoclwiKSkge1xuICAgICAgICAgICAgICAgICAgLy9yZXBsYWNlIGVsZW1lbnRcbiAgICAgICAgICAgICAgICAgIG91dGJiICs9IGJiY29kZTtcbiAgICAgICAgICAgICAgICAgICRlbCA9IG51bGw7XG4gICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgaWYgKGtlZXBFbGVtZW50ICYmICEkZWwuYXR0cihcIm5vdGtlZXBcIikpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKCRlbC5pcyhcInRhYmxlLHRyLHRkXCIpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgYmJjb2RlID0gdGhpcy5maXhUYWJsZVRyYW5zZm9ybShiYmNvZGUpO1xuICAgICAgICAgICAgICAgICAgICAgIG91dGJiICs9IHRoaXMudG9CQigkKCc8c3Bhbj4nKS5odG1sKGJiY29kZSkpO1xuICAgICAgICAgICAgICAgICAgICAgICRlbCA9IG51bGw7XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgJGVsLmVtcHR5KCkuaHRtbCgnPHNwYW4+JyArIGJiY29kZSArICc8L3NwYW4+Jyk7XG4gICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKCRlbC5pcyhcImlmcmFtZVwiKSkge1xuICAgICAgICAgICAgICAgICAgICAgIG91dGJiICs9IGJiY29kZTtcbiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAkZWwuZW1wdHkoKS5odG1sKGJiY29kZSk7XG4gICAgICAgICAgICAgICAgICAgICAgb3V0YmIgKz0gdGhpcy50b0JCKCRlbCk7XG4gICAgICAgICAgICAgICAgICAgICAgJGVsID0gbnVsbDtcblxuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cbiAgICAgICAgICBpZiAoISRlbCB8fCAkZWwuaXMoXCJpZnJhbWUsaW1nXCIpKSB7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICB9XG4gICAgICAgICAgb3V0YmIgKz0gdGhpcy50b0JCKCRlbCk7XG4gICAgICAgIH1cbiAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgb3V0YmIucmVwbGFjZSgvXFx1RkVGRi9nLCBcIlwiKTtcbiAgICAgIHJldHVybiBvdXRiYjtcbiAgICB9LFxuICAgIGdldEhUTUw6IGZ1bmN0aW9uIChiYmRhdGEsIGluaXQsIHNraXBsdCkge1xuICAgICAgaWYgKCF0aGlzLm9wdGlvbnMuYmJtb2RlICYmICFpbml0KSB7XG4gICAgICAgIHJldHVybiB0aGlzLiRib2R5Lmh0bWwoKVxuICAgICAgfVxuXG4gICAgICBpZiAoIXNraXBsdCkge1xuICAgICAgICBiYmRhdGEgPSBiYmRhdGEucmVwbGFjZSgvPC9nLCBcIiZsdDtcIikucmVwbGFjZSgvXFx7L2csIFwiJiMxMjM7XCIpLnJlcGxhY2UoL1xcfS9nLCBcIiYjMTI1O1wiKTtcbiAgICAgIH1cbiAgICAgIGJiZGF0YSA9IGJiZGF0YS5yZXBsYWNlKC9cXFtjb2RlXFxdKFtcXHNcXFNdKj8pXFxbXFwvY29kZVxcXS9nLCBmdW5jdGlvbiAocykge1xuICAgICAgICBzID0gcy5zdWJzdHIoXCJbY29kZV1cIi5sZW5ndGgsIHMubGVuZ3RoIC0gXCJbY29kZV1cIi5sZW5ndGggLSBcIlsvY29kZV1cIi5sZW5ndGgpLnJlcGxhY2UoL1xcWy9nLCBcIiYjOTE7XCIpLnJlcGxhY2UoL1xcXS9nLCBcIiYjOTM7XCIpO1xuICAgICAgICByZXR1cm4gXCJbY29kZV1cIiArIHMgKyBcIlsvY29kZV1cIjtcbiAgICAgIH0pO1xuXG5cbiAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMuYnRubGlzdCwgJC5wcm94eShmdW5jdGlvbiAoaSwgYikge1xuICAgICAgICBpZiAoYiAhPSBcInxcIiAmJiBiICE9IFwiLVwiKSB7XG4gICAgICAgICAgdmFyIGZpbmQgPSB0cnVlO1xuICAgICAgICAgIGlmICghdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbYl0gfHwgIXRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2JdLnRyYW5zZm9ybSkge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgJC5lYWNoKHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2JdLnRyYW5zZm9ybSwgJC5wcm94eShmdW5jdGlvbiAoaHRtbCwgYmIpIHtcbiAgICAgICAgICAgIGh0bWwgPSBodG1sLnJlcGxhY2UoL1xcbi9nLCBcIlwiKTsgLy9JRSA3LDggRklYXG4gICAgICAgICAgICB2YXIgYSA9IFtdO1xuICAgICAgICAgICAgYmIgPSBiYi5yZXBsYWNlKC8oXFwofFxcKXxcXFt8XFxdfFxcLnxcXCp8XFw/fFxcOnxcXFxcfFxcXFwpL2csIFwiXFxcXCQxXCIpO1xuICAgICAgICAgICAgLy8ucmVwbGFjZSgvXFxzL2csXCJcXFxcc1wiKTtcbiAgICAgICAgICAgIGJiID0gYmIucmVwbGFjZSgvXFx7KC4qPykoXFxcXFxcWy4qP1xcXFxcXF0pKlxcfS9naSwgJC5wcm94eShmdW5jdGlvbiAoc3RyLCBzLCB2cmd4KSB7XG4gICAgICAgICAgICAgIGEucHVzaChzKTtcbiAgICAgICAgICAgICAgaWYgKHZyZ3gpIHtcbiAgICAgICAgICAgICAgICAvL2hhcyB2YWxpZGF0aW9uIHJlZ2V4cFxuICAgICAgICAgICAgICAgIHZyZ3ggPSB2cmd4LnJlcGxhY2UoL1xcXFwvZywgXCJcIik7XG4gICAgICAgICAgICAgICAgcmV0dXJuIFwiKFwiICsgdnJneCArIFwiKj8pXCI7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgcmV0dXJuIFwiKFtcXFxcc1xcXFxTXSo/KVwiO1xuICAgICAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICAgICAgdmFyIG4gPSAwLCBhbTtcbiAgICAgICAgICAgIHdoaWxlICgoYW0gPSAobmV3IFJlZ0V4cChiYiwgXCJtZ2lcIikpLmV4ZWMoYmJkYXRhKSkgIT0gbnVsbCkge1xuICAgICAgICAgICAgICBpZiAoYW0pIHtcbiAgICAgICAgICAgICAgICB2YXIgciA9IHt9O1xuICAgICAgICAgICAgICAgICQuZWFjaChhLCAkLnByb3h5KGZ1bmN0aW9uIChpLCBrKSB7XG4gICAgICAgICAgICAgICAgICByW2tdID0gYW1baSArIDFdO1xuICAgICAgICAgICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgICAgICAgICB2YXIgbmh0bWwgPSBodG1sO1xuICAgICAgICAgICAgICAgIG5odG1sID0gbmh0bWwucmVwbGFjZSgvXFx7KC4qPykoXFxbLio/XFxdKVxcfS9nLCBcInskMX1cIik7XG4gICAgICAgICAgICAgICAgbmh0bWwgPSB0aGlzLnN0cmYobmh0bWwsIHIpO1xuICAgICAgICAgICAgICAgIGJiZGF0YSA9IGJiZGF0YS5yZXBsYWNlKGFtWzBdLCBuaHRtbCk7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9LCB0aGlzKSk7XG4gICAgICAgIH1cbiAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgLy90cmFuc2Zvcm0gc3lzdGVtIGNvZGVzXG4gICAgICAkLmVhY2godGhpcy5vcHRpb25zLnN5c3RyLCBmdW5jdGlvbiAoaHRtbCwgYmIpIHtcbiAgICAgICAgYmIgPSBiYi5yZXBsYWNlKC8oXFwofFxcKXxcXFt8XFxdfFxcLnxcXCp8XFw/fFxcOnxcXFxcfFxcXFwpL2csIFwiXFxcXCQxXCIpXG4gICAgICAgICAgLnJlcGxhY2UoXCIgXCIsIFwiXFxcXHNcIik7XG4gICAgICAgIGJiZGF0YSA9IGJiZGF0YS5yZXBsYWNlKG5ldyBSZWdFeHAoYmIsIFwiZ1wiKSwgaHRtbCk7XG4gICAgICB9KTtcblxuXG4gICAgICB2YXIgJHdyYXAgPSAkKHRoaXMuZWxGcm9tU3RyaW5nKFwiPGRpdj5cIiArIGJiZGF0YSArIFwiPC9kaXY+XCIpKTtcbiAgICAgIC8vdHJhbnNmb3JtIHNtaWxlc1xuICAgICAgLyogJHdyYXAuY29udGVudHMoKS5maWx0ZXIoZnVuY3Rpb24oKSB7cmV0dXJuIHRoaXMubm9kZVR5cGU9PTN9KS5lYWNoKCQucHJveHkoc21pbGVycGwsdGhpcykpLmVuZCgpLmZpbmQoXCIqXCIpLmNvbnRlbnRzKCkuZmlsdGVyKGZ1bmN0aW9uKCkge3JldHVybiB0aGlzLm5vZGVUeXBlPT0zfSkuZWFjaCgkLnByb3h5KHNtaWxlcnBsLHRoaXMpKTtcblxuXHRcdFx0ZnVuY3Rpb24gc21pbGVycGwoaSxlbCkge1xuXHRcdFx0XHR2YXIgbmRhdGEgPSBlbC5kYXRhO1xuXHRcdFx0XHQkLmVhY2godGhpcy5vcHRpb25zLnNtaWxlTGlzdCwkLnByb3h5KGZ1bmN0aW9uKGkscm93KSB7XG5cdFx0XHRcdFx0dmFyIGZpZHggPSBuZGF0YS5pbmRleE9mKHJvdy5iYmNvZGUpO1xuXHRcdFx0XHRcdGlmIChmaWR4IT0tMSkge1xuXHRcdFx0XHRcdFx0dmFyIGFmdGVybm9kZV90eHQgPSBuZGF0YS5zdWJzdHJpbmcoZmlkeCtyb3cuYmJjb2RlLmxlbmd0aCxuZGF0YS5sZW5ndGgpO1xuXHRcdFx0XHRcdFx0dmFyIGFmdGVybm9kZSA9IGRvY3VtZW50LmNyZWF0ZVRleHROb2RlKGFmdGVybm9kZV90eHQpO1xuXHRcdFx0XHRcdFx0ZWwuZGF0YSA9IG5kYXRhID0gZWwuZGF0YS5zdWJzdHIoMCxmaWR4KTtcblx0XHRcdFx0XHRcdCQoZWwpLmFmdGVyKGFmdGVybm9kZSkuYWZ0ZXIodGhpcy5zdHJmKHJvdy5pbWcsdGhpcy5vcHRpb25zKSk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9LHRoaXMpKTtcblx0XHRcdH0gKi9cbiAgICAgIHRoaXMuZ2V0SFRNTFNtaWxlcygkd3JhcCk7XG4gICAgICAvLyR3cmFwLmNvbnRlbnRzKCkuZmlsdGVyKGZ1bmN0aW9uKCkge3JldHVybiB0aGlzLm5vZGVUeXBlPT0zfSkuZWFjaCgkLnByb3h5KHRoaXMsc21pbGVSUEwsdGhpcykpO1xuXG4gICAgICByZXR1cm4gJHdyYXAuaHRtbCgpO1xuICAgIH0sXG4gICAgZ2V0SFRNTFNtaWxlczogZnVuY3Rpb24gKHJlbCkge1xuICAgICAgJChyZWwpLmNvbnRlbnRzKCkuZmlsdGVyKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMubm9kZVR5cGUgPT0gM1xuICAgICAgfSkuZWFjaCgkLnByb3h5KHRoaXMuc21pbGVSUEwsIHRoaXMpKTtcbiAgICB9LFxuICAgIHNtaWxlUlBMOiBmdW5jdGlvbiAoaSwgZWwpIHtcbiAgICAgIHZhciBuZGF0YSA9IGVsLmRhdGE7XG4gICAgICAkLmVhY2godGhpcy5vcHRpb25zLnNtaWxlTGlzdCwgJC5wcm94eShmdW5jdGlvbiAoaSwgcm93KSB7XG4gICAgICAgIHZhciBmaWR4ID0gbmRhdGEuaW5kZXhPZihyb3cuYmJjb2RlKTtcbiAgICAgICAgaWYgKGZpZHggIT0gLTEpIHtcbiAgICAgICAgICB2YXIgYWZ0ZXJub2RlX3R4dCA9IG5kYXRhLnN1YnN0cmluZyhmaWR4ICsgcm93LmJiY29kZS5sZW5ndGgsIG5kYXRhLmxlbmd0aCk7XG4gICAgICAgICAgdmFyIGFmdGVybm9kZSA9IGRvY3VtZW50LmNyZWF0ZVRleHROb2RlKGFmdGVybm9kZV90eHQpO1xuICAgICAgICAgIGVsLmRhdGEgPSBuZGF0YSA9IGVsLmRhdGEuc3Vic3RyKDAsIGZpZHgpO1xuICAgICAgICAgICQoZWwpLmFmdGVyKGFmdGVybm9kZSkuYWZ0ZXIodGhpcy5zdHJmKHJvdy5pbWcsIHRoaXMub3B0aW9ucykpO1xuICAgICAgICAgIHRoaXMuZ2V0SFRNTFNtaWxlcyhlbC5wYXJlbnROb2RlKTtcbiAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5nZXRIVE1MU21pbGVzKGVsKTtcbiAgICAgIH0sIHRoaXMpKTtcbiAgICB9LFxuICAgIC8vVVRJTFNcbiAgICBzZXRVSUQ6IGZ1bmN0aW9uIChlbCwgYXR0cikge1xuICAgICAgdmFyIGlkID0gXCJ3YmJpZF9cIiArICgrK3RoaXMubGFzdGlkKTtcbiAgICAgIGlmIChlbCkge1xuICAgICAgICAkKGVsKS5hdHRyKGF0dHIgfHwgXCJpZFwiLCBpZCk7XG4gICAgICB9XG4gICAgICByZXR1cm4gaWQ7XG4gICAgfSxcbiAgICBrZXlzVG9Mb3dlcjogZnVuY3Rpb24gKG8pIHtcbiAgICAgICQuZWFjaChvLCBmdW5jdGlvbiAoaywgdikge1xuICAgICAgICBpZiAoayAhPSBrLnRvTG93ZXJDYXNlKCkpIHtcbiAgICAgICAgICBkZWxldGUgb1trXTtcbiAgICAgICAgICBvW2sudG9Mb3dlckNhc2UoKV0gPSB2O1xuICAgICAgICB9XG4gICAgICB9KTtcbiAgICAgIHJldHVybiBvO1xuICAgIH0sXG4gICAgc3RyZjogZnVuY3Rpb24gKHN0ciwgZGF0YSkge1xuICAgICAgZGF0YSA9IHRoaXMua2V5c1RvTG93ZXIoJC5leHRlbmQoe30sIGRhdGEpKTtcbiAgICAgIHJldHVybiBzdHIucmVwbGFjZSgvXFx7KFtcXHdcXC5dKilcXH0vZywgZnVuY3Rpb24gKHN0ciwga2V5KSB7XG4gICAgICAgIGtleSA9IGtleS50b0xvd2VyQ2FzZSgpO1xuICAgICAgICB2YXIga2V5cyA9IGtleS5zcGxpdChcIi5cIiksIHZhbHVlID0gZGF0YVtrZXlzLnNoaWZ0KCkudG9Mb3dlckNhc2UoKV07XG4gICAgICAgICQuZWFjaChrZXlzLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgdmFsdWUgPSB2YWx1ZVt0aGlzXTtcbiAgICAgICAgfSk7XG4gICAgICAgIHJldHVybiAodmFsdWUgPT09IG51bGwgfHwgdmFsdWUgPT09IHVuZGVmaW5lZCkgPyBcIlwiIDogdmFsdWU7XG4gICAgICB9KTtcbiAgICB9LFxuICAgIGVsRnJvbVN0cmluZzogZnVuY3Rpb24gKHN0cikge1xuICAgICAgaWYgKHN0ci5pbmRleE9mKFwiPFwiKSAhPSAtMSAmJiBzdHIuaW5kZXhPZihcIj5cIikgIT0gLTEpIHtcbiAgICAgICAgLy9jcmVhdGUgdGFnXG4gICAgICAgIHZhciB3ciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJTUEFOXCIpO1xuICAgICAgICAkKHdyKS5odG1sKHN0cik7XG4gICAgICAgIHRoaXMuc2V0VUlEKHdyLCBcIndiYlwiKTtcbiAgICAgICAgcmV0dXJuICgkKHdyKS5jb250ZW50cygpLmxlbmd0aCA+IDEpID8gd3IgOiB3ci5maXJzdENoaWxkO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgLy9jcmVhdGUgdGV4dCBub2RlXG4gICAgICAgIHJldHVybiBkb2N1bWVudC5jcmVhdGVUZXh0Tm9kZShzdHIpO1xuICAgICAgfVxuICAgIH0sXG4gICAgaXNDb250YWluOiBmdW5jdGlvbiAobm9kZSwgc2VsKSB7XG4gICAgICB3aGlsZSAobm9kZSAmJiAhJChub2RlKS5oYXNDbGFzcyhcInd5c2liYlwiKSkge1xuICAgICAgICBpZiAoJChub2RlKS5pcyhzZWwpKSB7XG4gICAgICAgICAgcmV0dXJuIG5vZGVcbiAgICAgICAgfVxuICAgICAgICA7XG4gICAgICAgIGlmIChub2RlKSB7XG4gICAgICAgICAgbm9kZSA9IG5vZGUucGFyZW50Tm9kZTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICByZXR1cm4gbnVsbDtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgaXNCQkNvbnRhaW46IGZ1bmN0aW9uIChiYmNvZGUpIHtcbiAgICAgIHZhciBwb3MgPSB0aGlzLmdldEN1cnNvclBvc0JCKCk7XG4gICAgICB2YXIgYiA9IHRoaXMucHJlcGFyZVJHWChiYmNvZGUpO1xuICAgICAgdmFyIGJicmd4ID0gbmV3IFJlZ0V4cChiLCBcImdcIik7XG4gICAgICB2YXIgYTtcbiAgICAgIHZhciBsYXN0aW5kZXggPSAwO1xuICAgICAgd2hpbGUgKChhID0gYmJyZ3guZXhlYyh0aGlzLnR4dEFyZWEudmFsdWUpKSAhPSBudWxsKSB7XG4gICAgICAgIHZhciBwID0gdGhpcy50eHRBcmVhLnZhbHVlLmluZGV4T2YoYVswXSwgbGFzdGluZGV4KTtcbiAgICAgICAgaWYgKHBvcyA+IHAgJiYgcG9zIDwgKHAgKyBhWzBdLmxlbmd0aCkpIHtcbiAgICAgICAgICByZXR1cm4gW2EsIHBdO1xuICAgICAgICB9XG4gICAgICAgIGxhc3RpbmRleCA9IHAgKyAxO1xuICAgICAgfVxuICAgIH0sXG4gICAgcHJlcGFyZVJHWDogZnVuY3Rpb24gKHIpIHtcbiAgICAgIHJldHVybiByLnJlcGxhY2UoLyhcXFt8XFxdfFxcKXxcXCh8XFwufFxcKnxcXD98XFw6fFxcfHxcXFxcKS9nLCBcIlxcXFwkMVwiKS5yZXBsYWNlKC9cXHsuKj9cXH0vZywgXCIoW1xcXFxzXFxcXFNdKj8pXCIpO1xuICAgICAgLy9yZXR1cm4gci5yZXBsYWNlKC8oW15hLXowLTkpL2lnLFwiXFxcXCQxXCIpLnJlcGxhY2UoL1xcey4qP1xcfS9nLFwiKFtcXFxcc1xcXFxTXSo/KVwiKTtcbiAgICB9LFxuICAgIGNoZWNrRm9yTGFzdEJSOiBmdW5jdGlvbiAobm9kZSkge1xuICAgICAgaWYgKCFub2RlKSB7XG4gICAgICAgICRub2RlID0gdGhpcy5ib2R5O1xuICAgICAgfVxuICAgICAgaWYgKG5vZGUubm9kZVR5cGUgPT0gMykge1xuICAgICAgICBub2RlID0gbm9kZS5wYXJlbnROb2RlO1xuICAgICAgfVxuICAgICAgdmFyICRub2RlID0gJChub2RlKTtcbiAgICAgIGlmICgkbm9kZS5pcyhcInNwYW5baWQqPSd3YmJpZCddXCIpKSB7XG4gICAgICAgICRub2RlID0gJG5vZGUucGFyZW50KCk7XG4gICAgICB9XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmJibW9kZSA9PT0gZmFsc2UgJiYgJG5vZGUuaXMoJ2RpdixibG9ja3F1b3RlLGNvZGUnKSAmJiAkbm9kZS5jb250ZW50cygpLmxlbmd0aCA+IDApIHtcbiAgICAgICAgdmFyIGwgPSAkbm9kZVswXS5sYXN0Q2hpbGQ7XG4gICAgICAgIGlmICghbCB8fCAobCAmJiBsLnRhZ05hbWUgIT0gXCJCUlwiKSkge1xuICAgICAgICAgICRub2RlLmFwcGVuZChcIjxici8+XCIpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgICBpZiAodGhpcy4kYm9keS5jb250ZW50cygpLmxlbmd0aCA+IDAgJiYgdGhpcy5ib2R5Lmxhc3RDaGlsZC50YWdOYW1lICE9IFwiQlJcIikge1xuICAgICAgICB0aGlzLiRib2R5LmFwcGVuZCgnPGJyLz4nKTtcbiAgICAgIH1cbiAgICB9LFxuICAgIGdldEF0dHJpYnV0ZUxpc3Q6IGZ1bmN0aW9uIChlbCkge1xuICAgICAgdmFyIGEgPSBbXTtcbiAgICAgICQuZWFjaChlbC5hdHRyaWJ1dGVzLCBmdW5jdGlvbiAoaSwgYXR0cikge1xuICAgICAgICBpZiAoYXR0ci5zcGVjaWZpZWQpIHtcbiAgICAgICAgICBhLnB1c2goYXR0ci5uYW1lKTtcbiAgICAgICAgfVxuICAgICAgfSk7XG4gICAgICByZXR1cm4gYTtcbiAgICB9LFxuICAgIGNsZWFyRnJvbVN1Ykluc2VydDogZnVuY3Rpb24gKGh0bWwsIGNtZCkge1xuICAgICAgaWYgKHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2NtZF0gJiYgdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbY21kXS5yb290U2VsZWN0b3IpIHtcbiAgICAgICAgdmFyICR3ciA9ICQoJzxkaXY+JykuaHRtbChodG1sKTtcbiAgICAgICAgJC5lYWNoKHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2NtZF0ucm9vdFNlbGVjdG9yLCAkLnByb3h5KGZ1bmN0aW9uIChpLCBzKSB7XG4gICAgICAgICAgdmFyIHNlbHRleHQgPSBmYWxzZTtcbiAgICAgICAgICBpZiAodHlwZW9mICh0aGlzLm9wdGlvbnMucnVsZXNbc11bMF1bMV1bXCJzZWx0ZXh0XCJdKSAhPSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICAgICAgICBzZWx0ZXh0ID0gdGhpcy5vcHRpb25zLnJ1bGVzW3NdWzBdWzFdW1wic2VsdGV4dFwiXVtcInNlbFwiXTtcbiAgICAgICAgICB9XG4gICAgICAgICAgdmFyIHJlcyA9IHRydWU7XG4gICAgICAgICAgJHdyLmZpbmQoXCIqXCIpLmVhY2goZnVuY3Rpb24gKCkgeyAvL3dvcmsgd2l0aCBmaW5kKFwiKlwiKSBhbmQgXCJpc1wiLCBiZWNvdXNlIGluIGllNy04IGZpbmQgaXMgY2FzZSBzZW5zaXRpdmVcbiAgICAgICAgICAgIGlmICgkKHRoaXMpLmlzKHMpKSB7XG4gICAgICAgICAgICAgIGlmIChzZWx0ZXh0ICYmIHNlbHRleHRbXCJzZWxcIl0pIHtcbiAgICAgICAgICAgICAgICAkKHRoaXMpLnJlcGxhY2VXaXRoKCQodGhpcykuZmluZChzZWx0ZXh0W1wic2VsXCJdLnRvTG93ZXJDYXNlKCkpLmh0bWwoKSk7XG4gICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgJCh0aGlzKS5yZXBsYWNlV2l0aCgkKHRoaXMpLmh0bWwoKSk7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgcmVzID0gZmFsc2U7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSk7XG4gICAgICAgICAgcmV0dXJuIHJlcztcbiAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICByZXR1cm4gJHdyLmh0bWwoKTtcbiAgICAgIH1cbiAgICAgIHJldHVybiBodG1sO1xuICAgIH0sXG4gICAgc3BsaXRQcmV2TmV4dDogZnVuY3Rpb24gKG5vZGUpIHtcbiAgICAgIGlmIChub2RlLm5vZGVUeXBlID09IDMpIHtcbiAgICAgICAgbm9kZSA9IG5vZGUucGFyZW50Tm9kZVxuICAgICAgfVxuICAgICAgO1xuICAgICAgdmFyIGYgPSB0aGlzLmZpbHRlckJ5Tm9kZShub2RlKS5yZXBsYWNlKC9cXDplcS4qJC9nLCBcIlwiKTtcbiAgICAgIGlmICgkKG5vZGUubmV4dFNpYmxpbmcpLmlzKGYpKSB7XG4gICAgICAgICQobm9kZSkuYXBwZW5kKCQobm9kZS5uZXh0U2libGluZykuaHRtbCgpKTtcbiAgICAgICAgJChub2RlLm5leHRTaWJsaW5nKS5yZW1vdmUoKTtcbiAgICAgIH1cbiAgICAgIGlmICgkKG5vZGUucHJldmlvdXNTaWJsaW5nKS5pcyhmKSkge1xuICAgICAgICAkKG5vZGUpLnByZXBlbmQoJChub2RlLnByZXZpb3VzU2libGluZykuaHRtbCgpKTtcbiAgICAgICAgJChub2RlLnByZXZpb3VzU2libGluZykucmVtb3ZlKCk7XG4gICAgICB9XG4gICAgfSxcbiAgICBtb2RlU3dpdGNoOiBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmJibW9kZSkge1xuICAgICAgICAvL3RvIEhUTUxcbiAgICAgICAgdGhpcy4kYm9keS5odG1sKHRoaXMuZ2V0SFRNTCh0aGlzLiR0eHRBcmVhLnZhbCgpKSk7XG4gICAgICAgIHRoaXMuJHR4dEFyZWEuaGlkZSgpLnJlbW92ZUF0dHIoXCJ3YmJzeW5jXCIpLnZhbChcIlwiKTtcbiAgICAgICAgdGhpcy4kYm9keS5jc3MoXCJtaW4taGVpZ2h0XCIsIHRoaXMuJHR4dEFyZWEuaGVpZ2h0KCkpLnNob3coKS5mb2N1cygpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgLy90byBiYmNvZGVcbiAgICAgICAgdGhpcy4kdHh0QXJlYS52YWwodGhpcy5nZXRCQkNvZGUoKSkuY3NzKFwibWluLWhlaWdodFwiLCB0aGlzLiRib2R5LmhlaWdodCgpKTtcbiAgICAgICAgdGhpcy4kYm9keS5oaWRlKCk7XG4gICAgICAgIHRoaXMuJHR4dEFyZWEuc2hvdygpLmZvY3VzKCk7XG4gICAgICB9XG4gICAgICB0aGlzLm9wdGlvbnMuYmJtb2RlID0gIXRoaXMub3B0aW9ucy5iYm1vZGU7XG4gICAgfSxcbiAgICBjbGVhckVtcHR5OiBmdW5jdGlvbiAoKSB7XG4gICAgICB0aGlzLiRib2R5LmNoaWxkcmVuKCkuZmlsdGVyKGVtcHR5RmlsdGVyKS5yZW1vdmUoKTtcblxuICAgICAgZnVuY3Rpb24gZW1wdHlGaWx0ZXIoKVxuICAgICAge1xuICAgICAgICBpZiAoISQodGhpcykuaXMoXCJzcGFuLGZvbnQsYSxiLGksdSxzXCIpKSB7XG4gICAgICAgICAgLy9jbGVhciBlbXB0eSBvbmx5IGZvciBzcGFuLGZvbnRcbiAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKCEkKHRoaXMpLmhhc0NsYXNzKFwid2JidGFiXCIpICYmICQudHJpbSgkKHRoaXMpLmh0bWwoKSkubGVuZ3RoID09IDApIHtcbiAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfSBlbHNlIGlmICgkKHRoaXMpLmNoaWxkcmVuKCkubGVuZ3RoID4gMCkge1xuICAgICAgICAgICQodGhpcykuY2hpbGRyZW4oKS5maWx0ZXIoZW1wdHlGaWx0ZXIpLnJlbW92ZSgpO1xuICAgICAgICAgIGlmICgkKHRoaXMpLmh0bWwoKS5sZW5ndGggPT0gMCAmJiB0aGlzLnRhZ05hbWUgIT0gXCJCT0RZXCIpIHtcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgZHJvcGRvd25jbGljazogZnVuY3Rpb24gKGJzZWwsIHRzZWwsIGUpIHtcbiAgICAgIC8vdGhpcy5ib2R5LmZvY3VzKCk7XG4gICAgICB2YXIgJGJ0biA9ICQoZS5jdXJyZW50VGFyZ2V0KS5jbG9zZXN0KGJzZWwpO1xuICAgICAgaWYgKCRidG4uaGFzQ2xhc3MoXCJkaXNcIikpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgICAgfVxuICAgICAgaWYgKCRidG4uYXR0cihcIndiYnNob3dcIikpIHtcbiAgICAgICAgLy9oaWRlIGRyb3Bkb3duXG4gICAgICAgICRidG4ucmVtb3ZlQXR0cihcIndiYnNob3dcIik7XG4gICAgICAgICQoZG9jdW1lbnQpLnVuYmluZChcIm1vdXNlZG93blwiLCB0aGlzLmRyb3Bkb3duaGFuZGxlcik7XG4gICAgICAgIGlmIChkb2N1bWVudCkge1xuICAgICAgICAgICQoZG9jdW1lbnQpLnVuYmluZChcIm1vdXNlZG93blwiLCB0aGlzLmRyb3Bkb3duaGFuZGxlcik7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5sYXN0UmFuZ2UgPSBmYWxzZTtcblxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgdGhpcy5zYXZlUmFuZ2UoKTtcbiAgICAgICAgdGhpcy4kZWRpdG9yLmZpbmQoXCIqW3diYnNob3ddXCIpLmVhY2goZnVuY3Rpb24gKGksIGVsKSB7XG4gICAgICAgICAgJChlbCkucmVtb3ZlQ2xhc3MoXCJvblwiKS5maW5kKCQoZWwpLmF0dHIoXCJ3YmJzaG93XCIpKS5oaWRlKCkuZW5kKCkucmVtb3ZlQXR0cihcIndiYnNob3dcIik7XG4gICAgICAgIH0pXG4gICAgICAgICRidG4uYXR0cihcIndiYnNob3dcIiwgdHNlbCk7XG4gICAgICAgICQoZG9jdW1lbnQuYm9keSkuYmluZChcIm1vdXNlZG93blwiLCAkLnByb3h5KGZ1bmN0aW9uIChldnQpIHtcbiAgICAgICAgICB0aGlzLmRyb3Bkb3duaGFuZGxlcigkYnRuLCBic2VsLCB0c2VsLCBldnQpXG4gICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgaWYgKHRoaXMuJGJvZHkpIHtcbiAgICAgICAgICB0aGlzLiRib2R5LmJpbmQoXCJtb3VzZWRvd25cIiwgJC5wcm94eShmdW5jdGlvbiAoZXZ0KSB7XG4gICAgICAgICAgICB0aGlzLmRyb3Bkb3duaGFuZGxlcigkYnRuLCBic2VsLCB0c2VsLCBldnQpXG4gICAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgICAkYnRuLmZpbmQodHNlbCkudG9nZ2xlKCk7XG4gICAgICAkYnRuLnRvZ2dsZUNsYXNzKFwib25cIik7XG4gICAgfSxcbiAgICBkcm9wZG93bmhhbmRsZXI6IGZ1bmN0aW9uICgkYnRuLCBic2VsLCB0c2VsLCBlKSB7XG4gICAgICBpZiAoJChlLnRhcmdldCkucGFyZW50cyhic2VsKS5sZW5ndGggPT0gMCkge1xuICAgICAgICAkYnRuLnJlbW92ZUNsYXNzKFwib25cIikuZmluZCh0c2VsKS5oaWRlKCk7XG4gICAgICAgICQoZG9jdW1lbnQpLnVuYmluZCgnbW91c2Vkb3duJywgdGhpcy5kcm9wZG93bmhhbmRsZXIpO1xuICAgICAgICBpZiAodGhpcy4kYm9keSkge1xuICAgICAgICAgIHRoaXMuJGJvZHkudW5iaW5kKCdtb3VzZWRvd24nLCB0aGlzLmRyb3Bkb3duaGFuZGxlcik7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9LFxuICAgIHJnYlRvSGV4OiBmdW5jdGlvbiAocmdiKSB7XG4gICAgICBpZiAocmdiLnN1YnN0cigwLCAxKSA9PSAnIycpIHtcbiAgICAgICAgcmV0dXJuIHJnYjtcbiAgICAgIH1cbiAgICAgIC8vaWYgKHJnYi5pbmRleE9mKFwicmdiXCIpPT0tMSkge3JldHVybiByZ2I7fVxuICAgICAgaWYgKHJnYi5pbmRleE9mKFwicmdiXCIpID09IC0xKSB7XG4gICAgICAgIC8vSUVcbiAgICAgICAgdmFyIGNvbG9yID0gcGFyc2VJbnQocmdiKTtcbiAgICAgICAgY29sb3IgPSAoKGNvbG9yICYgMHgwMDAwZmYpIDw8IDE2KSB8IChjb2xvciAmIDB4MDBmZjAwKSB8ICgoY29sb3IgJiAweGZmMDAwMCkgPj4+IDE2KTtcbiAgICAgICAgcmV0dXJuICcjJyArIGNvbG9yLnRvU3RyaW5nKDE2KTtcbiAgICAgIH1cbiAgICAgIHZhciBkaWdpdHMgPSAvKC4qPylyZ2JcXCgoXFxkKyksXFxzKihcXGQrKSxcXHMqKFxcZCspXFwpLy5leGVjKHJnYik7XG4gICAgICByZXR1cm4gXCIjXCIgKyB0aGlzLmRlYzJoZXgocGFyc2VJbnQoZGlnaXRzWzJdKSkgKyB0aGlzLmRlYzJoZXgocGFyc2VJbnQoZGlnaXRzWzNdKSkgKyB0aGlzLmRlYzJoZXgocGFyc2VJbnQoZGlnaXRzWzRdKSk7XG4gICAgfSxcbiAgICBkZWMyaGV4OiBmdW5jdGlvbiAoZCkge1xuICAgICAgaWYgKGQgPiAxNSkge1xuICAgICAgICByZXR1cm4gZC50b1N0cmluZygxNik7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICByZXR1cm4gXCIwXCIgKyBkLnRvU3RyaW5nKDE2KTtcbiAgICAgIH1cbiAgICB9LFxuICAgIHN5bmM6IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuYmJtb2RlKSB7XG4gICAgICAgIHRoaXMuJGJvZHkuaHRtbCh0aGlzLmdldEhUTUwodGhpcy50eHRBcmVhLnZhbHVlLCB0cnVlKSk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICB0aGlzLiR0eHRBcmVhLmF0dHIoXCJ3YmJzeW5jXCIsIDEpLnZhbCh0aGlzLmdldEJCQ29kZSgpKTtcbiAgICAgIH1cbiAgICB9LFxuICAgIGNsZWFyUGFzdGU6IGZ1bmN0aW9uIChlbCkge1xuICAgICAgdmFyICRibG9jayA9ICQoZWwpO1xuICAgICAgLy9ORVdcbiAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMucnVsZXMsICQucHJveHkoZnVuY3Rpb24gKHMsIGFyKSB7XG4gICAgICAgIHZhciAkc2YgPSAkYmxvY2suZmluZChzKS5hdHRyKFwid2Jia2VlcFwiLCAxKTtcbiAgICAgICAgaWYgKCRzZi5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgdmFyIHMyID0gYXJbMF1bMV07XG4gICAgICAgICAgJC5lYWNoKHMyLCBmdW5jdGlvbiAoaSwgdikge1xuICAgICAgICAgICAgaWYgKHYuc2VsKSB7XG4gICAgICAgICAgICAgICRzZi5maW5kKHYuc2VsKS5hdHRyKFwid2Jia2VlcFwiLCAxKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgfSwgdGhpcykpO1xuICAgICAgJGJsb2NrLmZpbmQoXCIqW3diYmtlZXAhPScxJ11cIikuZWFjaCgkLnByb3h5KGZ1bmN0aW9uIChpLCBlbCkge1xuICAgICAgICB2YXIgJHRoaXMgPSAkKGVsKTtcbiAgICAgICAgaWYgKCR0aGlzLmlzKCdkaXYscCcpICYmICgkdGhpcy5jaGlsZHJlbigpLmxlbmd0aCA9PSAwIHx8IGVsLmxhc3RDaGlsZC50YWdOYW1lICE9IFwiQlJcIikpIHtcbiAgICAgICAgICAkdGhpcy5hZnRlcihcIjxici8+XCIpO1xuICAgICAgICB9XG4gICAgICB9LCB0aGlzKSk7XG4gICAgICAkYmxvY2suZmluZChcIipbd2Jia2VlcF1cIikucmVtb3ZlQXR0cihcIndiYmtlZXBcIikucmVtb3ZlQXR0cihcInN0eWxlXCIpO1xuICAgICAgJC5sb2coJGJsb2NrLmh0bWwoKSk7XG4gICAgICAvLyQubG9nKFwiQkJDT0RFOiBcIit0aGlzLnRvQkIoJGJsb2NrLmNsb25lKHRydWUpKSk7XG4gICAgICAkYmxvY2suaHRtbCh0aGlzLmdldEhUTUwodGhpcy50b0JCKCRibG9jayksIHRydWUpKTtcbiAgICAgICQubG9nKCRibG9jay5odG1sKCkpO1xuXG4gICAgICAvL09MRFxuICAgICAgLyogJC5lYWNoKHRoaXMub3B0aW9ucy5ydWxlcywkLnByb3h5KGZ1bmN0aW9uKHMsYmIpIHtcblx0XHRcdFx0JGJsb2NrLmZpbmQocykuYXR0cihcIndiYmtlZXBcIiwxKTtcblx0XHRcdH0sdGhpcykpO1xuXG5cdFx0XHQvL3JlcGxhY2UgZGl2IGFuZCBwIHdpdGhvdXQgbGFzdCBiciB0byBodG1sKCkrYnJcblx0XHRcdCRibG9jay5maW5kKFwiKlt3YmJrZWVwIT0nMSddXCIpLmVhY2goJC5wcm94eShmdW5jdGlvbihpLGVsKSB7XG5cdFx0XHRcdHZhciAkdGhpcyA9ICQoZWwpO1xuXHRcdFx0XHRpZiAoJHRoaXMuaXMoJ2RpdixwJykgJiYgKCR0aGlzLmNoaWxkcmVuKCkubGVuZ3RoPT0wIHx8IGVsLmxhc3RDaGlsZC50YWdOYW1lIT1cIkJSXCIpKSB7XG5cdFx0XHRcdFx0JHRoaXMuYWZ0ZXIoXCI8YnIvPlwiKS5hZnRlcigkdGhpcy5jb250ZW50cygpKS5yZW1vdmUoKTtcblx0XHRcdFx0fWVsc2V7XG5cdFx0XHRcdFx0JHRoaXMuYWZ0ZXIoJHRoaXMuY29udGVudHMoKSkucmVtb3ZlKCk7XG5cdFx0XHRcdH1cblx0XHRcdH0sdGhpcykpO1xuXHRcdFx0JGJsb2NrLmZpbmQoXCIqW3diYmtlZXBdXCIpLnJlbW92ZUF0dHIoXCJ3YmJrZWVwXCIpLnJlbW92ZUF0dHIoXCJzdHlsZVwiKTsgKi9cbiAgICB9LFxuICAgIHNvcnRBcnJheTogZnVuY3Rpb24gKGFyLCBhc2MpIHtcbiAgICAgIGFyLnNvcnQoZnVuY3Rpb24gKGEsIGIpIHtcbiAgICAgICAgcmV0dXJuIChhLmxlbmd0aCAtIGIubGVuZ3RoKSAqIChhc2MgfHwgMSk7XG4gICAgICB9KTtcbiAgICAgIHJldHVybiBhcjtcbiAgICB9LFxuICAgIHNtaWxlRmluZDogZnVuY3Rpb24gKCkge1xuICAgICAgaWYgKHRoaXMub3B0aW9ucy5zbWlsZWZpbmQpIHtcbiAgICAgICAgdmFyICRzbWxpc3QgPSAkKHRoaXMub3B0aW9ucy5zbWlsZWZpbmQpLmZpbmQoJ2ltZ1thbHRdJyk7XG4gICAgICAgIGlmICgkc21saXN0Lmxlbmd0aCA+IDApIHtcbiAgICAgICAgICB0aGlzLm9wdGlvbnMuc21pbGVMaXN0ID0gW107XG4gICAgICAgICAgJHNtbGlzdC5lYWNoKCQucHJveHkoZnVuY3Rpb24gKGksIGVsKSB7XG4gICAgICAgICAgICB2YXIgJGVsID0gJChlbCk7XG4gICAgICAgICAgICB0aGlzLm9wdGlvbnMuc21pbGVMaXN0LnB1c2goe3RpdGxlOiAkZWwuYXR0cihcInRpdGxlXCIpLCBiYmNvZGU6ICRlbC5hdHRyKFwiYWx0XCIpLCBpbWc6ICRlbC5yZW1vdmVBdHRyKFwiYWx0XCIpLnJlbW92ZUF0dHIoXCJ0aXRsZVwiKVswXS5vdXRlckhUTUx9KTtcbiAgICAgICAgICB9LCB0aGlzKSk7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9LFxuICAgIGRlc3Ryb3k6IGZ1bmN0aW9uICgpIHtcbiAgICAgIHRoaXMuJGVkaXRvci5yZXBsYWNlV2l0aCh0aGlzLiR0eHRBcmVhKTtcbiAgICAgIHRoaXMuJHR4dEFyZWEucmVtb3ZlQ2xhc3MoXCJ3eXNpYmItdGV4YXJlYVwiKS5zaG93KCk7XG4gICAgICB0aGlzLiRtb2RhbC5yZW1vdmUoKTtcbiAgICAgIHRoaXMuJHR4dEFyZWEuZGF0YShcIndiYlwiLCBudWxsKTtcbiAgICB9LFxuICAgIHByZXNzVGFiOiBmdW5jdGlvbiAoZSkge1xuICAgICAgaWYgKGUgJiYgZS53aGljaCA9PSA5KSB7XG4gICAgICAgIC8vaW5zZXJ0IHRhYlxuICAgICAgICBpZiAoZS5wcmV2ZW50RGVmYXVsdCkge1xuICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAodGhpcy5vcHRpb25zLmJibW9kZSkge1xuICAgICAgICAgIHRoaXMuaW5zZXJ0QXRDdXJzb3IoJyAgICcsIGZhbHNlKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICB0aGlzLmluc2VydEF0Q3Vyc29yKCc8c3BhbiBjbGFzcz1cIndiYnRhYlwiPlxcdUZFRkY8L3NwYW4+JywgZmFsc2UpO1xuICAgICAgICAgIC8vdGhpcy5leGVjTmF0aXZlQ29tbWFuZChcImluZGVudFwiLGZhbHNlKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgcmVtb3ZlTGFzdEJvZHlCUjogZnVuY3Rpb24gKCkge1xuICAgICAgaWYgKHRoaXMuYm9keS5sYXN0Q2hpbGQgJiYgdGhpcy5ib2R5Lmxhc3RDaGlsZC5ub2RlVHlwZSAhPSAzICYmIHRoaXMuYm9keS5sYXN0Q2hpbGQudGFnTmFtZSA9PSBcIkJSXCIpIHtcbiAgICAgICAgdGhpcy5ib2R5LnJlbW92ZUNoaWxkKHRoaXMuYm9keS5sYXN0Q2hpbGQpO1xuICAgICAgICB0aGlzLnJlbW92ZUxhc3RCb2R5QlIoKTtcbiAgICAgIH1cbiAgICB9LFxuICAgIHRyYWNlVGV4dGFyZWFFdmVudDogZnVuY3Rpb24gKGUpIHtcbiAgICAgIGlmICgkKGUudGFyZ2V0KS5jbG9zZXN0KFwiZGl2Lnd5c2liYlwiKS5sZW5ndGggPT0gMCkge1xuICAgICAgICBpZiAoJChkb2N1bWVudC5hY3RpdmVFbGVtZW50KS5pcyhcImRpdi53eXNpYmItYm9keVwiKSkge1xuICAgICAgICAgIHRoaXMuc2F2ZVJhbmdlKCk7XG4gICAgICAgIH1cbiAgICAgICAgc2V0VGltZW91dCgkLnByb3h5KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICB2YXIgZGF0YSA9IHRoaXMuJHR4dEFyZWEudmFsKCk7XG4gICAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5iYm1vZGUgPT09IGZhbHNlICYmIGRhdGEgIT0gXCJcIiAmJiAkKGUudGFyZ2V0KS5jbG9zZXN0KFwiZGl2Lnd5c2liYlwiKS5sZW5ndGggPT0gMCAmJiAhdGhpcy4kdHh0QXJlYS5hdHRyKFwid2Jic3luY1wiKSkge1xuICAgICAgICAgICAgdGhpcy5zZWxlY3RMYXN0UmFuZ2UoKTtcbiAgICAgICAgICAgIHRoaXMuaW5zZXJ0QXRDdXJzb3IodGhpcy5nZXRIVE1MKGRhdGEsIHRydWUpKTtcbiAgICAgICAgICAgIHRoaXMuJHR4dEFyZWEudmFsKFwiXCIpO1xuICAgICAgICAgIH1cbiAgICAgICAgICBpZiAoJChkb2N1bWVudC5hY3RpdmVFbGVtZW50KS5pcyhcImRpdi53eXNpYmItYm9keVwiKSkge1xuICAgICAgICAgICAgdGhpcy5sYXN0UmFuZ2UgPSBmYWxzZTtcbiAgICAgICAgICB9XG4gICAgICAgIH0sIHRoaXMpLCAxMDApO1xuICAgICAgfVxuICAgIH0sXG4gICAgdHh0QXJlYUluaXRDb250ZW50OiBmdW5jdGlvbiAoKSB7XG4gICAgICAvLyQubG9nKHRoaXMudHh0QXJlYS52YWx1ZSk7XG4gICAgICB0aGlzLiRib2R5Lmh0bWwodGhpcy5nZXRIVE1MKHRoaXMudHh0QXJlYS52YWx1ZSwgdHJ1ZSkpO1xuICAgIH0sXG4gICAgZ2V0VmFsaWRhdGlvblJHWDogZnVuY3Rpb24gKHMpIHtcbiAgICAgIGlmIChzLm1hdGNoKC9cXFtcXFMrXFxdLykpIHtcbiAgICAgICAgcmV0dXJuIHMucmVwbGFjZSgvLiooXFxcXCpcXFtcXFMrXFxdKS4qLywgXCIkMVwiKTtcbiAgICAgIH1cbiAgICAgIHJldHVybiBcIlwiO1xuICAgIH0sXG4gICAgc21pbGVDb252ZXJzaW9uOiBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLnNtaWxlTGlzdCAmJiB0aGlzLm9wdGlvbnMuc21pbGVMaXN0Lmxlbmd0aCA+IDApIHtcbiAgICAgICAgdmFyIHNub2RlID0gdGhpcy5nZXRTZWxlY3ROb2RlKCk7XG4gICAgICAgIGlmIChzbm9kZS5ub2RlVHlwZSA9PSAzKSB7XG4gICAgICAgICAgdmFyIG5kYXRhID0gc25vZGUuZGF0YTtcbiAgICAgICAgICBpZiAobmRhdGEubGVuZ3RoID49IDIgJiYgIXRoaXMuaXNJbkNsZWFyVGV4dEJsb2NrKHNub2RlKSAmJiAkKHNub2RlKS5wYXJlbnRzKFwiYVwiKS5sZW5ndGggPT0gMCkge1xuICAgICAgICAgICAgJC5lYWNoKHRoaXMub3B0aW9ucy5zcnVsZXMsICQucHJveHkoZnVuY3Rpb24gKGksIHNhcikge1xuICAgICAgICAgICAgICB2YXIgc21iYiA9IHNhclswXTtcbiAgICAgICAgICAgICAgdmFyIGZpZHggPSBuZGF0YS5pbmRleE9mKHNtYmIpO1xuICAgICAgICAgICAgICBpZiAoZmlkeCAhPSAtMSkge1xuICAgICAgICAgICAgICAgIHZhciBhZnRlcm5vZGVfdHh0ID0gbmRhdGEuc3Vic3RyaW5nKGZpZHggKyBzbWJiLmxlbmd0aCwgbmRhdGEubGVuZ3RoKTtcbiAgICAgICAgICAgICAgICB2YXIgYWZ0ZXJub2RlID0gZG9jdW1lbnQuY3JlYXRlVGV4dE5vZGUoYWZ0ZXJub2RlX3R4dCk7XG4gICAgICAgICAgICAgICAgdmFyIGFmdGVybm9kZV9jdXJzb3IgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KFwiU1BBTlwiKTtcbiAgICAgICAgICAgICAgICBzbm9kZS5kYXRhID0gc25vZGUuZGF0YS5zdWJzdHIoMCwgZmlkeCk7XG4gICAgICAgICAgICAgICAgJChzbm9kZSkuYWZ0ZXIoYWZ0ZXJub2RlKS5hZnRlcihhZnRlcm5vZGVfY3Vyc29yKS5hZnRlcih0aGlzLnN0cmYoc2FyWzFdLCB0aGlzLm9wdGlvbnMpKTtcbiAgICAgICAgICAgICAgICB0aGlzLnNlbGVjdE5vZGUoYWZ0ZXJub2RlX2N1cnNvcik7XG4gICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9LCB0aGlzKSk7XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcbiAgICBpc0luQ2xlYXJUZXh0QmxvY2s6IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICh0aGlzLmNsZWFydGV4dCkge1xuICAgICAgICB2YXIgZmluZCA9IGZhbHNlO1xuICAgICAgICAkLmVhY2godGhpcy5jbGVhcnRleHQsICQucHJveHkoZnVuY3Rpb24gKHNlbCwgY29tbWFuZCkge1xuICAgICAgICAgIGlmICh0aGlzLnF1ZXJ5U3RhdGUoY29tbWFuZCkpIHtcbiAgICAgICAgICAgIGZpbmQgPSBjb21tYW5kO1xuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgIH1cbiAgICAgICAgfSwgdGhpcykpXG4gICAgICAgIHJldHVybiBmaW5kO1xuICAgICAgfVxuICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH0sXG4gICAgd3JhcEF0dHJzOiBmdW5jdGlvbiAoaHRtbCkge1xuICAgICAgJC5lYWNoKHRoaXMub3B0aW9ucy5hdHRyV3JhcCwgZnVuY3Rpb24gKGksIGEpIHtcbiAgICAgICAgaHRtbCA9IGh0bWwucmVwbGFjZShhICsgJz1cIicsICdfJyArIGEgKyAnPVwiJyk7XG4gICAgICB9KTtcbiAgICAgIHJldHVybiBodG1sO1xuICAgIH0sXG4gICAgdW53cmFwQXR0cnM6IGZ1bmN0aW9uIChodG1sKSB7XG4gICAgICAkLmVhY2godGhpcy5vcHRpb25zLmF0dHJXcmFwLCBmdW5jdGlvbiAoaSwgYSkge1xuICAgICAgICBodG1sID0gaHRtbC5yZXBsYWNlKCdfJyArIGEgKyAnPVwiJywgYSArICc9XCInKTtcbiAgICAgIH0pO1xuICAgICAgcmV0dXJuIGh0bWw7XG4gICAgfSxcbiAgICBkaXNOb25BY3RpdmVCdXR0b25zOiBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAodGhpcy5pc0luQ2xlYXJUZXh0QmxvY2soKSkge1xuICAgICAgICB0aGlzLiR0b29sYmFyLmZpbmQoXCIud3lzaWJiLXRvb2xiYXItYnRuOm5vdCgub24sLm1zd2l0Y2gpXCIpLmFkZENsYXNzKFwiZGlzXCIpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgdGhpcy4kdG9vbGJhci5maW5kKFwiLnd5c2liYi10b29sYmFyLWJ0bi5kaXNcIikucmVtb3ZlQ2xhc3MoXCJkaXNcIik7XG4gICAgICB9XG4gICAgfSxcbiAgICBzZXRDdXJzb3JCeUVsOiBmdW5jdGlvbiAoZWwpIHtcbiAgICAgIHZhciBzbCA9IGRvY3VtZW50LmNyZWF0ZVRleHROb2RlKFwiXFx1RkVGRlwiKTtcbiAgICAgICQoZWwpLmFmdGVyKHNsKTtcbiAgICAgIHRoaXMuc2VsZWN0Tm9kZShzbCk7XG4gICAgfSxcblxuICAgIC8vaW1nIGxpc3RlbmVyc1xuICAgIGltZ0xpc3RlbmVyczogZnVuY3Rpb24gKCkge1xuICAgICAgJChkb2N1bWVudCkub24oXCJtb3VzZWRvd25cIiwgJC5wcm94eSh0aGlzLmltZ0V2ZW50SGFuZGxlciwgdGhpcykpO1xuICAgIH0sXG4gICAgaW1nRXZlbnRIYW5kbGVyOiBmdW5jdGlvbiAoZSkge1xuICAgICAgdmFyICRlID0gJChlLnRhcmdldCk7XG4gICAgICBpZiAodGhpcy5oYXNXcmFwZWRJbWFnZSAmJiAoJGUuY2xvc2VzdChcIi53YmItaW1nLCN3YmJtb2RhbFwiKS5sZW5ndGggPT0gMCB8fCAkZS5oYXNDbGFzcyhcIndiYi1jYW5jZWwtYnV0dG9uXCIpKSkge1xuICAgICAgICB0aGlzLiRib2R5LmZpbmQoXCIuaW1nV3JhcCBcIikuZWFjaChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgJC5sb2coXCJSZW1vdmVkIGltZ1dyYXAgYmxvY2tcIik7XG4gICAgICAgICAgJCh0aGlzKS5yZXBsYWNlV2l0aCgkKHRoaXMpLmZpbmQoXCJpbWdcIikpO1xuICAgICAgICB9KVxuICAgICAgICB0aGlzLmhhc1dyYXBlZEltYWdlID0gZmFsc2U7XG4gICAgICAgIHRoaXMudXBkYXRlVUkoKTtcbiAgICAgIH1cblxuICAgICAgaWYgKCRlLmlzKFwiaW1nXCIpICYmICRlLmNsb3Nlc3QoXCIud3lzaWJiLWJvZHlcIikubGVuZ3RoID4gMCkge1xuICAgICAgICAkZS53cmFwKFwiPHNwYW4gY2xhc3M9J2ltZ1dyYXAnPjwvc3Bhbj5cIik7XG4gICAgICAgIHRoaXMuaGFzV3JhcGVkSW1hZ2UgPSAkZTtcbiAgICAgICAgdGhpcy4kYm9keS5mb2N1cygpO1xuICAgICAgICB0aGlzLnNlbGVjdE5vZGUoJGUucGFyZW50KClbMF0pO1xuICAgICAgfVxuICAgIH0sXG5cbiAgICAvL01PREFMIFdJTkRPV1xuICAgIHNob3dNb2RhbDogZnVuY3Rpb24gKGNtZCwgb3B0LCBxdWVyeVN0YXRlKSB7XG4gICAgICAkLmxvZyhcInNob3dNb2RhbDogXCIgKyBjbWQpO1xuICAgICAgdGhpcy5zYXZlUmFuZ2UoKTtcbiAgICAgIHZhciAkY29udCA9IHRoaXMuJG1vZGFsLmZpbmQoXCIud2JibS1jb250ZW50XCIpLmh0bWwoXCJcIik7XG4gICAgICB2YXIgJHdiYm0gPSB0aGlzLiRtb2RhbC5maW5kKFwiLndiYm1cIikucmVtb3ZlQ2xhc3MoXCJoYXN0YWJzXCIpO1xuICAgICAgdGhpcy4kbW9kYWwuZmluZChcInNwYW4ud2JibS10aXRsZS10ZXh0XCIpLmh0bWwob3B0LnRpdGxlKTtcbiAgICAgIGlmIChvcHQudGFicyAmJiBvcHQudGFicy5sZW5ndGggPiAxKSB7XG4gICAgICAgIC8vaGFzIHRhYnMsIGNyZWF0ZVxuICAgICAgICAkd2JibS5hZGRDbGFzcyhcImhhc3RhYnNcIik7XG4gICAgICAgIHZhciAkdWwgPSAkKCc8ZGl2IGNsYXNzPVwid2JibS10YWJsaXN0XCI+JykuYXBwZW5kVG8oJGNvbnQpLmFwcGVuZChcIjx1bD5cIikuY2hpbGRyZW4oXCJ1bFwiKTtcbiAgICAgICAgJC5lYWNoKG9wdC50YWJzLCAkLnByb3h5KGZ1bmN0aW9uIChpLCByb3cpIHtcbiAgICAgICAgICBpZiAoaSA9PSAwKSB7XG4gICAgICAgICAgICByb3dbJ29uJ10gPSBcIm9uXCJcbiAgICAgICAgICB9XG4gICAgICAgICAgJHVsLmFwcGVuZCh0aGlzLnN0cmYoJzxsaSBjbGFzcz1cIntvbn1cIiBvbkNsaWNrPVwiJCh0aGlzKS5wYXJlbnQoKS5maW5kKFxcJy5vblxcJykucmVtb3ZlQ2xhc3MoXFwnb25cXCcpOyQodGhpcykuYWRkQ2xhc3MoXFwnb25cXCcpOyQodGhpcykucGFyZW50cyhcXCcud2JibS1jb250ZW50XFwnKS5maW5kKFxcJy50YWItY29udFxcJykuaGlkZSgpOyQodGhpcykucGFyZW50cyhcXCcud2JibS1jb250ZW50XFwnKS5maW5kKFxcJy50YWInICsgaSArICdcXCcpLnNob3coKVwiPnt0aXRsZX08L2xpPicsIHJvdykpO1xuXG4gICAgICAgIH0sIHRoaXMpKVxuICAgICAgfVxuICAgICAgaWYgKG9wdC53aWR0aCkge1xuICAgICAgICAkd2JibS5jc3MoXCJ3aWR0aFwiLCBvcHQud2lkdGgpO1xuICAgICAgfVxuICAgICAgdmFyICRjbnQgPSAkKCc8ZGl2IGNsYXNzPVwid2JibS1jb250XCI+JykuYXBwZW5kVG8oJGNvbnQpO1xuICAgICAgaWYgKHF1ZXJ5U3RhdGUpIHtcbiAgICAgICAgJHdiYm0uZmluZCgnI3diYm0tcmVtb3ZlJykuc2hvdygpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgJHdiYm0uZmluZCgnI3diYm0tcmVtb3ZlJykuaGlkZSgpO1xuICAgICAgfVxuICAgICAgJC5lYWNoKG9wdC50YWJzLCAkLnByb3h5KGZ1bmN0aW9uIChpLCByKSB7XG4gICAgICAgIHZhciAkYyA9ICQoJzxkaXY+JykuYWRkQ2xhc3MoXCJ0YWItY29udCB0YWJcIiArIGkpLmF0dHIoXCJ0aWRcIiwgaSkuYXBwZW5kVG8oJGNudCk7XG4gICAgICAgIGlmIChpID4gMCkge1xuICAgICAgICAgICRjLmhpZGUoKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAoci5odG1sKSB7XG4gICAgICAgICAgJGMuaHRtbCh0aGlzLnN0cmYoci5odG1sLCB0aGlzLm9wdGlvbnMpKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAkLmVhY2goci5pbnB1dCwgJC5wcm94eShmdW5jdGlvbiAoaiwgaW5wKSB7XG4gICAgICAgICAgICBpbnBbXCJ2YWx1ZVwiXSA9IHF1ZXJ5U3RhdGVbaW5wLnBhcmFtLnRvTG93ZXJDYXNlKCldO1xuICAgICAgICAgICAgaWYgKGlucC5wYXJhbS50b0xvd2VyQ2FzZSgpID09IFwic2VsdGV4dFwiICYmICghaW5wW1widmFsdWVcIl0gfHwgaW5wW1widmFsdWVcIl0gPT0gXCJcIikpIHtcbiAgICAgICAgICAgICAgaW5wW1widmFsdWVcIl0gPSB0aGlzLmdldFNlbGVjdFRleHQodGhpcy5vcHRpb25zLmJibW9kZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoaW5wW1widmFsdWVcIl0gJiYgaW5wW1widmFsdWVcIl0uaW5kZXhPZihcIjxzcGFuIGlkPSd3YmJpZFwiKSA9PSAwICYmICQoaW5wW1widmFsdWVcIl0pLmlzKFwic3BhbltpZCo9J3diYmlkJ11cIikpIHtcbiAgICAgICAgICAgICAgaW5wW1widmFsdWVcIl0gPSAkKGlucFtcInZhbHVlXCJdKS5odG1sKCk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoaW5wLnR5cGUgJiYgaW5wLnR5cGUgPT0gXCJkaXZcIikge1xuICAgICAgICAgICAgICAvL2RpdiBpbnB1dCwgc3VwcG9ydCB3eXNpd3lnIGlucHV0XG4gICAgICAgICAgICAgICRjLmFwcGVuZCh0aGlzLnN0cmYoJzxkaXYgY2xhc3M9XCJ3YmJtLWlucC1yb3dcIj48bGFiZWw+e3RpdGxlfTwvbGFiZWw+PGRpdiBjbGFzcz1cImlucC10ZXh0IGRpdi1tb2RhbC10ZXh0XCIgY29udGVudGVkaXRhYmxlPVwidHJ1ZVwiIG5hbWU9XCJ7cGFyYW19XCI+e3ZhbHVlfTwvZGl2PjwvZGl2PicsIGlucCkpO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgLy9kZWZhdWx0IGlucHV0XG4gICAgICAgICAgICAgICRjLmFwcGVuZCh0aGlzLnN0cmYoJzxkaXYgY2xhc3M9XCJ3YmJtLWlucC1yb3dcIj48bGFiZWw+e3RpdGxlfTwvbGFiZWw+PGlucHV0IGNsYXNzPVwiaW5wLXRleHQgbW9kYWwtdGV4dFwiIHR5cGU9XCJ0ZXh0XCIgbmFtZT1cIntwYXJhbX1cIiB2YWx1ZT1cInt2YWx1ZX1cIi8+PC9kaXY+JywgaW5wKSk7XG4gICAgICAgICAgICB9XG5cblxuICAgICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgfVxuICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAvL3RoaXMubGFzdFJhbmdlPXRoaXMuZ2V0UmFuZ2UoKTtcblxuICAgICAgaWYgKCQuaXNGdW5jdGlvbihvcHQub25Mb2FkKSkge1xuICAgICAgICBvcHQub25Mb2FkLmNhbGwodGhpcywgY21kLCBvcHQsIHF1ZXJ5U3RhdGUpO1xuICAgICAgfVxuXG4gICAgICAkd2JibS5maW5kKCcjd2JibS1zdWJtaXQnKS5jbGljaygkLnByb3h5KGZ1bmN0aW9uICgpIHtcblxuICAgICAgICBpZiAoJC5pc0Z1bmN0aW9uKG9wdC5vblN1Ym1pdCkpIHsgLy9jdXN0b20gc3VibWl0IGZ1bmN0aW9uLCBpZiByZXR1cm4gZmFsc2UsIHRoZW4gZG9uJ3QgcHJvY2VzcyBvdXIgZnVuY3Rpb25cbiAgICAgICAgICB2YXIgciA9IG9wdC5vblN1Ym1pdC5jYWxsKHRoaXMsIGNtZCwgb3B0LCBxdWVyeVN0YXRlKTtcbiAgICAgICAgICBpZiAociA9PT0gZmFsc2UpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgdmFyIHBhcmFtcyA9IHt9O1xuICAgICAgICB2YXIgdmFsaWQgPSB0cnVlO1xuICAgICAgICB0aGlzLiRtb2RhbC5maW5kKFwiLndiYm0taW5wZXJyXCIpLnJlbW92ZSgpO1xuICAgICAgICB0aGlzLiRtb2RhbC5maW5kKFwiLndiYm0tYnJkcmVkXCIpLnJlbW92ZUNsYXNzKFwid2JibS1icmRyZWRcIik7XG4gICAgICAgIC8vJC5lYWNoKHRoaXMuJG1vZGFsLmZpbmQoXCIudGFiLWNvbnQ6dmlzaWJsZSBpbnB1dFwiKSwkLnByb3h5KGZ1bmN0aW9uKGksZWwpIHtcbiAgICAgICAgJC5lYWNoKHRoaXMuJG1vZGFsLmZpbmQoXCIudGFiLWNvbnQ6dmlzaWJsZSAuaW5wLXRleHRcIiksICQucHJveHkoZnVuY3Rpb24gKGksIGVsKSB7XG4gICAgICAgICAgdmFyIHRpZCA9ICQoZWwpLnBhcmVudHMoXCIudGFiLWNvbnRcIikuYXR0cihcInRpZFwiKTtcbiAgICAgICAgICB2YXIgcG5hbWUgPSAkKGVsKS5hdHRyKFwibmFtZVwiKS50b0xvd2VyQ2FzZSgpO1xuICAgICAgICAgIHZhciBwdmFsID0gXCJcIjtcbiAgICAgICAgICBpZiAoJChlbCkuaXMoXCJpbnB1dCx0ZXh0cmVhLHNlbGVjdFwiKSkge1xuICAgICAgICAgICAgcHZhbCA9ICQoZWwpLnZhbCgpO1xuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBwdmFsID0gJChlbCkuaHRtbCgpO1xuICAgICAgICAgIH1cbiAgICAgICAgICB2YXIgdmFsaWRhdGlvbiA9IG9wdC50YWJzW3RpZF1bXCJpbnB1dFwiXVtpXVtcInZhbGlkYXRpb25cIl07XG4gICAgICAgICAgaWYgKHR5cGVvZiAodmFsaWRhdGlvbikgIT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICAgICAgaWYgKCFwdmFsLm1hdGNoKG5ldyBSZWdFeHAodmFsaWRhdGlvbiwgXCJpXCIpKSkge1xuICAgICAgICAgICAgICB2YWxpZCA9IGZhbHNlO1xuICAgICAgICAgICAgICAkKGVsKS5hZnRlcignPHNwYW4gY2xhc3M9XCJ3YmJtLWlucGVyclwiPicgKyBDVVJMQU5HLnZhbGlkYXRpb25fZXJyICsgJzwvc3Bhbj4nKS5hZGRDbGFzcyhcIndiYm0tYnJkcmVkXCIpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cbiAgICAgICAgICBwYXJhbXNbcG5hbWVdID0gcHZhbDtcbiAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICBpZiAodmFsaWQpIHtcbiAgICAgICAgICAkLmxvZyhcIkxhc3QgcmFuZ2U6IFwiICsgdGhpcy5sYXN0UmFuZ2UpO1xuICAgICAgICAgIHRoaXMuc2VsZWN0TGFzdFJhbmdlKCk7XG4gICAgICAgICAgLy9pbnNlcnQgY2FsbGJhY2tcbiAgICAgICAgICBpZiAocXVlcnlTdGF0ZSkge1xuICAgICAgICAgICAgdGhpcy53YmJSZW1vdmVDYWxsYmFjayhjbWQsIHRydWUpO1xuICAgICAgICAgIH1cbiAgICAgICAgICB0aGlzLndiYkluc2VydENhbGxiYWNrKGNtZCwgcGFyYW1zKTtcbiAgICAgICAgICAvL0VORCBpbnNlcnQgY2FsbGJhY2tcblxuICAgICAgICAgIHRoaXMuY2xvc2VNb2RhbCgpO1xuICAgICAgICAgIHRoaXMudXBkYXRlVUkoKTtcbiAgICAgICAgfVxuICAgICAgfSwgdGhpcykpO1xuICAgICAgJHdiYm0uZmluZCgnI3diYm0tcmVtb3ZlJykuY2xpY2soJC5wcm94eShmdW5jdGlvbiAoKSB7XG4gICAgICAgIC8vY2xiay5yZW1vdmUoKTtcbiAgICAgICAgdGhpcy5zZWxlY3RMYXN0UmFuZ2UoKTtcbiAgICAgICAgdGhpcy53YmJSZW1vdmVDYWxsYmFjayhjbWQpOyAvL3JlbW92ZSBjYWxsYmFja1xuICAgICAgICB0aGlzLmNsb3NlTW9kYWwoKTtcbiAgICAgICAgdGhpcy51cGRhdGVVSSgpO1xuICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAkKGRvY3VtZW50LmJvZHkpLmNzcyhcIm92ZXJmbG93XCIsIFwiaGlkZGVuXCIpOyAvL2xvY2sgdGhlIHNjcmVlbiwgcmVtb3ZlIHNjcm9sbCBvbiBib2R5XG4gICAgICBpZiAoJChcImJvZHlcIikuaGVpZ2h0KCkgPiAkKHdpbmRvdykuaGVpZ2h0KCkpIHsgLy9pZiBib2R5IGhhcyBzY3JvbGwsIGFkZCBwYWRkaW5nLXJpZ2h0IDE4cHhcbiAgICAgICAgJChkb2N1bWVudC5ib2R5KS5jc3MoXCJwYWRkaW5nLXJpZ2h0XCIsIFwiMThweFwiKTtcbiAgICAgIH1cbiAgICAgIHRoaXMuJG1vZGFsLnNob3coKTtcbiAgICAgIC8vaWYgKHdpbmRvdy5nZXRTZWxlY3Rpb24pXG4gICAgICBpZiAodGhpcy5pc01vYmlsZSkge1xuICAgICAgICAkd2JibS5jc3MoXCJtYXJnaW4tdG9wXCIsIFwiMTBweFwiKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgICR3YmJtLmNzcyhcIm1hcmdpbi10b3BcIiwgKCQod2luZG93KS5oZWlnaHQoKSAtICR3YmJtLm91dGVySGVpZ2h0KCkpIC8gMyArIFwicHhcIik7XG4gICAgICB9XG4gICAgICAvL3NldFRpbWVvdXQoJC5wcm94eShmdW5jdGlvbigpIHt0aGlzLiRtb2RhbC5maW5kKFwiaW5wdXQ6dmlzaWJsZVwiKVswXS5mb2N1cygpfSx0aGlzKSwxMCk7XG4gICAgICBzZXRUaW1lb3V0KCQucHJveHkoZnVuY3Rpb24gKCkge1xuICAgICAgICB0aGlzLiRtb2RhbC5maW5kKFwiLmlucC10ZXh0OnZpc2libGVcIilbMF0uZm9jdXMoKVxuICAgICAgfSwgdGhpcyksIDEwKTtcbiAgICB9LFxuICAgIGVzY01vZGFsOiBmdW5jdGlvbiAoZSkge1xuICAgICAgaWYgKGUud2hpY2ggPT0gMjcpIHtcbiAgICAgICAgdGhpcy5jbG9zZU1vZGFsKCk7XG4gICAgICB9XG4gICAgfSxcbiAgICBjbG9zZU1vZGFsOiBmdW5jdGlvbiAoKSB7XG4gICAgICAkKGRvY3VtZW50LmJvZHkpLmNzcyhcIm92ZXJmbG93XCIsIFwiYXV0b1wiKS5jc3MoXCJwYWRkaW5nLXJpZ2h0XCIsIFwiMFwiKS51bmJpbmQoXCJrZXl1cFwiLCB0aGlzLmVzY01vZGFsKTsgLy9FU0Mga2V5IGNsb3NlIG1vZGFsO1xuICAgICAgdGhpcy4kbW9kYWwuZmluZCgnI3diYm0tc3VibWl0LCN3YmJtLXJlbW92ZScpLnVuYmluZCgnY2xpY2snKTtcbiAgICAgIHRoaXMuJG1vZGFsLmhpZGUoKTtcbiAgICAgIHRoaXMubGFzdFJhbmdlID0gZmFsc2U7XG4gICAgICByZXR1cm4gdGhpcztcbiAgICB9LFxuICAgIGdldFBhcmFtczogZnVuY3Rpb24gKHNyYywgcywgb2Zmc2V0KSB7XG4gICAgICB2YXIgcGFyYW1zID0ge307XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmJibW9kZSkge1xuICAgICAgICAvL2JibW9kZVxuICAgICAgICB2YXIgc3RleHQgPSBzLm1hdGNoKC9cXHtbXFxzXFxTXSs/XFx9L2cpO1xuICAgICAgICBzID0gdGhpcy5wcmVwYXJlUkdYKHMpO1xuICAgICAgICB2YXIgcmd4ID0gbmV3IFJlZ0V4cChzLCBcImdcIik7XG4gICAgICAgIHZhciB2YWwgPSB0aGlzLnR4dEFyZWEudmFsdWU7XG4gICAgICAgIGlmIChvZmZzZXQgPiAwKSB7XG4gICAgICAgICAgdmFsID0gdmFsLnN1YnN0cihvZmZzZXQsIHZhbC5sZW5ndGggLSBvZmZzZXQpO1xuICAgICAgICB9XG4gICAgICAgIHZhciBhID0gcmd4LmV4ZWModmFsKTtcbiAgICAgICAgaWYgKGEpIHtcbiAgICAgICAgICAkLmVhY2goc3RleHQsIGZ1bmN0aW9uIChpLCBuKSB7XG4gICAgICAgICAgICBwYXJhbXNbbi5yZXBsYWNlKC9cXHt8XFx9L2csIFwiXCIpLnJlcGxhY2UoL1wiL2csIFwiJ1wiKS50b0xvd2VyQ2FzZSgpXSA9IGFbaSArIDFdO1xuICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgICB9IGVsc2Uge1xuICAgICAgICB2YXIgcnVsZXMgPSB0aGlzLm9wdGlvbnMucnVsZXNbc11bMF1bMV07XG4gICAgICAgICQuZWFjaChydWxlcywgJC5wcm94eShmdW5jdGlvbiAoaywgdikge1xuICAgICAgICAgIHZhciB2YWx1ZSA9IFwiXCI7XG4gICAgICAgICAgdmFyICR2ID0gKHYuc2VsICE9PSBmYWxzZSkgPyB2YWx1ZSA9ICQoc3JjKS5maW5kKHYuc2VsKSA6ICQoc3JjKTtcbiAgICAgICAgICBpZiAodi5hdHRyICE9PSBmYWxzZSkge1xuICAgICAgICAgICAgdmFsdWUgPSAkdi5hdHRyKHYuYXR0cik7XG4gICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHZhbHVlID0gJHYuaHRtbCgpO1xuICAgICAgICAgIH1cbiAgICAgICAgICBpZiAodmFsdWUpIHtcbiAgICAgICAgICAgIGlmICh2LnJneCAhPT0gZmFsc2UpIHtcbiAgICAgICAgICAgICAgdmFyIG0gPSB2YWx1ZS5tYXRjaChuZXcgUmVnRXhwKHYucmd4KSk7XG4gICAgICAgICAgICAgIGlmIChtICYmIG0ubGVuZ3RoID09IDIpIHtcbiAgICAgICAgICAgICAgICB2YWx1ZSA9IG1bMV07XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHBhcmFtc1trXSA9IHZhbHVlLnJlcGxhY2UoL1wiL2csIFwiJ1wiKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0sIHRoaXMpKVxuICAgICAgfVxuICAgICAgcmV0dXJuIHBhcmFtcztcbiAgICB9LFxuXG5cbiAgICAvL2ltZ1VwbG9hZGVyXG4gICAgaW1nTG9hZE1vZGFsOiBmdW5jdGlvbiAoKSB7XG4gICAgICAkLmxvZyhcImltZ0xvYWRNb2RhbFwiKTtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuaW1ndXBsb2FkID09PSB0cnVlKSB7XG4gICAgICAgIHRoaXMuJG1vZGFsLmZpbmQoXCIjaW1ndXBsb2FkZXJcIikuZHJhZ2ZpbGV1cGxvYWQoe1xuICAgICAgICAgIHVybDogdGhpcy5zdHJmKHRoaXMub3B0aW9ucy5pbWdfdXBsb2FkdXJsLCB0aGlzLm9wdGlvbnMpLFxuICAgICAgICAgIGV4dHJhUGFyYW1zOiB7XG4gICAgICAgICAgICBtYXh3aWR0aDogdGhpcy5vcHRpb25zLmltZ19tYXh3aWR0aCxcbiAgICAgICAgICAgIG1heGhlaWdodDogdGhpcy5vcHRpb25zLmltZ19tYXhoZWlnaHRcbiAgICAgICAgICB9LFxuICAgICAgICAgIHRoZW1lUHJlZml4OiB0aGlzLm9wdGlvbnMudGhlbWVQcmVmaXgsXG4gICAgICAgICAgdGhlbWVOYW1lOiB0aGlzLm9wdGlvbnMudGhlbWVOYW1lLFxuICAgICAgICAgIHN1Y2Nlc3M6ICQucHJveHkoZnVuY3Rpb24gKGRhdGEpIHtcbiAgICAgICAgICAgIHRoaXMuJHR4dEFyZWEuaW5zZXJ0SW1hZ2UoZGF0YS5pbWFnZV9saW5rLCBkYXRhLnRodW1iX2xpbmspO1xuXG4gICAgICAgICAgICB0aGlzLmNsb3NlTW9kYWwoKTtcbiAgICAgICAgICAgIHRoaXMudXBkYXRlVUkoKTtcbiAgICAgICAgICB9LCB0aGlzKVxuICAgICAgICB9KTtcblxuICAgICAgICB0aGlzLiRtb2RhbC5maW5kKFwiI2ZpbGV1cGxcIikuYmluZChcImNoYW5nZVwiLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgJChcIiNmdXBmb3JtXCIpLnN1Ym1pdCgpO1xuICAgICAgICB9KTtcbiAgICAgICAgdGhpcy4kbW9kYWwuZmluZChcIiNmdXBmb3JtXCIpLmJpbmQoXCJzdWJtaXRcIiwgJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICAgICQoZS50YXJnZXQpLnBhcmVudHMoXCIjaW1ndXBsb2FkZXJcIikuaGlkZSgpLmFmdGVyKCc8ZGl2IGNsYXNzPVwibG9hZGVyXCI+PGltZyBzcmM9XCInICsgdGhpcy5vcHRpb25zLnRoZW1lUHJlZml4ICsgJy8nICsgdGhpcy5vcHRpb25zLnRoZW1lTmFtZSArICcvaW1nL2xvYWRlci5naWZcIiAvPjxici8+PHNwYW4+JyArIENVUkxBTkcubG9hZGluZyArICc8L3NwYW4+PC9kaXY+JykucGFyZW50KCkuY3NzKFwidGV4dC1hbGlnblwiLCBcImNlbnRlclwiKTtcbiAgICAgICAgfSwgdGhpcykpXG5cbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHRoaXMuJG1vZGFsLmZpbmQoXCIuaGFzdGFic1wiKS5yZW1vdmVDbGFzcyhcImhhc3RhYnNcIik7XG4gICAgICAgIHRoaXMuJG1vZGFsLmZpbmQoXCIjaW1ndXBsb2FkZXJcIikucGFyZW50cyhcIi50YWItY29udFwiKS5yZW1vdmUoKTtcbiAgICAgICAgdGhpcy4kbW9kYWwuZmluZChcIi53YmJtLXRhYmxpc3RcIikucmVtb3ZlKCk7XG4gICAgICB9XG4gICAgfSxcbiAgICBpbWdTdWJtaXRNb2RhbDogZnVuY3Rpb24gKCkge1xuICAgICAgJC5sb2coXCJpbWdTdWJtaXRNb2RhbFwiKTtcbiAgICB9LFxuICAgIC8vREVCVUdcbiAgICBwcmludE9iamVjdEluSUU6IGZ1bmN0aW9uIChvYmopIHtcbiAgICAgIHRyeSB7XG4gICAgICAgICQubG9nKEpTT04uc3RyaW5naWZ5KG9iaikpO1xuICAgICAgfSBjYXRjaCAoZSkge1xuICAgICAgfVxuICAgIH0sXG4gICAgY2hlY2tGaWx0ZXI6IGZ1bmN0aW9uIChub2RlLCBmaWx0ZXIpIHtcbiAgICAgICQubG9nKFwibm9kZTogXCIgKyAkKG5vZGUpLmdldCgwKS5vdXRlckhUTUwgKyBcIiBmaWx0ZXI6IFwiICsgZmlsdGVyICsgXCIgcmVzOiBcIiArICQobm9kZSkuaXMoZmlsdGVyLnRvTG93ZXJDYXNlKCkpKTtcbiAgICB9LFxuICAgIGRlYnVnOiBmdW5jdGlvbiAobXNnKSB7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmRlYnVnID09PSB0cnVlKSB7XG4gICAgICAgIHZhciB0aW1lID0gKG5ldyBEYXRlKCkpLmdldFRpbWUoKTtcbiAgICAgICAgaWYgKHR5cGVvZiAoY29uc29sZSkgIT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICAgIGNvbnNvbGUubG9nKCh0aW1lIC0gdGhpcy5zdGFydFRpbWUpICsgXCIgbXM6IFwiICsgbXNnKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAkKFwiI2V4bG9nXCIpLmFwcGVuZCgnPHA+JyArICh0aW1lIC0gdGhpcy5zdGFydFRpbWUpICsgXCIgbXM6IFwiICsgbXNnICsgJzwvcD4nKTtcbiAgICAgICAgfVxuICAgICAgICB0aGlzLnN0YXJ0VGltZSA9IHRpbWU7XG4gICAgICB9XG4gICAgfSxcblxuICAgIC8vQnJvd3NlciBmaXhlc1xuICAgIGlzQ2hyb21lOiBmdW5jdGlvbiAoKSB7XG4gICAgICByZXR1cm4gKHdpbmRvdy5jaHJvbWUpID8gdHJ1ZSA6IGZhbHNlO1xuICAgIH0sXG4gICAgZml4VGFibGVUcmFuc2Zvcm06IGZ1bmN0aW9uIChodG1sKSB7XG4gICAgICBpZiAoIWh0bWwpIHtcbiAgICAgICAgcmV0dXJuIFwiXCI7XG4gICAgICB9XG4gICAgICBpZiAoJC5pbkFycmF5KFwidGFibGVcIiwgdGhpcy5vcHRpb25zLmJ1dHRvbnMpID09IC0xKSB7XG4gICAgICAgIHJldHVybiBodG1sLnJlcGxhY2UoL1xcPChcXC8qPyh0YWJsZXx0cnx0ZHx0Ym9keSkpW14+XSpcXD4vaWcsIFwiXCIpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgcmV0dXJuIGh0bWwucmVwbGFjZSgvXFw8KFxcLyo/KHRhYmxlfHRyfHRkKSlbXj5dKlxcPi9pZywgXCJbJDFdXCIudG9Mb3dlckNhc2UoKSkucmVwbGFjZSgvXFw8XFwvKnRib2R5W14+XSpcXD4vaWcsIFwiXCIpO1xuICAgICAgfVxuICAgIH1cbiAgfVxuXG4gICQubG9nID0gZnVuY3Rpb24gKG1zZykge1xuICAgIGlmICh0eXBlb2YgKHdiYmRlYnVnKSAhPSBcInVuZGVmaW5lZFwiICYmIHdiYmRlYnVnID09PSB0cnVlKSB7XG4gICAgICBpZiAodHlwZW9mIChjb25zb2xlKSAhPSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICAgIGNvbnNvbGUubG9nKG1zZyk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICAkKFwiI2V4bG9nXCIpLmFwcGVuZCgnPHA+JyArIG1zZyArICc8L3A+Jyk7XG4gICAgICB9XG4gICAgfVxuICB9XG4gICQuZm4ud3lzaWJiID0gZnVuY3Rpb24gKHNldHRpbmdzKSB7XG4gICAgcmV0dXJuIHRoaXMuZWFjaChmdW5jdGlvbiAoKSB7XG4gICAgICB2YXIgZGF0YSA9ICQodGhpcykuZGF0YShcIndiYlwiKTtcbiAgICAgIGlmICghZGF0YSkge1xuICAgICAgICBuZXcgJC53eXNpYmIodGhpcywgc2V0dGluZ3MpO1xuICAgICAgfVxuICAgIH0pO1xuICB9XG4gICQuZm4ud2RyYWcgPSBmdW5jdGlvbiAob3B0KSB7XG4gICAgaWYgKCFvcHQuc2NvcGUpIHtcbiAgICAgIG9wdC5zY29wZSA9IHRoaXM7XG4gICAgfVxuICAgIHZhciBzdGFydCA9IHt4OiAwLCB5OiAwLCBoZWlnaHQ6IDB9O1xuICAgIHZhciBkcmFnO1xuICAgIG9wdC5zY29wZS5kcmFnX21vdXNlZG93biA9IGZ1bmN0aW9uIChlKSB7XG4gICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICBzdGFydCA9IHtcbiAgICAgICAgeDogZS5wYWdlWCxcbiAgICAgICAgeTogZS5wYWdlWSxcbiAgICAgICAgaGVpZ2h0OiBvcHQuaGVpZ2h0LFxuICAgICAgICBzaGVpZ2h0OiBvcHQuc2NvcGUuJGJvZHkuaGVpZ2h0KClcbiAgICAgIH1cbiAgICAgIGRyYWcgPSB0cnVlO1xuICAgICAgJChkb2N1bWVudCkuYmluZChcIm1vdXNlbW92ZVwiLCAkLnByb3h5KG9wdC5zY29wZS5kcmFnX21vdXNlbW92ZSwgdGhpcykpO1xuICAgICAgJCh0aGlzKS5hZGRDbGFzcyhcImRyYWdcIik7XG4gICAgfTtcbiAgICBvcHQuc2NvcGUuZHJhZ19tb3VzZXVwID0gZnVuY3Rpb24gKGUpIHtcbiAgICAgIGlmIChkcmFnID09PSB0cnVlKSB7XG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgJChkb2N1bWVudCkudW5iaW5kKFwibW91c2Vtb3ZlXCIsIG9wdC5zY29wZS5kcmFnX21vdXNlbW92ZSk7XG4gICAgICAgICQodGhpcykucmVtb3ZlQ2xhc3MoXCJkcmFnXCIpO1xuICAgICAgICBkcmFnID0gZmFsc2U7XG4gICAgICB9XG4gICAgfTtcbiAgICBvcHQuc2NvcGUuZHJhZ19tb3VzZW1vdmUgPSBmdW5jdGlvbiAoZSkge1xuICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgdmFyIGF4aXNYID0gMCwgYXhpc1kgPSAwO1xuICAgICAgaWYgKG9wdC5heGlzWCkge1xuICAgICAgICBheGlzWCA9IGUucGFnZVggLSBzdGFydC54O1xuICAgICAgfVxuICAgICAgaWYgKG9wdC5heGlzWSkge1xuICAgICAgICBheGlzWSA9IGUucGFnZVkgLSBzdGFydC55O1xuICAgICAgfVxuICAgICAgaWYgKGF4aXNZICE9IDApIHtcbiAgICAgICAgdmFyIG5oZWlnaHQgPSBzdGFydC5zaGVpZ2h0ICsgYXhpc1k7XG4gICAgICAgIGlmIChuaGVpZ2h0ID4gc3RhcnQuaGVpZ2h0ICYmIG5oZWlnaHQgPD0gb3B0LnNjb3BlLm9wdGlvbnMucmVzaXplX21heGhlaWdodCkge1xuICAgICAgICAgIGlmIChvcHQuc2NvcGUub3B0aW9ucy5iYm1vZGUgPT0gdHJ1ZSkge1xuICAgICAgICAgICAgb3B0LnNjb3BlLiR0eHRBcmVhLmNzcygob3B0LnNjb3BlLm9wdGlvbnMuYXV0b3Jlc2l6ZSA9PT0gdHJ1ZSkgPyBcIm1pbi1oZWlnaHRcIiA6IFwiaGVpZ2h0XCIsIG5oZWlnaHQgKyBcInB4XCIpO1xuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBvcHQuc2NvcGUuJGJvZHkuY3NzKChvcHQuc2NvcGUub3B0aW9ucy5hdXRvcmVzaXplID09PSB0cnVlKSA/IFwibWluLWhlaWdodFwiIDogXCJoZWlnaHRcIiwgbmhlaWdodCArIFwicHhcIik7XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9XG4gICAgfTtcblxuXG4gICAgJCh0aGlzKS5iaW5kKFwibW91c2Vkb3duXCIsIG9wdC5zY29wZS5kcmFnX21vdXNlZG93bik7XG4gICAgJChkb2N1bWVudCkuYmluZChcIm1vdXNldXBcIiwgJC5wcm94eShvcHQuc2NvcGUuZHJhZ19tb3VzZXVwLCB0aGlzKSk7XG4gIH0sXG5cbiAgICAvL0FQSVxuICAgICQuZm4uZ2V0RG9jID0gZnVuY3Rpb24gKCkge1xuICAgICAgcmV0dXJuIHRoaXMuZGF0YSgnd2JiJykuZG9jO1xuICAgIH1cbiAgJC5mbi5nZXRTZWxlY3RUZXh0ID0gZnVuY3Rpb24gKGZyb21UZXh0QXJlYSkge1xuICAgIHJldHVybiB0aGlzLmRhdGEoJ3diYicpLmdldFNlbGVjdFRleHQoZnJvbVRleHRBcmVhKTtcbiAgfVxuICAkLmZuLmJiY29kZSA9IGZ1bmN0aW9uIChkYXRhKSB7XG4gICAgaWYgKHR5cGVvZiAoZGF0YSkgIT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgaWYgKHRoaXMuZGF0YSgnd2JiJykub3B0aW9ucy5iYm1vZGUpIHtcbiAgICAgICAgdGhpcy5kYXRhKCd3YmInKS4kdHh0QXJlYS52YWwoZGF0YSk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICB0aGlzLmRhdGEoJ3diYicpLiRib2R5Lmh0bWwodGhpcy5kYXRhKFwid2JiXCIpLmdldEhUTUwoZGF0YSkpO1xuICAgICAgfVxuICAgICAgcmV0dXJuIHRoaXM7XG4gICAgfSBlbHNlIHtcbiAgICAgIHJldHVybiB0aGlzLmRhdGEoJ3diYicpLmdldEJCQ29kZSgpO1xuICAgIH1cbiAgfVxuICAkLmZuLmh0bWxjb2RlID0gZnVuY3Rpb24gKGRhdGEpIHtcbiAgICBpZiAoIXRoaXMuZGF0YSgnd2JiJykub3B0aW9ucy5vbmx5QkJNb2RlICYmIHRoaXMuZGF0YSgnd2JiJykuaW5pdGVkID09PSB0cnVlKSB7XG4gICAgICBpZiAodHlwZW9mIChkYXRhKSAhPSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICAgIHRoaXMuZGF0YSgnd2JiJykuJGJvZHkuaHRtbChkYXRhKTtcbiAgICAgICAgcmV0dXJuIHRoaXM7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICByZXR1cm4gdGhpcy5kYXRhKCd3YmInKS5nZXRIVE1MKHRoaXMuZGF0YSgnd2JiJykuJHR4dEFyZWEudmFsKCkpO1xuICAgICAgfVxuICAgIH1cbiAgfVxuICAkLmZuLmdldEJCQ29kZSA9IGZ1bmN0aW9uICgpIHtcbiAgICByZXR1cm4gdGhpcy5kYXRhKCd3YmInKS5nZXRCQkNvZGUoKTtcbiAgfVxuICAkLmZuLmdldEhUTUwgPSBmdW5jdGlvbiAoKSB7XG4gICAgdmFyIHdiYiA9IHRoaXMuZGF0YSgnd2JiJyk7XG4gICAgcmV0dXJuIHdiYi5nZXRIVE1MKHdiYi4kdHh0QXJlYS52YWwoKSk7XG4gIH1cbiAgJC5mbi5nZXRIVE1MQnlDb21tYW5kID0gZnVuY3Rpb24gKGNvbW1hbmQsIHBhcmFtcykge1xuICAgIHJldHVybiB0aGlzLmRhdGEoXCJ3YmJcIikuZ2V0SFRNTEJ5Q29tbWFuZChjb21tYW5kLCBwYXJhbXMpO1xuICB9XG4gICQuZm4uZ2V0QkJDb2RlQnlDb21tYW5kID0gZnVuY3Rpb24gKGNvbW1hbmQsIHBhcmFtcykge1xuICAgIHJldHVybiB0aGlzLmRhdGEoXCJ3YmJcIikuZ2V0QkJDb2RlQnlDb21tYW5kKGNvbW1hbmQsIHBhcmFtcyk7XG4gIH1cbiAgJC5mbi5pbnNlcnRBdEN1cnNvciA9IGZ1bmN0aW9uIChkYXRhLCBmb3JjZUJCTW9kZSkge1xuICAgIHRoaXMuZGF0YShcIndiYlwiKS5pbnNlcnRBdEN1cnNvcihkYXRhLCBmb3JjZUJCTW9kZSk7XG4gICAgcmV0dXJuIHRoaXMuZGF0YShcIndiYlwiKTtcbiAgfVxuICAkLmZuLmV4ZWNDb21tYW5kID0gZnVuY3Rpb24gKGNvbW1hbmQsIHZhbHVlKSB7XG4gICAgdGhpcy5kYXRhKFwid2JiXCIpLmV4ZWNDb21tYW5kKGNvbW1hbmQsIHZhbHVlKTtcbiAgICByZXR1cm4gdGhpcy5kYXRhKFwid2JiXCIpO1xuICB9XG4gICQuZm4uaW5zZXJ0SW1hZ2UgPSBmdW5jdGlvbiAoaW1ndXJsLCB0aHVtYnVybCkge1xuICAgIHZhciBlZGl0b3IgPSB0aGlzLmRhdGEoXCJ3YmJcIik7XG4gICAgdmFyIGNvZGUgPSAodGh1bWJ1cmwpID8gZWRpdG9yLmdldENvZGVCeUNvbW1hbmQoJ2xpbmsnLCB7dXJsOiBpbWd1cmwsIHNlbHRleHQ6IGVkaXRvci5nZXRDb2RlQnlDb21tYW5kKCdpbWcnLCB7c3JjOiB0aHVtYnVybH0pfSkgOiBlZGl0b3IuZ2V0Q29kZUJ5Q29tbWFuZCgnaW1nJywge3NyYzogaW1ndXJsfSk7XG4gICAgdGhpcy5pbnNlcnRBdEN1cnNvcihjb2RlKTtcbiAgICByZXR1cm4gZWRpdG9yO1xuICB9XG4gICQuZm4uc3luYyA9IGZ1bmN0aW9uICgpIHtcbiAgICB0aGlzLmRhdGEoXCJ3YmJcIikuc3luYygpO1xuICAgIHJldHVybiB0aGlzLmRhdGEoXCJ3YmJcIik7XG4gIH1cbiAgJC5mbi5kZXN0cm95ID0gZnVuY3Rpb24gKCkge1xuICAgIHRoaXMuZGF0YShcIndiYlwiKS5kZXN0cm95KCk7XG4gIH1cblxuXG4gICQuZm4ucXVlcnlTdGF0ZSA9IGZ1bmN0aW9uIChjb21tYW5kKSB7XG4gICAgcmV0dXJuIHRoaXMuZGF0YShcIndiYlwiKS5xdWVyeVN0YXRlKGNvbW1hbmQpO1xuICB9XG59KShqUXVlcnkpO1xuXG5cbi8vRHJhZyZEcm9wIGZpbGUgdXBsb2FkZXJcbihmdW5jdGlvbiAoJCkge1xuICAndXNlIHN0cmljdCc7XG5cbiAgJC5mbi5kcmFnZmlsZXVwbG9hZCA9IGZ1bmN0aW9uIChvcHRpb25zKSB7XG4gICAgcmV0dXJuIHRoaXMuZWFjaChmdW5jdGlvbiAoKSB7XG4gICAgICB2YXIgdXBsID0gbmV3IEZpbGVVcGxvYWQodGhpcywgb3B0aW9ucyk7XG4gICAgICB1cGwuaW5pdCgpO1xuICAgIH0pO1xuICB9O1xuXG4gIGZ1bmN0aW9uIEZpbGVVcGxvYWQoZSwgb3B0aW9ucylcbiAge1xuICAgIHRoaXMuJGJsb2NrID0gJChlKTtcblxuICAgIHRoaXMub3B0ID0gJC5leHRlbmQoe1xuICAgICAgdXJsOiBmYWxzZSxcbiAgICAgIHN1Y2Nlc3M6IGZhbHNlLFxuICAgICAgZXh0cmFQYXJhbXM6IGZhbHNlLFxuICAgICAgZmlsZVBhcmFtOiAnaW1nJyxcbiAgICAgIHZhbGlkYXRpb246ICdcXC4oanBnfHBuZ3xnaWZ8anBlZykkJyxcblxuICAgICAgdDE6IENVUkxBTkcuZmlsZXVwbG9hZF90ZXh0MSxcbiAgICAgIHQyOiBDVVJMQU5HLmZpbGV1cGxvYWRfdGV4dDJcbiAgICB9LCBvcHRpb25zKTtcbiAgfVxuXG4gIEZpbGVVcGxvYWQucHJvdG90eXBlID0ge1xuICAgIGluaXQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICh3aW5kb3cuRm9ybURhdGEgIT0gbnVsbCkge1xuICAgICAgICB0aGlzLiRibG9jay5hZGRDbGFzcyhcImRyYWdcIik7XG4gICAgICAgIHRoaXMuJGJsb2NrLnByZXBlbmQoJzxkaXYgY2xhc3M9XCJwMlwiPicgKyB0aGlzLm9wdC50MiArICc8L2Rpdj4nKTtcbiAgICAgICAgdGhpcy4kYmxvY2sucHJlcGVuZCgnPGRpdiBjbGFzcz1cInBcIj4nICsgdGhpcy5vcHQudDEgKyAnPC9kaXY+Jyk7XG5cbiAgICAgICAgdGhpcy4kYmxvY2suYmluZCgnZHJhZ292ZXInLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgJCh0aGlzKS5hZGRDbGFzcygnZHJhZ292ZXInKTtcbiAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH0pO1xuICAgICAgICB0aGlzLiRibG9jay5iaW5kKCdkcmFnbGVhdmUnLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgJCh0aGlzKS5yZW1vdmVDbGFzcygnZHJhZ292ZXInKTtcbiAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIC8vdXBsb2FkIHByb2dyZXNzXG4gICAgICAgIHZhciB1cGxvYWRQcm9ncmVzcyA9ICQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICB2YXIgcCA9IHBhcnNlSW50KGUubG9hZGVkIC8gZS50b3RhbCAqIDEwMCwgMTApO1xuICAgICAgICAgIHRoaXMuJGxvYWRlci5jaGlsZHJlbihcInNwYW5cIikudGV4dChDVVJMQU5HLmxvYWRpbmcgKyAnOiAnICsgcCArICclJyk7XG5cbiAgICAgICAgfSwgdGhpcyk7XG4gICAgICAgIHZhciB4aHIgPSBqUXVlcnkuYWpheFNldHRpbmdzLnhocigpO1xuICAgICAgICBpZiAoeGhyLnVwbG9hZCkge1xuICAgICAgICAgIHhoci51cGxvYWQuYWRkRXZlbnRMaXN0ZW5lcigncHJvZ3Jlc3MnLCB1cGxvYWRQcm9ncmVzcywgZmFsc2UpO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMuJGJsb2NrWzBdLm9uZHJvcCA9ICQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgdGhpcy4kYmxvY2sucmVtb3ZlQ2xhc3MoJ2RyYWdvdmVyJyk7XG4gICAgICAgICAgdmFyIHVmaWxlID0gZS5kYXRhVHJhbnNmZXIuZmlsZXNbMF07XG4gICAgICAgICAgaWYgKHRoaXMub3B0LnZhbGlkYXRpb24gJiYgIXVmaWxlLm5hbWUubWF0Y2gobmV3IFJlZ0V4cCh0aGlzLm9wdC52YWxpZGF0aW9uKSkpIHtcbiAgICAgICAgICAgIHRoaXMuZXJyb3IoQ1VSTEFORy52YWxpZGF0aW9uX2Vycik7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgfVxuICAgICAgICAgIHZhciBmRGF0YSA9IG5ldyBGb3JtRGF0YSgpO1xuICAgICAgICAgIGZEYXRhLmFwcGVuZCh0aGlzLm9wdC5maWxlUGFyYW0sIHVmaWxlKTtcblxuICAgICAgICAgIGlmICh0aGlzLm9wdC5leHRyYVBhcmFtcykgeyAvL2NoZWNrIGZvciBleHRyYVBhcmFtcyB0byB1cGxvYWRcbiAgICAgICAgICAgICQuZWFjaCh0aGlzLm9wdC5leHRyYVBhcmFtcywgZnVuY3Rpb24gKGssIHYpIHtcbiAgICAgICAgICAgICAgZkRhdGEuYXBwZW5kKGssIHYpO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgdGhpcy4kbG9hZGVyID0gJCgnPGRpdiBjbGFzcz1cImxvYWRlclwiPjxpbWcgc3JjPVwiJyArIHRoaXMub3B0LnRoZW1lUHJlZml4ICsgJy8nICsgdGhpcy5vcHQudGhlbWVOYW1lICsgJy9pbWcvbG9hZGVyLmdpZlwiIC8+PGJyLz48c3Bhbj4nICsgQ1VSTEFORy5sb2FkaW5nICsgJzwvc3Bhbj48L2Rpdj4nKTtcbiAgICAgICAgICB0aGlzLiRibG9jay5odG1sKHRoaXMuJGxvYWRlcik7XG5cbiAgICAgICAgICAkLmFqYXgoe1xuICAgICAgICAgICAgdHlwZTogJ1BPU1QnLFxuICAgICAgICAgICAgdXJsOiB0aGlzLm9wdC51cmwsXG4gICAgICAgICAgICBkYXRhOiBmRGF0YSxcbiAgICAgICAgICAgIHByb2Nlc3NEYXRhOiBmYWxzZSxcbiAgICAgICAgICAgIGNvbnRlbnRUeXBlOiBmYWxzZSxcbiAgICAgICAgICAgIHhocjogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICByZXR1cm4geGhyXG4gICAgICAgICAgICB9LFxuICAgICAgICAgICAgZGF0YVR5cGU6ICdqc29uJyxcbiAgICAgICAgICAgIHN1Y2Nlc3M6ICQucHJveHkoZnVuY3Rpb24gKGRhdGEpIHtcbiAgICAgICAgICAgICAgaWYgKGRhdGEgJiYgZGF0YS5zdGF0dXMgPT0gMSkge1xuICAgICAgICAgICAgICAgIHRoaXMub3B0LnN1Y2Nlc3MoZGF0YSk7XG4gICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgdGhpcy5lcnJvcihkYXRhLm1zZyB8fCBDVVJMQU5HLmVycm9yX29udXBsb2FkKTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSwgdGhpcyksXG4gICAgICAgICAgICBlcnJvcjogJC5wcm94eShmdW5jdGlvbiAoeGhyLCB0eHQsIHRocikge1xuICAgICAgICAgICAgICB0aGlzLmVycm9yKENVUkxBTkcuZXJyb3Jfb251cGxvYWQpXG4gICAgICAgICAgICB9LCB0aGlzKVxuICAgICAgICAgIH0pO1xuICAgICAgICB9LCB0aGlzKTtcblxuICAgICAgfVxuICAgIH0sXG4gICAgZXJyb3I6IGZ1bmN0aW9uIChtc2cpIHtcbiAgICAgIHRoaXMuJGJsb2NrLmZpbmQoXCIudXBsLWVycm9yXCIpLnJlbW92ZSgpLmVuZCgpLmFwcGVuZCgnPHNwYW4gY2xhc3M9XCJ1cGwtZXJyb3JcIj4nICsgbXNnICsgJzwvc3Bhbj4nKS5hZGRDbGFzcyhcIndiYm0tYnJkcmVkXCIpO1xuICAgIH1cbiAgfVxufSkoalF1ZXJ5KTtcbiIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyJdLCJuYW1lcyI6WyJ3aW5kb3ciLCJQb3BwZXIiLCJyZXF1aXJlIiwiJCIsImpRdWVyeSIsImF4aW9zIiwiZGVmYXVsdHMiLCJoZWFkZXJzIiwiY29tbW9uIiwidG9rZW4iLCJkb2N1bWVudCIsImhlYWQiLCJxdWVyeVNlbGVjdG9yIiwiY29udGVudCIsIl8iLCJib290c3RyYXAiLCJ0b29sdGlwVHJpZ2dlckxpc3QiLCJzbGljZSIsImNhbGwiLCJxdWVyeVNlbGVjdG9yQWxsIiwibWFwIiwidG9vbHRpcFRyaWdnZXJFbCIsIlRvb2x0aXAiLCJlIiwib24iLCJ0b2dnbGVQcmV2aWV3IiwidG9nZ2xlIiwiZWFjaCIsIm1hZ25pZmljUG9wdXAiLCJkZWxlZ2F0ZSIsInR5cGUiLCJ0TG9hZGluZyIsIm1haW5DbGFzcyIsImdhbGxlcnkiLCJlbmFibGVkIiwibmF2aWdhdGVCeUltZ0NsaWNrIiwicHJlbG9hZCIsImltYWdlIiwidEVycm9yIiwidGl0bGVTcmMiLCJpdGVtIiwiZWwiLCJhdHRyIiwiem9vbSIsImR1cmF0aW9uIiwib3BlbmVyIiwiZWxlbWVudCIsImZpbmQiLCJ2ZXJ0aWNhbEZpdCIsInRvb2x0aXAiLCJmaWxlTmFtZSIsInZhbCIsInNwbGl0IiwicG9wIiwic2libGluZ3MiLCJhZGRDbGFzcyIsImh0bWwiLCJmYWN0b3J5IiwiZGVmaW5lIiwiYW1kIiwiZXhwb3J0cyIsIlplcHRvIiwiQ0xPU0VfRVZFTlQiLCJCRUZPUkVfQ0xPU0VfRVZFTlQiLCJBRlRFUl9DTE9TRV9FVkVOVCIsIkJFRk9SRV9BUFBFTkRfRVZFTlQiLCJNQVJLVVBfUEFSU0VfRVZFTlQiLCJPUEVOX0VWRU5UIiwiQ0hBTkdFX0VWRU5UIiwiTlMiLCJFVkVOVF9OUyIsIlJFQURZX0NMQVNTIiwiUkVNT1ZJTkdfQ0xBU1MiLCJQUkVWRU5UX0NMT1NFX0NMQVNTIiwibWZwIiwiTWFnbmlmaWNQb3B1cCIsIl9pc0pRIiwiX3ByZXZTdGF0dXMiLCJfd2luZG93IiwiX2RvY3VtZW50IiwiX3ByZXZDb250ZW50VHlwZSIsIl93cmFwQ2xhc3NlcyIsIl9jdXJyUG9wdXBUeXBlIiwiX21mcE9uIiwibmFtZSIsImYiLCJldiIsIl9nZXRFbCIsImNsYXNzTmFtZSIsImFwcGVuZFRvIiwicmF3IiwiY3JlYXRlRWxlbWVudCIsImlubmVySFRNTCIsImFwcGVuZENoaWxkIiwiX21mcFRyaWdnZXIiLCJkYXRhIiwidHJpZ2dlckhhbmRsZXIiLCJzdCIsImNhbGxiYWNrcyIsImNoYXJBdCIsInRvTG93ZXJDYXNlIiwiYXBwbHkiLCJpc0FycmF5IiwiX2dldENsb3NlQnRuIiwiY3VyclRlbXBsYXRlIiwiY2xvc2VCdG4iLCJjbG9zZU1hcmt1cCIsInJlcGxhY2UiLCJ0Q2xvc2UiLCJfY2hlY2tJbnN0YW5jZSIsImluc3RhbmNlIiwiaW5pdCIsInN1cHBvcnRzVHJhbnNpdGlvbnMiLCJzIiwic3R5bGUiLCJ2IiwidW5kZWZpbmVkIiwibGVuZ3RoIiwicHJvdG90eXBlIiwiY29uc3RydWN0b3IiLCJhcHBWZXJzaW9uIiwibmF2aWdhdG9yIiwiaXNMb3dJRSIsImlzSUU4IiwiYWxsIiwiYWRkRXZlbnRMaXN0ZW5lciIsImlzQW5kcm9pZCIsInRlc3QiLCJpc0lPUyIsInN1cHBvcnRzVHJhbnNpdGlvbiIsInByb2JhYmx5TW9iaWxlIiwidXNlckFnZW50IiwicG9wdXBzQ2FjaGUiLCJvcGVuIiwiaSIsImlzT2JqIiwiaXRlbXMiLCJ0b0FycmF5IiwiaW5kZXgiLCJwYXJzZWQiLCJpc09wZW4iLCJ1cGRhdGVJdGVtSFRNTCIsInR5cGVzIiwibWFpbkVsIiwiZXEiLCJrZXkiLCJleHRlbmQiLCJmaXhlZENvbnRlbnRQb3MiLCJtb2RhbCIsImNsb3NlT25Db250ZW50Q2xpY2siLCJjbG9zZU9uQmdDbGljayIsInNob3dDbG9zZUJ0biIsImVuYWJsZUVzY2FwZUtleSIsImJnT3ZlcmxheSIsImNsb3NlIiwid3JhcCIsIl9jaGVja0lmQ2xvc2UiLCJ0YXJnZXQiLCJjb250YWluZXIiLCJjb250ZW50Q29udGFpbmVyIiwicHJlbG9hZGVyIiwibW9kdWxlcyIsIm4iLCJ0b1VwcGVyQ2FzZSIsImNsb3NlQnRuSW5zaWRlIiwiYXBwZW5kIiwidGVtcGxhdGUiLCJ2YWx1ZXMiLCJjbG9zZV9yZXBsYWNlV2l0aCIsImFsaWduVG9wIiwiY3NzIiwib3ZlcmZsb3ciLCJvdmVyZmxvd1kiLCJvdmVyZmxvd1giLCJ0b3AiLCJzY3JvbGxUb3AiLCJwb3NpdGlvbiIsImZpeGVkQmdQb3MiLCJoZWlnaHQiLCJrZXlDb2RlIiwidXBkYXRlU2l6ZSIsIndpbmRvd0hlaWdodCIsIndIIiwid2luZG93U3R5bGVzIiwiX2hhc1Njcm9sbEJhciIsIl9nZXRTY3JvbGxiYXJTaXplIiwibWFyZ2luUmlnaHQiLCJpc0lFNyIsImNsYXNzZXNUb2FkZCIsIl9hZGRDbGFzc1RvTUZQIiwiYWRkIiwicHJlcGVuZFRvIiwiYm9keSIsIl9sYXN0Rm9jdXNlZEVsIiwiYWN0aXZlRWxlbWVudCIsInNldFRpbWVvdXQiLCJfc2V0Rm9jdXMiLCJfb25Gb2N1c0luIiwicmVtb3ZhbERlbGF5IiwiX2Nsb3NlIiwiY2xhc3Nlc1RvUmVtb3ZlIiwiZGV0YWNoIiwiZW1wdHkiLCJfcmVtb3ZlQ2xhc3NGcm9tTUZQIiwib2ZmIiwicmVtb3ZlQXR0ciIsImN1cnJJdGVtIiwiYXV0b0ZvY3VzTGFzdCIsImZvY3VzIiwicHJldkhlaWdodCIsIndpbkhlaWdodCIsInpvb21MZXZlbCIsImRvY3VtZW50RWxlbWVudCIsImNsaWVudFdpZHRoIiwiaW5uZXJXaWR0aCIsImlubmVySGVpZ2h0IiwicGFyc2VFbCIsIm1hcmt1cCIsInJlbW92ZUNsYXNzIiwibmV3Q29udGVudCIsImFwcGVuZENvbnRlbnQiLCJwcmVsb2FkZWQiLCJwcmVwZW5kIiwidGFnTmFtZSIsInNyYyIsImhhc0NsYXNzIiwiYWRkR3JvdXAiLCJvcHRpb25zIiwiZUhhbmRsZXIiLCJtZnBFbCIsIl9vcGVuQ2xpY2siLCJlTmFtZSIsIm1pZENsaWNrIiwid2hpY2giLCJjdHJsS2V5IiwibWV0YUtleSIsImFsdEtleSIsInNoaWZ0S2V5IiwiZGlzYWJsZU9uIiwiaXNGdW5jdGlvbiIsIndpZHRoIiwicHJldmVudERlZmF1bHQiLCJzdG9wUHJvcGFnYXRpb24iLCJ1cGRhdGVTdGF0dXMiLCJzdGF0dXMiLCJ0ZXh0Iiwic3RvcEltbWVkaWF0ZVByb3BhZ2F0aW9uIiwiY2xvc2VPbkNvbnRlbnQiLCJjbG9zZU9uQmciLCJjb250YWlucyIsImNOYW1lIiwic2Nyb2xsSGVpZ2h0IiwiX3BhcnNlTWFya3VwIiwiYXJyIiwidmFsdWUiLCJyZXBsYWNlV2l0aCIsImlzIiwic2Nyb2xsYmFyU2l6ZSIsInNjcm9sbERpdiIsImNzc1RleHQiLCJvZmZzZXRXaWR0aCIsInJlbW92ZUNoaWxkIiwicHJvdG8iLCJyZWdpc3Rlck1vZHVsZSIsIm1vZHVsZSIsInB1c2giLCJmbiIsImpxRWwiLCJpdGVtT3B0cyIsInBhcnNlSW50IiwiYXJndW1lbnRzIiwiQXJyYXkiLCJJTkxJTkVfTlMiLCJfaGlkZGVuQ2xhc3MiLCJfaW5saW5lUGxhY2Vob2xkZXIiLCJfbGFzdElubGluZUVsZW1lbnQiLCJfcHV0SW5saW5lRWxlbWVudHNCYWNrIiwiYWZ0ZXIiLCJoaWRkZW5DbGFzcyIsInROb3RGb3VuZCIsImluaXRJbmxpbmUiLCJnZXRJbmxpbmUiLCJpbmxpbmVTdCIsImlubGluZSIsInBhcmVudCIsInBhcmVudE5vZGUiLCJpbmxpbmVFbGVtZW50IiwiQUpBWF9OUyIsIl9hamF4Q3VyIiwiX3JlbW92ZUFqYXhDdXJzb3IiLCJfZGVzdHJveUFqYXhSZXF1ZXN0IiwicmVxIiwiYWJvcnQiLCJzZXR0aW5ncyIsImN1cnNvciIsImluaXRBamF4IiwiYWpheCIsImdldEFqYXgiLCJvcHRzIiwidXJsIiwic3VjY2VzcyIsInRleHRTdGF0dXMiLCJqcVhIUiIsInRlbXAiLCJ4aHIiLCJmaW5pc2hlZCIsImVycm9yIiwibG9hZEVycm9yIiwiX2ltZ0ludGVydmFsIiwiX2dldFRpdGxlIiwidGl0bGUiLCJpbml0SW1hZ2UiLCJpbWdTdCIsIm5zIiwicmVzaXplSW1hZ2UiLCJpbWciLCJkZWNyIiwiX29uSW1hZ2VIYXNTaXplIiwiaGFzU2l6ZSIsImNsZWFySW50ZXJ2YWwiLCJpc0NoZWNraW5nSW1nU2l6ZSIsImltZ0hpZGRlbiIsImZpbmRJbWFnZVNpemUiLCJjb3VudGVyIiwibWZwU2V0SW50ZXJ2YWwiLCJkZWxheSIsInNldEludGVydmFsIiwibmF0dXJhbFdpZHRoIiwiZ2V0SW1hZ2UiLCJndWFyZCIsIm9uTG9hZENvbXBsZXRlIiwiY29tcGxldGUiLCJsb2FkZWQiLCJvbkxvYWRFcnJvciIsImFsdCIsImNsb25lIiwiaW1nX3JlcGxhY2VXaXRoIiwibG9hZGluZyIsImhhc01velRyYW5zZm9ybSIsImdldEhhc01velRyYW5zZm9ybSIsIk1velRyYW5zZm9ybSIsImVhc2luZyIsImluaXRab29tIiwiem9vbVN0IiwiZ2V0RWxUb0FuaW1hdGUiLCJuZXdJbWciLCJ0cmFuc2l0aW9uIiwiY3NzT2JqIiwiekluZGV4IiwibGVmdCIsInQiLCJzaG93TWFpbkNvbnRlbnQiLCJvcGVuVGltZW91dCIsImFuaW1hdGVkSW1nIiwiX2FsbG93Wm9vbSIsImNsZWFyVGltZW91dCIsIl9nZXRJdGVtVG9ab29tIiwiX2dldE9mZnNldCIsInJlbW92ZSIsImlzTGFyZ2UiLCJvZmZzZXQiLCJwYWRkaW5nVG9wIiwicGFkZGluZ0JvdHRvbSIsIm9iaiIsIm9mZnNldEhlaWdodCIsIklGUkFNRV9OUyIsIl9lbXB0eVBhZ2UiLCJfZml4SWZyYW1lQnVncyIsImlzU2hvd2luZyIsInNyY0FjdGlvbiIsInBhdHRlcm5zIiwieW91dHViZSIsImlkIiwidmltZW8iLCJnbWFwcyIsImluaXRJZnJhbWUiLCJwcmV2VHlwZSIsIm5ld1R5cGUiLCJnZXRJZnJhbWUiLCJlbWJlZFNyYyIsImlmcmFtZVN0IiwiaWZyYW1lIiwiaW5kZXhPZiIsInN1YnN0ciIsImxhc3RJbmRleE9mIiwiZGF0YU9iaiIsIl9nZXRMb29wZWRJZCIsIm51bVNsaWRlcyIsIl9yZXBsYWNlQ3VyclRvdGFsIiwiY3VyciIsInRvdGFsIiwiYXJyb3dNYXJrdXAiLCJhcnJvd3MiLCJ0UHJldiIsInROZXh0IiwidENvdW50ZXIiLCJpbml0R2FsbGVyeSIsImdTdCIsImRpcmVjdGlvbiIsIm5leHQiLCJwcmV2IiwibCIsImFycm93TGVmdCIsImFycm93UmlnaHQiLCJjbGljayIsIl9wcmVsb2FkVGltZW91dCIsInByZWxvYWROZWFyYnlJbWFnZXMiLCJnb1RvIiwibmV3SW5kZXgiLCJwIiwicHJlbG9hZEJlZm9yZSIsIk1hdGgiLCJtaW4iLCJwcmVsb2FkQWZ0ZXIiLCJfcHJlbG9hZEl0ZW0iLCJSRVRJTkFfTlMiLCJyZXBsYWNlU3JjIiwibSIsInJhdGlvIiwiaW5pdFJldGluYSIsImRldmljZVBpeGVsUmF0aW8iLCJyZXRpbmEiLCJpc05hTiIsIlByaXNtIiwibWFudWFsIiwic2Nyb2xsX2J1dHRvbiIsImhpZ2hsaWdodEFsbFVuZGVyIiwic2Nyb2xsIiwiZXZlbnQiLCJhbmltYXRlIiwicmVhZHkiLCJ3eXNpYmJfaW5wdXQiLCJ3eXNpYmIiLCJ3eXNpYmJfc2V0dGluZ3MiLCJmbGF0cGlja3IiLCJkYXRlRm9ybWF0IiwiZW5hYmxlVGltZSIsInRvZ2dsZV9tZW51IiwiZ2V0U3Bpbm5lciIsImFqYXhfbW9kYWwiLCJidXR0b24iLCJyZWxhdGVkVGFyZ2V0IiwicGFyYW1zIiwiZGF0YVR5cGUiLCJzZWxlY3RfbGFuZ3VhZ2VfZm9ybSIsInNlcmlhbGl6ZSIsImxvY2F0aW9uIiwiaHJlZiIsIl9zZWxmIiwiV29ya2VyR2xvYmFsU2NvcGUiLCJzZWxmIiwidSIsImMiLCJyIiwiZGlzYWJsZVdvcmtlck1lc3NhZ2VIYW5kbGVyIiwidXRpbCIsImVuY29kZSIsIkwiLCJhbGlhcyIsIk9iamVjdCIsInRvU3RyaW5nIiwib2JqSWQiLCJfX2lkIiwiZGVmaW5lUHJvcGVydHkiLCJhIiwibyIsImhhc093blByb3BlcnR5IiwiZm9yRWFjaCIsImN1cnJlbnRTY3JpcHQiLCJFcnJvciIsImV4ZWMiLCJzdGFjayIsImdldEVsZW1lbnRzQnlUYWdOYW1lIiwibGFuZ3VhZ2VzIiwiaW5zZXJ0QmVmb3JlIiwiREZTIiwicGx1Z2lucyIsImhpZ2hsaWdodEFsbCIsImNhbGxiYWNrIiwic2VsZWN0b3IiLCJob29rcyIsInJ1biIsImhpZ2hsaWdodEVsZW1lbnQiLCJtYXRjaCIsIm5vZGVOYW1lIiwibGFuZ3VhZ2UiLCJncmFtbWFyIiwiY29kZSIsInRleHRDb250ZW50IiwiaGlnaGxpZ2h0ZWRDb2RlIiwiV29ya2VyIiwiZmlsZW5hbWUiLCJvbm1lc3NhZ2UiLCJwb3N0TWVzc2FnZSIsIkpTT04iLCJzdHJpbmdpZnkiLCJpbW1lZGlhdGVDbG9zZSIsImhpZ2hsaWdodCIsInRva2VucyIsInRva2VuaXplIiwibWF0Y2hHcmFtbWFyIiwiZyIsImluc2lkZSIsImxvb2tiZWhpbmQiLCJkIiwiZ3JlZWR5IiwiaCIsInBhdHRlcm4iLCJnbG9iYWwiLCJSZWdFeHAiLCJzb3VyY2UiLCJ5IiwiayIsImxhc3RJbmRleCIsIk8iLCJiIiwidyIsIkEiLCJQIiwieCIsIlMiLCJqIiwiTiIsIkUiLCJDIiwic3BsaWNlIiwicmVzdCIsIlRva2VuIiwiam9pbiIsInRhZyIsImNsYXNzZXMiLCJhdHRyaWJ1dGVzIiwia2V5cyIsInBhcnNlIiwiaGFzQXR0cmlidXRlIiwicmVhZHlTdGF0ZSIsImRlZmVyIiwicmVxdWVzdEFuaW1hdGlvbkZyYW1lIiwiY29tbWVudCIsInByb2xvZyIsImRvY3R5cGUiLCJjZGF0YSIsInB1bmN0dWF0aW9uIiwibmFtZXNwYWNlIiwiZW50aXR5IiwieG1sIiwibWF0aG1sIiwic3ZnIiwiYXRydWxlIiwicnVsZSIsInN0cmluZyIsInByb3BlcnR5IiwiaW1wb3J0YW50IiwiYWRkSW5saW5lZCIsImNsaWtlIiwia2V5d29yZCIsIm51bWJlciIsIm9wZXJhdG9yIiwiamF2YXNjcmlwdCIsInJlZ2V4IiwicGFyYW1ldGVyIiwiY29uc3RhbnQiLCJpbnRlcnBvbGF0aW9uIiwianMiLCJkZWZpbmVQcm9wZXJ0aWVzIiwiYnVpbGRQbGFjZWhvbGRlcnMiLCJ0b2tlblN0YWNrIiwidG9rZW5pemVQbGFjZWhvbGRlcnMiLCJzdWJzdHJpbmciLCJjb25jYXQiLCJwaHAiLCJkZWxpbWl0ZXIiLCJ2YXJpYWJsZSIsImphdmFkb2NsaWtlIiwiYWRkU3VwcG9ydCIsInBocGRvYyIsInNjb3BlIiwic3FsIiwiZGlzcGxheSIsImdldEJvdW5kaW5nQ2xpZW50UmVjdCIsImNoaWxkcmVuIiwiZ2V0Q29tcHV0ZWRTdHlsZSIsImN1cnJlbnRTdHlsZSIsInNldEF0dHJpYnV0ZSIsImNvdW50ZXJSZXNldCIsImdldEF0dHJpYnV0ZSIsImxpbmVOdW1iZXJzIiwiZ2V0TGluZSIsImNsYXNzTGlzdCIsInJpZ2h0IiwicGVyY2VudGFnZVRvRGVncmVlcyIsInBlcmNlbnRhZ2UiLCJTd2lwZXIiLCJzd2lwZXJTbGlkZXIiLCJzbGlkZXNQZXJWaWV3Iiwic3BhY2VCZXR3ZWVuIiwicGFnaW5hdGlvbiIsImNsaWNrYWJsZSIsImJyZWFrcG9pbnRzIiwiV0JCTEFORyIsIkNVUkxBTkciLCJib2xkIiwiaXRhbGljIiwidW5kZXJsaW5lIiwic3RyaWtlIiwibGluayIsInN1cCIsInN1YiIsImp1c3RpZnlsZWZ0IiwianVzdGlmeWNlbnRlciIsImp1c3RpZnlyaWdodCIsInRhYmxlIiwiYnVsbGlzdCIsIm51bWxpc3QiLCJxdW90ZSIsIm9mZnRvcCIsInNwb2lsZXIiLCJmb250Y29sb3IiLCJmb250c2l6ZSIsImZvbnRmYW1pbHkiLCJmc192ZXJ5c21hbGwiLCJmc19zbWFsbCIsImZzX25vcm1hbCIsImZzX2JpZyIsImZzX3ZlcnliaWciLCJzbWlsZWJveCIsInZpZGVvIiwicmVtb3ZlRm9ybWF0IiwibW9kYWxfbGlua190aXRsZSIsIm1vZGFsX2xpbmtfdGV4dCIsIm1vZGFsX2xpbmtfdXJsIiwibW9kYWxfZW1haWxfdGV4dCIsIm1vZGFsX2VtYWlsX3VybCIsIm1vZGFsX2xpbmtfdGFiMSIsIm1vZGFsX2ltZ190aXRsZSIsIm1vZGFsX2ltZ190YWIxIiwibW9kYWxfaW1nX3RhYjIiLCJtb2RhbF9pbWdzcmNfdGV4dCIsIm1vZGFsX2ltZ19idG4iLCJhZGRfYXR0YWNoIiwibW9kYWxfdmlkZW9fdGV4dCIsInNhdmUiLCJjYW5jZWwiLCJ2YWxpZGF0aW9uX2VyciIsImVycm9yX29udXBsb2FkIiwiZmlsZXVwbG9hZF90ZXh0MSIsImZpbGV1cGxvYWRfdGV4dDIiLCJhdXRvIiwidmlld3MiLCJkb3dubG9hZHMiLCJzbTEiLCJzbTIiLCJzbTMiLCJzbTQiLCJzbTUiLCJzbTYiLCJzbTciLCJzbTgiLCJzbTkiLCJ3YmJkZWJ1ZyIsInR4dEFyZWEiLCJkZWZsYW5nIiwibGFuZyIsIiR0eHRBcmVhIiwic2V0VUlEIiwiYmJtb2RlIiwib25seUJCbW9kZSIsInRoZW1lTmFtZSIsImJvZHlDbGFzcyIsInRhYkluc2VydCIsImltZ3VwbG9hZCIsImltZ191cGxvYWR1cmwiLCJpbWdfbWF4d2lkdGgiLCJpbWdfbWF4aGVpZ2h0IiwiaG90a2V5cyIsInNob3dIb3RrZXlzIiwiYXV0b3Jlc2l6ZSIsInJlc2l6ZV9tYXhoZWlnaHQiLCJsb2FkUGFnZVN0eWxlcyIsInRyYWNlVGV4dGFyZWEiLCJzbWlsZUNvbnZlcnNpb24iLCJidXR0b25zIiwiYWxsQnV0dG9ucyIsImJ1dHRvbkhUTUwiLCJleGNtZCIsImhvdGtleSIsInRyYW5zZm9ybSIsInRhYnMiLCJpbnB1dCIsInBhcmFtIiwidmFsaWRhdGlvbiIsImFkZFdyYXAiLCJvbkxvYWQiLCJpbWdMb2FkTW9kYWwiLCJidXR0b25UZXh0Iiwib25seUNsZWFyVGV4dCIsInZhbHVlQkJuYW1lIiwic3ViSW5zZXJ0IiwiY29sb3JzIiwiY29scyIsInJvd3MiLCJjZWxsd2lkdGgiLCJza2lwUnVsZXMiLCJleHZhbHVlIiwiZ3JvdXBrZXkiLCJvblN1Ym1pdCIsImNtZCIsIm9wdCIsInF1ZXJ5U3RhdGUiLCIkbW9kYWwiLCJpbnNlcnRBdEN1cnNvciIsImdldENvZGVCeUNvbW1hbmQiLCJjbG9zZU1vZGFsIiwidXBkYXRlVUkiLCJyZW1vdmVmb3JtYXQiLCJzeXN0ciIsImN1c3RvbVJ1bGVzIiwidGQiLCJzZWx0ZXh0Iiwicmd4Iiwic2VsIiwidHIiLCJzbWlsZUxpc3QiLCJhdHRyV3JhcCIsImluaXRlZCIsInRoZW1lUHJlZml4IiwicHJveHkiLCJpZHgiLCJzcmlwdE1hdGNoIiwiZ2V0IiwiV0JCUFJFU0VUIiwibGFzdGlkIiwibG9nIiwiaXNNb2JpbGUiLCJ2ZW5kb3IiLCJvcGVyYSIsImNvbnRyb2xsZXJzIiwic21pbGVGaW5kIiwiaW5pdFRyYW5zZm9ybXMiLCJidWlsZCIsImluaXRNb2RhbCIsImluaXRIb3RrZXlzIiwic29ydCIsImJiY29kZSIsInBhcmVudHMiLCJiaW5kIiwic3luYyIsImluaXRDYWxsYmFjayIsInJ1bGVzIiwiZ3JvdXBzIiwiYnRubGlzdCIsImJpZHgiLCJvYiIsImVuIiwic2ltcGxlYmJjb2RlIiwib2xpc3QiLCJvcCIsImluQXJyYXkiLCJvYnRyIiwiYmh0bWwiLCJvcmlnaHRtbCIsImJiU2VsZWN0b3IiLCJ3cmFwQXR0cnMiLCIkYmVsIiwiZWxGcm9tU3RyaW5nIiwicm9vdFNlbGVjdG9yIiwiZmlsdGVyQnlOb2RlIiwibmh0bWwyIiwidW53cmFwQXR0cnMiLCJvYmh0bWwiLCJjcnVsZXMiLCJnZXRBdHRyaWJ1dGVMaXN0Iiwicm5hbWUiLCJnZXRWYWxpZGF0aW9uUkdYIiwicmVsRmlsdGVyQnlOb2RlIiwicmVnUmVwbCIsImdldFJlZ2V4cFJlcGxhY2UiLCJ0cmltIiwic2wiLCJjb250ZW50cyIsImZpbHRlciIsIm5vZGVUeXBlIiwicmVsIiwidHh0IiwibmVsIiwic3RhcnQiLCJhZnRlcl90eHQiLCJuYmh0bWwiLCJjbGVhcnRleHQiLCJzb3J0QXJyYXkiLCJodG1sbCIsImJiIiwic3J1bGVzIiwic20iLCIkc20iLCJzdHJmIiwicm9vdHNlbCIsInJzZWxsaXN0IiwiJGVkaXRvciIsImluc2VydEFmdGVyIiwic3RhcnRIZWlnaHQiLCJvdXRlckhlaWdodCIsImJ1aWxkVG9vbGJhciIsIm1pbmhlaWdodCIsIm1heGhlaWdodCIsIm1oZWlnaHQiLCIkYm9keSIsImhpZGUiLCIkdG9vbGJhciIsImNvbnRlbnRFZGl0YWJsZSIsImV4ZWNDb21tYW5kIiwidHh0QXJlYUluaXRDb250ZW50IiwiJHBhc3RlQmxvY2siLCJzYXZlUmFuZ2UiLCJjbGVhclBhc3RlIiwicmRhdGEiLCJibHVyIiwiaXNJbkNsZWFyVGV4dEJsb2NrIiwidG9CQiIsInNlbGVjdFJhbmdlIiwibGFzdFJhbmdlIiwic2VsZWN0Tm9kZSIsImlzTGkiLCJpc0NvbnRhaW4iLCJnZXRTZWxlY3ROb2RlIiwiY2hlY2tGb3JMYXN0QlIiLCJwcmVzc1RhYiIsImNsZWFyTGFzdFJhbmdlIiwidHJhY2VUZXh0YXJlYUV2ZW50IiwicHJlc3NrZXkiLCIkYnJlc2l6ZSIsIndkcmFnIiwiYXhpc1kiLCJpbWdMaXN0ZW5lcnMiLCJ1aXRpbWVyIiwidG9vbGJhciIsIiRidG5Db250YWluZXIiLCJibiIsImJ1aWxkQ29sb3JwaWNrZXIiLCJidWlsZFRhYmxlcGlja2VyIiwiYnVpbGRTZWxlY3QiLCJidWlsZFNtaWxlYm94IiwiYnVpbGRCdXR0b24iLCJob3ZlciIsIiRiYnN3IiwiY3VycmVudFRhcmdldCIsInRvZ2dsZUNsYXNzIiwibW9kZVN3aXRjaCIsImJ0bkhUTUwiLCIkYnRuIiwibW91c2Vkb3duIiwidHJpZ2dlciIsIiRjcGxpbmUiLCIkZHJvcGJsb2NrIiwiY29sb3JsaXN0IiwiY29sb3IiLCJiYXNlY29sb3IiLCJkcm9wZG93bmNsaWNrIiwic2VsZWN0TGFzdFJhbmdlIiwiJGxpc3RibG9jayIsImFsbGNvdW50IiwicmMiLCIkc2Jsb2NrIiwiJHN2YWwiLCIkc2VsZWN0Ym94Iiwib25hbWUiLCJvcHRpb24iLCIkZWwiLCJjbWR2YWx1ZSIsInByb3AiLCJjaGFuZ2UiLCIkbyIsIm9pZCIsIiRidG5IVE1MIiwiZGlzTm9uQWN0aXZlQnV0dG9ucyIsImVzY01vZGFsIiwia2xpc3QiLCJtZXRhc3VtIiwiY29tbWFuZCIsInNraXBjbWQiLCJ3YmJSZW1vdmVDYWxsYmFjayIsImdldEJCQ29kZUJ5Q29tbWFuZCIsImV4ZWNOYXRpdmVDb21tYW5kIiwid2JiRXhlY0NvbW1hbmQiLCJ3aXRodmFsdWUiLCJpc0JCQ29udGFpbiIsImdldFBhcmFtcyIsInF1ZXJ5Q29tbWFuZFZhbHVlIiwicmdiVG9IZXgiLCJjbG9zZXN0IiwicXVlcnlDb21tYW5kU3RhdGUiLCJzaG93TW9kYWwiLCJncm91cHNlbCIsInNub2RlIiwiJHNwIiwid2JiSW5zZXJ0Q2FsbGJhY2siLCJwYXJhbW9iaiIsInNlbHRleHRJRCIsImNsZWFyIiwicG9zIiwiZ2V0Q3Vyc29yUG9zQkIiLCJzdGV4dG51bSIsInN0ZXh0Iiwic2V0Q3Vyc29yUG9zQkIiLCJub2RlIiwicm9vdCIsIiRyb290IiwiY3MiLCJzZXRDdXJzb3JCeUVsIiwiaHRtbGRhdGEiLCJnZXRIVE1MIiwicm5nIiwiZ2V0UmFuZ2UiLCJzaHRtbCIsImdldFNlbGVjdFRleHQiLCJybm9kZSIsImNsZWFyRnJvbVN1Ykluc2VydCIsImlucyIsImJlZm9yZV9ybmciLCJnZXRTZWxlY3Rpb24iLCJjbG9uZVJhbmdlIiwiY3JlYXRlVGV4dFJhbmdlIiwiYWZ0ZXJfcm5nIiwiZGl2Iiwic2V0U3RhcnQiLCJmaXJzdENoaWxkIiwic2V0RW5kQmVmb3JlIiwic2V0U3RhcnRBZnRlciIsInNldEVuZEFmdGVyIiwibGFzdENoaWxkIiwibW92ZVRvRWxlbWVudFRleHQiLCJzZXRFbmRQb2ludCIsImJmIiwiYWYiLCIkYWYiLCJzZWxlY3Rpb24iLCJjcmVhdGVSYW5nZSIsInBhc3RlSFRNTCIsImJyc3AiLCJtb3ZlU3RhcnQiLCJzZWxlY3QiLCJkZWxldGVDb250ZW50cyIsImluc2VydE5vZGUiLCJjb2xsYXBzZSIsInJlbW92ZUFsbFJhbmdlcyIsImFkZFJhbmdlIiwiZ2V0SFRNTEJ5Q29tbWFuZCIsImtleXNUb0xvd2VyIiwic3RyIiwidnJneCIsInZyZ3hwIiwicmJiY29kZSIsIm1heHBjb3VudCIsInZhbGlkIiwicGNvdW50IiwicG5hbWUiLCJwb3N0c2VsIiwicmh0bWwiLCJmcm9tVHh0QXJlYSIsInJhbmdlIiwic2VsZWN0aW9uRW5kIiwic2VsZWN0aW9uU3RhcnQiLCJjbG9uZUNvbnRlbnRzIiwiaHRtbFRleHQiLCJnZXRSYW5nZUF0IiwicmFuZ2VDb3VudCIsImFuY2hvck5vZGUiLCJhbmNob3JPZmZzZXQiLCJzZXRFbmQiLCJmb2N1c05vZGUiLCJmb2N1c09mZnNldCIsImZvcmNlQkJNb2RlIiwiY2xiYiIsInNwbGl0UHJldk5leHQiLCJzbiIsImNvbW1vbkFuY2VzdG9yQ29udGFpbmVyIiwicGFyZW50RWxlbWVudCIsInJ0IiwibW92ZSIsInNlbGVjdE5vZGVDb250ZW50cyIsImR1cGxpY2F0ZSIsImdldFJhbmdlQ2xvbmUiLCJzZXRCb2R5Rm9jdXMiLCIkbiIsInZhIiwidmYiLCJzdG9wIiwidmFsaWRuYW1lIiwiZ2V0QkJDb2RlIiwiY2xlYXJFbXB0eSIsInJlbW92ZUxhc3RCb2R5QlIiLCIkZSIsIm91dGJiIiwicnBsIiwicHJvY2Vzc2VkIiwicmxpc3QiLCJza2lwIiwia2VlcEVsZW1lbnQiLCJrZWVwQXR0ciIsIiRjZWwiLCJjb250IiwicmVnZXhwIiwibnN0eWxlIiwiZml4VGFibGVUcmFuc2Zvcm0iLCJiYmRhdGEiLCJza2lwbHQiLCJhbSIsIm5odG1sIiwiJHdyYXAiLCJnZXRIVE1MU21pbGVzIiwic21pbGVSUEwiLCJuZGF0YSIsInJvdyIsImZpZHgiLCJhZnRlcm5vZGVfdHh0IiwiYWZ0ZXJub2RlIiwiY3JlYXRlVGV4dE5vZGUiLCJzaGlmdCIsIndyIiwicHJlcGFyZVJHWCIsImJicmd4IiwibGFzdGluZGV4IiwiJG5vZGUiLCJzcGVjaWZpZWQiLCIkd3IiLCJyZXMiLCJuZXh0U2libGluZyIsInByZXZpb3VzU2libGluZyIsInNob3ciLCJlbXB0eUZpbHRlciIsImJzZWwiLCJ0c2VsIiwidW5iaW5kIiwiZHJvcGRvd25oYW5kbGVyIiwiZW5kIiwiZXZ0IiwicmdiIiwiZGlnaXRzIiwiZGVjMmhleCIsIiRibG9jayIsImFyIiwiJHNmIiwiczIiLCIkdGhpcyIsImFzYyIsInNtaWxlZmluZCIsIiRzbWxpc3QiLCJvdXRlckhUTUwiLCJkZXN0cm95Iiwic2FyIiwic21iYiIsImFmdGVybm9kZV9jdXJzb3IiLCJpbWdFdmVudEhhbmRsZXIiLCJoYXNXcmFwZWRJbWFnZSIsIiRjb250IiwiJHdiYm0iLCIkdWwiLCIkY250IiwiJGMiLCJpbnAiLCJ0aWQiLCJwdmFsIiwiJHYiLCJkcmFnZmlsZXVwbG9hZCIsImV4dHJhUGFyYW1zIiwibWF4d2lkdGgiLCJpbnNlcnRJbWFnZSIsImltYWdlX2xpbmsiLCJ0aHVtYl9saW5rIiwic3VibWl0IiwiaW1nU3VibWl0TW9kYWwiLCJwcmludE9iamVjdEluSUUiLCJjaGVja0ZpbHRlciIsImRlYnVnIiwibXNnIiwidGltZSIsIkRhdGUiLCJnZXRUaW1lIiwiY29uc29sZSIsInN0YXJ0VGltZSIsImlzQ2hyb21lIiwiY2hyb21lIiwiZHJhZyIsImRyYWdfbW91c2Vkb3duIiwicGFnZVgiLCJwYWdlWSIsInNoZWlnaHQiLCJkcmFnX21vdXNlbW92ZSIsImRyYWdfbW91c2V1cCIsImF4aXNYIiwibmhlaWdodCIsImdldERvYyIsImRvYyIsImZyb21UZXh0QXJlYSIsImh0bWxjb2RlIiwib25seUJCTW9kZSIsIndiYiIsImltZ3VybCIsInRodW1idXJsIiwiZWRpdG9yIiwidXBsIiwiRmlsZVVwbG9hZCIsImZpbGVQYXJhbSIsInQxIiwidDIiLCJGb3JtRGF0YSIsInVwbG9hZFByb2dyZXNzIiwiJGxvYWRlciIsImFqYXhTZXR0aW5ncyIsInVwbG9hZCIsIm9uZHJvcCIsInVmaWxlIiwiZGF0YVRyYW5zZmVyIiwiZmlsZXMiLCJmRGF0YSIsInByb2Nlc3NEYXRhIiwiY29udGVudFR5cGUiLCJ0aHIiXSwic291cmNlUm9vdCI6IiJ9