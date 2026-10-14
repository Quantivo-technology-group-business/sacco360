import crypto from 'crypto';
import { ClientRecord } from '../../types/ussd.types';

export function validatePin(client: ClientRecord, pin: string, appKey: string): boolean {
  const stored = (client.pin ?? '').toString();
  if (stored && timingSafe(stored, pin)) return true;

  const fallback = crypto.createHash('md5').update(`${client.mobile}${appKey}`).digest('hex').substring(0, 4);
  return timingSafe(fallback, pin);
}

function timingSafe(a: string, b: string): boolean {
  if (a.length !== b.length) return false;
  return crypto.timingSafeEqual(Buffer.from(a), Buffer.from(b));
}
