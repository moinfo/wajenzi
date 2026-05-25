
class Utility {
    static swal(text, title = '', type='success') {
        Swal.fire(text, title, type);
    }

    static swalConfirm(text, title, params, callback) {
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
        }).then((result) => {
            console.log('SWAL', result);
            callback(result.value);
        });
    }

    static ajaxLoad(url, params, div, callback) {
        $.ajax({
            type: 'POST',
            url: url,
            data: {_token: csrf_token, ...params},
            success: function (result) {
                $(div).html(result);
                callback(true);
            }, error: function (error) {
                callback(error);
            }
        });
    }

    static ajaxLoadForm(form_name, params, div, callback) {
        var url = '/ajax/form';
        params = params ? params : {};
        params.formName = form_name;
        this.ajaxLoad(url, params, div, function (result) {
            callback(result);
        });
    }

    static callClassMethod(className, id, method, params, callback, errorCallback) {
        var url = '/ajax/class';
        $.ajax({
            type: 'POST',
            url: url,
            dataType: 'json',
            data: { _token: csrf_token, className: className, id: id, method: method, params: params},
            success: function (result) {
                callback(result);
            },
            error: function (xhr) {
                var payload = xhr.responseJSON || {success: false, message: xhr.statusText || 'Request failed'};
                errorCallback(payload);
            }
        });
    }

    static deleteModelObject(className, id, callback, error_callback, verbose = true) {
        this.callClassMethod(className, id, 'delete', null, function (res) {
            var ok = res && res.success === true;
            if(verbose) {
                if(ok) { Swal.fire('Deleted', 'Deleted', 'success'); }
                else { Swal.fire('Failed to delete', (res && res.message) || 'Failed', 'error'); }
            }
            callback(ok);
        }, function (err) {
            if(verbose) { Swal.fire('Failed to delete', (err && err.message) || 'Failed', 'error'); }
            if (error_callback) { error_callback(err); }
            callback(false);
        });
    }
}
window.Utility = Utility;
console.log('Main JS loaded');
