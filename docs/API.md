# iWire Mobile API v1

**Base URL:** `https://iwire-digitals.ovationhealthcare.com.pk/api/v1`

## Headers

- All requests: `Accept: application/json`
- Requests with a JSON body: `Content-Type: application/json`
- File uploads (avatar, creator uploads): `Content-Type: multipart/form-data`
- 🔒 endpoints: `Authorization: Bearer {token}`

Every response uses this envelope: `{ "success": true|false, "message": "...", "data": ... }`

## Notes

- `duration` is in **seconds**.
- All image fields are full URLs and are never null, except `avatar`, which is `null` until the user uploads a picture.
- Dates are ISO 8601 (`2026-10-22T06:48:27+00:00`); `date_of_birth` is `YYYY-MM-DD`.
- The token doesn't expire. It's invalidated by logout, by changing the password (other devices), by deleting the account, or when an admin suspends the account.
- Paged lists return `meta`: `{ "current_page": 1, "last_page": 3, "per_page": 20, "total": 47 }`. Load the next page while `current_page < last_page`.
- There are no websockets or realtime channels; it's all plain REST.

## Test accounts

- Listener: `listener@iwire.app` / `listener123`
- Creator: `creator@iwire.app` / `creator123`

## Endpoints

| # | Method | Path | Auth |
|---|---|---|---|
| | **Account** | | |
| 1 | POST | `/auth/register` | — |
| 2 | POST | `/auth/login` | — |
| 3 | POST | `/auth/logout` | 🔒 |
| 4 | GET | `/auth/me` | 🔒 |
| | **App** | | |
| 5 | GET | `/app/config` | — |
| 6 | GET | `/pages/terms` | — |
| 7 | GET | `/pages/privacy` | — |
| | **Profile** | | |
| 8 | GET | `/profile` | 🔒 |
| 9 | PUT | `/profile` | 🔒 |
| 10 | POST | `/profile/avatar` | 🔒 |
| 11 | DELETE | `/profile/avatar` | 🔒 |
| 12 | PUT | `/profile/password` | 🔒 |
| 13 | DELETE | `/profile` | 🔒 |
| | **Music** | | |
| 14 | GET | `/music/home` | 🔒 |
| 15 | GET | `/music/genres` | 🔒 |
| 16 | GET | `/music/moods` | 🔒 |
| 17 | GET | `/music/tracks/{id}` | 🔒 |
| | **Films** | | |
| 18 | GET | `/films/home?tab=for_you` | 🔒 |
| 19 | GET | `/films/genres` | 🔒 |
| 20 | GET | `/films/genres/{id}?page=1` | 🔒 |
| 21 | GET | `/films/{id}` | 🔒 |
| | **Search** | | |
| 22 | GET | `/search?q=nova` | 🔒 |
| 23 | GET | `/creators/{id}` | 🔒 |
| | **Comments** | | |
| 24 | GET | `/music/tracks/{id}/comments?page=1` · `/films/{id}/comments?page=1` | 🔒 |
| 25 | POST | `/music/tracks/{id}/comments` · `/films/{id}/comments` | 🔒 |
| 26 | DELETE | `/comments/{id}` | 🔒 |
| | **Share** | | |
| 27 | POST | `/music/tracks/{id}/share` · `/films/{id}/share` | 🔒 |
| | **Creator** (creator accounts only) | | |
| 28 | POST | `/creator/uploads` | 🔒 |
| 29 | GET | `/creator/uploads?status=pending&page=1` | 🔒 |
| 30 | GET | `/creator/uploads/{id}` | 🔒 |
| 31 | DELETE | `/creator/uploads/{id}` | 🔒 |
| 32 | GET | `/creator/stats` | 🔒 |
| | **Playback** | | |
| 33 | GET | `stream_url` (signed link from 17 / 21) | — |

---

## 1. Register: `POST /auth/register`

**Request**

```json
{
  "name": "Ali Khan",
  "email": "ali@mail.com",
  "password": "secret123",
  "password_confirmation": "secret123",
  "account_type": "listener",
  "device_name": "Pixel 8"
}
```

- `account_type`: `"listener"` or `"creator"`.
- `password`: at least 8 characters.
- `device_name`: optional.

**Response 201**

```json
{
  "success": true,
  "message": "Registered",
  "data": {
    "token": "1|48k5yuMN9gIEjgFaICKxhq1AdH7trKjZZJy3bxRO14f8ac0d",
    "user": {
      "id": 4, "name": "Ali Khan", "email": "ali@mail.com", "account_type": "listener", "avatar": null,
      "gender": null, "date_of_birth": null, "country": null, "bio": null,
      "created_at": "2026-10-22T06:40:00+00:00"
    }
  }
}
```

**The `user` object** (same everywhere a user is returned):

| Field | Type |
|---|---|
| `id` | int |
| `name` | string |
| `email` | string |
| `account_type` | `"listener"` or `"creator"` |
| `avatar` | image URL or `null` |
| `gender` | `"male"`, `"female"`, `"other"`, `"prefer_not_to_say"` or `null` |
| `date_of_birth` | `"YYYY-MM-DD"` or `null` |
| `country` | string or `null` |
| `bio` | string or `null` |
| `created_at` | date-time |

## 2. Login: `POST /auth/login`

**Request**

```json
{ "email": "ali@mail.com", "password": "secret123", "device_name": "Pixel 8" }
```

`device_name` is optional.

**Response 200:** same shape as register, with `"message": "Logged in"`.

## 3. Logout: `POST /auth/logout` 🔒

No body. Revokes the current token.

```json
{ "success": true, "message": "Logged out", "data": null }
```

## 4. Current user: `GET /auth/me` 🔒

Call on app launch. **200** means the token is valid; **401** means log the user out.

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

## 5. App config: `GET /app/config` (no auth)

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
      "films": { "title": "Slide Up For Films", "image": "https://iwire-digitals.ovationhealthcare.com.pk/images/studio-films.png" }
    },
    "support_email": "support@iwire.app",
    "android_store_url": null
  }
}
```

- If `maintenance_mode` is true, show `maintenance_message` and block the app.
- If the installed version is lower than `min_app_version`, force an update.

## 6. Terms & Conditions: `GET /pages/terms` (no auth)

## 7. Privacy Policy: `GET /pages/privacy` (no auth)

Show on the sign-up screen and in Settings.

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "slug": "terms",
    "title": "Terms & Conditions",
    "content": "Welcome to iWire.\n\nBy using the app you agree to…",
    "updated_at": "2026-10-22T07:00:00+00:00"
  }
}
```

- `content` is plain text. A blank line (`\n\n`) starts a new paragraph.
- Until the admin saves the text, `content` is `""` and `updated_at` is `null`.
- `/pages/privacy` returns the same shape, with `"slug": "privacy"` and `"title": "Privacy Policy"`.

## 8. My profile: `GET /profile` 🔒

Same response as `GET /auth/me`.

## 9. Update profile: `PUT /profile` 🔒

Send only the fields that changed. Send `null` to clear a field.

**Request**

```json
{
  "name": "Ali Khan",
  "gender": "male",
  "date_of_birth": "1998-04-12",
  "country": "Pakistan",
  "bio": "Music lover",
  "email": "ali@mail.com"
}
```

- `name`: max 80 characters.
- `email`: must not belong to another account.
- `gender`: `male`, `female`, `other` or `prefer_not_to_say`.
- `date_of_birth`: `YYYY-MM-DD`, in the past.
- `country`: max 80 characters. `bio`: max 500 characters.

**Response 200**

```json
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

## 10. Upload profile picture: `POST /profile/avatar` 🔒

**Request** (`multipart/form-data`)

| Field | Notes |
|---|---|
| `avatar` | JPG, PNG or WebP, max 5 MB. Square works best. |

**Response 200:** `{ "success": true, "message": "Profile picture updated", "data": { "user": { ... } } }`. `user.avatar` holds the new image URL.

## 11. Remove profile picture: `DELETE /profile/avatar` 🔒

**Response 200:** `{ "success": true, "message": "Profile picture removed", "data": { "user": { ..., "avatar": null } } }`

## 12. Change password: `PUT /profile/password` 🔒

**Request**

```json
{ "current_password": "secret123", "password": "newsecret456", "password_confirmation": "newsecret456" }
```

- The new password must be at least 8 characters and different from the current one.
- A wrong `current_password` returns 422.

**Response 200**

```json
{ "success": true, "message": "Password changed", "data": null }
```

Other devices are signed out; this device stays signed in.

## 13. Delete account: `DELETE /profile` 🔒

Google Play requires apps to offer this.

**Request**

```json
{ "password": "secret123" }
```

**Response 200**

```json
{ "success": true, "message": "Account deleted", "data": null }
```

- Deletes the account, profile picture and comments. Clear the token and go to the welcome screen.
- A creator's already-published songs and films stay in the catalogue.

## 14. Music home: `GET /music/home` 🔒

Render the sections in the order received. The admin can add, rename or reorder sections, so don't hard-code the keys.

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

## 15. Music genres: `GET /music/genres` 🔒

## 16. Moods: `GET /music/moods` 🔒

Ordered as set by the admin. Used for browse screens and the creator upload form.

```json
{
  "success": true,
  "message": "OK",
  "data": [
    { "id": 12, "name": "Chill" },
    { "id": 13, "name": "Hype" },
    { "id": 14, "name": "Focus" },
    { "id": 15, "name": "Late Night" }
  ]
}
```

`/music/genres` returns the same shape (Hip-Hop, R&B, Electronic, Lo-fi, …).

## 17. Track (player): `GET /music/tracks/{id}` 🔒

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
    "stream_url": "https://iwire-digitals.ovationhealthcare.com.pk/api/v1/stream/1?expires=1792060000&signature=3f1c...",
    "share_url": "https://iwire-digitals.ovationhealthcare.com.pk/s/track/1"
  }
}
```

- `mood` can be `null`.
- If `explicit` is true, show an "E" badge.
- `stream_url` is `null` until the admin uploads the audio file. Show "Not available yet".

## 18. Films home: `GET /films/home?tab=for_you` 🔒

`tab` is `for_you` (the default), `new` or `trending`. Any other value returns 422.

- `for_you`: sections curated by the admin.
- `new`: one section with key `new_releases`.
- `trending`: one section with key `trending`.
- `banners` is the hero carousel. It's the same on every tab, and each banner's `id` is a film id.

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

Image sizes: banner `image` is 16:9; `poster` is 2:3.

## 19. Film genres: `GET /films/genres` 🔒

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

## 20. Films in a genre: `GET /films/genres/{id}?page=1` 🔒

20 films per page. Load the next page while `current_page < last_page`.

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

## 21. Film detail: `GET /films/{id}` 🔒

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
    "stream_url": "https://iwire-digitals.ovationhealthcare.com.pk/api/v1/stream/13?expires=1792060000&signature=9ab2...",
    "share_url": "https://iwire-digitals.ovationhealthcare.com.pk/s/film/13"
  }
}
```

`stream_url` is `null` until the admin uploads the video file.

## 22. Search: `GET /search?q=nova&type=all&limit=10` 🔒

| Param | Notes |
|---|---|
| `q` | Required, 2–100 characters. Matches title, genre and creator name. |
| `type` | `all` (default), `music`, `films` or `creators`. Only that list is returned. |
| `limit` | 1–50 per list. Default 10. |

Only published content and active creators are returned.

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

Tapping a result opens a track with 17, a film with 21, or a creator with 23.

## 23. Creator page: `GET /creators/{id}` 🔒

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

- `type` is `"musician"` or `"filmmaker"`.
- Returns 404 if the creator doesn't exist or is suspended.

## 24. List comments: `GET /music/tracks/{id}/comments?page=1` 🔒 or `GET /films/{id}/comments?page=1` 🔒

Newest first, 20 per page.

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

- If `is_mine` is true, show a delete option.
- `user.id` can be `null` on older comments.
- Comments an admin hides don't appear here.

## 25. Post a comment: `POST /music/tracks/{id}/comments` 🔒 or `POST /films/{id}/comments` 🔒

**Request**

```json
{ "body": "This bridge is unreal" }
```

`body`: 1–1000 characters.

**Response 201**

```json
{
  "success": true,
  "message": "Comment posted",
  "data": {
    "id": 11,
    "body": "This bridge is unreal",
    "created_at": "2026-10-22T06:48:27+00:00",
    "time_ago": "0 seconds ago",
    "is_mine": true,
    "user": { "id": 2, "name": "Ali Khan", "avatar": null }
  }
}
```

## 26. Delete my comment: `DELETE /comments/{id}` 🔒

**Response 200:** `{ "success": true, "message": "Comment deleted", "data": null }`

Returns 404 if the comment isn't yours.

## 27. Share: `POST /music/tracks/{id}/share` 🔒 or `POST /films/{id}/share` 🔒

Call when the user taps **Share**, then pass `text` to Android's share sheet. Each call counts as one share.

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "share_url": "https://iwire-digitals.ovationhealthcare.com.pk/s/track/1",
    "text": "Listen to “Midnight Transit” by Nova Reyes on iWire: https://iwire-digitals.ovationhealthcare.com.pk/s/track/1"
  }
}
```

- `share_url` opens a web page with the cover, the title and an **Open in iWire** button. Messaging apps such as WhatsApp show it as a link preview.
- The button opens the deep link `iwire://track/{id}` or `iwire://film/{id}`. **Register the `iwire` scheme in the Android app** (intent filter) and open the track or film screen for that id.

## 28. Submit an upload: `POST /creator/uploads` 🔒 (creator accounts)

**Request** (`multipart/form-data`)

| Field | Required | Notes |
|---|---|---|
| `type` | Yes | `music` or `films` |
| `title` | Yes | Max 160 characters |
| `genre` | Yes | A name from 15 (music) or 19 (films) |
| `mood` | No | Music only. A name from 16 |
| `description` | No | Max 2000 characters |
| `explicit` | No | `1` or `0` |
| `duration` | Yes | Seconds (read it from the file on the phone) |
| `file` | Yes | Music: MP3, M4A, AAC, WAV, FLAC or OGG, max 200 MB. Films: MP4, MOV, WebM or MKV |
| `cover` | Yes | JPG, PNG or WebP, max 5 MB. Square for music; 2:3 poster for films |
| `banner` | No | Films only. 16:9, max 8 MB |

The upload goes to the admin's Review Queue and only appears in the app after approval.

**Response 201**

```json
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
    "cover": "https://iwire-digitals.ovationhealthcare.com.pk/storage/artwork/35/cover.jpg",
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

Show upload progress. Large files can hit the server's upload size limit.

## 29. My uploads: `GET /creator/uploads?status=pending&page=1` 🔒

- `status` is optional: `pending`, `live`, `rejected` or `unpublished`.
- Newest first, 20 per page.
- Each item has the same shape as in 28, and the response includes `meta`.

| `status` | Show the creator |
|---|---|
| `pending` | Waiting for admin review |
| `live` | Approved and visible in the app |
| `rejected` | `reject_reasons` (for example `["Quality"]`) and `reject_note` from the admin |
| `unpublished` | Taken down by an admin |

## 30. One upload: `GET /creator/uploads/{id}` 🔒

Same shape as 28.

## 31. Withdraw an upload: `DELETE /creator/uploads/{id}` 🔒

Only while `status` is `pending`; otherwise it returns 409.

**Response 200:** `{ "success": true, "message": "Submission withdrawn", "data": null }`

## 32. Creator stats: `GET /creator/stats` 🔒

```json
{
  "success": true,
  "message": "OK",
  "data": { "uploads": 12, "pending": 1, "live": 10, "rejected": 1, "total_plays": 284000, "total_comments": 312 }
}
```

## 33. Playback (`stream_url`)

- Pass `stream_url` straight to the player (ExoPlayer or Media3). It needs **no** `Authorization` header.
- Seeking works (the server supports Range requests).
- The link expires after **6 hours**. On `403 "Link expired or invalid"`, call 17 or 21 again to get a new one.

---

## Errors

| HTTP | Body | App should |
|---|---|---|
| 401 | `{"success":false,"message":"Invalid credentials"}` | Show it on the login screen |
| 401 | `{"success":false,"message":"Unauthenticated"}` | Clear the token and go to login |
| 403 | `{"success":false,"message":"Your account has been suspended. Contact support."}` | Show the message and go to login |
| 403 | `{"success":false,"message":"Link expired or invalid"}` | Fetch the track or film again |
| 403 | `{"success":false,"message":"This feature is for creator accounts."}` | Hide creator screens for listeners |
| 404 | `{"success":false,"message":"Not found"}` | Item missing, not published, or not yours |
| 409 | `{"success":false,"message":"Only submissions still pending review can be withdrawn."}` | Show the message |
| 422 | `{"success":false,"message":"Validation failed","errors":{"email":["The email has already been taken."]}}` | Show each message under its field |
| 429 | `{"success":false,"message":"Too many requests. Try again shortly."}` | Retry later. Limits: login/register 10 per minute, posting comments 20 per minute, creator uploads 10 per minute, everything else 120 per minute |
| 500 | `{"success":false,"message":"Server error"}` | Show a generic error |
