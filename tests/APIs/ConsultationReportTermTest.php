<?php

namespace EscolaLms\Consultations\Tests\APIs;

use EscolaLms\Cart\Events\OrderPaid;
use EscolaLms\Cart\Models\Order;
use EscolaLms\Cart\Models\OrderItem;
use EscolaLms\Cart\Models\Product;
use EscolaLms\Cart\Models\ProductProductable;
use EscolaLms\Cart\Models\User;
use EscolaLms\Consultations\Enum\ConsultationTermStatusEnum;
use EscolaLms\Consultations\Events\ApprovedTerm;
use EscolaLms\Consultations\Events\RejectTerm;
use EscolaLms\Consultations\Events\ReportTerm;
use EscolaLms\Consultations\Listeners\ReportTermListener;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Repositories\Contracts\ConsultationTermsRepositoryContract;
use EscolaLms\Consultations\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;

class ConsultationReportTermTest extends TestCase
{
    use DatabaseTransactions;
    private string $apiUrl;
    private Order $order;
    private Consultation $consultation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->guard_name = 'api';
        $this->user->assignRole('tutor');
    }

    private function initVariable(): void
    {
        $consultationsForOrder = Consultation::factory(3)->create();
        $price = $consultationsForOrder->reduce(fn ($acc, Consultation $consultation) => $acc + $consultation->getBuyablePrice(), 0);
        $this->order = Order::factory()->afterCreating(
            fn (Order $order) => $order->items()->saveMany(
                $consultationsForOrder->map(
                    function (Consultation $consultation) {
                        $product = Product::factory()->create();
                        $product->productables()->save(new ProductProductable([
                            'productable_id' => $consultation->getKey(),
                            'productable_type' => $consultation->getMorphClass(),
                        ]));
                        return OrderItem::query()->make([
                            'quantity' => 1,
                            'buyable_id' => $product->getKey(),
                            'buyable_type' => Product::class,
                        ]);
                    }
                )
            )
        )->create([
            'user_id' => $this->user->getKey(),
            'total' => $price,
            'subtotal' => $price,
        ]);
        Event::fake();
        $event = new OrderPaid($this->order, $this->user);
        $listener = app(ReportTermListener::class);
        $listener->handle($event);
    }

    public function testConsultationReportTerm(): void
    {
        $this->initVariable();
        $item = Order::whereUserId($this->user->getKey())->first()->items()->first();
        $orderItem = $item->getKey();
        $now = now()->modify('+1 day');
        $this->response = $this->actingAs($this->user, 'api')
            ->json('POST',
                '/api/consultations/report-term/' . $orderItem,
                [
                    'term' => $now->format('Y-m-d H:i:s')
                ]
            );
        $consultationTermsRepositoryContract = app(ConsultationTermsRepositoryContract::class);
        $consultationTerm = $consultationTermsRepositoryContract->findByOrderItem($orderItem);
        $this->assertTrue($consultationTerm->executed_at === $now->format('Y-m-d H:i:s'));
        $this->assertTrue($consultationTerm->executed_status === ConsultationTermStatusEnum::REPORTED);
        $this->response->assertOk();
        $authorId = $consultationTerm->consultation->author->getKey();
        Event::assertDispatched(ReportTerm::class, fn (ReportTerm $event) =>
            $event->getUser()->getKey() === $authorId &&
            $event->getConsultationTerm()->getKey() === $consultationTerm->getKey()
        );
    }

    public function testConsultationReportTermUnauthorized(): void
    {
        $this->initVariable();
        $item = Order::whereUserId($this->user->getKey())->first()->items()->first();
        $now = now()->modify('+1 day');
        $this->response = $this->json('POST',
                '/api/consultations/report-term/' . $item->getKey(),
                [
                    'term' => $now->format('Y-m-d H:i:s')
                ]
            );
        $this->response->assertUnauthorized();
    }

    public function testConsultationTermApproved(): void
    {
        $this->initVariable();
        $orderItem = Order::whereUserId($this->user->getKey())->first()->items()->first();
        $now = now()->modify('+1 day');
        $this->response = $this->actingAs($this->user, 'api')->json('POST',
            '/api/consultations/report-term/' . $orderItem->getKey(),
            [
                'term' => $now->format('Y-m-d H:i:s')
            ]
        );
        $consultationTermsRepositoryContract = app(ConsultationTermsRepositoryContract::class);
        $consultationTerm = $consultationTermsRepositoryContract->findByOrderItem($orderItem->getKey());
        $this->response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/consultations/approve-term/' . $consultationTerm->getKey()
        );
        $userId = $this->user->getKey();
        Event::assertDispatched(ApprovedTerm::class, fn (ApprovedTerm $event) =>
            $event->getUser()->getKey() === $userId &&
            $event->getConsultationTerm()->getKey() === $consultationTerm->getKey()
        );
        $consultationTerm->refresh();
        $this->response->assertOk();
        $this->assertTrue($consultationTerm->executed_status === ConsultationTermStatusEnum::APPROVED);
    }

    public function testConsultationTermApprovedUnauthorized(): void
    {
        $this->response = $this->json(
            'GET',
            '/api/consultations/approve-term/1'
        );
        $this->response->assertUnauthorized();
    }

    public function testConsultationTermReject(): void
    {
        $this->initVariable();
        $orderItem = Order::whereUserId($this->user->getKey())->first()->items()->first();
        $now = now()->modify('+1 day');
        $this->response = $this->actingAs($this->user, 'api')->json('POST',
            '/api/consultations/report-term/' . $orderItem->getKey(),
            [
                'term' => $now->format('Y-m-d H:i:s')
            ]
        );
        $consultationTermsRepositoryContract = app(ConsultationTermsRepositoryContract::class);
        $consultationTerm = $consultationTermsRepositoryContract->findByOrderItem($orderItem->getKey());
        $this->response = $this->actingAs($this->user, 'api')->json(
            'GET',
            '/api/consultations/reject-term/' . $consultationTerm->getKey()
        );
        $userId = $this->user->getKey();
        Event::assertDispatched(RejectTerm::class, fn (RejectTerm $event) =>
            $event->getUser()->getKey() === $userId &&
            $event->getConsultationTerm()->getKey() === $consultationTerm->getKey()
        );
        $consultationTerm->refresh();
        $this->response->assertOk();
        $this->assertTrue($consultationTerm->executed_status === ConsultationTermStatusEnum::REJECT);
    }

    public function testConsultationTermRejectUnauthorized(): void
    {
        $this->response = $this->json(
            'GET',
            '/api/consultations/reject-term/1'
        );
        $this->response->assertUnauthorized();
    }
}
