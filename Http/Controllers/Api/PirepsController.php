<?php

namespace Modules\SmartCARS3phpVMS7Api\Http\Controllers\Api;

use App\Contracts\Controller;
use App\Models\Enums\PirepState;
use App\Models\Acars;
use App\Models\Pirep;
use App\Models\PirepComment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\SmartCARS3phpVMS7Api\Models\PirepLog;
use Illuminate\Support\Facades\Auth;

/**
 * class ApiController
 * @package Modules\SmartCARS3phpVMS7Api\Http\Controllers\Api
 */
class PirepsController extends Controller
{
    private const SEARCH_STATUSES = ['Accepted', 'Pending', 'Rejected'];

    /**
     * Just send out a message
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function details(Request $request)
    {
        $pirep = Pirep::where(['id' => $request->get('id'), 'user_id' => Auth::id()])->first();
        if ($pirep === null) {
            return response()->json(['message' => 'PIREP not found'], 404);
        }
        $pirep->load('comments', 'acars');

        return response()->json([
            'locationData' => $pirep->acars->map(function ($a) {return ['latitude' => $a->lat, 'longitude' => $a->lon, 'heading' => $a->heading];}),
            'flightData' => array_reverse($pirep->comments->map(function ($a ) { return ['eventId' => $a->id, 'eventTimestamp' => $a->created_at, 'eventElapsedTime' => 0, 'eventCondition' => null, 'message' => $a->comment];})->toArray()),
        ]);
    }
    /**
     * Handles /search
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $user->load('pireps', 'pireps.airline', 'pireps.aircraft');
        $filters = $this->searchFilters($request);
        $output_pireps = [];
        foreach ($user->pireps->sortByDesc('created_at') as $pirep) {
            $submitDate = Carbon::createFromTimeString($pirep->submitted_at);
            if (!$this->matchesFilters($pirep, $submitDate->toDateString(), $filters)) {
                continue;
            }
            $output_pireps[] = [
                'id' => $pirep->id,
                'submitDate' => $submitDate->toDateTimeString(),
                'airlineCode' => $pirep->airline->icao,
                'route' => $pirep->route ? $pirep->route : '',
                'number' => $pirep->flight_number,
                'distance' => $pirep->planned_distance->getResponseUnits()['mi'],
                'flightType' => $pirep->flight_type,
                'departureAirport' => $pirep->dpt_airport_id,
                'arrivalAirport' => $pirep->arr_airport_id,
                'aircraft' => $pirep->aircraft_id,
                'status' => self::getStatus($pirep->state),
                'flightTime' => $pirep->flight_time / 60,
                'landingRate' => $pirep->landing_rate,
                'fuelUsed' => $pirep->fuel_used->getResponseUnits()['lbs']
            ];
        }
        return response()->json($output_pireps);
    }

    /**
     * The search filters that parse. One that is empty or does not parse is left out, so it filters
     * nothing: what smartCARS 3 sends here was never checked.
     */
    private function searchFilters(Request $request): array
    {
        $filters = [];
        foreach (['departureAirport', 'arrivalAirport', 'aircraft'] as $name) {
            $value = trim((string) $request->input($name, ''));
            if ($value !== '') {
                $filters[$name] = strtoupper($value);
            }
        }
        $status = $request->input('status');
        if (in_array($status, self::SEARCH_STATUSES, true)) {
            $filters['status'] = $status;
        }
        foreach (['startDate', 'endDate'] as $name) {
            $value = (string) $request->input($name, '');
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)
                && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
                $filters[$name] = $value;
            }
        }
        return $filters;
    }

    private function matchesFilters(Pirep $pirep, string $submitDay, array $filters): bool
    {
        return (!isset($filters['departureAirport']) || strtoupper((string) $pirep->dpt_airport_id) === $filters['departureAirport'])
            && (!isset($filters['arrivalAirport']) || strtoupper((string) $pirep->arr_airport_id) === $filters['arrivalAirport'])
            && (!isset($filters['aircraft']) || strtoupper((string) $pirep->aircraft?->icao) === $filters['aircraft'])
            && (!isset($filters['status']) || self::getStatus($pirep->state) === $filters['status'])
            && (!isset($filters['startDate']) || $submitDay >= $filters['startDate'])
            && (!isset($filters['endDate']) || $submitDay <= $filters['endDate']);
    }

    /**
     * Handles /latest
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function latest(Request $request)
    {
        $user = $user = Auth::user();
        $pirep = Pirep::where('user_id', $user->id)->orderBy('created_at', 'desc')->first();

        if (!$pirep) {
            return response()->json([]);
        }

        return response()->json([
            'id' => $pirep->id,
            'submitDate' => Carbon::createFromTimeString($pirep->submitted_at)->toDateString(),
            'airlineCode' => $pirep->airline->icao,
            'route' => $pirep->route ? $pirep->route : '',
            'number' => $pirep->flight_number,
            'distance' => $pirep->planned_distance->getResponseUnits()['mi'],
            'flightType' => $pirep->flight_type,
            'departureAirport' => $pirep->dpt_airport_id,
            'arrivalAirport' => $pirep->arr_airport_id,
            'aircraft' => $pirep->aircraft_id,
            'status' => self::getStatus($pirep->state),
            'flightTime' => $pirep->flight_time / 60,
            'landingRate' => $pirep->landing_rate,
            'fuelUsed' => $pirep->fuel_used->getResponseUnits()['lbs']
        ]);
    }

    function getStatus($value) {
        switch(intval($value)) {
            case 1:
                return 'Pending';
                break;
            case 2:
                return 'Accepted';
                break;
            case 6:
                return 'Rejected';
                break;
            default:
                return;
                break;
        }
    }
}