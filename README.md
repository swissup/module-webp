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

### Usage 

#### Check available webp convertors 
Before images can be converted, you will need to check if some converter is already availble. 
Tool [cwebp](https://developers.google.com/speed/webp/docs/cwebp) is the most useful tool for converting

```bash
bin/magento swissup:webp:check -a 
+-----------+-----------------------+
| Converter | Path                  |
+-----------+-----------------------+
| imagick   | imagick PHP extension |
| gd        | gd PHP extension      |
+-----------+-----------------------+
```

*Convert JPEG & PNG to WebP with PHP*

This extension enables you to do webp conversion with PHP. It supports an abundance of methods for converting and automatically selects the most capable of these that is available on the system.

The library can convert using the following methods:
- *cwebp* (executing [cwebp](https://developers.google.com/speed/webp/docs/cwebp) binary using an `exec` call)
- *vips* (using [Vips PHP extension](https://github.com/libvips/php-vips-ext))
- *imagick* (using [Imagick PHP extension](https://github.com/Imagick/imagick))
- *gmagick* (using [Gmagick PHP extension](https://www.php.net/manual/en/book.gmagick.php))
- *imagemagick* (executing [imagemagick](https://imagemagick.org/index.php) binary using an `exec` call)
- *graphicsmagick* (executing [graphicsmagick](http://www.graphicsmagick.org/) binary using an `exec` call)
- *ffmpeg* (executing [ffmpeg](https://ffmpeg.org/) binary using an `exec` call)
- *wpc* (using [WebPConvert Cloud Service](https://github.com/rosell-dk/webp-convert-cloud-service/) - an open source webp converter for PHP - based on this library)
- *ewwww* (using the [ewww](https://ewww.io/plans/) cloud converter (1 USD startup and then free webp conversion))
- *gd* (using the [Gd PHP extension](https://www.php.net/manual/en/book.image.php))

In addition to converting, the library also has a method for *serving* converted images, and we have instructions here on how to set up a solution for automatically serving webp images to browsers that supports webp.

*Magento 2 at least has one reasonable system requirement out of the box and suits our needs. It is Gd PHP extension.* 

#### Convert product images to [webp](https://developers.google.com/speed/webp)

```bash
bin/magento swissup:webp:convert
```

###### Help by this command

```bash
bin/magento swissup:webp:convert -h 
Description:
  Convert product images to webp

Usage:
  swissup:webp:convert [options]
  webp:convert

Options:
      --skip_hidden_images  Do not process images marked as hidden from product page
  -l, --limit=LIMIT         limit --limit=10 (default: 100 000) [default: 100000]
  -f, --filename=FILENAME   filename filter --filename=1.png
  -h, --help                Display help for the given command. When no command is given display help for the list command
  -q, --quiet               Do not output any message
  -V, --version             Display this application version
      --ansi|--no-ansi      Force (or disable --no-ansi) ANSI output
  -n, --no-interaction      Do not ask any interactive question
  -v|vv|vvv, --verbose      Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```
