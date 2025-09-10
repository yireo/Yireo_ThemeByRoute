<?php
declare(strict_types=1);

namespace Yireo\ThemeByRoute\Config;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
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

            return is_array($data) ? $data : [];
        } catch (Throwable $e) {
            $this->logger->error('[Vendor_RouteTheme] Failed to read theme.json: '.$e->getMessage());

            return [];
        }
    }
}
