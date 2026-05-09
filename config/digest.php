<?php

return [
    'recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('DIGEST_RECIPIENT', ''))))),
];
