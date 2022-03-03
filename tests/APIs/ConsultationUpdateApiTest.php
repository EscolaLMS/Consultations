<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Categories\Models\Category;
use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\Fluent\AssertableJson;

class ConsultationUpdateApiTest extends TestCase
{
    use DatabaseTransactions;
    private Consultation $consultation;
    private string $apiUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = config('auth.providers.users.model')::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
        $this->consultation = Consultation::factory()->create();
        $this->apiUrl = '/api/admin/consultations/' . $this->consultation->getKey();
    }

    public function testConsultationUpdateUnauthorized(): void
    {
        $response = $this->json('POST',$this->apiUrl);
        $response->assertUnauthorized();
    }

    public function testConsultationUpdate(): void
    {
        $proposedTerms = [
            now()->format('Y-m-d H:i:s'),
            now()->modify('+1 day')->format('Y-m-d H:i:s')
        ];
        $consultationUpdate = Consultation::factory()->make()->toArray();
        $this->assertTrue(!isset($consultation['image_path']));
        $categories = Category::factory(2)->create()->pluck('id')->toArray();
        $requestArray = array_merge(
            $consultationUpdate,
            ['proposed_terms' => $proposedTerms],
            ['image' => UploadedFile::fake()->image('image.jpg')],
            ['categories' => $categories]
        );
        $response = $this->actingAs($this->user, 'api')->json(
            'POST',
            $this->apiUrl,
            $requestArray
        );
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $this->consultation->getKey(),
            'name' => $consultationUpdate['name'],
            'status' => $consultationUpdate['status'],
            'author_id' => $consultationUpdate['author_id'],
        ]);
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
                ->etc()
            )
            ->etc()
        );
        $response->assertJsonFragment([
            'proposed_terms' => $proposedTerms
        ]);
        $response->assertJsonFragment(['success' => true]);
    }

    public function testConsultationUpdateSingleField(): void
    {
        $categories = Category::factory(2)->create()->pluck('id')->toArray();
        $requestArray = array_merge(
            ['categories' => $categories]
        );
        $response = $this->actingAs($this->user, 'api')->json(
            'POST',
            $this->apiUrl,
            $requestArray
        );
        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) => $json->has(
            'data',
            fn ($json) => $json
                ->has('categories', fn (AssertableJson $json) =>
                $json->each(fn (AssertableJson $json) =>
                $json->where('id', fn ($json) =>
                in_array($json, $categories)
                )
                    ->etc()
                )
                    ->etc()
                )
                ->etc()
        )
            ->etc()
        );
        $response->assertJsonFragment(['success' => true]);
    }

    public function testConsultationUpdateFailed(): void
    {
        $consultation = Consultation::factory()->create();
        $id = $consultation->getKey();
        $consultation->delete();
        $consultationUpdate = Consultation::factory()->make();
        $response = $this->actingAs($this->user, 'api')->json(
            'POST',
            '/api/admin/consultations/' . $id,
            $consultationUpdate->toArray()
        );
        $response->assertNotFound();
    }

}
