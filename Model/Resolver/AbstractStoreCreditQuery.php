<?php
/**
 * Landofcoder
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * https://landofcoder.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Landofcoder
 * @package     Lof_StoreCreditGraphQl
 * @copyright   Copyright (c) Landofcoder (https://landofcoder.com/)
 * @license     https://landofcoder.com/LICENSE.txt
 */

declare(strict_types=1);

namespace Lof\StoreCreditGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Lof\StoreCredit\Api\CreditManagementInterface;

/**
 * Class AbstractStoreCreditQuery
 *
 * @package Lof\StoreCreditGraphQl\Model\Resolver
 */
abstract class AbstractStoreCreditQuery
{
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CreditManagementInterface
     */
    protected $_creditManagement;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var int
     */
    protected $_customerFlag;

    /**
     * @var int
     */
    protected $_shoppingCartFlag;

    /**
     * @var int
     */
    protected $_customerRedeemFlag;

    /**
     * Abstract StoreCredit constructor.
     * @param CreditManagementInterface $creditManagement
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CreditManagementInterface $creditManagement,
        ProductRepositoryInterface $productRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_creditManagement = $creditManagement;
        $this->_productRepository = $productRepository;
    }

    /**
     * @param array $args
     *
     * @throws GraphQlInputException
     */
    protected function validateArgs(array $args)
    {
        if ($this->_customerFlag && !isset($args['customer_id'])) {
            throw new GraphQlInputException(__('Customer id is required.'));
        }
        if ($this->_customerRedeemFlag) {
            if(!isset($args['customer_id'])){
                throw new GraphQlInputException(__('Customer id is required.'));
            }
            if(!isset($args['code'])){
                throw new GraphQlInputException(__('Redeem code is required.'));
            }
        }
        if ($this->_shoppingCartFlag && !isset($args['cart_id'])) {
            throw new GraphQlInputException(__('Shopping Cart id is required.'));
        }
    }
}
