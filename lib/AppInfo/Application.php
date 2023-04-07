<?php
declare(strict_types=1);

namespace OCA\HedgeNext\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCA\Files\Event\LoadAdditionalScriptsEvent;

use OCA\HedgeNext\Listener\LoadScriptsListener;

class Application extends App implements IBootstrap {
    public const APP_NAME = 'hedgenext';

    public function __construct() {
        parent::__construct(self::APP_NAME);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadScriptsListener::class);
        $context->registerEventListener(BeforeTemplateRenderedEvent::class, LoadScriptsListener::class);
        
    }

    public function boot(IBootContext $context): void {

    }
}

