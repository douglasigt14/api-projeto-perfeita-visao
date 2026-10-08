<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'state'])]
class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    /**
     * @return HasMany<Prospector, $this>
     */
    public function prospectors(): HasMany
    {
        return $this->hasMany(Prospector::class);
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<CityVisit, $this>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(CityVisit::class);
    }
}
