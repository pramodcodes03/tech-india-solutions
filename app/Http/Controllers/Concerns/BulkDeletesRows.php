<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Shared "delete the ticked rows" handler for the register screens.
 *
 * The registers all post through one form wrapping the whole table, because a
 * per-row <form> nested inside that one would be invalid HTML. So the row's own
 * Delete button carries `single_id`, and it wins over any ticked boxes:
 * pressing Delete on a row must delete that row, not whatever happens to be
 * selected further up the page.
 *
 * Callers gate the permission themselves before calling in — the check belongs
 * with the module, not here.
 */
trait BulkDeletesRows
{
    /**
     * @param  class-string<Model>  $model
     * @param  string  $singular  lower-case noun, e.g. 'break entry'
     * @param  string  $plural  lower-case plural, e.g. 'break entries'
     * @param  (callable(EloquentCollection): void)|null  $before  last look at the
     *                                                             rows before they go — used to clean up files the
     *                                                             rows own, which a bulk delete would otherwise orphan
     */
    protected function bulkDeleteRows(
        Request $request,
        string $model,
        string $singular,
        string $plural,
        ?callable $before = null,
    ): RedirectResponse {
        $data = $request->validate([
            'single_id' => ['nullable', 'integer'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = ! empty($data['single_id'])
            ? [$data['single_id']]
            : ($data['ids'] ?? []);

        if ($ids === []) {
            return back()->withErrors(['ids' => "Select at least one {$singular} to delete."]);
        }

        // BusinessScope is on every one of these models, so an id belonging to
        // another business simply is not found here — nothing to leak and
        // nothing to delete.
        $rows = $model::whereIn('id', $ids)->get();

        if ($rows->isEmpty()) {
            return back()->withErrors(['ids' => "Those {$plural} could not be found."]);
        }

        if ($before !== null) {
            $before($rows);
        }

        // Delete by the ids we actually loaded, not the ids that were posted:
        // anything scoped out above must stay untouched.
        $model::whereIn('id', $rows->modelKeys())->delete();

        $count = $rows->count();

        return back()->with('success', $count === 1
            ? ucfirst($singular).' deleted.'
            : "{$count} {$plural} deleted.");
    }
}
