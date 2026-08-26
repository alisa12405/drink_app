FROM node:22-alpine

RUN apk add --no-cache bash git python3 make g++

WORKDIR /app

COPY docker/node/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 5173

ENTRYPOINT ["entrypoint.sh"]
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
