<?php

namespace Modules\Finance\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Search, status and date-range filters shared by the document lists (?q=, ?status=, ?from=, ?to=).
 */
trait FiltersDocumentLists
{
    /**
     * Apply the request's filters to the query and return them for the filter bar.
     *
     * @param  list<string>  $searchColumns  document columns matched by ?q=
     * @param  string|null  $partyRelation  relation whose name is also matched by ?q= (customer, supplier)
     * @return array{q?: string|null, status?: string|null, from?: string|null, to?: string|null, overdue?: bool|null}
     */
    protected function applyListFilters(Builder $query, Request $request, string $dateColumn, array $searchColumns, ?string $partyRelation = null): array
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:30'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'overdue' => ['nullable', 'boolean'],
        ]);
        $term = isset($filters['q']) ? '%'.addcslashes($filters['q'], '%_\\').'%' : null;

        $query
            ->when($term, fn (Builder $query) => $query->where(function (Builder $query) use ($term, $searchColumns, $partyRelation) {
                foreach ($searchColumns as $column) {
                    $query->orWhere($query->qualifyColumn($column), 'like', $term);
                }

                if ($partyRelation) {
                    $query->orWhereHas($partyRelation, fn (Builder $party) => $party->where('name', 'like', $term));
                }
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where($query->qualifyColumn('status'), $status))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate($query->qualifyColumn($dateColumn), '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate($query->qualifyColumn($dateColumn), '<=', $to));

        return $filters;
    }
}
