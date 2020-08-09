<?php

namespace Nimbus\AlternateCms\Block;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class Alternate extends \Magento\Framework\View\Element\Template {

    protected $request;
    protected $cmsModel;
    protected $storeManager;
    protected $storeRepository;
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Cms\Model\Page $cmsModel,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
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

            $currentWebsiteId = $this->storeManager->getWebsite()->getId();
            $storeIds = $this->cmsModel->getStores();

            //If it's all stores, get the website store ids
            if (count($storeIds) == 1 && $storeIds[0] == 0) {
                $storeIds = $this->storeManager->getWebsite()->getStoreIds();
            }

            $identifier = $this->cmsModel->getIdentifier();

            //If it's home, remove identifier as it's the base url
            if ($this->request->getControllerName() == "index" && $this->request->getActionName() == "index") {
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
        $isCms = false;

        if ($this->request->getRouteName() == "cms") {
            $isCms = true;
        }

        return $isCms;
    }

    /**
     * Get the magento locale and format for the SEO Crawler language type
     * @param Store $store
     * @return string
     */
    private function getFormattedStoreLocale(Store $store)
    {
        $locale = $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $store->getId());
        return str_replace("_", "-", strtolower($locale));
    }
}
