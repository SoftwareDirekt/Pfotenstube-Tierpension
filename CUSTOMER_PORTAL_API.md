# Kundenportal API

Diese API verbindet eine externe HTML-Homepage mit dem Laravel-Backend fuer Registrierung, Login und Reservierungen.

## Voraussetzungen

- `FRONTEND_ORIGINS` in `.env` setzen (kommagetrennte Domains).
- Migrationen ausfuehren:
  - `php artisan migrate`

## Auth-Endpunkte

### Registrierung starten

- `POST /api/customer/auth/register`
- Body:

```json
{
  "name": "Max Muster",
  "email": "max@example.com",
  "password": "GeheimesPasswort123",
  "password_confirmation": "GeheimesPasswort123",
  "phone": "+431234567",
  "privacy_accepted": true
}
```

### E-Mail-Code bestaetigen

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

Antwort enthaelt `token` (Bearer Token).

### Code erneut senden

- `POST /api/customer/auth/resend-code`

```json
{
  "email": "max@example.com"
}
```

## Geschuetzte Endpunkte

Alle Endpunkte mit Header:

`Authorization: Bearer <token>`

### Konto

- `GET /api/customer/me`
- `POST /api/customer/auth/logout`

### Hunde

- `GET /api/customer/dogs`
- `POST /api/customer/dogs`

```json
{
  "name": "Bello",
  "race": "Labrador"
}
```

### Reservierungen

- `GET /api/customer/reservations`
- `POST /api/customer/reservations`

```json
{
  "dog_id": 1,
  "checkin_date": "2026-03-15",
  "checkout_date": "2026-03-18",
  "plan_id": 2
}
```

## Sicherheitsregeln

- Register/Login/Verify/Resend sind rate-limitiert.
- Verifizierungscode wird nur gehasht gespeichert.
- CORS ist auf `FRONTEND_ORIGINS` begrenzt.
- Login gibt absichtlich allgemeine Fehlermeldungen zurueck.
