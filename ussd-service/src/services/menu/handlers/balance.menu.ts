import { findClientByPhone } from '../../ulm/client.repository';
import * as savings from '../../ulm/savings.repository';
import { end } from '../../response.service';
import { formatAmount } from '../../../utils/currency';

export async function handleBalance(phone: string): Promise<string> {
  const client = await findClientByPhone(phone);
  if (!client) return end('Member not found for this number.');

  const [fosa, bosa, shares, loans] = await Promise.all([
    savings.getFosaBalance(client.id),
    savings.getBosaBalance(client.id),
    savings.getShareValue(client.id),
    savings.getLoanBalance(client.id),
  ]);

  return end(
    `FOSA: ${formatAmount(fosa)}\nBOSA: ${formatAmount(bosa)}\nShares: ${formatAmount(shares)}\nLoans: ${formatAmount(loans)}`
  );
}
