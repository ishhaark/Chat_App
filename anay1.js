const UserService = require('./anay2');

class CustomerService {
    create() {
        UserService.create();  // Calls back to UserService
        console.log('Create Customer');
    }
    get() {
        return { name: 'test' };
    }
}
module.exports = new CustomerService;

