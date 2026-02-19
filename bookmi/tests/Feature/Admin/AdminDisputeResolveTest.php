<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDisputeResolveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->admin()->create();
        $user->assignRole('admin_ceo');

        return $user;
    }

    #[Test]
    public function admin_can_list_disputed_bookings(): void
    {
        $admin   = $this->admin();
        BookingRequest::factory()->count(2)->create(['status' => BookingStatus::Disputed]);
        BookingRequest::factory()->create(['status' => BookingStatus::Completed]);

        $this->actingAs($admin)
            ->getJson('/admin/disputes')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function admin_can_view_dispute_detail(): void
    {
        $admin   = $this->admin();
        $booking = BookingRequest::factory()->create(['status' => BookingStatus::Disputed]);

        $this->actingAs($admin)
            ->getJson("/admin/disputes/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $booking->id);
    }

    #[Test]
    public function admin_can_add_note_to_dispute(): void
    {
        $admin   = $this->admin();
        $booking = BookingRequest::factory()->create(['status' => BookingStatus::Disputed]);

        $this->actingAs($admin)
            ->postJson("/admin/disputes/{$booking->id}/notes", ['note' => 'Note interne.'])
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'dispute.note_added',
            'subject_id' => $booking->id,
        ]);
    }

    #[Test]
    public function admin_can_resolve_dispute_with_refund_client(): void
    {
        $admin   = $this->admin();
        $booking = BookingRequest::factory()->create(['status' => BookingStatus::Disputed]);

        $this->actingAs($admin)
            ->postJson("/admin/disputes/{$booking->id}/resolve", [
                'resolution' => 'refund_client',
                'note'       => 'Client remboursé.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('booking_requests', [
            'id'     => $booking->id,
            'status' => BookingStatus::Cancelled->value,
        ]);
    }

    #[Test]
    public function admin_can_resolve_dispute_with_pay_talent(): void
    {
        $admin   = $this->admin();
        $booking = BookingRequest::factory()->create(['status' => BookingStatus::Disputed]);

        $this->actingAs($admin)
            ->postJson("/admin/disputes/{$booking->id}/resolve", ['resolution' => 'pay_talent'])
            ->assertOk();

        $this->assertDatabaseHas('booking_requests', [
            'id'     => $booking->id,
            'status' => BookingStatus::Completed->value,
        ]);
    }
}
