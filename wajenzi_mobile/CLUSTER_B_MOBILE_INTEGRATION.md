# Cluster B — Marketing trio: mobile integration manifest

Apply these edits to `wajenzi_mobile/lib/core/router/app_router.dart` during merge.

## Delivered screens
- `FieldMarketingScreen` (`field_marketing_screen.dart`)
- `WhatsAppMarketingScreen` (`whatsapp_marketing_screen.dart`)
- `ContentCreatorScreen` (`content_creator_screen.dart`)

All three back portal routes `field_marketing.index`, `whatsapp_marketing.index`,
and `content_creator.index` via the V1 API.

## 1. Imports to add (near the other screen imports, ~line 130)

```dart
import '../../presentation/screens/marketing/field_marketing_screen.dart';
import '../../presentation/screens/marketing/whatsapp_marketing_screen.dart';
import '../../presentation/screens/marketing/content_creator_screen.dart';
```

## 2. GoRoutes to add (inside the ShellRoute child route list, after the KPI block ~line 1359)

```dart
GoRoute(
  path: '/field-marketing',
  name: 'field-marketing',
  builder: (context, state) => const FieldMarketingScreen(),
),
GoRoute(
  path: '/whatsapp-marketing',
  name: 'whatsapp-marketing',
  builder: (context, state) => const WhatsAppMarketingScreen(),
),
GoRoute(
  path: '/content-creator',
  name: 'content-creator',
  builder: (context, state) => const ContentCreatorScreen(),
),
```

## 3. `_mapWebRoute` entries to add (inside the const map ~line 2885, before the closing brace)

```dart
'field_marketing.index': '/field-marketing',
'field_marketing': '/field-marketing',
'field-marketing': '/field-marketing',
'whatsapp_marketing.index': '/whatsapp-marketing',
'whatsapp_marketing': '/whatsapp-marketing',
'whatsapp-marketing': '/whatsapp-marketing',
'content_creator.index': '/content-creator',
'content_creator': '/content-creator',
'content-creator': '/content-creator',
```

## Test
After applying:
```sh
cd wajenzi_mobile && flutter analyze
```
Should show no new issues introduced by Cluster B.
