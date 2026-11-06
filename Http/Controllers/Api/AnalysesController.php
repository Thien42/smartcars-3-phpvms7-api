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

    private const JSON_PARTS = [
        'landingAnalysis' => 'landing_analysis',
        'flightScore'     => 'flight_score_detail',
        'approachTrail'   => 'approach_trail',
        'takeoffAnalysis' => 'takeoff_analysis',
        'takeoffTrail'    => 'takeoff_trail',
        'debrief'         => 'debrief',
    ];

    private const JSON_FLAGS = JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    public function upload(Request $request)
    {
        // The raw body, not $request->input(): phpVMS's TrimStrings and ConvertEmptyStringsToNull
        // middleware rewrite nested strings, and decoding to arrays would turn {} into [].
        $body = json_decode($request->getContent());
        if (!is_object($body)) {
            return response()->json(['message' => 'A JSON object body is required'], 422);
        }

        $pirepId = $body->pirepID ?? null;
        $pirep = is_string($pirepId)
            ? Pirep::where(['id' => $pirepId, 'user_id' => Auth::id()])->first()
            : null;
        if ($pirep === null) {
            return response()->json(['message' => 'PIREP not found'], 404);
        }

        $formatVersion = $body->formatVersion ?? null;
        if (!is_int($formatVersion) || $formatVersion < 0) {
            return response()->json(['message' => 'formatVersion must be an integer >= 0'], 422);
        }

        $landingAnalysis = $body->landingAnalysis ?? null;
        $flightScore = $body->flightScore ?? null;

        $values = [
            'user_id'            => Auth::id(),
            'format_version'     => $formatVersion,
            'touchdown_rate_fpm' => data_get($landingAnalysis, 'touchdown.rateFpm'),
            'landing_grade'      => data_get($landingAnalysis, 'overall'),
            'flight_score'       => data_get($flightScore, 'blended'),
        ];
        foreach (self::JSON_PARTS as $key => $column) {
            $values[$column] = isset($body->{$key}) ? json_encode($body->{$key}, self::JSON_FLAGS) : null;
        }

        FlightAnalysis::updateOrCreate(['pirep_id' => $pirep->id], $values);

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
            $result = [
                'pirepID'       => $row->pirep_id,
                'formatVersion' => $row->format_version,
            ];
            foreach (self::JSON_PARTS as $key => $column) {
                $raw = $row->getRawOriginal($column);
                $result[$key] = $raw === null ? null : json_decode($raw);
            }
            return $result;
        })->values(), 200, [], self::JSON_FLAGS);
    }
}
