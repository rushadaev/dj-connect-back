FROM node:16

# Set working directory inside the container
WORKDIR /usr/src/app

# Install app dependencies
COPY package*.json ./
RUN npm install

# Copy the rest of the application code (not necessary during development with volumes)
# COPY . .

EXPOSE 6001

# Command to run your app
CMD ["node", "socket-server.js"]