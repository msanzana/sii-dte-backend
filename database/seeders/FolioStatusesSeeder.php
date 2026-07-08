<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FolioStatusesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            [
                'code' => 'available',
                'name' => 'Disponible',
                'description' => 'Folio disponible para reserva.',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'reserved',
                'name' => 'Reservado',
                'description' => 'Folio reservado temporalmente para un sistema externo.',
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'code' => 'released',
                'name' => 'Liberado',
                'description' => 'Folio que fue reservado y luego devuelto a disponibilidad.',
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'code' => 'assigned',
                'name' => 'Asignado',
                'description' => 'Folio asignado a un documento en construcción.',
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'code' => 'xml_generating',
                'name' => 'Generando XML',
                'description' => 'El documento asociado está en preparación de XML.',
                'sort_order' => 50,
                'is_active' => true,
            ],
            [
                'code' => 'xml_generated',
                'name' => 'XML generado',
                'description' => 'El XML del documento ya fue generado.',
                'sort_order' => 60,
                'is_active' => true,
            ],
            [
                'code' => 'xml_signed',
                'name' => 'XML firmado',
                'description' => 'El XML ya fue firmado.',
                'sort_order' => 70,
                'is_active' => true,
            ],
            [
                'code' => 'queued_for_send',
                'name' => 'En cola de envío',
                'description' => 'El documento está en cola para envío al SII.',
                'sort_order' => 80,
                'is_active' => true,
            ],
            [
                'code' => 'sent_to_sii',
                'name' => 'Enviado al SII',
                'description' => 'El documento fue enviado al SII.',
                'sort_order' => 90,
                'is_active' => true,
            ],
            [
                'code' => 'accepted_by_sii',
                'name' => 'Aceptado por SII',
                'description' => 'El documento fue aceptado por el SII.',
                'sort_order' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'rejected_by_sii',
                'name' => 'Rechazado por SII',
                'description' => 'El documento fue rechazado por el SII.',
                'sort_order' => 110,
                'is_active' => true,
            ],
            [
                'code' => 'expired',
                'name' => 'Expirado',
                'description' => 'La reserva o el folio expiró por vigencia.',
                'sort_order' => 120,
                'is_active' => true,
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelado',
                'description' => 'Folio cancelado por operación o flujo de negocio.',
                'sort_order' => 130,
                'is_active' => true,
            ],
        ];

        foreach ($rows as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        DB::table('folio_statuses')->upsert(
            $rows,
            ['code'],
            ['name', 'description', 'sort_order', 'is_active', 'updated_at']
        );
    }
}