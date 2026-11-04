<?php

namespace Database\Factories;

use App\Support\Factories\FactoryPersistenceGuard;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * Project base factory. All model factories must extend this class so
 * persistence is gated by FactoryPersistenceGuard before any INSERT.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Factory<TModel>
 */
abstract class AppFactory extends Factory
{
    /**
     * @param  Collection<int, \Illuminate\Database\Eloquent\Model>  $results
     */
    protected function store(Collection $results): void
    {
        FactoryPersistenceGuard::assertPersistenceAllowed($this->connection);

        parent::store($results);
    }
}
