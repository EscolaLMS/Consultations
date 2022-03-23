<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Categories\Models\Category;
use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\Fluent\AssertableJson;

class ConsultationStoreApiTest extends TestCase
{
    use DatabaseTransactions;
    private string $apiUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = config('auth.providers.users.model')::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
        $this->apiUrl = '/api/admin/consultations';
    }

    public function testConsultationStoreUnauthorized(): void
    {
        $response = $this->json('POST',$this->apiUrl);
        $response->assertUnauthorized();
    }

    public function testConsultationStore(): void
    {
        $consultation = Consultation::factory()->make();
        $consultationArr = $consultation->toArray();
        $this->assertTrue(!isset($consultation['image_path']));
        $proposedTerms = [
            now()->format('Y-m-d H:i:s'),
            now()->modify('+1 day')->format('Y-m-d H:i:s')
        ];
        $categories = Category::factory(2)->create()->pluck('id')->toArray();
        $requestArray = array_merge(
            $consultationArr,
            ['proposed_terms' => $proposedTerms],
            ['image' => UploadedFile::fake()->image('image.jpg')],
            ['categories' => $categories]
        );
        $response = $this->actingAs($this->user, 'api')->json(
            'POST',
            $this->apiUrl,
            $requestArray
        );
        $response->assertCreated();
        $response->assertJsonFragment([
            'name' => $consultationArr['name'],
            'short_desc' => $consultationArr['short_desc'],
            'status' => $consultationArr['status'],
        ]);
        $response->assertJsonFragment([
            'proposed_terms' => $proposedTerms
        ]);
        $response->assertJsonFragment(['success' => true]);
        $response->assertJson(fn (AssertableJson $json) => $json->has(
            'data',
            fn ($json) => $json
                ->has('image_path')
                ->has('categories', fn (AssertableJson $json) =>
                    $json->each(fn (AssertableJson $json) =>
                        $json->where('id', fn ($json) =>
                            in_array($json, $categories)
                        )
                        ->etc()
                    )
                    ->etc()
                )
                ->has('author')
                ->etc()
            )
            ->etc()
        );
    }

    public function testConsultationStoreRequiredValidation(): void
    {
        $response = $this->actingAs($this->user, 'api')->json('POST', $this->apiUrl);
        $response->assertJsonValidationErrors(['name', 'status', 'description', 'author_id']);
    }
}
