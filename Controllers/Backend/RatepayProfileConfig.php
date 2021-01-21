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


    public function setContainer(Container $loader = null)
    {
        parent::setContainer($loader);

        $this->profileConfigService = $this->container->get(ProfileConfigService::class);
    }

    protected function getListQuery()
    {
        return parent::getListQuery()
            ->addSelect(['shop'])
            ->join($this->alias . '.shop', 'shop');
    }

    public function save($data)
    {
        $snippetManager = $this->container->get('snippets');
        /** @var Enlight_Components_Snippet_Namespace $snippetNamespace */
        $snippetNamespace = $snippetManager->getNamespace('backend/ratepay/profile_config/messages');


        try {
            $returnData = parent::save($data);
        } catch (UniqueConstraintViolationException $e) {
            return [
                'success' => false,
                'error' => $snippetNamespace->get('profile_config_already_exists'),
                'message' => $e->getMessage()
            ];
        }

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