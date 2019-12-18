<?php
/**
 * GiaPhuGroup Co., Ltd.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GiaPhuGroup.com license that is
 * available through the world-wide-web at this URL:
 * https://www.giaphugroup.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PHPCuong
 * @package     PHPCuong_ProductReviewForm
 * @copyright   Copyright (c) 2019-2020 GiaPhuGroup Co., Ltd. All rights reserved. (http://www.giaphugroup.com/)
 * @license     https://www.giaphugroup.com/LICENSE.txt
 */

namespace PHPCuong\ProductReviewForm\Override\Review\Block;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Context;
use Magento\Customer\Model\Url;

class Form extends \Magento\Review\Block\Form
{
	/**
     * Customer Session Factory
     *
     * @var \Magento\Customer\Model\SessionFactory
     */
	protected $_customerSession;
	/**
     * Order Collection Factory
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
	protected $_orderCollectionFactory;
	/**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
	protected $_registry;
	/**
     * Review data
     *
     * @var \Magento\Review\Helper\Data
     */
    protected $_reviewData = null;

    /**
     * Catalog product model
     *
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Rating model
     *
     * @var \Magento\Review\Model\RatingFactory
     */
    protected $_ratingFactory;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    protected $urlEncoder;

    /**
     * Message manager interface
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $customerUrl;

    /**
     * @var array
     */
    protected $jsLayout;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * Form constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Review\Helper\Data $reviewData
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Review\Model\RatingFactory $ratingFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param Url $customerUrl
     * @param array $data
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @throws \RuntimeException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Review\Helper\Data $reviewData,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Url $customerUrl,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
		\Magento\Customer\Model\SessionFactory $customerSession,
		\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
		\Magento\Framework\Registry $registry
	) {
		$this->urlEncoder = $urlEncoder;
		$this->_customerSession = $customerSession;
		$this->_reviewData = $reviewData;
		$this->_orderCollectionFactory = $orderCollectionFactory;
		$this->_registry = $registry;
		$this->httpContext = $httpContext;
		parent::__construct($context, $urlEncoder, $reviewData, $productRepository, $ratingFactory, $messageManager, $httpContext, $customerUrl, $data);
		$this->jsLayout = isset($data['jsLayout']) ? $data['jsLayout'] : [];
		$this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
	}

	protected function _construct()
	{
		parent::_construct();

		$this->setAllowWriteReviewFlag(
        $this->httpContext->getValue(Context::CONTEXT_AUTH)
        || $this->_reviewData->getIsGuestAllowToWrite()
    );
    if (!$this->getAllowWriteReviewFlag()) {
        $queryParam = $this->urlEncoder->encode(
            $this->getUrl('*/*/*', ['_current' => true]) . '#review-form'
        );
        $this->setLoginLink(
            $this->getUrl(
                'customer/account/login/',
                [Url::REFERER_QUERY_PARAM_NAME => $queryParam]
            )
        );

				$this->setTemplate('Magento_Review::form.phtml');

    }
		else {
			if ($this->isCurrentCustomerPurchasedThisProduct()) {
				$this->setTemplate('Magento_Review::form.phtml');
			} else {
					$this->setTemplate('PHPCuong_ProductReviewForm::review/form.phtml');
					// You can set null here if you don't want to load any template
					// $this->setTemplate(null);
			}
		}
	}

	public function getCurrentCustomerId()
	{
		return $this->_customerSession->create()->getCustomer()->getId();
	}

	public function getCustomerOrders()
	{
		$orders = $this->_orderCollectionFactory->create()->addFieldToSelect(
            '*'
        )->addFieldToFilter(
            'customer_id',
            $this->getCurrentCustomerId()
        );

        return $orders;
	}

	public function getCurrentProduct()
	{
		return $this->_registry->registry('current_product');
	}

	public function isCurrentCustomerPurchasedThisProduct()
	{
		$product_ids = [];

		foreach ($this->getCustomerOrders() as $order) {
		    foreach ($order->getAllVisibleItems() as $item) {
		        $product_ids[$item->getProductId()] = $item->getProductId();
		    }
		}

		if (in_array($this->getCurrentProduct()->getId(), $product_ids)) {
			return true;
		} else {
			return false;
		}
	}
}
