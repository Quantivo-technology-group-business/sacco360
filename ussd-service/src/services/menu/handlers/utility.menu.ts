import { findClientByPhone } from '../../ulm/client.repository';
import { getFosaBalance } from '../../ulm/savings.repository';
import { getActiveProviders, getProviderByIndex } from '../../ulm/utility.repository';
import * as laravel from '../../ulm/laravel.client';
import { con, end } from '../../response.service';
import { sendTransactionSms } from '../../sms/at-sms.service';
import { formatAmount } from '../../../utils/currency';
import { requirePin } from './pin.menu';
import { UssdSession } from '../../../types/ussd.types';

function buildProviderMenu(balance: number, providers: { name: string }[]): string {
  let menu = `FOSA Balance: KES ${formatAmount(balance)}\nSelect biller:\n`;
  providers.forEach((p, i) => { menu += `${i + 1}. ${p.name}\n`; });
  menu += '0. Back';
  return menu;
}

export async function handleUtilityPayment(session: UssdSession, steps: string[]): Promise<string> {
  const level = steps.length;
  const client = await findClientByPhone(session.phoneNumber);
  if (!client) return end('Member not found.');

  if (level < 1) return con('Enter your 4-digit PIN:');

  const pinCheck = await requirePin(session, steps[0]);
  if (!pinCheck.ok) return pinCheck.response!;
  session = pinCheck.session;

  const providers = await getActiveProviders();
  if (!providers.length) return end('No utility providers configured.');

  if (level < 2) {
    const balance = await getFosaBalance(client.id);
    return con(buildProviderMenu(balance, providers));
  }

  const provider = await getProviderByIndex(parseInt(steps[1], 10));
  if (!provider) return end('Invalid provider.');

  if (level < 3) return con(`Enter ${provider.account_label}:`);

  const accountNumber = steps[2];
  if (!accountNumber) return end('Invalid account number.');

  if (level < 4) return con('Enter amount (KES):');

  const amount = parseFloat(steps[3]);
  if (amount <= 0) return end('Invalid amount.');
  if (amount < provider.min_amount || amount > provider.max_amount) {
    return end(`Amount must be between ${formatAmount(provider.min_amount)} and ${formatAmount(provider.max_amount)}.`);
  }

  if (level < 5) {
    const balance = await getFosaBalance(client.id);
    return con(
      `Pay KES ${formatAmount(amount)} to ${provider.name}\nAcct: ${accountNumber}\nFOSA bal: ${formatAmount(balance)}\n1. Confirm\n0. Cancel`
    );
  }

  if (steps[4] !== '1') return end('Payment cancelled.');

  const result = await laravel.utilityPayment({
    clientId: client.id,
    providerId: provider.id,
    accountNumber,
    amount,
    phoneNumber: session.phoneNumber,
  });

  if (!result.success) return end(`Payment failed: ${result.message}`);

  const ref = String(result.data?.reference ?? '');
  const newBal = Number(result.data?.newFosaBalance ?? 0);
  await sendTransactionSms(
    session.phoneNumber,
    `SACCO: KES ${formatAmount(amount)} paid to ${provider.name} acct ${accountNumber}. Ref ${ref}. FOSA bal KES ${formatAmount(newBal)}.`
  );
  return end(`Payment successful.\nRef: ${ref}\nNew FOSA bal: KES ${formatAmount(newBal)}`);
}
