import { findClientByPhone } from '../../ulm/client.repository';
import { getMiniStatement } from '../../ulm/savings.repository';
import { end } from '../../response.service';
import { formatAmount } from '../../../utils/currency';

export async function handleStatement(phone: string): Promise<string> {
  const client = await findClientByPhone(phone);
  if (!client) return end('Member not found.');

  const txns = await getMiniStatement(client.id);
  if (!txns.length) return end('No transactions found.');

  let msg = 'Last 5 FOSA transactions:\n';
  for (const t of txns) {
    msg += `${t.date}: ${t.name} ${formatAmount(t.amount)}\n`;
  }
  return end(msg.trim());
}
