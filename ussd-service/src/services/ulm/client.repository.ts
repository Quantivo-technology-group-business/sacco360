import { RowDataPacket } from 'mysql2';
import { getPool } from '../../config/database';
import { ClientRecord } from '../../types/ussd.types';
import { normalizePhoneSuffix } from '../../utils/phone';

export async function findClientByPhone(phone: string): Promise<ClientRecord | null> {
  const suffix = normalizePhoneSuffix(phone);
  const [rows] = await getPool().query<RowDataPacket[]>(
    `SELECT id, mobile, first_name, last_name, branch_id, member_number, pin, kyc_status
     FROM clients WHERE mobile LIKE ? LIMIT 1`,
    [`%${suffix}`]
  );
  return rows.length ? (rows[0] as ClientRecord) : null;
}
