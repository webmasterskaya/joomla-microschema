<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\Social;

final class Facebook extends OpenGraph
{
    public function getName(): string
    {
        return 'Facebook';
    }

    public function getProperties(): array
    {
        return [...parent::getProperties(),
            $this->property('appId', tag: 'fb:app_id'),
            $this->property('admins', ['string'], multiple: true, tag: 'fb:admins'),
        ];
    }
}
