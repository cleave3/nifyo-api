<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
            margin: 0;
        }

        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #218838;
        }

        .message {
            margin-bottom: 10px;
            color: green;
            display: none;
        }

        .error {
            margin-bottom: 10px;
            color: red;
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h3>Reset Password</h3>
        <div class="message" id="message"></div>
        <div class="error" id="error"></div>
        <form id="resetForm">
            <input type="hidden" id="token" value="{{ $token }}">
            <input type="password" id="new_password" placeholder="New Password" required>
            <input type="password" id="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Reset Password</button>
        </form>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const token = document.getElementById('token').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const messageEl = document.getElementById('message');
            const errorEl = document.getElementById('error');

            messageEl.style.display = 'none';
            errorEl.style.display = 'none';

            if (newPassword !== confirmPassword) {
                errorEl.textContent = "Passwords do not match!";
                errorEl.style.display = 'block';
                return;
            }

            try {
                const response = await fetch(`/api/v1/auth/password-reset-confirm/${token}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ new_password: newPassword, confirm_password: confirmPassword })
                });

                const data = await response.json();

                if (response.ok) {
                    messageEl.textContent = data.message || "Password reset successfully!";
                    messageEl.style.display = 'block';
                    document.getElementById('resetForm').reset();
                } else {
                    errorEl.textContent = data.message || "An error occurred.";
                    errorEl.style.display = 'block';
                }
            } catch (err) {
                errorEl.textContent = "Failed to connect to the server.";
                errorEl.style.display = 'block';
            }
        });
    </script>
</body>

</html>