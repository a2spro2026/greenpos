<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierChangeLog;
use App\Models\SupplierDocument;
use App\Support\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SupplierService
{
    public function nextCode(int $companyId): string
    {
        $seq = Supplier::withTrashed()->forCompany($companyId)->count() + 1;

        return 'FRN-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $documents = []): Supplier
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data, $documents) {
            $supplier = Supplier::query()->create([
                ...$this->payload($data),
                'company_id' => $company->id,
                'code' => $data['code'] ?: $this->nextCode($company->id),
                'created_by' => Workspace::user()?->id,
                'updated_by' => Workspace::user()?->id,
            ]);

            foreach ($documents as $doc) {
                $this->storeDocument($supplier, $doc['file'], $doc['title'] ?? null, $doc['category'] ?? 'other');
            }

            $this->log($supplier, 'created', 'Fournisseur créé.');

            return $supplier->fresh();
        });
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update([
            ...$this->payload($data),
            'code' => $data['code'] ?: $supplier->code,
            'updated_by' => Workspace::user()?->id,
        ]);

        $this->log($supplier, 'updated', 'Fiche fournisseur mise à jour.');

        return $supplier->fresh();
    }

    public function softDelete(Supplier $supplier): void
    {
        if ($supplier->purchaseOrders()->whereNotIn('status', ['cancelled', 'received'])->exists()) {
            throw ValidationException::withMessages([
                'supplier' => 'Des commandes d’achat sont encore ouvertes pour ce fournisseur.',
            ]);
        }

        $supplier->update([
            'status' => 'inactive',
            'updated_by' => Workspace::user()?->id,
        ]);
        $supplier->delete();
        $this->log($supplier, 'deleted', 'Fournisseur archivé (suppression logique).');
    }

    public function storeDocument(Supplier $supplier, UploadedFile $file, ?string $title = null, string $category = 'other'): SupplierDocument
    {
        $path = $file->store('suppliers/'.$supplier->id, 'public');

        $document = SupplierDocument::query()->create([
            'company_id' => $supplier->company_id,
            'supplier_id' => $supplier->id,
            'uploaded_by' => Workspace::user()?->id,
            'title' => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'category' => $category,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $this->log($supplier, 'document_added', 'Document ajouté : '.$document->title, [
            'document_id' => $document->id,
        ]);

        return $document;
    }

    public function deleteDocument(SupplierDocument $document): void
    {
        $supplier = $document->supplier;
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();
        if ($supplier) {
            $this->log($supplier, 'document_removed', 'Document supprimé.');
        }
    }

    protected function payload(array $data): array
    {
        return [
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'category' => $data['category'] ?? 'general',
            'status' => $data['status'] ?? 'active',
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'website' => $data['website'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'region' => $data['region'] ?? null,
            'country' => $data['country'] ?? 'Maroc',
            'postal_code' => $data['postal_code'] ?? null,
            'currency' => $data['currency'] ?? 'MAD',
            'payment_terms' => $data['payment_terms'] ?? null,
            'delivery_delay_days' => $data['delivery_delay_days'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    public function log(Supplier $supplier, string $action, string $message, array $meta = []): void
    {
        SupplierChangeLog::query()->create([
            'company_id' => $supplier->company_id,
            'supplier_id' => $supplier->id,
            'user_id' => Workspace::user()?->id,
            'action' => $action,
            'message' => $message,
            'meta' => $meta ?: null,
        ]);
    }
}
