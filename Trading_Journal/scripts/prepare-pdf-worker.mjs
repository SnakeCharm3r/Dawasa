import { copyFile, mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const source = resolve(projectRoot, 'node_modules/pdfjs-dist/legacy/build/pdf.worker.min.mjs');
const destinationDirectory = resolve(projectRoot, 'public');
const destination = resolve(destinationDirectory, 'pdf.worker.min.mjs');

await mkdir(destinationDirectory, { recursive: true });
await copyFile(source, destination);
