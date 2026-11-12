<?php

namespace Tests\Unit;

use App\Models\Seat;
use App\Services\SingleSeatRule;
use Tests\TestCase;

class SingleSeatRuleTest extends TestCase
{
    private SingleSeatRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new SingleSeatRule;
    }

    public function test_it_allows_one_seat_and_adjacent_seats_without_leaving_an_orphan(): void
    {
        $seats = $this->seats(range(1, 6));

        $this->assertSame([], $this->rule->orphanedSeatIds($seats, [], [1003]));
        $this->assertSame([], $this->rule->orphanedSeatIds($seats, [1001], [1002, 1003]));
    }

    public function test_it_rejects_a_single_seat_between_occupied_seats(): void
    {
        $seats = $this->seats(range(1, 5));

        $this->assertSame([1002], $this->rule->orphanedSeatIds($seats, [1001, 1003], []));
    }

    public function test_it_rejects_single_empty_seats_at_either_end_of_a_row(): void
    {
        $seats = $this->seats(range(1, 5));

        $this->assertSame([1005], $this->rule->orphanedSeatIds($seats, [1001, 1002, 1003], [1004]));
        $this->assertSame([1001], $this->rule->orphanedSeatIds($seats, [1003, 1004, 1005], [1002]));
    }

    public function test_rows_seat_types_and_physical_position_gaps_are_independent(): void
    {
        $seats = [
            ...$this->seats([1, 2, 3], 1, ['standard', 'vip', 'couple']),
            ...$this->seats([1, 2, 3], 2),
            ...$this->seats([1, 3], 3),
        ];

        $this->assertSame([], $this->rule->orphanedSeatIds(
            $seats,
            [1001, 2001, 3001],
            [1002, 1003, 2002, 2003, 3003]
        ));
        $this->assertTrue($this->rule->leavesSingleSeat($seats, [1001, 1003], []));
        $this->assertSame([1002], $this->rule->orphanedSeatIds($seats, [1001, 1003], []));
    }

    public function test_an_inactive_seat_and_an_expired_hold_do_not_create_an_occupied_neighbor(): void
    {
        $seats = $this->seats([1, 2, 3, 4, 5]);
        $seats[1]->is_active = false;

        $this->assertSame([], $this->rule->orphanedSeatIds($seats, [1001], [1003]));
        $this->assertSame([], $this->rule->orphanedSeatIds($this->seats(range(1, 5)), [], [1003]));
    }

    private function seats(array $positions, int $row = 1, array $types = []): array
    {
        return array_map(function (int $position, int $index) use ($row, $types) {
            $seat = new Seat([
                'room_id' => 1,
                'row_number' => $row,
                'seat_number' => $position,
                'seat_type' => $types[$index] ?? 'standard',
                'is_active' => true,
            ]);
            $seat->forceFill(['id' => ($row * 1000) + $position]);

            return $seat;
        }, $positions, array_keys($positions));
    }
}
