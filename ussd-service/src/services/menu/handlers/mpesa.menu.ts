import { findClientByPhone } from '../../ulm/client.repository';
import { getActiveLoans } from '../../ulm/loan.repository';
import * as laravel from '../../ulm/laravel.client';
import { con, end } from '../../response.service';
import { sendTransactionSms } from '../../sms/at-sms.service';
import { formatAmount } from '../../../utils/currency';
import { requirePin } from './pin.menu';
import { UssdSession } from '../../../types/ussd.types';

export async function handleMpesaStk(session: UssdSession, steps: string[]): Promise<string> {
  const level = steps.length;
  const client = await findClientByPhone(session.phoneNumber);
  if (!client) return end('Member not found.');

  if (level < 1) return con('Enter your 4-digit PIN:');

  const pinCheck = await requirePin(session, steps[0]);
  if (!pinCheck.ok) return pinCheck.response!;
  session = pinCheck.session;

  const loans = await getActiveLoans(client.id);
  if (!loans.length) return end('No active loans found.');

  if (level < 2) {
    let menu = 'Select loan:\n';
    loans.forEach((l, i) => { menu += `${i + 1}. Loan #${l.id} bal ${formatAmount(l.balance)}\n`; });
    return con(menu.trim());
  }

  const loanIndex = parseInt(steps[1], 10) - 1;
  const loan = loans[loanIndex];
  if (!loan) return end('Invalid loan selection.');

  if (level < 3) return con('Enter repayment amount (KES):');

  const amount = parseFloat(steps[2]);
  if (amount <= 0) return end('Invalid amount.');

  if (level < 4) {
    return con(`M-Pesa STK KES ${formatAmount(amount)} for loan #${loan.id}?\n1. Confirm\n0. Cancel`);
  }

  if (steps[3] !== '1') return end('Payment cancelled.');

  const result = await laravel.mpesaStk({
    clientId: client.id,
    loanId: loan.id,
    amount,
    phoneNumber: session.phoneNumber,
  });

  if (!result.success) return end(`STK failed: ${result.message}`);

  await sendTransactionSms(session.phoneNumber, `SACCO: M-Pesa STK of KES ${formatAmount(amount)} sent for loan #${loan.id}. Check your phone.`);
  return end(`STK prompt sent for KES ${formatAmount(amount)}. Check your phone to complete payment.`);
}
