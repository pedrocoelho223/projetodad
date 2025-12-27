FROM oven/bun:1 AS build-stage
WORKDIR /app

COPY package.json ./
RUN bun install

COPY . .
RUN echo "=== DEBUG: listar src/components/ui ===" \
 && ls -la /app/src/components/ui || true \
 && echo "=== DEBUG: listar src/components/ui/sonner ===" \
 && ls -la /app/src/components/ui/sonner || true \
 && echo "=== DEBUG: mostrar App.vue ===" \
 && sed -n '1,120p' /app/src/App.vue || true

RUN bun run build

FROM nginx:alpine
COPY --from=build-stage /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80