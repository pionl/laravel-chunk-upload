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
        "timestamp" => "-3 HOURS",
        "schedule" => [
            "enabled" => true,
            "cron" => "0 */1 * * * *" // run every hour
        ]
    ],
    "chunk" => [
        // setup for the chunk naming setup to ensure same name upload at same time
        "name" => [
            "use" => [
                "session" => true, // should the chunk name use the session id? The uploader must send cookie!,
                "browser" => false // instead of session we can use the ip and browser?
            ]
        ]
    ]
];