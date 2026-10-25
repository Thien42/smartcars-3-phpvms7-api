<?php

namespace Modules\SmartCARS3phpVMS7Api\Models;

use App\Contracts\Model;

/**
 * Class FlightAnalysis
 * @package Modules\SmartCARS3phpVMS7Api\Models
 */
class FlightAnalysis extends Model
{
    public $table = 'sc_flight_analyses';
    protected $fillable = [
        'pirep_id',
        'user_id',
        'format_version',
        'touchdown_rate_fpm',
        'landing_grade',
        'flight_score',
        'landing_analysis',
        'flight_score_detail',
        'takeoff_analysis',
        'debrief',
        'approach_trail',
        'takeoff_trail',
    ];

    protected $casts = [
        'landing_analysis'    => 'array',
        'flight_score_detail' => 'array',
        'takeoff_analysis'    => 'array',
        'debrief'             => 'array',
        'approach_trail'      => 'array',
        'takeoff_trail'       => 'array',
    ];

    public static $rules = [

    ];
}
