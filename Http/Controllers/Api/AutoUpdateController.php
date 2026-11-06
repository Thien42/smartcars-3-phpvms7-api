<?php

namespace Modules\SmartCARS3phpVMS7Api\Http\Controllers\Api;

use App\Contracts\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\SmartCARS3phpVMS7Api\Jobs\AutoUpdate;
use Modules\SmartCARS3phpVMS7Api\Support\AutoUpdateSettings;

/**
 * Receives GitHub push webhooks for the fork. The request is only a nudge: the job installs the
 * current head of master, fetched itself from a fixed repository, so a caller can never pick the code.
 * @package Modules\SmartCARS3phpVMS7Api\Http\Controllers\Api
 */
class AutoUpdateController extends Controller
{
    private const REPOSITORY = 'Thien42/smartcars-3-phpvms7-api';
    private const BRANCH_REF = 'refs/heads/master';

    public function receive(Request $request)
    {
        if (!AutoUpdateSettings::isEnabled()) {
            return response()->json(['message' => 'Auto-update is disabled'], 200);
        }

        $secret = AutoUpdateSettings::secret();
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        // The raw body: GitHub signs the exact bytes it sent, which $request->input() would not reproduce.
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
        if ($secret === '' || !hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = (string) $request->header('X-GitHub-Event', '');
        if ($event === 'ping') {
            return response()->json(['message' => 'pong'], 200);
        }
        if ($event !== 'push') {
            return response()->json(['message' => 'Event ignored'], 202);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            Log::warning('smartCARS auto-update: the push payload is not JSON; set the webhook content type to application/json');
            return response()->json(['message' => 'Push ignored: the payload must be JSON'], 202);
        }
        $ref = $payload['ref'] ?? null;
        $repository = $payload['repository']['full_name'] ?? null;
        $sha = $payload['after'] ?? null;
        $isDeleted = ($payload['deleted'] ?? false) === true;
        if ($ref !== self::BRANCH_REF || $repository !== self::REPOSITORY || $isDeleted
            || !is_string($sha) || !preg_match('/^[0-9a-f]{40}$/', $sha)) {
            return response()->json(['message' => 'Push ignored'], 202);
        }

        // phpVMS ships with the sync queue: run after the response then, so GitHub's ~10 s wait is not spent on the update.
        if (config('queue.default') === 'sync') {
            AutoUpdate::dispatchAfterResponse($sha);
        } else {
            AutoUpdate::dispatch($sha);
        }

        return response()->json(['message' => 'Update queued'], 202);
    }
}
