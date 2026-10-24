<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Device log source
    |--------------------------------------------------------------------------
    |
    | The legacy shared punch table (`all_att_log` in the legacy master
    | database). It is read only, through a connection defined in
    | config/database.php and named here. Nothing is configured by default, so
    | the preview reports the source as not configured.
    |
    */

    'device_log' => [
        'connection' => env('ATTENDANCE_DEVICE_LOG_CONNECTION'),
        'table' => env('ATTENDANCE_DEVICE_LOG_TABLE', 'all_att_log'),
        'source_name' => 'legacy all_att_log',
        'source_event_prefix' => 'all_att_log:',
        'max_rows' => (int) env('ATTENDANCE_DEVICE_LOG_MAX_ROWS', 2000),
    ],

];
