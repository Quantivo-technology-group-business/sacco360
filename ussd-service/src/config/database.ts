import mysql from 'mysql2/promise';
import { getEnv } from './env';

let pool: mysql.Pool | null = null;

export function getPool(): mysql.Pool {
  if (!pool) {
    const env = getEnv();
    pool = mysql.createPool({
      host: env.DB_HOST,
      port: env.DB_PORT,
      user: env.DB_USERNAME,
      password: env.DB_PASSWORD,
      database: env.DB_DATABASE,
      waitForConnections: true,
      connectionLimit: 10,
    });
  }
  return pool;
}
