import { getEnv } from '../../../config/env';
import { con } from '../../response.service';

export function mainMenu(): string {
  const name = getEnv().APP_NAME;
  return con(
    `Welcome to ${name}\n1. Balance Inquiry\n2. Mini Statement\n3. Transfers\n4. Loan Services\n5. Pay Bill from SACCO\n6. M-Pesa Loan Repayment\n0. Exit`
  );
}
