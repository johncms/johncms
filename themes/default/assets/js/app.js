(self["webpackChunkjohncms"] = self["webpackChunkjohncms"] || []).push([["/themes/default/assets/js/app"],{

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "CkeditorInputComponent",
  props: {
    label: {
      type: String,
      "default": 'Message'
    },
    id: {
      type: String,
      "default": ''
    },
    name: {
      type: String,
      "default": ''
    },
    classes: {
      type: String,
      "default": ''
    },
    value: {
      type: String,
      "default": ''
    },
    errors: {
      type: String,
      "default": ''
    },
    language: {
      type: String,
      "default": 'en'
    },
    upload_url: {
      type: String,
      "default": ''
    }
  },
  data: function data() {
    return {
      model_value: this.value,
      attached_files: []
    };
  },
  mounted: function mounted() {
    var self = this;
    var config = {
      simpleUpload: {
        uploadUrl: this.upload_url,
        withCredentials: false,
        savedCallback: function savedCallback(file) {
          self.attached_files.push(file);
        }
      },
      language: this.language
    };
    ClassicEditor.create(document.querySelector('#' + this.id), config).then(function (editor) {
      window.editor = editor;
    })["catch"](function (error) {
      console.error(error);
    });
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CommentsComponent.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CommentsComponent.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "CommentsComponent",
  props: {
    article_id: Number,
    can_write: {
      type: Boolean,
      "default": false
    },
    i18n: {
      type: Object,
      "default": function _default() {
        return {
          write_comment: 'Write a comment',
          send: 'Send',
          "delete": 'Delete',
          quote: 'Quote',
          reply: 'Reply',
          comments: 'Comments',
          empty_list: 'The list is empty'
        };
      }
    },
    language: {
      type: String,
      "default": 'en'
    },
    upload_url: {
      type: String,
      "default": ''
    }
  },
  data: function data() {
    return {
      messages: {},
      comment_text: '',
      comment_added_message: '',
      error_message: '',
      loading: false,
      attached_files: []
    };
  },
  mounted: function mounted() {
    var _this = this;

    this.getComments(1, false);
    var self = this;
    var config = {
      simpleUpload: {
        uploadUrl: this.upload_url,
        withCredentials: false,
        savedCallback: function savedCallback(file) {
          self.attached_files.push(file.id);
        }
      },
      language: this.language
    };
    ClassicEditor.create(document.querySelector('#comment_text'), config).then(function (editor) {
      window.editor = editor;
      editor.model.document.on('change:data', function () {
        _this.comment_text = editor.getData();
      });
    })["catch"](function (error) {
      console.error(error);
    });
  },
  updated: function updated() {
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
  },
  computed: {},
  methods: {
    getComments: function getComments() {
      var _this2 = this;

      var page = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 1;
      var scroll_to_comments = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
      this.loading = true;
      axios.get('/news/comments/' + this.article_id + '/?page=' + page).then(function (response) {
        if (scroll_to_comments) {
          $('html, body').animate({
            scrollTop: $('.comments-list').offset().top
          }, 500);
        }

        _this2.messages = response.data;
        _this2.loading = false;
      })["catch"](function (error) {
        alert(error);
        _this2.loading = false;
      });
    },
    reply: function reply(message) {
      editor.editing.view.focus();
      $('html, body').animate({
        scrollTop: $('.comment-form').position().top
      }, 500);
      editor.model.change(function (writer) {
        var insertPosition = editor.model.document.selection.getFirstPosition();
        writer.insertText(message.user.user_name + ', ', {}, insertPosition);
        writer.setSelection(writer.createPositionAt(editor.model.document.getRoot(), 'end'));
      });
    },
    quote: function quote(message) {
      $('html, body').animate({
        scrollTop: $('.comment-form').position().top
      }, 500);
      var text = message.text.replace(/(<([^>]+)>)/ig, "");
      var content = '<blockquote><p>' + message.user.user_name + ', ' + message.created_at + '<br>' + text + '</p></blockquote><p></p>';
      var viewFragment = editor.data.processor.toView(content);
      var modelFragment = editor.data.toModel(viewFragment);
      editor.model.insertContent(modelFragment);
      editor.editing.view.focus();
    },
    sendComment: function sendComment() {
      var _this3 = this;

      this.loading = true;
      axios.post('/news/comments/add/' + this.article_id + '/', {
        comment: this.comment_text,
        attached_files: this.attached_files
      }).then(function (response) {
        _this3.comment_added_message = response.data.message;
        _this3.loading = false;
        _this3.comment_text = '';
        _this3.error_message = '';
        _this3.attached_files = [];
        window.editor.setData('');

        _this3.getComments(response.data.last_page, false);
      })["catch"](function (error) {
        _this3.error_message = error.response.data.message;
        _this3.loading = false;
      });
    },
    delComment: function delComment(comment_id) {
      var _this4 = this;

      this.loading = true;
      axios.post('/news/comments/del/', {
        comment_id: comment_id
      }).then(function (response) {
        _this4.getComments(_this4.messages.current_page, false);
      })["catch"](function (error) {
        alert(error.response.data.message);
        _this4.loading = false;
      });
    },
    __: function __(message) {
      return _.get(this.i18n, message, '');
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/LikesComponent.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/LikesComponent.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "LikesComponent",
  props: {
    article_id: Number,
    rating: {
      type: Number,
      "default": 0
    },
    can_vote: {
      type: Boolean,
      "default": false
    },
    voted: {
      type: Number,
      "default": 0
    },
    set_vote_url: {
      type: String,
      "default": '/news/add_vote/'
    }
  },
  data: function data() {
    return {
      message: '',
      loading: false
    };
  },
  computed: {
    rating_color: function rating_color() {
      var class_name = '';

      if (this.rating > 0) {
        class_name = 'text-success';
      } else if (this.rating < 0) {
        class_name = 'text-danger';
      }

      return class_name;
    }
  },
  methods: {
    setVote: function setVote(type) {
      var _this = this;

      this.loading = true;
      axios.get(this.set_vote_url + this.article_id + '/' + type + '/').then(function (response) {
        _this.rating = response.data.rating;
        _this.voted = response.data.voted;
        _this.message = response.data.message;
        _this.loading = false;
      })["catch"](function (error) {
        alert(error);
        _this.loading = false;
      });
    }
  }
});

/***/ }),

/***/ "./themes/default/src/js/app.js":
/*!**************************************!*\
  !*** ./themes/default/src/js/app.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.esm.js");
/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */


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
/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Автозагрузка компонентов
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */


var files = __webpack_require__("./themes/default/src/js sync recursive \\.vue$/");

files.keys().map(function (key) {
  return vue__WEBPACK_IMPORTED_MODULE_0__.default.component(key.split('/').pop().split('.')[0], files(key)["default"]);
});
vue__WEBPACK_IMPORTED_MODULE_0__.default.component('pagination', __webpack_require__(/*! laravel-vue-pagination */ "./node_modules/laravel-vue-pagination/dist/laravel-vue-pagination.common.js"));
var vue_apps = document.querySelectorAll('.vue_app');
vue_apps.forEach(function (el) {
  new vue__WEBPACK_IMPORTED_MODULE_0__.default({
    el: el
  });
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
  window.Popper = __webpack_require__(/*! popper.js */ "./node_modules/popper.js/dist/esm/popper.js").default;
  window.$ = window.jQuery = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
  window.axios = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
  window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

  var _ = __webpack_require__(/*! lodash */ "./node_modules/lodash/lodash.js");

  __webpack_require__(/*! bootstrap */ "./node_modules/bootstrap/dist/js/bootstrap.esm.js");
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
  $('[data-bs-toggle="tooltip"]').tooltip();
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
/* harmony import */ var swiper_swiper_bundle__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! swiper/swiper-bundle */ "./node_modules/swiper/swiper-bundle.js");
/* harmony import */ var swiper_swiper_bundle__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(swiper_swiper_bundle__WEBPACK_IMPORTED_MODULE_0__);
/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

var swiperSlider = new (swiper_swiper_bundle__WEBPACK_IMPORTED_MODULE_0___default())('.screenshots', {
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


/***/ }),

/***/ "./themes/default/src/js/components/CkeditorInputComponent.vue":
/*!*********************************************************************!*\
  !*** ./themes/default/src/js/components/CkeditorInputComponent.vue ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CkeditorInputComponent_vue_vue_type_template_id_3685964a___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CkeditorInputComponent.vue?vue&type=template&id=3685964a& */ "./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=template&id=3685964a&");
/* harmony import */ var _CkeditorInputComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CkeditorInputComponent.vue?vue&type=script&lang=js& */ "./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__.default)(
  _CkeditorInputComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__.default,
  _CkeditorInputComponent_vue_vue_type_template_id_3685964a___WEBPACK_IMPORTED_MODULE_0__.render,
  _CkeditorInputComponent_vue_vue_type_template_id_3685964a___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "themes/default/src/js/components/CkeditorInputComponent.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./themes/default/src/js/components/CommentsComponent.vue":
/*!****************************************************************!*\
  !*** ./themes/default/src/js/components/CommentsComponent.vue ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CommentsComponent_vue_vue_type_template_id_526753e6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CommentsComponent.vue?vue&type=template&id=526753e6& */ "./themes/default/src/js/components/CommentsComponent.vue?vue&type=template&id=526753e6&");
/* harmony import */ var _CommentsComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CommentsComponent.vue?vue&type=script&lang=js& */ "./themes/default/src/js/components/CommentsComponent.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__.default)(
  _CommentsComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__.default,
  _CommentsComponent_vue_vue_type_template_id_526753e6___WEBPACK_IMPORTED_MODULE_0__.render,
  _CommentsComponent_vue_vue_type_template_id_526753e6___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "themes/default/src/js/components/CommentsComponent.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./themes/default/src/js/components/LikesComponent.vue":
/*!*************************************************************!*\
  !*** ./themes/default/src/js/components/LikesComponent.vue ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _LikesComponent_vue_vue_type_template_id_3f8b1c74___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LikesComponent.vue?vue&type=template&id=3f8b1c74& */ "./themes/default/src/js/components/LikesComponent.vue?vue&type=template&id=3f8b1c74&");
/* harmony import */ var _LikesComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LikesComponent.vue?vue&type=script&lang=js& */ "./themes/default/src/js/components/LikesComponent.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__.default)(
  _LikesComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__.default,
  _LikesComponent_vue_vue_type_template_id_3f8b1c74___WEBPACK_IMPORTED_MODULE_0__.render,
  _LikesComponent_vue_vue_type_template_id_3f8b1c74___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "themes/default/src/js/components/LikesComponent.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************!*\
  !*** ./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_CkeditorInputComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CkeditorInputComponent.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_CkeditorInputComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__.default); 

/***/ }),

/***/ "./themes/default/src/js/components/CommentsComponent.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************!*\
  !*** ./themes/default/src/js/components/CommentsComponent.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_CommentsComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CommentsComponent.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CommentsComponent.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_CommentsComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__.default); 

/***/ }),

/***/ "./themes/default/src/js/components/LikesComponent.vue?vue&type=script&lang=js&":
/*!**************************************************************************************!*\
  !*** ./themes/default/src/js/components/LikesComponent.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_LikesComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LikesComponent.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-5[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/LikesComponent.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_5_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_LikesComponent_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__.default); 

/***/ }),

/***/ "./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=template&id=3685964a&":
/*!****************************************************************************************************!*\
  !*** ./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=template&id=3685964a& ***!
  \****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CkeditorInputComponent_vue_vue_type_template_id_3685964a___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CkeditorInputComponent_vue_vue_type_template_id_3685964a___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CkeditorInputComponent_vue_vue_type_template_id_3685964a___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CkeditorInputComponent.vue?vue&type=template&id=3685964a& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=template&id=3685964a&");


/***/ }),

/***/ "./themes/default/src/js/components/CommentsComponent.vue?vue&type=template&id=526753e6&":
/*!***********************************************************************************************!*\
  !*** ./themes/default/src/js/components/CommentsComponent.vue?vue&type=template&id=526753e6& ***!
  \***********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CommentsComponent_vue_vue_type_template_id_526753e6___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CommentsComponent_vue_vue_type_template_id_526753e6___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CommentsComponent_vue_vue_type_template_id_526753e6___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CommentsComponent.vue?vue&type=template&id=526753e6& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CommentsComponent.vue?vue&type=template&id=526753e6&");


/***/ }),

/***/ "./themes/default/src/js/components/LikesComponent.vue?vue&type=template&id=3f8b1c74&":
/*!********************************************************************************************!*\
  !*** ./themes/default/src/js/components/LikesComponent.vue?vue&type=template&id=3f8b1c74& ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_LikesComponent_vue_vue_type_template_id_3f8b1c74___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_LikesComponent_vue_vue_type_template_id_3f8b1c74___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_LikesComponent_vue_vue_type_template_id_3f8b1c74___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LikesComponent.vue?vue&type=template&id=3f8b1c74& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/LikesComponent.vue?vue&type=template&id=3f8b1c74&");


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=template&id=3685964a&":
/*!*******************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CkeditorInputComponent.vue?vue&type=template&id=3685964a& ***!
  \*******************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    [
      _c("div", { staticClass: "form-group" }, [
        _c("label", { attrs: { for: _vm.id } }, [_vm._v(_vm._s(_vm.label))]),
        _vm._v(" "),
        _c("textarea", {
          directives: [
            {
              name: "model",
              rawName: "v-model",
              value: _vm.model_value,
              expression: "model_value"
            }
          ],
          staticClass: "form-control",
          class: _vm.classes + (_vm.errors ? "is-invalid" : ""),
          attrs: { name: _vm.name, id: _vm.id },
          domProps: { value: _vm.model_value },
          on: {
            input: function($event) {
              if ($event.target.composing) {
                return
              }
              _vm.model_value = $event.target.value
            }
          }
        }),
        _vm._v(" "),
        _vm.errors
          ? _c("div", { staticClass: "invalid-feedback d-block" }, [
              _vm._v(_vm._s(_vm.errors))
            ])
          : _vm._e()
      ]),
      _vm._v(" "),
      _vm._l(_vm.attached_files, function(file) {
        return _c("div", [
          _c("input", {
            directives: [
              {
                name: "model",
                rawName: "v-model",
                value: file.id,
                expression: "file.id"
              }
            ],
            attrs: { type: "hidden", name: "attached_files[]" },
            domProps: { value: file.id },
            on: {
              input: function($event) {
                if ($event.target.composing) {
                  return
                }
                _vm.$set(file, "id", $event.target.value)
              }
            }
          })
        ])
      })
    ],
    2
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CommentsComponent.vue?vue&type=template&id=526753e6&":
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/CommentsComponent.vue?vue&type=template&id=526753e6& ***!
  \**************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    { staticClass: "mt-4 comments-list" },
    [
      _c("h3", { staticClass: "fw-bold" }, [
        _vm._v(_vm._s(_vm.__("comments")) + " "),
        _vm.messages.total > 0
          ? _c("span", { staticClass: "text-success" }, [
              _vm._v(_vm._s(_vm.messages.total))
            ])
          : _vm._e()
      ]),
      _vm._v(" "),
      _vm.messages.data && _vm.messages.data.length < 1
        ? _c("div", { staticClass: "alert alert-info" }, [
            _vm._v(_vm._s(_vm.__("empty_list")))
          ])
        : _vm._e(),
      _vm._v(" "),
      _vm._l(_vm.messages.data, function(message) {
        return _c("div", { staticClass: "new_post-item" }, [
          _c(
            "div",
            { staticClass: "new_post-header d-flex justify-content-between" },
            [
              _c("div", { staticClass: "post-user" }, [
                message.user.profile_url
                  ? _c("a", { attrs: { href: message.user.profile_url } }, [
                      _c("div", { staticClass: "avatar" }, [
                        _c("img", {
                          staticClass: "img-fluid",
                          attrs: { src: message.user.avatar, alt: "." }
                        })
                      ])
                    ])
                  : _vm._e(),
                _vm._v(" "),
                _c("span", {
                  staticClass: "user-status shadow",
                  class: message.user.is_online ? "online" : "offline"
                }),
                _vm._v(" "),
                message.user.rights_name
                  ? _c(
                      "div",
                      {
                        staticClass: "post-of-user",
                        attrs: {
                          "data-bs-toggle": "tooltip",
                          "data-bs-placement": "top",
                          "data-bs-html": "true",
                          title: message.user.rights_name
                        }
                      },
                      [
                        _c("svg", { staticClass: "icon-post" }, [
                          _c("use", {
                            attrs: {
                              "xlink:href":
                                "/themes/default/assets/icons/sprite.svg?#check"
                            }
                          })
                        ])
                      ]
                    )
                  : _vm._e()
              ]),
              _vm._v(" "),
              _c(
                "div",
                {
                  staticClass:
                    "flex-grow-1 post-user d-flex flex-wrap overflow-hidden d-flex align-items-center"
                },
                [
                  _c("div", { staticClass: "w-100" }, [
                    message.user.profile_url
                      ? _c("a", { attrs: { href: message.user.profile_url } }, [
                          _c(
                            "span",
                            { staticClass: "user-name d-inline me-2" },
                            [_vm._v(_vm._s(message.user.user_name))]
                          )
                        ])
                      : _vm._e(),
                    _vm._v(" "),
                    !message.user.profile_url
                      ? _c("div", { staticClass: "user-name d-inline me-2" }, [
                          _vm._v(_vm._s(message.user.user_name))
                        ])
                      : _vm._e(),
                    _vm._v(" "),
                    _c("span", { staticClass: "post-meta d-inline me-2" }, [
                      _vm._v(_vm._s(message.created_at))
                    ])
                  ]),
                  _vm._v(" "),
                  message.user.status
                    ? _c(
                        "div",
                        {
                          staticClass:
                            "overflow-hidden text-nowrap text-dark-brown overflow-ellipsis small"
                        },
                        [
                          _c("span", { staticClass: "fw-bold" }, [
                            _vm._v(_vm._s(message.user.status))
                          ])
                        ]
                      )
                    : _vm._e()
                ]
              )
            ]
          ),
          _vm._v(" "),
          _c("div", {
            staticClass: "post-body pt-2 pb-2",
            domProps: { innerHTML: _vm._s(message.text) }
          }),
          _vm._v(" "),
          _c(
            "div",
            { staticClass: "post-footer d-flex justify-content-between" },
            [
              _c("div", { staticClass: "overflow-hidden" }, [
                message.ip
                  ? _c("div", { staticClass: "post-meta d-flex" }, [
                      _c("div", { staticClass: "user-ip me-2" }, [
                        _c("a", { attrs: { href: message.search_ip_url } }, [
                          _vm._v(_vm._s(message.ip))
                        ])
                      ]),
                      _vm._v(" "),
                      _c("div", { staticClass: "useragent" }, [
                        _c("span", [_vm._v(_vm._s(message.user_agent))])
                      ])
                    ])
                  : _vm._e()
              ]),
              _vm._v(" "),
              _c("div", { staticClass: "d-flex" }, [
                message.can_reply
                  ? _c("div", { staticClass: "ms-3" }, [
                      _c(
                        "a",
                        {
                          attrs: { href: "#" },
                          on: {
                            click: function($event) {
                              $event.preventDefault()
                              return _vm.reply(message)
                            }
                          }
                        },
                        [_vm._v(_vm._s(_vm.__("reply")))]
                      )
                    ])
                  : _vm._e(),
                _vm._v(" "),
                message.can_quote
                  ? _c("div", { staticClass: "ms-3" }, [
                      _c(
                        "a",
                        {
                          attrs: { href: "#" },
                          on: {
                            click: function($event) {
                              $event.preventDefault()
                              return _vm.quote(message)
                            }
                          }
                        },
                        [_vm._v(_vm._s(_vm.__("quote")))]
                      )
                    ])
                  : _vm._e(),
                _vm._v(" "),
                message.can_delete
                  ? _c("div", { staticClass: "dropdown ms-3" }, [
                      _c(
                        "div",
                        {
                          staticClass: "cursor-pointer",
                          attrs: {
                            "data-bs-toggle": "dropdown",
                            "aria-haspopup": "true",
                            "aria-expanded": "false"
                          }
                        },
                        [
                          _c("svg", { staticClass: "icon text-primary" }, [
                            _c("use", {
                              attrs: {
                                "xlink:href":
                                  "/themes/default/assets/icons/sprite.svg?#more_horizontal"
                              }
                            })
                          ])
                        ]
                      ),
                      _vm._v(" "),
                      _c(
                        "div",
                        { staticClass: "dropdown-menu dropdown-menu-right" },
                        [
                          _c(
                            "a",
                            {
                              staticClass: "dropdown-item",
                              attrs: { href: "" },
                              on: {
                                click: function($event) {
                                  $event.preventDefault()
                                  return _vm.delComment(message.id)
                                }
                              }
                            },
                            [_vm._v(_vm._s(_vm.__("delete")))]
                          )
                        ]
                      )
                    ])
                  : _vm._e()
              ])
            ]
          )
        ])
      }),
      _vm._v(" "),
      _c("pagination", {
        staticClass: "mt-3",
        attrs: { data: _vm.messages },
        on: { "pagination-change-page": _vm.getComments }
      }),
      _vm._v(" "),
      _vm.can_write
        ? _c("div", { staticClass: "mt-4" }, [
            _c("h3", { staticClass: "fw-bold" }, [
              _vm._v(_vm._s(_vm.__("write_comment")))
            ]),
            _vm._v(" "),
            _c(
              "form",
              {
                staticClass: "comment-form",
                attrs: { action: "" },
                on: {
                  submit: function($event) {
                    $event.preventDefault()
                    return _vm.sendComment($event)
                  }
                }
              },
              [
                _vm.error_message
                  ? _c("div", { staticClass: "d-flex" }, [
                      _c(
                        "div",
                        { staticClass: "alert alert-danger d-inline" },
                        [_vm._v(_vm._s(_vm.error_message))]
                      )
                    ])
                  : _vm._e(),
                _vm._v(" "),
                _vm.comment_added_message
                  ? _c("div", { staticClass: "d-flex" }, [
                      _c(
                        "div",
                        { staticClass: "alert alert-success d-inline" },
                        [_vm._v(_vm._s(_vm.comment_added_message))]
                      )
                    ])
                  : _vm._e(),
                _vm._v(" "),
                _c("div", { staticStyle: { "max-width": "800px" } }, [
                  _c("div", { staticClass: "form-group" }, [
                    _c("textarea", {
                      directives: [
                        {
                          name: "model",
                          rawName: "v-model",
                          value: _vm.comment_text,
                          expression: "comment_text"
                        }
                      ],
                      staticClass: "form-control",
                      attrs: { name: "text", id: "comment_text", required: "" },
                      domProps: { value: _vm.comment_text },
                      on: {
                        input: function($event) {
                          if ($event.target.composing) {
                            return
                          }
                          _vm.comment_text = $event.target.value
                        }
                      }
                    })
                  ])
                ]),
                _vm._v(" "),
                _c("div", { staticClass: "mt-2" }, [
                  _c(
                    "button",
                    {
                      staticClass: "btn btn-primary",
                      attrs: {
                        type: "submit",
                        name: "submit",
                        value: "1",
                        disabled: _vm.loading
                      }
                    },
                    [
                      _vm.loading
                        ? _c("span", {
                            staticClass: "spinner-border spinner-border-sm",
                            attrs: { role: "status", "aria-hidden": "true" }
                          })
                        : _vm._e(),
                      _vm._v(
                        "\n          " + _vm._s(_vm.__("send")) + "\n        "
                      )
                    ]
                  ),
                  _vm._v(" "),
                  _c("div")
                ])
              ]
            )
          ])
        : _vm._e()
    ],
    2
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/LikesComponent.vue?vue&type=template&id=3f8b1c74&":
/*!***********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./themes/default/src/js/components/LikesComponent.vue?vue&type=template&id=3f8b1c74& ***!
  \***********************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", { staticClass: "position-relative" }, [
    _vm.loading
      ? _c(
          "div",
          {
            staticClass:
              "d-flex justify-content-center position-absolute w-100 vote-preloader"
          },
          [_vm._m(0)]
        )
      : _vm._e(),
    _vm._v(" "),
    _c(
      "button",
      {
        staticClass: "btn btn-light btn-sm",
        class: _vm.voted > 0 ? "liked" : "",
        attrs: { disabled: _vm.voted > 0 || !_vm.can_vote },
        on: {
          click: function($event) {
            return _vm.setVote(1)
          }
        }
      },
      [
        _c("svg", { staticClass: "icon download-button-icon mt-n1" }, [
          _c("use", {
            attrs: {
              "xlink:href": "/themes/default/assets/icons/sprite.svg#like"
            }
          })
        ])
      ]
    ),
    _vm._v(" "),
    _c("span", { staticClass: "ms-2 me-2 fw-bold", class: _vm.rating_color }, [
      _vm._v(_vm._s(_vm.rating > 0 ? "+" : "") + _vm._s(_vm.rating))
    ]),
    _vm._v(" "),
    _c(
      "button",
      {
        staticClass: "btn btn-light btn-sm",
        class: _vm.voted < 0 ? "disliked" : "",
        attrs: { disabled: _vm.voted < 0 || !_vm.can_vote },
        on: {
          click: function($event) {
            return _vm.setVote(0)
          }
        }
      },
      [
        _c("svg", { staticClass: "icon download-button-icon me-1" }, [
          _c("use", {
            attrs: {
              "xlink:href": "/themes/default/assets/icons/sprite.svg#dislike"
            }
          })
        ])
      ]
    )
  ])
}
var staticRenderFns = [
  function() {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c(
      "div",
      {
        staticClass: "spinner-border text-secondary",
        attrs: { role: "status" }
      },
      [_c("span", { staticClass: "visually-hidden" }, [_vm._v("Loading...")])]
    )
  }
]
render._withStripped = true



/***/ }),

/***/ "./themes/default/src/js sync recursive \\.vue$/":
/*!*********************************************!*\
  !*** ./themes/default/src/js/ sync \.vue$/ ***!
  \*********************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var map = {
	"./components/CkeditorInputComponent.vue": "./themes/default/src/js/components/CkeditorInputComponent.vue",
	"./components/CommentsComponent.vue": "./themes/default/src/js/components/CommentsComponent.vue",
	"./components/LikesComponent.vue": "./themes/default/src/js/components/LikesComponent.vue"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "./themes/default/src/js sync recursive \\.vue$/";

/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ "use strict";
/******/ 
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["themes/default/assets/css/app","/themes/default/assets/js/vendor"], () => (__webpack_exec__("./themes/default/src/js/app.js"), __webpack_exec__("./themes/default/src/scss/app.scss")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly9qb2huY21zL3RoZW1lcy9kZWZhdWx0L3NyYy9qcy9jb21wb25lbnRzL0NrZWRpdG9ySW5wdXRDb21wb25lbnQudnVlIiwid2VicGFjazovL2pvaG5jbXMvdGhlbWVzL2RlZmF1bHQvc3JjL2pzL2NvbXBvbmVudHMvQ29tbWVudHNDb21wb25lbnQudnVlIiwid2VicGFjazovL2pvaG5jbXMvdGhlbWVzL2RlZmF1bHQvc3JjL2pzL2NvbXBvbmVudHMvTGlrZXNDb21wb25lbnQudnVlIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvYXBwLmpzIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvYm9vdHN0cmFwLmpzIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvZm9ydW0uanMiLCJ3ZWJwYWNrOi8vam9obmNtcy8uL3RoZW1lcy9kZWZhdWx0L3NyYy9qcy9qcXVlcnkubWFnbmlmaWMtcG9wdXAuanMiLCJ3ZWJwYWNrOi8vam9obmNtcy8uL3RoZW1lcy9kZWZhdWx0L3NyYy9qcy9tYWluLmpzIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvbWVudS5qcyIsIndlYnBhY2s6Ly9qb2huY21zLy4vdGhlbWVzL2RlZmF1bHQvc3JjL2pzL21vZGFscy5qcyIsIndlYnBhY2s6Ly9qb2huY21zLy4vdGhlbWVzL2RlZmF1bHQvc3JjL2pzL3ByaXNtLmpzIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvcHJvZ3Jlc3MuanMiLCJ3ZWJwYWNrOi8vam9obmNtcy8uL3RoZW1lcy9kZWZhdWx0L3NyYy9qcy9zbGlkZXIuanMiLCJ3ZWJwYWNrOi8vam9obmNtcy8uL3RoZW1lcy9kZWZhdWx0L3NyYy9qcy93eXNpYmIuanMiLCJ3ZWJwYWNrOi8vam9obmNtcy8uL3RoZW1lcy9kZWZhdWx0L3NyYy9zY3NzL2FwcC5zY3NzPzZjNTYiLCJ3ZWJwYWNrOi8vam9obmNtcy8uL3RoZW1lcy9kZWZhdWx0L3NyYy9qcy9jb21wb25lbnRzL0NrZWRpdG9ySW5wdXRDb21wb25lbnQudnVlIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvY29tcG9uZW50cy9Db21tZW50c0NvbXBvbmVudC52dWUiLCJ3ZWJwYWNrOi8vam9obmNtcy8uL3RoZW1lcy9kZWZhdWx0L3NyYy9qcy9jb21wb25lbnRzL0xpa2VzQ29tcG9uZW50LnZ1ZSIsIndlYnBhY2s6Ly9qb2huY21zLy4vdGhlbWVzL2RlZmF1bHQvc3JjL2pzL2NvbXBvbmVudHMvQ2tlZGl0b3JJbnB1dENvbXBvbmVudC52dWU/NmU1NiIsIndlYnBhY2s6Ly9qb2huY21zLy4vdGhlbWVzL2RlZmF1bHQvc3JjL2pzL2NvbXBvbmVudHMvQ29tbWVudHNDb21wb25lbnQudnVlPzAwZjUiLCJ3ZWJwYWNrOi8vam9obmNtcy8uL3RoZW1lcy9kZWZhdWx0L3NyYy9qcy9jb21wb25lbnRzL0xpa2VzQ29tcG9uZW50LnZ1ZT8zMjBkIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvY29tcG9uZW50cy9Da2VkaXRvcklucHV0Q29tcG9uZW50LnZ1ZT9iOGJhIiwid2VicGFjazovL2pvaG5jbXMvLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvY29tcG9uZW50cy9Db21tZW50c0NvbXBvbmVudC52dWU/MDk0MiIsIndlYnBhY2s6Ly9qb2huY21zLy4vdGhlbWVzL2RlZmF1bHQvc3JjL2pzL2NvbXBvbmVudHMvTGlrZXNDb21wb25lbnQudnVlPzA2YmIiLCJ3ZWJwYWNrOi8vam9obmNtcy8uL3RoZW1lcy9kZWZhdWx0L3NyYy9qc3xzeW5jfC9cXC52dWUkL2kiXSwibmFtZXMiOlsicmVxdWlyZSIsImZpbGVzIiwia2V5cyIsIm1hcCIsImtleSIsIlZ1ZSIsInNwbGl0IiwicG9wIiwidnVlX2FwcHMiLCJkb2N1bWVudCIsInF1ZXJ5U2VsZWN0b3JBbGwiLCJmb3JFYWNoIiwiZWwiLCJ3aW5kb3ciLCJQb3BwZXIiLCIkIiwialF1ZXJ5IiwiYXhpb3MiLCJkZWZhdWx0cyIsImhlYWRlcnMiLCJjb21tb24iLCJfIiwiZSIsIm9uIiwidG9nZ2xlUHJldmlldyIsInRvZ2dsZSIsImVhY2giLCJtYWduaWZpY1BvcHVwIiwiZGVsZWdhdGUiLCJ0eXBlIiwidExvYWRpbmciLCJtYWluQ2xhc3MiLCJnYWxsZXJ5IiwiZW5hYmxlZCIsIm5hdmlnYXRlQnlJbWdDbGljayIsInByZWxvYWQiLCJpbWFnZSIsInRFcnJvciIsInRpdGxlU3JjIiwiaXRlbSIsImF0dHIiLCJ6b29tIiwiZHVyYXRpb24iLCJvcGVuZXIiLCJlbGVtZW50IiwiZmluZCIsInZlcnRpY2FsRml0IiwidG9vbHRpcCIsImZpbGVOYW1lIiwidmFsIiwic2libGluZ3MiLCJhZGRDbGFzcyIsImh0bWwiLCJmYWN0b3J5IiwiZGVmaW5lIiwiQ0xPU0VfRVZFTlQiLCJCRUZPUkVfQ0xPU0VfRVZFTlQiLCJBRlRFUl9DTE9TRV9FVkVOVCIsIkJFRk9SRV9BUFBFTkRfRVZFTlQiLCJNQVJLVVBfUEFSU0VfRVZFTlQiLCJPUEVOX0VWRU5UIiwiQ0hBTkdFX0VWRU5UIiwiTlMiLCJFVkVOVF9OUyIsIlJFQURZX0NMQVNTIiwiUkVNT1ZJTkdfQ0xBU1MiLCJQUkVWRU5UX0NMT1NFX0NMQVNTIiwibWZwIiwiTWFnbmlmaWNQb3B1cCIsIl9pc0pRIiwiX3ByZXZTdGF0dXMiLCJfd2luZG93IiwiX2RvY3VtZW50IiwiX3ByZXZDb250ZW50VHlwZSIsIl93cmFwQ2xhc3NlcyIsIl9jdXJyUG9wdXBUeXBlIiwiX21mcE9uIiwibmFtZSIsImYiLCJldiIsIl9nZXRFbCIsImNsYXNzTmFtZSIsImFwcGVuZFRvIiwicmF3IiwiY3JlYXRlRWxlbWVudCIsImlubmVySFRNTCIsImFwcGVuZENoaWxkIiwiX21mcFRyaWdnZXIiLCJkYXRhIiwidHJpZ2dlckhhbmRsZXIiLCJzdCIsImNhbGxiYWNrcyIsImNoYXJBdCIsInRvTG93ZXJDYXNlIiwic2xpY2UiLCJhcHBseSIsImlzQXJyYXkiLCJfZ2V0Q2xvc2VCdG4iLCJjdXJyVGVtcGxhdGUiLCJjbG9zZUJ0biIsImNsb3NlTWFya3VwIiwicmVwbGFjZSIsInRDbG9zZSIsIl9jaGVja0luc3RhbmNlIiwiaW5zdGFuY2UiLCJpbml0Iiwic3VwcG9ydHNUcmFuc2l0aW9ucyIsInMiLCJzdHlsZSIsInYiLCJ1bmRlZmluZWQiLCJsZW5ndGgiLCJwcm90b3R5cGUiLCJjb25zdHJ1Y3RvciIsImFwcFZlcnNpb24iLCJuYXZpZ2F0b3IiLCJpc0xvd0lFIiwiaXNJRTgiLCJhbGwiLCJhZGRFdmVudExpc3RlbmVyIiwiaXNBbmRyb2lkIiwidGVzdCIsImlzSU9TIiwic3VwcG9ydHNUcmFuc2l0aW9uIiwicHJvYmFibHlNb2JpbGUiLCJ1c2VyQWdlbnQiLCJwb3B1cHNDYWNoZSIsIm9wZW4iLCJpIiwiaXNPYmoiLCJpdGVtcyIsInRvQXJyYXkiLCJpbmRleCIsInBhcnNlZCIsImlzT3BlbiIsInVwZGF0ZUl0ZW1IVE1MIiwidHlwZXMiLCJtYWluRWwiLCJlcSIsImV4dGVuZCIsImZpeGVkQ29udGVudFBvcyIsIm1vZGFsIiwiY2xvc2VPbkNvbnRlbnRDbGljayIsImNsb3NlT25CZ0NsaWNrIiwic2hvd0Nsb3NlQnRuIiwiZW5hYmxlRXNjYXBlS2V5IiwiYmdPdmVybGF5IiwiY2xvc2UiLCJ3cmFwIiwiX2NoZWNrSWZDbG9zZSIsInRhcmdldCIsImNvbnRhaW5lciIsImNvbnRlbnRDb250YWluZXIiLCJwcmVsb2FkZXIiLCJtb2R1bGVzIiwibiIsInRvVXBwZXJDYXNlIiwiY2FsbCIsImNsb3NlQnRuSW5zaWRlIiwiYXBwZW5kIiwidGVtcGxhdGUiLCJ2YWx1ZXMiLCJjbG9zZV9yZXBsYWNlV2l0aCIsImFsaWduVG9wIiwiY3NzIiwib3ZlcmZsb3ciLCJvdmVyZmxvd1kiLCJvdmVyZmxvd1giLCJ0b3AiLCJzY3JvbGxUb3AiLCJwb3NpdGlvbiIsImZpeGVkQmdQb3MiLCJoZWlnaHQiLCJrZXlDb2RlIiwidXBkYXRlU2l6ZSIsIndpbmRvd0hlaWdodCIsIndIIiwid2luZG93U3R5bGVzIiwiX2hhc1Njcm9sbEJhciIsIl9nZXRTY3JvbGxiYXJTaXplIiwibWFyZ2luUmlnaHQiLCJpc0lFNyIsImNsYXNzZXNUb2FkZCIsIl9hZGRDbGFzc1RvTUZQIiwiYWRkIiwicHJlcGVuZFRvIiwiYm9keSIsIl9sYXN0Rm9jdXNlZEVsIiwiYWN0aXZlRWxlbWVudCIsInNldFRpbWVvdXQiLCJjb250ZW50IiwiX3NldEZvY3VzIiwiX29uRm9jdXNJbiIsInJlbW92YWxEZWxheSIsIl9jbG9zZSIsImNsYXNzZXNUb1JlbW92ZSIsImRldGFjaCIsImVtcHR5IiwiX3JlbW92ZUNsYXNzRnJvbU1GUCIsIm9mZiIsInJlbW92ZUF0dHIiLCJjdXJySXRlbSIsImF1dG9Gb2N1c0xhc3QiLCJmb2N1cyIsInByZXZIZWlnaHQiLCJ3aW5IZWlnaHQiLCJ6b29tTGV2ZWwiLCJkb2N1bWVudEVsZW1lbnQiLCJjbGllbnRXaWR0aCIsImlubmVyV2lkdGgiLCJpbm5lckhlaWdodCIsInBhcnNlRWwiLCJtYXJrdXAiLCJyZW1vdmVDbGFzcyIsIm5ld0NvbnRlbnQiLCJhcHBlbmRDb250ZW50IiwicHJlbG9hZGVkIiwicHJlcGVuZCIsInRhZ05hbWUiLCJzcmMiLCJoYXNDbGFzcyIsImFkZEdyb3VwIiwib3B0aW9ucyIsImVIYW5kbGVyIiwibWZwRWwiLCJfb3BlbkNsaWNrIiwiZU5hbWUiLCJtaWRDbGljayIsIndoaWNoIiwiY3RybEtleSIsIm1ldGFLZXkiLCJhbHRLZXkiLCJzaGlmdEtleSIsImRpc2FibGVPbiIsImlzRnVuY3Rpb24iLCJ3aWR0aCIsInByZXZlbnREZWZhdWx0Iiwic3RvcFByb3BhZ2F0aW9uIiwidXBkYXRlU3RhdHVzIiwic3RhdHVzIiwidGV4dCIsInN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbiIsImNsb3NlT25Db250ZW50IiwiY2xvc2VPbkJnIiwiY29udGFpbnMiLCJjTmFtZSIsInNjcm9sbEhlaWdodCIsIl9wYXJzZU1hcmt1cCIsImFyciIsInZhbHVlIiwicmVwbGFjZVdpdGgiLCJpcyIsInNjcm9sbGJhclNpemUiLCJzY3JvbGxEaXYiLCJjc3NUZXh0Iiwib2Zmc2V0V2lkdGgiLCJyZW1vdmVDaGlsZCIsInByb3RvIiwicmVnaXN0ZXJNb2R1bGUiLCJtb2R1bGUiLCJwdXNoIiwiZm4iLCJqcUVsIiwiaXRlbU9wdHMiLCJwYXJzZUludCIsImFyZ3VtZW50cyIsIkFycmF5IiwiSU5MSU5FX05TIiwiX2hpZGRlbkNsYXNzIiwiX2lubGluZVBsYWNlaG9sZGVyIiwiX2xhc3RJbmxpbmVFbGVtZW50IiwiX3B1dElubGluZUVsZW1lbnRzQmFjayIsImFmdGVyIiwiaGlkZGVuQ2xhc3MiLCJ0Tm90Rm91bmQiLCJpbml0SW5saW5lIiwiZ2V0SW5saW5lIiwiaW5saW5lU3QiLCJpbmxpbmUiLCJwYXJlbnQiLCJwYXJlbnROb2RlIiwiaW5saW5lRWxlbWVudCIsIkFKQVhfTlMiLCJfYWpheEN1ciIsIl9yZW1vdmVBamF4Q3Vyc29yIiwiX2Rlc3Ryb3lBamF4UmVxdWVzdCIsInJlcSIsImFib3J0Iiwic2V0dGluZ3MiLCJjdXJzb3IiLCJpbml0QWpheCIsImFqYXgiLCJnZXRBamF4Iiwib3B0cyIsInVybCIsInN1Y2Nlc3MiLCJ0ZXh0U3RhdHVzIiwianFYSFIiLCJ0ZW1wIiwieGhyIiwiZmluaXNoZWQiLCJlcnJvciIsImxvYWRFcnJvciIsIl9pbWdJbnRlcnZhbCIsIl9nZXRUaXRsZSIsInRpdGxlIiwiaW5pdEltYWdlIiwiaW1nU3QiLCJucyIsInJlc2l6ZUltYWdlIiwiaW1nIiwiZGVjciIsIl9vbkltYWdlSGFzU2l6ZSIsImhhc1NpemUiLCJjbGVhckludGVydmFsIiwiaXNDaGVja2luZ0ltZ1NpemUiLCJpbWdIaWRkZW4iLCJmaW5kSW1hZ2VTaXplIiwiY291bnRlciIsIm1mcFNldEludGVydmFsIiwiZGVsYXkiLCJzZXRJbnRlcnZhbCIsIm5hdHVyYWxXaWR0aCIsImdldEltYWdlIiwiZ3VhcmQiLCJvbkxvYWRDb21wbGV0ZSIsImNvbXBsZXRlIiwibG9hZGVkIiwib25Mb2FkRXJyb3IiLCJhbHQiLCJjbG9uZSIsImltZ19yZXBsYWNlV2l0aCIsImxvYWRpbmciLCJoYXNNb3pUcmFuc2Zvcm0iLCJnZXRIYXNNb3pUcmFuc2Zvcm0iLCJNb3pUcmFuc2Zvcm0iLCJlYXNpbmciLCJpbml0Wm9vbSIsInpvb21TdCIsImdldEVsVG9BbmltYXRlIiwibmV3SW1nIiwidHJhbnNpdGlvbiIsImNzc09iaiIsInpJbmRleCIsImxlZnQiLCJ0Iiwic2hvd01haW5Db250ZW50Iiwib3BlblRpbWVvdXQiLCJhbmltYXRlZEltZyIsIl9hbGxvd1pvb20iLCJjbGVhclRpbWVvdXQiLCJfZ2V0SXRlbVRvWm9vbSIsIl9nZXRPZmZzZXQiLCJyZW1vdmUiLCJpc0xhcmdlIiwib2Zmc2V0IiwicGFkZGluZ1RvcCIsInBhZGRpbmdCb3R0b20iLCJvYmoiLCJvZmZzZXRIZWlnaHQiLCJJRlJBTUVfTlMiLCJfZW1wdHlQYWdlIiwiX2ZpeElmcmFtZUJ1Z3MiLCJpc1Nob3dpbmciLCJzcmNBY3Rpb24iLCJwYXR0ZXJucyIsInlvdXR1YmUiLCJpZCIsInZpbWVvIiwiZ21hcHMiLCJpbml0SWZyYW1lIiwicHJldlR5cGUiLCJuZXdUeXBlIiwiZ2V0SWZyYW1lIiwiZW1iZWRTcmMiLCJpZnJhbWVTdCIsImlmcmFtZSIsImluZGV4T2YiLCJzdWJzdHIiLCJsYXN0SW5kZXhPZiIsImRhdGFPYmoiLCJfZ2V0TG9vcGVkSWQiLCJudW1TbGlkZXMiLCJfcmVwbGFjZUN1cnJUb3RhbCIsImN1cnIiLCJ0b3RhbCIsImFycm93TWFya3VwIiwiYXJyb3dzIiwidFByZXYiLCJ0TmV4dCIsInRDb3VudGVyIiwiaW5pdEdhbGxlcnkiLCJnU3QiLCJkaXJlY3Rpb24iLCJuZXh0IiwicHJldiIsImwiLCJhcnJvd0xlZnQiLCJhcnJvd1JpZ2h0IiwiY2xpY2siLCJfcHJlbG9hZFRpbWVvdXQiLCJwcmVsb2FkTmVhcmJ5SW1hZ2VzIiwiZ29UbyIsIm5ld0luZGV4IiwicCIsInByZWxvYWRCZWZvcmUiLCJNYXRoIiwibWluIiwicHJlbG9hZEFmdGVyIiwiX3ByZWxvYWRJdGVtIiwiUkVUSU5BX05TIiwicmVwbGFjZVNyYyIsIm0iLCJyYXRpbyIsImluaXRSZXRpbmEiLCJkZXZpY2VQaXhlbFJhdGlvIiwicmV0aW5hIiwiaXNOYU4iLCJQcmlzbSIsIm1hbnVhbCIsInNjcm9sbF9idXR0b24iLCJoaWdobGlnaHRBbGxVbmRlciIsInNjcm9sbCIsImV2ZW50IiwiYW5pbWF0ZSIsInJlYWR5Iiwid3lzaWJiX2lucHV0Iiwid3lzaWJiIiwid3lzaWJiX3NldHRpbmdzIiwiZmxhdHBpY2tyIiwiZGF0ZUZvcm1hdCIsImVuYWJsZVRpbWUiLCJ0b2dnbGVfbWVudSIsImdldFNwaW5uZXIiLCJhamF4X21vZGFsIiwiYnV0dG9uIiwicmVsYXRlZFRhcmdldCIsInBhcmFtcyIsImRhdGFUeXBlIiwic2VsZWN0X2xhbmd1YWdlX2Zvcm0iLCJzZXJpYWxpemUiLCJsb2NhdGlvbiIsImhyZWYiLCJfc2VsZiIsIldvcmtlckdsb2JhbFNjb3BlIiwic2VsZiIsInUiLCJjIiwiciIsImRpc2FibGVXb3JrZXJNZXNzYWdlSGFuZGxlciIsInV0aWwiLCJlbmNvZGUiLCJMIiwiYWxpYXMiLCJPYmplY3QiLCJ0b1N0cmluZyIsIm9iaklkIiwiX19pZCIsImRlZmluZVByb3BlcnR5IiwiYSIsIm8iLCJoYXNPd25Qcm9wZXJ0eSIsImN1cnJlbnRTY3JpcHQiLCJFcnJvciIsImV4ZWMiLCJzdGFjayIsImdldEVsZW1lbnRzQnlUYWdOYW1lIiwibGFuZ3VhZ2VzIiwiaW5zZXJ0QmVmb3JlIiwiREZTIiwicGx1Z2lucyIsImhpZ2hsaWdodEFsbCIsImNhbGxiYWNrIiwic2VsZWN0b3IiLCJob29rcyIsInJ1biIsImhpZ2hsaWdodEVsZW1lbnQiLCJtYXRjaCIsIm5vZGVOYW1lIiwibGFuZ3VhZ2UiLCJncmFtbWFyIiwiY29kZSIsInRleHRDb250ZW50IiwiaGlnaGxpZ2h0ZWRDb2RlIiwiV29ya2VyIiwiZmlsZW5hbWUiLCJvbm1lc3NhZ2UiLCJwb3N0TWVzc2FnZSIsIkpTT04iLCJzdHJpbmdpZnkiLCJpbW1lZGlhdGVDbG9zZSIsImhpZ2hsaWdodCIsInRva2VucyIsInRva2VuaXplIiwibWF0Y2hHcmFtbWFyIiwiZyIsImluc2lkZSIsImxvb2tiZWhpbmQiLCJkIiwiZ3JlZWR5IiwiaCIsInBhdHRlcm4iLCJnbG9iYWwiLCJSZWdFeHAiLCJzb3VyY2UiLCJ5IiwiayIsImxhc3RJbmRleCIsIk8iLCJiIiwidyIsIkEiLCJQIiwieCIsIlMiLCJqIiwiTiIsIkUiLCJDIiwic3BsaWNlIiwicmVzdCIsIlRva2VuIiwiam9pbiIsInRhZyIsImNsYXNzZXMiLCJhdHRyaWJ1dGVzIiwicGFyc2UiLCJoYXNBdHRyaWJ1dGUiLCJyZWFkeVN0YXRlIiwiZGVmZXIiLCJyZXF1ZXN0QW5pbWF0aW9uRnJhbWUiLCJleHBvcnRzIiwiY29tbWVudCIsInByb2xvZyIsImRvY3R5cGUiLCJjZGF0YSIsInB1bmN0dWF0aW9uIiwibmFtZXNwYWNlIiwiZW50aXR5IiwieG1sIiwibWF0aG1sIiwic3ZnIiwiYXRydWxlIiwicnVsZSIsInN0cmluZyIsInByb3BlcnR5IiwiaW1wb3J0YW50IiwiYWRkSW5saW5lZCIsImNsaWtlIiwia2V5d29yZCIsIm51bWJlciIsIm9wZXJhdG9yIiwiamF2YXNjcmlwdCIsInJlZ2V4IiwicGFyYW1ldGVyIiwiY29uc3RhbnQiLCJpbnRlcnBvbGF0aW9uIiwianMiLCJkZWZpbmVQcm9wZXJ0aWVzIiwiYnVpbGRQbGFjZWhvbGRlcnMiLCJ0b2tlblN0YWNrIiwidG9rZW5pemVQbGFjZWhvbGRlcnMiLCJzdWJzdHJpbmciLCJjb25jYXQiLCJwaHAiLCJkZWxpbWl0ZXIiLCJ2YXJpYWJsZSIsImphdmFkb2NsaWtlIiwiYWRkU3VwcG9ydCIsInBocGRvYyIsInNjb3BlIiwic3FsIiwicXVlcnlTZWxlY3RvciIsImRpc3BsYXkiLCJnZXRCb3VuZGluZ0NsaWVudFJlY3QiLCJjaGlsZHJlbiIsImdldENvbXB1dGVkU3R5bGUiLCJjdXJyZW50U3R5bGUiLCJzZXRBdHRyaWJ1dGUiLCJjb3VudGVyUmVzZXQiLCJnZXRBdHRyaWJ1dGUiLCJsaW5lTnVtYmVycyIsImdldExpbmUiLCJjbGFzc0xpc3QiLCJyaWdodCIsInBlcmNlbnRhZ2VUb0RlZ3JlZXMiLCJwZXJjZW50YWdlIiwic3dpcGVyU2xpZGVyIiwiU3dpcGVyIiwic2xpZGVzUGVyVmlldyIsInNwYWNlQmV0d2VlbiIsInBhZ2luYXRpb24iLCJjbGlja2FibGUiLCJicmVha3BvaW50cyIsIldCQkxBTkciLCJDVVJMQU5HIiwiYm9sZCIsIml0YWxpYyIsInVuZGVybGluZSIsInN0cmlrZSIsImxpbmsiLCJzdXAiLCJzdWIiLCJqdXN0aWZ5bGVmdCIsImp1c3RpZnljZW50ZXIiLCJqdXN0aWZ5cmlnaHQiLCJ0YWJsZSIsImJ1bGxpc3QiLCJudW1saXN0IiwicXVvdGUiLCJvZmZ0b3AiLCJzcG9pbGVyIiwiZm9udGNvbG9yIiwiZm9udHNpemUiLCJmb250ZmFtaWx5IiwiZnNfdmVyeXNtYWxsIiwiZnNfc21hbGwiLCJmc19ub3JtYWwiLCJmc19iaWciLCJmc192ZXJ5YmlnIiwic21pbGVib3giLCJ2aWRlbyIsInJlbW92ZUZvcm1hdCIsIm1vZGFsX2xpbmtfdGl0bGUiLCJtb2RhbF9saW5rX3RleHQiLCJtb2RhbF9saW5rX3VybCIsIm1vZGFsX2VtYWlsX3RleHQiLCJtb2RhbF9lbWFpbF91cmwiLCJtb2RhbF9saW5rX3RhYjEiLCJtb2RhbF9pbWdfdGl0bGUiLCJtb2RhbF9pbWdfdGFiMSIsIm1vZGFsX2ltZ190YWIyIiwibW9kYWxfaW1nc3JjX3RleHQiLCJtb2RhbF9pbWdfYnRuIiwiYWRkX2F0dGFjaCIsIm1vZGFsX3ZpZGVvX3RleHQiLCJzYXZlIiwiY2FuY2VsIiwidmFsaWRhdGlvbl9lcnIiLCJlcnJvcl9vbnVwbG9hZCIsImZpbGV1cGxvYWRfdGV4dDEiLCJmaWxldXBsb2FkX3RleHQyIiwiYXV0byIsInZpZXdzIiwiZG93bmxvYWRzIiwic20xIiwic20yIiwic20zIiwic200Iiwic201Iiwic202Iiwic203Iiwic204Iiwic205Iiwid2JiZGVidWciLCJ0eHRBcmVhIiwiZGVmbGFuZyIsImxhbmciLCIkdHh0QXJlYSIsInNldFVJRCIsImJibW9kZSIsIm9ubHlCQm1vZGUiLCJ0aGVtZU5hbWUiLCJib2R5Q2xhc3MiLCJ0YWJJbnNlcnQiLCJpbWd1cGxvYWQiLCJpbWdfdXBsb2FkdXJsIiwiaW1nX21heHdpZHRoIiwiaW1nX21heGhlaWdodCIsImhvdGtleXMiLCJzaG93SG90a2V5cyIsImF1dG9yZXNpemUiLCJyZXNpemVfbWF4aGVpZ2h0IiwibG9hZFBhZ2VTdHlsZXMiLCJ0cmFjZVRleHRhcmVhIiwic21pbGVDb252ZXJzaW9uIiwiYnV0dG9ucyIsImFsbEJ1dHRvbnMiLCJidXR0b25IVE1MIiwiZXhjbWQiLCJob3RrZXkiLCJ0cmFuc2Zvcm0iLCJ0YWJzIiwiaW5wdXQiLCJwYXJhbSIsInZhbGlkYXRpb24iLCJhZGRXcmFwIiwib25Mb2FkIiwiaW1nTG9hZE1vZGFsIiwiYnV0dG9uVGV4dCIsIm9ubHlDbGVhclRleHQiLCJ2YWx1ZUJCbmFtZSIsInN1Ykluc2VydCIsImNvbG9ycyIsImNvbHMiLCJyb3dzIiwiY2VsbHdpZHRoIiwic2tpcFJ1bGVzIiwiZXh2YWx1ZSIsImdyb3Vwa2V5Iiwib25TdWJtaXQiLCJjbWQiLCJvcHQiLCJxdWVyeVN0YXRlIiwiJG1vZGFsIiwiaW5zZXJ0QXRDdXJzb3IiLCJnZXRDb2RlQnlDb21tYW5kIiwiY2xvc2VNb2RhbCIsInVwZGF0ZVVJIiwicmVtb3ZlZm9ybWF0Iiwic3lzdHIiLCJjdXN0b21SdWxlcyIsInRkIiwic2VsdGV4dCIsInJneCIsInNlbCIsInRyIiwic21pbGVMaXN0IiwiYXR0cldyYXAiLCJpbml0ZWQiLCJ0aGVtZVByZWZpeCIsInByb3h5IiwiaWR4Iiwic3JpcHRNYXRjaCIsImdldCIsIldCQlBSRVNFVCIsImxhc3RpZCIsImxvZyIsImlzTW9iaWxlIiwidmVuZG9yIiwib3BlcmEiLCJjb250cm9sbGVycyIsInNtaWxlRmluZCIsImluaXRUcmFuc2Zvcm1zIiwiYnVpbGQiLCJpbml0TW9kYWwiLCJpbml0SG90a2V5cyIsInNvcnQiLCJiYmNvZGUiLCJwYXJlbnRzIiwiYmluZCIsInN5bmMiLCJpbml0Q2FsbGJhY2siLCJydWxlcyIsImdyb3VwcyIsImJ0bmxpc3QiLCJiaWR4Iiwib2IiLCJlbiIsInNpbXBsZWJiY29kZSIsIm9saXN0Iiwib3AiLCJpbkFycmF5Iiwib2J0ciIsImJodG1sIiwib3JpZ2h0bWwiLCJiYlNlbGVjdG9yIiwid3JhcEF0dHJzIiwiJGJlbCIsImVsRnJvbVN0cmluZyIsInJvb3RTZWxlY3RvciIsImZpbHRlckJ5Tm9kZSIsIm5odG1sMiIsInVud3JhcEF0dHJzIiwib2JodG1sIiwiY3J1bGVzIiwiZ2V0QXR0cmlidXRlTGlzdCIsInJuYW1lIiwiZ2V0VmFsaWRhdGlvblJHWCIsInJlbEZpbHRlckJ5Tm9kZSIsInJlZ1JlcGwiLCJnZXRSZWdleHBSZXBsYWNlIiwidHJpbSIsInNsIiwiY29udGVudHMiLCJmaWx0ZXIiLCJub2RlVHlwZSIsInJlbCIsInR4dCIsIm5lbCIsInN0YXJ0IiwiYWZ0ZXJfdHh0IiwibmJodG1sIiwiY2xlYXJ0ZXh0Iiwic29ydEFycmF5IiwiaHRtbGwiLCJiYiIsInNydWxlcyIsInNtIiwiJHNtIiwic3RyZiIsInJvb3RzZWwiLCJyc2VsbGlzdCIsIiRlZGl0b3IiLCJpbnNlcnRBZnRlciIsInN0YXJ0SGVpZ2h0Iiwib3V0ZXJIZWlnaHQiLCJidWlsZFRvb2xiYXIiLCJtaW5oZWlnaHQiLCJtYXhoZWlnaHQiLCJtaGVpZ2h0IiwiJGJvZHkiLCJoaWRlIiwiJHRvb2xiYXIiLCJjb250ZW50RWRpdGFibGUiLCJleGVjQ29tbWFuZCIsInR4dEFyZWFJbml0Q29udGVudCIsIiRwYXN0ZUJsb2NrIiwic2F2ZVJhbmdlIiwiY2xlYXJQYXN0ZSIsInJkYXRhIiwiYmx1ciIsImlzSW5DbGVhclRleHRCbG9jayIsInRvQkIiLCJzZWxlY3RSYW5nZSIsImxhc3RSYW5nZSIsInNlbGVjdE5vZGUiLCJpc0xpIiwiaXNDb250YWluIiwiZ2V0U2VsZWN0Tm9kZSIsImNoZWNrRm9yTGFzdEJSIiwicHJlc3NUYWIiLCJjbGVhckxhc3RSYW5nZSIsInRyYWNlVGV4dGFyZWFFdmVudCIsInByZXNza2V5IiwiJGJyZXNpemUiLCJ3ZHJhZyIsImF4aXNZIiwiaW1nTGlzdGVuZXJzIiwidWl0aW1lciIsInRvb2xiYXIiLCIkYnRuQ29udGFpbmVyIiwiYm4iLCJidWlsZENvbG9ycGlja2VyIiwiYnVpbGRUYWJsZXBpY2tlciIsImJ1aWxkU2VsZWN0IiwiYnVpbGRTbWlsZWJveCIsImJ1aWxkQnV0dG9uIiwiaG92ZXIiLCIkYmJzdyIsImN1cnJlbnRUYXJnZXQiLCJ0b2dnbGVDbGFzcyIsIm1vZGVTd2l0Y2giLCJidG5IVE1MIiwiJGJ0biIsIm1vdXNlZG93biIsInRyaWdnZXIiLCIkY3BsaW5lIiwiJGRyb3BibG9jayIsImNvbG9ybGlzdCIsImNvbG9yIiwiYmFzZWNvbG9yIiwiZHJvcGRvd25jbGljayIsInNlbGVjdExhc3RSYW5nZSIsIiRsaXN0YmxvY2siLCJhbGxjb3VudCIsInJjIiwiJHNibG9jayIsIiRzdmFsIiwiJHNlbGVjdGJveCIsIm9uYW1lIiwib3B0aW9uIiwiJGVsIiwiY21kdmFsdWUiLCJwcm9wIiwiY2hhbmdlIiwiJG8iLCJvaWQiLCIkYnRuSFRNTCIsImRpc05vbkFjdGl2ZUJ1dHRvbnMiLCJlc2NNb2RhbCIsImtsaXN0IiwibWV0YXN1bSIsImNvbW1hbmQiLCJza2lwY21kIiwid2JiUmVtb3ZlQ2FsbGJhY2siLCJnZXRCQkNvZGVCeUNvbW1hbmQiLCJleGVjTmF0aXZlQ29tbWFuZCIsIndiYkV4ZWNDb21tYW5kIiwid2l0aHZhbHVlIiwiaXNCQkNvbnRhaW4iLCJnZXRQYXJhbXMiLCJxdWVyeUNvbW1hbmRWYWx1ZSIsInJnYlRvSGV4IiwiY2xvc2VzdCIsInF1ZXJ5Q29tbWFuZFN0YXRlIiwic2hvd01vZGFsIiwiZ3JvdXBzZWwiLCJzbm9kZSIsIiRzcCIsIndiYkluc2VydENhbGxiYWNrIiwicGFyYW1vYmoiLCJzZWx0ZXh0SUQiLCJjbGVhciIsInBvcyIsImdldEN1cnNvclBvc0JCIiwic3RleHRudW0iLCJzdGV4dCIsInNldEN1cnNvclBvc0JCIiwibm9kZSIsInJvb3QiLCIkcm9vdCIsImNzIiwic2V0Q3Vyc29yQnlFbCIsImh0bWxkYXRhIiwiZ2V0SFRNTCIsInJuZyIsImdldFJhbmdlIiwic2h0bWwiLCJnZXRTZWxlY3RUZXh0Iiwicm5vZGUiLCJjbGVhckZyb21TdWJJbnNlcnQiLCJpbnMiLCJiZWZvcmVfcm5nIiwiZ2V0U2VsZWN0aW9uIiwiY2xvbmVSYW5nZSIsImNyZWF0ZVRleHRSYW5nZSIsImFmdGVyX3JuZyIsImRpdiIsInNldFN0YXJ0IiwiZmlyc3RDaGlsZCIsInNldEVuZEJlZm9yZSIsInNldFN0YXJ0QWZ0ZXIiLCJzZXRFbmRBZnRlciIsImxhc3RDaGlsZCIsIm1vdmVUb0VsZW1lbnRUZXh0Iiwic2V0RW5kUG9pbnQiLCJiZiIsImFmIiwiJGFmIiwic2VsZWN0aW9uIiwiY3JlYXRlUmFuZ2UiLCJwYXN0ZUhUTUwiLCJicnNwIiwibW92ZVN0YXJ0Iiwic2VsZWN0IiwiZGVsZXRlQ29udGVudHMiLCJpbnNlcnROb2RlIiwiY29sbGFwc2UiLCJyZW1vdmVBbGxSYW5nZXMiLCJhZGRSYW5nZSIsImdldEhUTUxCeUNvbW1hbmQiLCJrZXlzVG9Mb3dlciIsInN0ciIsInZyZ3giLCJ2cmd4cCIsInJiYmNvZGUiLCJtYXhwY291bnQiLCJ2YWxpZCIsInBjb3VudCIsInBuYW1lIiwicG9zdHNlbCIsInJodG1sIiwiZnJvbVR4dEFyZWEiLCJyYW5nZSIsInNlbGVjdGlvbkVuZCIsInNlbGVjdGlvblN0YXJ0IiwiY2xvbmVDb250ZW50cyIsImh0bWxUZXh0IiwiZ2V0UmFuZ2VBdCIsInJhbmdlQ291bnQiLCJhbmNob3JOb2RlIiwiYW5jaG9yT2Zmc2V0Iiwic2V0RW5kIiwiZm9jdXNOb2RlIiwiZm9jdXNPZmZzZXQiLCJmb3JjZUJCTW9kZSIsImNsYmIiLCJzcGxpdFByZXZOZXh0Iiwic24iLCJjb21tb25BbmNlc3RvckNvbnRhaW5lciIsInBhcmVudEVsZW1lbnQiLCJydCIsIm1vdmUiLCJzZWxlY3ROb2RlQ29udGVudHMiLCJkdXBsaWNhdGUiLCJnZXRSYW5nZUNsb25lIiwic2V0Qm9keUZvY3VzIiwiJG4iLCJ2YSIsInZmIiwic3RvcCIsInZhbGlkbmFtZSIsImdldEJCQ29kZSIsImNsZWFyRW1wdHkiLCJyZW1vdmVMYXN0Qm9keUJSIiwiJGUiLCJvdXRiYiIsInJwbCIsInByb2Nlc3NlZCIsInJsaXN0Iiwic2tpcCIsImtlZXBFbGVtZW50Iiwia2VlcEF0dHIiLCIkY2VsIiwiY29udCIsInJlZ2V4cCIsIm5zdHlsZSIsImZpeFRhYmxlVHJhbnNmb3JtIiwiYmJkYXRhIiwic2tpcGx0IiwiYW0iLCJuaHRtbCIsIiR3cmFwIiwiZ2V0SFRNTFNtaWxlcyIsInNtaWxlUlBMIiwibmRhdGEiLCJyb3ciLCJmaWR4IiwiYWZ0ZXJub2RlX3R4dCIsImFmdGVybm9kZSIsImNyZWF0ZVRleHROb2RlIiwic2hpZnQiLCJ3ciIsInByZXBhcmVSR1giLCJiYnJneCIsImxhc3RpbmRleCIsIiRub2RlIiwic3BlY2lmaWVkIiwiJHdyIiwicmVzIiwibmV4dFNpYmxpbmciLCJwcmV2aW91c1NpYmxpbmciLCJzaG93IiwiZW1wdHlGaWx0ZXIiLCJic2VsIiwidHNlbCIsInVuYmluZCIsImRyb3Bkb3duaGFuZGxlciIsImVuZCIsImV2dCIsInJnYiIsImRpZ2l0cyIsImRlYzJoZXgiLCIkYmxvY2siLCJhciIsIiRzZiIsInMyIiwiJHRoaXMiLCJhc2MiLCJzbWlsZWZpbmQiLCIkc21saXN0Iiwib3V0ZXJIVE1MIiwiZGVzdHJveSIsInNhciIsInNtYmIiLCJhZnRlcm5vZGVfY3Vyc29yIiwiaW1nRXZlbnRIYW5kbGVyIiwiaGFzV3JhcGVkSW1hZ2UiLCIkY29udCIsIiR3YmJtIiwiJHVsIiwiJGNudCIsIiRjIiwiaW5wIiwidGlkIiwicHZhbCIsIiR2IiwiZHJhZ2ZpbGV1cGxvYWQiLCJleHRyYVBhcmFtcyIsIm1heHdpZHRoIiwiaW5zZXJ0SW1hZ2UiLCJpbWFnZV9saW5rIiwidGh1bWJfbGluayIsInN1Ym1pdCIsImltZ1N1Ym1pdE1vZGFsIiwicHJpbnRPYmplY3RJbklFIiwiY2hlY2tGaWx0ZXIiLCJkZWJ1ZyIsIm1zZyIsInRpbWUiLCJEYXRlIiwiZ2V0VGltZSIsImNvbnNvbGUiLCJzdGFydFRpbWUiLCJpc0Nocm9tZSIsImNocm9tZSIsImRyYWciLCJkcmFnX21vdXNlZG93biIsInBhZ2VYIiwicGFnZVkiLCJzaGVpZ2h0IiwiZHJhZ19tb3VzZW1vdmUiLCJkcmFnX21vdXNldXAiLCJheGlzWCIsIm5oZWlnaHQiLCJnZXREb2MiLCJkb2MiLCJmcm9tVGV4dEFyZWEiLCJodG1sY29kZSIsIm9ubHlCQk1vZGUiLCJ3YmIiLCJpbWd1cmwiLCJ0aHVtYnVybCIsImVkaXRvciIsInVwbCIsIkZpbGVVcGxvYWQiLCJmaWxlUGFyYW0iLCJ0MSIsInQyIiwiRm9ybURhdGEiLCJ1cGxvYWRQcm9ncmVzcyIsIiRsb2FkZXIiLCJhamF4U2V0dGluZ3MiLCJ1cGxvYWQiLCJvbmRyb3AiLCJ1ZmlsZSIsImRhdGFUcmFuc2ZlciIsImZEYXRhIiwicHJvY2Vzc0RhdGEiLCJjb250ZW50VHlwZSIsInRociJdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7QUFjQTtBQUNBLGdDQURBO0FBRUE7QUFDQTtBQUNBLGtCQURBO0FBRUE7QUFGQSxLQURBO0FBS0E7QUFDQSxrQkFEQTtBQUVBO0FBRkEsS0FMQTtBQVNBO0FBQ0Esa0JBREE7QUFFQTtBQUZBLEtBVEE7QUFhQTtBQUNBLGtCQURBO0FBRUE7QUFGQSxLQWJBO0FBaUJBO0FBQ0Esa0JBREE7QUFFQTtBQUZBLEtBakJBO0FBcUJBO0FBQ0Esa0JBREE7QUFFQTtBQUZBLEtBckJBO0FBeUJBO0FBQ0Esa0JBREE7QUFFQTtBQUZBLEtBekJBO0FBNkJBO0FBQ0Esa0JBREE7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Q1A7QUFDQSxtQkFBbUIsaURBQWlEO0FBQ3BFO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsV0FBVztBQUNYO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZ0JBQWdCLDREQUE0RDtBQUM1RTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZ0JBQWdCLDJDQUEyQztBQUMzRDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsT0FBTztBQUNQO0FBQ0EsbUJBQW1CLGdEQUFnRDtBQUNuRTtBQUNBO0FBQ0E7QUFDQTtBQUNBLFdBQVc7QUFDWDtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZ0JBQWdCO0FBQ2hCLE9BQU87QUFDUCxtQkFBbUIsaUNBQWlDO0FBQ3BEO0FBQ0E7QUFDQTtBQUNBOzs7Ozs7Ozs7Ozs7QUNsRkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7O0FBR0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLHNFIiwiZmlsZSI6Ii90aGVtZXMvZGVmYXVsdC9hc3NldHMvanMvYXBwLmpzIiwic291cmNlc0NvbnRlbnQiOlsiPHRlbXBsYXRlPlxuICA8ZGl2PlxuICAgIDxkaXYgY2xhc3M9XCJmb3JtLWdyb3VwXCI+XG4gICAgICA8bGFiZWwgOmZvcj1cImlkXCI+e3sgbGFiZWwgfX08L2xhYmVsPlxuICAgICAgPHRleHRhcmVhIDpuYW1lPVwibmFtZVwiIDppZD1cImlkXCIgY2xhc3M9XCJmb3JtLWNvbnRyb2xcIiA6Y2xhc3M9XCJjbGFzc2VzICsgKGVycm9ycyA/ICdpcy1pbnZhbGlkJyA6ICcnKVwiIHYtbW9kZWw9XCJtb2RlbF92YWx1ZVwiPjwvdGV4dGFyZWE+XG4gICAgICA8ZGl2IGNsYXNzPVwiaW52YWxpZC1mZWVkYmFjayBkLWJsb2NrXCIgdi1pZj1cImVycm9yc1wiPnt7IGVycm9ycyB9fTwvZGl2PlxuICAgIDwvZGl2PlxuICAgIDxkaXYgdi1mb3I9XCJmaWxlIGluIGF0dGFjaGVkX2ZpbGVzXCI+XG4gICAgICA8aW5wdXQgdHlwZT1cImhpZGRlblwiIG5hbWU9XCJhdHRhY2hlZF9maWxlc1tdXCIgdi1tb2RlbD1cImZpbGUuaWRcIj5cbiAgICA8L2Rpdj5cbiAgPC9kaXY+XG48L3RlbXBsYXRlPlxuXG48c2NyaXB0PlxuZXhwb3J0IGRlZmF1bHQge1xuICBuYW1lOiBcIkNrZWRpdG9ySW5wdXRDb21wb25lbnRcIixcbiAgcHJvcHM6IHtcbiAgICBsYWJlbDoge1xuICAgICAgdHlwZTogU3RyaW5nLFxuICAgICAgZGVmYXVsdDogJ01lc3NhZ2UnXG4gICAgfSxcbiAgICBpZDoge1xuICAgICAgdHlwZTogU3RyaW5nLFxuICAgICAgZGVmYXVsdDogJydcbiAgICB9LFxuICAgIG5hbWU6IHtcbiAgICAgIHR5cGU6IFN0cmluZyxcbiAgICAgIGRlZmF1bHQ6ICcnXG4gICAgfSxcbiAgICBjbGFzc2VzOiB7XG4gICAgICB0eXBlOiBTdHJpbmcsXG4gICAgICBkZWZhdWx0OiAnJ1xuICAgIH0sXG4gICAgdmFsdWU6IHtcbiAgICAgIHR5cGU6IFN0cmluZyxcbiAgICAgIGRlZmF1bHQ6ICcnXG4gICAgfSxcbiAgICBlcnJvcnM6IHtcbiAgICAgIHR5cGU6IFN0cmluZyxcbiAgICAgIGRlZmF1bHQ6ICcnXG4gICAgfSxcbiAgICBsYW5ndWFnZToge1xuICAgICAgdHlwZTogU3RyaW5nLFxuICAgICAgZGVmYXVsdDogJ2VuJ1xuICAgIH0sXG4gICAgdXBsb2FkX3VybDoge1xuICAgICAgdHlwZTogU3RyaW5nLFxuICAgICAgZGVmYXVsdDogJydcbiAgICB9LFxuICB9LFxuICBkYXRhKClcbiAge1xuICAgIHJldHVybiB7XG4gICAgICBtb2RlbF92YWx1ZTogdGhpcy52YWx1ZSxcbiAgICAgIGF0dGFjaGVkX2ZpbGVzOiBbXSxcbiAgICB9XG4gIH0sXG4gIG1vdW50ZWQoKVxuICB7XG4gICAgY29uc3Qgc2VsZiA9IHRoaXM7XG4gICAgbGV0IGNvbmZpZyA9IHtcbiAgICAgIHNpbXBsZVVwbG9hZDoge1xuICAgICAgICB1cGxvYWRVcmw6IHRoaXMudXBsb2FkX3VybCxcbiAgICAgICAgd2l0aENyZWRlbnRpYWxzOiBmYWxzZSxcbiAgICAgICAgc2F2ZWRDYWxsYmFjazogZnVuY3Rpb24gKGZpbGUpIHtcbiAgICAgICAgICBzZWxmLmF0dGFjaGVkX2ZpbGVzLnB1c2goZmlsZSk7XG4gICAgICAgIH0sXG4gICAgICB9LFxuICAgICAgbGFuZ3VhZ2U6IHRoaXMubGFuZ3VhZ2VcbiAgICB9O1xuXG4gICAgQ2xhc3NpY0VkaXRvclxuICAgICAgLmNyZWF0ZShkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcjJyArIHRoaXMuaWQpLCBjb25maWcpXG4gICAgICAudGhlbihlZGl0b3IgPT4ge1xuICAgICAgICB3aW5kb3cuZWRpdG9yID0gZWRpdG9yO1xuICAgICAgfSlcbiAgICAgIC5jYXRjaChlcnJvciA9PiB7XG4gICAgICAgIGNvbnNvbGUuZXJyb3IoZXJyb3IpO1xuICAgICAgfSk7XG4gIH1cbn1cbjwvc2NyaXB0PlxuIiwiPHRlbXBsYXRlPlxuICA8ZGl2IGNsYXNzPVwibXQtNCBjb21tZW50cy1saXN0XCI+XG4gICAgPGgzIGNsYXNzPVwiZnctYm9sZFwiPnt7IF9fKCdjb21tZW50cycpIH19IDxzcGFuIGNsYXNzPVwidGV4dC1zdWNjZXNzXCIgdi1pZj1cIm1lc3NhZ2VzLnRvdGFsID4gMFwiPnt7IG1lc3NhZ2VzLnRvdGFsIH19PC9zcGFuPjwvaDM+XG4gICAgPGRpdiB2LWlmPVwibWVzc2FnZXMuZGF0YSAmJiBtZXNzYWdlcy5kYXRhLmxlbmd0aCA8IDFcIiBjbGFzcz1cImFsZXJ0IGFsZXJ0LWluZm9cIj57eyBfXygnZW1wdHlfbGlzdCcpIH19PC9kaXY+XG4gICAgPGRpdiBjbGFzcz1cIm5ld19wb3N0LWl0ZW1cIiB2LWZvcj1cIm1lc3NhZ2UgaW4gbWVzc2FnZXMuZGF0YVwiPlxuICAgICAgPGRpdiBjbGFzcz1cIm5ld19wb3N0LWhlYWRlciBkLWZsZXgganVzdGlmeS1jb250ZW50LWJldHdlZW5cIj5cbiAgICAgICAgPGRpdiBjbGFzcz1cInBvc3QtdXNlclwiPlxuICAgICAgICAgIDxhIDpocmVmPVwibWVzc2FnZS51c2VyLnByb2ZpbGVfdXJsXCIgdi1pZj1cIm1lc3NhZ2UudXNlci5wcm9maWxlX3VybFwiPlxuICAgICAgICAgICAgPGRpdiBjbGFzcz1cImF2YXRhclwiPlxuICAgICAgICAgICAgICA8aW1nIDpzcmM9XCJtZXNzYWdlLnVzZXIuYXZhdGFyXCIgY2xhc3M9XCJpbWctZmx1aWRcIiBhbHQ9XCIuXCI+XG4gICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICA8L2E+XG4gICAgICAgICAgPHNwYW4gY2xhc3M9XCJ1c2VyLXN0YXR1cyBzaGFkb3dcIiA6Y2xhc3M9XCJtZXNzYWdlLnVzZXIuaXNfb25saW5lID8gJ29ubGluZScgOiAnb2ZmbGluZSdcIj48L3NwYW4+XG4gICAgICAgICAgPGRpdiB2LWlmPVwibWVzc2FnZS51c2VyLnJpZ2h0c19uYW1lXCJcbiAgICAgICAgICAgICAgIGNsYXNzPVwicG9zdC1vZi11c2VyXCJcbiAgICAgICAgICAgICAgIGRhdGEtYnMtdG9nZ2xlPVwidG9vbHRpcFwiXG4gICAgICAgICAgICAgICBkYXRhLWJzLXBsYWNlbWVudD1cInRvcFwiXG4gICAgICAgICAgICAgICBkYXRhLWJzLWh0bWw9XCJ0cnVlXCJcbiAgICAgICAgICAgICAgIDp0aXRsZT1cIm1lc3NhZ2UudXNlci5yaWdodHNfbmFtZVwiPlxuICAgICAgICAgICAgPHN2ZyBjbGFzcz1cImljb24tcG9zdFwiPlxuICAgICAgICAgICAgICA8dXNlIHhsaW5rOmhyZWY9XCIvdGhlbWVzL2RlZmF1bHQvYXNzZXRzL2ljb25zL3Nwcml0ZS5zdmc/I2NoZWNrXCIvPlxuICAgICAgICAgICAgPC9zdmc+XG4gICAgICAgICAgPC9kaXY+XG4gICAgICAgIDwvZGl2PlxuICAgICAgICA8ZGl2IGNsYXNzPVwiZmxleC1ncm93LTEgcG9zdC11c2VyIGQtZmxleCBmbGV4LXdyYXAgb3ZlcmZsb3ctaGlkZGVuIGQtZmxleCBhbGlnbi1pdGVtcy1jZW50ZXJcIj5cbiAgICAgICAgICA8ZGl2IGNsYXNzPVwidy0xMDBcIj5cbiAgICAgICAgICAgIDxhIDpocmVmPVwibWVzc2FnZS51c2VyLnByb2ZpbGVfdXJsXCIgdi1pZj1cIm1lc3NhZ2UudXNlci5wcm9maWxlX3VybFwiPjxzcGFuIGNsYXNzPVwidXNlci1uYW1lIGQtaW5saW5lIG1lLTJcIj57eyBtZXNzYWdlLnVzZXIudXNlcl9uYW1lIH19PC9zcGFuPjwvYT5cbiAgICAgICAgICAgIDxkaXYgY2xhc3M9XCJ1c2VyLW5hbWUgZC1pbmxpbmUgbWUtMlwiIHYtaWY9XCIhbWVzc2FnZS51c2VyLnByb2ZpbGVfdXJsXCI+e3sgbWVzc2FnZS51c2VyLnVzZXJfbmFtZSB9fTwvZGl2PlxuICAgICAgICAgICAgPHNwYW4gY2xhc3M9XCJwb3N0LW1ldGEgZC1pbmxpbmUgbWUtMlwiPnt7IG1lc3NhZ2UuY3JlYXRlZF9hdCB9fTwvc3Bhbj5cbiAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICA8ZGl2IHYtaWY9XCJtZXNzYWdlLnVzZXIuc3RhdHVzXCIgY2xhc3M9XCJvdmVyZmxvdy1oaWRkZW4gdGV4dC1ub3dyYXAgdGV4dC1kYXJrLWJyb3duIG92ZXJmbG93LWVsbGlwc2lzIHNtYWxsXCI+XG4gICAgICAgICAgICA8c3BhbiBjbGFzcz1cImZ3LWJvbGRcIj57eyBtZXNzYWdlLnVzZXIuc3RhdHVzIH19PC9zcGFuPlxuICAgICAgICAgIDwvZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICAgIDwvZGl2PlxuICAgICAgPGRpdiBjbGFzcz1cInBvc3QtYm9keSBwdC0yIHBiLTJcIiB2LWh0bWw9XCJtZXNzYWdlLnRleHRcIj48L2Rpdj5cbiAgICAgIDxkaXYgY2xhc3M9XCJwb3N0LWZvb3RlciBkLWZsZXgganVzdGlmeS1jb250ZW50LWJldHdlZW5cIj5cbiAgICAgICAgPGRpdiBjbGFzcz1cIm92ZXJmbG93LWhpZGRlblwiPlxuICAgICAgICAgIDxkaXYgY2xhc3M9XCJwb3N0LW1ldGEgZC1mbGV4XCIgdi1pZj1cIm1lc3NhZ2UuaXBcIj5cbiAgICAgICAgICAgIDxkaXYgY2xhc3M9XCJ1c2VyLWlwIG1lLTJcIj5cbiAgICAgICAgICAgICAgPGEgOmhyZWY9XCJtZXNzYWdlLnNlYXJjaF9pcF91cmxcIj57eyBtZXNzYWdlLmlwIH19PC9hPlxuICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgICAgICA8ZGl2IGNsYXNzPVwidXNlcmFnZW50XCI+XG4gICAgICAgICAgICAgIDxzcGFuPnt7IG1lc3NhZ2UudXNlcl9hZ2VudCB9fTwvc3Bhbj5cbiAgICAgICAgICAgIDwvZGl2PlxuICAgICAgICAgIDwvZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICAgICAgPGRpdiBjbGFzcz1cImQtZmxleFwiPlxuICAgICAgICAgIDxkaXYgY2xhc3M9XCJtcy0zXCIgdi1pZj1cIm1lc3NhZ2UuY2FuX3JlcGx5XCI+XG4gICAgICAgICAgICA8YSBocmVmPVwiI1wiIEBjbGljay5wcmV2ZW50PVwicmVwbHkobWVzc2FnZSlcIj57eyBfXygncmVwbHknKSB9fTwvYT5cbiAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICA8ZGl2IGNsYXNzPVwibXMtM1wiIHYtaWY9XCJtZXNzYWdlLmNhbl9xdW90ZVwiPlxuICAgICAgICAgICAgPGEgaHJlZj1cIiNcIiBAY2xpY2sucHJldmVudD1cInF1b3RlKG1lc3NhZ2UpXCI+e3sgX18oJ3F1b3RlJykgfX08L2E+XG4gICAgICAgICAgPC9kaXY+XG4gICAgICAgICAgPGRpdiBjbGFzcz1cImRyb3Bkb3duIG1zLTNcIiB2LWlmPVwibWVzc2FnZS5jYW5fZGVsZXRlXCI+XG4gICAgICAgICAgICA8ZGl2IGNsYXNzPVwiY3Vyc29yLXBvaW50ZXJcIiBkYXRhLWJzLXRvZ2dsZT1cImRyb3Bkb3duXCIgYXJpYS1oYXNwb3B1cD1cInRydWVcIiBhcmlhLWV4cGFuZGVkPVwiZmFsc2VcIj5cbiAgICAgICAgICAgICAgPHN2ZyBjbGFzcz1cImljb24gdGV4dC1wcmltYXJ5XCI+XG4gICAgICAgICAgICAgICAgPHVzZSB4bGluazpocmVmPVwiL3RoZW1lcy9kZWZhdWx0L2Fzc2V0cy9pY29ucy9zcHJpdGUuc3ZnPyNtb3JlX2hvcml6b250YWxcIi8+XG4gICAgICAgICAgICAgIDwvc3ZnPlxuICAgICAgICAgICAgPC9kaXY+XG4gICAgICAgICAgICA8ZGl2IGNsYXNzPVwiZHJvcGRvd24tbWVudSBkcm9wZG93bi1tZW51LXJpZ2h0XCI+XG4gICAgICAgICAgICAgIDxhIGNsYXNzPVwiZHJvcGRvd24taXRlbVwiIGhyZWY9XCJcIiBAY2xpY2sucHJldmVudD1cImRlbENvbW1lbnQobWVzc2FnZS5pZClcIj57eyBfXygnZGVsZXRlJykgfX08L2E+XG4gICAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgPC9kaXY+XG4gICAgICA8L2Rpdj5cbiAgICA8L2Rpdj5cbiAgICA8cGFnaW5hdGlvbiA6ZGF0YT1cIm1lc3NhZ2VzXCIgQHBhZ2luYXRpb24tY2hhbmdlLXBhZ2U9XCJnZXRDb21tZW50c1wiIGNsYXNzPVwibXQtM1wiPjwvcGFnaW5hdGlvbj5cblxuICAgIDxkaXYgY2xhc3M9XCJtdC00XCIgdi1pZj1cImNhbl93cml0ZVwiPlxuICAgICAgPGgzIGNsYXNzPVwiZnctYm9sZFwiPnt7IF9fKCd3cml0ZV9jb21tZW50JykgfX08L2gzPlxuICAgICAgPGZvcm0gYWN0aW9uPVwiXCIgY2xhc3M9XCJjb21tZW50LWZvcm1cIiBAc3VibWl0LnByZXZlbnQ9XCJzZW5kQ29tbWVudFwiPlxuICAgICAgICA8ZGl2IGNsYXNzPVwiZC1mbGV4XCIgdi1pZj1cImVycm9yX21lc3NhZ2VcIj5cbiAgICAgICAgICA8ZGl2IGNsYXNzPVwiYWxlcnQgYWxlcnQtZGFuZ2VyIGQtaW5saW5lXCI+e3sgZXJyb3JfbWVzc2FnZSB9fTwvZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICAgICAgPGRpdiBjbGFzcz1cImQtZmxleFwiIHYtaWY9XCJjb21tZW50X2FkZGVkX21lc3NhZ2VcIj5cbiAgICAgICAgICA8ZGl2IGNsYXNzPVwiYWxlcnQgYWxlcnQtc3VjY2VzcyBkLWlubGluZVwiPnt7IGNvbW1lbnRfYWRkZWRfbWVzc2FnZSB9fTwvZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICAgICAgPGRpdiBzdHlsZT1cIm1heC13aWR0aDogODAwcHg7XCI+XG4gICAgICAgICAgPGRpdiBjbGFzcz1cImZvcm0tZ3JvdXBcIj5cbiAgICAgICAgICAgIDx0ZXh0YXJlYSA6bmFtZT1cIid0ZXh0J1wiIGlkPVwiY29tbWVudF90ZXh0XCIgcmVxdWlyZWQgY2xhc3M9XCJmb3JtLWNvbnRyb2xcIiB2LW1vZGVsPVwiY29tbWVudF90ZXh0XCI+PC90ZXh0YXJlYT5cbiAgICAgICAgICA8L2Rpdj5cbiAgICAgICAgPC9kaXY+XG4gICAgICAgIDxkaXYgY2xhc3M9XCJtdC0yXCI+XG4gICAgICAgICAgPGJ1dHRvbiB0eXBlPVwic3VibWl0XCIgbmFtZT1cInN1Ym1pdFwiIHZhbHVlPVwiMVwiIGNsYXNzPVwiYnRuIGJ0bi1wcmltYXJ5XCIgOmRpc2FibGVkPVwibG9hZGluZ1wiPlxuICAgICAgICAgICAgPHNwYW4gY2xhc3M9XCJzcGlubmVyLWJvcmRlciBzcGlubmVyLWJvcmRlci1zbVwiIHJvbGU9XCJzdGF0dXNcIiBhcmlhLWhpZGRlbj1cInRydWVcIiB2LWlmPVwibG9hZGluZ1wiPjwvc3Bhbj5cbiAgICAgICAgICAgIHt7IF9fKCdzZW5kJykgfX1cbiAgICAgICAgICA8L2J1dHRvbj5cbiAgICAgICAgICA8ZGl2PjwvZGl2PlxuICAgICAgICA8L2Rpdj5cbiAgICAgIDwvZm9ybT5cbiAgICA8L2Rpdj5cbiAgPC9kaXY+XG48L3RlbXBsYXRlPlxuXG48c2NyaXB0PlxuZXhwb3J0IGRlZmF1bHQge1xuICBuYW1lOiBcIkNvbW1lbnRzQ29tcG9uZW50XCIsXG4gIHByb3BzOiB7XG4gICAgYXJ0aWNsZV9pZDogTnVtYmVyLFxuICAgIGNhbl93cml0ZToge1xuICAgICAgdHlwZTogQm9vbGVhbixcbiAgICAgIGRlZmF1bHQ6IGZhbHNlLFxuICAgIH0sXG4gICAgaTE4bjoge1xuICAgICAgdHlwZTogT2JqZWN0LFxuICAgICAgZGVmYXVsdDogZnVuY3Rpb24gKCkge1xuICAgICAgICByZXR1cm4ge1xuICAgICAgICAgIHdyaXRlX2NvbW1lbnQ6ICdXcml0ZSBhIGNvbW1lbnQnLFxuICAgICAgICAgIHNlbmQ6ICdTZW5kJyxcbiAgICAgICAgICBkZWxldGU6ICdEZWxldGUnLFxuICAgICAgICAgIHF1b3RlOiAnUXVvdGUnLFxuICAgICAgICAgIHJlcGx5OiAnUmVwbHknLFxuICAgICAgICAgIGNvbW1lbnRzOiAnQ29tbWVudHMnLFxuICAgICAgICAgIGVtcHR5X2xpc3Q6ICdUaGUgbGlzdCBpcyBlbXB0eScsXG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9LFxuICAgIGxhbmd1YWdlOiB7XG4gICAgICB0eXBlOiBTdHJpbmcsXG4gICAgICBkZWZhdWx0OiAnZW4nXG4gICAgfSxcbiAgICB1cGxvYWRfdXJsOiB7XG4gICAgICB0eXBlOiBTdHJpbmcsXG4gICAgICBkZWZhdWx0OiAnJ1xuICAgIH1cbiAgfSxcbiAgZGF0YSgpXG4gIHtcbiAgICByZXR1cm4ge1xuICAgICAgbWVzc2FnZXM6IHt9LFxuICAgICAgY29tbWVudF90ZXh0OiAnJyxcbiAgICAgIGNvbW1lbnRfYWRkZWRfbWVzc2FnZTogJycsXG4gICAgICBlcnJvcl9tZXNzYWdlOiAnJyxcbiAgICAgIGxvYWRpbmc6IGZhbHNlLFxuICAgICAgYXR0YWNoZWRfZmlsZXM6IFtdLFxuICAgIH1cbiAgfSxcbiAgbW91bnRlZCgpXG4gIHtcbiAgICB0aGlzLmdldENvbW1lbnRzKDEsIGZhbHNlKTtcblxuICAgIGNvbnN0IHNlbGYgPSB0aGlzO1xuICAgIGxldCBjb25maWcgPSB7XG4gICAgICBzaW1wbGVVcGxvYWQ6IHtcbiAgICAgICAgdXBsb2FkVXJsOiB0aGlzLnVwbG9hZF91cmwsXG4gICAgICAgIHdpdGhDcmVkZW50aWFsczogZmFsc2UsXG4gICAgICAgIHNhdmVkQ2FsbGJhY2s6IGZ1bmN0aW9uIChmaWxlKSB7XG4gICAgICAgICAgc2VsZi5hdHRhY2hlZF9maWxlcy5wdXNoKGZpbGUuaWQpO1xuICAgICAgICB9LFxuICAgICAgfSxcbiAgICAgIGxhbmd1YWdlOiB0aGlzLmxhbmd1YWdlXG4gICAgfTtcblxuICAgIENsYXNzaWNFZGl0b3JcbiAgICAgIC5jcmVhdGUoZG9jdW1lbnQucXVlcnlTZWxlY3RvcignI2NvbW1lbnRfdGV4dCcpLCBjb25maWcpXG4gICAgICAudGhlbihlZGl0b3IgPT4ge1xuICAgICAgICB3aW5kb3cuZWRpdG9yID0gZWRpdG9yO1xuICAgICAgICBlZGl0b3IubW9kZWwuZG9jdW1lbnQub24oJ2NoYW5nZTpkYXRhJywgKCkgPT4ge1xuICAgICAgICAgIHRoaXMuY29tbWVudF90ZXh0ID0gZWRpdG9yLmdldERhdGEoKTtcbiAgICAgICAgfSk7XG4gICAgICB9KVxuICAgICAgLmNhdGNoKGVycm9yID0+IHtcbiAgICAgICAgY29uc29sZS5lcnJvcihlcnJvcik7XG4gICAgICB9KTtcblxuICB9LFxuICB1cGRhdGVkKClcbiAge1xuICAgICQoJy5pbWFnZS1wcmV2aWV3JykubWFnbmlmaWNQb3B1cCh7XG4gICAgICB0eXBlOiAnaW1hZ2UnLFxuICAgICAgaW1hZ2U6IHtcbiAgICAgICAgdmVydGljYWxGaXQ6IHRydWUsXG4gICAgICAgIHRpdGxlU3JjOiBmdW5jdGlvbiAoaXRlbSkge1xuICAgICAgICAgIHJldHVybiBpdGVtLmVsLmF0dHIoJ3RpdGxlJykgKyAnICZtaWRkb3Q7IDxhIGNsYXNzPVwiaW1hZ2Utc291cmNlLWxpbmtcIiBocmVmPVwiJyArIGl0ZW0uZWwuYXR0cignZGF0YS1zb3VyY2UnKSArICdcIiB0YXJnZXQ9XCJfYmxhbmtcIj5Eb3dubG9hZDwvYT4nO1xuICAgICAgICB9XG4gICAgICB9LFxuICAgICAgem9vbToge1xuICAgICAgICBlbmFibGVkOiB0cnVlLFxuICAgICAgICBkdXJhdGlvbjogMzAwLFxuICAgICAgICBvcGVuZXI6IGZ1bmN0aW9uIChlbGVtZW50KSB7XG4gICAgICAgICAgcmV0dXJuIGVsZW1lbnQuZmluZCgnaW1nJyk7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9KTtcbiAgfSxcbiAgY29tcHV0ZWQ6IHt9LFxuICBtZXRob2RzOiB7XG4gICAgZ2V0Q29tbWVudHMocGFnZSA9IDEsIHNjcm9sbF90b19jb21tZW50cyA9IHRydWUpXG4gICAge1xuICAgICAgdGhpcy5sb2FkaW5nID0gdHJ1ZTtcbiAgICAgIGF4aW9zLmdldCgnL25ld3MvY29tbWVudHMvJyArIHRoaXMuYXJ0aWNsZV9pZCArICcvP3BhZ2U9JyArIHBhZ2UpXG4gICAgICAgIC50aGVuKHJlc3BvbnNlID0+IHtcbiAgICAgICAgICBpZiAoc2Nyb2xsX3RvX2NvbW1lbnRzKSB7XG4gICAgICAgICAgICAkKCdodG1sLCBib2R5JykuYW5pbWF0ZSh7XG4gICAgICAgICAgICAgIHNjcm9sbFRvcDogJCgnLmNvbW1lbnRzLWxpc3QnKS5vZmZzZXQoKS50b3BcbiAgICAgICAgICAgIH0sIDUwMCk7XG4gICAgICAgICAgfVxuICAgICAgICAgIHRoaXMubWVzc2FnZXMgPSByZXNwb25zZS5kYXRhO1xuICAgICAgICAgIHRoaXMubG9hZGluZyA9IGZhbHNlO1xuICAgICAgICB9KVxuICAgICAgICAuY2F0Y2goZXJyb3IgPT4ge1xuICAgICAgICAgIGFsZXJ0KGVycm9yKTtcbiAgICAgICAgICB0aGlzLmxvYWRpbmcgPSBmYWxzZTtcbiAgICAgICAgfSk7XG4gICAgfSxcbiAgICByZXBseShtZXNzYWdlKVxuICAgIHtcbiAgICAgIGVkaXRvci5lZGl0aW5nLnZpZXcuZm9jdXMoKTtcbiAgICAgICQoJ2h0bWwsIGJvZHknKS5hbmltYXRlKHtcbiAgICAgICAgc2Nyb2xsVG9wOiAkKCcuY29tbWVudC1mb3JtJykucG9zaXRpb24oKS50b3BcbiAgICAgIH0sIDUwMCk7XG4gICAgICBlZGl0b3IubW9kZWwuY2hhbmdlKHdyaXRlciA9PiB7XG4gICAgICAgIGNvbnN0IGluc2VydFBvc2l0aW9uID0gZWRpdG9yLm1vZGVsLmRvY3VtZW50LnNlbGVjdGlvbi5nZXRGaXJzdFBvc2l0aW9uKCk7XG4gICAgICAgIHdyaXRlci5pbnNlcnRUZXh0KG1lc3NhZ2UudXNlci51c2VyX25hbWUgKyAnLCAnLCB7fSwgaW5zZXJ0UG9zaXRpb24pO1xuICAgICAgICB3cml0ZXIuc2V0U2VsZWN0aW9uKHdyaXRlci5jcmVhdGVQb3NpdGlvbkF0KGVkaXRvci5tb2RlbC5kb2N1bWVudC5nZXRSb290KCksICdlbmQnKSk7XG4gICAgICB9KTtcbiAgICB9LFxuICAgIHF1b3RlKG1lc3NhZ2UpXG4gICAge1xuICAgICAgJCgnaHRtbCwgYm9keScpLmFuaW1hdGUoe1xuICAgICAgICBzY3JvbGxUb3A6ICQoJy5jb21tZW50LWZvcm0nKS5wb3NpdGlvbigpLnRvcFxuICAgICAgfSwgNTAwKTtcbiAgICAgIGxldCB0ZXh0ID0gbWVzc2FnZS50ZXh0LnJlcGxhY2UoLyg8KFtePl0rKT4pL2lnLCBcIlwiKTtcbiAgICAgIGNvbnN0IGNvbnRlbnQgPSAnPGJsb2NrcXVvdGU+PHA+JyArIG1lc3NhZ2UudXNlci51c2VyX25hbWUgKyAnLCAnICsgbWVzc2FnZS5jcmVhdGVkX2F0ICsgJzxicj4nICsgdGV4dCArICc8L3A+PC9ibG9ja3F1b3RlPjxwPjwvcD4nO1xuICAgICAgY29uc3Qgdmlld0ZyYWdtZW50ID0gZWRpdG9yLmRhdGEucHJvY2Vzc29yLnRvVmlldyhjb250ZW50KTtcbiAgICAgIGNvbnN0IG1vZGVsRnJhZ21lbnQgPSBlZGl0b3IuZGF0YS50b01vZGVsKHZpZXdGcmFnbWVudCk7XG4gICAgICBlZGl0b3IubW9kZWwuaW5zZXJ0Q29udGVudChtb2RlbEZyYWdtZW50KTtcbiAgICAgIGVkaXRvci5lZGl0aW5nLnZpZXcuZm9jdXMoKTtcbiAgICB9LFxuICAgIHNlbmRDb21tZW50KClcbiAgICB7XG4gICAgICB0aGlzLmxvYWRpbmcgPSB0cnVlO1xuICAgICAgYXhpb3MucG9zdCgnL25ld3MvY29tbWVudHMvYWRkLycgKyB0aGlzLmFydGljbGVfaWQgKyAnLycsIHtcbiAgICAgICAgY29tbWVudDogdGhpcy5jb21tZW50X3RleHQsXG4gICAgICAgIGF0dGFjaGVkX2ZpbGVzOiB0aGlzLmF0dGFjaGVkX2ZpbGVzLFxuICAgICAgfSlcbiAgICAgICAgLnRoZW4ocmVzcG9uc2UgPT4ge1xuICAgICAgICAgIHRoaXMuY29tbWVudF9hZGRlZF9tZXNzYWdlID0gcmVzcG9uc2UuZGF0YS5tZXNzYWdlO1xuICAgICAgICAgIHRoaXMubG9hZGluZyA9IGZhbHNlO1xuICAgICAgICAgIHRoaXMuY29tbWVudF90ZXh0ID0gJyc7XG4gICAgICAgICAgdGhpcy5lcnJvcl9tZXNzYWdlID0gJyc7XG4gICAgICAgICAgdGhpcy5hdHRhY2hlZF9maWxlcyA9IFtdO1xuICAgICAgICAgIHdpbmRvdy5lZGl0b3Iuc2V0RGF0YSgnJyk7XG4gICAgICAgICAgdGhpcy5nZXRDb21tZW50cyhyZXNwb25zZS5kYXRhLmxhc3RfcGFnZSwgZmFsc2UpO1xuICAgICAgICB9KVxuICAgICAgICAuY2F0Y2goZXJyb3IgPT4ge1xuICAgICAgICAgIHRoaXMuZXJyb3JfbWVzc2FnZSA9IGVycm9yLnJlc3BvbnNlLmRhdGEubWVzc2FnZTtcbiAgICAgICAgICB0aGlzLmxvYWRpbmcgPSBmYWxzZTtcbiAgICAgICAgfSk7XG4gICAgfSxcbiAgICBkZWxDb21tZW50KGNvbW1lbnRfaWQpXG4gICAge1xuICAgICAgdGhpcy5sb2FkaW5nID0gdHJ1ZTtcbiAgICAgIGF4aW9zLnBvc3QoJy9uZXdzL2NvbW1lbnRzL2RlbC8nLCB7XG4gICAgICAgIGNvbW1lbnRfaWQ6IGNvbW1lbnRfaWRcbiAgICAgIH0pXG4gICAgICAgIC50aGVuKHJlc3BvbnNlID0+IHtcbiAgICAgICAgICB0aGlzLmdldENvbW1lbnRzKHRoaXMubWVzc2FnZXMuY3VycmVudF9wYWdlLCBmYWxzZSk7XG4gICAgICAgIH0pXG4gICAgICAgIC5jYXRjaChlcnJvciA9PiB7XG4gICAgICAgICAgYWxlcnQoZXJyb3IucmVzcG9uc2UuZGF0YS5tZXNzYWdlKTtcbiAgICAgICAgICB0aGlzLmxvYWRpbmcgPSBmYWxzZTtcbiAgICAgICAgfSk7XG4gICAgfSxcbiAgICBfXyhtZXNzYWdlKVxuICAgIHtcbiAgICAgIHJldHVybiBfLmdldCh0aGlzLmkxOG4sIG1lc3NhZ2UsICcnKTtcbiAgICB9XG4gIH1cbn1cbjwvc2NyaXB0PlxuIiwiPHRlbXBsYXRlPlxuICA8ZGl2IGNsYXNzPVwicG9zaXRpb24tcmVsYXRpdmVcIj5cbiAgICA8ZGl2IGNsYXNzPVwiZC1mbGV4IGp1c3RpZnktY29udGVudC1jZW50ZXIgcG9zaXRpb24tYWJzb2x1dGUgdy0xMDAgdm90ZS1wcmVsb2FkZXJcIiB2LWlmPVwibG9hZGluZ1wiPlxuICAgICAgPGRpdiBjbGFzcz1cInNwaW5uZXItYm9yZGVyIHRleHQtc2Vjb25kYXJ5XCIgcm9sZT1cInN0YXR1c1wiPlxuICAgICAgICA8c3BhbiBjbGFzcz1cInZpc3VhbGx5LWhpZGRlblwiPkxvYWRpbmcuLi48L3NwYW4+XG4gICAgICA8L2Rpdj5cbiAgICA8L2Rpdj5cbiAgICA8YnV0dG9uIGNsYXNzPVwiYnRuIGJ0bi1saWdodCBidG4tc21cIiBAY2xpY2s9XCJzZXRWb3RlKDEpXCIgOmNsYXNzPVwidm90ZWQgPiAwID8gJ2xpa2VkJyA6ICcnXCIgOmRpc2FibGVkPVwidm90ZWQgPiAwIHx8ICFjYW5fdm90ZVwiPlxuICAgICAgPHN2ZyBjbGFzcz1cImljb24gZG93bmxvYWQtYnV0dG9uLWljb24gbXQtbjFcIj5cbiAgICAgICAgPHVzZSB4bGluazpocmVmPVwiL3RoZW1lcy9kZWZhdWx0L2Fzc2V0cy9pY29ucy9zcHJpdGUuc3ZnI2xpa2VcIi8+XG4gICAgICA8L3N2Zz5cbiAgICA8L2J1dHRvbj5cbiAgICA8c3BhbiA6Y2xhc3M9XCJyYXRpbmdfY29sb3JcIiBjbGFzcz1cIm1zLTIgbWUtMiBmdy1ib2xkXCI+e3sgcmF0aW5nID4gMCA/ICcrJyA6ICcnIH19e3sgcmF0aW5nIH19PC9zcGFuPlxuICAgIDxidXR0b24gY2xhc3M9XCJidG4gYnRuLWxpZ2h0IGJ0bi1zbVwiIEBjbGljaz1cInNldFZvdGUoMClcIiA6Y2xhc3M9XCJ2b3RlZCA8IDAgPyAnZGlzbGlrZWQnIDogJydcIiA6ZGlzYWJsZWQ9XCJ2b3RlZCA8IDAgfHwgIWNhbl92b3RlXCI+XG4gICAgICA8c3ZnIGNsYXNzPVwiaWNvbiBkb3dubG9hZC1idXR0b24taWNvbiBtZS0xXCI+XG4gICAgICAgIDx1c2UgeGxpbms6aHJlZj1cIi90aGVtZXMvZGVmYXVsdC9hc3NldHMvaWNvbnMvc3ByaXRlLnN2ZyNkaXNsaWtlXCIvPlxuICAgICAgPC9zdmc+XG4gICAgPC9idXR0b24+XG4gIDwvZGl2PlxuPC90ZW1wbGF0ZT5cblxuPHNjcmlwdD5cbmV4cG9ydCBkZWZhdWx0IHtcbiAgbmFtZTogXCJMaWtlc0NvbXBvbmVudFwiLFxuICBwcm9wczoge1xuICAgIGFydGljbGVfaWQ6IE51bWJlcixcbiAgICByYXRpbmc6IHtcbiAgICAgIHR5cGU6IE51bWJlcixcbiAgICAgIGRlZmF1bHQ6IDBcbiAgICB9LFxuICAgIGNhbl92b3RlOiB7XG4gICAgICB0eXBlOiBCb29sZWFuLFxuICAgICAgZGVmYXVsdDogZmFsc2UsXG4gICAgfSxcbiAgICB2b3RlZDoge1xuICAgICAgdHlwZTogTnVtYmVyLFxuICAgICAgZGVmYXVsdDogMCxcbiAgICB9LFxuICAgIHNldF92b3RlX3VybDoge1xuICAgICAgdHlwZTogU3RyaW5nLFxuICAgICAgZGVmYXVsdDogJy9uZXdzL2FkZF92b3RlLycsXG4gICAgfVxuICB9LFxuICBkYXRhKClcbiAge1xuICAgIHJldHVybiB7XG4gICAgICBtZXNzYWdlOiAnJyxcbiAgICAgIGxvYWRpbmc6IGZhbHNlLFxuICAgIH1cbiAgfSxcbiAgY29tcHV0ZWQ6IHtcbiAgICByYXRpbmdfY29sb3I6IGZ1bmN0aW9uICgpIHtcbiAgICAgIGxldCBjbGFzc19uYW1lID0gJyc7XG4gICAgICBpZiAodGhpcy5yYXRpbmcgPiAwKSB7XG4gICAgICAgIGNsYXNzX25hbWUgPSAndGV4dC1zdWNjZXNzJztcbiAgICAgIH0gZWxzZSBpZiAodGhpcy5yYXRpbmcgPCAwKSB7XG4gICAgICAgIGNsYXNzX25hbWUgPSAndGV4dC1kYW5nZXInXG4gICAgICB9XG4gICAgICByZXR1cm4gY2xhc3NfbmFtZTtcbiAgICB9XG4gIH0sXG4gIG1ldGhvZHM6IHtcbiAgICBzZXRWb3RlKHR5cGUpXG4gICAge1xuICAgICAgdGhpcy5sb2FkaW5nID0gdHJ1ZTtcbiAgICAgIGF4aW9zLmdldCh0aGlzLnNldF92b3RlX3VybCArIHRoaXMuYXJ0aWNsZV9pZCArICcvJyArIHR5cGUgKyAnLycpXG4gICAgICAgIC50aGVuKHJlc3BvbnNlID0+IHtcbiAgICAgICAgICB0aGlzLnJhdGluZyA9IHJlc3BvbnNlLmRhdGEucmF0aW5nO1xuICAgICAgICAgIHRoaXMudm90ZWQgPSByZXNwb25zZS5kYXRhLnZvdGVkO1xuICAgICAgICAgIHRoaXMubWVzc2FnZSA9IHJlc3BvbnNlLmRhdGEubWVzc2FnZTtcbiAgICAgICAgICB0aGlzLmxvYWRpbmcgPSBmYWxzZTtcbiAgICAgICAgfSlcbiAgICAgICAgLmNhdGNoKGVycm9yID0+IHtcbiAgICAgICAgICBhbGVydChlcnJvcik7XG4gICAgICAgICAgdGhpcy5sb2FkaW5nID0gZmFsc2U7XG4gICAgICAgIH0pO1xuICAgIH1cbiAgfVxufVxuPC9zY3JpcHQ+XG4iLCIvKipcbiAqIFRoaXMgZmlsZSBpcyBwYXJ0IG9mIEpvaG5DTVMgQ29udGVudCBNYW5hZ2VtZW50IFN5c3RlbS5cbiAqXG4gKiBAY29weXJpZ2h0IEpvaG5DTVMgQ29tbXVuaXR5XG4gKiBAbGljZW5zZSAgIGh0dHBzOi8vb3BlbnNvdXJjZS5vcmcvbGljZW5zZXMvR1BMLTMuMCBHUEwtMy4wXG4gKiBAbGluayAgICAgIGh0dHBzOi8vam9obmNtcy5jb20gSm9obkNNUyBQcm9qZWN0XG4gKi9cblxuaW1wb3J0IFZ1ZSBmcm9tIFwidnVlXCI7XG5cbnJlcXVpcmUoJy4vYm9vdHN0cmFwJyk7XG5yZXF1aXJlKCcuL2pxdWVyeS5tYWduaWZpYy1wb3B1cCcpO1xucmVxdWlyZShcImZsYXRwaWNrclwiKTtcbnJlcXVpcmUoJy4vbWVudScpO1xucmVxdWlyZSgnLi9wcmlzbScpO1xucmVxdWlyZSgnLi9mb3J1bScpO1xucmVxdWlyZSgnLi9tb2RhbHMnKTtcbnJlcXVpcmUoJy4vc2xpZGVyJyk7XG5yZXF1aXJlKCcuL3Byb2dyZXNzJyk7XG5yZXF1aXJlKCcuL3d5c2liYicpO1xucmVxdWlyZSgnLi9tYWluJyk7XG5cbi8qKlxuICogVGhlIGZvbGxvd2luZyBibG9jayBvZiBjb2RlIG1heSBiZSB1c2VkIHRvIGF1dG9tYXRpY2FsbHkgcmVnaXN0ZXIgeW91clxuICogVnVlIGNvbXBvbmVudHMuIEl0IHdpbGwgcmVjdXJzaXZlbHkgc2NhbiB0aGlzIGRpcmVjdG9yeSBmb3IgdGhlIFZ1ZVxuICogY29tcG9uZW50cyBhbmQgYXV0b21hdGljYWxseSByZWdpc3RlciB0aGVtIHdpdGggdGhlaXIgXCJiYXNlbmFtZVwiLlxuICpcbiAqINCQ0LLRgtC+0LfQsNCz0YDRg9C30LrQsCDQutC+0LzQv9C+0L3QtdC90YLQvtCyXG4gKiBFZy4gLi9jb21wb25lbnRzL0V4YW1wbGVDb21wb25lbnQudnVlIC0+IDxleGFtcGxlLWNvbXBvbmVudD48L2V4YW1wbGUtY29tcG9uZW50PlxuICovXG5jb25zdCBmaWxlcyA9IHJlcXVpcmUuY29udGV4dCgnLi8nLCB0cnVlLCAvXFwudnVlJC9pKVxuZmlsZXMua2V5cygpLm1hcChrZXkgPT4gVnVlLmNvbXBvbmVudChrZXkuc3BsaXQoJy8nKS5wb3AoKS5zcGxpdCgnLicpWzBdLCBmaWxlcyhrZXkpLmRlZmF1bHQpKVxuXG5WdWUuY29tcG9uZW50KCdwYWdpbmF0aW9uJywgcmVxdWlyZSgnbGFyYXZlbC12dWUtcGFnaW5hdGlvbicpKTtcblxuY29uc3QgdnVlX2FwcHMgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCcudnVlX2FwcCcpO1xudnVlX2FwcHMuZm9yRWFjaChmdW5jdGlvbiAoZWwpIHtcbiAgbmV3IFZ1ZSh7XG4gICAgZWw6IGVsLFxuICB9KTtcbn0pO1xuIiwiLyoqXG4gKiBXZSdsbCBsb2FkIGpRdWVyeSBhbmQgdGhlIEJvb3RzdHJhcCBqUXVlcnkgcGx1Z2luIHdoaWNoIHByb3ZpZGVzIHN1cHBvcnRcbiAqIGZvciBKYXZhU2NyaXB0IGJhc2VkIEJvb3RzdHJhcCBmZWF0dXJlcyBzdWNoIGFzIG1vZGFscyBhbmQgdGFicy4gVGhpc1xuICogY29kZSBtYXkgYmUgbW9kaWZpZWQgdG8gZml0IHRoZSBzcGVjaWZpYyBuZWVkcyBvZiB5b3VyIGFwcGxpY2F0aW9uLlxuICovXG5cbnRyeSB7XG4gIHdpbmRvdy5Qb3BwZXIgPSByZXF1aXJlKCdwb3BwZXIuanMnKS5kZWZhdWx0O1xuICB3aW5kb3cuJCA9IHdpbmRvdy5qUXVlcnkgPSByZXF1aXJlKCdqcXVlcnknKTtcbiAgd2luZG93LmF4aW9zID0gcmVxdWlyZSgnYXhpb3MnKTtcbiAgd2luZG93LmF4aW9zLmRlZmF1bHRzLmhlYWRlcnMuY29tbW9uWydYLVJlcXVlc3RlZC1XaXRoJ10gPSAnWE1MSHR0cFJlcXVlc3QnO1xuICB2YXIgXyA9IHJlcXVpcmUoJ2xvZGFzaCcpO1xuICByZXF1aXJlKCdib290c3RyYXAnKTtcbn0gY2F0Y2ggKGUpIHtcbn1cblxuXG4iLCIkKCcjZmlyc3RfcG9zdCcpXG4gIC5vbignaGlkZS5icy5jb2xsYXBzZScsIGZ1bmN0aW9uIChlKSB7XG4gICAgdG9nZ2xlUHJldmlldygpO1xuICB9KVxuICAub24oJ3Nob3duLmJzLmNvbGxhcHNlJywgZnVuY3Rpb24gKCkge1xuICAgIHRvZ2dsZVByZXZpZXcoKTtcbiAgfSk7XG5cbmZ1bmN0aW9uIHRvZ2dsZVByZXZpZXcoKVxue1xuICAkKCcjZmlyc3RfcG9zdF9ibG9jayAucG9zdC1wcmV2aWV3JykudG9nZ2xlKDApO1xufVxuXG4kKGZ1bmN0aW9uICgpIHtcbiAgJCgnLmltYWdlLWdhbGxlcnknKS5lYWNoKGZ1bmN0aW9uICgpIHtcbiAgICAkKHRoaXMpLm1hZ25pZmljUG9wdXAoe1xuICAgICAgZGVsZWdhdGU6ICcuZ2FsbGVyeS1pdGVtJyxcbiAgICAgIHR5cGU6ICdpbWFnZScsXG4gICAgICB0TG9hZGluZzogJ0xvYWRpbmcgaW1hZ2UgIyVjdXJyJS4uLicsXG4gICAgICBtYWluQ2xhc3M6ICdtZnAtaW1nLW1vYmlsZScsXG4gICAgICBnYWxsZXJ5OiB7XG4gICAgICAgIGVuYWJsZWQ6IHRydWUsXG4gICAgICAgIG5hdmlnYXRlQnlJbWdDbGljazogdHJ1ZSxcbiAgICAgICAgcHJlbG9hZDogWzAsIDFdXG4gICAgICB9LFxuICAgICAgaW1hZ2U6IHtcbiAgICAgICAgdEVycm9yOiAnPGEgaHJlZj1cIiV1cmwlXCI+VGhlIGltYWdlICMlY3VyciU8L2E+IGNvdWxkIG5vdCBiZSBsb2FkZWQuJyxcbiAgICAgICAgdGl0bGVTcmM6IGZ1bmN0aW9uIChpdGVtKSB7XG4gICAgICAgICAgcmV0dXJuIGl0ZW0uZWwuYXR0cigndGl0bGUnKSArICcgJm1pZGRvdDsgPGEgY2xhc3M9XCJpbWFnZS1zb3VyY2UtbGlua1wiIGhyZWY9XCInICsgaXRlbS5lbC5hdHRyKCdkYXRhLXNvdXJjZScpICsgJ1wiIHRhcmdldD1cIl9ibGFua1wiPkRvd25sb2FkPC9hPic7XG4gICAgICAgIH1cbiAgICAgIH0sXG4gICAgICB6b29tOiB7XG4gICAgICAgIGVuYWJsZWQ6IHRydWUsXG4gICAgICAgIGR1cmF0aW9uOiAzMDAsXG4gICAgICAgIG9wZW5lcjogZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgICByZXR1cm4gZWxlbWVudC5maW5kKCdpbWcnKTtcbiAgICAgICAgfVxuICAgICAgfSxcbiAgICB9KTtcbiAgfSk7XG4gICQoJy5pbWFnZS1wcmV2aWV3JykubWFnbmlmaWNQb3B1cCh7XG4gICAgdHlwZTogJ2ltYWdlJyxcbiAgICBpbWFnZToge1xuICAgICAgdmVydGljYWxGaXQ6IHRydWUsXG4gICAgICB0aXRsZVNyYzogZnVuY3Rpb24gKGl0ZW0pIHtcbiAgICAgICAgcmV0dXJuIGl0ZW0uZWwuYXR0cigndGl0bGUnKSArICcgJm1pZGRvdDsgPGEgY2xhc3M9XCJpbWFnZS1zb3VyY2UtbGlua1wiIGhyZWY9XCInICsgaXRlbS5lbC5hdHRyKCdkYXRhLXNvdXJjZScpICsgJ1wiIHRhcmdldD1cIl9ibGFua1wiPkRvd25sb2FkPC9hPic7XG4gICAgICB9XG4gICAgfSxcbiAgICB6b29tOiB7XG4gICAgICBlbmFibGVkOiB0cnVlLFxuICAgICAgZHVyYXRpb246IDMwMCxcbiAgICAgIG9wZW5lcjogZnVuY3Rpb24gKGVsZW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIGVsZW1lbnQuZmluZCgnaW1nJyk7XG4gICAgICB9XG4gICAgfVxuICB9KTtcbiAgJCgnW2RhdGEtYnMtdG9nZ2xlPVwidG9vbHRpcFwiXScpLnRvb2x0aXAoKTtcbn0pO1xuXG4kKFwiLmN1c3RvbS1maWxlLWlucHV0XCIpLm9uKFwiY2hhbmdlXCIsIGZ1bmN0aW9uICgpIHtcbiAgdmFyIGZpbGVOYW1lID0gJCh0aGlzKS52YWwoKS5zcGxpdChcIlxcXFxcIikucG9wKCk7XG4gICQodGhpcykuc2libGluZ3MoXCIuY3VzdG9tLWZpbGUtbGFiZWxcIikuYWRkQ2xhc3MoXCJzZWxlY3RlZFwiKS5odG1sKGZpbGVOYW1lKTtcbn0pO1xuIiwiLyohIE1hZ25pZmljIFBvcHVwIC0gdjEuMS4wIC0gMjAxNi0wMi0yMFxuKiBodHRwOi8vZGltc2VtZW5vdi5jb20vcGx1Z2lucy9tYWduaWZpYy1wb3B1cC9cbiogQ29weXJpZ2h0IChjKSAyMDE2IERtaXRyeSBTZW1lbm92OyAqL1xuOyhmdW5jdGlvbiAoZmFjdG9yeSkge1xuICBpZiAodHlwZW9mIGRlZmluZSA9PT0gJ2Z1bmN0aW9uJyAmJiBkZWZpbmUuYW1kKSB7XG4gICAgLy8gQU1ELiBSZWdpc3RlciBhcyBhbiBhbm9ueW1vdXMgbW9kdWxlLlxuICAgIGRlZmluZShbJ2pxdWVyeSddLCBmYWN0b3J5KTtcbiAgfSBlbHNlIGlmICh0eXBlb2YgZXhwb3J0cyA9PT0gJ29iamVjdCcpIHtcbiAgICAvLyBOb2RlL0NvbW1vbkpTXG4gICAgZmFjdG9yeShyZXF1aXJlKCdqcXVlcnknKSk7XG4gIH0gZWxzZSB7XG4gICAgLy8gQnJvd3NlciBnbG9iYWxzXG4gICAgZmFjdG9yeSh3aW5kb3cualF1ZXJ5IHx8IHdpbmRvdy5aZXB0byk7XG4gIH1cbn0oZnVuY3Rpb24gKCQpIHtcblxuICAvKj4+Y29yZSovXG4gIC8qKlxuICAgKlxuICAgKiBNYWduaWZpYyBQb3B1cCBDb3JlIEpTIGZpbGVcbiAgICpcbiAgICovXG5cblxuICAvKipcbiAgICogUHJpdmF0ZSBzdGF0aWMgY29uc3RhbnRzXG4gICAqL1xuICB2YXIgQ0xPU0VfRVZFTlQgPSAnQ2xvc2UnLFxuICAgIEJFRk9SRV9DTE9TRV9FVkVOVCA9ICdCZWZvcmVDbG9zZScsXG4gICAgQUZURVJfQ0xPU0VfRVZFTlQgPSAnQWZ0ZXJDbG9zZScsXG4gICAgQkVGT1JFX0FQUEVORF9FVkVOVCA9ICdCZWZvcmVBcHBlbmQnLFxuICAgIE1BUktVUF9QQVJTRV9FVkVOVCA9ICdNYXJrdXBQYXJzZScsXG4gICAgT1BFTl9FVkVOVCA9ICdPcGVuJyxcbiAgICBDSEFOR0VfRVZFTlQgPSAnQ2hhbmdlJyxcbiAgICBOUyA9ICdtZnAnLFxuICAgIEVWRU5UX05TID0gJy4nICsgTlMsXG4gICAgUkVBRFlfQ0xBU1MgPSAnbWZwLXJlYWR5JyxcbiAgICBSRU1PVklOR19DTEFTUyA9ICdtZnAtcmVtb3ZpbmcnLFxuICAgIFBSRVZFTlRfQ0xPU0VfQ0xBU1MgPSAnbWZwLXByZXZlbnQtY2xvc2UnO1xuXG5cbiAgLyoqXG4gICAqIFByaXZhdGUgdmFyc1xuICAgKi9cbiAgLypqc2hpbnQgLVcwNzkgKi9cbiAgdmFyIG1mcCwgLy8gQXMgd2UgaGF2ZSBvbmx5IG9uZSBpbnN0YW5jZSBvZiBNYWduaWZpY1BvcHVwIG9iamVjdCwgd2UgZGVmaW5lIGl0IGxvY2FsbHkgdG8gbm90IHRvIHVzZSAndGhpcydcbiAgICBNYWduaWZpY1BvcHVwID0gZnVuY3Rpb24gKCkge1xuICAgIH0sXG4gICAgX2lzSlEgPSAhISh3aW5kb3cualF1ZXJ5KSxcbiAgICBfcHJldlN0YXR1cyxcbiAgICBfd2luZG93ID0gJCh3aW5kb3cpLFxuICAgIF9kb2N1bWVudCxcbiAgICBfcHJldkNvbnRlbnRUeXBlLFxuICAgIF93cmFwQ2xhc3NlcyxcbiAgICBfY3VyclBvcHVwVHlwZTtcblxuXG4gIC8qKlxuICAgKiBQcml2YXRlIGZ1bmN0aW9uc1xuICAgKi9cbiAgdmFyIF9tZnBPbiA9IGZ1bmN0aW9uIChuYW1lLCBmKSB7XG4gICAgICBtZnAuZXYub24oTlMgKyBuYW1lICsgRVZFTlRfTlMsIGYpO1xuICAgIH0sXG4gICAgX2dldEVsID0gZnVuY3Rpb24gKGNsYXNzTmFtZSwgYXBwZW5kVG8sIGh0bWwsIHJhdykge1xuICAgICAgdmFyIGVsID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG4gICAgICBlbC5jbGFzc05hbWUgPSAnbWZwLScgKyBjbGFzc05hbWU7XG4gICAgICBpZiAoaHRtbCkge1xuICAgICAgICBlbC5pbm5lckhUTUwgPSBodG1sO1xuICAgICAgfVxuICAgICAgaWYgKCFyYXcpIHtcbiAgICAgICAgZWwgPSAkKGVsKTtcbiAgICAgICAgaWYgKGFwcGVuZFRvKSB7XG4gICAgICAgICAgZWwuYXBwZW5kVG8oYXBwZW5kVG8pO1xuICAgICAgICB9XG4gICAgICB9IGVsc2UgaWYgKGFwcGVuZFRvKSB7XG4gICAgICAgIGFwcGVuZFRvLmFwcGVuZENoaWxkKGVsKTtcbiAgICAgIH1cbiAgICAgIHJldHVybiBlbDtcbiAgICB9LFxuICAgIF9tZnBUcmlnZ2VyID0gZnVuY3Rpb24gKGUsIGRhdGEpIHtcbiAgICAgIG1mcC5ldi50cmlnZ2VySGFuZGxlcihOUyArIGUsIGRhdGEpO1xuXG4gICAgICBpZiAobWZwLnN0LmNhbGxiYWNrcykge1xuICAgICAgICAvLyBjb252ZXJ0cyBcIm1mcEV2ZW50TmFtZVwiIHRvIFwiZXZlbnROYW1lXCIgY2FsbGJhY2sgYW5kIHRyaWdnZXJzIGl0IGlmIGl0J3MgcHJlc2VudFxuICAgICAgICBlID0gZS5jaGFyQXQoMCkudG9Mb3dlckNhc2UoKSArIGUuc2xpY2UoMSk7XG4gICAgICAgIGlmIChtZnAuc3QuY2FsbGJhY2tzW2VdKSB7XG4gICAgICAgICAgbWZwLnN0LmNhbGxiYWNrc1tlXS5hcHBseShtZnAsICQuaXNBcnJheShkYXRhKSA/IGRhdGEgOiBbZGF0YV0pO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcbiAgICBfZ2V0Q2xvc2VCdG4gPSBmdW5jdGlvbiAodHlwZSkge1xuICAgICAgaWYgKHR5cGUgIT09IF9jdXJyUG9wdXBUeXBlIHx8ICFtZnAuY3VyclRlbXBsYXRlLmNsb3NlQnRuKSB7XG4gICAgICAgIG1mcC5jdXJyVGVtcGxhdGUuY2xvc2VCdG4gPSAkKG1mcC5zdC5jbG9zZU1hcmt1cC5yZXBsYWNlKCcldGl0bGUlJywgbWZwLnN0LnRDbG9zZSkpO1xuICAgICAgICBfY3VyclBvcHVwVHlwZSA9IHR5cGU7XG4gICAgICB9XG4gICAgICByZXR1cm4gbWZwLmN1cnJUZW1wbGF0ZS5jbG9zZUJ0bjtcbiAgICB9LFxuICAgIC8vIEluaXRpYWxpemUgTWFnbmlmaWMgUG9wdXAgb25seSB3aGVuIGNhbGxlZCBhdCBsZWFzdCBvbmNlXG4gICAgX2NoZWNrSW5zdGFuY2UgPSBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAoISQubWFnbmlmaWNQb3B1cC5pbnN0YW5jZSkge1xuICAgICAgICAvKmpzaGludCAtVzAyMCAqL1xuICAgICAgICBtZnAgPSBuZXcgTWFnbmlmaWNQb3B1cCgpO1xuICAgICAgICBtZnAuaW5pdCgpO1xuICAgICAgICAkLm1hZ25pZmljUG9wdXAuaW5zdGFuY2UgPSBtZnA7XG4gICAgICB9XG4gICAgfSxcbiAgICAvLyBDU1MgdHJhbnNpdGlvbiBkZXRlY3Rpb24sIGh0dHA6Ly9zdGFja292ZXJmbG93LmNvbS9xdWVzdGlvbnMvNzI2NDg5OS9kZXRlY3QtY3NzLXRyYW5zaXRpb25zLXVzaW5nLWphdmFzY3JpcHQtYW5kLXdpdGhvdXQtbW9kZXJuaXpyXG4gICAgc3VwcG9ydHNUcmFuc2l0aW9ucyA9IGZ1bmN0aW9uICgpIHtcbiAgICAgIHZhciBzID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgncCcpLnN0eWxlLCAvLyAncycgZm9yIHN0eWxlLiBiZXR0ZXIgdG8gY3JlYXRlIGFuIGVsZW1lbnQgaWYgYm9keSB5ZXQgdG8gZXhpc3RcbiAgICAgICAgdiA9IFsnbXMnLCAnTycsICdNb3onLCAnV2Via2l0J107IC8vICd2JyBmb3IgdmVuZG9yXG5cbiAgICAgIGlmIChzWyd0cmFuc2l0aW9uJ10gIT09IHVuZGVmaW5lZCkge1xuICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgIH1cblxuICAgICAgd2hpbGUgKHYubGVuZ3RoKSB7XG4gICAgICAgIGlmICh2LnBvcCgpICsgJ1RyYW5zaXRpb24nIGluIHMpIHtcbiAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfVxuICAgICAgfVxuXG4gICAgICByZXR1cm4gZmFsc2U7XG4gICAgfTtcblxuXG4gIC8qKlxuICAgKiBQdWJsaWMgZnVuY3Rpb25zXG4gICAqL1xuICBNYWduaWZpY1BvcHVwLnByb3RvdHlwZSA9IHtcblxuICAgIGNvbnN0cnVjdG9yOiBNYWduaWZpY1BvcHVwLFxuXG4gICAgLyoqXG4gICAgICogSW5pdGlhbGl6ZXMgTWFnbmlmaWMgUG9wdXAgcGx1Z2luLlxuICAgICAqIFRoaXMgZnVuY3Rpb24gaXMgdHJpZ2dlcmVkIG9ubHkgb25jZSB3aGVuICQuZm4ubWFnbmlmaWNQb3B1cCBvciAkLm1hZ25pZmljUG9wdXAgaXMgZXhlY3V0ZWRcbiAgICAgKi9cbiAgICBpbml0OiBmdW5jdGlvbiAoKSB7XG4gICAgICB2YXIgYXBwVmVyc2lvbiA9IG5hdmlnYXRvci5hcHBWZXJzaW9uO1xuICAgICAgbWZwLmlzTG93SUUgPSBtZnAuaXNJRTggPSBkb2N1bWVudC5hbGwgJiYgIWRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXI7XG4gICAgICBtZnAuaXNBbmRyb2lkID0gKC9hbmRyb2lkL2dpKS50ZXN0KGFwcFZlcnNpb24pO1xuICAgICAgbWZwLmlzSU9TID0gKC9pcGhvbmV8aXBhZHxpcG9kL2dpKS50ZXN0KGFwcFZlcnNpb24pO1xuICAgICAgbWZwLnN1cHBvcnRzVHJhbnNpdGlvbiA9IHN1cHBvcnRzVHJhbnNpdGlvbnMoKTtcblxuICAgICAgLy8gV2UgZGlzYWJsZSBmaXhlZCBwb3NpdGlvbmVkIGxpZ2h0Ym94IG9uIGRldmljZXMgdGhhdCBkb24ndCBoYW5kbGUgaXQgbmljZWx5LlxuICAgICAgLy8gSWYgeW91IGtub3cgYSBiZXR0ZXIgd2F5IG9mIGRldGVjdGluZyB0aGlzIC0gbGV0IG1lIGtub3cuXG4gICAgICBtZnAucHJvYmFibHlNb2JpbGUgPSAobWZwLmlzQW5kcm9pZCB8fCBtZnAuaXNJT1MgfHwgLyhPcGVyYSBNaW5pKXxLaW5kbGV8d2ViT1N8QmxhY2tCZXJyeXwoT3BlcmEgTW9iaSl8KFdpbmRvd3MgUGhvbmUpfElFTW9iaWxlL2kudGVzdChuYXZpZ2F0b3IudXNlckFnZW50KSk7XG4gICAgICBfZG9jdW1lbnQgPSAkKGRvY3VtZW50KTtcblxuICAgICAgbWZwLnBvcHVwc0NhY2hlID0ge307XG4gICAgfSxcblxuICAgIC8qKlxuICAgICAqIE9wZW5zIHBvcHVwXG4gICAgICogQHBhcmFtICBkYXRhIFtkZXNjcmlwdGlvbl1cbiAgICAgKi9cbiAgICBvcGVuOiBmdW5jdGlvbiAoZGF0YSkge1xuXG4gICAgICB2YXIgaTtcblxuICAgICAgaWYgKGRhdGEuaXNPYmogPT09IGZhbHNlKSB7XG4gICAgICAgIC8vIGNvbnZlcnQgalF1ZXJ5IGNvbGxlY3Rpb24gdG8gYXJyYXkgdG8gYXZvaWQgY29uZmxpY3RzIGxhdGVyXG4gICAgICAgIG1mcC5pdGVtcyA9IGRhdGEuaXRlbXMudG9BcnJheSgpO1xuXG4gICAgICAgIG1mcC5pbmRleCA9IDA7XG4gICAgICAgIHZhciBpdGVtcyA9IGRhdGEuaXRlbXMsXG4gICAgICAgICAgaXRlbTtcbiAgICAgICAgZm9yIChpID0gMDsgaSA8IGl0ZW1zLmxlbmd0aDsgaSsrKSB7XG4gICAgICAgICAgaXRlbSA9IGl0ZW1zW2ldO1xuICAgICAgICAgIGlmIChpdGVtLnBhcnNlZCkge1xuICAgICAgICAgICAgaXRlbSA9IGl0ZW0uZWxbMF07XG4gICAgICAgICAgfVxuICAgICAgICAgIGlmIChpdGVtID09PSBkYXRhLmVsWzBdKSB7XG4gICAgICAgICAgICBtZnAuaW5kZXggPSBpO1xuICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBtZnAuaXRlbXMgPSAkLmlzQXJyYXkoZGF0YS5pdGVtcykgPyBkYXRhLml0ZW1zIDogW2RhdGEuaXRlbXNdO1xuICAgICAgICBtZnAuaW5kZXggPSBkYXRhLmluZGV4IHx8IDA7XG4gICAgICB9XG5cbiAgICAgIC8vIGlmIHBvcHVwIGlzIGFscmVhZHkgb3BlbmVkIC0gd2UganVzdCB1cGRhdGUgdGhlIGNvbnRlbnRcbiAgICAgIGlmIChtZnAuaXNPcGVuKSB7XG4gICAgICAgIG1mcC51cGRhdGVJdGVtSFRNTCgpO1xuICAgICAgICByZXR1cm47XG4gICAgICB9XG5cbiAgICAgIG1mcC50eXBlcyA9IFtdO1xuICAgICAgX3dyYXBDbGFzc2VzID0gJyc7XG4gICAgICBpZiAoZGF0YS5tYWluRWwgJiYgZGF0YS5tYWluRWwubGVuZ3RoKSB7XG4gICAgICAgIG1mcC5ldiA9IGRhdGEubWFpbkVsLmVxKDApO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgbWZwLmV2ID0gX2RvY3VtZW50O1xuICAgICAgfVxuXG4gICAgICBpZiAoZGF0YS5rZXkpIHtcbiAgICAgICAgaWYgKCFtZnAucG9wdXBzQ2FjaGVbZGF0YS5rZXldKSB7XG4gICAgICAgICAgbWZwLnBvcHVwc0NhY2hlW2RhdGEua2V5XSA9IHt9O1xuICAgICAgICB9XG4gICAgICAgIG1mcC5jdXJyVGVtcGxhdGUgPSBtZnAucG9wdXBzQ2FjaGVbZGF0YS5rZXldO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgbWZwLmN1cnJUZW1wbGF0ZSA9IHt9O1xuICAgICAgfVxuXG5cbiAgICAgIG1mcC5zdCA9ICQuZXh0ZW5kKHRydWUsIHt9LCAkLm1hZ25pZmljUG9wdXAuZGVmYXVsdHMsIGRhdGEpO1xuICAgICAgbWZwLmZpeGVkQ29udGVudFBvcyA9IG1mcC5zdC5maXhlZENvbnRlbnRQb3MgPT09ICdhdXRvJyA/ICFtZnAucHJvYmFibHlNb2JpbGUgOiBtZnAuc3QuZml4ZWRDb250ZW50UG9zO1xuXG4gICAgICBpZiAobWZwLnN0Lm1vZGFsKSB7XG4gICAgICAgIG1mcC5zdC5jbG9zZU9uQ29udGVudENsaWNrID0gZmFsc2U7XG4gICAgICAgIG1mcC5zdC5jbG9zZU9uQmdDbGljayA9IGZhbHNlO1xuICAgICAgICBtZnAuc3Quc2hvd0Nsb3NlQnRuID0gZmFsc2U7XG4gICAgICAgIG1mcC5zdC5lbmFibGVFc2NhcGVLZXkgPSBmYWxzZTtcbiAgICAgIH1cblxuXG4gICAgICAvLyBCdWlsZGluZyBtYXJrdXBcbiAgICAgIC8vIG1haW4gY29udGFpbmVycyBhcmUgY3JlYXRlZCBvbmx5IG9uY2VcbiAgICAgIGlmICghbWZwLmJnT3ZlcmxheSkge1xuXG4gICAgICAgIC8vIERhcmsgb3ZlcmxheVxuICAgICAgICBtZnAuYmdPdmVybGF5ID0gX2dldEVsKCdiZycpLm9uKCdjbGljaycgKyBFVkVOVF9OUywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIG1mcC5jbG9zZSgpO1xuICAgICAgICB9KTtcblxuICAgICAgICBtZnAud3JhcCA9IF9nZXRFbCgnd3JhcCcpLmF0dHIoJ3RhYmluZGV4JywgLTEpLm9uKCdjbGljaycgKyBFVkVOVF9OUywgZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICBpZiAobWZwLl9jaGVja0lmQ2xvc2UoZS50YXJnZXQpKSB7XG4gICAgICAgICAgICBtZnAuY2xvc2UoKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuXG4gICAgICAgIG1mcC5jb250YWluZXIgPSBfZ2V0RWwoJ2NvbnRhaW5lcicsIG1mcC53cmFwKTtcbiAgICAgIH1cblxuICAgICAgbWZwLmNvbnRlbnRDb250YWluZXIgPSBfZ2V0RWwoJ2NvbnRlbnQnKTtcbiAgICAgIGlmIChtZnAuc3QucHJlbG9hZGVyKSB7XG4gICAgICAgIG1mcC5wcmVsb2FkZXIgPSBfZ2V0RWwoJ3ByZWxvYWRlcicsIG1mcC5jb250YWluZXIsIG1mcC5zdC50TG9hZGluZyk7XG4gICAgICB9XG5cblxuICAgICAgLy8gSW5pdGlhbGl6aW5nIG1vZHVsZXNcbiAgICAgIHZhciBtb2R1bGVzID0gJC5tYWduaWZpY1BvcHVwLm1vZHVsZXM7XG4gICAgICBmb3IgKGkgPSAwOyBpIDwgbW9kdWxlcy5sZW5ndGg7IGkrKykge1xuICAgICAgICB2YXIgbiA9IG1vZHVsZXNbaV07XG4gICAgICAgIG4gPSBuLmNoYXJBdCgwKS50b1VwcGVyQ2FzZSgpICsgbi5zbGljZSgxKTtcbiAgICAgICAgbWZwWydpbml0JyArIG5dLmNhbGwobWZwKTtcbiAgICAgIH1cbiAgICAgIF9tZnBUcmlnZ2VyKCdCZWZvcmVPcGVuJyk7XG5cblxuICAgICAgaWYgKG1mcC5zdC5zaG93Q2xvc2VCdG4pIHtcbiAgICAgICAgLy8gQ2xvc2UgYnV0dG9uXG4gICAgICAgIGlmICghbWZwLnN0LmNsb3NlQnRuSW5zaWRlKSB7XG4gICAgICAgICAgbWZwLndyYXAuYXBwZW5kKF9nZXRDbG9zZUJ0bigpKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICBfbWZwT24oTUFSS1VQX1BBUlNFX0VWRU5ULCBmdW5jdGlvbiAoZSwgdGVtcGxhdGUsIHZhbHVlcywgaXRlbSkge1xuICAgICAgICAgICAgdmFsdWVzLmNsb3NlX3JlcGxhY2VXaXRoID0gX2dldENsb3NlQnRuKGl0ZW0udHlwZSk7XG4gICAgICAgICAgfSk7XG4gICAgICAgICAgX3dyYXBDbGFzc2VzICs9ICcgbWZwLWNsb3NlLWJ0bi1pbic7XG4gICAgICAgIH1cbiAgICAgIH1cblxuICAgICAgaWYgKG1mcC5zdC5hbGlnblRvcCkge1xuICAgICAgICBfd3JhcENsYXNzZXMgKz0gJyBtZnAtYWxpZ24tdG9wJztcbiAgICAgIH1cblxuXG4gICAgICBpZiAobWZwLmZpeGVkQ29udGVudFBvcykge1xuICAgICAgICBtZnAud3JhcC5jc3Moe1xuICAgICAgICAgIG92ZXJmbG93OiBtZnAuc3Qub3ZlcmZsb3dZLFxuICAgICAgICAgIG92ZXJmbG93WDogJ2hpZGRlbicsXG4gICAgICAgICAgb3ZlcmZsb3dZOiBtZnAuc3Qub3ZlcmZsb3dZXG4gICAgICAgIH0pO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgbWZwLndyYXAuY3NzKHtcbiAgICAgICAgICB0b3A6IF93aW5kb3cuc2Nyb2xsVG9wKCksXG4gICAgICAgICAgcG9zaXRpb246ICdhYnNvbHV0ZSdcbiAgICAgICAgfSk7XG4gICAgICB9XG4gICAgICBpZiAobWZwLnN0LmZpeGVkQmdQb3MgPT09IGZhbHNlIHx8IChtZnAuc3QuZml4ZWRCZ1BvcyA9PT0gJ2F1dG8nICYmICFtZnAuZml4ZWRDb250ZW50UG9zKSkge1xuICAgICAgICBtZnAuYmdPdmVybGF5LmNzcyh7XG4gICAgICAgICAgaGVpZ2h0OiBfZG9jdW1lbnQuaGVpZ2h0KCksXG4gICAgICAgICAgcG9zaXRpb246ICdhYnNvbHV0ZSdcbiAgICAgICAgfSk7XG4gICAgICB9XG5cblxuICAgICAgaWYgKG1mcC5zdC5lbmFibGVFc2NhcGVLZXkpIHtcbiAgICAgICAgLy8gQ2xvc2Ugb24gRVNDIGtleVxuICAgICAgICBfZG9jdW1lbnQub24oJ2tleXVwJyArIEVWRU5UX05TLCBmdW5jdGlvbiAoZSkge1xuICAgICAgICAgIGlmIChlLmtleUNvZGUgPT09IDI3KSB7XG4gICAgICAgICAgICBtZnAuY2xvc2UoKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgfVxuXG4gICAgICBfd2luZG93Lm9uKCdyZXNpemUnICsgRVZFTlRfTlMsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgbWZwLnVwZGF0ZVNpemUoKTtcbiAgICAgIH0pO1xuXG5cbiAgICAgIGlmICghbWZwLnN0LmNsb3NlT25Db250ZW50Q2xpY2spIHtcbiAgICAgICAgX3dyYXBDbGFzc2VzICs9ICcgbWZwLWF1dG8tY3Vyc29yJztcbiAgICAgIH1cblxuICAgICAgaWYgKF93cmFwQ2xhc3NlcylcbiAgICAgICAgbWZwLndyYXAuYWRkQ2xhc3MoX3dyYXBDbGFzc2VzKTtcblxuXG4gICAgICAvLyB0aGlzIHRyaWdnZXJzIHJlY2FsY3VsYXRpb24gb2YgbGF5b3V0LCBzbyB3ZSBnZXQgaXQgb25jZSB0byBub3QgdG8gdHJpZ2dlciB0d2ljZVxuICAgICAgdmFyIHdpbmRvd0hlaWdodCA9IG1mcC53SCA9IF93aW5kb3cuaGVpZ2h0KCk7XG5cblxuICAgICAgdmFyIHdpbmRvd1N0eWxlcyA9IHt9O1xuXG4gICAgICBpZiAobWZwLmZpeGVkQ29udGVudFBvcykge1xuICAgICAgICBpZiAobWZwLl9oYXNTY3JvbGxCYXIod2luZG93SGVpZ2h0KSkge1xuICAgICAgICAgIHZhciBzID0gbWZwLl9nZXRTY3JvbGxiYXJTaXplKCk7XG4gICAgICAgICAgaWYgKHMpIHtcbiAgICAgICAgICAgIHdpbmRvd1N0eWxlcy5tYXJnaW5SaWdodCA9IHM7XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIGlmIChtZnAuZml4ZWRDb250ZW50UG9zKSB7XG4gICAgICAgIGlmICghbWZwLmlzSUU3KSB7XG4gICAgICAgICAgd2luZG93U3R5bGVzLm92ZXJmbG93ID0gJ2hpZGRlbic7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgLy8gaWU3IGRvdWJsZS1zY3JvbGwgYnVnXG4gICAgICAgICAgJCgnYm9keSwgaHRtbCcpLmNzcygnb3ZlcmZsb3cnLCAnaGlkZGVuJyk7XG4gICAgICAgIH1cbiAgICAgIH1cblxuXG4gICAgICB2YXIgY2xhc3Nlc1RvYWRkID0gbWZwLnN0Lm1haW5DbGFzcztcbiAgICAgIGlmIChtZnAuaXNJRTcpIHtcbiAgICAgICAgY2xhc3Nlc1RvYWRkICs9ICcgbWZwLWllNyc7XG4gICAgICB9XG4gICAgICBpZiAoY2xhc3Nlc1RvYWRkKSB7XG4gICAgICAgIG1mcC5fYWRkQ2xhc3NUb01GUChjbGFzc2VzVG9hZGQpO1xuICAgICAgfVxuXG4gICAgICAvLyBhZGQgY29udGVudFxuICAgICAgbWZwLnVwZGF0ZUl0ZW1IVE1MKCk7XG5cbiAgICAgIF9tZnBUcmlnZ2VyKCdCdWlsZENvbnRyb2xzJyk7XG5cbiAgICAgIC8vIHJlbW92ZSBzY3JvbGxiYXIsIGFkZCBtYXJnaW4gZS50LmNcbiAgICAgICQoJ2h0bWwnKS5jc3Mod2luZG93U3R5bGVzKTtcblxuICAgICAgLy8gYWRkIGV2ZXJ5dGhpbmcgdG8gRE9NXG4gICAgICBtZnAuYmdPdmVybGF5LmFkZChtZnAud3JhcCkucHJlcGVuZFRvKG1mcC5zdC5wcmVwZW5kVG8gfHwgJChkb2N1bWVudC5ib2R5KSk7XG5cbiAgICAgIC8vIFNhdmUgbGFzdCBmb2N1c2VkIGVsZW1lbnRcbiAgICAgIG1mcC5fbGFzdEZvY3VzZWRFbCA9IGRvY3VtZW50LmFjdGl2ZUVsZW1lbnQ7XG5cbiAgICAgIC8vIFdhaXQgZm9yIG5leHQgY3ljbGUgdG8gYWxsb3cgQ1NTIHRyYW5zaXRpb25cbiAgICAgIHNldFRpbWVvdXQoZnVuY3Rpb24gKCkge1xuXG4gICAgICAgIGlmIChtZnAuY29udGVudCkge1xuICAgICAgICAgIG1mcC5fYWRkQ2xhc3NUb01GUChSRUFEWV9DTEFTUyk7XG4gICAgICAgICAgbWZwLl9zZXRGb2N1cygpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIC8vIGlmIGNvbnRlbnQgaXMgbm90IGRlZmluZWQgKG5vdCBsb2FkZWQgZS50LmMpIHdlIGFkZCBjbGFzcyBvbmx5IGZvciBCR1xuICAgICAgICAgIG1mcC5iZ092ZXJsYXkuYWRkQ2xhc3MoUkVBRFlfQ0xBU1MpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gVHJhcCB0aGUgZm9jdXMgaW4gcG9wdXBcbiAgICAgICAgX2RvY3VtZW50Lm9uKCdmb2N1c2luJyArIEVWRU5UX05TLCBtZnAuX29uRm9jdXNJbik7XG5cbiAgICAgIH0sIDE2KTtcblxuICAgICAgbWZwLmlzT3BlbiA9IHRydWU7XG4gICAgICBtZnAudXBkYXRlU2l6ZSh3aW5kb3dIZWlnaHQpO1xuICAgICAgX21mcFRyaWdnZXIoT1BFTl9FVkVOVCk7XG5cbiAgICAgIHJldHVybiBkYXRhO1xuICAgIH0sXG5cbiAgICAvKipcbiAgICAgKiBDbG9zZXMgdGhlIHBvcHVwXG4gICAgICovXG4gICAgY2xvc2U6IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICghbWZwLmlzT3BlbikgcmV0dXJuO1xuICAgICAgX21mcFRyaWdnZXIoQkVGT1JFX0NMT1NFX0VWRU5UKTtcblxuICAgICAgbWZwLmlzT3BlbiA9IGZhbHNlO1xuICAgICAgLy8gZm9yIENTUzMgYW5pbWF0aW9uXG4gICAgICBpZiAobWZwLnN0LnJlbW92YWxEZWxheSAmJiAhbWZwLmlzTG93SUUgJiYgbWZwLnN1cHBvcnRzVHJhbnNpdGlvbikge1xuICAgICAgICBtZnAuX2FkZENsYXNzVG9NRlAoUkVNT1ZJTkdfQ0xBU1MpO1xuICAgICAgICBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICBtZnAuX2Nsb3NlKCk7XG4gICAgICAgIH0sIG1mcC5zdC5yZW1vdmFsRGVsYXkpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgbWZwLl9jbG9zZSgpO1xuICAgICAgfVxuICAgIH0sXG5cbiAgICAvKipcbiAgICAgKiBIZWxwZXIgZm9yIGNsb3NlKCkgZnVuY3Rpb25cbiAgICAgKi9cbiAgICBfY2xvc2U6IGZ1bmN0aW9uICgpIHtcbiAgICAgIF9tZnBUcmlnZ2VyKENMT1NFX0VWRU5UKTtcblxuICAgICAgdmFyIGNsYXNzZXNUb1JlbW92ZSA9IFJFTU9WSU5HX0NMQVNTICsgJyAnICsgUkVBRFlfQ0xBU1MgKyAnICc7XG5cbiAgICAgIG1mcC5iZ092ZXJsYXkuZGV0YWNoKCk7XG4gICAgICBtZnAud3JhcC5kZXRhY2goKTtcbiAgICAgIG1mcC5jb250YWluZXIuZW1wdHkoKTtcblxuICAgICAgaWYgKG1mcC5zdC5tYWluQ2xhc3MpIHtcbiAgICAgICAgY2xhc3Nlc1RvUmVtb3ZlICs9IG1mcC5zdC5tYWluQ2xhc3MgKyAnICc7XG4gICAgICB9XG5cbiAgICAgIG1mcC5fcmVtb3ZlQ2xhc3NGcm9tTUZQKGNsYXNzZXNUb1JlbW92ZSk7XG5cbiAgICAgIGlmIChtZnAuZml4ZWRDb250ZW50UG9zKSB7XG4gICAgICAgIHZhciB3aW5kb3dTdHlsZXMgPSB7bWFyZ2luUmlnaHQ6ICcnfTtcbiAgICAgICAgaWYgKG1mcC5pc0lFNykge1xuICAgICAgICAgICQoJ2JvZHksIGh0bWwnKS5jc3MoJ292ZXJmbG93JywgJycpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIHdpbmRvd1N0eWxlcy5vdmVyZmxvdyA9ICcnO1xuICAgICAgICB9XG4gICAgICAgICQoJ2h0bWwnKS5jc3Mod2luZG93U3R5bGVzKTtcbiAgICAgIH1cblxuICAgICAgX2RvY3VtZW50Lm9mZigna2V5dXAnICsgRVZFTlRfTlMgKyAnIGZvY3VzaW4nICsgRVZFTlRfTlMpO1xuICAgICAgbWZwLmV2Lm9mZihFVkVOVF9OUyk7XG5cbiAgICAgIC8vIGNsZWFuIHVwIERPTSBlbGVtZW50cyB0aGF0IGFyZW4ndCByZW1vdmVkXG4gICAgICBtZnAud3JhcC5hdHRyKCdjbGFzcycsICdtZnAtd3JhcCcpLnJlbW92ZUF0dHIoJ3N0eWxlJyk7XG4gICAgICBtZnAuYmdPdmVybGF5LmF0dHIoJ2NsYXNzJywgJ21mcC1iZycpO1xuICAgICAgbWZwLmNvbnRhaW5lci5hdHRyKCdjbGFzcycsICdtZnAtY29udGFpbmVyJyk7XG5cbiAgICAgIC8vIHJlbW92ZSBjbG9zZSBidXR0b24gZnJvbSB0YXJnZXQgZWxlbWVudFxuICAgICAgaWYgKG1mcC5zdC5zaG93Q2xvc2VCdG4gJiZcbiAgICAgICAgKCFtZnAuc3QuY2xvc2VCdG5JbnNpZGUgfHwgbWZwLmN1cnJUZW1wbGF0ZVttZnAuY3Vyckl0ZW0udHlwZV0gPT09IHRydWUpKSB7XG4gICAgICAgIGlmIChtZnAuY3VyclRlbXBsYXRlLmNsb3NlQnRuKVxuICAgICAgICAgIG1mcC5jdXJyVGVtcGxhdGUuY2xvc2VCdG4uZGV0YWNoKCk7XG4gICAgICB9XG5cblxuICAgICAgaWYgKG1mcC5zdC5hdXRvRm9jdXNMYXN0ICYmIG1mcC5fbGFzdEZvY3VzZWRFbCkge1xuICAgICAgICAkKG1mcC5fbGFzdEZvY3VzZWRFbCkuZm9jdXMoKTsgLy8gcHV0IHRhYiBmb2N1cyBiYWNrXG4gICAgICB9XG4gICAgICBtZnAuY3Vyckl0ZW0gPSBudWxsO1xuICAgICAgbWZwLmNvbnRlbnQgPSBudWxsO1xuICAgICAgbWZwLmN1cnJUZW1wbGF0ZSA9IG51bGw7XG4gICAgICBtZnAucHJldkhlaWdodCA9IDA7XG5cbiAgICAgIF9tZnBUcmlnZ2VyKEFGVEVSX0NMT1NFX0VWRU5UKTtcbiAgICB9LFxuXG4gICAgdXBkYXRlU2l6ZTogZnVuY3Rpb24gKHdpbkhlaWdodCkge1xuXG4gICAgICBpZiAobWZwLmlzSU9TKSB7XG4gICAgICAgIC8vIGZpeGVzIGlPUyBuYXYgYmFycyBodHRwczovL2dpdGh1Yi5jb20vZGltc2VtZW5vdi9NYWduaWZpYy1Qb3B1cC9pc3N1ZXMvMlxuICAgICAgICB2YXIgem9vbUxldmVsID0gZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LmNsaWVudFdpZHRoIC8gd2luZG93LmlubmVyV2lkdGg7XG4gICAgICAgIHZhciBoZWlnaHQgPSB3aW5kb3cuaW5uZXJIZWlnaHQgKiB6b29tTGV2ZWw7XG4gICAgICAgIG1mcC53cmFwLmNzcygnaGVpZ2h0JywgaGVpZ2h0KTtcbiAgICAgICAgbWZwLndIID0gaGVpZ2h0O1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgbWZwLndIID0gd2luSGVpZ2h0IHx8IF93aW5kb3cuaGVpZ2h0KCk7XG4gICAgICB9XG4gICAgICAvLyBGaXhlcyAjODQ6IHBvcHVwIGluY29ycmVjdGx5IHBvc2l0aW9uZWQgd2l0aCBwb3NpdGlvbjpyZWxhdGl2ZSBvbiBib2R5XG4gICAgICBpZiAoIW1mcC5maXhlZENvbnRlbnRQb3MpIHtcbiAgICAgICAgbWZwLndyYXAuY3NzKCdoZWlnaHQnLCBtZnAud0gpO1xuICAgICAgfVxuXG4gICAgICBfbWZwVHJpZ2dlcignUmVzaXplJyk7XG5cbiAgICB9LFxuXG4gICAgLyoqXG4gICAgICogU2V0IGNvbnRlbnQgb2YgcG9wdXAgYmFzZWQgb24gY3VycmVudCBpbmRleFxuICAgICAqL1xuICAgIHVwZGF0ZUl0ZW1IVE1MOiBmdW5jdGlvbiAoKSB7XG4gICAgICB2YXIgaXRlbSA9IG1mcC5pdGVtc1ttZnAuaW5kZXhdO1xuXG4gICAgICAvLyBEZXRhY2ggYW5kIHBlcmZvcm0gbW9kaWZpY2F0aW9uc1xuICAgICAgbWZwLmNvbnRlbnRDb250YWluZXIuZGV0YWNoKCk7XG5cbiAgICAgIGlmIChtZnAuY29udGVudClcbiAgICAgICAgbWZwLmNvbnRlbnQuZGV0YWNoKCk7XG5cbiAgICAgIGlmICghaXRlbS5wYXJzZWQpIHtcbiAgICAgICAgaXRlbSA9IG1mcC5wYXJzZUVsKG1mcC5pbmRleCk7XG4gICAgICB9XG5cbiAgICAgIHZhciB0eXBlID0gaXRlbS50eXBlO1xuXG4gICAgICBfbWZwVHJpZ2dlcignQmVmb3JlQ2hhbmdlJywgW21mcC5jdXJySXRlbSA/IG1mcC5jdXJySXRlbS50eXBlIDogJycsIHR5cGVdKTtcbiAgICAgIC8vIEJlZm9yZUNoYW5nZSBldmVudCB3b3JrcyBsaWtlIHNvOlxuICAgICAgLy8gX21mcE9uKCdCZWZvcmVDaGFuZ2UnLCBmdW5jdGlvbihlLCBwcmV2VHlwZSwgbmV3VHlwZSkgeyB9KTtcblxuICAgICAgbWZwLmN1cnJJdGVtID0gaXRlbTtcblxuICAgICAgaWYgKCFtZnAuY3VyclRlbXBsYXRlW3R5cGVdKSB7XG4gICAgICAgIHZhciBtYXJrdXAgPSBtZnAuc3RbdHlwZV0gPyBtZnAuc3RbdHlwZV0ubWFya3VwIDogZmFsc2U7XG5cbiAgICAgICAgLy8gYWxsb3dzIHRvIG1vZGlmeSBtYXJrdXBcbiAgICAgICAgX21mcFRyaWdnZXIoJ0ZpcnN0TWFya3VwUGFyc2UnLCBtYXJrdXApO1xuXG4gICAgICAgIGlmIChtYXJrdXApIHtcbiAgICAgICAgICBtZnAuY3VyclRlbXBsYXRlW3R5cGVdID0gJChtYXJrdXApO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIC8vIGlmIHRoZXJlIGlzIG5vIG1hcmt1cCBmb3VuZCB3ZSBqdXN0IGRlZmluZSB0aGF0IHRlbXBsYXRlIGlzIHBhcnNlZFxuICAgICAgICAgIG1mcC5jdXJyVGVtcGxhdGVbdHlwZV0gPSB0cnVlO1xuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIGlmIChfcHJldkNvbnRlbnRUeXBlICYmIF9wcmV2Q29udGVudFR5cGUgIT09IGl0ZW0udHlwZSkge1xuICAgICAgICBtZnAuY29udGFpbmVyLnJlbW92ZUNsYXNzKCdtZnAtJyArIF9wcmV2Q29udGVudFR5cGUgKyAnLWhvbGRlcicpO1xuICAgICAgfVxuXG4gICAgICB2YXIgbmV3Q29udGVudCA9IG1mcFsnZ2V0JyArIHR5cGUuY2hhckF0KDApLnRvVXBwZXJDYXNlKCkgKyB0eXBlLnNsaWNlKDEpXShpdGVtLCBtZnAuY3VyclRlbXBsYXRlW3R5cGVdKTtcbiAgICAgIG1mcC5hcHBlbmRDb250ZW50KG5ld0NvbnRlbnQsIHR5cGUpO1xuXG4gICAgICBpdGVtLnByZWxvYWRlZCA9IHRydWU7XG5cbiAgICAgIF9tZnBUcmlnZ2VyKENIQU5HRV9FVkVOVCwgaXRlbSk7XG4gICAgICBfcHJldkNvbnRlbnRUeXBlID0gaXRlbS50eXBlO1xuXG4gICAgICAvLyBBcHBlbmQgY29udGFpbmVyIGJhY2sgYWZ0ZXIgaXRzIGNvbnRlbnQgY2hhbmdlZFxuICAgICAgbWZwLmNvbnRhaW5lci5wcmVwZW5kKG1mcC5jb250ZW50Q29udGFpbmVyKTtcblxuICAgICAgX21mcFRyaWdnZXIoJ0FmdGVyQ2hhbmdlJyk7XG4gICAgfSxcblxuXG4gICAgLyoqXG4gICAgICogU2V0IEhUTUwgY29udGVudCBvZiBwb3B1cFxuICAgICAqL1xuICAgIGFwcGVuZENvbnRlbnQ6IGZ1bmN0aW9uIChuZXdDb250ZW50LCB0eXBlKSB7XG4gICAgICBtZnAuY29udGVudCA9IG5ld0NvbnRlbnQ7XG5cbiAgICAgIGlmIChuZXdDb250ZW50KSB7XG4gICAgICAgIGlmIChtZnAuc3Quc2hvd0Nsb3NlQnRuICYmIG1mcC5zdC5jbG9zZUJ0bkluc2lkZSAmJlxuICAgICAgICAgIG1mcC5jdXJyVGVtcGxhdGVbdHlwZV0gPT09IHRydWUpIHtcbiAgICAgICAgICAvLyBpZiB0aGVyZSBpcyBubyBtYXJrdXAsIHdlIGp1c3QgYXBwZW5kIGNsb3NlIGJ1dHRvbiBlbGVtZW50IGluc2lkZVxuICAgICAgICAgIGlmICghbWZwLmNvbnRlbnQuZmluZCgnLm1mcC1jbG9zZScpLmxlbmd0aCkge1xuICAgICAgICAgICAgbWZwLmNvbnRlbnQuYXBwZW5kKF9nZXRDbG9zZUJ0bigpKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgbWZwLmNvbnRlbnQgPSBuZXdDb250ZW50O1xuICAgICAgICB9XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBtZnAuY29udGVudCA9ICcnO1xuICAgICAgfVxuXG4gICAgICBfbWZwVHJpZ2dlcihCRUZPUkVfQVBQRU5EX0VWRU5UKTtcbiAgICAgIG1mcC5jb250YWluZXIuYWRkQ2xhc3MoJ21mcC0nICsgdHlwZSArICctaG9sZGVyJyk7XG5cbiAgICAgIG1mcC5jb250ZW50Q29udGFpbmVyLmFwcGVuZChtZnAuY29udGVudCk7XG4gICAgfSxcblxuXG4gICAgLyoqXG4gICAgICogQ3JlYXRlcyBNYWduaWZpYyBQb3B1cCBkYXRhIG9iamVjdCBiYXNlZCBvbiBnaXZlbiBkYXRhXG4gICAgICogQHBhcmFtICB7aW50fSBpbmRleCBJbmRleCBvZiBpdGVtIHRvIHBhcnNlXG4gICAgICovXG4gICAgcGFyc2VFbDogZnVuY3Rpb24gKGluZGV4KSB7XG4gICAgICB2YXIgaXRlbSA9IG1mcC5pdGVtc1tpbmRleF0sXG4gICAgICAgIHR5cGU7XG5cbiAgICAgIGlmIChpdGVtLnRhZ05hbWUpIHtcbiAgICAgICAgaXRlbSA9IHtlbDogJChpdGVtKX07XG4gICAgICB9IGVsc2Uge1xuICAgICAgICB0eXBlID0gaXRlbS50eXBlO1xuICAgICAgICBpdGVtID0ge2RhdGE6IGl0ZW0sIHNyYzogaXRlbS5zcmN9O1xuICAgICAgfVxuXG4gICAgICBpZiAoaXRlbS5lbCkge1xuICAgICAgICB2YXIgdHlwZXMgPSBtZnAudHlwZXM7XG5cbiAgICAgICAgLy8gY2hlY2sgZm9yICdtZnAtVFlQRScgY2xhc3NcbiAgICAgICAgZm9yICh2YXIgaSA9IDA7IGkgPCB0eXBlcy5sZW5ndGg7IGkrKykge1xuICAgICAgICAgIGlmIChpdGVtLmVsLmhhc0NsYXNzKCdtZnAtJyArIHR5cGVzW2ldKSkge1xuICAgICAgICAgICAgdHlwZSA9IHR5cGVzW2ldO1xuICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgaXRlbS5zcmMgPSBpdGVtLmVsLmF0dHIoJ2RhdGEtbWZwLXNyYycpO1xuICAgICAgICBpZiAoIWl0ZW0uc3JjKSB7XG4gICAgICAgICAgaXRlbS5zcmMgPSBpdGVtLmVsLmF0dHIoJ2hyZWYnKTtcbiAgICAgICAgfVxuICAgICAgfVxuXG4gICAgICBpdGVtLnR5cGUgPSB0eXBlIHx8IG1mcC5zdC50eXBlIHx8ICdpbmxpbmUnO1xuICAgICAgaXRlbS5pbmRleCA9IGluZGV4O1xuICAgICAgaXRlbS5wYXJzZWQgPSB0cnVlO1xuICAgICAgbWZwLml0ZW1zW2luZGV4XSA9IGl0ZW07XG4gICAgICBfbWZwVHJpZ2dlcignRWxlbWVudFBhcnNlJywgaXRlbSk7XG5cbiAgICAgIHJldHVybiBtZnAuaXRlbXNbaW5kZXhdO1xuICAgIH0sXG5cblxuICAgIC8qKlxuICAgICAqIEluaXRpYWxpemVzIHNpbmdsZSBwb3B1cCBvciBhIGdyb3VwIG9mIHBvcHVwc1xuICAgICAqL1xuICAgIGFkZEdyb3VwOiBmdW5jdGlvbiAoZWwsIG9wdGlvbnMpIHtcbiAgICAgIHZhciBlSGFuZGxlciA9IGZ1bmN0aW9uIChlKSB7XG4gICAgICAgIGUubWZwRWwgPSB0aGlzO1xuICAgICAgICBtZnAuX29wZW5DbGljayhlLCBlbCwgb3B0aW9ucyk7XG4gICAgICB9O1xuXG4gICAgICBpZiAoIW9wdGlvbnMpIHtcbiAgICAgICAgb3B0aW9ucyA9IHt9O1xuICAgICAgfVxuXG4gICAgICB2YXIgZU5hbWUgPSAnY2xpY2subWFnbmlmaWNQb3B1cCc7XG4gICAgICBvcHRpb25zLm1haW5FbCA9IGVsO1xuXG4gICAgICBpZiAob3B0aW9ucy5pdGVtcykge1xuICAgICAgICBvcHRpb25zLmlzT2JqID0gdHJ1ZTtcbiAgICAgICAgZWwub2ZmKGVOYW1lKS5vbihlTmFtZSwgZUhhbmRsZXIpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgb3B0aW9ucy5pc09iaiA9IGZhbHNlO1xuICAgICAgICBpZiAob3B0aW9ucy5kZWxlZ2F0ZSkge1xuICAgICAgICAgIGVsLm9mZihlTmFtZSkub24oZU5hbWUsIG9wdGlvbnMuZGVsZWdhdGUsIGVIYW5kbGVyKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICBvcHRpb25zLml0ZW1zID0gZWw7XG4gICAgICAgICAgZWwub2ZmKGVOYW1lKS5vbihlTmFtZSwgZUhhbmRsZXIpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcbiAgICBfb3BlbkNsaWNrOiBmdW5jdGlvbiAoZSwgZWwsIG9wdGlvbnMpIHtcbiAgICAgIHZhciBtaWRDbGljayA9IG9wdGlvbnMubWlkQ2xpY2sgIT09IHVuZGVmaW5lZCA/IG9wdGlvbnMubWlkQ2xpY2sgOiAkLm1hZ25pZmljUG9wdXAuZGVmYXVsdHMubWlkQ2xpY2s7XG5cblxuICAgICAgaWYgKCFtaWRDbGljayAmJiAoZS53aGljaCA9PT0gMiB8fCBlLmN0cmxLZXkgfHwgZS5tZXRhS2V5IHx8IGUuYWx0S2V5IHx8IGUuc2hpZnRLZXkpKSB7XG4gICAgICAgIHJldHVybjtcbiAgICAgIH1cblxuICAgICAgdmFyIGRpc2FibGVPbiA9IG9wdGlvbnMuZGlzYWJsZU9uICE9PSB1bmRlZmluZWQgPyBvcHRpb25zLmRpc2FibGVPbiA6ICQubWFnbmlmaWNQb3B1cC5kZWZhdWx0cy5kaXNhYmxlT247XG5cbiAgICAgIGlmIChkaXNhYmxlT24pIHtcbiAgICAgICAgaWYgKCQuaXNGdW5jdGlvbihkaXNhYmxlT24pKSB7XG4gICAgICAgICAgaWYgKCFkaXNhYmxlT24uY2FsbChtZnApKSB7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSB7IC8vIGVsc2UgaXQncyBudW1iZXJcbiAgICAgICAgICBpZiAoX3dpbmRvdy53aWR0aCgpIDwgZGlzYWJsZU9uKSB7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgIH1cblxuICAgICAgaWYgKGUudHlwZSkge1xuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG5cbiAgICAgICAgLy8gVGhpcyB3aWxsIHByZXZlbnQgcG9wdXAgZnJvbSBjbG9zaW5nIGlmIGVsZW1lbnQgaXMgaW5zaWRlIGFuZCBwb3B1cCBpcyBhbHJlYWR5IG9wZW5lZFxuICAgICAgICBpZiAobWZwLmlzT3Blbikge1xuICAgICAgICAgIGUuc3RvcFByb3BhZ2F0aW9uKCk7XG4gICAgICAgIH1cbiAgICAgIH1cblxuICAgICAgb3B0aW9ucy5lbCA9ICQoZS5tZnBFbCk7XG4gICAgICBpZiAob3B0aW9ucy5kZWxlZ2F0ZSkge1xuICAgICAgICBvcHRpb25zLml0ZW1zID0gZWwuZmluZChvcHRpb25zLmRlbGVnYXRlKTtcbiAgICAgIH1cbiAgICAgIG1mcC5vcGVuKG9wdGlvbnMpO1xuICAgIH0sXG5cblxuICAgIC8qKlxuICAgICAqIFVwZGF0ZXMgdGV4dCBvbiBwcmVsb2FkZXJcbiAgICAgKi9cbiAgICB1cGRhdGVTdGF0dXM6IGZ1bmN0aW9uIChzdGF0dXMsIHRleHQpIHtcblxuICAgICAgaWYgKG1mcC5wcmVsb2FkZXIpIHtcbiAgICAgICAgaWYgKF9wcmV2U3RhdHVzICE9PSBzdGF0dXMpIHtcbiAgICAgICAgICBtZnAuY29udGFpbmVyLnJlbW92ZUNsYXNzKCdtZnAtcy0nICsgX3ByZXZTdGF0dXMpO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKCF0ZXh0ICYmIHN0YXR1cyA9PT0gJ2xvYWRpbmcnKSB7XG4gICAgICAgICAgdGV4dCA9IG1mcC5zdC50TG9hZGluZztcbiAgICAgICAgfVxuXG4gICAgICAgIHZhciBkYXRhID0ge1xuICAgICAgICAgIHN0YXR1czogc3RhdHVzLFxuICAgICAgICAgIHRleHQ6IHRleHRcbiAgICAgICAgfTtcbiAgICAgICAgLy8gYWxsb3dzIHRvIG1vZGlmeSBzdGF0dXNcbiAgICAgICAgX21mcFRyaWdnZXIoJ1VwZGF0ZVN0YXR1cycsIGRhdGEpO1xuXG4gICAgICAgIHN0YXR1cyA9IGRhdGEuc3RhdHVzO1xuICAgICAgICB0ZXh0ID0gZGF0YS50ZXh0O1xuXG4gICAgICAgIG1mcC5wcmVsb2FkZXIuaHRtbCh0ZXh0KTtcblxuICAgICAgICBtZnAucHJlbG9hZGVyLmZpbmQoJ2EnKS5vbignY2xpY2snLCBmdW5jdGlvbiAoZSkge1xuICAgICAgICAgIGUuc3RvcEltbWVkaWF0ZVByb3BhZ2F0aW9uKCk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIG1mcC5jb250YWluZXIuYWRkQ2xhc3MoJ21mcC1zLScgKyBzdGF0dXMpO1xuICAgICAgICBfcHJldlN0YXR1cyA9IHN0YXR1cztcbiAgICAgIH1cbiAgICB9LFxuXG5cbiAgICAvKlxuICAgICAgXCJQcml2YXRlXCIgaGVscGVycyB0aGF0IGFyZW4ndCBwcml2YXRlIGF0IGFsbFxuICAgICAqL1xuICAgIC8vIENoZWNrIHRvIGNsb3NlIHBvcHVwIG9yIG5vdFxuICAgIC8vIFwidGFyZ2V0XCIgaXMgYW4gZWxlbWVudCB0aGF0IHdhcyBjbGlja2VkXG4gICAgX2NoZWNrSWZDbG9zZTogZnVuY3Rpb24gKHRhcmdldCkge1xuXG4gICAgICBpZiAoJCh0YXJnZXQpLmhhc0NsYXNzKFBSRVZFTlRfQ0xPU0VfQ0xBU1MpKSB7XG4gICAgICAgIHJldHVybjtcbiAgICAgIH1cblxuICAgICAgdmFyIGNsb3NlT25Db250ZW50ID0gbWZwLnN0LmNsb3NlT25Db250ZW50Q2xpY2s7XG4gICAgICB2YXIgY2xvc2VPbkJnID0gbWZwLnN0LmNsb3NlT25CZ0NsaWNrO1xuXG4gICAgICBpZiAoY2xvc2VPbkNvbnRlbnQgJiYgY2xvc2VPbkJnKSB7XG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgfSBlbHNlIHtcblxuICAgICAgICAvLyBXZSBjbG9zZSB0aGUgcG9wdXAgaWYgY2xpY2sgaXMgb24gY2xvc2UgYnV0dG9uIG9yIG9uIHByZWxvYWRlci4gT3IgaWYgdGhlcmUgaXMgbm8gY29udGVudC5cbiAgICAgICAgaWYgKCFtZnAuY29udGVudCB8fCAkKHRhcmdldCkuaGFzQ2xhc3MoJ21mcC1jbG9zZScpIHx8IChtZnAucHJlbG9hZGVyICYmIHRhcmdldCA9PT0gbWZwLnByZWxvYWRlclswXSkpIHtcbiAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfVxuXG4gICAgICAgIC8vIGlmIGNsaWNrIGlzIG91dHNpZGUgdGhlIGNvbnRlbnRcbiAgICAgICAgaWYgKCh0YXJnZXQgIT09IG1mcC5jb250ZW50WzBdICYmICEkLmNvbnRhaW5zKG1mcC5jb250ZW50WzBdLCB0YXJnZXQpKSkge1xuICAgICAgICAgIGlmIChjbG9zZU9uQmcpIHtcbiAgICAgICAgICAgIC8vIGxhc3QgY2hlY2ssIGlmIHRoZSBjbGlja2VkIGVsZW1lbnQgaXMgaW4gRE9NLCAoaW4gY2FzZSBpdCdzIHJlbW92ZWQgb25jbGljaylcbiAgICAgICAgICAgIGlmICgkLmNvbnRhaW5zKGRvY3VtZW50LCB0YXJnZXQpKSB7XG4gICAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIGlmIChjbG9zZU9uQ29udGVudCkge1xuICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9XG5cbiAgICAgIH1cbiAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9LFxuICAgIF9hZGRDbGFzc1RvTUZQOiBmdW5jdGlvbiAoY05hbWUpIHtcbiAgICAgIG1mcC5iZ092ZXJsYXkuYWRkQ2xhc3MoY05hbWUpO1xuICAgICAgbWZwLndyYXAuYWRkQ2xhc3MoY05hbWUpO1xuICAgIH0sXG4gICAgX3JlbW92ZUNsYXNzRnJvbU1GUDogZnVuY3Rpb24gKGNOYW1lKSB7XG4gICAgICB0aGlzLmJnT3ZlcmxheS5yZW1vdmVDbGFzcyhjTmFtZSk7XG4gICAgICBtZnAud3JhcC5yZW1vdmVDbGFzcyhjTmFtZSk7XG4gICAgfSxcbiAgICBfaGFzU2Nyb2xsQmFyOiBmdW5jdGlvbiAod2luSGVpZ2h0KSB7XG4gICAgICByZXR1cm4gKChtZnAuaXNJRTcgPyBfZG9jdW1lbnQuaGVpZ2h0KCkgOiBkb2N1bWVudC5ib2R5LnNjcm9sbEhlaWdodCkgPiAod2luSGVpZ2h0IHx8IF93aW5kb3cuaGVpZ2h0KCkpKTtcbiAgICB9LFxuICAgIF9zZXRGb2N1czogZnVuY3Rpb24gKCkge1xuICAgICAgKG1mcC5zdC5mb2N1cyA/IG1mcC5jb250ZW50LmZpbmQobWZwLnN0LmZvY3VzKS5lcSgwKSA6IG1mcC53cmFwKS5mb2N1cygpO1xuICAgIH0sXG4gICAgX29uRm9jdXNJbjogZnVuY3Rpb24gKGUpIHtcbiAgICAgIGlmIChlLnRhcmdldCAhPT0gbWZwLndyYXBbMF0gJiYgISQuY29udGFpbnMobWZwLndyYXBbMF0sIGUudGFyZ2V0KSkge1xuICAgICAgICBtZnAuX3NldEZvY3VzKCk7XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgIH1cbiAgICB9LFxuICAgIF9wYXJzZU1hcmt1cDogZnVuY3Rpb24gKHRlbXBsYXRlLCB2YWx1ZXMsIGl0ZW0pIHtcbiAgICAgIHZhciBhcnI7XG4gICAgICBpZiAoaXRlbS5kYXRhKSB7XG4gICAgICAgIHZhbHVlcyA9ICQuZXh0ZW5kKGl0ZW0uZGF0YSwgdmFsdWVzKTtcbiAgICAgIH1cbiAgICAgIF9tZnBUcmlnZ2VyKE1BUktVUF9QQVJTRV9FVkVOVCwgW3RlbXBsYXRlLCB2YWx1ZXMsIGl0ZW1dKTtcblxuICAgICAgJC5lYWNoKHZhbHVlcywgZnVuY3Rpb24gKGtleSwgdmFsdWUpIHtcbiAgICAgICAgaWYgKHZhbHVlID09PSB1bmRlZmluZWQgfHwgdmFsdWUgPT09IGZhbHNlKSB7XG4gICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cbiAgICAgICAgYXJyID0ga2V5LnNwbGl0KCdfJyk7XG4gICAgICAgIGlmIChhcnIubGVuZ3RoID4gMSkge1xuICAgICAgICAgIHZhciBlbCA9IHRlbXBsYXRlLmZpbmQoRVZFTlRfTlMgKyAnLScgKyBhcnJbMF0pO1xuXG4gICAgICAgICAgaWYgKGVsLmxlbmd0aCA+IDApIHtcbiAgICAgICAgICAgIHZhciBhdHRyID0gYXJyWzFdO1xuICAgICAgICAgICAgaWYgKGF0dHIgPT09ICdyZXBsYWNlV2l0aCcpIHtcbiAgICAgICAgICAgICAgaWYgKGVsWzBdICE9PSB2YWx1ZVswXSkge1xuICAgICAgICAgICAgICAgIGVsLnJlcGxhY2VXaXRoKHZhbHVlKTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSBlbHNlIGlmIChhdHRyID09PSAnaW1nJykge1xuICAgICAgICAgICAgICBpZiAoZWwuaXMoJ2ltZycpKSB7XG4gICAgICAgICAgICAgICAgZWwuYXR0cignc3JjJywgdmFsdWUpO1xuICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIGVsLnJlcGxhY2VXaXRoKCQoJzxpbWc+JykuYXR0cignc3JjJywgdmFsdWUpLmF0dHIoJ2NsYXNzJywgZWwuYXR0cignY2xhc3MnKSkpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICBlbC5hdHRyKGFyclsxXSwgdmFsdWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cblxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIHRlbXBsYXRlLmZpbmQoRVZFTlRfTlMgKyAnLScgKyBrZXkpLmh0bWwodmFsdWUpO1xuICAgICAgICB9XG4gICAgICB9KTtcbiAgICB9LFxuXG4gICAgX2dldFNjcm9sbGJhclNpemU6IGZ1bmN0aW9uICgpIHtcbiAgICAgIC8vIHRoeCBEYXZpZFxuICAgICAgaWYgKG1mcC5zY3JvbGxiYXJTaXplID09PSB1bmRlZmluZWQpIHtcbiAgICAgICAgdmFyIHNjcm9sbERpdiA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoXCJkaXZcIik7XG4gICAgICAgIHNjcm9sbERpdi5zdHlsZS5jc3NUZXh0ID0gJ3dpZHRoOiA5OXB4OyBoZWlnaHQ6IDk5cHg7IG92ZXJmbG93OiBzY3JvbGw7IHBvc2l0aW9uOiBhYnNvbHV0ZTsgdG9wOiAtOTk5OXB4Oyc7XG4gICAgICAgIGRvY3VtZW50LmJvZHkuYXBwZW5kQ2hpbGQoc2Nyb2xsRGl2KTtcbiAgICAgICAgbWZwLnNjcm9sbGJhclNpemUgPSBzY3JvbGxEaXYub2Zmc2V0V2lkdGggLSBzY3JvbGxEaXYuY2xpZW50V2lkdGg7XG4gICAgICAgIGRvY3VtZW50LmJvZHkucmVtb3ZlQ2hpbGQoc2Nyb2xsRGl2KTtcbiAgICAgIH1cbiAgICAgIHJldHVybiBtZnAuc2Nyb2xsYmFyU2l6ZTtcbiAgICB9XG5cbiAgfTsgLyogTWFnbmlmaWNQb3B1cCBjb3JlIHByb3RvdHlwZSBlbmQgKi9cblxuXG4gIC8qKlxuICAgKiBQdWJsaWMgc3RhdGljIGZ1bmN0aW9uc1xuICAgKi9cbiAgJC5tYWduaWZpY1BvcHVwID0ge1xuICAgIGluc3RhbmNlOiBudWxsLFxuICAgIHByb3RvOiBNYWduaWZpY1BvcHVwLnByb3RvdHlwZSxcbiAgICBtb2R1bGVzOiBbXSxcblxuICAgIG9wZW46IGZ1bmN0aW9uIChvcHRpb25zLCBpbmRleCkge1xuICAgICAgX2NoZWNrSW5zdGFuY2UoKTtcblxuICAgICAgaWYgKCFvcHRpb25zKSB7XG4gICAgICAgIG9wdGlvbnMgPSB7fTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIG9wdGlvbnMgPSAkLmV4dGVuZCh0cnVlLCB7fSwgb3B0aW9ucyk7XG4gICAgICB9XG5cbiAgICAgIG9wdGlvbnMuaXNPYmogPSB0cnVlO1xuICAgICAgb3B0aW9ucy5pbmRleCA9IGluZGV4IHx8IDA7XG4gICAgICByZXR1cm4gdGhpcy5pbnN0YW5jZS5vcGVuKG9wdGlvbnMpO1xuICAgIH0sXG5cbiAgICBjbG9zZTogZnVuY3Rpb24gKCkge1xuICAgICAgcmV0dXJuICQubWFnbmlmaWNQb3B1cC5pbnN0YW5jZSAmJiAkLm1hZ25pZmljUG9wdXAuaW5zdGFuY2UuY2xvc2UoKTtcbiAgICB9LFxuXG4gICAgcmVnaXN0ZXJNb2R1bGU6IGZ1bmN0aW9uIChuYW1lLCBtb2R1bGUpIHtcbiAgICAgIGlmIChtb2R1bGUub3B0aW9ucykge1xuICAgICAgICAkLm1hZ25pZmljUG9wdXAuZGVmYXVsdHNbbmFtZV0gPSBtb2R1bGUub3B0aW9ucztcbiAgICAgIH1cbiAgICAgICQuZXh0ZW5kKHRoaXMucHJvdG8sIG1vZHVsZS5wcm90byk7XG4gICAgICB0aGlzLm1vZHVsZXMucHVzaChuYW1lKTtcbiAgICB9LFxuXG4gICAgZGVmYXVsdHM6IHtcblxuICAgICAgLy8gSW5mbyBhYm91dCBvcHRpb25zIGlzIGluIGRvY3M6XG4gICAgICAvLyBodHRwOi8vZGltc2VtZW5vdi5jb20vcGx1Z2lucy9tYWduaWZpYy1wb3B1cC9kb2N1bWVudGF0aW9uLmh0bWwjb3B0aW9uc1xuXG4gICAgICBkaXNhYmxlT246IDAsXG5cbiAgICAgIGtleTogbnVsbCxcblxuICAgICAgbWlkQ2xpY2s6IGZhbHNlLFxuXG4gICAgICBtYWluQ2xhc3M6ICcnLFxuXG4gICAgICBwcmVsb2FkZXI6IHRydWUsXG5cbiAgICAgIGZvY3VzOiAnJywgLy8gQ1NTIHNlbGVjdG9yIG9mIGlucHV0IHRvIGZvY3VzIGFmdGVyIHBvcHVwIGlzIG9wZW5lZFxuXG4gICAgICBjbG9zZU9uQ29udGVudENsaWNrOiBmYWxzZSxcblxuICAgICAgY2xvc2VPbkJnQ2xpY2s6IHRydWUsXG5cbiAgICAgIGNsb3NlQnRuSW5zaWRlOiB0cnVlLFxuXG4gICAgICBzaG93Q2xvc2VCdG46IHRydWUsXG5cbiAgICAgIGVuYWJsZUVzY2FwZUtleTogdHJ1ZSxcblxuICAgICAgbW9kYWw6IGZhbHNlLFxuXG4gICAgICBhbGlnblRvcDogZmFsc2UsXG5cbiAgICAgIHJlbW92YWxEZWxheTogMCxcblxuICAgICAgcHJlcGVuZFRvOiBudWxsLFxuXG4gICAgICBmaXhlZENvbnRlbnRQb3M6ICdhdXRvJyxcblxuICAgICAgZml4ZWRCZ1BvczogJ2F1dG8nLFxuXG4gICAgICBvdmVyZmxvd1k6ICdhdXRvJyxcblxuICAgICAgY2xvc2VNYXJrdXA6ICc8YnV0dG9uIHRpdGxlPVwiJXRpdGxlJVwiIHR5cGU9XCJidXR0b25cIiBjbGFzcz1cIm1mcC1jbG9zZVwiPiYjMjE1OzwvYnV0dG9uPicsXG5cbiAgICAgIHRDbG9zZTogJ0Nsb3NlIChFc2MpJyxcblxuICAgICAgdExvYWRpbmc6ICdMb2FkaW5nLi4uJyxcblxuICAgICAgYXV0b0ZvY3VzTGFzdDogdHJ1ZVxuXG4gICAgfVxuICB9O1xuXG5cbiAgJC5mbi5tYWduaWZpY1BvcHVwID0gZnVuY3Rpb24gKG9wdGlvbnMpIHtcbiAgICBfY2hlY2tJbnN0YW5jZSgpO1xuXG4gICAgdmFyIGpxRWwgPSAkKHRoaXMpO1xuXG4gICAgLy8gV2UgY2FsbCBzb21lIEFQSSBtZXRob2Qgb2YgZmlyc3QgcGFyYW0gaXMgYSBzdHJpbmdcbiAgICBpZiAodHlwZW9mIG9wdGlvbnMgPT09IFwic3RyaW5nXCIpIHtcblxuICAgICAgaWYgKG9wdGlvbnMgPT09ICdvcGVuJykge1xuICAgICAgICB2YXIgaXRlbXMsXG4gICAgICAgICAgaXRlbU9wdHMgPSBfaXNKUSA/IGpxRWwuZGF0YSgnbWFnbmlmaWNQb3B1cCcpIDoganFFbFswXS5tYWduaWZpY1BvcHVwLFxuICAgICAgICAgIGluZGV4ID0gcGFyc2VJbnQoYXJndW1lbnRzWzFdLCAxMCkgfHwgMDtcblxuICAgICAgICBpZiAoaXRlbU9wdHMuaXRlbXMpIHtcbiAgICAgICAgICBpdGVtcyA9IGl0ZW1PcHRzLml0ZW1zW2luZGV4XTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICBpdGVtcyA9IGpxRWw7XG4gICAgICAgICAgaWYgKGl0ZW1PcHRzLmRlbGVnYXRlKSB7XG4gICAgICAgICAgICBpdGVtcyA9IGl0ZW1zLmZpbmQoaXRlbU9wdHMuZGVsZWdhdGUpO1xuICAgICAgICAgIH1cbiAgICAgICAgICBpdGVtcyA9IGl0ZW1zLmVxKGluZGV4KTtcbiAgICAgICAgfVxuICAgICAgICBtZnAuX29wZW5DbGljayh7bWZwRWw6IGl0ZW1zfSwganFFbCwgaXRlbU9wdHMpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgaWYgKG1mcC5pc09wZW4pXG4gICAgICAgICAgbWZwW29wdGlvbnNdLmFwcGx5KG1mcCwgQXJyYXkucHJvdG90eXBlLnNsaWNlLmNhbGwoYXJndW1lbnRzLCAxKSk7XG4gICAgICB9XG5cbiAgICB9IGVsc2Uge1xuICAgICAgLy8gY2xvbmUgb3B0aW9ucyBvYmpcbiAgICAgIG9wdGlvbnMgPSAkLmV4dGVuZCh0cnVlLCB7fSwgb3B0aW9ucyk7XG5cbiAgICAgIC8qXG4gICAgICAgKiBBcyBaZXB0byBkb2Vzbid0IHN1cHBvcnQgLmRhdGEoKSBtZXRob2QgZm9yIG9iamVjdHNcbiAgICAgICAqIGFuZCBpdCB3b3JrcyBvbmx5IGluIG5vcm1hbCBicm93c2Vyc1xuICAgICAgICogd2UgYXNzaWduIFwib3B0aW9uc1wiIG9iamVjdCBkaXJlY3RseSB0byB0aGUgRE9NIGVsZW1lbnQuIEZUVyFcbiAgICAgICAqL1xuICAgICAgaWYgKF9pc0pRKSB7XG4gICAgICAgIGpxRWwuZGF0YSgnbWFnbmlmaWNQb3B1cCcsIG9wdGlvbnMpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAganFFbFswXS5tYWduaWZpY1BvcHVwID0gb3B0aW9ucztcbiAgICAgIH1cblxuICAgICAgbWZwLmFkZEdyb3VwKGpxRWwsIG9wdGlvbnMpO1xuXG4gICAgfVxuICAgIHJldHVybiBqcUVsO1xuICB9O1xuXG4gIC8qPj5jb3JlKi9cblxuICAvKj4+aW5saW5lKi9cblxuICB2YXIgSU5MSU5FX05TID0gJ2lubGluZScsXG4gICAgX2hpZGRlbkNsYXNzLFxuICAgIF9pbmxpbmVQbGFjZWhvbGRlcixcbiAgICBfbGFzdElubGluZUVsZW1lbnQsXG4gICAgX3B1dElubGluZUVsZW1lbnRzQmFjayA9IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmIChfbGFzdElubGluZUVsZW1lbnQpIHtcbiAgICAgICAgX2lubGluZVBsYWNlaG9sZGVyLmFmdGVyKF9sYXN0SW5saW5lRWxlbWVudC5hZGRDbGFzcyhfaGlkZGVuQ2xhc3MpKS5kZXRhY2goKTtcbiAgICAgICAgX2xhc3RJbmxpbmVFbGVtZW50ID0gbnVsbDtcbiAgICAgIH1cbiAgICB9O1xuXG4gICQubWFnbmlmaWNQb3B1cC5yZWdpc3Rlck1vZHVsZShJTkxJTkVfTlMsIHtcbiAgICBvcHRpb25zOiB7XG4gICAgICBoaWRkZW5DbGFzczogJ2hpZGUnLCAvLyB3aWxsIGJlIGFwcGVuZGVkIHdpdGggYG1mcC1gIHByZWZpeFxuICAgICAgbWFya3VwOiAnJyxcbiAgICAgIHROb3RGb3VuZDogJ0NvbnRlbnQgbm90IGZvdW5kJ1xuICAgIH0sXG4gICAgcHJvdG86IHtcblxuICAgICAgaW5pdElubGluZTogZnVuY3Rpb24gKCkge1xuICAgICAgICBtZnAudHlwZXMucHVzaChJTkxJTkVfTlMpO1xuXG4gICAgICAgIF9tZnBPbihDTE9TRV9FVkVOVCArICcuJyArIElOTElORV9OUywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIF9wdXRJbmxpbmVFbGVtZW50c0JhY2soKTtcbiAgICAgICAgfSk7XG4gICAgICB9LFxuXG4gICAgICBnZXRJbmxpbmU6IGZ1bmN0aW9uIChpdGVtLCB0ZW1wbGF0ZSkge1xuXG4gICAgICAgIF9wdXRJbmxpbmVFbGVtZW50c0JhY2soKTtcblxuICAgICAgICBpZiAoaXRlbS5zcmMpIHtcbiAgICAgICAgICB2YXIgaW5saW5lU3QgPSBtZnAuc3QuaW5saW5lLFxuICAgICAgICAgICAgZWwgPSAkKGl0ZW0uc3JjKTtcblxuICAgICAgICAgIGlmIChlbC5sZW5ndGgpIHtcblxuICAgICAgICAgICAgLy8gSWYgdGFyZ2V0IGVsZW1lbnQgaGFzIHBhcmVudCAtIHdlIHJlcGxhY2UgaXQgd2l0aCBwbGFjZWhvbGRlciBhbmQgcHV0IGl0IGJhY2sgYWZ0ZXIgcG9wdXAgaXMgY2xvc2VkXG4gICAgICAgICAgICB2YXIgcGFyZW50ID0gZWxbMF0ucGFyZW50Tm9kZTtcbiAgICAgICAgICAgIGlmIChwYXJlbnQgJiYgcGFyZW50LnRhZ05hbWUpIHtcbiAgICAgICAgICAgICAgaWYgKCFfaW5saW5lUGxhY2Vob2xkZXIpIHtcbiAgICAgICAgICAgICAgICBfaGlkZGVuQ2xhc3MgPSBpbmxpbmVTdC5oaWRkZW5DbGFzcztcbiAgICAgICAgICAgICAgICBfaW5saW5lUGxhY2Vob2xkZXIgPSBfZ2V0RWwoX2hpZGRlbkNsYXNzKTtcbiAgICAgICAgICAgICAgICBfaGlkZGVuQ2xhc3MgPSAnbWZwLScgKyBfaGlkZGVuQ2xhc3M7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgLy8gcmVwbGFjZSB0YXJnZXQgaW5saW5lIGVsZW1lbnQgd2l0aCBwbGFjZWhvbGRlclxuICAgICAgICAgICAgICBfbGFzdElubGluZUVsZW1lbnQgPSBlbC5hZnRlcihfaW5saW5lUGxhY2Vob2xkZXIpLmRldGFjaCgpLnJlbW92ZUNsYXNzKF9oaWRkZW5DbGFzcyk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIG1mcC51cGRhdGVTdGF0dXMoJ3JlYWR5Jyk7XG4gICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIG1mcC51cGRhdGVTdGF0dXMoJ2Vycm9yJywgaW5saW5lU3QudE5vdEZvdW5kKTtcbiAgICAgICAgICAgIGVsID0gJCgnPGRpdj4nKTtcbiAgICAgICAgICB9XG5cbiAgICAgICAgICBpdGVtLmlubGluZUVsZW1lbnQgPSBlbDtcbiAgICAgICAgICByZXR1cm4gZWw7XG4gICAgICAgIH1cblxuICAgICAgICBtZnAudXBkYXRlU3RhdHVzKCdyZWFkeScpO1xuICAgICAgICBtZnAuX3BhcnNlTWFya3VwKHRlbXBsYXRlLCB7fSwgaXRlbSk7XG4gICAgICAgIHJldHVybiB0ZW1wbGF0ZTtcbiAgICAgIH1cbiAgICB9XG4gIH0pO1xuXG4gIC8qPj5pbmxpbmUqL1xuXG4gIC8qPj5hamF4Ki9cbiAgdmFyIEFKQVhfTlMgPSAnYWpheCcsXG4gICAgX2FqYXhDdXIsXG4gICAgX3JlbW92ZUFqYXhDdXJzb3IgPSBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAoX2FqYXhDdXIpIHtcbiAgICAgICAgJChkb2N1bWVudC5ib2R5KS5yZW1vdmVDbGFzcyhfYWpheEN1cik7XG4gICAgICB9XG4gICAgfSxcbiAgICBfZGVzdHJveUFqYXhSZXF1ZXN0ID0gZnVuY3Rpb24gKCkge1xuICAgICAgX3JlbW92ZUFqYXhDdXJzb3IoKTtcbiAgICAgIGlmIChtZnAucmVxKSB7XG4gICAgICAgIG1mcC5yZXEuYWJvcnQoKTtcbiAgICAgIH1cbiAgICB9O1xuXG4gICQubWFnbmlmaWNQb3B1cC5yZWdpc3Rlck1vZHVsZShBSkFYX05TLCB7XG5cbiAgICBvcHRpb25zOiB7XG4gICAgICBzZXR0aW5nczogbnVsbCxcbiAgICAgIGN1cnNvcjogJ21mcC1hamF4LWN1cicsXG4gICAgICB0RXJyb3I6ICc8YSBocmVmPVwiJXVybCVcIj5UaGUgY29udGVudDwvYT4gY291bGQgbm90IGJlIGxvYWRlZC4nXG4gICAgfSxcblxuICAgIHByb3RvOiB7XG4gICAgICBpbml0QWpheDogZnVuY3Rpb24gKCkge1xuICAgICAgICBtZnAudHlwZXMucHVzaChBSkFYX05TKTtcbiAgICAgICAgX2FqYXhDdXIgPSBtZnAuc3QuYWpheC5jdXJzb3I7XG5cbiAgICAgICAgX21mcE9uKENMT1NFX0VWRU5UICsgJy4nICsgQUpBWF9OUywgX2Rlc3Ryb3lBamF4UmVxdWVzdCk7XG4gICAgICAgIF9tZnBPbignQmVmb3JlQ2hhbmdlLicgKyBBSkFYX05TLCBfZGVzdHJveUFqYXhSZXF1ZXN0KTtcbiAgICAgIH0sXG4gICAgICBnZXRBamF4OiBmdW5jdGlvbiAoaXRlbSkge1xuXG4gICAgICAgIGlmIChfYWpheEN1cikge1xuICAgICAgICAgICQoZG9jdW1lbnQuYm9keSkuYWRkQ2xhc3MoX2FqYXhDdXIpO1xuICAgICAgICB9XG5cbiAgICAgICAgbWZwLnVwZGF0ZVN0YXR1cygnbG9hZGluZycpO1xuXG4gICAgICAgIHZhciBvcHRzID0gJC5leHRlbmQoe1xuICAgICAgICAgIHVybDogaXRlbS5zcmMsXG4gICAgICAgICAgc3VjY2VzczogZnVuY3Rpb24gKGRhdGEsIHRleHRTdGF0dXMsIGpxWEhSKSB7XG4gICAgICAgICAgICB2YXIgdGVtcCA9IHtcbiAgICAgICAgICAgICAgZGF0YTogZGF0YSxcbiAgICAgICAgICAgICAgeGhyOiBqcVhIUlxuICAgICAgICAgICAgfTtcblxuICAgICAgICAgICAgX21mcFRyaWdnZXIoJ1BhcnNlQWpheCcsIHRlbXApO1xuXG4gICAgICAgICAgICBtZnAuYXBwZW5kQ29udGVudCgkKHRlbXAuZGF0YSksIEFKQVhfTlMpO1xuXG4gICAgICAgICAgICBpdGVtLmZpbmlzaGVkID0gdHJ1ZTtcblxuICAgICAgICAgICAgX3JlbW92ZUFqYXhDdXJzb3IoKTtcblxuICAgICAgICAgICAgbWZwLl9zZXRGb2N1cygpO1xuXG4gICAgICAgICAgICBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgbWZwLndyYXAuYWRkQ2xhc3MoUkVBRFlfQ0xBU1MpO1xuICAgICAgICAgICAgfSwgMTYpO1xuXG4gICAgICAgICAgICBtZnAudXBkYXRlU3RhdHVzKCdyZWFkeScpO1xuXG4gICAgICAgICAgICBfbWZwVHJpZ2dlcignQWpheENvbnRlbnRBZGRlZCcpO1xuICAgICAgICAgIH0sXG4gICAgICAgICAgZXJyb3I6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIF9yZW1vdmVBamF4Q3Vyc29yKCk7XG4gICAgICAgICAgICBpdGVtLmZpbmlzaGVkID0gaXRlbS5sb2FkRXJyb3IgPSB0cnVlO1xuICAgICAgICAgICAgbWZwLnVwZGF0ZVN0YXR1cygnZXJyb3InLCBtZnAuc3QuYWpheC50RXJyb3IucmVwbGFjZSgnJXVybCUnLCBpdGVtLnNyYykpO1xuICAgICAgICAgIH1cbiAgICAgICAgfSwgbWZwLnN0LmFqYXguc2V0dGluZ3MpO1xuXG4gICAgICAgIG1mcC5yZXEgPSAkLmFqYXgob3B0cyk7XG5cbiAgICAgICAgcmV0dXJuICcnO1xuICAgICAgfVxuICAgIH1cbiAgfSk7XG5cbiAgLyo+PmFqYXgqL1xuXG4gIC8qPj5pbWFnZSovXG4gIHZhciBfaW1nSW50ZXJ2YWwsXG4gICAgX2dldFRpdGxlID0gZnVuY3Rpb24gKGl0ZW0pIHtcbiAgICAgIGlmIChpdGVtLmRhdGEgJiYgaXRlbS5kYXRhLnRpdGxlICE9PSB1bmRlZmluZWQpXG4gICAgICAgIHJldHVybiBpdGVtLmRhdGEudGl0bGU7XG5cbiAgICAgIHZhciBzcmMgPSBtZnAuc3QuaW1hZ2UudGl0bGVTcmM7XG5cbiAgICAgIGlmIChzcmMpIHtcbiAgICAgICAgaWYgKCQuaXNGdW5jdGlvbihzcmMpKSB7XG4gICAgICAgICAgcmV0dXJuIHNyYy5jYWxsKG1mcCwgaXRlbSk7XG4gICAgICAgIH0gZWxzZSBpZiAoaXRlbS5lbCkge1xuICAgICAgICAgIHJldHVybiBpdGVtLmVsLmF0dHIoc3JjKSB8fCAnJztcbiAgICAgICAgfVxuICAgICAgfVxuICAgICAgcmV0dXJuICcnO1xuICAgIH07XG5cbiAgJC5tYWduaWZpY1BvcHVwLnJlZ2lzdGVyTW9kdWxlKCdpbWFnZScsIHtcblxuICAgIG9wdGlvbnM6IHtcbiAgICAgIG1hcmt1cDogJzxkaXYgY2xhc3M9XCJtZnAtZmlndXJlXCI+JyArXG4gICAgICAgICc8ZGl2IGNsYXNzPVwibWZwLWNsb3NlXCI+PC9kaXY+JyArXG4gICAgICAgICc8ZmlndXJlPicgK1xuICAgICAgICAnPGRpdiBjbGFzcz1cIm1mcC1pbWdcIj48L2Rpdj4nICtcbiAgICAgICAgJzxmaWdjYXB0aW9uPicgK1xuICAgICAgICAnPGRpdiBjbGFzcz1cIm1mcC1ib3R0b20tYmFyXCI+JyArXG4gICAgICAgICc8ZGl2IGNsYXNzPVwibWZwLXRpdGxlXCI+PC9kaXY+JyArXG4gICAgICAgICc8ZGl2IGNsYXNzPVwibWZwLWNvdW50ZXJcIj48L2Rpdj4nICtcbiAgICAgICAgJzwvZGl2PicgK1xuICAgICAgICAnPC9maWdjYXB0aW9uPicgK1xuICAgICAgICAnPC9maWd1cmU+JyArXG4gICAgICAgICc8L2Rpdj4nLFxuICAgICAgY3Vyc29yOiAnbWZwLXpvb20tb3V0LWN1cicsXG4gICAgICB0aXRsZVNyYzogJ3RpdGxlJyxcbiAgICAgIHZlcnRpY2FsRml0OiB0cnVlLFxuICAgICAgdEVycm9yOiAnPGEgaHJlZj1cIiV1cmwlXCI+VGhlIGltYWdlPC9hPiBjb3VsZCBub3QgYmUgbG9hZGVkLidcbiAgICB9LFxuXG4gICAgcHJvdG86IHtcbiAgICAgIGluaXRJbWFnZTogZnVuY3Rpb24gKCkge1xuICAgICAgICB2YXIgaW1nU3QgPSBtZnAuc3QuaW1hZ2UsXG4gICAgICAgICAgbnMgPSAnLmltYWdlJztcblxuICAgICAgICBtZnAudHlwZXMucHVzaCgnaW1hZ2UnKTtcblxuICAgICAgICBfbWZwT24oT1BFTl9FVkVOVCArIG5zLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgaWYgKG1mcC5jdXJySXRlbS50eXBlID09PSAnaW1hZ2UnICYmIGltZ1N0LmN1cnNvcikge1xuICAgICAgICAgICAgJChkb2N1bWVudC5ib2R5KS5hZGRDbGFzcyhpbWdTdC5jdXJzb3IpO1xuICAgICAgICAgIH1cbiAgICAgICAgfSk7XG5cbiAgICAgICAgX21mcE9uKENMT1NFX0VWRU5UICsgbnMsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICBpZiAoaW1nU3QuY3Vyc29yKSB7XG4gICAgICAgICAgICAkKGRvY3VtZW50LmJvZHkpLnJlbW92ZUNsYXNzKGltZ1N0LmN1cnNvcik7XG4gICAgICAgICAgfVxuICAgICAgICAgIF93aW5kb3cub2ZmKCdyZXNpemUnICsgRVZFTlRfTlMpO1xuICAgICAgICB9KTtcblxuICAgICAgICBfbWZwT24oJ1Jlc2l6ZScgKyBucywgbWZwLnJlc2l6ZUltYWdlKTtcbiAgICAgICAgaWYgKG1mcC5pc0xvd0lFKSB7XG4gICAgICAgICAgX21mcE9uKCdBZnRlckNoYW5nZScsIG1mcC5yZXNpemVJbWFnZSk7XG4gICAgICAgIH1cbiAgICAgIH0sXG4gICAgICByZXNpemVJbWFnZTogZnVuY3Rpb24gKCkge1xuICAgICAgICB2YXIgaXRlbSA9IG1mcC5jdXJySXRlbTtcbiAgICAgICAgaWYgKCFpdGVtIHx8ICFpdGVtLmltZykgcmV0dXJuO1xuXG4gICAgICAgIGlmIChtZnAuc3QuaW1hZ2UudmVydGljYWxGaXQpIHtcbiAgICAgICAgICB2YXIgZGVjciA9IDA7XG4gICAgICAgICAgLy8gZml4IGJveC1zaXppbmcgaW4gaWU3LzhcbiAgICAgICAgICBpZiAobWZwLmlzTG93SUUpIHtcbiAgICAgICAgICAgIGRlY3IgPSBwYXJzZUludChpdGVtLmltZy5jc3MoJ3BhZGRpbmctdG9wJyksIDEwKSArIHBhcnNlSW50KGl0ZW0uaW1nLmNzcygncGFkZGluZy1ib3R0b20nKSwgMTApO1xuICAgICAgICAgIH1cbiAgICAgICAgICBpdGVtLmltZy5jc3MoJ21heC1oZWlnaHQnLCBtZnAud0ggLSBkZWNyKTtcbiAgICAgICAgfVxuICAgICAgfSxcbiAgICAgIF9vbkltYWdlSGFzU2l6ZTogZnVuY3Rpb24gKGl0ZW0pIHtcbiAgICAgICAgaWYgKGl0ZW0uaW1nKSB7XG5cbiAgICAgICAgICBpdGVtLmhhc1NpemUgPSB0cnVlO1xuXG4gICAgICAgICAgaWYgKF9pbWdJbnRlcnZhbCkge1xuICAgICAgICAgICAgY2xlYXJJbnRlcnZhbChfaW1nSW50ZXJ2YWwpO1xuICAgICAgICAgIH1cblxuICAgICAgICAgIGl0ZW0uaXNDaGVja2luZ0ltZ1NpemUgPSBmYWxzZTtcblxuICAgICAgICAgIF9tZnBUcmlnZ2VyKCdJbWFnZUhhc1NpemUnLCBpdGVtKTtcblxuICAgICAgICAgIGlmIChpdGVtLmltZ0hpZGRlbikge1xuICAgICAgICAgICAgaWYgKG1mcC5jb250ZW50KVxuICAgICAgICAgICAgICBtZnAuY29udGVudC5yZW1vdmVDbGFzcygnbWZwLWxvYWRpbmcnKTtcblxuICAgICAgICAgICAgaXRlbS5pbWdIaWRkZW4gPSBmYWxzZTtcbiAgICAgICAgICB9XG5cbiAgICAgICAgfVxuICAgICAgfSxcblxuICAgICAgLyoqXG4gICAgICAgKiBGdW5jdGlvbiB0aGF0IGxvb3BzIHVudGlsIHRoZSBpbWFnZSBoYXMgc2l6ZSB0byBkaXNwbGF5IGVsZW1lbnRzIHRoYXQgcmVseSBvbiBpdCBhc2FwXG4gICAgICAgKi9cbiAgICAgIGZpbmRJbWFnZVNpemU6IGZ1bmN0aW9uIChpdGVtKSB7XG5cbiAgICAgICAgdmFyIGNvdW50ZXIgPSAwLFxuICAgICAgICAgIGltZyA9IGl0ZW0uaW1nWzBdLFxuICAgICAgICAgIG1mcFNldEludGVydmFsID0gZnVuY3Rpb24gKGRlbGF5KSB7XG5cbiAgICAgICAgICAgIGlmIChfaW1nSW50ZXJ2YWwpIHtcbiAgICAgICAgICAgICAgY2xlYXJJbnRlcnZhbChfaW1nSW50ZXJ2YWwpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgLy8gZGVjZWxlcmF0aW5nIGludGVydmFsIHRoYXQgY2hlY2tzIGZvciBzaXplIG9mIGFuIGltYWdlXG4gICAgICAgICAgICBfaW1nSW50ZXJ2YWwgPSBzZXRJbnRlcnZhbChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAgIGlmIChpbWcubmF0dXJhbFdpZHRoID4gMCkge1xuICAgICAgICAgICAgICAgIG1mcC5fb25JbWFnZUhhc1NpemUoaXRlbSk7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgaWYgKGNvdW50ZXIgPiAyMDApIHtcbiAgICAgICAgICAgICAgICBjbGVhckludGVydmFsKF9pbWdJbnRlcnZhbCk7XG4gICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICBjb3VudGVyKys7XG4gICAgICAgICAgICAgIGlmIChjb3VudGVyID09PSAzKSB7XG4gICAgICAgICAgICAgICAgbWZwU2V0SW50ZXJ2YWwoMTApO1xuICAgICAgICAgICAgICB9IGVsc2UgaWYgKGNvdW50ZXIgPT09IDQwKSB7XG4gICAgICAgICAgICAgICAgbWZwU2V0SW50ZXJ2YWwoNTApO1xuICAgICAgICAgICAgICB9IGVsc2UgaWYgKGNvdW50ZXIgPT09IDEwMCkge1xuICAgICAgICAgICAgICAgIG1mcFNldEludGVydmFsKDUwMCk7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0sIGRlbGF5KTtcbiAgICAgICAgICB9O1xuXG4gICAgICAgIG1mcFNldEludGVydmFsKDEpO1xuICAgICAgfSxcblxuICAgICAgZ2V0SW1hZ2U6IGZ1bmN0aW9uIChpdGVtLCB0ZW1wbGF0ZSkge1xuXG4gICAgICAgIHZhciBndWFyZCA9IDAsXG5cbiAgICAgICAgICAvLyBpbWFnZSBsb2FkIGNvbXBsZXRlIGhhbmRsZXJcbiAgICAgICAgICBvbkxvYWRDb21wbGV0ZSA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIGlmIChpdGVtKSB7XG4gICAgICAgICAgICAgIGlmIChpdGVtLmltZ1swXS5jb21wbGV0ZSkge1xuICAgICAgICAgICAgICAgIGl0ZW0uaW1nLm9mZignLm1mcGxvYWRlcicpO1xuXG4gICAgICAgICAgICAgICAgaWYgKGl0ZW0gPT09IG1mcC5jdXJySXRlbSkge1xuICAgICAgICAgICAgICAgICAgbWZwLl9vbkltYWdlSGFzU2l6ZShpdGVtKTtcblxuICAgICAgICAgICAgICAgICAgbWZwLnVwZGF0ZVN0YXR1cygncmVhZHknKTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBpdGVtLmhhc1NpemUgPSB0cnVlO1xuICAgICAgICAgICAgICAgIGl0ZW0ubG9hZGVkID0gdHJ1ZTtcblxuICAgICAgICAgICAgICAgIF9tZnBUcmlnZ2VyKCdJbWFnZUxvYWRDb21wbGV0ZScpO1xuXG4gICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgLy8gaWYgaW1hZ2UgY29tcGxldGUgY2hlY2sgZmFpbHMgMjAwIHRpbWVzICgyMCBzZWMpLCB3ZSBhc3N1bWUgdGhhdCB0aGVyZSB3YXMgYW4gZXJyb3IuXG4gICAgICAgICAgICAgICAgZ3VhcmQrKztcbiAgICAgICAgICAgICAgICBpZiAoZ3VhcmQgPCAyMDApIHtcbiAgICAgICAgICAgICAgICAgIHNldFRpbWVvdXQob25Mb2FkQ29tcGxldGUsIDEwMCk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgIG9uTG9hZEVycm9yKCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSxcblxuICAgICAgICAgIC8vIGltYWdlIGVycm9yIGhhbmRsZXJcbiAgICAgICAgICBvbkxvYWRFcnJvciA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIGlmIChpdGVtKSB7XG4gICAgICAgICAgICAgIGl0ZW0uaW1nLm9mZignLm1mcGxvYWRlcicpO1xuICAgICAgICAgICAgICBpZiAoaXRlbSA9PT0gbWZwLmN1cnJJdGVtKSB7XG4gICAgICAgICAgICAgICAgbWZwLl9vbkltYWdlSGFzU2l6ZShpdGVtKTtcbiAgICAgICAgICAgICAgICBtZnAudXBkYXRlU3RhdHVzKCdlcnJvcicsIGltZ1N0LnRFcnJvci5yZXBsYWNlKCcldXJsJScsIGl0ZW0uc3JjKSk7XG4gICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICBpdGVtLmhhc1NpemUgPSB0cnVlO1xuICAgICAgICAgICAgICBpdGVtLmxvYWRlZCA9IHRydWU7XG4gICAgICAgICAgICAgIGl0ZW0ubG9hZEVycm9yID0gdHJ1ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9LFxuICAgICAgICAgIGltZ1N0ID0gbWZwLnN0LmltYWdlO1xuXG5cbiAgICAgICAgdmFyIGVsID0gdGVtcGxhdGUuZmluZCgnLm1mcC1pbWcnKTtcbiAgICAgICAgaWYgKGVsLmxlbmd0aCkge1xuICAgICAgICAgIHZhciBpbWcgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdpbWcnKTtcbiAgICAgICAgICBpbWcuY2xhc3NOYW1lID0gJ21mcC1pbWcnO1xuICAgICAgICAgIGlmIChpdGVtLmVsICYmIGl0ZW0uZWwuZmluZCgnaW1nJykubGVuZ3RoKSB7XG4gICAgICAgICAgICBpbWcuYWx0ID0gaXRlbS5lbC5maW5kKCdpbWcnKS5hdHRyKCdhbHQnKTtcbiAgICAgICAgICB9XG4gICAgICAgICAgaXRlbS5pbWcgPSAkKGltZykub24oJ2xvYWQubWZwbG9hZGVyJywgb25Mb2FkQ29tcGxldGUpLm9uKCdlcnJvci5tZnBsb2FkZXInLCBvbkxvYWRFcnJvcik7XG4gICAgICAgICAgaW1nLnNyYyA9IGl0ZW0uc3JjO1xuXG4gICAgICAgICAgLy8gd2l0aG91dCBjbG9uZSgpIFwiZXJyb3JcIiBldmVudCBpcyBub3QgZmlyaW5nIHdoZW4gSU1HIGlzIHJlcGxhY2VkIGJ5IG5ldyBJTUdcbiAgICAgICAgICAvLyBUT0RPOiBmaW5kIGEgd2F5IHRvIGF2b2lkIHN1Y2ggY2xvbmluZ1xuICAgICAgICAgIGlmIChlbC5pcygnaW1nJykpIHtcbiAgICAgICAgICAgIGl0ZW0uaW1nID0gaXRlbS5pbWcuY2xvbmUoKTtcbiAgICAgICAgICB9XG5cbiAgICAgICAgICBpbWcgPSBpdGVtLmltZ1swXTtcbiAgICAgICAgICBpZiAoaW1nLm5hdHVyYWxXaWR0aCA+IDApIHtcbiAgICAgICAgICAgIGl0ZW0uaGFzU2l6ZSA9IHRydWU7XG4gICAgICAgICAgfSBlbHNlIGlmICghaW1nLndpZHRoKSB7XG4gICAgICAgICAgICBpdGVtLmhhc1NpemUgPSBmYWxzZTtcbiAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICBtZnAuX3BhcnNlTWFya3VwKHRlbXBsYXRlLCB7XG4gICAgICAgICAgdGl0bGU6IF9nZXRUaXRsZShpdGVtKSxcbiAgICAgICAgICBpbWdfcmVwbGFjZVdpdGg6IGl0ZW0uaW1nXG4gICAgICAgIH0sIGl0ZW0pO1xuXG4gICAgICAgIG1mcC5yZXNpemVJbWFnZSgpO1xuXG4gICAgICAgIGlmIChpdGVtLmhhc1NpemUpIHtcbiAgICAgICAgICBpZiAoX2ltZ0ludGVydmFsKSBjbGVhckludGVydmFsKF9pbWdJbnRlcnZhbCk7XG5cbiAgICAgICAgICBpZiAoaXRlbS5sb2FkRXJyb3IpIHtcbiAgICAgICAgICAgIHRlbXBsYXRlLmFkZENsYXNzKCdtZnAtbG9hZGluZycpO1xuICAgICAgICAgICAgbWZwLnVwZGF0ZVN0YXR1cygnZXJyb3InLCBpbWdTdC50RXJyb3IucmVwbGFjZSgnJXVybCUnLCBpdGVtLnNyYykpO1xuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICB0ZW1wbGF0ZS5yZW1vdmVDbGFzcygnbWZwLWxvYWRpbmcnKTtcbiAgICAgICAgICAgIG1mcC51cGRhdGVTdGF0dXMoJ3JlYWR5Jyk7XG4gICAgICAgICAgfVxuICAgICAgICAgIHJldHVybiB0ZW1wbGF0ZTtcbiAgICAgICAgfVxuXG4gICAgICAgIG1mcC51cGRhdGVTdGF0dXMoJ2xvYWRpbmcnKTtcbiAgICAgICAgaXRlbS5sb2FkaW5nID0gdHJ1ZTtcblxuICAgICAgICBpZiAoIWl0ZW0uaGFzU2l6ZSkge1xuICAgICAgICAgIGl0ZW0uaW1nSGlkZGVuID0gdHJ1ZTtcbiAgICAgICAgICB0ZW1wbGF0ZS5hZGRDbGFzcygnbWZwLWxvYWRpbmcnKTtcbiAgICAgICAgICBtZnAuZmluZEltYWdlU2l6ZShpdGVtKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiB0ZW1wbGF0ZTtcbiAgICAgIH1cbiAgICB9XG4gIH0pO1xuXG4gIC8qPj5pbWFnZSovXG5cbiAgLyo+Pnpvb20qL1xuICB2YXIgaGFzTW96VHJhbnNmb3JtLFxuICAgIGdldEhhc01velRyYW5zZm9ybSA9IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmIChoYXNNb3pUcmFuc2Zvcm0gPT09IHVuZGVmaW5lZCkge1xuICAgICAgICBoYXNNb3pUcmFuc2Zvcm0gPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdwJykuc3R5bGUuTW96VHJhbnNmb3JtICE9PSB1bmRlZmluZWQ7XG4gICAgICB9XG4gICAgICByZXR1cm4gaGFzTW96VHJhbnNmb3JtO1xuICAgIH07XG5cbiAgJC5tYWduaWZpY1BvcHVwLnJlZ2lzdGVyTW9kdWxlKCd6b29tJywge1xuXG4gICAgb3B0aW9uczoge1xuICAgICAgZW5hYmxlZDogZmFsc2UsXG4gICAgICBlYXNpbmc6ICdlYXNlLWluLW91dCcsXG4gICAgICBkdXJhdGlvbjogMzAwLFxuICAgICAgb3BlbmVyOiBmdW5jdGlvbiAoZWxlbWVudCkge1xuICAgICAgICByZXR1cm4gZWxlbWVudC5pcygnaW1nJykgPyBlbGVtZW50IDogZWxlbWVudC5maW5kKCdpbWcnKTtcbiAgICAgIH1cbiAgICB9LFxuXG4gICAgcHJvdG86IHtcblxuICAgICAgaW5pdFpvb206IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdmFyIHpvb21TdCA9IG1mcC5zdC56b29tLFxuICAgICAgICAgIG5zID0gJy56b29tJyxcbiAgICAgICAgICBpbWFnZTtcblxuICAgICAgICBpZiAoIXpvb21TdC5lbmFibGVkIHx8ICFtZnAuc3VwcG9ydHNUcmFuc2l0aW9uKSB7XG4gICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgdmFyIGR1cmF0aW9uID0gem9vbVN0LmR1cmF0aW9uLFxuICAgICAgICAgIGdldEVsVG9BbmltYXRlID0gZnVuY3Rpb24gKGltYWdlKSB7XG4gICAgICAgICAgICB2YXIgbmV3SW1nID0gaW1hZ2UuY2xvbmUoKS5yZW1vdmVBdHRyKCdzdHlsZScpLnJlbW92ZUF0dHIoJ2NsYXNzJykuYWRkQ2xhc3MoJ21mcC1hbmltYXRlZC1pbWFnZScpLFxuICAgICAgICAgICAgICB0cmFuc2l0aW9uID0gJ2FsbCAnICsgKHpvb21TdC5kdXJhdGlvbiAvIDEwMDApICsgJ3MgJyArIHpvb21TdC5lYXNpbmcsXG4gICAgICAgICAgICAgIGNzc09iaiA9IHtcbiAgICAgICAgICAgICAgICBwb3NpdGlvbjogJ2ZpeGVkJyxcbiAgICAgICAgICAgICAgICB6SW5kZXg6IDk5OTksXG4gICAgICAgICAgICAgICAgbGVmdDogMCxcbiAgICAgICAgICAgICAgICB0b3A6IDAsXG4gICAgICAgICAgICAgICAgJy13ZWJraXQtYmFja2ZhY2UtdmlzaWJpbGl0eSc6ICdoaWRkZW4nXG4gICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgIHQgPSAndHJhbnNpdGlvbic7XG5cbiAgICAgICAgICAgIGNzc09ialsnLXdlYmtpdC0nICsgdF0gPSBjc3NPYmpbJy1tb3otJyArIHRdID0gY3NzT2JqWyctby0nICsgdF0gPSBjc3NPYmpbdF0gPSB0cmFuc2l0aW9uO1xuXG4gICAgICAgICAgICBuZXdJbWcuY3NzKGNzc09iaik7XG4gICAgICAgICAgICByZXR1cm4gbmV3SW1nO1xuICAgICAgICAgIH0sXG4gICAgICAgICAgc2hvd01haW5Db250ZW50ID0gZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgbWZwLmNvbnRlbnQuY3NzKCd2aXNpYmlsaXR5JywgJ3Zpc2libGUnKTtcbiAgICAgICAgICB9LFxuICAgICAgICAgIG9wZW5UaW1lb3V0LFxuICAgICAgICAgIGFuaW1hdGVkSW1nO1xuXG4gICAgICAgIF9tZnBPbignQnVpbGRDb250cm9scycgKyBucywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIGlmIChtZnAuX2FsbG93Wm9vbSgpKSB7XG5cbiAgICAgICAgICAgIGNsZWFyVGltZW91dChvcGVuVGltZW91dCk7XG4gICAgICAgICAgICBtZnAuY29udGVudC5jc3MoJ3Zpc2liaWxpdHknLCAnaGlkZGVuJyk7XG5cbiAgICAgICAgICAgIC8vIEJhc2ljYWxseSwgYWxsIGNvZGUgYmVsb3cgZG9lcyBpcyBjbG9uZXMgZXhpc3RpbmcgaW1hZ2UsIHB1dHMgaW4gb24gdG9wIG9mIHRoZSBjdXJyZW50IG9uZSBhbmQgYW5pbWF0ZWQgaXRcblxuICAgICAgICAgICAgaW1hZ2UgPSBtZnAuX2dldEl0ZW1Ub1pvb20oKTtcblxuICAgICAgICAgICAgaWYgKCFpbWFnZSkge1xuICAgICAgICAgICAgICBzaG93TWFpbkNvbnRlbnQoKTtcbiAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBhbmltYXRlZEltZyA9IGdldEVsVG9BbmltYXRlKGltYWdlKTtcblxuICAgICAgICAgICAgYW5pbWF0ZWRJbWcuY3NzKG1mcC5fZ2V0T2Zmc2V0KCkpO1xuXG4gICAgICAgICAgICBtZnAud3JhcC5hcHBlbmQoYW5pbWF0ZWRJbWcpO1xuXG4gICAgICAgICAgICBvcGVuVGltZW91dCA9IHNldFRpbWVvdXQoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICBhbmltYXRlZEltZy5jc3MobWZwLl9nZXRPZmZzZXQodHJ1ZSkpO1xuICAgICAgICAgICAgICBvcGVuVGltZW91dCA9IHNldFRpbWVvdXQoZnVuY3Rpb24gKCkge1xuXG4gICAgICAgICAgICAgICAgc2hvd01haW5Db250ZW50KCk7XG5cbiAgICAgICAgICAgICAgICBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgIGFuaW1hdGVkSW1nLnJlbW92ZSgpO1xuICAgICAgICAgICAgICAgICAgaW1hZ2UgPSBhbmltYXRlZEltZyA9IG51bGw7XG4gICAgICAgICAgICAgICAgICBfbWZwVHJpZ2dlcignWm9vbUFuaW1hdGlvbkVuZGVkJyk7XG4gICAgICAgICAgICAgICAgfSwgMTYpOyAvLyBhdm9pZCBibGluayB3aGVuIHN3aXRjaGluZyBpbWFnZXNcblxuICAgICAgICAgICAgICB9LCBkdXJhdGlvbik7IC8vIHRoaXMgdGltZW91dCBlcXVhbHMgYW5pbWF0aW9uIGR1cmF0aW9uXG5cbiAgICAgICAgICAgIH0sIDE2KTsgLy8gYnkgYWRkaW5nIHRoaXMgdGltZW91dCB3ZSBhdm9pZCBzaG9ydCBnbGl0Y2ggYXQgdGhlIGJlZ2lubmluZyBvZiBhbmltYXRpb25cblxuXG4gICAgICAgICAgICAvLyBMb3RzIG9mIHRpbWVvdXRzLi4uXG4gICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgICAgX21mcE9uKEJFRk9SRV9DTE9TRV9FVkVOVCArIG5zLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgaWYgKG1mcC5fYWxsb3dab29tKCkpIHtcblxuICAgICAgICAgICAgY2xlYXJUaW1lb3V0KG9wZW5UaW1lb3V0KTtcblxuICAgICAgICAgICAgbWZwLnN0LnJlbW92YWxEZWxheSA9IGR1cmF0aW9uO1xuXG4gICAgICAgICAgICBpZiAoIWltYWdlKSB7XG4gICAgICAgICAgICAgIGltYWdlID0gbWZwLl9nZXRJdGVtVG9ab29tKCk7XG4gICAgICAgICAgICAgIGlmICghaW1hZ2UpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgYW5pbWF0ZWRJbWcgPSBnZXRFbFRvQW5pbWF0ZShpbWFnZSk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIGFuaW1hdGVkSW1nLmNzcyhtZnAuX2dldE9mZnNldCh0cnVlKSk7XG4gICAgICAgICAgICBtZnAud3JhcC5hcHBlbmQoYW5pbWF0ZWRJbWcpO1xuICAgICAgICAgICAgbWZwLmNvbnRlbnQuY3NzKCd2aXNpYmlsaXR5JywgJ2hpZGRlbicpO1xuXG4gICAgICAgICAgICBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgYW5pbWF0ZWRJbWcuY3NzKG1mcC5fZ2V0T2Zmc2V0KCkpO1xuICAgICAgICAgICAgfSwgMTYpO1xuICAgICAgICAgIH1cblxuICAgICAgICB9KTtcblxuICAgICAgICBfbWZwT24oQ0xPU0VfRVZFTlQgKyBucywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIGlmIChtZnAuX2FsbG93Wm9vbSgpKSB7XG4gICAgICAgICAgICBzaG93TWFpbkNvbnRlbnQoKTtcbiAgICAgICAgICAgIGlmIChhbmltYXRlZEltZykge1xuICAgICAgICAgICAgICBhbmltYXRlZEltZy5yZW1vdmUoKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGltYWdlID0gbnVsbDtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgfSxcblxuICAgICAgX2FsbG93Wm9vbTogZnVuY3Rpb24gKCkge1xuICAgICAgICByZXR1cm4gbWZwLmN1cnJJdGVtLnR5cGUgPT09ICdpbWFnZSc7XG4gICAgICB9LFxuXG4gICAgICBfZ2V0SXRlbVRvWm9vbTogZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAobWZwLmN1cnJJdGVtLmhhc1NpemUpIHtcbiAgICAgICAgICByZXR1cm4gbWZwLmN1cnJJdGVtLmltZztcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cbiAgICAgIH0sXG5cbiAgICAgIC8vIEdldCBlbGVtZW50IHBvc3Rpb24gcmVsYXRpdmUgdG8gdmlld3BvcnRcbiAgICAgIF9nZXRPZmZzZXQ6IGZ1bmN0aW9uIChpc0xhcmdlKSB7XG4gICAgICAgIHZhciBlbDtcbiAgICAgICAgaWYgKGlzTGFyZ2UpIHtcbiAgICAgICAgICBlbCA9IG1mcC5jdXJySXRlbS5pbWc7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgZWwgPSBtZnAuc3Quem9vbS5vcGVuZXIobWZwLmN1cnJJdGVtLmVsIHx8IG1mcC5jdXJySXRlbSk7XG4gICAgICAgIH1cblxuICAgICAgICB2YXIgb2Zmc2V0ID0gZWwub2Zmc2V0KCk7XG4gICAgICAgIHZhciBwYWRkaW5nVG9wID0gcGFyc2VJbnQoZWwuY3NzKCdwYWRkaW5nLXRvcCcpLCAxMCk7XG4gICAgICAgIHZhciBwYWRkaW5nQm90dG9tID0gcGFyc2VJbnQoZWwuY3NzKCdwYWRkaW5nLWJvdHRvbScpLCAxMCk7XG4gICAgICAgIG9mZnNldC50b3AgLT0gKCQod2luZG93KS5zY3JvbGxUb3AoKSAtIHBhZGRpbmdUb3ApO1xuXG5cbiAgICAgICAgLypcblxuICAgICAgICBBbmltYXRpbmcgbGVmdCArIHRvcCArIHdpZHRoL2hlaWdodCBsb29rcyBnbGl0Y2h5IGluIEZpcmVmb3gsIGJ1dCBwZXJmZWN0IGluIENocm9tZS4gQW5kIHZpY2UtdmVyc2EuXG5cbiAgICAgICAgICovXG4gICAgICAgIHZhciBvYmogPSB7XG4gICAgICAgICAgd2lkdGg6IGVsLndpZHRoKCksXG4gICAgICAgICAgLy8gZml4IFplcHRvIGhlaWdodCtwYWRkaW5nIGlzc3VlXG4gICAgICAgICAgaGVpZ2h0OiAoX2lzSlEgPyBlbC5pbm5lckhlaWdodCgpIDogZWxbMF0ub2Zmc2V0SGVpZ2h0KSAtIHBhZGRpbmdCb3R0b20gLSBwYWRkaW5nVG9wXG4gICAgICAgIH07XG5cbiAgICAgICAgLy8gSSBoYXRlIHRvIGRvIHRoaXMsIGJ1dCB0aGVyZSBpcyBubyBhbm90aGVyIG9wdGlvblxuICAgICAgICBpZiAoZ2V0SGFzTW96VHJhbnNmb3JtKCkpIHtcbiAgICAgICAgICBvYmpbJy1tb3otdHJhbnNmb3JtJ10gPSBvYmpbJ3RyYW5zZm9ybSddID0gJ3RyYW5zbGF0ZSgnICsgb2Zmc2V0LmxlZnQgKyAncHgsJyArIG9mZnNldC50b3AgKyAncHgpJztcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICBvYmoubGVmdCA9IG9mZnNldC5sZWZ0O1xuICAgICAgICAgIG9iai50b3AgPSBvZmZzZXQudG9wO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBvYmo7XG4gICAgICB9XG5cbiAgICB9XG4gIH0pO1xuXG5cbiAgLyo+Pnpvb20qL1xuXG4gIC8qPj5pZnJhbWUqL1xuXG4gIHZhciBJRlJBTUVfTlMgPSAnaWZyYW1lJyxcbiAgICBfZW1wdHlQYWdlID0gJy8vYWJvdXQ6YmxhbmsnLFxuXG4gICAgX2ZpeElmcmFtZUJ1Z3MgPSBmdW5jdGlvbiAoaXNTaG93aW5nKSB7XG4gICAgICBpZiAobWZwLmN1cnJUZW1wbGF0ZVtJRlJBTUVfTlNdKSB7XG4gICAgICAgIHZhciBlbCA9IG1mcC5jdXJyVGVtcGxhdGVbSUZSQU1FX05TXS5maW5kKCdpZnJhbWUnKTtcbiAgICAgICAgaWYgKGVsLmxlbmd0aCkge1xuICAgICAgICAgIC8vIHJlc2V0IHNyYyBhZnRlciB0aGUgcG9wdXAgaXMgY2xvc2VkIHRvIGF2b2lkIFwidmlkZW8ga2VlcHMgcGxheWluZyBhZnRlciBwb3B1cCBpcyBjbG9zZWRcIiBidWdcbiAgICAgICAgICBpZiAoIWlzU2hvd2luZykge1xuICAgICAgICAgICAgZWxbMF0uc3JjID0gX2VtcHR5UGFnZTtcbiAgICAgICAgICB9XG5cbiAgICAgICAgICAvLyBJRTggYmxhY2sgc2NyZWVuIGJ1ZyBmaXhcbiAgICAgICAgICBpZiAobWZwLmlzSUU4KSB7XG4gICAgICAgICAgICBlbC5jc3MoJ2Rpc3BsYXknLCBpc1Nob3dpbmcgPyAnYmxvY2snIDogJ25vbmUnKTtcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9O1xuXG4gICQubWFnbmlmaWNQb3B1cC5yZWdpc3Rlck1vZHVsZShJRlJBTUVfTlMsIHtcblxuICAgIG9wdGlvbnM6IHtcbiAgICAgIG1hcmt1cDogJzxkaXYgY2xhc3M9XCJtZnAtaWZyYW1lLXNjYWxlclwiPicgK1xuICAgICAgICAnPGRpdiBjbGFzcz1cIm1mcC1jbG9zZVwiPjwvZGl2PicgK1xuICAgICAgICAnPGlmcmFtZSBjbGFzcz1cIm1mcC1pZnJhbWVcIiBzcmM9XCIvL2Fib3V0OmJsYW5rXCIgZnJhbWVib3JkZXI9XCIwXCIgYWxsb3dmdWxsc2NyZWVuPjwvaWZyYW1lPicgK1xuICAgICAgICAnPC9kaXY+JyxcblxuICAgICAgc3JjQWN0aW9uOiAnaWZyYW1lX3NyYycsXG5cbiAgICAgIC8vIHdlIGRvbid0IGNhcmUgYW5kIHN1cHBvcnQgb25seSBvbmUgZGVmYXVsdCB0eXBlIG9mIFVSTCBieSBkZWZhdWx0XG4gICAgICBwYXR0ZXJuczoge1xuICAgICAgICB5b3V0dWJlOiB7XG4gICAgICAgICAgaW5kZXg6ICd5b3V0dWJlLmNvbScsXG4gICAgICAgICAgaWQ6ICd2PScsXG4gICAgICAgICAgc3JjOiAnLy93d3cueW91dHViZS5jb20vZW1iZWQvJWlkJT9hdXRvcGxheT0xJ1xuICAgICAgICB9LFxuICAgICAgICB2aW1lbzoge1xuICAgICAgICAgIGluZGV4OiAndmltZW8uY29tLycsXG4gICAgICAgICAgaWQ6ICcvJyxcbiAgICAgICAgICBzcmM6ICcvL3BsYXllci52aW1lby5jb20vdmlkZW8vJWlkJT9hdXRvcGxheT0xJ1xuICAgICAgICB9LFxuICAgICAgICBnbWFwczoge1xuICAgICAgICAgIGluZGV4OiAnLy9tYXBzLmdvb2dsZS4nLFxuICAgICAgICAgIHNyYzogJyVpZCUmb3V0cHV0PWVtYmVkJ1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcblxuICAgIHByb3RvOiB7XG4gICAgICBpbml0SWZyYW1lOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgIG1mcC50eXBlcy5wdXNoKElGUkFNRV9OUyk7XG5cbiAgICAgICAgX21mcE9uKCdCZWZvcmVDaGFuZ2UnLCBmdW5jdGlvbiAoZSwgcHJldlR5cGUsIG5ld1R5cGUpIHtcbiAgICAgICAgICBpZiAocHJldlR5cGUgIT09IG5ld1R5cGUpIHtcbiAgICAgICAgICAgIGlmIChwcmV2VHlwZSA9PT0gSUZSQU1FX05TKSB7XG4gICAgICAgICAgICAgIF9maXhJZnJhbWVCdWdzKCk7IC8vIGlmcmFtZSBpZiByZW1vdmVkXG4gICAgICAgICAgICB9IGVsc2UgaWYgKG5ld1R5cGUgPT09IElGUkFNRV9OUykge1xuICAgICAgICAgICAgICBfZml4SWZyYW1lQnVncyh0cnVlKTsgLy8gaWZyYW1lIGlzIHNob3dpbmdcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9Ly8gZWxzZSB7XG4gICAgICAgICAgLy8gaWZyYW1lIHNvdXJjZSBpcyBzd2l0Y2hlZCwgZG9uJ3QgZG8gYW55dGhpbmdcbiAgICAgICAgICAvL31cbiAgICAgICAgfSk7XG5cbiAgICAgICAgX21mcE9uKENMT1NFX0VWRU5UICsgJy4nICsgSUZSQU1FX05TLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgX2ZpeElmcmFtZUJ1Z3MoKTtcbiAgICAgICAgfSk7XG4gICAgICB9LFxuXG4gICAgICBnZXRJZnJhbWU6IGZ1bmN0aW9uIChpdGVtLCB0ZW1wbGF0ZSkge1xuICAgICAgICB2YXIgZW1iZWRTcmMgPSBpdGVtLnNyYztcbiAgICAgICAgdmFyIGlmcmFtZVN0ID0gbWZwLnN0LmlmcmFtZTtcblxuICAgICAgICAkLmVhY2goaWZyYW1lU3QucGF0dGVybnMsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICBpZiAoZW1iZWRTcmMuaW5kZXhPZih0aGlzLmluZGV4KSA+IC0xKSB7XG4gICAgICAgICAgICBpZiAodGhpcy5pZCkge1xuICAgICAgICAgICAgICBpZiAodHlwZW9mIHRoaXMuaWQgPT09ICdzdHJpbmcnKSB7XG4gICAgICAgICAgICAgICAgZW1iZWRTcmMgPSBlbWJlZFNyYy5zdWJzdHIoZW1iZWRTcmMubGFzdEluZGV4T2YodGhpcy5pZCkgKyB0aGlzLmlkLmxlbmd0aCwgZW1iZWRTcmMubGVuZ3RoKTtcbiAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICBlbWJlZFNyYyA9IHRoaXMuaWQuY2FsbCh0aGlzLCBlbWJlZFNyYyk7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVtYmVkU3JjID0gdGhpcy5zcmMucmVwbGFjZSgnJWlkJScsIGVtYmVkU3JjKTtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTsgLy8gYnJlYWs7XG4gICAgICAgICAgfVxuICAgICAgICB9KTtcblxuICAgICAgICB2YXIgZGF0YU9iaiA9IHt9O1xuICAgICAgICBpZiAoaWZyYW1lU3Quc3JjQWN0aW9uKSB7XG4gICAgICAgICAgZGF0YU9ialtpZnJhbWVTdC5zcmNBY3Rpb25dID0gZW1iZWRTcmM7XG4gICAgICAgIH1cbiAgICAgICAgbWZwLl9wYXJzZU1hcmt1cCh0ZW1wbGF0ZSwgZGF0YU9iaiwgaXRlbSk7XG5cbiAgICAgICAgbWZwLnVwZGF0ZVN0YXR1cygncmVhZHknKTtcblxuICAgICAgICByZXR1cm4gdGVtcGxhdGU7XG4gICAgICB9XG4gICAgfVxuICB9KTtcblxuXG4gIC8qPj5pZnJhbWUqL1xuXG4gIC8qPj5nYWxsZXJ5Ki9cbiAgLyoqXG4gICAqIEdldCBsb29wZWQgaW5kZXggZGVwZW5kaW5nIG9uIG51bWJlciBvZiBzbGlkZXNcbiAgICovXG4gIHZhciBfZ2V0TG9vcGVkSWQgPSBmdW5jdGlvbiAoaW5kZXgpIHtcbiAgICAgIHZhciBudW1TbGlkZXMgPSBtZnAuaXRlbXMubGVuZ3RoO1xuICAgICAgaWYgKGluZGV4ID4gbnVtU2xpZGVzIC0gMSkge1xuICAgICAgICByZXR1cm4gaW5kZXggLSBudW1TbGlkZXM7XG4gICAgICB9IGVsc2UgaWYgKGluZGV4IDwgMCkge1xuICAgICAgICByZXR1cm4gbnVtU2xpZGVzICsgaW5kZXg7XG4gICAgICB9XG4gICAgICByZXR1cm4gaW5kZXg7XG4gICAgfSxcbiAgICBfcmVwbGFjZUN1cnJUb3RhbCA9IGZ1bmN0aW9uICh0ZXh0LCBjdXJyLCB0b3RhbCkge1xuICAgICAgcmV0dXJuIHRleHQucmVwbGFjZSgvJWN1cnIlL2dpLCBjdXJyICsgMSkucmVwbGFjZSgvJXRvdGFsJS9naSwgdG90YWwpO1xuICAgIH07XG5cbiAgJC5tYWduaWZpY1BvcHVwLnJlZ2lzdGVyTW9kdWxlKCdnYWxsZXJ5Jywge1xuXG4gICAgb3B0aW9uczoge1xuICAgICAgZW5hYmxlZDogZmFsc2UsXG4gICAgICBhcnJvd01hcmt1cDogJzxidXR0b24gdGl0bGU9XCIldGl0bGUlXCIgdHlwZT1cImJ1dHRvblwiIGNsYXNzPVwibWZwLWFycm93IG1mcC1hcnJvdy0lZGlyJVwiPjwvYnV0dG9uPicsXG4gICAgICBwcmVsb2FkOiBbMCwgMl0sXG4gICAgICBuYXZpZ2F0ZUJ5SW1nQ2xpY2s6IHRydWUsXG4gICAgICBhcnJvd3M6IHRydWUsXG5cbiAgICAgIHRQcmV2OiAnUHJldmlvdXMgKExlZnQgYXJyb3cga2V5KScsXG4gICAgICB0TmV4dDogJ05leHQgKFJpZ2h0IGFycm93IGtleSknLFxuICAgICAgdENvdW50ZXI6ICclY3VyciUgb2YgJXRvdGFsJSdcbiAgICB9LFxuXG4gICAgcHJvdG86IHtcbiAgICAgIGluaXRHYWxsZXJ5OiBmdW5jdGlvbiAoKSB7XG5cbiAgICAgICAgdmFyIGdTdCA9IG1mcC5zdC5nYWxsZXJ5LFxuICAgICAgICAgIG5zID0gJy5tZnAtZ2FsbGVyeSc7XG5cbiAgICAgICAgbWZwLmRpcmVjdGlvbiA9IHRydWU7IC8vIHRydWUgLSBuZXh0LCBmYWxzZSAtIHByZXZcblxuICAgICAgICBpZiAoIWdTdCB8fCAhZ1N0LmVuYWJsZWQpIHJldHVybiBmYWxzZTtcblxuICAgICAgICBfd3JhcENsYXNzZXMgKz0gJyBtZnAtZ2FsbGVyeSc7XG5cbiAgICAgICAgX21mcE9uKE9QRU5fRVZFTlQgKyBucywgZnVuY3Rpb24gKCkge1xuXG4gICAgICAgICAgaWYgKGdTdC5uYXZpZ2F0ZUJ5SW1nQ2xpY2spIHtcbiAgICAgICAgICAgIG1mcC53cmFwLm9uKCdjbGljaycgKyBucywgJy5tZnAtaW1nJywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICBpZiAobWZwLml0ZW1zLmxlbmd0aCA+IDEpIHtcbiAgICAgICAgICAgICAgICBtZnAubmV4dCgpO1xuICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgX2RvY3VtZW50Lm9uKCdrZXlkb3duJyArIG5zLCBmdW5jdGlvbiAoZSkge1xuICAgICAgICAgICAgaWYgKGUua2V5Q29kZSA9PT0gMzcpIHtcbiAgICAgICAgICAgICAgbWZwLnByZXYoKTtcbiAgICAgICAgICAgIH0gZWxzZSBpZiAoZS5rZXlDb2RlID09PSAzOSkge1xuICAgICAgICAgICAgICBtZnAubmV4dCgpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH0pO1xuICAgICAgICB9KTtcblxuICAgICAgICBfbWZwT24oJ1VwZGF0ZVN0YXR1cycgKyBucywgZnVuY3Rpb24gKGUsIGRhdGEpIHtcbiAgICAgICAgICBpZiAoZGF0YS50ZXh0KSB7XG4gICAgICAgICAgICBkYXRhLnRleHQgPSBfcmVwbGFjZUN1cnJUb3RhbChkYXRhLnRleHQsIG1mcC5jdXJySXRlbS5pbmRleCwgbWZwLml0ZW1zLmxlbmd0aCk7XG4gICAgICAgICAgfVxuICAgICAgICB9KTtcblxuICAgICAgICBfbWZwT24oTUFSS1VQX1BBUlNFX0VWRU5UICsgbnMsIGZ1bmN0aW9uIChlLCBlbGVtZW50LCB2YWx1ZXMsIGl0ZW0pIHtcbiAgICAgICAgICB2YXIgbCA9IG1mcC5pdGVtcy5sZW5ndGg7XG4gICAgICAgICAgdmFsdWVzLmNvdW50ZXIgPSBsID4gMSA/IF9yZXBsYWNlQ3VyclRvdGFsKGdTdC50Q291bnRlciwgaXRlbS5pbmRleCwgbCkgOiAnJztcbiAgICAgICAgfSk7XG5cbiAgICAgICAgX21mcE9uKCdCdWlsZENvbnRyb2xzJyArIG5zLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgaWYgKG1mcC5pdGVtcy5sZW5ndGggPiAxICYmIGdTdC5hcnJvd3MgJiYgIW1mcC5hcnJvd0xlZnQpIHtcbiAgICAgICAgICAgIHZhciBtYXJrdXAgPSBnU3QuYXJyb3dNYXJrdXAsXG4gICAgICAgICAgICAgIGFycm93TGVmdCA9IG1mcC5hcnJvd0xlZnQgPSAkKG1hcmt1cC5yZXBsYWNlKC8ldGl0bGUlL2dpLCBnU3QudFByZXYpLnJlcGxhY2UoLyVkaXIlL2dpLCAnbGVmdCcpKS5hZGRDbGFzcyhQUkVWRU5UX0NMT1NFX0NMQVNTKSxcbiAgICAgICAgICAgICAgYXJyb3dSaWdodCA9IG1mcC5hcnJvd1JpZ2h0ID0gJChtYXJrdXAucmVwbGFjZSgvJXRpdGxlJS9naSwgZ1N0LnROZXh0KS5yZXBsYWNlKC8lZGlyJS9naSwgJ3JpZ2h0JykpLmFkZENsYXNzKFBSRVZFTlRfQ0xPU0VfQ0xBU1MpO1xuXG4gICAgICAgICAgICBhcnJvd0xlZnQuY2xpY2soZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICBtZnAucHJldigpO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICBhcnJvd1JpZ2h0LmNsaWNrKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgbWZwLm5leHQoKTtcbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICBtZnAuY29udGFpbmVyLmFwcGVuZChhcnJvd0xlZnQuYWRkKGFycm93UmlnaHQpKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuXG4gICAgICAgIF9tZnBPbihDSEFOR0VfRVZFTlQgKyBucywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIGlmIChtZnAuX3ByZWxvYWRUaW1lb3V0KSBjbGVhclRpbWVvdXQobWZwLl9wcmVsb2FkVGltZW91dCk7XG5cbiAgICAgICAgICBtZnAuX3ByZWxvYWRUaW1lb3V0ID0gc2V0VGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICBtZnAucHJlbG9hZE5lYXJieUltYWdlcygpO1xuICAgICAgICAgICAgbWZwLl9wcmVsb2FkVGltZW91dCA9IG51bGw7XG4gICAgICAgICAgfSwgMTYpO1xuICAgICAgICB9KTtcblxuXG4gICAgICAgIF9tZnBPbihDTE9TRV9FVkVOVCArIG5zLCBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgX2RvY3VtZW50Lm9mZihucyk7XG4gICAgICAgICAgbWZwLndyYXAub2ZmKCdjbGljaycgKyBucyk7XG4gICAgICAgICAgbWZwLmFycm93UmlnaHQgPSBtZnAuYXJyb3dMZWZ0ID0gbnVsbDtcbiAgICAgICAgfSk7XG5cbiAgICAgIH0sXG4gICAgICBuZXh0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgIG1mcC5kaXJlY3Rpb24gPSB0cnVlO1xuICAgICAgICBtZnAuaW5kZXggPSBfZ2V0TG9vcGVkSWQobWZwLmluZGV4ICsgMSk7XG4gICAgICAgIG1mcC51cGRhdGVJdGVtSFRNTCgpO1xuICAgICAgfSxcbiAgICAgIHByZXY6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgbWZwLmRpcmVjdGlvbiA9IGZhbHNlO1xuICAgICAgICBtZnAuaW5kZXggPSBfZ2V0TG9vcGVkSWQobWZwLmluZGV4IC0gMSk7XG4gICAgICAgIG1mcC51cGRhdGVJdGVtSFRNTCgpO1xuICAgICAgfSxcbiAgICAgIGdvVG86IGZ1bmN0aW9uIChuZXdJbmRleCkge1xuICAgICAgICBtZnAuZGlyZWN0aW9uID0gKG5ld0luZGV4ID49IG1mcC5pbmRleCk7XG4gICAgICAgIG1mcC5pbmRleCA9IG5ld0luZGV4O1xuICAgICAgICBtZnAudXBkYXRlSXRlbUhUTUwoKTtcbiAgICAgIH0sXG4gICAgICBwcmVsb2FkTmVhcmJ5SW1hZ2VzOiBmdW5jdGlvbiAoKSB7XG4gICAgICAgIHZhciBwID0gbWZwLnN0LmdhbGxlcnkucHJlbG9hZCxcbiAgICAgICAgICBwcmVsb2FkQmVmb3JlID0gTWF0aC5taW4ocFswXSwgbWZwLml0ZW1zLmxlbmd0aCksXG4gICAgICAgICAgcHJlbG9hZEFmdGVyID0gTWF0aC5taW4ocFsxXSwgbWZwLml0ZW1zLmxlbmd0aCksXG4gICAgICAgICAgaTtcblxuICAgICAgICBmb3IgKGkgPSAxOyBpIDw9IChtZnAuZGlyZWN0aW9uID8gcHJlbG9hZEFmdGVyIDogcHJlbG9hZEJlZm9yZSk7IGkrKykge1xuICAgICAgICAgIG1mcC5fcHJlbG9hZEl0ZW0obWZwLmluZGV4ICsgaSk7XG4gICAgICAgIH1cbiAgICAgICAgZm9yIChpID0gMTsgaSA8PSAobWZwLmRpcmVjdGlvbiA/IHByZWxvYWRCZWZvcmUgOiBwcmVsb2FkQWZ0ZXIpOyBpKyspIHtcbiAgICAgICAgICBtZnAuX3ByZWxvYWRJdGVtKG1mcC5pbmRleCAtIGkpO1xuICAgICAgICB9XG4gICAgICB9LFxuICAgICAgX3ByZWxvYWRJdGVtOiBmdW5jdGlvbiAoaW5kZXgpIHtcbiAgICAgICAgaW5kZXggPSBfZ2V0TG9vcGVkSWQoaW5kZXgpO1xuXG4gICAgICAgIGlmIChtZnAuaXRlbXNbaW5kZXhdLnByZWxvYWRlZCkge1xuICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIHZhciBpdGVtID0gbWZwLml0ZW1zW2luZGV4XTtcbiAgICAgICAgaWYgKCFpdGVtLnBhcnNlZCkge1xuICAgICAgICAgIGl0ZW0gPSBtZnAucGFyc2VFbChpbmRleCk7XG4gICAgICAgIH1cblxuICAgICAgICBfbWZwVHJpZ2dlcignTGF6eUxvYWQnLCBpdGVtKTtcblxuICAgICAgICBpZiAoaXRlbS50eXBlID09PSAnaW1hZ2UnKSB7XG4gICAgICAgICAgaXRlbS5pbWcgPSAkKCc8aW1nIGNsYXNzPVwibWZwLWltZ1wiIC8+Jykub24oJ2xvYWQubWZwbG9hZGVyJywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgaXRlbS5oYXNTaXplID0gdHJ1ZTtcbiAgICAgICAgICB9KS5vbignZXJyb3IubWZwbG9hZGVyJywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgaXRlbS5oYXNTaXplID0gdHJ1ZTtcbiAgICAgICAgICAgIGl0ZW0ubG9hZEVycm9yID0gdHJ1ZTtcbiAgICAgICAgICAgIF9tZnBUcmlnZ2VyKCdMYXp5TG9hZEVycm9yJywgaXRlbSk7XG4gICAgICAgICAgfSkuYXR0cignc3JjJywgaXRlbS5zcmMpO1xuICAgICAgICB9XG5cblxuICAgICAgICBpdGVtLnByZWxvYWRlZCA9IHRydWU7XG4gICAgICB9XG4gICAgfVxuICB9KTtcblxuICAvKj4+Z2FsbGVyeSovXG5cbiAgLyo+PnJldGluYSovXG5cbiAgdmFyIFJFVElOQV9OUyA9ICdyZXRpbmEnO1xuXG4gICQubWFnbmlmaWNQb3B1cC5yZWdpc3Rlck1vZHVsZShSRVRJTkFfTlMsIHtcbiAgICBvcHRpb25zOiB7XG4gICAgICByZXBsYWNlU3JjOiBmdW5jdGlvbiAoaXRlbSkge1xuICAgICAgICByZXR1cm4gaXRlbS5zcmMucmVwbGFjZSgvXFwuXFx3KyQvLCBmdW5jdGlvbiAobSkge1xuICAgICAgICAgIHJldHVybiAnQDJ4JyArIG07XG4gICAgICAgIH0pO1xuICAgICAgfSxcbiAgICAgIHJhdGlvOiAxIC8vIEZ1bmN0aW9uIG9yIG51bWJlci4gIFNldCB0byAxIHRvIGRpc2FibGUuXG4gICAgfSxcbiAgICBwcm90bzoge1xuICAgICAgaW5pdFJldGluYTogZnVuY3Rpb24gKCkge1xuICAgICAgICBpZiAod2luZG93LmRldmljZVBpeGVsUmF0aW8gPiAxKSB7XG5cbiAgICAgICAgICB2YXIgc3QgPSBtZnAuc3QucmV0aW5hLFxuICAgICAgICAgICAgcmF0aW8gPSBzdC5yYXRpbztcblxuICAgICAgICAgIHJhdGlvID0gIWlzTmFOKHJhdGlvKSA/IHJhdGlvIDogcmF0aW8oKTtcblxuICAgICAgICAgIGlmIChyYXRpbyA+IDEpIHtcbiAgICAgICAgICAgIF9tZnBPbignSW1hZ2VIYXNTaXplJyArICcuJyArIFJFVElOQV9OUywgZnVuY3Rpb24gKGUsIGl0ZW0pIHtcbiAgICAgICAgICAgICAgaXRlbS5pbWcuY3NzKHtcbiAgICAgICAgICAgICAgICAnbWF4LXdpZHRoJzogaXRlbS5pbWdbMF0ubmF0dXJhbFdpZHRoIC8gcmF0aW8sXG4gICAgICAgICAgICAgICAgJ3dpZHRoJzogJzEwMCUnXG4gICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICBfbWZwT24oJ0VsZW1lbnRQYXJzZScgKyAnLicgKyBSRVRJTkFfTlMsIGZ1bmN0aW9uIChlLCBpdGVtKSB7XG4gICAgICAgICAgICAgIGl0ZW0uc3JjID0gc3QucmVwbGFjZVNyYyhpdGVtLCByYXRpbyk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgfVxuICAgIH1cbiAgfSk7XG5cbiAgLyo+PnJldGluYSovXG4gIF9jaGVja0luc3RhbmNlKCk7XG59KSk7XG4iLCIvKlxuICogVGhpcyBmaWxlIGlzIHBhcnQgb2YgSm9obkNNUyBDb250ZW50IE1hbmFnZW1lbnQgU3lzdGVtLlxuICpcbiAqIEBjb3B5cmlnaHQgSm9obkNNUyBDb21tdW5pdHlcbiAqIEBsaWNlbnNlICAgaHR0cHM6Ly9vcGVuc291cmNlLm9yZy9saWNlbnNlcy9HUEwtMy4wIEdQTC0zLjBcbiAqIEBsaW5rICAgICAgaHR0cHM6Ly9qb2huY21zLmNvbSBKb2huQ01TIFByb2plY3RcbiAqL1xuXG5QcmlzbS5tYW51YWwgPSB0cnVlO1xuXG4kKGZ1bmN0aW9uICgpIHtcbiAgY29uc3Qgc2Nyb2xsX2J1dHRvbiA9ICQoJy50by10b3AnKTtcblxuICAkKFwiLnBvc3QtYm9keVwiKS5lYWNoKGZ1bmN0aW9uICgpIHtcbiAgICBQcmlzbS5oaWdobGlnaHRBbGxVbmRlcih0aGlzKTtcbiAgfSk7XG5cbiAgaWYgKCQoZG9jdW1lbnQpLmhlaWdodCgpID4gJCh3aW5kb3cpLmhlaWdodCgpICYmICQodGhpcykuc2Nyb2xsVG9wKCkgPCA1MCkge1xuICAgIHNjcm9sbF9idXR0b24uYWRkQ2xhc3MoJ3RvLWJvdHRvbScpLnJlbW92ZUNsYXNzKCd0by10b3BfaGlkZGVuJyk7XG4gIH1cblxuICAkKHdpbmRvdykuc2Nyb2xsKGZ1bmN0aW9uICgpIHtcbiAgICBpZiAoJCh0aGlzKS5zY3JvbGxUb3AoKSA+IDUwKSB7XG4gICAgICBzY3JvbGxfYnV0dG9uLnJlbW92ZUNsYXNzKCd0by1ib3R0b20nKTtcbiAgICAgIHNjcm9sbF9idXR0b24uYWRkQ2xhc3MoJ3RvLWhlYWRlcicpO1xuICAgIH0gZWxzZSB7XG4gICAgICBzY3JvbGxfYnV0dG9uLmFkZENsYXNzKCd0by1ib3R0b20nKTtcbiAgICAgIHNjcm9sbF9idXR0b24ucmVtb3ZlQ2xhc3MoJ3RvLWhlYWRlcicpO1xuICAgIH1cbiAgfSk7XG5cbiAgJChcIi50by10b3BcIikuY2xpY2soZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICBpZiAoJCh0aGlzKS5oYXNDbGFzcygndG8taGVhZGVyJykpIHtcbiAgICAgICQoJ2JvZHksaHRtbCcpLmFuaW1hdGUoe3Njcm9sbFRvcDogMH0sIDgwMCk7XG4gICAgfSBlbHNlIHtcbiAgICAgICQoJ2JvZHksaHRtbCcpLmFuaW1hdGUoe3Njcm9sbFRvcDogJChkb2N1bWVudCkuaGVpZ2h0KCl9LCA4MDApO1xuICAgIH1cbiAgfSk7XG59KTtcblxuJChkb2N1bWVudCkucmVhZHkoZnVuY3Rpb24gKCkge1xuICBpZiAodHlwZW9mIHd5c2liYl9pbnB1dCAhPSBcInVuZGVmaW5lZFwiKSB7XG4gICAgJCh3eXNpYmJfaW5wdXQpLnd5c2liYih3eXNpYmJfc2V0dGluZ3MpO1xuICB9XG5cbiAgJChcIi5mbGF0cGlja3JcIikuZmxhdHBpY2tyKHtcbiAgICBkYXRlRm9ybWF0OiAnZC5tLlknLFxuICB9KTtcbiAgJChcIi5mbGF0cGlja3JfdGltZVwiKS5mbGF0cGlja3Ioe1xuICAgIGRhdGVGb3JtYXQ6ICdkLm0uWSBIOmknLFxuICAgIGVuYWJsZVRpbWU6IHRydWUsXG4gIH0pO1xufSlcbiIsIiQoZG9jdW1lbnQpXG4gIC5vbignY2xpY2snLCAnLm5hdmJhci10b2dnbGVyLCAuc2hvd19tZW51X2J0bicsIGZ1bmN0aW9uICgpIHtcbiAgICB0b2dnbGVfbWVudSgpO1xuICB9KVxuICAub24oJ2NsaWNrJywgJy5zaWRlYmFyX29wZW5lZCAub3ZlcmxheScsIGZ1bmN0aW9uICgpIHtcbiAgICB2YXIgYm9keSA9ICQoJ2JvZHknKTtcbiAgICBpZiAoYm9keS5oYXNDbGFzcygnc2lkZWJhcl9vcGVuZWQnKSkge1xuICAgICAgdG9nZ2xlX21lbnUoKTtcbiAgICB9XG4gIH0pO1xuXG4vLyDQntGC0LrRgNGL0YLQuNC1L9C30LDQutGA0YvRgtC40LUg0LzQtdC90Y4g0LTQu9GPINC80L7QsdC40LvRjNC90L7QuSDQstC10YDRgdC40LhcbmZ1bmN0aW9uIHRvZ2dsZV9tZW51KClcbntcbiAgdmFyIGJvZHkgPSAkKCdib2R5Jyk7XG4gIGlmIChib2R5Lmhhc0NsYXNzKCdzaWRlYmFyX29wZW5lZCcpKSB7XG4gICAgYm9keS5yZW1vdmVDbGFzcygnc2lkZWJhcl9vcGVuZWQnKTtcbiAgICBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICQoJy50b3BfbmF2IC5uYXZiYXItdG9nZ2xlJykucmVtb3ZlQ2xhc3MoJ3RvZ2dsZWQnKTtcbiAgICB9LCA1MDApO1xuXG4gIH0gZWxzZSB7XG4gICAgYm9keS5hZGRDbGFzcygnc2lkZWJhcl9vcGVuZWQnKTtcbiAgICBzZXRUaW1lb3V0KGZ1bmN0aW9uICgpIHtcbiAgICAgICQoJy50b3BfbmF2IC5uYXZiYXItdG9nZ2xlJykuYWRkQ2xhc3MoJ3RvZ2dsZWQnKTtcbiAgICB9LCA1MDApO1xuICB9XG59XG4iLCIvKipcbiAqIFRoaXMgZmlsZSBpcyBwYXJ0IG9mIEpvaG5DTVMgQ29udGVudCBNYW5hZ2VtZW50IFN5c3RlbS5cbiAqXG4gKiBAY29weXJpZ2h0IEpvaG5DTVMgQ29tbXVuaXR5XG4gKiBAbGljZW5zZSAgIGh0dHBzOi8vb3BlbnNvdXJjZS5vcmcvbGljZW5zZXMvR1BMLTMuMCBHUEwtMy4wXG4gKiBAbGluayAgICAgIGh0dHBzOi8vam9obmNtcy5jb20gSm9obkNNUyBQcm9qZWN0XG4gKi9cblxuZnVuY3Rpb24gZ2V0U3Bpbm5lcigpXG57XG4gIHJldHVybiAnPGRpdiBjbGFzcz1cInRleHQtY2VudGVyIHAtNVwiPjxkaXYgY2xhc3M9XCJzcGlubmVyLWJvcmRlclwiIHJvbGU9XCJzdGF0dXNcIj48c3BhbiBjbGFzcz1cInZpc3VhbGx5LWhpZGRlblwiPkxvYWRpbmcuLi48L3NwYW4+PC9kaXY+PC9kaXY+Jztcbn1cblxuJChmdW5jdGlvbiAoKSB7XG4gIGxldCBhamF4X21vZGFsID0gJCgnLmFqYXhfbW9kYWwnKTtcblxuICBhamF4X21vZGFsLm9uKCdzaG93LmJzLm1vZGFsJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgJCgnLmFqYXhfbW9kYWwgLm1vZGFsLWNvbnRlbnQnKS5odG1sKGdldFNwaW5uZXIoKSk7XG4gIH0pO1xuXG4gIGFqYXhfbW9kYWwub24oJ3Nob3duLmJzLm1vZGFsJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgbGV0IGJ1dHRvbiA9ICQoZXZlbnQucmVsYXRlZFRhcmdldCk7XG4gICAgbGV0IHBhcmFtcyA9IGJ1dHRvbi5kYXRhKCk7XG4gICAgJC5hamF4KHtcbiAgICAgIHR5cGU6IFwiR0VUXCIsXG4gICAgICB1cmw6IHBhcmFtcy51cmwsXG4gICAgICBkYXRhVHlwZTogXCJodG1sXCIsXG4gICAgICBkYXRhOiBwYXJhbXMsXG4gICAgICBzdWNjZXNzOiBmdW5jdGlvbiAoaHRtbCkge1xuICAgICAgICAkKCcuYWpheF9tb2RhbCAubW9kYWwtY29udGVudCcpLmh0bWwoaHRtbCk7XG4gICAgICB9XG4gICAgfSk7XG4gIH0pO1xufSk7XG5cbiQoZG9jdW1lbnQpLm9uKCdjbGljaycsICcuc2VsZWN0X2xhbmd1YWdlJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gIGxldCBzZWxlY3RfbGFuZ3VhZ2VfZm9ybSA9ICQoJ2Zvcm1bbmFtZT1cInNlbGVjdF9sYW5ndWFnZVwiXScpO1xuXG4gICQuYWpheCh7XG4gICAgdHlwZTogXCJQT1NUXCIsXG4gICAgdXJsOiBzZWxlY3RfbGFuZ3VhZ2VfZm9ybS5hdHRyKCdhY3Rpb24nKSxcbiAgICBkYXRhVHlwZTogXCJodG1sXCIsXG4gICAgZGF0YTogc2VsZWN0X2xhbmd1YWdlX2Zvcm0uc2VyaWFsaXplKCksXG4gICAgc3VjY2VzczogZnVuY3Rpb24gKGh0bWwpIHtcbiAgICAgICQoJy5hamF4X21vZGFsJykubW9kYWwoJ2hpZGUnKTtcbiAgICAgIGRvY3VtZW50LmxvY2F0aW9uLmhyZWYgPSBkb2N1bWVudC5sb2NhdGlvbi5ocmVmO1xuICAgIH1cbiAgfSk7XG59KTtcbiIsIi8qIFByaXNtSlMgMS4xNy4xXG5odHRwczovL3ByaXNtanMuY29tL2Rvd25sb2FkLmh0bWwjdGhlbWVzPXByaXNtJmxhbmd1YWdlcz1tYXJrdXArY3NzK2NsaWtlK2phdmFzY3JpcHQrbWFya3VwLXRlbXBsYXRpbmcrcGhwK2phdmFkb2NsaWtlK3BocGRvYytwaHAtZXh0cmFzK3NxbCZwbHVnaW5zPWxpbmUtbnVtYmVycyAqL1xudmFyIF9zZWxmPVwidW5kZWZpbmVkXCIhPXR5cGVvZiB3aW5kb3c/d2luZG93OlwidW5kZWZpbmVkXCIhPXR5cGVvZiBXb3JrZXJHbG9iYWxTY29wZSYmc2VsZiBpbnN0YW5jZW9mIFdvcmtlckdsb2JhbFNjb3BlP3NlbGY6e30sUHJpc209ZnVuY3Rpb24odSl7dmFyIGM9L1xcYmxhbmcoPzp1YWdlKT8tKFtcXHctXSspXFxiL2kscj0wO3ZhciBfPXttYW51YWw6dS5QcmlzbSYmdS5QcmlzbS5tYW51YWwsZGlzYWJsZVdvcmtlck1lc3NhZ2VIYW5kbGVyOnUuUHJpc20mJnUuUHJpc20uZGlzYWJsZVdvcmtlck1lc3NhZ2VIYW5kbGVyLHV0aWw6e2VuY29kZTpmdW5jdGlvbihlKXtyZXR1cm4gZSBpbnN0YW5jZW9mIEw/bmV3IEwoZS50eXBlLF8udXRpbC5lbmNvZGUoZS5jb250ZW50KSxlLmFsaWFzKTpBcnJheS5pc0FycmF5KGUpP2UubWFwKF8udXRpbC5lbmNvZGUpOmUucmVwbGFjZSgvJi9nLFwiJmFtcDtcIikucmVwbGFjZSgvPC9nLFwiJmx0O1wiKS5yZXBsYWNlKC9cXHUwMGEwL2csXCIgXCIpfSx0eXBlOmZ1bmN0aW9uKGUpe3JldHVybiBPYmplY3QucHJvdG90eXBlLnRvU3RyaW5nLmNhbGwoZSkuc2xpY2UoOCwtMSl9LG9iaklkOmZ1bmN0aW9uKGUpe3JldHVybiBlLl9faWR8fE9iamVjdC5kZWZpbmVQcm9wZXJ0eShlLFwiX19pZFwiLHt2YWx1ZTorK3J9KSxlLl9faWR9LGNsb25lOmZ1bmN0aW9uIG4oZSx0KXt2YXIgYSxyLGk9Xy51dGlsLnR5cGUoZSk7c3dpdGNoKHQ9dHx8e30saSl7Y2FzZVwiT2JqZWN0XCI6aWYocj1fLnV0aWwub2JqSWQoZSksdFtyXSlyZXR1cm4gdFtyXTtmb3IodmFyIG8gaW4gYT17fSx0W3JdPWEsZSllLmhhc093blByb3BlcnR5KG8pJiYoYVtvXT1uKGVbb10sdCkpO3JldHVybiBhO2Nhc2VcIkFycmF5XCI6cmV0dXJuIHI9Xy51dGlsLm9iaklkKGUpLHRbcl0/dFtyXTooYT1bXSx0W3JdPWEsZS5mb3JFYWNoKGZ1bmN0aW9uKGUscil7YVtyXT1uKGUsdCl9KSxhKTtkZWZhdWx0OnJldHVybiBlfX0sY3VycmVudFNjcmlwdDpmdW5jdGlvbigpe2lmKFwidW5kZWZpbmVkXCI9PXR5cGVvZiBkb2N1bWVudClyZXR1cm4gbnVsbDtpZihcImN1cnJlbnRTY3JpcHRcImluIGRvY3VtZW50KXJldHVybiBkb2N1bWVudC5jdXJyZW50U2NyaXB0O3RyeXt0aHJvdyBuZXcgRXJyb3J9Y2F0Y2goZSl7dmFyIHI9KC9hdCBbXihcXHJcXG5dKlxcKCguKik6Lis6LitcXCkkL2kuZXhlYyhlLnN0YWNrKXx8W10pWzFdO2lmKHIpe3ZhciBuPWRvY3VtZW50LmdldEVsZW1lbnRzQnlUYWdOYW1lKFwic2NyaXB0XCIpO2Zvcih2YXIgdCBpbiBuKWlmKG5bdF0uc3JjPT1yKXJldHVybiBuW3RdfXJldHVybiBudWxsfX19LGxhbmd1YWdlczp7ZXh0ZW5kOmZ1bmN0aW9uKGUscil7dmFyIG49Xy51dGlsLmNsb25lKF8ubGFuZ3VhZ2VzW2VdKTtmb3IodmFyIHQgaW4gciluW3RdPXJbdF07cmV0dXJuIG59LGluc2VydEJlZm9yZTpmdW5jdGlvbihuLGUscix0KXt2YXIgYT0odD10fHxfLmxhbmd1YWdlcylbbl0saT17fTtmb3IodmFyIG8gaW4gYSlpZihhLmhhc093blByb3BlcnR5KG8pKXtpZihvPT1lKWZvcih2YXIgbCBpbiByKXIuaGFzT3duUHJvcGVydHkobCkmJihpW2xdPXJbbF0pO3IuaGFzT3duUHJvcGVydHkobyl8fChpW29dPWFbb10pfXZhciBzPXRbbl07cmV0dXJuIHRbbl09aSxfLmxhbmd1YWdlcy5ERlMoXy5sYW5ndWFnZXMsZnVuY3Rpb24oZSxyKXtyPT09cyYmZSE9biYmKHRoaXNbZV09aSl9KSxpfSxERlM6ZnVuY3Rpb24gZShyLG4sdCxhKXthPWF8fHt9O3ZhciBpPV8udXRpbC5vYmpJZDtmb3IodmFyIG8gaW4gcilpZihyLmhhc093blByb3BlcnR5KG8pKXtuLmNhbGwocixvLHJbb10sdHx8byk7dmFyIGw9cltvXSxzPV8udXRpbC50eXBlKGwpO1wiT2JqZWN0XCIhPT1zfHxhW2kobCldP1wiQXJyYXlcIiE9PXN8fGFbaShsKV18fChhW2kobCldPSEwLGUobCxuLG8sYSkpOihhW2kobCldPSEwLGUobCxuLG51bGwsYSkpfX19LHBsdWdpbnM6e30saGlnaGxpZ2h0QWxsOmZ1bmN0aW9uKGUscil7Xy5oaWdobGlnaHRBbGxVbmRlcihkb2N1bWVudCxlLHIpfSxoaWdobGlnaHRBbGxVbmRlcjpmdW5jdGlvbihlLHIsbil7dmFyIHQ9e2NhbGxiYWNrOm4sc2VsZWN0b3I6J2NvZGVbY2xhc3MqPVwibGFuZ3VhZ2UtXCJdLCBbY2xhc3MqPVwibGFuZ3VhZ2UtXCJdIGNvZGUsIGNvZGVbY2xhc3MqPVwibGFuZy1cIl0sIFtjbGFzcyo9XCJsYW5nLVwiXSBjb2RlJ307Xy5ob29rcy5ydW4oXCJiZWZvcmUtaGlnaGxpZ2h0YWxsXCIsdCk7Zm9yKHZhciBhLGk9ZS5xdWVyeVNlbGVjdG9yQWxsKHQuc2VsZWN0b3IpLG89MDthPWlbbysrXTspXy5oaWdobGlnaHRFbGVtZW50KGEsITA9PT1yLHQuY2FsbGJhY2spfSxoaWdobGlnaHRFbGVtZW50OmZ1bmN0aW9uKGUscixuKXt2YXIgdD1mdW5jdGlvbihlKXtmb3IoO2UmJiFjLnRlc3QoZS5jbGFzc05hbWUpOyllPWUucGFyZW50Tm9kZTtyZXR1cm4gZT8oZS5jbGFzc05hbWUubWF0Y2goYyl8fFssXCJub25lXCJdKVsxXS50b0xvd2VyQ2FzZSgpOlwibm9uZVwifShlKSxhPV8ubGFuZ3VhZ2VzW3RdO2UuY2xhc3NOYW1lPWUuY2xhc3NOYW1lLnJlcGxhY2UoYyxcIlwiKS5yZXBsYWNlKC9cXHMrL2csXCIgXCIpK1wiIGxhbmd1YWdlLVwiK3Q7dmFyIGk9ZS5wYXJlbnROb2RlO2kmJlwicHJlXCI9PT1pLm5vZGVOYW1lLnRvTG93ZXJDYXNlKCkmJihpLmNsYXNzTmFtZT1pLmNsYXNzTmFtZS5yZXBsYWNlKGMsXCJcIikucmVwbGFjZSgvXFxzKy9nLFwiIFwiKStcIiBsYW5ndWFnZS1cIit0KTt2YXIgbz17ZWxlbWVudDplLGxhbmd1YWdlOnQsZ3JhbW1hcjphLGNvZGU6ZS50ZXh0Q29udGVudH07ZnVuY3Rpb24gbChlKXtvLmhpZ2hsaWdodGVkQ29kZT1lLF8uaG9va3MucnVuKFwiYmVmb3JlLWluc2VydFwiLG8pLG8uZWxlbWVudC5pbm5lckhUTUw9by5oaWdobGlnaHRlZENvZGUsXy5ob29rcy5ydW4oXCJhZnRlci1oaWdobGlnaHRcIixvKSxfLmhvb2tzLnJ1bihcImNvbXBsZXRlXCIsbyksbiYmbi5jYWxsKG8uZWxlbWVudCl9aWYoXy5ob29rcy5ydW4oXCJiZWZvcmUtc2FuaXR5LWNoZWNrXCIsbyksIW8uY29kZSlyZXR1cm4gXy5ob29rcy5ydW4oXCJjb21wbGV0ZVwiLG8pLHZvaWQobiYmbi5jYWxsKG8uZWxlbWVudCkpO2lmKF8uaG9va3MucnVuKFwiYmVmb3JlLWhpZ2hsaWdodFwiLG8pLG8uZ3JhbW1hcilpZihyJiZ1Lldvcmtlcil7dmFyIHM9bmV3IFdvcmtlcihfLmZpbGVuYW1lKTtzLm9ubWVzc2FnZT1mdW5jdGlvbihlKXtsKGUuZGF0YSl9LHMucG9zdE1lc3NhZ2UoSlNPTi5zdHJpbmdpZnkoe2xhbmd1YWdlOm8ubGFuZ3VhZ2UsY29kZTpvLmNvZGUsaW1tZWRpYXRlQ2xvc2U6ITB9KSl9ZWxzZSBsKF8uaGlnaGxpZ2h0KG8uY29kZSxvLmdyYW1tYXIsby5sYW5ndWFnZSkpO2Vsc2UgbChfLnV0aWwuZW5jb2RlKG8uY29kZSkpfSxoaWdobGlnaHQ6ZnVuY3Rpb24oZSxyLG4pe3ZhciB0PXtjb2RlOmUsZ3JhbW1hcjpyLGxhbmd1YWdlOm59O3JldHVybiBfLmhvb2tzLnJ1bihcImJlZm9yZS10b2tlbml6ZVwiLHQpLHQudG9rZW5zPV8udG9rZW5pemUodC5jb2RlLHQuZ3JhbW1hciksXy5ob29rcy5ydW4oXCJhZnRlci10b2tlbml6ZVwiLHQpLEwuc3RyaW5naWZ5KF8udXRpbC5lbmNvZGUodC50b2tlbnMpLHQubGFuZ3VhZ2UpfSxtYXRjaEdyYW1tYXI6ZnVuY3Rpb24oZSxyLG4sdCxhLGksbyl7Zm9yKHZhciBsIGluIG4paWYobi5oYXNPd25Qcm9wZXJ0eShsKSYmbltsXSl7dmFyIHM9bltsXTtzPUFycmF5LmlzQXJyYXkocyk/czpbc107Zm9yKHZhciB1PTA7dTxzLmxlbmd0aDsrK3Upe2lmKG8mJm89PWwrXCIsXCIrdSlyZXR1cm47dmFyIGM9c1t1XSxnPWMuaW5zaWRlLGY9ISFjLmxvb2tiZWhpbmQsZD0hIWMuZ3JlZWR5LGg9MCxtPWMuYWxpYXM7aWYoZCYmIWMucGF0dGVybi5nbG9iYWwpe3ZhciBwPWMucGF0dGVybi50b1N0cmluZygpLm1hdGNoKC9baW1zdXldKiQvKVswXTtjLnBhdHRlcm49UmVnRXhwKGMucGF0dGVybi5zb3VyY2UscCtcImdcIil9Yz1jLnBhdHRlcm58fGM7Zm9yKHZhciB5PXQsdj1hO3k8ci5sZW5ndGg7dis9clt5XS5sZW5ndGgsKyt5KXt2YXIgaz1yW3ldO2lmKHIubGVuZ3RoPmUubGVuZ3RoKXJldHVybjtpZighKGsgaW5zdGFuY2VvZiBMKSl7aWYoZCYmeSE9ci5sZW5ndGgtMSl7aWYoYy5sYXN0SW5kZXg9diwhKE89Yy5leGVjKGUpKSlicmVhaztmb3IodmFyIGI9Ty5pbmRleCsoZiYmT1sxXT9PWzFdLmxlbmd0aDowKSx3PU8uaW5kZXgrT1swXS5sZW5ndGgsQT15LFA9dix4PXIubGVuZ3RoO0E8eCYmKFA8d3x8IXJbQV0udHlwZSYmIXJbQS0xXS5ncmVlZHkpOysrQSkoUCs9cltBXS5sZW5ndGgpPD1iJiYoKyt5LHY9UCk7aWYoclt5XWluc3RhbmNlb2YgTCljb250aW51ZTtTPUEteSxrPWUuc2xpY2UodixQKSxPLmluZGV4LT12fWVsc2V7Yy5sYXN0SW5kZXg9MDt2YXIgTz1jLmV4ZWMoayksUz0xfWlmKE8pe2YmJihoPU9bMV0/T1sxXS5sZW5ndGg6MCk7dz0oYj1PLmluZGV4K2gpKyhPPU9bMF0uc2xpY2UoaCkpLmxlbmd0aDt2YXIgaj1rLnNsaWNlKDAsYiksTj1rLnNsaWNlKHcpLEU9W3ksU107aiYmKCsreSx2Kz1qLmxlbmd0aCxFLnB1c2goaikpO3ZhciBDPW5ldyBMKGwsZz9fLnRva2VuaXplKE8sZyk6TyxtLE8sZCk7aWYoRS5wdXNoKEMpLE4mJkUucHVzaChOKSxBcnJheS5wcm90b3R5cGUuc3BsaWNlLmFwcGx5KHIsRSksMSE9UyYmXy5tYXRjaEdyYW1tYXIoZSxyLG4seSx2LCEwLGwrXCIsXCIrdSksaSlicmVha31lbHNlIGlmKGkpYnJlYWt9fX19fSx0b2tlbml6ZTpmdW5jdGlvbihlLHIpe3ZhciBuPVtlXSx0PXIucmVzdDtpZih0KXtmb3IodmFyIGEgaW4gdClyW2FdPXRbYV07ZGVsZXRlIHIucmVzdH1yZXR1cm4gXy5tYXRjaEdyYW1tYXIoZSxuLHIsMCwwLCExKSxufSxob29rczp7YWxsOnt9LGFkZDpmdW5jdGlvbihlLHIpe3ZhciBuPV8uaG9va3MuYWxsO25bZV09bltlXXx8W10sbltlXS5wdXNoKHIpfSxydW46ZnVuY3Rpb24oZSxyKXt2YXIgbj1fLmhvb2tzLmFsbFtlXTtpZihuJiZuLmxlbmd0aClmb3IodmFyIHQsYT0wO3Q9blthKytdOyl0KHIpfX0sVG9rZW46TH07ZnVuY3Rpb24gTChlLHIsbix0LGEpe3RoaXMudHlwZT1lLHRoaXMuY29udGVudD1yLHRoaXMuYWxpYXM9bix0aGlzLmxlbmd0aD0wfCh0fHxcIlwiKS5sZW5ndGgsdGhpcy5ncmVlZHk9ISFhfWlmKHUuUHJpc209XyxMLnN0cmluZ2lmeT1mdW5jdGlvbihlLHIpe2lmKFwic3RyaW5nXCI9PXR5cGVvZiBlKXJldHVybiBlO2lmKEFycmF5LmlzQXJyYXkoZSkpcmV0dXJuIGUubWFwKGZ1bmN0aW9uKGUpe3JldHVybiBMLnN0cmluZ2lmeShlLHIpfSkuam9pbihcIlwiKTt2YXIgbj17dHlwZTplLnR5cGUsY29udGVudDpMLnN0cmluZ2lmeShlLmNvbnRlbnQsciksdGFnOlwic3BhblwiLGNsYXNzZXM6W1widG9rZW5cIixlLnR5cGVdLGF0dHJpYnV0ZXM6e30sbGFuZ3VhZ2U6cn07aWYoZS5hbGlhcyl7dmFyIHQ9QXJyYXkuaXNBcnJheShlLmFsaWFzKT9lLmFsaWFzOltlLmFsaWFzXTtBcnJheS5wcm90b3R5cGUucHVzaC5hcHBseShuLmNsYXNzZXMsdCl9Xy5ob29rcy5ydW4oXCJ3cmFwXCIsbik7dmFyIGE9T2JqZWN0LmtleXMobi5hdHRyaWJ1dGVzKS5tYXAoZnVuY3Rpb24oZSl7cmV0dXJuIGUrJz1cIicrKG4uYXR0cmlidXRlc1tlXXx8XCJcIikucmVwbGFjZSgvXCIvZyxcIiZxdW90O1wiKSsnXCInfSkuam9pbihcIiBcIik7cmV0dXJuXCI8XCIrbi50YWcrJyBjbGFzcz1cIicrbi5jbGFzc2VzLmpvaW4oXCIgXCIpKydcIicrKGE/XCIgXCIrYTpcIlwiKStcIj5cIituLmNvbnRlbnQrXCI8L1wiK24udGFnK1wiPlwifSwhdS5kb2N1bWVudClyZXR1cm4gdS5hZGRFdmVudExpc3RlbmVyJiYoXy5kaXNhYmxlV29ya2VyTWVzc2FnZUhhbmRsZXJ8fHUuYWRkRXZlbnRMaXN0ZW5lcihcIm1lc3NhZ2VcIixmdW5jdGlvbihlKXt2YXIgcj1KU09OLnBhcnNlKGUuZGF0YSksbj1yLmxhbmd1YWdlLHQ9ci5jb2RlLGE9ci5pbW1lZGlhdGVDbG9zZTt1LnBvc3RNZXNzYWdlKF8uaGlnaGxpZ2h0KHQsXy5sYW5ndWFnZXNbbl0sbikpLGEmJnUuY2xvc2UoKX0sITEpKSxfO3ZhciBlPV8udXRpbC5jdXJyZW50U2NyaXB0KCk7aWYoZSYmKF8uZmlsZW5hbWU9ZS5zcmMsZS5oYXNBdHRyaWJ1dGUoXCJkYXRhLW1hbnVhbFwiKSYmKF8ubWFudWFsPSEwKSksIV8ubWFudWFsKXtmdW5jdGlvbiBuKCl7Xy5tYW51YWx8fF8uaGlnaGxpZ2h0QWxsKCl9dmFyIHQ9ZG9jdW1lbnQucmVhZHlTdGF0ZTtcImxvYWRpbmdcIj09PXR8fFwiaW50ZXJhY3RpdmVcIj09PXQmJmUuZGVmZXI/ZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcihcIkRPTUNvbnRlbnRMb2FkZWRcIixuKTp3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lP3dpbmRvdy5yZXF1ZXN0QW5pbWF0aW9uRnJhbWUobik6d2luZG93LnNldFRpbWVvdXQobiwxNil9cmV0dXJuIF99KF9zZWxmKTtcInVuZGVmaW5lZFwiIT10eXBlb2YgbW9kdWxlJiZtb2R1bGUuZXhwb3J0cyYmKG1vZHVsZS5leHBvcnRzPVByaXNtKSxcInVuZGVmaW5lZFwiIT10eXBlb2YgZ2xvYmFsJiYoZ2xvYmFsLlByaXNtPVByaXNtKTtcblByaXNtLmxhbmd1YWdlcy5tYXJrdXA9e2NvbW1lbnQ6LzwhLS1bXFxzXFxTXSo/LS0+Lyxwcm9sb2c6LzxcXD9bXFxzXFxTXSs/XFw/Pi8sZG9jdHlwZTp7cGF0dGVybjovPCFET0NUWVBFKD86W14+XCInW1xcXV18XCJbXlwiXSpcInwnW14nXSonKSsoPzpcXFsoPzooPyE8IS0tKVteXCInXFxdXXxcIlteXCJdKlwifCdbXiddKid8PCEtLVtcXHNcXFNdKj8tLT4pKlxcXVxccyopPz4vaSxncmVlZHk6ITB9LGNkYXRhOi88IVxcW0NEQVRBXFxbW1xcc1xcU10qP11dPi9pLHRhZzp7cGF0dGVybjovPFxcLz8oPyFcXGQpW15cXHM+XFwvPSQ8JV0rKD86XFxzKD86XFxzKlteXFxzPlxcLz1dKyg/Olxccyo9XFxzKig/OlwiW15cIl0qXCJ8J1teJ10qJ3xbXlxccydcIj49XSsoPz1bXFxzPl0pKXwoPz1bXFxzLz5dKSkpKyk/XFxzKlxcLz8+L2ksZ3JlZWR5OiEwLGluc2lkZTp7dGFnOntwYXR0ZXJuOi9ePFxcLz9bXlxccz5cXC9dKy9pLGluc2lkZTp7cHVuY3R1YXRpb246L148XFwvPy8sbmFtZXNwYWNlOi9eW15cXHM+XFwvOl0rOi99fSxcImF0dHItdmFsdWVcIjp7cGF0dGVybjovPVxccyooPzpcIlteXCJdKlwifCdbXiddKid8W15cXHMnXCI+PV0rKS9pLGluc2lkZTp7cHVuY3R1YXRpb246Wy9ePS8se3BhdHRlcm46L14oXFxzKilbXCInXXxbXCInXSQvLGxvb2tiZWhpbmQ6ITB9XX19LHB1bmN0dWF0aW9uOi9cXC8/Pi8sXCJhdHRyLW5hbWVcIjp7cGF0dGVybjovW15cXHM+XFwvXSsvLGluc2lkZTp7bmFtZXNwYWNlOi9eW15cXHM+XFwvOl0rOi99fX19LGVudGl0eTovJiM/W1xcZGEtel17MSw4fTsvaX0sUHJpc20ubGFuZ3VhZ2VzLm1hcmt1cC50YWcuaW5zaWRlW1wiYXR0ci12YWx1ZVwiXS5pbnNpZGUuZW50aXR5PVByaXNtLmxhbmd1YWdlcy5tYXJrdXAuZW50aXR5LFByaXNtLmhvb2tzLmFkZChcIndyYXBcIixmdW5jdGlvbihhKXtcImVudGl0eVwiPT09YS50eXBlJiYoYS5hdHRyaWJ1dGVzLnRpdGxlPWEuY29udGVudC5yZXBsYWNlKC8mYW1wOy8sXCImXCIpKX0pLE9iamVjdC5kZWZpbmVQcm9wZXJ0eShQcmlzbS5sYW5ndWFnZXMubWFya3VwLnRhZyxcImFkZElubGluZWRcIix7dmFsdWU6ZnVuY3Rpb24oYSxlKXt2YXIgcz17fTtzW1wibGFuZ3VhZ2UtXCIrZV09e3BhdHRlcm46LyhePCFcXFtDREFUQVxcWylbXFxzXFxTXSs/KD89XFxdXFxdPiQpL2ksbG9va2JlaGluZDohMCxpbnNpZGU6UHJpc20ubGFuZ3VhZ2VzW2VdfSxzLmNkYXRhPS9ePCFcXFtDREFUQVxcW3xcXF1cXF0+JC9pO3ZhciBuPXtcImluY2x1ZGVkLWNkYXRhXCI6e3BhdHRlcm46LzwhXFxbQ0RBVEFcXFtbXFxzXFxTXSo/XFxdXFxdPi9pLGluc2lkZTpzfX07bltcImxhbmd1YWdlLVwiK2VdPXtwYXR0ZXJuOi9bXFxzXFxTXSsvLGluc2lkZTpQcmlzbS5sYW5ndWFnZXNbZV19O3ZhciB0PXt9O3RbYV09e3BhdHRlcm46UmVnRXhwKFwiKDxfX1tcXFxcc1xcXFxTXSo/PikoPzo8IVxcXFxbQ0RBVEFcXFxcW1tcXFxcc1xcXFxTXSo/XFxcXF1cXFxcXT5cXFxccyp8W1xcXFxzXFxcXFNdKSo/KD89PFxcXFwvX18+KVwiLnJlcGxhY2UoL19fL2csYSksXCJpXCIpLGxvb2tiZWhpbmQ6ITAsZ3JlZWR5OiEwLGluc2lkZTpufSxQcmlzbS5sYW5ndWFnZXMuaW5zZXJ0QmVmb3JlKFwibWFya3VwXCIsXCJjZGF0YVwiLHQpfX0pLFByaXNtLmxhbmd1YWdlcy54bWw9UHJpc20ubGFuZ3VhZ2VzLmV4dGVuZChcIm1hcmt1cFwiLHt9KSxQcmlzbS5sYW5ndWFnZXMuaHRtbD1QcmlzbS5sYW5ndWFnZXMubWFya3VwLFByaXNtLmxhbmd1YWdlcy5tYXRobWw9UHJpc20ubGFuZ3VhZ2VzLm1hcmt1cCxQcmlzbS5sYW5ndWFnZXMuc3ZnPVByaXNtLmxhbmd1YWdlcy5tYXJrdXA7XG4hZnVuY3Rpb24ocyl7dmFyIHQ9LyhcInwnKSg/OlxcXFwoPzpcXHJcXG58W1xcc1xcU10pfCg/IVxcMSlbXlxcXFxcXHJcXG5dKSpcXDEvO3MubGFuZ3VhZ2VzLmNzcz17Y29tbWVudDovXFwvXFwqW1xcc1xcU10qP1xcKlxcLy8sYXRydWxlOntwYXR0ZXJuOi9AW1xcdy1dK1tcXHNcXFNdKj8oPzo7fCg/PVxccypcXHspKS8saW5zaWRlOntydWxlOi9AW1xcdy1dKy99fSx1cmw6e3BhdHRlcm46UmVnRXhwKFwidXJsXFxcXCgoPzpcIit0LnNvdXJjZStcInxbXlxcblxccigpXSopXFxcXClcIixcImlcIiksaW5zaWRlOntmdW5jdGlvbjovXnVybC9pLHB1bmN0dWF0aW9uOi9eXFwofFxcKSQvfX0sc2VsZWN0b3I6UmVnRXhwKFwiW157fVxcXFxzXSg/Oltee307XFxcIiddfFwiK3Quc291cmNlK1wiKSo/KD89XFxcXHMqXFxcXHspXCIpLHN0cmluZzp7cGF0dGVybjp0LGdyZWVkeTohMH0scHJvcGVydHk6L1stX2EtelxceEEwLVxcdUZGRkZdWy1cXHdcXHhBMC1cXHVGRkZGXSooPz1cXHMqOikvaSxpbXBvcnRhbnQ6LyFpbXBvcnRhbnRcXGIvaSxmdW5jdGlvbjovWy1hLXowLTldKyg/PVxcKCkvaSxwdW5jdHVhdGlvbjovWygpe307OixdL30scy5sYW5ndWFnZXMuY3NzLmF0cnVsZS5pbnNpZGUucmVzdD1zLmxhbmd1YWdlcy5jc3M7dmFyIGU9cy5sYW5ndWFnZXMubWFya3VwO2UmJihlLnRhZy5hZGRJbmxpbmVkKFwic3R5bGVcIixcImNzc1wiKSxzLmxhbmd1YWdlcy5pbnNlcnRCZWZvcmUoXCJpbnNpZGVcIixcImF0dHItdmFsdWVcIix7XCJzdHlsZS1hdHRyXCI6e3BhdHRlcm46L1xccypzdHlsZT0oXCJ8JykoPzpcXFxcW1xcc1xcU118KD8hXFwxKVteXFxcXF0pKlxcMS9pLGluc2lkZTp7XCJhdHRyLW5hbWVcIjp7cGF0dGVybjovXlxccypzdHlsZS9pLGluc2lkZTplLnRhZy5pbnNpZGV9LHB1bmN0dWF0aW9uOi9eXFxzKj1cXHMqWydcIl18WydcIl1cXHMqJC8sXCJhdHRyLXZhbHVlXCI6e3BhdHRlcm46Ly4rL2ksaW5zaWRlOnMubGFuZ3VhZ2VzLmNzc319LGFsaWFzOlwibGFuZ3VhZ2UtY3NzXCJ9fSxlLnRhZykpfShQcmlzbSk7XG5QcmlzbS5sYW5ndWFnZXMuY2xpa2U9e2NvbW1lbnQ6W3twYXR0ZXJuOi8oXnxbXlxcXFxdKVxcL1xcKltcXHNcXFNdKj8oPzpcXCpcXC98JCkvLGxvb2tiZWhpbmQ6ITB9LHtwYXR0ZXJuOi8oXnxbXlxcXFw6XSlcXC9cXC8uKi8sbG9va2JlaGluZDohMCxncmVlZHk6ITB9XSxzdHJpbmc6e3BhdHRlcm46LyhbXCInXSkoPzpcXFxcKD86XFxyXFxufFtcXHNcXFNdKXwoPyFcXDEpW15cXFxcXFxyXFxuXSkqXFwxLyxncmVlZHk6ITB9LFwiY2xhc3MtbmFtZVwiOntwYXR0ZXJuOi8oXFxiKD86Y2xhc3N8aW50ZXJmYWNlfGV4dGVuZHN8aW1wbGVtZW50c3x0cmFpdHxpbnN0YW5jZW9mfG5ldylcXHMrfFxcYmNhdGNoXFxzK1xcKClbXFx3LlxcXFxdKy9pLGxvb2tiZWhpbmQ6ITAsaW5zaWRlOntwdW5jdHVhdGlvbjovWy5cXFxcXS99fSxrZXl3b3JkOi9cXGIoPzppZnxlbHNlfHdoaWxlfGRvfGZvcnxyZXR1cm58aW58aW5zdGFuY2VvZnxmdW5jdGlvbnxuZXd8dHJ5fHRocm93fGNhdGNofGZpbmFsbHl8bnVsbHxicmVha3xjb250aW51ZSlcXGIvLGJvb2xlYW46L1xcYig/OnRydWV8ZmFsc2UpXFxiLyxmdW5jdGlvbjovXFx3Kyg/PVxcKCkvLG51bWJlcjovXFxiMHhbXFxkYS1mXStcXGJ8KD86XFxiXFxkK1xcLj9cXGQqfFxcQlxcLlxcZCspKD86ZVsrLV0/XFxkKyk/L2ksb3BlcmF0b3I6L1s8Pl09P3xbIT1dPT89P3wtLT98XFwrXFwrP3wmJj98XFx8XFx8P3xbPyovfl4lXS8scHVuY3R1YXRpb246L1t7fVtcXF07KCksLjpdL307XG5QcmlzbS5sYW5ndWFnZXMuamF2YXNjcmlwdD1QcmlzbS5sYW5ndWFnZXMuZXh0ZW5kKFwiY2xpa2VcIix7XCJjbGFzcy1uYW1lXCI6W1ByaXNtLmxhbmd1YWdlcy5jbGlrZVtcImNsYXNzLW5hbWVcIl0se3BhdHRlcm46LyhefFteJFxcd1xceEEwLVxcdUZGRkZdKVtfJEEtWlxceEEwLVxcdUZGRkZdWyRcXHdcXHhBMC1cXHVGRkZGXSooPz1cXC4oPzpwcm90b3R5cGV8Y29uc3RydWN0b3IpKS8sbG9va2JlaGluZDohMH1dLGtleXdvcmQ6W3twYXR0ZXJuOi8oKD86Xnx9KVxccyopKD86Y2F0Y2h8ZmluYWxseSlcXGIvLGxvb2tiZWhpbmQ6ITB9LHtwYXR0ZXJuOi8oXnxbXi5dKVxcYig/OmFzfGFzeW5jKD89XFxzKig/OmZ1bmN0aW9uXFxifFxcKHxbJFxcd1xceEEwLVxcdUZGRkZdfCQpKXxhd2FpdHxicmVha3xjYXNlfGNsYXNzfGNvbnN0fGNvbnRpbnVlfGRlYnVnZ2VyfGRlZmF1bHR8ZGVsZXRlfGRvfGVsc2V8ZW51bXxleHBvcnR8ZXh0ZW5kc3xmb3J8ZnJvbXxmdW5jdGlvbnxnZXR8aWZ8aW1wbGVtZW50c3xpbXBvcnR8aW58aW5zdGFuY2VvZnxpbnRlcmZhY2V8bGV0fG5ld3xudWxsfG9mfHBhY2thZ2V8cHJpdmF0ZXxwcm90ZWN0ZWR8cHVibGljfHJldHVybnxzZXR8c3RhdGljfHN1cGVyfHN3aXRjaHx0aGlzfHRocm93fHRyeXx0eXBlb2Z8dW5kZWZpbmVkfHZhcnx2b2lkfHdoaWxlfHdpdGh8eWllbGQpXFxiLyxsb29rYmVoaW5kOiEwfV0sbnVtYmVyOi9cXGIoPzooPzowW3hYXSg/OltcXGRBLUZhLWZdKD86X1tcXGRBLUZhLWZdKT8pK3wwW2JCXSg/OlswMV0oPzpfWzAxXSk/KSt8MFtvT10oPzpbMC03XSg/Ol9bMC03XSk/KSspbj98KD86XFxkKD86X1xcZCk/KStufE5hTnxJbmZpbml0eSlcXGJ8KD86XFxiKD86XFxkKD86X1xcZCk/KStcXC4/KD86XFxkKD86X1xcZCk/KSp8XFxCXFwuKD86XFxkKD86X1xcZCk/KSspKD86W0VlXVsrLV0/KD86XFxkKD86X1xcZCk/KSspPy8sZnVuY3Rpb246LyM/W18kYS16QS1aXFx4QTAtXFx1RkZGRl1bJFxcd1xceEEwLVxcdUZGRkZdKig/PVxccyooPzpcXC5cXHMqKD86YXBwbHl8YmluZHxjYWxsKVxccyopP1xcKCkvLG9wZXJhdG9yOi8tLXxcXCtcXCt8XFwqXFwqPT98PT58JiZ8XFx8XFx8fFshPV09PXw8PD0/fD4+Pj89P3xbLSsqLyUmfF4hPTw+XT0/fFxcLnszfXxcXD9bLj9dP3xbfjpdL30pLFByaXNtLmxhbmd1YWdlcy5qYXZhc2NyaXB0W1wiY2xhc3MtbmFtZVwiXVswXS5wYXR0ZXJuPS8oXFxiKD86Y2xhc3N8aW50ZXJmYWNlfGV4dGVuZHN8aW1wbGVtZW50c3xpbnN0YW5jZW9mfG5ldylcXHMrKVtcXHcuXFxcXF0rLyxQcmlzbS5sYW5ndWFnZXMuaW5zZXJ0QmVmb3JlKFwiamF2YXNjcmlwdFwiLFwia2V5d29yZFwiLHtyZWdleDp7cGF0dGVybjovKCg/Ol58W14kXFx3XFx4QTAtXFx1RkZGRi5cIidcXF0pXFxzXSlcXHMqKVxcLyg/OlxcWyg/OlteXFxdXFxcXFxcclxcbl18XFxcXC4pKl18XFxcXC58W14vXFxcXFxcW1xcclxcbl0pK1xcL1tnaW15dXNdezAsNn0oPz1cXHMqKD86JHxbXFxyXFxuLC47fSlcXF1dKSkvLGxvb2tiZWhpbmQ6ITAsZ3JlZWR5OiEwfSxcImZ1bmN0aW9uLXZhcmlhYmxlXCI6e3BhdHRlcm46LyM/W18kYS16QS1aXFx4QTAtXFx1RkZGRl1bJFxcd1xceEEwLVxcdUZGRkZdKig/PVxccypbPTpdXFxzKig/OmFzeW5jXFxzKik/KD86XFxiZnVuY3Rpb25cXGJ8KD86XFwoKD86W14oKV18XFwoW14oKV0qXFwpKSpcXCl8W18kYS16QS1aXFx4QTAtXFx1RkZGRl1bJFxcd1xceEEwLVxcdUZGRkZdKilcXHMqPT4pKS8sYWxpYXM6XCJmdW5jdGlvblwifSxwYXJhbWV0ZXI6W3twYXR0ZXJuOi8oZnVuY3Rpb24oPzpcXHMrW18kQS1aYS16XFx4QTAtXFx1RkZGRl1bJFxcd1xceEEwLVxcdUZGRkZdKik/XFxzKlxcKFxccyopKD8hXFxzKSg/OlteKCldfFxcKFteKCldKlxcKSkrPyg/PVxccypcXCkpLyxsb29rYmVoaW5kOiEwLGluc2lkZTpQcmlzbS5sYW5ndWFnZXMuamF2YXNjcmlwdH0se3BhdHRlcm46L1tfJGEtelxceEEwLVxcdUZGRkZdWyRcXHdcXHhBMC1cXHVGRkZGXSooPz1cXHMqPT4pL2ksaW5zaWRlOlByaXNtLmxhbmd1YWdlcy5qYXZhc2NyaXB0fSx7cGF0dGVybjovKFxcKFxccyopKD8hXFxzKSg/OlteKCldfFxcKFteKCldKlxcKSkrPyg/PVxccypcXClcXHMqPT4pLyxsb29rYmVoaW5kOiEwLGluc2lkZTpQcmlzbS5sYW5ndWFnZXMuamF2YXNjcmlwdH0se3BhdHRlcm46LygoPzpcXGJ8XFxzfF4pKD8hKD86YXN8YXN5bmN8YXdhaXR8YnJlYWt8Y2FzZXxjYXRjaHxjbGFzc3xjb25zdHxjb250aW51ZXxkZWJ1Z2dlcnxkZWZhdWx0fGRlbGV0ZXxkb3xlbHNlfGVudW18ZXhwb3J0fGV4dGVuZHN8ZmluYWxseXxmb3J8ZnJvbXxmdW5jdGlvbnxnZXR8aWZ8aW1wbGVtZW50c3xpbXBvcnR8aW58aW5zdGFuY2VvZnxpbnRlcmZhY2V8bGV0fG5ld3xudWxsfG9mfHBhY2thZ2V8cHJpdmF0ZXxwcm90ZWN0ZWR8cHVibGljfHJldHVybnxzZXR8c3RhdGljfHN1cGVyfHN3aXRjaHx0aGlzfHRocm93fHRyeXx0eXBlb2Z8dW5kZWZpbmVkfHZhcnx2b2lkfHdoaWxlfHdpdGh8eWllbGQpKD8hWyRcXHdcXHhBMC1cXHVGRkZGXSkpKD86W18kQS1aYS16XFx4QTAtXFx1RkZGRl1bJFxcd1xceEEwLVxcdUZGRkZdKlxccyopXFwoXFxzKikoPyFcXHMpKD86W14oKV18XFwoW14oKV0qXFwpKSs/KD89XFxzKlxcKVxccypcXHspLyxsb29rYmVoaW5kOiEwLGluc2lkZTpQcmlzbS5sYW5ndWFnZXMuamF2YXNjcmlwdH1dLGNvbnN0YW50Oi9cXGJbQS1aXSg/OltBLVpfXXxcXGR4PykqXFxiL30pLFByaXNtLmxhbmd1YWdlcy5pbnNlcnRCZWZvcmUoXCJqYXZhc2NyaXB0XCIsXCJzdHJpbmdcIix7XCJ0ZW1wbGF0ZS1zdHJpbmdcIjp7cGF0dGVybjovYCg/OlxcXFxbXFxzXFxTXXxcXCR7KD86W157fV18eyg/Oltee31dfHtbXn1dKn0pKn0pK318KD8hXFwkeylbXlxcXFxgXSkqYC8sZ3JlZWR5OiEwLGluc2lkZTp7XCJ0ZW1wbGF0ZS1wdW5jdHVhdGlvblwiOntwYXR0ZXJuOi9eYHxgJC8sYWxpYXM6XCJzdHJpbmdcIn0saW50ZXJwb2xhdGlvbjp7cGF0dGVybjovKCg/Ol58W15cXFxcXSkoPzpcXFxcezJ9KSopXFwkeyg/Oltee31dfHsoPzpbXnt9XXx7W159XSp9KSp9KSt9Lyxsb29rYmVoaW5kOiEwLGluc2lkZTp7XCJpbnRlcnBvbGF0aW9uLXB1bmN0dWF0aW9uXCI6e3BhdHRlcm46L15cXCR7fH0kLyxhbGlhczpcInB1bmN0dWF0aW9uXCJ9LHJlc3Q6UHJpc20ubGFuZ3VhZ2VzLmphdmFzY3JpcHR9fSxzdHJpbmc6L1tcXHNcXFNdKy99fX0pLFByaXNtLmxhbmd1YWdlcy5tYXJrdXAmJlByaXNtLmxhbmd1YWdlcy5tYXJrdXAudGFnLmFkZElubGluZWQoXCJzY3JpcHRcIixcImphdmFzY3JpcHRcIiksUHJpc20ubGFuZ3VhZ2VzLmpzPVByaXNtLmxhbmd1YWdlcy5qYXZhc2NyaXB0O1xuIWZ1bmN0aW9uKGgpe2Z1bmN0aW9uIHYoZSxuKXtyZXR1cm5cIl9fX1wiK2UudG9VcHBlckNhc2UoKStuK1wiX19fXCJ9T2JqZWN0LmRlZmluZVByb3BlcnRpZXMoaC5sYW5ndWFnZXNbXCJtYXJrdXAtdGVtcGxhdGluZ1wiXT17fSx7YnVpbGRQbGFjZWhvbGRlcnM6e3ZhbHVlOmZ1bmN0aW9uKGEscixlLG8pe2lmKGEubGFuZ3VhZ2U9PT1yKXt2YXIgYz1hLnRva2VuU3RhY2s9W107YS5jb2RlPWEuY29kZS5yZXBsYWNlKGUsZnVuY3Rpb24oZSl7aWYoXCJmdW5jdGlvblwiPT10eXBlb2YgbyYmIW8oZSkpcmV0dXJuIGU7Zm9yKHZhciBuLHQ9Yy5sZW5ndGg7LTEhPT1hLmNvZGUuaW5kZXhPZihuPXYocix0KSk7KSsrdDtyZXR1cm4gY1t0XT1lLG59KSxhLmdyYW1tYXI9aC5sYW5ndWFnZXMubWFya3VwfX19LHRva2VuaXplUGxhY2Vob2xkZXJzOnt2YWx1ZTpmdW5jdGlvbihwLGspe2lmKHAubGFuZ3VhZ2U9PT1rJiZwLnRva2VuU3RhY2spe3AuZ3JhbW1hcj1oLmxhbmd1YWdlc1trXTt2YXIgbT0wLGQ9T2JqZWN0LmtleXMocC50b2tlblN0YWNrKTshZnVuY3Rpb24gZShuKXtmb3IodmFyIHQ9MDt0PG4ubGVuZ3RoJiYhKG0+PWQubGVuZ3RoKTt0Kyspe3ZhciBhPW5bdF07aWYoXCJzdHJpbmdcIj09dHlwZW9mIGF8fGEuY29udGVudCYmXCJzdHJpbmdcIj09dHlwZW9mIGEuY29udGVudCl7dmFyIHI9ZFttXSxvPXAudG9rZW5TdGFja1tyXSxjPVwic3RyaW5nXCI9PXR5cGVvZiBhP2E6YS5jb250ZW50LGk9dihrLHIpLHU9Yy5pbmRleE9mKGkpO2lmKC0xPHUpeysrbTt2YXIgZz1jLnN1YnN0cmluZygwLHUpLGw9bmV3IGguVG9rZW4oayxoLnRva2VuaXplKG8scC5ncmFtbWFyKSxcImxhbmd1YWdlLVwiK2ssbykscz1jLnN1YnN0cmluZyh1K2kubGVuZ3RoKSxmPVtdO2cmJmYucHVzaC5hcHBseShmLGUoW2ddKSksZi5wdXNoKGwpLHMmJmYucHVzaC5hcHBseShmLGUoW3NdKSksXCJzdHJpbmdcIj09dHlwZW9mIGE/bi5zcGxpY2UuYXBwbHkobixbdCwxXS5jb25jYXQoZikpOmEuY29udGVudD1mfX1lbHNlIGEuY29udGVudCYmZShhLmNvbnRlbnQpfXJldHVybiBufShwLnRva2Vucyl9fX19KX0oUHJpc20pO1xuIWZ1bmN0aW9uKG4pe24ubGFuZ3VhZ2VzLnBocD1uLmxhbmd1YWdlcy5leHRlbmQoXCJjbGlrZVwiLHtrZXl3b3JkOi9cXGIoPzpfX2hhbHRfY29tcGlsZXJ8YWJzdHJhY3R8YW5kfGFycmF5fGFzfGJyZWFrfGNhbGxhYmxlfGNhc2V8Y2F0Y2h8Y2xhc3N8Y2xvbmV8Y29uc3R8Y29udGludWV8ZGVjbGFyZXxkZWZhdWx0fGRpZXxkb3xlY2hvfGVsc2V8ZWxzZWlmfGVtcHR5fGVuZGRlY2xhcmV8ZW5kZm9yfGVuZGZvcmVhY2h8ZW5kaWZ8ZW5kc3dpdGNofGVuZHdoaWxlfGV2YWx8ZXhpdHxleHRlbmRzfGZpbmFsfGZpbmFsbHl8Zm9yfGZvcmVhY2h8ZnVuY3Rpb258Z2xvYmFsfGdvdG98aWZ8aW1wbGVtZW50c3xpbmNsdWRlfGluY2x1ZGVfb25jZXxpbnN0YW5jZW9mfGluc3RlYWRvZnxpbnRlcmZhY2V8aXNzZXR8bGlzdHxuYW1lc3BhY2V8bmV3fG9yfHBhcmVudHxwcmludHxwcml2YXRlfHByb3RlY3RlZHxwdWJsaWN8cmVxdWlyZXxyZXF1aXJlX29uY2V8cmV0dXJufHN0YXRpY3xzd2l0Y2h8dGhyb3d8dHJhaXR8dHJ5fHVuc2V0fHVzZXx2YXJ8d2hpbGV8eG9yfHlpZWxkKVxcYi9pLGJvb2xlYW46e3BhdHRlcm46L1xcYig/OmZhbHNlfHRydWUpXFxiL2ksYWxpYXM6XCJjb25zdGFudFwifSxjb25zdGFudDpbL1xcYltBLVpfXVtBLVowLTlfXSpcXGIvLC9cXGIoPzpudWxsKVxcYi9pXSxjb21tZW50OntwYXR0ZXJuOi8oXnxbXlxcXFxdKSg/OlxcL1xcKltcXHNcXFNdKj9cXCpcXC98XFwvXFwvLiopLyxsb29rYmVoaW5kOiEwfX0pLG4ubGFuZ3VhZ2VzLmluc2VydEJlZm9yZShcInBocFwiLFwic3RyaW5nXCIse1wic2hlbGwtY29tbWVudFwiOntwYXR0ZXJuOi8oXnxbXlxcXFxdKSMuKi8sbG9va2JlaGluZDohMCxhbGlhczpcImNvbW1lbnRcIn19KSxuLmxhbmd1YWdlcy5pbnNlcnRCZWZvcmUoXCJwaHBcIixcImNvbW1lbnRcIix7ZGVsaW1pdGVyOntwYXR0ZXJuOi9cXD8+JHxePFxcPyg/OnBocCg/PVxccyl8PSk/L2ksYWxpYXM6XCJpbXBvcnRhbnRcIn19KSxuLmxhbmd1YWdlcy5pbnNlcnRCZWZvcmUoXCJwaHBcIixcImtleXdvcmRcIix7dmFyaWFibGU6L1xcJCsoPzpcXHcrXFxifCg/PXspKS9pLHBhY2thZ2U6e3BhdHRlcm46LyhcXFxcfG5hbWVzcGFjZVxccyt8dXNlXFxzKylbXFx3XFxcXF0rLyxsb29rYmVoaW5kOiEwLGluc2lkZTp7cHVuY3R1YXRpb246L1xcXFwvfX19KSxuLmxhbmd1YWdlcy5pbnNlcnRCZWZvcmUoXCJwaHBcIixcIm9wZXJhdG9yXCIse3Byb3BlcnR5OntwYXR0ZXJuOi8oLT4pW1xcd10rLyxsb29rYmVoaW5kOiEwfX0pO3ZhciBlPXtwYXR0ZXJuOi97XFwkKD86eyg/OntbXnt9XSt9fFtee31dKyl9fFtee31dKSt9fChefFteXFxcXHtdKVxcJCsoPzpcXHcrKD86XFxbLis/XXwtPlxcdyspKikvLGxvb2tiZWhpbmQ6ITAsaW5zaWRlOm4ubGFuZ3VhZ2VzLnBocH07bi5sYW5ndWFnZXMuaW5zZXJ0QmVmb3JlKFwicGhwXCIsXCJzdHJpbmdcIix7XCJub3dkb2Mtc3RyaW5nXCI6e3BhdHRlcm46Lzw8PCcoW14nXSspJyg/Olxcclxcbj98XFxuKSg/Oi4qKD86XFxyXFxuP3xcXG4pKSo/XFwxOy8sZ3JlZWR5OiEwLGFsaWFzOlwic3RyaW5nXCIsaW5zaWRlOntkZWxpbWl0ZXI6e3BhdHRlcm46L148PDwnW14nXSsnfFthLXpfXVxcdyo7JC9pLGFsaWFzOlwic3ltYm9sXCIsaW5zaWRlOntwdW5jdHVhdGlvbjovXjw8PCc/fFsnO10kL319fX0sXCJoZXJlZG9jLXN0cmluZ1wiOntwYXR0ZXJuOi88PDwoPzpcIihbXlwiXSspXCIoPzpcXHJcXG4/fFxcbikoPzouKig/Olxcclxcbj98XFxuKSkqP1xcMTt8KFthLXpfXVxcdyopKD86XFxyXFxuP3xcXG4pKD86LiooPzpcXHJcXG4/fFxcbikpKj9cXDI7KS9pLGdyZWVkeTohMCxhbGlhczpcInN0cmluZ1wiLGluc2lkZTp7ZGVsaW1pdGVyOntwYXR0ZXJuOi9ePDw8KD86XCJbXlwiXStcInxbYS16X11cXHcqKXxbYS16X11cXHcqOyQvaSxhbGlhczpcInN5bWJvbFwiLGluc2lkZTp7cHVuY3R1YXRpb246L148PDxcIj98W1wiO10kL319LGludGVycG9sYXRpb246ZX19LFwic2luZ2xlLXF1b3RlZC1zdHJpbmdcIjp7cGF0dGVybjovJyg/OlxcXFxbXFxzXFxTXXxbXlxcXFwnXSkqJy8sZ3JlZWR5OiEwLGFsaWFzOlwic3RyaW5nXCJ9LFwiZG91YmxlLXF1b3RlZC1zdHJpbmdcIjp7cGF0dGVybjovXCIoPzpcXFxcW1xcc1xcU118W15cXFxcXCJdKSpcIi8sZ3JlZWR5OiEwLGFsaWFzOlwic3RyaW5nXCIsaW5zaWRlOntpbnRlcnBvbGF0aW9uOmV9fX0pLGRlbGV0ZSBuLmxhbmd1YWdlcy5waHAuc3RyaW5nLG4uaG9va3MuYWRkKFwiYmVmb3JlLXRva2VuaXplXCIsZnVuY3Rpb24oZSl7aWYoLzxcXD8vLnRlc3QoZS5jb2RlKSl7bi5sYW5ndWFnZXNbXCJtYXJrdXAtdGVtcGxhdGluZ1wiXS5idWlsZFBsYWNlaG9sZGVycyhlLFwicGhwXCIsLzxcXD8oPzpbXlwiJy8jXXxcXC8oPyFbKi9dKXwoXCJ8JykoPzpcXFxcW1xcc1xcU118KD8hXFwxKVteXFxcXF0pKlxcMXwoPzpcXC9cXC98IykoPzpbXj9cXG5cXHJdfFxcPyg/IT4pKSp8XFwvXFwqW1xcc1xcU10qPyg/OlxcKlxcL3wkKSkqPyg/OlxcPz58JCkvZ2kpfX0pLG4uaG9va3MuYWRkKFwiYWZ0ZXItdG9rZW5pemVcIixmdW5jdGlvbihlKXtuLmxhbmd1YWdlc1tcIm1hcmt1cC10ZW1wbGF0aW5nXCJdLnRva2VuaXplUGxhY2Vob2xkZXJzKGUsXCJwaHBcIil9KX0oUHJpc20pO1xuIWZ1bmN0aW9uKHApe3ZhciBhPXAubGFuZ3VhZ2VzLmphdmFkb2NsaWtlPXtwYXJhbWV0ZXI6e3BhdHRlcm46LyheXFxzKig/OlxcL3szfXxcXCp8XFwvXFwqXFwqKVxccypAKD86cGFyYW18YXJnfGFyZ3VtZW50cylcXHMrKVxcdysvbSxsb29rYmVoaW5kOiEwfSxrZXl3b3JkOntwYXR0ZXJuOi8oXlxccyooPzpcXC97M318XFwqfFxcL1xcKlxcKilcXHMqfFxceylAW2Etel1bYS16QS1aLV0rXFxiL20sbG9va2JlaGluZDohMH0scHVuY3R1YXRpb246L1t7fV0vfTtPYmplY3QuZGVmaW5lUHJvcGVydHkoYSxcImFkZFN1cHBvcnRcIix7dmFsdWU6ZnVuY3Rpb24oYSxlKXtcInN0cmluZ1wiPT10eXBlb2YgYSYmKGE9W2FdKSxhLmZvckVhY2goZnVuY3Rpb24oYSl7IWZ1bmN0aW9uKGEsZSl7dmFyIG49XCJkb2MtY29tbWVudFwiLHQ9cC5sYW5ndWFnZXNbYV07aWYodCl7dmFyIHI9dFtuXTtpZighcil7dmFyIG89e1wiZG9jLWNvbW1lbnRcIjp7cGF0dGVybjovKF58W15cXFxcXSlcXC9cXCpcXCpbXi9dW1xcc1xcU10qPyg/OlxcKlxcL3wkKS8sbG9va2JlaGluZDohMCxhbGlhczpcImNvbW1lbnRcIn19O3I9KHQ9cC5sYW5ndWFnZXMuaW5zZXJ0QmVmb3JlKGEsXCJjb21tZW50XCIsbykpW25dfWlmKHIgaW5zdGFuY2VvZiBSZWdFeHAmJihyPXRbbl09e3BhdHRlcm46cn0pLEFycmF5LmlzQXJyYXkocikpZm9yKHZhciBpPTAscz1yLmxlbmd0aDtpPHM7aSsrKXJbaV1pbnN0YW5jZW9mIFJlZ0V4cCYmKHJbaV09e3BhdHRlcm46cltpXX0pLGUocltpXSk7ZWxzZSBlKHIpfX0oYSxmdW5jdGlvbihhKXthLmluc2lkZXx8KGEuaW5zaWRlPXt9KSxhLmluc2lkZS5yZXN0PWV9KX0pfX0pLGEuYWRkU3VwcG9ydChbXCJqYXZhXCIsXCJqYXZhc2NyaXB0XCIsXCJwaHBcIl0sYSl9KFByaXNtKTtcbiFmdW5jdGlvbihhKXt2YXIgZT1cIig/OlthLXpBLVpdXFxcXHcqfFt8XFxcXFxcXFxbXFxcXF1dKStcIjthLmxhbmd1YWdlcy5waHBkb2M9YS5sYW5ndWFnZXMuZXh0ZW5kKFwiamF2YWRvY2xpa2VcIix7cGFyYW1ldGVyOntwYXR0ZXJuOlJlZ0V4cChcIihAKD86Z2xvYmFsfHBhcmFtfHByb3BlcnR5KD86LXJlYWR8LXdyaXRlKT98dmFyKVxcXFxzKyg/OlwiK2UrXCJcXFxccyspPylcXFxcJFxcXFx3K1wiKSxsb29rYmVoaW5kOiEwfX0pLGEubGFuZ3VhZ2VzLmluc2VydEJlZm9yZShcInBocGRvY1wiLFwia2V5d29yZFwiLHtcImNsYXNzLW5hbWVcIjpbe3BhdHRlcm46UmVnRXhwKFwiKEAoPzpnbG9iYWx8cGFja2FnZXxwYXJhbXxwcm9wZXJ0eSg/Oi1yZWFkfC13cml0ZSk/fHJldHVybnxzdWJwYWNrYWdlfHRocm93c3x2YXIpXFxcXHMrKVwiK2UpLGxvb2tiZWhpbmQ6ITAsaW5zaWRlOntrZXl3b3JkOi9cXGIoPzpjYWxsYmFja3xyZXNvdXJjZXxib29sZWFufGludGVnZXJ8ZG91YmxlfG9iamVjdHxzdHJpbmd8YXJyYXl8ZmFsc2V8ZmxvYXR8bWl4ZWR8Ym9vbHxudWxsfHNlbGZ8dHJ1ZXx2b2lkfGludClcXGIvLHB1bmN0dWF0aW9uOi9bfFxcXFxbXFxdKCldL319XX0pLGEubGFuZ3VhZ2VzLmphdmFkb2NsaWtlLmFkZFN1cHBvcnQoXCJwaHBcIixhLmxhbmd1YWdlcy5waHBkb2MpfShQcmlzbSk7XG5QcmlzbS5sYW5ndWFnZXMuaW5zZXJ0QmVmb3JlKFwicGhwXCIsXCJ2YXJpYWJsZVwiLHt0aGlzOi9cXCR0aGlzXFxiLyxnbG9iYWw6L1xcJCg/Ol8oPzpTRVJWRVJ8R0VUfFBPU1R8RklMRVN8UkVRVUVTVHxTRVNTSU9OfEVOVnxDT09LSUUpfEdMT0JBTFN8SFRUUF9SQVdfUE9TVF9EQVRBfGFyZ2N8YXJndnxwaHBfZXJyb3Jtc2d8aHR0cF9yZXNwb25zZV9oZWFkZXIpXFxiLyxzY29wZTp7cGF0dGVybjovXFxiW1xcd1xcXFxdKzo6LyxpbnNpZGU6e2tleXdvcmQ6L3N0YXRpY3xzZWxmfHBhcmVudC8scHVuY3R1YXRpb246Lzo6fFxcXFwvfX19KTtcblByaXNtLmxhbmd1YWdlcy5zcWw9e2NvbW1lbnQ6e3BhdHRlcm46LyhefFteXFxcXF0pKD86XFwvXFwqW1xcc1xcU10qP1xcKlxcL3woPzotLXxcXC9cXC98IykuKikvLGxvb2tiZWhpbmQ6ITB9LHZhcmlhYmxlOlt7cGF0dGVybjovQChbXCInYF0pKD86XFxcXFtcXHNcXFNdfCg/IVxcMSlbXlxcXFxdKStcXDEvLGdyZWVkeTohMH0sL0BbXFx3LiRdKy9dLHN0cmluZzp7cGF0dGVybjovKF58W15AXFxcXF0pKFwifCcpKD86XFxcXFtcXHNcXFNdfCg/IVxcMilbXlxcXFxdfFxcMlxcMikqXFwyLyxncmVlZHk6ITAsbG9va2JlaGluZDohMH0sZnVuY3Rpb246L1xcYig/OkFWR3xDT1VOVHxGSVJTVHxGT1JNQVR8TEFTVHxMQ0FTRXxMRU58TUFYfE1JRHxNSU58TU9EfE5PV3xST1VORHxTVU18VUNBU0UpKD89XFxzKlxcKCkvaSxrZXl3b3JkOi9cXGIoPzpBQ1RJT058QUREfEFGVEVSfEFMR09SSVRITXxBTEx8QUxURVJ8QU5BTFlaRXxBTll8QVBQTFl8QVN8QVNDfEFVVEhPUklaQVRJT058QVVUT19JTkNSRU1FTlR8QkFDS1VQfEJEQnxCRUdJTnxCRVJLRUxFWURCfEJJR0lOVHxCSU5BUll8QklUfEJMT0J8Qk9PTHxCT09MRUFOfEJSRUFLfEJST1dTRXxCVFJFRXxCVUxLfEJZfENBTEx8Q0FTQ0FERUQ/fENBU0V8Q0hBSU58Q0hBUig/OkFDVEVSfFNFVCk/fENIRUNLKD86UE9JTlQpP3xDTE9TRXxDTFVTVEVSRUR8Q09BTEVTQ0V8Q09MTEFURXxDT0xVTU5TP3xDT01NRU5UfENPTU1JVCg/OlRFRCk/fENPTVBVVEV8Q09OTkVDVHxDT05TSVNURU5UfENPTlNUUkFJTlR8Q09OVEFJTlMoPzpUQUJMRSk/fENPTlRJTlVFfENPTlZFUlR8Q1JFQVRFfENST1NTfENVUlJFTlQoPzpfREFURXxfVElNRXxfVElNRVNUQU1QfF9VU0VSKT98Q1VSU09SfENZQ0xFfERBVEEoPzpCQVNFUz8pP3xEQVRFKD86VElNRSk/fERBWXxEQkNDfERFQUxMT0NBVEV8REVDfERFQ0lNQUx8REVDTEFSRXxERUZBVUxUfERFRklORVJ8REVMQVlFRHxERUxFVEV8REVMSU1JVEVSUz98REVOWXxERVNDfERFU0NSSUJFfERFVEVSTUlOSVNUSUN8RElTQUJMRXxESVNDQVJEfERJU0t8RElTVElOQ1R8RElTVElOQ1RST1d8RElTVFJJQlVURUR8RE98RE9VQkxFfERST1B8RFVNTVl8RFVNUCg/OkZJTEUpP3xEVVBMSUNBVEV8RUxTRSg/OklGKT98RU5BQkxFfEVOQ0xPU0VEfEVORHxFTkdJTkV8RU5VTXxFUlJMVkx8RVJST1JTfEVTQ0FQRUQ/fEVYQ0VQVHxFWEVDKD86VVRFKT98RVhJU1RTfEVYSVR8RVhQTEFJTnxFWFRFTkRFRHxGRVRDSHxGSUVMRFN8RklMRXxGSUxMRkFDVE9SfEZJUlNUfEZJWEVEfEZMT0FUfEZPTExPV0lOR3xGT1IoPzogRUFDSCBST1cpP3xGT1JDRXxGT1JFSUdOfEZSRUVURVhUKD86VEFCTEUpP3xGUk9NfEZVTEx8RlVOQ1RJT058R0VPTUVUUlkoPzpDT0xMRUNUSU9OKT98R0xPQkFMfEdPVE98R1JBTlR8R1JPVVB8SEFORExFUnxIQVNIfEhBVklOR3xIT0xETE9DS3xIT1VSfElERU5USVRZKD86X0lOU0VSVHxDT0wpP3xJRnxJR05PUkV8SU1QT1JUfElOREVYfElORklMRXxJTk5FUnxJTk5PREJ8SU5PVVR8SU5TRVJUfElOVHxJTlRFR0VSfElOVEVSU0VDVHxJTlRFUlZBTHxJTlRPfElOVk9LRVJ8SVNPTEFUSU9OfElURVJBVEV8Sk9JTnxLRVlTP3xLSUxMfExBTkdVQUdFfExBU1R8TEVBVkV8TEVGVHxMRVZFTHxMSU1JVHxMSU5FTk98TElORVN8TElORVNUUklOR3xMT0FEfExPQ0FMfExPQ0t8TE9ORyg/OkJMT0J8VEVYVCl8TE9PUHxNQVRDSCg/OkVEKT98TUVESVVNKD86QkxPQnxJTlR8VEVYVCl8TUVSR0V8TUlERExFSU5UfE1JTlVURXxNT0RFfE1PRElGSUVTfE1PRElGWXxNT05USHxNVUxUSSg/OkxJTkVTVFJJTkd8UE9JTlR8UE9MWUdPTil8TkFUSU9OQUx8TkFUVVJBTHxOQ0hBUnxORVhUfE5PfE5PTkNMVVNURVJFRHxOVUxMSUZ8TlVNRVJJQ3xPRkY/fE9GRlNFVFM/fE9OfE9QRU4oPzpEQVRBU09VUkNFfFFVRVJZfFJPV1NFVCk/fE9QVElNSVpFfE9QVElPTig/OkFMTFkpP3xPUkRFUnxPVVQoPzpFUnxGSUxFKT98T1ZFUnxQQVJUSUFMfFBBUlRJVElPTnxQRVJDRU5UfFBJVk9UfFBMQU58UE9JTlR8UE9MWUdPTnxQUkVDRURJTkd8UFJFQ0lTSU9OfFBSRVBBUkV8UFJFVnxQUklNQVJZfFBSSU5UfFBSSVZJTEVHRVN8UFJPQyg/OkVEVVJFKT98UFVCTElDfFBVUkdFfFFVSUNLfFJBSVNFUlJPUnxSRUFEUz98UkVBTHxSRUNPTkZJR1VSRXxSRUZFUkVOQ0VTfFJFTEVBU0V8UkVOQU1FfFJFUEVBVCg/OkFCTEUpP3xSRVBMQUNFfFJFUExJQ0FUSU9OfFJFUVVJUkV8UkVTSUdOQUx8UkVTVE9SRXxSRVNUUklDVHxSRVRVUk5TP3xSRVZPS0V8UklHSFR8Uk9MTEJBQ0t8Uk9VVElORXxST1coPzpDT1VOVHxHVUlEQ09MfFMpP3xSVFJFRXxSVUxFfFNBVkUoPzpQT0lOVCk/fFNDSEVNQXxTRUNPTkR8U0VMRUNUfFNFUklBTCg/OklaQUJMRSk/fFNFU1NJT04oPzpfVVNFUik/fFNFVCg/OlVTRVIpP3xTSEFSRXxTSE9XfFNIVVRET1dOfFNJTVBMRXxTTUFMTElOVHxTTkFQU0hPVHxTT01FfFNPTkFNRXxTUUx8U1RBUlQoPzpJTkcpP3xTVEFUSVNUSUNTfFNUQVRVU3xTVFJJUEVEfFNZU1RFTV9VU0VSfFRBQkxFUz98VEFCTEVTUEFDRXxURU1QKD86T1JBUll8VEFCTEUpP3xURVJNSU5BVEVEfFRFWFQoPzpTSVpFKT98VEhFTnxUSU1FKD86U1RBTVApP3xUSU5ZKD86QkxPQnxJTlR8VEVYVCl8VE9QP3xUUkFOKD86U0FDVElPTlM/KT98VFJJR0dFUnxUUlVOQ0FURXxUU0VRVUFMfFRZUEVTP3xVTkJPVU5ERUR8VU5DT01NSVRURUR8VU5ERUZJTkVEfFVOSU9OfFVOSVFVRXxVTkxPQ0t8VU5QSVZPVHxVTlNJR05FRHxVUERBVEUoPzpURVhUKT98VVNBR0V8VVNFfFVTRVJ8VVNJTkd8VkFMVUVTP3xWQVIoPzpCSU5BUll8Q0hBUnxDSEFSQUNURVJ8WUlORyl8VklFV3xXQUlURk9SfFdBUk5JTkdTfFdIRU58V0hFUkV8V0hJTEV8V0lUSCg/OiBST0xMVVB8SU4pP3xXT1JLfFdSSVRFKD86VEVYVCk/fFlFQVIpXFxiL2ksYm9vbGVhbjovXFxiKD86VFJVRXxGQUxTRXxOVUxMKVxcYi9pLG51bWJlcjovXFxiMHhbXFxkYS1mXStcXGJ8XFxiXFxkK1xcLj9cXGQqfFxcQlxcLlxcZCtcXGIvaSxvcGVyYXRvcjovWy0rKlxcLz0lXn5dfCYmP3xcXHxcXHw/fCE9P3w8KD86PT4/fDx8Pik/fD5bPj1dP3xcXGIoPzpBTkR8QkVUV0VFTnxJTnxMSUtFfE5PVHxPUnxJU3xESVZ8UkVHRVhQfFJMSUtFfFNPVU5EUyBMSUtFfFhPUilcXGIvaSxwdW5jdHVhdGlvbjovWztbXFxdKClgLC5dL307XG4hZnVuY3Rpb24oKXtpZihcInVuZGVmaW5lZFwiIT10eXBlb2Ygc2VsZiYmc2VsZi5QcmlzbSYmc2VsZi5kb2N1bWVudCl7dmFyIGw9XCJsaW5lLW51bWJlcnNcIixjPS9cXG4oPyEkKS9nLG09ZnVuY3Rpb24oZSl7dmFyIHQ9YShlKVtcIndoaXRlLXNwYWNlXCJdO2lmKFwicHJlLXdyYXBcIj09PXR8fFwicHJlLWxpbmVcIj09PXQpe3ZhciBuPWUucXVlcnlTZWxlY3RvcihcImNvZGVcIikscj1lLnF1ZXJ5U2VsZWN0b3IoXCIubGluZS1udW1iZXJzLXJvd3NcIikscz1lLnF1ZXJ5U2VsZWN0b3IoXCIubGluZS1udW1iZXJzLXNpemVyXCIpLGk9bi50ZXh0Q29udGVudC5zcGxpdChjKTtzfHwoKHM9ZG9jdW1lbnQuY3JlYXRlRWxlbWVudChcInNwYW5cIikpLmNsYXNzTmFtZT1cImxpbmUtbnVtYmVycy1zaXplclwiLG4uYXBwZW5kQ2hpbGQocykpLHMuc3R5bGUuZGlzcGxheT1cImJsb2NrXCIsaS5mb3JFYWNoKGZ1bmN0aW9uKGUsdCl7cy50ZXh0Q29udGVudD1lfHxcIlxcblwiO3ZhciBuPXMuZ2V0Qm91bmRpbmdDbGllbnRSZWN0KCkuaGVpZ2h0O3IuY2hpbGRyZW5bdF0uc3R5bGUuaGVpZ2h0PW4rXCJweFwifSkscy50ZXh0Q29udGVudD1cIlwiLHMuc3R5bGUuZGlzcGxheT1cIm5vbmVcIn19LGE9ZnVuY3Rpb24oZSl7cmV0dXJuIGU/d2luZG93LmdldENvbXB1dGVkU3R5bGU/Z2V0Q29tcHV0ZWRTdHlsZShlKTplLmN1cnJlbnRTdHlsZXx8bnVsbDpudWxsfTt3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcihcInJlc2l6ZVwiLGZ1bmN0aW9uKCl7QXJyYXkucHJvdG90eXBlLmZvckVhY2guY2FsbChkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKFwicHJlLlwiK2wpLG0pfSksUHJpc20uaG9va3MuYWRkKFwiY29tcGxldGVcIixmdW5jdGlvbihlKXtpZihlLmNvZGUpe3ZhciB0PWUuZWxlbWVudCxuPXQucGFyZW50Tm9kZTtpZihuJiYvcHJlL2kudGVzdChuLm5vZGVOYW1lKSYmIXQucXVlcnlTZWxlY3RvcihcIi5saW5lLW51bWJlcnMtcm93c1wiKSl7Zm9yKHZhciByPSExLHM9Lyg/Ol58XFxzKWxpbmUtbnVtYmVycyg/Olxcc3wkKS8saT10O2k7aT1pLnBhcmVudE5vZGUpaWYocy50ZXN0KGkuY2xhc3NOYW1lKSl7cj0hMDticmVha31pZihyKXt0LmNsYXNzTmFtZT10LmNsYXNzTmFtZS5yZXBsYWNlKHMsXCIgXCIpLHMudGVzdChuLmNsYXNzTmFtZSl8fChuLmNsYXNzTmFtZSs9XCIgbGluZS1udW1iZXJzXCIpO3ZhciBsLGE9ZS5jb2RlLm1hdGNoKGMpLG89YT9hLmxlbmd0aCsxOjEsdT1uZXcgQXJyYXkobysxKS5qb2luKFwiPHNwYW4+PC9zcGFuPlwiKTsobD1kb2N1bWVudC5jcmVhdGVFbGVtZW50KFwic3BhblwiKSkuc2V0QXR0cmlidXRlKFwiYXJpYS1oaWRkZW5cIixcInRydWVcIiksbC5jbGFzc05hbWU9XCJsaW5lLW51bWJlcnMtcm93c1wiLGwuaW5uZXJIVE1MPXUsbi5oYXNBdHRyaWJ1dGUoXCJkYXRhLXN0YXJ0XCIpJiYobi5zdHlsZS5jb3VudGVyUmVzZXQ9XCJsaW5lbnVtYmVyIFwiKyhwYXJzZUludChuLmdldEF0dHJpYnV0ZShcImRhdGEtc3RhcnRcIiksMTApLTEpKSxlLmVsZW1lbnQuYXBwZW5kQ2hpbGQobCksbShuKSxQcmlzbS5ob29rcy5ydW4oXCJsaW5lLW51bWJlcnNcIixlKX19fX0pLFByaXNtLmhvb2tzLmFkZChcImxpbmUtbnVtYmVyc1wiLGZ1bmN0aW9uKGUpe2UucGx1Z2lucz1lLnBsdWdpbnN8fHt9LGUucGx1Z2lucy5saW5lTnVtYmVycz0hMH0pLFByaXNtLnBsdWdpbnMubGluZU51bWJlcnM9e2dldExpbmU6ZnVuY3Rpb24oZSx0KXtpZihcIlBSRVwiPT09ZS50YWdOYW1lJiZlLmNsYXNzTGlzdC5jb250YWlucyhsKSl7dmFyIG49ZS5xdWVyeVNlbGVjdG9yKFwiLmxpbmUtbnVtYmVycy1yb3dzXCIpLHI9cGFyc2VJbnQoZS5nZXRBdHRyaWJ1dGUoXCJkYXRhLXN0YXJ0XCIpLDEwKXx8MSxzPXIrKG4uY2hpbGRyZW4ubGVuZ3RoLTEpO3Q8ciYmKHQ9ciksczx0JiYodD1zKTt2YXIgaT10LXI7cmV0dXJuIG4uY2hpbGRyZW5baV19fX19fSgpO1xuIiwiLyoqXG4gKiBUaGlzIGZpbGUgaXMgcGFydCBvZiBKb2huQ01TIENvbnRlbnQgTWFuYWdlbWVudCBTeXN0ZW0uXG4gKlxuICogQGNvcHlyaWdodCBKb2huQ01TIENvbW11bml0eVxuICogQGxpY2Vuc2UgICBodHRwczovL29wZW5zb3VyY2Uub3JnL2xpY2Vuc2VzL0dQTC0zLjAgR1BMLTMuMFxuICogQGxpbmsgICAgICBodHRwczovL2pvaG5jbXMuY29tIEpvaG5DTVMgUHJvamVjdFxuICovXG5cbiQoZnVuY3Rpb24gKCkge1xuICAkKFwiLnJvdW5kZWQtcHJvZ3Jlc3NcIikuZWFjaChmdW5jdGlvbiAoKSB7XG5cbiAgICBjb25zdCB2YWx1ZSA9ICQodGhpcykuYXR0cignZGF0YS12YWx1ZScpO1xuICAgIGNvbnN0IGxlZnQgPSAkKHRoaXMpLmZpbmQoJy5wcm9ncmVzcy1sZWZ0IC5wcm9ncmVzcy1iYXInKTtcbiAgICBjb25zdCByaWdodCA9ICQodGhpcykuZmluZCgnLnByb2dyZXNzLXJpZ2h0IC5wcm9ncmVzcy1iYXInKTtcblxuICAgIGlmICh2YWx1ZSA+IDApIHtcbiAgICAgIGlmICh2YWx1ZSA8PSA1MCkge1xuICAgICAgICByaWdodC5jc3MoJ3RyYW5zZm9ybScsICdyb3RhdGUoJyArIHBlcmNlbnRhZ2VUb0RlZ3JlZXModmFsdWUpICsgJ2RlZyknKVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgcmlnaHQuY3NzKCd0cmFuc2Zvcm0nLCAncm90YXRlKDE4MGRlZyknKTtcbiAgICAgICAgbGVmdC5jc3MoJ3RyYW5zZm9ybScsICdyb3RhdGUoJyArIHBlcmNlbnRhZ2VUb0RlZ3JlZXModmFsdWUgLSA1MCkgKyAnZGVnKScpXG4gICAgICB9XG4gICAgfVxuICB9KTtcblxuICBmdW5jdGlvbiBwZXJjZW50YWdlVG9EZWdyZWVzKHBlcmNlbnRhZ2UpXG4gIHtcbiAgICByZXR1cm4gcGVyY2VudGFnZSAvIDEwMCAqIDM2MFxuICB9XG59KTtcbiIsIi8qKlxuICogVGhpcyBmaWxlIGlzIHBhcnQgb2YgSm9obkNNUyBDb250ZW50IE1hbmFnZW1lbnQgU3lzdGVtLlxuICpcbiAqIEBjb3B5cmlnaHQgSm9obkNNUyBDb21tdW5pdHlcbiAqIEBsaWNlbnNlICAgaHR0cHM6Ly9vcGVuc291cmNlLm9yZy9saWNlbnNlcy9HUEwtMy4wIEdQTC0zLjBcbiAqIEBsaW5rICAgICAgaHR0cHM6Ly9qb2huY21zLmNvbSBKb2huQ01TIFByb2plY3RcbiAqL1xuXG5pbXBvcnQgU3dpcGVyIGZyb20gJ3N3aXBlci9zd2lwZXItYnVuZGxlJztcblxuY29uc3Qgc3dpcGVyU2xpZGVyID0gbmV3IFN3aXBlcignLnNjcmVlbnNob3RzJywge1xuICBzbGlkZXNQZXJWaWV3OiAxLFxuICBzcGFjZUJldHdlZW46IDEwLFxuICAvLyBpbml0OiBmYWxzZSxcbiAgcGFnaW5hdGlvbjoge1xuICAgIGVsOiAnLnN3aXBlci1wYWdpbmF0aW9uJyxcbiAgICBjbGlja2FibGU6IHRydWUsXG4gIH0sXG4gIGJyZWFrcG9pbnRzOiB7XG4gICAgNjQwOiB7XG4gICAgICBzbGlkZXNQZXJWaWV3OiAyLFxuICAgICAgc3BhY2VCZXR3ZWVuOiAyMCxcbiAgICB9LFxuICAgIDc2ODoge1xuICAgICAgc2xpZGVzUGVyVmlldzogMixcbiAgICAgIHNwYWNlQmV0d2VlbjogNDAsXG4gICAgfSxcbiAgICAxMDI0OiB7XG4gICAgICBzbGlkZXNQZXJWaWV3OiAzLFxuICAgICAgc3BhY2VCZXR3ZWVuOiAyMCxcbiAgICB9LFxuICB9XG59KTtcbiIsIi8qISBXeXNpQkIgdjEuNS4xIDIwMTQtMDMtMjZcbiAgICBBdXRob3I6IFZhZGltIERvYnJvc2tva1xuICovXG5pZiAodHlwZW9mIChXQkJMQU5HKSA9PSBcInVuZGVmaW5lZFwiKSB7XG4gIFdCQkxBTkcgPSB7fTtcbn1cbldCQkxBTkdbJ2VuJ10gPSBDVVJMQU5HID0ge1xuICBib2xkOiBcIkJvbGRcIixcbiAgaXRhbGljOiBcIkl0YWxpY1wiLFxuICB1bmRlcmxpbmU6IFwiVW5kZXJsaW5lXCIsXG4gIHN0cmlrZTogXCJTdHJpa2VcIixcbiAgbGluazogXCJMaW5rXCIsXG4gIGltZzogXCJJbnNlcnQgaW1hZ2VcIixcbiAgc3VwOiBcIlN1cGVyc2NyaXB0XCIsXG4gIHN1YjogXCJTdWJzY3JpcHRcIixcbiAganVzdGlmeWxlZnQ6IFwiQWxpZ24gbGVmdFwiLFxuICBqdXN0aWZ5Y2VudGVyOiBcIkFsaWduIGNlbnRlclwiLFxuICBqdXN0aWZ5cmlnaHQ6IFwiQWxpZ24gcmlnaHRcIixcbiAgdGFibGU6IFwiSW5zZXJ0IHRhYmxlXCIsXG4gIGJ1bGxpc3Q6IFwi4oCiIFVub3JkZXJlZCBsaXN0XCIsXG4gIG51bWxpc3Q6IFwiMS4gT3JkZXJlZCBsaXN0XCIsXG4gIHF1b3RlOiBcIlF1b3RlXCIsXG4gIG9mZnRvcDogXCJPZmZ0b3BcIixcbiAgY29kZTogXCJDb2RlXCIsXG4gIHNwb2lsZXI6IFwiU3BvaWxlclwiLFxuICBmb250Y29sb3I6IFwiRm9udCBjb2xvclwiLFxuICBmb250c2l6ZTogXCJGb250IHNpemVcIixcbiAgZm9udGZhbWlseTogXCJGb250IGZhbWlseVwiLFxuICBmc192ZXJ5c21hbGw6IFwiVmVyeSBzbWFsbFwiLFxuICBmc19zbWFsbDogXCJTbWFsbFwiLFxuICBmc19ub3JtYWw6IFwiTm9ybWFsXCIsXG4gIGZzX2JpZzogXCJCaWdcIixcbiAgZnNfdmVyeWJpZzogXCJWZXJ5IGJpZ1wiLFxuICBzbWlsZWJveDogXCJJbnNlcnQgZW1vdGljb25cIixcbiAgdmlkZW86IFwiSW5zZXJ0IFlvdVR1YmVcIixcbiAgcmVtb3ZlRm9ybWF0OiBcIlJlbW92ZSBGb3JtYXRcIixcblxuICBtb2RhbF9saW5rX3RpdGxlOiBcIkluc2VydCBsaW5rXCIsXG4gIG1vZGFsX2xpbmtfdGV4dDogXCJEaXNwbGF5IHRleHRcIixcbiAgbW9kYWxfbGlua191cmw6IFwiVVJMXCIsXG4gIG1vZGFsX2VtYWlsX3RleHQ6IFwiRGlzcGxheSBlbWFpbFwiLFxuICBtb2RhbF9lbWFpbF91cmw6IFwiRW1haWxcIixcbiAgbW9kYWxfbGlua190YWIxOiBcIkluc2VydCBVUkxcIixcblxuICBtb2RhbF9pbWdfdGl0bGU6IFwiSW5zZXJ0IGltYWdlXCIsXG4gIG1vZGFsX2ltZ190YWIxOiBcIkluc2VydCBVUkxcIixcbiAgbW9kYWxfaW1nX3RhYjI6IFwiVXBsb2FkIGltYWdlXCIsXG4gIG1vZGFsX2ltZ3NyY190ZXh0OiBcIkVudGVyIGltYWdlIFVSTFwiLFxuICBtb2RhbF9pbWdfYnRuOiBcIkNob29zZSBmaWxlXCIsXG4gIGFkZF9hdHRhY2g6IFwiQWRkIEF0dGFjaG1lbnRcIixcblxuICBtb2RhbF92aWRlb190ZXh0OiBcIkVudGVyIHRoZSBVUkwgb2YgdGhlIHZpZGVvXCIsXG5cbiAgY2xvc2U6IFwiQ2xvc2VcIixcbiAgc2F2ZTogXCJTYXZlXCIsXG4gIGNhbmNlbDogXCJDYW5jZWxcIixcbiAgcmVtb3ZlOiBcIkRlbGV0ZVwiLFxuXG4gIHZhbGlkYXRpb25fZXJyOiBcIlRoZSBlbnRlcmVkIGRhdGEgaXMgaW52YWxpZFwiLFxuICBlcnJvcl9vbnVwbG9hZDogXCJFcnJvciBkdXJpbmcgZmlsZSB1cGxvYWRcIixcblxuICBmaWxldXBsb2FkX3RleHQxOiBcIkRyb3AgZmlsZSBoZXJlXCIsXG4gIGZpbGV1cGxvYWRfdGV4dDI6IFwib3JcIixcblxuICBsb2FkaW5nOiBcIkxvYWRpbmdcIixcbiAgYXV0bzogXCJBdXRvXCIsXG4gIHZpZXdzOiBcIlZpZXdzXCIsXG4gIGRvd25sb2FkczogXCJEb3dubG9hZHNcIixcblxuICAvL3NtaWxlc1xuICBzbTE6IFwiU21pbGVcIixcbiAgc20yOiBcIkxhdWdodGVyXCIsXG4gIHNtMzogXCJXaW5rXCIsXG4gIHNtNDogXCJUaGFuayB5b3VcIixcbiAgc201OiBcIlNjb2xkXCIsXG4gIHNtNjogXCJTaG9ja1wiLFxuICBzbTc6IFwiQW5ncnlcIixcbiAgc204OiBcIlBhaW5cIixcbiAgc205OiBcIlNpY2tcIlxufTtcbndiYmRlYnVnID0gdHJ1ZTtcbihmdW5jdGlvbiAoJCkge1xuICAndXNlIHN0cmljdCc7XG4gICQud3lzaWJiID0gZnVuY3Rpb24gKHR4dEFyZWEsIHNldHRpbmdzKSB7XG4gICAgJCh0eHRBcmVhKS5kYXRhKFwid2JiXCIsIHRoaXMpO1xuXG4gICAgaWYgKHNldHRpbmdzICYmIHNldHRpbmdzLmRlZmxhbmcgJiYgdHlwZW9mIChXQkJMQU5HW3NldHRpbmdzLmRlZmxhbmddKSAhPSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICBDVVJMQU5HID0gV0JCTEFOR1tzZXR0aW5ncy5kZWZsYW5nXTtcbiAgICB9XG4gICAgaWYgKHNldHRpbmdzICYmIHNldHRpbmdzLmxhbmcgJiYgdHlwZW9mIChXQkJMQU5HW3NldHRpbmdzLmxhbmddKSAhPSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICBDVVJMQU5HID0gV0JCTEFOR1tzZXR0aW5ncy5sYW5nXTtcbiAgICB9XG4gICAgdGhpcy50eHRBcmVhID0gdHh0QXJlYTtcbiAgICB0aGlzLiR0eHRBcmVhID0gJCh0eHRBcmVhKTtcbiAgICB2YXIgaWQgPSB0aGlzLiR0eHRBcmVhLmF0dHIoXCJpZFwiKSB8fCB0aGlzLnNldFVJRCh0aGlzLnR4dEFyZWEpO1xuICAgIHRoaXMub3B0aW9ucyA9IHtcbiAgICAgIGJibW9kZTogZmFsc2UsXG4gICAgICBvbmx5QkJtb2RlOiBmYWxzZSxcbiAgICAgIHRoZW1lTmFtZTogXCJkZWZhdWx0XCIsXG4gICAgICBib2R5Q2xhc3M6IFwiXCIsXG4gICAgICBsYW5nOiBcInJ1XCIsXG4gICAgICB0YWJJbnNlcnQ6IHRydWUsXG4vL1x0XHRcdHRvb2xiYXI6XHRcdFx0ZmFsc2UsXG4gICAgICAvL2ltZyB1cGxvYWQgY29uZmlnXG4gICAgICBpbWd1cGxvYWQ6IGZhbHNlLFxuICAgICAgaW1nX3VwbG9hZHVybDogXCIvaXVwbG9hZC5waHBcIixcbiAgICAgIGltZ19tYXh3aWR0aDogODAwLFxuICAgICAgaW1nX21heGhlaWdodDogODAwLFxuICAgICAgaG90a2V5czogdHJ1ZSxcbiAgICAgIHNob3dIb3RrZXlzOiB0cnVlLFxuICAgICAgYXV0b3Jlc2l6ZTogdHJ1ZSxcbiAgICAgIHJlc2l6ZV9tYXhoZWlnaHQ6IDgwMCxcbiAgICAgIGxvYWRQYWdlU3R5bGVzOiB0cnVlLFxuICAgICAgdHJhY2VUZXh0YXJlYTogdHJ1ZSxcbi8vXHRcdFx0ZGlyZWN0aW9uOlx0XHRcdFwibHRyXCIsXG4gICAgICBzbWlsZUNvbnZlcnNpb246IHRydWUsXG5cbiAgICAgIC8vRU5EIGltZyB1cGxvYWQgY29uZmlnXG4gICAgICBidXR0b25zOiBcImJvbGQsaXRhbGljLHVuZGVybGluZSxzdHJpa2Usc3VwLHN1Yix8LGltZyx2aWRlbyxsaW5rLHwsYnVsbGlzdCxudW1saXN0LHwsZm9udGNvbG9yLGZvbnRzaXplLGZvbnRmYW1pbHksfCxqdXN0aWZ5bGVmdCxqdXN0aWZ5Y2VudGVyLGp1c3RpZnlyaWdodCx8LHF1b3RlLGNvZGUsdGFibGUscmVtb3ZlRm9ybWF0XCIsXG4gICAgICBhbGxCdXR0b25zOiB7XG4gICAgICAgIGJvbGQ6IHtcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5ib2xkLFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi1ib2xkMVwiPlxcdUUwMTg8L3NwYW4+JyxcbiAgICAgICAgICBleGNtZDogJ2JvbGQnLFxuICAgICAgICAgIGhvdGtleTogJ2N0cmwrYicsXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPGI+e1NFTFRFWFR9PC9iPic6IFwiW2Jde1NFTFRFWFR9Wy9iXVwiLFxuICAgICAgICAgICAgJzxzdHJvbmc+e1NFTFRFWFR9PC9zdHJvbmc+JzogXCJbYl17U0VMVEVYVH1bL2JdXCJcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIGl0YWxpYzoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLml0YWxpYyxcbiAgICAgICAgICBidXR0b25IVE1MOiAnPHNwYW4gY2xhc3M9XCJmb250aWNvbiB2ZS10bGItaXRhbGljMVwiPlxcdUUwMDE8L3NwYW4+JyxcbiAgICAgICAgICBleGNtZDogJ2l0YWxpYycsXG4gICAgICAgICAgaG90a2V5OiAnY3RybCtpJyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8aT57U0VMVEVYVH08L2k+JzogXCJbaV17U0VMVEVYVH1bL2ldXCIsXG4gICAgICAgICAgICAnPGVtPntTRUxURVhUfTwvZW0+JzogXCJbaV17U0VMVEVYVH1bL2ldXCJcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIHVuZGVybGluZToge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLnVuZGVybGluZSxcbiAgICAgICAgICBidXR0b25IVE1MOiAnPHNwYW4gY2xhc3M9XCJmb250aWNvbiB2ZS10bGItdW5kZXJsaW5lMVwiPlxcdUUwMDI8L3NwYW4+JyxcbiAgICAgICAgICBleGNtZDogJ3VuZGVybGluZScsXG4gICAgICAgICAgaG90a2V5OiAnY3RybCt1JyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8dT57U0VMVEVYVH08L3U+JzogXCJbdV17U0VMVEVYVH1bL3VdXCJcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIHN0cmlrZToge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLnN0cmlrZSxcbiAgICAgICAgICBidXR0b25IVE1MOiAnPHNwYW4gY2xhc3M9XCJmb250aWNvbiBmaS1zdHJva2UxIHZlLXRsYi1zdHJpa2UxXCI+XFx1RTAwMzwvc3Bhbj4nLFxuICAgICAgICAgIGV4Y21kOiAnc3RyaWtlVGhyb3VnaCcsXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPHN0cmlrZT57U0VMVEVYVH08L3N0cmlrZT4nOiBcIltzXXtTRUxURVhUfVsvc11cIixcbiAgICAgICAgICAgICc8cz57U0VMVEVYVH08L3M+JzogXCJbc117U0VMVEVYVH1bL3NdXCJcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIHN1cDoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLnN1cCxcbiAgICAgICAgICBidXR0b25IVE1MOiAnPHNwYW4gY2xhc3M9XCJmb250aWNvbiB2ZS10bGItc3VwMVwiPlxcdUUwMDU8L3NwYW4+JyxcbiAgICAgICAgICBleGNtZDogJ3N1cGVyc2NyaXB0JyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8c3VwPntTRUxURVhUfTwvc3VwPic6IFwiW3N1cF17U0VMVEVYVH1bL3N1cF1cIlxuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgc3ViOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuc3ViLFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi1zdWIxXCI+XFx1RTAwNDwvc3Bhbj4nLFxuICAgICAgICAgIGV4Y21kOiAnc3Vic2NyaXB0JyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8c3ViPntTRUxURVhUfTwvc3ViPic6IFwiW3N1Yl17U0VMVEVYVH1bL3N1Yl1cIlxuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgbGluazoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLmxpbmssXG4gICAgICAgICAgYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb24gdmUtdGxiLWxpbmsxXCI+XFx1RTAwNzwvc3Bhbj4nLFxuICAgICAgICAgIGhvdGtleTogJ2N0cmwrc2hpZnQrMicsXG4gICAgICAgICAgbW9kYWw6IHtcbiAgICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLm1vZGFsX2xpbmtfdGl0bGUsXG4gICAgICAgICAgICB3aWR0aDogXCI1MDBweFwiLFxuICAgICAgICAgICAgdGFiczogW1xuICAgICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgaW5wdXQ6IFtcbiAgICAgICAgICAgICAgICAgIHtwYXJhbTogXCJTRUxURVhUXCIsIHRpdGxlOiBDVVJMQU5HLm1vZGFsX2xpbmtfdGV4dCwgdHlwZTogXCJkaXZcIn0sXG4gICAgICAgICAgICAgICAgICB7cGFyYW06IFwiVVJMXCIsIHRpdGxlOiBDVVJMQU5HLm1vZGFsX2xpbmtfdXJsLCB2YWxpZGF0aW9uOiAnXmh0dHAocyk/Oi8vJ31cbiAgICAgICAgICAgICAgICBdXG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIF1cbiAgICAgICAgICB9LFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxhIGhyZWY9XCJ7VVJMfVwiPntTRUxURVhUfTwvYT4nOiBcIlt1cmw9e1VSTH1de1NFTFRFWFR9Wy91cmxdXCIsXG4gICAgICAgICAgICAnPGEgaHJlZj1cIntVUkx9XCI+e1VSTH08L2E+JzogXCJbdXJsXXtVUkx9Wy91cmxdXCJcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIGltZzoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLmltZyxcbiAgICAgICAgICBidXR0b25IVE1MOiAnPHNwYW4gY2xhc3M9XCJmb250aWNvbiB2ZS10bGItaW1nMVwiPlxcdUUwMDY8L3NwYW4+JyxcbiAgICAgICAgICBob3RrZXk6ICdjdHJsK3NoaWZ0KzEnLFxuICAgICAgICAgIGFkZFdyYXA6IHRydWUsXG4gICAgICAgICAgbW9kYWw6IHtcbiAgICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLm1vZGFsX2ltZ190aXRsZSxcbiAgICAgICAgICAgIHdpZHRoOiBcIjYwMHB4XCIsXG4gICAgICAgICAgICB0YWJzOiBbXG4gICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICB0aXRsZTogQ1VSTEFORy5tb2RhbF9pbWdfdGFiMSxcbiAgICAgICAgICAgICAgICBpbnB1dDogW1xuICAgICAgICAgICAgICAgICAge3BhcmFtOiBcIlNSQ1wiLCB0aXRsZTogQ1VSTEFORy5tb2RhbF9pbWdzcmNfdGV4dCwgdmFsaWRhdGlvbjogJ15odHRwKHMpPzovLy4qP1xcLihqcGd8cG5nfGdpZnxqcGVnKSQnfVxuICAgICAgICAgICAgICAgIF1cbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgXSxcbiAgICAgICAgICAgIG9uTG9hZDogdGhpcy5pbWdMb2FkTW9kYWxcbiAgICAgICAgICB9LFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxpbWcgc3JjPVwie1NSQ31cIiAvPic6IFwiW2ltZ117U1JDfVsvaW1nXVwiLFxuICAgICAgICAgICAgJzxpbWcgc3JjPVwie1NSQ31cIiB3aWR0aD1cIntXSURUSH1cIiBoZWlnaHQ9XCJ7SEVJR0hUfVwiLz4nOiBcIltpbWcgd2lkdGg9e1dJRFRIfSxoZWlnaHQ9e0hFSUdIVH1de1NSQ31bL2ltZ11cIlxuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgYnVsbGlzdDoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLmJ1bGxpc3QsXG4gICAgICAgICAgYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb24gdmUtdGxiLWxpc3QxXCI+XFx1RTAwOTwvc3Bhbj4nLFxuICAgICAgICAgIGV4Y21kOiAnaW5zZXJ0VW5vcmRlcmVkTGlzdCcsXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPHVsPntTRUxURVhUfTwvdWw+JzogXCJbbGlzdF17U0VMVEVYVH1bL2xpc3RdXCIsXG4gICAgICAgICAgICAnPGxpPntTRUxURVhUfTwvbGk+JzogXCJbKl17U0VMVEVYVH1bLypdXCJcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIG51bWxpc3Q6IHtcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5udW1saXN0LFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi1udW1saXN0MVwiPlxcdUUwMGE8L3NwYW4+JyxcbiAgICAgICAgICBleGNtZDogJ2luc2VydE9yZGVyZWRMaXN0JyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8b2w+e1NFTFRFWFR9PC9vbD4nOiBcIltsaXN0PTFde1NFTFRFWFR9Wy9saXN0XVwiLFxuICAgICAgICAgICAgJzxsaT57U0VMVEVYVH08L2xpPic6IFwiWypde1NFTFRFWFR9Wy8qXVwiXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBxdW90ZToge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLnF1b3RlLFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi1xdW90ZTFcIj5cXHVFMDBjPC9zcGFuPicsXG4gICAgICAgICAgaG90a2V5OiAnY3RybCtzaGlmdCszJyxcbiAgICAgICAgICAvL3N1Ykluc2VydDogdHJ1ZSxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8YmxvY2txdW90ZSBjbGFzcz1cImJsb2NrcXVvdGUgcG9zdC1xdW90ZSBwLTIgYmctbGlnaHQgYm9yZGVyIHJvdW5kZWQgZC1pbmxpbmUtYmxvY2tcIj57U0VMVEVYVH08L2Jsb2NrcXVvdGU+JzogXCJbcXVvdGVde1NFTFRFWFR9Wy9xdW90ZV1cIlxuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgY29kZToge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLmNvZGUsXG4gICAgICAgICAgYnV0dG9uVGV4dDogJ1tjb2RlXScsXG4gICAgICAgICAgLyogYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb25cIj5cXHVFMDBkPC9zcGFuPicsICovXG4gICAgICAgICAgaG90a2V5OiAnY3RybCtzaGlmdCs0JyxcbiAgICAgICAgICBvbmx5Q2xlYXJUZXh0OiB0cnVlLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxjb2RlPntTRUxURVhUfTwvY29kZT4nOiBcIltjb2RlPXBocF17U0VMVEVYVH1bL2NvZGVdXCJcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIG9mZnRvcDoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLm9mZnRvcCxcbiAgICAgICAgICBidXR0b25UZXh0OiAnb2ZmdG9wJyxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8c3BhbiBzdHlsZT1cImZvbnQtc2l6ZToxMHB4O2NvbG9yOiNjY2NcIj57U0VMVEVYVH08L3NwYW4+JzogXCJbb2ZmdG9wXXtTRUxURVhUfVsvb2ZmdG9wXVwiXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBmb250Y29sb3I6IHtcbiAgICAgICAgICB0eXBlOiBcImNvbG9ycGlja2VyXCIsXG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuZm9udGNvbG9yLFxuICAgICAgICAgIGV4Y21kOiBcImZvcmVDb2xvclwiLFxuICAgICAgICAgIHZhbHVlQkJuYW1lOiBcImNvbG9yXCIsXG4gICAgICAgICAgc3ViSW5zZXJ0OiB0cnVlLFxuICAgICAgICAgIGNvbG9yczogXCIjMDAwMDAwLCM0NDQ0NDQsIzY2NjY2NiwjOTk5OTk5LCNiNmI2YjYsI2NjY2NjYywjZDhkOGQ4LCNlZmVmZWYsI2Y0ZjRmNCwjZmZmZmZmLC0sIFxcXG5cdFx0XHRcdFx0XHRcdCAjZmYwMDAwLCM5ODAwMDAsI2ZmNzcwMCwjZmZmZjAwLCMwMGZmMDAsIzAwZmZmZiwjMWU4NGNjLCMwMDAwZmYsIzk5MDBmZiwjZmYwMGZmLC0sIFxcXG5cdFx0XHRcdFx0XHRcdCAjZjRjY2NjLCNkYmIwYTcsI2ZjZTVjZCwjZmZmMmNjLCNkOWVhZDMsI2QwZTBlMywjYzlkYWY4LCNjZmUyZjMsI2Q5ZDJlOSwjZWFkMWRjLCBcXFxuXHRcdFx0XHRcdFx0XHQgI2VhOTk5OSwjZGQ3ZTZiLCNmOWNiOWMsI2ZmZTU5OSwjYjZkN2E4LCNhMmM0YzksI2E0YzJmNCwjOWZjNWU4LCNiNGE3ZDYsI2Q1YTZiZCwgXFxcblx0XHRcdFx0XHRcdFx0ICNlMDY2NjYsI2NjNDEyNSwjZjZiMjZiLCNmZmQ5NjYsIzkzYzQ3ZCwjNzZhNWFmLCM2ZDllZWIsIzZmYThkYywjOGU3Y2MzLCNjMjdiYTAsIFxcXG5cdFx0XHRcdFx0XHRcdCAjY2MwMDAwLCNhNjFjMDAsI2U2OTEzOCwjZjFjMjMyLCM2YWE4NGYsIzQ1ODE4ZSwjM2M3OGQ4LCMzZDg1YzYsIzY3NGVhNywjYTY0ZDc5LCBcXFxuXHRcdFx0XHRcdFx0XHQgIzkwMDAwMCwjODUyMDBDLCNCNDVGMDYsI0JGOTAwMCwjMzg3NjFELCMxMzRGNUMsIzExNTVDYywjMEI1Mzk0LCMzNTFDNzUsIzc0MUI0NywgXFxcblx0XHRcdFx0XHRcdFx0ICM2NjAwMDAsIzVCMEYwMCwjNzgzRjA0LCM3RjYwMDAsIzI3NEUxMywjMEMzNDNELCMxQzQ1ODcsIzA3Mzc2MywjMjAxMjRELCM0QzExMzBcIixcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8Zm9udCBjb2xvcj1cIntDT0xPUn1cIj57U0VMVEVYVH08L2ZvbnQ+JzogJ1tjb2xvcj17Q09MT1J9XXtTRUxURVhUfVsvY29sb3JdJ1xuICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgdGFibGU6IHtcbiAgICAgICAgICB0eXBlOiBcInRhYmxlXCIsXG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcudGFibGUsXG4gICAgICAgICAgY29sczogMTAsXG4gICAgICAgICAgcm93czogMTAsXG4gICAgICAgICAgY2VsbHdpZHRoOiAyMCxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8dGQ+e1NFTFRFWFR9PC90ZD4nOiAnW3RkXXtTRUxURVhUfVsvdGRdJyxcbiAgICAgICAgICAgICc8dHI+e1NFTFRFWFR9PC90cj4nOiAnW3RyXXtTRUxURVhUfVsvdHJdJyxcbiAgICAgICAgICAgICc8dGFibGUgY2xhc3M9XCJ3YmItdGFibGVcIj57U0VMVEVYVH08L3RhYmxlPic6ICdbdGFibGVde1NFTFRFWFR9Wy90YWJsZV0nXG4gICAgICAgICAgfSxcbiAgICAgICAgICBza2lwUnVsZXM6IHRydWVcbiAgICAgICAgfSxcbiAgICAgICAgZm9udHNpemU6IHtcbiAgICAgICAgICB0eXBlOiAnc2VsZWN0JyxcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5mb250c2l6ZSxcbiAgICAgICAgICBvcHRpb25zOiBcImZzX3ZlcnlzbWFsbCxmc19zbWFsbCxmc19ub3JtYWwsZnNfYmlnLGZzX3ZlcnliaWdcIlxuICAgICAgICB9LFxuICAgICAgICBmb250ZmFtaWx5OiB7XG4gICAgICAgICAgdHlwZTogJ3NlbGVjdCcsXG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuZm9udGZhbWlseSxcbiAgICAgICAgICBleGNtZDogJ2ZvbnROYW1lJyxcbiAgICAgICAgICB2YWx1ZUJCbmFtZTogXCJmb250XCIsXG4gICAgICAgICAgb3B0aW9uczogW1xuICAgICAgICAgICAge3RpdGxlOiBcIkFyaWFsXCIsIGV4dmFsdWU6IFwiQXJpYWxcIn0sXG4gICAgICAgICAgICB7dGl0bGU6IFwiQ29taWMgU2FucyBNU1wiLCBleHZhbHVlOiBcIkNvbWljIFNhbnMgTVNcIn0sXG4gICAgICAgICAgICB7dGl0bGU6IFwiQ291cmllciBOZXdcIiwgZXh2YWx1ZTogXCJDb3VyaWVyIE5ld1wifSxcbiAgICAgICAgICAgIHt0aXRsZTogXCJHZW9yZ2lhXCIsIGV4dmFsdWU6IFwiR2VvcmdpYVwifSxcbiAgICAgICAgICAgIHt0aXRsZTogXCJMdWNpZGEgU2FucyBVbmljb2RlXCIsIGV4dmFsdWU6IFwiTHVjaWRhIFNhbnMgVW5pY29kZVwifSxcbiAgICAgICAgICAgIHt0aXRsZTogXCJUYWhvbWFcIiwgZXh2YWx1ZTogXCJUYWhvbWFcIn0sXG4gICAgICAgICAgICB7dGl0bGU6IFwiVGltZXMgTmV3IFJvbWFuXCIsIGV4dmFsdWU6IFwiVGltZXMgTmV3IFJvbWFuXCJ9LFxuICAgICAgICAgICAge3RpdGxlOiBcIlRyZWJ1Y2hldCBNU1wiLCBleHZhbHVlOiBcIlRyZWJ1Y2hldCBNU1wifSxcbiAgICAgICAgICAgIHt0aXRsZTogXCJWZXJkYW5hXCIsIGV4dmFsdWU6IFwiVmVyZGFuYVwifVxuICAgICAgICAgIF0sXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPGZvbnQgZmFjZT1cIntGT05UfVwiPntTRUxURVhUfTwvZm9udD4nOiAnW2ZvbnQ9e0ZPTlR9XXtTRUxURVhUfVsvZm9udF0nXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBzbWlsZWJveDoge1xuICAgICAgICAgIHR5cGU6ICdzbWlsZWJveCcsXG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuc21pbGVib3gsXG4gICAgICAgICAgYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb24gdmUtdGxiLXNtaWxlYm94MVwiPlxcdUUwMGI8L3NwYW4+J1xuICAgICAgICB9LFxuICAgICAgICBqdXN0aWZ5bGVmdDoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLmp1c3RpZnlsZWZ0LFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi10ZXh0bGVmdDFcIj5cXHVFMDE1PC9zcGFuPicsXG4gICAgICAgICAgZ3JvdXBrZXk6ICdhbGlnbicsXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPHAgc3R5bGU9XCJ0ZXh0LWFsaWduOmxlZnRcIj57U0VMVEVYVH08L3A+JzogJ1tsZWZ0XXtTRUxURVhUfVsvbGVmdF0nXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBqdXN0aWZ5cmlnaHQ6IHtcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5qdXN0aWZ5cmlnaHQsXG4gICAgICAgICAgYnV0dG9uSFRNTDogJzxzcGFuIGNsYXNzPVwiZm9udGljb24gdmUtdGxiLXRleHRyaWdodDFcIj5cXHVFMDE2PC9zcGFuPicsXG4gICAgICAgICAgZ3JvdXBrZXk6ICdhbGlnbicsXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPHAgc3R5bGU9XCJ0ZXh0LWFsaWduOnJpZ2h0XCI+e1NFTFRFWFR9PC9wPic6ICdbcmlnaHRde1NFTFRFWFR9Wy9yaWdodF0nXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBqdXN0aWZ5Y2VudGVyOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuanVzdGlmeWNlbnRlcixcbiAgICAgICAgICBidXR0b25IVE1MOiAnPHNwYW4gY2xhc3M9XCJmb250aWNvbiB2ZS10bGItdGV4dGNlbnRlcjFcIj5cXHVFMDE0PC9zcGFuPicsXG4gICAgICAgICAgZ3JvdXBrZXk6ICdhbGlnbicsXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPHAgc3R5bGU9XCJ0ZXh0LWFsaWduOmNlbnRlclwiPntTRUxURVhUfTwvcD4nOiAnW2NlbnRlcl17U0VMVEVYVH1bL2NlbnRlcl0nXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICB2aWRlbzoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLnZpZGVvLFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi12aWRlbzFcIj5cXHVFMDA4PC9zcGFuPicsXG4gICAgICAgICAgbW9kYWw6IHtcbiAgICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLnZpZGVvLFxuICAgICAgICAgICAgd2lkdGg6IFwiNjAwcHhcIixcbiAgICAgICAgICAgIHRhYnM6IFtcbiAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLnZpZGVvLFxuICAgICAgICAgICAgICAgIGlucHV0OiBbXG4gICAgICAgICAgICAgICAgICB7cGFyYW06IFwiU1JDXCIsIHRpdGxlOiBDVVJMQU5HLm1vZGFsX3ZpZGVvX3RleHR9XG4gICAgICAgICAgICAgICAgXVxuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICBdLFxuICAgICAgICAgICAgb25TdWJtaXQ6IGZ1bmN0aW9uIChjbWQsIG9wdCwgcXVlcnlTdGF0ZSkge1xuICAgICAgICAgICAgICB2YXIgdXJsID0gdGhpcy4kbW9kYWwuZmluZCgnaW5wdXRbbmFtZT1cIlNSQ1wiXScpLnZhbCgpO1xuICAgICAgICAgICAgICBpZiAodXJsKSB7XG4gICAgICAgICAgICAgICAgdXJsID0gdXJsLnJlcGxhY2UoL15cXHMrLywgXCJcIikucmVwbGFjZSgvXFxzKyQvLCBcIlwiKTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICB2YXIgYTtcbiAgICAgICAgICAgICAgaWYgKHVybC5pbmRleE9mKFwieW91dHUuYmVcIikgIT0gLTEpIHtcbiAgICAgICAgICAgICAgICBhID0gdXJsLm1hdGNoKC9eaHR0cFtzXSo6XFwvXFwveW91dHVcXC5iZVxcLyhbYS16MC05Xy1dKykvaSk7XG4gICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgYSA9IHVybC5tYXRjaCgvXmh0dHBbc10qOlxcL1xcL3d3d1xcLnlvdXR1YmVcXC5jb21cXC93YXRjaFxcPy4qP3Y9KFthLXowLTlfLV0rKS9pKTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICBpZiAoYSAmJiBhLmxlbmd0aCA9PSAyKSB7XG4gICAgICAgICAgICAgICAgdmFyIGNvZGUgPSBhWzFdO1xuICAgICAgICAgICAgICAgIHRoaXMuaW5zZXJ0QXRDdXJzb3IodGhpcy5nZXRDb2RlQnlDb21tYW5kKGNtZCwge3NyYzogY29kZX0pKTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICB0aGlzLmNsb3NlTW9kYWwoKTtcbiAgICAgICAgICAgICAgdGhpcy51cGRhdGVVSSgpO1xuICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSxcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8ZGl2IHN0eWxlPVwibWF4LXdpZHRoOiA2MDBweFwiPjxkaXYgY2xhc3M9XCJlbWJlZC1yZXNwb25zaXZlIGVtYmVkLXJlc3BvbnNpdmUtMTZieTlcIj48aWZyYW1lIHNyYz1cImh0dHA6Ly93d3cueW91dHViZS5jb20vZW1iZWQve1NSQ31cIiBmcmFtZWJvcmRlcj1cIjBcIj48L2lmcmFtZT48L2Rpdj48L2Rpdj4nOiAnW3lvdXR1YmVdaHR0cHM6Ly93d3cueW91dHViZS5jb20vd2F0Y2g/dj17U1JDfVsveW91dHViZV0nXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuXG4gICAgICAgIC8vc2VsZWN0IG9wdGlvbnNcbiAgICAgICAgZnNfdmVyeXNtYWxsOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuZnNfdmVyeXNtYWxsLFxuICAgICAgICAgIGJ1dHRvblRleHQ6IFwiZnMxXCIsXG4gICAgICAgICAgZXhjbWQ6ICdmb250U2l6ZScsXG4gICAgICAgICAgZXh2YWx1ZTogXCIxXCIsXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPGZvbnQgc2l6ZT1cIjFcIj57U0VMVEVYVH08L2ZvbnQ+JzogJ1tzaXplPTUwXXtTRUxURVhUfVsvc2l6ZV0nXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBmc19zbWFsbDoge1xuICAgICAgICAgIHRpdGxlOiBDVVJMQU5HLmZzX3NtYWxsLFxuICAgICAgICAgIGJ1dHRvblRleHQ6IFwiZnMyXCIsXG4gICAgICAgICAgZXhjbWQ6ICdmb250U2l6ZScsXG4gICAgICAgICAgZXh2YWx1ZTogXCIyXCIsXG4gICAgICAgICAgdHJhbnNmb3JtOiB7XG4gICAgICAgICAgICAnPGZvbnQgc2l6ZT1cIjJcIj57U0VMVEVYVH08L2ZvbnQ+JzogJ1tzaXplPTg1XXtTRUxURVhUfVsvc2l6ZV0nXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBmc19ub3JtYWw6IHtcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5mc19ub3JtYWwsXG4gICAgICAgICAgYnV0dG9uVGV4dDogXCJmczNcIixcbiAgICAgICAgICBleGNtZDogJ2ZvbnRTaXplJyxcbiAgICAgICAgICBleHZhbHVlOiBcIjNcIixcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8Zm9udCBzaXplPVwiM1wiPntTRUxURVhUfTwvZm9udD4nOiAnW3NpemU9MTAwXXtTRUxURVhUfVsvc2l6ZV0nXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBmc19iaWc6IHtcbiAgICAgICAgICB0aXRsZTogQ1VSTEFORy5mc19iaWcsXG4gICAgICAgICAgYnV0dG9uVGV4dDogXCJmczRcIixcbiAgICAgICAgICBleGNtZDogJ2ZvbnRTaXplJyxcbiAgICAgICAgICBleHZhbHVlOiBcIjRcIixcbiAgICAgICAgICB0cmFuc2Zvcm06IHtcbiAgICAgICAgICAgICc8Zm9udCBzaXplPVwiNFwiPntTRUxURVhUfTwvZm9udD4nOiAnW3NpemU9MTUwXXtTRUxURVhUfVsvc2l6ZV0nXG4gICAgICAgICAgfVxuICAgICAgICB9LFxuICAgICAgICBmc192ZXJ5YmlnOiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcuZnNfdmVyeWJpZyxcbiAgICAgICAgICBidXR0b25UZXh0OiBcImZzNVwiLFxuICAgICAgICAgIGV4Y21kOiAnZm9udFNpemUnLFxuICAgICAgICAgIGV4dmFsdWU6IFwiNlwiLFxuICAgICAgICAgIHRyYW5zZm9ybToge1xuICAgICAgICAgICAgJzxmb250IHNpemU9XCI2XCI+e1NFTFRFWFR9PC9mb250Pic6ICdbc2l6ZT0yMDBde1NFTFRFWFR9Wy9zaXplXSdcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG5cbiAgICAgICAgcmVtb3ZlZm9ybWF0OiB7XG4gICAgICAgICAgdGl0bGU6IENVUkxBTkcucmVtb3ZlRm9ybWF0LFxuICAgICAgICAgIGJ1dHRvbkhUTUw6ICc8c3BhbiBjbGFzcz1cImZvbnRpY29uIHZlLXRsYi1yZW1vdmVmb3JtYXQxXCI+XFx1RTAwZjwvc3Bhbj4nLFxuICAgICAgICAgIGV4Y21kOiBcInJlbW92ZUZvcm1hdFwiXG4gICAgICAgIH1cbiAgICAgIH0sXG4gICAgICBzeXN0cjoge1xuICAgICAgICAnPGJyLz4nOiBcIlxcblwiLFxuICAgICAgICAnPHNwYW4gY2xhc3M9XCJ3YmJ0YWJcIj57U0VMVEVYVH08L3NwYW4+JzogJyAgIHtTRUxURVhUfSdcbiAgICAgIH0sXG4gICAgICBjdXN0b21SdWxlczoge1xuICAgICAgICB0ZDogW1tcIlt0ZF17U0VMVEVYVH1bL3RkXVwiLCB7c2VsdGV4dDoge3JneDogZmFsc2UsIGF0dHI6IGZhbHNlLCBzZWw6IGZhbHNlfX1dXSxcbiAgICAgICAgdHI6IFtbXCJbdHJde1NFTFRFWFR9Wy90cl1cIiwge3NlbHRleHQ6IHtyZ3g6IGZhbHNlLCBhdHRyOiBmYWxzZSwgc2VsOiBmYWxzZX19XV0sXG4gICAgICAgIHRhYmxlOiBbW1wiW3RhYmxlXXtTRUxURVhUfVsvdGFibGVdXCIsIHtzZWx0ZXh0OiB7cmd4OiBmYWxzZSwgYXR0cjogZmFsc2UsIHNlbDogZmFsc2V9fV1dXG4gICAgICAgIC8vYmxvY2txdW90ZTogW1tcIiAgIHtTRUxURVhUfVwiLHtzZWx0ZXh0OiB7cmd4OmZhbHNlLGF0dHI6ZmFsc2Usc2VsOmZhbHNlfX1dXVxuICAgICAgfSxcbiAgICAgIHNtaWxlTGlzdDogW1xuICAgICAgICAvL3t0aXRsZTpDVVJMQU5HLnNtMSwgaW1nOiAnPGltZyBzcmM9XCJ7dGhlbWVQcmVmaXh9e3RoZW1lTmFtZX0vaW1nL3NtaWxlcy9zbTEucG5nXCIgY2xhc3M9XCJzbVwiPicsIGJiY29kZTpcIjopXCJ9LFxuICAgICAgXSxcbiAgICAgIGF0dHJXcmFwOiBbJ3NyYycsICdjb2xvcicsICdocmVmJ10gLy91c2UgYmVjb3VzZSBGRiBhbmQgSUUgY2hhbmdlIHZhbHVlcyBmb3IgdGhpcyBhdHRyLCBtb2RpZnkgW2F0dHJdIHRvIF9bYXR0cl1cbiAgICB9XG5cbiAgICAvL0ZJWCBmb3IgT3BlcmEuIFdhaXQgd2hpbGUgaWZyYW1lIGxvYWRlZFxuICAgIHRoaXMuaW5pdGVkID0gdGhpcy5vcHRpb25zLm9ubHlCQm1vZGU7XG5cbiAgICAvL2luaXQgY3NzIHByZWZpeCwgaWYgbm90IHNldFxuICAgIGlmICghdGhpcy5vcHRpb25zLnRoZW1lUHJlZml4KSB7XG4gICAgICAkKCdsaW5rJykuZWFjaCgkLnByb3h5KGZ1bmN0aW9uIChpZHgsIGVsKSB7XG4gICAgICAgIHZhciBzcmlwdE1hdGNoID0gJChlbCkuZ2V0KDApLmhyZWYubWF0Y2goLyguKlxcLykoLiopXFwvd2JidGhlbWVcXC5jc3MuKiQvKTtcbiAgICAgICAgaWYgKHNyaXB0TWF0Y2ggIT09IG51bGwpIHtcbiAgICAgICAgICB0aGlzLm9wdGlvbnMudGhlbWVOYW1lID0gc3JpcHRNYXRjaFsyXTtcbiAgICAgICAgICB0aGlzLm9wdGlvbnMudGhlbWVQcmVmaXggPSBzcmlwdE1hdGNoWzFdO1xuICAgICAgICB9XG4gICAgICB9LCB0aGlzKSk7XG4gICAgfVxuXG4gICAgLy9jaGVjayBmb3IgcHJlc2V0XG4gICAgaWYgKHR5cGVvZiAoV0JCUFJFU0VUKSAhPSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICBpZiAoV0JCUFJFU0VULmFsbEJ1dHRvbnMpIHtcbiAgICAgICAgLy9jbGVhciB0cmFuc2Zvcm1cbiAgICAgICAgJC5lYWNoKFdCQlBSRVNFVC5hbGxCdXR0b25zLCAkLnByb3h5KGZ1bmN0aW9uIChrLCB2KSB7XG4gICAgICAgICAgaWYgKHYudHJhbnNmb3JtICYmIHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2tdKSB7XG4gICAgICAgICAgICBkZWxldGUgdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNba10udHJhbnNmb3JtO1xuICAgICAgICAgIH1cbiAgICAgICAgfSwgdGhpcykpO1xuICAgICAgfVxuICAgICAgJC5leHRlbmQodHJ1ZSwgdGhpcy5vcHRpb25zLCBXQkJQUkVTRVQpO1xuICAgIH1cblxuICAgIGlmIChzZXR0aW5ncyAmJiBzZXR0aW5ncy5hbGxCdXR0b25zKSB7XG4gICAgICAkLmVhY2goc2V0dGluZ3MuYWxsQnV0dG9ucywgJC5wcm94eShmdW5jdGlvbiAoaywgdikge1xuICAgICAgICBpZiAodi50cmFuc2Zvcm0gJiYgdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNba10pIHtcbiAgICAgICAgICBkZWxldGUgdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNba10udHJhbnNmb3JtO1xuICAgICAgICB9XG4gICAgICB9LCB0aGlzKSk7XG4gICAgfVxuICAgICQuZXh0ZW5kKHRydWUsIHRoaXMub3B0aW9ucywgc2V0dGluZ3MpO1xuICAgIHRoaXMuaW5pdCgpO1xuICB9XG5cbiAgJC53eXNpYmIucHJvdG90eXBlID0ge1xuICAgIGxhc3RpZDogMSxcbiAgICBpbml0OiBmdW5jdGlvbiAoKSB7XG4gICAgICAkLmxvZyhcIkluaXRcIiwgdGhpcyk7XG4gICAgICAvL2NoZWNrIGZvciBtb2JpbGVcbiAgICAgIHRoaXMuaXNNb2JpbGUgPSBmdW5jdGlvbiAoYSkge1xuICAgICAgICAoL2FuZHJvaWR8YXZhbnRnb3xiYWRhXFwvfGJsYWNrYmVycnl8YmxhemVyfGNvbXBhbHxlbGFpbmV8ZmVubmVjfGhpcHRvcHxpZW1vYmlsZXxpcChob25lfG9kKXxpcmlzfGtpbmRsZXxsZ2UgfG1hZW1vfG1lZWdvLittb2JpbGV8bWlkcHxtbXB8bmV0ZnJvbnR8b3BlcmEgbShvYnxpbilpfHBhbG0oIG9zKT98cGhvbmV8cChpeGl8cmUpXFwvfHBsdWNrZXJ8cG9ja2V0fHBzcHxzZXJpZXMoNHw2KTB8c3ltYmlhbnx0cmVvfHVwXFwuKGJyb3dzZXJ8bGluayl8dm9kYWZvbmV8d2FwfHdpbmRvd3MgKGNlfHBob25lKXx4ZGF8eGlpbm8vaS50ZXN0KGEpKVxuICAgICAgfShuYXZpZ2F0b3IudXNlckFnZW50IHx8IG5hdmlnYXRvci52ZW5kb3IgfHwgd2luZG93Lm9wZXJhKTtcblxuICAgICAgLy91c2UgYmJtb2RlIG9uIG1vYmlsZSBkZXZpY2VzXG4gICAgICAvL3RoaXMuaXNNb2JpbGUgPSB0cnVlOyAvL1RFTVBcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMub25seUJCbW9kZSA9PT0gdHJ1ZSkge1xuICAgICAgICB0aGlzLm9wdGlvbnMuYmJtb2RlID0gdHJ1ZTtcbiAgICAgIH1cbiAgICAgIC8vY3JlYXRlIGFycmF5IG9mIGNvbnRyb2xzLCBmb3IgcXVlcnlTdGF0ZVxuICAgICAgdGhpcy5jb250cm9sbGVycyA9IFtdO1xuXG4gICAgICAvL2NvbnZlcnQgYnV0dG9uIHN0cmluZyB0byBhcnJheVxuICAgICAgdGhpcy5vcHRpb25zLmJ1dHRvbnMgPSB0aGlzLm9wdGlvbnMuYnV0dG9ucy50b0xvd2VyQ2FzZSgpO1xuICAgICAgdGhpcy5vcHRpb25zLmJ1dHRvbnMgPSB0aGlzLm9wdGlvbnMuYnV0dG9ucy5zcGxpdChcIixcIik7XG5cbiAgICAgIC8vaW5pdCBzeXN0ZW0gdHJhbnNmb3Jtc1xuICAgICAgdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbXCJfc3lzdHJcIl0gPSB7fTtcbiAgICAgIHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW1wiX3N5c3RyXCJdW1widHJhbnNmb3JtXCJdID0gdGhpcy5vcHRpb25zLnN5c3RyO1xuXG4gICAgICB0aGlzLnNtaWxlRmluZCgpO1xuICAgICAgdGhpcy5pbml0VHJhbnNmb3JtcygpO1xuICAgICAgdGhpcy5idWlsZCgpO1xuICAgICAgdGhpcy5pbml0TW9kYWwoKTtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuaG90a2V5cyA9PT0gdHJ1ZSAmJiAhdGhpcy5pc01vYmlsZSkge1xuICAgICAgICB0aGlzLmluaXRIb3RrZXlzKCk7XG4gICAgICB9XG5cbiAgICAgIC8vc29ydCBzbWlsZXNcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuc21pbGVMaXN0ICYmIHRoaXMub3B0aW9ucy5zbWlsZUxpc3QubGVuZ3RoID4gMCkge1xuICAgICAgICB0aGlzLm9wdGlvbnMuc21pbGVMaXN0LnNvcnQoZnVuY3Rpb24gKGEsIGIpIHtcbiAgICAgICAgICByZXR1cm4gKGIuYmJjb2RlLmxlbmd0aCAtIGEuYmJjb2RlLmxlbmd0aCk7XG4gICAgICAgIH0pXG4gICAgICB9XG5cbiAgICAgIHRoaXMuJHR4dEFyZWEucGFyZW50cyhcImZvcm1cIikuYmluZChcInN1Ym1pdFwiLCAkLnByb3h5KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5zeW5jKCk7XG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgfSwgdGhpcykpO1xuXG5cbiAgICAgIC8vcGhwYmIyXG4gICAgICB0aGlzLiR0eHRBcmVhLnBhcmVudHMoXCJmb3JtXCIpLmZpbmQoXCJpbnB1dFtpZCo9J3ByZXZpZXcnXSxpbnB1dFtpZCo9J3N1Ym1pdCddLGlucHV0W2NsYXNzKj0ncHJldmlldyddLGlucHV0W2NsYXNzKj0nc3VibWl0J10saW5wdXRbbmFtZSo9J3ByZXZpZXcnXSxpbnB1dFtuYW1lKj0nc3VibWl0J11cIikuYmluZChcIm1vdXNlZG93blwiLCAkLnByb3h5KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy5zeW5jKCk7XG4gICAgICAgIHNldFRpbWVvdXQoJC5wcm94eShmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5iYm1vZGUgPT09IGZhbHNlKSB7XG4gICAgICAgICAgICB0aGlzLiR0eHRBcmVhLnJlbW92ZUF0dHIoXCJ3YmJzeW5jXCIpLnZhbChcIlwiKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0sIHRoaXMpLCAxMDAwKTtcbiAgICAgIH0sIHRoaXMpKTtcbiAgICAgIC8vZW5kIHBocGJiMlxuXG4gICAgICBpZiAodGhpcy5vcHRpb25zLmluaXRDYWxsYmFjaykge1xuICAgICAgICB0aGlzLm9wdGlvbnMuaW5pdENhbGxiYWNrLmNhbGwodGhpcyk7XG4gICAgICB9XG5cbiAgICAgICQubG9nKHRoaXMpO1xuXG4gICAgfSxcbiAgICBpbml0VHJhbnNmb3JtczogZnVuY3Rpb24gKCkge1xuICAgICAgJC5sb2coXCJDcmVhdGUgcnVsZXMgZm9yIHRyYW5zZm9ybSBIVE1MPT5CQlwiKTtcbiAgICAgIHZhciBvID0gdGhpcy5vcHRpb25zO1xuICAgICAgLy9uZWVkIHRvIGNoZWNrIGZvciBhY3RpdmUgYnV0dG9uc1xuICAgICAgaWYgKCFvLnJ1bGVzKSB7XG4gICAgICAgIG8ucnVsZXMgPSB7fTtcbiAgICAgIH1cbiAgICAgIGlmICghby5ncm91cHMpIHtcbiAgICAgICAgby5ncm91cHMgPSB7fTtcbiAgICAgIH0gLy91c2UgZm9yIGdyb3Vwa2V5LCBGb3IgZXhhbXBsZToganVzdGlmeWxlZnQsanVzdGlmeXJpZ2h0LGp1c3RpZnljZW50ZXIuIEl0IGlzIG11c3QgcmVwbGFjZSBlYWNoIG90aGVyLlxuICAgICAgdmFyIGJ0bmxpc3QgPSBvLmJ1dHRvbnMuc2xpY2UoKTtcblxuICAgICAgLy9hZGQgc3lzdGVtIHRyYW5zZm9ybVxuICAgICAgYnRubGlzdC5wdXNoKFwiX3N5c3RyXCIpO1xuICAgICAgZm9yICh2YXIgYmlkeCA9IDA7IGJpZHggPCBidG5saXN0Lmxlbmd0aDsgYmlkeCsrKSB7XG4gICAgICAgIHZhciBvYiA9IG8uYWxsQnV0dG9uc1tidG5saXN0W2JpZHhdXTtcbiAgICAgICAgaWYgKCFvYikge1xuICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICB9XG4gICAgICAgIG9iLmVuID0gdHJ1ZTtcblxuICAgICAgICAvL2NoZWNrIGZvciBzaW1wbGViYmNvZGVcbiAgICAgICAgaWYgKG9iLnNpbXBsZWJiY29kZSAmJiAkLmlzQXJyYXkob2Iuc2ltcGxlYmJjb2RlKSAmJiBvYi5zaW1wbGViYmNvZGUubGVuZ3RoID09IDIpIHtcbiAgICAgICAgICBvYi5iYmNvZGUgPSBvYi5odG1sID0gb2Iuc2ltcGxlYmJjb2RlWzBdICsgXCJ7U0VMVEVYVH1cIiArIG9iLnNpbXBsZWJiY29kZVsxXTtcbiAgICAgICAgICBpZiAob2IudHJhbnNmb3JtKSBkZWxldGUgb2IudHJhbnNmb3JtO1xuICAgICAgICAgIGlmIChvYi5tb2RhbCkgZGVsZXRlIG9iLm1vZGFsO1xuICAgICAgICB9XG5cbiAgICAgICAgLy9hZGQgdHJhbnNmb3JtcyB0byBvcHRpb24gbGlzdFxuICAgICAgICBpZiAob2IudHlwZSA9PSBcInNlbGVjdFwiICYmIHR5cGVvZiAob2Iub3B0aW9ucykgPT0gXCJzdHJpbmdcIikge1xuICAgICAgICAgIHZhciBvbGlzdCA9IG9iLm9wdGlvbnMuc3BsaXQoXCIsXCIpO1xuICAgICAgICAgICQuZWFjaChvbGlzdCwgZnVuY3Rpb24gKGksIG9wKSB7XG4gICAgICAgICAgICBpZiAoJC5pbkFycmF5KG9wLCBidG5saXN0KSA9PSAtMSkge1xuICAgICAgICAgICAgICBidG5saXN0LnB1c2gob3ApO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgICAgIGlmIChvYi50cmFuc2Zvcm0gJiYgb2Iuc2tpcFJ1bGVzICE9PSB0cnVlKSB7XG4gICAgICAgICAgdmFyIG9idHIgPSAkLmV4dGVuZCh7fSwgb2IudHJhbnNmb3JtKTtcblxuICAgICAgICAgIC8qIGlmIChvYi5hZGRXcmFwKSB7XG5cdFx0XHRcdFx0XHQvL2FkZFdyYXBcblx0XHRcdFx0XHRcdCQubG9nKFwibmVlZFdyYXBcIik7XG5cdFx0XHRcdFx0XHRmb3IgKHZhciBiaHRtbCBpbiBvYnRyKSB7XG5cdFx0XHRcdFx0XHRcdHZhciBiYmNvZGUgPSBvYi50cmFuc2Zvcm1bYmh0bWxdO1xuXHRcdFx0XHRcdFx0XHR2YXIgbmV3aHRtbCA9ICc8c3BhbiB3YmI9XCInK2J0bmxpc3RbYmlkeF0rJ1wiPicrYmh0bWwrJzwvc3Bhbj4nO1xuXHRcdFx0XHRcdFx0XHRvYnRyW25ld2h0bWxdID0gYmJjb2RlO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdH0gKi9cblxuICAgICAgICAgIGZvciAodmFyIGJodG1sIGluIG9idHIpIHtcbiAgICAgICAgICAgIHZhciBvcmlnaHRtbCA9IGJodG1sO1xuICAgICAgICAgICAgdmFyIGJiY29kZSA9IG9idHJbYmh0bWxdO1xuXG4gICAgICAgICAgICAvL2NyZWF0ZSByb290IHNlbGVjdG9yIGZvciBpc0NvbnRhaW4gYmJtb2RlXG4gICAgICAgICAgICBpZiAoIW9iLmJiU2VsZWN0b3IpIHtcbiAgICAgICAgICAgICAgb2IuYmJTZWxlY3RvciA9IFtdO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKCQuaW5BcnJheShiYmNvZGUsIG9iLmJiU2VsZWN0b3IpID09IC0xKSB7XG4gICAgICAgICAgICAgIG9iLmJiU2VsZWN0b3IucHVzaChiYmNvZGUpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5vbmx5QkJtb2RlID09PSBmYWxzZSkge1xuXG4gICAgICAgICAgICAgIC8vd3JhcCBhdHRyaWJ1dGVzXG4gICAgICAgICAgICAgIGJodG1sID0gdGhpcy53cmFwQXR0cnMoYmh0bWwpO1xuXG5cbiAgICAgICAgICAgICAgdmFyICRiZWwgPSAkKGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ0RJVicpKS5hcHBlbmQoJCh0aGlzLmVsRnJvbVN0cmluZyhiaHRtbCwgZG9jdW1lbnQpKSk7XG4gICAgICAgICAgICAgIHZhciByb290U2VsZWN0b3IgPSB0aGlzLmZpbHRlckJ5Tm9kZSgkYmVsLmNoaWxkcmVuKCkpO1xuXG5cbiAgICAgICAgICAgICAgLy9jaGVjayBpZiBjdXJyZW50IHJvb3RTZWxlY3RvciBpcyBleGlzdCwgY3JlYXRlIHVuaXF1ZSBzZWxlY3RvciBmb3IgZWFjaCB0cmFuc2Zvcm0gKDEuMi4yKVxuICAgICAgICAgICAgICBpZiAocm9vdFNlbGVjdG9yID09IFwiZGl2XCIgfHwgdHlwZW9mIChvLnJ1bGVzW3Jvb3RTZWxlY3Rvcl0pICE9IFwidW5kZWZpbmVkXCIpIHtcbiAgICAgICAgICAgICAgICAvL2NyZWF0ZSB1bmlxdWUgc2VsZWN0b3JcbiAgICAgICAgICAgICAgICAkLmxvZyhcImNyZWF0ZSB1bmlxdWUgc2VsZWN0b3I6IFwiICsgcm9vdFNlbGVjdG9yKTtcbiAgICAgICAgICAgICAgICB0aGlzLnNldFVJRCgkYmVsLmNoaWxkcmVuKCkpO1xuICAgICAgICAgICAgICAgIHJvb3RTZWxlY3RvciA9IHRoaXMuZmlsdGVyQnlOb2RlKCRiZWwuY2hpbGRyZW4oKSk7XG4gICAgICAgICAgICAgICAgJC5sb2coXCJOZXcgcm9vdFNlbGVjdG9yOiBcIiArIHJvb3RTZWxlY3Rvcik7XG4gICAgICAgICAgICAgICAgLy9yZXBsYWNlIHRyYW5zZm9ybSB3aXRoIHVuaXF1ZSBzZWxlY3RvclxuICAgICAgICAgICAgICAgIHZhciBuaHRtbDIgPSAkYmVsLmh0bWwoKTtcbiAgICAgICAgICAgICAgICBuaHRtbDIgPSB0aGlzLnVud3JhcEF0dHJzKG5odG1sMik7XG4gICAgICAgICAgICAgICAgdmFyIG9iaHRtbCA9IHRoaXMudW53cmFwQXR0cnMoYmh0bWwpO1xuXG5cbiAgICAgICAgICAgICAgICBvYi50cmFuc2Zvcm1bbmh0bWwyXSA9IGJiY29kZTtcbiAgICAgICAgICAgICAgICBkZWxldGUgb2IudHJhbnNmb3JtW29iaHRtbF07XG5cbiAgICAgICAgICAgICAgICBiaHRtbCA9IG5odG1sMjtcbiAgICAgICAgICAgICAgICBvcmlnaHRtbCA9IG5odG1sMjtcbiAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgIC8vY3JlYXRlIHJvb3Qgc2VsZWN0b3IgZm9yIGlzQ29udGFpblxuICAgICAgICAgICAgICBpZiAoIW9iLmV4Y21kKSB7XG4gICAgICAgICAgICAgICAgaWYgKCFvYi5yb290U2VsZWN0b3IpIHtcbiAgICAgICAgICAgICAgICAgIG9iLnJvb3RTZWxlY3RvciA9IFtdO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBvYi5yb290U2VsZWN0b3IucHVzaChyb290U2VsZWN0b3IpO1xuICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgLy9jaGVjayBmb3IgcnVsZXMgb24gdGhpcyByb290U2VsZXRvclxuICAgICAgICAgICAgICBpZiAodHlwZW9mIChvLnJ1bGVzW3Jvb3RTZWxlY3Rvcl0pID09IFwidW5kZWZpbmVkXCIpIHtcbiAgICAgICAgICAgICAgICBvLnJ1bGVzW3Jvb3RTZWxlY3Rvcl0gPSBbXTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICB2YXIgY3J1bGVzID0ge307XG5cbiAgICAgICAgICAgICAgaWYgKGJodG1sLm1hdGNoKC9cXHtcXFMrP1xcfS8pKSB7XG4gICAgICAgICAgICAgICAgJGJlbC5maW5kKCcqJykuZWFjaCgkLnByb3h5KGZ1bmN0aW9uIChpZHgsIGVsKSB7XG4gICAgICAgICAgICAgICAgICAvL2NoZWNrIGF0dHJpYnV0ZXNcblxuICAgICAgICAgICAgICAgICAgdmFyIGF0dHJpYnV0ZXMgPSB0aGlzLmdldEF0dHJpYnV0ZUxpc3QoZWwpO1xuICAgICAgICAgICAgICAgICAgJC5lYWNoKGF0dHJpYnV0ZXMsICQucHJveHkoZnVuY3Rpb24gKGksIGl0ZW0pIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyIGF0dHIgPSAkKGVsKS5hdHRyKGl0ZW0pO1xuICAgICAgICAgICAgICAgICAgICBpZiAoaXRlbS5zdWJzdHIoMCwgMSkgPT0gJ18nKSB7XG4gICAgICAgICAgICAgICAgICAgICAgaXRlbSA9IGl0ZW0uc3Vic3RyKDEpO1xuICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgdmFyIHIgPSBhdHRyLm1hdGNoKC9cXHtcXFMrP1xcfS9nKTtcbiAgICAgICAgICAgICAgICAgICAgaWYgKHIpIHtcbiAgICAgICAgICAgICAgICAgICAgICBmb3IgKHZhciBhID0gMDsgYSA8IHIubGVuZ3RoOyBhKyspIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBybmFtZSA9IHJbYV0uc3Vic3RyKDEsIHJbYV0ubGVuZ3RoIC0gMik7XG4gICAgICAgICAgICAgICAgICAgICAgICBybmFtZSA9IHJuYW1lLnJlcGxhY2UodGhpcy5nZXRWYWxpZGF0aW9uUkdYKHJuYW1lKSwgXCJcIik7XG4gICAgICAgICAgICAgICAgICAgICAgICB2YXIgcCA9IHRoaXMucmVsRmlsdGVyQnlOb2RlKGVsLCByb290U2VsZWN0b3IpO1xuICAgICAgICAgICAgICAgICAgICAgICAgdmFyIHJlZ1JlcGwgPSAoYXR0ciAhPSByW2FdKSA/IHRoaXMuZ2V0UmVnZXhwUmVwbGFjZShhdHRyLCByW2FdKSA6IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAgICAgY3J1bGVzW3JuYW1lLnRvTG93ZXJDYXNlKCldID0ge3NlbDogKHApID8gJC50cmltKHApIDogZmFsc2UsIGF0dHI6IGl0ZW0sIHJneDogcmVnUmVwbH1cbiAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgICAgICAgICAgICAgLy9jaGVjayBmb3IgdGV4dFxuICAgICAgICAgICAgICAgICAgdmFyIHNsID0gW107XG4gICAgICAgICAgICAgICAgICBpZiAoISQoZWwpLmlzKFwiaWZyYW1lXCIpKSB7XG4gICAgICAgICAgICAgICAgICAgICQoZWwpLmNvbnRlbnRzKCkuZmlsdGVyKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy5ub2RlVHlwZSA9PT0gM1xuICAgICAgICAgICAgICAgICAgICB9KS5lYWNoKCQucHJveHkoZnVuY3Rpb24gKGksIHJlbCkge1xuICAgICAgICAgICAgICAgICAgICAgIHZhciB0eHQgPSByZWwudGV4dENvbnRlbnQgfHwgcmVsLmRhdGE7XG4gICAgICAgICAgICAgICAgICAgICAgaWYgKHR5cGVvZiAodHh0KSA9PSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgdmFyIHIgPSB0eHQubWF0Y2goL1xce1xcUys/XFx9L2cpXG4gICAgICAgICAgICAgICAgICAgICAgaWYgKHIpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGZvciAodmFyIGEgPSAwOyBhIDwgci5sZW5ndGg7IGErKykge1xuICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgcm5hbWUgPSByW2FdLnN1YnN0cigxLCByW2FdLmxlbmd0aCAtIDIpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICBybmFtZSA9IHJuYW1lLnJlcGxhY2UodGhpcy5nZXRWYWxpZGF0aW9uUkdYKHJuYW1lKSwgXCJcIik7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBwID0gdGhpcy5yZWxGaWx0ZXJCeU5vZGUoZWwsIHJvb3RTZWxlY3Rvcik7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIHZhciByZWdSZXBsID0gKHR4dCAhPSByW2FdKSA/IHRoaXMuZ2V0UmVnZXhwUmVwbGFjZSh0eHQsIHJbYV0pIDogZmFsc2U7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBzZWwgPSAocCkgPyAkLnRyaW0ocCkgOiBmYWxzZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCQuaW5BcnJheShzZWwsIHNsKSA+IC0xIHx8ICQocmVsKS5wYXJlbnQoKS5jb250ZW50cygpLmxlbmd0aCA+IDEpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvL2hhcyBkdWJsaWNhdGUgYW5kIG5vdCBvbmUgY2hpbGRyZW4sIG5lZWQgd3JhcFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBuZWwgPSAkKFwiPHNwYW4+XCIpLmh0bWwoXCJ7XCIgKyBybmFtZSArIFwifVwiKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0aGlzLnNldFVJRChuZWwsIFwid2JiXCIpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciBzdGFydCA9ICh0eHQuaW5kZXhPZihybmFtZSkgKyBybmFtZS5sZW5ndGgpICsgMTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgYWZ0ZXJfdHh0ID0gdHh0LnN1YnN0cihzdGFydCwgdHh0Lmxlbmd0aCAtIHN0YXJ0KTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvL2NyZWF0ZSB3cmFwIGVsZW1lbnRcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICByZWwuZGF0YSA9IHR4dC5zdWJzdHIoMCwgdHh0LmluZGV4T2Yocm5hbWUpIC0gMSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJChyZWwpLmFmdGVyKHRoaXMuZWxGcm9tU3RyaW5nKGFmdGVyX3R4dCwgZG9jdW1lbnQpKS5hZnRlcihuZWwpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsID0gKChzZWwpID8gc2VsICsgXCIgXCIgOiBcIlwiKSArIHRoaXMuZmlsdGVyQnlOb2RlKG5lbCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmVnUmVwbCA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgIGNydWxlc1tybmFtZS50b0xvd2VyQ2FzZSgpXSA9IHtzZWw6IHNlbCwgYXR0cjogZmFsc2UsIHJneDogcmVnUmVwbH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgc2xbc2wubGVuZ3RoXSA9IHNlbDtcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgIHNsID0gbnVsbDtcblxuXG4gICAgICAgICAgICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAgICAgICAgICAgdmFyIG5iaHRtbCA9ICRiZWwuaHRtbCgpO1xuICAgICAgICAgICAgICAgIC8vVW5XcmFwIGF0dHJpYnV0ZXNcbiAgICAgICAgICAgICAgICBuYmh0bWwgPSB0aGlzLnVud3JhcEF0dHJzKG5iaHRtbCk7XG4gICAgICAgICAgICAgICAgaWYgKG9yaWdodG1sICE9IG5iaHRtbCkge1xuICAgICAgICAgICAgICAgICAgLy9pZiB3ZSBtb2RpZnkgaHRtbCwgcmVwbGFjZSBpdFxuICAgICAgICAgICAgICAgICAgZGVsZXRlIG9iLnRyYW5zZm9ybVtvcmlnaHRtbF07XG4gICAgICAgICAgICAgICAgICBvYi50cmFuc2Zvcm1bbmJodG1sXSA9IGJiY29kZTtcbiAgICAgICAgICAgICAgICAgIGJodG1sID0gbmJodG1sO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIG8ucnVsZXNbcm9vdFNlbGVjdG9yXS5wdXNoKFtiYmNvZGUsIGNydWxlc10pO1xuXG4gICAgICAgICAgICAgIC8vY2hlY2sgZm9yIG9ubHlDbGVhclRleHRcbiAgICAgICAgICAgICAgaWYgKG9iLm9ubHlDbGVhclRleHQgPT09IHRydWUpIHtcbiAgICAgICAgICAgICAgICBpZiAoIXRoaXMuY2xlYXJ0ZXh0KSB7XG4gICAgICAgICAgICAgICAgICB0aGlzLmNsZWFydGV4dCA9IHt9O1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB0aGlzLmNsZWFydGV4dFtyb290U2VsZWN0b3JdID0gYnRubGlzdFtiaWR4XTtcbiAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgIC8vY2hlY2sgZm9yIGdyb3Vwa2V5XG4gICAgICAgICAgICAgIGlmIChvYi5ncm91cGtleSkge1xuICAgICAgICAgICAgICAgIGlmICghby5ncm91cHNbb2IuZ3JvdXBrZXldKSB7XG4gICAgICAgICAgICAgICAgICBvLmdyb3Vwc1tvYi5ncm91cGtleV0gPSBbXVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBvLmdyb3Vwc1tvYi5ncm91cGtleV0ucHVzaChyb290U2VsZWN0b3IpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgLy9zb3J0IHJvb3RTZWxlY3RvclxuICAgICAgICAgIGlmIChvYi5yb290U2VsZWN0b3IpIHtcbiAgICAgICAgICAgIHRoaXMuc29ydEFycmF5KG9iLnJvb3RTZWxlY3RvciwgLTEpO1xuICAgICAgICAgIH1cblxuICAgICAgICAgIHZhciBodG1sbCA9ICQubWFwKG9iLnRyYW5zZm9ybSwgZnVuY3Rpb24gKGJiLCBodG1sKSB7XG4gICAgICAgICAgICByZXR1cm4gaHRtbFxuICAgICAgICAgIH0pLnNvcnQoZnVuY3Rpb24gKGEsIGIpIHtcbiAgICAgICAgICAgIHJldHVybiAoKGJbMF0gfHwgXCJcIikubGVuZ3RoIC0gKGFbMF0gfHwgXCJcIikubGVuZ3RoKVxuICAgICAgICAgIH0pO1xuICAgICAgICAgIG9iLmJiY29kZSA9IG9iLnRyYW5zZm9ybVtodG1sbFswXV07XG4gICAgICAgICAgb2IuaHRtbCA9IGh0bWxsWzBdO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgICA7XG5cbiAgICAgIHRoaXMub3B0aW9ucy5idG5saXN0ID0gYnRubGlzdDsgLy91c2UgZm9yIHRyYW5zZm9ybXMsIGJlY291c2Ugc2VsZWN0IGVsZW1lbnRzIG5vdCBwcmVzZW50IGluIGJ1dHRvbnNcblxuICAgICAgLy9hZGQgY3VzdG9tIHJ1bGVzLCBmb3IgdGFibGUsdHIsdGQgYW5kIG90aGVyXG4gICAgICAkLmV4dGVuZChvLnJ1bGVzLCB0aGlzLm9wdGlvbnMuY3VzdG9tUnVsZXMpO1xuXG4gICAgICAvL3NtaWxlIHJ1bGVzXG4gICAgICBvLnNydWxlcyA9IHt9O1xuICAgICAgaWYgKHRoaXMub3B0aW9ucy5zbWlsZUxpc3QpIHtcbiAgICAgICAgJC5lYWNoKG8uc21pbGVMaXN0LCAkLnByb3h5KGZ1bmN0aW9uIChpLCBzbSkge1xuICAgICAgICAgIHZhciAkc20gPSAkKHRoaXMuc3RyZihzbS5pbWcsIG8pKTtcbiAgICAgICAgICB2YXIgZiA9IHRoaXMuZmlsdGVyQnlOb2RlKCRzbSk7XG4gICAgICAgICAgby5zcnVsZXNbZl0gPSBbc20uYmJjb2RlLCBzbS5pbWddO1xuICAgICAgICB9LCB0aGlzKSk7XG4gICAgICB9XG5cbiAgICAgIC8vc29ydCB0cmFuc2Zvcm1zIGJ5IGJiY29kZSBsZW5ndGggZGVzY1xuICAgICAgZm9yICh2YXIgcm9vdHNlbCBpbiBvLnJ1bGVzKSB7XG4gICAgICAgIHRoaXMub3B0aW9ucy5ydWxlc1tyb290c2VsXS5zb3J0KGZ1bmN0aW9uIChhLCBiKSB7XG4gICAgICAgICAgcmV0dXJuIChiWzBdLmxlbmd0aCAtIGFbMF0ubGVuZ3RoKVxuICAgICAgICB9KTtcbiAgICAgIH1cblxuICAgICAgLy9jcmVhdGUgcm9vdHNlbCBsaXN0XG4gICAgICB0aGlzLnJzZWxsaXN0ID0gW107XG4gICAgICBmb3IgKHZhciByb290c2VsIGluIHRoaXMub3B0aW9ucy5ydWxlcykge1xuICAgICAgICB0aGlzLnJzZWxsaXN0LnB1c2gocm9vdHNlbCk7XG4gICAgICB9XG4gICAgICB0aGlzLnNvcnRBcnJheSh0aGlzLnJzZWxsaXN0LCAtMSk7XG4gICAgfSxcblxuICAgIC8vQlVJTERcbiAgICBidWlsZDogZnVuY3Rpb24gKCkge1xuICAgICAgJC5sb2coXCJCdWlsZCBlZGl0b3JcIik7XG5cbiAgICAgIC8vdGhpcy4kZWRpdG9yID0gJCgnPGRpdiBjbGFzcz1cInd5c2liYlwiPicpO1xuICAgICAgdGhpcy4kZWRpdG9yID0gJCgnPGRpdj4nKS5hZGRDbGFzcyhcInd5c2liYlwiKTtcblxuICAgICAgaWYgKHRoaXMuaXNNb2JpbGUpIHtcbiAgICAgICAgdGhpcy4kZWRpdG9yLmFkZENsYXNzKFwid3lzaWJiLW1vYmlsZVwiKTtcbiAgICAgIH1cblxuICAgICAgLy9zZXQgZGlyZWN0aW9uIGlmIGRlZmluZWRcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuZGlyZWN0aW9uKSB7XG4gICAgICAgIHRoaXMuJGVkaXRvci5jc3MoXCJkaXJlY3Rpb25cIiwgdGhpcy5vcHRpb25zLmRpcmVjdGlvbilcbiAgICAgIH1cblxuICAgICAgdGhpcy4kZWRpdG9yLmluc2VydEFmdGVyKHRoaXMudHh0QXJlYSkuYXBwZW5kKHRoaXMudHh0QXJlYSk7XG5cbiAgICAgIHRoaXMuc3RhcnRIZWlnaHQgPSB0aGlzLiR0eHRBcmVhLm91dGVySGVpZ2h0KCk7XG4gICAgICB0aGlzLiR0eHRBcmVhLmFkZENsYXNzKFwid3lzaWJiLXRleGFyZWFcIik7XG4gICAgICB0aGlzLmJ1aWxkVG9vbGJhcigpO1xuICAgICAgLy9CdWlsZCBpZnJhbWUgaWYgbmVlZGVkXG4gICAgICB0aGlzLiR0eHRBcmVhLndyYXAoJzxkaXYgY2xhc3M9XCJ3eXNpYmItdGV4dFwiPicpO1xuXG4gICAgICBpZiAodGhpcy5vcHRpb25zLm9ubHlCQm1vZGUgPT09IGZhbHNlKSB7XG4gICAgICAgIHZhciBoZWlnaHQgPSB0aGlzLm9wdGlvbnMubWluaGVpZ2h0IHx8IHRoaXMuJHR4dEFyZWEub3V0ZXJIZWlnaHQoKTtcbiAgICAgICAgdmFyIG1heGhlaWdodCA9IHRoaXMub3B0aW9ucy5yZXNpemVfbWF4aGVpZ2h0O1xuICAgICAgICB2YXIgbWhlaWdodCA9ICh0aGlzLm9wdGlvbnMuYXV0b3Jlc2l6ZSA9PT0gdHJ1ZSkgPyB0aGlzLm9wdGlvbnMucmVzaXplX21heGhlaWdodCA6IGhlaWdodDtcbiAgICAgICAgdGhpcy4kYm9keSA9ICQodGhpcy5zdHJmKCc8ZGl2IGNsYXNzPVwid3lzaWJiLXRleHQtZWRpdG9yXCIgc3R5bGU9XCJtYXgtaGVpZ2h0OnttYXhoZWlnaHR9cHg7bWluLWhlaWdodDp7aGVpZ2h0fXB4XCI+PC9kaXY+Jywge21heGhlaWdodDogbWhlaWdodCwgaGVpZ2h0OiBoZWlnaHR9KSkuaW5zZXJ0QWZ0ZXIodGhpcy4kdHh0QXJlYSk7XG4gICAgICAgIHRoaXMuYm9keSA9IHRoaXMuJGJvZHlbMF07XG4gICAgICAgIHRoaXMuJHR4dEFyZWEuaGlkZSgpO1xuXG4gICAgICAgIGlmIChoZWlnaHQgPiAzMikge1xuICAgICAgICAgIHRoaXMuJHRvb2xiYXIuY3NzKFwibWF4LWhlaWdodFwiLCBoZWlnaHQpO1xuICAgICAgICB9XG5cbiAgICAgICAgJC5sb2coXCJXeXNpQkIgbG9hZGVkXCIpO1xuXG4gICAgICAgIHRoaXMuJGJvZHkuYWRkQ2xhc3MoXCJ3eXNpYmItYm9keVwiKS5hZGRDbGFzcyh0aGlzLm9wdGlvbnMuYm9keUNsYXNzKTtcblxuICAgICAgICAvL3NldCBkaXJlY3Rpb24gaWYgZGVmaW5lZFxuICAgICAgICBpZiAodGhpcy5vcHRpb25zLmRpcmVjdGlvbikge1xuICAgICAgICAgIHRoaXMuJGJvZHkuY3NzKFwiZGlyZWN0aW9uXCIsIHRoaXMub3B0aW9ucy5kaXJlY3Rpb24pXG4gICAgICAgIH1cblxuXG4gICAgICAgIGlmICgnY29udGVudEVkaXRhYmxlJyBpbiB0aGlzLmJvZHkpIHtcbiAgICAgICAgICB0aGlzLmJvZHkuY29udGVudEVkaXRhYmxlID0gdHJ1ZTtcbiAgICAgICAgICB0cnkge1xuICAgICAgICAgICAgLy9maXggZm9yIG1maXJlZm94XG4gICAgICAgICAgICAvL2RvY3VtZW50LmV4ZWNDb21tYW5kKCdlbmFibGVPYmplY3RSZXNpemluZycsIGZhbHNlLCAnZmFsc2UnKTsgLy9kaXNhYmxlIGltYWdlIHJlc2l6aW5nXG4gICAgICAgICAgICBkb2N1bWVudC5leGVjQ29tbWFuZCgnU3R5bGVXaXRoQ1NTJywgZmFsc2UsIGZhbHNlKTtcbiAgICAgICAgICAgIC8vZG9jdW1lbnQuZGVzaWduTW9kZSA9IFwib25cIjtcbiAgICAgICAgICAgIHRoaXMuJGJvZHkuYXBwZW5kKFwiPHNwYW4+PC9zcGFuPlwiKTtcbiAgICAgICAgICB9IGNhdGNoIChlKSB7XG4gICAgICAgICAgfVxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIC8vdXNlIG9ubHliYm1vZGVcbiAgICAgICAgICB0aGlzLm9wdGlvbnMub25seUJCbW9kZSA9IHRoaXMub3B0aW9ucy5iYm1vZGUgPSB0cnVlO1xuICAgICAgICB9XG5cbiAgICAgICAgLy9jaGVjayBmb3IgZXhpc3QgY29udGVudCBpbiB0ZXh0YXJlYVxuICAgICAgICBpZiAodGhpcy50eHRBcmVhLnZhbHVlLmxlbmd0aCA+IDApIHtcbiAgICAgICAgICB0aGlzLnR4dEFyZWFJbml0Q29udGVudCgpO1xuICAgICAgICB9XG5cblxuICAgICAgICAvL2NsZWFyIGh0bWwgb24gcGFzdGUgZnJvbSBleHRlcm5hbCBlZGl0b3JzXG4gICAgICAgIHRoaXMuJGJvZHkuYmluZCgna2V5ZG93bicsICQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICBpZiAoKGUud2hpY2ggPT0gODYgJiYgKGUuY3RybEtleSA9PSB0cnVlIHx8IGUubWV0YUtleSA9PSB0cnVlKSkgfHwgKGUud2hpY2ggPT0gNDUgJiYgKGUuc2hpZnRLZXkgPT0gdHJ1ZSB8fCBlLm1ldGFLZXkgPT0gdHJ1ZSkpKSB7XG4gICAgICAgICAgICBpZiAoIXRoaXMuJHBhc3RlQmxvY2spIHtcbiAgICAgICAgICAgICAgdGhpcy5zYXZlUmFuZ2UoKTtcbiAgICAgICAgICAgICAgdGhpcy4kcGFzdGVCbG9jayA9ICQodGhpcy5lbEZyb21TdHJpbmcoJzxkaXYgc3R5bGU9XCJvcGFjaXR5OjA7XCIgY29udGVudGVkaXRhYmxlPVwidHJ1ZVwiPlxcdUZFRkY8L2Rpdj4nKSk7XG5cbiAgICAgICAgICAgICAgdGhpcy4kcGFzdGVCbG9jay5hcHBlbmRUbyh0aGlzLmJvZHkpO1xuICAgICAgICAgICAgICAvL2lmICghJC5zdXBwb3J0LnNlYXJjaD90eXBlPTIpIHt0aGlzLiRwYXN0ZUJsb2NrLmZvY3VzKCk7fSAvL0lFIDcsOCBGSVhcbiAgICAgICAgICAgICAgc2V0VGltZW91dCgkLnByb3h5KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgIHRoaXMuY2xlYXJQYXN0ZSh0aGlzLiRwYXN0ZUJsb2NrKTtcbiAgICAgICAgICAgICAgICAgIHZhciByZGF0YSA9ICc8c3Bhbj4nICsgdGhpcy4kcGFzdGVCbG9jay5odG1sKCkgKyAnPC9zcGFuPic7XG4gICAgICAgICAgICAgICAgICB0aGlzLiRib2R5LmF0dHIoXCJjb250ZW50RWRpdGFibGVcIiwgXCJ0cnVlXCIpO1xuICAgICAgICAgICAgICAgICAgdGhpcy4kcGFzdGVCbG9jay5ibHVyKCkucmVtb3ZlKCk7XG4gICAgICAgICAgICAgICAgICB0aGlzLmJvZHkuZm9jdXMoKTtcblxuICAgICAgICAgICAgICAgICAgaWYgKHRoaXMuY2xlYXJ0ZXh0KSB7XG4gICAgICAgICAgICAgICAgICAgICQubG9nKFwiQ2hlY2sgaWYgcGFzdGUgdG8gY2xlYXJUZXh0IEJsb2NrXCIpO1xuICAgICAgICAgICAgICAgICAgICBpZiAodGhpcy5pc0luQ2xlYXJUZXh0QmxvY2soKSkge1xuICAgICAgICAgICAgICAgICAgICAgIHJkYXRhID0gdGhpcy50b0JCKHJkYXRhKS5yZXBsYWNlKC9cXG4vZywgXCI8YnIvPlwiKS5yZXBsYWNlKC9cXHN7M30vZywgJzxzcGFuIGNsYXNzPVwid2JidGFiXCI+PC9zcGFuPicpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICByZGF0YSA9IHJkYXRhLnJlcGxhY2UoL1xcdC9nLCAnPHNwYW4gY2xhc3M9XCJ3YmJ0YWJcIj48L3NwYW4+Jyk7XG4gICAgICAgICAgICAgICAgICB0aGlzLnNlbGVjdFJhbmdlKHRoaXMubGFzdFJhbmdlKTtcbiAgICAgICAgICAgICAgICAgIHRoaXMuaW5zZXJ0QXRDdXJzb3IocmRhdGEsIGZhbHNlKTtcbiAgICAgICAgICAgICAgICAgIHRoaXMubGFzdFJhbmdlID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgICB0aGlzLiRwYXN0ZUJsb2NrID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICwgdGhpcyksIDEpO1xuICAgICAgICAgICAgICB0aGlzLnNlbGVjdE5vZGUodGhpcy4kcGFzdGVCbG9ja1swXSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICB9XG4gICAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgICAvL2luc2VydCBCUiBvbiBwcmVzcyBlbnRlclxuICAgICAgICB0aGlzLiRib2R5LmJpbmQoJ2tleWRvd24nLCAkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgaWYgKGUud2hpY2ggPT0gMTMpIHtcbiAgICAgICAgICAgIHZhciBpc0xpID0gdGhpcy5pc0NvbnRhaW4odGhpcy5nZXRTZWxlY3ROb2RlKCksICdsaScpO1xuICAgICAgICAgICAgaWYgKCFpc0xpKSB7XG4gICAgICAgICAgICAgIGlmIChlLnByZXZlbnREZWZhdWx0KSB7XG4gICAgICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIHRoaXMuY2hlY2tGb3JMYXN0QlIodGhpcy5nZXRTZWxlY3ROb2RlKCkpO1xuICAgICAgICAgICAgICB0aGlzLmluc2VydEF0Q3Vyc29yKCc8YnIvPicsIGZhbHNlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9XG4gICAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgICAvL3RhYkluc2VydFxuICAgICAgICBpZiAodGhpcy5vcHRpb25zLnRhYkluc2VydCA9PT0gdHJ1ZSkge1xuICAgICAgICAgIHRoaXMuJGJvZHkuYmluZCgna2V5ZG93bicsICQucHJveHkodGhpcy5wcmVzc1RhYiwgdGhpcykpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy9hZGQgZXZlbnQgbGlzdGVuZXJzXG4gICAgICAgIHRoaXMuJGJvZHkuYmluZCgnbW91c2V1cCBrZXl1cCcsICQucHJveHkodGhpcy51cGRhdGVVSSwgdGhpcykpO1xuICAgICAgICB0aGlzLiRib2R5LmJpbmQoJ21vdXNlZG93bicsICQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICB0aGlzLmNsZWFyTGFzdFJhbmdlKCk7XG4gICAgICAgICAgdGhpcy5jaGVja0Zvckxhc3RCUihlLnRhcmdldClcbiAgICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAgIC8vdHJhY2UgVGV4dGFyZWFcbiAgICAgICAgaWYgKHRoaXMub3B0aW9ucy50cmFjZVRleHRhcmVhID09PSB0cnVlKSB7XG4gICAgICAgICAgJChkb2N1bWVudCkuYmluZChcIm1vdXNlZG93blwiLCAkLnByb3h5KHRoaXMudHJhY2VUZXh0YXJlYUV2ZW50LCB0aGlzKSk7XG4gICAgICAgICAgdGhpcy4kdHh0QXJlYS52YWwoXCJcIik7XG4gICAgICAgIH1cblxuICAgICAgICAvL2F0dGFjaCBob3RrZXlzXG4gICAgICAgIGlmICh0aGlzLm9wdGlvbnMuaG90a2V5cyA9PT0gdHJ1ZSkge1xuICAgICAgICAgIHRoaXMuJGJvZHkuYmluZCgna2V5ZG93bicsICQucHJveHkodGhpcy5wcmVzc2tleSwgdGhpcykpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy9zbWlsZUNvbnZlcnNpb25cbiAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5zbWlsZUNvbnZlcnNpb24gPT09IHRydWUpIHtcbiAgICAgICAgICB0aGlzLiRib2R5LmJpbmQoJ2tleXVwJywgJC5wcm94eSh0aGlzLnNtaWxlQ29udmVyc2lvbiwgdGhpcykpO1xuICAgICAgICB9XG5cbiAgICAgICAgdGhpcy5pbml0ZWQgPSB0cnVlO1xuXG4gICAgICAgIC8vY3JlYXRlIHJlc2l6ZSBsaW5lc1xuICAgICAgICBpZiAodGhpcy5vcHRpb25zLmF1dG9yZXNpemUgPT09IHRydWUpIHtcbiAgICAgICAgICB0aGlzLiRicmVzaXplID0gJCh0aGlzLmVsRnJvbVN0cmluZygnPGRpdiBjbGFzcz1cImJvdHRvbS1yZXNpemUtbGluZVwiPjwvZGl2PicpKS5hcHBlbmRUbyh0aGlzLiRlZGl0b3IpXG4gICAgICAgICAgICAud2RyYWcoe1xuICAgICAgICAgICAgICBzY29wZTogdGhpcyxcbiAgICAgICAgICAgICAgYXhpc1k6IHRydWUsXG4gICAgICAgICAgICAgIGhlaWdodDogaGVpZ2h0XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMuaW1nTGlzdGVuZXJzKCk7XG4gICAgICB9XG5cblxuICAgICAgLy90aGlzLiRlZGl0b3IuYXBwZW5kKCc8c3BhbiBjbGFzcz1cInBvd2VyZWRcIj5Qb3dlcmVkIGJ5IDxhIGhyZWY9XCJodHRwOi8vd3d3Lnd5c2liYi5jb21cIiB0YXJnZXQ9XCJfYmxhbmtcIj5XeXNpQkI8YS8+PC9zcGFuPicpO1xuXG4gICAgICAvL2FkZCBldmVudCBsaXN0ZW5lcnMgdG8gdGV4dGFyZWFcbiAgICAgIHRoaXMuJHR4dEFyZWEuYmluZCgnbW91c2V1cCBrZXl1cCcsICQucHJveHkoZnVuY3Rpb24gKCkge1xuICAgICAgICBjbGVhclRpbWVvdXQodGhpcy51aXRpbWVyKTtcbiAgICAgICAgdGhpcy51aXRpbWVyID0gc2V0VGltZW91dCgkLnByb3h5KHRoaXMudXBkYXRlVUksIHRoaXMpLCAxMDApO1xuICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAvL2F0dGFjaCBob3RrZXlzXG4gICAgICBpZiAodGhpcy5vcHRpb25zLmhvdGtleXMgPT09IHRydWUpIHtcbiAgICAgICAgJChkb2N1bWVudCkuYmluZCgna2V5ZG93bicsICQucHJveHkodGhpcy5wcmVzc2tleSwgdGhpcykpO1xuICAgICAgfVxuICAgIH0sXG4gICAgYnVpbGRUb29sYmFyOiBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLnRvb2xiYXIgPT09IGZhbHNlKSB7XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgIH1cblxuICAgICAgLy90aGlzLiR0b29sYmFyID0gJCgnPGRpdiBjbGFzcz1cInd5c2liYi10b29sYmFyXCI+JykucHJlcGVuZFRvKHRoaXMuJGVkaXRvcik7XG4gICAgICB0aGlzLiR0b29sYmFyID0gJCgnPGRpdj4nKS5hZGRDbGFzcyhcInd5c2liYi10b29sYmFyXCIpLnByZXBlbmRUbyh0aGlzLiRlZGl0b3IpO1xuXG4gICAgICB2YXIgJGJ0bkNvbnRhaW5lcjtcbiAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMuYnV0dG9ucywgJC5wcm94eShmdW5jdGlvbiAoaSwgYm4pIHtcbiAgICAgICAgdmFyIG9wdCA9IHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2JuXTtcbiAgICAgICAgaWYgKGkgPT0gMCB8fCBibiA9PSBcInxcIiB8fCBibiA9PSBcIi1cIikge1xuICAgICAgICAgIGlmIChibiA9PSBcIi1cIikge1xuICAgICAgICAgICAgdGhpcy4kdG9vbGJhci5hcHBlbmQoXCI8ZGl2PlwiKTtcbiAgICAgICAgICB9XG4gICAgICAgICAgJGJ0bkNvbnRhaW5lciA9ICQoJzxkaXYgY2xhc3M9XCJ3eXNpYmItdG9vbGJhci1jb250YWluZXJcIj4nKS5hcHBlbmRUbyh0aGlzLiR0b29sYmFyKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAob3B0KSB7XG4gICAgICAgICAgaWYgKG9wdC50eXBlID09IFwiY29sb3JwaWNrZXJcIikge1xuICAgICAgICAgICAgdGhpcy5idWlsZENvbG9ycGlja2VyKCRidG5Db250YWluZXIsIGJuLCBvcHQpO1xuICAgICAgICAgIH0gZWxzZSBpZiAob3B0LnR5cGUgPT0gXCJ0YWJsZVwiKSB7XG4gICAgICAgICAgICB0aGlzLmJ1aWxkVGFibGVwaWNrZXIoJGJ0bkNvbnRhaW5lciwgYm4sIG9wdCk7XG4gICAgICAgICAgfSBlbHNlIGlmIChvcHQudHlwZSA9PSBcInNlbGVjdFwiKSB7XG4gICAgICAgICAgICB0aGlzLmJ1aWxkU2VsZWN0KCRidG5Db250YWluZXIsIGJuLCBvcHQpO1xuICAgICAgICAgIH0gZWxzZSBpZiAob3B0LnR5cGUgPT0gXCJzbWlsZWJveFwiKSB7XG4gICAgICAgICAgICB0aGlzLmJ1aWxkU21pbGVib3goJGJ0bkNvbnRhaW5lciwgYm4sIG9wdCk7XG4gICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMuYnVpbGRCdXR0b24oJGJ0bkNvbnRhaW5lciwgYm4sIG9wdCk7XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9LCB0aGlzKSk7XG5cbiAgICAgIC8vZml4IGZvciBoaWRlIHRvb2x0aXAgb24gcXVpY2sgbW91c2Ugb3ZlclxuICAgICAgdGhpcy4kdG9vbGJhci5maW5kKFwiLmJ0bi10b29sdGlwXCIpLmhvdmVyKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgJCh0aGlzKS5wYXJlbnQoKS5jc3MoXCJvdmVyZmxvd1wiLCBcImhpZGRlblwiKVxuICAgICAgfSwgZnVuY3Rpb24gKCkge1xuICAgICAgICAkKHRoaXMpLnBhcmVudCgpLmNzcyhcIm92ZXJmbG93XCIsIFwidmlzaWJsZVwiKVxuICAgICAgfSk7XG5cbiAgICAgIC8vYnVpbGQgYmJjb2RlIHN3aXRjaCBidXR0b25cbiAgICAgIC8vdmFyICRiYnN3ID0gJCgnPGRpdiBjbGFzcz1cInd5c2liYi10b29sYmFyLWNvbnRhaW5lciBtb2RlU3dpdGNoXCI+PGRpdiBjbGFzcz1cInd5c2liYi10b29sYmFyLWJ0blwiIHVuc2VsZWN0YWJsZT1cIm9uXCI+PHNwYW4gY2xhc3M9XCJidG4taW5uZXIgdmUtdGxiLWJiY29kZVwiIHVuc2VsZWN0YWJsZT1cIm9uXCI+PC9zcGFuPjwvZGl2PjwvZGl2PicpLmFwcGVuZFRvKHRoaXMuJHRvb2xiYXIpO1xuICAgICAgdmFyICRiYnN3ID0gJChkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKSkuYWRkQ2xhc3MoXCJ3eXNpYmItdG9vbGJhci1jb250YWluZXIgbW9kZVN3aXRjaFwiKS5odG1sKCc8ZGl2IGNsYXNzPVwid3lzaWJiLXRvb2xiYXItYnRuIG1zd2l0Y2hcIiB1bnNlbGVjdGFibGU9XCJvblwiPjxzcGFuIGNsYXNzPVwiYnRuLWlubmVyIG1vZGVzd1wiIHVuc2VsZWN0YWJsZT1cIm9uXCI+W2JiY29kZV08L3NwYW4+PC9kaXY+JykuYXBwZW5kVG8odGhpcy4kdG9vbGJhcik7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmJibW9kZSA9PSB0cnVlKSB7XG4gICAgICAgICRiYnN3LmNoaWxkcmVuKFwiLnd5c2liYi10b29sYmFyLWJ0blwiKS5hZGRDbGFzcyhcIm9uXCIpO1xuICAgICAgfVxuICAgICAgaWYgKHRoaXMub3B0aW9ucy5vbmx5QkJtb2RlID09PSBmYWxzZSkge1xuICAgICAgICAkYmJzdy5jaGlsZHJlbihcIi53eXNpYmItdG9vbGJhci1idG5cIikuY2xpY2soJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICAgICQoZS5jdXJyZW50VGFyZ2V0KS50b2dnbGVDbGFzcyhcIm9uXCIpO1xuICAgICAgICAgIHRoaXMubW9kZVN3aXRjaCgpO1xuICAgICAgICB9LCB0aGlzKSk7XG4gICAgICB9XG4gICAgfSxcbiAgICBidWlsZEJ1dHRvbjogZnVuY3Rpb24gKGNvbnRhaW5lciwgYm4sIG9wdCkge1xuICAgICAgaWYgKHR5cGVvZiAoY29udGFpbmVyKSAhPSBcIm9iamVjdFwiKSB7XG4gICAgICAgIGNvbnRhaW5lciA9IHRoaXMuJHRvb2xiYXI7XG4gICAgICB9XG4gICAgICB2YXIgYnRuSFRNTCA9IChvcHQuYnV0dG9uSFRNTCkgPyAkKHRoaXMuc3RyZihvcHQuYnV0dG9uSFRNTCwgdGhpcy5vcHRpb25zKSkuYWRkQ2xhc3MoXCJidG4taW5uZXJcIikgOiB0aGlzLnN0cmYoJzxzcGFuIGNsYXNzPVwiYnRuLWlubmVyIGJ0bi10ZXh0XCI+e3RleHR9PC9zcGFuPicsIHt0ZXh0OiBvcHQuYnV0dG9uVGV4dC5yZXBsYWNlKC88L2csIFwiJmx0O1wiKX0pO1xuICAgICAgdmFyIGhvdGtleSA9ICh0aGlzLm9wdGlvbnMuaG90a2V5cyA9PT0gdHJ1ZSAmJiB0aGlzLm9wdGlvbnMuc2hvd0hvdGtleXMgPT09IHRydWUgJiYgb3B0LmhvdGtleSkgPyAoJyA8c3BhbiBjbGFzcz1cInR0aG90a2V5XCI+WycgKyBvcHQuaG90a2V5ICsgJ108L3NwYW4+JykgOiBcIlwiXG4gICAgICB2YXIgJGJ0biA9ICQoJzxkaXYgY2xhc3M9XCJ3eXNpYmItdG9vbGJhci1idG4gd2JiLScgKyBibiArICdcIj4nKS5hcHBlbmRUbyhjb250YWluZXIpLmFwcGVuZChidG5IVE1MKS5hcHBlbmQodGhpcy5zdHJmKCc8c3BhbiBjbGFzcz1cImJ0bi10b29sdGlwXCI+e3RpdGxlfTxpbnMvPntob3RrZXl9PC9zcGFuPicsIHt0aXRsZTogb3B0LnRpdGxlLCBob3RrZXk6IGhvdGtleX0pKTtcblxuICAgICAgLy9hdHRhY2ggZXZlbnRzXG4gICAgICB0aGlzLmNvbnRyb2xsZXJzLnB1c2goJGJ0bik7XG4gICAgICAkYnRuLmJpbmQoJ3F1ZXJ5U3RhdGUnLCAkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICh0aGlzLnF1ZXJ5U3RhdGUoYm4pKSA/ICQoZS5jdXJyZW50VGFyZ2V0KS5hZGRDbGFzcyhcIm9uXCIpIDogJChlLmN1cnJlbnRUYXJnZXQpLnJlbW92ZUNsYXNzKFwib25cIik7XG4gICAgICB9LCB0aGlzKSk7XG4gICAgICAkYnRuLm1vdXNlZG93bigkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgdGhpcy5leGVjQ29tbWFuZChibiwgb3B0LmV4dmFsdWUgfHwgZmFsc2UpO1xuICAgICAgICAkKGUuY3VycmVudFRhcmdldCkudHJpZ2dlcigncXVlcnlTdGF0ZScpO1xuICAgICAgfSwgdGhpcykpO1xuICAgIH0sXG4gICAgYnVpbGRDb2xvcnBpY2tlcjogZnVuY3Rpb24gKGNvbnRhaW5lciwgYm4sIG9wdCkge1xuICAgICAgdmFyICRidG4gPSAkKCc8ZGl2IGNsYXNzPVwid3lzaWJiLXRvb2xiYXItYnRuIHdiYi1kcm9wZG93biB3YmItY3BcIj4nKS5hcHBlbmRUbyhjb250YWluZXIpLmFwcGVuZCgnPGRpdiBjbGFzcz1cInZlLXRsYi1jb2xvcnBpY2tcIj48c3BhbiBjbGFzcz1cImZvbnRpY29uXCI+XFx1RTAxMDwvc3Bhbj48c3BhbiBjbGFzcz1cImNwLWxpbmVcIj48L3NwYW4+PC9kaXY+PGlucyBjbGFzcz1cImZvbnRpY29uIGFyXCI+XFx1RTAxMTwvaW5zPicpLmFwcGVuZCh0aGlzLnN0cmYoJzxzcGFuIGNsYXNzPVwiYnRuLXRvb2x0aXBcIj57dGl0bGV9PGlucy8+PC9zcGFuPicsIHt0aXRsZTogb3B0LnRpdGxlfSkpO1xuICAgICAgdmFyICRjcGxpbmUgPSAkYnRuLmZpbmQoXCIuY3AtbGluZVwiKTtcblxuICAgICAgdmFyICRkcm9wYmxvY2sgPSAkKCc8ZGl2IGNsYXNzPVwid2JiLWxpc3RcIj4nKS5hcHBlbmRUbygkYnRuKTtcbiAgICAgICRkcm9wYmxvY2suYXBwZW5kKCc8ZGl2IGNsYXNzPVwibmNcIj4nICsgQ1VSTEFORy5hdXRvICsgJzwvZGl2PicpO1xuICAgICAgdmFyIGNvbG9ybGlzdCA9IChvcHQuY29sb3JzKSA/IG9wdC5jb2xvcnMuc3BsaXQoXCIsXCIpIDogW107XG4gICAgICBmb3IgKHZhciBqID0gMDsgaiA8IGNvbG9ybGlzdC5sZW5ndGg7IGorKykge1xuICAgICAgICBjb2xvcmxpc3Rbal0gPSAkLnRyaW0oY29sb3JsaXN0W2pdKTtcbiAgICAgICAgaWYgKGNvbG9ybGlzdFtqXSA9PSBcIi1cIikge1xuICAgICAgICAgIC8vaW5zZXJ0IHBhZGRpbmdcbiAgICAgICAgICAkZHJvcGJsb2NrLmFwcGVuZCgnPHNwYW4gY2xhc3M9XCJwbFwiPjwvc3Bhbj4nKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAkZHJvcGJsb2NrLmFwcGVuZCh0aGlzLnN0cmYoJzxkaXYgY2xhc3M9XCJzY1wiIHN0eWxlPVwiYmFja2dyb3VuZDp7Y29sb3J9XCIgdGl0bGU9XCJ7Y29sb3J9XCI+PC9kaXY+Jywge2NvbG9yOiBjb2xvcmxpc3Rbal19KSk7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICAgIHZhciBiYXNlY29sb3IgPSAkKGRvY3VtZW50LmJvZHkpLmNzcyhcImNvbG9yXCIpO1xuICAgICAgLy9hdHRhY2ggZXZlbnRzXG4gICAgICB0aGlzLmNvbnRyb2xsZXJzLnB1c2goJGJ0bik7XG4gICAgICAkYnRuLmJpbmQoJ3F1ZXJ5U3RhdGUnLCAkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgIC8vcXVlcnlTdGF0ZVxuICAgICAgICAkY3BsaW5lLmNzcyhcImJhY2tncm91bmQtY29sb3JcIiwgYmFzZWNvbG9yKTtcbiAgICAgICAgdmFyIHIgPSB0aGlzLnF1ZXJ5U3RhdGUoYm4sIHRydWUpO1xuICAgICAgICBpZiAocikge1xuICAgICAgICAgICRjcGxpbmUuY3NzKFwiYmFja2dyb3VuZC1jb2xvclwiLCAodGhpcy5vcHRpb25zLmJibW9kZSkgPyByLmNvbG9yIDogcik7XG4gICAgICAgICAgJGJ0bi5maW5kKFwiLnZlLXRsYi1jb2xvcnBpY2sgc3Bhbi5mb250aWNvblwiKS5jc3MoXCJjb2xvclwiLCAodGhpcy5vcHRpb25zLmJibW9kZSkgPyByLmNvbG9yIDogcik7XG4gICAgICAgIH1cbiAgICAgIH0sIHRoaXMpKTtcbiAgICAgICRidG4ubW91c2Vkb3duKCQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICB0aGlzLmRyb3Bkb3duY2xpY2soXCIud2JiLWNwXCIsIFwiLndiYi1saXN0XCIsIGUpO1xuICAgICAgfSwgdGhpcykpO1xuICAgICAgJGJ0bi5maW5kKFwiLnNjXCIpLm1vdXNlZG93bigkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgdGhpcy5zZWxlY3RMYXN0UmFuZ2UoKTtcbiAgICAgICAgdmFyIGMgPSAkKGUuY3VycmVudFRhcmdldCkuYXR0cihcInRpdGxlXCIpO1xuICAgICAgICB0aGlzLmV4ZWNDb21tYW5kKGJuLCBjKTtcbiAgICAgICAgJGJ0bi50cmlnZ2VyKCdxdWVyeVN0YXRlJyk7XG4gICAgICB9LCB0aGlzKSk7XG4gICAgICAkYnRuLmZpbmQoXCIubmNcIikubW91c2Vkb3duKCQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICB0aGlzLnNlbGVjdExhc3RSYW5nZSgpO1xuICAgICAgICB0aGlzLmV4ZWNDb21tYW5kKGJuLCBiYXNlY29sb3IpO1xuICAgICAgICAkYnRuLnRyaWdnZXIoJ3F1ZXJ5U3RhdGUnKTtcbiAgICAgIH0sIHRoaXMpKTtcbiAgICAgICRidG4ubW91c2Vkb3duKGZ1bmN0aW9uIChlKSB7XG4gICAgICAgIGlmIChlLnByZXZlbnREZWZhdWx0KSBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICB9KTtcbiAgICB9LFxuICAgIGJ1aWxkVGFibGVwaWNrZXI6IGZ1bmN0aW9uIChjb250YWluZXIsIGJuLCBvcHQpIHtcbiAgICAgIHZhciAkYnRuID0gJCgnPGRpdiBjbGFzcz1cInd5c2liYi10b29sYmFyLWJ0biB3YmItZHJvcGRvd24gd2JiLXRibFwiPicpLmFwcGVuZFRvKGNvbnRhaW5lcikuYXBwZW5kKCc8c3BhbiBjbGFzcz1cImJ0bi1pbm5lciBmb250aWNvbiB2ZS10bGItdGFibGUxXCI+XFx1RTAwZTwvc3Bhbj48aW5zIGNsYXNzPVwiZm9udGljb24gYXJcIj5cXHVFMDExPC9pbnM+JykuYXBwZW5kKHRoaXMuc3RyZignPHNwYW4gY2xhc3M9XCJidG4tdG9vbHRpcFwiPnt0aXRsZX08aW5zLz48L3NwYW4+Jywge3RpdGxlOiBvcHQudGl0bGV9KSk7XG5cbiAgICAgIHZhciAkbGlzdGJsb2NrID0gJCgnPGRpdiBjbGFzcz1cIndiYi1saXN0XCI+JykuYXBwZW5kVG8oJGJ0bik7XG4gICAgICB2YXIgJGRyb3BibG9jayA9ICQoJzxkaXY+JykuY3NzKHtcInBvc2l0aW9uXCI6IFwicmVsYXRpdmVcIiwgXCJib3gtc2l6aW5nXCI6IFwiYm9yZGVyLWJveFwifSkuYXBwZW5kVG8oJGxpc3RibG9jayk7XG4gICAgICB2YXIgcm93cyA9IG9wdC5yb3dzIHx8IDEwO1xuICAgICAgdmFyIGNvbHMgPSBvcHQuY29scyB8fCAxMDtcbiAgICAgIHZhciBhbGxjb3VudCA9IHJvd3MgKiBjb2xzO1xuICAgICAgJGRyb3BibG9jay5jc3MoXCJoZWlnaHRcIiwgKHJvd3MgKiBvcHQuY2VsbHdpZHRoICsgMikgKyBcInB4XCIpO1xuICAgICAgZm9yICh2YXIgaiA9IDE7IGogPD0gY29sczsgaisrKSB7XG4gICAgICAgIGZvciAodmFyIGggPSAxOyBoIDw9IHJvd3M7IGgrKykge1xuICAgICAgICAgIC8vdmFyIGh0bWwgPSB0aGlzLnN0cmYoJzxkaXYgY2xhc3M9XCJ0Ymwtc2VsXCIgc3R5bGU9XCJ3aWR0aDp7d2lkdGh9cHg7aGVpZ2h0OntoZWlnaHR9cHg7ei1pbmRleDp7emluZGV4fVwiIHRpdGxlPVwie3Jvd30se2NvbH1cIj48L2Rpdj4nLHt3aWR0aDogKGoqb3B0LmNlbGx3aWR0aCksaGVpZ2h0OiAoaCpvcHQuY2VsbHdpZHRoKSx6aW5kZXg6IC0tYWxsY291bnQscm93OmgsY29sOmp9KTtcbiAgICAgICAgICB2YXIgaHRtbCA9ICc8ZGl2IGNsYXNzPVwidGJsLXNlbFwiIHN0eWxlPVwid2lkdGg6JyArIChqICogMTAwIC8gY29scykgKyAnJTtoZWlnaHQ6JyArIChoICogMTAwIC8gcm93cykgKyAnJTt6LWluZGV4OicgKyAoLS1hbGxjb3VudCkgKyAnXCIgdGl0bGU9XCInICsgaCArICcsJyArIGogKyAnXCI+PC9kaXY+JztcbiAgICAgICAgICAkZHJvcGJsb2NrLmFwcGVuZChodG1sKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgICAgLy90aGlzLmRlYnVnKFwiQXR0YWNoIGV2ZW50IG9uOiB0Ymwtc2VsXCIpO1xuICAgICAgJGJ0bi5maW5kKFwiLnRibC1zZWxcIikubW91c2Vkb3duKCQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICB2YXIgdCA9ICQoZS5jdXJyZW50VGFyZ2V0KS5hdHRyKFwidGl0bGVcIik7XG4gICAgICAgIHZhciByYyA9IHQuc3BsaXQoXCIsXCIpO1xuICAgICAgICB2YXIgY29kZSA9ICh0aGlzLm9wdGlvbnMuYmJtb2RlKSA/ICdbdGFibGVdJyA6ICc8dGFibGUgY2xhc3M9XCJ3YmItdGFibGVcIiBjZWxsc3BhY2luZz1cIjVcIiBjZWxscGFkZGluZz1cIjBcIj4nO1xuICAgICAgICBmb3IgKHZhciBpID0gMTsgaSA8PSByY1swXTsgaSsrKSB7XG4gICAgICAgICAgY29kZSArPSAodGhpcy5vcHRpb25zLmJibW9kZSkgPyAnIFt0cl1cXG4nIDogJzx0cj4nO1xuICAgICAgICAgIGZvciAodmFyIGogPSAxOyBqIDw9IHJjWzFdOyBqKyspIHtcbiAgICAgICAgICAgIGNvZGUgKz0gKHRoaXMub3B0aW9ucy5iYm1vZGUpID8gJyAgW3RkXVsvdGRdXFxuJyA6ICc8dGQ+XFx1RkVGRjwvdGQ+JztcbiAgICAgICAgICB9XG4gICAgICAgICAgY29kZSArPSAodGhpcy5vcHRpb25zLmJibW9kZSkgPyAnWy90cl1cXG4nIDogJzwvdHI+JztcbiAgICAgICAgfVxuICAgICAgICBjb2RlICs9ICh0aGlzLm9wdGlvbnMuYmJtb2RlKSA/ICdbL3RhYmxlXScgOiAnPC90YWJsZT4nO1xuICAgICAgICB0aGlzLmluc2VydEF0Q3Vyc29yKGNvZGUpO1xuICAgICAgfSwgdGhpcykpO1xuICAgICAgLy90aGlzLmRlYnVnKFwiRU5EIEF0dGFjaCBldmVudCBvbjogdGJsLXNlbFwiKTtcbiAgICAgICRidG4ubW91c2Vkb3duKCQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICB0aGlzLmRyb3Bkb3duY2xpY2soXCIud2JiLXRibFwiLCBcIi53YmItbGlzdFwiLCBlKTtcbiAgICAgIH0sIHRoaXMpKTtcblxuICAgIH0sXG4gICAgYnVpbGRTZWxlY3Q6IGZ1bmN0aW9uIChjb250YWluZXIsIGJuLCBvcHQpIHtcbiAgICAgIHZhciAkYnRuID0gJCgnPGRpdiBjbGFzcz1cInd5c2liYi10b29sYmFyLWJ0biB3YmItc2VsZWN0IHdiYi0nICsgYm4gKyAnXCI+JykuYXBwZW5kVG8oY29udGFpbmVyKS5hcHBlbmQodGhpcy5zdHJmKCc8c3BhbiBjbGFzcz1cInZhbFwiPnt0aXRsZX08L3NwYW4+PGlucyBjbGFzcz1cImZvbnRpY29uIHNhclwiPlxcdUUwMTI8L2lucz4nLCBvcHQpKS5hcHBlbmQodGhpcy5zdHJmKCc8c3BhbiBjbGFzcz1cImJ0bi10b29sdGlwXCI+e3RpdGxlfTxpbnMvPjwvc3Bhbj4nLCB7dGl0bGU6IG9wdC50aXRsZX0pKTtcbiAgICAgIHZhciAkc2Jsb2NrID0gJCgnPGRpdiBjbGFzcz1cIndiYi1saXN0XCI+JykuYXBwZW5kVG8oJGJ0bik7XG4gICAgICB2YXIgJHN2YWwgPSAkYnRuLmZpbmQoXCJzcGFuLnZhbFwiKTtcblxuICAgICAgdmFyIG9saXN0ID0gKCQuaXNBcnJheShvcHQub3B0aW9ucykpID8gb3B0Lm9wdGlvbnMgOiBvcHQub3B0aW9ucy5zcGxpdChcIixcIik7XG4gICAgICB2YXIgJHNlbGVjdGJveCA9ICh0aGlzLmlzTW9iaWxlKSA/ICQoXCI8c2VsZWN0PlwiKS5hZGRDbGFzcyhcIndiYi1zZWxlY3Rib3hcIikgOiBcIlwiO1xuICAgICAgZm9yICh2YXIgaSA9IDA7IGkgPCBvbGlzdC5sZW5ndGg7IGkrKykge1xuICAgICAgICB2YXIgb25hbWUgPSBvbGlzdFtpXTtcbiAgICAgICAgaWYgKHR5cGVvZiAob25hbWUpID09IFwic3RyaW5nXCIpIHtcbiAgICAgICAgICB2YXIgb3B0aW9uID0gdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbb25hbWVdO1xuICAgICAgICAgIGlmIChvcHRpb24pIHtcbiAgICAgICAgICAgIC8vJC5sb2coXCJjcmVhdGU6IFwiK29uYW1lKTtcbiAgICAgICAgICAgIGlmIChvcHRpb24uaHRtbCkge1xuICAgICAgICAgICAgICAkKCc8c3Bhbj4nKS5hZGRDbGFzcyhcIm9wdGlvblwiKS5hdHRyKFwib2lkXCIsIG9uYW1lKS5hdHRyKFwiY21kdmFsdWVcIiwgb3B0aW9uLmV4dmFsdWUpLmFwcGVuZFRvKCRzYmxvY2spLmFwcGVuZCh0aGlzLnN0cmYob3B0aW9uLmh0bWwsIHtzZWx0ZXh0OiBvcHRpb24udGl0bGV9KSk7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAkc2Jsb2NrLmFwcGVuZCh0aGlzLnN0cmYoJzxzcGFuIGNsYXNzPVwib3B0aW9uXCIgb2lkPVwiJyArIG9uYW1lICsgJ1wiIGNtZHZhbHVlPVwiJyArIG9wdGlvbi5leHZhbHVlICsgJ1wiPnt0aXRsZX08L3NwYW4+Jywgb3B0aW9uKSk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIC8vU2VsZWN0Qm94IGZvciBtb2JpbGUgZGV2aWNlc1xuICAgICAgICAgICAgaWYgKHRoaXMuaXNNb2JpbGUpIHtcbiAgICAgICAgICAgICAgJHNlbGVjdGJveC5hcHBlbmQoJCgnPG9wdGlvbj4nKS5hdHRyKFwib2lkXCIsIG9uYW1lKS5hdHRyKFwiY21kdmFsdWVcIiwgb3B0aW9uLmV4dmFsdWUpLmFwcGVuZChvcHRpb24udGl0bGUpKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgLy9idWlsZCBvcHRpb24gbGlzdCBmcm9tIGFycmF5XG4gICAgICAgICAgdmFyIHBhcmFtcyA9IHtcbiAgICAgICAgICAgIHNlbHRleHQ6IG9uYW1lLnRpdGxlXG4gICAgICAgICAgfVxuICAgICAgICAgIHBhcmFtc1tvcHQudmFsdWVCQm5hbWVdID0gb25hbWUuZXh2YWx1ZTtcbiAgICAgICAgICAkKCc8c3Bhbj4nKS5hZGRDbGFzcyhcIm9wdGlvblwiKS5hdHRyKFwib2lkXCIsIGJuKS5hdHRyKFwiY21kdmFsdWVcIiwgb25hbWUuZXh2YWx1ZSkuYXBwZW5kVG8oJHNibG9jaykuYXBwZW5kKHRoaXMuc3RyZihvcHQuaHRtbCwgcGFyYW1zKSk7XG5cbiAgICAgICAgICBpZiAodGhpcy5pc01vYmlsZSkge1xuICAgICAgICAgICAgJHNlbGVjdGJveC5hcHBlbmQoJCgnPG9wdGlvbj4nKS5hdHRyKFwib2lkXCIsIGJuKS5hdHRyKFwiY21kdmFsdWVcIiwgb25hbWUuZXh2YWx1ZSkuYXBwZW5kKG9uYW1lLmV4dmFsdWUpKVxuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfVxuICAgICAgLy8kc2Jsb2NrLmFwcGVuZCgkc2VsZWN0Ym94KTtcbiAgICAgIGlmICh0aGlzLmlzTW9iaWxlKSB7XG4gICAgICAgICRzZWxlY3Rib3guYXBwZW5kVG8oY29udGFpbmVyKTtcbiAgICAgICAgdGhpcy5jb250cm9sbGVycy5wdXNoKCRzZWxlY3Rib3gpO1xuXG4gICAgICAgICRzZWxlY3Rib3guYmluZCgncXVlcnlTdGF0ZScsICQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICAvL3F1ZXJ5U3RhdGVcbiAgICAgICAgICAkc2VsZWN0Ym94LmZpbmQoXCJvcHRpb25cIikuZWFjaCgkLnByb3h5KGZ1bmN0aW9uIChpLCBlbCkge1xuICAgICAgICAgICAgdmFyICRlbCA9ICQoZWwpO1xuICAgICAgICAgICAgdmFyIHIgPSB0aGlzLnF1ZXJ5U3RhdGUoJGVsLmF0dHIoXCJvaWRcIiksIHRydWUpO1xuICAgICAgICAgICAgdmFyIGNtZHZhbHVlID0gJGVsLmF0dHIoXCJjbWR2YWx1ZVwiKTtcbiAgICAgICAgICAgIGlmICgoY21kdmFsdWUgJiYgciA9PSAkZWwuYXR0cihcImNtZHZhbHVlXCIpKSB8fCAoIWNtZHZhbHVlICYmIHIpKSB7XG4gICAgICAgICAgICAgICRlbC5wcm9wKFwic2VsZWN0ZWRcIiwgdHJ1ZSk7XG4gICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9LCB0aGlzKSk7XG4gICAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgICAkc2VsZWN0Ym94LmNoYW5nZSgkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgIHZhciAkbyA9ICQoZS5jdXJyZW50VGFyZ2V0KS5maW5kKFwiOnNlbGVjdGVkXCIpO1xuICAgICAgICAgIHZhciBvaWQgPSAkby5hdHRyKFwib2lkXCIpO1xuICAgICAgICAgIHZhciBjbWR2YWx1ZSA9ICRvLmF0dHIoXCJjbWR2YWx1ZVwiKTtcbiAgICAgICAgICB2YXIgb3B0ID0gdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbb2lkXTtcbiAgICAgICAgICB0aGlzLmV4ZWNDb21tYW5kKG9pZCwgb3B0LmV4dmFsdWUgfHwgY21kdmFsdWUgfHwgZmFsc2UpO1xuICAgICAgICAgICQoZS5jdXJyZW50VGFyZ2V0KS50cmlnZ2VyKCdxdWVyeVN0YXRlJyk7XG4gICAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgfVxuICAgICAgdGhpcy5jb250cm9sbGVycy5wdXNoKCRidG4pO1xuICAgICAgJGJ0bi5iaW5kKCdxdWVyeVN0YXRlJywgJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICAvL3F1ZXJ5U3RhdGVcbiAgICAgICAgJHN2YWwudGV4dChvcHQudGl0bGUpO1xuICAgICAgICAkYnRuLmZpbmQoXCIub3B0aW9uLnNlbGVjdGVkXCIpLnJlbW92ZUNsYXNzKFwic2VsZWN0ZWRcIik7XG4gICAgICAgICRidG4uZmluZChcIi5vcHRpb25cIikuZWFjaCgkLnByb3h5KGZ1bmN0aW9uIChpLCBlbCkge1xuICAgICAgICAgIHZhciAkZWwgPSAkKGVsKTtcbiAgICAgICAgICB2YXIgciA9IHRoaXMucXVlcnlTdGF0ZSgkZWwuYXR0cihcIm9pZFwiKSwgdHJ1ZSk7XG4gICAgICAgICAgdmFyIGNtZHZhbHVlID0gJGVsLmF0dHIoXCJjbWR2YWx1ZVwiKTtcbiAgICAgICAgICBpZiAoKGNtZHZhbHVlICYmIHIgPT0gJGVsLmF0dHIoXCJjbWR2YWx1ZVwiKSkgfHwgKCFjbWR2YWx1ZSAmJiByKSkge1xuICAgICAgICAgICAgJHN2YWwudGV4dCgkZWwudGV4dCgpKTtcbiAgICAgICAgICAgICRlbC5hZGRDbGFzcyhcInNlbGVjdGVkXCIpO1xuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgIH1cbiAgICAgICAgfSwgdGhpcykpO1xuICAgICAgfSwgdGhpcykpO1xuICAgICAgJGJ0bi5tb3VzZWRvd24oJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgIHRoaXMuZHJvcGRvd25jbGljayhcIi53YmItc2VsZWN0XCIsIFwiLndiYi1saXN0XCIsIGUpO1xuICAgICAgfSwgdGhpcykpO1xuICAgICAgJGJ0bi5maW5kKFwiLm9wdGlvblwiKS5tb3VzZWRvd24oJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgIHZhciBvaWQgPSAkKGUuY3VycmVudFRhcmdldCkuYXR0cihcIm9pZFwiKTtcbiAgICAgICAgdmFyIGNtZHZhbHVlID0gJChlLmN1cnJlbnRUYXJnZXQpLmF0dHIoXCJjbWR2YWx1ZVwiKTtcbiAgICAgICAgdmFyIG9wdCA9IHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW29pZF07XG4gICAgICAgIHRoaXMuZXhlY0NvbW1hbmQob2lkLCBvcHQuZXh2YWx1ZSB8fCBjbWR2YWx1ZSB8fCBmYWxzZSk7XG4gICAgICAgICQoZS5jdXJyZW50VGFyZ2V0KS50cmlnZ2VyKCdxdWVyeVN0YXRlJyk7XG4gICAgICB9LCB0aGlzKSk7XG4gICAgfSxcbiAgICBidWlsZFNtaWxlYm94OiBmdW5jdGlvbiAoY29udGFpbmVyLCBibiwgb3B0KSB7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLnNtaWxlTGlzdCAmJiB0aGlzLm9wdGlvbnMuc21pbGVMaXN0Lmxlbmd0aCA+IDApIHtcbiAgICAgICAgdmFyICRidG5IVE1MID0gJCh0aGlzLnN0cmYob3B0LmJ1dHRvbkhUTUwsIG9wdCkpLmFkZENsYXNzKFwiYnRuLWlubmVyXCIpO1xuICAgICAgICB2YXIgJGJ0biA9ICQoJzxkaXYgY2xhc3M9XCJ3eXNpYmItdG9vbGJhci1idG4gd2JiLXNtaWxlYm94IHdiYi0nICsgYm4gKyAnXCI+JykuYXBwZW5kVG8oY29udGFpbmVyKS5hcHBlbmQoJGJ0bkhUTUwpLmFwcGVuZCh0aGlzLnN0cmYoJzxzcGFuIGNsYXNzPVwiYnRuLXRvb2x0aXBcIj57dGl0bGV9PGlucy8+PC9zcGFuPicsIHt0aXRsZTogb3B0LnRpdGxlfSkpO1xuICAgICAgICB2YXIgJHNibG9jayA9ICQoJzxkaXYgY2xhc3M9XCJ3YmItbGlzdFwiPicpLmFwcGVuZFRvKCRidG4pO1xuICAgICAgICBpZiAoJC5pc0FycmF5KHRoaXMub3B0aW9ucy5zbWlsZUxpc3QpKSB7XG4gICAgICAgICAgJC5lYWNoKHRoaXMub3B0aW9ucy5zbWlsZUxpc3QsICQucHJveHkoZnVuY3Rpb24gKGksIHNtKSB7XG4gICAgICAgICAgICAkKCc8c3Bhbj4nKS5hZGRDbGFzcyhcInNtaWxlXCIpLmFwcGVuZFRvKCRzYmxvY2spLmFwcGVuZCgkKHRoaXMuc3RyZihzbS5pbWcsIHRoaXMub3B0aW9ucykpLmF0dHIoXCJ0aXRsZVwiLCBzbS50aXRsZSkpO1xuICAgICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgfVxuICAgICAgICAkYnRuLm1vdXNlZG93bigkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgIHRoaXMuZHJvcGRvd25jbGljayhcIi53YmItc21pbGVib3hcIiwgXCIud2JiLWxpc3RcIiwgZSk7XG4gICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgJGJ0bi5maW5kKCcuc21pbGUnKS5tb3VzZWRvd24oJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAvL3RoaXMuc2VsZWN0TGFzdFJhbmdlKCk7XG4gICAgICAgICAgdGhpcy5pbnNlcnRBdEN1cnNvcigodGhpcy5vcHRpb25zLmJibW9kZSkgPyB0aGlzLnRvQkIoJChlLmN1cnJlbnRUYXJnZXQpLmh0bWwoKSkgOiAkKCQoZS5jdXJyZW50VGFyZ2V0KS5odG1sKCkpKTtcbiAgICAgICAgfSwgdGhpcykpXG4gICAgICB9XG4gICAgfSxcbiAgICB1cGRhdGVVSTogZnVuY3Rpb24gKGUpIHtcbiAgICAgIGlmICghZSB8fCAoKGUud2hpY2ggPj0gOCAmJiBlLndoaWNoIDw9IDQ2KSB8fCBlLndoaWNoID4gOTAgfHwgZS50eXBlID09IFwibW91c2V1cFwiKSkge1xuICAgICAgICAkLmVhY2godGhpcy5jb250cm9sbGVycywgJC5wcm94eShmdW5jdGlvbiAoaSwgJGJ0bikge1xuICAgICAgICAgICRidG4udHJpZ2dlcigncXVlcnlTdGF0ZScpO1xuICAgICAgICB9LCB0aGlzKSk7XG4gICAgICB9XG5cbiAgICAgIC8vY2hlY2sgZm9yIG9ubHlDbGVhclRleHRcbiAgICAgIHRoaXMuZGlzTm9uQWN0aXZlQnV0dG9ucygpO1xuXG4gICAgfSxcbiAgICBpbml0TW9kYWw6IGZ1bmN0aW9uICgpIHtcbiAgICAgIHRoaXMuJG1vZGFsID0gJChcIiN3YmJtb2RhbFwiKTtcbiAgICAgIGlmICh0aGlzLiRtb2RhbC5sZW5ndGggPT0gMCkge1xuICAgICAgICAkLmxvZyhcIkluaXQgbW9kYWxcIik7XG4gICAgICAgIHRoaXMuJG1vZGFsID0gJCgnPGRpdj4nKS5hdHRyKFwiaWRcIiwgXCJ3YmJtb2RhbFwiKS5wcmVwZW5kVG8oZG9jdW1lbnQuYm9keSlcbiAgICAgICAgICAuaHRtbCgnPGRpdiBjbGFzcz1cIndiYm1cIj48ZGl2IGNsYXNzPVwid2JibS10aXRsZVwiPjxzcGFuIGNsYXNzPVwid2JibS10aXRsZS10ZXh0XCI+PC9zcGFuPjxzcGFuIGNsYXNzPVwid2JiY2xvc2VcIiB0aXRsZT1cIicgKyBDVVJMQU5HLmNsb3NlICsgJ1wiPsOXPC9zcGFuPjwvZGl2PjxkaXYgY2xhc3M9XCJ3YmJtLWNvbnRlbnRcIj48L2Rpdj48ZGl2IGNsYXNzPVwid2JibS1ib3R0b21cIj48YnV0dG9uIGlkPVwid2JibS1zdWJtaXRcIiBjbGFzcz1cIndiYi1idXR0b25cIj4nICsgQ1VSTEFORy5zYXZlICsgJzwvYnV0dG9uPjxidXR0b24gaWQ9XCJ3YmJtLWNhbmNlbFwiIGNsYXNzPVwid2JiLWNhbmNlbC1idXR0b25cIj4nICsgQ1VSTEFORy5jYW5jZWwgKyAnPC9idXR0b24+PGJ1dHRvbiBpZD1cIndiYm0tcmVtb3ZlXCIgY2xhc3M9XCJ3YmItcmVtb3ZlLWJ1dHRvblwiPicgKyBDVVJMQU5HLnJlbW92ZSArICc8L2J1dHRvbj48L2Rpdj48L2Rpdj4nKS5oaWRlKCk7XG5cbiAgICAgICAgdGhpcy4kbW9kYWwuZmluZCgnI3diYm0tY2FuY2VsLC53YmJjbG9zZScpLmNsaWNrKCQucHJveHkodGhpcy5jbG9zZU1vZGFsLCB0aGlzKSk7XG4gICAgICAgIHRoaXMuJG1vZGFsLmJpbmQoJ2NsaWNrJywgJC5wcm94eShmdW5jdGlvbiAoZSkge1xuICAgICAgICAgIGlmICgkKGUudGFyZ2V0KS5wYXJlbnRzKFwiLndiYm1cIikubGVuZ3RoID09IDApIHtcbiAgICAgICAgICAgIHRoaXMuY2xvc2VNb2RhbCgpO1xuICAgICAgICAgIH1cbiAgICAgICAgfSwgdGhpcykpO1xuXG4gICAgICAgICQoZG9jdW1lbnQpLmJpbmQoXCJrZXlkb3duXCIsICQucHJveHkodGhpcy5lc2NNb2RhbCwgdGhpcykpOyAvL0VTQyBrZXkgY2xvc2UgbW9kYWxcbiAgICAgIH1cbiAgICB9LFxuICAgIGluaXRIb3RrZXlzOiBmdW5jdGlvbiAoKSB7XG4gICAgICAkLmxvZyhcImluaXRIb3RrZXlzXCIpO1xuICAgICAgdGhpcy5ob3RrZXlzID0gW107XG4gICAgICB2YXIga2xpc3QgPSBcIjAxMjM0NTY3ODkgICAgICAgYWJjZGVmZ2hpamtsbW5vcHFyc3R1dnd4eXpcIjtcbiAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMuYWxsQnV0dG9ucywgJC5wcm94eShmdW5jdGlvbiAoY21kLCBvcHQpIHtcbiAgICAgICAgaWYgKG9wdC5ob3RrZXkpIHtcbiAgICAgICAgICB2YXIga2V5cyA9IG9wdC5ob3RrZXkuc3BsaXQoXCIrXCIpO1xuICAgICAgICAgIGlmIChrZXlzICYmIGtleXMubGVuZ3RoID49IDIpIHtcbiAgICAgICAgICAgIHZhciBtZXRhc3VtID0gMDtcbiAgICAgICAgICAgIHZhciBrZXkgPSBrZXlzLnBvcCgpO1xuICAgICAgICAgICAgJC5lYWNoKGtleXMsIGZ1bmN0aW9uIChpLCBrKSB7XG4gICAgICAgICAgICAgIHN3aXRjaCAoJC50cmltKGsudG9Mb3dlckNhc2UoKSkpIHtcbiAgICAgICAgICAgICAgICBjYXNlIFwiY3RybFwiOiB7XG4gICAgICAgICAgICAgICAgICBtZXRhc3VtICs9IDE7XG4gICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgY2FzZSBcInNoaWZ0XCI6IHtcbiAgICAgICAgICAgICAgICAgIG1ldGFzdW0gKz0gNDtcbiAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBjYXNlIFwiYWx0XCI6IHtcbiAgICAgICAgICAgICAgICAgIG1ldGFzdW0gKz0gNztcbiAgICAgICAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSlcbiAgICAgICAgICAgIC8vJC5sb2coXCJtZXRhc3VtOiBcIittZXRhc3VtK1wiIGtleTogXCIra2V5K1wiIGNvZGU6IFwiKyhrbGlzdC5pbmRleE9mKGtleSkrNDgpKTtcbiAgICAgICAgICAgIGlmIChtZXRhc3VtID4gMCkge1xuICAgICAgICAgICAgICBpZiAoIXRoaXMuaG90a2V5c1tcIm1cIiArIG1ldGFzdW1dKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5ob3RrZXlzW1wibVwiICsgbWV0YXN1bV0gPSBbXTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICB0aGlzLmhvdGtleXNbXCJtXCIgKyBtZXRhc3VtXVtcImtcIiArIChrbGlzdC5pbmRleE9mKGtleSkgKyA0OCldID0gY21kO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfSwgdGhpcykpXG4gICAgfSxcbiAgICBwcmVzc2tleTogZnVuY3Rpb24gKGUpIHtcbiAgICAgIGlmIChlLmN0cmxLZXkgPT0gdHJ1ZSB8fCBlLnNoaWZ0S2V5ID09IHRydWUgfHwgZS5hbHRLZXkgPT0gdHJ1ZSkge1xuICAgICAgICB2YXIgbWV0YXN1bSA9ICgoZS5jdHJsS2V5ID09IHRydWUpID8gMSA6IDApICsgKChlLnNoaWZ0S2V5ID09IHRydWUpID8gNCA6IDApICsgKChlLmFsdEtleSA9PSB0cnVlKSA/IDcgOiAwKTtcbiAgICAgICAgaWYgKHRoaXMuaG90a2V5c1tcIm1cIiArIG1ldGFzdW1dICYmIHRoaXMuaG90a2V5c1tcIm1cIiArIG1ldGFzdW1dW1wia1wiICsgZS53aGljaF0pIHtcbiAgICAgICAgICB0aGlzLmV4ZWNDb21tYW5kKHRoaXMuaG90a2V5c1tcIm1cIiArIG1ldGFzdW1dW1wia1wiICsgZS53aGljaF0sIGZhbHNlKTtcbiAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcblxuICAgIC8vQ09nZGZNTUFORCBGVU5DVElPTlNcbiAgICBleGVjQ29tbWFuZDogZnVuY3Rpb24gKGNvbW1hbmQsIHZhbHVlKSB7XG4gICAgICAkLmxvZyhcImV4ZWNDb21tYW5kOiBcIiArIGNvbW1hbmQpO1xuICAgICAgdmFyIG9wdCA9IHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2NvbW1hbmRdO1xuICAgICAgaWYgKG9wdC5lbiAhPT0gdHJ1ZSkge1xuICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICB9XG4gICAgICB2YXIgcXVlcnlTdGF0ZSA9IHRoaXMucXVlcnlTdGF0ZShjb21tYW5kLCB2YWx1ZSk7XG5cbiAgICAgIC8vY2hlY2sgZm9yIG9ubHlDbGVhclRleHRcbiAgICAgIHZhciBza2lwY21kID0gdGhpcy5pc0luQ2xlYXJUZXh0QmxvY2soKTtcbiAgICAgIGlmIChza2lwY21kICYmIHNraXBjbWQgIT0gY29tbWFuZCkge1xuICAgICAgICByZXR1cm47XG4gICAgICB9XG5cblxuICAgICAgaWYgKG9wdC5leGNtZCkge1xuICAgICAgICAvL3VzZSBOYXRpdmVDb21tYW5kXG4gICAgICAgIGlmICh0aGlzLm9wdGlvbnMuYmJtb2RlKSB7XG4gICAgICAgICAgJC5sb2coXCJOYXRpdmUgY29tbWFuZCBpbiBiYm1vZGU6IFwiICsgY29tbWFuZCk7XG4gICAgICAgICAgaWYgKHF1ZXJ5U3RhdGUgJiYgb3B0LnN1Ykluc2VydCAhPSB0cnVlKSB7XG4gICAgICAgICAgICAvL3JlbW92ZSBiYmNvZGVcbiAgICAgICAgICAgIHRoaXMud2JiUmVtb3ZlQ2FsbGJhY2soY29tbWFuZCwgdmFsdWUpO1xuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAvL2luc2VydCBiYmNvZGVcbiAgICAgICAgICAgIHZhciB2ID0ge307XG4gICAgICAgICAgICBpZiAob3B0LnZhbHVlQkJuYW1lICYmIHZhbHVlKSB7XG4gICAgICAgICAgICAgIHZbb3B0LnZhbHVlQkJuYW1lXSA9IHZhbHVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgdGhpcy5pbnNlcnRBdEN1cnNvcih0aGlzLmdldEJCQ29kZUJ5Q29tbWFuZChjb21tYW5kLCB2KSk7XG4gICAgICAgICAgfVxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIHRoaXMuZXhlY05hdGl2ZUNvbW1hbmQob3B0LmV4Y21kLCB2YWx1ZSB8fCBmYWxzZSk7XG4gICAgICAgIH1cbiAgICAgIH0gZWxzZSBpZiAoIW9wdC5jbWQpIHtcbiAgICAgICAgLy93YmJDb21tYW5kXG4gICAgICAgIC8vdGhpcy53YmJFeGVjQ29tbWFuZChjb21tYW5kLHZhbHVlLHF1ZXJ5U3RhdGUsJC5wcm94eSh0aGlzLndiYkluc2VydENhbGxiYWNrLHRoaXMpLCQucHJveHkodGhpcy53YmJSZW1vdmVDYWxsYmFjayx0aGlzKSk7XG4gICAgICAgIHRoaXMud2JiRXhlY0NvbW1hbmQuY2FsbCh0aGlzLCBjb21tYW5kLCB2YWx1ZSwgcXVlcnlTdGF0ZSk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICAvL3VzZXIgY3VzdG9tIGNvbW1hbmRcbiAgICAgICAgb3B0LmNtZC5jYWxsKHRoaXMsIGNvbW1hbmQsIHZhbHVlLCBxdWVyeVN0YXRlKTtcbiAgICAgIH1cbiAgICAgIHRoaXMudXBkYXRlVUkoKTtcbiAgICB9LFxuICAgIHF1ZXJ5U3RhdGU6IGZ1bmN0aW9uIChjb21tYW5kLCB3aXRodmFsdWUpIHtcbiAgICAgIHZhciBvcHQgPSB0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXTtcbiAgICAgIGlmIChvcHQuZW4gIT09IHRydWUpIHtcbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgfVxuICAgICAgLy9pZiAob3B0LnN1Ykluc2VydD09PXRydWUgJiYgb3B0LnR5cGUhPVwiY29sb3JwaWNrZXJcIikge3JldHVybiBmYWxzZTt9XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmJibW9kZSkge1xuICAgICAgICAvL2JibW9kZVxuICAgICAgICBpZiAob3B0LmJiU2VsZWN0b3IpIHtcbiAgICAgICAgICBmb3IgKHZhciBpID0gMDsgaSA8IG9wdC5iYlNlbGVjdG9yLmxlbmd0aDsgaSsrKSB7XG4gICAgICAgICAgICB2YXIgYiA9IHRoaXMuaXNCQkNvbnRhaW4ob3B0LmJiU2VsZWN0b3JbaV0pO1xuICAgICAgICAgICAgaWYgKGIpIHtcbiAgICAgICAgICAgICAgcmV0dXJuIHRoaXMuZ2V0UGFyYW1zKGIsIG9wdC5iYlNlbGVjdG9yW2ldLCBiWzFdKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgaWYgKG9wdC5leGNtZCkge1xuICAgICAgICAgIC8vbmF0aXZlIGNvbW1hbmRcbiAgICAgICAgICBpZiAod2l0aHZhbHVlKSB7XG4gICAgICAgICAgICB0cnkge1xuICAgICAgICAgICAgICAvL0ZpcmVmb3ggZml4XG4gICAgICAgICAgICAgIHZhciB2ID0gKGRvY3VtZW50LnF1ZXJ5Q29tbWFuZFZhbHVlKG9wdC5leGNtZCkgKyBcIlwiKS5yZXBsYWNlKC9cXCcvZywgXCJcIik7XG4gICAgICAgICAgICAgIGlmIChvcHQuZXhjbWQgPT0gXCJmb3JlQ29sb3JcIikge1xuICAgICAgICAgICAgICAgIHYgPSB0aGlzLnJnYlRvSGV4KHYpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIC8vcmV0dXJuICh2PT12YWx1ZSk7XG4gICAgICAgICAgICAgIHJldHVybiB2O1xuICAgICAgICAgICAgfSBjYXRjaCAoZSkge1xuICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHRyeSB7IC8vRmlyZWZveCBmaXgsIGV4Y2VwdGlvbiB3aGlsZSBnZXQgcXVlcnlTdGF0ZSBmb3IgVW5vcmRlcmVkTGlzdFxuICAgICAgICAgICAgICBpZiAoKG9wdC5leGNtZCA9PSBcImJvbGRcIiB8fCBvcHQuZXhjbWQgPT0gXCJpdGFsaWNcIiB8fCBvcHQuZXhjbWQgPT0gXCJ1bmRlcmxpbmVcIiB8fCBvcHQuZXhjbWQgPT0gXCJzdHJpa2VUaHJvdWdoXCIpICYmICQodGhpcy5nZXRTZWxlY3ROb2RlKCkpLmlzKFwiaW1nXCIpKSB7IC8vRml4LCB3aGVuIGltZyBzZWxlY3RlZFxuICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgICAgfSBlbHNlIGlmIChvcHQuZXhjbWQgPT0gXCJ1bmRlcmxpbmVcIiAmJiAkKHRoaXMuZ2V0U2VsZWN0Tm9kZSgpKS5jbG9zZXN0KFwiYVwiKS5sZW5ndGggPiAwKSB7IC8vZml4LCB3aGVuIGxpbmsgc2VsZWN0XG4gICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIHJldHVybiBkb2N1bWVudC5xdWVyeUNvbW1hbmRTdGF0ZShvcHQuZXhjbWQpO1xuICAgICAgICAgICAgfSBjYXRjaCAoZSkge1xuICAgICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfVxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIC8vY3VzdG9tIGNvbW1hbmRcbiAgICAgICAgICBpZiAoJC5pc0FycmF5KG9wdC5yb290U2VsZWN0b3IpKSB7XG4gICAgICAgICAgICBmb3IgKHZhciBpID0gMDsgaSA8IG9wdC5yb290U2VsZWN0b3IubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgICAgICAgdmFyIG4gPSB0aGlzLmlzQ29udGFpbih0aGlzLmdldFNlbGVjdE5vZGUoKSwgb3B0LnJvb3RTZWxlY3RvcltpXSk7XG4gICAgICAgICAgICAgIGlmIChuKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMuZ2V0UGFyYW1zKG4sIG9wdC5yb290U2VsZWN0b3JbaV0pO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfVxuICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgd2JiRXhlY0NvbW1hbmQ6IGZ1bmN0aW9uIChjb21tYW5kLCB2YWx1ZSwgcXVlcnlTdGF0ZSkgeyAvL2RlZmF1bHQgY29tbWFuZCBmb3IgY3VzdG9tIGJiY29kZXNcbiAgICAgICQubG9nKFwid2JiRXhlY0NvbW1hbmRcIik7XG4gICAgICB2YXIgb3B0ID0gdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbY29tbWFuZF07XG4gICAgICBpZiAob3B0KSB7XG4gICAgICAgIGlmIChvcHQubW9kYWwpIHtcbiAgICAgICAgICBpZiAoJC5pc0Z1bmN0aW9uKG9wdC5tb2RhbCkpIHtcbiAgICAgICAgICAgIC8vY3VzdG9tIG1vZGFsIGZ1bmN0aW9uXG4gICAgICAgICAgICAvL29wdC5tb2RhbChjb21tYW5kLG9wdC5tb2RhbCxxdWVyeVN0YXRlLG5ldyBjbGJrKHRoaXMpKTtcbiAgICAgICAgICAgIG9wdC5tb2RhbC5jYWxsKHRoaXMsIGNvbW1hbmQsIG9wdC5tb2RhbCwgcXVlcnlTdGF0ZSk7XG4gICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMuc2hvd01vZGFsLmNhbGwodGhpcywgY29tbWFuZCwgb3B0Lm1vZGFsLCBxdWVyeVN0YXRlKTtcbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgaWYgKHF1ZXJ5U3RhdGUgJiYgb3B0LnN1Ykluc2VydCAhPSB0cnVlKSB7XG4gICAgICAgICAgICAvL3JlbW92ZSBmb3JtYXR0aW5nXG4gICAgICAgICAgICAvL3JlbW92ZUNhbGxiYWNrKGNvbW1hbmQsdmFsdWUpO1xuICAgICAgICAgICAgdGhpcy53YmJSZW1vdmVDYWxsYmFjayhjb21tYW5kKTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgLy9pbnNlcnQgZm9ybWF0XG4gICAgICAgICAgICBpZiAob3B0Lmdyb3Vwa2V5KSB7XG4gICAgICAgICAgICAgIHZhciBncm91cHNlbCA9IHRoaXMub3B0aW9ucy5ncm91cHNbb3B0Lmdyb3Vwa2V5XTtcbiAgICAgICAgICAgICAgaWYgKGdyb3Vwc2VsKSB7XG4gICAgICAgICAgICAgICAgdmFyIHNub2RlID0gdGhpcy5nZXRTZWxlY3ROb2RlKCk7XG4gICAgICAgICAgICAgICAgJC5lYWNoKGdyb3Vwc2VsLCAkLnByb3h5KGZ1bmN0aW9uIChpLCBzZWwpIHtcbiAgICAgICAgICAgICAgICAgIHZhciBpcyA9IHRoaXMuaXNDb250YWluKHNub2RlLCBzZWwpO1xuICAgICAgICAgICAgICAgICAgaWYgKGlzKSB7XG4gICAgICAgICAgICAgICAgICAgIHZhciAkc3AgPSAkKCc8c3Bhbj4nKS5odG1sKGlzLmlubmVySFRNTClcbiAgICAgICAgICAgICAgICAgICAgdmFyIGlkID0gdGhpcy5zZXRVSUQoJHNwKTtcbiAgICAgICAgICAgICAgICAgICAgJChpcykucmVwbGFjZVdpdGgoJHNwKTtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5zZWxlY3ROb2RlKHRoaXMuJGVkaXRvci5maW5kKFwiI1wiICsgaWQpWzBdKTtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgdGhpcy53YmJJbnNlcnRDYWxsYmFjayhjb21tYW5kLCB2YWx1ZSlcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9LFxuICAgIHdiYkluc2VydENhbGxiYWNrOiBmdW5jdGlvbiAoY29tbWFuZCwgcGFyYW1vYmopIHtcbiAgICAgIGlmICh0eXBlb2YgKHBhcmFtb2JqKSAhPSBcIm9iamVjdFwiKSB7XG4gICAgICAgIHBhcmFtb2JqID0ge31cbiAgICAgIH1cbiAgICAgIDtcbiAgICAgICQubG9nKFwid2JiSW5zZXJ0Q2FsbGJhY2s6IFwiICsgY29tbWFuZCk7XG4gICAgICB2YXIgZGF0YSA9IHRoaXMuZ2V0Q29kZUJ5Q29tbWFuZChjb21tYW5kLCBwYXJhbW9iaik7XG4gICAgICB0aGlzLmluc2VydEF0Q3Vyc29yKGRhdGEpO1xuXG4gICAgICBpZiAodGhpcy5zZWx0ZXh0SUQgJiYgZGF0YS5pbmRleE9mKHRoaXMuc2VsdGV4dElEKSAhPSAtMSkge1xuICAgICAgICB2YXIgc25vZGUgPSB0aGlzLiRib2R5LmZpbmQoXCIjXCIgKyB0aGlzLnNlbHRleHRJRClbMF07XG4gICAgICAgIHRoaXMuc2VsZWN0Tm9kZShzbm9kZSk7XG4gICAgICAgICQoc25vZGUpLnJlbW92ZUF0dHIoXCJpZFwiKTtcbiAgICAgICAgdGhpcy5zZWx0ZXh0SUQgPSBmYWxzZTtcbiAgICAgIH1cbiAgICB9LFxuICAgIHdiYlJlbW92ZUNhbGxiYWNrOiBmdW5jdGlvbiAoY29tbWFuZCwgY2xlYXIpIHtcbiAgICAgICQubG9nKFwid2JiUmVtb3ZlQ2FsbGJhY2s6IFwiICsgY29tbWFuZCk7XG4gICAgICB2YXIgb3B0ID0gdGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbY29tbWFuZF07XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmJibW9kZSkge1xuICAgICAgICAvL2JibW9kZVxuICAgICAgICAvL1JFTU9WRSBCQkNPREVcbiAgICAgICAgdmFyIHBvcyA9IHRoaXMuZ2V0Q3Vyc29yUG9zQkIoKTtcbiAgICAgICAgdmFyIHN0ZXh0bnVtID0gMDtcbiAgICAgICAgJC5lYWNoKG9wdC5iYlNlbGVjdG9yLCAkLnByb3h5KGZ1bmN0aW9uIChpLCBiYmNvZGUpIHtcbiAgICAgICAgICB2YXIgc3RleHQgPSBiYmNvZGUubWF0Y2goL1xce1tcXHNcXFNdKz9cXH0vZyk7XG4gICAgICAgICAgJC5lYWNoKHN0ZXh0LCBmdW5jdGlvbiAobiwgcykge1xuICAgICAgICAgICAgaWYgKHMudG9Mb3dlckNhc2UoKSA9PSBcIntzZWx0ZXh0fVwiKSB7XG4gICAgICAgICAgICAgIHN0ZXh0bnVtID0gbjtcbiAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlXG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSk7XG4gICAgICAgICAgdmFyIGEgPSB0aGlzLmlzQkJDb250YWluKGJiY29kZSk7XG4gICAgICAgICAgaWYgKGEpIHtcbiAgICAgICAgICAgIHRoaXMudHh0QXJlYS52YWx1ZSA9IHRoaXMudHh0QXJlYS52YWx1ZS5zdWJzdHIoMCwgYVsxXSkgKyB0aGlzLnR4dEFyZWEudmFsdWUuc3Vic3RyKGFbMV0sIHRoaXMudHh0QXJlYS52YWx1ZS5sZW5ndGggLSBhWzFdKS5yZXBsYWNlKGFbMF1bMF0sIChjbGVhciA9PT0gdHJ1ZSkgPyAnJyA6IGFbMF1bc3RleHRudW0gKyAxXSk7XG4gICAgICAgICAgICB0aGlzLnNldEN1cnNvclBvc0JCKGFbMV0pO1xuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgIH1cbiAgICAgICAgfSwgdGhpcykpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgdmFyIG5vZGUgPSB0aGlzLmdldFNlbGVjdE5vZGUoKTtcbiAgICAgICAgJC5lYWNoKG9wdC5yb290U2VsZWN0b3IsICQucHJveHkoZnVuY3Rpb24gKGksIHMpIHtcbiAgICAgICAgICAvLyQubG9nKFwiUlM6IFwiK3MpO1xuICAgICAgICAgIHZhciByb290ID0gdGhpcy5pc0NvbnRhaW4obm9kZSwgcyk7XG4gICAgICAgICAgaWYgKCFyb290KSB7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICB9XG4gICAgICAgICAgdmFyICRyb290ID0gJChyb290KTtcbiAgICAgICAgICB2YXIgY3MgPSB0aGlzLm9wdGlvbnMucnVsZXNbc11bMF1bMV07XG4gICAgICAgICAgaWYgKCRyb290LmlzKFwic3Bhblt3YmJdXCIpIHx8ICEkcm9vdC5pcyhcInNwYW4sZm9udFwiKSkgeyAvL3JlbW92ZSBvbmx5IGJsb2Nrc1xuICAgICAgICAgICAgaWYgKGNsZWFyID09PSB0cnVlIHx8ICghY3MgfHwgIWNzW1wic2VsdGV4dFwiXSkpIHtcbiAgICAgICAgICAgICAgdGhpcy5zZXRDdXJzb3JCeUVsKCRyb290KTtcbiAgICAgICAgICAgICAgJHJvb3QucmVtb3ZlKCk7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICBpZiAoY3MgJiYgY3NbXCJzZWx0ZXh0XCJdICYmIGNzW1wic2VsdGV4dFwiXVtcInNlbFwiXSkge1xuICAgICAgICAgICAgICAgIHZhciBodG1sZGF0YSA9ICRyb290LmZpbmQoY3NbXCJzZWx0ZXh0XCJdW1wic2VsXCJdKS5odG1sKCk7XG4gICAgICAgICAgICAgICAgaWYgKG9wdC5vbmx5Q2xlYXJUZXh0ID09PSB0cnVlKSB7XG4gICAgICAgICAgICAgICAgICBodG1sZGF0YSA9IHRoaXMuZ2V0SFRNTChodG1sZGF0YSwgdHJ1ZSwgdHJ1ZSk7XG4gICAgICAgICAgICAgICAgICBodG1sZGF0YSA9IGh0bWxkYXRhLnJlcGxhY2UoL1xcJiMxMjM7L2csIFwie1wiKS5yZXBsYWNlKC9cXCYjMTI1Oy9nLCBcIn1cIik7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICRyb290LnJlcGxhY2VXaXRoKGh0bWxkYXRhKTtcbiAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICB2YXIgaHRtbGRhdGEgPSAkcm9vdC5odG1sKCk7XG4gICAgICAgICAgICAgICAgaWYgKG9wdC5vbmx5Q2xlYXJUZXh0ID09PSB0cnVlKSB7XG4gICAgICAgICAgICAgICAgICBodG1sZGF0YSA9IHRoaXMuZ2V0SFRNTChodG1sZGF0YSwgdHJ1ZSk7XG4gICAgICAgICAgICAgICAgICBodG1sZGF0YSA9IGh0bWxkYXRhLnJlcGxhY2UoL1xcJmx0Oy9nLCBcIjxcIikucmVwbGFjZSgvXFwmZ3Q7L2csIFwiPlwiKS5yZXBsYWNlKC9cXCYjMTIzOy9nLCBcIntcIikucmVwbGFjZSgvXFwmIzEyNTsvZywgXCJ9XCIpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAkcm9vdC5yZXBsYWNlV2l0aChodG1sZGF0YSk7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgLy9zcGFuLGZvbnQgLSBleHRyYWN0IHNlbGVjdCBjb250ZW50IGZyb20gdGhpcyBzcGFuLGZvbnRcbiAgICAgICAgICAgIHZhciBybmcgPSB0aGlzLmdldFJhbmdlKCk7XG4gICAgICAgICAgICB2YXIgc2h0bWwgPSB0aGlzLmdldFNlbGVjdFRleHQoKTtcbiAgICAgICAgICAgIHZhciBybm9kZSA9IHRoaXMuZ2V0U2VsZWN0Tm9kZSgpO1xuICAgICAgICAgICAgaWYgKHNodG1sID09IFwiXCIpIHtcbiAgICAgICAgICAgICAgc2h0bWwgPSBcIlxcdUZFRkZcIjtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgIHNodG1sID0gdGhpcy5jbGVhckZyb21TdWJJbnNlcnQoc2h0bWwsIGNvbW1hbmQpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgdmFyIGlucyA9IHRoaXMuZWxGcm9tU3RyaW5nKHNodG1sKTtcblxuICAgICAgICAgICAgdmFyIGJlZm9yZV9ybmcgPSAod2luZG93LmdldFNlbGVjdGlvbikgPyBybmcuY2xvbmVSYW5nZSgpIDogdGhpcy5ib2R5LmNyZWF0ZVRleHRSYW5nZSgpO1xuICAgICAgICAgICAgdmFyIGFmdGVyX3JuZyA9ICh3aW5kb3cuZ2V0U2VsZWN0aW9uKSA/IHJuZy5jbG9uZVJhbmdlKCkgOiB0aGlzLmJvZHkuY3JlYXRlVGV4dFJhbmdlKCk7XG5cbiAgICAgICAgICAgIGlmICh3aW5kb3cuZ2V0U2VsZWN0aW9uKSB7XG4gICAgICAgICAgICAgIHRoaXMuaW5zZXJ0QXRDdXJzb3IoJzxzcGFuIGlkPVwid2JiZGl2aWRlXCI+PC9zcGFuPicpO1xuICAgICAgICAgICAgICB2YXIgZGl2ID0gJHJvb3QuZmluZCgnc3BhbiN3YmJkaXZpZGUnKS5nZXQoMCk7XG4gICAgICAgICAgICAgIGJlZm9yZV9ybmcuc2V0U3RhcnQocm9vdC5maXJzdENoaWxkLCAwKTtcbiAgICAgICAgICAgICAgYmVmb3JlX3JuZy5zZXRFbmRCZWZvcmUoZGl2KTtcbiAgICAgICAgICAgICAgYWZ0ZXJfcm5nLnNldFN0YXJ0QWZ0ZXIoZGl2KTtcbiAgICAgICAgICAgICAgYWZ0ZXJfcm5nLnNldEVuZEFmdGVyKHJvb3QubGFzdENoaWxkKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgIGJlZm9yZV9ybmcubW92ZVRvRWxlbWVudFRleHQocm9vdCk7XG4gICAgICAgICAgICAgIGFmdGVyX3JuZy5tb3ZlVG9FbGVtZW50VGV4dChyb290KTtcbiAgICAgICAgICAgICAgYmVmb3JlX3JuZy5zZXRFbmRQb2ludCgnRW5kVG9TdGFydCcsIHJuZyk7XG4gICAgICAgICAgICAgIGFmdGVyX3JuZy5zZXRFbmRQb2ludCgnU3RhcnRUb0VuZCcsIHJuZyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICB2YXIgYmYgPSB0aGlzLmdldFNlbGVjdFRleHQoZmFsc2UsIGJlZm9yZV9ybmcpO1xuICAgICAgICAgICAgdmFyIGFmID0gdGhpcy5nZXRTZWxlY3RUZXh0KGZhbHNlLCBhZnRlcl9ybmcpO1xuICAgICAgICAgICAgaWYgKGFmICE9IFwiXCIpIHtcbiAgICAgICAgICAgICAgdmFyICRhZiA9ICRyb290LmNsb25lKCkuaHRtbChhZik7XG4gICAgICAgICAgICAgICRyb290LmFmdGVyKCRhZik7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoY2xlYXIgIT09IHRydWUpICRyb290LmFmdGVyKGlucyk7IC8vaW5zZXJ0IHNlbGVjdCBodG1sXG4gICAgICAgICAgICBpZiAod2luZG93LmdldFNlbGVjdGlvbikge1xuICAgICAgICAgICAgICAkcm9vdC5odG1sKGJmKTtcbiAgICAgICAgICAgICAgaWYgKGNsZWFyICE9PSB0cnVlKSB0aGlzLnNlbGVjdE5vZGUoaW5zKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICRyb290LnJlcGxhY2VXaXRoKGJmKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICB9XG4gICAgICAgIH0sIHRoaXMpKTtcbiAgICAgIH1cbiAgICB9LFxuICAgIGV4ZWNOYXRpdmVDb21tYW5kOiBmdW5jdGlvbiAoY21kLCBwYXJhbSkge1xuICAgICAgLy8kLmxvZyhcImV4ZWNOYXRpdmVDb21tYW5kOiAnXCIrY21kK1wiJyA6IFwiK3BhcmFtKTtcbiAgICAgIHRoaXMuYm9keS5mb2N1cygpOyAvL3NldCBmb2N1cyB0byBmcmFtZSBib2R5XG4gICAgICBpZiAoY21kID09IFwiaW5zZXJ0SFRNTFwiICYmICF3aW5kb3cuZ2V0U2VsZWN0aW9uKSB7IC8vSUUgZG9lcyd0IHN1cHBvcnQgaW5zZXJ0SFRNTFxuICAgICAgICB2YXIgciA9ICh0aGlzLmxhc3RSYW5nZSkgPyB0aGlzLmxhc3RSYW5nZSA6IGRvY3VtZW50LnNlbGVjdGlvbi5jcmVhdGVSYW5nZSgpOyAvL0lFIDcsOCByYW5nZSBsb3N0IGZpeFxuICAgICAgICByLnBhc3RlSFRNTChwYXJhbSk7XG4gICAgICAgIHZhciB0eHQgPSAkKCc8ZGl2PicpLmh0bWwocGFyYW0pLnRleHQoKTsgLy9mb3IgaWUgc2VsZWN0aW9uIGluc2lkZSBibG9ja1xuICAgICAgICB2YXIgYnJzcCA9IHR4dC5pbmRleE9mKFwiXFx1RkVGRlwiKTtcbiAgICAgICAgaWYgKGJyc3AgPiAtMSkge1xuICAgICAgICAgIHIubW92ZVN0YXJ0KCdjaGFyYWN0ZXInLCAoLTEpICogKHR4dC5sZW5ndGggLSBicnNwKSk7XG4gICAgICAgICAgci5zZWxlY3QoKTtcbiAgICAgICAgfVxuICAgICAgICB0aGlzLmxhc3RSYW5nZSA9IGZhbHNlO1xuICAgICAgfSBlbHNlIGlmIChjbWQgPT0gXCJpbnNlcnRIVE1MXCIpIHsgLy9maXggd2Via2l0IGJ1ZyB3aXRoIGluc2VydEhUTUxcbiAgICAgICAgdmFyIHNlbCA9IHRoaXMuZ2V0U2VsZWN0aW9uKCk7XG4gICAgICAgIHZhciBlID0gdGhpcy5lbEZyb21TdHJpbmcocGFyYW0pO1xuICAgICAgICB2YXIgcm5nID0gKHRoaXMubGFzdFJhbmdlKSA/IHRoaXMubGFzdFJhbmdlIDogdGhpcy5nZXRSYW5nZSgpO1xuICAgICAgICBybmcuZGVsZXRlQ29udGVudHMoKTtcbiAgICAgICAgcm5nLmluc2VydE5vZGUoZSk7XG4gICAgICAgIHJuZy5jb2xsYXBzZShmYWxzZSk7XG4gICAgICAgIHNlbC5yZW1vdmVBbGxSYW5nZXMoKTtcbiAgICAgICAgc2VsLmFkZFJhbmdlKHJuZyk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBpZiAodHlwZW9mIHBhcmFtID09IFwidW5kZWZpbmVkXCIpIHtcbiAgICAgICAgICBwYXJhbSA9IGZhbHNlO1xuICAgICAgICB9XG4gICAgICAgIGlmICh0aGlzLmxhc3RSYW5nZSkge1xuICAgICAgICAgICQubG9nKFwiTGFzdCByYW5nZSBzZWxlY3RcIik7XG4gICAgICAgICAgdGhpcy5zZWxlY3RMYXN0UmFuZ2UoKVxuICAgICAgICB9XG4gICAgICAgIGRvY3VtZW50LmV4ZWNDb21tYW5kKGNtZCwgZmFsc2UsIHBhcmFtKTtcbiAgICAgIH1cblxuICAgIH0sXG4gICAgZ2V0Q29kZUJ5Q29tbWFuZDogZnVuY3Rpb24gKGNvbW1hbmQsIHBhcmFtb2JqKSB7XG4gICAgICByZXR1cm4gKHRoaXMub3B0aW9ucy5iYm1vZGUpID8gdGhpcy5nZXRCQkNvZGVCeUNvbW1hbmQoY29tbWFuZCwgcGFyYW1vYmopIDogdGhpcy5nZXRIVE1MQnlDb21tYW5kKGNvbW1hbmQsIHBhcmFtb2JqKTtcbiAgICB9LFxuICAgIGdldEJCQ29kZUJ5Q29tbWFuZDogZnVuY3Rpb24gKGNvbW1hbmQsIHBhcmFtcykge1xuICAgICAgaWYgKCF0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXSkge1xuICAgICAgICByZXR1cm4gXCJcIjtcbiAgICAgIH1cbiAgICAgIGlmICh0eXBlb2YgKHBhcmFtcykgPT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICBwYXJhbXMgPSB7fTtcbiAgICAgIH1cbiAgICAgIHBhcmFtcyA9IHRoaXMua2V5c1RvTG93ZXIocGFyYW1zKTtcbiAgICAgIGlmICghcGFyYW1zW1wic2VsdGV4dFwiXSkge1xuICAgICAgICAvL2dldCBzZWxlY3RlZCB0ZXh0XG4gICAgICAgIHBhcmFtc1tcInNlbHRleHRcIl0gPSB0aGlzLmdldFNlbGVjdFRleHQodHJ1ZSk7XG4gICAgICB9XG5cbiAgICAgIHZhciBiYmNvZGUgPSB0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXS5iYmNvZGU7XG4gICAgICAvL2JiY29kZSA9IHRoaXMuc3RyZihiYmNvZGUscGFyYW1zKTtcbiAgICAgIGJiY29kZSA9IGJiY29kZS5yZXBsYWNlKC9cXHsoLio/KShcXFsuKj9cXF0pKlxcfS9nLCBmdW5jdGlvbiAoc3RyLCBwLCB2cmd4KSB7XG4gICAgICAgIGlmICh2cmd4KSB7XG4gICAgICAgICAgdmFyIHZyZ3hwO1xuICAgICAgICAgIGlmICh2cmd4KSB7XG4gICAgICAgICAgICB2cmd4cCA9IG5ldyBSZWdFeHAodnJneCArIFwiK1wiLCBcImlcIik7XG4gICAgICAgICAgfVxuICAgICAgICAgIGlmICh0eXBlb2YgKHBhcmFtc1twLnRvTG93ZXJDYXNlKCldKSAhPSBcInVuZGVmaW5lZFwiICYmIHBhcmFtc1twLnRvTG93ZXJDYXNlKCldLnRvU3RyaW5nKCkubWF0Y2godnJneHApID09PSBudWxsKSB7XG4gICAgICAgICAgICAvL25vdCB2YWxpZCB2YWx1ZVxuICAgICAgICAgICAgcmV0dXJuIFwiXCI7XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIHJldHVybiAodHlwZW9mIChwYXJhbXNbcC50b0xvd2VyQ2FzZSgpXSkgPT0gXCJ1bmRlZmluZWRcIikgPyBcIlwiIDogcGFyYW1zW3AudG9Mb3dlckNhc2UoKV07XG4gICAgICB9KTtcblxuICAgICAgLy9pbnNlcnQgZmlyc3Qgd2l0aCBtYXggcGFyYW1zXG4gICAgICB2YXIgcmJiY29kZSA9IG51bGwsIG1heHBjb3VudCA9IDA7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbY29tbWFuZF0udHJhbnNmb3JtKSB7XG4gICAgICAgIHZhciB0ciA9IFtdO1xuICAgICAgICAkLmVhY2godGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbY29tbWFuZF0udHJhbnNmb3JtLCBmdW5jdGlvbiAoaHRtbCwgYmIpIHtcbiAgICAgICAgICB0ci5wdXNoKGJiKTtcbiAgICAgICAgfSk7XG4gICAgICAgIHRyID0gdGhpcy5zb3J0QXJyYXkodHIsIC0xKTtcbiAgICAgICAgJC5lYWNoKHRyLCBmdW5jdGlvbiAoaSwgdikge1xuICAgICAgICAgIHZhciB2YWxpZCA9IHRydWUsIHBjb3VudCA9IDAsIHBuYW1lID0ge307XG4gICAgICAgICAgO1xuICAgICAgICAgIHYgPSB2LnJlcGxhY2UoL1xceyguKj8pKFxcWy4qP1xcXSkqXFx9L2csIGZ1bmN0aW9uIChzdHIsIHAsIHZyZ3gpIHtcbiAgICAgICAgICAgIHZhciB2cmd4cDtcbiAgICAgICAgICAgIHAgPSBwLnRvTG93ZXJDYXNlKCk7XG4gICAgICAgICAgICBpZiAodnJneCkge1xuICAgICAgICAgICAgICB2cmd4cCA9IG5ldyBSZWdFeHAodnJneCArIFwiK1wiLCBcImlcIik7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAodHlwZW9mIChwYXJhbXNbcC50b0xvd2VyQ2FzZSgpXSkgPT0gXCJ1bmRlZmluZWRcIiB8fCAodnJneCAmJiBwYXJhbXNbcC50b0xvd2VyQ2FzZSgpXS50b1N0cmluZygpLm1hdGNoKHZyZ3hwKSA9PT0gbnVsbCkpIHtcbiAgICAgICAgICAgICAgdmFsaWQgPSBmYWxzZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIDtcbiAgICAgICAgICAgIGlmICh0eXBlb2YgKHBhcmFtc1twXSkgIT0gXCJ1bmRlZmluZWRcIiAmJiAhcG5hbWVbcF0pIHtcbiAgICAgICAgICAgICAgcG5hbWVbcF0gPSAxO1xuICAgICAgICAgICAgICBwY291bnQrKztcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiAodHlwZW9mIChwYXJhbXNbcC50b0xvd2VyQ2FzZSgpXSkgPT0gXCJ1bmRlZmluZWRcIikgPyBcIlwiIDogcGFyYW1zW3AudG9Mb3dlckNhc2UoKV07XG4gICAgICAgICAgfSk7XG4gICAgICAgICAgaWYgKHZhbGlkICYmIChwY291bnQgPiBtYXhwY291bnQpKSB7XG4gICAgICAgICAgICByYmJjb2RlID0gdjtcbiAgICAgICAgICAgIG1heHBjb3VudCA9IHBjb3VudDtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgfVxuICAgICAgcmV0dXJuIHJiYmNvZGUgfHwgYmJjb2RlO1xuICAgIH0sXG4gICAgZ2V0SFRNTEJ5Q29tbWFuZDogZnVuY3Rpb24gKGNvbW1hbmQsIHBhcmFtcykge1xuICAgICAgaWYgKCF0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXSkge1xuICAgICAgICByZXR1cm4gXCJcIjtcbiAgICAgIH1cbiAgICAgIHBhcmFtcyA9IHRoaXMua2V5c1RvTG93ZXIocGFyYW1zKTtcbiAgICAgIGlmICh0eXBlb2YgKHBhcmFtcykgPT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICBwYXJhbXMgPSB7fTtcbiAgICAgIH1cbiAgICAgIGlmICghcGFyYW1zW1wic2VsdGV4dFwiXSkge1xuICAgICAgICAvL2dldCBzZWxlY3RlZCB0ZXh0XG4gICAgICAgIHBhcmFtc1tcInNlbHRleHRcIl0gPSB0aGlzLmdldFNlbGVjdFRleHQoZmFsc2UpO1xuICAgICAgICAvLyQubG9nKFwic2VsdGV4dDogJ1wiK3BhcmFtc1tcInNlbHRleHRcIl0rXCInXCIpO1xuICAgICAgICBpZiAocGFyYW1zW1wic2VsdGV4dFwiXSA9PSBcIlwiKSB7XG4gICAgICAgICAgcGFyYW1zW1wic2VsdGV4dFwiXSA9IFwiXFx1RkVGRlwiO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIC8vY2xlYXIgc2VsZWN0aW9uIGZyb20gY3VycmVudCBjb21tYW5kIHRhZ3NcbiAgICAgICAgICBwYXJhbXNbXCJzZWx0ZXh0XCJdID0gdGhpcy5jbGVhckZyb21TdWJJbnNlcnQocGFyYW1zW1wic2VsdGV4dFwiXSwgY29tbWFuZCk7XG5cbiAgICAgICAgICAvL3RvQkIgaWYgcGFyYW1zIG9ubHlDbGVhclRleHQ9dHJ1ZVxuICAgICAgICAgIGlmICh0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXS5vbmx5Q2xlYXJUZXh0ID09PSB0cnVlKSB7XG4gICAgICAgICAgICBwYXJhbXNbXCJzZWx0ZXh0XCJdID0gdGhpcy50b0JCKHBhcmFtc1tcInNlbHRleHRcIl0pLnJlcGxhY2UoL1xcPC9nLCBcIiZsdDtcIikucmVwbGFjZSgvXFxuL2csIFwiPGJyLz5cIikucmVwbGFjZSgvXFxzezN9L2csICc8c3BhbiBjbGFzcz1cIndiYnRhYlwiPjwvc3Bhbj4nKTtcbiAgICAgICAgICB9XG5cbiAgICAgICAgfVxuICAgICAgfVxuXG4gICAgICB2YXIgcG9zdHNlbCA9IFwiXCI7XG4gICAgICB0aGlzLnNlbHRleHRJRCA9IFwid2JiaWRfXCIgKyAoKyt0aGlzLmxhc3RpZCk7XG4gICAgICBpZiAoY29tbWFuZCAhPSBcImxpbmtcIiAmJiBjb21tYW5kICE9IFwiaW1nXCIpIHtcbiAgICAgICAgcGFyYW1zW1wic2VsdGV4dFwiXSA9ICc8c3BhbiBpZD1cIicgKyB0aGlzLnNlbHRleHRJRCArICdcIj4nICsgcGFyYW1zW1wic2VsdGV4dFwiXSArICc8L3NwYW4+JzsgLy91c2UgZm9yIHNlbGVjdCBzZWx0ZXh0XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBwb3N0c2VsID0gJzxzcGFuIGlkPVwiJyArIHRoaXMuc2VsdGV4dElEICsgJ1wiPlxcdUZFRkY8L3NwYW4+J1xuICAgICAgfVxuICAgICAgdmFyIGh0bWwgPSB0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjb21tYW5kXS5odG1sO1xuICAgICAgaHRtbCA9IGh0bWwucmVwbGFjZSgvXFx7KC4qPykoXFxbLio/XFxdKSpcXH0vZywgZnVuY3Rpb24gKHN0ciwgcCwgdnJneCkge1xuICAgICAgICBpZiAodnJneCkge1xuICAgICAgICAgIHZhciB2cmd4cCA9IG5ldyBSZWdFeHAodnJneCArIFwiK1wiLCBcImlcIik7XG4gICAgICAgICAgaWYgKHR5cGVvZiAocGFyYW1zW3AudG9Mb3dlckNhc2UoKV0pICE9IFwidW5kZWZpbmVkXCIgJiYgcGFyYW1zW3AudG9Mb3dlckNhc2UoKV0udG9TdHJpbmcoKS5tYXRjaCh2cmd4cCkgPT09IG51bGwpIHtcbiAgICAgICAgICAgIC8vbm90IHZhbGlkIHZhbHVlXG4gICAgICAgICAgICByZXR1cm4gXCJcIjtcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuICh0eXBlb2YgKHBhcmFtc1twLnRvTG93ZXJDYXNlKCldKSA9PSBcInVuZGVmaW5lZFwiKSA/IFwiXCIgOiBwYXJhbXNbcC50b0xvd2VyQ2FzZSgpXTtcbiAgICAgIH0pO1xuXG4gICAgICAvL2luc2VydCBmaXJzdCB3aXRoIG1heCBwYXJhbXNcbiAgICAgIHZhciByaHRtbCA9IG51bGwsIG1heHBjb3VudCA9IDA7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbY29tbWFuZF0udHJhbnNmb3JtKSB7XG4gICAgICAgIHZhciB0ciA9IFtdO1xuICAgICAgICAkLmVhY2godGhpcy5vcHRpb25zLmFsbEJ1dHRvbnNbY29tbWFuZF0udHJhbnNmb3JtLCBmdW5jdGlvbiAoaHRtbCwgYmIpIHtcbiAgICAgICAgICB0ci5wdXNoKGh0bWwpO1xuICAgICAgICB9KTtcbiAgICAgICAgdHIgPSB0aGlzLnNvcnRBcnJheSh0ciwgLTEpO1xuICAgICAgICAkLmVhY2godHIsIGZ1bmN0aW9uIChpLCB2KSB7XG4gICAgICAgICAgdmFyIHZhbGlkID0gdHJ1ZSwgcGNvdW50ID0gMCwgcG5hbWUgPSB7fTtcbiAgICAgICAgICB2ID0gdi5yZXBsYWNlKC9cXHsoLio/KShcXFsuKj9cXF0pKlxcfS9nLCBmdW5jdGlvbiAoc3RyLCBwLCB2cmd4KSB7XG4gICAgICAgICAgICB2YXIgdnJneHA7XG4gICAgICAgICAgICBwID0gcC50b0xvd2VyQ2FzZSgpO1xuICAgICAgICAgICAgaWYgKHZyZ3gpIHtcbiAgICAgICAgICAgICAgdnJneHAgPSBuZXcgUmVnRXhwKHZyZ3ggKyBcIitcIiwgXCJpXCIpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKHR5cGVvZiAocGFyYW1zW3BdKSA9PSBcInVuZGVmaW5lZFwiIHx8ICh2cmd4ICYmIHBhcmFtc1twXS50b1N0cmluZygpLm1hdGNoKHZyZ3hwKSA9PT0gbnVsbCkpIHtcbiAgICAgICAgICAgICAgdmFsaWQgPSBmYWxzZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIDtcbiAgICAgICAgICAgIGlmICh0eXBlb2YgKHBhcmFtc1twXSkgIT0gXCJ1bmRlZmluZWRcIiAmJiAhcG5hbWVbcF0pIHtcbiAgICAgICAgICAgICAgcG5hbWVbcF0gPSAxO1xuICAgICAgICAgICAgICBwY291bnQrKztcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiAodHlwZW9mIChwYXJhbXNbcF0pID09IFwidW5kZWZpbmVkXCIpID8gXCJcIiA6IHBhcmFtc1twXTtcbiAgICAgICAgICB9KTtcbiAgICAgICAgICBpZiAodmFsaWQgJiYgKHBjb3VudCA+IG1heHBjb3VudCkpIHtcbiAgICAgICAgICAgIHJodG1sID0gdjtcbiAgICAgICAgICAgIG1heHBjb3VudCA9IHBjb3VudDtcbiAgICAgICAgICB9XG4gICAgICAgIH0pO1xuICAgICAgfVxuICAgICAgcmV0dXJuIChyaHRtbCB8fCBodG1sKSArIHBvc3RzZWw7XG4gICAgfSxcblxuICAgIC8vU0VMRUNUSU9OIEZVTkNUSU9OU1xuICAgIGdldFNlbGVjdGlvbjogZnVuY3Rpb24gKCkge1xuICAgICAgaWYgKHdpbmRvdy5nZXRTZWxlY3Rpb24pIHtcbiAgICAgICAgcmV0dXJuIHdpbmRvdy5nZXRTZWxlY3Rpb24oKTtcbiAgICAgIH0gZWxzZSBpZiAoZG9jdW1lbnQuc2VsZWN0aW9uKSB7XG4gICAgICAgIHJldHVybiAodGhpcy5vcHRpb25zLmJibW9kZSkgPyBkb2N1bWVudC5zZWxlY3Rpb24uY3JlYXRlUmFuZ2UoKSA6IGRvY3VtZW50LnNlbGVjdGlvbi5jcmVhdGVSYW5nZSgpO1xuICAgICAgfVxuICAgIH0sXG4gICAgZ2V0U2VsZWN0VGV4dDogZnVuY3Rpb24gKGZyb21UeHRBcmVhLCByYW5nZSkge1xuICAgICAgaWYgKGZyb21UeHRBcmVhKSB7XG4gICAgICAgIC8vcmV0dXJuIHNlbGVjdCB0ZXh0IGZyb20gdGV4dGFyZWFcbiAgICAgICAgdGhpcy50eHRBcmVhLmZvY3VzKCk7XG4gICAgICAgIGlmICgnc2VsZWN0aW9uU3RhcnQnIGluIHRoaXMudHh0QXJlYSkge1xuICAgICAgICAgIHZhciBsID0gdGhpcy50eHRBcmVhLnNlbGVjdGlvbkVuZCAtIHRoaXMudHh0QXJlYS5zZWxlY3Rpb25TdGFydDtcbiAgICAgICAgICByZXR1cm4gdGhpcy50eHRBcmVhLnZhbHVlLnN1YnN0cih0aGlzLnR4dEFyZWEuc2VsZWN0aW9uU3RhcnQsIGwpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIC8vSUVcbiAgICAgICAgICB2YXIgciA9IGRvY3VtZW50LnNlbGVjdGlvbi5jcmVhdGVSYW5nZSgpO1xuICAgICAgICAgIHJldHVybiByLnRleHQ7XG4gICAgICAgIH1cbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIC8vcmV0dXJuIHNlbGVjdCBodG1sIGZyb20gYm9keVxuICAgICAgICB0aGlzLmJvZHkuZm9jdXMoKTtcbiAgICAgICAgaWYgKCFyYW5nZSkge1xuICAgICAgICAgIHJhbmdlID0gdGhpcy5nZXRSYW5nZSgpXG4gICAgICAgIH1cbiAgICAgICAgO1xuICAgICAgICBpZiAod2luZG93LmdldFNlbGVjdGlvbikge1xuICAgICAgICAgIC8vdzNjXG4gICAgICAgICAgaWYgKHJhbmdlKSB7XG4gICAgICAgICAgICByZXR1cm4gJCgnPGRpdj4nKS5hcHBlbmQocmFuZ2UuY2xvbmVDb250ZW50cygpKS5odG1sKCk7XG4gICAgICAgICAgfVxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIC8vaWVcbiAgICAgICAgICByZXR1cm4gcmFuZ2UuaHRtbFRleHQ7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICAgIHJldHVybiBcIlwiO1xuICAgIH0sXG4gICAgZ2V0UmFuZ2U6IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICh3aW5kb3cuZ2V0U2VsZWN0aW9uKSB7XG4gICAgICAgIHZhciBzZWwgPSB0aGlzLmdldFNlbGVjdGlvbigpO1xuICAgICAgICBpZiAoc2VsLmdldFJhbmdlQXQgJiYgc2VsLnJhbmdlQ291bnQgPiAwKSB7XG4gICAgICAgICAgcmV0dXJuIHNlbC5nZXRSYW5nZUF0KDApO1xuICAgICAgICB9IGVsc2UgaWYgKHNlbC5hbmNob3JOb2RlKSB7XG4gICAgICAgICAgdmFyIHJhbmdlID0gKHRoaXMub3B0aW9ucy5iYm1vZGUpID8gZG9jdW1lbnQuY3JlYXRlUmFuZ2UoKSA6IGRvY3VtZW50LmNyZWF0ZVJhbmdlKCk7XG4gICAgICAgICAgcmFuZ2Uuc2V0U3RhcnQoc2VsLmFuY2hvck5vZGUsIHNlbC5hbmNob3JPZmZzZXQpO1xuICAgICAgICAgIHJhbmdlLnNldEVuZChzZWwuZm9jdXNOb2RlLCBzZWwuZm9jdXNPZmZzZXQpO1xuICAgICAgICAgIHJldHVybiByYW5nZTtcbiAgICAgICAgfVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgcmV0dXJuICh0aGlzLm9wdGlvbnMuYmJtb2RlID09PSB0cnVlKSA/IGRvY3VtZW50LnNlbGVjdGlvbi5jcmVhdGVSYW5nZSgpIDogZG9jdW1lbnQuc2VsZWN0aW9uLmNyZWF0ZVJhbmdlKCk7XG4gICAgICB9XG4gICAgfSxcbiAgICBpbnNlcnRBdEN1cnNvcjogZnVuY3Rpb24gKGNvZGUsIGZvcmNlQkJNb2RlKSB7XG4gICAgICBpZiAodHlwZW9mIChjb2RlKSAhPSBcInN0cmluZ1wiKSB7XG4gICAgICAgIGNvZGUgPSAkKFwiPGRpdj5cIikuYXBwZW5kKGNvZGUpLmh0bWwoKTtcbiAgICAgIH1cbiAgICAgIGlmICgodGhpcy5vcHRpb25zLmJibW9kZSAmJiB0eXBlb2YgKGZvcmNlQkJNb2RlKSA9PSBcInVuZGVmaW5lZFwiKSB8fCBmb3JjZUJCTW9kZSA9PT0gdHJ1ZSkge1xuICAgICAgICB2YXIgY2xiYiA9IGNvZGUucmVwbGFjZSgvLiooXFxbXFwvXFxTKz9cXF0pJC8sIFwiJDFcIik7XG4gICAgICAgIHZhciBwID0gdGhpcy5nZXRDdXJzb3JQb3NCQigpICsgKChjb2RlLmluZGV4T2YoY2xiYikgIT0gLTEgJiYgY29kZS5tYXRjaCgvXFxbLipcXF0vKSkgPyBjb2RlLmluZGV4T2YoY2xiYikgOiBjb2RlLmxlbmd0aCk7XG4gICAgICAgIGlmIChkb2N1bWVudC5zZWxlY3Rpb24pIHtcbiAgICAgICAgICAvL0lFXG4gICAgICAgICAgdGhpcy50eHRBcmVhLmZvY3VzKCk7XG4gICAgICAgICAgdGhpcy5nZXRTZWxlY3Rpb24oKS50ZXh0ID0gY29kZTtcbiAgICAgICAgfSBlbHNlIGlmICh0aGlzLnR4dEFyZWEuc2VsZWN0aW9uU3RhcnQgfHwgdGhpcy50eHRBcmVhLnNlbGVjdGlvblN0YXJ0ID09ICcwJykge1xuICAgICAgICAgIHRoaXMudHh0QXJlYS52YWx1ZSA9IHRoaXMudHh0QXJlYS52YWx1ZS5zdWJzdHJpbmcoMCwgdGhpcy50eHRBcmVhLnNlbGVjdGlvblN0YXJ0KSArIGNvZGUgKyB0aGlzLnR4dEFyZWEudmFsdWUuc3Vic3RyaW5nKHRoaXMudHh0QXJlYS5zZWxlY3Rpb25FbmQsIHRoaXMudHh0QXJlYS52YWx1ZS5sZW5ndGgpO1xuICAgICAgICB9XG4gICAgICAgIGlmIChwIDwgMCkge1xuICAgICAgICAgIHAgPSAwO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMuc2V0Q3Vyc29yUG9zQkIocCk7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICB0aGlzLmV4ZWNOYXRpdmVDb21tYW5kKFwiaW5zZXJ0SFRNTFwiLCBjb2RlKTtcbiAgICAgICAgdmFyIG5vZGUgPSB0aGlzLmdldFNlbGVjdE5vZGUoKTtcbiAgICAgICAgaWYgKCEkKG5vZGUpLmNsb3Nlc3QoXCJ0YWJsZSx0cix0ZFwiKSkge1xuICAgICAgICAgIHRoaXMuc3BsaXRQcmV2TmV4dChub2RlKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgZ2V0U2VsZWN0Tm9kZTogZnVuY3Rpb24gKHJuZykge1xuICAgICAgdGhpcy5ib2R5LmZvY3VzKCk7XG4gICAgICBpZiAoIXJuZykge1xuICAgICAgICBybmcgPSB0aGlzLmdldFJhbmdlKCk7XG4gICAgICB9XG4gICAgICBpZiAoIXJuZykge1xuICAgICAgICByZXR1cm4gdGhpcy4kYm9keTtcbiAgICAgIH1cbiAgICAgIC8vcmV0dXJuICh3aW5kb3cuZ2V0U2VsZWN0aW9uKSA/IHJuZy5jb21tb25BbmNlc3RvckNvbnRhaW5lcjpybmcucGFyZW50RWxlbWVudCgpO1xuICAgICAgdmFyIHNuID0gKHdpbmRvdy5nZXRTZWxlY3Rpb24pID8gcm5nLmNvbW1vbkFuY2VzdG9yQ29udGFpbmVyIDogcm5nLnBhcmVudEVsZW1lbnQoKTtcbiAgICAgIGlmICgkKHNuKS5pcyhcIi5pbWdXcmFwXCIpKSB7XG4gICAgICAgIHNuID0gJChzbikuY2hpbGRyZW4oXCJpbWdcIilbMF07XG4gICAgICB9XG4gICAgICByZXR1cm4gc247XG4gICAgfSxcbiAgICBnZXRDdXJzb3JQb3NCQjogZnVuY3Rpb24gKCkge1xuICAgICAgdmFyIHBvcyA9IDA7XG4gICAgICBpZiAoJ3NlbGVjdGlvblN0YXJ0JyBpbiB0aGlzLnR4dEFyZWEpIHtcbiAgICAgICAgcG9zID0gdGhpcy50eHRBcmVhLnNlbGVjdGlvblN0YXJ0O1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgdGhpcy50eHRBcmVhLmZvY3VzKCk7XG4gICAgICAgIHZhciByID0gdGhpcy5nZXRSYW5nZSgpO1xuICAgICAgICB2YXIgcnQgPSBkb2N1bWVudC5ib2R5LmNyZWF0ZVRleHRSYW5nZSgpO1xuICAgICAgICBydC5tb3ZlVG9FbGVtZW50VGV4dCh0aGlzLnR4dEFyZWEpO1xuICAgICAgICBydC5zZXRFbmRQb2ludCgnRW5kVG9TdGFydCcsIHIpO1xuICAgICAgICBwb3MgPSBydC50ZXh0Lmxlbmd0aDtcbiAgICAgIH1cbiAgICAgIHJldHVybiBwb3M7XG4gICAgfSxcbiAgICBzZXRDdXJzb3JQb3NCQjogZnVuY3Rpb24gKHBvcykge1xuICAgICAgaWYgKHRoaXMub3B0aW9ucy5iYm1vZGUpIHtcbiAgICAgICAgaWYgKHdpbmRvdy5nZXRTZWxlY3Rpb24pIHtcbiAgICAgICAgICB0aGlzLnR4dEFyZWEuc2VsZWN0aW9uU3RhcnQgPSBwb3M7XG4gICAgICAgICAgdGhpcy50eHRBcmVhLnNlbGVjdGlvbkVuZCA9IHBvcztcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICB2YXIgcmFuZ2UgPSB0aGlzLnR4dEFyZWEuY3JlYXRlVGV4dFJhbmdlKCk7XG4gICAgICAgICAgcmFuZ2UuY29sbGFwc2UodHJ1ZSk7XG4gICAgICAgICAgcmFuZ2UubW92ZSgnY2hhcmFjdGVyJywgcG9zKTtcbiAgICAgICAgICByYW5nZS5zZWxlY3QoKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgc2VsZWN0Tm9kZTogZnVuY3Rpb24gKG5vZGUsIHJuZykge1xuICAgICAgaWYgKCFybmcpIHtcbiAgICAgICAgcm5nID0gdGhpcy5nZXRSYW5nZSgpO1xuICAgICAgfVxuICAgICAgaWYgKCFybmcpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgICAgfVxuICAgICAgaWYgKHdpbmRvdy5nZXRTZWxlY3Rpb24pIHtcbiAgICAgICAgdmFyIHNlbCA9IHRoaXMuZ2V0U2VsZWN0aW9uKCk7XG4gICAgICAgIHJuZy5zZWxlY3ROb2RlQ29udGVudHMobm9kZSlcbiAgICAgICAgc2VsLnJlbW92ZUFsbFJhbmdlcygpO1xuICAgICAgICBzZWwuYWRkUmFuZ2Uocm5nKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHJuZy5tb3ZlVG9FbGVtZW50VGV4dChub2RlKTtcbiAgICAgICAgcm5nLnNlbGVjdCgpO1xuICAgICAgfVxuICAgIH0sXG4gICAgc2VsZWN0UmFuZ2U6IGZ1bmN0aW9uIChybmcpIHtcbiAgICAgIGlmIChybmcpIHtcbiAgICAgICAgaWYgKCF3aW5kb3cuZ2V0U2VsZWN0aW9uKSB7XG4gICAgICAgICAgcm5nLnNlbGVjdCgpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIHZhciBzZWwgPSB0aGlzLmdldFNlbGVjdGlvbigpO1xuICAgICAgICAgIHNlbC5yZW1vdmVBbGxSYW5nZXMoKTtcbiAgICAgICAgICBzZWwuYWRkUmFuZ2Uocm5nKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgY2xvbmVSYW5nZTogZnVuY3Rpb24gKHJuZykge1xuICAgICAgaWYgKHJuZykge1xuICAgICAgICBpZiAoIXdpbmRvdy5nZXRTZWxlY3Rpb24pIHtcbiAgICAgICAgICByZXR1cm4gcm5nLmR1cGxpY2F0ZSgpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIHJldHVybiBybmcuY2xvbmVSYW5nZSgpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcbiAgICBnZXRSYW5nZUNsb25lOiBmdW5jdGlvbiAoKSB7XG4gICAgICByZXR1cm4gdGhpcy5jbG9uZVJhbmdlKHRoaXMuZ2V0UmFuZ2UoKSk7XG4gICAgfSxcbiAgICBzYXZlUmFuZ2U6IGZ1bmN0aW9uICgpIHtcbiAgICAgIHRoaXMuc2V0Qm9keUZvY3VzKCk7XG4gICAgICAvL3RoaXMubGFzdFJhbmdlPSh0aGlzLm9wdGlvbnMuYmJtb2RlKSA/IHRoaXMuZ2V0Q3Vyc29yUG9zQkIoKTp0aGlzLmdldFJhbmdlQ2xvbmUoKTtcbiAgICAgIHRoaXMubGFzdFJhbmdlID0gdGhpcy5nZXRSYW5nZUNsb25lKCk7XG4gICAgfSxcbiAgICBzZWxlY3RMYXN0UmFuZ2U6IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICh0aGlzLmxhc3RSYW5nZSkge1xuICAgICAgICB0aGlzLmJvZHkuZm9jdXMoKTtcbiAgICAgICAgdGhpcy5zZWxlY3RSYW5nZSh0aGlzLmxhc3RSYW5nZSk7XG4gICAgICAgIHRoaXMubGFzdFJhbmdlID0gZmFsc2U7XG4gICAgICB9XG4gICAgfSxcbiAgICBzZXRCb2R5Rm9jdXM6IGZ1bmN0aW9uICgpIHtcbiAgICAgICQubG9nKFwiU2V0IGZvY3VzIHRvIFd5c2lCQiBlZGl0b3JcIik7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmJibW9kZSkge1xuICAgICAgICBpZiAoIXRoaXMuJHR4dEFyZWEuaXMoXCI6Zm9jdXNcIikpIHtcbiAgICAgICAgICB0aGlzLiR0eHRBcmVhLmZvY3VzKCk7XG4gICAgICAgIH1cbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIGlmICghdGhpcy4kYm9keS5pcyhcIjpmb2N1c1wiKSkge1xuICAgICAgICAgIHRoaXMuJGJvZHkuZm9jdXMoKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgY2xlYXJMYXN0UmFuZ2U6IGZ1bmN0aW9uICgpIHtcbiAgICAgIHRoaXMubGFzdFJhbmdlID0gZmFsc2U7XG4gICAgfSxcblxuICAgIC8vVFJBTlNGT1JNIEZVTkNUSU9OU1xuICAgIGZpbHRlckJ5Tm9kZTogZnVuY3Rpb24gKG5vZGUpIHtcbiAgICAgIHZhciAkbiA9ICQobm9kZSk7XG4gICAgICB2YXIgdGFnTmFtZSA9ICRuLmdldCgwKS50YWdOYW1lLnRvTG93ZXJDYXNlKCk7XG4gICAgICB2YXIgZmlsdGVyID0gdGFnTmFtZTtcbiAgICAgIHZhciBhdHRyaWJ1dGVzID0gdGhpcy5nZXRBdHRyaWJ1dGVMaXN0KCRuLmdldCgwKSk7XG4gICAgICAkLmVhY2goYXR0cmlidXRlcywgJC5wcm94eShmdW5jdGlvbiAoaSwgaXRlbSkge1xuICAgICAgICB2YXIgdiA9ICRuLmF0dHIoaXRlbSk7XG4gICAgICAgIC8qICQubG9nKFwidjogXCIrdik7XG5cdFx0XHRcdGlmICgkLmluQXJyYXkoaXRlbSx0aGlzLm9wdGlvbnMuYXR0cldyYXApIT0tMSkge1xuXHRcdFx0XHRcdGl0ZW0gPSAnXycraXRlbTtcblx0XHRcdFx0fSAqL1xuICAgICAgICAvLyQubG9nKGl0ZW0pO1xuICAgICAgICBpZiAoaXRlbS5zdWJzdHIoMCwgMSkgPT0gXCJfXCIpIHtcbiAgICAgICAgICBpdGVtID0gaXRlbS5zdWJzdHIoMSwgaXRlbS5sZW5ndGgpXG4gICAgICAgIH1cbiAgICAgICAgaWYgKHYgJiYgIXYubWF0Y2goL1xcey4qP1xcfS8pKSB7XG4gICAgICAgICAgLy8kLmxvZyhcIkkxOiBcIitpdGVtKTtcbiAgICAgICAgICBpZiAoaXRlbSA9PSBcInN0eWxlXCIpIHtcbiAgICAgICAgICAgIHZhciB2ID0gJG4uYXR0cihpdGVtKTtcbiAgICAgICAgICAgIHZhciB2YSA9IHYuc3BsaXQoXCI7XCIpO1xuICAgICAgICAgICAgJC5lYWNoKHZhLCBmdW5jdGlvbiAoaSwgZikge1xuICAgICAgICAgICAgICBpZiAoZiAmJiBmLmxlbmd0aCA+IDApIHtcbiAgICAgICAgICAgICAgICBmaWx0ZXIgKz0gJ1snICsgaXRlbSArICcqPVwiJyArICQudHJpbShmKSArICdcIl0nO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgZmlsdGVyICs9ICdbJyArIGl0ZW0gKyAnPVwiJyArIHYgKyAnXCJdJztcbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSBpZiAodiAmJiBpdGVtID09IFwic3R5bGVcIikge1xuICAgICAgICAgIC8vJC5sb2coXCJJMjogXCIraXRlbSk7XG4gICAgICAgICAgdmFyIHZmID0gdi5zdWJzdHIoMCwgdi5pbmRleE9mKFwie1wiKSk7XG4gICAgICAgICAgaWYgKHZmICYmIHZmICE9IFwiXCIpIHtcbiAgICAgICAgICAgIHZhciB2ID0gdi5zdWJzdHIoMCwgdi5pbmRleE9mKFwie1wiKSk7XG4gICAgICAgICAgICB2YXIgdmEgPSB2LnNwbGl0KFwiO1wiKTtcbiAgICAgICAgICAgICQuZWFjaCh2YSwgZnVuY3Rpb24gKGksIGYpIHtcbiAgICAgICAgICAgICAgZmlsdGVyICs9ICdbJyArIGl0ZW0gKyAnKj1cIicgKyBmICsgJ1wiXSc7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIC8vZmlsdGVyKz0nWycraXRlbSsnKj1cIicrdi5zdWJzdHIoMCx2LmluZGV4T2YoXCJ7XCIpKSsnXCJdJztcbiAgICAgICAgICB9XG4gICAgICAgIH0gZWxzZSB7IC8vMS4yLjJcbiAgICAgICAgICAvLyQubG9nKFwiSTM6IFwiK2l0ZW0pO1xuICAgICAgICAgIGZpbHRlciArPSAnWycgKyBpdGVtICsgJ10nO1xuICAgICAgICB9XG4gICAgICB9LCB0aGlzKSk7XG5cbiAgICAgIC8vaW5kZXhcbiAgICAgIHZhciBpZHggPSAkbi5wYXJlbnQoKS5jaGlsZHJlbihmaWx0ZXIpLmluZGV4KCRuKTtcbiAgICAgIGlmIChpZHggPiAwKSB7XG4gICAgICAgIGZpbHRlciArPSBcIjplcShcIiArICRuLmluZGV4KCkgKyBcIilcIjtcbiAgICAgIH1cbiAgICAgIHJldHVybiBmaWx0ZXI7XG4gICAgfSxcbiAgICByZWxGaWx0ZXJCeU5vZGU6IGZ1bmN0aW9uIChub2RlLCBzdG9wKSB7XG4gICAgICB2YXIgcCA9IFwiXCI7XG4gICAgICAkLmVhY2godGhpcy5vcHRpb25zLmF0dHJXcmFwLCBmdW5jdGlvbiAoaSwgYSkge1xuICAgICAgICBzdG9wID0gc3RvcC5yZXBsYWNlKCdbJyArIGEsICdbXycgKyBhKTtcbiAgICAgIH0pO1xuICAgICAgd2hpbGUgKG5vZGUgJiYgbm9kZS50YWdOYW1lICE9IFwiQk9EWVwiICYmICEkKG5vZGUpLmlzKHN0b3ApKSB7XG4gICAgICAgIHAgPSB0aGlzLmZpbHRlckJ5Tm9kZShub2RlKSArIFwiIFwiICsgcDtcbiAgICAgICAgaWYgKG5vZGUpIHtcbiAgICAgICAgICBub2RlID0gbm9kZS5wYXJlbnROb2RlO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgICByZXR1cm4gcDtcbiAgICB9LFxuICAgIGdldFJlZ2V4cFJlcGxhY2U6IGZ1bmN0aW9uIChzdHIsIHZhbGlkbmFtZSkge1xuICAgICAgc3RyID0gc3RyLnJlcGxhY2UoLyhcXCh8XFwpfFxcW3xcXF18XFwufFxcKnxcXD98XFw6fFxcXFwpL2csIFwiXFxcXCQxXCIpXG4gICAgICAgIC5yZXBsYWNlKC9cXHMrL2csIFwiXFxcXHMrXCIpXG4gICAgICAgIC5yZXBsYWNlKHZhbGlkbmFtZS5yZXBsYWNlKC8oXFwofFxcKXxcXFt8XFxdfFxcLnxcXCp8XFw/fFxcOnxcXFxcKS9nLCBcIlxcXFwkMVwiKSwgXCIoLispXCIpXG4gICAgICAgIC5yZXBsYWNlKC9cXHtcXFMrP1xcfS9nLCBcIi4qXCIpO1xuICAgICAgcmV0dXJuIChzdHIpO1xuICAgIH0sXG4gICAgZ2V0QkJDb2RlOiBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAoIXRoaXMub3B0aW9ucy5ydWxlcykge1xuICAgICAgICByZXR1cm4gdGhpcy4kdHh0QXJlYS52YWwoKTtcbiAgICAgIH1cbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuYmJtb2RlKSB7XG4gICAgICAgIHJldHVybiB0aGlzLiR0eHRBcmVhLnZhbCgpO1xuICAgICAgfVxuICAgICAgdGhpcy5jbGVhckVtcHR5KCk7XG4gICAgICB0aGlzLnJlbW92ZUxhc3RCb2R5QlIoKTtcbiAgICAgIHJldHVybiB0aGlzLnRvQkIodGhpcy4kYm9keS5odG1sKCkpO1xuICAgIH0sXG4gICAgdG9CQjogZnVuY3Rpb24gKGRhdGEpIHtcbiAgICAgIGlmICghZGF0YSkge1xuICAgICAgICByZXR1cm4gXCJcIjtcbiAgICAgIH1cbiAgICAgIDtcbiAgICAgIHZhciAkZSA9ICh0eXBlb2YgKGRhdGEpID09IFwic3RyaW5nXCIpID8gJCgnPHNwYW4+JykuaHRtbChkYXRhKSA6ICQoZGF0YSk7XG4gICAgICAvL3JlbW92ZSBsYXN0IEJSXG4gICAgICAkZS5maW5kKFwiZGl2LGJsb2NrcXVvdGUscFwiKS5lYWNoKGZ1bmN0aW9uICgpIHtcbiAgICAgICAgaWYgKHRoaXMubm9kZVR5cGUgIT0gMyAmJiB0aGlzLmxhc3RDaGlsZCAmJiB0aGlzLmxhc3RDaGlsZC50YWdOYW1lID09IFwiQlJcIikge1xuICAgICAgICAgICQodGhpcy5sYXN0Q2hpbGQpLnJlbW92ZSgpO1xuICAgICAgICB9XG4gICAgICB9KVxuICAgICAgaWYgKCRlLmlzKFwiZGl2LGJsb2NrcXVvdGUscFwiKSAmJiAkZVswXS5ub2RlVHlwZSAhPSAzICYmICRlWzBdLmxhc3RDaGlsZCAmJiAkZVswXS5sYXN0Q2hpbGQudGFnTmFtZSA9PSBcIkJSXCIpIHtcbiAgICAgICAgJCgkZVswXS5sYXN0Q2hpbGQpLnJlbW92ZSgpO1xuICAgICAgfVxuICAgICAgLy9FTkQgcmVtb3ZlIGxhc3QgQlJcblxuICAgICAgLy9SZW1vdmUgQlJcbiAgICAgICRlLmZpbmQoXCJ1bCA+IGJyLCB0YWJsZSA+IGJyLCB0ciA+IGJyXCIpLnJlbW92ZSgpO1xuICAgICAgLy9JRVxuXG4gICAgICB2YXIgb3V0YmIgPSBcIlwiO1xuXG4gICAgICAvL3RyYW5zZm9ybSBzbWlsZXNcbiAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMuc3J1bGVzLCAkLnByb3h5KGZ1bmN0aW9uIChzLCBiYikge1xuICAgICAgICAkZS5maW5kKHMpLnJlcGxhY2VXaXRoKGJiWzBdKTtcbiAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgJGUuY29udGVudHMoKS5lYWNoKCQucHJveHkoZnVuY3Rpb24gKGksIGVsKSB7XG4gICAgICAgIHZhciAkZWwgPSAkKGVsKTtcbiAgICAgICAgaWYgKGVsLm5vZGVUeXBlID09PSAzKSB7XG4gICAgICAgICAgb3V0YmIgKz0gZWwuZGF0YS5yZXBsYWNlKC9cXG4rLywgXCJcIikucmVwbGFjZSgvXFx0L2csIFwiICAgXCIpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIC8vcHJvY2VzcyBodG1sIHRhZ1xuICAgICAgICAgIHZhciBycGwsIHByb2Nlc3NlZCA9IGZhbHNlO1xuXG4gICAgICAgICAgLy9mb3IgKHZhciByb290c2VsIGluIHRoaXMub3B0aW9ucy5ydWxlcykge1xuICAgICAgICAgIGZvciAodmFyIGogPSAwOyBqIDwgdGhpcy5yc2VsbGlzdC5sZW5ndGg7IGorKykge1xuICAgICAgICAgICAgdmFyIHJvb3RzZWwgPSB0aGlzLnJzZWxsaXN0W2pdO1xuICAgICAgICAgICAgaWYgKCRlbCAmJiAkZWwuaXMocm9vdHNlbCkpIHtcbiAgICAgICAgICAgICAgLy9pdCBpcyByb290IHNlbFxuICAgICAgICAgICAgICB2YXIgcmxpc3QgPSB0aGlzLm9wdGlvbnMucnVsZXNbcm9vdHNlbF07XG4gICAgICAgICAgICAgIGZvciAodmFyIGkgPSAwOyBpIDwgcmxpc3QubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgICAgICAgICB2YXIgYmJjb2RlID0gcmxpc3RbaV1bMF07XG4gICAgICAgICAgICAgICAgdmFyIGNydWxlcyA9IHJsaXN0W2ldWzFdO1xuICAgICAgICAgICAgICAgIHZhciBza2lwID0gZmFsc2UsIGtlZXBFbGVtZW50ID0gZmFsc2UsIGtlZXBBdHRyID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgaWYgKCEkZWwuaXMoXCJiclwiKSkge1xuICAgICAgICAgICAgICAgICAgYmJjb2RlID0gYmJjb2RlLnJlcGxhY2UoL1xcbi9nLCBcIjxicj5cIik7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGJiY29kZSA9IGJiY29kZS5yZXBsYWNlKC9cXHsoLio/KShcXFsuKj9cXF0pKlxcfS9nLCAkLnByb3h5KGZ1bmN0aW9uIChzdHIsIHMsIHZyZ3gpIHtcbiAgICAgICAgICAgICAgICAgIHZhciBjID0gY3J1bGVzW3MudG9Mb3dlckNhc2UoKV07XG4gICAgICAgICAgICAgICAgICAvL2lmICh0eXBlb2YoYyk9PVwidW5kZWZpbmVkXCIpIHskLmxvZyhcIlBhcmFtOiB7XCIrcytcIn0gbm90IGZvdW5kIGluIEhUTUwgcmVwcmVzZW50YXRpb24uXCIpO3NraXA9dHJ1ZTtyZXR1cm4gczt9XG4gICAgICAgICAgICAgICAgICBpZiAodHlwZW9mIChjKSA9PSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICAgICAgICAgICAgICAgICQubG9nKFwiUGFyYW06IHtcIiArIHMgKyBcIn0gbm90IGZvdW5kIGluIEhUTUwgcmVwcmVzZW50YXRpb24uXCIpO1xuICAgICAgICAgICAgICAgICAgICBza2lwID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgIHZhciAkY2VsID0gKGMuc2VsKSA/ICQoZWwpLmZpbmQoYy5zZWwpIDogJChlbCk7XG4gICAgICAgICAgICAgICAgICBpZiAoYy5hdHRyICYmICEkY2VsLmF0dHIoYy5hdHRyKSkge1xuICAgICAgICAgICAgICAgICAgICBza2lwID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHM7XG4gICAgICAgICAgICAgICAgICB9IC8vc2tpcCBpZiBuZWVkZWQgYXR0cmlidXRlIG5vdCBwcmVzZW50LCBtYXliZSBvdGhlciBiYmNvZGVcbiAgICAgICAgICAgICAgICAgIHZhciBjb250ID0gKGMuYXR0cikgPyAkY2VsLmF0dHIoYy5hdHRyKSA6ICRjZWwuaHRtbCgpO1xuICAgICAgICAgICAgICAgICAgaWYgKHR5cGVvZiAoY29udCkgPT0gXCJ1bmRlZmluZWRcIiB8fCBjb250ID09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgc2tpcCA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBzO1xuICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgdmFyIHJlZ2V4cCA9IGMucmd4O1xuXG4gICAgICAgICAgICAgICAgICAvL3N0eWxlIGZpeFxuICAgICAgICAgICAgICAgICAgaWYgKHJlZ2V4cCAmJiBjLmF0dHIgPT0gXCJzdHlsZVwiICYmIHJlZ2V4cC5zdWJzdHIocmVnZXhwLmxlbmd0aCAtIDEsIDEpICE9IFwiO1wiKSB7XG4gICAgICAgICAgICAgICAgICAgIHJlZ2V4cCArPSBcIjtcIjtcbiAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgIGlmIChjLmF0dHIgPT0gXCJzdHlsZVwiICYmIGNvbnQgJiYgY29udC5zdWJzdHIoY29udC5sZW5ndGggLSAxLCAxKSAhPSBcIjtcIikge1xuICAgICAgICAgICAgICAgICAgICBjb250ICs9IFwiO1wiXG4gICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAvL3ByZXBhcmUgcmVnZXhwXG4gICAgICAgICAgICAgICAgICB2YXIgcmd4ID0gKHJlZ2V4cCkgPyBuZXcgUmVnRXhwKHJlZ2V4cCwgXCJcIikgOiBmYWxzZTtcbiAgICAgICAgICAgICAgICAgIGlmIChyZ3gpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGNvbnQubWF0Y2gocmd4KSkge1xuICAgICAgICAgICAgICAgICAgICAgIHZhciBtID0gY29udC5tYXRjaChyZ3gpO1xuICAgICAgICAgICAgICAgICAgICAgIGlmIChtICYmIG0ubGVuZ3RoID09IDIpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGNvbnQgPSBtWzFdO1xuICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICBjb250ID0gXCJcIjtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAvL2lmIGl0IGlzIHN0eWxlIGF0dHIsIHRoZW4ga2VlcCB0YWcgYWxpdmUsIHJlbW92ZSB0aGlzIHN0eWxlXG4gICAgICAgICAgICAgICAgICBpZiAoYy5hdHRyICYmIHNraXAgPT09IGZhbHNlKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChjLmF0dHIgPT0gXCJzdHlsZVwiKSB7XG4gICAgICAgICAgICAgICAgICAgICAga2VlcEVsZW1lbnQgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgIHZhciBuc3R5bGUgPSBcIlwiO1xuICAgICAgICAgICAgICAgICAgICAgIHZhciByID0gYy5yZ3gucmVwbGFjZSgvXlxcLlxcKlxcPy8sIFwiXCIpLnJlcGxhY2UoL1xcLlxcKiQvLCBcIlwiKS5yZXBsYWNlKC87JC8sIFwiXCIpO1xuICAgICAgICAgICAgICAgICAgICAgICQoJGNlbC5hdHRyKFwic3R5bGVcIikuc3BsaXQoXCI7XCIpKS5lYWNoKGZ1bmN0aW9uIChpZHgsIHN0eWxlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoc3R5bGUgJiYgc3R5bGUgIT0gXCJcIikge1xuICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoIXN0eWxlLm1hdGNoKHIpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgbnN0eWxlICs9IHN0eWxlICsgXCI7XCI7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICBpZiAobnN0eWxlID09IFwiXCIpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICRjZWwucmVtb3ZlQXR0cihcInN0eWxlXCIpO1xuICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkY2VsLmF0dHIoXCJzdHlsZVwiLCBuc3R5bGUpO1xuICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfSBlbHNlIGlmIChjLnJneCA9PT0gZmFsc2UpIHtcbiAgICAgICAgICAgICAgICAgICAgICBrZWVwRWxlbWVudCA9IHRydWU7XG4gICAgICAgICAgICAgICAgICAgICAga2VlcEF0dHIgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgICRjZWwucmVtb3ZlQXR0cihjLmF0dHIpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICBpZiAoJGVsLmlzKCd0YWJsZSx0cix0ZCxmb250JykpIHtcbiAgICAgICAgICAgICAgICAgICAga2VlcEVsZW1lbnQgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICByZXR1cm4gY29udCB8fCBcIlwiO1xuICAgICAgICAgICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgICAgICAgICBpZiAoc2tpcCkge1xuICAgICAgICAgICAgICAgICAgY29udGludWU7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGlmICgkZWwuaXMoXCJpbWcsYnIsaHJcIikpIHtcbiAgICAgICAgICAgICAgICAgIC8vcmVwbGFjZSBlbGVtZW50XG4gICAgICAgICAgICAgICAgICBvdXRiYiArPSBiYmNvZGU7XG4gICAgICAgICAgICAgICAgICAkZWwgPSBudWxsO1xuICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgIGlmIChrZWVwRWxlbWVudCAmJiAhJGVsLmF0dHIoXCJub3RrZWVwXCIpKSB7XG4gICAgICAgICAgICAgICAgICAgIGlmICgkZWwuaXMoXCJ0YWJsZSx0cix0ZFwiKSkge1xuICAgICAgICAgICAgICAgICAgICAgIGJiY29kZSA9IHRoaXMuZml4VGFibGVUcmFuc2Zvcm0oYmJjb2RlKTtcbiAgICAgICAgICAgICAgICAgICAgICBvdXRiYiArPSB0aGlzLnRvQkIoJCgnPHNwYW4+JykuaHRtbChiYmNvZGUpKTtcbiAgICAgICAgICAgICAgICAgICAgICAkZWwgPSBudWxsO1xuICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICRlbC5lbXB0eSgpLmh0bWwoJzxzcGFuPicgKyBiYmNvZGUgKyAnPC9zcGFuPicpO1xuICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgIGlmICgkZWwuaXMoXCJpZnJhbWVcIikpIHtcbiAgICAgICAgICAgICAgICAgICAgICBvdXRiYiArPSBiYmNvZGU7XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgJGVsLmVtcHR5KCkuaHRtbChiYmNvZGUpO1xuICAgICAgICAgICAgICAgICAgICAgIG91dGJiICs9IHRoaXMudG9CQigkZWwpO1xuICAgICAgICAgICAgICAgICAgICAgICRlbCA9IG51bGw7XG5cbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9XG4gICAgICAgICAgaWYgKCEkZWwgfHwgJGVsLmlzKFwiaWZyYW1lLGltZ1wiKSkge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgICAgfVxuICAgICAgICAgIG91dGJiICs9IHRoaXMudG9CQigkZWwpO1xuICAgICAgICB9XG4gICAgICB9LCB0aGlzKSk7XG5cbiAgICAgIG91dGJiLnJlcGxhY2UoL1xcdUZFRkYvZywgXCJcIik7XG4gICAgICByZXR1cm4gb3V0YmI7XG4gICAgfSxcbiAgICBnZXRIVE1MOiBmdW5jdGlvbiAoYmJkYXRhLCBpbml0LCBza2lwbHQpIHtcbiAgICAgIGlmICghdGhpcy5vcHRpb25zLmJibW9kZSAmJiAhaW5pdCkge1xuICAgICAgICByZXR1cm4gdGhpcy4kYm9keS5odG1sKClcbiAgICAgIH1cblxuICAgICAgaWYgKCFza2lwbHQpIHtcbiAgICAgICAgYmJkYXRhID0gYmJkYXRhLnJlcGxhY2UoLzwvZywgXCImbHQ7XCIpLnJlcGxhY2UoL1xcey9nLCBcIiYjMTIzO1wiKS5yZXBsYWNlKC9cXH0vZywgXCImIzEyNTtcIik7XG4gICAgICB9XG4gICAgICBiYmRhdGEgPSBiYmRhdGEucmVwbGFjZSgvXFxbY29kZVxcXShbXFxzXFxTXSo/KVxcW1xcL2NvZGVcXF0vZywgZnVuY3Rpb24gKHMpIHtcbiAgICAgICAgcyA9IHMuc3Vic3RyKFwiW2NvZGVdXCIubGVuZ3RoLCBzLmxlbmd0aCAtIFwiW2NvZGVdXCIubGVuZ3RoIC0gXCJbL2NvZGVdXCIubGVuZ3RoKS5yZXBsYWNlKC9cXFsvZywgXCImIzkxO1wiKS5yZXBsYWNlKC9cXF0vZywgXCImIzkzO1wiKTtcbiAgICAgICAgcmV0dXJuIFwiW2NvZGVdXCIgKyBzICsgXCJbL2NvZGVdXCI7XG4gICAgICB9KTtcblxuXG4gICAgICAkLmVhY2godGhpcy5vcHRpb25zLmJ0bmxpc3QsICQucHJveHkoZnVuY3Rpb24gKGksIGIpIHtcbiAgICAgICAgaWYgKGIgIT0gXCJ8XCIgJiYgYiAhPSBcIi1cIikge1xuICAgICAgICAgIHZhciBmaW5kID0gdHJ1ZTtcbiAgICAgICAgICBpZiAoIXRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2JdIHx8ICF0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tiXS50cmFuc2Zvcm0pIHtcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgIH1cblxuICAgICAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tiXS50cmFuc2Zvcm0sICQucHJveHkoZnVuY3Rpb24gKGh0bWwsIGJiKSB7XG4gICAgICAgICAgICBodG1sID0gaHRtbC5yZXBsYWNlKC9cXG4vZywgXCJcIik7IC8vSUUgNyw4IEZJWFxuICAgICAgICAgICAgdmFyIGEgPSBbXTtcbiAgICAgICAgICAgIGJiID0gYmIucmVwbGFjZSgvKFxcKHxcXCl8XFxbfFxcXXxcXC58XFwqfFxcP3xcXDp8XFxcXHxcXFxcKS9nLCBcIlxcXFwkMVwiKTtcbiAgICAgICAgICAgIC8vLnJlcGxhY2UoL1xccy9nLFwiXFxcXHNcIik7XG4gICAgICAgICAgICBiYiA9IGJiLnJlcGxhY2UoL1xceyguKj8pKFxcXFxcXFsuKj9cXFxcXFxdKSpcXH0vZ2ksICQucHJveHkoZnVuY3Rpb24gKHN0ciwgcywgdnJneCkge1xuICAgICAgICAgICAgICBhLnB1c2gocyk7XG4gICAgICAgICAgICAgIGlmICh2cmd4KSB7XG4gICAgICAgICAgICAgICAgLy9oYXMgdmFsaWRhdGlvbiByZWdleHBcbiAgICAgICAgICAgICAgICB2cmd4ID0gdnJneC5yZXBsYWNlKC9cXFxcL2csIFwiXCIpO1xuICAgICAgICAgICAgICAgIHJldHVybiBcIihcIiArIHZyZ3ggKyBcIio/KVwiO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIHJldHVybiBcIihbXFxcXHNcXFxcU10qPylcIjtcbiAgICAgICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgICAgIHZhciBuID0gMCwgYW07XG4gICAgICAgICAgICB3aGlsZSAoKGFtID0gKG5ldyBSZWdFeHAoYmIsIFwibWdpXCIpKS5leGVjKGJiZGF0YSkpICE9IG51bGwpIHtcbiAgICAgICAgICAgICAgaWYgKGFtKSB7XG4gICAgICAgICAgICAgICAgdmFyIHIgPSB7fTtcbiAgICAgICAgICAgICAgICAkLmVhY2goYSwgJC5wcm94eShmdW5jdGlvbiAoaSwgaykge1xuICAgICAgICAgICAgICAgICAgcltrXSA9IGFtW2kgKyAxXTtcbiAgICAgICAgICAgICAgICB9LCB0aGlzKSk7XG4gICAgICAgICAgICAgICAgdmFyIG5odG1sID0gaHRtbDtcbiAgICAgICAgICAgICAgICBuaHRtbCA9IG5odG1sLnJlcGxhY2UoL1xceyguKj8pKFxcWy4qP1xcXSlcXH0vZywgXCJ7JDF9XCIpO1xuICAgICAgICAgICAgICAgIG5odG1sID0gdGhpcy5zdHJmKG5odG1sLCByKTtcbiAgICAgICAgICAgICAgICBiYmRhdGEgPSBiYmRhdGEucmVwbGFjZShhbVswXSwgbmh0bWwpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICB9XG4gICAgICB9LCB0aGlzKSk7XG5cbiAgICAgIC8vdHJhbnNmb3JtIHN5c3RlbSBjb2Rlc1xuICAgICAgJC5lYWNoKHRoaXMub3B0aW9ucy5zeXN0ciwgZnVuY3Rpb24gKGh0bWwsIGJiKSB7XG4gICAgICAgIGJiID0gYmIucmVwbGFjZSgvKFxcKHxcXCl8XFxbfFxcXXxcXC58XFwqfFxcP3xcXDp8XFxcXHxcXFxcKS9nLCBcIlxcXFwkMVwiKVxuICAgICAgICAgIC5yZXBsYWNlKFwiIFwiLCBcIlxcXFxzXCIpO1xuICAgICAgICBiYmRhdGEgPSBiYmRhdGEucmVwbGFjZShuZXcgUmVnRXhwKGJiLCBcImdcIiksIGh0bWwpO1xuICAgICAgfSk7XG5cblxuICAgICAgdmFyICR3cmFwID0gJCh0aGlzLmVsRnJvbVN0cmluZyhcIjxkaXY+XCIgKyBiYmRhdGEgKyBcIjwvZGl2PlwiKSk7XG4gICAgICAvL3RyYW5zZm9ybSBzbWlsZXNcbiAgICAgIC8qICR3cmFwLmNvbnRlbnRzKCkuZmlsdGVyKGZ1bmN0aW9uKCkge3JldHVybiB0aGlzLm5vZGVUeXBlPT0zfSkuZWFjaCgkLnByb3h5KHNtaWxlcnBsLHRoaXMpKS5lbmQoKS5maW5kKFwiKlwiKS5jb250ZW50cygpLmZpbHRlcihmdW5jdGlvbigpIHtyZXR1cm4gdGhpcy5ub2RlVHlwZT09M30pLmVhY2goJC5wcm94eShzbWlsZXJwbCx0aGlzKSk7XG5cblx0XHRcdGZ1bmN0aW9uIHNtaWxlcnBsKGksZWwpIHtcblx0XHRcdFx0dmFyIG5kYXRhID0gZWwuZGF0YTtcblx0XHRcdFx0JC5lYWNoKHRoaXMub3B0aW9ucy5zbWlsZUxpc3QsJC5wcm94eShmdW5jdGlvbihpLHJvdykge1xuXHRcdFx0XHRcdHZhciBmaWR4ID0gbmRhdGEuaW5kZXhPZihyb3cuYmJjb2RlKTtcblx0XHRcdFx0XHRpZiAoZmlkeCE9LTEpIHtcblx0XHRcdFx0XHRcdHZhciBhZnRlcm5vZGVfdHh0ID0gbmRhdGEuc3Vic3RyaW5nKGZpZHgrcm93LmJiY29kZS5sZW5ndGgsbmRhdGEubGVuZ3RoKTtcblx0XHRcdFx0XHRcdHZhciBhZnRlcm5vZGUgPSBkb2N1bWVudC5jcmVhdGVUZXh0Tm9kZShhZnRlcm5vZGVfdHh0KTtcblx0XHRcdFx0XHRcdGVsLmRhdGEgPSBuZGF0YSA9IGVsLmRhdGEuc3Vic3RyKDAsZmlkeCk7XG5cdFx0XHRcdFx0XHQkKGVsKS5hZnRlcihhZnRlcm5vZGUpLmFmdGVyKHRoaXMuc3RyZihyb3cuaW1nLHRoaXMub3B0aW9ucykpO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSx0aGlzKSk7XG5cdFx0XHR9ICovXG4gICAgICB0aGlzLmdldEhUTUxTbWlsZXMoJHdyYXApO1xuICAgICAgLy8kd3JhcC5jb250ZW50cygpLmZpbHRlcihmdW5jdGlvbigpIHtyZXR1cm4gdGhpcy5ub2RlVHlwZT09M30pLmVhY2goJC5wcm94eSh0aGlzLHNtaWxlUlBMLHRoaXMpKTtcblxuICAgICAgcmV0dXJuICR3cmFwLmh0bWwoKTtcbiAgICB9LFxuICAgIGdldEhUTUxTbWlsZXM6IGZ1bmN0aW9uIChyZWwpIHtcbiAgICAgICQocmVsKS5jb250ZW50cygpLmZpbHRlcihmdW5jdGlvbiAoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLm5vZGVUeXBlID09IDNcbiAgICAgIH0pLmVhY2goJC5wcm94eSh0aGlzLnNtaWxlUlBMLCB0aGlzKSk7XG4gICAgfSxcbiAgICBzbWlsZVJQTDogZnVuY3Rpb24gKGksIGVsKSB7XG4gICAgICB2YXIgbmRhdGEgPSBlbC5kYXRhO1xuICAgICAgJC5lYWNoKHRoaXMub3B0aW9ucy5zbWlsZUxpc3QsICQucHJveHkoZnVuY3Rpb24gKGksIHJvdykge1xuICAgICAgICB2YXIgZmlkeCA9IG5kYXRhLmluZGV4T2Yocm93LmJiY29kZSk7XG4gICAgICAgIGlmIChmaWR4ICE9IC0xKSB7XG4gICAgICAgICAgdmFyIGFmdGVybm9kZV90eHQgPSBuZGF0YS5zdWJzdHJpbmcoZmlkeCArIHJvdy5iYmNvZGUubGVuZ3RoLCBuZGF0YS5sZW5ndGgpO1xuICAgICAgICAgIHZhciBhZnRlcm5vZGUgPSBkb2N1bWVudC5jcmVhdGVUZXh0Tm9kZShhZnRlcm5vZGVfdHh0KTtcbiAgICAgICAgICBlbC5kYXRhID0gbmRhdGEgPSBlbC5kYXRhLnN1YnN0cigwLCBmaWR4KTtcbiAgICAgICAgICAkKGVsKS5hZnRlcihhZnRlcm5vZGUpLmFmdGVyKHRoaXMuc3RyZihyb3cuaW1nLCB0aGlzLm9wdGlvbnMpKTtcbiAgICAgICAgICB0aGlzLmdldEhUTUxTbWlsZXMoZWwucGFyZW50Tm9kZSk7XG4gICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMuZ2V0SFRNTFNtaWxlcyhlbCk7XG4gICAgICB9LCB0aGlzKSk7XG4gICAgfSxcbiAgICAvL1VUSUxTXG4gICAgc2V0VUlEOiBmdW5jdGlvbiAoZWwsIGF0dHIpIHtcbiAgICAgIHZhciBpZCA9IFwid2JiaWRfXCIgKyAoKyt0aGlzLmxhc3RpZCk7XG4gICAgICBpZiAoZWwpIHtcbiAgICAgICAgJChlbCkuYXR0cihhdHRyIHx8IFwiaWRcIiwgaWQpO1xuICAgICAgfVxuICAgICAgcmV0dXJuIGlkO1xuICAgIH0sXG4gICAga2V5c1RvTG93ZXI6IGZ1bmN0aW9uIChvKSB7XG4gICAgICAkLmVhY2gobywgZnVuY3Rpb24gKGssIHYpIHtcbiAgICAgICAgaWYgKGsgIT0gay50b0xvd2VyQ2FzZSgpKSB7XG4gICAgICAgICAgZGVsZXRlIG9ba107XG4gICAgICAgICAgb1trLnRvTG93ZXJDYXNlKCldID0gdjtcbiAgICAgICAgfVxuICAgICAgfSk7XG4gICAgICByZXR1cm4gbztcbiAgICB9LFxuICAgIHN0cmY6IGZ1bmN0aW9uIChzdHIsIGRhdGEpIHtcbiAgICAgIGRhdGEgPSB0aGlzLmtleXNUb0xvd2VyKCQuZXh0ZW5kKHt9LCBkYXRhKSk7XG4gICAgICByZXR1cm4gc3RyLnJlcGxhY2UoL1xceyhbXFx3XFwuXSopXFx9L2csIGZ1bmN0aW9uIChzdHIsIGtleSkge1xuICAgICAgICBrZXkgPSBrZXkudG9Mb3dlckNhc2UoKTtcbiAgICAgICAgdmFyIGtleXMgPSBrZXkuc3BsaXQoXCIuXCIpLCB2YWx1ZSA9IGRhdGFba2V5cy5zaGlmdCgpLnRvTG93ZXJDYXNlKCldO1xuICAgICAgICAkLmVhY2goa2V5cywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgIHZhbHVlID0gdmFsdWVbdGhpc107XG4gICAgICAgIH0pO1xuICAgICAgICByZXR1cm4gKHZhbHVlID09PSBudWxsIHx8IHZhbHVlID09PSB1bmRlZmluZWQpID8gXCJcIiA6IHZhbHVlO1xuICAgICAgfSk7XG4gICAgfSxcbiAgICBlbEZyb21TdHJpbmc6IGZ1bmN0aW9uIChzdHIpIHtcbiAgICAgIGlmIChzdHIuaW5kZXhPZihcIjxcIikgIT0gLTEgJiYgc3RyLmluZGV4T2YoXCI+XCIpICE9IC0xKSB7XG4gICAgICAgIC8vY3JlYXRlIHRhZ1xuICAgICAgICB2YXIgd3IgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KFwiU1BBTlwiKTtcbiAgICAgICAgJCh3cikuaHRtbChzdHIpO1xuICAgICAgICB0aGlzLnNldFVJRCh3ciwgXCJ3YmJcIik7XG4gICAgICAgIHJldHVybiAoJCh3cikuY29udGVudHMoKS5sZW5ndGggPiAxKSA/IHdyIDogd3IuZmlyc3RDaGlsZDtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIC8vY3JlYXRlIHRleHQgbm9kZVxuICAgICAgICByZXR1cm4gZG9jdW1lbnQuY3JlYXRlVGV4dE5vZGUoc3RyKTtcbiAgICAgIH1cbiAgICB9LFxuICAgIGlzQ29udGFpbjogZnVuY3Rpb24gKG5vZGUsIHNlbCkge1xuICAgICAgd2hpbGUgKG5vZGUgJiYgISQobm9kZSkuaGFzQ2xhc3MoXCJ3eXNpYmJcIikpIHtcbiAgICAgICAgaWYgKCQobm9kZSkuaXMoc2VsKSkge1xuICAgICAgICAgIHJldHVybiBub2RlXG4gICAgICAgIH1cbiAgICAgICAgO1xuICAgICAgICBpZiAobm9kZSkge1xuICAgICAgICAgIG5vZGUgPSBub2RlLnBhcmVudE5vZGU7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgcmV0dXJuIG51bGw7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9LFxuICAgIGlzQkJDb250YWluOiBmdW5jdGlvbiAoYmJjb2RlKSB7XG4gICAgICB2YXIgcG9zID0gdGhpcy5nZXRDdXJzb3JQb3NCQigpO1xuICAgICAgdmFyIGIgPSB0aGlzLnByZXBhcmVSR1goYmJjb2RlKTtcbiAgICAgIHZhciBiYnJneCA9IG5ldyBSZWdFeHAoYiwgXCJnXCIpO1xuICAgICAgdmFyIGE7XG4gICAgICB2YXIgbGFzdGluZGV4ID0gMDtcbiAgICAgIHdoaWxlICgoYSA9IGJicmd4LmV4ZWModGhpcy50eHRBcmVhLnZhbHVlKSkgIT0gbnVsbCkge1xuICAgICAgICB2YXIgcCA9IHRoaXMudHh0QXJlYS52YWx1ZS5pbmRleE9mKGFbMF0sIGxhc3RpbmRleCk7XG4gICAgICAgIGlmIChwb3MgPiBwICYmIHBvcyA8IChwICsgYVswXS5sZW5ndGgpKSB7XG4gICAgICAgICAgcmV0dXJuIFthLCBwXTtcbiAgICAgICAgfVxuICAgICAgICBsYXN0aW5kZXggPSBwICsgMTtcbiAgICAgIH1cbiAgICB9LFxuICAgIHByZXBhcmVSR1g6IGZ1bmN0aW9uIChyKSB7XG4gICAgICByZXR1cm4gci5yZXBsYWNlKC8oXFxbfFxcXXxcXCl8XFwofFxcLnxcXCp8XFw/fFxcOnxcXHx8XFxcXCkvZywgXCJcXFxcJDFcIikucmVwbGFjZSgvXFx7Lio/XFx9L2csIFwiKFtcXFxcc1xcXFxTXSo/KVwiKTtcbiAgICAgIC8vcmV0dXJuIHIucmVwbGFjZSgvKFteYS16MC05KS9pZyxcIlxcXFwkMVwiKS5yZXBsYWNlKC9cXHsuKj9cXH0vZyxcIihbXFxcXHNcXFxcU10qPylcIik7XG4gICAgfSxcbiAgICBjaGVja0Zvckxhc3RCUjogZnVuY3Rpb24gKG5vZGUpIHtcbiAgICAgIGlmICghbm9kZSkge1xuICAgICAgICAkbm9kZSA9IHRoaXMuYm9keTtcbiAgICAgIH1cbiAgICAgIGlmIChub2RlLm5vZGVUeXBlID09IDMpIHtcbiAgICAgICAgbm9kZSA9IG5vZGUucGFyZW50Tm9kZTtcbiAgICAgIH1cbiAgICAgIHZhciAkbm9kZSA9ICQobm9kZSk7XG4gICAgICBpZiAoJG5vZGUuaXMoXCJzcGFuW2lkKj0nd2JiaWQnXVwiKSkge1xuICAgICAgICAkbm9kZSA9ICRub2RlLnBhcmVudCgpO1xuICAgICAgfVxuICAgICAgaWYgKHRoaXMub3B0aW9ucy5iYm1vZGUgPT09IGZhbHNlICYmICRub2RlLmlzKCdkaXYsYmxvY2txdW90ZSxjb2RlJykgJiYgJG5vZGUuY29udGVudHMoKS5sZW5ndGggPiAwKSB7XG4gICAgICAgIHZhciBsID0gJG5vZGVbMF0ubGFzdENoaWxkO1xuICAgICAgICBpZiAoIWwgfHwgKGwgJiYgbC50YWdOYW1lICE9IFwiQlJcIikpIHtcbiAgICAgICAgICAkbm9kZS5hcHBlbmQoXCI8YnIvPlwiKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgICAgaWYgKHRoaXMuJGJvZHkuY29udGVudHMoKS5sZW5ndGggPiAwICYmIHRoaXMuYm9keS5sYXN0Q2hpbGQudGFnTmFtZSAhPSBcIkJSXCIpIHtcbiAgICAgICAgdGhpcy4kYm9keS5hcHBlbmQoJzxici8+Jyk7XG4gICAgICB9XG4gICAgfSxcbiAgICBnZXRBdHRyaWJ1dGVMaXN0OiBmdW5jdGlvbiAoZWwpIHtcbiAgICAgIHZhciBhID0gW107XG4gICAgICAkLmVhY2goZWwuYXR0cmlidXRlcywgZnVuY3Rpb24gKGksIGF0dHIpIHtcbiAgICAgICAgaWYgKGF0dHIuc3BlY2lmaWVkKSB7XG4gICAgICAgICAgYS5wdXNoKGF0dHIubmFtZSk7XG4gICAgICAgIH1cbiAgICAgIH0pO1xuICAgICAgcmV0dXJuIGE7XG4gICAgfSxcbiAgICBjbGVhckZyb21TdWJJbnNlcnQ6IGZ1bmN0aW9uIChodG1sLCBjbWQpIHtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjbWRdICYmIHRoaXMub3B0aW9ucy5hbGxCdXR0b25zW2NtZF0ucm9vdFNlbGVjdG9yKSB7XG4gICAgICAgIHZhciAkd3IgPSAkKCc8ZGl2PicpLmh0bWwoaHRtbCk7XG4gICAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMuYWxsQnV0dG9uc1tjbWRdLnJvb3RTZWxlY3RvciwgJC5wcm94eShmdW5jdGlvbiAoaSwgcykge1xuICAgICAgICAgIHZhciBzZWx0ZXh0ID0gZmFsc2U7XG4gICAgICAgICAgaWYgKHR5cGVvZiAodGhpcy5vcHRpb25zLnJ1bGVzW3NdWzBdWzFdW1wic2VsdGV4dFwiXSkgIT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICAgICAgc2VsdGV4dCA9IHRoaXMub3B0aW9ucy5ydWxlc1tzXVswXVsxXVtcInNlbHRleHRcIl1bXCJzZWxcIl07XG4gICAgICAgICAgfVxuICAgICAgICAgIHZhciByZXMgPSB0cnVlO1xuICAgICAgICAgICR3ci5maW5kKFwiKlwiKS5lYWNoKGZ1bmN0aW9uICgpIHsgLy93b3JrIHdpdGggZmluZChcIipcIikgYW5kIFwiaXNcIiwgYmVjb3VzZSBpbiBpZTctOCBmaW5kIGlzIGNhc2Ugc2Vuc2l0aXZlXG4gICAgICAgICAgICBpZiAoJCh0aGlzKS5pcyhzKSkge1xuICAgICAgICAgICAgICBpZiAoc2VsdGV4dCAmJiBzZWx0ZXh0W1wic2VsXCJdKSB7XG4gICAgICAgICAgICAgICAgJCh0aGlzKS5yZXBsYWNlV2l0aCgkKHRoaXMpLmZpbmQoc2VsdGV4dFtcInNlbFwiXS50b0xvd2VyQ2FzZSgpKS5odG1sKCkpO1xuICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICQodGhpcykucmVwbGFjZVdpdGgoJCh0aGlzKS5odG1sKCkpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgIHJlcyA9IGZhbHNlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH0pO1xuICAgICAgICAgIHJldHVybiByZXM7XG4gICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgcmV0dXJuICR3ci5odG1sKCk7XG4gICAgICB9XG4gICAgICByZXR1cm4gaHRtbDtcbiAgICB9LFxuICAgIHNwbGl0UHJldk5leHQ6IGZ1bmN0aW9uIChub2RlKSB7XG4gICAgICBpZiAobm9kZS5ub2RlVHlwZSA9PSAzKSB7XG4gICAgICAgIG5vZGUgPSBub2RlLnBhcmVudE5vZGVcbiAgICAgIH1cbiAgICAgIDtcbiAgICAgIHZhciBmID0gdGhpcy5maWx0ZXJCeU5vZGUobm9kZSkucmVwbGFjZSgvXFw6ZXEuKiQvZywgXCJcIik7XG4gICAgICBpZiAoJChub2RlLm5leHRTaWJsaW5nKS5pcyhmKSkge1xuICAgICAgICAkKG5vZGUpLmFwcGVuZCgkKG5vZGUubmV4dFNpYmxpbmcpLmh0bWwoKSk7XG4gICAgICAgICQobm9kZS5uZXh0U2libGluZykucmVtb3ZlKCk7XG4gICAgICB9XG4gICAgICBpZiAoJChub2RlLnByZXZpb3VzU2libGluZykuaXMoZikpIHtcbiAgICAgICAgJChub2RlKS5wcmVwZW5kKCQobm9kZS5wcmV2aW91c1NpYmxpbmcpLmh0bWwoKSk7XG4gICAgICAgICQobm9kZS5wcmV2aW91c1NpYmxpbmcpLnJlbW92ZSgpO1xuICAgICAgfVxuICAgIH0sXG4gICAgbW9kZVN3aXRjaDogZnVuY3Rpb24gKCkge1xuICAgICAgaWYgKHRoaXMub3B0aW9ucy5iYm1vZGUpIHtcbiAgICAgICAgLy90byBIVE1MXG4gICAgICAgIHRoaXMuJGJvZHkuaHRtbCh0aGlzLmdldEhUTUwodGhpcy4kdHh0QXJlYS52YWwoKSkpO1xuICAgICAgICB0aGlzLiR0eHRBcmVhLmhpZGUoKS5yZW1vdmVBdHRyKFwid2Jic3luY1wiKS52YWwoXCJcIik7XG4gICAgICAgIHRoaXMuJGJvZHkuY3NzKFwibWluLWhlaWdodFwiLCB0aGlzLiR0eHRBcmVhLmhlaWdodCgpKS5zaG93KCkuZm9jdXMoKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIC8vdG8gYmJjb2RlXG4gICAgICAgIHRoaXMuJHR4dEFyZWEudmFsKHRoaXMuZ2V0QkJDb2RlKCkpLmNzcyhcIm1pbi1oZWlnaHRcIiwgdGhpcy4kYm9keS5oZWlnaHQoKSk7XG4gICAgICAgIHRoaXMuJGJvZHkuaGlkZSgpO1xuICAgICAgICB0aGlzLiR0eHRBcmVhLnNob3coKS5mb2N1cygpO1xuICAgICAgfVxuICAgICAgdGhpcy5vcHRpb25zLmJibW9kZSA9ICF0aGlzLm9wdGlvbnMuYmJtb2RlO1xuICAgIH0sXG4gICAgY2xlYXJFbXB0eTogZnVuY3Rpb24gKCkge1xuICAgICAgdGhpcy4kYm9keS5jaGlsZHJlbigpLmZpbHRlcihlbXB0eUZpbHRlcikucmVtb3ZlKCk7XG5cbiAgICAgIGZ1bmN0aW9uIGVtcHR5RmlsdGVyKClcbiAgICAgIHtcbiAgICAgICAgaWYgKCEkKHRoaXMpLmlzKFwic3Bhbixmb250LGEsYixpLHUsc1wiKSkge1xuICAgICAgICAgIC8vY2xlYXIgZW1wdHkgb25seSBmb3Igc3Bhbixmb250XG4gICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgICAgIGlmICghJCh0aGlzKS5oYXNDbGFzcyhcIndiYnRhYlwiKSAmJiAkLnRyaW0oJCh0aGlzKS5odG1sKCkpLmxlbmd0aCA9PSAwKSB7XG4gICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH0gZWxzZSBpZiAoJCh0aGlzKS5jaGlsZHJlbigpLmxlbmd0aCA+IDApIHtcbiAgICAgICAgICAkKHRoaXMpLmNoaWxkcmVuKCkuZmlsdGVyKGVtcHR5RmlsdGVyKS5yZW1vdmUoKTtcbiAgICAgICAgICBpZiAoJCh0aGlzKS5odG1sKCkubGVuZ3RoID09IDAgJiYgdGhpcy50YWdOYW1lICE9IFwiQk9EWVwiKSB7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9LFxuICAgIGRyb3Bkb3duY2xpY2s6IGZ1bmN0aW9uIChic2VsLCB0c2VsLCBlKSB7XG4gICAgICAvL3RoaXMuYm9keS5mb2N1cygpO1xuICAgICAgdmFyICRidG4gPSAkKGUuY3VycmVudFRhcmdldCkuY2xvc2VzdChic2VsKTtcbiAgICAgIGlmICgkYnRuLmhhc0NsYXNzKFwiZGlzXCIpKSB7XG4gICAgICAgIHJldHVybjtcbiAgICAgIH1cbiAgICAgIGlmICgkYnRuLmF0dHIoXCJ3YmJzaG93XCIpKSB7XG4gICAgICAgIC8vaGlkZSBkcm9wZG93blxuICAgICAgICAkYnRuLnJlbW92ZUF0dHIoXCJ3YmJzaG93XCIpO1xuICAgICAgICAkKGRvY3VtZW50KS51bmJpbmQoXCJtb3VzZWRvd25cIiwgdGhpcy5kcm9wZG93bmhhbmRsZXIpO1xuICAgICAgICBpZiAoZG9jdW1lbnQpIHtcbiAgICAgICAgICAkKGRvY3VtZW50KS51bmJpbmQoXCJtb3VzZWRvd25cIiwgdGhpcy5kcm9wZG93bmhhbmRsZXIpO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMubGFzdFJhbmdlID0gZmFsc2U7XG5cbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHRoaXMuc2F2ZVJhbmdlKCk7XG4gICAgICAgIHRoaXMuJGVkaXRvci5maW5kKFwiKlt3YmJzaG93XVwiKS5lYWNoKGZ1bmN0aW9uIChpLCBlbCkge1xuICAgICAgICAgICQoZWwpLnJlbW92ZUNsYXNzKFwib25cIikuZmluZCgkKGVsKS5hdHRyKFwid2Jic2hvd1wiKSkuaGlkZSgpLmVuZCgpLnJlbW92ZUF0dHIoXCJ3YmJzaG93XCIpO1xuICAgICAgICB9KVxuICAgICAgICAkYnRuLmF0dHIoXCJ3YmJzaG93XCIsIHRzZWwpO1xuICAgICAgICAkKGRvY3VtZW50LmJvZHkpLmJpbmQoXCJtb3VzZWRvd25cIiwgJC5wcm94eShmdW5jdGlvbiAoZXZ0KSB7XG4gICAgICAgICAgdGhpcy5kcm9wZG93bmhhbmRsZXIoJGJ0biwgYnNlbCwgdHNlbCwgZXZ0KVxuICAgICAgICB9LCB0aGlzKSk7XG4gICAgICAgIGlmICh0aGlzLiRib2R5KSB7XG4gICAgICAgICAgdGhpcy4kYm9keS5iaW5kKFwibW91c2Vkb3duXCIsICQucHJveHkoZnVuY3Rpb24gKGV2dCkge1xuICAgICAgICAgICAgdGhpcy5kcm9wZG93bmhhbmRsZXIoJGJ0biwgYnNlbCwgdHNlbCwgZXZ0KVxuICAgICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgfVxuICAgICAgfVxuICAgICAgJGJ0bi5maW5kKHRzZWwpLnRvZ2dsZSgpO1xuICAgICAgJGJ0bi50b2dnbGVDbGFzcyhcIm9uXCIpO1xuICAgIH0sXG4gICAgZHJvcGRvd25oYW5kbGVyOiBmdW5jdGlvbiAoJGJ0biwgYnNlbCwgdHNlbCwgZSkge1xuICAgICAgaWYgKCQoZS50YXJnZXQpLnBhcmVudHMoYnNlbCkubGVuZ3RoID09IDApIHtcbiAgICAgICAgJGJ0bi5yZW1vdmVDbGFzcyhcIm9uXCIpLmZpbmQodHNlbCkuaGlkZSgpO1xuICAgICAgICAkKGRvY3VtZW50KS51bmJpbmQoJ21vdXNlZG93bicsIHRoaXMuZHJvcGRvd25oYW5kbGVyKTtcbiAgICAgICAgaWYgKHRoaXMuJGJvZHkpIHtcbiAgICAgICAgICB0aGlzLiRib2R5LnVuYmluZCgnbW91c2Vkb3duJywgdGhpcy5kcm9wZG93bmhhbmRsZXIpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcbiAgICByZ2JUb0hleDogZnVuY3Rpb24gKHJnYikge1xuICAgICAgaWYgKHJnYi5zdWJzdHIoMCwgMSkgPT0gJyMnKSB7XG4gICAgICAgIHJldHVybiByZ2I7XG4gICAgICB9XG4gICAgICAvL2lmIChyZ2IuaW5kZXhPZihcInJnYlwiKT09LTEpIHtyZXR1cm4gcmdiO31cbiAgICAgIGlmIChyZ2IuaW5kZXhPZihcInJnYlwiKSA9PSAtMSkge1xuICAgICAgICAvL0lFXG4gICAgICAgIHZhciBjb2xvciA9IHBhcnNlSW50KHJnYik7XG4gICAgICAgIGNvbG9yID0gKChjb2xvciAmIDB4MDAwMGZmKSA8PCAxNikgfCAoY29sb3IgJiAweDAwZmYwMCkgfCAoKGNvbG9yICYgMHhmZjAwMDApID4+PiAxNik7XG4gICAgICAgIHJldHVybiAnIycgKyBjb2xvci50b1N0cmluZygxNik7XG4gICAgICB9XG4gICAgICB2YXIgZGlnaXRzID0gLyguKj8pcmdiXFwoKFxcZCspLFxccyooXFxkKyksXFxzKihcXGQrKVxcKS8uZXhlYyhyZ2IpO1xuICAgICAgcmV0dXJuIFwiI1wiICsgdGhpcy5kZWMyaGV4KHBhcnNlSW50KGRpZ2l0c1syXSkpICsgdGhpcy5kZWMyaGV4KHBhcnNlSW50KGRpZ2l0c1szXSkpICsgdGhpcy5kZWMyaGV4KHBhcnNlSW50KGRpZ2l0c1s0XSkpO1xuICAgIH0sXG4gICAgZGVjMmhleDogZnVuY3Rpb24gKGQpIHtcbiAgICAgIGlmIChkID4gMTUpIHtcbiAgICAgICAgcmV0dXJuIGQudG9TdHJpbmcoMTYpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgcmV0dXJuIFwiMFwiICsgZC50b1N0cmluZygxNik7XG4gICAgICB9XG4gICAgfSxcbiAgICBzeW5jOiBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmJibW9kZSkge1xuICAgICAgICB0aGlzLiRib2R5Lmh0bWwodGhpcy5nZXRIVE1MKHRoaXMudHh0QXJlYS52YWx1ZSwgdHJ1ZSkpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgdGhpcy4kdHh0QXJlYS5hdHRyKFwid2Jic3luY1wiLCAxKS52YWwodGhpcy5nZXRCQkNvZGUoKSk7XG4gICAgICB9XG4gICAgfSxcbiAgICBjbGVhclBhc3RlOiBmdW5jdGlvbiAoZWwpIHtcbiAgICAgIHZhciAkYmxvY2sgPSAkKGVsKTtcbiAgICAgIC8vTkVXXG4gICAgICAkLmVhY2godGhpcy5vcHRpb25zLnJ1bGVzLCAkLnByb3h5KGZ1bmN0aW9uIChzLCBhcikge1xuICAgICAgICB2YXIgJHNmID0gJGJsb2NrLmZpbmQocykuYXR0cihcIndiYmtlZXBcIiwgMSk7XG4gICAgICAgIGlmICgkc2YubGVuZ3RoID4gMCkge1xuICAgICAgICAgIHZhciBzMiA9IGFyWzBdWzFdO1xuICAgICAgICAgICQuZWFjaChzMiwgZnVuY3Rpb24gKGksIHYpIHtcbiAgICAgICAgICAgIGlmICh2LnNlbCkge1xuICAgICAgICAgICAgICAkc2YuZmluZCh2LnNlbCkuYXR0cihcIndiYmtlZXBcIiwgMSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICAgIH0sIHRoaXMpKTtcbiAgICAgICRibG9jay5maW5kKFwiKlt3YmJrZWVwIT0nMSddXCIpLmVhY2goJC5wcm94eShmdW5jdGlvbiAoaSwgZWwpIHtcbiAgICAgICAgdmFyICR0aGlzID0gJChlbCk7XG4gICAgICAgIGlmICgkdGhpcy5pcygnZGl2LHAnKSAmJiAoJHRoaXMuY2hpbGRyZW4oKS5sZW5ndGggPT0gMCB8fCBlbC5sYXN0Q2hpbGQudGFnTmFtZSAhPSBcIkJSXCIpKSB7XG4gICAgICAgICAgJHRoaXMuYWZ0ZXIoXCI8YnIvPlwiKTtcbiAgICAgICAgfVxuICAgICAgfSwgdGhpcykpO1xuICAgICAgJGJsb2NrLmZpbmQoXCIqW3diYmtlZXBdXCIpLnJlbW92ZUF0dHIoXCJ3YmJrZWVwXCIpLnJlbW92ZUF0dHIoXCJzdHlsZVwiKTtcbiAgICAgICQubG9nKCRibG9jay5odG1sKCkpO1xuICAgICAgLy8kLmxvZyhcIkJCQ09ERTogXCIrdGhpcy50b0JCKCRibG9jay5jbG9uZSh0cnVlKSkpO1xuICAgICAgJGJsb2NrLmh0bWwodGhpcy5nZXRIVE1MKHRoaXMudG9CQigkYmxvY2spLCB0cnVlKSk7XG4gICAgICAkLmxvZygkYmxvY2suaHRtbCgpKTtcblxuICAgICAgLy9PTERcbiAgICAgIC8qICQuZWFjaCh0aGlzLm9wdGlvbnMucnVsZXMsJC5wcm94eShmdW5jdGlvbihzLGJiKSB7XG5cdFx0XHRcdCRibG9jay5maW5kKHMpLmF0dHIoXCJ3YmJrZWVwXCIsMSk7XG5cdFx0XHR9LHRoaXMpKTtcblxuXHRcdFx0Ly9yZXBsYWNlIGRpdiBhbmQgcCB3aXRob3V0IGxhc3QgYnIgdG8gaHRtbCgpK2JyXG5cdFx0XHQkYmxvY2suZmluZChcIipbd2Jia2VlcCE9JzEnXVwiKS5lYWNoKCQucHJveHkoZnVuY3Rpb24oaSxlbCkge1xuXHRcdFx0XHR2YXIgJHRoaXMgPSAkKGVsKTtcblx0XHRcdFx0aWYgKCR0aGlzLmlzKCdkaXYscCcpICYmICgkdGhpcy5jaGlsZHJlbigpLmxlbmd0aD09MCB8fCBlbC5sYXN0Q2hpbGQudGFnTmFtZSE9XCJCUlwiKSkge1xuXHRcdFx0XHRcdCR0aGlzLmFmdGVyKFwiPGJyLz5cIikuYWZ0ZXIoJHRoaXMuY29udGVudHMoKSkucmVtb3ZlKCk7XG5cdFx0XHRcdH1lbHNle1xuXHRcdFx0XHRcdCR0aGlzLmFmdGVyKCR0aGlzLmNvbnRlbnRzKCkpLnJlbW92ZSgpO1xuXHRcdFx0XHR9XG5cdFx0XHR9LHRoaXMpKTtcblx0XHRcdCRibG9jay5maW5kKFwiKlt3YmJrZWVwXVwiKS5yZW1vdmVBdHRyKFwid2Jia2VlcFwiKS5yZW1vdmVBdHRyKFwic3R5bGVcIik7ICovXG4gICAgfSxcbiAgICBzb3J0QXJyYXk6IGZ1bmN0aW9uIChhciwgYXNjKSB7XG4gICAgICBhci5zb3J0KGZ1bmN0aW9uIChhLCBiKSB7XG4gICAgICAgIHJldHVybiAoYS5sZW5ndGggLSBiLmxlbmd0aCkgKiAoYXNjIHx8IDEpO1xuICAgICAgfSk7XG4gICAgICByZXR1cm4gYXI7XG4gICAgfSxcbiAgICBzbWlsZUZpbmQ6IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICh0aGlzLm9wdGlvbnMuc21pbGVmaW5kKSB7XG4gICAgICAgIHZhciAkc21saXN0ID0gJCh0aGlzLm9wdGlvbnMuc21pbGVmaW5kKS5maW5kKCdpbWdbYWx0XScpO1xuICAgICAgICBpZiAoJHNtbGlzdC5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgdGhpcy5vcHRpb25zLnNtaWxlTGlzdCA9IFtdO1xuICAgICAgICAgICRzbWxpc3QuZWFjaCgkLnByb3h5KGZ1bmN0aW9uIChpLCBlbCkge1xuICAgICAgICAgICAgdmFyICRlbCA9ICQoZWwpO1xuICAgICAgICAgICAgdGhpcy5vcHRpb25zLnNtaWxlTGlzdC5wdXNoKHt0aXRsZTogJGVsLmF0dHIoXCJ0aXRsZVwiKSwgYmJjb2RlOiAkZWwuYXR0cihcImFsdFwiKSwgaW1nOiAkZWwucmVtb3ZlQXR0cihcImFsdFwiKS5yZW1vdmVBdHRyKFwidGl0bGVcIilbMF0ub3V0ZXJIVE1MfSk7XG4gICAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcbiAgICBkZXN0cm95OiBmdW5jdGlvbiAoKSB7XG4gICAgICB0aGlzLiRlZGl0b3IucmVwbGFjZVdpdGgodGhpcy4kdHh0QXJlYSk7XG4gICAgICB0aGlzLiR0eHRBcmVhLnJlbW92ZUNsYXNzKFwid3lzaWJiLXRleGFyZWFcIikuc2hvdygpO1xuICAgICAgdGhpcy4kbW9kYWwucmVtb3ZlKCk7XG4gICAgICB0aGlzLiR0eHRBcmVhLmRhdGEoXCJ3YmJcIiwgbnVsbCk7XG4gICAgfSxcbiAgICBwcmVzc1RhYjogZnVuY3Rpb24gKGUpIHtcbiAgICAgIGlmIChlICYmIGUud2hpY2ggPT0gOSkge1xuICAgICAgICAvL2luc2VydCB0YWJcbiAgICAgICAgaWYgKGUucHJldmVudERlZmF1bHQpIHtcbiAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHRoaXMub3B0aW9ucy5iYm1vZGUpIHtcbiAgICAgICAgICB0aGlzLmluc2VydEF0Q3Vyc29yKCcgICAnLCBmYWxzZSk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgdGhpcy5pbnNlcnRBdEN1cnNvcignPHNwYW4gY2xhc3M9XCJ3YmJ0YWJcIj5cXHVGRUZGPC9zcGFuPicsIGZhbHNlKTtcbiAgICAgICAgICAvL3RoaXMuZXhlY05hdGl2ZUNvbW1hbmQoXCJpbmRlbnRcIixmYWxzZSk7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9LFxuICAgIHJlbW92ZUxhc3RCb2R5QlI6IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICh0aGlzLmJvZHkubGFzdENoaWxkICYmIHRoaXMuYm9keS5sYXN0Q2hpbGQubm9kZVR5cGUgIT0gMyAmJiB0aGlzLmJvZHkubGFzdENoaWxkLnRhZ05hbWUgPT0gXCJCUlwiKSB7XG4gICAgICAgIHRoaXMuYm9keS5yZW1vdmVDaGlsZCh0aGlzLmJvZHkubGFzdENoaWxkKTtcbiAgICAgICAgdGhpcy5yZW1vdmVMYXN0Qm9keUJSKCk7XG4gICAgICB9XG4gICAgfSxcbiAgICB0cmFjZVRleHRhcmVhRXZlbnQ6IGZ1bmN0aW9uIChlKSB7XG4gICAgICBpZiAoJChlLnRhcmdldCkuY2xvc2VzdChcImRpdi53eXNpYmJcIikubGVuZ3RoID09IDApIHtcbiAgICAgICAgaWYgKCQoZG9jdW1lbnQuYWN0aXZlRWxlbWVudCkuaXMoXCJkaXYud3lzaWJiLWJvZHlcIikpIHtcbiAgICAgICAgICB0aGlzLnNhdmVSYW5nZSgpO1xuICAgICAgICB9XG4gICAgICAgIHNldFRpbWVvdXQoJC5wcm94eShmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgdmFyIGRhdGEgPSB0aGlzLiR0eHRBcmVhLnZhbCgpO1xuICAgICAgICAgIGlmICh0aGlzLm9wdGlvbnMuYmJtb2RlID09PSBmYWxzZSAmJiBkYXRhICE9IFwiXCIgJiYgJChlLnRhcmdldCkuY2xvc2VzdChcImRpdi53eXNpYmJcIikubGVuZ3RoID09IDAgJiYgIXRoaXMuJHR4dEFyZWEuYXR0cihcIndiYnN5bmNcIikpIHtcbiAgICAgICAgICAgIHRoaXMuc2VsZWN0TGFzdFJhbmdlKCk7XG4gICAgICAgICAgICB0aGlzLmluc2VydEF0Q3Vyc29yKHRoaXMuZ2V0SFRNTChkYXRhLCB0cnVlKSk7XG4gICAgICAgICAgICB0aGlzLiR0eHRBcmVhLnZhbChcIlwiKTtcbiAgICAgICAgICB9XG4gICAgICAgICAgaWYgKCQoZG9jdW1lbnQuYWN0aXZlRWxlbWVudCkuaXMoXCJkaXYud3lzaWJiLWJvZHlcIikpIHtcbiAgICAgICAgICAgIHRoaXMubGFzdFJhbmdlID0gZmFsc2U7XG4gICAgICAgICAgfVxuICAgICAgICB9LCB0aGlzKSwgMTAwKTtcbiAgICAgIH1cbiAgICB9LFxuICAgIHR4dEFyZWFJbml0Q29udGVudDogZnVuY3Rpb24gKCkge1xuICAgICAgLy8kLmxvZyh0aGlzLnR4dEFyZWEudmFsdWUpO1xuICAgICAgdGhpcy4kYm9keS5odG1sKHRoaXMuZ2V0SFRNTCh0aGlzLnR4dEFyZWEudmFsdWUsIHRydWUpKTtcbiAgICB9LFxuICAgIGdldFZhbGlkYXRpb25SR1g6IGZ1bmN0aW9uIChzKSB7XG4gICAgICBpZiAocy5tYXRjaCgvXFxbXFxTK1xcXS8pKSB7XG4gICAgICAgIHJldHVybiBzLnJlcGxhY2UoLy4qKFxcXFwqXFxbXFxTK1xcXSkuKi8sIFwiJDFcIik7XG4gICAgICB9XG4gICAgICByZXR1cm4gXCJcIjtcbiAgICB9LFxuICAgIHNtaWxlQ29udmVyc2lvbjogZnVuY3Rpb24gKCkge1xuICAgICAgaWYgKHRoaXMub3B0aW9ucy5zbWlsZUxpc3QgJiYgdGhpcy5vcHRpb25zLnNtaWxlTGlzdC5sZW5ndGggPiAwKSB7XG4gICAgICAgIHZhciBzbm9kZSA9IHRoaXMuZ2V0U2VsZWN0Tm9kZSgpO1xuICAgICAgICBpZiAoc25vZGUubm9kZVR5cGUgPT0gMykge1xuICAgICAgICAgIHZhciBuZGF0YSA9IHNub2RlLmRhdGE7XG4gICAgICAgICAgaWYgKG5kYXRhLmxlbmd0aCA+PSAyICYmICF0aGlzLmlzSW5DbGVhclRleHRCbG9jayhzbm9kZSkgJiYgJChzbm9kZSkucGFyZW50cyhcImFcIikubGVuZ3RoID09IDApIHtcbiAgICAgICAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMuc3J1bGVzLCAkLnByb3h5KGZ1bmN0aW9uIChpLCBzYXIpIHtcbiAgICAgICAgICAgICAgdmFyIHNtYmIgPSBzYXJbMF07XG4gICAgICAgICAgICAgIHZhciBmaWR4ID0gbmRhdGEuaW5kZXhPZihzbWJiKTtcbiAgICAgICAgICAgICAgaWYgKGZpZHggIT0gLTEpIHtcbiAgICAgICAgICAgICAgICB2YXIgYWZ0ZXJub2RlX3R4dCA9IG5kYXRhLnN1YnN0cmluZyhmaWR4ICsgc21iYi5sZW5ndGgsIG5kYXRhLmxlbmd0aCk7XG4gICAgICAgICAgICAgICAgdmFyIGFmdGVybm9kZSA9IGRvY3VtZW50LmNyZWF0ZVRleHROb2RlKGFmdGVybm9kZV90eHQpO1xuICAgICAgICAgICAgICAgIHZhciBhZnRlcm5vZGVfY3Vyc29yID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudChcIlNQQU5cIik7XG4gICAgICAgICAgICAgICAgc25vZGUuZGF0YSA9IHNub2RlLmRhdGEuc3Vic3RyKDAsIGZpZHgpO1xuICAgICAgICAgICAgICAgICQoc25vZGUpLmFmdGVyKGFmdGVybm9kZSkuYWZ0ZXIoYWZ0ZXJub2RlX2N1cnNvcikuYWZ0ZXIodGhpcy5zdHJmKHNhclsxXSwgdGhpcy5vcHRpb25zKSk7XG4gICAgICAgICAgICAgICAgdGhpcy5zZWxlY3ROb2RlKGFmdGVybm9kZV9jdXJzb3IpO1xuICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSwgdGhpcykpO1xuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfVxuICAgIH0sXG4gICAgaXNJbkNsZWFyVGV4dEJsb2NrOiBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAodGhpcy5jbGVhcnRleHQpIHtcbiAgICAgICAgdmFyIGZpbmQgPSBmYWxzZTtcbiAgICAgICAgJC5lYWNoKHRoaXMuY2xlYXJ0ZXh0LCAkLnByb3h5KGZ1bmN0aW9uIChzZWwsIGNvbW1hbmQpIHtcbiAgICAgICAgICBpZiAodGhpcy5xdWVyeVN0YXRlKGNvbW1hbmQpKSB7XG4gICAgICAgICAgICBmaW5kID0gY29tbWFuZDtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICB9XG4gICAgICAgIH0sIHRoaXMpKVxuICAgICAgICByZXR1cm4gZmluZDtcbiAgICAgIH1cbiAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9LFxuICAgIHdyYXBBdHRyczogZnVuY3Rpb24gKGh0bWwpIHtcbiAgICAgICQuZWFjaCh0aGlzLm9wdGlvbnMuYXR0cldyYXAsIGZ1bmN0aW9uIChpLCBhKSB7XG4gICAgICAgIGh0bWwgPSBodG1sLnJlcGxhY2UoYSArICc9XCInLCAnXycgKyBhICsgJz1cIicpO1xuICAgICAgfSk7XG4gICAgICByZXR1cm4gaHRtbDtcbiAgICB9LFxuICAgIHVud3JhcEF0dHJzOiBmdW5jdGlvbiAoaHRtbCkge1xuICAgICAgJC5lYWNoKHRoaXMub3B0aW9ucy5hdHRyV3JhcCwgZnVuY3Rpb24gKGksIGEpIHtcbiAgICAgICAgaHRtbCA9IGh0bWwucmVwbGFjZSgnXycgKyBhICsgJz1cIicsIGEgKyAnPVwiJyk7XG4gICAgICB9KTtcbiAgICAgIHJldHVybiBodG1sO1xuICAgIH0sXG4gICAgZGlzTm9uQWN0aXZlQnV0dG9uczogZnVuY3Rpb24gKCkge1xuICAgICAgaWYgKHRoaXMuaXNJbkNsZWFyVGV4dEJsb2NrKCkpIHtcbiAgICAgICAgdGhpcy4kdG9vbGJhci5maW5kKFwiLnd5c2liYi10b29sYmFyLWJ0bjpub3QoLm9uLC5tc3dpdGNoKVwiKS5hZGRDbGFzcyhcImRpc1wiKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHRoaXMuJHRvb2xiYXIuZmluZChcIi53eXNpYmItdG9vbGJhci1idG4uZGlzXCIpLnJlbW92ZUNsYXNzKFwiZGlzXCIpO1xuICAgICAgfVxuICAgIH0sXG4gICAgc2V0Q3Vyc29yQnlFbDogZnVuY3Rpb24gKGVsKSB7XG4gICAgICB2YXIgc2wgPSBkb2N1bWVudC5jcmVhdGVUZXh0Tm9kZShcIlxcdUZFRkZcIik7XG4gICAgICAkKGVsKS5hZnRlcihzbCk7XG4gICAgICB0aGlzLnNlbGVjdE5vZGUoc2wpO1xuICAgIH0sXG5cbiAgICAvL2ltZyBsaXN0ZW5lcnNcbiAgICBpbWdMaXN0ZW5lcnM6IGZ1bmN0aW9uICgpIHtcbiAgICAgICQoZG9jdW1lbnQpLm9uKFwibW91c2Vkb3duXCIsICQucHJveHkodGhpcy5pbWdFdmVudEhhbmRsZXIsIHRoaXMpKTtcbiAgICB9LFxuICAgIGltZ0V2ZW50SGFuZGxlcjogZnVuY3Rpb24gKGUpIHtcbiAgICAgIHZhciAkZSA9ICQoZS50YXJnZXQpO1xuICAgICAgaWYgKHRoaXMuaGFzV3JhcGVkSW1hZ2UgJiYgKCRlLmNsb3Nlc3QoXCIud2JiLWltZywjd2JibW9kYWxcIikubGVuZ3RoID09IDAgfHwgJGUuaGFzQ2xhc3MoXCJ3YmItY2FuY2VsLWJ1dHRvblwiKSkpIHtcbiAgICAgICAgdGhpcy4kYm9keS5maW5kKFwiLmltZ1dyYXAgXCIpLmVhY2goZnVuY3Rpb24gKCkge1xuICAgICAgICAgICQubG9nKFwiUmVtb3ZlZCBpbWdXcmFwIGJsb2NrXCIpO1xuICAgICAgICAgICQodGhpcykucmVwbGFjZVdpdGgoJCh0aGlzKS5maW5kKFwiaW1nXCIpKTtcbiAgICAgICAgfSlcbiAgICAgICAgdGhpcy5oYXNXcmFwZWRJbWFnZSA9IGZhbHNlO1xuICAgICAgICB0aGlzLnVwZGF0ZVVJKCk7XG4gICAgICB9XG5cbiAgICAgIGlmICgkZS5pcyhcImltZ1wiKSAmJiAkZS5jbG9zZXN0KFwiLnd5c2liYi1ib2R5XCIpLmxlbmd0aCA+IDApIHtcbiAgICAgICAgJGUud3JhcChcIjxzcGFuIGNsYXNzPSdpbWdXcmFwJz48L3NwYW4+XCIpO1xuICAgICAgICB0aGlzLmhhc1dyYXBlZEltYWdlID0gJGU7XG4gICAgICAgIHRoaXMuJGJvZHkuZm9jdXMoKTtcbiAgICAgICAgdGhpcy5zZWxlY3ROb2RlKCRlLnBhcmVudCgpWzBdKTtcbiAgICAgIH1cbiAgICB9LFxuXG4gICAgLy9NT0RBTCBXSU5ET1dcbiAgICBzaG93TW9kYWw6IGZ1bmN0aW9uIChjbWQsIG9wdCwgcXVlcnlTdGF0ZSkge1xuICAgICAgJC5sb2coXCJzaG93TW9kYWw6IFwiICsgY21kKTtcbiAgICAgIHRoaXMuc2F2ZVJhbmdlKCk7XG4gICAgICB2YXIgJGNvbnQgPSB0aGlzLiRtb2RhbC5maW5kKFwiLndiYm0tY29udGVudFwiKS5odG1sKFwiXCIpO1xuICAgICAgdmFyICR3YmJtID0gdGhpcy4kbW9kYWwuZmluZChcIi53YmJtXCIpLnJlbW92ZUNsYXNzKFwiaGFzdGFic1wiKTtcbiAgICAgIHRoaXMuJG1vZGFsLmZpbmQoXCJzcGFuLndiYm0tdGl0bGUtdGV4dFwiKS5odG1sKG9wdC50aXRsZSk7XG4gICAgICBpZiAob3B0LnRhYnMgJiYgb3B0LnRhYnMubGVuZ3RoID4gMSkge1xuICAgICAgICAvL2hhcyB0YWJzLCBjcmVhdGVcbiAgICAgICAgJHdiYm0uYWRkQ2xhc3MoXCJoYXN0YWJzXCIpO1xuICAgICAgICB2YXIgJHVsID0gJCgnPGRpdiBjbGFzcz1cIndiYm0tdGFibGlzdFwiPicpLmFwcGVuZFRvKCRjb250KS5hcHBlbmQoXCI8dWw+XCIpLmNoaWxkcmVuKFwidWxcIik7XG4gICAgICAgICQuZWFjaChvcHQudGFicywgJC5wcm94eShmdW5jdGlvbiAoaSwgcm93KSB7XG4gICAgICAgICAgaWYgKGkgPT0gMCkge1xuICAgICAgICAgICAgcm93WydvbiddID0gXCJvblwiXG4gICAgICAgICAgfVxuICAgICAgICAgICR1bC5hcHBlbmQodGhpcy5zdHJmKCc8bGkgY2xhc3M9XCJ7b259XCIgb25DbGljaz1cIiQodGhpcykucGFyZW50KCkuZmluZChcXCcub25cXCcpLnJlbW92ZUNsYXNzKFxcJ29uXFwnKTskKHRoaXMpLmFkZENsYXNzKFxcJ29uXFwnKTskKHRoaXMpLnBhcmVudHMoXFwnLndiYm0tY29udGVudFxcJykuZmluZChcXCcudGFiLWNvbnRcXCcpLmhpZGUoKTskKHRoaXMpLnBhcmVudHMoXFwnLndiYm0tY29udGVudFxcJykuZmluZChcXCcudGFiJyArIGkgKyAnXFwnKS5zaG93KClcIj57dGl0bGV9PC9saT4nLCByb3cpKTtcblxuICAgICAgICB9LCB0aGlzKSlcbiAgICAgIH1cbiAgICAgIGlmIChvcHQud2lkdGgpIHtcbiAgICAgICAgJHdiYm0uY3NzKFwid2lkdGhcIiwgb3B0LndpZHRoKTtcbiAgICAgIH1cbiAgICAgIHZhciAkY250ID0gJCgnPGRpdiBjbGFzcz1cIndiYm0tY29udFwiPicpLmFwcGVuZFRvKCRjb250KTtcbiAgICAgIGlmIChxdWVyeVN0YXRlKSB7XG4gICAgICAgICR3YmJtLmZpbmQoJyN3YmJtLXJlbW92ZScpLnNob3coKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgICR3YmJtLmZpbmQoJyN3YmJtLXJlbW92ZScpLmhpZGUoKTtcbiAgICAgIH1cbiAgICAgICQuZWFjaChvcHQudGFicywgJC5wcm94eShmdW5jdGlvbiAoaSwgcikge1xuICAgICAgICB2YXIgJGMgPSAkKCc8ZGl2PicpLmFkZENsYXNzKFwidGFiLWNvbnQgdGFiXCIgKyBpKS5hdHRyKFwidGlkXCIsIGkpLmFwcGVuZFRvKCRjbnQpO1xuICAgICAgICBpZiAoaSA+IDApIHtcbiAgICAgICAgICAkYy5oaWRlKCk7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKHIuaHRtbCkge1xuICAgICAgICAgICRjLmh0bWwodGhpcy5zdHJmKHIuaHRtbCwgdGhpcy5vcHRpb25zKSk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgJC5lYWNoKHIuaW5wdXQsICQucHJveHkoZnVuY3Rpb24gKGosIGlucCkge1xuICAgICAgICAgICAgaW5wW1widmFsdWVcIl0gPSBxdWVyeVN0YXRlW2lucC5wYXJhbS50b0xvd2VyQ2FzZSgpXTtcbiAgICAgICAgICAgIGlmIChpbnAucGFyYW0udG9Mb3dlckNhc2UoKSA9PSBcInNlbHRleHRcIiAmJiAoIWlucFtcInZhbHVlXCJdIHx8IGlucFtcInZhbHVlXCJdID09IFwiXCIpKSB7XG4gICAgICAgICAgICAgIGlucFtcInZhbHVlXCJdID0gdGhpcy5nZXRTZWxlY3RUZXh0KHRoaXMub3B0aW9ucy5iYm1vZGUpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKGlucFtcInZhbHVlXCJdICYmIGlucFtcInZhbHVlXCJdLmluZGV4T2YoXCI8c3BhbiBpZD0nd2JiaWRcIikgPT0gMCAmJiAkKGlucFtcInZhbHVlXCJdKS5pcyhcInNwYW5baWQqPSd3YmJpZCddXCIpKSB7XG4gICAgICAgICAgICAgIGlucFtcInZhbHVlXCJdID0gJChpbnBbXCJ2YWx1ZVwiXSkuaHRtbCgpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKGlucC50eXBlICYmIGlucC50eXBlID09IFwiZGl2XCIpIHtcbiAgICAgICAgICAgICAgLy9kaXYgaW5wdXQsIHN1cHBvcnQgd3lzaXd5ZyBpbnB1dFxuICAgICAgICAgICAgICAkYy5hcHBlbmQodGhpcy5zdHJmKCc8ZGl2IGNsYXNzPVwid2JibS1pbnAtcm93XCI+PGxhYmVsPnt0aXRsZX08L2xhYmVsPjxkaXYgY2xhc3M9XCJpbnAtdGV4dCBkaXYtbW9kYWwtdGV4dFwiIGNvbnRlbnRlZGl0YWJsZT1cInRydWVcIiBuYW1lPVwie3BhcmFtfVwiPnt2YWx1ZX08L2Rpdj48L2Rpdj4nLCBpbnApKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgIC8vZGVmYXVsdCBpbnB1dFxuICAgICAgICAgICAgICAkYy5hcHBlbmQodGhpcy5zdHJmKCc8ZGl2IGNsYXNzPVwid2JibS1pbnAtcm93XCI+PGxhYmVsPnt0aXRsZX08L2xhYmVsPjxpbnB1dCBjbGFzcz1cImlucC10ZXh0IG1vZGFsLXRleHRcIiB0eXBlPVwidGV4dFwiIG5hbWU9XCJ7cGFyYW19XCIgdmFsdWU9XCJ7dmFsdWV9XCIvPjwvZGl2PicsIGlucCkpO1xuICAgICAgICAgICAgfVxuXG5cbiAgICAgICAgICB9LCB0aGlzKSk7XG4gICAgICAgIH1cbiAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgLy90aGlzLmxhc3RSYW5nZT10aGlzLmdldFJhbmdlKCk7XG5cbiAgICAgIGlmICgkLmlzRnVuY3Rpb24ob3B0Lm9uTG9hZCkpIHtcbiAgICAgICAgb3B0Lm9uTG9hZC5jYWxsKHRoaXMsIGNtZCwgb3B0LCBxdWVyeVN0YXRlKTtcbiAgICAgIH1cblxuICAgICAgJHdiYm0uZmluZCgnI3diYm0tc3VibWl0JykuY2xpY2soJC5wcm94eShmdW5jdGlvbiAoKSB7XG5cbiAgICAgICAgaWYgKCQuaXNGdW5jdGlvbihvcHQub25TdWJtaXQpKSB7IC8vY3VzdG9tIHN1Ym1pdCBmdW5jdGlvbiwgaWYgcmV0dXJuIGZhbHNlLCB0aGVuIGRvbid0IHByb2Nlc3Mgb3VyIGZ1bmN0aW9uXG4gICAgICAgICAgdmFyIHIgPSBvcHQub25TdWJtaXQuY2FsbCh0aGlzLCBjbWQsIG9wdCwgcXVlcnlTdGF0ZSk7XG4gICAgICAgICAgaWYgKHIgPT09IGZhbHNlKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIHZhciBwYXJhbXMgPSB7fTtcbiAgICAgICAgdmFyIHZhbGlkID0gdHJ1ZTtcbiAgICAgICAgdGhpcy4kbW9kYWwuZmluZChcIi53YmJtLWlucGVyclwiKS5yZW1vdmUoKTtcbiAgICAgICAgdGhpcy4kbW9kYWwuZmluZChcIi53YmJtLWJyZHJlZFwiKS5yZW1vdmVDbGFzcyhcIndiYm0tYnJkcmVkXCIpO1xuICAgICAgICAvLyQuZWFjaCh0aGlzLiRtb2RhbC5maW5kKFwiLnRhYi1jb250OnZpc2libGUgaW5wdXRcIiksJC5wcm94eShmdW5jdGlvbihpLGVsKSB7XG4gICAgICAgICQuZWFjaCh0aGlzLiRtb2RhbC5maW5kKFwiLnRhYi1jb250OnZpc2libGUgLmlucC10ZXh0XCIpLCAkLnByb3h5KGZ1bmN0aW9uIChpLCBlbCkge1xuICAgICAgICAgIHZhciB0aWQgPSAkKGVsKS5wYXJlbnRzKFwiLnRhYi1jb250XCIpLmF0dHIoXCJ0aWRcIik7XG4gICAgICAgICAgdmFyIHBuYW1lID0gJChlbCkuYXR0cihcIm5hbWVcIikudG9Mb3dlckNhc2UoKTtcbiAgICAgICAgICB2YXIgcHZhbCA9IFwiXCI7XG4gICAgICAgICAgaWYgKCQoZWwpLmlzKFwiaW5wdXQsdGV4dHJlYSxzZWxlY3RcIikpIHtcbiAgICAgICAgICAgIHB2YWwgPSAkKGVsKS52YWwoKTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgcHZhbCA9ICQoZWwpLmh0bWwoKTtcbiAgICAgICAgICB9XG4gICAgICAgICAgdmFyIHZhbGlkYXRpb24gPSBvcHQudGFic1t0aWRdW1wiaW5wdXRcIl1baV1bXCJ2YWxpZGF0aW9uXCJdO1xuICAgICAgICAgIGlmICh0eXBlb2YgKHZhbGlkYXRpb24pICE9IFwidW5kZWZpbmVkXCIpIHtcbiAgICAgICAgICAgIGlmICghcHZhbC5tYXRjaChuZXcgUmVnRXhwKHZhbGlkYXRpb24sIFwiaVwiKSkpIHtcbiAgICAgICAgICAgICAgdmFsaWQgPSBmYWxzZTtcbiAgICAgICAgICAgICAgJChlbCkuYWZ0ZXIoJzxzcGFuIGNsYXNzPVwid2JibS1pbnBlcnJcIj4nICsgQ1VSTEFORy52YWxpZGF0aW9uX2VyciArICc8L3NwYW4+JykuYWRkQ2xhc3MoXCJ3YmJtLWJyZHJlZFwiKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9XG4gICAgICAgICAgcGFyYW1zW3BuYW1lXSA9IHB2YWw7XG4gICAgICAgIH0sIHRoaXMpKTtcbiAgICAgICAgaWYgKHZhbGlkKSB7XG4gICAgICAgICAgJC5sb2coXCJMYXN0IHJhbmdlOiBcIiArIHRoaXMubGFzdFJhbmdlKTtcbiAgICAgICAgICB0aGlzLnNlbGVjdExhc3RSYW5nZSgpO1xuICAgICAgICAgIC8vaW5zZXJ0IGNhbGxiYWNrXG4gICAgICAgICAgaWYgKHF1ZXJ5U3RhdGUpIHtcbiAgICAgICAgICAgIHRoaXMud2JiUmVtb3ZlQ2FsbGJhY2soY21kLCB0cnVlKTtcbiAgICAgICAgICB9XG4gICAgICAgICAgdGhpcy53YmJJbnNlcnRDYWxsYmFjayhjbWQsIHBhcmFtcyk7XG4gICAgICAgICAgLy9FTkQgaW5zZXJ0IGNhbGxiYWNrXG5cbiAgICAgICAgICB0aGlzLmNsb3NlTW9kYWwoKTtcbiAgICAgICAgICB0aGlzLnVwZGF0ZVVJKCk7XG4gICAgICAgIH1cbiAgICAgIH0sIHRoaXMpKTtcbiAgICAgICR3YmJtLmZpbmQoJyN3YmJtLXJlbW92ZScpLmNsaWNrKCQucHJveHkoZnVuY3Rpb24gKCkge1xuICAgICAgICAvL2NsYmsucmVtb3ZlKCk7XG4gICAgICAgIHRoaXMuc2VsZWN0TGFzdFJhbmdlKCk7XG4gICAgICAgIHRoaXMud2JiUmVtb3ZlQ2FsbGJhY2soY21kKTsgLy9yZW1vdmUgY2FsbGJhY2tcbiAgICAgICAgdGhpcy5jbG9zZU1vZGFsKCk7XG4gICAgICAgIHRoaXMudXBkYXRlVUkoKTtcbiAgICAgIH0sIHRoaXMpKTtcblxuICAgICAgJChkb2N1bWVudC5ib2R5KS5jc3MoXCJvdmVyZmxvd1wiLCBcImhpZGRlblwiKTsgLy9sb2NrIHRoZSBzY3JlZW4sIHJlbW92ZSBzY3JvbGwgb24gYm9keVxuICAgICAgaWYgKCQoXCJib2R5XCIpLmhlaWdodCgpID4gJCh3aW5kb3cpLmhlaWdodCgpKSB7IC8vaWYgYm9keSBoYXMgc2Nyb2xsLCBhZGQgcGFkZGluZy1yaWdodCAxOHB4XG4gICAgICAgICQoZG9jdW1lbnQuYm9keSkuY3NzKFwicGFkZGluZy1yaWdodFwiLCBcIjE4cHhcIik7XG4gICAgICB9XG4gICAgICB0aGlzLiRtb2RhbC5zaG93KCk7XG4gICAgICAvL2lmICh3aW5kb3cuZ2V0U2VsZWN0aW9uKVxuICAgICAgaWYgKHRoaXMuaXNNb2JpbGUpIHtcbiAgICAgICAgJHdiYm0uY3NzKFwibWFyZ2luLXRvcFwiLCBcIjEwcHhcIik7XG4gICAgICB9IGVsc2Uge1xuICAgICAgICAkd2JibS5jc3MoXCJtYXJnaW4tdG9wXCIsICgkKHdpbmRvdykuaGVpZ2h0KCkgLSAkd2JibS5vdXRlckhlaWdodCgpKSAvIDMgKyBcInB4XCIpO1xuICAgICAgfVxuICAgICAgLy9zZXRUaW1lb3V0KCQucHJveHkoZnVuY3Rpb24oKSB7dGhpcy4kbW9kYWwuZmluZChcImlucHV0OnZpc2libGVcIilbMF0uZm9jdXMoKX0sdGhpcyksMTApO1xuICAgICAgc2V0VGltZW91dCgkLnByb3h5KGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdGhpcy4kbW9kYWwuZmluZChcIi5pbnAtdGV4dDp2aXNpYmxlXCIpWzBdLmZvY3VzKClcbiAgICAgIH0sIHRoaXMpLCAxMCk7XG4gICAgfSxcbiAgICBlc2NNb2RhbDogZnVuY3Rpb24gKGUpIHtcbiAgICAgIGlmIChlLndoaWNoID09IDI3KSB7XG4gICAgICAgIHRoaXMuY2xvc2VNb2RhbCgpO1xuICAgICAgfVxuICAgIH0sXG4gICAgY2xvc2VNb2RhbDogZnVuY3Rpb24gKCkge1xuICAgICAgJChkb2N1bWVudC5ib2R5KS5jc3MoXCJvdmVyZmxvd1wiLCBcImF1dG9cIikuY3NzKFwicGFkZGluZy1yaWdodFwiLCBcIjBcIikudW5iaW5kKFwia2V5dXBcIiwgdGhpcy5lc2NNb2RhbCk7IC8vRVNDIGtleSBjbG9zZSBtb2RhbDtcbiAgICAgIHRoaXMuJG1vZGFsLmZpbmQoJyN3YmJtLXN1Ym1pdCwjd2JibS1yZW1vdmUnKS51bmJpbmQoJ2NsaWNrJyk7XG4gICAgICB0aGlzLiRtb2RhbC5oaWRlKCk7XG4gICAgICB0aGlzLmxhc3RSYW5nZSA9IGZhbHNlO1xuICAgICAgcmV0dXJuIHRoaXM7XG4gICAgfSxcbiAgICBnZXRQYXJhbXM6IGZ1bmN0aW9uIChzcmMsIHMsIG9mZnNldCkge1xuICAgICAgdmFyIHBhcmFtcyA9IHt9O1xuICAgICAgaWYgKHRoaXMub3B0aW9ucy5iYm1vZGUpIHtcbiAgICAgICAgLy9iYm1vZGVcbiAgICAgICAgdmFyIHN0ZXh0ID0gcy5tYXRjaCgvXFx7W1xcc1xcU10rP1xcfS9nKTtcbiAgICAgICAgcyA9IHRoaXMucHJlcGFyZVJHWChzKTtcbiAgICAgICAgdmFyIHJneCA9IG5ldyBSZWdFeHAocywgXCJnXCIpO1xuICAgICAgICB2YXIgdmFsID0gdGhpcy50eHRBcmVhLnZhbHVlO1xuICAgICAgICBpZiAob2Zmc2V0ID4gMCkge1xuICAgICAgICAgIHZhbCA9IHZhbC5zdWJzdHIob2Zmc2V0LCB2YWwubGVuZ3RoIC0gb2Zmc2V0KTtcbiAgICAgICAgfVxuICAgICAgICB2YXIgYSA9IHJneC5leGVjKHZhbCk7XG4gICAgICAgIGlmIChhKSB7XG4gICAgICAgICAgJC5lYWNoKHN0ZXh0LCBmdW5jdGlvbiAoaSwgbikge1xuICAgICAgICAgICAgcGFyYW1zW24ucmVwbGFjZSgvXFx7fFxcfS9nLCBcIlwiKS5yZXBsYWNlKC9cIi9nLCBcIidcIikudG9Mb3dlckNhc2UoKV0gPSBhW2kgKyAxXTtcbiAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgdmFyIHJ1bGVzID0gdGhpcy5vcHRpb25zLnJ1bGVzW3NdWzBdWzFdO1xuICAgICAgICAkLmVhY2gocnVsZXMsICQucHJveHkoZnVuY3Rpb24gKGssIHYpIHtcbiAgICAgICAgICB2YXIgdmFsdWUgPSBcIlwiO1xuICAgICAgICAgIHZhciAkdiA9ICh2LnNlbCAhPT0gZmFsc2UpID8gdmFsdWUgPSAkKHNyYykuZmluZCh2LnNlbCkgOiAkKHNyYyk7XG4gICAgICAgICAgaWYgKHYuYXR0ciAhPT0gZmFsc2UpIHtcbiAgICAgICAgICAgIHZhbHVlID0gJHYuYXR0cih2LmF0dHIpO1xuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICB2YWx1ZSA9ICR2Lmh0bWwoKTtcbiAgICAgICAgICB9XG4gICAgICAgICAgaWYgKHZhbHVlKSB7XG4gICAgICAgICAgICBpZiAodi5yZ3ggIT09IGZhbHNlKSB7XG4gICAgICAgICAgICAgIHZhciBtID0gdmFsdWUubWF0Y2gobmV3IFJlZ0V4cCh2LnJneCkpO1xuICAgICAgICAgICAgICBpZiAobSAmJiBtLmxlbmd0aCA9PSAyKSB7XG4gICAgICAgICAgICAgICAgdmFsdWUgPSBtWzFdO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBwYXJhbXNba10gPSB2YWx1ZS5yZXBsYWNlKC9cIi9nLCBcIidcIik7XG4gICAgICAgICAgfVxuICAgICAgICB9LCB0aGlzKSlcbiAgICAgIH1cbiAgICAgIHJldHVybiBwYXJhbXM7XG4gICAgfSxcblxuXG4gICAgLy9pbWdVcGxvYWRlclxuICAgIGltZ0xvYWRNb2RhbDogZnVuY3Rpb24gKCkge1xuICAgICAgJC5sb2coXCJpbWdMb2FkTW9kYWxcIik7XG4gICAgICBpZiAodGhpcy5vcHRpb25zLmltZ3VwbG9hZCA9PT0gdHJ1ZSkge1xuICAgICAgICB0aGlzLiRtb2RhbC5maW5kKFwiI2ltZ3VwbG9hZGVyXCIpLmRyYWdmaWxldXBsb2FkKHtcbiAgICAgICAgICB1cmw6IHRoaXMuc3RyZih0aGlzLm9wdGlvbnMuaW1nX3VwbG9hZHVybCwgdGhpcy5vcHRpb25zKSxcbiAgICAgICAgICBleHRyYVBhcmFtczoge1xuICAgICAgICAgICAgbWF4d2lkdGg6IHRoaXMub3B0aW9ucy5pbWdfbWF4d2lkdGgsXG4gICAgICAgICAgICBtYXhoZWlnaHQ6IHRoaXMub3B0aW9ucy5pbWdfbWF4aGVpZ2h0XG4gICAgICAgICAgfSxcbiAgICAgICAgICB0aGVtZVByZWZpeDogdGhpcy5vcHRpb25zLnRoZW1lUHJlZml4LFxuICAgICAgICAgIHRoZW1lTmFtZTogdGhpcy5vcHRpb25zLnRoZW1lTmFtZSxcbiAgICAgICAgICBzdWNjZXNzOiAkLnByb3h5KGZ1bmN0aW9uIChkYXRhKSB7XG4gICAgICAgICAgICB0aGlzLiR0eHRBcmVhLmluc2VydEltYWdlKGRhdGEuaW1hZ2VfbGluaywgZGF0YS50aHVtYl9saW5rKTtcblxuICAgICAgICAgICAgdGhpcy5jbG9zZU1vZGFsKCk7XG4gICAgICAgICAgICB0aGlzLnVwZGF0ZVVJKCk7XG4gICAgICAgICAgfSwgdGhpcylcbiAgICAgICAgfSk7XG5cbiAgICAgICAgdGhpcy4kbW9kYWwuZmluZChcIiNmaWxldXBsXCIpLmJpbmQoXCJjaGFuZ2VcIiwgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICQoXCIjZnVwZm9ybVwiKS5zdWJtaXQoKTtcbiAgICAgICAgfSk7XG4gICAgICAgIHRoaXMuJG1vZGFsLmZpbmQoXCIjZnVwZm9ybVwiKS5iaW5kKFwic3VibWl0XCIsICQucHJveHkoZnVuY3Rpb24gKGUpIHtcbiAgICAgICAgICAkKGUudGFyZ2V0KS5wYXJlbnRzKFwiI2ltZ3VwbG9hZGVyXCIpLmhpZGUoKS5hZnRlcignPGRpdiBjbGFzcz1cImxvYWRlclwiPjxpbWcgc3JjPVwiJyArIHRoaXMub3B0aW9ucy50aGVtZVByZWZpeCArICcvJyArIHRoaXMub3B0aW9ucy50aGVtZU5hbWUgKyAnL2ltZy9sb2FkZXIuZ2lmXCIgLz48YnIvPjxzcGFuPicgKyBDVVJMQU5HLmxvYWRpbmcgKyAnPC9zcGFuPjwvZGl2PicpLnBhcmVudCgpLmNzcyhcInRleHQtYWxpZ25cIiwgXCJjZW50ZXJcIik7XG4gICAgICAgIH0sIHRoaXMpKVxuXG4gICAgICB9IGVsc2Uge1xuICAgICAgICB0aGlzLiRtb2RhbC5maW5kKFwiLmhhc3RhYnNcIikucmVtb3ZlQ2xhc3MoXCJoYXN0YWJzXCIpO1xuICAgICAgICB0aGlzLiRtb2RhbC5maW5kKFwiI2ltZ3VwbG9hZGVyXCIpLnBhcmVudHMoXCIudGFiLWNvbnRcIikucmVtb3ZlKCk7XG4gICAgICAgIHRoaXMuJG1vZGFsLmZpbmQoXCIud2JibS10YWJsaXN0XCIpLnJlbW92ZSgpO1xuICAgICAgfVxuICAgIH0sXG4gICAgaW1nU3VibWl0TW9kYWw6IGZ1bmN0aW9uICgpIHtcbiAgICAgICQubG9nKFwiaW1nU3VibWl0TW9kYWxcIik7XG4gICAgfSxcbiAgICAvL0RFQlVHXG4gICAgcHJpbnRPYmplY3RJbklFOiBmdW5jdGlvbiAob2JqKSB7XG4gICAgICB0cnkge1xuICAgICAgICAkLmxvZyhKU09OLnN0cmluZ2lmeShvYmopKTtcbiAgICAgIH0gY2F0Y2ggKGUpIHtcbiAgICAgIH1cbiAgICB9LFxuICAgIGNoZWNrRmlsdGVyOiBmdW5jdGlvbiAobm9kZSwgZmlsdGVyKSB7XG4gICAgICAkLmxvZyhcIm5vZGU6IFwiICsgJChub2RlKS5nZXQoMCkub3V0ZXJIVE1MICsgXCIgZmlsdGVyOiBcIiArIGZpbHRlciArIFwiIHJlczogXCIgKyAkKG5vZGUpLmlzKGZpbHRlci50b0xvd2VyQ2FzZSgpKSk7XG4gICAgfSxcbiAgICBkZWJ1ZzogZnVuY3Rpb24gKG1zZykge1xuICAgICAgaWYgKHRoaXMub3B0aW9ucy5kZWJ1ZyA9PT0gdHJ1ZSkge1xuICAgICAgICB2YXIgdGltZSA9IChuZXcgRGF0ZSgpKS5nZXRUaW1lKCk7XG4gICAgICAgIGlmICh0eXBlb2YgKGNvbnNvbGUpICE9IFwidW5kZWZpbmVkXCIpIHtcbiAgICAgICAgICBjb25zb2xlLmxvZygodGltZSAtIHRoaXMuc3RhcnRUaW1lKSArIFwiIG1zOiBcIiArIG1zZyk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgJChcIiNleGxvZ1wiKS5hcHBlbmQoJzxwPicgKyAodGltZSAtIHRoaXMuc3RhcnRUaW1lKSArIFwiIG1zOiBcIiArIG1zZyArICc8L3A+Jyk7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5zdGFydFRpbWUgPSB0aW1lO1xuICAgICAgfVxuICAgIH0sXG5cbiAgICAvL0Jyb3dzZXIgZml4ZXNcbiAgICBpc0Nocm9tZTogZnVuY3Rpb24gKCkge1xuICAgICAgcmV0dXJuICh3aW5kb3cuY2hyb21lKSA/IHRydWUgOiBmYWxzZTtcbiAgICB9LFxuICAgIGZpeFRhYmxlVHJhbnNmb3JtOiBmdW5jdGlvbiAoaHRtbCkge1xuICAgICAgaWYgKCFodG1sKSB7XG4gICAgICAgIHJldHVybiBcIlwiO1xuICAgICAgfVxuICAgICAgaWYgKCQuaW5BcnJheShcInRhYmxlXCIsIHRoaXMub3B0aW9ucy5idXR0b25zKSA9PSAtMSkge1xuICAgICAgICByZXR1cm4gaHRtbC5yZXBsYWNlKC9cXDwoXFwvKj8odGFibGV8dHJ8dGR8dGJvZHkpKVtePl0qXFw+L2lnLCBcIlwiKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHJldHVybiBodG1sLnJlcGxhY2UoL1xcPChcXC8qPyh0YWJsZXx0cnx0ZCkpW14+XSpcXD4vaWcsIFwiWyQxXVwiLnRvTG93ZXJDYXNlKCkpLnJlcGxhY2UoL1xcPFxcLyp0Ym9keVtePl0qXFw+L2lnLCBcIlwiKTtcbiAgICAgIH1cbiAgICB9XG4gIH1cblxuICAkLmxvZyA9IGZ1bmN0aW9uIChtc2cpIHtcbiAgICBpZiAodHlwZW9mICh3YmJkZWJ1ZykgIT0gXCJ1bmRlZmluZWRcIiAmJiB3YmJkZWJ1ZyA9PT0gdHJ1ZSkge1xuICAgICAgaWYgKHR5cGVvZiAoY29uc29sZSkgIT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICBjb25zb2xlLmxvZyhtc2cpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgJChcIiNleGxvZ1wiKS5hcHBlbmQoJzxwPicgKyBtc2cgKyAnPC9wPicpO1xuICAgICAgfVxuICAgIH1cbiAgfVxuICAkLmZuLnd5c2liYiA9IGZ1bmN0aW9uIChzZXR0aW5ncykge1xuICAgIHJldHVybiB0aGlzLmVhY2goZnVuY3Rpb24gKCkge1xuICAgICAgdmFyIGRhdGEgPSAkKHRoaXMpLmRhdGEoXCJ3YmJcIik7XG4gICAgICBpZiAoIWRhdGEpIHtcbiAgICAgICAgbmV3ICQud3lzaWJiKHRoaXMsIHNldHRpbmdzKTtcbiAgICAgIH1cbiAgICB9KTtcbiAgfVxuICAkLmZuLndkcmFnID0gZnVuY3Rpb24gKG9wdCkge1xuICAgIGlmICghb3B0LnNjb3BlKSB7XG4gICAgICBvcHQuc2NvcGUgPSB0aGlzO1xuICAgIH1cbiAgICB2YXIgc3RhcnQgPSB7eDogMCwgeTogMCwgaGVpZ2h0OiAwfTtcbiAgICB2YXIgZHJhZztcbiAgICBvcHQuc2NvcGUuZHJhZ19tb3VzZWRvd24gPSBmdW5jdGlvbiAoZSkge1xuICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgc3RhcnQgPSB7XG4gICAgICAgIHg6IGUucGFnZVgsXG4gICAgICAgIHk6IGUucGFnZVksXG4gICAgICAgIGhlaWdodDogb3B0LmhlaWdodCxcbiAgICAgICAgc2hlaWdodDogb3B0LnNjb3BlLiRib2R5LmhlaWdodCgpXG4gICAgICB9XG4gICAgICBkcmFnID0gdHJ1ZTtcbiAgICAgICQoZG9jdW1lbnQpLmJpbmQoXCJtb3VzZW1vdmVcIiwgJC5wcm94eShvcHQuc2NvcGUuZHJhZ19tb3VzZW1vdmUsIHRoaXMpKTtcbiAgICAgICQodGhpcykuYWRkQ2xhc3MoXCJkcmFnXCIpO1xuICAgIH07XG4gICAgb3B0LnNjb3BlLmRyYWdfbW91c2V1cCA9IGZ1bmN0aW9uIChlKSB7XG4gICAgICBpZiAoZHJhZyA9PT0gdHJ1ZSkge1xuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICQoZG9jdW1lbnQpLnVuYmluZChcIm1vdXNlbW92ZVwiLCBvcHQuc2NvcGUuZHJhZ19tb3VzZW1vdmUpO1xuICAgICAgICAkKHRoaXMpLnJlbW92ZUNsYXNzKFwiZHJhZ1wiKTtcbiAgICAgICAgZHJhZyA9IGZhbHNlO1xuICAgICAgfVxuICAgIH07XG4gICAgb3B0LnNjb3BlLmRyYWdfbW91c2Vtb3ZlID0gZnVuY3Rpb24gKGUpIHtcbiAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgIHZhciBheGlzWCA9IDAsIGF4aXNZID0gMDtcbiAgICAgIGlmIChvcHQuYXhpc1gpIHtcbiAgICAgICAgYXhpc1ggPSBlLnBhZ2VYIC0gc3RhcnQueDtcbiAgICAgIH1cbiAgICAgIGlmIChvcHQuYXhpc1kpIHtcbiAgICAgICAgYXhpc1kgPSBlLnBhZ2VZIC0gc3RhcnQueTtcbiAgICAgIH1cbiAgICAgIGlmIChheGlzWSAhPSAwKSB7XG4gICAgICAgIHZhciBuaGVpZ2h0ID0gc3RhcnQuc2hlaWdodCArIGF4aXNZO1xuICAgICAgICBpZiAobmhlaWdodCA+IHN0YXJ0LmhlaWdodCAmJiBuaGVpZ2h0IDw9IG9wdC5zY29wZS5vcHRpb25zLnJlc2l6ZV9tYXhoZWlnaHQpIHtcbiAgICAgICAgICBpZiAob3B0LnNjb3BlLm9wdGlvbnMuYmJtb2RlID09IHRydWUpIHtcbiAgICAgICAgICAgIG9wdC5zY29wZS4kdHh0QXJlYS5jc3MoKG9wdC5zY29wZS5vcHRpb25zLmF1dG9yZXNpemUgPT09IHRydWUpID8gXCJtaW4taGVpZ2h0XCIgOiBcImhlaWdodFwiLCBuaGVpZ2h0ICsgXCJweFwiKTtcbiAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgb3B0LnNjb3BlLiRib2R5LmNzcygob3B0LnNjb3BlLm9wdGlvbnMuYXV0b3Jlc2l6ZSA9PT0gdHJ1ZSkgPyBcIm1pbi1oZWlnaHRcIiA6IFwiaGVpZ2h0XCIsIG5oZWlnaHQgKyBcInB4XCIpO1xuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfVxuICAgIH07XG5cblxuICAgICQodGhpcykuYmluZChcIm1vdXNlZG93blwiLCBvcHQuc2NvcGUuZHJhZ19tb3VzZWRvd24pO1xuICAgICQoZG9jdW1lbnQpLmJpbmQoXCJtb3VzZXVwXCIsICQucHJveHkob3B0LnNjb3BlLmRyYWdfbW91c2V1cCwgdGhpcykpO1xuICB9LFxuXG4gICAgLy9BUElcbiAgICAkLmZuLmdldERvYyA9IGZ1bmN0aW9uICgpIHtcbiAgICAgIHJldHVybiB0aGlzLmRhdGEoJ3diYicpLmRvYztcbiAgICB9XG4gICQuZm4uZ2V0U2VsZWN0VGV4dCA9IGZ1bmN0aW9uIChmcm9tVGV4dEFyZWEpIHtcbiAgICByZXR1cm4gdGhpcy5kYXRhKCd3YmInKS5nZXRTZWxlY3RUZXh0KGZyb21UZXh0QXJlYSk7XG4gIH1cbiAgJC5mbi5iYmNvZGUgPSBmdW5jdGlvbiAoZGF0YSkge1xuICAgIGlmICh0eXBlb2YgKGRhdGEpICE9IFwidW5kZWZpbmVkXCIpIHtcbiAgICAgIGlmICh0aGlzLmRhdGEoJ3diYicpLm9wdGlvbnMuYmJtb2RlKSB7XG4gICAgICAgIHRoaXMuZGF0YSgnd2JiJykuJHR4dEFyZWEudmFsKGRhdGEpO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgdGhpcy5kYXRhKCd3YmInKS4kYm9keS5odG1sKHRoaXMuZGF0YShcIndiYlwiKS5nZXRIVE1MKGRhdGEpKTtcbiAgICAgIH1cbiAgICAgIHJldHVybiB0aGlzO1xuICAgIH0gZWxzZSB7XG4gICAgICByZXR1cm4gdGhpcy5kYXRhKCd3YmInKS5nZXRCQkNvZGUoKTtcbiAgICB9XG4gIH1cbiAgJC5mbi5odG1sY29kZSA9IGZ1bmN0aW9uIChkYXRhKSB7XG4gICAgaWYgKCF0aGlzLmRhdGEoJ3diYicpLm9wdGlvbnMub25seUJCTW9kZSAmJiB0aGlzLmRhdGEoJ3diYicpLmluaXRlZCA9PT0gdHJ1ZSkge1xuICAgICAgaWYgKHR5cGVvZiAoZGF0YSkgIT0gXCJ1bmRlZmluZWRcIikge1xuICAgICAgICB0aGlzLmRhdGEoJ3diYicpLiRib2R5Lmh0bWwoZGF0YSk7XG4gICAgICAgIHJldHVybiB0aGlzO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGF0YSgnd2JiJykuZ2V0SFRNTCh0aGlzLmRhdGEoJ3diYicpLiR0eHRBcmVhLnZhbCgpKTtcbiAgICAgIH1cbiAgICB9XG4gIH1cbiAgJC5mbi5nZXRCQkNvZGUgPSBmdW5jdGlvbiAoKSB7XG4gICAgcmV0dXJuIHRoaXMuZGF0YSgnd2JiJykuZ2V0QkJDb2RlKCk7XG4gIH1cbiAgJC5mbi5nZXRIVE1MID0gZnVuY3Rpb24gKCkge1xuICAgIHZhciB3YmIgPSB0aGlzLmRhdGEoJ3diYicpO1xuICAgIHJldHVybiB3YmIuZ2V0SFRNTCh3YmIuJHR4dEFyZWEudmFsKCkpO1xuICB9XG4gICQuZm4uZ2V0SFRNTEJ5Q29tbWFuZCA9IGZ1bmN0aW9uIChjb21tYW5kLCBwYXJhbXMpIHtcbiAgICByZXR1cm4gdGhpcy5kYXRhKFwid2JiXCIpLmdldEhUTUxCeUNvbW1hbmQoY29tbWFuZCwgcGFyYW1zKTtcbiAgfVxuICAkLmZuLmdldEJCQ29kZUJ5Q29tbWFuZCA9IGZ1bmN0aW9uIChjb21tYW5kLCBwYXJhbXMpIHtcbiAgICByZXR1cm4gdGhpcy5kYXRhKFwid2JiXCIpLmdldEJCQ29kZUJ5Q29tbWFuZChjb21tYW5kLCBwYXJhbXMpO1xuICB9XG4gICQuZm4uaW5zZXJ0QXRDdXJzb3IgPSBmdW5jdGlvbiAoZGF0YSwgZm9yY2VCQk1vZGUpIHtcbiAgICB0aGlzLmRhdGEoXCJ3YmJcIikuaW5zZXJ0QXRDdXJzb3IoZGF0YSwgZm9yY2VCQk1vZGUpO1xuICAgIHJldHVybiB0aGlzLmRhdGEoXCJ3YmJcIik7XG4gIH1cbiAgJC5mbi5leGVjQ29tbWFuZCA9IGZ1bmN0aW9uIChjb21tYW5kLCB2YWx1ZSkge1xuICAgIHRoaXMuZGF0YShcIndiYlwiKS5leGVjQ29tbWFuZChjb21tYW5kLCB2YWx1ZSk7XG4gICAgcmV0dXJuIHRoaXMuZGF0YShcIndiYlwiKTtcbiAgfVxuICAkLmZuLmluc2VydEltYWdlID0gZnVuY3Rpb24gKGltZ3VybCwgdGh1bWJ1cmwpIHtcbiAgICB2YXIgZWRpdG9yID0gdGhpcy5kYXRhKFwid2JiXCIpO1xuICAgIHZhciBjb2RlID0gKHRodW1idXJsKSA/IGVkaXRvci5nZXRDb2RlQnlDb21tYW5kKCdsaW5rJywge3VybDogaW1ndXJsLCBzZWx0ZXh0OiBlZGl0b3IuZ2V0Q29kZUJ5Q29tbWFuZCgnaW1nJywge3NyYzogdGh1bWJ1cmx9KX0pIDogZWRpdG9yLmdldENvZGVCeUNvbW1hbmQoJ2ltZycsIHtzcmM6IGltZ3VybH0pO1xuICAgIHRoaXMuaW5zZXJ0QXRDdXJzb3IoY29kZSk7XG4gICAgcmV0dXJuIGVkaXRvcjtcbiAgfVxuICAkLmZuLnN5bmMgPSBmdW5jdGlvbiAoKSB7XG4gICAgdGhpcy5kYXRhKFwid2JiXCIpLnN5bmMoKTtcbiAgICByZXR1cm4gdGhpcy5kYXRhKFwid2JiXCIpO1xuICB9XG4gICQuZm4uZGVzdHJveSA9IGZ1bmN0aW9uICgpIHtcbiAgICB0aGlzLmRhdGEoXCJ3YmJcIikuZGVzdHJveSgpO1xuICB9XG5cblxuICAkLmZuLnF1ZXJ5U3RhdGUgPSBmdW5jdGlvbiAoY29tbWFuZCkge1xuICAgIHJldHVybiB0aGlzLmRhdGEoXCJ3YmJcIikucXVlcnlTdGF0ZShjb21tYW5kKTtcbiAgfVxufSkoalF1ZXJ5KTtcblxuXG4vL0RyYWcmRHJvcCBmaWxlIHVwbG9hZGVyXG4oZnVuY3Rpb24gKCQpIHtcbiAgJ3VzZSBzdHJpY3QnO1xuXG4gICQuZm4uZHJhZ2ZpbGV1cGxvYWQgPSBmdW5jdGlvbiAob3B0aW9ucykge1xuICAgIHJldHVybiB0aGlzLmVhY2goZnVuY3Rpb24gKCkge1xuICAgICAgdmFyIHVwbCA9IG5ldyBGaWxlVXBsb2FkKHRoaXMsIG9wdGlvbnMpO1xuICAgICAgdXBsLmluaXQoKTtcbiAgICB9KTtcbiAgfTtcblxuICBmdW5jdGlvbiBGaWxlVXBsb2FkKGUsIG9wdGlvbnMpXG4gIHtcbiAgICB0aGlzLiRibG9jayA9ICQoZSk7XG5cbiAgICB0aGlzLm9wdCA9ICQuZXh0ZW5kKHtcbiAgICAgIHVybDogZmFsc2UsXG4gICAgICBzdWNjZXNzOiBmYWxzZSxcbiAgICAgIGV4dHJhUGFyYW1zOiBmYWxzZSxcbiAgICAgIGZpbGVQYXJhbTogJ2ltZycsXG4gICAgICB2YWxpZGF0aW9uOiAnXFwuKGpwZ3xwbmd8Z2lmfGpwZWcpJCcsXG5cbiAgICAgIHQxOiBDVVJMQU5HLmZpbGV1cGxvYWRfdGV4dDEsXG4gICAgICB0MjogQ1VSTEFORy5maWxldXBsb2FkX3RleHQyXG4gICAgfSwgb3B0aW9ucyk7XG4gIH1cblxuICBGaWxlVXBsb2FkLnByb3RvdHlwZSA9IHtcbiAgICBpbml0OiBmdW5jdGlvbiAoKSB7XG4gICAgICBpZiAod2luZG93LkZvcm1EYXRhICE9IG51bGwpIHtcbiAgICAgICAgdGhpcy4kYmxvY2suYWRkQ2xhc3MoXCJkcmFnXCIpO1xuICAgICAgICB0aGlzLiRibG9jay5wcmVwZW5kKCc8ZGl2IGNsYXNzPVwicDJcIj4nICsgdGhpcy5vcHQudDIgKyAnPC9kaXY+Jyk7XG4gICAgICAgIHRoaXMuJGJsb2NrLnByZXBlbmQoJzxkaXYgY2xhc3M9XCJwXCI+JyArIHRoaXMub3B0LnQxICsgJzwvZGl2PicpO1xuXG4gICAgICAgIHRoaXMuJGJsb2NrLmJpbmQoJ2RyYWdvdmVyJywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICQodGhpcykuYWRkQ2xhc3MoJ2RyYWdvdmVyJyk7XG4gICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9KTtcbiAgICAgICAgdGhpcy4kYmxvY2suYmluZCgnZHJhZ2xlYXZlJywgZnVuY3Rpb24gKCkge1xuICAgICAgICAgICQodGhpcykucmVtb3ZlQ2xhc3MoJ2RyYWdvdmVyJyk7XG4gICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9KTtcblxuICAgICAgICAvL3VwbG9hZCBwcm9ncmVzc1xuICAgICAgICB2YXIgdXBsb2FkUHJvZ3Jlc3MgPSAkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgdmFyIHAgPSBwYXJzZUludChlLmxvYWRlZCAvIGUudG90YWwgKiAxMDAsIDEwKTtcbiAgICAgICAgICB0aGlzLiRsb2FkZXIuY2hpbGRyZW4oXCJzcGFuXCIpLnRleHQoQ1VSTEFORy5sb2FkaW5nICsgJzogJyArIHAgKyAnJScpO1xuXG4gICAgICAgIH0sIHRoaXMpO1xuICAgICAgICB2YXIgeGhyID0galF1ZXJ5LmFqYXhTZXR0aW5ncy54aHIoKTtcbiAgICAgICAgaWYgKHhoci51cGxvYWQpIHtcbiAgICAgICAgICB4aHIudXBsb2FkLmFkZEV2ZW50TGlzdGVuZXIoJ3Byb2dyZXNzJywgdXBsb2FkUHJvZ3Jlc3MsIGZhbHNlKTtcbiAgICAgICAgfVxuICAgICAgICB0aGlzLiRibG9ja1swXS5vbmRyb3AgPSAkLnByb3h5KGZ1bmN0aW9uIChlKSB7XG4gICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgIHRoaXMuJGJsb2NrLnJlbW92ZUNsYXNzKCdkcmFnb3ZlcicpO1xuICAgICAgICAgIHZhciB1ZmlsZSA9IGUuZGF0YVRyYW5zZmVyLmZpbGVzWzBdO1xuICAgICAgICAgIGlmICh0aGlzLm9wdC52YWxpZGF0aW9uICYmICF1ZmlsZS5uYW1lLm1hdGNoKG5ldyBSZWdFeHAodGhpcy5vcHQudmFsaWRhdGlvbikpKSB7XG4gICAgICAgICAgICB0aGlzLmVycm9yKENVUkxBTkcudmFsaWRhdGlvbl9lcnIpO1xuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgIH1cbiAgICAgICAgICB2YXIgZkRhdGEgPSBuZXcgRm9ybURhdGEoKTtcbiAgICAgICAgICBmRGF0YS5hcHBlbmQodGhpcy5vcHQuZmlsZVBhcmFtLCB1ZmlsZSk7XG5cbiAgICAgICAgICBpZiAodGhpcy5vcHQuZXh0cmFQYXJhbXMpIHsgLy9jaGVjayBmb3IgZXh0cmFQYXJhbXMgdG8gdXBsb2FkXG4gICAgICAgICAgICAkLmVhY2godGhpcy5vcHQuZXh0cmFQYXJhbXMsIGZ1bmN0aW9uIChrLCB2KSB7XG4gICAgICAgICAgICAgIGZEYXRhLmFwcGVuZChrLCB2KTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgIH1cblxuICAgICAgICAgIHRoaXMuJGxvYWRlciA9ICQoJzxkaXYgY2xhc3M9XCJsb2FkZXJcIj48aW1nIHNyYz1cIicgKyB0aGlzLm9wdC50aGVtZVByZWZpeCArICcvJyArIHRoaXMub3B0LnRoZW1lTmFtZSArICcvaW1nL2xvYWRlci5naWZcIiAvPjxici8+PHNwYW4+JyArIENVUkxBTkcubG9hZGluZyArICc8L3NwYW4+PC9kaXY+Jyk7XG4gICAgICAgICAgdGhpcy4kYmxvY2suaHRtbCh0aGlzLiRsb2FkZXIpO1xuXG4gICAgICAgICAgJC5hamF4KHtcbiAgICAgICAgICAgIHR5cGU6ICdQT1NUJyxcbiAgICAgICAgICAgIHVybDogdGhpcy5vcHQudXJsLFxuICAgICAgICAgICAgZGF0YTogZkRhdGEsXG4gICAgICAgICAgICBwcm9jZXNzRGF0YTogZmFsc2UsXG4gICAgICAgICAgICBjb250ZW50VHlwZTogZmFsc2UsXG4gICAgICAgICAgICB4aHI6IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgcmV0dXJuIHhoclxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGRhdGFUeXBlOiAnanNvbicsXG4gICAgICAgICAgICBzdWNjZXNzOiAkLnByb3h5KGZ1bmN0aW9uIChkYXRhKSB7XG4gICAgICAgICAgICAgIGlmIChkYXRhICYmIGRhdGEuc3RhdHVzID09IDEpIHtcbiAgICAgICAgICAgICAgICB0aGlzLm9wdC5zdWNjZXNzKGRhdGEpO1xuICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIHRoaXMuZXJyb3IoZGF0YS5tc2cgfHwgQ1VSTEFORy5lcnJvcl9vbnVwbG9hZCk7XG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0sIHRoaXMpLFxuICAgICAgICAgICAgZXJyb3I6ICQucHJveHkoZnVuY3Rpb24gKHhociwgdHh0LCB0aHIpIHtcbiAgICAgICAgICAgICAgdGhpcy5lcnJvcihDVVJMQU5HLmVycm9yX29udXBsb2FkKVxuICAgICAgICAgICAgfSwgdGhpcylcbiAgICAgICAgICB9KTtcbiAgICAgICAgfSwgdGhpcyk7XG5cbiAgICAgIH1cbiAgICB9LFxuICAgIGVycm9yOiBmdW5jdGlvbiAobXNnKSB7XG4gICAgICB0aGlzLiRibG9jay5maW5kKFwiLnVwbC1lcnJvclwiKS5yZW1vdmUoKS5lbmQoKS5hcHBlbmQoJzxzcGFuIGNsYXNzPVwidXBsLWVycm9yXCI+JyArIG1zZyArICc8L3NwYW4+JykuYWRkQ2xhc3MoXCJ3YmJtLWJyZHJlZFwiKTtcbiAgICB9XG4gIH1cbn0pKGpRdWVyeSk7XG4iLCIvLyBleHRyYWN0ZWQgYnkgbWluaS1jc3MtZXh0cmFjdC1wbHVnaW5cbmV4cG9ydCB7fTsiLCJpbXBvcnQgeyByZW5kZXIsIHN0YXRpY1JlbmRlckZucyB9IGZyb20gXCIuL0NrZWRpdG9ySW5wdXRDb21wb25lbnQudnVlP3Z1ZSZ0eXBlPXRlbXBsYXRlJmlkPTM2ODU5NjRhJlwiXG5pbXBvcnQgc2NyaXB0IGZyb20gXCIuL0NrZWRpdG9ySW5wdXRDb21wb25lbnQudnVlP3Z1ZSZ0eXBlPXNjcmlwdCZsYW5nPWpzJlwiXG5leHBvcnQgKiBmcm9tIFwiLi9Da2VkaXRvcklucHV0Q29tcG9uZW50LnZ1ZT92dWUmdHlwZT1zY3JpcHQmbGFuZz1qcyZcIlxuXG5cbi8qIG5vcm1hbGl6ZSBjb21wb25lbnQgKi9cbmltcG9ydCBub3JtYWxpemVyIGZyb20gXCIhLi4vLi4vLi4vLi4vLi4vbm9kZV9tb2R1bGVzL3Z1ZS1sb2FkZXIvbGliL3J1bnRpbWUvY29tcG9uZW50Tm9ybWFsaXplci5qc1wiXG52YXIgY29tcG9uZW50ID0gbm9ybWFsaXplcihcbiAgc2NyaXB0LFxuICByZW5kZXIsXG4gIHN0YXRpY1JlbmRlckZucyxcbiAgZmFsc2UsXG4gIG51bGwsXG4gIG51bGwsXG4gIG51bGxcbiAgXG4pXG5cbi8qIGhvdCByZWxvYWQgKi9cbmlmIChtb2R1bGUuaG90KSB7XG4gIHZhciBhcGkgPSByZXF1aXJlKFwiL1VzZXJzL21ha3NpbS9NeVByb2plY3RzL2pvaG5jbXM5LmxvY2FsL25vZGVfbW9kdWxlcy92dWUtaG90LXJlbG9hZC1hcGkvZGlzdC9pbmRleC5qc1wiKVxuICBhcGkuaW5zdGFsbChyZXF1aXJlKCd2dWUnKSlcbiAgaWYgKGFwaS5jb21wYXRpYmxlKSB7XG4gICAgbW9kdWxlLmhvdC5hY2NlcHQoKVxuICAgIGlmICghYXBpLmlzUmVjb3JkZWQoJzM2ODU5NjRhJykpIHtcbiAgICAgIGFwaS5jcmVhdGVSZWNvcmQoJzM2ODU5NjRhJywgY29tcG9uZW50Lm9wdGlvbnMpXG4gICAgfSBlbHNlIHtcbiAgICAgIGFwaS5yZWxvYWQoJzM2ODU5NjRhJywgY29tcG9uZW50Lm9wdGlvbnMpXG4gICAgfVxuICAgIG1vZHVsZS5ob3QuYWNjZXB0KFwiLi9Da2VkaXRvcklucHV0Q29tcG9uZW50LnZ1ZT92dWUmdHlwZT10ZW1wbGF0ZSZpZD0zNjg1OTY0YSZcIiwgZnVuY3Rpb24gKCkge1xuICAgICAgYXBpLnJlcmVuZGVyKCczNjg1OTY0YScsIHtcbiAgICAgICAgcmVuZGVyOiByZW5kZXIsXG4gICAgICAgIHN0YXRpY1JlbmRlckZuczogc3RhdGljUmVuZGVyRm5zXG4gICAgICB9KVxuICAgIH0pXG4gIH1cbn1cbmNvbXBvbmVudC5vcHRpb25zLl9fZmlsZSA9IFwidGhlbWVzL2RlZmF1bHQvc3JjL2pzL2NvbXBvbmVudHMvQ2tlZGl0b3JJbnB1dENvbXBvbmVudC52dWVcIlxuZXhwb3J0IGRlZmF1bHQgY29tcG9uZW50LmV4cG9ydHMiLCJpbXBvcnQgeyByZW5kZXIsIHN0YXRpY1JlbmRlckZucyB9IGZyb20gXCIuL0NvbW1lbnRzQ29tcG9uZW50LnZ1ZT92dWUmdHlwZT10ZW1wbGF0ZSZpZD01MjY3NTNlNiZcIlxuaW1wb3J0IHNjcmlwdCBmcm9tIFwiLi9Db21tZW50c0NvbXBvbmVudC52dWU/dnVlJnR5cGU9c2NyaXB0Jmxhbmc9anMmXCJcbmV4cG9ydCAqIGZyb20gXCIuL0NvbW1lbnRzQ29tcG9uZW50LnZ1ZT92dWUmdHlwZT1zY3JpcHQmbGFuZz1qcyZcIlxuXG5cbi8qIG5vcm1hbGl6ZSBjb21wb25lbnQgKi9cbmltcG9ydCBub3JtYWxpemVyIGZyb20gXCIhLi4vLi4vLi4vLi4vLi4vbm9kZV9tb2R1bGVzL3Z1ZS1sb2FkZXIvbGliL3J1bnRpbWUvY29tcG9uZW50Tm9ybWFsaXplci5qc1wiXG52YXIgY29tcG9uZW50ID0gbm9ybWFsaXplcihcbiAgc2NyaXB0LFxuICByZW5kZXIsXG4gIHN0YXRpY1JlbmRlckZucyxcbiAgZmFsc2UsXG4gIG51bGwsXG4gIG51bGwsXG4gIG51bGxcbiAgXG4pXG5cbi8qIGhvdCByZWxvYWQgKi9cbmlmIChtb2R1bGUuaG90KSB7XG4gIHZhciBhcGkgPSByZXF1aXJlKFwiL1VzZXJzL21ha3NpbS9NeVByb2plY3RzL2pvaG5jbXM5LmxvY2FsL25vZGVfbW9kdWxlcy92dWUtaG90LXJlbG9hZC1hcGkvZGlzdC9pbmRleC5qc1wiKVxuICBhcGkuaW5zdGFsbChyZXF1aXJlKCd2dWUnKSlcbiAgaWYgKGFwaS5jb21wYXRpYmxlKSB7XG4gICAgbW9kdWxlLmhvdC5hY2NlcHQoKVxuICAgIGlmICghYXBpLmlzUmVjb3JkZWQoJzUyNjc1M2U2JykpIHtcbiAgICAgIGFwaS5jcmVhdGVSZWNvcmQoJzUyNjc1M2U2JywgY29tcG9uZW50Lm9wdGlvbnMpXG4gICAgfSBlbHNlIHtcbiAgICAgIGFwaS5yZWxvYWQoJzUyNjc1M2U2JywgY29tcG9uZW50Lm9wdGlvbnMpXG4gICAgfVxuICAgIG1vZHVsZS5ob3QuYWNjZXB0KFwiLi9Db21tZW50c0NvbXBvbmVudC52dWU/dnVlJnR5cGU9dGVtcGxhdGUmaWQ9NTI2NzUzZTYmXCIsIGZ1bmN0aW9uICgpIHtcbiAgICAgIGFwaS5yZXJlbmRlcignNTI2NzUzZTYnLCB7XG4gICAgICAgIHJlbmRlcjogcmVuZGVyLFxuICAgICAgICBzdGF0aWNSZW5kZXJGbnM6IHN0YXRpY1JlbmRlckZuc1xuICAgICAgfSlcbiAgICB9KVxuICB9XG59XG5jb21wb25lbnQub3B0aW9ucy5fX2ZpbGUgPSBcInRoZW1lcy9kZWZhdWx0L3NyYy9qcy9jb21wb25lbnRzL0NvbW1lbnRzQ29tcG9uZW50LnZ1ZVwiXG5leHBvcnQgZGVmYXVsdCBjb21wb25lbnQuZXhwb3J0cyIsImltcG9ydCB7IHJlbmRlciwgc3RhdGljUmVuZGVyRm5zIH0gZnJvbSBcIi4vTGlrZXNDb21wb25lbnQudnVlP3Z1ZSZ0eXBlPXRlbXBsYXRlJmlkPTNmOGIxYzc0JlwiXG5pbXBvcnQgc2NyaXB0IGZyb20gXCIuL0xpa2VzQ29tcG9uZW50LnZ1ZT92dWUmdHlwZT1zY3JpcHQmbGFuZz1qcyZcIlxuZXhwb3J0ICogZnJvbSBcIi4vTGlrZXNDb21wb25lbnQudnVlP3Z1ZSZ0eXBlPXNjcmlwdCZsYW5nPWpzJlwiXG5cblxuLyogbm9ybWFsaXplIGNvbXBvbmVudCAqL1xuaW1wb3J0IG5vcm1hbGl6ZXIgZnJvbSBcIiEuLi8uLi8uLi8uLi8uLi9ub2RlX21vZHVsZXMvdnVlLWxvYWRlci9saWIvcnVudGltZS9jb21wb25lbnROb3JtYWxpemVyLmpzXCJcbnZhciBjb21wb25lbnQgPSBub3JtYWxpemVyKFxuICBzY3JpcHQsXG4gIHJlbmRlcixcbiAgc3RhdGljUmVuZGVyRm5zLFxuICBmYWxzZSxcbiAgbnVsbCxcbiAgbnVsbCxcbiAgbnVsbFxuICBcbilcblxuLyogaG90IHJlbG9hZCAqL1xuaWYgKG1vZHVsZS5ob3QpIHtcbiAgdmFyIGFwaSA9IHJlcXVpcmUoXCIvVXNlcnMvbWFrc2ltL015UHJvamVjdHMvam9obmNtczkubG9jYWwvbm9kZV9tb2R1bGVzL3Z1ZS1ob3QtcmVsb2FkLWFwaS9kaXN0L2luZGV4LmpzXCIpXG4gIGFwaS5pbnN0YWxsKHJlcXVpcmUoJ3Z1ZScpKVxuICBpZiAoYXBpLmNvbXBhdGlibGUpIHtcbiAgICBtb2R1bGUuaG90LmFjY2VwdCgpXG4gICAgaWYgKCFhcGkuaXNSZWNvcmRlZCgnM2Y4YjFjNzQnKSkge1xuICAgICAgYXBpLmNyZWF0ZVJlY29yZCgnM2Y4YjFjNzQnLCBjb21wb25lbnQub3B0aW9ucylcbiAgICB9IGVsc2Uge1xuICAgICAgYXBpLnJlbG9hZCgnM2Y4YjFjNzQnLCBjb21wb25lbnQub3B0aW9ucylcbiAgICB9XG4gICAgbW9kdWxlLmhvdC5hY2NlcHQoXCIuL0xpa2VzQ29tcG9uZW50LnZ1ZT92dWUmdHlwZT10ZW1wbGF0ZSZpZD0zZjhiMWM3NCZcIiwgZnVuY3Rpb24gKCkge1xuICAgICAgYXBpLnJlcmVuZGVyKCczZjhiMWM3NCcsIHtcbiAgICAgICAgcmVuZGVyOiByZW5kZXIsXG4gICAgICAgIHN0YXRpY1JlbmRlckZuczogc3RhdGljUmVuZGVyRm5zXG4gICAgICB9KVxuICAgIH0pXG4gIH1cbn1cbmNvbXBvbmVudC5vcHRpb25zLl9fZmlsZSA9IFwidGhlbWVzL2RlZmF1bHQvc3JjL2pzL2NvbXBvbmVudHMvTGlrZXNDb21wb25lbnQudnVlXCJcbmV4cG9ydCBkZWZhdWx0IGNvbXBvbmVudC5leHBvcnRzIiwiaW1wb3J0IG1vZCBmcm9tIFwiLSEuLi8uLi8uLi8uLi8uLi9ub2RlX21vZHVsZXMvYmFiZWwtbG9hZGVyL2xpYi9pbmRleC5qcz8/Y2xvbmVkUnVsZVNldC01WzBdLnJ1bGVzWzBdLnVzZVswXSEuLi8uLi8uLi8uLi8uLi9ub2RlX21vZHVsZXMvdnVlLWxvYWRlci9saWIvaW5kZXguanM/P3Z1ZS1sb2FkZXItb3B0aW9ucyEuL0NrZWRpdG9ySW5wdXRDb21wb25lbnQudnVlP3Z1ZSZ0eXBlPXNjcmlwdCZsYW5nPWpzJlwiOyBleHBvcnQgZGVmYXVsdCBtb2Q7IGV4cG9ydCAqIGZyb20gXCItIS4uLy4uLy4uLy4uLy4uL25vZGVfbW9kdWxlcy9iYWJlbC1sb2FkZXIvbGliL2luZGV4LmpzPz9jbG9uZWRSdWxlU2V0LTVbMF0ucnVsZXNbMF0udXNlWzBdIS4uLy4uLy4uLy4uLy4uL25vZGVfbW9kdWxlcy92dWUtbG9hZGVyL2xpYi9pbmRleC5qcz8/dnVlLWxvYWRlci1vcHRpb25zIS4vQ2tlZGl0b3JJbnB1dENvbXBvbmVudC52dWU/dnVlJnR5cGU9c2NyaXB0Jmxhbmc9anMmXCIiLCJpbXBvcnQgbW9kIGZyb20gXCItIS4uLy4uLy4uLy4uLy4uL25vZGVfbW9kdWxlcy9iYWJlbC1sb2FkZXIvbGliL2luZGV4LmpzPz9jbG9uZWRSdWxlU2V0LTVbMF0ucnVsZXNbMF0udXNlWzBdIS4uLy4uLy4uLy4uLy4uL25vZGVfbW9kdWxlcy92dWUtbG9hZGVyL2xpYi9pbmRleC5qcz8/dnVlLWxvYWRlci1vcHRpb25zIS4vQ29tbWVudHNDb21wb25lbnQudnVlP3Z1ZSZ0eXBlPXNjcmlwdCZsYW5nPWpzJlwiOyBleHBvcnQgZGVmYXVsdCBtb2Q7IGV4cG9ydCAqIGZyb20gXCItIS4uLy4uLy4uLy4uLy4uL25vZGVfbW9kdWxlcy9iYWJlbC1sb2FkZXIvbGliL2luZGV4LmpzPz9jbG9uZWRSdWxlU2V0LTVbMF0ucnVsZXNbMF0udXNlWzBdIS4uLy4uLy4uLy4uLy4uL25vZGVfbW9kdWxlcy92dWUtbG9hZGVyL2xpYi9pbmRleC5qcz8/dnVlLWxvYWRlci1vcHRpb25zIS4vQ29tbWVudHNDb21wb25lbnQudnVlP3Z1ZSZ0eXBlPXNjcmlwdCZsYW5nPWpzJlwiIiwiaW1wb3J0IG1vZCBmcm9tIFwiLSEuLi8uLi8uLi8uLi8uLi9ub2RlX21vZHVsZXMvYmFiZWwtbG9hZGVyL2xpYi9pbmRleC5qcz8/Y2xvbmVkUnVsZVNldC01WzBdLnJ1bGVzWzBdLnVzZVswXSEuLi8uLi8uLi8uLi8uLi9ub2RlX21vZHVsZXMvdnVlLWxvYWRlci9saWIvaW5kZXguanM/P3Z1ZS1sb2FkZXItb3B0aW9ucyEuL0xpa2VzQ29tcG9uZW50LnZ1ZT92dWUmdHlwZT1zY3JpcHQmbGFuZz1qcyZcIjsgZXhwb3J0IGRlZmF1bHQgbW9kOyBleHBvcnQgKiBmcm9tIFwiLSEuLi8uLi8uLi8uLi8uLi9ub2RlX21vZHVsZXMvYmFiZWwtbG9hZGVyL2xpYi9pbmRleC5qcz8/Y2xvbmVkUnVsZVNldC01WzBdLnJ1bGVzWzBdLnVzZVswXSEuLi8uLi8uLi8uLi8uLi9ub2RlX21vZHVsZXMvdnVlLWxvYWRlci9saWIvaW5kZXguanM/P3Z1ZS1sb2FkZXItb3B0aW9ucyEuL0xpa2VzQ29tcG9uZW50LnZ1ZT92dWUmdHlwZT1zY3JpcHQmbGFuZz1qcyZcIiIsInZhciByZW5kZXIgPSBmdW5jdGlvbigpIHtcbiAgdmFyIF92bSA9IHRoaXNcbiAgdmFyIF9oID0gX3ZtLiRjcmVhdGVFbGVtZW50XG4gIHZhciBfYyA9IF92bS5fc2VsZi5fYyB8fCBfaFxuICByZXR1cm4gX2MoXG4gICAgXCJkaXZcIixcbiAgICBbXG4gICAgICBfYyhcImRpdlwiLCB7IHN0YXRpY0NsYXNzOiBcImZvcm0tZ3JvdXBcIiB9LCBbXG4gICAgICAgIF9jKFwibGFiZWxcIiwgeyBhdHRyczogeyBmb3I6IF92bS5pZCB9IH0sIFtfdm0uX3YoX3ZtLl9zKF92bS5sYWJlbCkpXSksXG4gICAgICAgIF92bS5fdihcIiBcIiksXG4gICAgICAgIF9jKFwidGV4dGFyZWFcIiwge1xuICAgICAgICAgIGRpcmVjdGl2ZXM6IFtcbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgbmFtZTogXCJtb2RlbFwiLFxuICAgICAgICAgICAgICByYXdOYW1lOiBcInYtbW9kZWxcIixcbiAgICAgICAgICAgICAgdmFsdWU6IF92bS5tb2RlbF92YWx1ZSxcbiAgICAgICAgICAgICAgZXhwcmVzc2lvbjogXCJtb2RlbF92YWx1ZVwiXG4gICAgICAgICAgICB9XG4gICAgICAgICAgXSxcbiAgICAgICAgICBzdGF0aWNDbGFzczogXCJmb3JtLWNvbnRyb2xcIixcbiAgICAgICAgICBjbGFzczogX3ZtLmNsYXNzZXMgKyAoX3ZtLmVycm9ycyA/IFwiaXMtaW52YWxpZFwiIDogXCJcIiksXG4gICAgICAgICAgYXR0cnM6IHsgbmFtZTogX3ZtLm5hbWUsIGlkOiBfdm0uaWQgfSxcbiAgICAgICAgICBkb21Qcm9wczogeyB2YWx1ZTogX3ZtLm1vZGVsX3ZhbHVlIH0sXG4gICAgICAgICAgb246IHtcbiAgICAgICAgICAgIGlucHV0OiBmdW5jdGlvbigkZXZlbnQpIHtcbiAgICAgICAgICAgICAgaWYgKCRldmVudC50YXJnZXQuY29tcG9zaW5nKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuXG4gICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgX3ZtLm1vZGVsX3ZhbHVlID0gJGV2ZW50LnRhcmdldC52YWx1ZVxuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cbiAgICAgICAgfSksXG4gICAgICAgIF92bS5fdihcIiBcIiksXG4gICAgICAgIF92bS5lcnJvcnNcbiAgICAgICAgICA/IF9jKFwiZGl2XCIsIHsgc3RhdGljQ2xhc3M6IFwiaW52YWxpZC1mZWVkYmFjayBkLWJsb2NrXCIgfSwgW1xuICAgICAgICAgICAgICBfdm0uX3YoX3ZtLl9zKF92bS5lcnJvcnMpKVxuICAgICAgICAgICAgXSlcbiAgICAgICAgICA6IF92bS5fZSgpXG4gICAgICBdKSxcbiAgICAgIF92bS5fdihcIiBcIiksXG4gICAgICBfdm0uX2woX3ZtLmF0dGFjaGVkX2ZpbGVzLCBmdW5jdGlvbihmaWxlKSB7XG4gICAgICAgIHJldHVybiBfYyhcImRpdlwiLCBbXG4gICAgICAgICAgX2MoXCJpbnB1dFwiLCB7XG4gICAgICAgICAgICBkaXJlY3RpdmVzOiBbXG4gICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBuYW1lOiBcIm1vZGVsXCIsXG4gICAgICAgICAgICAgICAgcmF3TmFtZTogXCJ2LW1vZGVsXCIsXG4gICAgICAgICAgICAgICAgdmFsdWU6IGZpbGUuaWQsXG4gICAgICAgICAgICAgICAgZXhwcmVzc2lvbjogXCJmaWxlLmlkXCJcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgXSxcbiAgICAgICAgICAgIGF0dHJzOiB7IHR5cGU6IFwiaGlkZGVuXCIsIG5hbWU6IFwiYXR0YWNoZWRfZmlsZXNbXVwiIH0sXG4gICAgICAgICAgICBkb21Qcm9wczogeyB2YWx1ZTogZmlsZS5pZCB9LFxuICAgICAgICAgICAgb246IHtcbiAgICAgICAgICAgICAgaW5wdXQ6IGZ1bmN0aW9uKCRldmVudCkge1xuICAgICAgICAgICAgICAgIGlmICgkZXZlbnQudGFyZ2V0LmNvbXBvc2luZykge1xuICAgICAgICAgICAgICAgICAgcmV0dXJuXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIF92bS4kc2V0KGZpbGUsIFwiaWRcIiwgJGV2ZW50LnRhcmdldC52YWx1ZSlcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICAgIH0pXG4gICAgICAgIF0pXG4gICAgICB9KVxuICAgIF0sXG4gICAgMlxuICApXG59XG52YXIgc3RhdGljUmVuZGVyRm5zID0gW11cbnJlbmRlci5fd2l0aFN0cmlwcGVkID0gdHJ1ZVxuXG5leHBvcnQgeyByZW5kZXIsIHN0YXRpY1JlbmRlckZucyB9IiwidmFyIHJlbmRlciA9IGZ1bmN0aW9uKCkge1xuICB2YXIgX3ZtID0gdGhpc1xuICB2YXIgX2ggPSBfdm0uJGNyZWF0ZUVsZW1lbnRcbiAgdmFyIF9jID0gX3ZtLl9zZWxmLl9jIHx8IF9oXG4gIHJldHVybiBfYyhcbiAgICBcImRpdlwiLFxuICAgIHsgc3RhdGljQ2xhc3M6IFwibXQtNCBjb21tZW50cy1saXN0XCIgfSxcbiAgICBbXG4gICAgICBfYyhcImgzXCIsIHsgc3RhdGljQ2xhc3M6IFwiZnctYm9sZFwiIH0sIFtcbiAgICAgICAgX3ZtLl92KF92bS5fcyhfdm0uX18oXCJjb21tZW50c1wiKSkgKyBcIiBcIiksXG4gICAgICAgIF92bS5tZXNzYWdlcy50b3RhbCA+IDBcbiAgICAgICAgICA/IF9jKFwic3BhblwiLCB7IHN0YXRpY0NsYXNzOiBcInRleHQtc3VjY2Vzc1wiIH0sIFtcbiAgICAgICAgICAgICAgX3ZtLl92KF92bS5fcyhfdm0ubWVzc2FnZXMudG90YWwpKVxuICAgICAgICAgICAgXSlcbiAgICAgICAgICA6IF92bS5fZSgpXG4gICAgICBdKSxcbiAgICAgIF92bS5fdihcIiBcIiksXG4gICAgICBfdm0ubWVzc2FnZXMuZGF0YSAmJiBfdm0ubWVzc2FnZXMuZGF0YS5sZW5ndGggPCAxXG4gICAgICAgID8gX2MoXCJkaXZcIiwgeyBzdGF0aWNDbGFzczogXCJhbGVydCBhbGVydC1pbmZvXCIgfSwgW1xuICAgICAgICAgICAgX3ZtLl92KF92bS5fcyhfdm0uX18oXCJlbXB0eV9saXN0XCIpKSlcbiAgICAgICAgICBdKVxuICAgICAgICA6IF92bS5fZSgpLFxuICAgICAgX3ZtLl92KFwiIFwiKSxcbiAgICAgIF92bS5fbChfdm0ubWVzc2FnZXMuZGF0YSwgZnVuY3Rpb24obWVzc2FnZSkge1xuICAgICAgICByZXR1cm4gX2MoXCJkaXZcIiwgeyBzdGF0aWNDbGFzczogXCJuZXdfcG9zdC1pdGVtXCIgfSwgW1xuICAgICAgICAgIF9jKFxuICAgICAgICAgICAgXCJkaXZcIixcbiAgICAgICAgICAgIHsgc3RhdGljQ2xhc3M6IFwibmV3X3Bvc3QtaGVhZGVyIGQtZmxleCBqdXN0aWZ5LWNvbnRlbnQtYmV0d2VlblwiIH0sXG4gICAgICAgICAgICBbXG4gICAgICAgICAgICAgIF9jKFwiZGl2XCIsIHsgc3RhdGljQ2xhc3M6IFwicG9zdC11c2VyXCIgfSwgW1xuICAgICAgICAgICAgICAgIG1lc3NhZ2UudXNlci5wcm9maWxlX3VybFxuICAgICAgICAgICAgICAgICAgPyBfYyhcImFcIiwgeyBhdHRyczogeyBocmVmOiBtZXNzYWdlLnVzZXIucHJvZmlsZV91cmwgfSB9LCBbXG4gICAgICAgICAgICAgICAgICAgICAgX2MoXCJkaXZcIiwgeyBzdGF0aWNDbGFzczogXCJhdmF0YXJcIiB9LCBbXG4gICAgICAgICAgICAgICAgICAgICAgICBfYyhcImltZ1wiLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIHN0YXRpY0NsYXNzOiBcImltZy1mbHVpZFwiLFxuICAgICAgICAgICAgICAgICAgICAgICAgICBhdHRyczogeyBzcmM6IG1lc3NhZ2UudXNlci5hdmF0YXIsIGFsdDogXCIuXCIgfVxuICAgICAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgICBdKVxuICAgICAgICAgICAgICAgICAgICBdKVxuICAgICAgICAgICAgICAgICAgOiBfdm0uX2UoKSxcbiAgICAgICAgICAgICAgICBfdm0uX3YoXCIgXCIpLFxuICAgICAgICAgICAgICAgIF9jKFwic3BhblwiLCB7XG4gICAgICAgICAgICAgICAgICBzdGF0aWNDbGFzczogXCJ1c2VyLXN0YXR1cyBzaGFkb3dcIixcbiAgICAgICAgICAgICAgICAgIGNsYXNzOiBtZXNzYWdlLnVzZXIuaXNfb25saW5lID8gXCJvbmxpbmVcIiA6IFwib2ZmbGluZVwiXG4gICAgICAgICAgICAgICAgfSksXG4gICAgICAgICAgICAgICAgX3ZtLl92KFwiIFwiKSxcbiAgICAgICAgICAgICAgICBtZXNzYWdlLnVzZXIucmlnaHRzX25hbWVcbiAgICAgICAgICAgICAgICAgID8gX2MoXG4gICAgICAgICAgICAgICAgICAgICAgXCJkaXZcIixcbiAgICAgICAgICAgICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgICAgICAgICBzdGF0aWNDbGFzczogXCJwb3N0LW9mLXVzZXJcIixcbiAgICAgICAgICAgICAgICAgICAgICAgIGF0dHJzOiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIFwiZGF0YS1icy10b2dnbGVcIjogXCJ0b29sdGlwXCIsXG4gICAgICAgICAgICAgICAgICAgICAgICAgIFwiZGF0YS1icy1wbGFjZW1lbnRcIjogXCJ0b3BcIixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgXCJkYXRhLWJzLWh0bWxcIjogXCJ0cnVlXCIsXG4gICAgICAgICAgICAgICAgICAgICAgICAgIHRpdGxlOiBtZXNzYWdlLnVzZXIucmlnaHRzX25hbWVcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgICAgICAgIFtcbiAgICAgICAgICAgICAgICAgICAgICAgIF9jKFwic3ZnXCIsIHsgc3RhdGljQ2xhc3M6IFwiaWNvbi1wb3N0XCIgfSwgW1xuICAgICAgICAgICAgICAgICAgICAgICAgICBfYyhcInVzZVwiLCB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgYXR0cnM6IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIFwieGxpbms6aHJlZlwiOlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBcIi90aGVtZXMvZGVmYXVsdC9hc3NldHMvaWNvbnMvc3ByaXRlLnN2Zz8jY2hlY2tcIlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgICAgIF0pXG4gICAgICAgICAgICAgICAgICAgICAgXVxuICAgICAgICAgICAgICAgICAgICApXG4gICAgICAgICAgICAgICAgICA6IF92bS5fZSgpXG4gICAgICAgICAgICAgIF0pLFxuICAgICAgICAgICAgICBfdm0uX3YoXCIgXCIpLFxuICAgICAgICAgICAgICBfYyhcbiAgICAgICAgICAgICAgICBcImRpdlwiLFxuICAgICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAgIHN0YXRpY0NsYXNzOlxuICAgICAgICAgICAgICAgICAgICBcImZsZXgtZ3Jvdy0xIHBvc3QtdXNlciBkLWZsZXggZmxleC13cmFwIG92ZXJmbG93LWhpZGRlbiBkLWZsZXggYWxpZ24taXRlbXMtY2VudGVyXCJcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIFtcbiAgICAgICAgICAgICAgICAgIF9jKFwiZGl2XCIsIHsgc3RhdGljQ2xhc3M6IFwidy0xMDBcIiB9LCBbXG4gICAgICAgICAgICAgICAgICAgIG1lc3NhZ2UudXNlci5wcm9maWxlX3VybFxuICAgICAgICAgICAgICAgICAgICAgID8gX2MoXCJhXCIsIHsgYXR0cnM6IHsgaHJlZjogbWVzc2FnZS51c2VyLnByb2ZpbGVfdXJsIH0gfSwgW1xuICAgICAgICAgICAgICAgICAgICAgICAgICBfYyhcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBcInNwYW5cIixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB7IHN0YXRpY0NsYXNzOiBcInVzZXItbmFtZSBkLWlubGluZSBtZS0yXCIgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBbX3ZtLl92KF92bS5fcyhtZXNzYWdlLnVzZXIudXNlcl9uYW1lKSldXG4gICAgICAgICAgICAgICAgICAgICAgICAgIClcbiAgICAgICAgICAgICAgICAgICAgICAgIF0pXG4gICAgICAgICAgICAgICAgICAgICAgOiBfdm0uX2UoKSxcbiAgICAgICAgICAgICAgICAgICAgX3ZtLl92KFwiIFwiKSxcbiAgICAgICAgICAgICAgICAgICAgIW1lc3NhZ2UudXNlci5wcm9maWxlX3VybFxuICAgICAgICAgICAgICAgICAgICAgID8gX2MoXCJkaXZcIiwgeyBzdGF0aWNDbGFzczogXCJ1c2VyLW5hbWUgZC1pbmxpbmUgbWUtMlwiIH0sIFtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgX3ZtLl92KF92bS5fcyhtZXNzYWdlLnVzZXIudXNlcl9uYW1lKSlcbiAgICAgICAgICAgICAgICAgICAgICAgIF0pXG4gICAgICAgICAgICAgICAgICAgICAgOiBfdm0uX2UoKSxcbiAgICAgICAgICAgICAgICAgICAgX3ZtLl92KFwiIFwiKSxcbiAgICAgICAgICAgICAgICAgICAgX2MoXCJzcGFuXCIsIHsgc3RhdGljQ2xhc3M6IFwicG9zdC1tZXRhIGQtaW5saW5lIG1lLTJcIiB9LCBbXG4gICAgICAgICAgICAgICAgICAgICAgX3ZtLl92KF92bS5fcyhtZXNzYWdlLmNyZWF0ZWRfYXQpKVxuICAgICAgICAgICAgICAgICAgICBdKVxuICAgICAgICAgICAgICAgICAgXSksXG4gICAgICAgICAgICAgICAgICBfdm0uX3YoXCIgXCIpLFxuICAgICAgICAgICAgICAgICAgbWVzc2FnZS51c2VyLnN0YXR1c1xuICAgICAgICAgICAgICAgICAgICA/IF9jKFxuICAgICAgICAgICAgICAgICAgICAgICAgXCJkaXZcIixcbiAgICAgICAgICAgICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgc3RhdGljQ2xhc3M6XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgXCJvdmVyZmxvdy1oaWRkZW4gdGV4dC1ub3dyYXAgdGV4dC1kYXJrLWJyb3duIG92ZXJmbG93LWVsbGlwc2lzIHNtYWxsXCJcbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBbXG4gICAgICAgICAgICAgICAgICAgICAgICAgIF9jKFwic3BhblwiLCB7IHN0YXRpY0NsYXNzOiBcImZ3LWJvbGRcIiB9LCBbXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgX3ZtLl92KF92bS5fcyhtZXNzYWdlLnVzZXIuc3RhdHVzKSlcbiAgICAgICAgICAgICAgICAgICAgICAgICAgXSlcbiAgICAgICAgICAgICAgICAgICAgICAgIF1cbiAgICAgICAgICAgICAgICAgICAgICApXG4gICAgICAgICAgICAgICAgICAgIDogX3ZtLl9lKClcbiAgICAgICAgICAgICAgICBdXG4gICAgICAgICAgICAgIClcbiAgICAgICAgICAgIF1cbiAgICAgICAgICApLFxuICAgICAgICAgIF92bS5fdihcIiBcIiksXG4gICAgICAgICAgX2MoXCJkaXZcIiwge1xuICAgICAgICAgICAgc3RhdGljQ2xhc3M6IFwicG9zdC1ib2R5IHB0LTIgcGItMlwiLFxuICAgICAgICAgICAgZG9tUHJvcHM6IHsgaW5uZXJIVE1MOiBfdm0uX3MobWVzc2FnZS50ZXh0KSB9XG4gICAgICAgICAgfSksXG4gICAgICAgICAgX3ZtLl92KFwiIFwiKSxcbiAgICAgICAgICBfYyhcbiAgICAgICAgICAgIFwiZGl2XCIsXG4gICAgICAgICAgICB7IHN0YXRpY0NsYXNzOiBcInBvc3QtZm9vdGVyIGQtZmxleCBqdXN0aWZ5LWNvbnRlbnQtYmV0d2VlblwiIH0sXG4gICAgICAgICAgICBbXG4gICAgICAgICAgICAgIF9jKFwiZGl2XCIsIHsgc3RhdGljQ2xhc3M6IFwib3ZlcmZsb3ctaGlkZGVuXCIgfSwgW1xuICAgICAgICAgICAgICAgIG1lc3NhZ2UuaXBcbiAgICAgICAgICAgICAgICAgID8gX2MoXCJkaXZcIiwgeyBzdGF0aWNDbGFzczogXCJwb3N0LW1ldGEgZC1mbGV4XCIgfSwgW1xuICAgICAgICAgICAgICAgICAgICAgIF9jKFwiZGl2XCIsIHsgc3RhdGljQ2xhc3M6IFwidXNlci1pcCBtZS0yXCIgfSwgW1xuICAgICAgICAgICAgICAgICAgICAgICAgX2MoXCJhXCIsIHsgYXR0cnM6IHsgaHJlZjogbWVzc2FnZS5zZWFyY2hfaXBfdXJsIH0gfSwgW1xuICAgICAgICAgICAgICAgICAgICAgICAgICBfdm0uX3YoX3ZtLl9zKG1lc3NhZ2UuaXApKVxuICAgICAgICAgICAgICAgICAgICAgICAgXSlcbiAgICAgICAgICAgICAgICAgICAgICBdKSxcbiAgICAgICAgICAgICAgICAgICAgICBfdm0uX3YoXCIgXCIpLFxuICAgICAgICAgICAgICAgICAgICAgIF9jKFwiZGl2XCIsIHsgc3RhdGljQ2xhc3M6IFwidXNlcmFnZW50XCIgfSwgW1xuICAgICAgICAgICAgICAgICAgICAgICAgX2MoXCJzcGFuXCIsIFtfdm0uX3YoX3ZtLl9zKG1lc3NhZ2UudXNlcl9hZ2VudCkpXSlcbiAgICAgICAgICAgICAgICAgICAgICBdKVxuICAgICAgICAgICAgICAgICAgICBdKVxuICAgICAgICAgICAgICAgICAgOiBfdm0uX2UoKVxuICAgICAgICAgICAgICBdKSxcbiAgICAgICAgICAgICAgX3ZtLl92KFwiIFwiKSxcbiAgICAgICAgICAgICAgX2MoXCJkaXZcIiwgeyBzdGF0aWNDbGFzczogXCJkLWZsZXhcIiB9LCBbXG4gICAgICAgICAgICAgICAgbWVzc2FnZS5jYW5fcmVwbHlcbiAgICAgICAgICAgICAgICAgID8gX2MoXCJkaXZcIiwgeyBzdGF0aWNDbGFzczogXCJtcy0zXCIgfSwgW1xuICAgICAgICAgICAgICAgICAgICAgIF9jKFxuICAgICAgICAgICAgICAgICAgICAgICAgXCJhXCIsXG4gICAgICAgICAgICAgICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgIGF0dHJzOiB7IGhyZWY6IFwiI1wiIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgIG9uOiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgY2xpY2s6IGZ1bmN0aW9uKCRldmVudCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBfdm0ucmVwbHkobWVzc2FnZSlcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBbX3ZtLl92KF92bS5fcyhfdm0uX18oXCJyZXBseVwiKSkpXVxuICAgICAgICAgICAgICAgICAgICAgIClcbiAgICAgICAgICAgICAgICAgICAgXSlcbiAgICAgICAgICAgICAgICAgIDogX3ZtLl9lKCksXG4gICAgICAgICAgICAgICAgX3ZtLl92KFwiIFwiKSxcbiAgICAgICAgICAgICAgICBtZXNzYWdlLmNhbl9xdW90ZVxuICAgICAgICAgICAgICAgICAgPyBfYyhcImRpdlwiLCB7IHN0YXRpY0NsYXNzOiBcIm1zLTNcIiB9LCBbXG4gICAgICAgICAgICAgICAgICAgICAgX2MoXG4gICAgICAgICAgICAgICAgICAgICAgICBcImFcIixcbiAgICAgICAgICAgICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgYXR0cnM6IHsgaHJlZjogXCIjXCIgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgb246IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjbGljazogZnVuY3Rpb24oJGV2ZW50KSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkZXZlbnQucHJldmVudERlZmF1bHQoKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIF92bS5xdW90ZShtZXNzYWdlKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICAgICAgICAgIFtfdm0uX3YoX3ZtLl9zKF92bS5fXyhcInF1b3RlXCIpKSldXG4gICAgICAgICAgICAgICAgICAgICAgKVxuICAgICAgICAgICAgICAgICAgICBdKVxuICAgICAgICAgICAgICAgICAgOiBfdm0uX2UoKSxcbiAgICAgICAgICAgICAgICBfdm0uX3YoXCIgXCIpLFxuICAgICAgICAgICAgICAgIG1lc3NhZ2UuY2FuX2RlbGV0ZVxuICAgICAgICAgICAgICAgICAgPyBfYyhcImRpdlwiLCB7IHN0YXRpY0NsYXNzOiBcImRyb3Bkb3duIG1zLTNcIiB9LCBbXG4gICAgICAgICAgICAgICAgICAgICAgX2MoXG4gICAgICAgICAgICAgICAgICAgICAgICBcImRpdlwiLFxuICAgICAgICAgICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICAgICAgICBzdGF0aWNDbGFzczogXCJjdXJzb3ItcG9pbnRlclwiLFxuICAgICAgICAgICAgICAgICAgICAgICAgICBhdHRyczoge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIFwiZGF0YS1icy10b2dnbGVcIjogXCJkcm9wZG93blwiLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIFwiYXJpYS1oYXNwb3B1cFwiOiBcInRydWVcIixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBcImFyaWEtZXhwYW5kZWRcIjogXCJmYWxzZVwiXG4gICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICBbXG4gICAgICAgICAgICAgICAgICAgICAgICAgIF9jKFwic3ZnXCIsIHsgc3RhdGljQ2xhc3M6IFwiaWNvbiB0ZXh0LXByaW1hcnlcIiB9LCBbXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgX2MoXCJ1c2VcIiwge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYXR0cnM6IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgXCJ4bGluazpocmVmXCI6XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgXCIvdGhlbWVzL2RlZmF1bHQvYXNzZXRzL2ljb25zL3Nwcml0ZS5zdmc/I21vcmVfaG9yaXpvbnRhbFwiXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgICAgICAgXSlcbiAgICAgICAgICAgICAgICAgICAgICAgIF1cbiAgICAgICAgICAgICAgICAgICAgICApLFxuICAgICAgICAgICAgICAgICAgICAgIF92bS5fdihcIiBcIiksXG4gICAgICAgICAgICAgICAgICAgICAgX2MoXG4gICAgICAgICAgICAgICAgICAgICAgICBcImRpdlwiLFxuICAgICAgICAgICAgICAgICAgICAgICAgeyBzdGF0aWNDbGFzczogXCJkcm9wZG93bi1tZW51IGRyb3Bkb3duLW1lbnUtcmlnaHRcIiB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgW1xuICAgICAgICAgICAgICAgICAgICAgICAgICBfYyhcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBcImFcIixcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICBzdGF0aWNDbGFzczogXCJkcm9wZG93bi1pdGVtXCIsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICBhdHRyczogeyBocmVmOiBcIlwiIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICBvbjoge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjbGljazogZnVuY3Rpb24oJGV2ZW50KSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgJGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICByZXR1cm4gX3ZtLmRlbENvbW1lbnQobWVzc2FnZS5pZClcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgW192bS5fdihfdm0uX3MoX3ZtLl9fKFwiZGVsZXRlXCIpKSldXG4gICAgICAgICAgICAgICAgICAgICAgICAgIClcbiAgICAgICAgICAgICAgICAgICAgICAgIF1cbiAgICAgICAgICAgICAgICAgICAgICApXG4gICAgICAgICAgICAgICAgICAgIF0pXG4gICAgICAgICAgICAgICAgICA6IF92bS5fZSgpXG4gICAgICAgICAgICAgIF0pXG4gICAgICAgICAgICBdXG4gICAgICAgICAgKVxuICAgICAgICBdKVxuICAgICAgfSksXG4gICAgICBfdm0uX3YoXCIgXCIpLFxuICAgICAgX2MoXCJwYWdpbmF0aW9uXCIsIHtcbiAgICAgICAgc3RhdGljQ2xhc3M6IFwibXQtM1wiLFxuICAgICAgICBhdHRyczogeyBkYXRhOiBfdm0ubWVzc2FnZXMgfSxcbiAgICAgICAgb246IHsgXCJwYWdpbmF0aW9uLWNoYW5nZS1wYWdlXCI6IF92bS5nZXRDb21tZW50cyB9XG4gICAgICB9KSxcbiAgICAgIF92bS5fdihcIiBcIiksXG4gICAgICBfdm0uY2FuX3dyaXRlXG4gICAgICAgID8gX2MoXCJkaXZcIiwgeyBzdGF0aWNDbGFzczogXCJtdC00XCIgfSwgW1xuICAgICAgICAgICAgX2MoXCJoM1wiLCB7IHN0YXRpY0NsYXNzOiBcImZ3LWJvbGRcIiB9LCBbXG4gICAgICAgICAgICAgIF92bS5fdihfdm0uX3MoX3ZtLl9fKFwid3JpdGVfY29tbWVudFwiKSkpXG4gICAgICAgICAgICBdKSxcbiAgICAgICAgICAgIF92bS5fdihcIiBcIiksXG4gICAgICAgICAgICBfYyhcbiAgICAgICAgICAgICAgXCJmb3JtXCIsXG4gICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBzdGF0aWNDbGFzczogXCJjb21tZW50LWZvcm1cIixcbiAgICAgICAgICAgICAgICBhdHRyczogeyBhY3Rpb246IFwiXCIgfSxcbiAgICAgICAgICAgICAgICBvbjoge1xuICAgICAgICAgICAgICAgICAgc3VibWl0OiBmdW5jdGlvbigkZXZlbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgJGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIF92bS5zZW5kQ29tbWVudCgkZXZlbnQpXG4gICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICBbXG4gICAgICAgICAgICAgICAgX3ZtLmVycm9yX21lc3NhZ2VcbiAgICAgICAgICAgICAgICAgID8gX2MoXCJkaXZcIiwgeyBzdGF0aWNDbGFzczogXCJkLWZsZXhcIiB9LCBbXG4gICAgICAgICAgICAgICAgICAgICAgX2MoXG4gICAgICAgICAgICAgICAgICAgICAgICBcImRpdlwiLFxuICAgICAgICAgICAgICAgICAgICAgICAgeyBzdGF0aWNDbGFzczogXCJhbGVydCBhbGVydC1kYW5nZXIgZC1pbmxpbmVcIiB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgW192bS5fdihfdm0uX3MoX3ZtLmVycm9yX21lc3NhZ2UpKV1cbiAgICAgICAgICAgICAgICAgICAgICApXG4gICAgICAgICAgICAgICAgICAgIF0pXG4gICAgICAgICAgICAgICAgICA6IF92bS5fZSgpLFxuICAgICAgICAgICAgICAgIF92bS5fdihcIiBcIiksXG4gICAgICAgICAgICAgICAgX3ZtLmNvbW1lbnRfYWRkZWRfbWVzc2FnZVxuICAgICAgICAgICAgICAgICAgPyBfYyhcImRpdlwiLCB7IHN0YXRpY0NsYXNzOiBcImQtZmxleFwiIH0sIFtcbiAgICAgICAgICAgICAgICAgICAgICBfYyhcbiAgICAgICAgICAgICAgICAgICAgICAgIFwiZGl2XCIsXG4gICAgICAgICAgICAgICAgICAgICAgICB7IHN0YXRpY0NsYXNzOiBcImFsZXJ0IGFsZXJ0LXN1Y2Nlc3MgZC1pbmxpbmVcIiB9LFxuICAgICAgICAgICAgICAgICAgICAgICAgW192bS5fdihfdm0uX3MoX3ZtLmNvbW1lbnRfYWRkZWRfbWVzc2FnZSkpXVxuICAgICAgICAgICAgICAgICAgICAgIClcbiAgICAgICAgICAgICAgICAgICAgXSlcbiAgICAgICAgICAgICAgICAgIDogX3ZtLl9lKCksXG4gICAgICAgICAgICAgICAgX3ZtLl92KFwiIFwiKSxcbiAgICAgICAgICAgICAgICBfYyhcImRpdlwiLCB7IHN0YXRpY1N0eWxlOiB7IFwibWF4LXdpZHRoXCI6IFwiODAwcHhcIiB9IH0sIFtcbiAgICAgICAgICAgICAgICAgIF9jKFwiZGl2XCIsIHsgc3RhdGljQ2xhc3M6IFwiZm9ybS1ncm91cFwiIH0sIFtcbiAgICAgICAgICAgICAgICAgICAgX2MoXCJ0ZXh0YXJlYVwiLCB7XG4gICAgICAgICAgICAgICAgICAgICAgZGlyZWN0aXZlczogW1xuICAgICAgICAgICAgICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgICAgICAgICAgICBuYW1lOiBcIm1vZGVsXCIsXG4gICAgICAgICAgICAgICAgICAgICAgICAgIHJhd05hbWU6IFwidi1tb2RlbFwiLFxuICAgICAgICAgICAgICAgICAgICAgICAgICB2YWx1ZTogX3ZtLmNvbW1lbnRfdGV4dCxcbiAgICAgICAgICAgICAgICAgICAgICAgICAgZXhwcmVzc2lvbjogXCJjb21tZW50X3RleHRcIlxuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgIF0sXG4gICAgICAgICAgICAgICAgICAgICAgc3RhdGljQ2xhc3M6IFwiZm9ybS1jb250cm9sXCIsXG4gICAgICAgICAgICAgICAgICAgICAgYXR0cnM6IHsgbmFtZTogXCJ0ZXh0XCIsIGlkOiBcImNvbW1lbnRfdGV4dFwiLCByZXF1aXJlZDogXCJcIiB9LFxuICAgICAgICAgICAgICAgICAgICAgIGRvbVByb3BzOiB7IHZhbHVlOiBfdm0uY29tbWVudF90ZXh0IH0sXG4gICAgICAgICAgICAgICAgICAgICAgb246IHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGlucHV0OiBmdW5jdGlvbigkZXZlbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCRldmVudC50YXJnZXQuY29tcG9zaW5nKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuXG4gICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgX3ZtLmNvbW1lbnRfdGV4dCA9ICRldmVudC50YXJnZXQudmFsdWVcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICBdKVxuICAgICAgICAgICAgICAgIF0pLFxuICAgICAgICAgICAgICAgIF92bS5fdihcIiBcIiksXG4gICAgICAgICAgICAgICAgX2MoXCJkaXZcIiwgeyBzdGF0aWNDbGFzczogXCJtdC0yXCIgfSwgW1xuICAgICAgICAgICAgICAgICAgX2MoXG4gICAgICAgICAgICAgICAgICAgIFwiYnV0dG9uXCIsXG4gICAgICAgICAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICAgICAgICBzdGF0aWNDbGFzczogXCJidG4gYnRuLXByaW1hcnlcIixcbiAgICAgICAgICAgICAgICAgICAgICBhdHRyczoge1xuICAgICAgICAgICAgICAgICAgICAgICAgdHlwZTogXCJzdWJtaXRcIixcbiAgICAgICAgICAgICAgICAgICAgICAgIG5hbWU6IFwic3VibWl0XCIsXG4gICAgICAgICAgICAgICAgICAgICAgICB2YWx1ZTogXCIxXCIsXG4gICAgICAgICAgICAgICAgICAgICAgICBkaXNhYmxlZDogX3ZtLmxvYWRpbmdcbiAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgICAgIFtcbiAgICAgICAgICAgICAgICAgICAgICBfdm0ubG9hZGluZ1xuICAgICAgICAgICAgICAgICAgICAgICAgPyBfYyhcInNwYW5cIiwge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHN0YXRpY0NsYXNzOiBcInNwaW5uZXItYm9yZGVyIHNwaW5uZXItYm9yZGVyLXNtXCIsXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgYXR0cnM6IHsgcm9sZTogXCJzdGF0dXNcIiwgXCJhcmlhLWhpZGRlblwiOiBcInRydWVcIiB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICAgICAgICAgICAgICA6IF92bS5fZSgpLFxuICAgICAgICAgICAgICAgICAgICAgIF92bS5fdihcbiAgICAgICAgICAgICAgICAgICAgICAgIFwiXFxuICAgICAgICAgIFwiICsgX3ZtLl9zKF92bS5fXyhcInNlbmRcIikpICsgXCJcXG4gICAgICAgIFwiXG4gICAgICAgICAgICAgICAgICAgICAgKVxuICAgICAgICAgICAgICAgICAgICBdXG4gICAgICAgICAgICAgICAgICApLFxuICAgICAgICAgICAgICAgICAgX3ZtLl92KFwiIFwiKSxcbiAgICAgICAgICAgICAgICAgIF9jKFwiZGl2XCIpXG4gICAgICAgICAgICAgICAgXSlcbiAgICAgICAgICAgICAgXVxuICAgICAgICAgICAgKVxuICAgICAgICAgIF0pXG4gICAgICAgIDogX3ZtLl9lKClcbiAgICBdLFxuICAgIDJcbiAgKVxufVxudmFyIHN0YXRpY1JlbmRlckZucyA9IFtdXG5yZW5kZXIuX3dpdGhTdHJpcHBlZCA9IHRydWVcblxuZXhwb3J0IHsgcmVuZGVyLCBzdGF0aWNSZW5kZXJGbnMgfSIsInZhciByZW5kZXIgPSBmdW5jdGlvbigpIHtcbiAgdmFyIF92bSA9IHRoaXNcbiAgdmFyIF9oID0gX3ZtLiRjcmVhdGVFbGVtZW50XG4gIHZhciBfYyA9IF92bS5fc2VsZi5fYyB8fCBfaFxuICByZXR1cm4gX2MoXCJkaXZcIiwgeyBzdGF0aWNDbGFzczogXCJwb3NpdGlvbi1yZWxhdGl2ZVwiIH0sIFtcbiAgICBfdm0ubG9hZGluZ1xuICAgICAgPyBfYyhcbiAgICAgICAgICBcImRpdlwiLFxuICAgICAgICAgIHtcbiAgICAgICAgICAgIHN0YXRpY0NsYXNzOlxuICAgICAgICAgICAgICBcImQtZmxleCBqdXN0aWZ5LWNvbnRlbnQtY2VudGVyIHBvc2l0aW9uLWFic29sdXRlIHctMTAwIHZvdGUtcHJlbG9hZGVyXCJcbiAgICAgICAgICB9LFxuICAgICAgICAgIFtfdm0uX20oMCldXG4gICAgICAgIClcbiAgICAgIDogX3ZtLl9lKCksXG4gICAgX3ZtLl92KFwiIFwiKSxcbiAgICBfYyhcbiAgICAgIFwiYnV0dG9uXCIsXG4gICAgICB7XG4gICAgICAgIHN0YXRpY0NsYXNzOiBcImJ0biBidG4tbGlnaHQgYnRuLXNtXCIsXG4gICAgICAgIGNsYXNzOiBfdm0udm90ZWQgPiAwID8gXCJsaWtlZFwiIDogXCJcIixcbiAgICAgICAgYXR0cnM6IHsgZGlzYWJsZWQ6IF92bS52b3RlZCA+IDAgfHwgIV92bS5jYW5fdm90ZSB9LFxuICAgICAgICBvbjoge1xuICAgICAgICAgIGNsaWNrOiBmdW5jdGlvbigkZXZlbnQpIHtcbiAgICAgICAgICAgIHJldHVybiBfdm0uc2V0Vm90ZSgxKVxuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfSxcbiAgICAgIFtcbiAgICAgICAgX2MoXCJzdmdcIiwgeyBzdGF0aWNDbGFzczogXCJpY29uIGRvd25sb2FkLWJ1dHRvbi1pY29uIG10LW4xXCIgfSwgW1xuICAgICAgICAgIF9jKFwidXNlXCIsIHtcbiAgICAgICAgICAgIGF0dHJzOiB7XG4gICAgICAgICAgICAgIFwieGxpbms6aHJlZlwiOiBcIi90aGVtZXMvZGVmYXVsdC9hc3NldHMvaWNvbnMvc3ByaXRlLnN2ZyNsaWtlXCJcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9KVxuICAgICAgICBdKVxuICAgICAgXVxuICAgICksXG4gICAgX3ZtLl92KFwiIFwiKSxcbiAgICBfYyhcInNwYW5cIiwgeyBzdGF0aWNDbGFzczogXCJtcy0yIG1lLTIgZnctYm9sZFwiLCBjbGFzczogX3ZtLnJhdGluZ19jb2xvciB9LCBbXG4gICAgICBfdm0uX3YoX3ZtLl9zKF92bS5yYXRpbmcgPiAwID8gXCIrXCIgOiBcIlwiKSArIF92bS5fcyhfdm0ucmF0aW5nKSlcbiAgICBdKSxcbiAgICBfdm0uX3YoXCIgXCIpLFxuICAgIF9jKFxuICAgICAgXCJidXR0b25cIixcbiAgICAgIHtcbiAgICAgICAgc3RhdGljQ2xhc3M6IFwiYnRuIGJ0bi1saWdodCBidG4tc21cIixcbiAgICAgICAgY2xhc3M6IF92bS52b3RlZCA8IDAgPyBcImRpc2xpa2VkXCIgOiBcIlwiLFxuICAgICAgICBhdHRyczogeyBkaXNhYmxlZDogX3ZtLnZvdGVkIDwgMCB8fCAhX3ZtLmNhbl92b3RlIH0sXG4gICAgICAgIG9uOiB7XG4gICAgICAgICAgY2xpY2s6IGZ1bmN0aW9uKCRldmVudCkge1xuICAgICAgICAgICAgcmV0dXJuIF92bS5zZXRWb3RlKDApXG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9LFxuICAgICAgW1xuICAgICAgICBfYyhcInN2Z1wiLCB7IHN0YXRpY0NsYXNzOiBcImljb24gZG93bmxvYWQtYnV0dG9uLWljb24gbWUtMVwiIH0sIFtcbiAgICAgICAgICBfYyhcInVzZVwiLCB7XG4gICAgICAgICAgICBhdHRyczoge1xuICAgICAgICAgICAgICBcInhsaW5rOmhyZWZcIjogXCIvdGhlbWVzL2RlZmF1bHQvYXNzZXRzL2ljb25zL3Nwcml0ZS5zdmcjZGlzbGlrZVwiXG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSlcbiAgICAgICAgXSlcbiAgICAgIF1cbiAgICApXG4gIF0pXG59XG52YXIgc3RhdGljUmVuZGVyRm5zID0gW1xuICBmdW5jdGlvbigpIHtcbiAgICB2YXIgX3ZtID0gdGhpc1xuICAgIHZhciBfaCA9IF92bS4kY3JlYXRlRWxlbWVudFxuICAgIHZhciBfYyA9IF92bS5fc2VsZi5fYyB8fCBfaFxuICAgIHJldHVybiBfYyhcbiAgICAgIFwiZGl2XCIsXG4gICAgICB7XG4gICAgICAgIHN0YXRpY0NsYXNzOiBcInNwaW5uZXItYm9yZGVyIHRleHQtc2Vjb25kYXJ5XCIsXG4gICAgICAgIGF0dHJzOiB7IHJvbGU6IFwic3RhdHVzXCIgfVxuICAgICAgfSxcbiAgICAgIFtfYyhcInNwYW5cIiwgeyBzdGF0aWNDbGFzczogXCJ2aXN1YWxseS1oaWRkZW5cIiB9LCBbX3ZtLl92KFwiTG9hZGluZy4uLlwiKV0pXVxuICAgIClcbiAgfVxuXVxucmVuZGVyLl93aXRoU3RyaXBwZWQgPSB0cnVlXG5cbmV4cG9ydCB7IHJlbmRlciwgc3RhdGljUmVuZGVyRm5zIH0iLCJ2YXIgbWFwID0ge1xuXHRcIi4vY29tcG9uZW50cy9Da2VkaXRvcklucHV0Q29tcG9uZW50LnZ1ZVwiOiBcIi4vdGhlbWVzL2RlZmF1bHQvc3JjL2pzL2NvbXBvbmVudHMvQ2tlZGl0b3JJbnB1dENvbXBvbmVudC52dWVcIixcblx0XCIuL2NvbXBvbmVudHMvQ29tbWVudHNDb21wb25lbnQudnVlXCI6IFwiLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvY29tcG9uZW50cy9Db21tZW50c0NvbXBvbmVudC52dWVcIixcblx0XCIuL2NvbXBvbmVudHMvTGlrZXNDb21wb25lbnQudnVlXCI6IFwiLi90aGVtZXMvZGVmYXVsdC9zcmMvanMvY29tcG9uZW50cy9MaWtlc0NvbXBvbmVudC52dWVcIlxufTtcblxuXG5mdW5jdGlvbiB3ZWJwYWNrQ29udGV4dChyZXEpIHtcblx0dmFyIGlkID0gd2VicGFja0NvbnRleHRSZXNvbHZlKHJlcSk7XG5cdHJldHVybiBfX3dlYnBhY2tfcmVxdWlyZV9fKGlkKTtcbn1cbmZ1bmN0aW9uIHdlYnBhY2tDb250ZXh0UmVzb2x2ZShyZXEpIHtcblx0aWYoIV9fd2VicGFja19yZXF1aXJlX18ubyhtYXAsIHJlcSkpIHtcblx0XHR2YXIgZSA9IG5ldyBFcnJvcihcIkNhbm5vdCBmaW5kIG1vZHVsZSAnXCIgKyByZXEgKyBcIidcIik7XG5cdFx0ZS5jb2RlID0gJ01PRFVMRV9OT1RfRk9VTkQnO1xuXHRcdHRocm93IGU7XG5cdH1cblx0cmV0dXJuIG1hcFtyZXFdO1xufVxud2VicGFja0NvbnRleHQua2V5cyA9IGZ1bmN0aW9uIHdlYnBhY2tDb250ZXh0S2V5cygpIHtcblx0cmV0dXJuIE9iamVjdC5rZXlzKG1hcCk7XG59O1xud2VicGFja0NvbnRleHQucmVzb2x2ZSA9IHdlYnBhY2tDb250ZXh0UmVzb2x2ZTtcbm1vZHVsZS5leHBvcnRzID0gd2VicGFja0NvbnRleHQ7XG53ZWJwYWNrQ29udGV4dC5pZCA9IFwiLi90aGVtZXMvZGVmYXVsdC9zcmMvanMgc3luYyByZWN1cnNpdmUgXFxcXC52dWUkL1wiOyJdLCJzb3VyY2VSb290IjoiIn0=