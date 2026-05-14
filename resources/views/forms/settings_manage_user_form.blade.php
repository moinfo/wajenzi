
<div class="block-content">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                <label class="control-label">Name</label>
                <input type="text" class="form-control" value="{{ $object->name ?? '' }}" disabled>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label class="control-label">Email</label>
                <input type="email" class="form-control" value="{{ $object->email ?? '' }}" disabled>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label class="control-label required">New Password</label>
                <input type="password" class="form-control" id="cmu-password" placeholder="Enter new password" autocomplete="new-password">
                <small class="text-muted">Minimum 4 characters</small>
            </div>
        </div>
    </div>
    <div class="form-group mt-2">
        <button type="button" class="btn btn-alt-primary" id="cmu-submit" onclick="changeUserPassword({{ $object->id ?? 0 }})">
            <i class="si si-check" id="cmu-btn-icon"></i>
            <span id="cmu-btn-text"> Update Password</span>
        </button>
        <span id="cmu-msg" class="ml-3" style="display:none;"></span>
    </div>
</div>

<script>
function changeUserPassword(userId) {
    var pwd = document.getElementById('cmu-password').value;
    var msg = document.getElementById('cmu-msg');
    var btn = document.getElementById('cmu-submit');
    var icon = document.getElementById('cmu-btn-icon');
    var label = document.getElementById('cmu-btn-text');

    if (!pwd || pwd.length < 4) {
        msg.style.display = 'inline';
        msg.style.color = '#dc3545';
        msg.textContent = 'Password must be at least 4 characters.';
        return;
    }

    btn.disabled = true;
    icon.className = 'fa fa-spinner fa-spin';
    label.textContent = ' Saving...';
    msg.style.display = 'none';

    $.ajax({
        type: 'POST',
        url: '/settings/users/' + userId + '/change-password',
        data: { _token: csrf_token, password: pwd },
        success: function(res) {
            if (res.success) {
                msg.style.display = 'inline';
                msg.style.color = '#28a745';
                msg.textContent = 'Password updated successfully!';
                icon.className = 'si si-check';
                label.textContent = ' Update Password';
                btn.disabled = false;
                document.getElementById('cmu-password').value = '';
            }
        },
        error: function(xhr) {
            msg.style.display = 'inline';
            msg.style.color = '#dc3545';
            var err = (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.password)
                ? xhr.responseJSON.errors.password[0]
                : 'Failed to update. Please try again.';
            msg.textContent = err;
            icon.className = 'si si-check';
            label.textContent = ' Update Password';
            btn.disabled = false;
        }
    });
}
</script>
