<?php
declare(strict_types=1);

namespace Yireo\ThemeByRoute\Test\Unit\Observer;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Theme\Model\Theme\ThemeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Yireo\ThemeByRoute\Config\Config;
use Yireo\ThemeByRoute\Observer\ApplyThemeByRoute;

class ApplyThemeByRouteTest extends TestCase
{
    private DesignInterface&MockObject $design;
    private ThemeProvider&MockObject $themeProvider;
    private Config&MockObject $config;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->design = $this->createMock(DesignInterface::class);
        $this->themeProvider = $this->createMock(ThemeProvider::class);
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->config->method('isEnabled')->willReturn(true);
    }

    public function testMatchesRouteName(): void
    {
        $this->expectTheme('Loki/luma');
        $this->config->method('getMap')->willReturn(['Loki/luma' => ['CMS']]);

        $this->createObserver()->execute($this->createEvent($this->createRequest()));
    }

    public function testRouteNameWithSlashDoesNotMatchRouteName(): void
    {
        $this->expectNoTheme();
        $this->config->method('getMap')->willReturn(['Loki/luma' => ['cms/']]);

        $this->createObserver()->execute($this->createEvent($this->createRequest()));
    }

    public function testMatchesFullActionName(): void
    {
        $this->expectTheme('Loki/luma');
        $this->config->method('getMap')->willReturn(['Loki/luma' => [' cms/index/index ']]);

        $this->createObserver()->execute($this->createEvent($this->createRequest()));
    }

    public function testMatchesRequestUriWithoutQueryString(): void
    {
        $this->expectTheme('Loki/luma');
        $this->config->method('getMap')->willReturn(['Loki/luma' => ['about-us']]);

        $request = $this->createRequest('cms', 'cms_page_view', '/about-us/?a=b?c');
        $this->createObserver()->execute($this->createEvent($request));
    }

    public function testNoMatch(): void
    {
        $this->expectNoTheme();
        $this->config->method('getMap')->willReturn(['Loki/luma' => ['', 'checkout', 'about-us']]);

        $this->createObserver()->execute($this->createEvent($this->createRequest()));
    }

    public function testFirstMatchingThemeWins(): void
    {
        $this->expectTheme('Loki/first');
        $this->config->method('getMap')->willReturn([
            'Loki/none' => ['checkout'],
            'Loki/first' => ['cms'],
            'Loki/second' => ['cms'],
        ]);

        $this->createObserver()->execute($this->createEvent($this->createRequest()));
    }

    public function testUnknownThemeIsNotApplied(): void
    {
        $this->themeProvider->method('getThemeByFullPath')->willReturn($this->createStub(ThemeInterface::class));
        $this->design->expects($this->never())->method('setDesignTheme');
        $this->config->method('getMap')->willReturn(['Loki/unknown' => ['cms']]);

        $this->createObserver()->execute($this->createEvent($this->createRequest()));
    }

    public function testDisabledDoesNothing(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn(false);
        $config->expects($this->never())->method('getMap');
        $this->config = $config;

        $this->createObserver()->execute($this->createEvent($this->createRequest()));
    }

    public function testNonHttpRequestDoesNothing(): void
    {
        $this->config->expects($this->never())->method('getMap');

        $this->createObserver()->execute($this->createEvent($this->createStub(RequestInterface::class)));
    }

    public function testExceptionIsLoggedInsteadOfThrown(): void
    {
        $this->config->method('getMap')->willReturn(['Loki/luma' => ['cms']]);
        $this->themeProvider->method('getThemeByFullPath')->willThrowException(new RuntimeException('boom'));
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('[Yireo_ThemeByRoute]'));

        $this->createObserver()->execute($this->createEvent($this->createRequest()));
    }

    private function expectTheme(string $themeCode): void
    {
        $theme = $this->createStub(ThemeInterface::class);
        $theme->method('getId')->willReturn(42);
        $this->themeProvider->expects($this->once())
            ->method('getThemeByFullPath')
            ->with('frontend/'.$themeCode)
            ->willReturn($theme);
        $this->design->expects($this->once())->method('setDesignTheme')->with($theme, 'frontend');
    }

    private function expectNoTheme(): void
    {
        $this->themeProvider->expects($this->never())->method('getThemeByFullPath');
        $this->design->expects($this->never())->method('setDesignTheme');
    }

    private function createRequest(
        string $routeName = 'cms',
        string $fullActionName = 'cms_index_index',
        string $requestUri = '/'
    ): Http {
        $request = $this->createStub(Http::class);
        $request->method('getRouteName')->willReturn($routeName);
        $request->method('getFullActionName')->willReturn($fullActionName);
        $request->method('getRequestUri')->willReturn($requestUri);

        return $request;
    }

    private function createEvent(RequestInterface $request): Observer
    {
        return new Observer(['event' => new Event(['request' => $request])]);
    }

    private function createObserver(): ApplyThemeByRoute
    {
        return new ApplyThemeByRoute($this->design, $this->themeProvider, $this->config, $this->logger);
    }
}
