```aiignore
# ファイアウォール（まだなら）
sudo ufw allow 80,443/tcp
sudo ufw allow 3478/udp
sudo ufw allow 55000:55999/udp

# 再起動
docker compose down
docker compose up -d
docker compose logs -f livekit coturn caddy

```
