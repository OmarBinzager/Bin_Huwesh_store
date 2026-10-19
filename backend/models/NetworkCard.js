const mongoose = require('mongoose');

const networkCardSchema = new mongoose.Schema({
  cardCode: { type: String, required: true, unique: true, index: true },
  price: { type: Number, required: true },
  status: { type: String, required: true, enum: ['available', 'sold'], default: 'available' },
  networkOwnerId: { type: String, required: true }
});

module.exports = mongoose.model('NetworkCard', networkCardSchema);
