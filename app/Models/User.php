<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'barber_id',
        'customer_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isBarber(): bool
    {
        return $this->role === UserRole::BARBER;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }

    public function matchesBarber(Barber|int|null $barber): bool
    {
        $barberId = $barber instanceof Barber ? $barber->getKey() : $barber;

        return $this->isBarber()
            && filled($this->barber_id)
            && filled($barberId)
            && ((int) $this->barber_id === (int) $barberId);
    }

    public function matchesCustomer(Customer|int|null $customer): bool
    {
        $customerId = $customer instanceof Customer ? $customer->getKey() : $customer;

        return $this->isCustomer()
            && filled($this->customer_id)
            && filled($customerId)
            && ((int) $this->customer_id === (int) $customerId);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isBarber()) {
            return filled($this->barber_id);
        }

        if ($this->isCustomer()) {
            return filled($this->customer_id);
        }

        return false;
    }
}
