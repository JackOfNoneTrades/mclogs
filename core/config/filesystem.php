<?php

$config = [

    /**
     * Path to store data in
     *
     * Relative to CORE_PATH, begins and ends with /
     */
	 "path" => getenv('FS_PATH') ?: "/../storage/logs/",
];
