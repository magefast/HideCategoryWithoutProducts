<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Dragonfly\HideCategoryWithoutProducts\Service;

use Exception;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\StoreRepositoryInterface;

class HideCategoryWithoutProducts
{
    private const SKIP_IDS = [1, 2];

    private array $storeIds = [];

    private ProductCollectionFactory $productCollection;
    private CategoryCollectionFactory $categoryCollection;
    private Stock $stock;
    private Category $categoryModel;
    private StoreRepositoryInterface $storeRepository;

    /**
     * @param ProductCollectionFactory $productCollection
     * @param CategoryCollectionFactory $categoryCollection
     * @param Stock $stock
     * @param Category $categoryModel
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        ProductCollectionFactory  $productCollection,
        CategoryCollectionFactory $categoryCollection,
        Stock                     $stock,
        Category                  $categoryModel,
        StoreRepositoryInterface  $storeRepository
    )
    {
        $this->productCollection = $productCollection;
        $this->categoryCollection = $categoryCollection;
        $this->stock = $stock;
        $this->categoryModel = $categoryModel;
        $this->storeRepository = $storeRepository;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function execute(): void
    {
        $catIds = $this->getCategoriesWithProductsInStock();
        $this->addParentCategories($catIds);

        $categoriesIncludeInMenu = $this->getAllCategoriesIncludeInMenu();
        $categoriesNotIncludeInMenu = $this->getAllCategoriesNotIncludeInMenu();

        foreach ($categoriesIncludeInMenu as $c) {
            if (!isset($catIds[$c])) {
                // set to enable menu
                $this->updateCategoryData($c, false);
            }
        }

        foreach ($categoriesNotIncludeInMenu as $c) {
            if (isset($catIds[$c])) {
                // set to enable menu
                $this->updateCategoryData($c, true);
            }
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getCategoriesWithProductsInStock(): array
    {
        $collection = $this->productCollection->create();
        $collection->setFlag('has_stock_status_filter', true);
        $collection
            ->addFieldToFilter('status', Status::STATUS_ENABLED)
            ->addFieldToFilter('type_id', Type::TYPE_SIMPLE)
            ->addCategoryIds();

        $collection
            ->joinField('qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )->joinTable('cataloginventory_stock_item', 'product_id=entity_id', array('stock_status' => 'is_in_stock'))
            ->addAttributeToSelect('stock_status')
            ->addFieldToFilter('stock_status', 1);

        $this->stock->addInStockFilterToCollection($collection);
        $this->stock->addIsInStockFilterToCollection($collection);

        $collection->load();

        $categoryIdsWithProducts = [];
        foreach ($collection as $p) {
            foreach ($p->getCategoryIds() as $categoryId) {
                $categoryIdsWithProducts[$categoryId] = $categoryId;
            }
        }
        unset($collection);

        return $categoryIdsWithProducts;
    }

    /**
     * @param $categories
     * @return void
     */
    private function addParentCategories(&$categories): void
    {
        $categoriesAll = $this->categoryCollection->create()
            ->addFieldToFilter(CategoryInterface::KEY_IS_ACTIVE, array('eq' => '1'));

        foreach ($categoriesAll as $c) {
            if (isset($categories[$c->getId()])) {
                $path = $c->getPath();
                $pathArray = explode('/', $path);
                foreach ($pathArray as $p) {
                    if (!in_array(intval($p), self::SKIP_IDS)) {
                        $categories[intval($p)] = intval($p);
                    }
                }
            }
        }
        unset($categoriesAll);
    }

    /**
     * @return array
     */
    public function getAllCategoriesIncludeInMenu(): array
    {
        $categories = $this->categoryCollection->create()
            ->addFieldToFilter(CategoryInterface::KEY_INCLUDE_IN_MENU, array('eq' => 1))
            ->addFieldToFilter(CategoryInterface::KEY_IS_ACTIVE, array('eq' => '1'));

        $cat = [];
        foreach ($categories as $c) {
            $cat[$c->getId()] = $c->getId();
        }
        unset($categories);

        return $cat;
    }

    /**
     * @return array
     */
    public function getAllCategoriesNotIncludeInMenu(): array
    {
        $categories = $this->categoryCollection->create()
            ->addFieldToFilter(CategoryInterface::KEY_INCLUDE_IN_MENU, array('eq' => 0))
            ->addFieldToFilter(CategoryInterface::KEY_IS_ACTIVE, array('eq' => '1'));

        $cat = [];
        foreach ($categories as $c) {
            $cat[$c->getId()] = $c->getId();
        }
        unset($categories);

        return $cat;
    }

    /**
     * @param int $id
     * @param bool $value
     * @return void
     */
    private function updateCategoryData(int $id, bool $value): void
    {
        foreach ($this->getAllStoresIds() as $storeId) {
            $category = $this->categoryModel->load($id);
            $category->setIncludeInMenu($value);
            $category->setStoreId($storeId);
            try {
                $category->save();
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * @return array
     */
    private function getAllStoresIds(): array
    {
        if (!empty($this->storeIds)) {
            return $this->storeIds;
        }

        $stores = $this->storeRepository->getList();
        $ids = [];
        foreach ($stores as $store) {
            $ids[] = $store->getStoreId();
        }

        $this->storeIds = $ids;

        return $this->storeIds;
    }

}
