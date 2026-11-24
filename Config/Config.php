<?php
declare(strict_types=1);

namespace Yireo\ThemeByRoute\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Config
{
    private const CONFIG_PATH = 'app/etc/theme-by-route.json';
    private const THEME_CODE_PATTERN = '#^[A-Za-z0-9_-]+/[A-Za-z0-9_-]+$#';

    private ?array $data = null;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly File $file,
        private readonly JsonSerializer $json,
        private readonly LoggerInterface $logger,
        private readonly DirectoryList $directoryList,
        private readonly StoreManagerInterface $storeManager,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag('system/yireo_themebyroute/enabled', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return array<string, string[]>
     */
    public function getMap(): array
    {
        if ($this->data === null) {
            $this->data = $this->readData();
        }

        return $this->convertData($this->data);
    }

    private function readData(): array
    {
        $configFile = $this->directoryList->getRoot().'/'.self::CONFIG_PATH;

        try {
            if (!$this->file->isExists($configFile)) {
                return [];
            }

            $data = $this->json->unserialize($this->file->fileGetContents($configFile));

            return is_array($data) ? $data : [];
        } catch (Throwable $e) {
            $this->logger->error('[Yireo_ThemeByRoute] Failed to read theme-by-route.json: '.$e->getMessage());

            return [];
        }
    }

    private function convertData(array $data): array
    {
        if (!array_is_list($data)) {
            return $this->filterMap($data);
        }

        foreach ($data as $value) {
            if (!is_array($value) || !is_string($value['theme'] ?? null)) {
                continue;
            }

            $map = $this->filterMap([$value['theme'] => $value['pages'] ?? null]);
            if ($map !== [] && $this->matchesScope($value)) {
                return $map;
            }
        }

        return [];
    }

    private function matchesScope(array $value): bool
    {
        $scopeCode = $value['scope_code'] ?? null;
        if (!is_string($scopeCode)) {
            return false;
        }

        return match ($value['scope_type'] ?? null) {
            'website' => $this->storeManager->getWebsite()->getCode() === $scopeCode,
            'group' => $this->storeManager->getGroup()->getCode() === $scopeCode,
            'store' => $this->storeManager->getStore()->getCode() === $scopeCode,
            default => false,
        };
    }

    private function filterMap(array $data): array
    {
        $map = [];
        foreach ($data as $themeCode => $pages) {
            $themeCode = (string)$themeCode;
            if (!is_array($pages) || preg_match(self::THEME_CODE_PATTERN, $themeCode) !== 1) {
                continue;
            }

            $pages = array_values(array_filter($pages, 'is_string'));
            if ($pages === []) {
                continue;
            }

            $map[$themeCode] = $pages;
        }

        return $map;
    }
}
