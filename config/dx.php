<?php

return [
    'broker' => env('DX_BROKER', 'pkc-4yyd6.us-east1.gcp.confluent.cloud:9092'),
    /**
     * Driver determines the message pusher used:
     *   * MessageLogger - pushes message to logs
     *   * KafkaProducer - pushes messge to configured Kafka broker & topic
     *   * DisabledPusher - does not push messages
     */
    'driver' => env('DX_ENABLE_PUSH', false)
                    ? env('DX_DRIVER', 'kafka')
                    : 'log',
    'username' => env('DX_USERNAME'),
    'password' => env('DX_PASSWORD'),
    'group' => env('DX_GROUP'),
    'push-enable' => env('DX_ENABLE_PUSH', false),
    'warn-disabled' => env('DX_WARN_DISABLED', true),
    'consume' => env('DX_CONSUME', true),
    # Deprecated <--- WHY ARE YOU MARKED AS DEPRECATED?!?!?!
    'topics' => [
        'incoming' => [
            'cspec-general' => env('DX_INCOMING_CSPEC', 'cspec-general')
        ],
        'outgoing' => [
            'gpm-general-events' => env('DX_OUTGOING_GPM_GENERAL_EVENTS', 'gpm-general-events'),
            'gpm-person-events' => env('DX_OUTGOING_GPM_PERSON_EVENTS', 'gpm-person-events'),
        ]
    ],
    'schema_versions' => [
        'gpm-general-events' => '1.1.0'
    ]

];
