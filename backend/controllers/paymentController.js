const crypto = require('crypto');
const NetworkCard = require('../models/NetworkCard');
const Transaction = require('../models/Transaction');
const UserWallet = require('../models/UserWallet');

function generateId() {
  return crypto.randomBytes(16).toString('hex');
}

function calculatePoints(amount) {
  return Math.floor(amount / 100);
}

function buildWebhookSignature(payload, secret) {
  return crypto.createHmac('sha256', secret).update(payload).digest('hex');
}

const MESSAGES = {
  missing_required_fields: { en: 'Missing required fields: amount, userWallet, networkOwnerId', ar: 'الحقول المطلوبة مفقودة: amount, userWallet, networkOwnerId' },
  invalid_amount: { en: 'Invalid amount', ar: 'المبلغ غير صالح' },
  no_card_available: { en: 'No available network card for this price and owner', ar: 'لا توجد كروت شبكة متاحة لهذا السعر والمالك' },
  checkout_initiated: { en: 'Checkout initiated', ar: 'تم بدء عملية الدفع' },
  missing_webhook_fields: { en: 'Missing webhook fields', ar: 'حقول إشعار الدفع مفقودة' },
  invalid_signature: { en: 'Invalid signature', ar: 'التوقيع الإلكتروني غير صالح' },
  transaction_not_found: { en: 'Transaction not found', ar: 'المعاملة غير موجودة' },
  transaction_already_processed: { en: 'Transaction already processed', ar: 'تمت معالجة المعاملة مسبقاً' },
  payment_failed: { en: 'Payment failed', ar: 'فشلت عملية الدفع' },
  missing_user_wallet: { en: 'Missing user wallet phone number', ar: 'رقم هاتف محفظة المستخدم مطلوب' },
  wallet_not_found: { en: 'Wallet not found', ar: 'المحفظة غير موجودة' },
};

function t(req, key) {
  const lang = (req.headers['x-localization'] || req.headers['accept-language'] || 'ar').toLowerCase();
  const isEn = lang.startsWith('en');
  return isEn ? MESSAGES[key]?.en : (MESSAGES[key]?.ar || MESSAGES[key]?.en);
}

async function simulateSendToWallet({ transactionId, amount, userWallet }) {
  const merchantId = process.env.MERCHANT_ID || 'default_merchant';
  const walletApiKey = process.env.WALLET_API_KEY || 'default_key';
  const walletSecret = process.env.WALLET_SECRET || 'default_secret';

  const paymentRequest = {
    merchantId,
    walletApiKey,
    walletSecret,
    transactionId,
    amount,
    userWallet,
    callbackSignature: buildWebhookSignature(`${transactionId}|pending|${amount}|${userWallet}`, process.env.CALLBACK_SECRET || '')
  };

  return new Promise((resolve) => {
    const checkoutUrl = `https://mock-wallet.example/checkout/${transactionId}`;
    setTimeout(() => resolve({ checkoutUrl, paymentRequest }), 200);
  });
}

exports.initiatePayment = async (req, res, next) => {
  try {
    const { amount, userWallet, networkOwnerId } = req.body;
    if (!amount || !userWallet || !networkOwnerId) {
      return res.status(400).json({ error: t(req, 'missing_required_fields') });
    }

    const price = Number(amount);
    if (isNaN(price) || price <= 0) {
      return res.status(400).json({ error: t(req, 'invalid_amount') });
    }

    const card = await NetworkCard.findOne({ price, status: 'available', networkOwnerId });
    if (!card) {
      return res.status(404).json({ error: t(req, 'no_card_available') });
    }

    const transactionId = generateId();
    const commissionAmount = Number((price * 0.05).toFixed(2));
    const pointsAwarded = calculatePoints(price);

    const transaction = new Transaction({
      transactionId,
      amount: price,
      commissionAmount,
      pointsAwarded,
      userWallet,
      status: 'pending',
      cardIssued: card._id
    });

    await transaction.save();

    const walletResponse = await simulateSendToWallet({ transactionId, amount: price, userWallet });

    return res.status(201).json({
      message: t(req, 'checkout_initiated'),
      transactionId,
      checkoutUrl: walletResponse.checkoutUrl,
      pendingPoints: pointsAwarded
    });
  } catch (err) {
    next(err);
  }
};

exports.handleWebhook = async (req, res, next) => {
  try {
    const { transactionId, status, amount, userWallet, hash } = req.body;
    if (!transactionId || !status || !amount || !userWallet || !hash) {
      return res.status(400).json({ error: t(req, 'missing_webhook_fields') });
    }

    const secret = process.env.CALLBACK_SECRET || '';
    const payload = `${transactionId}|${status}|${amount}|${userWallet}`;
    const expectedHash = buildWebhookSignature(payload, secret);
    const signatureBuffer = Buffer.from(hash, 'hex');
    const expectedBuffer = Buffer.from(expectedHash, 'hex');

    if (signatureBuffer.length !== expectedBuffer.length || !crypto.timingSafeEqual(expectedBuffer, signatureBuffer)) {
      return res.status(403).json({ error: t(req, 'invalid_signature') });
    }

    const tx = await Transaction.findOne({ transactionId }).populate('cardIssued');
    if (!tx) {
      return res.status(404).json({ error: t(req, 'transaction_not_found') });
    }

    if (tx.status === 'success') {
      return res.status(200).json({ message: t(req, 'transaction_already_processed'), cardCode: tx.cardIssued?.cardCode || null });
    }

    if (status === 'success') {
      tx.status = 'success';
      tx.amount = Number(amount);
      await tx.save();

      if (tx.cardIssued) {
        await NetworkCard.findByIdAndUpdate(tx.cardIssued._id, { status: 'sold' });
      }

      const user = await UserWallet.findOneAndUpdate(
        { phoneNumber: userWallet },
        {
          $inc: {
            accumulatedPoints: tx.pointsAwarded,
            totalCashbackEarned: tx.amount
          }
        },
        {
          new: true,
          upsert: true,
          setDefaultsOnInsert: true
        }
      );

      return res.status(200).json({
        success: true,
        cardCode: tx.cardIssued?.cardCode || null,
        pointsBalance: user.accumulatedPoints,
        totalCashbackEarned: user.totalCashbackEarned
      });
    }

    tx.status = 'failed';
    await tx.save();
    return res.status(200).json({ success: false, message: t(req, 'payment_failed') });
  } catch (err) {
    next(err);
  }
};

exports.getPointsBalance = async (req, res, next) => {
  try {
    const { userWallet } = req.params;
    if (!userWallet) {
      return res.status(400).json({ error: t(req, 'missing_user_wallet') });
    }

    const user = await UserWallet.findOne({ phoneNumber: userWallet });
    if (!user) {
      return res.status(404).json({ error: t(req, 'wallet_not_found') });
    }

    return res.status(200).json({
      phoneNumber: user.phoneNumber,
      accumulatedPoints: user.accumulatedPoints,
      totalCashbackEarned: user.totalCashbackEarned,
      updatedAt: user.updatedAt
    });
  } catch (err) {
    next(err);
  }
};
