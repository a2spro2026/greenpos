<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    public const GROUPS = [
        'tax' => 'Fiscalité',
        'currencies' => 'Devises',
        'languages' => 'Langues',
        'numbering' => 'Numérotation',
        'pos' => 'POS & Caisse',
        'invoicing' => 'Facturation',
        'payments' => 'Paiements',
        'notifications' => 'Notifications',
        'security' => 'Sécurité',
        'backup' => 'Sauvegarde',
        'appearance' => 'Apparence',
        'branding' => 'Branding & Personnalisation',
        'integrations' => 'Intégrations',
    ];

    protected $fillable = ['company_id', 'group', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->payload ?? [], $key, $default);
    }
}
