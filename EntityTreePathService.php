<?php
declare(strict_types=1);

namespace Common\FileManager\Application\Service;

use Common\FileManager\Application\ReadModel\ContextEntityParentServiceInterface;
use Common\FileManager\Application\ReadModel\FileContextParentResult;
use Common\FileManager\Configuration\FileManagerConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webmozart\Assert\Assert;

class EntityTreePathService
{
    public function __construct(
        private GetContextEntity $contextEntity,
        private FileManagerConfig $fileManagerConfig,
        private ContainerInterface $container,
    ) {
    }

    //get service based on context from config file
    //service process current tree node and returns context and id of its logical parent
    public function __invoke(string $portfolioId, string $context, string $entityId, ?string $nestedId) : FileContextParentResult
    {
        Assert::keyExists($this->fileManagerConfig->contextConfig, $context);
        $contextServiceName = $this->fileManagerConfig->contextConfig[$context]->parentEntityContextService;

        // parent service does not exist, just add portfolio
        if ($contextServiceName === null) {
            if (empty($nestedId)) {
                $path = $context . ContextEntityParentServiceInterface::CONTEXT_ID_SEPARATOR . $entityId;
            } else {
                $path = $entityId . ContextEntityParentServiceInterface::CONTEXT_NODE_SEPARATOR . $context .
                    ContextEntityParentServiceInterface::CONTEXT_ID_SEPARATOR . $nestedId;
            }

            return new FileContextParentResult(
                $path,
                null,
                null
            );
        }
        /** @var callable|null $service */
        $service = $this->container->get($contextServiceName);
        Assert::notNull($service);
        $entity = ($this->contextEntity)($context, $entityId);

        return ($service)($entity, $nestedId);
    }
}
