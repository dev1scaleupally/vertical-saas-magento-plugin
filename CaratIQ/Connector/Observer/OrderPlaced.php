<?php

namespace CaratIQ\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

// error_reporting(0);
// ini_set('display_errors', 0);

class OrderPlaced implements ObserverInterface
{
    protected $curl;
    protected $orderRepository;
    protected $storeManager;

    public function __construct(
        Curl $curl, 
        OrderRepositoryInterface $orderRepository,
        StoreManagerInterface $storeManager,
    )
    {
        $this->curl = $curl;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;    
    }

    public function execute(Observer $observer)
    {
        // Get the order IDs from the observer
        $order = $observer->getEvent()->getOrder();

        // Check if the order object is valid
        if ($order) {
            // Convert the order object to an array
            $orderData = $this->prepareOrderData($order);

            // Log the order data for debugging
            file_put_contents('var/log/order_data.log', print_r($orderData, true));

            // Convert the array to JSON
            $jsonData = json_encode($orderData);

            // Setup headers
            $this->curl->addHeader("Content-Type", "application/json");

            // Post the order data to the external API
            $this->curl->post('https://caratiq-cms.scaleupdevops.in/api/create-magento-order', $jsonData);

            // Optional: Handle the response
            // $response = $this->curl->getBody();
        } else {
            // Log if the order object is not found
            file_put_contents('var/log/order_data.log', "Order object not found.", FILE_APPEND);
        }
    }

    private function prepareOrderData($order)
    {
        $orderData = $order->toArray();
        
        // Get the shop URL
        $shopUrl = $this->storeManager->getStore()->getBaseUrl();
        $orderData['shop_url'] = $shopUrl;
        // Add customer details
        $orderData['customer_name'] = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
        $orderData['customer_email'] = $order->getCustomerEmail();

        // Add order items
        $orderData['items'] = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $orderData['items'][] = [
                'product_id' => $item->getProductId(),
                'order_id' => $item->getOrderId(),
                'product_name' => $item->getName(),
                'sku' => $item->getSku(),
                'quantity' => $item->getQtyOrdered(),
                'price' => $item->getPrice(),
                'row_total' => $item->getRowTotal(),
                'tax_amount' => $item->getTaxAmount(),
                'tax_percent' => $item->getTaxPercent(),
            ];
        }

        // Add shipping address
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $orderData['shipping_address'] = [
                'firstname' => $shippingAddress->getFirstname(),
                'lastname' => $shippingAddress->getLastname(),
                'street' => implode(', ', $shippingAddress->getStreet()),
                'city' => $shippingAddress->getCity(),
                'postcode' => $shippingAddress->getPostcode(),
                'country' => $shippingAddress->getCountryId(),
                'telephone' => $shippingAddress->getTelephone(),
            ];
        }

        // Add billing address
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $orderData['billing_address'] = [
                'firstname' => $billingAddress->getFirstname(),
                'lastname' => $billingAddress->getLastname(),
                'street' => implode(', ', $billingAddress->getStreet()),
                'city' => $billingAddress->getCity(),
                'postcode' => $billingAddress->getPostcode(),
                'country' => $billingAddress->getCountryId(),
                'telephone' => $billingAddress->getTelephone(),
                'email' => $billingAddress->getEmail(), // If you want to add billing email too
            ];
        }

        // Add tax details
        $orderData['tax_details'] = [
            'total_tax' => $order->getTaxAmount(),
            'base_tax' => $order->getBaseTaxAmount(),
            'shipping_tax_amount' => $order->getShippingTaxAmount(),
        ];

        return $orderData;
    }

}
