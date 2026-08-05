<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\UserInvitation;
use App\Models\UserLog;
use App\Models\UserLoginLog;
use App\Support\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function create(array $data, array $storeIds = [], ?UploadedFile $photo = null): User
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data, $storeIds, $photo) {
            $this->assertEmailAvailable($data['email']);
            $this->assertUsernameAvailable($data['username'] ?? null);

            $first = trim($data['first_name'] ?? '');
            $last = trim($data['last_name'] ?? '');
            $name = trim($first.' '.$last) ?: ($data['name'] ?? $data['email']);

            $user = User::query()->create([
                'name' => $name,
                'first_name' => $first ?: null,
                'last_name' => $last ?: null,
                'email' => $data['email'],
                'username' => $data['username'] ?? null,
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'department' => $data['department'] ?? null,
                'hired_at' => $data['hired_at'] ?? null,
                'status' => $data['status'] ?? 'active',
                'password' => $data['password'],
            ]);

            if ($photo) {
                $user->update(['photo_path' => $photo->store('users/'.$user->id, 'public')]);
            }

            $company->users()->attach($user->id, [
                'role' => $data['role'] ?? 'sales',
                'status' => $data['status'] ?? 'active',
                'is_primary' => true,
            ]);

            $this->syncStores($user, $company, $storeIds);

            $this->log($company, $user, 'created', 'Utilisateur créé.');

            return $user->fresh(['stores', 'companies']);
        });
    }

    public function update(User $user, array $data, array $storeIds = [], ?UploadedFile $photo = null): User
    {
        $company = Workspace::company();
        $this->ensureBelongsToCompany($user, $company);

        return DB::transaction(function () use ($company, $user, $data, $storeIds, $photo) {
            $this->assertEmailAvailable($data['email'], $user->id);
            $this->assertUsernameAvailable($data['username'] ?? null, $user->id);

            $first = trim($data['first_name'] ?? '');
            $last = trim($data['last_name'] ?? '');
            $name = trim($first.' '.$last) ?: ($data['name'] ?? $user->name);

            $payload = [
                'name' => $name,
                'first_name' => $first ?: null,
                'last_name' => $last ?: null,
                'email' => $data['email'],
                'username' => $data['username'] ?? null,
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'department' => $data['department'] ?? null,
                'hired_at' => $data['hired_at'] ?? null,
                'status' => $data['status'] ?? $user->status,
            ];

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            if ($photo) {
                if ($user->photo_path) {
                    Storage::disk('public')->delete($user->photo_path);
                }
                $payload['photo_path'] = $photo->store('users/'.$user->id, 'public');
            }

            $user->update($payload);

            $company->users()->updateExistingPivot($user->id, [
                'role' => $data['role'] ?? $user->roleIn($company),
                'status' => $data['status'] ?? $user->status,
            ]);

            $this->syncStores($user, $company, $storeIds);
            $this->log($company, $user, 'updated', 'Profil utilisateur mis à jour.');

            return $user->fresh(['stores', 'companies']);
        });
    }

    public function deactivate(User $user): User
    {
        $company = Workspace::company();
        $this->ensureBelongsToCompany($user, $company);
        $this->assertNotSelf($user);

        $user->update([
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);
        $company->users()->updateExistingPivot($user->id, ['status' => 'inactive']);
        $this->log($company, $user, 'deactivated', 'Utilisateur désactivé.');

        return $user;
    }

    public function reactivate(User $user): User
    {
        $company = Workspace::company();
        $this->ensureBelongsToCompany($user, $company);

        $user->update([
            'status' => 'active',
            'deactivated_at' => null,
        ]);
        $company->users()->updateExistingPivot($user->id, ['status' => 'active']);
        $this->log($company, $user, 'reactivated', 'Utilisateur réactivé.');

        return $user;
    }

    public function delete(User $user): void
    {
        $company = Workspace::company();
        $this->ensureBelongsToCompany($user, $company);
        $this->assertNotSelf($user);

        if ($user->roleIn($company) === 'owner') {
            $owners = $company->users()->wherePivot('role', 'owner')->where('users.status', 'active')->count();
            if ($owners <= 1) {
                throw ValidationException::withMessages(['user' => 'Impossible de supprimer le dernier propriétaire.']);
            }
        }

        $this->log($company, $user, 'deleted', 'Utilisateur archivé.');
        $company->users()->detach($user->id);
        $user->stores()->detach(
            Store::query()->where('company_id', $company->id)->pluck('id')
        );
        $user->update(['status' => 'inactive', 'deactivated_at' => now()]);
        $user->delete();
    }

    public function resetPassword(User $user, string $password): User
    {
        $company = Workspace::company();
        $this->ensureBelongsToCompany($user, $company);

        $user->update(['password' => $password]);
        $this->log($company, $user, 'password_reset', 'Mot de passe réinitialisé.');

        return $user;
    }

    public function invite(array $data): UserInvitation
    {
        $company = Workspace::company();
        $email = strtolower(trim($data['email']));

        if (User::query()->forCompany($company->id)->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'Cet email appartient déjà à un utilisateur de l\'entreprise.']);
        }

        UserInvitation::query()
            ->where('company_id', $company->id)
            ->where('email', $email)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $invitation = UserInvitation::query()->create([
            'company_id' => $company->id,
            'invited_by' => Workspace::user()?->id,
            'email' => $email,
            'role' => $data['role'] ?? 'sales',
            'token' => Str::random(48),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        if ($actor = Workspace::user()) {
            $this->log($company, $actor, 'invitation_sent', 'Invitation préparée pour '.$email.'.', [
                'invitation_id' => $invitation->id,
                'email' => $email,
                'role' => $invitation->role,
            ]);
        }

        return $invitation;
    }

    public function storeDocument(User $user, UploadedFile $file, ?string $title = null, string $category = 'other'): UserDocument
    {
        $company = Workspace::company();
        $this->ensureBelongsToCompany($user, $company);

        $path = $file->store('users/'.$user->id.'/documents', 'public');

        $document = UserDocument::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'uploaded_by' => Workspace::user()?->id,
            'title' => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'category' => $category,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $this->log($company, $user, 'document_added', 'Document ajouté : '.$document->title);

        return $document;
    }

    public function deleteDocument(UserDocument $document): void
    {
        $company = Workspace::company();
        if ($document->company_id !== $company?->id) {
            abort(404);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();
    }

    public function recordLogin(User $user, ?string $ip = null, ?string $userAgent = null): void
    {
        $device = $this->parseDevice($userAgent);
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'last_login_device' => $device,
        ]);

        UserLoginLog::query()->create([
            'user_id' => $user->id,
            'company_id' => Workspace::company()?->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device' => $device,
            'logged_in_at' => now(),
        ]);
    }

    protected function syncStores(User $user, Company $company, array $storeIds): void
    {
        $validIds = Store::query()
            ->where('company_id', $company->id)
            ->whereIn('id', $storeIds)
            ->pluck('id')
            ->all();

        $otherStoreIds = $user->stores()
            ->where('company_id', '!=', $company->id)
            ->pluck('stores.id')
            ->all();

        $user->stores()->sync(array_unique(array_merge($otherStoreIds, $validIds)));
    }

    protected function assertEmailAvailable(string $email, ?int $ignoreId = null): void
    {
        $exists = User::withTrashed()
            ->where('email', $email)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['email' => 'Cet email est déjà utilisé.']);
        }
    }

    protected function assertUsernameAvailable(?string $username, ?int $ignoreId = null): void
    {
        if (! filled($username)) {
            return;
        }

        $exists = User::withTrashed()
            ->where('username', $username)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['username' => 'Ce nom d\'utilisateur est déjà pris.']);
        }
    }

    protected function ensureBelongsToCompany(User $user, ?Company $company): void
    {
        if (! $company || ! $user->companies()->where('companies.id', $company->id)->exists()) {
            abort(404);
        }
    }

    protected function assertNotSelf(User $user): void
    {
        if (Workspace::user()?->id === $user->id) {
            throw ValidationException::withMessages(['user' => 'Vous ne pouvez pas effectuer cette action sur votre propre compte.']);
        }
    }

    protected function parseDevice(?string $ua): string
    {
        if (! $ua) {
            return 'Inconnu';
        }
        if (str_contains($ua, 'Mobile') || str_contains($ua, 'Android')) {
            return 'Mobile';
        }
        if (str_contains($ua, 'iPad') || str_contains($ua, 'Tablet')) {
            return 'Tablette';
        }

        return 'Desktop';
    }

    protected function log(Company $company, User $user, string $action, string $message, ?array $meta = null): void
    {
        if (! $user->id) {
            return;
        }

        UserLog::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'actor_id' => Workspace::user()?->id,
            'action' => $action,
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}
