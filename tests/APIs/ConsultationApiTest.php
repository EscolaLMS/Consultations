<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Consultations\Enum\ConsultationStatusEnum;
use EscolaLms\Consultations\Tests\Models\User;
use EscolaLms\Categories\Models\Category;
use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;

class ConsultationApiTest extends TestCase
{
    use DatabaseTransactions;
    private Consultation $consultation;
    private Collection $categories;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
        $this->consultation = Consultation::factory()->create();
        $this->consultation->author()->associate($this->user);
        $this->categories = Category::factory(2)->create();
        $this->consultation->categories()->sync($this->categories->pluck('id')->toArray());
    }

    public function testConsultationsList(): void
    {
        $this->response = $this->actingAs($this->user, 'api')->get('/api/admin/consultations');
        $this->response->assertOk();
    }

    public function testConsultationListAPI()
    {
        $response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/consultations/'
        );
        $response->assertOk();
    }

    public function testConsultationListAdminFilterOnlyWithCategories(): void
    {
        $consultation = Consultation::factory()->create();
        $filterData = [
            'only_with_categories=true'
        ];
        $response = $this->actingAs($this->user, 'api')->get('/api/admin/consultations?' . implode('&', $filterData));
        $response->assertOk();
        $response->assertJsonMissing(['id' => $consultation->getKey()]);
        $response = $this->actingAs($this->user, 'api')->get('/api/admin/consultations');
        $response->assertOk();
        $response->assertJsonFragment(['id' => $consultation->getKey()]);
    }

    public function testConsultationListAPIFilterOnlyWithCategories(): void
    {
        $consultation = Consultation::factory([
            'status' => ConsultationStatusEnum::PUBLISHED
        ])->create();
        $filterData = [
            'only_with_categories=true'
        ];
        $response = $this->get('/api/consultations?' . implode('&', $filterData));
        $response->assertOk();
        $response->assertJsonMissing(['id' => $consultation->getKey()]);
        $response = $this->get('/api/consultations');
        $response->assertOk();
        $response->assertJsonFragment(['id' => $consultation->getKey()]);
    }

    public function testConsultationsListByDate(): void
    {
        $now = now();
        $cons = Consultation::factory([
            'active_from' => $now->modify('+1 days'),
            'active_to' => (clone $now)->modify('+1 days')
        ])->create();
        $this->response = $this->actingAs($this->user, 'api')->get('/api/consultations');
        $this->response->assertOk();
        $this->response->assertJsonMissing([$cons->toArray()]);
    }

    public function testConsultationsListWithFilter(): void
    {
        $categories = $this->categories->pluck('id')->toArray();
        $filterData = [
            'base_price=' . $this->consultation->base_price,
            'name=' . $this->consultation->name,
            'status[]=' . $this->consultation->status,
            'categories[]=' . $categories[0]
        ];
        $this->response = $this->actingAs($this->user, 'api')->get('/api/admin/consultations?' . implode('&', $filterData));
        $this->response->assertOk();
        $this->response->assertJsonFragment([
            'id' => $this->consultation->getKey(),
            'name' => $this->consultation->name,
            'status' => $this->consultation->status,
            'created_at' => $this->consultation->created_at,
            'categories' => $this->consultation->categories->toArray()
        ]);
        $this->response->assertJson(fn (AssertableJson $json) =>
            $json->has('data', fn (AssertableJson $json) =>
                $json->each(fn (AssertableJson $json) => $json->has('author')->etc())->etc()
            )->etc()
        );
    }

    public function testConsultationsListUnauthorized(): void
    {
        $this->response = $this->json('GET','/api/admin/consultations');
        $this->response->assertUnauthorized();
    }

}
