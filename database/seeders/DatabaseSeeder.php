<?php

namespace Database\Seeders;

use App\Models\AgentTask;
use App\Models\Appointment;
use App\Models\AvailabilityRange;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $maria = User::query()->updateOrCreate(
            ['email' => 'maria.santos@remax.com'],
            [
                'name' => 'María Santos',
                'password' => Hash::make('Remax2026!'),
                'phone' => '+1 809 555 2140',
                'office' => 'RE/MAX Capital · Piantini',
                'license_number' => 'CIE-18452',
                'timezone' => 'America/Santo_Domingo',
                'role' => 'agent',
                'bio' => 'Agente residencial y de inversión. Especialista en Piantini, Naco y costa este.',
                'google_calendar_linked' => false,
            ],
        );

        $carlos = User::query()->updateOrCreate(
            ['email' => 'carlos.herrera@remax.com'],
            [
                'name' => 'Carlos Herrera',
                'password' => Hash::make('Remax2026!'),
                'phone' => '+1 809 555 8871',
                'office' => 'RE/MAX Premium · Bella Vista',
                'license_number' => 'CIE-20911',
                'timezone' => 'America/Santo_Domingo',
                'role' => 'agent',
                'bio' => 'Luxury y reubicación corporativa. Acompañamiento de compradores internacionales.',
                'google_calendar_linked' => false,
            ],
        );

        $this->seedAvailability($maria);
        $this->seedAvailability($carlos);
        $this->seedMariaBook($maria);
        $this->seedCarlosBook($carlos);
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

        AvailabilityRange::query()->create([
            'user_id' => $user->id,
            'weekday' => 6,
            'start_time' => '10:00',
            'end_time' => '13:00',
            'slot_minutes' => 45,
            'is_active' => true,
        ]);
    }

    private function seedMariaBook(User $user): void
    {
        $user->appointments()->delete();
        $user->tasks()->delete();
        $user->contacts()->delete();

        $contacts = collect([
            [
                'name' => 'Laura Méndez',
                'phone' => '+1 809 701 3344',
                'email' => 'laura.mendez@correo.com',
                'type' => 'comprador',
                'stage' => 'visitas',
                'address' => 'Naco, Santo Domingo',
                'property_interest' => 'Apartamento 3 habitaciones en Piantini',
                'budget' => 'USD 285,000',
                'notes' => 'Quiere mudarse antes de diciembre. Prefiere piso alto y parqueos.',
                'source' => 'referido',
            ],
            [
                'name' => 'Andrés Peña',
                'phone' => '+1 809 612 9088',
                'email' => 'andres.pena@empresa.com',
                'type' => 'vendedor',
                'stage' => 'captacion',
                'address' => 'Bella Vista, Santo Domingo',
                'property_interest' => 'Casa esquinera 4 hab. con jardín',
                'budget' => 'USD 420,000',
                'notes' => 'Necesita CMA y fotos profesionales. Está abierto a negociación.',
                'source' => 'telefono',
            ],
            [
                'name' => 'Sofía Cabrera',
                'phone' => '+1 829 455 1120',
                'email' => 'sofia.cabrera@gmail.com',
                'type' => 'lead',
                'stage' => 'nuevo',
                'address' => 'Santiago de los Caballeros',
                'property_interest' => 'Townhouse en Cerros de Gurabo',
                'budget' => 'USD 190,000',
                'notes' => 'Llamar para calificar presupuesto y preaprobación bancaria.',
                'source' => 'web',
            ],
            [
                'name' => 'Familia Rosario',
                'phone' => '+1 809 333 6712',
                'email' => 'rosario.familia@outlook.com',
                'type' => 'comprador',
                'stage' => 'oferta',
                'address' => 'Evaristo Morales',
                'property_interest' => 'Penthouse con terraza',
                'budget' => 'USD 510,000',
                'notes' => 'Oferta enviada. Esperar contraoferta del vendedor.',
                'source' => 'open_house',
            ],
            [
                'name' => 'Miguel Ángel Núñez',
                'phone' => '+1 849 220 4477',
                'email' => 'miguel.nunez@invest.com',
                'type' => 'inversionista',
                'stage' => 'seguimiento',
                'address' => 'Punta Cana',
                'property_interest' => 'Estudio frente a marina',
                'budget' => 'USD 165,000',
                'notes' => 'Busca ROI de alquiler vacacional. Pedir comparables 2025-2026.',
                'source' => 'referido',
            ],
            [
                'name' => 'Elena Vargas',
                'phone' => '+1 809 888 0091',
                'email' => 'elena.vargas@correo.com',
                'type' => 'cerrado',
                'stage' => 'postventa',
                'address' => 'Los Cacicazgos',
                'property_interest' => 'Apartamento ya entregado',
                'budget' => 'USD 310,000',
                'notes' => 'Cliente feliz. Pedir referidos y review Google.',
                'source' => 'manual',
            ],
        ])->map(fn (array $row) => Contact::query()->create($row + ['user_id' => $user->id]));

        $laura = $contacts[0];
        $andres = $contacts[1];
        $sofia = $contacts[2];
        $rosario = $contacts[3];
        $miguel = $contacts[4];

        $today = Carbon::now('America/Santo_Domingo')->startOfDay();
        $this->weekdaySafe($today, 10, 0);

        Appointment::query()->create([
            'user_id' => $user->id,
            'contact_id' => $laura->id,
            'title' => 'Visita apto Torre Ámbar',
            'type' => 'visita',
            'starts_at' => $this->nextSlot($today, 10, 0),
            'ends_at' => $this->nextSlot($today, 11, 0),
            'location' => 'Av. Abraham Lincoln 106, Piantini',
            'indications' => 'Llegar 10 min antes. Pedir en recepción a nombre de RE/MAX. Mostrar 3er y 8vo nivel. Destacar amenidades y cuota de mantenimiento.',
            'status' => 'programada',
        ]);

        Appointment::query()->create([
            'user_id' => $user->id,
            'contact_id' => $andres->id,
            'title' => 'Cita de captación y CMA',
            'type' => 'captacion',
            'starts_at' => $this->nextSlot($today, 16, 0),
            'ends_at' => $this->nextSlot($today, 17, 0),
            'location' => 'Casa del cliente, Bella Vista',
            'indications' => 'Llevar comparables impresos. Recorrer jardín y techo. Proponer plan de marketing 14 días.',
            'status' => 'programada',
        ]);

        $tomorrow = $today->copy()->addDay();
        Appointment::query()->create([
            'user_id' => $user->id,
            'contact_id' => $rosario->id,
            'title' => 'Negociación de oferta',
            'type' => 'reunion',
            'starts_at' => $this->nextSlot($tomorrow, 9, 30),
            'ends_at' => $this->nextSlot($tomorrow, 10, 30),
            'location' => 'Oficina RE/MAX Capital',
            'indications' => 'Revisar contraoferta. Tener preaprobación bancaria a mano. Confirmar fecha tentativa de cierre.',
            'status' => 'programada',
        ]);

        Appointment::query()->create([
            'user_id' => $user->id,
            'contact_id' => $miguel->id,
            'title' => 'Tour virtual Punta Cana',
            'type' => 'visita',
            'starts_at' => $this->nextSlot($tomorrow, 15, 30),
            'ends_at' => $this->nextSlot($tomorrow, 16, 30),
            'location' => 'Videollamada Meet + marina Cap Cana',
            'indications' => 'Enviar link 1 hora antes. Mostrar occupancy, HOA y temporada alta. Preguntar si viaja la próxima semana.',
            'status' => 'programada',
        ]);

        Appointment::query()->create([
            'user_id' => $user->id,
            'contact_id' => $andres->id,
            'title' => 'Firma de listado exclusivo',
            'type' => 'cierre',
            'starts_at' => $this->nextSlot($today->copy()->addDays(3), 11, 0),
            'ends_at' => $this->nextSlot($today->copy()->addDays(3), 12, 0),
            'location' => 'Notaría Piantini',
            'indications' => 'Contrato de exclusividad 90 días. Fotógrafo el viernes.',
            'status' => 'programada',
        ]);

        AgentTask::query()->create([
            'user_id' => $user->id,
            'contact_id' => $sofia->id,
            'title' => 'Llamar para calificar lead',
            'description' => 'Confirmar presupuesto, zona y si ya tiene preaprobación. Si califica, agendar visita el sábado.',
            'type' => 'llamada',
            'due_at' => $tomorrow->copy()->setTime(9, 0),
            'duration_minutes' => 20,
            'completed' => false,
        ]);

        AgentTask::query()->create([
            'user_id' => $user->id,
            'contact_id' => $laura->id,
            'title' => 'Enviar 3 opciones similares',
            'description' => 'Filtrar por 3 hab, 2 parqueos, Piantini/Naco, menos de USD 300k.',
            'type' => 'seguimiento',
            'due_at' => $today->copy()->setTime(18, 0),
            'duration_minutes' => 30,
            'completed' => false,
        ]);

        AgentTask::query()->create([
            'user_id' => $user->id,
            'contact_id' => $rosario->id,
            'title' => 'Pedir documentos de oferta',
            'description' => 'Cédulas, preaprobación y comprobante de señal.',
            'type' => 'documentos',
            'due_at' => $tomorrow->copy()->setTime(12, 0),
            'duration_minutes' => 25,
            'completed' => false,
        ]);
    }

    private function seedCarlosBook(User $user): void
    {
        $user->appointments()->delete();
        $user->tasks()->delete();
        $user->contacts()->delete();

        $ana = Contact::query()->create([
            'user_id' => $user->id,
            'name' => 'Ana Lucía Gómez',
            'phone' => '+1 809 444 2188',
            'email' => 'ana.gomez@corp.com',
            'type' => 'comprador',
            'stage' => 'visitas',
            'address' => 'Serrallés',
            'property_interest' => 'Villa con vista, 4 hab.',
            'budget' => 'USD 780,000',
            'notes' => 'Traslado desde Miami. Quiere colegio cercano y staff quarters.',
            'source' => 'referido',
        ]);

        $diego = Contact::query()->create([
            'user_id' => $user->id,
            'name' => 'Diego Martínez',
            'phone' => '+1 829 901 6655',
            'email' => 'diego.martinez@mail.com',
            'type' => 'lead',
            'stage' => 'nuevo',
            'address' => 'Gazcue',
            'property_interest' => 'Loft remodelado',
            'budget' => 'USD 210,000',
            'notes' => 'Llamar mañana. Vio el listing en Instagram.',
            'source' => 'redes',
        ]);

        $today = Carbon::now('America/Santo_Domingo')->startOfDay();

        Appointment::query()->create([
            'user_id' => $user->id,
            'contact_id' => $ana->id,
            'title' => 'Tour villas Serrallés',
            'type' => 'visita',
            'starts_at' => $this->nextSlot($today, 11, 0),
            'ends_at' => $this->nextSlot($today, 12, 30),
            'location' => 'Calle Principal, Serrallés',
            'indications' => 'Recoger en hotel El Embajador a las 10:40. Mostrar 2 villas. Resaltar seguridad y área social.',
            'status' => 'programada',
        ]);

        AgentTask::query()->create([
            'user_id' => $user->id,
            'contact_id' => $diego->id,
            'title' => 'Llamar lead de Instagram',
            'description' => 'Calificar zona, financiamiento y disponibilidad para visita.',
            'type' => 'llamada',
            'due_at' => $today->copy()->addDay()->setTime(10, 0),
            'duration_minutes' => 15,
            'completed' => false,
        ]);
    }

    private function nextSlot(Carbon $day, int $hour, int $minute): Carbon
    {
        $slot = $day->copy()->setTime($hour, $minute);
        if (in_array((int) $slot->dayOfWeek, [0], true)) {
            $slot->addDay();
        }

        return $slot;
    }

    private function weekdaySafe(Carbon $day, int $hour, int $minute): Carbon
    {
        return $this->nextSlot($day, $hour, $minute);
    }
}
