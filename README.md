# Webp

<sup>It's a magento2-module for the [metapackage](https://github.com/swissup/webp).</sup>

### [Installation](https://docs.swissuplabs.com/m2/extensions/webp/installation/)

###### For clients

There are several ways to install the extension for clients:

 1. If you've bought the product at Magento's Marketplace - use
    [Marketplace installation instructions](https://docs.magento.com/marketplace/user_guide/buyers/install-extension.html)

 2. Otherwise, you have two options:
    - Install the sources directly from [our repository](https://docs.swissuplabs.com/m2/extensions/webp/installation/composer/) - **recommended**
    - Download archive and use [manual installation](https://docs.swissuplabs.com/m2/extensions/webp/installation/manual/)


###### For maintainers

```bash
cd <magento_root>
composer config repositories.swissup composer https://docs.swissuplabs.com/packages/
composer require swissup/module-webp --prefer-source --ignore-platform-reqs
bin/magento module:enable Swissup_Webp Swissup_Core
bin/magento setup:upgrade
bin/magento setup:di:compile
```
