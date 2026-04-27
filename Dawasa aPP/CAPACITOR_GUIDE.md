# Dawasa Mobile App - Capacitor Setup

## Overview
This React web application has been converted to a native mobile app using Capacitor for Android and iOS deployment.

## Installation
```bash
npm install
```

## Development Commands

### Web Development
```bash
npm run dev          # Start web development server
npm run build        # Build for production
```

### Mobile Development
```bash
npm run android      # Build and run on Android device/emulator
npm run ios          # Build and run on iOS device/simulator
npm run sync         # Sync web assets to native platforms
```

## Platform Requirements

### Android
- Android Studio
- Android SDK (API 21+)
- Java Development Kit (JDK)

### iOS
- Xcode
- iOS Simulator
- macOS (required for iOS development)

## Development Workflow

1. **Web Development**: Make changes to your React app
2. **Build**: Run `npm run build` to create production build
3. **Sync**: Run `npm run sync` to copy assets to native platforms
4. **Test**: Run `npm run android` or `npm run ios` to test on device

## Capacitor Configuration

- **App ID**: `com.dawasa.mobileapp`
- **App Name**: `Dawasa Mobile App`
- **Web Directory**: `dist`

## Features Added
- Native splash screen
- Status bar customization
- Back button handling (Android)
- App state management
- Haptic feedback support

## Next Steps
1. Set up Android Studio and Xcode
2. Create app icons and splash screens
3. Configure app permissions
4. Test on real devices
5. Prepare for app store deployment
