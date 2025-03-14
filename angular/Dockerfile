FROM node:22 AS base

# Installation de pnpm
RUN npm install -g pnpm

WORKDIR /SportGest

COPY ./SportGest/package*.json ./

# Installation des dépendances avec pnpm
RUN pnpm install

COPY ./SportGest .

FROM base AS dev

EXPOSE 4200

CMD ["pnpm", "start"]