<?php

namespace Nimbus\AlternateCms\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Cms\Model\Page as CmsPage;
use Magento\Directory\Helper\Data as DirectoryHelperData;

class Alternate extends \Magento\Framework\View\Element\Template {

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var \Magento\Cms\Model\Page
     */
    protected $cmsModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Alternate constructor.
     * @param Context                  $context
     * @param HttpRequest              $request
     * @param CmsPage                  $cmsModel
     * @param StoreManagerInterface    $storeManager
     * @param StoreRepositoryInterface $storeRepository
     * @param ScopeConfigInterface     $scopeConfig
     * @param array                    $data
     */
    public function __construct(
        Context $context,
        HttpRequest $request,
        CmsPage $cmsModel,
        StoreManagerInterface $storeManager,
        StoreRepositoryInterface $storeRepository,
        ScopeConfigInterface $scopeConfig,
        array $data = [])
    {
        $this->request          = $request;
        $this->cmsModel         = $cmsModel;
        $this->storeManager     = $storeManager;
        $this->storeRepository  = $storeRepository;
        $this->scopeConfig      = $scopeConfig;

        parent::__construct($context, $data);
    }

    /**
     * Return a list of variations of the CMS page in other stores under the same Website
     * @return array
     */
    public function getAlternateList() {
        $list = [];

        if ($this->isCmsPage() && $this->cmsModel->getIdentifier() != "no-route") {
            $website = $this->storeManager->getWebsite();
            $currentWebsiteId = $website->getId();
            $storeIds = $this->cmsModel->getStores();

            //If it's all stores, get the website store ids
            if (count($storeIds) === 1 && (int) $storeIds[0] === 0) {
                $storeIds = $website->getStoreIds();
            }

            $identifier = $this->cmsModel->getIdentifier();

            //If it's home, remove identifier as it's the base url
            if ($this->request->getControllerName() === "index" && $this->request->getActionName() === "index") {
                $identifier = "";
            }

            foreach ($storeIds as $storeId) {

                $store = $this->storeRepository->getById($storeId);

                if ($store->getWebsite()->getId() == $currentWebsiteId) {

                    $list[] = [
                        'url' => $store->getBaseUrl() . $identifier,
                        'lang' => $this->getFormattedStoreLocale($store)
                    ];
                }
            }
        }

        return $list;
    }

    /**
     * Validate if the current page is a CMS page.
     * @return boolean
     */
    private function isCmsPage()
    {
        return $this->request->getRouteName() === "cms";
    }

    /**
     * Get the magento locale and format for the SEO Crawler language type
     * @param Store $store
     * @return string
     */
    private function getFormattedStoreLocale(Store $store)
    {
        $locale = $this->scopeConfig->getValue(DirectoryHelperData::XML_PATH_DEFAULT_LOCALE, ScopeInterface::SCOPE_STORE, $store->getId());
        return str_replace("_", "-", strtolower($locale));
    }
}
