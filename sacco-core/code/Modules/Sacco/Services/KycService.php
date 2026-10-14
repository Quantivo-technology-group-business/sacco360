<?php

namespace Modules\Sacco\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoKycDocument;
use Modules\Sacco\Entities\SaccoKycRequirement;
use Modules\Sacco\Entities\SaccoKycVerification;

class KycService
{
    public function getRequirements(): array
    {
        return SaccoKycRequirement::where('active', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    public function getMemberProfile(int $clientId): array
    {
        $client = Client::with(['identifications', 'client_type'])->findOrFail($clientId);
        
        $isOrganisation = false;
        if ($client->client_type && strtolower($client->client_type->name) === 'organisation') {
            $isOrganisation = true;
        }

        $typeFilter = $isOrganisation ? 'organisation' : 'individual';

        $requirements = SaccoKycRequirement::where('active', 1)
            ->whereIn('applies_to', [$typeFilter, 'both'])
            ->orderBy('sort_order')
            ->get();

        $documents = SaccoKycDocument::where('client_id', $clientId)->orderByDesc('id')->get();
        $verifications = SaccoKycVerification::where('client_id', $clientId)->orderByDesc('id')->limit(10)->get();

        $docsByType = [];
        foreach ($documents as $doc) {
            if (!isset($docsByType[$doc->document_type]) || $doc->id > $docsByType[$doc->document_type]->id) {
                $docsByType[$doc->document_type] = $doc;
            }
        }

        $checklist = [];
        foreach ($requirements as $req) {
            $doc = $docsByType[$req->code] ?? null;
            $satisfied = $this->requirementSatisfied($req, $doc, $client);
            $checklist[] = [
                'requirement' => $req,
                'document' => $doc,
                'satisfied' => $satisfied,
                'status' => $this->requirementStatus($req, $doc, $client, $satisfied),
            ];
        }

        $mandatory = $requirements->where('mandatory', 1);
        $mandatoryMet = collect($checklist)->filter(function ($item) {
            return $item['requirement']->mandatory && $item['satisfied'];
        })->count();

        $iprsRequired = $requirements->where('requires_iprs', 1)->isNotEmpty();
        $iprsOk = !empty($client->iprs_verified_at)
            || $verifications->contains(function ($v) {
                return in_array($v->match_status, ['matched', 'simulated'], true);
            });

        return [
            'client' => $client,
            'requirements' => $requirements,
            'documents' => $documents,
            'verifications' => $verifications,
            'checklist' => $checklist,
            'completion_percent' => $mandatory->count() > 0
                ? round(($mandatoryMet / $mandatory->count()) * 100)
                : 100,
            'mandatory_met' => $mandatoryMet,
            'mandatory_total' => $mandatory->count(),
            'iprs_verified' => $iprsOk,
            'iprs_required' => $iprsRequired,
            'can_approve' => $this->canApprove($client, $checklist, $iprsOk),
            'kyc_status' => $this->resolveStatus($client),
        ];
    }

    public function uploadDocument(int $clientId, array $data, ?UploadedFile $file = null, ?int $uploadedById = null): SaccoKycDocument
    {
        $path = null;
        if ($file) {
            $stored = $file->store('public/uploads/kyc');
            $path = str_replace('public/', '', $stored);
        }

        $doc = SaccoKycDocument::create([
            'client_id' => $clientId,
            'uploaded_by_id' => $uploadedById,
            'document_type' => $data['document_type'],
            'document_number' => $data['document_number'] ?? null,
            'file_path' => $path,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'status' => 'pending',
        ]);

        if ($data['document_type'] === 'kra_pin' && !empty($data['document_number'])) {
            Client::where('id', $clientId)->update(['kra_pin' => strtoupper($data['document_number'])]);
        }

        $this->recalculateStatus($clientId);

        return $doc;
    }

    public function verifyIprs(int $clientId, ?string $idNumber = null, ?int $verifiedById = null): array
    {
        $client = Client::with('identifications')->findOrFail($clientId);
        $idNumber = $idNumber ?: optional($client->identifications->first())->identification_value;

        if (!$idNumber) {
            return ['success' => false, 'message' => 'No national ID on file for this member.'];
        }

        $service = new NidaIntegrationService();
        $result = $service->verifyMember(
            $clientId,
            $idNumber,
            $client->first_name,
            $client->last_name,
            $verifiedById
        );

        $this->recalculateStatus($clientId);

        return $result;
    }

    public function approveDocument(int $documentId, int $reviewedById): SaccoKycDocument
    {
        $doc = SaccoKycDocument::findOrFail($documentId);
        $doc->update([
            'status' => 'approved',
            'reviewed_by_id' => $reviewedById,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);
        $this->recalculateStatus($doc->client_id);

        return $doc;
    }

    public function rejectDocument(int $documentId, int $reviewedById, string $reason): SaccoKycDocument
    {
        $doc = SaccoKycDocument::findOrFail($documentId);
        $doc->update([
            'status' => 'rejected',
            'reviewed_by_id' => $reviewedById,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
        $this->recalculateStatus($doc->client_id);

        return $doc;
    }

    public function approveKyc(int $clientId, int $verifiedById, ?string $notes = null, ?int $validDays = null): Client
    {
        $profile = $this->getMemberProfile($clientId);
        if (!$profile['can_approve']) {
            throw new \RuntimeException('Mandatory KYC requirements are not complete.');
        }

        $days = $validDays ?? (int)config('sacco.kyc_validity_days', 365);
        $client = Client::findOrFail($clientId);
        $client->kyc_verified = 1;
        $client->kyc_status = 'verified';
        $client->kyc_verified_at = now();
        $client->kyc_verified_by_id = $verifiedById;
        $client->kyc_expiry_date = now()->addDays($days)->toDateString();
        $client->kyc_notes = $notes;
        $client->save();

        return $client;
    }

    public function rejectKyc(int $clientId, int $rejectedById, string $reason): Client
    {
        $client = Client::findOrFail($clientId);
        $client->kyc_verified = 0;
        $client->kyc_status = 'rejected';
        $client->kyc_verified_at = null;
        $client->kyc_verified_by_id = null;
        $client->kyc_notes = $reason;
        $client->save();

        return $client;
    }

    public function recalculateStatus(int $clientId): string
    {
        $client = Client::findOrFail($clientId);
        $profile = $this->getMemberProfile($clientId);

        if ($client->kyc_status === 'verified') {
            if ($client->kyc_expiry_date && $client->kyc_expiry_date < date('Y-m-d')) {
                $client->kyc_status = 'expired';
                $client->kyc_verified = 0;
                $client->save();
            }

            return $client->kyc_status;
        }

        if ($client->kyc_status === 'rejected') {
            return 'rejected';
        }

        $hasPending = collect($profile['checklist'])->contains(function ($item) {
            $doc = $item['document'];
            return $doc && $doc->status === 'pending';
        });

        if ($profile['mandatory_met'] >= $profile['mandatory_total']
            && (!$profile['iprs_required'] || $profile['iprs_verified'])
            && $hasPending) {
            $client->kyc_status = 'pending_review';
        } elseif ($profile['mandatory_met'] >= $profile['mandatory_total']
            && (!$profile['iprs_required'] || $profile['iprs_verified'])) {
            $client->kyc_status = 'pending_review';
        } elseif ($profile['mandatory_met'] > 0 || $hasPending) {
            $client->kyc_status = 'pending_review';
        } else {
            $client->kyc_status = 'incomplete';
        }

        $client->kyc_verified = 0;
        $client->save();

        return $client->kyc_status;
    }

    public function resolveStatus(Client $client): string
    {
        if (!empty($client->kyc_status)) {
            if ($client->kyc_status === 'verified' && $client->kyc_expiry_date && $client->kyc_expiry_date < date('Y-m-d')) {
                return 'expired';
            }

            return $client->kyc_status;
        }

        return !empty($client->kyc_verified) ? 'verified' : 'incomplete';
    }

    public function isVerified(int $clientId): bool
    {
        $client = Client::find($clientId);
        if (!$client) {
            return false;
        }

        $status = $this->resolveStatus($client);

        return $status === 'verified';
    }

    public function canTransact(int $clientId): array
    {
        if (!config('sacco.require_kyc_for_transactions', true)) {
            return ['allowed' => true];
        }

        if ($this->isVerified($clientId)) {
            return ['allowed' => true];
        }

        $status = Client::where('id', $clientId)->value('kyc_status') ?? 'incomplete';

        return [
            'allowed' => false,
            'reason' => "Member KYC is {$status}. Complete identity verification (IPRS/NIDA) and document review before transacting.",
            'kyc_status' => $status,
        ];
    }

    public function getDashboardStats(): array
    {
        return [
            'incomplete' => (int)DB::table('clients')->where('kyc_status', 'incomplete')->orWhereNull('kyc_status')->count(),
            'pending_review' => (int)DB::table('clients')->where('kyc_status', 'pending_review')->count(),
            'verified' => (int)DB::table('clients')->where('kyc_status', 'verified')->count(),
            'rejected' => (int)DB::table('clients')->where('kyc_status', 'rejected')->count(),
            'expired' => (int)DB::table('clients')->where('kyc_status', 'expired')->count(),
            'pending_documents' => (int)SaccoKycDocument::where('status', 'pending')->count(),
        ];
    }

    protected function requirementSatisfied($req, $doc, Client $client): bool
    {
        if ($req->code === 'signature' && !empty($client->signature)) {
            return true;
        }

        if ($req->code === 'photo' && !empty($client->photo)) {
            return true;
        }

        if ($req->code === 'national_id') {
            if (!empty($client->iprs_verified_at)) {
                return true;
            }
            if ($doc && $doc->status === 'approved') {
                return true;
            }

            return false;
        }

        return $doc && $doc->status === 'approved';
    }

    protected function requirementStatus($req, $doc, Client $client, bool $satisfied): string
    {
        if ($satisfied) {
            return 'approved';
        }
        if ($req->code === 'signature' && !empty($client->signature)) {
            return 'approved';
        }
        if ($req->code === 'photo' && !empty($client->photo)) {
            return 'approved';
        }
        if ($req->code === 'national_id' && !empty($client->iprs_verified_at)) {
            return 'approved';
        }
        if ($doc) {
            return $doc->status;
        }
        if ($req->code === 'national_id' && $client->identifications->isNotEmpty()) {
            return 'on_file';
        }

        return 'missing';
    }

    protected function canApprove(Client $client, array $checklist, bool $iprsOk): bool
    {
        foreach ($checklist as $item) {
            if (!$item['requirement']->mandatory) {
                continue;
            }
            if (!$item['satisfied']) {
                return false;
            }
        }

        $iprsRequired = collect($checklist)->contains(function ($item) {
            return $item['requirement']->requires_iprs;
        });

        return !$iprsRequired || $iprsOk;
    }
}
