// pages/api/dev/token.ts
import type { NextApiRequest, NextApiResponse } from 'next'
import { AccessToken } from 'livekit-server-sdk'

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
    try {
        const { room, identity } = (typeof req.body === 'string' ? JSON.parse(req.body) : req.body) || {}
        if (!room || !identity) return res.status(400).json({ error: 'room/identity required' })

        const at = new AccessToken(process.env.LIVEKIT_API_KEY!, process.env.LIVEKIT_API_SECRET!, { identity, ttl: '1h' })
        at.addGrant({ room, roomJoin: true, canPublish: true, canSubscribe: true })
        const token = await at.toJwt()

        res.setHeader('Content-Type', 'application/json')
        return res.status(200).json({ token, url: process.env.LIVEKIT_URL }) // wss://xxxxx.livekit.cloud
    } catch (e:any) {
        return res.status(500).json({ error: String(e?.message || e) })
    }
}
