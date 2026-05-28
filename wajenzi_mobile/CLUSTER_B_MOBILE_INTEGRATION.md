# Cluster B — Marketing trio: mobile integration manifest

Apply these edits to `wajenzi_mobile/lib/core/router/app_router.dart` during merge.

## ⚠️ Partial delivery
The Content Creator API controller (`ContentCreatorApiController.php`) was created and routes registered, but the Flutter screen for it was **NOT** written before the agent hit a socket error. Two screens delivered:
- `FieldMarketingScreen` (`field_marketing_screen.dart`)
- `WhatsAppMarketingScreen` (`whatsapp_marketing_screen.dart`)

A follow-up agent (or this one resumed) should add `content_creator_screen.dart` mirroring the existing two for completeness. Until then, the `content_creator.index` menu entry will continue to fall through to the web URL.

## 1. Imports to add (near the other screen imports, ~line 130)

```dart
import '../../presentation/screens/marketing/field_marketing_screen.dart';
import '../../presentation/screens/marketing/whatsapp_marketing_screen.dart';
// TODO: add when content_creator_screen.dart exists
// import '../../presentation/screens/marketing/content_creator_screen.dart';
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
// TODO: add when ContentCreatorScreen exists
// GoRoute(
//   path: '/content-creator',
//   name: 'content-creator',
//   builder: (context, state) => const ContentCreatorScreen(),
// ),
```

## 3. `_mapWebRoute` entries to add (inside the const map ~line 2885, before the closing brace)

```dart
'field_marketing.index': '/field-marketing',
'field_marketing': '/field-marketing',
'field-marketing': '/field-marketing',
'whatsapp_marketing.index': '/whatsapp-marketing',
'whatsapp_marketing': '/whatsapp-marketing',
'whatsapp-marketing': '/whatsapp-marketing',
// content_creator.index intentionally NOT mapped yet — screen TBD
```

## Test
After applying:
```sh
cd wajenzi_mobile && flutter analyze
```
Should show no new issues introduced by Cluster B.
