<?php

namespace Modules\Saml\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use \App\User;
use Illuminate\Support\Facades\Auth;

// use Illuminate\Support\Facades\Log;

use Session;

use \Modules\Saml\Providers\SamlServiceProvider;
use OneLogin\Saml2\Auth as OneLogin_Saml2_Auth;

class SamlController extends Controller
{
    /**
     * Handle SAML2 Response from IdP
     * @return Response
     */
    public function callback(Request $request)
    {
        $settings = \Option::getOptions([
            'saml.active',
            'saml.entity_id',
            'saml.sso_url',
            'saml.slo_url', // logout url
            'saml.auto_create',
            'saml.mapping',
            'saml.exclusive_login',
            'saml.x509',
        ]);
        $requestID = session()->get('AuthNRequestID');

        if (empty($requestID)) {
            \Log::error("SAML2 Returned Visitor from IdP. Missing requestID in session.");
            return response('SAML2 Error: Missing correct requestID in session', 403);
        }
        $auth = new OneLogin_Saml2_Auth(SamlServiceProvider::getSamlSettings());
        $auth->processResponse($requestID);

        \Log::debug("SAML2 Received user back from IdP", array('RequestID' => $requestID));

        $errors = $auth->getErrors();

        if (!empty($errors)) {
            \Log::error("SAML2 - IdP Errors", array('errors' => $errors, 'lastReason' => $auth->getLastErrorReason()));
            return response('SAML2 Error. Please check logfile for more details', 403);
        }

        if (!$auth->isAuthenticated()) {
            \Log::error("SAML2 - IdP User not authenticated", array());
            return response('SAML2 Error. User is not authenticated. Please check logfile for more details', 404);
        }

        $email = $auth->getNameId();

        $user = User::where('email', '=', $email)->first();

		if (!$user && $settings['saml.auto_create'] != 'on') {
			\Log::error("SAML2 - IdP User does not exists", array('email'=>$email));
            return response("SAML2 User with email address <strong>$email</strong> does not exists. Please ask your administrator to create account first", 403);
		} else if (!$user) {
            // Create New User
            \Log::info("SAML2 - Did not find user by email address. Creating new user", array('email' => $email));
            $user = new User();
            $user->first_name = "SAML"; // Must have at least some name
            $user->last_name = "User";
            $user->setPassword();
            $attributes = $auth->getAttributes();

            foreach ($attributes as $attributeName => $attributeValues) {
                if (strtolower($attributeName) == 'firstname' || strtolower($attributeName) == 'first_name') {
                    $user->first_name = $attributeValues[0];
                } else if (strtolower($attributeName) == 'lastname' || strtolower($attributeName == 'last_name')) {
                    $user->last_name = $attributeValues[0];
                } else if (strtolower($attributeName) == 'jobtitle' || strtolower($attributeName == 'job_title')) {
                    $user->job_title = $attributeValues[0];
                }
            }

            $user->email = $email;
            $user->save();

            Auth::login($user);
            return redirect($request->session()->get('url.intended', '/'));
        } else {
            \Log::debug("SAML2 - Response Success. User found", array('email' => $email));
            Auth::login($user);
            return redirect($request->session()->get('url.intended', '/'));
        }
    }

    public function login(Request $request)
    {
        return \Response::json(array());

    }
}
