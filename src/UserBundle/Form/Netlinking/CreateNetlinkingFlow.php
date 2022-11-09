<?php

namespace UserBundle\Form\Netlinking;

use UserBundle\Entity\NetlinkingFlowEntity;
use UserBundle\Entity\NetlinkingUrlFlowEntity;
use UserBundle\Entity\NetlinkingUrlAnchorsFlowEntity;

/**
 * Class CreateNetlinkingFlow
 *
 * @package UserBundle\Form\Netlinking
 */
class CreateNetlinkingFlow extends FormFlow
{
    /**
     * @return array
     */
    protected function loadStepsConfig()
    {
        return [
            [
                'label' => 'urls',
                'form_type' => AddFirstStepType::class,
            ],
            [
                'label' => 'anchors',
                'form_type' => AddSecondStepType::class,
            ],
        ];
    }

    /**
     * @param integer $step
     * @param array   $options
     *
     * @return array
     */
    public function getFormOptions($step, array $options = array())
    {
        $options = parent::getFormOptions($step, $options);

        /** @var NetlinkingFlowEntity $formData */
        $formData = $this->getFormData();

        if ($step === 2) {
            $urlAnchors = [];

            /** @var NetlinkingUrlFlowEntity $url */
            foreach ($formData->getUrls() as $key => $url) {
                $netlinkingUrlAnchorsFlowEntity = new NetlinkingUrlAnchorsFlowEntity();
                $netlinkingUrlAnchorsFlowEntity->setUrl($url->getUrl());

                $this->anchorProcessing($formData, $netlinkingUrlAnchorsFlowEntity);

                $urlAnchors[] = $netlinkingUrlAnchorsFlowEntity;
            }

            $formData->setUrlAnchors($urlAnchors);
        }

        return $options;
    }
}