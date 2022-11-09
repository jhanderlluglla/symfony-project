<?php

namespace UserBundle\Form\Netlinking;

use Craue\FormFlowBundle\Form\FormFlow as BasicFormFlow;

use Symfony\Component\Form\FormInterface;
use UserBundle\Entity\NetlinkingFlowEntity;
use UserBundle\Entity\NetlinkingUrlAnchorsFlowEntity;
use UserBundle\Entity\NetlinkingAnchorFlowEntity;

use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;

/**
 * Class FormFlow
 *
 * @package UserBundle\Form\Netlinking
 */
class FormFlow extends BasicFormFlow
{
    /** @var FormInterface[]  */
    private $formSteps = [];

    /**
     * {@inheritdoc}
     */
    public function getFormForStep($stepNumber, array $options = array())
    {
        if (!isset($this->formSteps[$stepNumber])) {
            $this->formSteps[$stepNumber] = parent::createFormForStep($stepNumber, $options);
        }
        return $this->formSteps[$stepNumber];
    }

    /**
     * @param NetlinkingFlowEntity           $formData
     * @param NetlinkingUrlAnchorsFlowEntity $netlinkingUrlAnchorsFlowEntity
     */
    protected function anchorProcessing($formData, $netlinkingUrlAnchorsFlowEntity)
    {
        $directoryList = $formData->getDirectoryList();
        $directories = $directoryList->getDirectories();
        $exchangeSites = $directoryList->getExchangeSite();

        $anchors = [];
        $directoryAnchors = [];
        $exchangeSitesAnchors = [];

        if (!empty($directories)) {
            /** @var Directory $directory */
            foreach ($directories as $directory) {
                $netlinkingAnchorFlowEntity = $netlinkingUrlAnchorsFlowEntity->hasAnchorForDirectory($directory->getId());

                if (is_null($netlinkingAnchorFlowEntity)) {
                    $netlinkingAnchorFlowEntity = new NetlinkingAnchorFlowEntity();
                }

                $netlinkingAnchorFlowEntity
                    ->setUrl($directory->getName())
                    ->setWebmasterAnchor($directory->getWebmasterAnchor())
                    ->setDirectory($directory->getId())
                ;

                $directoryAnchors[$directory->getId()] = $netlinkingAnchorFlowEntity;
                $anchors[] = $netlinkingAnchorFlowEntity;
            }
        }

        if (!empty($exchangeSites)) {
            /** @var ExchangeSite $exchangeSite */
            foreach ($exchangeSites as $exchangeSite) {
                $netlinkingAnchorFlowEntity = $netlinkingUrlAnchorsFlowEntity->hasAnchorForExchangeSite($exchangeSite->getId());

                if (is_null($netlinkingAnchorFlowEntity)) {
                    $netlinkingAnchorFlowEntity = new NetlinkingAnchorFlowEntity();
                }

                $netlinkingAnchorFlowEntity
                    ->setUrl($exchangeSite->getUrl())
                    ->setWebmasterAnchor($exchangeSite->getWebmasterAnchor())
                    ->setExchangeSite($exchangeSite->getId())
                ;

                $exchangeSitesAnchors[$exchangeSite->getId()] = $netlinkingAnchorFlowEntity;
                $anchors[] = $netlinkingAnchorFlowEntity;
            }
        }

        $netlinkingUrlAnchorsFlowEntity
            ->setAnchors($anchors)
            ->setDirectoryAnchors($exchangeSitesAnchors)
            ->setExchangeAnchors($exchangeSitesAnchors)
        ;
    }
}