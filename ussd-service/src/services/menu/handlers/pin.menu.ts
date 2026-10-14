import { getEnv } from '../../../config/env';
import { validatePin } from '../../auth/pin.service';
import { findClientByPhone } from '../../ulm/client.repository';
import { con, end } from '../../response.service';
import { getSession, saveSession } from '../../session.service';
import { UssdSession } from '../../../types/ussd.types';

export async function requirePin(session: UssdSession, pinInput?: string): Promise<{ ok: boolean; response?: string; session: UssdSession }> {
  if (session.pinVerified) return { ok: true, session };

  if (!pinInput) {
    return { ok: false, response: con('Enter your 4-digit PIN:'), session };
  }

  const client = await findClientByPhone(session.phoneNumber);
  if (!client) return { ok: false, response: end('Member not found.'), session };

  session.clientId = client.id;
  const valid = validatePin(client, pinInput, getEnv().APP_KEY);

  if (!valid) {
    session.pinAttempts += 1;
    await saveSession(session);
    if (session.pinAttempts >= getEnv().MAX_PIN_ATTEMPTS) {
      await saveSession({ ...session, pinVerified: false });
      return { ok: false, response: end('Too many incorrect PIN attempts.'), session };
    }
    return { ok: false, response: con(`Invalid PIN. ${getEnv().MAX_PIN_ATTEMPTS - session.pinAttempts} attempts left.`), session };
  }

  session.pinVerified = true;
  await saveSession(session);
  return { ok: true, session };
}
