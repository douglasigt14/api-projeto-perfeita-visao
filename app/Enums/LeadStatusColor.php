<?php

namespace App\Enums;

/**
 * Cores possíveis do selo da situação. O painel e os apps traduzem cada uma para o seu estilo.
 */
enum LeadStatusColor: string
{
    case Sky = 'sky';
    case Amber = 'amber';
    case Orange = 'orange';
    case Emerald = 'emerald';
    case Green = 'green';
    case Teal = 'teal';
    case Violet = 'violet';
    case Rose = 'rose';
    case Zinc = 'zinc';
}
