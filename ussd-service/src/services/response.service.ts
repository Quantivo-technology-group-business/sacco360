const USSD_MAX = 182;

export function con(message: string): string {
  const body = message.trim();
  return `CON ${truncate(body)}`;
}

export function end(message: string): string {
  const body = message.trim();
  return `END ${truncate(body)}`;
}

function truncate(text: string): string {
  if (text.length <= USSD_MAX) return text;
  return text.slice(0, USSD_MAX - 3) + '...';
}
