# Live session integrations — Zoom and Google Meet

`local_cmc_lms` can create live-session join links through provider APIs when a CMC program-course link is created.

## Supported providers

| Provider | API mode | What CMC creates |
| --- | --- | --- |
| Zoom | Server-to-Server OAuth | Scheduled Zoom meeting with `join_url`. |
| Google Meet | Meet REST API + OAuth JWT | Google Meet meeting space with `meetingUri`. |

No provider credentials are stored in the repository. Configure them in Moodle admin settings.

## Moodle settings

Open:

```text
Site administration → Plugins → CMC LMS domain → Integrations
```

### Zoom

1. Create a Zoom **Server-to-Server OAuth** app.
2. Configure:
   - Account ID
   - Client ID
   - Client secret
   - Zoom user ID/email (`me` or a licensed user)
3. Enable Zoom integration.

When a coordinator links a Moodle course to a CMC program, selecting provider `Zoom` and checking **Create live session through provider API** calls Zoom and stores the generated `join_url` in `liveurl`.

### Google Meet

1. Create a Google Cloud service account.
2. Enable Google Meet REST API.
3. Configure domain-wide delegation if the Workspace domain requires impersonating an organizer.
4. Grant scope:

```text
https://www.googleapis.com/auth/meetings.space.created
```

5. Configure:
   - service account email
   - PEM private key
   - optional Workspace subject user / organizer email
6. Enable Google Meet integration.

When a coordinator links a Moodle course to a CMC program, selecting provider `Google Meet` and checking **Create live session through provider API** calls `spaces.create` and stores the returned `meetingUri` in `liveurl`.

## Runtime behavior

- API creation requires schedule start and schedule end.
- If creation succeeds, `liveintegrationstatus = created` and `liveexternalid` stores the provider resource id.
- If creation fails, the course remains linked, `liveintegrationstatus = error`, and `liveintegrationerror` stores the failure message for troubleshooting.
- Manual links still work: leave **Create live session through provider API** unchecked and paste a `liveurl`.

## Security notes

- Do not commit provider credentials.
- Limit provider credentials to least privilege.
- For Google, prefer a dedicated Workspace organizer account for CMC sessions.
- For Zoom, use a licensed integration user rather than a personal account.
