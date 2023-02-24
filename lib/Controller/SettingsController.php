<?php

namespace OCA\HedgeNext\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCA\HedgeNext\AppConfig;

class SettingsController extends Controller {
	public function __construct(
		$appName,
		IRequest $request
	) {
        $this->appConfig = new AppConfig($appName);
		parent::__construct($appName, $request);
	}
	
    /**
     * Save address settings
     *
     * @param string $docserver - document service address
     *
     * @return array
     */
    public function post($docserver) {
        $this->appConfig->setHedgeURL($docserver);
		
		return new DataResponse(['result' => "success"]);
    }
	
	public function get() {
		return new DataResponse(['hdoc' => $this->appConfig->getHedgeURL()]);
	}

   
}
