<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends CI_Controller {

    private $api_base = "http://10.10.15.140:4590/api";

    public function __construct() {
        parent::__construct();
        $this->load->helper(['url', 'cookie']);
        $this->load->library('session');
    }

    // Index - redirect to login
    public function index() {
        redirect('chat/login');
    }

    // Login page
    public function login() {
        $this->load->view('login_form');
    }

    // Register page
    public function register() {
        $this->load->view('register_form');
    }

    // Dashboard (main chat interface)
    public function dashboard() {
        $token = $this->session->userdata('token');
        $user = $this->session->userdata('user');

        if (!$token || !$user) {
            redirect('chat/login');
            return;
        }

        $this->load->view('dashboard', [
            'token' => $token,
            'me'    => $user,
            'api_base' => $this->api_base
        ]);
    }

    // ========== UPDATED API PROXY METHOD ==========
    public function apiProxy($endpoint = '', $param1 = '', $param2 = '') {
        // Get token from session
        $token = $this->session->userdata('token');
        
        if (!$token) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            http_response_code(401);
            return;
        }
        
        // Build the complete API path
        $api_path = $endpoint;
        if ($param1) $api_path .= '/' . $param1;
        if ($param2) $api_path .= '/' . $param2;
        
        $url = $this->api_base . '/' . $api_path;
        
        $ch = curl_init($url);
        
        // Get HTTP method
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Prepare headers - USE BEARER TOKEN
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // For POST, PUT, PATCH - send request body
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $input = file_get_contents('php://input');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        header('Content-Type: application/json');
        http_response_code($http_code);
        echo $response;
    }

    // ========== SPECIFIC GROUP PROXY METHODS ==========
    
    // Create group (POST /groups)
    public function createGroupProxy() {
        $this->apiProxy('groups');
    }
    
    // Get user's groups (GET /my-groups)
    public function myGroupsProxy() {
        $this->apiProxy('my-groups');
    }
    
    // Get group messages (GET /groups/:id/messages)
    public function groupMessagesProxy($groupId = '') {
        $this->apiProxy('groups', $groupId, 'messages');
    }
    
    // Update group (PUT /groups/:id)
    public function updateGroupProxy($groupId = '') {
        $this->apiProxy('groups', $groupId);
    }
    
    // Add members (POST /groups/:id/members)
    public function addMembersProxy($groupId = '') {
        $this->apiProxy('groups', $groupId, 'members');
    }
    
    // Remove member (DELETE /groups/:id/members/:memberId)
    public function removeMemberProxy($groupId = '', $memberId = '') {
        $this->apiProxy('groups', $groupId, 'members/' . $memberId);
    }
    
    // Leave group (POST /groups/:id/leave)
    public function leaveGroupProxy($groupId = '') {
        $this->apiProxy('groups', $groupId, 'leave');
    }
    
    // Delete group (DELETE /groups/:id)
    public function deleteGroupProxy($groupId = '') {
        $this->apiProxy('groups', $groupId);
    }
    
    // Get non-members (GET /groups/:id/non-members)
    public function nonMembersProxy($groupId = '') {
        $this->apiProxy('groups', $groupId, 'non-members');
    }

    // ========== EXISTING METHODS ==========
    
    // Handle registration
    public function submit() {

        if ($this->input->post('password') !== $this->input->post('confirm_password')) {
        $this->load->view('register_form', ['error' => 'Passwords do not match']);
        return;
    }



        $data = [
            "first_name" => $this->input->post('first_name'),
            "last_name"  => $this->input->post('last_name'),
            "email"      => $this->input->post('email'),
            "username"   => $this->input->post('username'),
            "password"   => $this->input->post('password')
        ];

        $result = $this->api_request('/register', $data);
        
        if (isset($result['status']) && $result['status'] === 'success') {
            redirect('chat/login');
        } else {
            $error = $result['message'] ?? 'Registration failed';
            $this->load->view('register_form', ['error' => $error]);
        }
    }

    // Handle login
    public function loginSubmit() {
        $data = [
            "identifier" => $this->input->post('identity'),
            "password"   => $this->input->post('password')
        ];

        $result = $this->api_request('/login', $data);
        
        if (isset($result['status']) && $result['status'] === 'success') {
            // Store token and user in session
            $this->session->set_userdata([
                'token' => $result['token'],
                'user' => $result['user']
            ]);
            redirect('chat/dashboard');
        } else {
            $error = $result['message'] ?? 'Login failed';
            $this->load->view('login_form', ['error' => $error]);
        }
    }

    // Add this method to handle DELETE /groups/{id}/members/{memberId}
public function deleteGroupMember($groupId, $memberId) {
    // Get token from session
    $token = $this->session->userdata('token');
    
    if (!$token) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        http_response_code(401);
        return;
    }
    
    // Build the URL
    $url = $this->api_base . '/groups/' . $groupId . '/members/' . $memberId;
    
    $ch = curl_init($url);
    
    // Prepare headers
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    header('Content-Type: application/json');
    http_response_code($http_code);
    echo $response;
}

// Get unread messages grouped by sender
public function unreadGroupedProxy() {
    $this->apiProxy('messages', 'unread-grouped');
}

    // Logout
    public function logout() {
        $this->session->unset_userdata(['token', 'user']);
        redirect('chat/login');
    }

    // Helper method for API requests
    private function api_request($endpoint, $data = null, $method = 'POST') {
        $url = $this->api_base . $endpoint;
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}