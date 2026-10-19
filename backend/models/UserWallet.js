const mongoose = require('mongoose');

const userWalletSchema = new mongoose.Schema({
  phoneNumber: { type: String, required: true, unique: true, index: true },
  accumulatedPoints: { type: Number, required: true, default: 0 },
  totalCashbackEarned: { type: Number, required: true, default: 0 },
  updatedAt: { type: Date, default: Date.now }
});

userWalletSchema.pre('save', function (next) {
  this.updatedAt = new Date();
  next();
});

module.exports = mongoose.model('UserWallet', userWalletSchema);
