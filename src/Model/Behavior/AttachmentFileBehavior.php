<?php
declare(strict_types=1);

namespace Brammo\Admin\Model\Behavior;

use ArrayObject;
use Brammo\Admin\FileManager\FileManagerService;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;

/**
 * Unlink attachment files from disk after delete, only when unused.
 *
 * Checks the same table plus configured `references` before unlinking so
 * File Manager shared paths are not removed out from under other rows.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AttachmentFileBehavior extends Behavior
{
    use LocatorAwareTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'field' => 'filename',
        // table alias => column that may store the same path
        'references' => [],
    ];

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @param \ArrayObject<string, mixed> $options Options
     * @return void
     */
    public function afterDelete(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
    ): void {
        $field = (string)$this->getConfig('field');
        $filename = $entity->get($field);
        if (!is_string($filename) || $filename === '') {
            return;
        }

        if ($this->isReferenced($filename, $entity)) {
            return;
        }

        try {
            $fileManager = FileManagerService::fromConfigure();
        } catch (RuntimeException) {
            return;
        }

        $relative = ltrim(str_replace('\\', '/', $filename), '/');
        if ($relative === '' || str_contains($relative, '..') || str_contains($relative, "\0")) {
            return;
        }

        $folder = dirname($relative);
        if ($folder === '.' || !$fileManager->isValidFolder($folder)) {
            return;
        }

        $path = $fileManager->getFullPath($relative);
        if (!is_file($path) || !is_writable($path)) {
            return;
        }

        if (!$fileManager->isPathWithinBase($path)) {
            return;
        }

        unlink($path);
    }

    /**
     * @param string $filename Stored path (usually with leading slash)
     * @param \Cake\Datasource\EntityInterface $deleted Deleted entity
     * @return bool
     */
    private function isReferenced(string $filename, EntityInterface $deleted): bool
    {
        $locator = $this->getTableLocator();
        $references = $this->getConfig('references');
        if (!is_array($references)) {
            $references = [];
        }

        $ownerTable = $this->table();
        $field = (string)$this->getConfig('field');
        $ownerPk = $ownerTable->getPrimaryKey();

        if (is_string($ownerPk) && $deleted->has($ownerPk)) {
            $sameTableConditions = [$ownerTable->aliasField($field) => $filename];
            $sameTableConditions[$ownerTable->aliasField($ownerPk) . ' !='] = $deleted->get($ownerPk);
            if ($ownerTable->exists($sameTableConditions)) {
                return true;
            }
        }

        foreach ($references as $alias => $column) {
            if (!is_string($alias) || !is_string($column) || $alias === $ownerTable->getAlias()) {
                continue;
            }

            $table = $locator->get($alias);
            if ($table->exists([$table->aliasField($column) => $filename])) {
                return true;
            }
        }

        return false;
    }
}
