<?php

namespace Joomla\Component\Microschema\Administrator\Event;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\Component\Microschema\Administrator\Metadata\DescriptorInterface;
use Joomla\Component\Microschema\Administrator\Metadata\MetadataRegistry;

final class RegisterMetadataEvent extends AbstractEvent
{
    public const NAME = 'onMicroschemaRegisterMetadata';

    public function __construct(private readonly MetadataRegistry $registry)
    {
        parent::__construct(self::NAME);
    }

    /** @param class-string<DescriptorInterface> $descriptorClass */
    public function registerSchemaOrg(string $descriptorClass, bool $selectable = true): void
    {
        $this->registry->registerSchemaOrg($descriptorClass, $selectable);
    }

    /** @param class-string<DescriptorInterface> $descriptorClass */
    public function registerSocial(string $descriptorClass): void
    {
        $this->registry->registerSocial($descriptorClass);
    }

    public function getRegistry(): MetadataRegistry
    {
        return $this->registry;
    }
}
