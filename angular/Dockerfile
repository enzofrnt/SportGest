FROM node:22 AS base

# Installation de pnpm
RUN npm install -g pnpm

WORKDIR /SportGest

COPY ./SportGest .

RUN rm -rf node_modules

RUN pnpm install

FROM base AS build

RUN pnpm build

FROM nginx:latest as prod

COPY --from=build /SportGest/dist/sport-gest/browser /usr/share/nginx/html

COPY ./nginx.conf /etc/nginx/conf.d/default.conf

COPY ./entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT [ "/entrypoint.sh" ]

FROM base AS dev

EXPOSE 4200

CMD ["pnpm", "start"]