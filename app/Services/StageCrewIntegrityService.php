<?php

namespace App\Services;

use App\Models\Component;
use App\Models\StageMechanicLog;

class StageCrewIntegrityService
{
    public function mutationDeniedReason(Component $component, ?StageMechanicLog $log = null): ?string
    {
        if ($component->is_waiting_approval) {
            return 'Crew tidak dapat diubah saat menunggu approval.';
        }

        if ($log !== null && $log->stage_number !== $component->current_stage) {
            return 'Crew tahap historis tidak dapat diubah.';
        }

        if ($log !== null && $log->clock_out !== null) {
            return 'Mekanik ini sudah tidak aktif.';
        }

        return null;
    }
}
