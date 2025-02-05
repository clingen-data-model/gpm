<?php

return [
    /**
     * The uri of the clingen DX message broker.
     */
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

    /**
     * Cedentials used to authenticate with the broker
     */
    'username' => env('DX_USERNAME'),
    'password' => env('DX_PASSWORD'),
    'group' => env('DX_GROUP'),

    /**
     * Whether pushing messages to the broker is enabled.
     */
    'push-enable' => env('DX_ENABLE_PUSH', false),

    /**
     * Whether to write warning to the logs when pushing is disabled.
     */
    'warn-disabled' => env('DX_WARN_DISABLED', true),

    /**
     * Whether to consume incoming topics.
     */
    'consume' => env('DX_CONSUME', true),

    /**
     * Topics that this application consumes (incoming) or to which it produces (outgoing)
     */
    'topics' => [
        'incoming' => [
            'cspec-general' => env('DX_INCOMING_CSPEC', 'cspec-general')
        ],
        'outgoing' => [
            'gpm-general-events' => env('DX_OUTGOING_GPM_GENERAL_EVENTS', 'gpm-general-events'),
            'gpm-person-events' => env('DX_OUTGOING_GPM_PERSON_EVENTS', 'gpm-person-events'),
        ]
    ],

    /**
     * The current schema version of messages sent to a particular topic.
     */
    'schema_versions' => [
        'gpm-general-events' => '1.9.9',
        'gpm-person-events' => '1.9.9'
    ]

];
