const mongoose = require('mongoose');

const transactionSchema = new mongoose.Schema({
  transactionId: { type: String, required: true, unique: true, index: true },
  amount: { type: Number, required: true },
  commissionAmount: { type: Number, required: true, default: 0 },
  userWallet: { type: String, required: true },
  pointsAwarded: { type: Number, required: true, default: 0 },
  status: { type: String, required: true, enum: ['pending', 'success', 'failed'], default: 'pending' },
  cardIssued: { type: mongoose.Schema.Types.ObjectId, ref: 'NetworkCard' },
  createdAt: { type: Date, default: Date.now }
});

module.exports = mongoose.model('Transaction', transactionSchema);
