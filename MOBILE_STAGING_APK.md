# Mobile Staging APK

Use this flow when you want to share an APK with testers outside your local network.

## 1. Deploy branch to a public staging server

Deploy the backend and web changes from `dev-athumani` to a staging URL that is reachable over the internet.

Example staging URL:

```text
https://staging.example.com
```

## 2. Create the staging config asset

Copy the example file and replace the URLs with the real staging domain:

```bash
cp wajenzi_mobile/assets/staging_config.json.example wajenzi_mobile/assets/staging_config.json
```

Then edit `wajenzi_mobile/assets/staging_config.json` so it looks like this:

```json
{
  "ENVIRONMENT_NAME": "STAGING",
  "API_BASE_URL": "https://staging.example.com/api/v1",
  "PORTAL_BASE_URL": "https://staging.example.com",
  "CLIENT_API_BASE_URL": "https://staging.example.com/api/client"
}
```

## 3. Build the staging APK

```bash
cd wajenzi_mobile
flutter clean
flutter build apk --release --dart-define=APP_CONFIG_ASSET=assets/staging_config.json
```

## 4. Share the APK

The generated APK will be here:

```text
wajenzi_mobile/build/app/outputs/flutter-apk/app-release.apk
```

## 5. Verify inside the app

Open the app and go to:

```text
Settings -> Environment
```

You should see:

- `Current environment: STAGING`
- the staging portal URL
- the staging API URLs

If the app shows `PROD`, then the wrong config asset was used during build.

## Production-backed APK for remote UI testing

If you want to share an APK that shows the latest mobile UI changes but still uses the live backend, build with:

```bash
cd wajenzi_mobile
flutter clean
flutter build apk --release --dart-define=APP_CONFIG_ASSET=assets/prod_config.json
```

This keeps the Flutter/mobile UI changes from your branch inside the APK while routing API calls to the public production server.
