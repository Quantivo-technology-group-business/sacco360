<?php

namespace Modules\Sacco\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Sacco\Entities\SaccoAgent;
use Modules\Sacco\Entities\SaccoAgentKycDocument;
use Modules\Sacco\Entities\SaccoAgentKycRequirement;

class AgentKycService
{
    protected int $kycValidityDays;

    public function __construct()
    {
        $this->kycValidityDays = (int)config('sacco.agent_kyc_validity_days', 365);
    }

    public function getRequirements(): \Illuminate\Database\Eloquent\Collection
    {
        return SaccoAgentKycRequirement::active()->ordered()->get();
    }

    public function getMandatoryRequirements(): \Illuminate\Database\Eloquent\Collection
    {
        return SaccoAgentKycRequirement::active()->mandatory()->ordered()->get();
    }

    public function getAgentKycStatus(SaccoAgent $agent): array
    {
        $requirements = $this->getRequirements();
        $documents = $agent->kycDocuments()->get()->keyBy('document_type');
        
        $checklist = [];
        $mandatoryMet = 0;
        $mandatoryTotal = 0;

        foreach ($requirements as $req) {
            $doc = $documents->get($req->code);
            $status = $this->getDocumentStatus($doc);
            
            $checklist[] = [
                'requirement' => $req,
                'document' => $doc,
                'status' => $status,
                'is_mandatory' => $req->is_mandatory,
            ];

            if ($req->is_mandatory) {
                $mandatoryTotal++;
                if ($status === 'approved') {
                    $mandatoryMet++;
                }
            }
        }

        $canApprove = $mandatoryTotal > 0 && $mandatoryMet === $mandatoryTotal;
        $hasPending = $documents->where('status', 'pending')->count() > 0;

        return [
            'checklist' => $checklist,
            'mandatory_met' => $mandatoryMet,
            'mandatory_total' => $mandatoryTotal,
            'can_approve' => $canApprove,
            'has_pending' => $hasPending,
            'progress' => $mandatoryTotal > 0 ? round(($mandatoryMet / $mandatoryTotal) * 100) : 0,
        ];
    }

    protected function getDocumentStatus(?SaccoAgentKycDocument $doc): string
    {
        if (!$doc) {
            return 'missing';
        }

        if ($doc->status === 'approved' && $doc->isExpired()) {
            return 'expired';
        }

        return $doc->status;
    }

    public function uploadDocument(SaccoAgent $agent, string $documentType, ?UploadedFile $file, ?string $documentNumber = null): SaccoAgentKycDocument
    {
        $filePath = null;
        if ($file) {
            $filePath = $file->store('uploads/agent_kyc', 'public');
        }

        // Check if document already exists
        $existing = $agent->kycDocuments()->where('document_type', $documentType)->first();

        if ($existing) {
            $existing->update([
                'uploaded_by_id' => Auth::id(),
                'document_number' => $documentNumber,
                'file_path' => $filePath ?? $existing->file_path,
                'status' => 'pending',
                'rejection_reason' => null,
                'reviewed_by_id' => null,
                'reviewed_at' => null,
            ]);
            $document = $existing;
        } else {
            $document = SaccoAgentKycDocument::create([
                'sacco_agent_id' => $agent->id,
                'uploaded_by_id' => Auth::id(),
                'document_type' => $documentType,
                'document_number' => $documentNumber,
                'file_path' => $filePath,
                'status' => 'pending',
            ]);
        }

        $this->recalculateKycStatus($agent);

        return $document;
    }

    public function approveDocument(SaccoAgentKycDocument $document, ?string $notes = null): SaccoAgentKycDocument
    {
        $document->update([
            'status' => 'approved',
            'reviewed_by_id' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->recalculateKycStatus($document->agent);

        return $document;
    }

    public function rejectDocument(SaccoAgentKycDocument $document, string $reason): SaccoAgentKycDocument
    {
        $document->update([
            'status' => 'rejected',
            'reviewed_by_id' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->recalculateKycStatus($document->agent);

        return $document;
    }

    public function approveKyc(SaccoAgent $agent, ?string $notes = null): SaccoAgent
    {
        $status = $this->getAgentKycStatus($agent);

        if (!$status['can_approve']) {
            throw new \RuntimeException('Cannot approve KYC. Not all mandatory requirements are met.');
        }

        $agent->update([
            'kyc_status' => 'verified',
            'kyc_verified_at' => now(),
            'kyc_verified_by_id' => Auth::id(),
            'kyc_expiry_date' => now()->addDays($this->kycValidityDays),
            'kyc_notes' => $notes,
        ]);

        return $agent;
    }

    public function rejectKyc(SaccoAgent $agent, string $reason): SaccoAgent
    {
        $agent->update([
            'kyc_status' => 'rejected',
            'kyc_verified_at' => null,
            'kyc_verified_by_id' => null,
            'kyc_expiry_date' => null,
            'kyc_notes' => $reason,
        ]);

        return $agent;
    }

    public function recalculateKycStatus(SaccoAgent $agent): void
    {
        // Don't change verified or rejected status automatically
        if (in_array($agent->kyc_status, ['verified', 'rejected'])) {
            // Check for expiry
            if ($agent->kyc_status === 'verified' && $agent->isKycExpired()) {
                $agent->update(['kyc_status' => 'expired']);
            }
            return;
        }

        $documents = $agent->kycDocuments;
        $requirements = $this->getMandatoryRequirements();

        if ($documents->isEmpty()) {
            $agent->update(['kyc_status' => 'incomplete']);
            return;
        }

        $hasPending = $documents->where('status', 'pending')->isNotEmpty();
        $hasApproved = $documents->where('status', 'approved')->isNotEmpty();

        // Check if all mandatory docs are approved
        $allMandatoryApproved = true;
        foreach ($requirements as $req) {
            $doc = $documents->where('document_type', $req->code)->first();
            if (!$doc || $doc->status !== 'approved') {
                $allMandatoryApproved = false;
                break;
            }
        }

        if ($hasPending || $hasApproved) {
            $agent->update(['kyc_status' => 'pending_review']);
        } else {
            $agent->update(['kyc_status' => 'incomplete']);
        }
    }

    public function getDashboardStats(): array
    {
        return [
            'total_agents' => SaccoAgent::count(),
            'kyc_verified' => SaccoAgent::where('kyc_status', 'verified')->count(),
            'kyc_pending' => SaccoAgent::where('kyc_status', 'pending_review')->count(),
            'kyc_incomplete' => SaccoAgent::where('kyc_status', 'incomplete')->count(),
            'kyc_rejected' => SaccoAgent::where('kyc_status', 'rejected')->count(),
            'kyc_expired' => SaccoAgent::where('kyc_status', 'expired')->count(),
            'pending_documents' => SaccoAgentKycDocument::pending()->count(),
        ];
    }

    public function searchAgents(?string $query, ?string $kycStatus = null, int $limit = 20)
    {
        $q = SaccoAgent::with('branch');

        if ($query) {
            $q->where(function ($builder) use ($query) {
                $builder->where('agent_code', 'like', "%{$query}%")
                    ->orWhere('agent_number', 'like', "%{$query}%")
                    ->orWhere('agent_name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('id_number', 'like', "%{$query}%");
            });
        }

        if ($kycStatus) {
            $q->where('kyc_status', $kycStatus);
        }

        return $q->orderBy('agent_name')->paginate($limit);
    }

    public function generateAgentNumber(): string
    {
        do {
            $number = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        } while (SaccoAgent::where('agent_number', $number)->exists());

        return $number;
    }
}
