<?php

namespace Yajra\DataTables\Tests\Integration;

use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use Yajra\DataTables\Tests\TestCase;

class CollectionOrderingTest extends TestCase
{
    #[Test]
    public function it_can_sort_on_a_nested_column()
    {
        $this->orderBy('user.name');

        $collection = collect([
            ['id' => 1, 'user' => ['name' => 'Charlie']],
            ['id' => 2, 'user' => ['name' => 'Alice']],
            ['id' => 3, 'user' => ['name' => 'Bob']],
        ]);

        $this->assertEquals([
            ['id' => 2, 'user' => ['name' => 'Alice']],
            ['id' => 3, 'user' => ['name' => 'Bob']],
            ['id' => 1, 'user' => ['name' => 'Charlie']],
        ], $this->sortedData($collection));
    }

    #[Test]
    public function it_keeps_the_structure_of_a_row_that_is_not_sorted_on()
    {
        $this->orderBy('name');

        $collection = collect([
            ['name' => 'B', 'roles' => [['role' => 'User']], 'meta' => []],
            ['name' => 'A', 'roles' => [['role' => 'Administrator']], 'meta' => []],
        ]);

        $this->assertEquals([
            ['name' => 'A', 'roles' => [['role' => 'Administrator']], 'meta' => []],
            ['name' => 'B', 'roles' => [['role' => 'User']], 'meta' => []],
        ], $this->sortedData($collection));
    }

    #[Test]
    public function it_can_sort_numeric_values_of_a_nested_column()
    {
        $this->orderBy('user.amount');

        $collection = collect([
            ['user' => ['amount' => '100']],
            ['user' => ['amount' => '9']],
            ['user' => ['amount' => '10']],
        ]);

        $this->assertEquals([
            ['user' => ['amount' => '9']],
            ['user' => ['amount' => '10']],
            ['user' => ['amount' => '100']],
        ], $this->sortedData($collection));
    }

    protected function orderBy(string $column, string $direction = 'asc'): void
    {
        config()->set('app.debug', false);

        request()->merge([
            'columns' => [
                ['data' => $column, 'name' => $column, 'searchable' => 'true', 'orderable' => 'true'],
            ],
            'order' => [['column' => 0, 'dir' => $direction]],
            'start' => 0,
            'length' => 10,
            'draw' => 1,
        ]);
    }

    protected function sortedData($collection): array
    {
        /** @var JsonResponse $response */
        $response = app('datatables')->collection($collection)->toJson();

        return $response->getData(true)['data'];
    }
}
