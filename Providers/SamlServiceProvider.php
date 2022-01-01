<?php

namespace Modules\Saml\Providers;

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Auth;

use Session;
use OneLogin\Saml2\Auth as OneLogin_Saml2_Auth;

define('SAMPLE_SAML', 'saml');
require_once __DIR__ . '/../vendor/autoload.php';

class SamlServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        \Eventy::addFilter('settings.sections', function ($sections) {
            $sections['saml'] = ['title' => __('SAML 2.0'), 'icon' => 'user', 'order' => 750];

            return $sections;
        }, 30);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function ($settings, $section) {

            if ($section != 'saml') {
                return $settings;
            }

            $settings = \Option::getOptions([
                'saml.active',
                'saml.sso_url',
                'saml.slo_url',
                'saml.entity_id',
                'saml.auto_create',
                'saml.mapping',
                'saml.exclusive_login',
                'saml.x509'
            ]);

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function ($params, $section) {
            if ($section != 'saml') {
                return $params;
            }

            $params = [
                'template_vars' => [],
                'validator_rules' => [
                    // 'settings.saml\.active' => 'required',
                    'settings.saml\.sso_url' => 'required|url',
                    'settings.saml\.slo_url' => 'required|url',
                    'settings.saml\.entity_id' => 'required',
                    // 'settings.saml\.auto_create' => 'required',
                    // 'settings.saml\.exclusive_login' => 'required',
                    'settings.saml\.x509' => 'required',
                ]
            ];

            return $params;
        }, 20, 2);

        \Eventy::addAction('login_form.after', [$this, 'renderSsoButton']);

        // Settings view name
        \Eventy::addFilter('settings.view', function ($view, $section) {
            if ($section != 'saml') {
                return $view;
            } else {
                return 'saml::index';
            }
        }, 20, 2);

        \Eventy::addFilter('middleware.web.custom_handle.response', function ($prev, $rq, $next) {
            $path = $rq->path();
            $loggedIn = Auth::check();

            $settings = \Option::getOptions([
                'saml.active',
                'saml.sso_url',
                'saml.slo_url',
                'saml.entity_id',
                'saml.auto_create',
                'saml.mapping',
                'saml.exclusive_login',
                'saml.x509'
            ]);

            if (!$rq->get('disable_saml', false) && $path == 'login' && !$loggedIn &&
                $settings['saml.active'] == 'on' && ($settings['saml.exclusive_login'] == 'on' || $rq->get('saml', true))) {

                $auth = new OneLogin_Saml2_Auth($this::getSamlSettings());
                $ssoBuiltUrl = $auth->login(null, array(), false, false, true);

                \Log::debug("SAML2 Redirecting to IdP", array('RequestID' => $auth->getLastRequestID()));
                session()->put('AuthNRequestID', $auth->getLastRequestID());
                return redirect($ssoBuiltUrl);
            }

			if ($settings['saml.active'] == 'on' && $path == 'logout'){
				// $auth = new OneLogin_Saml2_Auth($this::getSamlSettings());
                // $sloBuiltUrl = $auth->logout();

				// Use link for now since we are getting error: `The IdP does not support Single Log Out`
				// https://console.jumpcloud.com/userconsole/logout
				if (!empty($settings['saml.slo_url'])){
					return redirect($settings['saml.slo_url']);
				}
			}

            return $prev;
        }, 10, 3);
    }


    /**
     * Adds SSO button under login form
     */
    public function renderSsoButton()
    {
        echo \View::make('saml::login_view', [
        ])->render();

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('saml.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php', 'saml'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/saml');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/saml';
        }, \Config::get('view.paths')), [$sourcePath]), 'saml');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    public static function getSamlSettings()
    {
        $settings = \Option::getOptions([
            'saml.active',
            'saml.sso_url',
            'saml.slo_url',
            'saml.entity_id',
            'saml.auto_create',
            'saml.mapping',
            'saml.x509'

        ]);
        $returnUrl = 'https://mydummyurl/';

        return array(
            // If 'strict' is True, then the PHP Toolkit will reject unsigned
            // or unencrypted messages if it expects them to be signed or encrypted.
            // Also it will reject the messages if the SAML standard is not strictly
            // followed: Destination, NameId, Conditions ... are validated too.
            'strict' => true,

            // Enable debug mode (to print errors).
            'debug' => false,

            // Set a BaseURL to be used instead of try to guess
            // the BaseURL of the view that process the SAML Message.
            // Ex http://sp.example.com/
            //    http://example.com/sp/
            'baseurl' => null,

            // Service Provider Data that we are deploying.
            'sp' => array(
                // Identifier of the SP entity  (must be a URI)
                'entityId' => $settings['saml.entity_id'],
                // Specifies info about where and how the <AuthnResponse> message MUST be
                // returned to the requester, in this case our SP.
                'assertionConsumerService' => array(
                    // URL Location where the <Response> from the IdP will be returned
                    'url' => $returnUrl,
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports this endpoint for the
                    // HTTP-POST binding only.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ),
                // If you need to specify requested attributes, set a
                // attributeConsumingService. nameFormat, attributeValue and
                // friendlyName can be omitted
                "attributeConsumingService" => array(
                    "serviceName" => "Freescout",
                    "serviceDescription" => "PHP Helpdesk",
                    "requestedAttributes" => array(
                        array(
                            "name" => "",
                            "isRequired" => false,
                            "nameFormat" => "",
                            "friendlyName" => "",
                            "attributeValue" => array()
                        )
                    )
                ),
                // Specifies info about where and how the <Logout Response> message MUST be
                // returned to the requester, in this case our SP.
                'singleLogoutService' => array(
                    // URL Location where the <Response> from the IdP will be returned
                    'url' => $settings['saml.slo_url'],
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports the HTTP-Redirect binding
                    // only for this endpoint.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ),
                // Specifies the constraints on the name identifier to be used to
                // represent the requested subject.
                // Take a look on lib/Saml2/Constants.php to see the NameIdFormat supported.
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
                // Usually x509cert and privateKey of the SP are provided by files placed at
                // the certs folder. But we can also provide them with the following parameters
                'x509cert' => '',
                'privateKey' => '',
            ),

            // Identity Provider Data that we want connected with our SP.
            'idp' => array(
                // Identifier of the IdP entity  (must be a URI)
                'entityId' => $settings['saml.entity_id'],
                // SSO endpoint info of the IdP. (Authentication Request protocol)
                'singleSignOnService' => array(
                    // URL Target of the IdP where the Authentication Request Message
                    // will be sent.
                    'url' => $settings['saml.sso_url'],
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports the HTTP-Redirect binding
                    // only for this endpoint.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ),
                // SLO endpoint info of the IdP.
                'singleLogoutService' => array(
                    // URL Location of the IdP where SLO Request will be sent.
                    'url' => '',
                    // URL location of the IdP where the SP will send the SLO Response (ResponseLocation)
                    // if not set, url for the SLO Request will be used
                    'responseUrl' => '',
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports the HTTP-Redirect binding
                    // only for this endpoint.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ),
                // Public x509 certificate of the IdP
                'x509cert' => $settings['saml.x509'],
            ),
        );

    }
}
