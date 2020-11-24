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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\ArgumentApplier\Filter;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\ArgumentApplier\Sort;
use Magento\Search\Model\Query;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Lof\StoreCredit\Api\CreditManagementInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder;
/**
 * Class GiftByProductSku
 * @package Lof\StoreCreditGraphQl\Model\Resolver
 */
class CustomerCreditTransaction implements ResolverInterface
{
    /**
     * @var string
     */
    private const SPECIAL_CHARACTERS = '-+~/\\<>\'":*$#@()!,.?`=%&^';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CreditManagementInterface
     */
    private $creditManagement;

    /**
     * @var Builder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CreditManagementInterface $creditManagement,
        Builder $searchCriteriaBuilder
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->creditManagement = $creditManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $customer_id = $context->getUserId();
        if(!$customer_id){
            throw new GraphQlInputException(__('Required logged in customer account.'));
        }
        $args["customer_id"] = $customer_id;
        $store = $context->getExtensionAttributes()->getStore();

        if (isset($args['currentPage']) && $args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if (isset($args['pageSize']) && $args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        if (!isset($args['filters'])) {
            //When no filters are specified, get the root category
            $args['filters']['customer_id'] = ['eq' => $customer_id];
        }
        try {
            $filterResult = $this->getTransactionResult($args, $store);
        } catch (InputException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
        return $filterResult;
    }

    /**
     * Search for transactions
     *
     * @param array $criteria
     * @param StoreInterface $store
     * @return mixed
     * @throws InputException
     */
    public function getTransactionResult(array $criteria, StoreInterface $store)
    {
        $criteria[Filter::ARGUMENT_NAME] = $this->formatMatchFilters($criteria['filters'], $store);
        $criteria[Sort::ARGUMENT_NAME]['transaction_id'] = ['DESC'];
        
        $searchCriteria = $this->searchCriteriaBuilder->build('lofCustomerCreditTransaction', $criteria);
        $pageSize = $criteria['pageSize'] ?? 20;
        $currentPage = $criteria['currentPage'] ?? 1;
        $searchCriteria->setPageSize($pageSize)->setCurrentPage($currentPage);
        
        $transactions = $this->creditManagement->getCreditTransactionsByCustId($criteria["customer_id"], $searchCriteria);
        
        $totalPages = 0;
        if ($transactions->getTotalCount() > 0 && $searchCriteria->getPageSize() > 0) {
            $totalPages = ceil($transactions->getTotalCount() / $searchCriteria->getPageSize());
        }
        if ($searchCriteria->getCurrentPage() > $totalPages && $transactions->getTotalCount() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$searchCriteria->getCurrentPage(), $totalPages]
                )
            );
        }

        return [
            'items' => $transactions->getItems(),
            'total_count' => $transactions->getTotalCount(),
            'page_info' => [
                'total_pages' => $totalPages,
                'page_size' => $searchCriteria->getPageSize(),
                'current_page' => $searchCriteria->getCurrentPage(),
            ]
        ];
    }

    /**
     * Format match filters to behave like fuzzy match
     *
     * @param array $filters
     * @param StoreInterface $store
     * @return array
     * @throws InputException
     */
    private function formatMatchFilters(array $filters, StoreInterface $store): array
    {
        $minQueryLength = $this->scopeConfig->getValue(
            Query::XML_PATH_MIN_QUERY_LENGTH,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        foreach ($filters as $filter => $condition) {
            $conditionType = current(array_keys($condition));
            if ($conditionType === 'match') {
                $searchValue = trim(str_replace(self::SPECIAL_CHARACTERS, '', $condition[$conditionType]));
                $matchLength = strlen($searchValue);
                if ($matchLength < $minQueryLength) {
                    throw new InputException(__('Invalid match filter. Minimum length is %1.', $minQueryLength));
                }
                unset($filters[$filter]['match']);
                $filters[$filter]['like'] = '%' . $searchValue . '%';
            }
        }
        return $filters;
    }
}
