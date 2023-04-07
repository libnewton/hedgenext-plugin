<?php

namespace OCA\HedgeNext\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCA\HedgeNext\AppConfig;
use OCA\HedgeNext\CryptoStuff;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\IConfig;

class OauthController extends Controller {
	
	private $config;

    /** @var IUserSession */
    private $userSession;

    /** @var IUserManager */
    private $userManager;

		
	/**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IUserSession $userSession - root folder
     * @param IUserManager $userManager - root folder
	 * @param IConfig $config - application configuration
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IUserSession $userSession,
        IUserManager $userManager,
        Iconfig $config
	) {
        $this->appConfig = new AppConfig($appName);
        $this->config = $config;
		$this->userSession = $userSession;
        $this->userManager = $userManager;
        $this->request = $request;
		parent::__construct($appName, $request);
	}
    private function getSecret() {
        return $this->config->getAppValue($this->appName, 'hdoc.secretkey');
    }
    private function getHedgeURL() {
        return $this->config->getAppValue($this->appName, 'hdoc.server');
    }
    /**
     * Manage Stuff
     *
     * @param string $state - state
     * @NoCSRFRequired
     * @NoAdminRequired
     */
	public function authorize($state) {
        // return redirect
        $userId = $this->userSession->getUser()->getUID() ;
        $code = urlencode(CryptoStuff::encrypt($userId ."@_" . strval(time() + 10), $this->getSecret()));
        $url = $this->getHedgeURL() . '/auth/oauth2/callback?state=' . $state . '&code=' . $code;
        // current uniox
        return new RedirectResponse($url);
	}
    /**
     * Manage Stuff
     *
     * @param string $client_secret - data
     * @param string $code - data
     *
     * @return array
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
	public function tokenget($client_secret, $code) {
        try {
            if($client_secret === $this->getSecret()) {
                $currentTime = time();
                $payl = CryptoStuff::decrypt(urldecode($code), $this->getSecret());
                $extractedTime = intval(explode("@_", $payl)[1]);
                $userid = explode("@_", $payl)[0];
                if($currentTime > $extractedTime) {
                    return new DataResponse(['error' => 'invalid_grant'], 400);
                }
                return new DataResponse([
                    "token_type" => "Bearer",
                    "access_token" => urlencode(CryptoStuff::encrypt($userid, $this->getSecret())),
                    "refresh_token" => urlencode(CryptoStuff::encrypt($userid, $this->getSecret())),
                    "expires_in" => 3600,
                    "user_id" => $userid
                ]);
            } else {
                return new DataResponse(['error' => 'invalid_grant'], 400);
            }

        }
        catch (\Exception $e) {
            return new DataResponse(['error' => 'invalid_grant'], 400);
        }

    }
    /**
     * Manage Stuff
     *
     * @param string $client_secret - data
     * @param string $refresh_token - data
     *
     * @return array
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
	public function refreshtokenget($client_secret, $refresh_token) {
        try {
            if($client_secret === $this->getSecret()) {
                $userid = CryptoStuff::decrypt(urldecode($refresh_token), $this->getSecret());
                return new DataResponse([
                    "token_type" => "Bearer",
                    "access_token" => urlencode(CryptoStuff::encrypt($userid, $this->getSecret())),
                    "refresh_token" => urlencode(CryptoStuff::encrypt($userid, $this->getSecret())),
                    "expires_in" => 3600,
                    "user_id" => $userid
                ]);
            } else {
                return new DataResponse(['error' => 'invalid_grant'], 400);
            }

        }
        catch (\Exception $e) {
            return new DataResponse(['error' => 'invalid_grant'], 400);
        }

    }
    /**
     * Manage Stuff
     *
     *
     * @return array
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
	public function userdataget() {
        try {
            $authHeader = $this->request->getHeader('Authorization');
            $authHeader = explode(" ", $authHeader)[1];
            $userid = CryptoStuff::decrypt(urldecode($authHeader), $this->getSecret());
            $user = $this->userManager->get($userid);
            return new DataResponse([
                "username" => $userid,
                "displayname" => $user->getDisplayName(),
                "email" => $user->getEMailAddress(),
            ]);
            

        }
        catch (\Exception $e) {
            return new DataResponse(['error' => 'invalid_grant'], 400);
        }

    }


}
