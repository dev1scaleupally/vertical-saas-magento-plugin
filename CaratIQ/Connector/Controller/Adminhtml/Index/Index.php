<?php
namespace CaratIQ\Connector\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;

class Index extends Action
{
    protected $resultPageFactory;
    protected $curl;
    protected $storeManager;


    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        Curl $curl,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->curl = $curl;
        $this->storeManager = $storeManager;    
    }

    public function execute()
    {
        // Check connection status via API
        $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        $apiUrl = 'https://vertical-saas.bndigital.dev/api/ecommerce-integration-status';
        
        // Prepare request data
        $requestData = [
            'ecommerceType' => 'magento',
            'storeUrl' => $storeUrl
        ];

        // Make the API request using cURL
        try {
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($apiUrl, $requestData);
            $response = json_decode($this->curl->getBody(), true);

            // Pass connection status to the view
            $connectionStatus = $response['connectionStatus'] ?? false;

        } catch (\Exception $e) {
            $connectionStatus = false;  // Default to false if there's an error
        }

        // Load the page defined in the layout file
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('CaratIQ'));

        // Pass the connection status to the block
        $resultPage->getLayout()->getBlock('caratiq_connector_block')->setConnectionStatus($connectionStatus);

        return $resultPage;
    }
}
