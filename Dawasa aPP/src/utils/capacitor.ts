import { Capacitor } from '@capacitor/core';
import { SplashScreen } from '@capacitor/splash-screen';
import { StatusBar } from '@capacitor/status-bar';
import { App } from '@capacitor/app';
import { useEffect } from 'react';

export const useCapacitor = () => {
  useEffect(() => {
    const initializeCapacitor = async () => {
      if (Capacitor.isNativePlatform()) {
        // Hide splash screen
        await SplashScreen.hide();
        
        // Set status bar style
        await StatusBar.setStyle({ style: 'LIGHT' });
        await StatusBar.setBackgroundColor({ color: '#1e40af' });
        
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
