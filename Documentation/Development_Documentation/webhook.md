# Using the webhook

The webhook can be used by external systems to integrate with PlanZ and add
participants.

The webhook client can make an HTTP request to `/Webhook.php`.

## Authentication

Each webhook client is given a name and assigned one or more shared secrets,
called a "webhook key". It combines the key with the request data to create an
[HMAC](https://en.wikipedia.org/wiki/HMAC) that is sent in the Authorization
header.

### Configuring webhook keys
In `[db_env.php](webpages/config/db_env_sample.php)`, the `WEBHOOK_KEYS`
variable defines the client names and their keys. For example:

```php
define("WEBHOOK_KEYS", array(
    "Demo" => array(
        "QqGS&U$r1?9^@/rf$5q+t(I#"7t'TS%B}Om4^=Q/xjjE4X[x]x>_|Qi7}DjNa(8s"
    )
)
```

Here a client with the name "Demo" is assigned the key
`QqGS&U$r1?9^@/rf$5q+t(I#"7t'TS%B}Om4^=Q/xjjE4X[x]x>_|Qi7}DjNa(8s`. The key
would then also be added to the configuration in the Demo application.

A client will typically only have one key. However, if you are rotating keys it
can be beneficial to first update the config so that the client has both the old
and the new key, then update the client to use the new key, and finally remove
the old key from the config. This allows you to rotate the keys without any
downtime.

### Computing the Authorization header
The client must send an Authorization header that consists of the authorization
mechanism, the client name, and a signature for the request.

For example:

```
Authorization: PlanZ:1 Demo 400250a22286e5372617536d8da39b502726a02dd3a1f347cab43adc037febf9
```

Here the authorization mechanism is "PlanZ:1", the client name is "Demo", and
the signature is
`400250a22286e5372617536d8da39b502726a02dd3a1f347cab43adc037febf9`.

The method for generating the signature depends on the authorization mechanism.
Currently the only version supported is "PlanZ:1".

The PlanZ:1 mechanism requires the client to send an additional header
`X-PlanZ-RequestTime`. This contains the UTC time that the request was sent in
the format `YYYYMMDD'T'HHMMSSZ` (e.g. `20230216T174832Z`) The request will
only be valid if it is processed within 5 minutes either side of that time
(the window can be configured via `WEBHOOK_TIME_TOLERANCE` in
`[db_name.php](webpages/config/db_name_sample.php)`).

The signature is
`hmac(request_method + "\n" + request_uri + "\n" + x_planz_requesttime_header + "\n" + base64(request body), key)`.

`request_method` is the uppercase HTTP request method. For example, `GET` or
`POST`.

`request_uri` is the path of the request, including query parameters.
For example, if the request was to
`https://example.com:8080/Webhook.php?action=AddParticipant`,
then the `request_uri` would be
`/Webhook.php?action=AddParticipant`

`x_planz_requesttime_header` is the value of the header `X-PlanZ-RequestTime`
header that the client sent in the request.

`base64(request_body)` is the request body encoded using
[base64](https://en.wikipedia.org/wiki/Base64) with padding. If there is no
request body, this will be an empty string.

`hmac(..., key)` is the SHA-256 HMAC of the content using the client's webhook
key as the secret.

For example, if a client called `Demo` had the key `super secret` and made a GET
request to
`https://example.com:8080/Webhook.php?action=GetBadgeIdsForEmail&email=participant@example.com`
at 2023-02-16 17:48:32 UTC,
it would compute the signature as
`hmac("GET\n/Webhook.php?action=GetBadgeIdsForEmail&email=participant@example.com\n20230216T174832\n", "super secret")`
and add the header
`Authorization: PlanZ:1 Demo 4811910949a4c5ce69826c992035b85d26ed7904003cd30d318fcdfa569b2883`.

If the same client made a request to POST request to
`https://example.com:8080/Webhook.php?action=AddParticipant`
at 2023-02-16 17:48:32 UTC with the body
`{"badgeid": "M001", "email": "joebloggs@example.com", "firstname": "Joe", "lastname": "Bloggs", "badgename": "Joe Bloggs", "perm_roles": ["Program Participant"]}`
it could compute the signature as
`hmac("POST\n/Webhook.php?action=AddParticipant\n20230216T174832\neyJiYWRnZWlkIjogIk0wMDEiLCAiZW1haWwiOiAiam9lYmxvZ2dzQGV4YW1wbGUuY29tIiwgImZpcnN0bmFtZSI6ICJKb2UiLCAibGFzdG5hbWUiOiAiQmxvZ2dzIiwgImJhZGdlbmFtZSI6ICJKb2UgQmxvZ2dzIiwgInBlcm1fcm9sZXMiOiBbIlByb2dyYW0gUGFydGljaXBhbnQiXX0=", "super secret")`
and add the header
`Authorization: PlanZ:1 Demo 8c2942d9bcb9dbcca655998057dcfc5342fed8f2718e3925ba28e4b90d78b22e`.

## Actions
The webhook allows the client to perform several actions, which can be selected
using the `action` query parameter.

## GetBadgeIdsForEmail

Returns the list of badge ids that are associated with the provided email
address. This may be an empty list, a single badge id, or multiple badge ids if
the same email has been used for multiple participants.

### Example
```
GET https://example.com:8080/Webhook.php?action=GetBadgeIdsForEmail&email=participant@example.com

200 OK
{"badgeids": ["M001"]}
```

### Request

| Parameter | Description                                     | Type   | Required | Example                 |
| --------- | ----------------------------------------------- | ------ | -------- | ----------------------- |
| action    | The action to perform.                          | string | Yes      | `GetBadgeIdsForEmail`   |
| email     | The email address to look up the badge ids for. | string | Yes      | `joebloggs@example.com` |

### 200 OK

The lookup was successful. The body will contain a JSON document with the badge
ids.

```json
{
  "$schema": "https://json-schema.org/draft/2019-09/schema",
  "type": "object",
  "properties": {
    "badgeids": {
      "description": "The badge ids of the participants associated with the email. Will be empty if there are no participants associated with the email. May contain more than one badge id if there are multiple participants using the same email.",
      "type": "array",
      "items": {
        "type": "string"
      }
    }
  },
  "required": ["badgeids"]
}
```

## GetPermissionRoles

Gets the permission roles that are configured. These define what the participant
is allowed to do in the UI.

### Example

```
GET https://example.com:8080/Webhook.php?action=GetPermissionRoles

200 OK
{
  "perm_roles": [
    {
      "name": "Administrator",
      "notes": "Reconfigure reports + ???"
    },
    {
      "name": "Staff",
      "notes": "Can access staff pages"
    },
    {
      "name": "Program Participant",
      "notes": "Login as Participant"
    }
  ]
}
```

### Request

| Parameter | Description                                     | Type   | Required | Example              |
| --------- | ----------------------------------------------- | ------ | -------- | -------------------- |
| action    | The action to perform.                          | string | Yes      | `GetPermissionRoles` |


### 200 OK

The lookup was successful. The body will contain a JSON document with the
permission roles.

```json
{
  "$schema": "https://json-schema.org/draft/2019-09/schema",
  "type": "object",
  "properties": {
    "perm_roles": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "name": {
            "description": "Name of the permission role.",
            "type": "string"
          },
          "notes": {
            "description": "A human readable description of what the role enables the user to do.",
            "type": "string"
          }
        },
        "required": ["name", "notes"]
      }
    }
  },
  "required": ["perm_roles"]
}
```

## AddParticipant

Adds a participant to the database, gives them the specified roles, and sends
them a welcome email that asks them to set their password.

### Example
```
POST https://example.com:8080/Webhook.php?action=AddParticipant
{
  "badgeid": "M001",
  "email": "joebloggs@example.com",
  "firstname": "Joe",
  "lastname": "Bloggs",
  "badgename": "Joe Bloggs",
  "perm_roles": ["Program Participant"]
}

200 OK
```

### Request

| Parameter | Description                                     | Type   | Required | Example                   |
| --------- | ----------------------------------------------- | ------ | -------- | ------------------------- |
| action    | The action to perform.                          | string | Yes      | `AddParticipant`          |

The request body must be a JSON document following the schema below:

```json
{
  "$schema": "https://json-schema.org/draft/2019-09/schema",
  "type": "object",
  "properties": {
    "badgeid": {
      "description": "Badge id of the participant. Badge ids must be unique across all participants.",
      "type": "string"
    },
    "email": {
      "description": "Email address of the participant.",
      "type": "string"
    },
    "regtype": {
      "description": "Type of registration for the participant. Must be one of the registration types configured in PlanZ.",
      "type": "string"
    },
    "firstname": {
      "description": "First name of the participant.",
      "type": "string"
    },
    "lastname": {
      "description": "Last name of the participant.",
      "type": "string"
    },
    "badgename": {
      "description": "Name to be printed on the participant's badge.",
      "type": "string"
    },
    "phone": {
      "description": "Phone number of the participant.",
      "type": "string"
    },
    "postaddress1": {
      "description": "First line of the participant's postal address.",
      "type": "string"
    },
    "postaddress2": {
      "description": "Second line of the participant's postal address.",
      "type": "string"
    },
    "postcity": {
      "description": "City of the participant's postal address (or closest regional equivalent).",
      "type": "string"
    },
    "poststate": {
      "description": "State of the participant's postal address (or closest regional equivalent).",
      "type": "string"
    },
    "postzip": {
      "description": "Zip code of the participant's postal address (or closest regional equivalent).",
      "type": "string"
    },
    "postcountry": {
      "description": "Country of the participant's postal address.",
      "type": "string"
    },
    "perm_roles": {
      "description": "List of permission roles to assign to the participant. Each must be the name of a participant role configured in PlanZ.",
      "type": "array",
      "items": {
        "type": "string"
      },
      "minItems": 1
    }
  },
  "required": ["badgeid", "email"],
  "oneOf": [
    {
      "required": ["badgename"]
    },
    {
      "required": ["firstname"]
    },
    {
      "required": ["lastname"]
    }
  ]
}
```

### 200 OK

The participant was successfully added, and the welcome email sent. The body of
the response will be empty.

## Error responses
Error responses will typically have a JSON body that describes the error.
However, the error may have been generated by a proxy, so the clients shouldn't
rely on the body always existing, or always being JSON, or always conforming to
the schema below.

See [Webhook.php](webpages/Webhook.php) for a list of possible error codes.

```json
{
  "$schema": "https://json-schema.org/draft/2019-09/schema",
  "type": "object",
  "properties": {
    "code": {
      "description": "A code associated with a specific type of error.",
      "type": "string",
        "anyOf": [
          {
            "description": "This field may contain new codes in the future. Clients should not rely on the code being one of the listed values."
          },
          {
            "const": "ERR_INTERNAL",
            "description": "An unexpected error occurred on the server while processing the request. It is undetermined how much of the request was successfully processed. There may be partial data left in the database."
          },
          {
            "const": "ERR_AUTH",
            "description": "There was a problem authorizing the client. This can be because the header is missing, or the signature was computed incorrectly, or the webhook key is wrong."
          },
          {
            "const": "ERR_MISSING_REQ_PARAM",
            "description": "A required query parameter is not present."
          },
          {
            "const": "ERR_MALFORMED_BODY",
            "description": "The request body is malformed. This may be because it is not correct JSON, or a required member is missing, or one of the members is the incorrect type."
          },
          {
            "const": "ERR_UNKNOWN_ACTION",
            "description": "The action query parameter contains an unknown value."
          },
          {
            "const": "ERR_BADGEID_EXISTS",
            "description": "The badge id of the participant being added is already associated with a participant. Badge ids must be unique across all participants."
          },
          {
            "const": "ERR_ROLE_NOT_EXIST",
            "description": "One or more of the roles provided does not exist in the PlanZ configuration."
          }
       ]
    },
    "error": {
      "description": "A human readable description of the error that occurred. This is suitable to be displayed in the client's logs, but is unsuitable to be shown to users of the client.",
      "type": "string"
    },
    "instance": {
      "description": "A string that uniquely identifies this instance of the error in the logs.",
      "type": "string"
    }
  },
  "required": ["code", "error"]
}
```

### 400 Bad Request
The client provided incorrect arguments. The body will contain a JSON document
describing the error.

### 401 Unauthorized
There was a problem authorizing the client. This can be because the header is
missing, or the signature was computed incorrectly, or the webhook key is wrong.

### 500 Internal Error
An unexpected error occurred on the server while processing the request. It is
undetermined how much of the request was successfully processed. There may be
partial data left in the database.