<?php

namespace App\EventSubscriber;

use App\Repository\SettingRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class TwigGlobalSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Environment $twig,
        private SettingRepository $settingRepository
    ) {}

    public function onKernelController(ControllerEvent $event): void
    {
        // Execute only on the main request
        if (!$event->isMainRequest()) {
            return;
        }

        // Cache all settings in a simple associative array
        $settingsRaw = $this->settingRepository->findAll();
        $settings = [];
        foreach ($settingsRaw as $setting) {
            $settings[$setting->getKey()] = $setting->getValue();
        }

        $this->twig->addGlobal('global_settings', $settings);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
