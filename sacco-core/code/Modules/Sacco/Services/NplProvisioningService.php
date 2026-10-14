<?php

namespace Modules\Sacco\Services;

class NplProvisioningService
{
    public function classifyPortfolio(string $asOfDate): array
    {
        return (new CaelsClassificationService())->classifyPortfolio($asOfDate);
    }
}
