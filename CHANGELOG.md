# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.3] - 26 November 2026
### Fixed
- Fix TypeError on every frontend request when the advanced config has no matching website, group or store
- Validate the theme map: theme codes must look like Vendor/theme and only string pages are kept
- Make the enabled flag work at website and store scope, not only default scope
- Never break the storefront: errors are logged instead of thrown
- Only apply the first matching theme
- Strip the query string reliably when matching the request URI

### Changed
- Drop PHP 8.1 support, add PHP 8.5 support
- Declare Magento_Backend and Magento_Config as module dependencies

## [1.1.2] - 01 December 2025
### Fixed
- Add enabled config option

## [1.1.1] - 22 October 2025
### Fixed
- Allow psr/log 1 and 2 besides 3

## [1.1.0] - 22 October 2025
### Added
- Add advanced syntax

## [1.0.3] - 30 September 2025
### Fixed
- Fix matching of request URI

## [1.0.2] - 30 September 2025
### Fixed
- Also do a direct match on URLs

## [1.0.1] - 10 September 2025
### Fixed
- Change JSON filename to app/etc/theme-by-route.json
- Better README

## [1.0.0] - 09 September 2025
### Added
- Copy generic CI/CD files
- Adding all files to git via Yireo Command
- Initial release
