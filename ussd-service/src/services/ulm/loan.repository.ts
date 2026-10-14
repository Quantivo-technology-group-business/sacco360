import { RowDataPacket } from 'mysql2';
import { getPool } from '../../config/database';
import { ActiveLoan } from '../../types/ussd.types';

export async function getActiveLoans(clientId: number): Promise<ActiveLoan[]> {
  const [rows] = await getPool().query<RowDataPacket[]>(
    `SELECT l.id, l.applied_amount, l.status,
            COALESCE(SUM(lt.debit) - SUM(lt.credit), 0) AS balance
     FROM loans l
     LEFT JOIN loan_transactions lt ON lt.loan_id = l.id
     WHERE l.client_id = ? AND l.status IN ('active', 'approved', 'disbursed')
     GROUP BY l.id, l.applied_amount, l.status
     ORDER BY l.id DESC`,
    [clientId]
  );
  return rows.map((r) => ({
    id: Number(r.id),
    applied_amount: Number(r.applied_amount),
    status: String(r.status),
    balance: Number(r.balance),
  }));
}

export async function getDefaultLoanProductId(): Promise<number> {
  const [rows] = await getPool().query<RowDataPacket[]>(
    `SELECT id FROM loan_products WHERE active = 1 ORDER BY id ASC LIMIT 1`
  );
  return rows.length ? Number(rows[0].id) : 1;
}
