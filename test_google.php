<!DOCTYPE html>
<html>
<head>
    <title>Test reCAPTCHA v3</title>
    <script src="https://www.google.com/recaptcha/api.js?render=6LdgwRstAAAAAHfV28B5ezVRDJFHcqtRiqJSlfae"></script>
</head>
<body>
    <h2>reCAPTCHA v3 Test</h2>
    <button onclick="test()">Click to Test</button>
    <div id="result"></div>
    
    <script>
        function test() {
            grecaptcha.ready(function() {
                grecaptcha.execute('6LdgwRstAAAAAHfV28B5ezVRDJFHcqtRiqJSlfae', {action: 'test'}).then(function(token) {
                    document.getElementById('result').innerHTML = 'Token received: ' + token.substring(0, 50) + '...';
                    
                    // Send to server
                    fetch('verify_token.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'token=' + token
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('result').innerHTML += '<br>Result: ' + JSON.stringify(data);
                    });
                });
            });
        }
    </script>
</body>
</html>