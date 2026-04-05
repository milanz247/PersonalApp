<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
try {
    Illuminate\Support\Facades\Mail::raw('Test from PersonalApp', function($m) {
        $m->to('milanmadusankamms@gmail.com')->subject('Test Mail');
    });
    echo 'SUCCESS: Mail sent' . PHP_EOL;
} catch(\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
