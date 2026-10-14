import { RowDataPacket } from 'mysql2';
import { getPool } from '../../config/database';
import { UtilityProvider } from '../../types/ussd.types';

export async function getActiveProviders(): Promise<UtilityProvider[]> {
  const [rows] = await getPool().query<RowDataPacket[]>(
    `SELECT id, code, name, category, account_label, min_amount, max_amount
     FROM sacco_utility_providers WHERE active = 1 ORDER BY id ASC`
  );
  return rows.map((r) => ({
    id: Number(r.id),
    code: String(r.code),
    name: String(r.name),
    category: String(r.category),
    account_label: String(r.account_label),
    min_amount: Number(r.min_amount),
    max_amount: Number(r.max_amount),
  }));
}

export async function getProviderByIndex(index: number): Promise<UtilityProvider | null> {
  const providers = await getActiveProviders();
  return providers[index - 1] ?? null;
}
