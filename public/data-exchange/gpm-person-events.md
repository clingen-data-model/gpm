# GPM Person Events Stream
The `gpm-person-events` DataExchange topic publishes event messages about people in the GPM.  
Messages in this topic will only include be about **people** in the GPM.
For messages about group status, scope, and membership please see the gpm-general-events topic.

This topic is intended for use by other ClinGen applications that would like to monitor person information and/or keep their own records synchronized with the GPM.  The primary use case is to share data about ClinGen membership with [clinicalgenome.org](https://clinicalgenome.org).

## Messages

### Structure
Each message in this topic will have:
* `event_type`: A string defining the type of event.
* `schema_version`: A string representing the semantic version of the message.
* `person`: An object with the current data for the person.  Attributes include:
  * **id** `(uuid)`
  * **first_name** `(string)`
  * **last_name** `(string)`
  * **email** `(string)`
  * phone `(string)`
  * institution
    * id `(uuid)`
    * website_id `(integer)`
    * name `(string)` 
    * abbreviation `(string)`
    * url `(string)`
    * address `(string)`
    * country `(string)`
  * credentials `(string)`
  * biography `(string)`
  * profile_photo `(url)`
  * orcid_id `(string)`
  * hypothesis_id `(string)`
  * address
    * street1 `(string)`
    * street2 `(string)`
    * city `(string)`
    * state `(string)`
    * zip `(string)`
    * country `(string)`
  * timezone `(string)`

Required attributes are **bolded**.

### Event types
Event types in this topic include
* `created`: The person defined in the `person` attribute has been created in the GPM.
* `updated`: The person defined in the `person` attribute has been updated.
* `deleted`: The person defined in the `person` attribute has been deleted.

### Event Schema
The full JSON schema can be found at [gpm-person-events.json](gpm-person-events.json).

## Questions & Comments
Questions and comments and issues can be directed to TJ Ward or via github issues on this repository.
