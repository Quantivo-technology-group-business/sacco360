export interface AtUssdRequest {
  sessionId: string;
  serviceCode: string;
  phoneNumber: string;
  text: string;
  networkCode?: string;
}

export interface UssdSession {
  sessionId: string;
  phoneNumber: string;
  clientId?: number;
  pinVerified: boolean;
  pinAttempts: number;
  pendingAction?: string;
  pendingData?: Record<string, string | number>;
}

export interface ClientRecord {
  id: number;
  mobile: string;
  first_name: string | null;
  last_name: string | null;
  branch_id: number | null;
  member_number: string | null;
  pin: string | null;
  kyc_status: string | null;
}

export interface UtilityProvider {
  id: number;
  code: string;
  name: string;
  category: string;
  account_label: string;
  min_amount: number;
  max_amount: number;
}

export interface ActiveLoan {
  id: number;
  applied_amount: number;
  status: string;
  balance: number;
}
