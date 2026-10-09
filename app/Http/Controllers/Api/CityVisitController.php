<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityVisitResource;
use App\Models\City;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CityVisitController extends Controller
{
    /**
     * Atendimentos abertos da cidade (para vincular a indicação), do mais próximo para o mais distante.
     */
    public function index(City $city): AnonymousResourceCollection
    {
        abort_unless($city->active, 404);

        return CityVisitResource::collection($city->visits()->open()->orderBy('visit_date')->orderBy('id')->get());
    }
}
