import 'dotenv/config';
import { createApp } from './app';
import { getEnv } from './config/env';

const env = getEnv();
const app = createApp();

app.listen(env.PORT, () => {
  console.log(`USSD service listening on port ${env.PORT}`);
});
