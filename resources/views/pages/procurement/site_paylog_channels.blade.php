@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Payment Channels
            <div class="float-right">
                <a href="{{ route('site_paylog') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="si si-arrow-left"></i> Back to Paylog
                </a>
            </div>
        </div>

        <div class="row">
            {{-- Add a new channel --}}
            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default"><h3 class="block-title">Add Channel</h3></div>
                    <div class="block-content">
                        <form method="POST" action="{{ route('site_paylog.channels') }}">
                            @csrf
                            <div class="form-group">
                                <label class="control-label required">Name</label>
                                <input type="text" class="form-control" name="name" placeholder="e.g. Equity Bank" required>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Type</label>
                                <select name="type" class="form-control">
                                    <option value="bank">Bank</option>
                                    <option value="mobile">Mobile Money</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                            <input type="hidden" name="is_active" value="1">
                            <button type="submit" name="addItem" value="PaymentChannel" class="btn btn-alt-primary">
                                <i class="si si-plus"></i> Add
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Existing channels --}}
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header block-header-default"><h3 class="block-title">Channels</h3></div>
                    <div class="block-content">
                        <table class="table table-bordered table-vcenter">
                            <thead>
                                <tr><th>#</th><th>Name</th><th>Type</th><th>Active</th><th style="width:60px;"></th></tr>
                            </thead>
                            <tbody>
                                @forelse($channels as $i => $c)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $c->name }}</td>
                                        <td>{{ ucfirst($c->type ?? '—') }}</td>
                                        <td>
                                            <span class="badge badge-{{ $c->is_active ? 'success' : 'secondary' }}">{{ $c->is_active ? 'Yes' : 'No' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <form method="POST" action="{{ route('site_paylog.channels') }}" onsubmit="return confirm('Delete this channel?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="id" value="{{ $c->id }}">
                                                <button type="submit" name="deleteItem" value="1" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No channels yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
