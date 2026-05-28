{{-- Landing CMS — About (singleton) --}}
@extends('layouts.backend')

@php
    $loc = fn ($val) => is_array($val) ? ($val['en'] ?? '') : (is_string($val) ? $val : '');
@endphp

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Website Content &mdash; About
                <small>Company story shown on the mobile app</small>
            </div>

            <form method="post" action="{{ route('landing_about.update') }}" autocomplete="off">
                @csrf

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Company Story</h3>
                    </div>
                    <div class="block-content">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Founded year</label>
                                    <input type="text" class="form-control" name="founded_year" value="{{ $about->founded_year ?? '' }}" placeholder="e.g. 2012">
                                </div>
                            </div>
                            <div class="col-sm-8">
                                <div class="form-group">
                                    <label>Tagline (English)</label>
                                    <input type="text" class="form-control" name="tagline" value="{{ $loc($about->tagline ?? null) }}" placeholder="e.g. Building Dreams, Creating Reality">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Our story (English)</label>
                                    <textarea class="form-control" name="story" rows="6" placeholder="Use blank lines to separate paragraphs">{{ $loc($about->story ?? null) }}</textarea>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Mission (English)</label>
                                    <textarea class="form-control" name="mission" rows="4">{{ $loc($about->mission ?? null) }}</textarea>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Vision (English)</label>
                                    <textarea class="form-control" name="vision" rows="4">{{ $loc($about->vision ?? null) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Contact</h3>
                    </div>
                    <div class="block-content">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Address</label>
                                    <input type="text" class="form-control" name="address" value="{{ $about->address ?? '' }}">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" class="form-control" name="phone" value="{{ $about->phone ?? '' }}">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="text" class="form-control" name="email" value="{{ $about->email ?? '' }}">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label>Working hours (English)</label>
                                    <input type="text" class="form-control" name="working_hours" value="{{ $loc($about->working_hours ?? null) }}" placeholder="e.g. Mon - Fri: 8:00 AM - 6:00 PM">
                                </div>
                            </div>
                        </div>
                        <div class="form-group text-right mt-10">
                            <button type="submit" class="btn btn-alt-primary"><i class="si si-check"></i> Save About</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
