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
| 403 | A listener calls a `/creator/...` endpoint | `{ "success": false, "message": "This feature is for creator accounts." }` |
| 404 | Item doesn't exist or isn't live | `{ "success": false, "message": "Not found" }` |
| 409 | Withdrawing an upload that was already reviewed | `{ "success": false, "message": "Only submissions still pending review can be withdrawn." }` |
| 422 | Validation | `{ "success": false, "message": "Validation failed", "errors": { "email": ["The email has already been taken."] } }` |
| 429 | Rate limit — login/register 10/min · posting comments 20/min · creator uploads 10/min · everything else 120/min | `{ "success": false, "message": "Too many requests. Try again shortly." }` |
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
  "data": {
    "user": {
      "id": 4, "name": "Ali Khan", "email": "ali@mail.com", "account_type": "listener", "avatar": null,
      "gender": "male", "date_of_birth": "1998-04-12", "country": "Pakistan", "bio": "Music lover",
      "created_at": "2026-10-15T07:07:19+00:00"
    }
  }
}
```

**`user` object** — used everywhere a user is returned (register, login, me, profile):

| Field | Type |
|---|---|
| `id` | int |
| `name` | string |
| `email` | string |
| `account_type` | `"listener"` \| `"creator"` |
| `avatar` | image URL or `null` |
| `gender` | `"male"` \| `"female"` \| `"other"` \| `"prefer_not_to_say"` \| `null` |
| `date_of_birth` | `"YYYY-MM-DD"` or `null` |
| `country` | string or `null` |
| `bio` | string (max 500) or `null` |
| `created_at` | ISO 8601 date-time |

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

## 6. Profile

### `GET /profile` 🔒

Same as `GET /auth/me`: returns `{ "data": { "user": {...} } }`.

### `PUT /profile` 🔒

Partial update — send **only** the fields that changed. All optional.

```json
// request
{ "name": "Ali Khan", "gender": "male", "date_of_birth": "1998-04-12", "country": "Pakistan", "bio": "Music lover", "email": "ali@mail.com" }
```

Rules: `name` max 80 · `email` valid, not used by another account · `gender` one of `male`, `female`, `other`, `prefer_not_to_say` (or `null` to clear) · `date_of_birth` `YYYY-MM-DD`, in the past · `country` max 80 · `bio` max 500. Send `null` to clear a field.

```json
// 200
{
  "success": true,
  "message": "Profile updated",
  "data": {
    "user": {
      "id": 2, "name": "Ali Khan", "email": "listener@iwire.app", "account_type": "listener", "avatar": null,
      "gender": "male", "date_of_birth": "1998-04-12", "country": "Pakistan", "bio": "Music lover",
      "created_at": "2026-10-15T07:07:19+00:00"
    }
  }
}
```

### `POST /profile/avatar` 🔒 — upload display picture

`multipart/form-data` with one field: `avatar` (JPG / PNG / WebP, max 5 MB). Square images look best.
Response: `{ "message": "Profile picture updated", "data": { "user": {...} } }` — `user.avatar` is the new image URL.

### `DELETE /profile/avatar` 🔒 — remove display picture

Response: `{ "message": "Profile picture removed", "data": { "user": { ..., "avatar": null } } }`

### `PUT /profile/password` 🔒

```json
{ "current_password": "secret123", "password": "newsecret456", "password_confirmation": "newsecret456" }
```

New password min 8 and different from the current one. Wrong `current_password` → 422 on `current_password`.
Response: `{ "success": true, "message": "Password changed", "data": null }`. **Other devices are signed out; this device stays signed in.**

### `DELETE /profile` 🔒 — delete account

```json
{ "password": "secret123" }
```

Permanently deletes the account, profile picture and comments; all tokens stop working. (A creator's already-published songs/films stay in the catalogue.) Required by the App Store and Google Play.
Response: `{ "success": true, "message": "Account deleted", "data": null }` → clear the token and go to the welcome screen.

---

## 7. Search

### `GET /search?q=nova&type=all&limit=10` 🔒

| Param | |
|---|---|
| `q` | required, 2–100 characters. Matches title, genre and creator name. |
| `type` | `all` (default) · `music` · `films` · `creators` — only that list is returned |
| `limit` | 1–50 per list, default 10 |

Only live (published) content and active creators are returned.

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "query": "nova",
    "tracks": [
      { "id": 1, "title": "Midnight Transit", "artist": "Nova Reyes", "cover": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/600x600/0-0.png", "genre": "Electronic", "duration": 222 }
    ],
    "films": [
      { "id": 15, "title": "Signal Lost", "poster": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/400x600/5-0.png", "genre": "Action" }
    ],
    "creators": [
      { "id": 1, "name": "Nova Reyes", "type": "musician", "avatar": null, "uploads": 1 }
    ]
  }
}
```

Tapping a result: track → `GET /music/tracks/{id}` · film → `GET /films/{id}` · creator → `GET /creators/{id}`.

### `GET /creators/{id}` 🔒 — creator profile

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": 1,
    "name": "Nova Reyes",
    "type": "musician",
    "avatar": null,
    "uploads": 1,
    "bio": null,
    "total_plays": 3200000,
    "tracks": [
      { "id": 1, "title": "Midnight Transit", "cover": "https://iwire-digitals.ovationhealthcare.com.pk/media/art/600x600/0-0.png", "genre": "Electronic", "duration": 222 }
    ],
    "films": []
  }
}
```

`type`: `"musician"` | `"filmmaker"`. 404 if the creator doesn't exist or is suspended.

---

## 8. Comments

| Song | Film |
|---|---|
| `GET /music/tracks/{id}/comments?page=1` 🔒 | `GET /films/{id}/comments?page=1` 🔒 |
| `POST /music/tracks/{id}/comments` 🔒 | `POST /films/{id}/comments` 🔒 |

Delete your own comment: `DELETE /comments/{commentId}` 🔒 (404 if it isn't yours).

All comments appear in the admin **Moderation** page. If an admin hides a comment, it disappears from these lists.

### List — newest first, 20 per page

```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": 11,
      "body": "This bridge is unreal",
      "created_at": "2026-10-22T06:48:27+00:00",
      "time_ago": "2 minutes ago",
      "is_mine": true,
      "user": { "id": 2, "name": "Ali Khan", "avatar": null }
    },
    {
      "id": 1,
      "body": "This bridge at 2:10 is unreal. On repeat all week.",
      "created_at": "2026-10-15T07:05:12+00:00",
      "time_ago": "6 days ago",
      "is_mine": false,
      "user": { "id": null, "name": "Ayesha Khan", "avatar": null }
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 2 }
}
```

`is_mine: true` → show a delete option. `user.id` can be `null` for older comments that aren't linked to an app account.

### Post

```json
// request
{ "body": "This bridge is unreal" }          // 1–1000 characters
```

```json
// 201
{
  "success": true,
  "message": "Comment posted",
  "data": { "id": 11, "body": "This bridge is unreal", "created_at": "2026-10-22T06:48:27+00:00", "time_ago": "0 seconds ago", "is_mine": true, "user": { "id": 2, "name": "Ali Khan", "avatar": null } }
}
```

404 if the song/film isn't live (or a film id is used on the music route and vice versa).

---

## 9. Creators (creator accounts only)

All `/creator/...` endpoints return **403** for listener accounts.

### `POST /creator/uploads` 🔒 — submit a song or film

`multipart/form-data`:

| Field | Required | Notes |
|---|---|---|
| `type` | ✅ | `music` or `films` |
| `title` | ✅ | max 160 |
| `genre` | ✅ | a name from `GET /music/genres` (music) or `GET /films/genres` (films) |
| `mood` | — | music only; a name from `GET /music/moods` |
| `description` | — | max 2000 |
| `explicit` | — | `1` / `0` |
| `duration` | ✅ | **seconds** (read it from the file on the phone) |
| `file` | ✅ | music: MP3, M4A, AAC, WAV, FLAC, OGG (max 200 MB) · films: MP4, MOV, WebM, MKV |
| `cover` | ✅ | JPG / PNG / WebP, max 5 MB — square for music, 2:3 poster for films |
| `banner` | — | films only, 16:9, max 8 MB |

The submission goes into the admin **Review Queue**. It appears in the app only after an admin approves it.

```json
// 201
{
  "success": true,
  "message": "Submitted for review",
  "data": {
    "id": 35,
    "type": "music",
    "title": "First Light",
    "genre": "Electronic",
    "mood": "Chill",
    "description": "Debut single",
    "explicit": false,
    "duration": 204,
    "cover": "https://iwire-digitals.ovationhealthcare.com.pk/storage/artwork/35/xYz.jpg",
    "banner": null,
    "status": "pending",
    "reject_reasons": [],
    "reject_note": null,
    "plays": 0,
    "comments": 0,
    "submitted_at": "2026-10-22T06:50:00+00:00",
    "reviewed_at": null
  }
}
```

**Large films:** the server's upload size limit applies (set by the hosting plan). Show upload progress and handle a failed upload gracefully.

### `GET /creator/uploads?status=pending&page=1` 🔒 — my uploads

`status` optional: `pending` · `live` · `rejected` · `unpublished`. Newest first, 20 per page, same item shape as above, plus `meta`.

| `status` | Meaning for the creator |
|---|---|
| `pending` | Waiting for admin review |
| `live` | Approved — visible in the app |
| `rejected` | Show `reject_reasons` (e.g. `["Quality"]`) and `reject_note` from the admin |
| `unpublished` | Taken down by an admin |

### `GET /creator/uploads/{id}` 🔒 — one upload (same shape)

### `DELETE /creator/uploads/{id}` 🔒 — withdraw

Only while `pending`; otherwise **409**. Response: `{ "success": true, "message": "Submission withdrawn", "data": null }`.

### `GET /creator/stats` 🔒

```json
{
  "success": true,
  "message": "OK",
  "data": { "uploads": 12, "pending": 1, "live": 10, "rejected": 1, "total_plays": 284000, "total_comments": 312 }
}
```

---

## 10. Genres & moods (for pickers and browse screens)

| Endpoint | Returns |
|---|---|
| `GET /music/genres` 🔒 | `[{ "id": 1, "name": "Hip-Hop" }, ...]` |
| `GET /music/moods` 🔒 | `[{ "id": 12, "name": "Chill" }, { "id": 13, "name": "Hype" }, { "id": 14, "name": "Focus" }, { "id": 15, "name": "Late Night" }]` |
| `GET /films/genres` 🔒 | `[{ "id": 7, "name": "Drama" }, ...]` |

All three are ordered as set by the admin in **Genres & Moods**.

---

## 11. Terms & Privacy (no auth)

`GET /pages/terms` · `GET /pages/privacy`

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "slug": "terms",
    "title": "Terms & Conditions",
    "content": "Welcome to iWire.\n\nBy using the app you agree…",
    "updated_at": "2026-10-22T07:00:00+00:00"
  }
}
```

`content` is plain text — a blank line (`\n\n`) starts a new paragraph. It's edited by the admin in **Settings → Terms & Privacy**. `content` is `""` and `updated_at` is `null` until the admin saves them. Show these on the sign-up screen and in Settings.

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
| GET | `/pages/terms` · `/pages/privacy` | — |
| GET | `/profile` | 🔒 |
| PUT | `/profile` | 🔒 |
| POST | `/profile/avatar` (multipart) | 🔒 |
| DELETE | `/profile/avatar` | 🔒 |
| PUT | `/profile/password` | 🔒 |
| DELETE | `/profile` | 🔒 |
| GET | `/search?q=&type=&limit=` | 🔒 |
| GET | `/creators/{id}` | 🔒 |
| GET | `/music/home` | 🔒 |
| GET | `/music/genres` | 🔒 |
| GET | `/music/moods` | 🔒 |
| GET | `/music/tracks/{id}` | 🔒 |
| GET / POST | `/music/tracks/{id}/comments` | 🔒 |
| GET | `/films/home?tab=for_you\|new\|trending` | 🔒 |
| GET | `/films/genres` | 🔒 |
| GET | `/films/genres/{id}?page=` | 🔒 |
| GET | `/films/{id}` | 🔒 |
| GET / POST | `/films/{id}/comments` | 🔒 |
| DELETE | `/comments/{id}` | 🔒 |
| GET | `/creator/stats` | 🔒 creator |
| GET / POST | `/creator/uploads` | 🔒 creator |
| GET / DELETE | `/creator/uploads/{id}` | 🔒 creator |
| GET | `/stream/{id}?expires=&signature=` | signed link (returned as `stream_url`) |
