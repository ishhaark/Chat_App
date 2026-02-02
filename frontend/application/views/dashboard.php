<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Dashboard</title>
    <link rel="stylesheet" href="<?php echo base_url('assets/dashboard.css'); ?>">
</head>
<body>
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="user-header">
                <div class="current-user">
                    <div class="avatar">
                        <?php echo strtoupper(substr($me['username'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($me['username'] ?? 'User'); ?></h3>
                        <p class="online-status"><span class="status-dot"></span> Online</p>
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="chat-tabs">
                <button class="tab-btn active" id="usersTab" onclick="chatApp.showUsersTab()">
                    👤 Users
                </button>
                <button class="tab-btn" id="groupsTab" onclick="chatApp.showGroupsTab()">
                    🏠 Groups
                </button>
            </div>
            
            <!-- USERS Container (default visible) -->
            <div id="usersContainer">
                <div class="users-list" id="usersList">
                    <div class="loading">Loading users...</div>
                </div>
            </div>
            
            <!-- GROUPS Container (initially hidden) -->
            <div id="groupsContainer" style="display: none;">
                <div class="section-header">
                    <h4>Your Groups</h4>
                    <button class="create-group-btn" onclick="chatApp.showCreateGroupModal()">
                        + New Group
                    </button>
                </div>
                <div class="groups-list" id="groupsList">
                    <!-- Groups will be loaded here -->
                </div>
            </div>
        </div> <!-- Close sidebar -->

        <!-- Chat Area -->
        <div class="chat-area">
            <div class="chat-header">
                <h2 id="currentChatUser">Select a user to chat</h2>
                <div class="online-status" id="chatStatus">
                    <span class="status-dot offline"></span>
                    <span>Offline</span>
                </div>
            </div>
            
            <div class="messages-container" id="messagesContainer">
                <div class="welcome-message">
                    Select a user from the sidebar to start chatting
                </div>
            </div>

            <div class="typing-indicator" id="typingIndicator" style="display: none;">
                <span id="typingUser"></span> is typing...
            </div>

            <div class="message-input-area">
                <div class="input-group">
                    <input type="text" 
                           class="message-input" 
                           id="messageInput" 
                           placeholder="Type your message..." 
                           disabled>
                    <button class="send-btn" id="sendButton">Send</button>
                </div>
            </div>
        </div>
    </div> <!-- Close chat-container -->

    <!-- MODALS (outside main container) -->
    <!-- Group Chat Modal -->
    <div id="groupChatModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="groupChatTitle">Group Chat</h3>
                <div class="group-actions">
                    <button onclick="chatApp.showGroupMembers()" class="group-action-btn" title="View Members">👥</button>
                    <button onclick="chatApp.showGroupSettings()" class="group-action-btn" title="Group Settings">⚙️</button>
                    <button onclick="chatApp.leaveGroup()" class="group-action-btn" title="Leave Group" style="color: #f44336;">🚪</button>
                    <button onclick="chatApp.closeGroupChat()" class="close-btn">&times;</button>
                </div>
            </div>
            
            <div id="groupMessagesContainer" class="messages-container">
                <!-- Group messages will appear here -->
            </div>
            <div class="message-input-area">
                <div class="input-group">
                    <input type="text" 
                           class="message-input" 
                           id="groupMessageInput" 
                           placeholder="Type your message..."
                           onkeypress="if(event.key==='Enter') chatApp.sendGroupMessage()">
                    <button class="send-btn" onclick="chatApp.sendGroupMessage()">Send</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Group Modal -->
    <div id="createGroupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Group</h3>
                <button onclick="chatApp.closeCreateGroupModal()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <input type="text" id="groupName" placeholder="Group Name" required>
                </div>
                <div class="form-group">
                    <textarea id="groupDescription" placeholder="Group Description (optional)"></textarea>
                </div>
                <div class="member-selection">
                    <h4>Add Members</h4>
                    <div id="availableUsersList" class="users-list">
                        <!-- Available users will be loaded here -->
                    </div>
                    <div class="selected-members">
                        <h4>Selected Members</h4>
                        <div id="selectedMembersList"></div>
                    </div>
                </div>
                <button onclick="chatApp.createGroup()" class="btn-primary">Create Group</button>
            </div>
        </div>
    </div>

    <!-- Group Members Modal -->
    <div id="groupMembersModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Group Members</h3>
                <button onclick="chatApp.closeGroupMembersModal()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="members-list" id="groupMembersList">
                    <!-- Members will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button onclick="chatApp.showAddMembersModal()" class="btn-primary">Add Members</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Members Modal -->
    <div id="addMembersModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Add Members to Group</h3>
                <button onclick="chatApp.closeAddMembersModal()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div id="availableUsersForGroup" class="users-list">
                    <!-- Available users will be loaded here -->
                </div>
                <div class="selected-members-preview">
                    <h4>Selected to Add</h4>
                    <div id="addMembersSelectedList"></div>
                </div>
                <div class="modal-footer">
                    <button onclick="chatApp.addSelectedMembers()" class="btn-primary">Add Selected Members</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Group Settings Modal -->
    <div id="groupSettingsModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Group Settings</h3>
                <button onclick="chatApp.closeGroupSettingsModal()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Group Name</label>
                    <input type="text" id="editGroupName" placeholder="Group Name">
                </div>
                <div class="form-group">
                    <label>Group Description</label>
                    <textarea id="editGroupDescription" placeholder="Group Description"></textarea>
                </div>
                <div class="danger-zone">
                    <h4>Danger Zone</h4>
                    <button onclick="chatApp.deleteGroup()" class="btn-danger">Delete Group</button>
                    <p class="danger-note">Only group creator can delete the group</p>
                </div>
                <div class="modal-footer">
                    <button onclick="chatApp.updateGroupSettings()" class="btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Socket.io Client -->
    <script src="https://cdn.socket.io/4.5.0/socket.io.min.js"></script>
    
    <script>
        class ChatApplication {
            constructor() {
                this.socket = null;
                this.currentUser = <?php echo json_encode($me); ?>;
                this.currentChat = null;
                this.currentGroup = null;
                this.typingTimeout = null;
                this.isTyping = false;
                this.onlineUsers = new Set();
                this.groups = [];
                this.selectedMembers = new Set();
                this.selectedMembersMap = new Map();
                this.membersToAdd = new Set();
                this.membersToAddMap = new Map();
                this.unreadChats = new Map(); // Store unread messages by user ID
                this.totalUnreadCount = 0;
                
                this.initialize();      
            }

            async initialize() {
                try {
                    this.connectSocket();
                    await this.loadUsers();
                    await this.loadUnreadChats();  
                    this.setupEventListeners();
                    this.setupNotificationListeners();
                } catch (error) {
                    console.error('Initialization error:', error);
                }
            }

            connectSocket() {
                this.socket = io('http://10.10.15.140:4590', {
                    withCredentials: true
                });

                this.socket.on('connect', () => {
                    console.log('Socket connected');
                    this.socket.emit('join', this.currentUser._id);
                });

                this.socket.on('receive-message', this.handleIncomingMessage.bind(this));
                this.socket.on('message-sent', this.handleMessageSent.bind(this));
                this.socket.on('user-online', this.handleUserOnline.bind(this));
                this.socket.on('user-offline', this.handleUserOffline.bind(this));
                this.socket.on('typing', this.handleTypingIndicator.bind(this));
                this.socket.on('receive-group-message', this.handleGroupMessage.bind(this));
                this.socket.on('group-notification', this.handleGroupNotification.bind(this));
                this.socket.on('error', this.handleSocketError.bind(this));
            }

            // ========== USERS TAB METHODS ==========
            async loadUsers() {
                try {
                    const response = await fetch('<?php echo base_url("index.php/chat/apiProxy/users"); ?>');
                    if (!response.ok) throw new Error('Failed to load users');
                    const data = await response.json();
                    this.renderUsersList(data.users);
                } catch (error) {
                    console.error('Error loading users:', error);
                    document.getElementById('usersList').innerHTML = '<div class="error">Failed to load users</div>';
                }
            }

            renderUsersList(users) {
                const usersList = document.getElementById('usersList');
                if (!users || users.length === 0) {
                    usersList.innerHTML = '<div class="no-users">No users found</div>';
                    return;
                }
                const html = users.map(user => {
                    const username = user.username || 'Unknown User';
                    const userId = user._id || 'no-id';
                    const email = user.email || '';
                    const isOnline = user.is_online || false;
                    const firstLetter = username.charAt(0).toUpperCase();
                    return `<div class="user-item" data-user-id="${userId}"
                    onclick="chatApp.openChat('${userId}', '${this.escapeHtml(username)}')">
                    <div class="avatar">${firstLetter}</div>
                    <div class="user-info">
                    <strong>${this.escapeHtml(username)}</strong>
                    <p>${this.escapeHtml(email)}</p>
                    </div>
                    <div class="status-dot ${isOnline ? '' : 'offline'}" id="status-${userId}"></div>
            </div>
        `;
    }).join('');

    usersList.innerHTML = html;
}

            async openChat(userId, username) {
                this.currentChat = { _id: userId, username };
                this.currentGroup = null;
                
                document.getElementById('currentChatUser').textContent = username;
                document.getElementById('messageInput').disabled = false;
                document.getElementById('sendButton').disabled = false;
                
                document.querySelectorAll('.user-item').forEach(item => {
                    item.classList.toggle('active', item.dataset.userId === userId);
                });

                await this.loadChatHistory(userId);
                this.scrollToBottom();
            }

            async loadChatHistory(receiverId) {
                try {
                    const response = await fetch(`<?php echo base_url("index.php/chat/apiProxy/messages/"); ?>${receiverId}`, {
                        credentials: 'include'
                    });
                    if (!response.ok) throw new Error('Failed to load messages');
                    const data = await response.json();
                    this.renderMessages(data.messages);
                } catch (error) {
                    console.error('Error loading messages:', error);
                }
            }

            renderMessages(messages) {
                const container = document.getElementById('messagesContainer');
                if (!messages || messages.length === 0) {
                    container.innerHTML = '<div class="welcome-message">No messages yet. Start the conversation!</div>';
                    return;
                }
                const html = messages.map(msg => {
                    const isSent = msg.sender._id === this.currentUser._id;
                    const time = new Date(msg.created_at).toLocaleTimeString([], { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                    return `
                        <div class="message ${isSent ? 'sent' : 'received'}">
                            <div class="message-text">${this.escapeHtml(msg.message)}</div>
                            <div class="message-info">
                                <span>${time}</span>
                                ${isSent ? '<span>✓✓</span>' : ''}
                            </div>
                        </div>
                    `;
                }).join('');
                container.innerHTML = html;
                this.scrollToBottom();
            }

            async sendMessage() {
                const input = document.getElementById('messageInput');
                const message = input.value.trim();
                if (!message || !this.currentChat || !this.socket) return;
                input.value = '';
                this.stopTyping();
                const messageData = {
                    sender: this.currentUser._id,
                    receiver: this.currentChat._id,
                    message: message
                };
                this.addMessageToUI({
                    sender: { _id: this.currentUser._id, username: this.currentUser.username },
                    receiver: { _id: this.currentChat._id, username: this.currentChat.username },
                    message: message,
                    created_at: new Date().toISOString(),
                    is_read: false
                }, true);
                this.socket.emit('send-message', messageData);
            }

            addMessageToUI(messageData, isSent = true) {
                const container = document.getElementById('messagesContainer');
                if (container.innerHTML.includes('welcome-message')) {
                    container.innerHTML = '';
                }
                const time = new Date(messageData.created_at).toLocaleTimeString([], { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                const messageElement = document.createElement('div');
                messageElement.className = `message ${isSent ? 'sent' : 'received'}`;
                messageElement.innerHTML = `
                    <div class="message-text">${this.escapeHtml(messageData.message)}</div>
                    <div class="message-info">
                        <span>${time}</span>
                        ${isSent ? '<span>✓</span>' : ''}
                    </div>
                `;
                container.appendChild(messageElement);
                this.scrollToBottom();
            }

            // ========== GROUPS TAB METHODS ==========
            showUsersTab() {
                document.getElementById('usersContainer').style.display = 'block';
                document.getElementById('groupsContainer').style.display = 'none';
                document.getElementById('usersTab').classList.add('active');
                document.getElementById('groupsTab').classList.remove('active');
                this.currentGroup = null;
            }

            showGroupsTab() {
                document.getElementById('usersContainer').style.display = 'none';
                document.getElementById('groupsContainer').style.display = 'block';
                document.getElementById('groupsTab').classList.add('active');
                document.getElementById('usersTab').classList.remove('active');
                if (this.groups.length === 0) {
                    this.loadGroups();
                }
            }

            async loadGroups() {
                try {
                    const response = await fetch('<?php echo base_url("index.php/chat/apiProxy/my-groups"); ?>', {
                        credentials: 'include'
                    });
                    if (!response.ok) throw new Error('Failed to load groups');
                    const data = await response.json();
                    this.groups = data.groups;
                    this.renderGroupsList();
                } catch (error) {
                    console.error('Error loading groups:', error);
                    document.getElementById('groupsList').innerHTML = '<div class="error">Failed to load groups</div>';
                }
            }

            renderGroupsList() {
                const container = document.getElementById('groupsList');
                if (!this.groups || this.groups.length === 0) {
                    container.innerHTML = `
                        <div class="no-groups">
                            <p>No groups yet</p>
                            <p style="font-size: 12px; margin-top: 5px; opacity: 0.7;">Create your first group to start chatting!</p>
                        </div>
                    `;
                    return;
                }
                const html = this.groups.map(group => {
                    const memberCount = group.members ? group.members.length : 0;
                    const firstLetter = group.name.charAt(0).toUpperCase();
                    return `
                        <div class="group-item" 
                             onclick="chatApp.openGroupChat('${group._id}', '${this.escapeHtml(group.name)}')">
                            <div class="group-avatar">${firstLetter}</div>
                            <div class="group-info">
                                <strong>${this.escapeHtml(group.name)}</strong>
                                <p>${memberCount} members</p>
                            </div>
                        </div>
                    `;
                }).join('');
                container.innerHTML = html;
            }

            async openGroupChat(groupId, groupName) {
                this.currentGroup = { _id: groupId, name: groupName };
                this.currentChat = null;
                document.getElementById('groupChatModal').style.display = 'flex';
                document.getElementById('groupChatTitle').textContent = groupName;
                this.socket.emit('join-group', groupId);
                await this.loadGroupMessages(groupId);
                setTimeout(() => {
                    document.getElementById('groupMessageInput').focus();
                }, 100);
            }

            async loadGroupMessages(groupId) {
                try {
                    const response = await fetch(`<?php echo base_url("index.php/chat/apiProxy/groups/"); ?>${groupId}/messages`, {
                        credentials: 'include'
                    });
                    const data = await response.json();
                    this.renderGroupMessages(data.messages);
                } catch (error) {
                    console.error('Error loading group messages:', error);
                }
            }

            renderGroupMessages(messages) {
                const container = document.getElementById('groupMessagesContainer');
                if (!messages || messages.length === 0) {
                    container.innerHTML = '<div class="welcome-message">No messages yet. Start the conversation!</div>';
                    return;
                }
                const html = messages.map(msg => {
                    const isSent = msg.sender._id === this.currentUser._id;
                    const time = new Date(msg.created_at).toLocaleTimeString([], { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                    return `
                        <div class="group-message ${isSent ? 'sent' : 'received'}">
                            ${!isSent ? `<div class="sender-name">${this.escapeHtml(msg.sender.username)}:</div>` : ''}
                            <div class="message-text">${this.escapeHtml(msg.message)}</div>
                            <div class="message-time">${time}</div>
                        </div>
                    `;
                }).join('');
                container.innerHTML = html;
                this.scrollGroupToBottom();
            }

            async sendGroupMessage() {
                const input = document.getElementById('groupMessageInput');
                const message = input.value.trim();
                if (!message || !this.currentGroup || !this.socket) return;
                input.value = '';
                const messageData = {
                    groupId: this.currentGroup._id,
                    sender: this.currentUser._id,
                    message: message
                };
                this.addGroupMessageToUI({
                    sender: { _id: this.currentUser._id, username: this.currentUser.username },
                    message: message,
                    created_at: new Date().toISOString()
                }, true);
                this.socket.emit('send-group-message', messageData);
            }

            addGroupMessageToUI(messageData, isSent = true) {
                const container = document.getElementById('groupMessagesContainer');
                if (container.innerHTML.includes('welcome-message')) {
                    container.innerHTML = '';
                }
                const time = new Date(messageData.created_at).toLocaleTimeString([], { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                const messageElement = document.createElement('div');
                messageElement.className = `group-message ${isSent ? 'sent' : 'received'}`;
                messageElement.innerHTML = `
                    ${!isSent ? `<div class="sender-name">${this.escapeHtml(messageData.sender.username)}:</div>` : ''}
                    <div class="message-text">${this.escapeHtml(messageData.message)}</div>
                    <div class="message-time">${time}</div>
                `;
                container.appendChild(messageElement);
                this.scrollGroupToBottom();
            }

            handleGroupMessage(message) {
                if (this.currentGroup && message.group === this.currentGroup._id) {
                    if (message.sender._id !== this.currentUser._id) {
                        this.addGroupMessageToUI(message, false);
                    }
                }
            }

            handleGroupNotification(data) {
                console.log('Group notification:', data);
                // You could show a toast notification here
            }

            closeGroupChat() {
                document.getElementById('groupChatModal').style.display = 'none';
                document.getElementById('groupMessageInput').value = '';
                this.currentGroup = null;
            }

            // ========== CREATE GROUP MODAL METHODS ==========
            showCreateGroupModal() {
                document.getElementById('createGroupModal').style.display = 'flex';
                this.loadAvailableUsers();
            }

            closeCreateGroupModal() {
                document.getElementById('createGroupModal').style.display = 'none';
                document.getElementById('groupName').value = '';
                document.getElementById('groupDescription').value = '';
                this.selectedMembers.clear();
                this.selectedMembersMap.clear();
                this.renderSelectedMembers();
            }

            async loadAvailableUsers() {
                try {
                    const response = await fetch('<?php echo base_url("index.php/chat/apiProxy/users"); ?>', {
                        credentials: 'include'
                    });
                    const data = await response.json();
                    this.renderAvailableUsers(data.users);
                } catch (error) {
                    console.error('Error loading users:', error);
                }
            }

            renderAvailableUsers(users) {
                const container = document.getElementById('availableUsersList');
                const html = users.map(user => {
                    const isSelected = this.selectedMembers.has(user._id);
                    return `
                        <div class="user-select-item ${isSelected ? 'selected' : ''}" 
                             onclick="chatApp.toggleMemberSelection('${user._id}', '${this.escapeHtml(user.username)}')">
                            <div class="avatar" style="width: 30px; height: 30px; font-size: 12px;">${user.username.charAt(0).toUpperCase()}</div>
                            <div class="user-info">
                                <strong style="font-size: 13px;">${this.escapeHtml(user.username)}</strong>
                                <p style="font-size: 11px;">${this.escapeHtml(user.email)}</p>
                            </div>
                             ${isSelected ? '<span class="selected-check">✓</span>' : ''}
                        </div>
                    `;
                }).join('');
                container.innerHTML = html;
            }

            toggleMemberSelection(userId, username) {
                if (this.selectedMembers.has(userId)) {
                    this.selectedMembers.delete(userId);
                    this.selectedMembersMap.delete(userId);
                } else {
                    this.selectedMembers.add(userId);
                    this.selectedMembersMap.set(userId, username);
                }
                this.renderSelectedMembers();
                this.refreshAvailableUsersList();
            }

            async refreshAvailableUsersList() {
                try {
                    const response = await fetch('<?php echo base_url("index.php/chat/apiProxy/users"); ?>', {
                        credentials: 'include'
                    });
                    const data = await response.json();
                    this.renderAvailableUsers(data.users);
                } catch (error) {
                    console.error('Error loading users:', error);
                }
            }

            renderSelectedMembers() {
                const container = document.getElementById('selectedMembersList');
                if (this.selectedMembers.size === 0) {
                    container.innerHTML = '<p style="color: #888; font-style: italic;">No members selected</p>';
                    return;
                }
                const selectedArray = Array.from(this.selectedMembersMap.entries());
                container.innerHTML = selectedArray.map(([id, username]) => 
                    `<span class="selected-member">${this.escapeHtml(username)} 
                     <span onclick="chatApp.removeSelectedMember('${id}')" style="cursor: pointer; margin-left: 5px;">×</span></span>`
                ).join('');
            }

            removeSelectedMember(userId) {
                this.selectedMembers.delete(userId);
                this.selectedMembersMap.delete(userId);
                this.renderSelectedMembers();
                this.refreshAvailableUsersList();
            }

            async createGroup() {
                const name = document.getElementById('groupName').value.trim();
                const description = document.getElementById('groupDescription').value.trim();
                if (!name) {
                    alert('Please enter a group name');
                    return;
                }
                if (this.selectedMembers.size === 0) {
                    alert('Please select at least one member');
                    return;
                }
                const groupData = {
                    name: name,
                    description: description,
                    members: Array.from(this.selectedMembers)
                };
                try {
                    const response = await fetch('<?php echo base_url("index.php/chat/apiProxy/groups"); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify(groupData)
                    });
                    const data = await response.json();
                    if (data.status === 'success') {
                        alert('Group created successfully!');
                        this.closeCreateGroupModal();
                        this.loadGroups();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error creating group:', error);
                    alert('Failed to create group');
                }
            }

            // ========== GROUP MANAGEMENT METHODS ==========
            async showGroupMembers() {
                if (!this.currentGroup) return;
                document.getElementById('groupMembersModal').style.display = 'flex';
                await this.loadGroupMembers();
            }

          async loadGroupMembers() {
            try {
                // CHANGE THIS LINE: Remove "/members" from the URL
                const response = await fetch(`<?php echo base_url("index.php/chat/apiProxy/groups/"); ?>${this.currentGroup._id}`, {
                    credentials: 'include'
                });
                
                const data = await response.json();
                
                // Check if the response has the expected structure
                if (data.status === 'success' && data.group) {
                    // Get members from the group object
                    const members = data.group.members || [];
                    
                    // Check if current user is the creator
                    const isCreator = data.group.created_by && 
                                     data.group.created_by._id === this.currentUser._id;
                    
                    this.renderGroupMembers(members, isCreator);
                } else {
                    alert('Error: ' + (data.message || 'Invalid response format'));
                }
            } catch (error) {
                console.error('Error loading group members:', error);
                alert('Failed to load members: ' + error.message);
            }
        }

            renderGroupMembers(members, isCreator) {
                const container = document.getElementById('groupMembersList');
                const currentUserId = this.currentUser._id;
                const html = members.map(member => {
                    const isCurrentUser = member._id === currentUserId;
                    const canRemove = isCreator && !isCurrentUser;
                    const isMemberCreator = member._id === members[0]?._id;
                    return `
                        <div class="member-item">
                            <div class="member-info">
                                <div class="member-avatar">${member.username.charAt(0).toUpperCase()}</div>
                                <div class="member-details">
                                    <strong>${this.escapeHtml(member.username)}</strong>
                                    <div>
                                        ${isMemberCreator ? '<span class="is-creator">Creator</span>' : ''}
                                        ${isCurrentUser ? '<span class="is-creator">You</span>' : ''}
                                    </div>
                                </div>
                            </div>
                            ${canRemove ? 
                                `<button onclick="chatApp.removeMember('${member._id}', '${this.escapeHtml(member.username)}')" 
                                        class="remove-member-btn">Remove</button>` 
                                : ''}
                        </div>
                    `;
                }).join('');
                container.innerHTML = html;
            }

           async removeMember(userId, username) {
    if (!confirm(`Remove ${username} from the group?`)) return;
    try {
        console.log("Removing member:", userId, "from group:", this.currentGroup._id);
        
        // Change this URL to call the deleteGroupMember method
        const response = await fetch(`<?php echo base_url("index.php/chat/deleteGroupMember/"); ?>${this.currentGroup._id}/${userId}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json' },
            credentials: 'include'
        });
        
        console.log("Remove member response status:", response.status);
        const responseText = await response.text();
        console.log("Remove member response text:", responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error("Failed to parse JSON:", e);
            throw new Error("Invalid response from server");
        }
        
        if (data.status === 'success') {
            alert('Member removed successfully');
            await this.loadGroupMembers();
            
            // Notify via socket
            this.socket.emit('group-notification', {
                groupId: this.currentGroup._id,
                message: `${username} has been removed from the group`,
                type: 'member_removed'
            });
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error removing member:', error);
        alert('Failed to remove member: ' + error.message);
    }
}
            async showAddMembersModal() {
                this.closeGroupMembersModal();
                this.membersToAdd = new Set();
                this.membersToAddMap = new Map();
                document.getElementById('addMembersModal').style.display = 'flex';
                await this.loadAvailableUsersForGroup();
            }

            async loadAvailableUsersForGroup() {
                try {
                    console.log("Loading non-members for group:", this.currentGroup._id);
                    
                    const response = await fetch(`<?php echo base_url("index.php/chat/apiProxy/groups/"); ?>${this.currentGroup._id}/non-members`, {
                        credentials: 'include',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    
                    console.log("Response status:", response.status);
                    
                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error("Error response:", errorText);
                        throw new Error(`HTTP ${response.status}: ${errorText}`);
                    }
                    
                    const data = await response.json();
                    console.log("Non-members data:", data);
                    
                    if (data.status === 'success') {
                        this.renderAvailableUsersForGroup(data.users || []);
                    } else {
                        console.error('API returned error:', data.message);
                        this.renderAvailableUsersForGroup([]);
                    }
                } catch (error) {
                    console.error('Error loading available users:', error);
                    this.renderAvailableUsersForGroup([]);
                }
            }

            renderAvailableUsersForGroup(users) {
                const container = document.getElementById('availableUsersForGroup');
                const html = users.map(user => {
                    const isSelected = this.membersToAdd && this.membersToAdd.has(user._id);
                    return `
                        <div class="user-select-item ${isSelected ? 'selected' : ''}" 
                             onclick="chatApp.toggleMemberToAdd('${user._id}', '${this.escapeHtml(user.username)}')">
                            <div class="avatar" style="width: 30px; height: 30px;">${user.username.charAt(0).toUpperCase()}</div>
                            <div class="user-info">
                                <strong>${this.escapeHtml(user.username)}</strong>
                                <p>${this.escapeHtml(user.email)}</p>
                            </div>
                            ${isSelected ? '<span class="selected-check">✓</span>' : ''}
                        </div>
                    `;
                }).join('');
                container.innerHTML = html;
                this.renderAddMembersSelectedList();
            }

            toggleMemberToAdd(userId, username) {
                if (!this.membersToAdd) {
                    this.membersToAdd = new Set();
                    this.membersToAddMap = new Map();
                }
                if (this.membersToAdd.has(userId)) {
                    this.membersToAdd.delete(userId);
                    this.membersToAddMap.delete(userId);
                } else {
                    this.membersToAdd.add(userId);
                    this.membersToAddMap.set(userId, username);
                }
                this.loadAvailableUsersForGroup();
                this.renderAddMembersSelectedList();
            }

            renderAddMembersSelectedList() {
                const container = document.getElementById('addMembersSelectedList');
                if (!this.membersToAdd || this.membersToAdd.size === 0) {
                    container.innerHTML = '<p style="color: #888; font-style: italic;">No members selected</p>';
                    return;
                }
                const selectedArray = Array.from(this.membersToAddMap.entries());
                container.innerHTML = selectedArray.map(([id, username]) => 
                    `<span class="selected-member">${this.escapeHtml(username)} 
                     <span onclick="chatApp.removeMemberFromAdd('${id}')" style="cursor: pointer; margin-left: 5px;">×</span></span>`
                ).join('');
            }

            removeMemberFromAdd(userId) {
                if (!this.membersToAdd) return;
                this.membersToAdd.delete(userId);
                this.membersToAddMap.delete(userId);
                this.renderAddMembersSelectedList();
                this.loadAvailableUsersForGroup();
            }

            async addSelectedMembers() {
    if (!this.membersToAdd || this.membersToAdd.size === 0) {
        alert('Please select at least one member to add');
        return;
    }
    
    try {
        console.log("Adding members to group:", this.currentGroup._id);
        console.log("Members to add:", Array.from(this.membersToAdd));
        
        const response = await fetch(`<?php echo base_url("index.php/chat/apiProxy/groups/"); ?>${this.currentGroup._id}/members`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ 
                memberIds: Array.from(this.membersToAdd)  // Note: Node.js expects "memberIds" not "members"
            })
        });
        
        console.log("Add members response status:", response.status);
        
        const responseText = await response.text();
        console.log("Add members response text:", responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error("Failed to parse JSON:", e);
            throw new Error("Invalid response from server");
        }
        
        if (data.status === 'success') {
            alert('Members added successfully!');
            this.closeAddMembersModal();
            await this.showGroupMembers(); // Reload members list
            
            // Notify group via socket
            this.socket.emit('group-notification', {
                groupId: this.currentGroup._id,
                message: 'New members have been added to the group',
                type: 'members_added'
            });
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error adding members:', error);
        alert('Failed to add members: ' + error.message);
    }
}

            // Group settings
            async showGroupSettings() {
                if (!this.currentGroup) return;
                document.getElementById('groupSettingsModal').style.display = 'flex';
                try {
                    const response = await fetch(`<?php echo base_url("index.php/chat/apiProxy/groups/"); ?>${this.currentGroup._id}`, {
                        credentials: 'include'
                    });
                    const data = await response.json();
                    if (data.group) {
                        document.getElementById('editGroupName').value = data.group.name || '';
                        document.getElementById('editGroupDescription').value = data.group.description || '';
                    }
                } catch (error) {
                    console.error('Error loading group info:', error);
                }
            }

            async updateGroupSettings() {
                const name = document.getElementById('editGroupName').value.trim();
                const description = document.getElementById('editGroupDescription').value.trim();
                if (!name) {
                    alert('Group name is required');
                    return;
                }
                try {
                    const response = await fetch(`<?php echo base_url("index.php/chat/apiProxy/groups/"); ?>${this.currentGroup._id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ name: name, description: description })
                    });
                    const data = await response.json();
                    if (data.status === 'success') {
                        alert('Group updated successfully!');
                        this.currentGroup.name = name;
                        document.getElementById('groupChatTitle').textContent = name;
                        this.closeGroupSettingsModal();
                        await this.loadGroups();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error updating group:', error);
                    alert('Failed to update group');
                }
            }

            // Delete group
            async deleteGroup() {
                if (!confirm('Are you sure you want to delete this group? This action cannot be undone.')) return;
                try {
                    const response = await fetch(`<?php echo base_url("index.php/chat/apiProxy/groups/"); ?>${this.currentGroup._id}`, {
                        method: 'DELETE',
                        credentials: 'include'
                    });
                    const data = await response.json();
                    if (data.status === 'success') {
                        alert('Group deleted successfully!');
                        this.closeGroupSettingsModal();
                        this.closeGroupChat();
                        await this.loadGroups();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error deleting group:', error);
                    alert('Failed to delete group');
                }
            }

            // Leave group
            async leaveGroup() {
                if (!confirm('Are you sure you want to leave this group?')) return;
                try {
                    const response = await fetch(`<?php echo base_url("index.php/chat/apiProxy/groups/"); ?>${this.currentGroup._id}/leave`, {
                        method: 'POST',
                        credentials: 'include'
                    });
                    const data = await response.json();
                    if (data.status === 'success') {
                        alert('You have left the group');
                        this.closeGroupChat();
                        await this.loadGroups();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error leaving group:', error);
                    alert('Failed to leave group');
                }
            }

            async loadUnreadChats() {
        try {
            const response = await fetch('<?php echo base_url("index.php/chat/apiProxy/messages/unread-grouped"); ?>', {
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Store unread chats
                    data.unread_chats.forEach(chat => {
                        this.unreadChats.set(chat.sender._id, chat);
                    });
                    
                    this.totalUnreadCount = data.total_unread || 0;
                    
                    // Update UI
                    this.updateUserListWithNotifications();
                    this.showUnreadChatsNotification();
                    this.updateNotificationBadges();
                }
            }
        } catch (error) {
            console.error('Error loading unread chats:', error);
        }
    }

    updateUserListWithNotifications() {
        // Update user items with notification badges
        document.querySelectorAll('.user-item').forEach(userItem => {
            const userId = userItem.dataset.userId;
            const chat = this.unreadChats.get(userId);
            
            if (chat) {
                // Add or update notification badge
                let badge = userItem.querySelector('.unread-badge');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'unread-badge';
                    userItem.appendChild(badge);
                }
                badge.textContent = chat.count;
                badge.style.display = 'inline-block';
                
                // Highlight the user item
                userItem.classList.add('has-unread');
            } else {
                // Remove notification badge if exists
                const badge = userItem.querySelector('.unread-badge');
                if (badge) {
                    badge.style.display = 'none';
                }
                userItem.classList.remove('has-unread');
            }
        });
    }

    setupNotificationListeners() {
        // Listen for new messages
        this.socket.on('receive-message', (message) => {
            if (message.receiver._id === this.currentUser._id) {
                this.handleIncomingNotification(message);
            }
        });

        // When we send a message, mark that chat as read
        this.socket.on('message-sent', (message) => {
            if (message.sender._id === this.currentUser._id) {
                this.markChatAsRead(message.receiver._id);
            }
        });
    }
    

    handleIncomingNotification(message) {
        const senderId = message.sender._id;
        
        // Add to unread chats
        if (this.unreadChats.has(senderId)) {
            const chat = this.unreadChats.get(senderId);
            chat.messages.push(message);
            chat.count++;
            chat.lastMessageTime = message.created_at;
        } else {
            this.unreadChats.set(senderId, {
                sender: message.sender,
                messages: [message],
                count: 1,
                lastMessageTime: message.created_at
            });
        }
        
        this.totalUnreadCount++;
        
        // Update UI
        this.updateUserListWithNotifications();
        this.updateNotificationBadges();
        
        // Show notification if not in that chat
        if (!this.currentChat || this.currentChat._id !== senderId) {
            this.showMessageNotification(message);
        }
    }

    async markChatAsRead(userId) {
        try {
            // Mark messages as read on server
            await fetch(`<?php echo base_url("index.php/chat/apiProxy/messages/"); ?>${userId}/read`, {
                method: 'POST',
                credentials: 'include'
            });
            
            // Remove from unread chats
            if (this.unreadChats.has(userId)) {
                const chat = this.unreadChats.get(userId);
                this.totalUnreadCount -= chat.count;
                this.unreadChats.delete(userId);
                
                // Update UI
                this.updateUserListWithNotifications();
                this.updateNotificationBadges();
            }
        } catch (error) {
            console.error('Error marking chat as read:', error);
        }
    }

    updateNotificationBadges() {
        // Update tab badge
        const usersTab = document.getElementById('usersTab');
        let tabBadge = usersTab.querySelector('.tab-notification-badge');
        
        if (this.totalUnreadCount > 0) {
            if (!tabBadge) {
                tabBadge = document.createElement('span');
                tabBadge.className = 'tab-notification-badge';
                usersTab.appendChild(tabBadge);
            }
            tabBadge.textContent = this.totalUnreadCount;
            tabBadge.style.display = 'inline-block';
        } else if (tabBadge) {
            tabBadge.style.display = 'none';
        }
        
        // Update page title
        if (this.totalUnreadCount > 0) {
            document.title = `(${this.totalUnreadCount}) Chat App`;
        } else {
            document.title = 'Chat App';
        }
    }

    showMessageNotification(message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'message-notification';
        notification.innerHTML = `
            <div class="notification-avatar">${message.sender.username.charAt(0).toUpperCase()}</div>
            <div class="notification-content">
                <strong>${this.escapeHtml(message.sender.username)}</strong>
                <p>${this.escapeHtml(message.message.length > 50 ? message.message.substring(0, 50) + '...' : message.message)}</p>
                <small>${new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</small>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        
        // Click to open chat
        notification.addEventListener('click', () => {
            this.openChat(message.sender._id, message.sender.username);
            notification.remove();
        });
        
        // Add to notification container
        const container = this.getNotificationContainer();
        container.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    showUnreadChatsNotification() {
        if (this.totalUnreadCount > 0) {
            // Create a summary notification
            const summary = document.createElement('div');
            summary.className = 'summary-notification';
            summary.innerHTML = `
                <div class="notification-icon">📨</div>
                <div class="notification-content">
                    <strong>You have ${this.totalUnreadCount} unread message${this.totalUnreadCount > 1 ? 's' : ''}</strong>
                    <p>${this.unreadChats.size} conversation${this.unreadChats.size > 1 ? 's' : ''} with unread messages</p>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
            `;
            
            const container = this.getNotificationContainer();
            container.insertBefore(summary, container.firstChild);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                if (summary.parentElement) {
                    summary.remove();
                }
            }, 3000);
        }
    }

    getNotificationContainer() {
        let container = document.getElementById('notificationContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notificationContainer';
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        return container;
    }

            // Close modals
            closeGroupMembersModal() {
                document.getElementById('groupMembersModal').style.display = 'none';
            }

            closeAddMembersModal() {
                document.getElementById('addMembersModal').style.display = 'none';
                if (this.membersToAdd) {
                    this.membersToAdd.clear();
                    this.membersToAddMap.clear();
                }
            }

            closeGroupSettingsModal() {
                document.getElementById('groupSettingsModal').style.display = 'none';
            }

            // ========== EVENT HANDLERS ==========
            handleIncomingMessage(message) {
                console.log('New message received from:', message.sender.username);
                if (message.sender._id === this.currentChat?._id) {
                    this.addMessageToUI(message, false);
                }
            }

            handleMessageSent(message) {
                console.log('Message sent:', message);
            }

            handleUserOnline(userId) {
                const statusDot = document.getElementById(`status-${userId}`);
                if (statusDot) {
                    statusDot.classList.remove('offline');
                }
                this.onlineUsers.add(userId);
            }

            handleUserOffline(userId) {
                const statusDot = document.getElementById(`status-${userId}`);
                if (statusDot) {
                    statusDot.classList.add('offline');
                }
                this.onlineUsers.delete(userId);
            }

            handleTypingIndicator(data) {
                if (data.sender === this.currentChat?._id) {
                    const indicator = document.getElementById('typingIndicator');
                    const typingUser = document.getElementById('typingUser');
                    typingUser.textContent = this.currentChat.username;
                    indicator.style.display = data.isTyping ? 'block' : 'none';
                }
            }

            handleSocketError(error) {
                console.error('Socket error:', error);
                alert('Connection error: ' + error);
            }

            startTyping() {
                if (!this.currentChat || this.isTyping) return;
                this.isTyping = true;
                this.socket.emit('typing', {
                    sender: this.currentUser._id,
                    receiver: this.currentChat._id,
                    isTyping: true
                });
            }

            stopTyping() {
                if (!this.currentChat || !this.isTyping) return;
                this.isTyping = false;
                this.socket.emit('typing', {
                    sender: this.currentUser._id,
                    receiver: this.currentChat._id,
                    isTyping: false
                });
            }

            setupEventListeners() {
                // Send message on button click
                document.getElementById('sendButton').addEventListener('click', () => {
                    this.sendMessage();
                });

                // Send message on Enter key
                document.getElementById('messageInput').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });

                // Typing indicator
                document.getElementById('messageInput').addEventListener('input', () => {
                    this.startTyping();
                    clearTimeout(this.typingTimeout);
                    this.typingTimeout = setTimeout(() => {
                        this.stopTyping();
                    }, 1000);
                });
            }

            scrollToBottom() {
                const container = document.getElementById('messagesContainer');
                container.scrollTop = container.scrollHeight;
            }

            scrollGroupToBottom() {
                const container = document.getElementById('groupMessagesContainer');
                container.scrollTop = container.scrollHeight;
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }

        // Initialize chat application
        const chatApp = new ChatApplication();
    </script>
</body>
</html>