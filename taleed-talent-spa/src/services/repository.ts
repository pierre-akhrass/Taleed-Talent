import type { Envelope, PersistedData } from '../domain/types';
import { envelopeSchema } from './schema';
export const STORAGE_KEY = 'taleed.talent.prototype.v2';
export const MAX_BYTES = 2000000;
export class StorageConflict extends Error {
}
export class StorageUnavailable extends Error {
}
export interface StoragePort {
    getItem(key: string): string | null;
    setItem(key: string, value: string): void;
    removeItem(key: string): void;
}
/** Explicitly supported legacy format: v1 used the same entities without weekStart. */
export function migrateAndParse(raw: string): Envelope {
    if (new TextEncoder().encode(raw).length > MAX_BYTES)
        throw new Error('The file exceeds the 2 MB prototype limit.');
    const value: unknown = JSON.parse(raw);
    if (value && typeof value === 'object' && 'schemaVersion' in value && value.schemaVersion === 1) {
        const old = value as {
            schemaVersion: number;
            data?: {
                preferences?: Record<string, unknown>;
            };
        };
        if (old.data?.preferences)
            old.data.preferences.weekStart ??= 0;
        old.schemaVersion = 2;
    }
    const result = envelopeSchema.safeParse(value);
    if (!result.success)
        throw new Error(`This backup is not compatible: ${result.error.issues[0]?.message ?? 'invalid data'}`);
    return result.data as Envelope;
}
export class LocalRepository {
    revision = 0;
    constructor(private storage: StoragePort, readonly clientId: string) { }
    read(): Envelope | null {
        const raw = this.storage.getItem(STORAGE_KEY);
        if (raw === null)
            return null;
        const data = migrateAndParse(raw);
        this.revision = data.revision;
        return data;
    }
    save(data: PersistedData): Envelope {
        const raw = this.storage.getItem(STORAGE_KEY);
        const stored = raw ? migrateAndParse(raw) : null;
        if ((stored?.revision ?? 0) !== this.revision)
            throw new StorageConflict('Another tab has a newer version. Reload its data before continuing.');
        const envelope: Envelope = { schemaVersion: 2, revision: this.revision + 1, clientId: this.clientId, savedAt: new Date().toISOString(), data };
        const text = JSON.stringify(envelope);
        if (new TextEncoder().encode(text).length > MAX_BYTES)
            throw new StorageUnavailable('The prototype data limit was reached. Export a backup before removing old demo records.');
        migrateAndParse(text); // Validate before committing any browser state.
        this.storage.setItem(STORAGE_KEY, text); // Revision advances only after an actual successful write.
        this.revision = envelope.revision;
        return envelope;
    }
    replace(data: PersistedData): Envelope {
        const envelope: Envelope = { schemaVersion: 2, revision: this.revision + 1, clientId: this.clientId, savedAt: new Date().toISOString(), data };
        const text = JSON.stringify(envelope);
        migrateAndParse(text);
        this.storage.setItem(STORAGE_KEY, text); // Atomic replacement: never remove the old snapshot first.
        this.revision = envelope.revision;
        return envelope;
    }
    raw(): string | null { return this.storage.getItem(STORAGE_KEY); }
    reset(): void { this.storage.removeItem(STORAGE_KEY); this.revision = 0; }
}

