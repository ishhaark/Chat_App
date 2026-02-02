<!DOCTYPE html>
<html>
<head>
    <title>Login</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Glass card */
        .glass-card {
            width: 100%;
            max-width: 420px;
            padding: 35px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(16px);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.35);
            color: #fff;
            animation: fadeUp 0.9s ease;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .glass-card h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .input-group {
            margin-bottom: 18px;
        }

        label {
            font-size: 13px;
            color: #e5e5e5;
            margin-bottom: 6px;
            display: block;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input::placeholder {
            color: #ddd;
        }

        input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.35);
            box-shadow: 0 0 0 2px rgba(79, 172, 254, 0.9);
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: #000;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 242, 254, 0.65);
        }

        .links {
            margin-top: 18px;
            text-align: center;
            font-size: 13px;
        }

        .links a {
            color: #4facfe;
            text-decoration: none;
            font-weight: 600;
        }

        .links a:hover {
            text-decoration: underline;
        }

        /* Mobile */
        @media (max-width: 480px) {
            .glass-card {
                padding: 25px;
            }
        }
    </style>
</head>

<body>

<div class="glass-card">
    <h2>Welcome Back</h2>

    <form method="post" action="<?php echo site_url('chat/loginSubmit'); ?>">

        <div class="input-group">
            <label>Email or Username</label>
            <input type="text" name="identity" placeholder="Enter email or username" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>

        <button type="submit" class="btn-login">Login</button>

    </form>

    <div class="links">
        Don’t have an account?
        <a href="<?php echo site_url('chat/register'); ?>">Register</a>
    </div>
</div>


</body>
</html>
