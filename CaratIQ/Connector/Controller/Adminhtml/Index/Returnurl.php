<?php
namespace CaratIQ\Connector\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Backend\Model\Auth\Session as AdminSession;

class Returnurl extends Action
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var TokenFactory
     */
    protected $tokenFactory;

    /**
     * @var AdminSession
     */
    protected $adminSession;

    
    /**
     * Constructor.
     *
     * @param Action\Context $context
     * @param Curl $curl
     * @param TokenFactory $tokenFactory
     * @param AdminSession $adminSession
     */
    public function __construct(
        Action\Context $context,
        Curl $curl,
        TokenFactory $tokenFactory,
        AdminSession $adminSession,
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->tokenFactory = $tokenFactory;
        $this->adminSession = $adminSession;
    }

    /**
     * Execute method to handle the return URL, verify the auth code, and generate a Magento token.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            // Get the auth code from the return URL
            $authCode = $this->getRequest()->getParam('code');
            if (!$authCode) {
                throw new \Exception('Authorization code is missing.');
            }

            // Send the auth code to the third-party API for verification
            $this->curl->post('https://caratiq-cms.scaleupdevops.in/api/verify-auth-code', ['code' => $authCode]);
            $response = json_decode($this->curl->getBody(), true);

            
            // Check if the response is valid and successful
            if (!isset($response['status']) || $response['status'] !== 200) {
                throw new \Exception('Failed to verify the authorization code.');
            }

            // Get the logged-in admin user ID dynamically
            $adminUserId = $this->adminSession->getUser()->getId();
            
            if (!$adminUserId) {
                throw new \Exception('Unable to retrieve the logged-in admin user ID.');
            }

            $magentoToken = $this->_generateAdminToken($adminUserId);

            // Send the generated Magento token back to the third-party API
            $this->curl->post('https://caratiq-cms.scaleupdevops.in/api/verify-magento-token', [
                'magento_token' => $magentoToken,
                'token' => $response['data']['token'] ?? ''
            ]);

            // Add success message to be displayed on the next page
            $this->messageManager->addSuccessMessage(__('Authorization token has been successfully generated to the CaratIQ.'));
            // Redirect to the "Connect" page
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setUrl($this->_url->getUrl('caratiq/index/index')); // Change this to your Connect page URL

        } catch (\Exception $e) {
            // Log the error and display a user-friendly error message
            $this->messageManager->addErrorMessage(__('An error occurred while verifying the authorization code. Please try again.'));
            return $this->_redirect('admin/dashboard');
        }
    }

    /**
     * Generate an admin token for the specified user ID (without expiration).
     *
     * @param int $userId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _generateAdminToken($userId)
    {
        try {
            // Token Factory to generate an OAuth token
            $tokenModel = $this->tokenFactory->create();

            // Check if a token already exists for the admin user
            $existingToken = $tokenModel->loadByAdminId($userId);
            $futureDate = (new \DateTime())->modify('+10 years')->format('Y-m-d H:i:s');

            if ($existingToken->getId()) {
                // Token exists, so we modify it to update the expiration date
                $existingToken->setExpiresAt($futureDate);
                $existingToken->save();

                return $existingToken->getToken(); // Return the existing token
            } else {
                // No existing token, so we generate a new one
                $newToken = $tokenModel->createAdminToken($userId);

                // Set the expiration to 10 years in the future
                $tokenModel->loadByToken($newToken);
                $tokenModel->setExpiresAt($futureDate);
                $tokenModel->save();

                return $newToken; // Return the new token
            }
        } catch (\Exception $e) {
            // Log the error and throw a localized exception for user-friendly error display
            throw new \Magento\Framework\Exception\LocalizedException(__('Unable to generate admin token.'));
        }
    }

}
