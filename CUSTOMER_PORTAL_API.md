# Kundenportal API

Diese API verbindet das Homepage-Kundenportal mit dem Laravel-Backend für Registrierung, Hundeverwaltung und Reservierungen.

## Einheitliche Antwortstruktur

Alle Endpunkte liefern eine konsistente JSON-Struktur:

```json
{
  "success": true,
  "message": "Beschreibung",
  "data": {}
}
```

Bei Fehlern (z. B. 422):

```json
{
  "success": false,
  "message": "Validierungsfehler ...",
  "errors": {
    "feldname": ["Fehlertext"]
  }
}
```

## Voraussetzungen

- `FRONTEND_ORIGINS` in `.env` setzen (kommagetrennte Domains)
- Migrationen ausführen: `php artisan migrate`

## Auth-Endpunkte

### Registrierung starten

- `POST /api/customer/auth/register`
- Pflichtfelder:
  - `name`
  - `email`
  - `password`
  - `password_confirmation`
  - `privacy_accepted`
- Optional:
  - `phone`, `street`, `city`, `zipcode`, `country`, `type`
- Defaults:
  - `type` wird ohne Eingabe auf `Stammkunde` gesetzt
  - `picture` am Customer ist `no-user-picture.gif`

```json
{
  "name": "Max Muster",
  "email": "max@example.com",
  "password": "GeheimesPasswort123",
  "password_confirmation": "GeheimesPasswort123",
  "privacy_accepted": true,
  "phone": "+431234567"
}
```

### E-Mail-Code bestätigen

- `POST /api/customer/auth/verify-email-code`

```json
{
  "email": "max@example.com",
  "code": "123456"
}
```

### Login

- `POST /api/customer/auth/login`

```json
{
  "email": "max@example.com",
  "password": "GeheimesPasswort123"
}
```

Antwort enthält `data.token` (Bearer Token).

### Code erneut senden

- `POST /api/customer/auth/resend-code`

### Logout

- `POST /api/customer/auth/logout` (auth erforderlich)

## Geschützte Endpunkte

Alle Endpunkte mit Header:
`Authorization: Bearer <token>`

### Konto

- `GET /api/customer/me`

### Hunde

- `GET /api/customer/dogs` (nur eigene Hunde)
- `POST /api/customer/dogs`

Einzelanlage:

```json
{
  "name": "Bello",
  "race": "Labrador",
  "chip_number": "123456"
}
```

Mehrfachanlage:

```json
{
  "dogs": [
    { "name": "Bello", "race": "Labrador" },
    { "name": "Luna", "gender": "Hündin" }
  ]
}
```

Plan-Defaults bei Hundeanlage:
- `day_plan` = 1. Plan-ID
- `reg_plan` = 2. Plan-ID
- bei weniger als 2 Plänen: `422`

### Reservierungen

- `GET /api/customer/reservations`
- `POST /api/customer/reservations`

Pflicht:
- `dog_id` (muss dem eingeloggten Kunden gehören)
- `checkin_date`
- `checkout_date`

Optional:
- `plan_id`

Plan-Defaultlogik, wenn `plan_id` fehlt:
- Aufenthalt 1 Tag -> 1. Plan
- Aufenthalt > 1 Tag -> 2. Plan

```json
{
  "dog_id": 1,
  "checkin_date": "2026-03-15",
  "checkout_date": "2026-03-18"
}
```

## Typische Fehlercodes

- `401`: Nicht eingeloggt / Token fehlt
- `403`: Zugriff auf fremde Daten (Ownership)
- `422`: Validierungsfehler oder Plan-/Konfliktfehler
- `429`: Rate Limit erreicht

## Sicherheitsregeln

- Register/Login/Verify/Resend und Write-Endpunkte sind rate-limitiert.
- Verifizierungscode wird gehasht gespeichert.
- CORS ist auf `FRONTEND_ORIGINS` begrenzt.
