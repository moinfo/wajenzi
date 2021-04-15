
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
        // var url = {!! route('admin_ajax') !!}
        var url = '/ajax/class';
        $.ajax({
            type: 'POST',
            url: url,
            data: { _token: csrf_token, className: className, id: id, method: method, params: params},
            success: function (result) {
                callback(result);
            },
            onFailure: function (er) {
                errorCallback(er);
            }
        });
    }

    static deleteModelObject(className, id, callback, error_callback, verbose = true) {
        this.callClassMethod(className, id, 'delete', null, function (res) {
            if(verbose) { Swal.fire('Deleted', 'Deleted', 'success'); }
            callback(true);
        }, function (err) {
            if(verbose) { Swal.fire('Failed to delete', 'Failed', 'error'); }
            error_callback(err);
            callback(false);
        });
    }
}
window.Utility = Utility;
console.log('Main JS loaded');
