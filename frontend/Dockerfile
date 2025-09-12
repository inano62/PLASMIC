#FROM node:20-alpine
#WORKDIR /app
#COPY package*.json ./
#RUN npm ci || npm i
#COPY . .
#EXPOSE 3000
#CMD ["npm","run","dev","--","-p","3000","-H","0.0.0.0"]
FROM node:20-alpine

# JST
RUN apk add --no-cache tzdata && \
    cp /usr/share/zoneinfo/Asia/Tokyo /etc/localtime && \
    echo "Asia/Tokyo" > /etc/timezone

WORKDIR /app
COPY package*.json ./
RUN npm ci || npm i
COPY . .

# Docker/Windows でのファイル変更検知を安定させる
ENV CHOKIDAR_USEPOLLING=1
ENV WATCHPACK_POLLING=true

# Vite のデフォポート
EXPOSE 5176

# Vite を 0.0.0.0 で公開
CMD ["npm","run","dev","--","--host","0.0.0.0","--port","5176"]
