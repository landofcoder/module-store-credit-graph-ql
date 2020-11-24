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

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Lof\StoreCredit\Api\CreditManagementInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Lof\StoreCreditGraphQl\Model\Data\MaskedCart;

/**
 * Class GiftByProductSku
 * @package Lof\StoreCreditGraphQl\Model\Resolver
 */
class SetStoreCredit implements ResolverInterface
{
    protected $_creditManagement;
    protected $_maskedCart;
    /**
     * AddByGiftId constructor.
     * @param CreditManagementInterface $creditManagement
     * @param MaskedCart $maskedCart
     */
    public function __construct(
        CreditManagementInterface $creditManagement,
        MaskedCart $maskedCart
    ) {
        $this->_creditManagement = $creditManagement;
        $this->_maskedCart = $maskedCart;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $customer_id = $context->getUserId();
        if($customer_id){
            $args['customer_id'] = (int)$customer_id;
        }
        $this->validateArgs($args);
        $result = $this->_creditManagement->set($this->getCardId($args, $context), (float)$args['creditAmount']);
        return [
            "base_credit_amount" => (float)$result[0],
            "credit_amount" => (float)$result[1],
            "credit_price_amount" => (float)$result[2]
        ];
    }
    /**
     * @param array $args
     *
     * @throws GraphQlInputException
     */
    public function validateArgs($args)
    {
        if (!isset($args['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        if (!isset($args['creditAmount'])) {
            throw new GraphQlInputException(__('Required parameter "creditAmount" is missing'));
        }
    }
    /**
     * @param array $data
     * @param ContextInterface $context
     * @return int
     * @throws GraphQlInputException
     */
    public function getCardId($data, $context)
    {
        try {
            $cart = $this->_maskedCart->getCartByMaskedId((string) $data['cart_id'], $context);
            return $cart->getId();
        } catch (\Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
        return 0;
    }
}
