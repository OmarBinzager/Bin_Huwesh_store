require('dotenv').config();
const express = require('express');
const helmet = require('helmet');
const cors = require('cors');
const connectDB = require('./config/db');
const paymentRoutes = require('./routes/paymentRoutes');

const app = express();
app.use(helmet());
app.use(cors());
app.use(express.json({ limit: '10kb' }));
app.use(express.urlencoded({ extended: false, limit: '10kb' }));

connectDB().catch(err => {
  console.error('Failed to initialize database', err);
  process.exit(1);
});

app.use('/api/payment', paymentRoutes);

app.get('/', (req, res) => {
  res.json({ service: 'Shabakati Backend', status: 'ok' });
});

app.use((err, req, res, next) => {
  console.error(err);
  const status = err.status || 500;
  res.status(status).json({ error: err.message || 'Internal Server Error' });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
