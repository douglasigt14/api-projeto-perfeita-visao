<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Histórico: a indicação passou de uma situação para outra (from vazio = quando foi criada).
 */
#[Fillable(['lead_id', 'from_lead_status_id', 'to_lead_status_id', 'user_id'])]
class LeadStatusChange extends Model
{
    /**
     * @return BelongsTo<LeadStatus, $this>
     */
    public function from(): BelongsTo
    {
        return $this->belongsTo(LeadStatus::class, 'from_lead_status_id');
    }

    /**
     * @return BelongsTo<LeadStatus, $this>
     */
    public function to(): BelongsTo
    {
        return $this->belongsTo(LeadStatus::class, 'to_lead_status_id');
    }

    /**
     * Quem mudou (equipe ou o próprio parceiro, ao indicar). Vazio se o usuário foi removido.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
