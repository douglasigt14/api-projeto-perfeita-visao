<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Indicação feita por um prospector. Ainda não é cliente: quando virar, o cliente aponta para cá (clients.lead_id).
 * Apagar só preenche deleted_at (soft delete).
 */
#[Fillable(['city_id', 'city_visit_id', 'name', 'phone_number', 'status', 'appointment_date'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'new',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'appointment_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Prospector, $this>
     */
    public function prospector(): BelongsTo
    {
        return $this->belongsTo(Prospector::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Atendimento na cidade ao qual a indicação foi vinculada.
     *
     * @return BelongsTo<CityVisit, $this>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(CityVisit::class, 'city_visit_id');
    }

    /**
     * Contatos da equipe com a pessoa indicada, do mais recente para o mais antigo.
     *
     * @return HasMany<LeadContact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(LeadContact::class)->latest()->latest('id');
    }

    /**
     * @return HasOne<Client, $this>
     */
    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }
}
