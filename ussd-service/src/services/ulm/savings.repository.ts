import { RowDataPacket } from 'mysql2';
import { getPool } from '../../config/database';

export async function getFosaBalance(clientId: number): Promise<number> {
  const [rows] = await getPool().query<RowDataPacket[]>(
    `SELECT COALESCE(SUM(st.credit) - SUM(st.debit), 0) AS balance
     FROM savings s
     JOIN savings_products sp ON sp.id = s.savings_product_id
     LEFT JOIN savings_transactions st ON st.savings_id = s.id AND st.reversed = 0
     WHERE s.client_id = ? AND s.status = 'active'
       AND (sp.savings_category IS NULL OR sp.savings_category != 'compulsory')`,
    [clientId]
  );
  return Number(rows[0]?.balance ?? 0);
}

export async function getBosaBalance(clientId: number): Promise<number> {
  const [rows] = await getPool().query<RowDataPacket[]>(
    `SELECT COALESCE(SUM(st.credit) - SUM(st.debit), 0) AS balance
     FROM savings s
     JOIN savings_products sp ON sp.id = s.savings_product_id
     LEFT JOIN savings_transactions st ON st.savings_id = s.id AND st.reversed = 0
     WHERE s.client_id = ? AND s.status = 'active' AND sp.savings_category = 'compulsory'`,
    [clientId]
  );
  return Number(rows[0]?.balance ?? 0);
}

export async function getShareValue(clientId: number): Promise<number> {
  const [rows] = await getPool().query<RowDataPacket[]>(
    `SELECT COALESCE(SUM(sh.total_shares * sp.nominal_price), 0) AS share_value
     FROM shares sh
     JOIN share_products sp ON sp.id = sh.share_product_id
     WHERE sh.client_id = ? AND sh.status = 'active'`,
    [clientId]
  );
  return Number(rows[0]?.share_value ?? 0);
}

export async function getLoanBalance(clientId: number): Promise<number> {
  const [rows] = await getPool().query<RowDataPacket[]>(
    `SELECT COALESCE(SUM(lt.debit) - SUM(lt.credit), 0) AS loan_balance
     FROM loans l
     LEFT JOIN loan_transactions lt ON lt.loan_id = l.id
     WHERE l.client_id = ? AND l.status IN ('active', 'approved', 'disbursed')`,
    [clientId]
  );
  return Number(rows[0]?.loan_balance ?? 0);
}

export async function getFosaAccountId(clientId: number): Promise<number | null> {
  const [rows] = await getPool().query<RowDataPacket[]>(
    `SELECT s.id FROM savings s
     JOIN savings_products sp ON sp.id = s.savings_product_id
     WHERE s.client_id = ? AND s.status = 'active'
       AND (sp.savings_category IS NULL OR sp.savings_category != 'compulsory')
     ORDER BY s.id ASC LIMIT 1`,
    [clientId]
  );
  return rows.length ? Number(rows[0].id) : null;
}

export async function getMiniStatement(clientId: number, limit = 5): Promise<Array<{ name: string; amount: number; date: string }>> {
  const fosaId = await getFosaAccountId(clientId);
  const params: (number | string)[] = [clientId];
  let savingsFilter = '';
  if (fosaId) {
    savingsFilter = ' AND st.savings_id = ?';
    params.push(fosaId);
  }
  params.push(limit);

  const [rows] = await getPool().query<RowDataPacket[]>(
    `SELECT st.name, st.amount, st.submitted_on
     FROM savings_transactions st
     JOIN savings s ON s.id = st.savings_id
     WHERE s.client_id = ?${savingsFilter}
     ORDER BY st.id DESC LIMIT ?`,
    params
  );

  return rows.map((r) => ({
    name: String(r.name),
    amount: Number(r.amount),
    date: String(r.submitted_on),
  }));
}
