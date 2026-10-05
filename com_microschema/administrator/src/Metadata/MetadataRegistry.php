<?php

namespace Joomla\Component\Microschema\Administrator\Metadata;

final class MetadataRegistry
{
    /** @var array<string, class-string<DescriptorInterface>> */
    private array $schemaOrg = [];

    /** @var array<string, class-string<DescriptorInterface>> */
    private array $selectableSchemaOrg = [];

    /** @var array<string, class-string<DescriptorInterface>> */
    private array $socials = [];

    /** @param class-string<DescriptorInterface> $descriptorClass */
    public function registerSchemaOrg(string $descriptorClass, bool $selectable = true): void
    {
        $this->register($this->schemaOrg, $descriptorClass, 'Schema.org');

        if ($selectable) {
            $this->register($this->selectableSchemaOrg, $descriptorClass, 'Selectable Schema.org');
        }
    }

    /** @param class-string<DescriptorInterface> $descriptorClass */
    public function registerSocial(string $descriptorClass): void
    {
        $this->register($this->socials, $descriptorClass, 'Social');
    }

    /** @return array<string, class-string<DescriptorInterface>> */
    public function getSchemaOrg(): array
    {
        return $this->schemaOrg;
    }

    /** @return array<string, class-string<DescriptorInterface>> */
    public function getSelectableSchemaOrg(): array
    {
        return $this->selectableSchemaOrg;
    }

    /** @return array<string, class-string<DescriptorInterface>> */
    public function getSocials(): array
    {
        return $this->socials;
    }

    /**
     * @param array<string, class-string<DescriptorInterface>> $registry
     * @param class-string<DescriptorInterface>                $descriptorClass
     */
    private function register(array &$registry, string $descriptorClass, string $category): void
    {
        if (!is_a($descriptorClass, DescriptorInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf('%s metadata descriptor must implement %s.', $descriptorClass, DescriptorInterface::class));
        }

        $name = (new $descriptorClass())->getName();

        if ($name === '') {
            throw new \InvalidArgumentException(sprintf('%s metadata descriptor name must not be empty.', $category));
        }

        if (isset($registry[$name]) && $registry[$name] !== $descriptorClass) {
            throw new \LogicException(sprintf('%s metadata descriptor "%s" is already registered.', $category, $name));
        }

        $registry[$name] = $descriptorClass;
    }
}
