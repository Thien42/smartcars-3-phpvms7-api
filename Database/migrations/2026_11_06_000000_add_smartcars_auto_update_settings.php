<?php

use App\Contracts\Migration;
use App\Models\Setting;
use App\Services\Installer\SeederService;
use Illuminate\Support\Facades\DB;

/**
 * Class AddSmartcarsAutoUpdateSettings
 */
class AddSmartcarsAutoUpdateSettings extends Migration
{
    private const GROUP = 'smartcars';

    private const SETTINGS = [
        'smartcars.auto_update' => [
            'name'        => 'Auto-update',
            'value'       => 'false',
            'type'        => 'boolean',
            'description' => 'Update this module from GitHub when the fork is pushed (needs the webhook secret below and the phpVMS cron)',
        ],
        'smartcars.auto_update_secret' => [
            'name'        => 'Auto-update webhook secret',
            'value'       => '',
            'type'        => 'text',
            'description' => 'The secret set on the GitHub webhook; requests without a matching signature are rejected',
        ],
        'smartcars.auto_update_commit' => [
            'name'        => 'Auto-update installed commit',
            'value'       => '',
            'type'        => 'text',
            'description' => 'Written by the updater: the commit installed by the last successful update',
        ],
        'smartcars.auto_update_status' => [
            'name'        => 'Auto-update last result',
            'value'       => '',
            'type'        => 'text',
            'description' => 'Written by the updater: time, commit and outcome of the last update attempt',
        ],
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $seeder = app(SeederService::class);
        foreach (self::SETTINGS as $key => $attrs) {
            $seeder->addSetting($key, array_merge($attrs, ['group' => self::GROUP]));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('settings')
            ->whereIn('id', array_map(fn ($key) => Setting::formatKey($key), array_keys(self::SETTINGS)))
            ->delete();
    }
}
