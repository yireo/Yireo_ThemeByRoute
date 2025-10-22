# Yireo_ThemeByRoute

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
