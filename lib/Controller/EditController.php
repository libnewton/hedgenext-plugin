<?php

namespace OCA\HedgeNext\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\Share\IManager;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCA\HedgeNext\AppConfig;
use OCA\Files\Helper;
use OCA\HedgeNext\CryptoStuff;
use OCP\IConfig;


/**
 * Controller with the main functions
 */
class EditController extends Controller {

    /**
     * Current user session
     *
     * @var IUserSession
     */
    private $userSession;

    /**
     * Root folder
     *
     * @var IRootFolder
     */
    private $root;

    /**
     * Url generator service
     *
     * @var IURLGenerator
     */
    private $urlGenerator;

    /**
     * Share manager
     *
     * @var IManager
     */
    private $shareManager;
    /**
     * Logger
     *
     * @var ILogger
     */
    private $logger;

    private $config;
    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IRootFolder $root - root folder
     * @param IUserSession $userSession - current user session
     * @param IURLGenerator $urlGenerator - url generator service
     * @param IManager $shareManager - share manager
     * @param ILogger $logger - logger
     */
    public function __construct($AppName,
        IRequest $request,
        IRootFolder $root,
        IUserSession $userSession,
        IURLGenerator $urlGenerator,
        IManager $shareManager,
        ILogger $logger,
        IConfig $config
    ) {
//        $this->appConfig = new AppConfig($appName);
        $this->config = $config;
        parent::__construct($AppName, $request);

        $this->userSession = $userSession;
        $this->root = $root;
        $this->urlGenerator = $urlGenerator;
        $this->shareManager = $shareManager;
        $this->logger = $logger;
        

        
    }
    private function getSecret() {
        return $this->config->getAppValue($this->appName, 'hdoc.secretkey');
    }
    private function getHedgeURL() {
        return $this->config->getAppValue($this->appName, 'hdoc.server');
    }

    /**
     * Get user by share token
     * 
     * @param string $shareToken - share token
     * 
     * @return string
     */
    private function getUserByShareToken($user, $shareToken) {
        if ($shareToken) {
            $share = $this->shareManager->getShareByToken($shareToken);
            if ($share) {
                $user = $share->getShareOwner();
            }
        }
        return $user;
    }

    private function getFileById($user, $fid) {
       // list($instanceId, $fileId) = explode('_', $fid);
        $file = $this->root->getUserFolder($user)->getById($fid)[0];
        return $file;
    }
    /**
     * Create new file in folder
     *
     * @param string $name - file name
     * @param string $dir - folder path
     *
     * @return array
     *
     * @NoAdminRequired
     * @PublicPage
     */
    public function create($name, $dir) {
        $this->logger->debug("Create: $name", ["app" => $this->appName]);

        $userId = $this->userSession->getUser()->getUID();
        $userFolder = $this->root->getUserFolder($userId);

        $folder = $userFolder->get($dir);

        if ($folder === null) {
            $this->logger->error("Folder not found: $dir", ["app" => $this->appName]);
            return ["error" => "Folder not found"];
        }
        if (!$folder->isCreatable()) {
            $this->logger->error("Folder without permission: $dir", ["app" => $this->appName]);
            return ["error" => "Insufficient permissions"];
        }

        $name = $folder->getNonExistingName($name);
        $nonce = base64_encode(openssl_random_pseudo_bytes(20));
        $nonce = str_replace(['+', '/', '='], ['-', '_', '.'], $nonce);
        try {
            $file = $folder->newFile($name, "UNSET°VALUE°" . $nonce);
        } catch (NotPermittedException $e) {
            $this->logger->logException($e, ["message" => "Can't create file: $name", "app" => $this->appName]);
            return ["error" => "Can't create file"];
        }

        $fileInfo = $file->getFileInfo();
        
        $result = Helper::formatFileInfo($fileInfo);
        return $result;
    }

    /**
     * API Get file
     * 
     * @param string $handoff - file path
     *
     * @return array
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function get($handoff) {
        
        $payload = json_decode(base64_decode(urldecode($handoff)), true);
        $path = $payload['path'];
        $user_id = $payload['user_id'];
        $fid = $payload['fid'];
        $contenthash = $payload['contenthash'];
        $nonce = $payload['nonce'];
        $share_token = $payload['share_token'];
        
        $this->logger->debug("Callback GET $fid", ["app" => $this->appName]);
        try {
            $owner = $this->getUserByShareToken($user_id, $share_token);
            $file = $this->getFileById($owner, $fid);

            $contentNew = $file->getContent();
            // sha256
            $hash_old = hash('sha256', $contentNew);

            if($hash_old !== $contenthash) {
                $this->logger->error("Content hash mismatch: $fid", ["app" => $this->appName]);
                return new JSONResponse("Content hash mismatch", Http::STATUS_INTERNAL_SERVER_ERROR);
            }


        $fileInfo = $file->getFileInfo();
        
        $result = Helper::formatFileInfo($fileInfo);
        $new_fid = $result['id'];
        $new_userid = $user_id;
        $payload_encoded = base64_encode(json_encode(array('fid' => $fid, 'path' => $path, 'userid' => $owner, 'nonce' => $nonce)));
        $payload_encrypted = urlencode(CryptoStuff::encrypt($payload_encoded, $this->getSecret()));
        $hdocURL = $this->getHedgeURL();
        if($contentNew === "UNSET°VALUE°" . $nonce) {
            $hdocURL .= '/new-handoff?handoff=' . $payload_encrypted;
        }
        else if(str_starts_with($contentNew, "HEDGENEXT°DOC°" . $nonce . "°")) {
            $hedgeCode = explode("°", $contentNew)[3];
            $hdocURL .= '/handoff' .'/'.$hedgeCode.'?handoff=' . $payload_encrypted;
        }
        return new RedirectResponse($hdocURL);


        } catch (NotFoundException $e)  {
            return new JSONResponse("File Not Found", Http::STATUS_NOT_FOUND);
        } catch (NotPermittedException  $e) {
            $this->logger->logException($e, ["message" => "Download Not permitted: $fileId", "app" => $this->appName]);
            return new JSONResponse("Internal Server Error", Http::STATUS_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            $this->logger->logException($e, ["message" => "Download file error: $fileId", "app" => $this->appName]);
            return new JSONResponse("Internal Server Error", Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }


}

