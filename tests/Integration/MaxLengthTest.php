<?php

namespace Yajra\DataTables\Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\Tests\Models\User;
use Yajra\DataTables\Tests\TestCase;

class MaxLengthTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_returns_all_records_when_no_max_length_is_set()
    {
        $response = $this->call('GET', '/max-length');

        $response->assertJson([
            'draw' => 0,
            'recordsTotal' => 20,
            'recordsFiltered' => 20,
        ]);

        $this->assertCount(20, $response->json()['data']);
    }

    #[Test]
    public function it_caps_a_request_without_a_length()
    {
        config(['datatables.max_length' => 5]);

        $response = $this->call('GET', '/max-length');

        $response->assertJson([
            'draw' => 0,
            'recordsTotal' => 20,
            'recordsFiltered' => 20,
        ]);

        $this->assertCount(5, $response->json()['data']);
    }

    #[Test]
    public function it_caps_a_request_asking_for_all_the_records()
    {
        config(['datatables.max_length' => 5]);

        $response = $this->call('GET', '/max-length', ['start' => 0, 'length' => -1]);

        $this->assertCount(5, $response->json()['data']);
    }

    #[Test]
    public function it_caps_a_length_that_is_over_the_maximum()
    {
        config(['datatables.max_length' => 5]);

        $response = $this->call('GET', '/max-length', ['start' => 0, 'length' => 100]);

        $this->assertCount(5, $response->json()['data']);
    }

    #[Test]
    public function it_keeps_a_length_that_is_below_the_maximum()
    {
        config(['datatables.max_length' => 5]);

        $response = $this->call('GET', '/max-length', ['start' => 0, 'length' => 2]);

        $this->assertCount(2, $response->json()['data']);
    }

    #[Test]
    public function it_still_paginates_from_the_requested_start()
    {
        config(['datatables.max_length' => 5]);

        $response = $this->call('GET', '/max-length', ['start' => 5, 'length' => -1]);

        $data = $response->json()['data'];

        $this->assertCount(5, $data);
        $this->assertEquals('Record-6', $data[0]['name']);
    }

    #[Test]
    public function it_caps_a_collection_data_table_as_well()
    {
        config(['datatables.max_length' => 5]);

        $this->app['router']->get('/max-length-collection', fn (DataTables $datatables) => $datatables
            ->collection(User::all())
            ->toJson());

        $response = $this->call('GET', '/max-length-collection', ['start' => 0, 'length' => -1]);

        $this->assertCount(5, $response->json()['data']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['router']->get('/max-length', fn (DataTables $datatables) => $datatables
            ->eloquent(User::query())
            ->toJson());
    }
}
