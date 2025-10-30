FROM node:20

COPY package*.json ./

COPY . .

RUN npm install -g npm@11.5.1

RUN npm -v

EXPOSE 3000