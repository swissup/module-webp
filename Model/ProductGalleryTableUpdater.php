<?php
namespace Swissup\Webp\Model;

class ProductGalleryTableUpdater
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Gallery
     */
    private $galleryResource;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Gallery $galleryResource
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Gallery $galleryResource
    ) {
        $this->galleryResource = $galleryResource;
    }

    /**
     * Update media type
     *
     * @param string $oldValue
     * @param string $newValue
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function update($oldValue, $newValue)
    {
        $galleryResource = $this->galleryResource;
        $connection = $galleryResource->getConnection();
        $data = ['value' => $newValue];
        $where = [
            'value = ?' => $oldValue,
            'media_type = ?' => 'image'
        ];
        return $connection->update($galleryResource->getMainTable(), $data, $where);
    }
}
