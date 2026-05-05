import { Capacitor } from '@capacitor/core';
import { SplashScreen } from '@capacitor/splash-screen';
import { StatusBar, Style } from '@capacitor/status-bar';
import { App } from '@capacitor/app';
import { useEffect } from 'react';

export const useCapacitor = () => {
  useEffect(() => {
    if (!Capacitor.isNativePlatform()) return;

    let appStateHandle: Awaited<ReturnType<typeof App.addListener>> | null = null;
    let backButtonHandle: Awaited<ReturnType<typeof App.addListener>> | null = null;

    const initializeCapacitor = async () => {
      // Hide splash screen
      await SplashScreen.hide();

      // Set status bar style
      await StatusBar.setStyle({ style: Style.Light });
      await StatusBar.setBackgroundColor({ color: '#003F7F' });

      // Handle app state changes
      appStateHandle = await App.addListener('appStateChange', ({ isActive }) => {
        if (!isActive) return;
      });

      // Handle back button on Android
      backButtonHandle = await App.addListener('backButton', () => {
        window.history.back();
      });
    };

    initializeCapacitor();

    return () => {
      appStateHandle?.remove();
      backButtonHandle?.remove();
    };
  }, []);

  return {
    isNative: Capacitor.isNativePlatform(),
    platform: Capacitor.getPlatform(),
  };
};
