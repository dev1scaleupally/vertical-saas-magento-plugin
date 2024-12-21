<?php
namespace CaratIQ\Connector\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;  // Required for POST actions
use Magento\Store\Model\StoreManagerInterface;

class Disconnect extends Action implements HttpPostActionInterface
{
    protected $curl;
    protected $resultRedirectFactory;
    protected $storeManager;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl,
        RedirectFactory $resultRedirectFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        // Get the base URL of the store
        $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        $apiUrl = 'https://13ca-103-108-5-157.ngrok-free.app/api/disconnect-ecommerce';

        // Prepare request data
        $requestData = [
            'ecommerceType' => 'magento',
            'storeUrl' => $storeUrl
        ];
        // Perform API request
        try {
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($apiUrl, json_encode($requestData));
            $response = json_decode($this->curl->getBody(), true);

            if ($response['connectionStatus'] === false) {
                $this->messageManager->addSuccessMessage(__('Successfully disconnected.'));
            } else {
                $this->messageManager->addErrorMessage(__('Failed to disconnect.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error disconnecting: %1', $e->getMessage()));
        }
        // Redirect back to the same page or any other page
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index');
    }
}
