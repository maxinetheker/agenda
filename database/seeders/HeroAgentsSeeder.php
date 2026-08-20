<?php

namespace Database\Seeders;

use App\Models\AvailabilityRange;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HeroAgentsSeeder extends Seeder
{
    public function run(): void
    {
        $heroes = [
            [
                'name' => 'Barry Allen',
                'email' => 'barry.allen@remax.com',
                'phone' => '+1 809 555 1001',
                'office' => 'RE/MAX Central City',
                'license_number' => 'CIE-FLASH',
                'bio' => 'Cierres a la velocidad de la luz. Especialista en preventa y reubicación.',
            ],
            [
                'name' => 'Clark Kent',
                'email' => 'clark.kent@remax.com',
                'phone' => '+1 809 555 1002',
                'office' => 'RE/MAX Metrópolis',
                'license_number' => 'CIE-SUPER',
                'bio' => 'Propiedades residenciales y fincas. Atención discreta a clientes corporativos.',
            ],
            [
                'name' => 'Diana Prince',
                'email' => 'diana.prince@remax.com',
                'phone' => '+1 809 555 1003',
                'office' => 'RE/MAX Themyscira',
                'license_number' => 'CIE-WW',
                'bio' => 'Luxury y negociación. Captación de villas y penthouses.',
            ],
            [
                'name' => 'Peter Parker',
                'email' => 'peter.parker@remax.com',
                'phone' => '+1 809 555 1004',
                'office' => 'RE/MAX Queens',
                'license_number' => 'CIE-SPIDEY',
                'bio' => 'Primeros compradores y alquileres. Seguimiento cercano de cada lead.',
            ],
            [
                'name' => 'Natasha Romanoff',
                'email' => 'natasha.romanoff@remax.com',
                'phone' => '+1 809 555 1005',
                'office' => 'RE/MAX Shield',
                'license_number' => 'CIE-WIDOW',
                'bio' => 'Inversión y off-market. Perfiles internacionales y confidencialidad.',
            ],
        ];

        foreach ($heroes as $hero) {
            $user = User::query()->updateOrCreate(
                ['email' => $hero['email']],
                [
                    'name' => $hero['name'],
                    'password' => Hash::make('Hero2026!'),
                    'phone' => $hero['phone'],
                    'office' => $hero['office'],
                    'license_number' => $hero['license_number'],
                    'timezone' => 'America/Santo_Domingo',
                    'role' => 'agent',
                    'bio' => $hero['bio'],
                    'google_calendar_linked' => false,
                ],
            );

            $this->seedAvailability($user);
            $this->seedSampleContact($user);
        }
    }

    private function seedAvailability(User $user): void
    {
        $user->availabilityRanges()->delete();

        foreach ([1, 2, 3, 4, 5] as $weekday) {
            AvailabilityRange::query()->create([
                'user_id' => $user->id,
                'weekday' => $weekday,
                'start_time' => '09:00',
                'end_time' => '13:00',
                'slot_minutes' => 30,
                'is_active' => true,
            ]);
            AvailabilityRange::query()->create([
                'user_id' => $user->id,
                'weekday' => $weekday,
                'start_time' => '15:00',
                'end_time' => '18:00',
                'slot_minutes' => 30,
                'is_active' => true,
            ]);
        }
    }

    private function seedSampleContact(User $user): void
    {
        Contact::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'email' => 'cliente.'.strtok($user->email, '@').'@correo.com',
            ],
            [
                'name' => 'Cliente de '.$user->name,
                'phone' => '+1 809 700 0000',
                'type' => 'lead',
                'stage' => 'nuevo',
                'property_interest' => 'Apartamento 2 habitaciones',
                'budget' => 'USD 180,000',
                'notes' => 'Contacto de ejemplo. Puedes editarlo desde la ficha.',
                'address' => 'Piantini, Santo Domingo',
                'source' => 'manual',
            ],
        );
    }
}
