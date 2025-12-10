- **Contacts**: Unified entity for all people (leads, clients, agents).
- **Deals & Tickets**: Pipeline-managed entities for sales and support/tasks.
- **Associations**: A universal `inmo_associations` table linking any entity to any other (N-to-N).
- **Timeline**: A unified service aggregating Activities, Tasks, and Meetings.

### Schema Diagram
```mermaid
erDiagram
    User ||--o{ Contact : owns
    User ||--o{ Deal : owns
    User ||--o{ Ticket : owns
    
    Contact ||--o{ Deal : "associated via inmo_associations"
    Contact ||--o{ Ticket : "associated via inmo_associations"
    Company ||--o{ Contact : "associated via inmo_associations"
    
    Pipeline ||--o{ PipelineStage : has
    PipelineStage ||--o{ Deal : contains
    PipelineStage ||--o{ Ticket : contains
    
    Deal ||--o{ Activity : "timeline event"
    Deal ||--o{ Task : "timeline event"
    Deal ||--o{ Meeting : "timeline event"
    
    class Contact {
        string first_name
        string last_name
        string email
        string mobile
        string lifecycle_stage
    }
    
    class Deal {
        string name
        decimal amount
        date close_date
        int pipeline_id
        int stage_id
    }
    
    class Ticket {
        string subject
        string priority
        string status
        int pipeline_id
        int stage_id
    }
```

## 2. API Endpoints

### Contacts
- `GET /api/crm/contacts` - List contacts (paginated, searchable)
- `POST /api/crm/contacts` - Create a new contact
- `GET /api/crm/contacts/{id}` - Get contact details + timeline + associations

### Deals
- `GET /api/crm/deals` - List deals (filterable by pipeline)
- `POST /api/crm/deals` - Create a deal
- `GET /api/crm/deals/{id}` - Get deal details + timeline + associations

### Tickets
- `GET /api/crm/tickets` - List tickets (filterable by status)
- `POST /api/crm/tickets` - Create a ticket
- `GET /api/crm/tickets/{id}` - Get ticket details + timeline + associations

### Chat / Messaging
- `GET /api/chat/rooms` - List chat rooms (with latest message)
- `POST /api/chat/rooms` - Create a private chat (body: `participant_id`)
- `GET /api/chat/rooms/{id}/messages` - Get message history
- `POST /api/chat/rooms/{id}/messages` - Send a message (body: `content`)

> **Real-Time / Push**: Sending a message triggers the `App\Events\MessageSent` event on the `private-chat.{id}` channel. The frontend can listen to this event using Laravel Echo to update the UI instantly (Push).

## 3. Detail View Response Structure
All detail endpoints (`show` methods) return a structured response designed for a 3-column UI:

```json
{
  "data": {
    "id": 123,
    "attributes": { ... },       // Column 1: Core Info
    "timeline": [ ... ],         // Column 2: Activity Stream (sorted by date)
    "associations": {            // Column 3: Related Items
      "contacts": [ ... ],
      "companies": [ ... ],
      "deals": [ ... ]
    }
  }
}
```

## Documentation usage details.

To set up the application and generate documentation, run:
```bash
php artisan app:install
```
This command will:
1. Refresh migrations (`migrate:fresh`)
2. Run all seeders (Voyager, CRM, Real Estate, **Documentation**)
3. Create the configured Admin user (admin@admin.com / password)
4. Link storage and clear caches.
5. Generate **Swagger Documentation**.

## Documentation

The project now includes two types of documentation:

1.  **Internal Documentation / Wiki**:
    -   Accessible at: `[Admin Panel] > Documentation` (or `/admin/documentation`)
    -   Contains: Architecture, Module breakdown, Concepts, and Integration details.

2.  **API Swagger Documentation**:
    -   Accessible at: `/api/documentation`
    -   Contains: Interactive API endpoint testing and schemas.

> **Note**: For Swagger to work correctly with the local server, please add the following to your `.env` file:
> `L5_SWAGGER_CONST_HOST=http://localhost:8000/api`

## 4. Cleanup Performed
- Removed legacy models: `Lead`, `Client`, `Proposal`, `Requirement`.
- Removed legacy controllers: `LeadController`, `ClientController`, `ProposalController`, `RequirementController`.
- Cleaned up `routes/api.php`.
- Optimized database indexes for the new schema.

## 5. New Features (Phase 9 & 10)
### Geospatial Search & Caching
- **Endpoint**: `GET /api/real-estate/search?sw_lat=...&sw_lng=...&ne_lat=...&ne_lng=...&zoom=12`
- **Logic**: Filters properties within the Bounding Box.
- **Optimization**: Uses **Redis Cache** (Cache-Aside Pattern) with normalized keys (rounded coordinates + zoom) to improve performance and reduce DB load.
- **Service**: `App\Services\PropertySearchService`.

### CRM Automation (Contact Agent Flow)
- **Entry Point**: `POST /api/real-estate/{id}/contact`.
- **Flow**:
    1. **PropertyContact** record created.
    2. event `PropertyContactCreated` dispatched.
    3. Listener `StartCrmFlowFromInquiry` triggers:
        - **Contact** creation (deduplicated by email).
        - **Deal** creation in 'Sales' Pipeline.
        - **Activity (Note)** creation with the inquiry message.
        - **Associations** linked (Deal <-> Property, Deal <-> Contact, Deal <-> Activity).
        - **Chat Room** created (Private 1:1) with the initial message sent.

### CRM Cache Strategy
- **Implementation**: Cache-Aside Pattern using **Redis**.
- **Scope**:
    - **Lists**: `Deal`, `Contact`, `Ticket` lists are cached per user (filtered/paginated). Tagged `user_{id}_{entity}`.
    - **Details**: `Deal`, `Contact`, `Ticket`, `Property` details are cached globally. Tagged `crm_{entity}_{id}` or `property_{id}`.
- **Invalidation**: Automated via **Observers** (`DealObserver`, `PropertyObserver`, etc.). Updating a record invalidates its detail cache and the owner's list cache.
- **Benefits**:
    - Instant page loads for frequently accessed entities.
    - Reduced database load for complex dashboards (which aggregate data from multiple relations).

## Next Steps
- **Frontend Integration**: Update the frontend to consume these new endpoints.
- **ERD Visualization**: Use the mermaid diagram above as a reference.
- **Testing**: Run `php artisan test` to verify the Search and CRM flow.
