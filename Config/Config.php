<?php
declare(strict_types=1);

namespace Yireo\ThemeByRoute\Config;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Config
{
    private const CONFIG_PATH = 'app/etc/theme-by-route.json';

    public function __construct(
        private readonly File $file,
        private readonly JsonSerializer $json,
        private readonly LoggerInterface $logger,
        private readonly DirectoryList $directoryList,
        private readonly StoreManagerInterface $storeManager,
    ) {
    }

    public function getMap(): array
    {
        $configFile = $this->directoryList->getRoot().'/'.self::CONFIG_PATH;

        try {
            if (!$this->file->isExists($configFile)) {
                return [];
            }

            $raw = $this->file->fileGetContents($configFile);
            $data = $this->json->unserialize($raw);

            return is_array($data) ? $this->convertData($data) : [];
        } catch (Throwable $e) {
            $this->logger->error('[Vendor_RouteTheme] Failed to read theme.json: '.$e->getMessage());

            return [];
        }
    }

    private function convertData(array $data): array
    {
        foreach ($data as $value) {
            if (!isset($value['scope_type']) || !isset($value['scope_code'])) {
                continue;
            }

            if (!isset($value['theme']) || !isset($value['pages'])) {
                continue;
            }

            if ($value['scope_type'] === 'website' && $this->storeManager->getWebsite()->getCode() === $value['scope_code']) {
                return [
                    $value['theme'] => $value['pages']
                ];
            }

            if ($value['scope_type'] === 'group' && $this->storeManager->getGroup()->getCode() === $value['scope_code']) {
                return [
                    $value['theme'] => $value['pages']
                ];
            }

            if ($value['scope_type'] === 'store' && $this->storeManager->getStore()->getCode() === $value['scope_code']) {
                return [
                    $value['theme'] => $value['pages']
                ];
            }
        }

        return $data;
    }
}
