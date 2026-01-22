<?php
header('Content-Type: application/json; charset=utf-8');

// Simple API for recipients/messages/send.
// No external dependencies. Stores messages in data/messages.json (array).

$DATA_DIR = __DIR__ . '/data';
$MESSAGES_FILE = $DATA_DIR . '/messages.json';

$recipients = [
  ['id' => 1, 'name' => 'Alice', 'phone' => '+33111111111'],
  ['id' => 2, 'name' => 'Bob', 'phone' => '+33122222222'],
  ['id' => 3, 'name' => 'Caroline', 'phone' => '+33133333333'],
  ['id' => 4, 'name' => 'David', 'phone' => '+33144444444'],
];

$action = $_GET['action'] ?? ($_POST['action'] ?? 'messages');

function ensure_data_dir($dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Read messages from JSON file (returns array)
function read_messages($file) {
    if (!file_exists($file)) return [];
    $text = @file_get_contents($file);
    if ($text === false) return [];
    $data = json_decode($text, true);
    return is_array($data) ? $data : [];
}

// Write messages array to file with lock
function write_messages($file, $messages) {
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $tmp = $file . '.tmp';
    $fp = fopen($tmp, 'w');
    if ($fp === false) return false;
    // exclusive lock while writing
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    fwrite($fp, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    rename($tmp, $file);
    return true;
}

// Helper to find recipient by id
function find_recipient($recipients, $id) {
    foreach ($recipients as $r) {
        if ($r['id'] == $id) return $r;
    }
    return null;
}

// Return JSON response and exit
function respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'recipients':
        respond($recipients);
        break;

    case 'messages':
        $messages = read_messages($MESSAGES_FILE);
        respond($messages);
        break;

    case 'send':
        // Read JSON body
        $body = file_get_contents('php://input');
        $json = json_decode($body, true);
        if (!is_array($json)) {
            http_response_code(400);
            respond(['error' => 'Corps JSON attendu.']);
        }
        $recipientIds = $json['recipientIds'] ?? null;
        $message = trim($json['message'] ?? '');

        if (!is_array($recipientIds) || empty($recipientIds) || $message === '') {
            http_response_code(400);
            respond(['error' => 'recipientIds (array) et message sont requis.']);
        }

        global $recipients, $MESSAGES_FILE;
        $chosen = [];
        foreach ($recipientIds as $id) {
            $r = find_recipient($recipients, $id);
            if ($r) $chosen[] = $r;
        }
        if (empty($chosen)) {
            http_response_code(400);
            respond(['error' => 'Aucun destinataire valide.']);
        }

        // load current messages
        $messages = read_messages($MESSAGES_FILE);

        $results = [];
        foreach ($chosen as $r) {
            $record = [
                'id' => uniqid('', true),
                'toId' => $r['id'],
                'toName' => $r['name'],
                'toPhone' => $r['phone'],
                'message' => $message,
                'status' => 'stored',
                'created_at' => date('c')
            ];
            // prepend newest first
            array_unshift($messages, $record);
            $results[] = $record;
        }

        // write back
        $ok = write_messages($MESSAGES_FILE, $messages);
        if (!$ok) {
            http_response_code(500);
            respond(['error' => 'Impossible d\'écrire le fichier de messages. Vérifie les permissions.']);
        }

        respond(['results' => $results]);
        break;

    default:
        http_response_code(404);
        respond(['error' => 'Action inconnue']);
}