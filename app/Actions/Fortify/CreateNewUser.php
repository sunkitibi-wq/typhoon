<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Banking\KycVerification;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'phone' => 'nullable|string|regex:/^\+?[1-9]\d{1,14}$/',
            'password' => $this->passwordRules(),
        ])->validate();

        // Ensure a default "client" role exists
        $clientRole = Role::firstOrCreate(
            ['name' => 'client'],
            ['description' => 'Default client role']
        );

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone_number'] ?? $input['phone'] ?? null,
            'password' => $input['password'],
            'role_id' => $clientRole->id,
        ]);

        // Save profile/KYC data collected during registration wizard
        if (!empty($input['country']) || !empty($input['id_type'])) {
            KycVerification::create([
                'user_id' => $user->id,
                'kyc_level' => 'basic',
                'status' => 'pending',
                'id_type' => $input['id_type'] ?? null,
                'id_number' => $input['id_number'] ?? null,
                'country' => $input['country'] ?? null,
                'date_of_birth' => $input['date_of_birth'] ?? null,
                'address_line1' => $input['address_line1'] ?? null,
                'address_line2' => $input['address_line2'] ?? null,
                'city' => $input['city'] ?? null,
                'state' => $input['state'] ?? null,
                'postal_code' => $input['postal_code'] ?? null,
                'source_of_funds' => $input['source_of_funds'] ?? null,
                'occupation' => $input['occupation'] ?? null,
            ]);
        }

        return $user;
    }
}
