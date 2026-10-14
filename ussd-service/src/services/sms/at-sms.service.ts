import AfricasTalking from 'africastalking';
import { getEnv } from '../../config/env';

let smsClient: { send: (opts: { to: string[]; message: string; from?: string }) => Promise<unknown> } | null = null;

function getSms() {
  if (!smsClient) {
    const env = getEnv();
    const at = AfricasTalking({ apiKey: env.AT_API_KEY, username: env.AT_USERNAME });
    smsClient = at.SMS;
  }
  return smsClient!;
}

export async function sendTransactionSms(phone: string, message: string): Promise<void> {
  try {
    const env = getEnv();
    await getSms().send({ to: [phone], message, from: env.AT_SMS_SHORTCODE });
  } catch (err) {
    console.error('SMS send failed:', err);
  }
}
