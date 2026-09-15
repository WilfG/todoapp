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

    public function test_can_delete_completed_todos()
    {
        Todo::factory(3)->create(['completed' => true]);
        Todo::factory(2)->create(['completed' => false]);

        $this->deleteJson('/api/todos/completed')
            ->assertOk()
            ->assertJson(['deleted' => 3]);

        // Il doit rester uniquement les 2 non terminés
        $this->assertDatabaseCount('todos', 2);
        $this->assertEquals(0, Todo::where('completed', true)->count());
    }

    public function test_delete_completed_returns_zero_when_none()
    {
        Todo::factory(2)->create(['completed' => false]);

        $this->deleteJson('/api/todos/completed')
            ->assertOk()
            ->assertJson(['deleted' => 0]);
    }
}
