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

    public function test_can_filter_completed_todos()
    {
        Todo::factory(3)->create(['completed' => true]);
        Todo::factory(2)->create(['completed' => false]);

        $this->getJson('/api/todos?completed=true')
            ->assertOk()
            ->assertJsonCount(3);

        $this->getJson('/api/todos?completed=false')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_can_search_todos_by_title()
    {
        Todo::factory()->create(['title' => 'Apprendre Laravel']);
        Todo::factory()->create(['title' => 'Apprendre React Native']);
        Todo::factory()->create(['title' => 'Dormir']);

        $this->getJson('/api/todos?search=Apprendre')
            ->assertOk()
            ->assertJsonCount(2);
    }
}
