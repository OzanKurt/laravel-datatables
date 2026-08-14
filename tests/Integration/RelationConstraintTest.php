<?php

namespace Yajra\DataTables\Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Tests\Models\User;
use Yajra\DataTables\Tests\TestCase;

class RelationConstraintTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_applies_the_relation_constraints_on_the_join()
    {
        $sql = $this->orderedQuery()->toSql();

        $this->assertStringContainsString(
            'left join "hearts" on "hearts"."user_id" = "users"."id" and "hearts"."size" = ?',
            $sql
        );
    }

    #[Test]
    public function it_binds_the_relation_constraints_of_the_join()
    {
        $this->assertEquals(['heart-2'], $this->orderedQuery()->getBindings());
    }

    #[Test]
    public function it_only_joins_the_rows_matching_the_relation_constraints()
    {
        $response = $this->call('GET', '/relations/constrained', [
            'columns' => [
                [
                    'data' => 'filtered_heart.size',
                    'name' => 'filteredHeart.size',
                    'searchable' => 'true',
                    'orderable' => 'true',
                ],
            ],
            'order' => [['column' => 0, 'dir' => 'desc']],
            'start' => 0,
            'length' => 10,
            'draw' => 1,
        ]);

        $response->assertJson([
            'draw' => 1,
            'recordsTotal' => 20,
            'recordsFiltered' => 20,
        ]);

        $data = $response->json()['data'];

        // Only the heart matching the relation constraint is joined, so it is
        // the only row with a value to sort on and comes first descending.
        $this->assertCount(10, $data);
        $this->assertEquals('Record-2', $data[0]['name']);
        $this->assertEquals('heart-2', $data[0]['filtered_heart']['size']);

        foreach (array_slice($data, 1) as $row) {
            $this->assertNull($row['filtered_heart']);
        }
    }

    #[Test]
    public function it_applies_the_nested_constraints_of_a_relation()
    {
        request()->merge([
            'columns' => [
                [
                    'data' => 'nested_filtered_heart.size',
                    'name' => 'nestedFilteredHeart.size',
                    'searchable' => 'true',
                    'orderable' => 'true',
                ],
            ],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ]);

        $dataTable = new EloquentDataTable(User::with('nestedFilteredHeart')->select('users.*'));
        $dataTable->ordering();

        $this->assertStringContainsString(
            'left join "hearts" on "hearts"."user_id" = "users"."id" and ("hearts"."size" = ? or "hearts"."size" = ?)',
            $dataTable->getQuery()->toSql()
        );
        $this->assertEquals(['heart-2', 'heart-3'], $dataTable->getQuery()->getBindings());
    }

    #[Test]
    public function it_keeps_joining_a_relation_without_constraints()
    {
        request()->merge([
            'columns' => [
                ['data' => 'heart.size', 'name' => 'heart.size', 'searchable' => 'true', 'orderable' => 'true'],
            ],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]);

        $dataTable = new EloquentDataTable(User::with('heart')->select('users.*'));
        $dataTable->ordering();

        $this->assertStringContainsString(
            'left join "hearts" on "hearts"."user_id" = "users"."id"',
            $dataTable->getQuery()->toSql()
        );
        $this->assertEmpty($dataTable->getQuery()->getBindings());
    }

    protected function orderedQuery()
    {
        request()->merge([
            'columns' => [
                [
                    'data' => 'filtered_heart.size',
                    'name' => 'filteredHeart.size',
                    'searchable' => 'true',
                    'orderable' => 'true',
                ],
            ],
            'order' => [['column' => 0, 'dir' => 'desc']],
        ]);

        $dataTable = new EloquentDataTable(User::with('filteredHeart')->select('users.*'));
        $dataTable->ordering();

        return $dataTable->getQuery();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['router']->get('/relations/constrained', fn () => (new EloquentDataTable(
            User::with('filteredHeart')->select('users.*')
        ))->toJson());
    }
}
