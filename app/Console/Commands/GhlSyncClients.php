<?php

namespace App\Console\Commands;

use App\Models\GhlClient;
use App\Models\GhlLocation;
use App\Services\GhlApiService;
use Illuminate\Console\Command;
use Throwable;

class GhlSyncClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ghl:sync-clients {--location= : GHL location ID para sync puntual}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza subcuentas y clientes de GoHighLevel';

    /**
     * Execute the console command.
     */
    public function handle(GhlApiService $ghlApiService): int
    {
        try {
            $locations = $ghlApiService->fetchLocations();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $totalClients = 0;
        $processedLocations = 0;

        $requestedLocation = (string) $this->option('location');

        foreach ($locations as $rawLocation) {
            $locationId = (string) ($rawLocation['id'] ?? $rawLocation['_id'] ?? '');

            if ($locationId === '') {
                continue;
            }

            if ($requestedLocation !== '' && $requestedLocation !== $locationId) {
                continue;
            }

            $processedLocations++;

            $location = GhlLocation::updateOrCreate(
                ['ghl_id' => $locationId],
                [
                    'name' => (string) ($rawLocation['name'] ?? 'Sin nombre'),
                    'company_id' => $rawLocation['companyId'] ?? null,
                    'timezone' => $rawLocation['timezone'] ?? null,
                    'raw' => $rawLocation,
                ]
            );

            try {
                $contacts = $ghlApiService->fetchContactsByLocation($locationId);
            } catch (Throwable $exception) {
                $this->warn("Location {$location->name}: sin acceso a contactos ({$exception->getMessage()}).");
                continue;
            }

            foreach ($contacts as $rawContact) {
                $contactId = (string) ($rawContact['id'] ?? $rawContact['_id'] ?? '');

                if ($contactId === '') {
                    continue;
                }

                $firstName = trim((string) ($rawContact['firstName'] ?? ''));
                $lastName = trim((string) ($rawContact['lastName'] ?? ''));
                $fullName = trim($firstName.' '.$lastName);

                GhlClient::updateOrCreate(
                    [
                        'ghl_location_id' => $location->id,
                        'ghl_contact_id' => $contactId,
                    ],
                    [
                        'name' => $fullName !== '' ? $fullName : ($rawContact['name'] ?? null),
                        'email' => $rawContact['email'] ?? null,
                        'phone' => $rawContact['phone'] ?? null,
                        'tags' => $rawContact['tags'] ?? [],
                        'last_activity_at' => $rawContact['dateUpdated'] ?? null,
                        'raw' => $rawContact,
                    ]
                );

                $totalClients++;
            }

            $this->info("Location {$location->name}: ".count($contacts).' clientes sincronizados.');
        }

        if ($requestedLocation !== '' && $processedLocations === 0) {
            $this->error('No se encontro la subcuenta seleccionada en GHL: '.$requestedLocation);

            return self::FAILURE;
        }

        $this->info('Sync finalizado. Total clientes procesados: '.$totalClients);

        return self::SUCCESS;
    }
}
