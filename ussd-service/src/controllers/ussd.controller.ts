import { Request, Response } from 'express';
import { processUssd } from '../services/menu/menu.engine';
import { AtUssdRequest } from '../types/ussd.types';

export async function handleCallback(req: Request, res: Response): Promise<void> {
  const input: AtUssdRequest = {
    sessionId: String(req.body.sessionId ?? req.query.sessionId ?? ''),
    serviceCode: String(req.body.serviceCode ?? req.query.serviceCode ?? ''),
    phoneNumber: String(req.body.phoneNumber ?? req.query.phoneNumber ?? ''),
    text: String(req.body.text ?? req.query.text ?? ''),
    networkCode: String(req.body.networkCode ?? req.query.networkCode ?? ''),
  };

  const response = await processUssd(input);
  res.status(200).type('text/plain').send(response);
}
