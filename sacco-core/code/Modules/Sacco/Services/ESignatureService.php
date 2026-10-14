<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Client\Entities\Client;

class ESignatureService
{
    public function capture(Client $client, string $base64Image): bool
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $extension = $matches[1];
            $data = substr($base64Image, strpos($base64Image, ',') + 1);
            $data = base64_decode($data);
            if ($data === false) {
                return false;
            }
            $filename = 'signature_' . $client->id . '_' . time() . '.' . $extension;
            Storage::put('public/uploads/clients/' . $filename, $data);
            $client->signature = $filename;
            $client->save();
            return true;
        }
        return false;
    }

    public function hasSignature(Client $client): bool
    {
        return !empty($client->signature);
    }
}
