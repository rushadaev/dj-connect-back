const redis = require('redis');
const http = require('http');
const socketIo = require('socket.io');

// Create an HTTP server
const server = http.createServer();

// Initialize Socket.IO server
const io = socketIo(server, {
  cors: {
    origin: '*', // Adjust this according to your CORS policy
  },
});

// Create a Redis client and connect to the Redis container
const redisClient = redis.createClient({
  url: 'redis://redis:6379'
});

// Handle Redis connection errors
redisClient.on('error', (err) => {
  console.error('Redis error:', err);
});

// Log when connected to Redis
(async () => {
  try {
    await redisClient.connect();
    console.log('Connected to Redis');

    // Subscribe to order-created channels dynamically
    await redisClient.pSubscribe('djconnect_database_order-created-*', (message) => {
      try {
        const parsedMessage = JSON.parse(message);
        const orderId = parsedMessage.data.order.id;
        const userId = parsedMessage.data.order.user_id;
        // Emit the message to the specific order update channel
        // const orderUpdateChannel = `order_update_${orderId}`;
        // Notify user aswell
        // const orderCreatedUserChannel = `order_created_user_${userId}`;
         
        const channelName = `order_created_${parsedMessage.data.order.dj_id}`;

        // Emit the message to the specific DJ's order-created channel
        io.emit(channelName, parsedMessage);
        console.log('Order Created:', parsedMessage);
      } catch (err) {
        console.error('Failed to parse message:', err);
      }
    });

    // Subscribe to order-update channels dynamically
    await redisClient.pSubscribe('djconnect_database_order-update-*', (message) => {
      try {
        const parsedMessage = JSON.parse(message);
        const orderId = parsedMessage.data.order.id;
        const userId = parsedMessage.data.order.user_id;
        const djId = parsedMessage.data.order.dj_id;

        // Emit the message to the specific order update channel
        const orderUpdateChannel = `order_update_${orderId}`;
        // Notify user aswell
        const orderUpdatedUserChannel = `order_updated_user_${userId}`;
        const orderUpdatedDjChannel = `order_updated_dj_${djId}`;

        io.emit(orderUpdateChannel, parsedMessage);
        io.emit(orderUpdatedUserChannel, parsedMessage);
        io.emit(orderUpdatedDjChannel, parsedMessage);
        console.log('Order Updated:', parsedMessage);
      } catch (err) {
        console.error('Failed to parse message:', err);
      }
    });

    console.log('Subscribed to Redis channels successfully!');
  } catch (err) {
    console.error('Failed to subscribe:', err.message);
  }
})();

// Handle new client connections
io.on('connection', (socket) => {
  console.log('A user connected:', socket.id);

  // Handle client disconnection
  socket.on('disconnect', () => {
    console.log('A user disconnected:', socket.id);
  });
});

// Start the server on port 6001
const PORT = 6001;
server.listen(PORT, () => {
  console.log(`Socket.IO server running on port ${PORT}`);
});