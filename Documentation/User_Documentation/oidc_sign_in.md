# Signing in through a registration system (OpenID Connect)

PlanZ can let people sign in with their account on the convention's
registration system, instead of a PlanZ password. It uses
[OpenID Connect](https://openid.net/connect/) (OIDC), so any registration
system that acts as an OIDC provider can be used. The examples here use
[ConReg](https://www.drupal.org/project/conreg), which has the member details
and staff permissions PlanZ needs.

When someone signs in:

- PlanZ sends them to the registration system to log in, then they come back
  to PlanZ.
- The registration system tells PlanZ which memberships they have for this
  convention, and PlanZ signs them in as that participant.
- Their name, badge name, email, phone, address and membership type are
  copied into PlanZ, so the registration system stays the one place to
  update them.
- Their PlanZ staff roles come from permissions set in the registration
  system.

This page is for the site administrator setting up PlanZ.

## Contents

1. [Before you start](#before-you-start)
2. [Choose how people sign in](#choose-how-people-sign-in)
3. [Register PlanZ with the registration system](#register-planz-with-the-registration-system)
4. [Connect PlanZ to the registration system](#connect-planz-to-the-registration-system)
5. [Badge IDs](#badge-ids)
6. [Who can sign in](#who-can-sign-in)
7. [Staff roles](#staff-roles)
8. [Contact details](#contact-details)
9. [People with more than one membership](#people-with-more-than-one-membership)
10. [Moving existing PlanZ users over](#moving-existing-planz-users-over)
11. [Example: ConReg](#example-conreg)
12. [Troubleshooting](#troubleshooting)

## Before you start

- PlanZ must be served over HTTPS.
- PHP 8.0 or later, with the `curl` and `openssl` extensions. The `intl`
  extension is optional. Without it, countries are stored as two-letter codes,
  such as "IE", instead of names, such as "Ireland".
- Install the PHP libraries for sign-in with [Composer](https://getcomposer.org/),
  from the `webpages` directory, and block web access to the `vendor`
  directory it creates. See "Installing PHP libraries with Composer" in
  [the installation notes](../../Install/INSTALL.md).

  ```
  composer install --no-dev
  ```

- The PlanZ server must be able to reach the registration system's web
  address. Users' browsers go between the two sites, and PlanZ also calls the
  registration system directly to finish each sign-in.

All the settings below go in `config/db_name.php`, except the client secret,
which goes in `db_env.php`. `config/db_name_sample.php` has every setting,
with comments.

## Choose how people sign in

`LOGIN_MODE` chooses the sign-in method:

| Value | Login page shows |
|---|---|
| `"password"` | The PlanZ user ID (or email) and password form. This is the default. |
| `"oidc"` | Only the button to sign in through the registration system. Passwords can't be used, and password reset and password change are switched off. |
| `"both"` | The button, and the password form below it. |

`"both"` is useful while you move over, or to keep a password login for an
administrator in case the registration system is down. With `"oidc"`, people
signed in with a password are signed out at their next page.

The button says "Sign in with" followed by `OIDC_PROVIDER_NAME`. Most members
won't know what the registration system is called, so you can set your own
text with `OIDC_LOGIN_BUTTON_TEXT`:

```php
define("OIDC_PROVIDER_NAME", "Octocon registration");
define("OIDC_LOGIN_BUTTON_TEXT", "Sign in with your Octocon membership");
```

`OIDC_PROVIDER_NAME` is also used in messages, such as "Your Octocon
registration account has no membership for Octocon 2026", so give it a name
members will recognise.

## Register PlanZ with the registration system

In the registration system, add PlanZ as an OIDC client (often called an
application, consumer or client). Use these settings:

| Setting | Value |
|---|---|
| Client type | Confidential, with a client secret |
| Grant type | Authorization code. PlanZ doesn't need refresh tokens. |
| PKCE | S256. PlanZ always uses it. |
| Redirect URI | `https://<your PlanZ address>/oidc_callback.php`, exactly as in `OIDC_REDIRECT_URI` |
| Consent screen | Off, if the system offers it. People are signing in to the convention's own tool. |

Note the client ID and secret for the next step.

## Connect PlanZ to the registration system

```php
define("LOGIN_MODE", "oidc");
define("OIDC_ISSUER", "https://reg.example.org/");
define("OIDC_CLIENT_ID", "planz");
define("OIDC_REDIRECT_URI", "https://planz.example.org/oidc_callback.php");
define("OIDC_SCOPES", "openid profile email phone address conreg_member conreg_members conreg_entitlements");
define("OIDC_EVENT_ID", 1);
```

- `OIDC_ISSUER` must match the `issuer` in the registration system's
  discovery document exactly, including any trailing slash. You can see it at
  `<issuer>.well-known/openid-configuration`.
- `OIDC_EVENT_ID` is the registration system's ID for this convention. People
  sign in only with memberships for this event, and memberships for other
  events are ignored.
- `OIDC_SCOPES` lists what PlanZ asks for. Leave out `phone` or `address` if
  you don't want PlanZ to have them.

Put the client secret in `db_env.php`, which should be outside the web root:

```php
define("OIDC_CLIENT_SECRET", "the secret from the registration system");
```

Optional settings:

| Setting | What it does |
|---|---|
| `OIDC_LOGOUT_URL` | After logging out of PlanZ, shows a link to log out of the registration system too. Logging out of one doesn't log out of the other. |
| `OIDC_SILENT_SIGNIN` | `TRUE` signs in people who are already logged in to the registration system without them clicking the button. PlanZ tries this once per browser session, and not after someone has logged out of PlanZ. |

## Badge IDs

Every PlanZ participant has a badge ID, which is also their PlanZ user ID.
Signing in finds the participant by badge ID, so the registration system and
PlanZ must agree on it.

If the registration system sends a badge ID for each member, PlanZ uses it.
ConReg sends one, called `planz_badge_id`, when its `conreg_planz` module is
installed:

```php
define("OIDC_BADGEID_CLAIM", "planz_badge_id");
```

A member whose badge ID is empty can't sign in yet. They're told their
membership isn't ready for PlanZ.

Otherwise, PlanZ builds the badge ID from the member's number:

```php
define("OIDC_BADGEID_PREFIX", "");    // e.g. "OC" gives "OC0042"
define("OIDC_BADGEID_DIGITS", 4);     // zero-padded to this many digits
define("OIDC_BADGEID_SOURCE", "mid"); // "mid" (member ID) or "member_no" (membership number)
```

These must give the same badge IDs as anything else that creates PlanZ users,
such as `conreg_planz` or a registration import.

## Who can sign in

`OIDC_AUTO_PROVISION` chooses who can use PlanZ:

| Value | Who can sign in |
|---|---|
| `FALSE` | Only members already set up in PlanZ, for example by `conreg_planz`, an import, or staff. Anyone else is told to contact the programme team (`PROGRAM_EMAIL`). |
| `TRUE` | Any confirmed member of the event. A PlanZ participant is created the first time they sign in. |

Members without a confirmed membership for the event are always refused, with
a message saying they need one.

Everyone who signs in gets the role named in `OIDC_PARTICIPANT_ROLE`
(`"Program Participant"` by default). That role must exist in PlanZ.

## Staff roles

Staff roles come only from the registration system. In ConReg, these are
called entitlements. Map each one to the PlanZ roles it gives:

```php
define("OIDC_ENTITLEMENT_ROLES", [
    "staff" => ["Staff"],
    "admin" => ["Administrator", "Staff"],
]);
```

At every sign-in, the person's PlanZ roles are set to exactly the participant
role plus the mapped roles. Roles given in PlanZ are removed, so taking a
permission away in the registration system really takes it away. Otherwise,
someone could keep old administrator rights that the lead administrator
thinks were removed.

The exception is `OIDC_LOCAL_ROLES`. These are roles that sign-in leaves
alone, for roles managed only in PlanZ, such as a guest of honour role used
for reports and email lists:

```php
define("OIDC_LOCAL_ROLES", ["GOH"]);
```

Staff roles are only given for the person's *own* membership. When someone
uses PlanZ as another member, such as a child they registered, they only have
participant access. Per-user permissions set up in PlanZ (as opposed to
roles) aren't used for people who sign in this way.

Before you switch to `"oidc"`, make sure at least one administrator has the
right entitlement in the registration system. Otherwise nobody will be able to
reach the staff pages.

## Contact details

Each sign-in copies the member's details into the Contact Information section
of their PlanZ profile:

| PlanZ field | Comes from (ConReg) |
|---|---|
| First Name, Last Name | `first_name`, `last_name` |
| Badge Name | `badge_name` (or the member's name, if that's empty) |
| Phone Info | `phone` |
| Email Address | `email` |
| Postal Address, (line 2) | `address.street_address`, split at the first line break |
| Postal City | `address.locality` |
| Postal State | `address.region` |
| Zip Code | `address.postal_code` |
| Country | `address.country` |
| Registration Type | `member_type` |

- The registration system's values replace what PlanZ has, even when they're
  empty. If a member removes their phone number in the registration system,
  it's removed in PlanZ at their next sign-in.
- If the registration system doesn't send a field at all, for example because
  `phone` or `address` isn't in `OIDC_SCOPES`, PlanZ keeps what it has.
- Values are cut to the length of the PlanZ field.
- Two-letter country codes ("IE") are turned into English names ("Ireland").
  To use your own names, or keep the codes:

  ```php
  define("OIDC_COUNTRY_NAME_OVERRIDES", ["IE" => "Republic of Ireland"]);
  define("OIDC_COUNTRY_NAMES", FALSE); // keep the codes
  ```

- The name for publications (on the profile page) is set from the badge name
  when the participant is created. After that it's the participant's to
  change, and sign-in doesn't touch it.

For another registration system, `OIDC_MEMBER_FIELDS` changes where each field
comes from. See the comments in `db_name_sample.php`.

### Make contact details read-only in PlanZ

Changes made in PlanZ would be overwritten at the next sign-in, so make the
contact fields read-only:

```php
define("USE_REG_SYSTEM", TRUE);
```

With this setting:

- On My Profile, the contact fields are shown as text, under "Data from
  Registration System". Change the note above them so it tells members where
  to update their details: edit the custom text `registration_data` for the
  My Profile page.
- Staff can't edit names and badge names on the participant administration
  page, and phone and address aren't shown there.
- The staff menu doesn't offer to add users.
- The welcome page shows each participant's membership type.

### Membership types

Registration systems usually send a short code for the membership type, such
as `A`. To show a name instead, set:

```php
define("USE_REGTYPE_DESCRIPTION", TRUE);
```

Then add each code to the `RegTypes` table, with the name to show in
`message`. You can do this with the configuration table editor, or in SQL:

```sql
INSERT INTO RegTypes (display_order, regtype, message) VALUES
  (1, 'A', 'Adult Attending'),
  (2, 'S', 'Supporting'),
  (3, 'V', 'Virtual');
```

Add a row for every code, even if `USE_REGTYPE_DESCRIPTION` is off. My
Schedule tells participants whose type isn't in `RegTypes` that they're "not
registered". The report *Conflict Report - Unknown RegTypes* lists any codes
you've missed.

## People with more than one membership

One registration account can own several memberships, for example a parent who
registered their children. When someone has more than one membership for the
event, they choose which member to use PlanZ as after signing in. Their own
membership is selected to start with.

They can change later with the **Switch Participant** link next to Staff View
and Participant View. It only appears for people with more than one
membership. Remember that staff roles only apply to their own membership.

## Moving existing PlanZ users over

People already set up in PlanZ must have the badge ID the registration system
expects, or signing in will create a second participant for them (with
`OIDC_AUTO_PROVISION` on) or refuse them (with it off).

If the registration system sends badge IDs, check what it sends for existing
users first. ConReg's `conreg_planz` links existing PlanZ users by email and
sends their current badge IDs, so they may need no changes.

To change a participant's badge ID, update it in `Participants` and in every
table that refers to it. Take a database backup first. Changed participants
need to sign in again afterwards.

Users who signed in with a password still have one in PlanZ. With
`LOGIN_MODE` set to `"oidc"` it can't be used, so it doesn't need removing.

## Example: ConReg

### In ConReg

1. Add a consumer at **Configuration > Web services > Consumers**
   (`/admin/config/services/consumer`):
   - Client ID: `planz`
   - Secret: a long random value
   - Confidential: on
   - Grant types: Authorization Code only
   - Redirect URI: `https://planz.example.org/oidc_callback.php`
   - Automatic authorization: on
2. Open the event to connected applications, on the event's **Connected
   applications** tab (`/admin/config/conreg/events/<event>/oidc`).
3. For staff roles, enable `conreg_oidc_entitlements`. Then add entitlements
   for the PlanZ consumer at `/admin/config/conreg/entitlements`, such as
   `staff` and `admin`. Each entitlement becomes a Drupal permission: grant it
   to a Drupal role, and give that role to your staff.
4. Optionally, install `conreg_planz` so ConReg sends PlanZ badge IDs.

### In PlanZ

`config/db_name.php`:

```php
define("LOGIN_MODE", "oidc");
define("USE_REG_SYSTEM", TRUE);
define("USE_REGTYPE_DESCRIPTION", TRUE);

define("OIDC_PROVIDER_NAME", "Octocon registration");
define("OIDC_LOGIN_BUTTON_TEXT", "Sign in with your Octocon membership");
define("OIDC_ISSUER", "https://reg.example.org/");
define("OIDC_CLIENT_ID", "planz");
define("OIDC_REDIRECT_URI", "https://planz.example.org/oidc_callback.php");
define("OIDC_SCOPES", "openid profile email phone address conreg_member conreg_members conreg_entitlements");
define("OIDC_LOGOUT_URL", "https://reg.example.org/user/logout");
define("OIDC_EVENT_ID", 1);
define("OIDC_BADGEID_CLAIM", "planz_badge_id");
define("OIDC_AUTO_PROVISION", TRUE);
define("OIDC_PARTICIPANT_ROLE", "Program Participant");
define("OIDC_ENTITLEMENT_ROLES", [
    "staff" => ["Staff"],
    "admin" => ["Administrator", "Staff"],
]);
define("OIDC_LOCAL_ROLES", []);
```

`db_env.php`:

```php
define("OIDC_CLIENT_SECRET", "the secret from the ConReg consumer");
```

## Troubleshooting

PlanZ shows members a short message, and writes the details to the PHP error
log. Check the log first. Its lines start with `OIDC`.

| Message | Likely cause |
|---|---|
| Sign-in through … is not set up correctly | A required setting is missing, the Composer libraries aren't installed, or PHP is older than 8.0. The log says which. |
| Could not contact … | The PlanZ server can't reach the registration system. Check `OIDC_ISSUER`, DNS, firewalls and HTTPS certificates from the PlanZ server itself, for example with `curl <issuer>.well-known/openid-configuration`. |
| Sign-in failed. Please try again… | Usually a configuration mismatch. The log may say "discovery issuer … does not match configured issuer" (fix `OIDC_ISSUER`, including the trailing slash), "returned HTTP 401: invalid_client" (wrong client ID or secret), or "ID token audience does not include client ID" (wrong client ID). |
| Sign-in through … did not succeed | The registration system sent an error back, such as `invalid_scope` when a scope in `OIDC_SCOPES` isn't allowed. The log has the error code. |
| An error on the registration system's own page, before returning to PlanZ | Usually the redirect URI isn't registered exactly as in `OIDC_REDIRECT_URI` (including `https`, the host name and any path), or the client ID is wrong. |
| Your sign-in attempt has expired or was started in another browser | The person took more than 10 minutes, used the back button, or their browser blocked PlanZ's cookie. Trying again usually works. |
| Your … account has no membership for … | They have no confirmed membership for the event, or the event isn't open to PlanZ in the registration system. Check `OIDC_EVENT_ID`. |
| Your membership for … is not ready for PlanZ yet | The registration system sent no badge ID for their membership. In ConReg, check the event's `conreg_planz` settings. |
| You have not been set up as a participant in PlanZ yet | `OIDC_AUTO_PROVISION` is `FALSE` and there's no PlanZ participant with their badge ID. Add them, or check the badge ID settings. |
| Staff View doesn't appear for a staff member | They don't have the entitlement in the registration system, the entitlement isn't in `OIDC_ENTITLEMENT_ROLES`, or they're using PlanZ as another member. The log says if a mapped role doesn't exist in PlanZ. |
| Contact details don't update | Check `OIDC_SCOPES` includes `phone` and `address`, and that the registration system sends them. Details only update when someone signs in. |
