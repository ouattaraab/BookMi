<?php

namespace Tests\Unit\Enums;

use App\Enums\TrackingStatus;
use PHPUnit\Framework\TestCase;

class TrackingStatusTest extends TestCase
{
    public function test_valid_forward_transitions(): void
    {
        $chain = [
            [TrackingStatus::Preparing,  TrackingStatus::EnRoute],
            [TrackingStatus::EnRoute,    TrackingStatus::Arrived],
            [TrackingStatus::Arrived,    TrackingStatus::Performing],
            [TrackingStatus::Performing, TrackingStatus::Completed],
        ];

        foreach ($chain as [$from, $to]) {
            $this->assertTrue($from->canTransitionTo($to), "{$from->value} → {$to->value}");
        }
    }

    public function test_backward_transitions_are_invalid(): void
    {
        $backwards = [
            [TrackingStatus::EnRoute, TrackingStatus::Preparing],
            [TrackingStatus::Arrived, TrackingStatus::EnRoute],
            [TrackingStatus::Performing, TrackingStatus::Arrived],
            [TrackingStatus::Completed, TrackingStatus::Performing],
        ];

        foreach ($backwards as [$from, $to]) {
            $this->assertFalse($from->canTransitionTo($to), "{$from->value} should not → {$to->value}");
        }
    }

    public function test_completed_has_no_transitions(): void
    {
        $this->assertEmpty(TrackingStatus::Completed->allowedTransitions());
    }

    public function test_labels_are_defined(): void
    {
        foreach (TrackingStatus::cases() as $case) {
            $this->assertNotEmpty($case->label());
        }
    }
}
