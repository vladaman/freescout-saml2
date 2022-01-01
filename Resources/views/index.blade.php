<form class="form-horizontal margin-top margin-bottom" method="POST" action="" id="saml_form">
    {{ csrf_field() }}

	 <div class="form-group{{ $errors->has('settings[saml.active]') ? ' has-error' : '' }}">
        <label for="saml_active" class="col-sm-2 control-label">{{ __('Activate Module') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[saml.active]" value="on" id="saml_active" class="onoffswitch-checkbox" @if (old('settings[saml.active]', $settings['saml.active'])  == 'on') checked="checked"@endif >
                        <label class="onoffswitch-label" for="saml_active"></label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group{{ $errors->has('settings.saml.entity_id') ? ' has-error' : '' }} margin-bottom-10">
        <label for="saml.entity_id" class="col-sm-2 control-label">{{ __('IdP Entity ID') }}</label>

        <div class="col-sm-6">
            <input id="saml.entity_id" type="text" class="form-control input-sized-lg"
                   name="settings[saml.entity_id]" value="{{ old('settings.saml.entity_id', $settings['saml.entity_id']) }}">
            @include('partials/field_error', ['field'=>'settings.saml.entity_id'])
        </div>
    </div>
    <div class="form-group{{ $errors->has('settings.saml.sso_url') ? ' has-error' : '' }} margin-bottom-10">
        <label for="saml.sso_url" class="col-sm-2 control-label">{{ __('IdP Signin URL') }}</label>

        <div class="col-sm-6">
            <input id="saml_sso_url" type="text" class="form-control input-sized-lg"
                   name="settings[saml.sso_url]" value="{{ old('settings.saml.sso_url', $settings['saml.sso_url']) }}">
        </div>
    </div>
	<div class="form-group{{ $errors->has('settings.saml.slo_url') ? ' has-error' : '' }} margin-bottom-10">
        <label for="saml.slo_url" class="col-sm-2 control-label">{{ __('IdP Logout URL') }}</label>

        <div class="col-sm-6">
            <input id="saml_slo_url" type="text" class="form-control input-sized-lg"
                   name="settings[saml.slo_url]" value="{{ old('settings.saml.slo_url', $settings['saml.slo_url']) }}">
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.saml.x509') ? ' has-error' : '' }} margin-bottom-10">
        <label for="saml.x509" class="col-sm-2 control-label">{{ __('x509 Certificate') }}</label>

        <div class="col-sm-6">
            <textarea id="saml.x509" type="text" class="form-control input-sized-lg" style="height: 300px"
                   name="settings[saml.x509]">{{ old('settings.saml.x509', $settings['saml.x509']) }}</textarea>
        </div>
    </div>
	<div class="form-group{{ $errors->has('settings[saml.auto_create]') ? ' has-error' : '' }}">
        <label for="saml_auto_create" class="col-sm-2 control-label">{{ __('Auto Create Users') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[saml.auto_create]" value="on" id="saml_auto_create" class="onoffswitch-checkbox" @if (old('settings[saml.auto_create]', $settings['saml.auto_create'])  == 'on') checked="checked"@endif >
                        <label class="onoffswitch-label" for="saml_auto_create"></label>
                    </div>
                </div>
            </div>
            <p class="help-block">
                {{ __('Will trust IdP to auto-create new users. If disabled then users must first exists to be able to log in') }}
            </p>
        </div>
    </div>

	<div class="form-group{{ $errors->has('settings[saml.exclusive_login]') ? ' has-error' : '' }}">
        <label for="saml_exclusive_login" class="col-sm-2 control-label">{{ __('Force SAML Login') }}</label>

        <div class="col-sm-6">
            <div class="controls">
                <div class="onoffswitch-wrap">
                    <div class="onoffswitch">
                        <input type="checkbox" name="settings[saml.exclusive_login]" value="on" id="saml_exclusive_login" class="onoffswitch-checkbox" @if (old('settings[saml.exclusive_login]', $settings['saml.exclusive_login'])  == 'on') checked="checked"@endif >
                        <label class="onoffswitch-label" for="saml_exclusive_login"></label>
                    </div>
                </div>
            </div>
            <p class="help-block">
                {{ __('Users will be required to sign-in using SSO IdP. No email/password form will be displayed') }}
            </p>
        </div>
    </div>

    <div class="form-group">
        <label for="saml.saml_callback_url" class="col-sm-2 control-label">{{ __('ACS URL') }}</label>
        <a href="{{ route('saml_acs')  }}">{{ route('saml_acs')  }}</a>
    </div>
	<div class="form-group">
        <label for="saml.login_url" class="col-sm-2 control-label">{{ __('Login URL') }}</label>
        <a href="{{ route('login')  }}">{{ route('login')  }}</a>
    </div>

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>
