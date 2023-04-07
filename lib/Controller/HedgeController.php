<?php

namespace OCA\HedgeNext\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCA\HedgeNext\AppConfig;
use OCP\IConfig;
use OCA\HedgeNext\CryptoStuff;

class HedgeController extends Controller {
	


	/** @var IRootFolder */
	private $root;
	private $config;
		
	/**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IRootFolder $root - root folder
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IRootFolder $root,
		Iconfig $config
	) {
		$this->config = $config;

		parent::__construct($appName, $request);
		$this->root = $root;
	}
    private function getSecret() {
        return $this->config->getAppValue($this->appName, 'hdoc.secretkey');
    }
    private function getHedgeURL() {
        return $this->config->getAppValue($this->appName, 'hdoc.server');
    }
    private function getFileById($user, $fid) {
        $file = $this->root->getUserFolder($user)->getById($fid)[0];
        return $file;
    }
	private function retryOperation(callable $operation) {
		for ($i = 0; $i < 5; $i++) {
			try {
				if ($operation() !== false) {
					return;
				}
			} catch (LockedException $e) {
				if ($i === 4) {
					throw $e;
				}
				usleep(600000);
			}
		}
		throw new GenericFileException('Operation failed');
    }
    /**
     * Manage Stuff
     *
     * @param string $handin - data
     *
     * @return array
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
	public function post($handin) {
		$handin2 = urldecode($handin);
		$decoded = CryptoStuff::decrypt($handin2, $this->getSecret());
		$payload = json_decode(urldecode($decoded), true);
		// expect userid, fid, nonce, hedgecode, content = ""?
		$ncfile = $this->getFileById($payload['userid'], $payload['fid']);

		$currentContent = $ncfile->getContent();
		$header_insg = "HEDGENEXT°DOC°" . $payload["nonce"] . "°" . $payload["hedgecode"] . "°\n\n";
		if($currentContent === "UNSET°VALUE°" . $payload["nonce"] || str_starts_with($currentContent, $header_insg)) {
			if($header_insg . $payload["content"] !== $currentContent) {

				$this->retryOperation(function () use ($ncfile, $header_insg, $payload) {
					return $ncfile->putContent($header_insg . $payload["content"]);
				});
			}
		}
		return new DataResponse("OK");

	}

}
