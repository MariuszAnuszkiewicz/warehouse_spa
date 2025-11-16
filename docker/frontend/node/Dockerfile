FROM node:20

WORKDIR /frontend

COPY frontend/package*.json ./

RUN npm install

COPY ./frontend /frontend

EXPOSE 3000

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
