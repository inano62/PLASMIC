// token.ts
import { AccessToken } from 'livekit-server-sdk';

export function createToken(identity: string, room: string) {
    const at = new AccessToken(
        'k1', // API Key
        'supersecret1234567890supersecret1234', // API Secret
        { identity }
    );
    at.addGrant({ roomJoin: true, room });
    return at.toJwt();
}
