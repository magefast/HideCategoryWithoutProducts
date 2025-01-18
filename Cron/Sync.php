<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Dragonfly\HideCategoryWithoutProducts\Cron;

use Dragonfly\HideCategoryWithoutProducts\Service\HideCategoryWithoutProducts;
use Magento\Framework\Exception\LocalizedException;

class Sync
{
    /**
     * @var HideCategoryWithoutProducts
     */
    private HideCategoryWithoutProducts $hideCategoryWithoutProducts;

    /**
     * @param HideCategoryWithoutProducts $hideCategoryWithoutProducts
     */
    public function __construct(HideCategoryWithoutProducts $hideCategoryWithoutProducts)
    {
        $this->hideCategoryWithoutProducts = $hideCategoryWithoutProducts;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function execute(): void
    {
        $this->hideCategoryWithoutProducts->execute();
    }
}
