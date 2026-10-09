<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Database\Factories\ProspectorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['city_id', 'name', 'birth_date', 'pix_key', 'phone_number', 'instagram_handle', 'blocked_at'])]
class Prospector extends Model
{
    /** @use HasFactory<ProspectorFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'blocked_at' => 'datetime',
        ];
    }

    /**
     * Bloqueado pela equipe: não entra no app nem envia indicações.
     */
    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Indicações feitas por este prospector.
     *
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Indicações que compareceram ao exame.
     *
     * @return HasMany<Lead, $this>
     */
    public function attendedLeads(): HasMany
    {
        return $this->leads()->where('status', LeadStatus::Attended);
    }

    /**
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
