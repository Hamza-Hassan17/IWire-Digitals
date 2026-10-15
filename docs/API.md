# iWire Mobile API — v1

**Base URL:** `https://iwire-digitals.ovationhealthcare.com.pk/api/v1`

Every path below is relative to the base URL. Example: login is
`POST https://iwire-digitals.ovationhealthcare.com.pk/api/v1/auth/login`.

## Conventions

| | |
|---|---|
| Request body | JSON — send `Content-Type: application/json` |
| Recommended header | `Accept: application/json` |
| Auth (🔒 endpoints) | `Authorization: Bearer {token}` — token from register/login |
| Token lifetime | Does not expire. It is invalidated by `POST /auth/logout`, or when the account is suspended. |
| IDs | Integers |
| `duration` | Integer, **seconds** |
| Images | Absolute `https` URLs (PNG/JPG). Always present; if the team hasn't uploaded artwork yet, the server returns generated artwork. |
| Realtime / websockets | None. There are no channels or push sockets in v1; everything is plain REST. |

### Response envelope

Every response, success or error, has this shape:

```json
{ "success": true, "message": "OK", "data": { } }
```

### Errors

| Status | When | Body |
|---|---|---|
| 401 | Wrong email/password on login | `{ "success": false, "message": "Invalid credentials" }` |
| 401 | Missing / invalid / logged-out token on 🔒 endpoint | `{ "success": false, "message": "Unauthenticated" }` |
| 403 | Account suspended by an admin (login or any 🔒 call) — token is revoked | `{ "success": false, "message": "Your account has been suspended. Contact support." }` |
| 403 | `stream_url` expired or tampered with | `{ "success": false, "message": "Link expired or invalid" }` |
| 404 | Item doesn't exist or isn't live | `{ "success": false, "message": "Not found" }` |
| 422 | Validation | `{ "success": false, "message": "Validation failed", "errors": { "email": ["The email has already been taken."] } }` |
| 429 | Rate limit (auth: 10/min, other 🔒 calls: 120/min) | `{ "success": false, "message": "Too many requests. Try again shortly." }` |
| 500 | Server error | `{ "success": false, "message": "Server error" }` |

**App handling:** on **401** from any 🔒 call → clear the stored token and go to login. On **403 suspended** → show the message and go to login.

---

## 1. Auth

### `POST /auth/register`

```json
// request
{
  "name": "Ali Khan",
  "email": "ali@mail.com",
  "password": "secret123",
  "password_confirmation": "secret123",
  "account_type": "listener",          // "listener" | "creator"
  "device_name": "Pixel 8"             // optional — label for the token
}
```

Rules: `name` required (max 80) · `email` valid, unique · `password` min 8, must match `password_confirmation` · `account_type` = `listener` or `creator`.

```json
// 201 Created
{
  "success": true,
  "message": "Registered",
  "data": {
    "token": "1|48k5yuMN9gIEjgFaICKxhq1AdH7trKjZZJy3bxRO14f8ac0d",
    "user": { "id": 4, "name": "Ali Khan", "email": "ali@mail.com", "account_type": "listener", "avatar": null }
  }
}
```

### `POST /auth/login`

```json
// request
{ "email": "ali@mail.com", "password": "secret123", "device_name": "Pixel 8" }   // device_name optional
```

Response **200**: same shape as register, with `"message": "Logged in"`.
Errors: `401 Invalid credentials`, `403` suspended, `422` validation.

### `POST /auth/logout` 🔒

No body. Revokes the token used for the call.

```json
{ "success": true, "message": "Logged out", "data": null }
```

### `GET /auth/me` 🔒

Call this on launch to check whether the stored token is still valid (200 = valid, 401 = log out).

```json
{
  "success": true,
  "message": "OK",
  "data": { "user": { "id": 4, "name": "Ali Khan", "email": "ali@mail.com", "account_type": "listener", "avatar": null } }
}
```

`user` object: `id` int · `name` string · `email` string · `account_type` `"listener" | "creator"` · `avatar` string URL or `null`.

---

## 2. App config

### `GET /app/config` (no auth)

Call on the splash screen.

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "maintenance_mode": false,
    "maintenance_message": null,
    "min_app_version": "1.0.0",
    "splash": {
      "music": { "title": "Slide Down For Music", "image": "https://iwire-digitals.ovationhealthcare.com.pk/images/studio-music.png" },
      "films": { "title": "Slide Up For Films",  "image": "https://iwire-digitals.ovationhealthcare.com.pk/images/studio-films.png" }
    },
    "support_email": "support@iwire.app"
  }
}
```

- `maintenance_mode: true` → show `maintenance_message` and block the app.
- If the installed version is lower than `min_app_version` (semver, e.g. `1.2.0`) → force update.
- All of these values are editable by the admin in **Control Room → Settings → Mobile app**.

---

## 3. Music

### `GET /music/home` 🔒

Sections and their order are set by the admin in **Home Curation → Music home**. Render them in the order received. `key` is the section title in snake_case; new sections can appear, so don't hard-code the list.

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "sections": [
      {
        "key": "find_your_mood",
        "title": "Find Your Mood",
        "items": [
          {
            "id": 1,
            "title": "Midnight Transit",
            "subtitle": "Late Night electronic from Nova Reyes.",
            "cover": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/600x600/0-0.png",
            "artist": "Nova Reyes"
          }
        ]
      },
      {
        "key": "top_picks",
        "title": "Top Picks",
        "items": [
          {
            "id": 2,
            "title": "Gold Teeth Sermon",
            "subtitle": "Hype hip-hop from Kairo Banks.",
            "cover": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/600x600/1-1.png",
            "artist": "Kairo Banks"
          }
        ]
      }
    ]
  }
}
```

Item: `id` int · `title` · `subtitle` (max ~80 chars) · `cover` square image URL · `artist`.

### `GET /music/tracks/{id}` 🔒

Used by the player. 404 if the id isn't a live track.

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 1,
    "title": "Midnight Transit",
    "description": "Late Night electronic from Nova Reyes.",
    "artist": "Nova Reyes",
    "cover": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/600x600/0-0.png",
    "genre": "Electronic",
    "mood": "Late Night",
    "explicit": false,
    "duration": 222,
    "stream_url": "https://iwire-digitals.ovationhealthcare.com.pk/api/v1/stream/1?expires=1792060000&signature=3f1c…"
  }
}
```

`mood` may be `null`. `explicit` → show an "E" badge.

---

## 4. Films

### `GET /films/home?tab=for_you` 🔒

`tab`: `for_you` (default) · `new` · `trending`. Any other value → 422.

| tab | `sections` |
|---|---|
| `for_you` | Curated rows set by the admin in **Home Curation → Films home** (e.g. `trending_now`, `new`) |
| `new` | One section, `{ "key": "new_releases", "title": "New Releases" }`, newest first (max 20) |
| `trending` | One section, `{ "key": "trending", "title": "Trending" }`, most played first (max 20) |

`banners` (the hero carousel) is the same on every tab and is set by the admin.

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "banners": [
      {
        "id": 13,
        "title": "Ember of the North",
        "subtitle": "The last fire-keeper heads north · Epic Fantasy",
        "image": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/1280x720/3-2.png"
      }
    ],
    "sections": [
      {
        "key": "trending_now",
        "title": "Trending Now",
        "items": [
          { "id": 15, "title": "Signal Lost", "poster": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/400x600/5-0.png", "genre": "Action" }
        ]
      },
      {
        "key": "new",
        "title": "New",
        "items": [
          { "id": 18, "title": "Hollow Crown", "poster": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/400x600/8-2.png", "genre": "Fantasy" }
        ]
      }
    ]
  }
}
```

Banner `id` is the **film id**, so tapping it opens `GET /films/{id}`. `image` is 16:9. `poster` is 2:3.

### `GET /films/genres` 🔒

For the Genre tab. Ordered as set by the admin.

```json
{
  "success": true,
  "message": "OK",
  "data": [
    { "id": 7, "name": "Drama" },
    { "id": 8, "name": "Action" },
    { "id": 9, "name": "Documentary" },
    { "id": 10, "name": "Fantasy" },
    { "id": 11, "name": "Thriller" }
  ]
}
```

### `GET /films/genres/{id}?page=1` 🔒

Films in a genre, 20 per page, most played first. `message` is the genre name. Keep requesting `page + 1` while `current_page < last_page`.

```json
{
  "success": true,
  "message": "Drama",
  "data": [
    { "id": 14, "title": "The Last Lighthouse", "poster": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/400x600/4-4.png", "genre": "Drama" }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 2 }
}
```

### `GET /films/{id}` 🔒

404 if the id isn't a live film.

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 13,
    "title": "Ember of the North",
    "description": "A fantasy feature directed by Ilse Varga.",
    "poster": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/400x600/3-2.png",
    "banner": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/1280x720/3-2.png",
    "genre": "Fantasy",
    "duration": 6720,
    "creator": "Ilse Varga",
    "explicit": false,
    "stream_url": "https://iwire-digitals.ovationhealthcare.com.pk/api/v1/stream/13?expires=1792060000&signature=9ab2…"
  }
}
```

---

## 5. Streaming (`stream_url`)

- `stream_url` is a **signed link valid for 6 hours**. It needs **no** `Authorization` header, so hand it straight to the audio/video player (ExoPlayer / AVPlayer / just_audio / video_player).
- It supports HTTP **Range** requests, so seeking works.
- Don't cache the URL long-term. Fetch the track/film again when you need a new link (e.g. after the app was in the background for hours, or on a `403 Link expired or invalid`).
- **`stream_url` is `null` when no media file has been uploaded for that item yet.** Show a "Not available yet" state rather than crashing. Admins upload files in **Library → item → Media & artwork**.

---

## Demo accounts

| Role | Email | Password |
|---|---|---|
| Listener | `listener@iwire.app` | `listener123` |
| Creator | `creator@iwire.app` | `creator123` |

## Endpoint summary

| Method | Path | Auth |
|---|---|---|
| POST | `/auth/register` | — |
| POST | `/auth/login` | — |
| POST | `/auth/logout` | 🔒 |
| GET | `/auth/me` | 🔒 |
| GET | `/app/config` | — |
| GET | `/music/home` | 🔒 |
| GET | `/music/tracks/{id}` | 🔒 |
| GET | `/films/home?tab=for_you\|new\|trending` | 🔒 |
| GET | `/films/genres` | 🔒 |
| GET | `/films/genres/{id}?page=` | 🔒 |
| GET | `/films/{id}` | 🔒 |
| GET | `/stream/{id}?expires=&signature=` | signed link (returned as `stream_url`) |
