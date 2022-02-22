<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Cart\Models\Order;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Cart\Models\User;
use EscolaLms\Consultations\Database\Seeders\ConsultationsPermissionSeeder;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationProposedTerm;
use EscolaLms\Consultations\Models\ConsultationTerm;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ConsultationScheduleTermsTest extends TestCase
{
    use DatabaseTransactions;
    private Consultation $consultation;
    private Collection $consultationTerms;
    private string $apiUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConsultationsPermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
    }

    private function initVariable(): void
    {
        $this->consultationTerms = collect();
        $this->consultation = Consultation::factory()->create();
        $this->apiUrl = '/api/admin/consultations/' . $this->consultation->getKey() . '/schedule';
        $this->consultation->proposedTerms()->saveMany(ConsultationProposedTerm::factory(3)->create());
        $price = $this->consultation->getBuyablePrice();
        $this->order = Order::factory()->afterCreating(
            fn (Order $order) => $order->items()->saveMany(
                collect([$this->consultation])->map(
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
        $this->consultation->orderItems->each(fn ($item) =>
            $this->consultationTerms->push(ConsultationTerm::factory([
                'order_item_id' => $item->getKey(),
                'user_id' => $this->user->getKey()
            ])->create())
        );
    }

    public function testConsultationTermsListUnauthorized(): void
    {
        $this->response = $this->json('GET','/api/admin/consultations/1/schedule');
        $this->response->assertUnauthorized();
    }

    public function testConsultationTermsList(): void
    {
        $this->initVariable();
        $this->order->items->map(fn ($orderItem) => $orderItem->update(['executed_at' => now(), 'executed_status' => ConsultationTermStatusEnum::APPROVED]));
        $this->response = $this->actingAs($this->user, 'api')->get($this->apiUrl);
        $this->response->assertOk();
        $consultationTerms = $this->consultationTerms->map(function (ConsultationTerm $element) {
            return [
                'date' => Carbon::make($element->executed_at)->format('Y-m-d H:i:s'),
                'status' => $element->executed_status,
            ];
        })->toArray();
        $this->response->assertJsonFragment($consultationTerms);
    }
}
