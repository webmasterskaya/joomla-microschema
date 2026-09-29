<?php

namespace Joomla\Component\Microschema\Administrator\Metadata\SchemaOrg;

use Joomla\Component\Microschema\Administrator\Metadata\AbstractDescriptor;

final class Recipe extends AbstractDescriptor
{
    public function getName(): string
    {
        return 'Recipe';
    }

    public function getProperties(): array
    {
        return [
            $this->property('name', required: true), $this->property('image', ['URL', 'ImageObject'], required: true, multiple: true),
            $this->property('author', ['Person', 'Organization']), $this->property('datePublished', ['Date']),
            $this->property('description'), $this->property('prepTime', ['Duration']),
            $this->property('cookTime', ['Duration']), $this->property('totalTime', ['Duration']),
            $this->property('recipeYield'), $this->property('recipeCategory'), $this->property('recipeCuisine'),
            $this->property('recipeIngredient', ['string'], required: true, multiple: true),
            $this->property('recipeInstructions', ['string', 'HowToStep', 'HowToSection'], required: true, multiple: true),
            $this->property('nutrition', ['NutritionInformation']),
        ];
    }
}
