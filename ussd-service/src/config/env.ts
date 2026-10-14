import { z } from 'zod';

const envSchema = z.object({
  PORT: z.coerce.number().default(3001),
  AT_USERNAME: z.string().min(1),
  AT_API_KEY: z.string().min(1),
  AT_SMS_SHORTCODE: z.string().default('ULM'),
  USSD_SERVICE_CODE: z.string().default('*384*123#'),
  DB_HOST: z.string().min(1),
  DB_PORT: z.coerce.number().default(3306),
  DB_DATABASE: z.string().min(1),
  DB_USERNAME: z.string().min(1),
  DB_PASSWORD: z.string(),
  LARAVEL_BASE_URL: z.string().url(),
  USSD_API_KEY: z.string().min(8),
  APP_KEY: z.string().min(1),
  APP_NAME: z.string().default('SACCO Mobile Banking'),
  REDIS_URL: z.string().default('redis://127.0.0.1:6379'),
  SESSION_TTL: z.coerce.number().default(180),
  MAX_PIN_ATTEMPTS: z.coerce.number().default(3),
});

export type Env = z.infer<typeof envSchema>;

let cached: Env | null = null;

export function getEnv(): Env {
  if (!cached) {
    cached = envSchema.parse(process.env);
  }
  return cached;
}
