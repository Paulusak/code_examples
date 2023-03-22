<?php
declare(strict_types=1);

namespace Common\FileManager\Domain;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MongoDB\BSON\ObjectId;
use Bridge\Doctrine\ArchivableEntity\Archivable;
use Bridge\Doctrine\ComposableEntity\Composable;
use Bridge\Doctrine\ComposableEntity\ComposableTrait;
use Bridge\Doctrine\Entity\AbstractEntity;
use Common\GenericEntity\Domain\GenericEntity;
use Common\Tag\Domain\TagsReference;
use Common\User\Domain\User;
use Misc\StringUtil;

class DomainEntity extends AbstractEntity implements Archivable, Composable
{
    use ComposableTrait;

    public const GENERIC_ENTITY_TAG_CONTEXT = 'persisted_file_tag';

    /**
     * @param ?ObjectId $nestedId you can add file to nested entity, but as it is dependent on entity
     *                            with repository, you need to specify both IDs if nestedId will not
     *                            be specified, it will be assigned to parent entity (entityId)
     */
    public function __construct(
        public string $displayName,
        public readonly ?FileExtension $extension,
        public readonly string $context,
        public readonly ObjectId $entityId,
        public readonly string $path,
        public readonly int $size, // in bytes
        public readonly ?string $mimeType,
        public readonly ?ObjectId $nestedId,
        public readonly ?User $author,
        public ?string $description = null,
    ) {
        parent::__construct();
        $this->recordThat(Events::FILE_CREATED);
    }

    public function updateDisplayName(string $displayName) : void
    {
        if ($this->displayName === $displayName) {
            return;
        }
        $this->displayName = $displayName;

        $this->recordThat(Events::FILE_NAME_CHANGED);
    }

    public function updateDescription(?string $description) : void
    {
        if ($this->description === $description) {
            return;
        }
        $this->description = $description;

        $this->recordThat(Events::FILE_DESCRIPTION_CHANGED);
    }

    public function getFullFileName() : string
    {
        if ($this->extension === null) {
            return $this->displayName;
        }

        return $this->displayName . '.' . $this->extension->value;
    }

    /**
     * @return Collection<string,GenericEntity>
     */
    public function getTags() : Collection
    {
        return clone ($this->getComponentOrNull(TagsReference::class)?->getTags() ?? new ArrayCollection());
    }

    public static function generateRandomNameWithExtension(?string $extensionString) : string
    {
        return StringUtil::randomString(20) . ($extensionString !== null ? ('.' . $extensionString) : null);
    }

    public static function getDisplayNameFromFileName(string $fileName) : string
    {
        $extensionDotPosition = \mb_strrpos($fileName, '.');
        if ($extensionDotPosition !== false) {
            return \mb_substr($fileName, 0, $extensionDotPosition);
        }

        return $fileName;
    }
}
