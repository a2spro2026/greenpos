<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerChangeLog;
use App\Models\CustomerContact;
use App\Models\CustomerDocument;
use App\Support\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CustomerService
{
    public function nextCode(int $companyId): string
    {
        $seq = Customer::withTrashed()->forCompany($companyId)->count() + 1;

        return 'CLI-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $contacts = [], array $documents = []): Customer
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data, $contacts, $documents) {
            $customer = Customer::query()->create([
                ...$this->payload($data),
                'company_id' => $company->id,
                'store_id' => $data['store_id'] ?? Workspace::store()?->id,
                'code' => ($data['code'] ?? null) ?: $this->nextCode($company->id),
                'created_by' => Workspace::user()?->id,
                'updated_by' => Workspace::user()?->id,
            ]);

            $this->syncContacts($customer, $contacts);

            foreach ($documents as $doc) {
                $this->storeDocument($customer, $doc['file'], $doc['title'] ?? null, $doc['category'] ?? 'other');
            }

            $this->log($customer, 'created', 'Client créé.');

            return $customer->fresh(['contacts']);
        });
    }

    public function update(Customer $customer, array $data, array $contacts = []): Customer
    {
        return DB::transaction(function () use ($customer, $data, $contacts) {
            $customer->update([
                ...$this->payload($data),
                'code' => ($data['code'] ?? null) ?: $customer->code,
                'store_id' => $data['store_id'] ?? $customer->store_id,
                'updated_by' => Workspace::user()?->id,
            ]);

            $customer->contacts()->delete();
            $this->syncContacts($customer, $contacts);
            $this->log($customer, 'updated', 'Fiche client mise à jour.');

            return $customer->fresh(['contacts']);
        });
    }

    public function softDelete(Customer $customer): void
    {
        $customer->update([
            'status' => 'inactive',
            'updated_by' => Workspace::user()?->id,
        ]);
        $customer->delete();
        $this->log($customer, 'deleted', 'Client archivé (suppression logique).');
    }

    public function storeDocument(Customer $customer, UploadedFile $file, ?string $title = null, string $category = 'other'): CustomerDocument
    {
        $path = $file->store('customers/'.$customer->id, 'public');

        $document = CustomerDocument::query()->create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'uploaded_by' => Workspace::user()?->id,
            'title' => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'category' => $category,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $this->log($customer, 'document_added', 'Document ajouté : '.$document->title, [
            'document_id' => $document->id,
        ]);

        return $document;
    }

    public function deleteDocument(CustomerDocument $document): void
    {
        $customer = $document->customer;
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();
        if ($customer) {
            $this->log($customer, 'document_removed', 'Document supprimé.');
        }
    }

    protected function syncContacts(Customer $customer, array $contacts): void
    {
        foreach ($contacts as $row) {
            if (empty($row['name'])) {
                continue;
            }
            CustomerContact::query()->create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'name' => $row['name'],
                'role' => $row['role'] ?? null,
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'mobile' => $row['mobile'] ?? null,
                'is_primary' => (bool) ($row['is_primary'] ?? false),
                'notes' => $row['notes'] ?? null,
            ]);
        }
    }

    protected function payload(array $data): array
    {
        return [
            'type' => $data['type'] ?? 'individual',
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'category' => $data['category'] ?? 'standard',
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
            'credit_limit' => $data['credit_limit'] ?? 0,
            'tax_id' => $data['tax_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    public function log(Customer $customer, string $action, string $message, array $meta = []): void
    {
        CustomerChangeLog::query()->create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'user_id' => Workspace::user()?->id,
            'action' => $action,
            'message' => $message,
            'meta' => $meta ?: null,
        ]);
    }
}
