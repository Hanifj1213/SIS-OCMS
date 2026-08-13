<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentChecksheet;
use Illuminate\Support\Carbon;

/**
 * Aturan integritas checksheet komponen — enforcement server-side agar
 * bypass UI (review mode, CAN_INTERACT=false) tidak dapat memutasi data.
 */
class ChecksheetIntegrityService
{
    /** @var list<string> */
    public const VALID_ANSWERS = ['good', 'bad', 'none'];

    /**
     * Alasan penolakan mutasi, atau null bila checksheet boleh diubah.
     */
    public function mutationDeniedReason(Component $component, ComponentChecksheet $checksheet): ?string
    {
        if ($checksheet->stage_number !== $component->current_stage) {
            return 'Checksheet tahap historis/review tidak dapat diubah.';
        }

        if ($component->is_waiting_approval) {
            return 'Checksheet tidak dapat diubah saat menunggu approval.';
        }

        if ($checksheet->completed_at !== null) {
            return 'Checksheet yang sudah selesai tidak dapat diubah.';
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function validItemIds(ComponentChecksheet $checksheet): array
    {
        return collect($checksheet->items ?? [])
            ->pluck('id')
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->values()
            ->all();
    }

    public function isValidItemId(ComponentChecksheet $checksheet, string $itemId): bool
    {
        return in_array($itemId, $this->validItemIds($checksheet), true);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findItem(ComponentChecksheet $checksheet, string $itemId): ?array
    {
        foreach ($checksheet->items ?? [] as $item) {
            if (($item['id'] ?? null) === $itemId) {
                return $item;
            }
        }

        return null;
    }

    public function isCustomItem(array $item): bool
    {
        return ($item['custom'] ?? false) === true;
    }

    /**
     * Alasan penolakan hapus item, atau null bila boleh dihapus.
     */
    public function removeItemDeniedReason(
        Component $component,
        ComponentChecksheet $checksheet,
        string $itemId,
    ): ?string {
        if ($reason = $this->mutationDeniedReason($component, $checksheet)) {
            return $reason;
        }

        $item = $this->findItem($checksheet, $itemId);
        if ($item === null) {
            return 'Item checksheet tidak dikenal.';
        }

        if (! $this->isCustomItem($item)) {
            return 'Item standar/template tidak dapat dihapus.';
        }

        return null;
    }

    /**
     * Buang jawaban orphan/duplikat dan nilai di luar good/bad/none.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, string>
     */
    public function sanitizeAnswers(ComponentChecksheet $checksheet, array $answers): array
    {
        $validIds = $this->validItemIds($checksheet);
        $clean = [];

        foreach ($validIds as $id) {
            if (! array_key_exists($id, $answers)) {
                continue;
            }

            $value = $answers[$id];
            if (is_string($value) && in_array($value, self::VALID_ANSWERS, true)) {
                $clean[$id] = $value;
            }
        }

        return $clean;
    }

    /**
     * Progress hanya dari item valid yang benar-benar terjawab.
     */
    public function answeredCount(ComponentChecksheet $checksheet, ?array $answers = null): int
    {
        $answers ??= $checksheet->answers ?? [];

        return count($this->sanitizeAnswers($checksheet, $answers));
    }

    public function isFullyAnswered(ComponentChecksheet $checksheet, ?array $answers = null): bool
    {
        $total = count($this->validItemIds($checksheet));

        if ($total === 0) {
            return false;
        }

        return $this->answeredCount($checksheet, $answers) >= $total;
    }

    public function resolveCompletedAt(ComponentChecksheet $checksheet, array $answers): ?Carbon
    {
        return $this->isFullyAnswered($checksheet, $answers) ? Carbon::now() : null;
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return list<string> ID item dengan nilai tidak valid (bukan good/bad/none)
     */
    public function invalidAnswerKeys(ComponentChecksheet $checksheet, array $answers): array
    {
        $invalid = [];

        foreach ($answers as $key => $value) {
            if (! is_string($key) || ! $this->isValidItemId($checksheet, $key)) {
                $invalid[] = (string) $key;

                continue;
            }

            if (! is_string($value) || ! in_array($value, self::VALID_ANSWERS, true)) {
                $invalid[] = $key;
            }
        }

        return array_values(array_unique($invalid));
    }
}
