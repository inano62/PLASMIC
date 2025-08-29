// // frontend/scripts/mint-token.mjs
// import { AccessToken } from 'livekit-server-sdk';
//
// const [, , room = 'demo', identity = `user-${Date.now()}`] = process.argv;
//
// // ここは自分の環境に合わせて。--devなら devkey/secret、config.yamlならその値
// const API_KEY = process.env.LK_API_KEY || 'devkey';
// const API_SECRET = process.env.LK_API_SECRET || '6d1680de114c4ac0908936c8de20c4905896dbb2e2634087a87bb5ed6df986cb4';
// const at = new AccessToken(API_KEY, API_SECRET, { identity, ttl: 60 * 60 }); // 1時間
// at.addGrant({ room, roomJoin: true, canPublish: true, canSubscribe: true });
//
// // const at = new AccessToken(API_KEY, API_SECRET, { identity });
// // at.addGrant({ room, roomJoin: true, canPublish: true, canSubscribe: true });
//
// console.log(await at.toJwt());
// frontend/scripts/mint-token.mjs
import jwt from 'jsonwebtoken';

const [, , room = 'demo', identity = 'alice'] = process.argv;

const key = process.env.LK_API_KEY || 'devkey'; // ← config.yaml の keys: のキー名
const secret = process.env.LK_API_SECRET;       // ← 同じくシークレット

if (!secret) {
    console.error('Missing LK_API_SECRET');
    process.exit(1);
}

const payload = {
    video: {
        room,            // 例: "demo"
        roomJoin: true,  // ← 必須
        canPublish: true,
        canSubscribe: true,
        // roomCreate: true, // 必要なら付けてもOK
    },
    sub: identity,                               // 例: "inano1989"
    iss: key,                                     // ← "devkey" と一致
    nbf: 0,
    exp: Math.floor(Date.now() / 1000) + 60 * 60, // 有効期限 1h
};

const token = jwt.sign(payload, secret, { algorithm: 'HS256' });
console.log(token);
