<?php
declare(strict_types=1);

namespace Yireo\ThemeByRoute\Observer;

use Magento\Framework\App\Area;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Theme\Model\Theme\ThemeProvider;
use Psr\Log\LoggerInterface;
use Throwable;
use Yireo\ThemeByRoute\Config\Config;

class ApplyThemeByRoute implements ObserverInterface
{
    public function __construct(
        private readonly DesignInterface $design,
        private readonly ThemeProvider $themeProvider,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            $request = $observer->getEvent()->getData('request');
            if (!$request instanceof Http || false === $this->config->isEnabled()) {
                return;
            }

            foreach ($this->config->getMap() as $themeCode => $routes) {
                if ($this->matchesRoutes($request, $routes)) {
                    $this->applyTheme((string)$themeCode);
                    return;
                }
            }
        } catch (Throwable $e) {
            $this->logger->error('[Yireo_ThemeByRoute] Failed to apply theme by route: '.$e->getMessage());
        }
    }

    private function matchesRoutes(Http $request, array $routes): bool
    {
        $currentRouteName = (string)$request->getRouteName();
        $fullAction = (string)$request->getFullActionName();
        $requestUri = trim(explode('?', (string)$request->getRequestUri(), 2)[0], '/');

        foreach ($routes as $route) {
            $route = trim((string)$route, " \f\n\r\t\v\x00");
            if ($route === '') {
                continue;
            }

            if (!str_contains($route, '/') && strcasecmp($route, $currentRouteName) === 0) {
                return true;
            }

            if (strcasecmp(str_replace('/', '_', $route), $fullAction) === 0) {
                return true;
            }

            if ($requestUri === $route) {
                return true;
            }
        }

        return false;
    }

    private function applyTheme(string $themeCode): void
    {
        $theme = $this->themeProvider->getThemeByFullPath(Area::AREA_FRONTEND.'/'.$themeCode);
        if ($theme && $theme->getId()) {
            $this->design->setDesignTheme($theme, Area::AREA_FRONTEND);
        }
    }
}
