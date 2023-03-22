<?php
declare(strict_types=1);

namespace Rent\Application\Property\Wizard;

use Bridge\Symfony\HttpFoundation\AddFlashMessage;
use Rent\Application\Property\DataGrid\Property\PropertyDataGrid;
use Rent\Application\Property\Wizard\AddBuildingUnitForms\BuildingUnitDispositionForm;
use Rent\Application\Property\Wizard\AddBuildingUnitForms\BuildingUnitInformationForm;
use Rent\Domain\Property\Building;
use Rent\Infrastructure\Repository\BuildingRepository;
use WizardBundle\Context\CustomContextData;
use WizardBundle\Context\WizardContext;
use WizardBundle\Form\SubmittedFormData;
use WizardBundle\Navigation\WizardInitializerInterface;
use WizardBundle\Navigation\WizardRedirectBeforeRenderInterface;
use WizardBundle\OnWizardFinishHandlerInterface;
use WizardBundle\Step\WizardFormStep;
use WizardBundle\Wizard;
use WizardBundle\WizardFactoryInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final class WizardForEntityCreation implements OnWizardFinishHandlerInterface, WizardFactoryInterface, WizardInitializerInterface, WizardRedirectBeforeRenderInterface
{
    public const NAME = 'create_building_unit_wizard';

    private const STEP_BUILDING_UNIT_INFORMATION = 'building_unit_information';
    private const STEP_BUILDING_UNIT_DISPOSITION = 'building_unit_disposition';

    public function __construct(
        private AddFlashMessage $addFlashMessage,
        private CreateBuildingUnitFromWizard $createBuildingUnitFromWizard,
        private TranslatorInterface $translator,
        private BuildingRepository $buildingRepository,
        private RouterInterface $router
    ) {
    }

    public function create() : Wizard
    {
        return new Wizard(
            self::NAME,
            [
                new WizardFormStep(
                    stepName: self::STEP_BUILDING_UNIT_INFORMATION,
                    formClass: BuildingUnitInformationForm::class,
                ),

                new WizardFormStep(
                    stepName: self::STEP_BUILDING_UNIT_DISPOSITION,
                    formClass: BuildingUnitDispositionForm::class,
                ),
            ],
            finishHandler: $this,
            finishTemplate: 'rent/property/wizard/create_building_unit_finish.html.twig',
            baseTemplate: 'rent/layout_admin.html.twig',
            redirectBeforeRender: $this,
            initializer: $this,
        );
    }

    public function initialize(Wizard $wizard, InputBag $queryParams) : WizardContext
    {
        $buildingId = $queryParams->get('buildingId');
        if ($buildingId === null) {
            return new WizardContext(self::getName());
        }

        $building = $this->buildingRepository->find($buildingId);
        Assert::isInstanceOf($building, Building::class);

        return new WizardContext(
            self::getName(),
            new SubmittedFormData([
            ]),
            new CustomContextData([
                'buildingId' => (string) $building->getId(),
            ])
        );
    }

    public function redirectBeforeRender(Wizard $wizard, ?WizardContext $wizardContext, InputBag $queryParams) : ?RedirectResponse
    {
        if (!isset($wizardContext->customContextData['buildingId'])) {
            return new RedirectResponse($this->router->generate('rent_property_list', [
                'filter' => PropertyDataGrid::PRIMARY_FILTER_ALL,
            ]));
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function finish(WizardContext $context) : ?RedirectResponse
    {
        ($this->createBuildingUnitFromWizard)($context);

        $this->addFlashMessage->success($this->translator->trans('building_unit_added', [], 'wizard.create_building_unit_wizard'));

        return null;
    }

    public static function getName() : string
    {
        return self::NAME;
    }
}
