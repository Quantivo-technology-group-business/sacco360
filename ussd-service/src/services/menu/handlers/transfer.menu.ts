import { findClientByPhone } from '../../ulm/client.repository';
import * as laravel from '../../ulm/laravel.client';
import { con, end } from '../../response.service';
import { sendTransactionSms } from '../../sms/at-sms.service';
import { formatAmount } from '../../../utils/currency';
import { requirePin } from './pin.menu';
import { UssdSession } from '../../../types/ussd.types';

export async function handleTransfer(session: UssdSession, steps: string[]): Promise<string> {
  const level = steps.length;

  if (level < 1) return con('Enter your 4-digit PIN:');

  const pinCheck = await requirePin(session, steps[0]);
  if (!pinCheck.ok) return pinCheck.response!;
  session = pinCheck.session;

  if (level < 2) {
    return con('Select transfer type:\n1. FOSA to Loan\n2. BOSA to FOSA');
  }

  const type = steps[1];
  const client = await findClientByPhone(session.phoneNumber);
  if (!client) return end('Member not found.');

  if (type === '1') {
    if (level < 3) return con('Enter loan ID:');
    const loanId = parseInt(steps[2], 10);
    if (level < 4) return con('Enter repayment amount:');
    const amount = parseFloat(steps[3]);
    if (!loanId || amount <= 0) return end('Invalid loan ID or amount.');

    const result = await laravel.transfer({
      clientId: client.id,
      transferType: 'savings_to_loan',
      loanId,
      amount,
      phoneNumber: session.phoneNumber,
    });

    if (!result.success) return end(`Transfer failed: ${result.message}`);

    const ref = String(result.data?.reference ?? '');
    await sendTransactionSms(session.phoneNumber, `SACCO: KES ${formatAmount(amount)} transferred to loan #${loanId}. Ref ${ref}`);
    return end(`Transfer of ${formatAmount(amount)} to loan #${loanId} successful.`);
  }

  if (type === '2') {
    if (level < 3) return con('Enter amount to transfer from BOSA to FOSA:');
    const amount = parseFloat(steps[2]);
    if (amount <= 0) return end('Invalid amount.');

    const result = await laravel.transfer({
      clientId: client.id,
      transferType: 'savings_to_savings',
      amount,
      phoneNumber: session.phoneNumber,
    });

    if (!result.success) return end(`Transfer failed: ${result.message}`);

    await sendTransactionSms(session.phoneNumber, `SACCO: KES ${formatAmount(amount)} transferred from BOSA to FOSA.`);
    return end(`Transferred ${formatAmount(amount)} from BOSA to FOSA successfully.`);
  }

  return end('Invalid transfer type.');
}
