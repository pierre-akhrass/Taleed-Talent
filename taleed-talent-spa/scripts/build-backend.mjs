import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const vite = path.join(root, 'node_modules', 'vite', 'bin', 'vite.js');
const result = spawnSync(process.execPath, [vite, 'build'], {
  stdio: 'inherit',
  env: { ...process.env, VITE_BACKEND_BUILD: '1', VITE_APP_BASE: '/app/' },
});

process.exit(result.status ?? 1);