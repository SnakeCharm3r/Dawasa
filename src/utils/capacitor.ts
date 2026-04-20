import { Capacitor } from '@capacitor/core';
import { SplashScreen } from '@capacitor/splash-screen';
import { StatusBar, Style } from '@capacitor/status-bar';
import { App } from '@capacitor/app';
import { useEffect } from 'react';

export const useCapacitor = () => {
  useEffect(() => {
    const initializeCapacitor = async () => {
      if (Capacitor.isNativePlatform()) {
        // Hide splash screen
        await SplashScreen.hide();
        
        // Set status bar style
        await StatusBar.setStyle({ style: Style.Light });
        await StatusBar.setBackgroundColor({ color: '#003F7F' });
        
        // Handle app state changes
        App.addListener('appStateChange', ({ isActive }) => {
          console.log('App state changed. Is active?', isActive);
        });
        
        // Handle back button on Android
        App.addListener('backButton', () => {
          // Handle back button press
          window.history.back();
        });
      }
    };

    initializeCapacitor();
  }, []);

  return {
    isNative: Capacitor.isNativePlatform(),
    platform: Capacitor.getPlatform(),
  };
};
