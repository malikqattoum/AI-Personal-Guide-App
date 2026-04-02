<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTest extends TestCase
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

    public function test_user_can_list_documents(): void
    {
        Document::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/documents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'documents' => [
                    '*' => ['uuid', 'title', 'source_type', 'status'],
                ],
            ])
            ->assertJsonCount(3, 'documents');
    }

    public function test_user_can_view_single_document(): void
    {
        $document = Document::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/documents/' . $document->uuid);

        $response->assertStatus(200)
            ->assertJson([
                'document' => [
                    'uuid' => $document->uuid,
                ],
            ]);
    }

    public function test_user_can_view_document_content(): void
    {
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'extracted_text' => 'This is the extracted text content.',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/documents/' . $document->uuid . '/content');

        $response->assertStatus(200)
            ->assertJson([
                'extracted_text' => 'This is the extracted text content.',
                'title' => $document->title,
            ]);
    }

    public function test_user_cannot_view_other_users_document(): void
    {
        $otherUser = User::factory()->create();
        $document = Document::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/documents/' . $document->uuid);

        $response->assertStatus(404);
    }

    public function test_user_can_delete_document(): void
    {
        $document = Document::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/documents/' . $document->uuid);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Document deleted successfully']);

        $this->assertDatabaseMissing('documents', ['uuid' => $document->uuid]);
    }

    public function test_user_cannot_delete_other_users_document(): void
    {
        $otherUser = User::factory()->create();
        $document = Document::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/documents/' . $document->uuid);

        $response->assertStatus(404);

        $this->assertDatabaseHas('documents', ['uuid' => $document->uuid]);
    }
}
