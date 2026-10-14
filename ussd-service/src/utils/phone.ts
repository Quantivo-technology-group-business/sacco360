export function normalizePhoneSuffix(phone: string): string {
  const digits = phone.replace(/\D/g, '');
  return digits.slice(-9);
}
