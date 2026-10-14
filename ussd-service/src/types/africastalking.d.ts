declare module 'africastalking' {
  interface ATConfig {
    apiKey: string;
    username: string;
  }
  interface ATInstance {
    SMS: {
      send: (opts: { to: string[]; message: string; from?: string }) => Promise<unknown>;
    };
  }
  function AfricasTalking(config: ATConfig): ATInstance;
  export = AfricasTalking;
}
