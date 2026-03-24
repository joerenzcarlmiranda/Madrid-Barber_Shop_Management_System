<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\BarberSchedule;
use App\Models\Customer;
use App\Models\Service;
use App\Models\WalkIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppointmentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_back_to_back_bookings_are_allowed_for_the_same_barber(): void
    {
        [$service, $barber, $customer] = $this->createDependencies();

        Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'appointment_date' => '2026-03-24',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'confirmed',
        ]);

        $this->assertFalse(
            Appointment::hasBarberConflict($barber->id, '2026-03-24', '10:00:00', '11:00:00'),
        );
    }

    public function test_overlapping_appointments_and_walk_ins_are_blocked(): void
    {
        [$service, $barber, $customer] = $this->createDependencies();

        Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'appointment_date' => '2026-03-24',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'confirmed',
        ]);

        WalkIn::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'queue_number' => 'W-20260324-001',
            'visit_date' => '2026-03-24',
            'arrival_time' => '10:00:00',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'in_service',
        ]);

        $this->assertTrue(
            Appointment::hasBarberConflict($barber->id, '2026-03-24', '09:30:00', '10:30:00'),
        );

        $this->assertTrue(
            Appointment::hasBarberConflict($barber->id, '2026-03-24', '11:30:00', '12:30:00'),
        );
    }

    public function test_barber_must_be_marked_available_to_accept_appointments(): void
    {
        [$service, $barber] = $this->createDependencies();

        $this->assertTrue(Appointment::barberIsAvailable($barber->id));

        $barber->update([
            'is_available' => false,
        ]);

        $this->assertFalse(Appointment::barberIsAvailable($barber->id));
    }

    public function test_barber_schedule_blocks_times_outside_working_hours(): void
    {
        [$service, $barber] = $this->createDependencies();
        $appointmentDate = '2026-03-24';

        BarberSchedule::create([
            'barber_id' => $barber->id,
            'day_of_week' => Carbon::parse($appointmentDate)->dayOfWeekIso,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_day_off' => false,
        ]);

        $status = Appointment::getBarberAvailabilityStatus(
            $barber->id,
            $appointmentDate,
            '16:30:00',
            $service,
        );

        $this->assertFalse($status['available']);
        $this->assertStringContainsString('outside the barber', $status['message']);
    }

    public function test_barber_day_off_blocks_appointments(): void
    {
        [$service, $barber] = $this->createDependencies();
        $appointmentDate = '2026-03-24';

        BarberSchedule::create([
            'barber_id' => $barber->id,
            'day_of_week' => Carbon::parse($appointmentDate)->dayOfWeekIso,
            'start_time' => null,
            'end_time' => null,
            'is_day_off' => true,
        ]);

        $status = Appointment::getBarberAvailabilityStatus(
            $barber->id,
            $appointmentDate,
            '09:00:00',
            $service,
        );

        $this->assertFalse($status['available']);
        $this->assertStringContainsString('does not accept appointments', $status['message']);
    }

    protected function createDependencies(): array
    {
        $service = Service::create([
            'name' => 'Haircut',
            'description' => 'Basic haircut service',
            'price' => 15,
            'duration' => '60',
        ]);

        $barber = Barber::create([
            'firstname' => 'Julius',
            'lastname' => 'Naron',
            'email' => 'julius@example.com',
            'is_available' => true,
        ]);

        $customer = Customer::create([
            'firstname' => 'Alfred',
            'lastname' => 'Tamayo',
            'email' => 'alfred@example.com',
        ]);

        return [$service, $barber, $customer];
    }
}
