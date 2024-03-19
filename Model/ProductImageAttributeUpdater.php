<?php
namespace Swissup\Webp\Model;

use Magento\Framework\App\ResourceConnection;

class ProductImageAttributeUpdater
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var array
     */
    private $attributeIds;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Update
     *
     * @param string $oldValue
     * @param string $newValue
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function update($oldValue, $newValue)
    {
        $resourceConnection = $this->resourceConnection;
        $tableName = $resourceConnection->getTableName('catalog_product_entity_varchar');
        $connection = $resourceConnection->getConnection();
        $data = ['value' => $newValue];
        $where = [
            'value = ?' => $oldValue,
            'attribute_id IN (?)' => $this->getAttributeIds()
        ];
        return $connection->update($tableName, $data, $where);
    }

    /**
     * Get attribute ids
     *
     * @return array
     */
    private function getAttributeIds()
    {
        if ($this->attributeIds === null) {
            $resourceConnection = $this->resourceConnection;
            $connection = $resourceConnection->getConnection();
            $tableName = $resourceConnection->getTableName('eav_attribute');
            $attributeCodes = ['thumbnail', 'image', 'small_image'/*, 'swatch_image'*/];
            $select = $connection->select()
                ->from(['main_table' => $tableName], 'attribute_id')
                ->where('attribute_code IN (?)', $attributeCodes);
            return $connection->fetchCol($select);
        }
        return $this->attributeIds;
    }
}
