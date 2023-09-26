<?php
declare(strict_types=1);

namespace Swissup\Webp\Model;

use Generator;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Image;
use Magento\Catalog\Model\ResourceModel\Product\Image as ProductImage;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Helper\File\Storage\Database as FileStorageDatabase;
use Magento\Catalog\Model\Product\Media\ConfigInterface as MediaConfig;
use WebPConvert\Convert\Exceptions\ConversionFailedException;
use WebPConvert\WebPConvert;

/**
 * Image resize service.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImageConvert
{
    /**
     * @var MediaConfig
     */
    private $mediaConfig;

    /**
     * @var ProductImage
     */
    private $productImage;

    /**
     * @var WriteInterface
     */
    private $mediaDirectory;

    /**
     * @var FileStorageDatabase
     */
    private $fileStorageDatabase;

    /**
     * @var ProductGalleryTableUpdater
     */
    private $galleryUpdater;

    /**
     * @var bool
     */
    private $skipHiddenImages = false;

    /**
     *
     * @var string
     */
    private $filenameFilter;

    /**
     * @var int
     */
    private $limit = 100;

    /**
     * @param MediaConfig $mediaConfig,
     * @param ProductImage $productImage
     * @param Filesystem $filesystem
     * @param FileStorageDatabase $fileStorageDatabase
     * @throws \Magento\Framework\Exception\FileSystemException
     * @internal param ProductImage $gallery
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        MediaConfig $mediaConfig,
        ProductImage $productImage,
        Filesystem $filesystem,
        FileStorageDatabase $fileStorageDatabase,
        \Swissup\Webp\Model\ProductGalleryTableUpdater $galleryUpdater

    ) {
        $this->mediaConfig = $mediaConfig;
        $this->productImage = $productImage;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->fileStorageDatabase = $fileStorageDatabase;
        $this->galleryUpdater = $galleryUpdater;
    }

    /**
     *
     * @return Generator
     * @throws NotFoundException
     */
    public function execute(): Generator
    {
        $count = $this->getSize();
        if (!$count) {
            throw new NotFoundException(__('Cannot converts images - product images not found'));
        }

        $productImages = $this->getProductImages();
        $i = 0;
        $convertFileExtensions = ['png', 'jpg', 'jpeg'];
        $regexReplacePattern = '/\.(' . implode('|', $convertFileExtensions) . ')$/';
        foreach ($productImages as $image) {
            if ($i >= $this->limit) {
                break;
            }

            $error = '';
            $originalImageName = $image['filepath'];

            $extension = pathinfo($originalImageName, PATHINFO_EXTENSION);
            if (!in_array($extension, $convertFileExtensions)) {
                continue;
            }

            if ($this->filenameFilter !== null && !str_contains($originalImageName, $this->filenameFilter)) {
                continue;
            }

            $mediastoragefilename = $this->mediaConfig->getMediaPath($originalImageName);
            $originalImagePath = $this->mediaDirectory->getAbsolutePath($mediastoragefilename);

            if ($this->fileStorageDatabase->checkDbUsage()) {
                $this->fileStorageDatabase->saveFileToFilesystem($mediastoragefilename);
            }
            if ($this->mediaDirectory->isFile($originalImagePath)) {
                try {
                    $webpImagePath = preg_replace($regexReplacePattern, '.webp', $originalImagePath);
                    if (!$this->mediaDirectory->isExist($webpImagePath)) {
                        $this->convert($originalImagePath, $webpImagePath);
                        $i++;
                    }
                    if ($this->mediaDirectory->isExist($webpImagePath)) {
                        $webpImageName = preg_replace($regexReplacePattern, '.webp', $originalImageName);
                        $this->galleryUpdater->update($originalImageName, $webpImageName);
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            } else {
                $error = __('Cannot convert image "%1" - original image not found', $originalImagePath);
            }

            yield ['filename' => $originalImageName, 'error' => (string) $error] => $count;
        }
    }

    /**
     * @param bool $status
     * @return $this
     */
    public function setSkipHiddenImages(bool $status = true)
    {
        $this->skipHiddenImages = $status;
        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     *
     * @param string $filename
     */
    public function setFilenameFilter($filename)
    {
        $this->filenameFilter = (string) $filename;
        return $this;
    }

    /**
     * @return int
     */
    private function getSize(): int
    {
        $size = $this->skipHiddenImages ?
            $this->productImage->getCountUsedProductImages() : $this->productImage->getCountAllProductImages();

        return $this->limit < $size ? $this->limit : $size;
    }

    /**
     * @return Generator
     */
    private function getProductImages(): \Generator
    {
        return $this->skipHiddenImages ?
            $this->productImage->getUsedProductImages() : $this->productImage->getAllProductImages();
    }

    /**
     * Convert image
     *
     * @param string $originalImagePath
     * @param string $webpImagePath
     */
    private function convert(string $originalImagePath, string $webpImagePath)
    {
        $options = $this->getWebpConvertorOptions();
        /* @phpstan-ignore-next-line */
        WebPConvert::convert($originalImagePath, $webpImagePath, $options);
    }

    /**
     * @return array
     */
    private function getWebpConvertorOptions(): array
    {
        $options = [];
//        $options['metadata'] = 'none';
        $options['quality'] = 100;

        return $options;
    }
}
