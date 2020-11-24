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

namespace Lof\StoreCreditGraphQl\Model\Transaction;

use Magento\Framework\GraphQl\ConfigInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\FieldEntityAttributesInterface;

/**
 * Retrieve filterable attributes for Category queries
 */
class TransactionFilterAttributesForAst implements FieldEntityAttributesInterface
{
    /**
     * Map schema fields to entity attributes
     *
     * @var array
     */
    private $fieldMapping = [
        'ids' => 'transaction_id'
    ];

    /**
     * @var array
     */
    private $additionalFields = [
        'description',
        'additional_info'
    ];

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     * @param array $additionalFields
     * @param array $attributeFieldMapping
     */
    public function __construct(
        ConfigInterface $config,
        array $additionalFields = [],
        array $attributeFieldMapping = []
    ) {
        $this->config = $config;
        $this->additionalFields = array_merge($this->additionalFields, $additionalFields);
        $this->fieldMapping = array_merge($this->fieldMapping, $attributeFieldMapping);
    }

    /**
     * @inheritdoc
     *
     * Gather attributes for Transaction filtering
     * Example format ['attributeNameInGraphQl' => ['type' => 'String'. 'fieldName' => 'attributeNameInSearchCriteria']]
     *
     * @return array
     */
    public function getEntityAttributes() : array
    {
        $transactionFilterType = $this->config->getConfigElement('CreditTransactionFilterInput');

        if (!$transactionFilterType) {
            throw new \LogicException(__("CreditTransactionFilterInput type not defined in schema."));
        }

        $fields = [];
        foreach ($transactionFilterType->getFields() as $field) {
            $fields[$field->getName()] = [
                'type' => 'String',
                'fieldName' => $this->fieldMapping[$field->getName()] ?? $field->getName(),
            ];
        }

        foreach ($this->additionalFields as $additionalField) {
            $fields[$additionalField] = [
                'type' => 'String',
                'fieldName' => $additionalField,
            ];
        }

        return $fields;
    }
}
