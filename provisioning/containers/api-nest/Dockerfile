FROM node:24-bookworm-slim AS build
WORKDIR /app
COPY nestjs/package.json nestjs/package-lock.json ./
RUN npm ci
COPY nestjs/ ./
RUN npm run build

FROM node:24-bookworm-slim
WORKDIR /app
COPY --from=build /app/node_modules ./node_modules
COPY --from=build /app/dist ./dist
COPY src/Bluesky/Resources /app/src/Bluesky/Resources
ENV PROJECT_DIR=/app
ENV NODE_ENV=production
USER node
EXPOSE 3000
CMD ["node", "dist/main.js"]
