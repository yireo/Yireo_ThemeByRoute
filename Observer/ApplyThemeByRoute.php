<?php
declare(strict_types=1);

namespace Yireo\ThemeByRoute\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Theme\Model\Theme\ThemeProvider;
use Yireo\ThemeByRoute\Config\Config;

class ApplyThemeByRoute implements ObserverInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly DesignInterface $design,
        private readonly ThemeProvider $themeProvider,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        $map = $this->config->getMap();
        if (empty($map)) {
            return;
        }

        foreach ($map as $themeCode => $routes) {
            if (false === is_array($routes) || empty($routes)) {
                continue;
            }

            if (false === $this->applyThemeRoutes($themeCode, $routes)) {
                continue;
            }

            $this->applyTheme($themeCode);
        }
    }

    private function applyThemeRoutes(string $themeCode, array $routes): bool
    {
        $currentRouteName = (string)$this->request->getRouteName();
        $fullAction = (string)$this->request->getFullActionName();

        foreach ($routes as $route) {
            $route = trim((string)$route);
            if ($route === '') {
                continue;
            }

            $isRouteMatch = (strpos($route, '/') === false) && strcasecmp($route, $currentRouteName) === 0;
            $asFullAction = str_replace('/', '_', $route);
            $isFullActionMatch = strcasecmp($asFullAction, $fullAction) === 0;

            if (false === $isRouteMatch && false === $isFullActionMatch) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function applyTheme(string $themeCode): void
    {
        try {
            $theme = $this->themeProvider->getThemeByFullPath('frontend/'.$themeCode);
            if ($theme && $theme->getId()) {
                $this->design->setDesignTheme($theme, Area::AREA_FRONTEND);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                '[Yireo_ThemeByRoute] Failed to set theme "'.$themeCode.'": '.$e->getMessage()
            );
        }
    }
}
