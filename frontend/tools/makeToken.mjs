import { AccessToken } from 'livekit-server-sdk';

const apiKey = 'k1';
const apiSecret = 'supersecret1234567890supersecret1234';
const roomName = 'demo';

const identity = process.argv[2] || 'user-' + Math.random().toString(36).slice(2);
const at = new AccessToken(apiKey, apiSecret, { identity, ttl: 3600 });
at.addGrant({ roomJoin: true, room: roomName });

// 改行を入れない（PowerShell の改行混入対策）
process.stdout.write(await at.toJwt());
