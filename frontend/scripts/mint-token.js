// node scripts/mint-token.js demo alice
// const { AccessToken } = require('livekit-server-sdk');
// scripts/mint-token.js  （package.json に "type":"module" がある前提）
// import { AccessToken, VideoGrant } from 'livekit-server-sdk';
// // const room = process.argv[2] || 'demo';
// // const identity = process.argv[3] || `user-${Date.now()}`;
//
// (async () => {
//     const at = new AccessToken('devkey', 'secret', { identity, ttl: '10m' });
//     at.addGrant({ room, roomJoin: true, canPublish: true, canSubscribe: true });
//     console.log(await at.toJwt());
// })();


// const room = process.argv[2] ?? 'demo';
// const identity = process.argv[3] ?? `user-${Date.now()}`;

// const at = new AccessToken('devkey', 'secret', { identity });
// at.addGrant(new VideoGrant({
//     room,
//     roomJoin: true,
//     canPublish: true,
//     canSubscribe: true,
// }));
//
// console.log(await at.toJwt());
// frontend/scripts/mint-token.mjs
import { AccessToken } from 'livekit-server-sdk';
const [, , room = 'demo', identity = `user-${Date.now()}`] = process.argv;
const at = new AccessToken(process.env.LK_API_KEY, process.env.LK_API_SECRET, { identity });
at.addGrant({ room, roomJoin: true, canPublish: true, canSubscribe: true });
console.log(await at.toJwt());
