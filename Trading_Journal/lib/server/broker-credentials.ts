import { createCipheriv, createDecipheriv, createHash, randomBytes } from 'node:crypto';

const VERSION = 'v1';

function encryptionKey(): Buffer {
  const source =
    process.env.BROKER_CREDENTIAL_ENCRYPTION_KEY?.trim() ||
    process.env.SUPABASE_SECRET_KEY?.trim();

  if (!source) {
    throw new Error('Broker credential encryption is not configured.');
  }

  return createHash('sha256')
    .update('trading-journal:broker-credentials:v1\0', 'utf8')
    .update(source, 'utf8')
    .digest();
}

export function encryptBrokerPassword(password: string): string {
  const iv = randomBytes(12);
  const cipher = createCipheriv('aes-256-gcm', encryptionKey(), iv);
  const encrypted = Buffer.concat([cipher.update(password, 'utf8'), cipher.final()]);
  const tag = cipher.getAuthTag();

  return [VERSION, iv.toString('base64url'), tag.toString('base64url'), encrypted.toString('base64url')].join('.');
}

export function decryptBrokerPassword(payload: string): string {
  const [version, encodedIv, encodedTag, encodedCiphertext, extra] = payload.split('.');
  if (version !== VERSION || !encodedIv || !encodedTag || !encodedCiphertext || extra) {
    throw new Error('Unsupported broker credential payload.');
  }

  const decipher = createDecipheriv('aes-256-gcm', encryptionKey(), Buffer.from(encodedIv, 'base64url'));
  decipher.setAuthTag(Buffer.from(encodedTag, 'base64url'));
  const decrypted = Buffer.concat([
    decipher.update(Buffer.from(encodedCiphertext, 'base64url')),
    decipher.final(),
  ]);

  return decrypted.toString('utf8');
}
