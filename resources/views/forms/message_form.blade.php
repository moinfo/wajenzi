<div class="block-content">
    <form method="post"  enctype="multipart/form-data"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-amount">Name</label>
            <input type="text" class="form-control" id="input-name" name="name"
                   value="{{ $object->name ?? '' }}" placeholder="Full Name" required>
        </div>

        <div class="form-group">
            <label for="example-nf-amount">Phone</label>
            <input type="number" class="form-control" pattern="[0-9]{3}[0-9]{3}[0-9]{3}" id="input-phone" name="phone"
                   value="{{ $object->phone ?? '' }}" placeholder="652894205" aria-label="phone"
                   aria-describedby="phone"
                   autocomplete="phone" required>
        </div>
        <div class="form-group">
            <label for="example-nf-description">Message</label>
            <textarea type="text" class="form-control" id="input-message" name="message"
                     placeholder="Your Text Here" rows="6" required>{{ $object->message ?? '' }}</textarea>
        </div>

        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Message">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    (function() {
        var textarea = document.getElementById('input-message');
        if (!textarea) return;
        var counter = document.createElement('div');
        counter.style.cssText = 'margin-top:4px;font-size:0.85rem;font-weight:600;';
        textarea.parentNode.appendChild(counter);

        function update() {
            var len = textarea.value.length;
            var sms = len <= 160 ? 1 : Math.ceil((len - 160) / 153) + 1;
            counter.textContent = len + ' / 160 characters (' + sms + ' SMS)';
            counter.style.color = sms <= 1 ? '#16A34A' : sms <= 3 ? '#EA580C' : '#DC2626';
        }
        textarea.addEventListener('input', update);
        update();
    })();
</script>


