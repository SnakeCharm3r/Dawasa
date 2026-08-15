import type { Profile, Settings, Strategy, Trade } from '@/lib/types';

const DATABASE_NAME = 'trading-journal-local-backups';
const STORE_NAME = 'backups';
const LATEST_BACKUP_KEY = 'latest';

export type JournalImportBackup = {
  schema_version: 1;
  backup_type: 'post-report-import';
  created_at: string;
  source_report: {
    name: string;
    type: string;
    size: number;
    last_modified: string;
    detected_format: string;
  };
  import_result: {
    parsed: number;
    imported: number;
    duplicates: number;
    failed: number;
    warnings: string[];
  };
  database: {
    profile: Profile | null;
    settings: Settings;
    strategies: Strategy[];
    trades: Trade[];
  };
};

function openBackupDatabase(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DATABASE_NAME, 1);
    request.onupgradeneeded = () => {
      const database = request.result;
      if (!database.objectStoreNames.contains(STORE_NAME)) database.createObjectStore(STORE_NAME);
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error ?? new Error('Could not open the local backup database.'));
  });
}

async function persistLatestBackup(backup: JournalImportBackup) {
  const database = await openBackupDatabase();
  try {
    await new Promise<void>((resolve, reject) => {
      const transaction = database.transaction(STORE_NAME, 'readwrite');
      transaction.objectStore(STORE_NAME).put(backup, LATEST_BACKUP_KEY);
      transaction.oncomplete = () => resolve();
      transaction.onerror = () => reject(transaction.error ?? new Error('Could not save the local backup.'));
      transaction.onabort = () => reject(transaction.error ?? new Error('The local backup was cancelled.'));
    });
  } finally {
    database.close();
  }
}

function downloadBackup(backup: JournalImportBackup) {
  const json = JSON.stringify(backup, null, 2);
  const blob = new Blob([json], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  const timestamp = backup.created_at.replace(/[:.]/g, '-');
  link.href = url;
  link.download = `trading-journal-backup-${timestamp}.json`;
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 0);
}

export async function saveAndDownloadJournalBackup(backup: JournalImportBackup) {
  await persistLatestBackup(backup);
  downloadBackup(backup);
}
