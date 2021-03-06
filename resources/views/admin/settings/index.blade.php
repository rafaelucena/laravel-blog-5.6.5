@extends('admin/layouts.app')

@section('custom_css')
@endsection

@section('content')
<div class="card">
    <div class="card-header">Settings</div>

    <div class="card-body">
        <form method="post" action="{{ route('settings.update') }}">
            @csrf
            {{ method_field('PUT') }}
            <div class="row">
                <div class="col-md-5">
                    @foreach($settings as $setting)
                    <div class="form-group">
                        <label for="name">{{ ucfirst(str_replace('_', ' ', $setting->settingName)) }} <span class="required">*</span></label>
                        <input type="text" class="form-control" id="{{ $setting->settingName }}" name="settings[{{ $setting->settingName }}]" value="{{ $setting->settingValue }}" required>
                        <small id="{{ $setting->settingName }}Help" class="form-text text-muted">{{ $setting->description }}</small>
                    </div>
                    @endforeach
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </div>
        </form>
    </div>

</div>
@endsection

@section('custom_js')
@endsection
