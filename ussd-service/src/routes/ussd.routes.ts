import { Router } from 'express';
import { handleCallback } from '../controllers/ussd.controller';

const router = Router();

router.post('/callback', handleCallback);
router.get('/callback', handleCallback);

export default router;
