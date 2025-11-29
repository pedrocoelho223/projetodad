FROM oven/bun:1 AS build-stage
WORKDIR /app

COPY package.json ./
RUN bun install

COPY . .

# --- FORÇAR ENDEREÇOS REAIS AQUI ---
# Isto sobrepõe qualquer .env que possa estar errado
ENV VITE_API_DOMAIN="http://api-dad-group-5-172.22.21.253.sslip.io/api"
ENV VITE_WS_CONNECTION="http://ws-dad-group-5-172.22.21.253.sslip.io"
# -----------------------------------

RUN bun run build

FROM nginx:alpine
COPY --from=build-stage /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80