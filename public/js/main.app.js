/******/ (() => { // webpackBootstrap
/*!******************************!*\
  !*** ./resources/js/main.js ***!
  \******************************/
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var Utility = /*#__PURE__*/function () {
  function Utility() {
    _classCallCheck(this, Utility);
  }

  _createClass(Utility, null, [{
    key: "swal",
    value: function swal(text) {
      var title = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
      var type = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'success';
      Swal.fire(text, title, type);
    }
  }, {
    key: "swalConfirm",
    value: function swalConfirm(text, title, params, callback) {
      var confirmButtonText = params && params['confirmButtonText'] ? params['confirmButtonText'] : 'Yes';
      var cancelButtonText = params && params['cancelButtonText'] ? params['cancelButtonText'] : 'No, Cancel';
      Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmButtonText,
        cancelButtonText: cancelButtonText
      }).then(function (result) {
        console.log('SWAL', result);
        callback(result.value);
      });
    }
  }, {
    key: "ajaxLoad",
    value: function ajaxLoad(url, params, div, callback) {
      $.ajax({
        type: 'POST',
        url: url,
        data: _objectSpread({
          _token: csrf_token
        }, params),
        success: function success(result) {
          $(div).html(result);
          callback(true);
        },
        error: function error(_error) {
          callback(_error);
        }
      });
    }
  }, {
    key: "ajaxLoadForm",
    value: function ajaxLoadForm(form_name, params, div, callback) {
      var url = '/ajax/form';
      params = params ? params : {};
      params.formName = form_name;
      this.ajaxLoad(url, params, div, function (result) {
        callback(result);
      });
    }
  }, {
    key: "callClassMethod",
    value: function callClassMethod(className, id, method, params, callback, errorCallback) {
      // var url = {!! route('admin_ajax') !!}
      var url = '/ajax/class';
      $.ajax({
        type: 'POST',
        url: url,
        data: {
          _token: csrf_token,
          className: className,
          id: id,
          method: method,
          params: params
        },
        success: function success(result) {
          callback(result);
        },
        onFailure: function onFailure(er) {
          errorCallback(er);
        }
      });
    }
  }, {
    key: "deleteModelObject",
    value: function deleteModelObject(className, id, callback, error_callback) {
      var verbose = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : true;
      this.callClassMethod(className, id, 'delete', null, function (res) {
        if (verbose) {
          Swal.fire('Deleted', 'Deleted', 'success');
        }

        callback(true);
      }, function (err) {
        if (verbose) {
          Swal.fire('Failed to delete', 'Failed', 'error');
        }

        error_callback(err);
        callback(false);
      });
    }
  }]);

  return Utility;
}();

window.Utility = Utility;
console.log('Main JS loaded');
/******/ })()
;