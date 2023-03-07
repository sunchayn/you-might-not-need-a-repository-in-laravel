<?php

namespace App\Modules\Example\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
 * The following is a dummy custom query class to demonstrate what it can do:
 *
 * The examples include:
 * - Serialize/Deserialize the cache.
 * - Using other models results.
 * - Early exists.
 * - Dynamic empty state.
 * - Custom eager loading.
 * - Filter out results.
 * - Different private methods to reduce the cognitive load.
 * - Different public methods to access results for the same business logic.
 */
class ComplexCustomQueryExample
{
    private const CACHE_TTL = 3600;

    private readonly int $threshold;

    private readonly int $limit;

    public function __construct(
        private readonly Model $dummyModel,
        ?int $threshold = null,
        ?int $limit = null,
    ) {
        $this->threshold = $threshold ?? config('app.dummies.threshold');
        $this->limit = $limit ?? config('app.dummies.limit');
    }

    public function get(): Collection
    {
        /** @var int[]|null $cachedIds */
        $cachedIds = Cache::get($this->getCacheKey());

        // Example of Cache deserialization.
        if (! empty($cachedIds)) {
            // If we have already computed the ids, we fetch them on the spot to get fresh results.
            // this might have different benefits including system correctness and lightweight cache.
            // fetching models using a list of ids is faster than complex queries that have filtering logic.
            return Model::query()->whereIntegerInRaw('id', $cachedIds)->get();
        }

        $results = $this->compute();

        // Example of a dynamic empty state.
        if ($results->isEmpty()) {
            return $this->getEmptyStateResults();
        }

        // Example of Cache serialization.
        Cache::put(
            $this->getCacheKey(),
            $results->pluck('id')->all(),
            self::CACHE_TTL,
        );

        return $results;
    }

    /**
     * Get a fresh count (independent of the cache).
     */
    public function count(): int
    {
        if ($this->someCriteriaValue() < $this->threshold) {
            return 0;
        }

        return $this->query()->count();
    }

    private function getCacheKey(): string
    {
        return "examples:{$this->model->id}:scope:v1";
    }

    private function compute(): Collection
    {
        // Example of early exist 1.
        if ($this->model->type === 'not-allowed') {
            return Collection::empty();
        }

        // Example of early exist 2.
        if ($this->someCriteriaValue() < $this->threshold) {
            // if complex criteria is not met, we don't have to compute anything for this model.
            return Collection::empty();
        }

        $results = $this->query()->get();

        $this->eagerLoadRelatedModels($results);

        return $this->getEligibleResults($results);
    }

    private function query(): Builder
    {
        return $this
            ->dummyModel
            ->whereVisible() // <- scope or custom builder.
            ->join('table_2', 'table_2.id', '=', 'dummy.fk')
            ->where('table_2.users', '>', 100) // <- it can be a configured value.
            ->whereIn('table_2.some_property', $this->getComplexSubQuery())
            ->orderByRaw(
                sprintf(
                    "FIELD(table_2.accuracy, '%s', '%s', '%s')",
                    'high',
                    'medium',
                    'low',
                ),
            )
            ->orderByDesc('table_2.followers')
            ->limit($this->limit);
    }

    private function someCriteriaValue(): int
    {
        // As long as it is a dummy example, I'm not using models here.
        return once(
            fn () => DB::table('different_dummy_table')
                ->where('property_id', $this->dummyModel->id)
                ->sum('points'),
        );
    }

    /*
     * This is an example of custom eager loading.
     * The cache serialization doesn't take the relations into account (it is only to illustrate the possibility).
     */
    private function eagerLoadRelatedModels(Collection $results): void
    {
        // Example 1, normal eager with subset of columns.
        $results->load('dummyRelation1:id,name,type');

        // Example 2, set the current model as the related model.
        $results->each->setRelation('dummyRelation2', $this->dummyModel);

        // Example 3, eager load with limit.
        // Because laravel natively does not support eager loading with a limit on a collection, you can do it
        // as part of the custom query class.

        // step 1, use a union to get all limited related models
        $queries = $results->map(fn (Model $model) => $model->relatedModels()->limit(5)); // The limit can be configured.

        /** @var Collection<Model> $relatedModels */
        // Union builder is an imaginary helper that will take all the queries and return the SQL that will union all of them.
        $relatedModels = UnionBuilder::make()->union($queries)->get();

        // step 2, map the results
        $results->each(
            fn (Model $model) => $model->setRelation(
                'dummyRelation3',
                $relatedModels->where('fk', $model->id)->values(),
            ),
        );

        // Example 4, use know relation value if it is set.
        // This will allow us to conditionally load the dummyRelation4 only when we don't have the information.
        // This example makes sense only in the cases where the relation cannot diverge theoretically from the $this->model and the results.
        if ($this->model->dummy_relation_id) {
            $results->each->setRelation('dummyRelation4', $this->model->dumyRelation);
        } else {
            $results->load('dummyRelation4');
        }

        // etc.
    }

    /*
     * This is an example to further filter out corrupt data for instance.
     * The class `get` and `count` might diverge as a consequence (it is only to illustrate the possibility).
     */
    private function getEligibleResults(Collection $results): Collection
    {
        // Let's eliminate the records that hold invalid FKs.
        // This is situational examples for cases when the relation might be loaded with or without extra conditions.
        return $results->reject(fn (Model $result) => $result->related_model_id && is_null($result->relatedModel));
    }

    private function getEmptyStateResults(): Collection
    {
        // here you can have any logic suits the business needs to have an empty state.

        // Example 1, instantiate the result on the spot.

        $dummy = new class extends Model
        {
        };

        $results = Collection::make([
            $dummy->newFromBuilder((array) config('app.dummies.empty-state')),
        ]);

        // Example 2, load placeholders from the DB.

        $results = Collection::make(
            Model::query()
                ->where([
                    // find the placeholder
                ])
                ->get(),
        );

        return $results;
    }

    private function getComplexSubQuery(): Builder|array // <- not real behaviour, it is to say it can be built to return both.
    {
        $builder = DB::table('table_3')
            ->select('property')
            ->where([
                // some filtering here.
            ]);

        // You can either return a complex sub-query here as a builder.
        return $builder;

        // Or compute the results.
        return $builder->pluck('property');
    }
}
