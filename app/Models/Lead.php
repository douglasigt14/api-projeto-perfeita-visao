<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Indicação feita por um prospector. Ainda não é cliente: quando virar, o cliente aponta para cá (clients.lead_id).
 */
#[Fillable(['city_id', 'city_visit_id', 'name', 'phone_number'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

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
     * @return HasOne<Client, $this>
     */
    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }
}
