<?php
declare(strict_types=1);

namespace Rent\Tests\Infrastructure\Repository;

use Misc\ObjectIdUtil;
use Rent\Domain\RentContract\RentContract;
use Rent\Domain\RentContract\RentDueDateSettings;
use Rent\Domain\RentContract\RentSettings;
use Rent\Infrastructure\DataFixtures\Factory\BuildingFactory;
use Rent\Infrastructure\DataFixtures\Factory\RentContractFactory;
use Rent\Infrastructure\Repository\RentContractRepository;
use Test\KernelTestUtilTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webmozart\Assert\Assert;
use Zenstruck\Foundry\Test\Factories;

class RepositoryFunctionalTest extends KernelTestCase
{
    use KernelTestUtilTrait;
    use Factories;

    public function testFindByTenant() : void
    {
        $contract = RentContractFactory::createOne()->object();
        $tenant = $contract->getTenant();

        $contractWithDifferentTenant = RentContractFactory::createOne();
        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $filteredContracts = $rentContractRepository->findByTenant($tenant->getId());
        static::assertCount(1, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            static::assertEquals($contract->getId(), $filteredContract->getId());
        }

        $contractWithSameTenant = RentContractFactory::createOne([
            'tenant' => $tenant,
        ]);

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $filteredContracts = $rentContractRepository->findByTenant($tenant->getId());
        static::assertCount(2, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            Assert::inArray((string) $filteredContract->getId(), [(string) $contract->getId(), (string) $contractWithSameTenant->getId()]);
            Assert::eq($filteredContract->getTenant()->getId(), $tenant->getId());
        }
    }

    public function testFindByLandlord() : void
    {
        $contract = RentContractFactory::createOne()->object();
        $landlord = $contract->getLandlord();
        $contractWithSameLandlord = RentContractFactory::createOne([
            'landlord' => $landlord,
        ]);
        $contractWithoutSameLandlord = RentContractFactory::createOne();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $filteredContracts = $rentContractRepository->findByLandlord($landlord->getId());
        static::assertCount(2, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            Assert::inArray((string) $filteredContract->getId(), [(string) $contract->getId(), (string) $contractWithSameLandlord->getId()]);
            Assert::eq($filteredContract->getLandlord()->getId(), $landlord->getId());
        }
    }

    public function testFindAllInProperties() : void
    {
        $contract = RentContractFactory::createOne()->object();
        $property = $contract->getProperty();
        $contractSameProperty = RentContractFactory::createOne([
            'property' => $property,
        ])->object();
        $contractDifferentProperty = RentContractFactory::createOne()->object();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $filteredContracts = $rentContractRepository->findAllInProperties([
            $property->getId(),
        ]);
        static::assertCount(2, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            Assert::inArray((string) $filteredContract->getId(), [(string) $contract->getId(), (string) $contractSameProperty->getId()]);
            Assert::eq($filteredContract->getProperty()->getId(), $property->getId());
        }

        $filteredContracts = $rentContractRepository->findAllInProperties([
            $property->getId(),
            $contractDifferentProperty->getProperty()->getId(),
        ]);
        static::assertCount(3, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            Assert::inArray((string) $filteredContract->getId(), [
                (string) $contract->getId(),
                (string) $contractSameProperty->getId(),
                (string) $contractDifferentProperty->getId(),
            ]);
        }
    }

    public function testTenantsIdsInProperties() : void
    {
        $contract = RentContractFactory::createOne()->object();
        $property = $contract->getProperty();
        $tenant = $contract->getTenant();
        $contractSameProperty = RentContractFactory::createOne([
            'property' => $property,
        ])->object();
        $tenantSameProperty = $contractSameProperty->getTenant();

        $contractDifferentProperty = RentContractFactory::createOne()->object();
        $differentProperty = $contractDifferentProperty->getProperty();
        $tenantDifferentProperty = $contractDifferentProperty->getTenant();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $filteredContractTenantsIds = $rentContractRepository->getTenantsIdsInProperties([$property->getId()]);
        static::assertCount(2, $filteredContractTenantsIds);
        foreach ($filteredContractTenantsIds as $filteredContractTenantId) {
            Assert::inArray((string) $filteredContractTenantId, [
                (string) $tenant->getId(), (string) $tenantSameProperty->getId(),
            ]);
        }

        $filteredContractTenantsIds = $rentContractRepository->getTenantsIdsInProperties([
            $property->getId(), $differentProperty->getId(),
        ]);
        static::assertCount(3, $filteredContractTenantsIds);
        foreach ($filteredContractTenantsIds as $filteredContractTenantId) {
            Assert::inArray((string) $filteredContractTenantId, [
                (string) $tenant->getId(), (string) $tenantSameProperty->getId(), (string) $tenantDifferentProperty->getId(),
            ]);
        }
    }

    public function testFindAllInPortfolios() : void
    {
        $contract = RentContractFactory::createOne()->object();
        $property = $contract->getProperty();
        $portfolio = $property->getPortfolio();

        $contractSamePropertySamePortfolio = RentContractFactory::createOne([
            'property' => $property,
        ])->object();

        $contractDifferentPropertySamePortfolio = RentContractFactory::createOne([
            'property' => BuildingFactory::new([
                'portfolio' => $portfolio,
            ]),
        ])->object();

        $differentContract = RentContractFactory::createOne()->object();
        $differentPortfolio = $differentContract->getPortfolio();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        // default rent validTo gte NOW or null
        $filteredContracts = $rentContractRepository->findAllInPortfolios([$portfolio->getId()]);
        static::assertCount(3, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            Assert::inArray((string) $filteredContract->getId(), [
                (string) $contract->getId(),
                (string) $contractSamePropertySamePortfolio->getId(),
                (string) $contractDifferentPropertySamePortfolio->getId(),
            ]);
        }
        $filteredContracts = $rentContractRepository->findAllInPortfolios([$portfolio->getId(), $differentPortfolio->getId()]);
        static::assertCount(4, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            if (ObjectIdUtil::equals($filteredContract->getPortfolio()->getId(), $portfolio->getId())) {
                Assert::inArray((string) $filteredContract->getId(), [
                    (string) $contract->getId(),
                    (string) $contractSamePropertySamePortfolio->getId(),
                    (string) $contractDifferentPropertySamePortfolio->getId(),
                ]);
            } else {
                Assert::eq($filteredContract->getId(), $differentContract->getId());
            }
        }
    }

    public function testActiveByTenant() : void
    {
        $activeContract = RentContractFactory::new()->active()->create()->object();
        $tenant = $activeContract->getTenant();

        $activeContractSameTenant = RentContractFactory::new([
            'tenant' => $tenant,
        ])->active()->create();
        $notActiveContract = RentContractFactory::new()->inactive()->create();
        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $filteredContracts = $rentContractRepository->findActiveByTenant($tenant->getId());
        static::assertCount(2, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            Assert::inArray((string) $filteredContract->getId(), [(string) $activeContract->getId(), (string) $activeContractSameTenant->getId()]);
            Assert::false($filteredContract->isPast());
        }
    }

    public function testActiveByProperty() : void
    {
        $activeContract = RentContractFactory::new()->active()->create()->object();
        $property = $activeContract->getProperty();
        $activeContractSameProperty = RentContractFactory::new([
            'property' => $property,
        ])->active()->create();
        $notActiveContractSameProperty = RentContractFactory::new([
            'property' => $property,
        ])->inactive()->create();
        $notActiveContractDifferentProperty = RentContractFactory::new()->inactive()->create();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $filteredContractsCounter = $rentContractRepository->getCountOfActiveByProperty($property->getId());
        static::assertEquals(2, $filteredContractsCounter);
        $filteredContracts = $rentContractRepository->findActiveByProperty($property->getId());
        static::assertCount(2, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            Assert::inArray((string) $filteredContract->getId(), [
                (string) $activeContract->getId(), (string) $activeContractSameProperty->getId(),
            ]);
            Assert::false($filteredContract->isPast());
        }
    }

    public function testGetCurrentTenantsIdsInProperties() : void
    {
        $activeContract = RentContractFactory::new()->active()->create()->object();
        $property = $activeContract->getProperty();
        $tenant = $activeContract->getTenant();
        $activeContractSameProperty = RentContractFactory::new([
            'property' => $property,
        ])->active()->create()->object();
        $samePropertyTenant = $activeContractSameProperty->getTenant();

        $differentTenantSameProperty = $activeContractSameProperty->getTenant();
        $inactiveContractSameProperty = RentContractFactory::new([
            'property' => $property,
        ])->inactive()->create()->object();
        $activeContractDifferentProperty = RentContractFactory::new()->active()->create()->object();
        $differentTenant = $activeContractDifferentProperty->getTenant();
        $differentProperty = $activeContractDifferentProperty->getProperty();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        // default contract validTo gte NOW or null
        $filteredContractTenantsIds = $rentContractRepository->getCurrentTenantsIdsInProperties([$property->getId()]);
        static::assertCount(2, $filteredContractTenantsIds);
        foreach ($filteredContractTenantsIds as $filteredContractTenantId) {
            Assert::inArray((string) $filteredContractTenantId, [
                (string) $tenant->getId(), (string) $samePropertyTenant->getId(),
            ]);
        }

        $filteredContractTenantsIds = $rentContractRepository->getCurrentTenantsIdsInProperties([
            $property->getId(), $differentProperty->getId(),
        ]);
        static::assertCount(3, $filteredContractTenantsIds);
        foreach ($filteredContractTenantsIds as $filteredContractTenantId) {
            Assert::inArray((string) $filteredContractTenantId, [
                (string) $tenant->getId(), (string) $samePropertyTenant->getId(), (string) $differentTenant->getId(),
            ]);
        }
    }

    public function testActiveContractsInProperties() : void
    {
        $activeContract = RentContractFactory::new()->active()->create()->object();
        $property = $activeContract->getProperty();
        $contractSamePropertyInactive = RentContractFactory::new([
            'property' => $property,
        ])->inactive()->create()->object();
        $contractDifferentPropertyActive = RentContractFactory::new()->active()->create()->object();
        $differentActiveProperty = $contractDifferentPropertyActive->getProperty();
        $contractDifferentPropertyInactive = RentContractFactory::new()->inactive()->create()->object();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $filteredContracts = $rentContractRepository->findActiveContractsInProperties([$property->getId()]);
        static::assertCount(1, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            Assert::eq($filteredContract->getId(), $activeContract->getId());
            Assert::eq($filteredContract->getProperty()->getId(), $property->getId());
        }

        $filteredContracts = $rentContractRepository->findActiveContractsInProperties([
            $property->getId(), $differentActiveProperty->getId(),
        ]);
        static::assertCount(2, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            if (ObjectIdUtil::equals($filteredContract->getId(), $activeContract->getId())) {
                Assert::eq($filteredContract->getProperty()->getId(), $property->getId());
            } else {
                Assert::eq($filteredContract->getId(), $contractDifferentPropertyActive->getId());
                Assert::eq($filteredContract->getProperty()->getId(), $differentActiveProperty->getId());
            }
        }
    }

    public function testFindPastByProperty() : void
    {
        $activeContract = RentContractFactory::new()->active()->create()->object();
        $property = $activeContract->getProperty();
        $inactiveContractSameProperty = RentContractFactory::new([
            'property' => $property,
        ])->inactive()->create()->object();
        $inactiveContractDifferentProperty = RentContractFactory::new()->inactive()->create();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $filteredContracts = $rentContractRepository->findPastByProperty($property->getId());
        static::assertCount(1, $filteredContracts);

        foreach ($filteredContracts as $filteredContract) {
            Assert::eq($filteredContract->getId(), $inactiveContractSameProperty->getId());
            Assert::true($filteredContract->isPast());
        }
    }

    public function testFindAllActive() : void
    {
        // Get rid of previous contracts
        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $contracts = $rentContractRepository->findAll();
        /** @var RentContract $contract */
        foreach ($contracts as $contract) {
            if (!$contract->isArchived()) {
                $contract->archive(false);
            }
        }

        $activeContract = RentContractFactory::new()->active()->create()->object();
        $differentActiveContract = RentContractFactory::new()->active()->create();
        $notActiveContract = RentContractFactory::new()->inactive()->create();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        // default rent validTo gte NOW or null
        $filteredContracts = $rentContractRepository->findAllActive();
        static::assertCount(2, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            Assert::inArray((string) $filteredContract->getId(), [
                (string) $activeContract->getId(), (string) $differentActiveContract->getId(),
            ]);
            Assert::false($filteredContract->isPast());
        }
    }

    public function testLatestByProperty() : void
    {
        $contract = RentContractFactory::createOne([
            'rentSettings' => new RentSettings(
                new \DateTimeImmutable(),
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 3 months')),
                new RentDueDateSettings(15, RentDueDateSettings::BACKWARD_MONTHLY_PAYMENT)
            ),
        ])->object();
        $property = $contract->getProperty();
        $laterContractWithSameProperty = RentContractFactory::createOne([
            'property' => $property,
            'rentSettings' => new RentSettings(
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 6 months')),
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 9 months')),
                new RentDueDateSettings(15, RentDueDateSettings::BACKWARD_MONTHLY_PAYMENT)
            ),
        ])->object();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $result = $rentContractRepository->findLatestByProperty($property->getId());
        static::assertInstanceOf(RentContract::class, $result);
        static::assertEquals($result->getId(), $laterContractWithSameProperty->getId());

        $latestContractWithSameProperty = RentContractFactory::createOne([
            'property' => $property,
            'rentSettings' => new RentSettings(
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 10 months')),
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 12 months')),
                new RentDueDateSettings(15, RentDueDateSettings::BACKWARD_MONTHLY_PAYMENT)
            ),
        ])->object();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $result = $rentContractRepository->findLatestByProperty($property->getId());
        static::assertInstanceOf(RentContract::class, $result);
        static::assertEquals($result->getId(), $latestContractWithSameProperty->getId());
    }

    public function testEndingInProperties() : void
    {
        $contractEndingInTwoMonths = RentContractFactory::createOne([
            'rentSettings' => new RentSettings(
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('- 3 months')),
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 2 months')),
                new RentDueDateSettings(15, RentDueDateSettings::BACKWARD_MONTHLY_PAYMENT)
            ),
        ])->object();
        $property = $contractEndingInTwoMonths->getProperty();

        $contractEndingInFourMonthsSameProperty = RentContractFactory::createOne([
            'property' => $property,
            'rentSettings' => new RentSettings(
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('- 3 months')),
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 4 months')),
                new RentDueDateSettings(15, RentDueDateSettings::BACKWARD_MONTHLY_PAYMENT)
            ),
        ])->object();

        $contractEndingInFourMonthsDifferentProperty = RentContractFactory::createOne([
            'rentSettings' => new RentSettings(
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('- 3 months')),
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 4 months')),
                new RentDueDateSettings(15, RentDueDateSettings::BACKWARD_MONTHLY_PAYMENT)
            ),
        ])->object();
        $differentProperty = $contractEndingInFourMonthsDifferentProperty->getProperty();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        // default value 3 months
        $filteredContracts = $rentContractRepository->findEndingInProperties([$property->getId()]);
        static::assertCount(1, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            static::assertEquals($filteredContract->getId(), $contractEndingInTwoMonths->getId());
            static::assertEquals($filteredContract->getProperty()->getId(), $property->getId());
        }
        $filteredContracts = $rentContractRepository->findEndingInProperties([$property->getId()], 5);
        static::assertCount(2, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            Assert::inArray((string) $filteredContract->getId(), [
                (string) $contractEndingInTwoMonths->getId(),
                (string) $contractEndingInFourMonthsSameProperty->getId(),
            ]);
            static::assertEquals($filteredContract->getProperty()->getId(), $property->getId());
        }
        $filteredContracts = $rentContractRepository->findEndingInProperties(
            [$property->getId(), $differentProperty->getId()],
            5
        );
        static::assertCount(3, $filteredContracts);
        foreach ($filteredContracts as $filteredContract) {
            if (ObjectIdUtil::equals($filteredContract->getProperty()->getId(), $property->getId())) {
                Assert::inArray((string) $filteredContract->getId(), [
                    (string) $contractEndingInTwoMonths->getId(),
                    (string) $contractEndingInFourMonthsSameProperty->getId(),
                ]);
            } else {
                static::assertEquals($filteredContract->getProperty()->getId(), $differentProperty->getId());
                static::assertEquals($filteredContract->getId(), $contractEndingInFourMonthsDifferentProperty->getId());
            }
        }
    }

    public function testFindFutureContractsInProperty() : void
    {
        $futureContract = RentContractFactory::createOne([
            'rentSettings' => new RentSettings(
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 3 months')),
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 6 months')),
                new RentDueDateSettings(15, RentDueDateSettings::BACKWARD_MONTHLY_PAYMENT)
            ),
        ])->object();
        $property = $futureContract->getProperty();

        $futureContractSameProperty = RentContractFactory::createOne([
            'property' => $property,
            'rentSettings' => new RentSettings(
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 7 months')),
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 10 months')),
                new RentDueDateSettings(15, RentDueDateSettings::BACKWARD_MONTHLY_PAYMENT)
            ),
        ])->object();

        $futureContractDifferentProperty = RentContractFactory::createOne([
            'rentSettings' => new RentSettings(
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 7 months')),
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 10 months')),
                new RentDueDateSettings(15, RentDueDateSettings::BACKWARD_MONTHLY_PAYMENT)
            ),
        ])->object();

        $notFutureContractSameProperty = RentContractFactory::createOne([
            'property' => $property,
            'rentSettings' => new RentSettings(
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('- 1 months')),
                \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+ 2 months')),
                new RentDueDateSettings(15, RentDueDateSettings::BACKWARD_MONTHLY_PAYMENT)
            ),
        ])->object();

        $rentContractRepository = self::getServiceByClass(RentContractRepository::class);
        $filteredContracts = $rentContractRepository->findFutureContractsInProperty($property->getId());
        static::assertCount(2, $filteredContracts);

        foreach ($filteredContracts as $filteredContract) {
            Assert::inArray((string) $filteredContract->getId(), [
                (string) $futureContract->getId(), (string) $futureContractSameProperty->getId(),
            ]);
            static::assertEquals($filteredContract->getProperty()->getId(), $property->getId());
        }
    }
}
