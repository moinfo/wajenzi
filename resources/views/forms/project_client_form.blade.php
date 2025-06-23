<?php
$document_id = \App\Classes\Utility::getLastId('ProjectClient')+1;
?>
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="first_name" class="control-label required">First Name</label>
                    <input type="text" class="form-control" id="input-first-name" required="required" name="first_name" value="{{ $object->first_name ?? '' }}" placeholder="First Name">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="last_name" class="control-label required">Last Name</label>
                    <input type="text" class="form-control" id="input-last-name" required="required" name="last_name" value="{{ $object->last_name ?? '' }}" placeholder="Last Name">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="email" class="control-label required">Email</label>
                    <input type="email" class="form-control" id="input-email" name="email" value="{{ $object->email ?? '' }}" placeholder="Email" required="required">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" class="form-control" id="input-phone" name="phone_number" value="{{ $object->phone_number ?? '' }}" placeholder="Phone Number">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" class="form-control" id="input-address" name="address" value="{{ $object->address ?? '' }}" placeholder="Address">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="identification_number">Identification Number </label>
                    <input type="text" class="form-control" id="input-identification" name="identification_number" value="{{ $object->identification_number ?? '' }}" placeholder="Identification Number">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="client_source_id" class="control-label required">Client Source</label>
                    <select name="client_source_id" id="input-project-type" class="form-control" required="required">
                        <option value="">Select Client Source</option>
                        @foreach ($client_sources as $type)
                            <option value="{{ $type->id }}" {{ ($type->id == $object->client_source_id) ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <input type="hidden" name="create_by_id" value="{{ Auth::user()->id }}">
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <input type="hidden" name="document_number" value="PCTC/{{$document_id}}/{{date('Y')}}">
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="document_type_id" value="9">
                <input type="hidden" name="link" value="project_clients/{{$document_id}}/9">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectClient">Submit</button>
            @endif
        </div>
    </form>
</div>
