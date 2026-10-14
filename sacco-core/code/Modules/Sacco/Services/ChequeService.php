<?php

namespace Modules\Sacco\Services;

use Modules\Sacco\Entities\SaccoCheque;

class ChequeService
{
    public function present(SaccoCheque $cheque): void
    {
        $cheque->status = 'presented';
        $cheque->presented_date = date('Y-m-d');
        $cheque->save();
    }

    public function clear(SaccoCheque $cheque): void
    {
        $cheque->status = 'cleared';
        $cheque->cleared_date = date('Y-m-d');
        $cheque->save();
    }

    public function bounce(SaccoCheque $cheque, ?string $notes = null): void
    {
        $cheque->status = 'bounced';
        $cheque->notes = $notes;
        $cheque->save();
    }

    public function parseMicr(?string $micr): array
    {
        if (!$micr) {
            return ['cheque_number' => null, 'bank_code' => null, 'branch_code' => null, 'account_number' => null];
        }
        $parts = preg_split('/[\s<:]+/', trim($micr));
        return [
            'cheque_number' => $parts[0] ?? null,
            'bank_code' => $parts[1] ?? null,
            'branch_code' => $parts[2] ?? null,
            'account_number' => $parts[3] ?? null,
        ];
    }
}
