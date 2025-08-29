import { AccessToken } from 'livekit-server-sdk';

// config.yaml.example の keys と一致させる
const apiKey = 'devkey';
const apiSecret = 'secret';

// 使う部屋名（自由に）
const roomName = 'testroom';

// 引数から identity を受け取る（未指定なら random）
const identity = process.argv[2] || 'user-' + Math.random().toString(36).slice(2);

const at = new AccessToken(apiKey, apiSecret, { identity });
// その部屋に join できる権限を付与
at.addGrant({ roomJoin: true, room: roomName });

const jwt = await at.toJwt();
console.log(jwt);
