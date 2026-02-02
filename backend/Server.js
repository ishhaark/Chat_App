const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');
const cookieParser = require('cookie-parser');
const jwt = require('jsonwebtoken');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

const bcrypt = require('bcryptjs');
const saltRounds = 10;


// ----------------------
// CORS and Middleware
// ----------------------
app.use(cors({
    origin: ['http://10.10.15.140', 'http://localhost'],
    credentials: true,
    allowedHeaders: ['Content-Type', 'Authorization'],
    exposedHeaders: ['Authorization']
}));

app.use(express.json());
app.use(cookieParser());

// ----------------------
// WebSocket (Socket.io)
// ----------------------
const io = new Server(server, {
    cors: {
        origin: ["http://localhost", "http://10.10.15.140"],
        credentials: true
    }
});

// Store online users
const onlineUsers = new Map();

// ----------------------
// MongoDB Connection
// ----------------------
mongoose.connect(
    "mongodb+srv://ik459123_db_user:vILJAuoNl2zW7UGb@cluster0.hlbadfq.mongodb.net/chat"
)
.then(() => console.log("MongoDB Connected"))
.catch(err => console.log("MongoDB Error:", err));

// ----------------------
// Schemas
// ----------------------
const UserSchema = new mongoose.Schema({
    first_name: String,
    last_name: String,
    email: { type: String, unique: true },
    mobile: String,
    username: String,
    password: String,
    is_online: { type: Boolean, default: false },
    last_seen: { type: Date, default: Date.now }
});

const User = mongoose.model("user", UserSchema);

const MessageSchema = new mongoose.Schema({
    sender: { type: mongoose.Schema.Types.ObjectId, ref: 'user' },
    receiver: { type: mongoose.Schema.Types.ObjectId, ref: 'user' },
    message: String,
    is_read: { type: Boolean, default: false },
    created_at: { type: Date, default: Date.now }
});

const Message = mongoose.model("message", MessageSchema);

// Group Schema
const GroupSchema = new mongoose.Schema({
    name: String,
    description: String,
    created_by: { type: mongoose.Schema.Types.ObjectId, ref: 'user' },
    members: [{ type: mongoose.Schema.Types.ObjectId, ref: 'user' }],
    is_active: { type: Boolean, default: true },
    created_at: { type: Date, default: Date.now }
});

const Group = mongoose.model("group", GroupSchema);

// Group Message Schema
const GroupMessageSchema = new mongoose.Schema({
    group: { type: mongoose.Schema.Types.ObjectId, ref: 'group' },
    sender: { type: mongoose.Schema.Types.ObjectId, ref: 'user' },
    message: String,
    created_at: { type: Date, default: Date.now }
});

const GroupMessage = mongoose.model("group_message", GroupMessageSchema);

// ----------------------
// JWT Configuration
// ----------------------
const JWT_SECRET = "your_secret_key_here_change_this";

// ----------------------
// Auth Middleware
// ----------------------
const auth = async (req, res, next) => {
    let token = req.cookies.token;
    
    // Also check Authorization header
    if (!token && req.headers.authorization) {
        const authHeader = req.headers.authorization;
        if (authHeader.startsWith('Bearer ')) {
            token = authHeader.substring(7);
        }
    }

    if (!token) {
        return res.status(401).json({ 
            status: "error", 
            message: "Authentication required" 
        });
    }

    try {
        const decoded = jwt.verify(token, JWT_SECRET);
        req.user = decoded;
        next();
    } catch (err) {
        return res.status(401).json({ 
            status: "error", 
            message: "Invalid token" 
        });
    }
};

// ========== SOCKET.IO EVENTS ==========
io.on('connection', (socket) => {
    console.log('User connected:', socket.id);

    // Join user to their personal room
    socket.on('join', (userId) => {
        socket.join(userId);
        onlineUsers.set(userId, socket.id);
        
        // Update user online status
        User.findByIdAndUpdate(userId, { 
            is_online: true, 
            last_seen: new Date() 
        }).exec();

        // Notify other users
        socket.broadcast.emit('user-online', userId);
    });

    // Join group room
    socket.on('join-group', (groupId) => {
        socket.join(`group_${groupId}`);
        console.log(`User joined group room: ${groupId}`);
    });

    // Send 1:1 message
    socket.on('send-message', async (data) => {
        try {
            const { sender, receiver, message } = data;
            
            // Save message to database
            const newMessage = new Message({
                sender,
                receiver,
                message
            });
            
            await newMessage.save();
            
            // Populate sender details
            const populatedMessage = await Message.findById(newMessage._id)
                .populate('sender', 'username')
                .populate('receiver', 'username');
            
            // Emit to receiver
            io.to(receiver).emit('receive-message', populatedMessage);
            
            // Emit back to sender for confirmation
            io.to(sender).emit('message-sent', populatedMessage);
        } catch (error) {
            console.error('Error sending message:', error);
        }
    });

    // Send group message
    socket.on('send-group-message', async (data) => {
        try {
            const { groupId, sender, message } = data;
            
            // Verify sender is a group member
            const group = await Group.findOne({
                _id: groupId,
                members: sender
            });
            
            if (!group) {
                return socket.emit('error', 'You are not a member of this group');
            }
            
            // Save group message
            const newGroupMessage = new GroupMessage({
                group: groupId,
                sender,
                message
            });
            
            await newGroupMessage.save();
            
            // Get populated message
            const populatedMessage = await GroupMessage.findById(newGroupMessage._id)
                .populate('sender', 'username');
            
            // Send to group room
            io.to(`group_${groupId}`).emit('receive-group-message', populatedMessage);
            
            // Notify members who might not be in the group room
            group.members.forEach(memberId => {
                if (memberId.toString() !== sender.toString()) {
                    io.to(memberId.toString()).emit('group-notification', {
                        groupId,
                        groupName: group.name,
                        message: `${populatedMessage.sender.username}: ${message}`
                    });
                }
            });
            
        } catch (error) {
            console.error('Error sending group message:', error);
            socket.emit('error', 'Failed to send message');
        }
    });

    // Typing indicator for 1:1 chat
    socket.on('typing', (data) => {
        socket.to(data.receiver).emit('typing', {
            sender: data.sender,
            isTyping: data.isTyping
        });
    });

    // User disconnected
    socket.on('disconnect', () => {
        // Find user by socket ID
        let userId = null;
        for (const [key, value] of onlineUsers.entries()) {
            if (value === socket.id) {
                userId = key;
                break;
            }
        }
        
        if (userId) {
            onlineUsers.delete(userId);
            
            // Update user offline status
            User.findByIdAndUpdate(userId, { 
                is_online: false,
                last_seen: new Date()
            }).exec();
            
            // Notify other users
            socket.broadcast.emit('user-offline', userId);
        }
        
        console.log('User disconnected:', socket.id);
    });
});

// ========== API ROUTES ==========

// 1. Auth & User Routes
app.get('/api/me', auth, async (req, res) => {
    try {
        const user = await User.findById(req.user.id).select("-password");
        if (!user) {
            return res.status(404).json({ 
                status: "error", 
                message: "User not found" 
            });
        }
        res.json({ 
            status: "success", 
            user 
        });
    } catch (err) {
        console.error('Error in /api/me:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Server error" 
        });
    }
});

// Add validation functions
const validateRegistration = (data) => {
    const errors = [];
    
    // Name validation
    if (!data.first_name || data.first_name.trim().length < 2) {
        errors.push('First name must be at least 2 characters long');
    }
    
    if (!data.last_name || data.last_name.trim().length < 2) {
        errors.push('Last name must be at least 2 characters long');
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!data.email || !emailRegex.test(data.email)) {
        errors.push('Please enter a valid email address');
    }
    
    // Username validation
    const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
    if (!data.username || !usernameRegex.test(data.username)) {
        errors.push('Username must be 3-20 characters (letters, numbers, underscore only)');
    }
    
    // Password validation
    if (!data.password || data.password.length < 8) {
        errors.push('Password must be at least 8 characters long');
    }
    
    if (!/(?=.*[a-z])/.test(data.password)) {
        errors.push('Password must contain at least one lowercase letter');
    }
    
    if (!/(?=.*[A-Z])/.test(data.password)) {
        errors.push('Password must contain at least one uppercase letter');
    }
    
    if (!/(?=.*\d)/.test(data.password)) {
        errors.push('Password must contain at least one number');
    }
    
    if (!/(?=.*[@$!%*?&])/.test(data.password)) {
        errors.push('Password must contain at least one special character (@$!%*?&)');
    }
    
    return errors;
};


// const validateRegistration = (data) => {
//     if (!data.email || !data.email.includes('@')) return 'Invalid email';
//     if (!data.username || data.username.length < 3) return 'Username must be at least 3 characters';
//     if (!data.password || data.password.length < 6) return 'Password must be at least 6 characters';
//     return null;
// };

app.post('/api/register', async (req, res) => {
    try {
        const { first_name, last_name, email, username, password } = req.body;
        
        // Validate required fields
        if (!email || !username || !password) {
            return res.status(400).json({ 
                status: "error", 
                message: "Email, username and password are required" 
            });
        }
        
        // Check for existing user
        const existingUser = await User.findOne({ 
            $or: [
                { email: req.body.email },
                { username: req.body.username }
            ] 
        });

        if (existingUser) {
            return res.status(400).json({ 
                status: "error", 
                message: "Email or username already exists" 
            });
        }

        // Hash password
        const hashedPassword = await bcrypt.hash(password, 10);
        
        // Create user with hashed password
        const user = new User({
            first_name,
            last_name,
            email,
            username,
            password: hashedPassword
        });
        
        await user.save();
        
        res.json({ 
            status: "success", 
            message: "Registration successful" 
        });
    } catch (err) {
        console.error('Registration error:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Registration failed" 
        });
    }
});

app.post('/api/login', async (req, res) => {
    try {
        const { identifier, password } = req.body;

        const user = await User.findOne({
            $or: [
                { email: identifier }, 
                { username: identifier }
            ]
        });

        if (!user) {
            return res.status(401).json({ 
                status: "error", 
                message: "Invalid credentials" 
            });
        }

        // CHANGED: Use bcrypt.compare to check hashed password
        const isPasswordValid = await bcrypt.compare(password, user.password);
        
        if (!isPasswordValid) {
            return res.status(401).json({ 
                status: "error", 
                message: "Invalid credentials" 
            });
        }

        const payload = { 
            id: user._id, 
            username: user.username, 
            email: user.email 
        };
        
        const token = jwt.sign(payload, JWT_SECRET, { expiresIn: "24h" });

        res.cookie("token", token, {
            httpOnly: true,
            secure: false,
            maxAge: 24 * 60 * 60 * 1000
        });

        res.json({
            status: "success",
            token,
            user: {
                _id: user._id,
                email: user.email,
                username: user.username,
                first_name: user.first_name,
                last_name: user.last_name
            }
        });
    } catch (err) {
        console.error('Login error:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Server error" 
        });
    }
});

// Get unread messages count for a user
// Get unread messages grouped by sender
app.get('/api/messages/unread-grouped', auth, async (req, res) => {
    try {
        const unreadMessages = await Message.find({
            receiver: req.user.id,
            is_read: false
        })
        .populate('sender', 'username')
        .populate('receiver', 'username')
        .sort({ created_at: -1 });
        
        // Group messages by sender
        const groupedMessages = {};
        unreadMessages.forEach(message => {
            const senderId = message.sender._id.toString();
            if (!groupedMessages[senderId]) {
                groupedMessages[senderId] = {
                    sender: message.sender,
                    messages: [],
                    count: 0,
                    lastMessageTime: message.created_at
                };
            }
            groupedMessages[senderId].messages.push(message);
            groupedMessages[senderId].count++;
            groupedMessages[senderId].lastMessageTime = message.created_at;
        });
        
        // Convert to array and sort by most recent
        const result = Object.values(groupedMessages).sort((a, b) => {
            return new Date(b.lastMessageTime) - new Date(a.lastMessageTime);
        });
        
        res.json({
            status: "success",
            unread_chats: result,
            total_unread: unreadMessages.length
        });
    } catch (err) {
        console.error('Error fetching grouped unread messages:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Failed to get unread messages" 
        });
    }
});

app.post('/api/logout', auth, (req, res) => {
    res.clearCookie('token');
    res.json({ 
        status: "success", 
        message: "Logged out successfully" 
    });
});

// 2. User Management
app.get('/api/users', auth, async (req, res) => {
    try {
        const users = await User.find(
            { _id: { $ne: req.user.id } },
            { 
                username: 1, 
                email: 1,
                _id: 1,
                is_online: 1,
                last_seen: 1
            }
        );
        
        // Get last message time for each user
        const usersWithLastActivity = await Promise.all(
            users.map(async (user) => {
                const lastMessage = await Message.findOne({
                    $or: [
                        { sender: req.user.id, receiver: user._id },
                        { sender: user._id, receiver: req.user.id }
                    ]
                })
                .sort({ created_at: -1 })
                .select('created_at')
                .limit(1);
                
                const userObj = user.toObject();
                userObj.last_message_time = lastMessage ? lastMessage.created_at : null;
                userObj.has_conversation = !!lastMessage;
                
                return userObj;
            })
        );
        
        // Sort users
        usersWithLastActivity.sort((a, b) => {
            if (a.is_online && !b.is_online) return -1;
            if (!a.is_online && b.is_online) return 1;
            
            const aTime = a.last_message_time || a.last_seen || new Date(0);
            const bTime = b.last_message_time || b.last_seen || new Date(0);
            
            return new Date(bTime) - new Date(aTime);
        });
        
        res.json({ 
            status: "success", 
            users: usersWithLastActivity 
        });
    } catch (err) {
        console.error('Error fetching users:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Failed to load users" 
        });
    }
});

app.get('/api/messages/:receiverId', auth, async (req, res) => {
    try {
        const { receiverId } = req.params;
        const senderId = req.user.id;

        const messages = await Message.find({
            $or: [
                { sender: senderId, receiver: receiverId },
                { sender: receiverId, receiver: senderId }
            ]
        })
        .populate('sender', 'username')
        .populate('receiver', 'username')
        .sort({ created_at: 1 })
        .limit(100);

        // Mark messages as read
        await Message.updateMany(
            { 
                sender: receiverId, 
                receiver: senderId, 
                is_read: false 
            },
            { is_read: true }
        );

        res.json({ 
            status: "success", 
            messages 
        });
    } catch (err) {
        console.error('Error fetching messages:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Failed to load messages" 
        });
    }
});

// ========== GROUP MANAGEMENT ROUTES ==========

// Create new group
app.post('/api/groups', auth, async (req, res) => {
    try {
        const { name, description, members } = req.body;
        
        // Create group with creator as first member
        const group = new Group({
            name,
            description,
            created_by: req.user.id,
            members: [req.user.id, ...(members || [])]
        });
        
        await group.save();
        
        // Get populated group data
        const populatedGroup = await Group.findById(group._id)
            .populate('created_by', 'username')
            .populate('members', 'username email');
        
        res.json({
            status: "success",
            message: "Group created successfully",
            group: populatedGroup
        });
    } catch (err) {
        console.error('Error creating group:', err);
        res.status(500).json({
            status: "error",
            message: "Failed to create group"
        });
    }
});

// Get user's groups
app.get('/api/my-groups', auth, async (req, res) => {
    try {
        const groups = await Group.find({
            members: req.user.id,
            is_active: true
        })
        .populate('created_by', 'username')
        .populate('members', 'username')
        .sort({ created_at: -1 });
        
        res.json({
            status: "success",
            groups
        });
    } catch (err) {
        console.error('Error fetching groups:', err);
        res.status(500).json({
            status: "error",
            message: "Failed to load groups"
        });
    }
});

// Get group messages
app.get('/api/groups/:groupId/messages', auth, async (req, res) => {
    try {
        const group = await Group.findOne({
            _id: req.params.groupId,
            members: req.user.id
        });
        
        if (!group) {
            return res.status(403).json({
                status: "error",
                message: "You are not a member of this group"
            });
        }
        
        const messages = await GroupMessage.find({ group: req.params.groupId })
            .populate('sender', 'username')
            .sort({ created_at: 1 })
            .limit(100);
        
        res.json({
            status: "success",
            messages
        });
    } catch (err) {
        console.error('Error fetching group messages:', err);
        res.status(500).json({
            status: "error",
            message: "Failed to load group messages"
        });
    }
});

// Get single group details
app.get('/api/groups/:groupId', auth, async (req, res) => {
    try {
        const group = await Group.findOne({
            _id: req.params.groupId,
            members: req.user.id
        })
        .populate('created_by', 'username')
        .populate('members', 'username email');
        
        if (!group) {
            return res.status(404).json({ 
                status: "error", 
                message: "Group not found" 
            });
        }
        
        res.json({ 
            status: "success", 
            group 
        });
    } catch (err) {
        console.error('Error fetching group:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Failed to load group" 
        });
    }
});

// Update group (admin only)
app.put('/api/groups/:groupId', auth, async (req, res) => {
    try {
        const { name, description } = req.body;

        const group = await Group.findOne({
            _id: req.params.groupId,
            created_by: req.user.id
        });

        if (!group) {
            return res.status(403).json({
                status: "error",
                message: "Only admin can update group"
            });
        }

        group.name = name;
        group.description = description;
        await group.save();

        res.json({
            status: "success",
            message: "Group updated successfully",
            group
        });

    } catch (err) {
        console.error("Update group error:", err);
        res.status(500).json({ status: "error", message: "Failed to update group" });
    }
});

// Add members to group
app.post('/api/groups/:groupId/members', auth, async (req, res) => {
    try {
        const { memberIds } = req.body;
        
        const group = await Group.findOne({
            _id: req.params.groupId,
            created_by: req.user.id
        });
        
        if (!group) {
            return res.status(403).json({ 
                status: "error", 
                message: "Only group admin can add members" 
            });
        }
        
        // Add new members (avoid duplicates)
        memberIds.forEach(memberId => {
            if (!group.members.includes(memberId)) {
                group.members.push(memberId);
            }
        });
        
        await group.save();
        
        const updatedGroup = await Group.findById(group._id)
            .populate('members', 'username email');
        
        res.json({ 
            status: "success", 
            message: "Members added successfully",
            group: updatedGroup
        });
    } catch (err) {
        console.error('Error adding members:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Failed to add members" 
        });
    }
});

// Remove member from group
app.delete('/api/groups/:groupId/members/:memberId', auth, async (req, res) => {
    try {
        const group = await Group.findOne({
            _id: req.params.groupId,
            created_by: req.user.id
        });
        
        if (!group) {
            return res.status(403).json({ 
                status: "error", 
                message: "Only group admin can remove members" 
            });
        }
        
        // Check if trying to remove self (admin)
        if (req.params.memberId === req.user.id) {
            return res.status(400).json({ 
                status: "error", 
                message: "Admin cannot remove themselves" 
            });
        }
        
        // Remove the member
        group.members = group.members.filter(
            member => member.toString() !== req.params.memberId
        );
        
        await group.save();
        
        res.json({ 
            status: "success", 
            message: "Member removed successfully"
        });
    } catch (err) {
        console.error('Error removing member:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Failed to remove member" 
        });
    }
});

// Leave group (for non-admin members)
app.post('/api/groups/:groupId/leave', auth, async (req, res) => {
    try {
        const group = await Group.findById(req.params.groupId);
        
        if (!group) {
            return res.status(404).json({ 
                status: "error", 
                message: "Group not found" 
            });
        }
        
        // Check if user is the admin
        if (group.created_by.toString() === req.user.id) {
            return res.status(400).json({ 
                status: "error", 
                message: "Admin cannot leave. Delete group instead." 
            });
        }
        
        // Remove user from members
        group.members = group.members.filter(
            member => member.toString() !== req.user.id
        );
        
        await group.save();
        
        res.json({ 
            status: "success", 
            message: "Left group successfully"
        });
    } catch (err) {
        console.error('Error leaving group:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Failed to leave group" 
        });
    }
});

// Delete group (admin only)
app.delete('/api/groups/:groupId', auth, async (req, res) => {
    try {
        const group = await Group.findOne({
            _id: req.params.groupId,
            created_by: req.user.id
        });
        
        if (!group) {
            return res.status(403).json({ 
                status: "error", 
                message: "Only group admin can delete group" 
            });
        }
        
        // Soft delete
        group.is_active = false;
        await group.save();
        
        res.json({ 
            status: "success", 
            message: "Group deleted successfully"
        });
    } catch (err) {
        console.error('Error deleting group:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Failed to delete group" 
        });
    }
});

// Get users NOT in group (for adding members)
app.get('/api/groups/:groupId/non-members', auth, async (req, res) => {
    try {
        const group = await Group.findById(req.params.groupId);
        
        if (!group) {
            return res.status(404).json({ 
                status: "error", 
                message: "Group not found" 
            });
        }
        
        // Find users who are NOT in the group AND not the current user
        const nonMembers = await User.find({
            _id: { 
                $nin: group.members,
                $ne: req.user.id
            }
        })
        .select('username email _id')
        .limit(50);
        
        res.json({ 
            status: "success", 
            users: nonMembers 
        });
    } catch (err) {
        console.error('Error fetching non-members:', err);
        res.status(500).json({ 
            status: "error", 
            message: "Failed to load users" 
        });
    }
});

// ----------------------
// Start Server
// ----------------------
const PORT = 4590;
const HOST = '10.10.15.140';

server.listen(PORT, HOST, () => {
    console.log(`Server running on http://${HOST}:${PORT}`);
    console.log('Group Chat APIs Available:');
    console.log('  POST   /api/groups                    - Create group');
    console.log('  GET    /api/my-groups                 - Get user\'s groups');
    console.log('  GET    /api/groups/:groupId/messages  - Get group messages');
    console.log('  GET    /api/groups/:groupId           - Get group details');
    console.log('  PUT    /api/groups/:groupId           - Update group');
    console.log('  POST   /api/groups/:groupId/members   - Add members');
    console.log('  DELETE /api/groups/:groupId/members/:memberId - Remove member');
    console.log('  POST   /api/groups/:groupId/leave     - Leave group');
    console.log('  DELETE /api/groups/:groupId           - Delete group');
    console.log('  GET    /api/groups/:groupId/non-members - Get non-members');
});