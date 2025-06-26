# TODO- this needs more clarification
Right now, the GPM directly accessess the mysql database of the genetracker.
This is not optimal from the standpoint of isolation/security, and also
makes dev setup/mocking a bit more complicated. Ideally the genetracker
would have an api to abstract this away...
We are currently implementing a major architectural change to transition from direct database access between the GPM and GeneTracker (GT) Laravel applications to a more secure and scalable API-based communication. This change is being tracked under the following tickets: CGSP-755, GPM-500, and GT-70.
---
### GT-70: Set Up API Server on GeneTracker
This task involves configuring GeneTracker as an API server using Laravel Passport with the Client Credentials Grant. The implementation follows a machine-to-machine pattern.
On the GPM side, the necessary credentials (Client ID and Client Secret) are stored in the .env file and accessed via Laravel’s config helper through the config('clientapi') configuration.
---
### GPM-500: Configure GPM as API Client
This task focuses on enabling GPM to act as an API client to GeneTracker. Key components include:
- Token Management
The AccessTokenManager handles OAuth2 token retrieval and caching.
- API Services
Request logic is encapsulated under the App\Services\GtApi\ namespace, primarily within the GtApiService class.
To call the API, developers can use App\Services\GtApi\GtApiService and invoke the desired service method. Currently, this is built exclusively for GeneTracker integration, but the design allows for expansion to support additional services or APIs. Future enhancements could include modularizing services further and extending the token manager for multiple machine-to-machine integrations.
This task also involves replacing existing direct database queries from GPM to GeneTracker with equivalent API calls.
---
### CGSP-755: UI-Level Integration
This is the parent ticket that oversees the overall integration. Its current scope includes UI-level features, such as:
- Sending curated gene data after approval
- Posting lists of genes to GeneTracker
More integrations may be added as this work evolves.

### CGSP-2: look up in Gene Curation GCEP/VCEP to GT via api