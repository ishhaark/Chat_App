// index.js - Error version
const CustomerService = require('./CustomerService');
const UserService = require('./UserService');

// Add this line to trigger during require phase
console.log('CustomerService.create exists:', typeof CustomerService.create);

CustomerService.create();

