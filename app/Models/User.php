<?php

namespace App\Models;

use App\Models\Banking\Account;
use App\Models\Banking\CryptoWallet;
use App\Models\Banking\KycVerification;
use App\Models\Banking\Loan;
use App\Models\Banking\Transaction;
use App\Models\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name', 'email', 'password', 'phone', 'nationality',
    'date_of_birth', 'country_of_residence', 'kyc_level',
    'two_factor_enabled', 'status', 'terms_accepted_at',
    'solaris_person_id',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes, Billable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'date_of_birth' => 'date',
            'terms_accepted_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function kycVerification(): HasOne
    {
        return $this->hasOne(KycVerification::class);
    }

    public function cryptoWallets(): HasMany
    {
        return $this->hasMany(CryptoWallet::class);
    }

    public function defaultAccount(): HasOne
    {
        return $this->hasOne(Account::class)->where('is_default', true);
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(\App\Models\Banking\Beneficiary::class, 'user_id');
    }

    public function cryptoOrders(): HasMany
    {
        return $this->hasMany(\App\Models\Banking\CryptoOrder::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
