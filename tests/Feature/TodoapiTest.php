<?php

namespace Tests\Feature;

use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoapiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_todos()
    {
        Todo::factory(3)->create();

        $response = $this->getJson('/api/todos');

        // dump($response->getContent());   // ← AJOUTEZ CETTE LIGNE

        $response->assertOk()->assertJsonCount(3);
    }

    public function test_can_create_todo()
    {
        $response = $this->postJson('/api/todos', [
            'title' => 'Apprendre à conduire une voiture',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('todos', [
            'title' => 'Apprendre à conduire une voiture',
        ]);
    }

    public function test_health_endpoint()
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_stats_endpoint()
    {
        Todo::factory(3)->create(['completed' => true]);
        Todo::factory(2)->create(['completed' => false]);

        $this->getJson('/api/todos/stats')
            ->assertOk()
            ->assertJson([
                'total' => 5,
                'completed' => 3,
                'pending' => 2,
            ]);
    }
}
