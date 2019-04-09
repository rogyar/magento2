<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Atwix\Testmodule\Model\ResourceModel\Log;

use Atwix\Testmodule\Model\Log as LogModel;
use Atwix\Testmodule\Model\ResourceModel\Log as LogResource;
use Magento\Cms\Model\ResourceModel\AbstractCollection;

/**
 * CMS page collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'log_id';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'catalog_product_modification_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'catalog_product_modification';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(LogModel::class, LogResource::class);
    }

    /**
     * Set first store flag
     *
     * @param bool $flag
     * @return $this
     */
    public function setFirstStoreFlag($flag = false)
    {
        $this->_previewFlag = $flag;
        return $this;
    }

    /**
     * Add filter by store
     *
     * @param int|array|\Magento\Store\Model\Store $store
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if (!$this->getFlag('store_filter_added')) {
            $this->performAddStoreFilter($store, $withAdmin);
        }
        return $this;
    }
}
