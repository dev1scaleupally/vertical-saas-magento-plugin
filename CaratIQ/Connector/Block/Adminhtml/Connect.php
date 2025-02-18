<?php
namespace CaratIQ\Connector\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\HTTP\Client\Curl;

class Connect extends Template
{
    protected $connectionStatus;
    protected $storeManager;
    protected $curl;

    // Inject StoreManager and Curl via constructor
    public function __construct(
        Template\Context $context,
        Curl $curl,
        StoreManagerInterface $storeManager,
        array $data = []  // Add this array parameter
    ) 
    {
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        parent::__construct($context, $data);  // Make sure to pass $data to the parent constructor
    }

    public function setConnectionStatus($status)
    {
        $this->connectionStatus = $status;
    }

    public function getConnectionStatus()
    {
        return $this->connectionStatus;
    }

    public function isConnected()
    {
        // Add logic to check connection status from the API and return true or false
        $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        $apiUrl = 'https://vertical-saas.bndigital.dev/api/ecommerce-integration-status';

        $requestData = [
            'ecommerceType' => 'magento',
            'storeUrl' => $storeUrl
        ];

        $this->curl->addHeader("Content-Type", "application/json");
        $this->curl->post($apiUrl, json_encode($requestData));
        $response = json_decode($this->curl->getBody(), true);

        return isset($response['connectionStatus']) && $response['connectionStatus'];
    }

    public function getConnectUrl()
    {
        // Third-party URL
        $thirdPartyUrl = 'https://vertical-saas.bndigital.dev/magento-auth';

        // Your return URL, which is the Magento admin URL that handles the return
        $returnUrl = $this->getUrl('caratiq/index/returnurl', ['_secure' => true]);

        // Append the return_url as a query parameter to the third-party URL
        $redirectUrl = $thirdPartyUrl . '?redirect_uri=' . $returnUrl;

        return $redirectUrl;
    }

    public function getDisconnectUrl()
    {
        return $this->getUrl('caratiq/index/disconnect');  // Modify if needed
    }
}
