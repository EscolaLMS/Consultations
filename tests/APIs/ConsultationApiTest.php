<?php

namespace EscolaLms\Consultations\Tests\APIs;

use Carbon\Carbon;
use EscolaLms\Auth\Dtos\Admin\UserAssignableDto;
use EscolaLms\Auth\Services\Contracts\UserServiceContract;
use EscolaLms\Categories\Models\Category;
use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Enum\ConstantEnum;
use EscolaLms\Consultations\Enum\ConsultationsPermissionsEnum;
use EscolaLms\Consultations\Enum\ConsultationStatusEnum;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationUserPivot;
use EscolaLms\Consultations\Tests\Models\User;
use EscolaLms\Consultations\Tests\TestCase;
use EscolaLms\Core\Tests\CreatesUsers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

class ConsultationApiTest extends TestCase
{
    use DatabaseTransactions, CreatesUsers;

    private Consultation $consultation;
    private Collection $categories;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
        $this->consultation = Consultation::factory([
            'status' => ConsultationStatusEnum::PUBLISHED
        ])->create();
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
        $responseConsultationId = collect(json_decode($response->content(), true)['data'])->pluck('id')->toArray();

        $this->assertTrue(!in_array($consultation->getKey(), $responseConsultationId));
        $response = $this->actingAs($this->user, 'api')->get('/api/admin/consultations');

        $response->assertOk();
        $responseConsultationId = collect(json_decode($response->content(), true)['data'])->pluck('id')->toArray();
        $this->assertTrue(in_array($consultation->getKey(), $responseConsultationId));
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
        $responseConsultationId = collect(json_decode($response->content(), true)['data'])->pluck('id')->toArray();

        $this->assertTrue(!in_array($consultation->getKey(), $responseConsultationId));
        $response = $this->get('/api/consultations');
        $response->assertOk();
        $responseConsultationId = collect(json_decode($response->content(), true)['data'])->pluck('id')->toArray();
        $this->assertTrue(in_array($consultation->getKey(), $responseConsultationId));
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
        $this->response->assertJson(fn(AssertableJson $json) => $json->has('data', fn(AssertableJson $json) => $json->each(fn(AssertableJson $json) => $json->has('author')->etc())->etc()
        )->etc()
        );
    }

    public function testConsultationsListUnauthorized(): void
    {
        $this->response = $this->json('GET', '/api/admin/consultations');

        $this->response->assertUnauthorized();
    }

    public function testConsultationsAssignableUsersUnauthorized(): void
    {
        $this->response = $this
            ->json('GET', '/api/admin/consultations/users/assignable')
            ->assertUnauthorized();
    }

    public function testConsultationsAssignableUsers(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $dto = UserAssignableDto::instantiateFromArray(['assignable_by' => ConsultationsPermissionsEnum::CONSULTATION_CREATE]);
        $users = app(UserServiceContract::class)->assignableUsersWithCriteria($dto);
        assert($users instanceof LengthAwarePaginator);

        $this->response = $this
            ->actingAs($this->user, 'api')
            ->json('GET', '/api/admin/consultations/users/assignable')
            ->assertOk()
            ->assertJsonCount(min($users->total(), $users->perPage()), 'data')
            ->assertJsonMissing([
                'id' => $student->getKey(),
                'email' => $student->email,
            ])
            ->assertJsonFragment([
                'id' => $admin->getKey(),
                'email' => $admin->email,
            ])
            ->assertJsonFragment([
                'id' => $this->user->getKey(),
                'email' => $this->user->email,
            ]);
    }

    public function testConsultationsAssignableUsersSearch(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        $dto = UserAssignableDto::instantiateFromArray([
            'assignable_by' => ConsultationsPermissionsEnum::CONSULTATION_CREATE,
            'search' => $admin->email,
        ]);
        $users = app(UserServiceContract::class)->assignableUsersWithCriteria($dto);
        assert($users instanceof LengthAwarePaginator);

        $this->response = $this
            ->actingAs($this->user, 'api')
            ->json('GET', '/api/admin/consultations/users/assignable', ['search' => $admin->email])
            ->assertOk()
            ->assertJsonCount(min($users->total(), $users->perPage()), 'data')
            ->assertJsonFragment([
                'id' => $admin->getKey(),
                'email' => $admin->email,
            ])
            ->assertJsonMissing([
                'id' => $student->getKey(),
                'email' => $student->email,
            ])
            ->assertJsonMissing([
                'id' => $this->user->getKey(),
                'email' => $this->user->email,
            ]);
    }

    public function testConsultationSaveScreen(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeStudent();

        /** @var Consultation $consultation */
        $consultation = Consultation::factory()->create();
        $consultation->author()->associate($this->user);
        $time = now();
        $data = [
            'consultation_id' => $consultation->getKey(),
            'user_id' => $student->getKey(),
            'executed_status' => ConsultationTermStatusEnum::APPROVED,
            'executed_at' => $time,
        ];
        $consultation->attachToConsultationPivot($data);

        $consultationUser = ConsultationUserPivot::query()
            ->where('user_id', '=', $student->getKey())
            ->where('consultation_id', '=', $consultation->getKey())->first();

        Storage::fake();
        $this->response = $this->json('POST', '/api/consultations/save-screen', [
            'consultation_id' => $consultation->getKey(),
            'user_email' => $student->email,
            'user_termin_id' => $consultationUser->getKey(),
            'file' => UploadedFile::fake()->image('image.jpg'),
            'timestamp' => $time->addMinutes(10)->format('Y-m-d H:i:s'),
            'executed_at' => $this->format('Y-m-d H:i:s'),
        ])
            ->assertOk();

        $term = Carbon::make($consultationUser->executed_at);
        // consultation_id/term_start_timestamp/user_id/timestamp.jpg
        Storage::assertExists(ConstantEnum::DIRECTORY . "/{$consultation->getKey()}/{$term->getTimestamp()}/{$student->getKey()}/{$time->addMinutes(10)->getTimestamp()}.jpg");

        $this->response = $this->json('POST', '/api/consultations/save-screen', [
            'consultation_id' => $consultation->getKey(),
            'user_email' => $admin->email,
            'user_termin_id' => $consultationUser->getKey(),
            'file' => UploadedFile::fake()->image('image.jpg'),
            'timestamp' => $time->format('Y-m-d H:i:s'),
            'executed_at' => $this->format('Y-m-d H:i:s'),
        ])->assertNotFound();

        $this->response = $this->json('POST', '/api/consultations/save-screen', [
            'consultation_id' => $consultation->getKey(),
            'user_email' => $student->email,
            'user_termin_id' => $consultationUser->getKey() + 1,
            'file' => UploadedFile::fake()->image('image.jpg'),
            'timestamp' => $time->format('Y-m-d H:i:s'),
            'executed_at' => $this->format('Y-m-d H:i:s'),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_termin_id']);
    }
}
