<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Flashcard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashcardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_user_can_list_flashcards(): void
    {
        Flashcard::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/flashcards');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'flashcards' => [
                    '*' => ['uuid', 'front_text', 'back_text', 'difficulty'],
                ],
            ])
            ->assertJsonCount(3, 'flashcards');
    }

    public function test_user_can_view_single_flashcard(): void
    {
        $flashcard = Flashcard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/flashcards/' . $flashcard->uuid);

        $response->assertStatus(200)
            ->assertJson([
                'flashcard' => [
                    'uuid' => $flashcard->uuid,
                ],
            ]);
    }

    public function test_user_cannot_view_other_users_flashcard(): void
    {
        $otherUser = User::factory()->create();
        $flashcard = Flashcard::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/flashcards/' . $flashcard->uuid);

        $response->assertStatus(404);
    }

    public function test_user_can_delete_flashcard(): void
    {
        $flashcard = Flashcard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/flashcards/' . $flashcard->uuid);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Flashcard deleted successfully']);

        $this->assertDatabaseMissing('flashcards', ['uuid' => $flashcard->uuid]);
    }

    public function test_flashcard_review_requires_correct_field(): void
    {
        $flashcard = Flashcard::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/flashcards/' . $flashcard->uuid . '/review', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['correct']);
    }

    public function test_flashcard_review_updates_review_counts(): void
    {
        $flashcard = Flashcard::factory()->create([
            'user_id' => $this->user->id,
            'times_reviewed' => 0,
            'times_correct' => 0,
        ]);

        // Test correct review
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/flashcards/' . $flashcard->uuid . '/review', ['correct' => true]);

        $response->assertStatus(200);

        $flashcard->refresh();
        $this->assertEquals(1, $flashcard->times_reviewed);
        $this->assertEquals(1, $flashcard->times_correct);

        // Test incorrect review
        $flashcard2 = Flashcard::factory()->create([
            'user_id' => $this->user->id,
            'times_reviewed' => 0,
            'times_correct' => 0,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/flashcards/' . $flashcard2->uuid . '/review', ['correct' => false]);

        $flashcard2->refresh();
        $this->assertEquals(1, $flashcard2->times_reviewed);
        $this->assertEquals(0, $flashcard2->times_correct);
    }

    public function test_user_can_get_flashcards_by_document(): void
    {
        $document = Document::factory()->create(['user_id' => $this->user->id]);
        Flashcard::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'document_id' => $document->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/documents/' . $document->uuid . '/flashcards');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'flashcards');
    }
}
