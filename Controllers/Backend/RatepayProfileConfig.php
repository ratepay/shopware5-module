<?php


use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ProfileConfigService;
use Shopware\Components\DependencyInjection\Container;

class Shopware_Controllers_Backend_RatepayProfileConfig extends Shopware_Controllers_Backend_Application
{

    protected $model = ProfileConfig::class;
    protected $alias = 'profile_config';

    /**
     * @var ProfileConfigService
     */
    private $profileConfigService;

    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippetManager;


    public function setContainer(Container $loader = null)
    {
        parent::setContainer($loader);

        $this->profileConfigService = $this->container->get(ProfileConfigService::class);
        $this->snippetManager = $this->container->get('snippets');
    }

    protected function getListQuery()
    {
        return parent::getListQuery()
            ->addSelect(['shop'])
            ->join($this->alias . '.shop', 'shop');
    }

    public function reloadProfileAction()
    {
        $snippetNamespace = $this->snippetManager->getNamespace('backend/ratepay/profile_config/messages');

        try {
            $result = $this->profileConfigService->refreshProfileConfig($this->Request()->getParam('ids'));

            if ($result === false) {
                $message = $snippetNamespace->get('invalid_profile');
            } else {
                $message = $snippetNamespace->get('profile_reloaded');
            }
        } catch (Exception $e) {
            $message = $snippetNamespace->get('unknown_error');
        }

        $this->View()->assign([
            'success' => true,
            'message' => $message
        ]);
    }

    public function save($data)
    {
        $snippetNamespace = $this->snippetManager->getNamespace('backend/ratepay/profile_config/messages');

        try {
            $returnData = parent::save($data);
        } catch (UniqueConstraintViolationException $e) {
            return [
                'success' => false,
                'error' => $snippetNamespace->get('profile_config_already_exists'),
                'message' => $snippetNamespace->get('profile_config_already_exists')
            ];
        }

        // RATEPLUG-165: shopware issue, that the response is not available in the frontend. so we will call the
        // `reloadProfileAction` if the no response is available. this issue is fixed in shopware 5.6
        if(version_compare(Shopware()->Config()->get('version'), '5.6', '>=')) {
            if (isset($returnData['data']['id'])) {
                try {
                    $result = $this->profileConfigService->refreshProfileConfig($returnData['data']['id']);

                    if ($result === false) {
                        $returnData['message'] = $snippetNamespace->get('invalid_profile');
                    } else {
                        $returnData['message'] = $snippetNamespace->get('profile_reloaded');
                    }
                } catch (Exception $e) {
                    $message = $snippetNamespace->get('unknown_error');
                    if ($e->getPrevious() && ((int)$e->getPrevious()->getCode()) === 23000) {
                        $message = $snippetNamespace->get('profile_config_already_exists');
                    }
                    $returnData['message'] = $message;
                }
            }
        }
        return $returnData;
    }


    public function searchAssociation($search, $association, $offset, $limit, $id = null, $filter = [], $sort = [])
    {
        if ($association === 'shop') {
            $filter['mainId'] = [
                'property' => 'mainId',
                'value' => null
            ];
        }
        return parent::searchAssociation($search, $association, $offset, $limit, $id, $filter, $sort);
    }
}