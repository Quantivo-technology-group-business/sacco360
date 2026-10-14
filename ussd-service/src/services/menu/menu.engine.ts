import { AtUssdRequest } from '../../types/ussd.types';
import { getSession } from '../session.service';
import { end } from '../response.service';
import { mainMenu } from './handlers/main.menu';
import { handleBalance } from './handlers/balance.menu';
import { handleStatement } from './handlers/statement.menu';
import { handleTransfer } from './handlers/transfer.menu';
import { handleLoanApplication } from './handlers/loan.menu';
import { handleUtilityPayment } from './handlers/utility.menu';
import { handleMpesaStk } from './handlers/mpesa.menu';

export async function processUssd(input: AtUssdRequest): Promise<string> {
  const text = input.text ?? '';
  const steps = text === '' ? [''] : text.split('*');
  const session = await getSession(input.sessionId, input.phoneNumber);

  if (steps.length === 1 && steps[0] === '') {
    return mainMenu();
  }

  const choice = steps[0];

  try {
    switch (choice) {
      case '1':
        return await handleBalance(input.phoneNumber);
      case '2':
        return await handleStatement(input.phoneNumber);
      case '3':
        return await handleTransfer(session, steps.slice(1));
      case '4':
        return await handleLoanApplication(session, steps.slice(1));
      case '5':
        return await handleUtilityPayment(session, steps.slice(1));
      case '6':
        return await handleMpesaStk(session, steps.slice(1));
      case '0':
        return end('Thank you for using SACCO Mobile Banking.');
      default:
        return end('Invalid option.');
    }
  } catch (err) {
    console.error('USSD error:', err);
    return end('Service temporarily unavailable. Please try again.');
  }
}
