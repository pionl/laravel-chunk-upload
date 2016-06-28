<?php
return [

    /**
     * The storage config
     */
    "storage" => [
        /**
         * Returns the folder name of the chunks. The location is in storage/app/{folder_name}
         */
        "chunks" => "chunks",
        "disk" => "local"
    ],
    "clear" => [
        /**
         * How old chunks we should delete
         */
        "timestamp" => "-1 HOUR",
        "schedule" => [
            "enabled" => true,
            "cron" => "0 */1 * * * *" // run every hour
        ]
    ]
];