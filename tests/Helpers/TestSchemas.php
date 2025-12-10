<?php

namespace Tests\Helpers;

class TestSchemas
{
    // Common
    public const TIMESTAMPS = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Auth & User
    public const ROLE = [
        'id' => 'integer',
        'name' => 'string',
        'display_name' => 'string',
    ];

    public const USER = [
        'id' => 'integer',
        'name' => 'string',
        'email' => 'string',
        'role' => self::ROLE,
        // 'role_id' => 'integer', // Deprecated in JSON response if role object is present
    ];

    public const USER_ANALYTICS = [
        'favorites_count' => 'integer',
        'messages_sent' => 'integer',
        'searches_saved' => 'integer',
        'last_active' => 'string' // datetime string
    ];

    // Real Estate
    public const PROPERTY_GENERAL = [
        'property_type?' => 'string', // Enum check usually done in test logic or array
        'condition?' => 'string',
        'bedrooms?' => 'integer',
        'bathrooms?' => 'integer',
    ];
    
    public const PROPERTY_DATA = [
        'general?' => self::PROPERTY_GENERAL,
        'coordinates?' => [
            'lat' => 'number',
            'lng' => 'number'
        ]
    ];

    public const PROPERTY = [
        'id' => 'integer',
        'title' => 'string',
        'price' => 'number',
        'currency?' => 'string',
        'status' => 'string', // Enum: draft, published, etc.
        'publisher_id' => 'integer',
        'publisher_type' => 'string',
        'data' => self::PROPERTY_DATA,
    ];

    public const CATEGORY = [
        'id' => 'integer',
        'name' => 'string',
        'slug' => 'string',
        'data?' => 'array'
    ];

    public const PLAN = [
        'id' => 'integer',
        'name' => 'string',
        'price' => 'number',
        'period_days' => 'integer',
        'data?' => 'array'
    ];
    
    public const BUILDING = [
        'id' => 'integer',
        'name' => 'string',
        'slug' => 'string',
        'address' => 'string',
        'floors?' => 'integer',
        'year_built?' => 'integer',
        'lat?' => 'number',
        'lng?' => 'number',
        'publisher_id' => 'integer',
        'publisher_type' => 'string',
        'data?' => 'array'
    ];

    // CRM Core
    public const CONTACT = [
        'id' => 'integer',
        'first_name' => 'string',
        'last_name' => 'string',
        'email' => 'string',
        'lead_status?' => 'string',
        'owner_id?' => 'integer',
        'data' => 'array',
    ];

    public const DEAL = [
        'id' => 'integer',
        'title' => 'string',
        'amount?' => 'number',
        'status' => 'string', // open, won, lost
        'pipeline_id' => 'integer',
        'stage_id' => 'integer',
    ];

    public const TICKET = [
        'id' => 'integer',
        'title' => 'string',
        'status?' => 'string',
        'priority?' => 'string',
        'pipeline_id' => 'integer',
        'stage_id' => 'integer',
        'owner_id' => 'integer'
    ];

    public const PIPELINE_STAGE = [
        'id' => 'integer',
        'name' => 'string',
        'position' => 'integer',
        'probability?' => 'integer',
    ];

    public const PIPELINE = [
        'id' => 'integer',
        'name' => 'string',
        'entity_type' => 'string', // deal, ticket
        'stages' => [self::PIPELINE_STAGE] // Array of stages
    ];

    // Activities
    public const ACTIVITY = [
        'id' => 'integer',
        'type' => 'string',
        'content?' => 'string',
        'created_by' => 'integer',
    ];
}
