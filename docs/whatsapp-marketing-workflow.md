# WhatsApp Marketing Workflow

**Module:** WhatsApp Marketing  
**Role responsible:** Sales / Marketing team; Manager (campaigns + reports)  
**Route:** `/whatsapp-marketing`

---

## What This Workflow Does

The WhatsApp Marketing module manages inbound WhatsApp leads — prospects who contacted the business through WhatsApp ads or organic messages. Staff qualify contacts through a pipeline of stages, record call history, tag interests, and convert them to project clients when ready.

There is **no RingleSoft approval** in this module — it is a CRM-style pipeline workflow.

---

## Actors

| Who | What they do |
|---|---|
| Marketing Staff | Manages contacts, records calls, updates stages |
| Manager / Admin | Creates campaigns, closes campaigns, views reports |

---

## Contact Pipeline

Each `WhatsAppContact` moves through stages:

```
new → contacted → qualified → proposal_sent → negotiating → converted
                                                              ↓
                                                    ProjectClient created
```

Or drops out:
```
any stage → not_interested → lost
```

### Contact fields

| Field | Notes |
|---|---|
| `name` | Prospect name |
| `phone` | WhatsApp number |
| `source` | `whatsapp_ad` `organic` `referral` |
| `campaign_id` | FK → whatsapp_ad_campaigns, nullable |
| `stage` | Pipeline stage (see above) |
| `assigned_to` | FK → users — responsible staff |
| `client_id` | FK → project_clients, nullable — set when converted |
| `is_important` | Boolean flag — pinned to top of list |
| `labels` | Tags/labels stored in `whatsapp_contact_labels` pivot |
| `notes` | Freeform notes |

### Label management

Labels are stored in the `whatsapp_contact_labels` pivot table (`contact_id`, `label`). Bulk-updated via `PATCH /whatsapp-marketing/contacts/{id}/labels`.

### Conversion to client

When a contact is converted, `client_id` is set to the created/linked `ProjectClient`. The contact then appears in the "Converted" filter.

---

## Call History

Each contact can have multiple call records tracking outreach attempts.

### Call fields

| Field | Notes |
|---|---|
| `contact_id` | FK → whatsapp_contacts |
| `called_by` | FK → users |
| `called_at` | Datetime |
| `outcome` | `no_answer` `answered` `scheduled_callback` `converted` |
| `notes` | What was discussed |

**Routes:**
- `GET /whatsapp-marketing/contacts/{id}/calls` → `getContactCalls()`
- `POST /whatsapp-marketing/contacts/{id}/calls` → `storeContactCall()`

---

## Ad Campaigns

`WhatsAppAdCampaign` records organise contacts acquired through a specific WhatsApp ad campaign.

### Campaign fields

| Field | Notes |
|---|---|
| `name` | Campaign name |
| `start_date` | |
| `end_date` | |
| `status` | `active` `closed` |
| `description` | |

### Campaign lifecycle

```
active → closeCampaign() → closed
```

**Route:** `POST /whatsapp-marketing/campaigns/{id}/close` → `closeCampaign()`

---

## Dashboard Statistics

| Metric | How calculated |
|---|---|
| Total Contacts | `WhatsAppContact::count()` |
| Converted | Contacts with `client_id IS NOT NULL` |
| From Ads | Contacts with `source = whatsapp_ad` |
| Conversion Rate | `converted / total × 100` |
| Active Campaigns | `WhatsAppAdCampaign::where(status, active)::count()` |

---

## Views (Tabs)

| Tab | Content |
|---|---|
| **Contacts** | Full contact list with stage filters, search, label filters |
| **Campaigns** | Campaign listing with contact/conversion counts |
| **Reports** | Stage funnel, source breakdown, monthly trends |

---

## HTTP Routes

| Method | URI | Controller Method |
|---|---|---|
| GET | `/whatsapp-marketing` | `index()` |
| POST | `/whatsapp-marketing/contacts` | `storeContact()` |
| PATCH | `/whatsapp-marketing/contacts/{id}` | `updateContact()` |
| DELETE | `/whatsapp-marketing/contacts/{id}` | `destroyContact()` |
| PATCH | `/whatsapp-marketing/contacts/{id}/labels` | `updateContactLabels()` |
| GET | `/whatsapp-marketing/contacts/{id}/calls` | `getContactCalls()` |
| POST | `/whatsapp-marketing/contacts/{id}/calls` | `storeContactCall()` |
| POST | `/whatsapp-marketing/campaigns` | `storeCampaign()` |
| PATCH | `/whatsapp-marketing/campaigns/{id}` | `updateCampaign()` |
| DELETE | `/whatsapp-marketing/campaigns/{id}` | `destroyCampaign()` |
| POST | `/whatsapp-marketing/campaigns/{id}/close` | `closeCampaign()` |

---

## Database Tables

### `whatsapp_contacts`

`id`, `name`, `phone`, `source`, `campaign_id`, `stage`, `assigned_to`, `client_id`, `is_important`, `notes`

### `whatsapp_contact_labels`

`contact_id`, `label`

### `whatsapp_contact_calls`

`id`, `contact_id`, `called_by`, `called_at`, `outcome`, `notes`

### `whatsapp_ad_campaigns`

`id`, `name`, `start_date`, `end_date`, `status`, `description`

---

## Key Files

```
app/Models/WhatsAppContact.php
app/Models/WhatsAppAdCampaign.php
app/Models/WhatsAppContactCall.php

app/Http/Controllers/WhatsAppMarketingController.php

resources/views/pages/whatsapp_marketing/index.blade.php
```

---

## Related

- **Field Marketing** — uses the same `FieldMarketingService` lookup for service interest tags
- **Project Client** — converted contacts link to `project_clients` records
