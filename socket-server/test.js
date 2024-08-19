const redis = require('redis');

console.log('Initializing Redis client...');

const client = redis.createClient({
    legacyMode: true,
  socket: {
    host: 'redis',
  port: 6379,
  }
});
client.connect().catch(console.error)
client.on('error', (err) => {
  console.error('Redis error:', err);
});

client.on('connect', () => {
  console.log('Connected to Redis');
});

client.on('ready', () => {
  console.log('Redis client is ready');
//   client.quit();  // Close the connection after the test
});

client.on('end', () => {
  console.log('Redis client connection closed');
});

client.on('reconnecting', () => {
  console.log('Redis client is reconnecting...');
});

console.log('Script finished executing.');