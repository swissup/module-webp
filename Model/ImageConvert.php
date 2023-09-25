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
     * @var ThemeCustomizationConfig
     */
    private $themeCustomizationConfig;

    /**
     * @var ThemeCollection
     */
    private $themeCollection;

    /**
     * @var WriteInterface
     */
    private $mediaDirectory;

    /**
     * @var FileStorageDatabase
     */
    private $fileStorageDatabase;

    /**
     * @var bool
     */
    private $skipHiddenImages = false;

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
        FileStorageDatabase $fileStorageDatabase
    ) {
        $this->mediaConfig = $mediaConfig;
        $this->productImage = $productImage;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->fileStorageDatabase = $fileStorageDatabase;
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
            throw new NotFoundException(__('Cannot resize images - product images not found'));
        }

        $productImages = $this->getProductImages();

        foreach ($productImages as $image) {
            var_dump($image);

            $error = '';
            $originalImageName = $image['filepath'];

            $mediastoragefilename = $this->mediaConfig->getMediaPath($originalImageName);
            $originalImagePath = $this->mediaDirectory->getAbsolutePath($mediastoragefilename);

            if ($this->fileStorageDatabase->checkDbUsage()) {
                $this->fileStorageDatabase->saveFileToFilesystem($mediastoragefilename);
            }
            if ($this->mediaDirectory->isFile($originalImagePath)) {
                try {
                    $this->convert($originalImagePath, $originalImageName);
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            } else {
                $error = __('Cannot resize image "%1" - original image not found', $originalImagePath);
            }

            yield ['filename' => $originalImageName, 'error' => (string) $error] => $count;
        }
    }

    public function setSkipHiddenImages(bool $status = true)
    {
        $this->skipHiddenImages = $status;
        return $this;
    }

    /**
     * @return int
     */
    private function getSize(): int
    {
        return $this->skipHiddenImages ?
            $this->productImage->getCountUsedProductImages() : $this->productImage->getCountAllProductImages();
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
     * Resize image if not already resized before
     *
     * @param array $imageParams
     * @param string $originalImagePath
     * @param string $originalImageName
     */
    private function convert(string $originalImagePath, string $originalImageName)
    {
        var_dump([
            __METHOD__,
            $originalImagePath,
            $originalImageName
        ]);
    }
}
