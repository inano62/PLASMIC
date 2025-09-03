// tools/makeToken.js
import { AccessToken } from 'livekit-server-sdk';
const apiKey = 'devkey';
const apiSecret = 'secret';
const roomName = 'testroom';
const identity = process.argv[2] || 'user-' + Math.random().toString(36).slice(2);

const at = new AccessToken(apiKey, apiSecret, { identity });
at.addGrant({ roomJoin: true, room: roomName });
console.log(await at.toJwt());
