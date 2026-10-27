<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Support\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeService
{
    public function nextCode(int $companyId): string
    {
        $seq = Employee::withTrashed()->forCompany($companyId)->count() + 1;

        return 'EMP-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $documents = []): Employee
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data, $documents) {
            $employee = Employee::query()->create([
                ...$this->payload($data),
                'company_id' => $company->id,
                'store_id' => $data['store_id'] ?? Workspace::store()?->id,
                'user_id' => $data['user_id'] ?? null,
                'code' => ($data['code'] ?? null) ?: $this->nextCode($company->id),
                'created_by' => Workspace::user()?->id,
                'updated_by' => Workspace::user()?->id,
            ]);

            if (! empty($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $this->storePhoto($employee, $data['photo']);
            }

            foreach ($documents as $doc) {
                if (! empty($doc['file']) && $doc['file'] instanceof UploadedFile) {
                    $this->storeDocument($employee, $doc['file'], $doc['title'] ?? null, $doc['category'] ?? 'other');
                }
            }

            return $employee->fresh(['store', 'user']);
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $employee->update([
                ...$this->payload($data),
                'code' => ($data['code'] ?? null) ?: $employee->code,
                'store_id' => array_key_exists('store_id', $data) ? ($data['store_id'] ?: null) : $employee->store_id,
                'user_id' => array_key_exists('user_id', $data) ? ($data['user_id'] ?: null) : $employee->user_id,
                'updated_by' => Workspace::user()?->id,
            ]);

            if (! empty($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $this->storePhoto($employee, $data['photo']);
            }

            return $employee->fresh(['store', 'user']);
        });
    }

    public function deactivate(Employee $employee): void
    {
        $employee->update([
            'status' => 'inactive',
            'updated_by' => Workspace::user()?->id,
        ]);
        $employee->delete();
    }

    public function storeDocument(Employee $employee, UploadedFile $file, ?string $title = null, string $category = 'other'): EmployeeDocument
    {
        $path = $file->store('employees/'.$employee->id.'/docs', 'public');

        return EmployeeDocument::query()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'uploaded_by' => Workspace::user()?->id,
            'title' => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'category' => $category,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    public function deleteDocument(EmployeeDocument $document): void
    {
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();
    }

    protected function storePhoto(Employee $employee, UploadedFile $file): void
    {
        if ($employee->photo_path) {
            Storage::disk('public')->delete($employee->photo_path);
        }

        $path = $file->store('employees/'.$employee->id.'/photo', 'public');
        $employee->update(['photo_path' => $path]);
    }

    protected function payload(array $data): array
    {
        return [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'job_role' => $data['job_role'] ?? 'other',
            'status' => $data['status'] ?? 'active',
            'hire_date' => $data['hire_date'] ?? null,
            'bank_iban' => $data['bank_iban'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'commission_percent' => $data['commission_percent'] ?? 0,
            'commission_fixed' => $data['commission_fixed'] ?? 0,
            'pay_type' => $data['pay_type'] ?? 'monthly',
            'base_rate' => $data['base_rate'] ?? 0,
            'overtime_multiplier' => $data['overtime_multiplier'] ?? 1.5,
            'normal_hours_per_day' => $data['normal_hours_per_day'] ?? 8,
            'rest_day' => $data['rest_day'] ?? null,
            'absence_penalty' => $data['absence_penalty'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
