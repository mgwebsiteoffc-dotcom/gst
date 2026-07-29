<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $name = $this->faker->company(),
            'slug' => Str::slug($name).'-'.Str::random(4),
            'legal_name' => $name,
            'gstin' => '09AAACD1234F1Z5',
            'state_code' => '09',
            'registration_type' => 'regular',
            'tax_mode' => 'gst',
            'allow_both_modes' => true,
            'einvoice_enabled' => true,
            'nic_sandbox' => true,
            'currency' => 'INR',
        ];
    }

    public function nonGst(): static
    {
        return $this->state(fn () => ['gstin' => null, 'tax_mode' => 'non_gst', 'einvoice_enabled' => false]);
    }
}
