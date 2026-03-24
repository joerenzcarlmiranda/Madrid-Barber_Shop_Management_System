<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\WalkIn;
use App\Policies\AppointmentPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\WalkInPolicy;
use App\Support\LinkedUserAccountManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_linked_account_manager_creates_barber_and_customer_users_with_roles(): void
    {
        $barber = Barber::create([
            'firstname' => 'Julius',
            'lastname' => 'Naron',
            'email' => 'barber@example.com',
            'is_available' => true,
        ]);

        $customer = Customer::create([
            'firstname' => 'Alfred',
            'lastname' => 'Tamayo',
            'email' => 'customer@example.com',
        ]);

        $manager = app(LinkedUserAccountManager::class);

        $manager->syncForBarber($barber, 'password');
        $manager->syncForCustomer($customer, 'password');

        $this->assertDatabaseHas('users', [
            'email' => 'barber@example.com',
            'role' => UserRole::BARBER->value,
            'barber_id' => $barber->id,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'customer@example.com',
            'role' => UserRole::CUSTOMER->value,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_barber_can_only_access_own_appointments_and_unassigned_walk_ins(): void
    {
        $service = $this->createService();

        $barber = Barber::create([
            'firstname' => 'Barber',
            'lastname' => 'One',
            'email' => 'barber.one@example.com',
            'is_available' => true,
        ]);

        $otherBarber = Barber::create([
            'firstname' => 'Barber',
            'lastname' => 'Two',
            'email' => 'barber.two@example.com',
            'is_available' => true,
        ]);

        $customer = Customer::create([
            'firstname' => 'Customer',
            'lastname' => 'One',
            'email' => 'customer.one@example.com',
        ]);

        $barberUser = User::create([
            'name' => $barber->full_name,
            'email' => 'barber.user@example.com',
            'password' => 'password',
            'role' => UserRole::BARBER,
            'barber_id' => $barber->id,
        ]);

        $ownAppointment = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'appointment_date' => '2026-03-24',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'pending',
        ]);

        $otherAppointment = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'barber_id' => $otherBarber->id,
            'appointment_date' => '2026-03-24',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
        ]);

        $unassignedWalkIn = WalkIn::create([
            'guest_name' => 'Walk In Guest',
            'service_id' => $service->id,
            'queue_number' => 'W-20260324-001',
            'visit_date' => '2026-03-24',
            'arrival_time' => '08:45:00',
            'status' => 'waiting',
        ]);

        $otherWalkIn = WalkIn::create([
            'guest_name' => 'Assigned Elsewhere',
            'service_id' => $service->id,
            'barber_id' => $otherBarber->id,
            'queue_number' => 'W-20260324-002',
            'visit_date' => '2026-03-24',
            'arrival_time' => '09:15:00',
            'status' => 'waiting',
        ]);

        $appointmentPolicy = new AppointmentPolicy;
        $walkInPolicy = new WalkInPolicy;

        $this->assertTrue($appointmentPolicy->view($barberUser, $ownAppointment));
        $this->assertTrue($appointmentPolicy->update($barberUser, $ownAppointment));
        $this->assertFalse($appointmentPolicy->view($barberUser, $otherAppointment));

        $this->assertTrue($walkInPolicy->view($barberUser, $unassignedWalkIn));
        $this->assertFalse($walkInPolicy->view($barberUser, $otherWalkIn));
    }

    public function test_customer_can_only_access_own_profile_and_non_completed_appointments(): void
    {
        $service = $this->createService();

        $customer = Customer::create([
            'firstname' => 'Customer',
            'lastname' => 'One',
            'email' => 'customer.one@example.com',
        ]);

        $otherCustomer = Customer::create([
            'firstname' => 'Customer',
            'lastname' => 'Two',
            'email' => 'customer.two@example.com',
        ]);

        $barber = Barber::create([
            'firstname' => 'Barber',
            'lastname' => 'One',
            'email' => 'barber.one@example.com',
            'is_available' => true,
        ]);

        $customerUser = User::create([
            'name' => $customer->full_name,
            'email' => 'customer.user@example.com',
            'password' => 'password',
            'role' => UserRole::CUSTOMER,
            'customer_id' => $customer->id,
        ]);

        $pendingAppointment = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'appointment_date' => '2026-03-24',
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'status' => 'pending',
        ]);

        $completedAppointment = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'appointment_date' => '2026-03-24',
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'status' => 'completed',
        ]);

        $otherAppointment = Appointment::create([
            'customer_id' => $otherCustomer->id,
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'appointment_date' => '2026-03-24',
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
            'status' => 'pending',
        ]);

        $appointmentPolicy = new AppointmentPolicy;
        $customerPolicy = new CustomerPolicy;

        $this->assertTrue($customerPolicy->view($customerUser, $customer));
        $this->assertTrue($customerPolicy->update($customerUser, $customer));
        $this->assertFalse($customerPolicy->view($customerUser, $otherCustomer));

        $this->assertTrue($appointmentPolicy->view($customerUser, $pendingAppointment));
        $this->assertTrue($appointmentPolicy->update($customerUser, $pendingAppointment));
        $this->assertFalse($appointmentPolicy->update($customerUser, $completedAppointment));
        $this->assertFalse($appointmentPolicy->view($customerUser, $otherAppointment));
    }

    protected function createService(): Service
    {
        return Service::create([
            'name' => 'Haircut',
            'description' => 'Basic haircut service',
            'price' => 15,
            'duration' => '60',
        ]);
    }
}
