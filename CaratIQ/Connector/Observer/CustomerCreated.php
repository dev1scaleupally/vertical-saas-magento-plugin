<?php

namespace CaratIQ\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;


class CustomerCreated implements ObserverInterface
{
    protected $curl;

    protected $storeManager;


    public function __construct(
        Curl $curl,
        StoreManagerInterface $storeManager
    )
    {
        $this->curl = $curl;
        $this->storeManager = $storeManager;    
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $customerData = $customer->toArray();

        // Get the shop URL
        $shopUrl = $this->storeManager->getStore()->getBaseUrl();

        // Add shop URL to customer data
        $customerData['shop_url'] = $shopUrl;

        // Call 3rd party API
        $this->curl->post('https://caratiq-cms.scaleupdevops.in/api/create-magento-contact', json_encode($customerData));
        $this->curl->addHeader("Content-Type", "application/json");
    }
}
