<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Cart\Models\Order;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Cart\Models\User;
use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;

class ConsultationListForUserTest extends TestCase
{
    use DatabaseTransactions;
    private string $apiUrl;
    private Collection $consultations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
        $this->apiUrl = 'api/consultations/me';
    }

    private function initVariable(): void
    {
        $this->consultations = Consultation::factory(3)->create();
        $price = $this->consultations->reduce(fn ($acc, Consultation $consultation) => $acc + $consultation->getBuyablePrice(), 0);
        $this->order = Order::factory()->afterCreating(
            fn (Order $order) => $order->items()->saveMany(
                $this->consultations->map(
                    function (Consultation $consultation) {
                        return OrderItem::query()->make([
                            'quantity' => 1,
                            'buyable_id' => $consultation->getKey(),
                            'buyable_type' => Consultation::class,
                        ]);
                    }
                )
            )
        )->create([
            'user_id' => $this->user->getKey(),
            'total' => $price,
            'subtotal' => $price,
        ]);
    }

    public function testConsultationListForUser(): void
    {
        $this->initVariable();
        $this->response = $this->actingAs($this->user, 'api')->json('GET', $this->apiUrl);
        $consArray = $this->consultations->pluck('id')->toArray();
        $this->response->assertJson(fn (AssertableJson $json) => $json->has(
            'data',
                fn ($json) => $json->each(fn (AssertableJson $json) =>
                    $json->where('id', fn ($json) =>
                        in_array($json, $consArray)
                    )
                    ->etc()
                )
                ->etc()
            )
            ->etc()
        );
        $this->response->assertOk();
    }

    public function testConsultationReportTermUnauthorized(): void
    {
        $this->initVariable();
        $this->response = $this->json('GET', $this->apiUrl);
        $this->response->assertUnauthorized();
    }
}
