<?php

namespace App\Models;

use App\Enums\CityVisitStatus;
use App\Enums\LeadStatus;
use Database\Factories\CityVisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['city_id', 'title', 'visit_date', 'end_date', 'status', 'active'])]
class CityVisit extends Model
{
    /** @use HasFactory<CityVisitFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'end_date' => 'date',
            'status' => CityVisitStatus::class,
            'active' => 'boolean',
        ];
    }

    /**
     * Atendimentos que ainda aceitam indicações: ativos, agendados ou em andamento e que não terminaram.
     *
     * @param  Builder<CityVisit>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('active', true)
            ->whereIn('status', [CityVisitStatus::Scheduled, CityVisitStatus::InProgress])
            ->whereRaw('COALESCE(end_date, visit_date) >= ?', [today()->toDateString()]);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Indicações com exame marcado (agendadas).
     *
     * @return HasMany<Lead, $this>
     */
    public function scheduledLeads(): HasMany
    {
        return $this->leads()->where('status', LeadStatus::Scheduled);
    }
}
