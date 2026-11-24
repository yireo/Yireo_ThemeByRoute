<?php
declare(strict_types=1);

namespace Yireo\ThemeByRoute\Test\Unit\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Yireo\ThemeByRoute\Config\Config;

class ConfigTest extends TestCase
{
    private File&MockObject $file;
    private ScopeConfigInterface&MockObject $scopeConfig;
    private LoggerInterface&MockObject $logger;

    public function testIsEnabledReadsStoreScope(): void
    {
        $config = $this->createConfig();
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('system/yireo_themebyroute/enabled', ScopeInterface::SCOPE_STORE)
            ->willReturn(true);

        $this->assertTrue($config->isEnabled());
    }

    public function testMissingFileReturnsEmptyMap(): void
    {
        $config = $this->createConfig();
        $this->file->method('isExists')->willReturn(false);
        $this->file->expects($this->never())->method('fileGetContents');

        $this->assertSame([], $config->getMap());
    }

    public function testInvalidJsonReturnsEmptyMapAndLogs(): void
    {
        $config = $this->createConfig('{invalid');
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('[Yireo_ThemeByRoute] Failed to read theme-by-route.json'));

        $this->assertSame([], $config->getMap());
    }

    public function testUnreadableFileReturnsEmptyMap(): void
    {
        $config = $this->createConfig();
        $this->file->method('isExists')->willReturn(true);
        $this->file->method('fileGetContents')->willThrowException(new FileSystemException(__('fail')));

        $this->assertSame([], $config->getMap());
    }

    public function testScalarJsonReturnsEmptyMap(): void
    {
        $this->assertSame([], $this->createConfig('"Loki/luma"')->getMap());
    }

    public function testSimpleSyntax(): void
    {
        $config = $this->createConfig('{"Loki/luma": ["cms/index/index"], "Hyva/default-csp": ["checkout"]}');

        $this->assertSame(
            ['Loki/luma' => ['cms/index/index'], 'Hyva/default-csp' => ['checkout']],
            $config->getMap()
        );
    }

    public function testSimpleSyntaxDropsInvalidThemeCodes(): void
    {
        $config = $this->createConfig(
            '{"123": ["cms"], "../../etc": ["cms"], "Loki": ["cms"], "Loki/luma/x": ["cms"], "Loki/luma": ["cms"]}'
        );

        $this->assertSame(['Loki/luma' => ['cms']], $config->getMap());
    }

    public function testSimpleSyntaxDropsNonStringPages(): void
    {
        $config = $this->createConfig('{"Loki/luma": ["cms", ["nested"], null, 42, {"a": "b"}, "checkout"]}');

        $this->assertSame(['Loki/luma' => ['cms', 'checkout']], $config->getMap());
    }

    public function testSimpleSyntaxDropsThemesWithoutValidPages(): void
    {
        $config = $this->createConfig('{"Loki/luma": "cms", "Loki/other": [], "Loki/third": [null]}');

        $this->assertSame([], $config->getMap());
    }

    public function testAdvancedSyntaxMatchesWebsite(): void
    {
        $config = $this->createConfig($this->advanced('website', 'base'));

        $this->assertSame(['Loki/luma' => ['cms/index/index']], $config->getMap());
    }

    public function testAdvancedSyntaxMatchesGroup(): void
    {
        $config = $this->createConfig($this->advanced('group', 'main_website_store'));

        $this->assertSame(['Loki/luma' => ['cms/index/index']], $config->getMap());
    }

    public function testAdvancedSyntaxMatchesStore(): void
    {
        $config = $this->createConfig($this->advanced('store', 'default'));

        $this->assertSame(['Loki/luma' => ['cms/index/index']], $config->getMap());
    }

    public function testAdvancedSyntaxWithoutMatchReturnsEmptyMap(): void
    {
        $config = $this->createConfig($this->advanced('website', 'other'));

        $this->assertSame([], $config->getMap());
    }

    public function testAdvancedSyntaxWithUnknownScopeTypeReturnsEmptyMap(): void
    {
        $config = $this->createConfig($this->advanced('global', 'base'));

        $this->assertSame([], $config->getMap());
    }

    public function testAdvancedSyntaxSkipsInvalidEntries(): void
    {
        $json = '[
            "garbage",
            {"scope_type": "store", "scope_code": "default"},
            {"scope_type": "store", "scope_code": "default", "theme": 123, "pages": ["cms"]},
            {"scope_type": "store", "scope_code": "default", "theme": "../x", "pages": ["cms"]},
            {"scope_type": "store", "scope_code": "default", "theme": "Loki/luma", "pages": "cms"},
            {"scope_type": "store", "scope_code": ["default"], "theme": "Loki/luma", "pages": ["cms"]},
            {"scope_type": "store", "scope_code": "default", "theme": "Loki/valid", "pages": ["cms", null]}
        ]';

        $this->assertSame(['Loki/valid' => ['cms']], $this->createConfig($json)->getMap());
    }

    public function testAdvancedSyntaxReturnsFirstMatch(): void
    {
        $json = '[
            {"scope_type": "website", "scope_code": "base", "theme": "Loki/first", "pages": ["cms"]},
            {"scope_type": "store", "scope_code": "default", "theme": "Loki/second", "pages": ["cms"]}
        ]';

        $this->assertSame(['Loki/first' => ['cms']], $this->createConfig($json)->getMap());
    }

    public function testFileIsReadOncePerInstance(): void
    {
        $config = $this->createConfig();
        $this->file->method('isExists')->willReturn(true);
        $this->file->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('{"Loki/luma": ["cms"]}');

        $config->getMap();
        $this->assertSame(['Loki/luma' => ['cms']], $config->getMap());
    }

    private function advanced(string $scopeType, string $scopeCode): string
    {
        return json_encode([[
            'scope_type' => $scopeType,
            'scope_code' => $scopeCode,
            'theme' => 'Loki/luma',
            'pages' => ['cms/index/index'],
        ]]);
    }

    private function createConfig(?string $json = null): Config
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->file = $this->createMock(File::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        if ($json !== null) {
            $this->file->method('isExists')->with('/var/www/app/etc/theme-by-route.json')->willReturn(true);
            $this->file->method('fileGetContents')->willReturn($json);
        }

        $directoryList = $this->createStub(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn('/var/www');

        $website = $this->createStub(WebsiteInterface::class);
        $website->method('getCode')->willReturn('base');
        $group = $this->createStub(GroupInterface::class);
        $group->method('getCode')->willReturn('main_website_store');
        $store = $this->createStub(StoreInterface::class);
        $store->method('getCode')->willReturn('default');

        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getWebsite')->willReturn($website);
        $storeManager->method('getGroup')->willReturn($group);
        $storeManager->method('getStore')->willReturn($store);

        return new Config($this->scopeConfig, $this->file, new Json(), $this->logger, $directoryList, $storeManager);
    }
}
