<?php

$file = 'school_api_postman_collection.json';
if (!file_exists($file)) {
    die("File not found");
}

$json = json_decode(file_get_contents($file), true);

$newItems = [
    [
        "name" => "Holidays",
        "item" => [
            [
                "name" => "Get Holidays",
                "request" => [
                    "method" => "GET",
                    "header" => [
                        ["key" => "Authorization", "value" => "Bearer {{token}}"]
                    ],
                    "url" => [
                        "raw" => "{{base_url}}api/staff/holidays",
                        "host" => ["{{base_url}}"],
                        "path" => ["api", "staff", "holidays"]
                    ]
                ]
            ],
            [
                "name" => "Store Holiday",
                "request" => [
                    "method" => "POST",
                    "header" => [
                        ["key" => "Authorization", "value" => "Bearer {{token}}"]
                    ],
                    "body" => [
                        "mode" => "raw",
                        "raw" => "{\n  \"date\": \"2026-08-15\",\n  \"title\": \"Independence Day\"\n}",
                        "options" => ["raw" => ["language" => "json"]]
                    ],
                    "url" => [
                        "raw" => "{{base_url}}api/staff/holidays",
                        "host" => ["{{base_url}}"],
                        "path" => ["api", "staff", "holidays"]
                    ]
                ]
            ],
            [
                "name" => "Update Holiday",
                "request" => [
                    "method" => "PUT",
                    "header" => [
                        ["key" => "Authorization", "value" => "Bearer {{token}}"]
                    ],
                    "body" => [
                        "mode" => "raw",
                        "raw" => "{\n  \"date\": \"2026-08-15\",\n  \"title\": \"Independence Day Updated\"\n}",
                        "options" => ["raw" => ["language" => "json"]]
                    ],
                    "url" => [
                        "raw" => "{{base_url}}api/staff/holidays/1",
                        "host" => ["{{base_url}}"],
                        "path" => ["api", "staff", "holidays", "1"]
                    ]
                ]
            ],
            [
                "name" => "Delete Holiday",
                "request" => [
                    "method" => "DELETE",
                    "header" => [
                        ["key" => "Authorization", "value" => "Bearer {{token}}"]
                    ],
                    "url" => [
                        "raw" => "{{base_url}}api/staff/holidays/1",
                        "host" => ["{{base_url}}"],
                        "path" => ["api", "staff", "holidays", "1"]
                    ]
                ]
            ]
        ]
    ],
    [
        "name" => "Session Years",
        "item" => [
            [
                "name" => "Get Session Years",
                "request" => [
                    "method" => "GET",
                    "header" => [
                        ["key" => "Authorization", "value" => "Bearer {{token}}"]
                    ],
                    "url" => [
                        "raw" => "{{base_url}}api/staff/session-years",
                        "host" => ["{{base_url}}"],
                        "path" => ["api", "staff", "session-years"]
                    ]
                ]
            ]
        ]
    ],
    [
        "name" => "Timetable",
        "item" => [
            [
                "name" => "Get Timetable",
                "request" => [
                    "method" => "GET",
                    "header" => [
                        ["key" => "Authorization", "value" => "Bearer {{token}}"]
                    ],
                    "url" => [
                        "raw" => "{{base_url}}api/staff/timetable?class_section_id=1",
                        "host" => ["{{base_url}}"],
                        "path" => ["api", "staff", "timetable"],
                        "query" => [
                            ["key" => "class_section_id", "value" => "1"]
                        ]
                    ]
                ]
            ],
            [
                "name" => "Store/Update Timetable",
                "request" => [
                    "method" => "POST",
                    "header" => [
                        ["key" => "Authorization", "value" => "Bearer {{token}}"]
                    ],
                    "body" => [
                        "mode" => "raw",
                        "raw" => "{\n  \"class_section_id\": 1,\n  \"timetable\": [\n    {\n      \"day\": 1,\n      \"start_time\": \"09:00:00\",\n      \"end_time\": \"10:00:00\",\n      \"subject_id\": 1,\n      \"teacher_id\": 1\n    }\n  ]\n}",
                        "options" => ["raw" => ["language" => "json"]]
                    ],
                    "url" => [
                        "raw" => "{{base_url}}api/staff/timetable",
                        "host" => ["{{base_url}}"],
                        "path" => ["api", "staff", "timetable"]
                    ]
                ]
            ]
        ]
    ]
];

// Append to the root item list
$json['item'][] = [
    "name" => "Newly Added Staff APIs",
    "item" => $newItems
];

// Re-write to file
file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Successfully updated the Postman JSON file.\n";
