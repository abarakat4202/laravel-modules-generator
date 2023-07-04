<?php

return [
    'allowed_types' => [
        'action' => 'Actions',
        'api' => 'Domain\Routes',
        'class' => 'Domain\Support',
        'command' => 'Domain\Commands',
        'controller' => 'Domain\Controllers',
        'enum' => 'Domain\Enums',
        'event' => 'Domain\Events',
        'export' => 'Domain\Exports',
        'facade' => 'Domain\Support\Facades',
        'factory' => 'Domain\Factories',
        'import' => 'Domain\Imports',
        'job' => 'Domain\Jobs',
        'responder' => 'Responders',
        'listener'  => 'Domain\Listeners',
        'mail' => 'Domain\Mails',
        'model' => 'Domain\Models',
        'notification' => 'Domain\Notifications',
        'observer' => 'Domain\Observers',
        'repository' => 'Domain\Repositories',
        'request' => 'Domain\Requests',
        'resource' => 'Domain\Resources',
        'seeder' => 'Domain\Seeders',
        'service' => 'Domain\Services',
        'trait' => 'Domain\Traits',
    ],

    'related_types' => [
        'action,responder,request,service',
        'facade,class',        
    ],

    'dont_rename' => [
        'model',
        'api',
        'web',
        'trait',
        'facade',
        'class',
        'enum',        
    ],
];