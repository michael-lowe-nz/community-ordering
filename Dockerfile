FROM --platform=linux/amd64 node:latest

WORKDIR /usr/src/app

RUN echo "Heya man"

# Copy package.json and package-lock.json
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the application
COPY . .

# Make port 80 available to the world outside this container
EXPOSE 80

CMD ["node", "./app.js"]