<?php
header('Content-Type: application/json');
http_response_code(410);
echo json_encode(['success' => false, 'error' => 'Configure SMTP through the server environment or private .env file. Web-based credential editing is disabled.']);
