import { findClientByPhone } from '../../ulm/client.repository';
import * as laravel from '../../ulm/laravel.client';
import { con, end } from '../../response.service';
import { sendTransactionSms } from '../../sms/at-sms.service';
import { formatAmount } from '../../../utils/currency';
import { requirePin } from './pin.menu';
import { UssdSession } from '../../../types/ussd.types';

export async function handleLoanApplication(session: UssdSession, steps: string[]): Promise<string> {
  const level = steps.length;

  if (level < 1) return con('Enter your 4-digit PIN:');

  const pinCheck = await requirePin(session, steps[0]);
  if (!pinCheck.ok) return pinCheck.response!;
  session = pinCheck.session;

  if (level < 2) return con('Enter loan amount (KES):');

  const amount = parseFloat(steps[1]);
  if (amount <= 0) return end('Invalid amount.');

  if (level < 3) {
    return con(`Apply for loan of KES ${formatAmount(amount)}?\n1. Confirm\n0. Cancel`);
  }

  if (steps[2] !== '1') return end('Loan application cancelled.');

  const client = await findClientByPhone(session.phoneNumber);
  if (!client) return end('Member not found.');

  const result = await laravel.loanApplication({
    clientId: client.id,
    amount,
    phoneNumber: session.phoneNumber,
    notes: 'USSD loan application',
  });

  if (!result.success) return end(`Application failed: ${result.message}`);

  const appId = result.data?.applicationId;
  await sendTransactionSms(session.phoneNumber, `SACCO: Loan application for KES ${formatAmount(amount)} received. Ref #${appId}. Visit branch to complete.`);
  return end(`Loan application of ${formatAmount(amount)} received. Application #${appId}. Visit branch to complete.`);
}
