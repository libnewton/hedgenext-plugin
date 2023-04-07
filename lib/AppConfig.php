<?php

namespace OCA\HedgeNext;

use OCP\IConfig;


/**
 * Application configutarion
 *
 * @package OCA\HedgeNext
 */
class AppConfig {

    /** @var string */
    private $appName;

    /** @var IConfig */
    private $config;



    /**
     * @param string $AppName - application name
     */
    public function __construct($AppName) {

        $this->appName = $AppName;

        $this->config = \OC::$server->getConfig();
    }


    /**
     * Retrieve the type of document server
     * 
     * @return string
     */
    public function getHedgeURL() {
        return $this->config->getAppValue($this->appName, "hdoc.server");
    }
    private function generateAESkey() {
        $key = openssl_random_pseudo_bytes(32);
        $foo = base64_encode($key);
        $foo = str_replace("=", "_", $foo);
        $foo = str_replace("/", "-", $foo);
        $foo = str_replace("+", "^", $foo);
        return $foo;
    }
    public function setHedgeURL($newVal) {
        $this->config->setAppValue($this->appName,"hdoc.server", $newVal);
        $currentAES = $this->getSecret();
        if ($currentAES == "" || $currentAES == null) {
            $this->config->setAppValue($this->appName,"hdoc.secretkey", $this->generateAESkey());
        }
    }
    public function getSecret() {
        return $this->config->getAppValue($this->appName, "hdoc.secretkey");
    }
 
}

