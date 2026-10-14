import express from 'express';
import healthRoutes from './routes/health.routes';
import ussdRoutes from './routes/ussd.routes';
import { errorHandler } from './middleware/errorHandler';

export function createApp() {
  const app = express();
  app.use(express.urlencoded({ extended: true }));
  app.use(express.json());
  app.use(healthRoutes);
  app.use('/ussd', ussdRoutes);
  app.use(errorHandler);
  return app;
}
