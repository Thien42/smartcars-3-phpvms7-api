<?php

namespace Modules\SmartCARS3phpVMS7Api\Http\Controllers\Api;

use App\Contracts\Controller;
use App\Models\Pirep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\SmartCARS3phpVMS7Api\Models\FlightAnalysis;

/**
 * class AnalysesController
 * @package Modules\SmartCARS3phpVMS7Api\Http\Controllers\Api
 */
class AnalysesController extends Controller
{
    private const SEARCH_LIMIT = 100;

    public function upload(Request $request)
    {
        $pirepId = $request->input('pirepID');
        $pirep = is_string($pirepId)
            ? Pirep::where(['id' => $pirepId, 'user_id' => Auth::id()])->first()
            : null;
        if ($pirep === null) {
            return response()->json(['message' => 'PIREP not found'], 404);
        }

        $formatVersion = $request->input('formatVersion');
        if (!is_numeric($formatVersion)) {
            return response()->json(['message' => 'formatVersion is required'], 422);
        }

        $landingAnalysis = $request->input('landingAnalysis');
        $flightScore = $request->input('flightScore');

        FlightAnalysis::updateOrCreate(
            ['pirep_id' => $pirep->id],
            [
                'user_id'             => Auth::id(),
                'format_version'      => (int) $formatVersion,
                'touchdown_rate_fpm'  => data_get($landingAnalysis, 'touchdown.rateFpm'),
                'landing_grade'       => data_get($landingAnalysis, 'overall'),
                'flight_score'        => data_get($flightScore, 'blended'),
                'landing_analysis'    => $landingAnalysis,
                'flight_score_detail' => $flightScore,
                'takeoff_analysis'    => $request->input('takeoffAnalysis'),
                'debrief'             => $request->input('debrief'),
                'approach_trail'      => $request->input('approachTrail'),
                'takeoff_trail'       => $request->input('takeoffTrail'),
            ]
        );

        return response()->json(['status' => 200]);
    }

    public function search(Request $request)
    {
        $pirepIds = $request->input('pirepIDs');
        if (!is_array($pirepIds)) {
            return response()->json(['message' => 'pirepIDs is required'], 422);
        }
        $pirepIds = array_values(array_filter($pirepIds, 'is_string'));
        if (count($pirepIds) > self::SEARCH_LIMIT) {
            return response()->json(['message' => 'At most ' . self::SEARCH_LIMIT . ' pirepIDs are allowed'], 422);
        }

        $rows = FlightAnalysis::whereIn('pirep_id', $pirepIds)
            ->where('user_id', Auth::id())
            ->get();

        return response()->json($rows->map(function ($row) {
            return [
                'pirepID'         => $row->pirep_id,
                'formatVersion'   => $row->format_version,
                'landingAnalysis' => $row->landing_analysis,
                'flightScore'     => $row->flight_score_detail,
                'approachTrail'   => $row->approach_trail,
                'takeoffAnalysis' => $row->takeoff_analysis,
                'takeoffTrail'    => $row->takeoff_trail,
                'debrief'         => $row->debrief,
            ];
        })->values());
    }
}
