<?php
declare(strict_types=1);

namespace Brammo\Admin\Controller\Component;

use ADmad\Sequence\Model\Behavior\SequenceBehavior;
use Cake\Controller\Component;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\ORM\Association;
use Cake\ORM\Table;
use RuntimeException;

/**
 * Persist drag-and-drop order for tables that use ADmad Sequence.
 */
class SortableComponent extends Component
{
    /**
     * Rewrite `sort_order` (or the Sequence field) from a posted id list.
     *
     * Only IDs matching `$ownerScope` are kept (posted order preserved), then
     * SequenceBehavior::setOrder() is called. Empty or invalid lists still
     * return `{ok: true}`.
     *
     * @param \Cake\ORM\Table|\Cake\ORM\Association $source Table or association
     * @param list<string|int>|null $ids Ordered list of primary keys
     * @param array<string, mixed> $ownerScope Owner foreign key conditions
     * @return \Cake\Http\Response JSON `{ok: true}`
     */
    public function reorder(
        Table|Association $source,
        ?array $ids,
        array $ownerScope = [],
    ): Response {
        $table = $this->resolveTable($source);
        $request = $this->getController()->getRequest();
        if (!$request->is('post')) {
            throw new BadRequestException('Invalid request');
        }

        if ($ids) {
            $ids = array_values(array_filter(
                array_map(static fn($id) => (int)$id, $ids),
                static fn(int $id) => $id > 0,
            ));

            if ($ownerScope !== [] && $ids !== []) {
                $ids = $this->filterIdsToScope($table, $ids, $ownerScope);
            }

            if ($ids !== []) {
                if (!$table->hasBehavior('Sequence')) {
                    throw new RuntimeException('Sequence behavior is required for sorting');
                }
                $sequence = $table->getBehavior('Sequence');
                if (!$sequence instanceof SequenceBehavior) {
                    throw new RuntimeException('Sequence behavior is required for sorting');
                }
                $sequence->setOrder($ids);
            }
        }

        return $this->jsonResponse(['ok' => true]);
    }

    /**
     * Keep posted IDs that belong to the owner scope, preserving order.
     *
     * @param \Cake\ORM\Table $table Table
     * @param list<int> $ids Ordered ids
     * @param array<string, mixed> $ownerScope Conditions
     * @return list<int>
     */
    private function filterIdsToScope(Table $table, array $ids, array $ownerScope): array
    {
        $conditions = [];
        foreach ($ownerScope as $field => $value) {
            if ($value === null) {
                $conditions[$field . ' IS'] = null;
            } else {
                $conditions[$field] = $value;
            }
        }

        $primaryKey = $table->getPrimaryKey();
        if (!is_string($primaryKey)) {
            return [];
        }

        $allowed = $table->find()
            ->select([$primaryKey])
            ->where($conditions)
            ->where([$primaryKey . ' IN' => $ids])
            ->all()
            ->extract($primaryKey)
            ->map(static fn($id) => (int)$id)
            ->toList();
        $allowedSet = array_flip($allowed);

        return array_values(array_filter(
            $ids,
            static fn(int $id) => isset($allowedSet[$id]),
        ));
    }

    /**
     * @param \Cake\ORM\Table|\Cake\ORM\Association $source Table or association
     * @return \Cake\ORM\Table
     */
    private function resolveTable(Table|Association $source): Table
    {
        if ($source instanceof Association) {
            return $source->getTarget();
        }

        return $source;
    }

    /**
     * @param array<string, mixed> $payload JSON body
     * @param int $status HTTP status
     * @return \Cake\Http\Response
     */
    private function jsonResponse(array $payload, int $status = 200): Response
    {
        return $this->getController()->getResponse()
            ->withType('application/json')
            ->withStatus($status)
            ->withStringBody(json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
