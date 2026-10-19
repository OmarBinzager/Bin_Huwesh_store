const mongoose = require('mongoose');
const MONGO_URI = process.env.MONGO_URI || 'mongodb://localhost:27017/shabakati';

async function connectDB() {
  mongoose.set('strictQuery', false);
  try {
    await mongoose.connect(MONGO_URI, {
      useNewUrlParser: true,
      useUnifiedTopology: true
    });
    console.log('MongoDB connected');
  } catch (err) {
    console.error('MongoDB connection error:', err.message);
    process.exit(1);
  }

  mongoose.connection.on('error', err => {
    console.error('MongoDB error:', err);
  });

  mongoose.connection.on('disconnected', () => {
    console.warn('MongoDB disconnected');
  });
}

process.on('SIGINT', async () => {
  try {
    await mongoose.connection.close(false);
    console.log('MongoDB connection closed through app termination');
    process.exit(0);
  } catch (err) {
    console.error('Error closing MongoDB connection:', err);
    process.exit(1);
  }
});

module.exports = connectDB;
