<?php
declare(strict_types=1);

namespace OCA\HedgeNext\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\HedgeNext\AppInfo\Application;
use OCP\Util;

class LoadScriptsListener implements IEventListener {
    public function handle(Event $event): void {
        if (!$event instanceof LoadAdditionalScriptsEvent) {
            return;
        }

        Util::addScript(Application::APP_NAME, 'script');
    }
}

