# Yireo_ThemeByRoute

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-theme-by-route)
<!-- badges.specs.end -->


**Magento 2 module to allow a file `app/etc/theme-by-route.json` to determine which theme needs to be loaded for which route.**

## Installation
```bash
composer require yireo/magento2-theme-by-route
bin/magento module:enable Yireo_ThemeByRoute
```

## Configuration
Create a file `app/etc/theme-by-route.json` similar to the following:

```json
{
  "Loki/luma": [
    "cms/index/index"
  ]
}
```

Now, the `Loki/luma` theme is applied to all pages.

If you want to control under which circumstances which theme is applied, there is an advanced syntax as well:


```json
[
  {
    "scope_type": "website",
    "scope_code": "default",
    "theme": "Loki/luma",
    "pages": [
      "cms/index/index"
    ]
  }
]
```

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ThemeByRoute/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ThemeByRoute/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ThemeByRoute/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ThemeByRoute/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ThemeByRoute/compile.yml?label=compile)
<!-- badges.test.end -->
