<?php
declare(strict_types=1);

namespace Rent\Infrastructure\Repository;

use Doctrine\Persistence\ManagerRegistry;
use MongoDB\BSON\ObjectId;
use Common\FileManager\Infrastructure\Repository\AllFromPortfolioIdsInterface;
use QueryObject\DoctrineODM\QueryableRepository;
use QueryObject\DoctrineODM\ResultSet;
use QueryObject\HydrationMode;
use Rent\Domain\RentContract\RentContract;
use Rent\Infrastructure\Query\Filter\PortfolioFilter;
use Rent\Infrastructure\Query\QueryObject\RentContractQuery;
use Webmozart\Assert\Assert;

/**
 * @extends QueryableRepository<RentContract>
 *
 * @method RentContractQuery createQuery()
 * @method RentContract      get(ObjectId $id)
 */
class Repository extends QueryableRepository implements AllFromPortfolioIdsInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RentContract::class, RentContractQuery::class);
    }

    /**
     * @return ResultSet<RentContract>
     */
    public function findByTenant(ObjectId $tenantId) : ResultSet
    {
        $query = $this->createQuery()
            ->byTenantId($tenantId)
            ->isArchived(false);

        return $this->fetch($query);
    }

    /**
     * @return ResultSet<RentContract>
     */
    public function findActiveByTenant(ObjectId $tenantId) : ResultSet
    {
        $query = $this->createQuery()
            ->byTenantId($tenantId)
            ->isArchived(false)
            ->byValidFromLowerThan(new \DateTimeImmutable())
            ->byValidToGreaterThanOrNull(new \DateTimeImmutable());

        return $this->fetch($query);
    }

    /**
     * @return ResultSet<RentContract>
     */
    public function findByLandlord(ObjectId $landlordId) : ResultSet
    {
        $query = $this->createQuery()
            ->byLandlordId($landlordId)
            ->isArchived(false);

        return $this->fetch($query);
    }

    /**
     * @param iterable<ObjectId> $propertyIds
     *
     * @return ResultSet<RentContract>
     */
    public function findAllInProperties(iterable $propertyIds) : ResultSet
    {
        Assert::allIsInstanceOf($propertyIds, ObjectId::class);

        $query = $this->createQuery()
            ->byPropertyIds(\iter\toArray($propertyIds))
            ->isArchived(false);

        return $this->fetch($query);
    }

    public function findLatestByProperty(ObjectId $propertyId) : ?RentContract
    {
        $query = $this->createQuery()
            ->byPropertyIds([$propertyId])
            ->isArchived(false)
            ->sortByValidTo();

        return $this->fetchOne($query);
    }

    /**
     * @param iterable<ObjectId> $propertyIds
     *
     * @return ResultSet<RentContract>
     */
    public function findActiveContractsInProperties(iterable $propertyIds) : ResultSet
    {
        Assert::allIsInstanceOf($propertyIds, ObjectId::class);

        $query = $this->createQuery()
            ->byPropertyIds(\iter\toArray($propertyIds))
            ->byValidFromLowerThan(new \DateTimeImmutable())
            ->isArchived(false)
            ->byValidToGreaterThanOrNull(new \DateTimeImmutable());

        return $this->fetch($query);
    }

    /**
     * @param iterable<ObjectId> $propertyIds
     *
     * @return ResultSet<RentContract>
     */
    public function findEndingInProperties(iterable $propertyIds, int $month = 3) : ResultSet
    {
        Assert::allIsInstanceOf($propertyIds, ObjectId::class);

        $dateLimit = \DateTimeImmutable::createFromMutable((new \DateTime())->modify('+' . $month . ' month'));

        $query = $this->createQuery()
            ->byPropertyIds(\iter\toArray($propertyIds))
            ->byValidFromLowerThan(new \DateTimeImmutable())
            ->byValidToLowerThan($dateLimit)
            ->byValidToGreaterThanOrNull(new \DateTimeImmutable())
            ->isArchived(false);

        return $this->fetch($query);
    }

    /**
     * @return ResultSet<RentContract>
     */
    public function findActiveByProperty(ObjectId $propertyId) : ResultSet
    {
        $query = $this->createQuery()
            ->byPropertyIds([$propertyId])
            ->byValidToGreaterThanOrNull(new \DateTimeImmutable())
            ->isArchived(false)
            ->sortByValidFrom();

        return $this->fetch($query);
    }

    public function getCountOfActiveByProperty(ObjectId $propertyId) : int
    {
        $query = $this->createQuery()
            ->byPropertyIds([$propertyId])
            ->byValidToGreaterThanOrNull(new \DateTimeImmutable())
            ->isArchived(false);

        return $this->countQuery($query);
    }

    /**
     * @param iterable<ObjectId> $propertyIds
     *
     * @return array<ObjectId>
     */
    public function getTenantsIdsInProperties(iterable $propertyIds) : array
    {
        Assert::allIsInstanceOf($propertyIds, ObjectId::class);

        $query = $this->createQuery()
            ->selectTenantId()
            ->byPropertyIds(\iter\toArray($propertyIds))
            ->isArchived(false);

        /** @var array<array{tenant:array{id:ObjectId}}> $queryResult */
        $queryResult = $this->fetch($query, HydrationMode::ARRAY);

        return \array_map(fn (array $item) => $item['tenant']['id'], $queryResult);
    }

    /**
     * @param iterable<ObjectId> $propertyIds
     *
     * @return array<ObjectId> tenantIds
     */
    public function getCurrentTenantsIdsInProperties(iterable $propertyIds, \DateTimeInterface $now = new \DateTimeImmutable()) : array
    {
        Assert::allIsInstanceOf($propertyIds, ObjectId::class);

        $query = $this->createQuery()
            ->selectTenantId()
            ->byPropertyIds(\iter\toArray($propertyIds))
            ->byValidFromLowerThan($now)
            ->byValidToGreaterThanOrNull($now)
            ->isArchived(false);

        /** @var array<array{tenant: array{id: ObjectId}}> $queryResult */
        $queryResult = $this->fetch($query, HydrationMode::ARRAY);

        return \array_map(fn (array $item) => $item['tenant']['id'], $queryResult);
    }

    /**
     * @return ResultSet<RentContract>
     */
    public function findPastByProperty(ObjectId $propertyId) : ResultSet
    {
        $query = $this->createQuery()
            ->byPropertyIds([$propertyId])
            ->byValidToLowerThan(new \DateTimeImmutable(), false)
            ->isArchived(false)
            ->sortByValidFrom();

        return $this->fetch($query);
    }

    /**
     * @return ResultSet<RentContract>
     */
    public function findAllActive(\DateTimeInterface $now = new \DateTimeImmutable()) : ResultSet
    {
        $query = $this->createQuery()
            ->byValidFromLowerThan($now)
            ->byValidToGreaterThanOrNull($now)
            ->isArchived(false)
            ->sortByValidFrom();

        return $this->fetch($query);
    }

    /**
     * Finds all rent contracts that belong to a portfolio.
     *
     * @param iterable<ObjectId> $portfolioIds
     *
     * @return ResultSet<RentContract>
     */
    public function findAllInPortfolios(iterable $portfolioIds) : ResultSet // @phpstan-ignore-line
    {
        Assert::allIsInstanceOf($portfolioIds, ObjectId::class);

        $query = $this->createQuery()
            ->isArchived(false)
            ->addFilter(PortfolioFilter::filter(\iter\toArray($portfolioIds)));

        return $this->fetch($query);
    }

    /**
     * Finds upcoming rent contracts for property - used for badge upcoming.
     *
     * @return ResultSet<RentContract>
     */
    public function findFutureContractsInProperty(ObjectId $propertyId) : ResultSet
    {
        $query = $this->createQuery()
            ->byPropertyIds([$propertyId])
            ->byValidFromGreaterThan(new \DateTimeImmutable())
            ->isArchived(false)
            ->sortByValidFrom();

        return $this->fetch($query);
    }
}
