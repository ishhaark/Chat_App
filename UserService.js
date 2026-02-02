const CustomerService = require('./CustomerService');

class UserService {
    create() {
        console.log('Create user');
    }
    get() {
        let customer = CustomerService.get();  // Loops back to CustomerService
        console.log({ customer });
    }
}
module.exports = new UserService;

