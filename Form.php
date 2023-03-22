<?php
declare(strict_types=1);

namespace Rent\Application\Property\Wizard\AddPropertyForms;

use Bridge\Symfony\Form\StyledChoice\StyledChoice;
use Bridge\Symfony\Form\StyledChoice\StyledChoiceType;
use FormManager\AbstractForm;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class Form extends AbstractForm
{
    public const APARTMENT = 'apartment';
    public const FAMILY_HOUSE = 'family_house';
    public const BUILDING = 'building';
    public const OTHER = 'other';

    public function __construct(
        private TranslatorInterface $translator
    ){
    }

    public function buildForm(FormBuilderInterface $builder, array $options) : void
    {
        /** @var string $translationDomain */
        $translationDomain = $options['translation_domain'];

        $choices = [
            new StyledChoice(
                identifier: self::APARTMENT,
                title: $this->translator->trans('propertyType.title.apartment', [], $translationDomain),
                description: $this->translator->trans('propertyType.description.apartment', [], $translationDomain),
                icon: 'apartment'
            ),
            new StyledChoice(
                identifier: self::FAMILY_HOUSE,
                title: $this->translator->trans('propertyType.title.family_house', [], $translationDomain),
                description: $this->translator->trans('propertyType.description.family_house', [], $translationDomain),
                icon: 'house'
            ),
            new StyledChoice(
                identifier: self::BUILDING,
                title: $this->translator->trans('propertyType.title.building', [], $translationDomain),
                description: $this->translator->trans('propertyType.description.building', [], $translationDomain),
                icon: 'building',
                nestedFormClass: ChooseBuildingTypeForm::class
            ),
            new StyledChoice(
                identifier: self::OTHER,
                title: $this->translator->trans('propertyType.title.other', [], $translationDomain),
                description: $this->translator->trans('propertyType.description.other', [], $translationDomain),
                icon: 'building',
                nestedFormClass: ChooseBuildingUnitTypeForm::class
            ),
        ];

        $builder->add('propertyType', StyledChoiceType::class, [
            'label' => false,
            'styled_choices' => $choices,
            'layout' => StyledChoiceType::LAYOUT_HORIZONTAL,
            'selected_choices_data_class' => SelectPropertyEntityChoice::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver) : void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('translation_domain', 'rent.create_property_wizard.property_type_form');
    }
}
