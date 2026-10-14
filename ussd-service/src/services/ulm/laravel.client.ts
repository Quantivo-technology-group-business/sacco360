import { getEnv } from '../../config/env';

interface ApiResult {
  success: boolean;
  message?: string;
  data?: Record<string, unknown>;
}

async function post(path: string, body: Record<string, unknown>): Promise<ApiResult> {
  const env = getEnv();
  const res = await fetch(`${env.LARAVEL_BASE_URL}/api/v1/sacco/ussd/actions/${path}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-USSD-API-Key': env.USSD_API_KEY,
    },
    body: JSON.stringify(body),
  });

  const json = (await res.json()) as ApiResult;
  if (!res.ok) {
    return { success: false, message: json.message ?? `Request failed (${res.status})` };
  }
  return json;
}

export function transfer(data: Record<string, unknown>) {
  return post('transfer', data);
}

export function loanApplication(data: Record<string, unknown>) {
  return post('loan-application', data);
}

export function utilityPayment(data: Record<string, unknown>) {
  return post('utility-payment', data);
}

export function mpesaStk(data: Record<string, unknown>) {
  return post('mpesa-stk', data);
}
