import Redis from 'ioredis';
import { getEnv } from '../config/env';
import { UssdSession } from '../types/ussd.types';

let redis: Redis | null = null;

function getRedis(): Redis {
  if (!redis) {
    redis = new Redis(getEnv().REDIS_URL);
  }
  return redis;
}

function key(sessionId: string): string {
  return `ussd:session:${sessionId}`;
}

export async function getSession(sessionId: string, phoneNumber: string): Promise<UssdSession> {
  const raw = await getRedis().get(key(sessionId));
  if (raw) return JSON.parse(raw) as UssdSession;
  return { sessionId, phoneNumber, pinVerified: false, pinAttempts: 0 };
}

export async function saveSession(session: UssdSession): Promise<void> {
  await getRedis().set(key(session.sessionId), JSON.stringify(session), 'EX', getEnv().SESSION_TTL);
}

export async function clearSession(sessionId: string): Promise<void> {
  await getRedis().del(key(sessionId));
}
